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
	ini_set("session.use_only_cookies", True);
	ini_set("session.cookie_httponly", True);
	if ($_SERVER["HTTPS"] == "on") { ini_set("session.cookie_secure", True); }
	if (!isset($_SESSION)) { session_start(); }

//define variables
	if (!isset($_SESSION['login']['destination']['url'])) { $_SESSION['login']['destination']['url'] = null; }
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
			if ($result["authorized"] == "true") {
				//set the session variables
					$_SESSION["domain_uuid"] = $result["domain_uuid"];
					$_SESSION["user_uuid"] = $result["user_uuid"];

				//user session array
					$_SESSION["user"]["domain_uuid"] = $result["domain_uuid"];
					$_SESSION["user"]["user_uuid"] = $result["user_uuid"];
					$_SESSION["user"]["username"] = $result["username"];
					$_SESSION["user"]["contact_uuid"] = $result["contact_uuid"];
			}
			else {
				//debug
					if ($debug) {
						echo "<pre>";
						print_r($result);
						echo "</pre>";
						exit;
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
			$sql = "select u.user_group_uuid, u.domain_uuid, u.user_uuid, u.group_uuid, g.group_name, g.group_level ";
			$sql .= "from v_user_groups as u, v_groups as g  ";
			$sql .= "where u.domain_uuid = :domain_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$sql .= "and u.group_uuid = g.group_uuid ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->bindParam(':domain_uuid', $_SESSION["domain_uuid"] );
			$prep_statement->bindParam(':user_uuid', $_SESSION["user_uuid"]);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$_SESSION["groups"] = $result;
			$_SESSION["user"]["groups"] = $result;
			unset($sql, $row_count, $prep_statement);

		//get the users group level
			$_SESSION["user"]["group_level"] = 0;
			foreach ($_SESSION['user']['groups'] as $row) {
				if ($_SESSION["user"]["group_level"] < $row['group_level']) {
					$_SESSION["user"]["group_level"] = $row['group_level'];
				}
			}

		//get the permissions assigned to the groups that the user is a member of set the permissions in $_SESSION['permissions']
			if (count($_SESSION["groups"]) > 0) {
				$x = 0;
				$sql = "select distinct(permission_name) from v_group_permissions ";
				foreach($_SESSION["groups"] as $field) {
					if (strlen($field['group_name']) > 0) {
						if ($x == 0) {
							$sql .= "where (domain_uuid = '".$_SESSION["domain_uuid"]."' and domain_uuid = null) ";
						}
						else {
							$sql .= "or (domain_uuid = '".$_SESSION["domain_uuid"]."' and domain_uuid = null) ";
						}
						$sql .= "or group_name = '".$field['group_name']."' ";
						$x++;
					}
				}
				$prep_statement_sub = $db->prepare($sql);
				$prep_statement_sub->execute();
				$result = $prep_statement_sub->fetchAll(PDO::FETCH_NAMED);
				if (is_array($result)) {
					foreach ($result as $row) {
						$_SESSION['permissions'][$row["permission_name"]] = true;
						$_SESSION["user"]["permissions"][$row["permission_name"]] = true;
					}
				}
				unset($sql, $prep_statement_sub);
			}

		//get the user settings
			$sql = "select * from v_user_settings ";
			$sql .= "where domain_uuid = '" . $_SESSION["domain_uuid"] . "' ";
			$sql .= "and user_uuid = '" . $_SESSION["user_uuid"] . "' ";
			$sql .= "and user_setting_enabled = 'true' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as $row) {
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
						} else {
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

		//get the extensions that are assigned to this user
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/extensions/app_config.php")) {
				if (isset($_SESSION["user"]) && isset($_SESSION["user_uuid"]) && $db && strlen($_SESSION["domain_uuid"]) > 0 && strlen($_SESSION["user_uuid"]) > 0 && count($_SESSION['user']['extension']) == 0) {
					//get the user extension list
						$_SESSION['user']['extension'] = null;
						$sql = "select ";
						$sql .= "	e.extension_uuid, ";
						$sql .= "	e.extension, ";
						$sql .= "	e.number_alias, ";
						$sql .= "	e.user_context, ";
						$sql .= "	e.outbound_caller_id_name, ";
						$sql .= "	e.outbound_caller_id_number, ";
						$sql .= "	e.description ";
						$sql .= "from ";
						$sql .= "	v_extension_users as u, ";
						$sql .= "	v_extensions as e ";
						$sql .= "where ";
						$sql .= "	e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
						$sql .= "	and e.extension_uuid = u.extension_uuid ";
						$sql .= "	and u.user_uuid = '".$_SESSION['user_uuid']."' ";
						$sql .= "	and e.enabled = 'true' ";
						$sql .= "order by ";
						$sql .= "	e.extension asc ";
						$query = $db->query($sql);
						if($query !== false) {
							$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
							$x = 0;
							foreach($result as $row) {
								//set the destination
								$destination = $row['extension'];
								if (strlen($row['number_alias']) > 0) {
									$destination = $row['number_alias'];
								}

								//build the uers array
								$_SESSION['user']['extension'][$x]['user'] = $row['extension'];
								$_SESSION['user']['extension'][$x]['number_alias'] = $row['number_alias'];
								$_SESSION['user']['extension'][$x]['destination'] = $destination;
								$_SESSION['user']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
								$_SESSION['user']['extension'][$x]['outbound_caller_id_name'] = $row['outbound_caller_id_name'];
								$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
								$_SESSION['user']['extension'][$x]['user_context'] = $row['user_context'];
								$_SESSION['user']['extension'][$x]['description'] = $row['description'];

								//set the user context
								$_SESSION['user']['user_context'] = $row["user_context"];
								$_SESSION['user_context'] = $row["user_context"];
								$x++;
							}
						}
				}
			}

		//redirect the user
			if (check_str($_REQUEST["rdr"]) !== 'n'){
				$path = check_str($_POST["path"]);
				if (isset($path) && !empty($path) && $path!="index2.php" && $path!="/install.php") {
					header("Location: ".$path);
					exit();
				}
				else if ($_SESSION['login']['destination']['url'] != '') {
					header("Location: ".$_SESSION['login']['destination']['url']);
					exit();
				}
			}

		//get the domains
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php")){
				require_once "app/domains/resources/domains.php";
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

//hide the path unless logged in as a superadmin.
	if (!if_group("superadmin")) {
		$v_path_show = false;
	}

?>
