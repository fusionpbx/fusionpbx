<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2010-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	BlueCloud <support@blueuc.com>
*/

//define the acd class
	class acd {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

		/**
		 * declare public variables
		 */
		public $queue_uuid;
		public $app_table   = 'v_acd_queues';
		public $app_prefix  = 'queue';
		public $member_table  = 'v_acd_queue_members';
		public $session_table = 'v_acd_sessions';

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name         = 'acd';
				$this->app_uuid         = 'c8e2f4a6-b0d2-4e6f-8a0c-2e4f6a8c0e2f';
				$this->permission_prefix = 'acd_';
				$this->list_page        = 'acd.php';
				$this->table            = 'acd_queues';
				$this->uuid_prefix      = 'queue_';
				$this->toggle_field     = 'queue_enabled';
				$this->toggle_values    = ['true', 'false'];

		}

		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'], 'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked queues, build where clause for below
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary queue details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select queue_uuid as uuid, dialplan_uuid, queue_context from v_acd_queues ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and queue_uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$queues[$row['uuid']]['dialplan_uuid']  = $row['dialplan_uuid'];
										$queue_contexts[] = $row['queue_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//delete using raw SQL for custom tables (PK naming convention mismatch)
						//and $database->delete() for standard FusionPBX tables
							if (is_array($queues) && @sizeof($queues) != 0) {

								//grant temporary permissions for dialplan tables
									$p = permissions::new();
									$p->add('dialplan_delete', 'temp');
									$p->add('dialplan_detail_delete', 'temp');

								$database = new database;
								$database->app_name = $this->app_name;
								$database->app_uuid = $this->app_uuid;

								foreach ($queues as $queue_uuid => $queue) {
									//delete sessions (raw SQL — custom table)
									$database->execute(
										"DELETE FROM v_acd_sessions WHERE queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid",
										['queue_uuid' => $queue_uuid, 'domain_uuid' => $_SESSION['domain_uuid']]
									);
									//delete members (raw SQL — custom table)
									$database->execute(
										"DELETE FROM v_acd_queue_members WHERE queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid",
										['queue_uuid' => $queue_uuid, 'domain_uuid' => $_SESSION['domain_uuid']]
									);
									//delete queue (raw SQL — custom table)
									$database->execute(
										"DELETE FROM v_acd_queues WHERE queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid",
										['queue_uuid' => $queue_uuid, 'domain_uuid' => $_SESSION['domain_uuid']]
									);
									//delete dialplan details and dialplan ($database->delete() — standard tables)
									if (!empty($queue['dialplan_uuid']) && is_uuid($queue['dialplan_uuid'])) {
										$dp_array['dialplan_details'][0]['dialplan_uuid'] = $queue['dialplan_uuid'];
										$dp_array['dialplans'][0]['dialplan_uuid'] = $queue['dialplan_uuid'];
										$database->delete($dp_array);
										unset($dp_array);
									}
								}

								//revoke temporary permissions
									$p->delete('acd_session_delete', 'temp');
									$p->delete('acd_queue_member_delete', 'temp');
									$p->delete('dialplan_delete', 'temp');
									$p->delete('dialplan_detail_delete', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($queue_contexts) && @sizeof($queue_contexts) != 0) {
										$queue_contexts = array_unique($queue_contexts);
										$cache = new cache;
										foreach ($queue_contexts as $queue_context) {
											$cache->delete("dialplan:".$queue_context);
										}
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * toggle records
		 */
		public function toggle($records) {
			if (permission_exists($this->permission_prefix.'edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'], 'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state
							foreach ($records as $x => $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select queue_uuid as uuid, queue_enabled as toggle, dialplan_uuid, queue_context from v_acd_queues ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and queue_uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$queues[$row['uuid']]['state']        = $row['toggle'];
										$queues[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
										$queue_contexts[] = $row['queue_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//toggle queue and dialplan enabled state
							if (is_array($queues) && @sizeof($queues) != 0) {

								//grant temporary permissions
									$p = permissions::new();
									$p->add('dialplan_edit', 'temp');

								$database = new database;
								$database->app_name = $this->app_name;
								$database->app_uuid = $this->app_uuid;

								$x = 0;
								foreach ($queues as $uuid => $queue) {
									$new_state = ($queue['state'] == $this->toggle_values[0]) ? $this->toggle_values[1] : $this->toggle_values[0];

									//toggle queue (raw SQL — custom table)
									$database->execute(
										"UPDATE v_acd_queues SET queue_enabled = :state, update_date = now() WHERE queue_uuid = :uuid AND domain_uuid = :domain_uuid",
										['state' => $new_state, 'uuid' => $uuid, 'domain_uuid' => $_SESSION['domain_uuid']]
									);

									//toggle dialplan ($database->save() — standard table)
									$array['dialplans'][$x]['dialplan_uuid']    = $queue['dialplan_uuid'];
									$array['dialplans'][$x]['dialplan_enabled'] = $new_state;
									$x++;
								}

								if (!empty($array)) {
									$database->save($array);
									unset($array);
								}

								//revoke temporary permissions
									$p->delete('dialplan_edit', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($queue_contexts) && @sizeof($queue_contexts) != 0) {
										$queue_contexts = array_unique($queue_contexts);
										$cache = new cache;
										foreach ($queue_contexts as $queue_context) {
											$cache->delete("dialplan:".$queue_context);
										}
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}

			}
		}

		/**
		 * copy records
		 */
		public function copy($records) {
			if (permission_exists($this->permission_prefix.'add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'], 'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								//primary table
									$sql = "select * from v_acd_queues ";
									$sql .= "where domain_uuid = :domain_uuid ";
									$sql .= "and queue_uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										$y = 0;
										foreach ($rows as $x => $row) {
											$new_queue_uuid    = uuid();
											$new_dialplan_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x]['queue_uuid']        = $new_queue_uuid;
												$array[$this->table][$x]['dialplan_uuid']      = $new_dialplan_uuid;
												$array[$this->table][$x]['queue_name']         = trim($row['queue_name'].' ('.$text['label-copy'].')');
												$array[$this->table][$x]['queue_extension']    = $row['queue_extension'].'_copy';
												$array[$this->table][$x]['queue_description']  = trim($row['queue_description'].' ('.$text['label-copy'].')');

											//members sub table
												$sql_2 = "select * from v_acd_queue_members where queue_uuid = :queue_uuid";
												$parameters_2['queue_uuid'] = $row['queue_uuid'];
												$database_2 = new database;
												$rows_2 = $database_2->select($sql_2, $parameters_2, 'all');
												if (is_array($rows_2) && @sizeof($rows_2) != 0) {
													foreach ($rows_2 as $row_2) {

														//copy data
															$array['acd_queue_members'][$y] = $row_2;

														//overwrite
															$array['acd_queue_members'][$y]['queue_member_uuid'] = uuid();
															$array['acd_queue_members'][$y]['queue_uuid']        = $new_queue_uuid;

														//increment
															$y++;

													}
												}
												unset($sql_2, $parameters_2, $rows_2, $row_2);

											//dialplan record
												$sql_3 = "select * from v_dialplans where dialplan_uuid = :dialplan_uuid";
												$parameters_3['dialplan_uuid'] = $row['dialplan_uuid'];
												$database_3 = new database;
												$dialplan = $database_3->select($sql_3, $parameters_3, 'row');
												if (is_array($dialplan) && @sizeof($dialplan) != 0) {

													//copy data
														$array['dialplans'][$x] = $dialplan;

													//overwrite
														$array['dialplans'][$x]['dialplan_uuid'] = $new_dialplan_uuid;
														$dialplan_xml = $dialplan['dialplan_xml'];
														$dialplan_xml = str_replace($row['queue_uuid'], $new_queue_uuid, $dialplan_xml);
														$dialplan_xml = str_replace($dialplan['dialplan_uuid'], $new_dialplan_uuid, $dialplan_xml);
														$array['dialplans'][$x]['dialplan_xml']         = $dialplan_xml;
														$array['dialplans'][$x]['dialplan_name']        = 'queue_'.trim($array[$this->table][$x]['queue_name']);
														$array['dialplans'][$x]['dialplan_number']      = $array[$this->table][$x]['queue_extension'];
														$array['dialplans'][$x]['dialplan_description'] = trim($dialplan['dialplan_description'].' ('.$text['label-copy'].')');

												}
												unset($sql_3, $parameters_3, $dialplan);

											//create queue context array
												$queue_contexts[] = $row['queue_context'];
										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = permissions::new();
									$p->add('dialplan_add', 'temp');

								$database = new database;
								$database->app_name = $this->app_name;
								$database->app_uuid = $this->app_uuid;

								//save custom tables with raw SQL, standard tables with $database->save()
								$dp_array = [];
								foreach ($array[$this->table] ?? [] as $x => $q) {
									//insert queue (raw SQL)
									$database->execute(
										"INSERT INTO v_acd_queues "
										."(queue_uuid, domain_uuid, queue_name, queue_extension, queue_context, "
										."queue_hold_music, queue_cid_name_prefix, queue_announce_position, "
										."queue_announce_interval, queue_tier_advance_seconds, queue_ring_lower_tiers, "
										."queue_timeout, queue_timeout_app, queue_timeout_data, queue_enabled, "
										."queue_description, dialplan_uuid, insert_date) "
										."VALUES (:queue_uuid, :domain_uuid, :queue_name, :queue_extension, :queue_context, "
										.":queue_hold_music, :queue_cid_name_prefix, :queue_announce_position, "
										.":queue_announce_interval, :queue_tier_advance_seconds, :queue_ring_lower_tiers, "
										.":queue_timeout, :queue_timeout_app, :queue_timeout_data, :queue_enabled, "
										.":queue_description, :dialplan_uuid, now())",
										[
											'queue_uuid' => $q['queue_uuid'], 'domain_uuid' => $q['domain_uuid'],
											'queue_name' => $q['queue_name'], 'queue_extension' => $q['queue_extension'],
											'queue_context' => $q['queue_context'], 'queue_hold_music' => $q['queue_hold_music'] ?? '',
											'queue_cid_name_prefix' => $q['queue_cid_name_prefix'] ?? '',
											'queue_announce_position' => $q['queue_announce_position'] ?? 'false',
											'queue_announce_interval' => $q['queue_announce_interval'] ?? '30',
											'queue_tier_advance_seconds' => $q['queue_tier_advance_seconds'] ?? '20',
											'queue_ring_lower_tiers' => $q['queue_ring_lower_tiers'] ?? 'true',
											'queue_timeout' => $q['queue_timeout'] ?? '0',
											'queue_timeout_app' => $q['queue_timeout_app'] ?? '',
											'queue_timeout_data' => $q['queue_timeout_data'] ?? '',
											'queue_enabled' => $q['queue_enabled'] ?? 'true',
											'queue_description' => $q['queue_description'] ?? '',
											'dialplan_uuid' => $q['dialplan_uuid'],
										]
									);
									//collect dialplan for $database->save()
									if (isset($array['dialplans'][$x])) {
										$dp_array['dialplans'][$x] = $array['dialplans'][$x];
									}
								}
								//insert members (raw SQL)
								foreach ($array['acd_queue_members'] ?? [] as $m) {
									$database->execute(
										"INSERT INTO v_acd_queue_members "
										."(queue_member_uuid, queue_uuid, domain_uuid, queue_member_number, "
										."queue_member_tier, queue_member_honor_follow_me, queue_member_busy_handling, "
										."queue_member_enabled, insert_date) "
										."VALUES (:queue_member_uuid, :queue_uuid, :domain_uuid, :queue_member_number, "
										.":queue_member_tier, :queue_member_honor_follow_me, :queue_member_busy_handling, "
										.":queue_member_enabled, now())",
										$m
									);
								}
								//save dialplans via $database->save() (standard table)
								if (!empty($dp_array)) {
									$database->save($dp_array);
								}
								unset($array, $dp_array);

								//revoke temporary permissions
									$p->delete('acd_queue_member_add', 'temp');
									$p->delete('dialplan_add', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($queue_contexts) && @sizeof($queue_contexts) != 0) {
										$queue_contexts = array_unique($queue_contexts);
										$cache = new cache;
										foreach ($queue_contexts as $queue_context) {
											$cache->delete("dialplan:".$queue_context);
										}
									}

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		}

		/**
		 * save a queue and its members, write dialplan
		 */
		public function save($queue, $members) {
			global $database;

			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//determine if this is an insert or update
				$is_new = empty($queue['queue_uuid']) || !is_uuid($queue['queue_uuid']);
				if ($is_new) {
					$queue['queue_uuid'] = uuid();
				}
				$queue_uuid = $queue['queue_uuid'];

			//ensure domain_uuid is set
				if (empty($queue['domain_uuid'])) {
					$queue['domain_uuid'] = $_SESSION['domain_uuid'];
				}

			//ensure queue_context is set
				if (empty($queue['queue_context'])) {
					$queue['queue_context'] = $_SESSION['domain_name'];
				}

			//generate or preserve dialplan_uuid
				$dialplan_uuid = '';
				if (!empty($queue['dialplan_uuid']) && is_uuid($queue['dialplan_uuid'])) {
					$dialplan_uuid = $queue['dialplan_uuid'];
				}
				else {
					//look it up
					$sql = "select dialplan_uuid from v_acd_queues where queue_uuid = :queue_uuid and domain_uuid = :domain_uuid";
					$params = ['queue_uuid' => $queue_uuid, 'domain_uuid' => $queue['domain_uuid']];
					$existing_dialplan_uuid = $database->select($sql, $params, 'column');
					$dialplan_uuid = (!empty($existing_dialplan_uuid) && is_uuid($existing_dialplan_uuid))
						? $existing_dialplan_uuid
						: uuid();
				}
				$queue['dialplan_uuid'] = $dialplan_uuid;

			//build dialplan XML
				$queue_name      = $queue['queue_name'] ?? '';
				$queue_extension = $queue['queue_extension'] ?? '';
				$queue_context   = $queue['queue_context'];

				$dialplan_xml  = "<extension name=\"queue_".xml::sanitize($queue_name)."\" continue=\"false\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
				$dialplan_xml .= "  <condition field=\"destination_number\" expression=\"^".xml::sanitize($queue_extension)."$\">\n";
				$dialplan_xml .= "    <action application=\"set\" data=\"queue_uuid=".xml::sanitize($queue_uuid)."\"/>\n";
				$dialplan_xml .= "    <action application=\"lua\" data=\"app/acd/acd.lua\"/>\n";
				$dialplan_xml .= "  </condition>\n";
				$dialplan_xml .= "</extension>";

			//save queue record with raw SQL (can't use $database->save() because
			//it derives PK column as singular(table)_uuid = acd_queue_uuid
			//but our actual PK column is queue_uuid)
				if ($is_new) {
					$database->execute(
						"INSERT INTO v_acd_queues "
						."(queue_uuid, domain_uuid, queue_name, queue_extension, queue_context, "
						."queue_hold_music, queue_cid_name_prefix, queue_announce_position, "
						."queue_announce_interval, queue_tier_advance_seconds, queue_ring_lower_tiers, "
						."queue_timeout, queue_timeout_app, queue_timeout_data, queue_enabled, "
						."queue_description, dialplan_uuid, insert_date) "
						."VALUES (:queue_uuid, :domain_uuid, :queue_name, :queue_extension, :queue_context, "
						.":queue_hold_music, :queue_cid_name_prefix, :queue_announce_position, "
						.":queue_announce_interval, :queue_tier_advance_seconds, :queue_ring_lower_tiers, "
						.":queue_timeout, :queue_timeout_app, :queue_timeout_data, :queue_enabled, "
						.":queue_description, :dialplan_uuid, now())",
						$queue
					);
				}
				else {
					$database->execute(
						"UPDATE v_acd_queues SET "
						."queue_name = :queue_name, queue_extension = :queue_extension, "
						."queue_context = :queue_context, queue_hold_music = :queue_hold_music, "
						."queue_cid_name_prefix = :queue_cid_name_prefix, "
						."queue_announce_position = :queue_announce_position, "
						."queue_announce_interval = :queue_announce_interval, "
						."queue_tier_advance_seconds = :queue_tier_advance_seconds, "
						."queue_ring_lower_tiers = :queue_ring_lower_tiers, "
						."queue_timeout = :queue_timeout, queue_timeout_app = :queue_timeout_app, "
						."queue_timeout_data = :queue_timeout_data, queue_enabled = :queue_enabled, "
						."queue_description = :queue_description, dialplan_uuid = :dialplan_uuid, "
						."update_date = now() "
						."WHERE queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid",
						$queue
					);
				}

			//dialplan record
				$array['dialplans'][0]['domain_uuid']          = $queue['domain_uuid'];
				$array['dialplans'][0]['dialplan_uuid']         = $dialplan_uuid;
				$array['dialplans'][0]['dialplan_name']         = 'queue_'.$queue_name;
				$array['dialplans'][0]['dialplan_number']       = $queue_extension;
				$array['dialplans'][0]['dialplan_context']      = $queue_context;
				$array['dialplans'][0]['dialplan_continue']     = 'false';
				$array['dialplans'][0]['dialplan_order']        = 350;
				$array['dialplans'][0]['dialplan_enabled']      = $queue['queue_enabled'] ?? 'true';
				$array['dialplans'][0]['dialplan_description']  = 'Advanced Call Distribution queue: '.$queue_name;
				$array['dialplans'][0]['dialplan_xml']          = $dialplan_xml;
				$array['dialplans'][0]['app_uuid']              = $this->app_uuid;

			//dialplan detail records
				$detail_condition_uuid = uuid();
				$detail_action1_uuid   = uuid();
				$detail_action2_uuid   = uuid();

				$array['dialplan_details'][0]['domain_uuid']             = $queue['domain_uuid'];
				$array['dialplan_details'][0]['dialplan_uuid']           = $dialplan_uuid;
				$array['dialplan_details'][0]['dialplan_detail_uuid']    = $detail_condition_uuid;
				$array['dialplan_details'][0]['dialplan_detail_tag']     = 'condition';
				$array['dialplan_details'][0]['dialplan_detail_type']    = 'destination_number';
				$array['dialplan_details'][0]['dialplan_detail_data']    = '^'.$queue_extension.'$';
				$array['dialplan_details'][0]['dialplan_detail_group']   = 0;
				$array['dialplan_details'][0]['dialplan_detail_order']   = 5;
				$array['dialplan_details'][0]['dialplan_detail_enabled'] = 'true';

				$array['dialplan_details'][1]['domain_uuid']             = $queue['domain_uuid'];
				$array['dialplan_details'][1]['dialplan_uuid']           = $dialplan_uuid;
				$array['dialplan_details'][1]['dialplan_detail_uuid']    = $detail_action1_uuid;
				$array['dialplan_details'][1]['dialplan_detail_tag']     = 'action';
				$array['dialplan_details'][1]['dialplan_detail_type']    = 'set';
				$array['dialplan_details'][1]['dialplan_detail_data']    = 'queue_uuid='.$queue_uuid;
				$array['dialplan_details'][1]['dialplan_detail_group']   = 0;
				$array['dialplan_details'][1]['dialplan_detail_order']   = 10;
				$array['dialplan_details'][1]['dialplan_detail_enabled'] = 'true';

				$array['dialplan_details'][2]['domain_uuid']             = $queue['domain_uuid'];
				$array['dialplan_details'][2]['dialplan_uuid']           = $dialplan_uuid;
				$array['dialplan_details'][2]['dialplan_detail_uuid']    = $detail_action2_uuid;
				$array['dialplan_details'][2]['dialplan_detail_tag']     = 'action';
				$array['dialplan_details'][2]['dialplan_detail_type']    = 'lua';
				$array['dialplan_details'][2]['dialplan_detail_data']    = 'app/acd/acd.lua';
				$array['dialplan_details'][2]['dialplan_detail_group']   = 0;
				$array['dialplan_details'][2]['dialplan_detail_order']   = 15;
				$array['dialplan_details'][2]['dialplan_detail_enabled'] = 'true';

			//grant temporary permissions
				$p = permissions::new();
				$p->add('acd_queue_add', 'temp');
				$p->add('acd_queue_edit', 'temp');
				$p->add('dialplan_add', 'temp');
				$p->add('dialplan_edit', 'temp');
				$p->add('dialplan_detail_add', 'temp');
				$p->add('dialplan_detail_edit', 'temp');
				$p->add('acd_queue_member_add', 'temp');
				$p->add('acd_queue_member_edit', 'temp');
				$p->add('acd_queue_member_delete', 'temp');

			//delete existing members with raw SQL before the save transaction opens
				//using execute() directly avoids nested transaction conflicts
				$database->execute(
					"DELETE FROM v_acd_queue_members WHERE queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid",
					['queue_uuid' => $queue_uuid, 'domain_uuid' => $queue['domain_uuid']]
				);

			//delete existing dialplan_details so they don't accumulate on every save.
				//$database->save() below will INSERT fresh rows (it doesn't know to update
				//or delete the old ones because we re-generate dialplan_detail_uuid each
				//time). Routing itself reads dialplan_xml from v_dialplans so duplication
				//didn't break calls, but it bloated the table and broke the dialplan UI.
				//Raw SQL bypasses FusionPBX's per-row permission checks (we don't want to
				//temp-grant dialplan_detail_delete just for housekeeping our own rows).
				$database->execute(
					"DELETE FROM v_dialplan_details WHERE dialplan_uuid = :dialplan_uuid AND domain_uuid = :domain_uuid",
					['dialplan_uuid' => $dialplan_uuid, 'domain_uuid' => $queue['domain_uuid']]
				);

			//save dialplan and dialplan_details via $database->save() (standard FusionPBX tables)
				$database->app_name = $this->app_name;
				$database->app_uuid = $this->app_uuid;
				$database->save($array);
				unset($array);

			//insert members with raw SQL (same PK naming issue as queue table)
				if (is_array($members) && @sizeof($members) != 0) {
					foreach ($members as $member) {
						if (empty($member['queue_member_uuid']) || !is_uuid($member['queue_member_uuid'])) {
							$member['queue_member_uuid'] = uuid();
						}
						$member['queue_uuid']  = $queue_uuid;
						$member['domain_uuid'] = $queue['domain_uuid'];
						$database->execute(
							"INSERT INTO v_acd_queue_members "
							."(queue_member_uuid, queue_uuid, domain_uuid, queue_member_number, "
							."queue_member_tier, queue_member_honor_follow_me, queue_member_busy_handling, "
							."queue_member_enabled, insert_date) "
							."VALUES (:queue_member_uuid, :queue_uuid, :domain_uuid, :queue_member_number, "
							.":queue_member_tier, :queue_member_honor_follow_me, :queue_member_busy_handling, "
							.":queue_member_enabled, now())",
							$member
						);
					}
				}

			//revoke temporary permissions
				$p->delete('dialplan_add', 'temp');
				$p->delete('dialplan_edit', 'temp');
				$p->delete('dialplan_detail_add', 'temp');
				$p->delete('dialplan_detail_edit', 'temp');
				$p->delete('acd_queue_member_add', 'temp');
				$p->delete('acd_queue_member_edit', 'temp');
				$p->delete('acd_queue_member_delete', 'temp');

			//apply settings reminder
				$_SESSION["reload_xml"] = true;

			//clear the cache
				$cache = new cache;
				$cache->delete("dialplan:".$queue_context);

			//clear the destinations session array
				if (isset($_SESSION['destinations']['array'])) {
					unset($_SESSION['destinations']['array']);
				}

			//reload XML
				$esl = new event_socket;
				if ($esl->connect()) {
					$esl->request('api reloadxml');
				}

			return $queue_uuid;
		}

	}
