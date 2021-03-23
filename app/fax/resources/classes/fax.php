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
 Portions created by the Initial Developer are Copyright (C) 2008 - 2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the fax class
if (!class_exists('fax')) {
	class fax {

		/**
		* define the variables
		*/
		public $domain_uuid;
		public $fax_uuid;
		public $dialplan_uuid;
		public $fax_name;
		public $fax_description;
		public $fax_extension;
		public $fax_forward_number;
		public $destination_number;
		public $box;
		private $forward_prefix;

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
		* Called when the object is created
		*/
		public function __construct() {

			//assign private variables
				$this->app_name = 'fax';
				$this->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';

		}

		/**
		* Called when there are no references to a particular object
		* unset the variables used in the class
		*/
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		* Add a dialplan for call center
		* @var string $domain_uuid		the multi-tenant id
		* @var string $value	string to be cached
		*/
		public function dialplan() {

			//normalize the fax forward number
				if (strlen($this->fax_forward_number) > 3) {
					//$fax_forward_number = preg_replace("~[^0-9]~", "",$fax_forward_number);
					$this->fax_forward_number = str_replace(" ", "", $this->fax_forward_number);
					$this->fax_forward_number = str_replace("-", "", $this->fax_forward_number);
				}

			//set the forward prefix
				if (strripos($this->fax_forward_number, '$1') === false) {
					$this->forward_prefix = ''; //not found
				} else {
					$this->forward_prefix = $this->forward_prefix.$this->fax_forward_number.'#'; //found
				}

			//set the dialplan_uuid
				if (strlen($this->dialplan_uuid) == 0) {
					$this->dialplan_uuid = uuid();
				}
				else {
					//build previous details delete array
						$array['dialplan_details'][0]['dialplan_uuid'] = $this->dialplan_uuid;
						$array['dialplan_details'][0]['domain_uuid'] = $this->domain_uuid;

					//grant temporary permissions
						$p = new permissions;
						$p->add('dialplan_detail_delete', 'temp');

					//execute delete
						$database = new database;
						$database->app_name = 'fax';
						$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
						$database->delete($array);
						unset($array);

					//revoke temporary permissions
						$p->delete('dialplan_detail_delete', 'temp');
				}

			//set the fax name
				$fax_name = ($this->fax_name != '') ? $this->fax_name : format_phone($this->destination_number);

			//set the  last fax
				if (strlen($_SESSION['fax']['last_fax']['text']) > 0) {
					$last_fax = "last_fax=".$_SESSION['fax']['last_fax']['text'];
				}
				else {
					$last_fax = "last_fax=\${caller_id_number}-\${strftime(%Y-%m-%d-%H-%M-%S)}";
				}

			//set the rx_fax
				$rxfax_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'].'/'.$this->fax_extension.'/inbox/'.$this->forward_prefix.'${last_fax}.tif';

			//build the xml dialplan
				$dialplan_xml = "<extension name=\"".$fax_name ."\" continue=\"false\" uuid=\"".$this->dialplan_uuid."\">\n";
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$this->destination_number."$\">\n";
				$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"fax_uuid=".$this->fax_uuid."\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"api_hangup_hook=lua app/fax/resources/scripts/hangup_rx.lua\"/>\n";
				foreach($_SESSION['fax']['variable'] as $data) {
					if (substr($data,0,8) == "inbound:") {
						$dialplan_xml .= "		<action application=\"set\" data=\"".substr($data,8,strlen($data))."\"/>\n";
					}
					elseif (substr($data,0,9) == "outbound:") {}
					else {
						$dialplan_xml .= "		<action application=\"set\" data=\"".$data."\"/>\n";
					}
				}
				$dialplan_xml .= "		<action application=\"set\" data=\"".$last_fax."\"/>\n";
				$dialplan_xml .= "		<action application=\"playback\" data=\"silence_stream://2000\"/>\n";
				$dialplan_xml .= "		<action application=\"rxfax\" data=\"$rxfax_data\"/>\n";
				$dialplan_xml .= "		<action application=\"hangup\" data=\"\"/>\n";
				$dialplan_xml .= "	</condition>\n";
				$dialplan_xml .= "</extension>\n";

			//build the dialplan array
				$dialplan["app_uuid"] = "24108154-4ac3-1db6-1551-4731703a4440";
				$dialplan["domain_uuid"] = $this->domain_uuid;
				$dialplan["dialplan_uuid"] = $this->dialplan_uuid;
				$dialplan["dialplan_name"] = ($this->fax_name != '') ? $this->fax_name : format_phone($this->destination_number);
				$dialplan["dialplan_number"] = $this->fax_extension;
				$dialplan["dialplan_context"] = $_SESSION['domain_name'];
				$dialplan["dialplan_continue"] = "false";
				$dialplan["dialplan_xml"] = $dialplan_xml;
				$dialplan["dialplan_order"] = "310";
				$dialplan["dialplan_enabled"] = "true";
				$dialplan["dialplan_description"] = $this->fax_description;
				$dialplan_detail_order = 10;

			//prepare the array
				$array['dialplans'][] = $dialplan;

			//add the dialplan permission
				$p = new permissions;
				$p->add("dialplan_add", 'temp');
				$p->add("dialplan_detail_add", 'temp');
				$p->add("dialplan_edit", 'temp');
				$p->add("dialplan_detail_edit", 'temp');

			//save the dialplan
				$database = new database;
				$database->app_name = 'fax';
				$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
				$database->save($array);
				//$message = $database->message;

			//remove the temporary permission
				$p->delete("dialplan_add", 'temp');
				$p->delete("dialplan_detail_add", 'temp');
				$p->delete("dialplan_edit", 'temp');
				$p->delete("dialplan_detail_edit", 'temp');

			//clear the cache
				$cache = new cache;
				$cache->delete("dialplan:".$_SESSION['domain_name']);

			//return the dialplan_uuid
				return $dialplan_response;

		}

		/**
		* delete records
		*/
		public function delete($records) {

			//set private variables
				$this->permission_prefix = 'fax_extension_';
				$this->list_page = 'fax.php';
				$this->table = 'fax';
				$this->uuid_prefix = 'fax_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked fax extensions, build where clause for below
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary fax details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, dialplan_uuid from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$faxes[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//get necessary fax file details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select fax_file_uuid as uuid, fax_mode, fax_file_path, fax_file_type from v_fax_files ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										if ($row['fax_mode'] == 'rx') { $fax_files[$row['uuid']]['folder'] = 'inbox'; }
										if ($row['fax_mode'] == 'tx') { $fax_files[$row['uuid']]['folder'] = 'sent'; }
										$fax_files[$row['uuid']]['path'] = $row['fax_file_path'];
										$fax_files[$row['uuid']]['type'] = $row['fax_file_type'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//delete fax file(s)
							if (is_array($fax_files) && @sizeof($fax_files) != 0) {
								foreach ($fax_files as $fax_file_uuid => $fax_file) {
									if (substr_count($fax_file['path'], '/temp/') > 0) {
										$fax_file['path'] = str_replace('/temp/', '/'.$fax_file['type'].'/', $fax_file['path']);
									}
									if (file_exists($fax_file['path'])) {
										@unlink($fax_file['path']);
									}
									if ($fax_file['type'] == 'tif') {
										$fax_file['path'] = str_replace('.tif', '.pdf', $fax_file['path']);
										if (file_exists($fax_file['path'])) {
											@unlink($fax_file['path']);
										}
									}
									else if ($fax_file['type'] == 'pdf') {
										$fax_file['path'] = str_replace('.pdf', '.tif', $fax_file['path']);
										if (file_exists($fax_file['path'])) {
											@unlink($fax_file['path']);
										}
									}
								}
							}

						//build the delete array
							$x = 0;
							foreach ($faxes as $fax_uuid => $fax) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $fax_uuid;
								$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['fax_users'][$x][$this->uuid_prefix.'uuid'] = $fax_uuid;
								$array['fax_users'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['fax_files'][$x][$this->uuid_prefix.'uuid'] = $fax_uuid;
								$array['fax_files'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['fax_logs'][$x][$this->uuid_prefix.'uuid'] = $fax_uuid;
								$array['fax_logs'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplans'][$x]['dialplan_uuid'] = $fax['dialplan_uuid'];
								$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplan_details'][$x]['dialplan_uuid'] = $fax['dialplan_uuid'];
								$array['dialplan_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('fax_delete', 'temp');
									$p->add('fax_user_delete', 'temp');
									$p->add('fax_file_delete', 'temp');
									$p->add('fax_log_delete', 'temp');
									$p->add('dialplan_delete', 'temp');
									$p->add('dialplan_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('fax_delete', 'temp');
									$p->delete('fax_user_delete', 'temp');
									$p->delete('fax_file_delete', 'temp');
									$p->delete('fax_log_delete', 'temp');
									$p->delete('dialplan_delete', 'temp');
									$p->delete('dialplan_detail_delete', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									$cache = new cache;
									$cache->delete("dialplan:".$_SESSION["domain_name"]);

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

		public function delete_files($records) {

			//set private variables
				$this->permission_prefix = 'fax_file_';
				$this->list_page = 'fax_files.php?id='.urlencode($this->fax_uuid).'&box='.urlencode($this->box);
				$this->table = 'fax_files';
				$this->uuid_prefix = 'fax_file_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked fax files, build where clause for below
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary fax file details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, fax_mode, fax_file_path, fax_file_type from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										if ($row['fax_mode'] == 'rx') { $fax_files[$row['uuid']]['folder'] = 'inbox'; }
										if ($row['fax_mode'] == 'tx') { $fax_files[$row['uuid']]['folder'] = 'sent'; }
										$fax_files[$row['uuid']]['path'] = $row['fax_file_path'];
										$fax_files[$row['uuid']]['type'] = $row['fax_file_type'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//delete fax file(s)
							if (is_array($fax_files) && @sizeof($fax_files) != 0) {
								foreach ($fax_files as $fax_file_uuid => $fax_file) {
									if (substr_count($fax_file['path'], '/temp/') > 0) {
										$fax_file['path'] = str_replace('/temp/', '/'.$fax_file['type'].'/', $fax_file['path']);
									}
									if (file_exists($fax_file['path'])) {
										@unlink($fax_file['path']);
									}
									if ($fax_file['type'] == 'tif') {
										$fax_file['path'] = str_replace('.tif', '.pdf', $fax_file['path']);
										if (file_exists($fax_file['path'])) {
											@unlink($fax_file['path']);
										}
									}
									else if ($fax_file['type'] == 'pdf') {
										$fax_file['path'] = str_replace('.pdf', '.tif', $fax_file['path']);
										if (file_exists($fax_file['path'])) {
											@unlink($fax_file['path']);
										}
									}
								}
							}

						//build the delete array
							$x = 0;
							foreach ($fax_files as $fax_file_uuid => $fax_file) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $fax_file_uuid;
								$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_logs($records) {

			//set private variables
				$this->permission_prefix = 'fax_log_';
				$this->list_page = 'fax_logs.php?id='.urlencode($this->fax_uuid);
				$this->table = 'fax_logs';
				$this->uuid_prefix = 'fax_log_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked fax logs, build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		* copy records
		*/
		public function copy($records) {

			//set private variables
				$this->permission_prefix = 'fax_extension_';
				$this->list_page = 'fax.php';
				$this->table = 'fax';
				$this->uuid_prefix = 'fax_';

			if (permission_exists($this->permission_prefix.'copy')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								//primary table
									$sql = "select * from v_".$this->table." ";
									$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										$y = 0;
										foreach ($rows as $x => $row) {
											$new_fax_uuid = uuid();
											$new_dialplan_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $new_fax_uuid;
												$array[$this->table][$x]['dialplan_uuid'] = $new_dialplan_uuid;
												if ($row['fax_forward_number'] == '') {
													unset($array[$this->table][$x]['fax_forward_number']);
												}
												$array[$this->table][$x]['fax_description'] = trim($row['fax_description'].' ('.$text['label-copy'].')');

											//fax users sub table
												$sql_2 = "select e.* from v_fax_users as e, v_users as u ";
												$sql_2 .= "where e.user_uuid = u.user_uuid  ";
												$sql_2 .= "and e.domain_uuid = :domain_uuid ";
												$sql_2 .= "and e.fax_uuid = :fax_uuid ";
												$parameters_2['domain_uuid'] = $_SESSION['domain_uuid'];
												$parameters_2['fax_uuid'] = $row['fax_uuid'];
												$database = new database;
												$rows_2 = $database->select($sql_2, $parameters_2, 'all');
												if (is_array($rows_2) && @sizeof($rows_2) != 0) {
													foreach ($rows_2 as $row_2) {

														//copy data
															$array['fax_users'][$y] = $row_2;

														//overwrite
															$array['fax_users'][$y]['fax_user_uuid'] = uuid();
															$array['fax_users'][$y]['fax_uuid'] = $new_fax_uuid;

														//increment
															$y++;

													}
												}
												unset($sql_2, $parameters_2, $rows_2, $row_2);

											//fax dialplan record
												$sql_3 = "select * from v_dialplans where dialplan_uuid = :dialplan_uuid";
												$parameters_3['dialplan_uuid'] = $row['dialplan_uuid'];
												$database = new database;
												$dialplan = $database->select($sql_3, $parameters_3, 'row');
												if (is_array($dialplan) && @sizeof($dialplan) != 0) {

													//copy data
														$array['dialplans'][$x] = $dialplan;

													//overwrite
														$array['dialplans'][$x]['dialplan_uuid'] = $new_dialplan_uuid;
														$dialplan_xml = $dialplan['dialplan_xml'];
														$dialplan_xml = str_replace($row['fax_uuid'], $new_fax_uuid, $dialplan_xml); //replace source fax_uuid with new
														$dialplan_xml = str_replace($dialplan['dialplan_uuid'], $new_dialplan_uuid, $dialplan_xml); //replace source dialplan_uuid with new
														$array['dialplans'][$x]['dialplan_xml'] = $dialplan_xml;
														$array['dialplans'][$x]['dialplan_description'] = trim($dialplan['dialplan_description'].' ('.$text['label-copy'].')');

												}
												unset($sql_3, $parameters_3, $dialplan);

										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('fax_add', 'temp');
									$p->add('dialplan_add', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('fax_add', 'temp');
									$p->delete('dialplan_add', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									$cache = new cache;
									$cache->delete("dialplan:".$_SESSION["domain_name"]);

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		} //method

	} //class
}

/*
$o = new fax;
$c->domain_uuid = "";
$c->dialplan_uuid = "";
$c->fax_name = "";
$c->fax_extension = $fax_extension;
$c->fax_forward_number = $fax_forward_number;
$c->destination_number = $fax_destination_number;
$c->fax_description = $fax_description;
$c->dialplan();
*/

?>
