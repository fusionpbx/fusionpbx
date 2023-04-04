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
 Portions created by the Initial Developer are Copyright (C) 2008-2022
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the gateways class
if (!class_exists('gateways')) {
	class gateways {

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
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'gateways';
				$this->app_uuid = '297ab33e-2c2f-8196-552c-f3567d2caaf8';
				$this->permission_prefix = 'gateway_';
				$this->list_page = 'gateways.php';
				$this->table = 'gateways';
				$this->uuid_prefix = 'gateway_';
				$this->toggle_field = 'enabled';
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
		 * start gateways
		 */
		public function start($records) {
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

				//start the checked gateways
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked gateways, build where clause for below
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary gateway details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, gateway, profile, enabled from v_".$this->table." ";
								if (permission_exists('gateway_all')) {
									$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								}
								else {
									$sql .= "where (domain_uuid = :domain_uuid) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								}
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$gateways[$row['uuid']]['name'] = $row['gateway'];
										$gateways[$row['uuid']]['profile'] = $row['profile'];
										$gateways[$row['uuid']]['enabled'] = $row['enabled'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						if (is_array($gateways) && @sizeof($gateways) != 0) {
							//create the event socket connection
							$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
							if ($fp) {
								//start gateways
									foreach ($gateways as $gateway_uuid => $gateway) {
										if ($gateway['enabled'] == 'true') {
											//start gateways
											foreach ($gateways as $gateway_uuid => $gateway) {
												if ($gateway['enabled'] == 'true') {
													$cmd = 'api sofia profile '.$gateway['profile'].' startgw '.$gateway_uuid;
													$responses[$gateway_uuid]['gateway'] = $gateway['name'];
													$responses[$gateway_uuid]['message'] = trim(event_socket_request($fp, $cmd));
												}
											}
											//old method used to start gateways
											//$cmd = 'api sofia profile '.$gateway['profile'].' rescan';
											//$responses[$gateway_uuid]['gateway'] = $gateway['name'];
											//$responses[$gateway_uuid]['message'] = trim(event_socket_request($fp, $cmd));
										}
									}

								//set message
									if (is_array($responses) && @sizeof($responses) != 0) {
										$message = $text['message-gateway_started'];
										foreach ($responses as $response) {
											$message .= "<br><strong>".$response['gateway']."</strong>: ".$response['message'];
										}
										message::add($message, 'positive', 7000);
									}
							}
						}
					}

			}
		}

		/**
		 * stop gateways
		 */
		public function stop($records) {
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

				//stop the checked gateways
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked gateways, build where clause for below
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary gateway details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, gateway, profile, enabled from v_".$this->table." ";
								if (permission_exists('gateway_all')) {
									$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								}
								else {
									$sql .= "where (domain_uuid = :domain_uuid) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								}
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$gateways[$row['uuid']]['name'] = $row['gateway'];
										$gateways[$row['uuid']]['profile'] = $row['profile'];
										$gateways[$row['uuid']]['enabled'] = $row['enabled'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						if (is_array($gateways) && @sizeof($gateways) != 0) {
							//create the event socket connection
							$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
							if ($fp) {
								//stop gateways
									foreach ($gateways as $gateway_uuid => $gateway) {
										if ($gateway['enabled'] == 'true') {
											$cmd = 'api sofia profile '.$gateway['profile'].' killgw '.$gateway_uuid;
											$responses[$gateway_uuid]['gateway'] = $gateway['name'];
											$responses[$gateway_uuid]['message'] = trim(event_socket_request($fp, $cmd));
										}
									}
								//set message
									if (is_array($responses) && @sizeof($responses) != 0) {
										$message = $text['message-gateway_stopped'];
										foreach ($responses as $response) {
											$message .= "<br><strong>".$response['gateway']."</strong>: ".$response['message'];
										}
										message::add($message, 'positive', 7000);
									}
							}
						}
					}

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

						//filter out unchecked gateways, build where clause for below
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary gateway details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, gateway, profile from v_".$this->table." ";
								if (permission_exists('gateway_all')) {
									$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								}
								else {
									$sql .= "where (domain_uuid = :domain_uuid) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								}
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$gateways[$row['uuid']]['name'] = $row['gateway'];
										$gateways[$row['uuid']]['profile'] = $row['profile'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//create the event socket connection
							if (!$fp) {
								$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
							}

						//loop through gateways
							$x = 0;
							foreach ($gateways as $gateway_uuid => $gateway) {

								//remove gateway from session variable
									unset($_SESSION['gateways'][$gateway_uuid]);

								//remove the xml file (if any)
									if ($_SESSION['switch']['sip_profiles']['dir'] != '') {
										$gateway_xml_file = $_SESSION['switch']['sip_profiles']['dir']."/".$gateway['profile']."/v_".$gateway_uuid.".xml";
										if (file_exists($gateway_xml_file)) {
											unlink($gateway_xml_file);
										}
									}

								//send the api command to stop the gateway
									if ($fp) {
										$cmd = 'api sofia profile '.$gateway['profile'].' killgw '.$gateway_uuid;
										$response = event_socket_request($fp, $cmd);
										unset($cmd);
									}

								//build delete array
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $gateway_uuid;
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

								//synchronize the xml config
									save_gateway_xml();

								//clear the cache
									if (!$fp) {
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
									}
									if ($fp) {
										$hostname = trim(event_socket_request($fp, 'api switchname'));
										$cache = new cache;
										$cache->delete("configuration:sofia.conf:".$hostname);
									}

								//rescan the sip profile to look for new or stopped gateways
									if (!$fp) {
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
									}
									if ($fp) {
										//get distinct profiles from gateways
											foreach ($gateways as $gateway) {
												$array[] = $gateway['profile'];
											}
											$profiles = array_unique($array);

										//send the api command to rescan each profile
											foreach ($profiles as $profile) {
												$cmd = 'api sofia profile '.$profile.' rescan';
												$response = event_socket_request($fp, $cmd);
											}
											unset($cmd);

										//close the connection
											fclose($fp);
									}
									usleep(1000);

								//clear the apply settings reminder
									$_SESSION["reload_xml"] = false;

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
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as state, gateway, profile from v_".$this->table." ";
								if (permission_exists('gateway_all')) {
									$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								}
								else {
									$sql .= "where (domain_uuid = :domain_uuid) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								}
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$gateways[$row['uuid']]['state'] = $row['state'];
										$gateways[$row['uuid']]['name'] = $row['gateway'];
										$gateways[$row['uuid']]['profile'] = $row['profile'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($gateways as $uuid => $gateway) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $gateway['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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

								//update gateway session variables or remove xml files (if necessary)
									foreach ($gateways as $gateway_uuid => $gateway) {
										if ($gateway['state'] == 'true') {
											$_SESSION['gateways'][$gateway_uuid] = $gateway['name'];
										}
										else {
											unset($_SESSION['gateways'][$gateway_uuid]);

											//remove the xml file (if any)
												if ($_SESSION['switch']['sip_profiles']['dir'] != '') {
													$gateway_xml_file = $_SESSION['switch']['sip_profiles']['dir']."/".$gateway['profile']."/v_".$gateway_uuid.".xml";
													if (file_exists($gateway_xml_file)) {
														unlink($gateway_xml_file);
													}
												}
										}
									}

								//synchronize the xml config
									save_gateway_xml();

								//clear the cache
									$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
									$hostname = trim(event_socket_request($fp, 'api switchname'));
									$cache = new cache;
									$cache->delete("configuration:sofia.conf:".$hostname);

								//create the event socket connection
									if (!$fp) {
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
									}

								//rescan the sip profile to look for new or stopped gateways
									if ($fp) {
										//get distinct profiles from gateways
											foreach ($gateways as $gateway) {
												$array[] = $gateway['profile'];
											}
											$profiles = array_unique($array);

										//send the api command to rescan each profile
											foreach ($profiles as $profile) {
												$cmd = 'api sofia profile '.$profile.' rescan';
												$response = event_socket_request($fp, $cmd);
											}
											unset($cmd);

										//close the connection
											fclose($fp);
									}
									usleep(1000);

								//clear the apply settings reminder
									$_SESSION["reload_xml"] = false;

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $gateways);
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
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select * from v_".$this->table." ";
								if (permission_exists('gateway_all')) {
									$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								}
								else {
									$sql .= "where (domain_uuid = :domain_uuid) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								}
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $x => $row) {
										$primary_uuid = uuid();

										//copy data
											$array[$this->table][$x] = $row;

										//overwrite
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $primary_uuid;
											$array[$this->table][$x]['description'] = trim($row['description'].' ('.$text['label-copy'].')');
											unset($array[$this->table][$x]['channels']);

										//defaults
											if (strlen($row['expire_seconds']) == 0) {
												$array[$this->table][$x]['expire_seconds'] = '800';
											}
											if (strlen($row['retry_seconds']) == 0) {
												$array[$this->table][$x]['retry_seconds'] = '30';
											}

										//array of new gateways
											if ($row['enabled'] == 'true') {
												$gateways[$primary_uuid]['name'] = $row['gateway'];
											}
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//add new gateways to session variables
									if (is_array($gateways) && @sizeof($gateways) != 0) {
										foreach ($gateways as $gateway_uuid => $gateway) {
											$_SESSION['gateways'][$gateway_uuid] = $gateway['name'];
										}
									}

								//synchronize the xml config
									save_gateway_xml();

								//clear the cache
									$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
									$hostname = trim(event_socket_request($fp, 'api switchname'));
									$cache = new cache;
									$cache->delete("configuration:sofia.conf:".$hostname);

								//set message
									message::add($text['message-copy']);

							}
							unset($records, $gateways);
					}

			}
		}

	}
}

?>
