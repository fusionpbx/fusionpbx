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
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the sip profiles class
if (!class_exists('sip_profiles')) {
	class sip_profiles {

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
		public $sip_profile_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'sip_profiles';
				$this->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
				$this->permission_prefix = 'sip_profile_';
				$this->list_page = 'sip_profiles.php';
				$this->table = 'sip_profiles';
				$this->uuid_prefix = 'sip_profile_';
				$this->toggle_field = 'sip_profile_enabled';
				$this->toggle_values = ['true','false'];

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
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
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked sip profiles, build where clause for below
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary sip profile details
							$sql = "select ".$this->uuid_prefix."uuid as uuid, sip_profile_name, sip_profile_hostname from v_".$this->table." ";
							$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
							$database = new database;
							$rows = $database->select($sql, $parameters, 'all');
							if (is_array($rows) && @sizeof($rows) != 0) {
								foreach ($rows as $row) {
									$sip_profiles[$row['uuid']]['name'] = $row['sip_profile_name'];
									$sip_profiles[$row['uuid']]['hostname'] = $row['sip_profile_hostname'];
								}
							}
							unset($sql, $parameters, $rows, $row);

						//build the delete array
							$x = 0;
							foreach ($sip_profiles as $sip_profile_uuid => $sip_profile) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $sip_profile_uuid;
								$array['sip_profile_domains'][$x][$this->uuid_prefix.'uuid'] = $sip_profile_uuid;
								$array['sip_profile_settings'][$x][$this->uuid_prefix.'uuid'] = $sip_profile_uuid;
								$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('sip_profile_domain_delete', 'temp');
									$p->add('sip_profile_setting_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('sip_profile_domain_delete', 'temp');
									$p->delete('sip_profile_setting_delete', 'temp');

								//delete the xml sip profile and directory
									foreach ($sip_profiles as $sip_profile_uuid => $sip_profile) {
										@unlink($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile['name'].".xml");
										@unlink($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile['name']);
									}

								//save the sip profile xml
									save_sip_profile_xml();

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//determine hostnames, get system hostname if necessary
									$empty_hostname = false;
									foreach ($sip_profiles as $sip_profile_uuid => $sip_profile) {
										if ($sip_profile['hostname'] != '') {
											$hostnames[] = $sip_profile['hostname'];
										}
										else {
											$empty_hostname = true;
										}
									}
									if ($empty_hostname) {
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
										if ($fp) {
											$hostnames[] = event_socket_request($fp, 'api switchname');
										}
									}

								//clear the cache
									if (is_array($hostnames) && @sizeof($hostnames) != 0) {
										$hostnames = array_unique($hostnames);
										$cache = new cache;
										foreach ($hostnames as $hostname) {
											$cache->delete("configuration:sofia.conf:".$hostname);
										}
									}

								//set message
									message::add($text['message-delete']);
							}
							unset($records, $sip_profiles);
					}
			}
		}

		public function delete_domains($records) {
			//assign private variables
				$this->permission_prefix = 'sip_profile_domain_';
				$this->list_page = 'sip_profile_edit.php?id='.$this->sip_profile_uuid;
				$this->table = 'sip_profile_domains';
				$this->uuid_prefix = 'sip_profile_domain_';

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

						//filter out unchecked sip profiles, build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['sip_profile_uuid'] = $this->sip_profile_uuid;
								}
							}

						//get necessary sip profile details
							if (is_uuid($this->sip_profile_uuid)) {
								$sql = "select sip_profile_hostname from v_sip_profiles ";
								$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
								$parameters['sip_profile_uuid'] = $this->sip_profile_uuid;
								$database = new database;
								$sip_profile_hostname = $database->select($sql, $parameters, 'column');
								unset($sql, $parameters);
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//save the sip profile xml
									save_sip_profile_xml();

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//get system hostname if necessary
									if ($sip_profile_hostname == '') {
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
										if ($fp) {
											$sip_profile_hostname[] = event_socket_request($fp, 'api switchname');
										}
									}

								//clear the cache
									if ($sip_profile_hostname != '') {
										$cache = new cache;
										$cache->delete("configuration:sofia.conf:".$sip_profile_hostname);
									}

							}
							unset($records);
					}
			}
		}

		public function delete_settings($records) {
			//assign private variables
				$this->permission_prefix = 'sip_profile_setting_';
				$this->list_page = 'sip_profile_edit.php?id='.$this->sip_profile_uuid;
				$this->table = 'sip_profile_settings';
				$this->uuid_prefix = 'sip_profile_setting_';

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

						//filter out unchecked sip profiles, build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['sip_profile_uuid'] = $this->sip_profile_uuid;
								}
							}

						//get necessary sip profile details
							if (is_uuid($this->sip_profile_uuid)) {
								$sql = "select sip_profile_hostname from v_sip_profiles ";
								$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
								$parameters['sip_profile_uuid'] = $this->sip_profile_uuid;
								$database = new database;
								$sip_profile_hostname = $database->select($sql, $parameters, 'column');
								unset($sql, $parameters);
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//save the sip profile xml
									save_sip_profile_xml();

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//get system hostname if necessary
									if ($sip_profile_hostname == '') {
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
										if ($fp) {
											$sip_profile_hostname[] = event_socket_request($fp, 'api switchname');
										}
									}

								//clear the cache
									if ($sip_profile_hostname != '') {
										$cache = new cache;
										$cache->delete("configuration:sofia.conf:".$sip_profile_hostname);
									}

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
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, sip_profile_hostname from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$sip_profiles[$row['uuid']]['state'] = $row['toggle'];
										$sip_profiles[$row['uuid']]['hostname'] = $row['sip_profile_hostname'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach ($sip_profiles as $uuid => $sip_profile) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $sip_profile['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//determine hostnames, get system hostname if necessary
									$empty_hostname = false;
									foreach ($sip_profiles as $sip_profile_uuid => $sip_profile) {
										if ($sip_profile['hostname'] != '') {
											$hostnames[] = $sip_profile['hostname'];
										}
										else {
											$empty_hostname = true;
										}
									}
									if ($empty_hostname) {
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
										if ($fp) {
											$hostnames[] = event_socket_request($fp, 'api switchname');
										}
									}

								//clear the cache
									if (is_array($hostnames) && @sizeof($hostnames) != 0) {
										$hostnames = array_unique($hostnames);
										$cache = new cache;
										foreach ($hostnames as $hostname) {
											$cache->delete("configuration:sofia.conf:".$hostname);
										}
									}

								//save the sip profile xml
									save_sip_profile_xml();

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}

			}
		}

	}
}

?>