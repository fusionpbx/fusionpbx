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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "resources/require.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get(null, 'resources');

//for compatibility require this library if less than version 5.5
	if (version_compare(phpversion(), '5.5', '<')) {
		require_once "resources/functions/password.php";
	}

//start the session
	if (!isset($_SESSION)) { session_start(); }

//define variables
	if (!isset($_SESSION['template_content'])) { $_SESSION["template_content"] = null; }

//if the username is not provided then send to login.php
	if (strlen($_SESSION['username']) == 0 && strlen($_REQUEST["username"]) == 0 && strlen($_REQUEST["key"]) == 0) {
		$target_path = ($_REQUEST["path"] != '') ? $_REQUEST["path"] : $_SERVER["REQUEST_URI"];
		header("Location: ".PROJECT_PATH."/login.php?path=".urlencode($target_path));
		exit;
	}

//if the username session is not set the check username and password
	if (strlen($_SESSION['username']) == 0) {

		//clear the menu
			unset($_SESSION["menu"]);

		//clear the template only if the template has not been assigned by the superadmin
			if (strlen($_SESSION['domain']['template']['name']) == 0) {
				$_SESSION["template_content"] = '';
			}

		//validate the username and password
			$auth = new authentication;
			if (isset($_REQUEST["username"]) && isset($_REQUEST["password"])) {
				$auth->username = $_REQUEST["username"];
				$auth->password = $_REQUEST["password"];
			}
			if (isset($_REQUEST["key"])) {
				$auth->key = $_REQUEST["key"];
			}
			$auth->debug = false;
			$result = $auth->validate();
			if ($result["authorized"] === "true") {

				//get the user settings
					$sql = "select * from v_user_settings ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and user_uuid = :user_uuid ";
					$sql .= "and user_setting_enabled = 'true' ";
					$parameters['domain_uuid'] = $result["domain_uuid"];
					$parameters['user_uuid'] = $result["user_uuid"];
					$database = new database;
					$user_settings = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);

				//build the user cidr array
					if (is_array($user_settings) && @sizeof($user_settings) != 0) {
						foreach ($user_settings as $row) {
							if ($row['user_setting_category'] == "domain" && $row['user_setting_subcategory'] == "cidr" && $row['user_setting_name'] == "array") {
								$cidr_array[] = $row['user_setting_value'];
							}
						}
					}

				//check to see if user address is in the cidr array
					if (isset($cidr_array) && !defined('STDIN')) {
						$found = false;
						foreach($cidr_array as $cidr) {
							if (check_cidr($cidr, $_SERVER['REMOTE_ADDR'])) {
								$found = true;
								break;
							}
						}
						if (!$found) {
							//destroy session
							session_unset();
							session_destroy();

							//send http 403
							header('HTTP/1.0 403 Forbidden', true, 403);

							//redirect to the root of the website
							header("Location: ".PROJECT_PATH."/login.php");

							//exit the code
							exit();
						}
					}

				//set the session variables
					$_SESSION["domain_uuid"] = $result["domain_uuid"];
					//$_SESSION["domain_name"] = $result["domain_name"];
					$_SESSION["user_uuid"] = $result["user_uuid"];
					$_SESSION["context"] = $result['domain_name'];

				//user session array
					$_SESSION["user"]["domain_uuid"] = $result["domain_uuid"];
					$_SESSION["user"]["domain_name"] = $result["domain_name"];
					$_SESSION["user"]["user_uuid"] = $result["user_uuid"];
					$_SESSION["user"]["username"] = $result["username"];
					$_SESSION["user"]["contact_uuid"] = $result["contact_uuid"];
			}
			else {
				//debug
					if ($debug) {
						view_array($result);
					}

				//log the failed auth attempt to the system, to be available for fail2ban.
					openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
					syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] authentication failed for ".$result["username"]);
					closelog();

				//redirect the user to the login page
					$target_path = ($_REQUEST["path"] != '') ? $_REQUEST["path"] : $_SERVER["PHP_SELF"];
					message::add($text['message-invalid_credentials'], 'negative');
					header("Location: ".PROJECT_PATH."/login.php?path=".urlencode($target_path));
					exit;
			}

		//get the groups assigned to the user and then set the groups in $_SESSION["groups"]
			$sql = "select ";
			$sql .= "u.user_group_uuid, ";
			$sql .= "u.domain_uuid, ";
			$sql .= "u.user_uuid, ";
			$sql .= "u.group_uuid, ";
			$sql .= "g.group_name, ";
			$sql .= "g.group_level ";
			$sql .= "from ";
			$sql .= "v_user_groups as u, ";
			$sql .= "v_groups as g ";
			$sql .= "where u.domain_uuid = :domain_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$sql .= "and u.group_uuid = g.group_uuid ";
			$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
			$parameters['user_uuid'] = $_SESSION["user_uuid"];
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');
			$_SESSION["groups"] = $result;
			$_SESSION["user"]["groups"] = $result;
			unset($sql, $parameters);

		//get the users group level
			$_SESSION["user"]["group_level"] = 0;
			foreach ($_SESSION['user']['groups'] as $row) {
				if ($_SESSION["user"]["group_level"] < $row['group_level']) {
					$_SESSION["user"]["group_level"] = $row['group_level'];
				}
			}

		//get the permissions assigned to the groups that the user is a member of set the permissions in $_SESSION['permissions']
			if (is_array($_SESSION["groups"]) && @sizeof($_SESSION["groups"]) != 0) {
				$x = 0;
				$sql = "select distinct(permission_name) from v_group_permissions ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
				foreach ($_SESSION["groups"] as $field) {
					if (strlen($field['group_name']) > 0) {
						$sql_where_or[] = "group_name = :group_name_".$x;
						$parameters['group_name_'.$x] = $field['group_name'];
						$x++;
					}
				}
				if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
					$sql .= "and (".implode(' or ', $sql_where_or).") ";
				}
				$sql .= "and permission_assigned = 'true' ";
				$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');
				if (is_array($result) && @sizeof($result) != 0) {
					foreach ($result as $row) {
						$_SESSION['permissions'][$row["permission_name"]] = true;
						$_SESSION["user"]["permissions"][$row["permission_name"]] = true;
					}
				}
				unset($sql, $parameters, $result, $row);
			}

		//get the domains
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php") && !is_cli()){
				require_once "app/domains/resources/domains.php";
			}

		//get the user settings
			if (is_array($user_settings) && @sizeof($user_settings) != 0) {
				foreach ($user_settings as $row) {
					$name = $row['user_setting_name'];
					$category = $row['user_setting_category'];
					$subcategory = $row['user_setting_subcategory'];
					if (strlen($row['user_setting_value']) > 0) {
						if (strlen($subcategory) == 0) {
							//$$category[$name] = $row['domain_setting_value'];
							if ($name == "array") {
								$_SESSION[$category][] = $row['user_setting_value'];
							}
							else {
								$_SESSION[$category][$name] = $row['user_setting_value'];
							}
						}
						else {
							//$$category[$subcategory][$name] = $row['domain_setting_value'];
							if ($name == "array") {
								$_SESSION[$category][$subcategory][] = $row['user_setting_value'];
							}
							else {
								$_SESSION[$category][$subcategory][$name] = $row['user_setting_value'];
							}
						}
					}
				}
			}
			unset($user_settings);

		//get the extensions that are assigned to this user
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/extensions/app_config.php")) {
				if (isset($_SESSION["user"]) && is_uuid($_SESSION["user_uuid"]) && is_uuid($_SESSION["domain_uuid"]) && !isset($_SESSION['user']['extension'])) {
						//get the user extension list
						$_SESSION['user']['extension'] = null;
						$sql = "select ";
						$sql .= "e.extension_uuid, ";
						$sql .= "e.extension, ";
						$sql .= "e.number_alias, ";
						$sql .= "e.user_context, ";
						$sql .= "e.outbound_caller_id_name, ";
						$sql .= "e.outbound_caller_id_number, ";
						$sql .= "e.description ";
						$sql .= "from ";
						$sql .= "v_extension_users as u, ";
						$sql .= "v_extensions as e ";
						$sql .= "where ";
						$sql .= "e.domain_uuid = :domain_uuid ";
						$sql .= "and e.extension_uuid = u.extension_uuid ";
						$sql .= "and u.user_uuid = :user_uuid ";
						$sql .= "and e.enabled = 'true' ";
						$sql .= "order by ";
						$sql .= "e.extension asc ";
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$parameters['user_uuid'] = $_SESSION['user_uuid'];
						$database = new database;
						$result = $database->select($sql, $parameters, 'all');
						if (is_array($result) && @sizeof($result) != 0) {
							foreach($result as $x => $row) {
								//set the destination
								$destination = $row['extension'];
								if (strlen($row['number_alias']) > 0) {
									$destination = $row['number_alias'];
								}

								//build the user array
								$_SESSION['user']['extension'][$x]['user'] = $row['extension'];
								$_SESSION['user']['extension'][$x]['number_alias'] = $row['number_alias'];
								$_SESSION['user']['extension'][$x]['destination'] = $destination;
								$_SESSION['user']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
								$_SESSION['user']['extension'][$x]['outbound_caller_id_name'] = $row['outbound_caller_id_name'];
								$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
								$_SESSION['user']['extension'][$x]['user_context'] = $row['user_context'];
								$_SESSION['user']['extension'][$x]['description'] = $row['description'];

								//set the context
								$_SESSION['user']['user_context'] = $row["user_context"];
								$_SESSION['user_context'] = $row["user_context"];
							}
						}
						unset($sql, $parameters, $result, $row);
				}
			}

		//if logged in, redirect to login destination
			if (!isset($_REQUEST["key"])) {
				if (isset($_SESSION['redirect_path'])) {
					$redirect_path = $_SESSION['redirect_path'];
					unset($_SESSION['redirect_path']);
					// prevent open redirect attacks. redirect url shouldn't contain a hostname
					$parsed_url = parse_url($redirect_path);
					if ($parsed_url['host']) {
						die("Was someone trying to hack you?");
					}
					header("Location: ".$redirect_path);
				}
				elseif (isset($_SESSION['login']['destination']['url'])) {
					header("Location: ".$_SESSION['login']['destination']['url']);
				} elseif (file_exists($_SERVER["PROJECT_ROOT"]."/core/dashboard/app_config.php")) {
					header("Location: ".PROJECT_PATH."/core/dashboard/");
				}
				else {
					require_once "resources/header.php";
					require_once "resources/footer.php";
				}
			}

	}

//set the time zone
	if (!isset($_SESSION["time_zone"]["user"])) { $_SESSION["time_zone"]["user"] = null; }
	if (strlen($_SESSION["time_zone"]["user"]) == 0) {
		//set the domain time zone as the default time zone
		date_default_timezone_set($_SESSION['domain']['time_zone']['name']);
	}
	else {
		//set the user defined time zone
		date_default_timezone_set($_SESSION["time_zone"]["user"]);
	}

?>
