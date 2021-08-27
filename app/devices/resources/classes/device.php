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
	Copyright (C) 2010 - 2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the device class
	class device {
		public $db;
		public $domain_uuid;
		public $template_dir;
		public $device_uuid;
		public $device_vendor_uuid;
		public $device_profile_uuid;

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

		public function __construct() {

			//assign private variables
				$this->app_name = 'devices';
				$this->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';

		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function get_domain_uuid() {
			return $this->domain_uuid;
		}

		public static function get_vendor($mac){
			//use the mac address to find the vendor
				$mac = preg_replace('#[^a-fA-F0-9./]#', '', $mac);
				$mac = strtolower($mac);
				switch (substr($mac, 0, 6)) {
				case "00085d":
					$device_vendor = "aastra";
					break;
				case "001873":
					$device_vendor = "cisco";
					break;
				case "a44c11":
					$device_vendor = "cisco";
					break;
				case "0021A0":
					$device_vendor = "cisco";
					break;
				case "30e4db":
					$device_vendor = "cisco";
					break;
				case "002155":
					$device_vendor = "cisco";
					break;
				case "68efbd":
					$device_vendor = "cisco";
					break;
				case "000b82":
					$device_vendor = "grandstream";
					break;
				case "00177d":
					$device_vendor = "konftel";
					break;
				case "00045a":
					$device_vendor = "linksys";
					break;
				case "000625":
					$device_vendor = "linksys";
					break;
				case "000e08":
					$device_vendor = "linksys";
					break;
				case "08000f":
					$device_vendor = "mitel";
					break;
				case "0080f0":
					$device_vendor = "panasonic";
					break;
				case "0004f2":
					$device_vendor = "polycom";
					break;
				case "00907a":
					$device_vendor = "polycom";
					break;
				case "64167f":
					$device_vendor = "polycom";
					break;
				case "000413":
					$device_vendor = "snom";
					break;
				case "001565":
					$device_vendor = "yealink";
					break;
				case "805ec0":
					$device_vendor = "yealink";
					break;
				case "00268B":
					$device_vendor = "escene";
					break;
				case "001fc1":
					$device_vendor = "htek";
					break;
				case "0C383E":
					$device_vendor = "fanvil";
					break;
				case "7c2f80":
					$device_vendor = "gigaset";
					break;
				case "14b370":
					$device_vendor = "gigaset";
					break;
				case "002104":
					$device_vendor = "gigaset";
					break;
				case "bcc342":
					$device_vendor = "panasonic";
					break;
				case "080023":
					$device_vendor = "panasonic";
					break;
				case "0080f0":
					$device_vendor = "panasonic";
					break;
				case "0021f2":
					$device_vendor = "flyingvoice";
					break;					
				default:
					$device_vendor = "";
				}
				return $device_vendor;
		}

		public static function get_vendor_by_agent($agent){
			if ($agent) {
					$agent = strtolower($agent);
				//get the vendor
					if (preg_replace('/^.*?(aastra).*$/i', '$1', $agent) == "aastra") {
						return "aastra";
					}
					if (preg_replace('/^.*?(cisco).*$/i', '$1', $agent) == "cisco") {
						return "cisco";
					}
					if (preg_replace('/^.*?(cisco\/spa).*$/i', '$1', $agent) == "cisco/spa") {
						return "cisco-spa";
					}
					if (preg_replace('/^.*?(digium).*$/i', '$1', $agent) == "digium") {
                                                return "digium";
                                        }
					if (preg_replace('/^.*?(grandstream).*$/i', '$1', $agent) == "grandstream") {
						return "grandstream";
					}
					if (preg_replace('/^.*?(linksys).*$/i', '$1', $agent) == "linksys") {
						return "linksys";
					}
					if (preg_replace('/^.*?(polycom).*$/i', '$1', $agent) == "polycom") {
						return "polycom";
					}
					if (preg_replace('/^.*?(yealink).*$/i', '$1', $agent) == "yealink") {
						return "yealink";
					}
					if (preg_replace('/^.*?(vp530p).*$/i', '$1', $agent) == "vp530p") {
						return "yealink";
					}
					if (preg_replace('/^.*?(snom).*$/i', '$1', $agent) == "snom") {
						return "snom";
					}
					if (preg_match('/^.*?addpac.*$/i', $agent)) {
						return "addpac";
					}
					/*Escene use User-Agent string like `ES320VN2 v4.0 ...  or `ES206 v1.0 ...` */
					if (preg_match('/^es\d\d\d.*$/i', $agent)) {
						return "escene";
					}
					if (preg_match('/^.*?panasonic.*$/i', $agent)) {
						return "panasonic";
					}
					if (preg_replace('/^.*?(N510).*$/i', '$1', $agent) == "n510") {
						return "gigaset";
					}
					if (preg_match('/^.*?htek.*$/i', $agent)) {
						return "htek";
					}
					if (preg_replace('/^.*?(fanvil).*$/i', '$1', $agent) == "fanvil") {
						return "fanvil";
					}
					if (preg_replace('/^.*?(flyingvoice).*$/i', '$1', $agent) == "flyingvoice") {
						return "flyingvoice";
					}
					// unknown vendor
					return "";
				}
		}

		public function get_template_dir() {
			//set the default template directory
				if (PHP_OS == "Linux") {
					//set the default template dir
						if (strlen($this->template_dir) == 0) {
							if (file_exists('/etc/fusionpbx/resources/templates/provision')) {
								$this->template_dir = '/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				}
				elseif (PHP_OS == "FreeBSD") {
					//if the FreeBSD port is installed use the following paths by default.
						if (file_exists('/usr/local/etc/fusionpbx/resources/templates/provision')) {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = '/usr/local/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
						else {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				}
				elseif (PHP_OS == "NetBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}
				elseif (PHP_OS == "OpenBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}
				else {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}

			//check to see if the domain name sub directory exists
				if (is_dir($this->template_dir."/".$_SESSION["domain_name"])) {
					$this->template_dir = $this->template_dir."/".$_SESSION["domain_name"];
				}

			//return the template directory
				return $this->template_dir;
		}

		/**
		 * delete records
		 */
		public function delete($records) {

			//assign private variables
				$this->permission_prefix = 'device_';
				$this->list_page = 'devices.php';
				$this->table = 'devices';
				$this->uuid_prefix = 'device_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$sql = "update v_devices set device_uuid_alternate = null where device_uuid_alternate = :device_uuid_alternate; ";
									$parameters['device_uuid_alternate'] = $record['uuid'];
									$database = new database;
									$database->execute($sql, $parameters);
									unset($sql, $parameters);

									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array['device_settings'][$x]['device_uuid'] = $record['uuid'];
									$array['device_lines'][$x]['device_uuid'] = $record['uuid'];
									$array['device_keys'][$x]['device_uuid'] = $record['uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('device_setting_delete', 'temp');
									$p->add('device_line_delete', 'temp');
									$p->add('device_key_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('device_setting_delete', 'temp');
									$p->delete('device_line_delete', 'temp');
									$p->delete('device_key_delete', 'temp');

								//write the provision files
									if (strlen($_SESSION['provision']['path']['text']) > 0) {
										$prov = new provision;
										$prov->domain_uuid = $_SESSION['domain_uuid'];
										$response = $prov->write();
									}

								//set message
									message::add($text['message-delete']);

							}
							unset($records);
					}
			}
		}

		public function delete_lines($records) {
			//assign private variables
				$this->permission_prefix = 'device_line_';
				$this->table = 'device_lines';
				$this->uuid_prefix = 'device_line_';

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

						//filter out unchecked device lines, build delete array
							$x = 0;
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['device_uuid'] = $this->device_uuid;
									$x++;
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
							}
							unset($records);
					}
			}
		}

		public function delete_keys($records) {
			//assign private variables
				$this->permission_prefix = 'device_key_';
				$this->table = 'device_keys';
				$this->uuid_prefix = 'device_key_';

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

						//filter out unchecked device keys, build delete array
							$x = 0;
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['device_uuid'] = $this->device_uuid;
									$x++;
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
							}
							unset($records);
					}
			}
		}

		public function delete_settings($records) {
			//assign private variables
				$this->permission_prefix = 'device_setting_';
				$this->table = 'device_settings';
				$this->uuid_prefix = 'device_setting_';

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

						//filter out unchecked device settings, build delete array
							$x = 0;
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['device_uuid'] = $this->device_uuid;
									$x++;
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
							}
							unset($records);
					}
			}
		}

		public function delete_vendors($records) {

			//assign private variables
				$this->permission_prefix = 'device_vendor_';
				$this->list_page = 'device_vendors.php';
				$this->tables[] = 'device_vendors';
				$this->tables[] = 'device_vendor_functions';
				$this->tables[] = 'device_vendor_function_groups';
				$this->uuid_prefix = 'device_vendor_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									foreach ($this->tables as $table) {
										$array[$table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									}
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('device_vendor_function_delete', 'temp');
									$p->add('device_vendor_function_group_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('device_vendor_function_delete', 'temp');
									$p->delete('device_vendor_function_group_delete', 'temp');

								//set message
									message::add($text['message-delete']);

							}
							unset($records);
					}
			}
		}

		public function delete_vendor_functions($records) {

			//assign private variables
				$this->permission_prefix = 'device_vendor_function_';
				$this->list_page = 'device_vendor_edit.php';
				$this->tables[] = 'device_vendor_functions';
				$this->tables[] = 'device_vendor_function_groups';
				$this->uuid_prefix = 'device_vendor_function_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/app/devices/device_vendor_functions.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page.'?id='.$this->device_vendor_uuid);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									foreach ($this->tables as $table) {
										$array[$table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									}
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('device_vendor_function_group_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('device_vendor_function_group_delete', 'temp');

								//set message
									message::add($text['message-delete']);

							}
							unset($records);
					}
			}
		}

		public function delete_profiles($records) {

			//assign private variables
				$this->permission_prefix = 'device_profile_';
				$this->list_page = 'device_profiles.php';
				$this->tables[] = 'device_profiles';
				$this->tables[] = 'device_profile_keys';
				$this->tables[] = 'device_profile_settings';
				$this->uuid_prefix = 'device_profile_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									foreach ($this->tables as $table) {
										$array[$table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									}
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('device_profile_key_delete', 'temp');
									$p->add('device_profile_setting_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('device_profile_key_delete', 'temp');
									$p->delete('device_profile_setting_delete', 'temp');

								//set message
									message::add($text['message-delete']);

							}
							unset($records);
					}
			}
		}

		public function delete_profile_keys($records) {

			//assign private variables
				$this->permission_prefix = 'device_profile_key_';
				$this->list_page = 'device_profile_edit.php?id='.$this->device_profile_uuid;
				$this->table = 'device_profile_keys';
				$this->uuid_prefix = 'device_profile_key_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
								}
							}

						//execute delete
							if (is_array($array) && @sizeof($array) != 0) {
								$database = new database;
								$database->app_name = $this->app_name;
								$database->app_uuid = $this->app_uuid;
								$database->delete($array);
								unset($array);
							}
							unset($records);

					}
			}
		}

		public function delete_profile_settings($records) {

			//assign private variables
				$this->permission_prefix = 'device_profile_setting_';
				$this->list_page = 'device_profile_edit.php?id='.$this->device_profile_uuid;
				$this->table = 'device_profile_settings';
				$this->uuid_prefix = 'device_profile_setting_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
								}
							}

						//execute delete
							if (is_array($array) && @sizeof($array) != 0) {
								$database = new database;
								$database->app_name = $this->app_name;
								$database->app_uuid = $this->app_uuid;
								$database->delete($array);
								unset($array);
							}
							unset($records);

					}
			}
		}

		/**
		 * toggle records
		 */
		public function toggle($records) {

			//assign private variables
				$this->permission_prefix = 'device_';
				$this->list_page = 'devices.php';
				$this->table = 'devices';
				$this->uuid_prefix = 'device_';
				$this->toggle_field = 'device_enabled';
				$this->toggle_values = ['true','false'];

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
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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

								//write the provision files
									if (strlen($_SESSION['provision']['path']['text']) > 0) {
										$prov = new provision;
										$prov->domain_uuid = $_SESSION['domain_uuid'];
										$response = $prov->write();
									}

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}

			}
		}

		public function toggle_vendors($records) {

			//assign private variables
				$this->permission_prefix = 'device_vendor_';
				$this->list_page = 'device_vendors.php';
				$this->table = 'device_vendors';
				$this->uuid_prefix = 'device_vendor_';
				$this->toggle_field = 'enabled';
				$this->toggle_values = ['true','false'];

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
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}

			}
		}

		public function toggle_vendor_functions($records) {

			//assign private variables
				$this->permission_prefix = 'device_vendor_function_';
				$this->list_page = 'device_vendor_edit.php';
				$this->table = 'device_vendor_functions';
				$this->uuid_prefix = 'device_vendor_function_';
				$this->toggle_field = 'enabled';
				$this->toggle_values = ['true','false'];

			if (permission_exists($this->permission_prefix.'edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/app/devices/device_vendor_functions.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page.'?id='.$this->device_vendor_uuid);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}

			}
		}

		public function toggle_profiles($records) {

			//assign private variables
				$this->permission_prefix = 'device_profile_';
				$this->list_page = 'device_profiles.php';
				$this->table = 'device_profiles';
				$this->uuid_prefix = 'device_profile_';
				$this->toggle_field = 'device_profile_enabled';
				$this->toggle_values = ['true','false'];

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
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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
		public function copy_profiles($records) {

			//assign private variables
				$this->permission_prefix = 'device_profile_';
				$this->list_page = 'device_profiles.php';
				$this->table = 'device_profiles';
				$this->uuid_prefix = 'device_profile_';

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
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select * from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$y = $z = 0;
									foreach ($rows as $x => $row) {
										$primary_uuid = uuid();

										//copy data
											$array[$this->table][$x] = $row;

										//overwrite
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $primary_uuid;
											$array[$this->table][$x]['device_profile_description'] = trim($row['device_profile_description'].' ('.$text['label-copy'].')');

										//keys sub table
											$sql_2 = "select * from v_device_profile_keys ";
											$sql_2 .= "where device_profile_uuid = :device_profile_uuid ";
											$sql_2 .= "order by ";
											$sql_2 .= "case profile_key_category ";
											$sql_2 .= "when 'line' then 1 ";
											$sql_2 .= "when 'memort' then 2 ";
											$sql_2 .= "when 'programmable' then 3 ";
											$sql_2 .= "when 'expansion' then 4 ";
											$sql_2 .= "else 100 end, ";
											$sql_2 .= "profile_key_id asc ";
											$parameters_2['device_profile_uuid'] = $row['device_profile_uuid'];
											$database = new database;
											$rows_2 = $database->select($sql_2, $parameters_2, 'all');
											if (is_array($rows_2) && @sizeof($rows_2) != 0) {
												foreach ($rows_2 as $row_2) {

													//copy data
														$array['device_profile_keys'][$y] = $row_2;

													//overwrite
														$array['device_profile_keys'][$y]['device_profile_key_uuid'] = uuid();
														$array['device_profile_keys'][$y]['device_profile_uuid'] = $primary_uuid;

													//increment
														$y++;

												}
											}
											unset($sql_2, $parameters_2, $rows_2, $row_2);

										//settings sub table
											$sql_3 = "select * from v_device_profile_settings where device_profile_uuid = :device_profile_uuid";
											$parameters_3['device_profile_uuid'] = $row['device_profile_uuid'];
											$database = new database;
											$rows_3 = $database->select($sql_3, $parameters_3, 'all');
											if (is_array($rows_3) && @sizeof($rows_3) != 0) {
												foreach ($rows_3 as $row_3) {

													//copy data
														$array['device_profile_settings'][$z] = $row_3;

													//overwrite
														$array['device_profile_settings'][$z]['device_profile_setting_uuid'] = uuid();
														$array['device_profile_settings'][$z]['device_profile_uuid'] = $primary_uuid;

													//increment
														$z++;

												}
											}
											unset($sql_3, $parameters_3, $rows_3, $row_3);

									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('device_profile_key_add', 'temp');
									$p->add('device_profile_setting_add', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('device_profile_key_add', 'temp');
									$p->delete('device_profile_setting_add', 'temp');

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}

		} //method

	} //class

?>
