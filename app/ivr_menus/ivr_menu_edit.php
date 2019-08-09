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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/classes/logging.php";
	require_once "resources/classes/ringbacks.php";

//check permissions
	if (permission_exists('ivr_menu_add') || permission_exists('ivr_menu_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//function to show the list of sound files
	// moved to functions.php

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$ivr_menu_uuid = $_REQUEST["id"];
		if (is_uuid($_REQUEST["ivr_menu_uuid"])) {
			$ivr_menu_uuid = $_REQUEST["ivr_menu_uuid"];
		}
	}
	else {
		$action = "add";
		$ivr_menu_uuid = uuid();
	}

//get total ivr menu count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['ivr_menus']['numeric'] != '') {
			$sql = "select count(*) as num_rows from v_ivr_menus where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$total_ivr_menus = $database->select($sql, $parameters, 'column');
			if ($total_ivr_menus >= $_SESSION['limit']['ivr_menus']['numeric']) {
				message::add($text['message-maximum_ivr_menus'].' '.$_SESSION['limit']['ivr_menus']['numeric'], 'negative');
				header('Location: ivr_menus.php');
				exit;
			}
			unset($sql, $parameters, $total_ivr_menus);
		}
	}

//get http post values and set them to php variables
	if (count($_POST) > 0) {

		//get ivr menu
			$ivr_menu_name = $_POST["ivr_menu_name"];
			$ivr_menu_extension = $_POST["ivr_menu_extension"];
			$ivr_menu_greet_long = $_POST["ivr_menu_greet_long"];
			$ivr_menu_greet_short = $_POST["ivr_menu_greet_short"];
			$ivr_menu_options = $_POST["ivr_menu_options"];
			$ivr_menu_invalid_sound = $_POST["ivr_menu_invalid_sound"];
			$ivr_menu_exit_sound = $_POST["ivr_menu_exit_sound"];
			$ivr_menu_confirm_macro = $_POST["ivr_menu_confirm_macro"];
			$ivr_menu_confirm_key = $_POST["ivr_menu_confirm_key"];
			$ivr_menu_tts_engine = $_POST["ivr_menu_tts_engine"];
			$ivr_menu_tts_voice = $_POST["ivr_menu_tts_voice"];
			$ivr_menu_confirm_attempts = $_POST["ivr_menu_confirm_attempts"];
			$ivr_menu_timeout = $_POST["ivr_menu_timeout"];
			$ivr_menu_inter_digit_timeout = $_POST["ivr_menu_inter_digit_timeout"];
			$ivr_menu_max_failures = $_POST["ivr_menu_max_failures"];
			$ivr_menu_max_timeouts = $_POST["ivr_menu_max_timeouts"];
			$ivr_menu_digit_len = $_POST["ivr_menu_digit_len"];
			$ivr_menu_direct_dial = $_POST["ivr_menu_direct_dial"];
			$ivr_menu_ringback = $_POST["ivr_menu_ringback"];
			$ivr_menu_cid_prefix = $_POST["ivr_menu_cid_prefix"];
			$ivr_menu_enabled = $_POST["ivr_menu_enabled"];
			$ivr_menu_description = $_POST["ivr_menu_description"];
			$dialplan_uuid = $_POST["dialplan_uuid"];

		//set the context for users that do not have the permission
			if (permission_exists('ivr_menu_context')) {
				$ivr_menu_context = $_POST["ivr_menu_context"];
			}
			else if ($action == 'add') {
				$ivr_menu_context = $_SESSION['domain_name'];
			}

		//process the values
			$ivr_menu_exit_action = $_POST["ivr_menu_exit_action"];
			//$ivr_menu_exit_action = "transfer:1001 XML default";
			$timeout_action_array = explode(":", $ivr_menu_exit_action);
			$ivr_menu_exit_app = array_shift($timeout_action_array);
			$ivr_menu_exit_data = join(':', $timeout_action_array);

		//set the default ivr_menu_option_action
			if (strlen($ivr_menu_option_action) == 0) {
				$ivr_menu_option_action = "menu-exec-app";
			}
	}

//process the http data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the domain_uuid
			if (permission_exists('ivr_menu_domain')) {
				$domain_uuid = $_POST["domain_uuid"];
			}
			else {
				$_POST["domain_uuid"] = $_SESSION['domain_uuid'];
				$domain_uuid = $_SESSION['domain_uuid'];
			}

		//check for all required data
			$msg = '';
			if (strlen($ivr_menu_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			if (strlen($ivr_menu_extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (strlen($ivr_menu_greet_long) == 0) { $msg .= $text['message-required'].$text['label-greet_long']."<br>\n"; }
			//if (strlen($ivr_menu_greet_short) == 0) { $msg .= $text['message-required'].$text['label-greet_short']."<br>\n"; }
			//if (strlen($ivr_menu_invalid_sound) == 0) { $msg .= $text['message-required'].$text['label-invalid_sound']."<br>\n"; }
			//if (strlen($ivr_menu_exit_sound) == 0) { $msg .= $text['message-required'].$text['label-exit_sound']."<br>\n"; }
			//if (strlen($ivr_menu_confirm_macro) == 0) { $msg .= $text['message-required'].$text['label-comfirm_macro']."<br>\n"; }
			//if (strlen($ivr_menu_confirm_key) == 0) { $msg .= $text['message-required'].$text['label-comfirm_key']."<br>\n"; }
			//if (strlen($ivr_menu_tts_engine) == 0) { $msg .= $text['message-required'].$text['label-tts_engine']."<br>\n"; }
			//if (strlen($ivr_menu_tts_voice) == 0) { $msg .= $text['message-required'].$text['label-tts_voice']."<br>\n"; }
			if (strlen($ivr_menu_confirm_attempts) == 0) { $msg .= $text['message-required'].$text['label-comfirm_attempts']."<br>\n"; }
			if (strlen($ivr_menu_timeout) == 0) { $msg .= $text['message-required'].$text['label-timeout']."<br>\n"; }
			//if (strlen($ivr_menu_exit_app) == 0) { $msg .= $text['message-required'].$text['label-exit_action']."<br>\n"; }
			if (strlen($ivr_menu_inter_digit_timeout) == 0) { $msg .= $text['message-required'].$text['label-inter_digit_timeout']."<br>\n"; }
			if (strlen($ivr_menu_max_failures) == 0) { $msg .= $text['message-required'].$text['label-max_failures']."<br>\n"; }
			if (strlen($ivr_menu_max_timeouts) == 0) { $msg .= $text['message-required'].$text['label-max_timeouts']."<br>\n"; }
			if (strlen($ivr_menu_digit_len) == 0) { $msg .= $text['message-required'].$text['label-digit_length']."<br>\n"; }
			if (strlen($ivr_menu_direct_dial) == 0) { $msg .= $text['message-required'].$text['label-direct_dial']."<br>\n"; }
			//if (strlen($ivr_menu_ringback) == 0) { $msg .= $text['message-required'].$text['label-ring_back']."<br>\n"; }
			
			//if (strlen($ivr_menu_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//array cleanup
			//remove the save
				unset($_POST["submit"]);

			//add the domain_uuid
				if (!is_uuid($_POST["domain_uuid"])) {
					$_POST["domain_uuid"] = $_SESSION['domain_uuid'];
				}

			//seperate the action and the param
				$exit_array = explode(":", $_POST["ivr_menu_exit_action"]);
				$_POST["ivr_menu_exit_app"] = array_shift($exit_array);
				$_POST["ivr_menu_exit_data"] = join(':', $exit_array);
				unset($_POST["ivr_menu_exit_action"]);

			//unset empty options, and seperate the option action from the param
				$x = 0;
				foreach ($_POST["ivr_menu_options"] as $row) {
					if (strlen($row["ivr_menu_option_param"]) == 0) {
						//remove the empty row
						unset($_POST["ivr_menu_options"][$x]);
					}
					else {
						//check if the option param is numeric
						if (is_numeric($row["ivr_menu_option_param"])) {
							//add the ivr menu syntax
							$_POST["ivr_menu_options"][$x]["ivr_menu_option_action"] = "menu-exec-app";
							$_POST["ivr_menu_options"][$x]["ivr_menu_option_param"] = "transfer ".$row["ivr_menu_option_param"]." XML ".$_SESSION['domain_name'];
						}
						else {
							//seperate the action and the param
							$options_array = explode(":", $row["ivr_menu_option_param"]);
							$_POST["ivr_menu_options"][$x]["ivr_menu_option_action"] = array_shift($options_array);
							$_POST["ivr_menu_options"][$x]["ivr_menu_option_param"] = join(':', $options_array);
						}

						//add the domain_uuid
						if (strlen($row["domain_uuid"]) == 0) {
							$_POST["ivr_menu_options"][$x]["domain_uuid"] = $_POST["domain_uuid"];
						}
					}
					$x++;
				}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {

				//used for debugging
					if ($_POST["debug"] == "true") {
						unset($_POST["debug"]);
						echo "<pre>\n";
						print_r($_POST);
						echo "</pre>\n";
						exit;
					}

				//add a uuid to ivr_menu_uuid if it is empty
					if ($action = 'add') {
						$_POST["ivr_menu_uuid"] = $ivr_menu_uuid;
					}

				//add a uuid to dialplan_uuid if it is empty
					if (!is_uuid($dialplan_uuid)) {
						$dialplan_uuid = uuid();
						$_POST["dialplan_uuid"] = $dialplan_uuid;
					}

				//build the xml dialplan
					$ivr_menu_language = explode("/",$_POST["ivr_menu_language"]);
					
					$dialplan_xml = "<extension name=\"".$ivr_menu_name."\" continue=\"false\" uuid=\"".$dialplan_uuid."\">\n";
					$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$ivr_menu_extension."\$\">\n";
					$dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";
					$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
					$dialplan_xml .= "		<action application=\"sleep\" data=\"1000\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"ringback=".$ivr_menu_ringback."\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"presence_id=".$ivr_menu_extension."@".$_SESSION['domain_name']."\"/>\n";
					if (isset($_POST["ivr_menu_language"])) {
						$dialplan_xml .= "		<action application=\"set\" data=\"default_language=".$ivr_menu_language[0]."\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"default_dialect=".$ivr_menu_language[1]."\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"default_voice=".$ivr_menu_language[2]."\"/>\n";
					}
					$dialplan_xml .= "		<action application=\"set\" data=\"transfer_ringback=".$ivr_menu_ringback."\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"ivr_menu_uuid=".$ivr_menu_uuid."\"/>\n";

					if ($_SESSION['ivr_menu']['application']['text'] == "lua") {
						$dialplan_xml .= "		<action application=\"lua\" data=\"ivr_menu.lua\"/>\n";
					}
					else {
						$dialplan_xml .= "		<action application=\"ivr\" data=\"".$ivr_menu_uuid."\"/>\n";
					}

					$dialplan_xml .= "		<action application=\"".$ivr_menu_exit_app."\" data=\"".$ivr_menu_exit_data."\"/>\n";
					$dialplan_xml .= "	</condition>\n";
					$dialplan_xml .= "</extension>\n";

				//build the dialplan array
					$dialplan["domain_uuid"] = $_SESSION['domain_uuid'];
					$dialplan["dialplan_uuid"] = $dialplan_uuid;
					$dialplan["dialplan_name"] = $ivr_menu_name;
					$dialplan["dialplan_number"] = $ivr_menu_extension;
					if (isset($ivr_menu_context)) {
						$dialplan["dialplan_context"] = $ivr_menu_context;
					}
					$dialplan["dialplan_continue"] = "false";
					$dialplan["dialplan_xml"] = $dialplan_xml;
					$dialplan["dialplan_order"] = "101";
					$dialplan["dialplan_enabled"] = "true";
					$dialplan["dialplan_description"] = $ivr_menu_description;
					$dialplan["app_uuid"] = "a5788e9b-58bc-bd1b-df59-fff5d51253ab";

				//prepare the array
					$array['ivr_menus'][] = $_POST;
					$array['dialplans'][] = $dialplan;

				//add the dialplan permission
					$p = new permissions;
					if ($action = "add") {
						$p->add("dialplan_add", "temp");
					}
					else if ($action = "update") {
						$p->add("dialplan_edit", "temp");
					}

				//save to the data
					$database = new database;
					$database->app_name = 'ivr_menus';
					$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
					if (is_uuid($ivr_menu_uuid)) {
						$database->uuid($ivr_menu_uuid);
					}
					$database->save($array);
					$message = $database->message;

				//remove the temporary permission
					$p->delete("dialplan_add", "temp");
					$p->delete("dialplan_edit", "temp");

				//save the ivr menu
					//$ivr = new ivr_menu;
					//$ivr->domain_uuid = $_SESSION["domain_uuid"];
					//if (strlen($ivr_menu_uuid ) > 0) {
					//	$ivr->ivr_menu_uuid = $ivr_menu_uuid;
					//}
					//$response = $ivr->save($_POST);
					//if (strlen($response['uuid']) > 0) {
					//	$ivr_menu_uuid = $response['uuid'];
					//}

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$_SESSION["context"]);
					$cache->delete("configuration:ivr.conf:".$ivr_menu_uuid);

				//set the add message
					if ($action == "add" && permission_exists('ivr_menu_add')) {
						message::add($text['message-add']);
					}

				//set the update message
					if ($action == "update" && permission_exists('ivr_menu_edit')) {
						message::add($text['message-update']);
					}

				//redirect the user
					header("Location: ivr_menu_edit.php?id=".escape($ivr_menu_uuid));
					return;

			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (!is_uuid($ivr_menu_uuid)) { $ivr_menu_uuid = $_REQUEST["id"]; }
	if (is_uuid($ivr_menu_uuid) && $_POST["persistformvar"] != "true") {
		$ivr = new ivr_menu;
		$ivr->domain_uuid = $_SESSION["domain_uuid"];
		$ivr->ivr_menu_uuid = $ivr_menu_uuid;
		$ivr_menus = $ivr->find();
		if (is_array($ivr_menus)) {
			foreach ($ivr_menus as &$row) {
				$dialplan_uuid = $row["dialplan_uuid"];
				$ivr_menu_name = $row["ivr_menu_name"];
				$ivr_menu_extension = $row["ivr_menu_extension"];
				$ivr_menu_language = $row["ivr_menu_language"];
				$ivr_menu_dialect = $row["ivr_menu_dialect"];
				$ivr_menu_voice = $row["ivr_menu_voice"];
				$ivr_menu_greet_long = $row["ivr_menu_greet_long"];
				$ivr_menu_greet_short = $row["ivr_menu_greet_short"];
				$ivr_menu_invalid_sound = $row["ivr_menu_invalid_sound"];
				$ivr_menu_exit_sound = $row["ivr_menu_exit_sound"];
				$ivr_menu_confirm_macro = $row["ivr_menu_confirm_macro"];
				$ivr_menu_confirm_key = $row["ivr_menu_confirm_key"];
				$ivr_menu_tts_engine = $row["ivr_menu_tts_engine"];
				$ivr_menu_tts_voice = $row["ivr_menu_tts_voice"];
				$ivr_menu_confirm_attempts = $row["ivr_menu_confirm_attempts"];
				$ivr_menu_timeout = $row["ivr_menu_timeout"];
				$ivr_menu_exit_app = $row["ivr_menu_exit_app"];
				$ivr_menu_exit_data = $row["ivr_menu_exit_data"];
				$ivr_menu_inter_digit_timeout = $row["ivr_menu_inter_digit_timeout"];
				$ivr_menu_max_failures = $row["ivr_menu_max_failures"];
				$ivr_menu_max_timeouts = $row["ivr_menu_max_timeouts"];
				$ivr_menu_digit_len = $row["ivr_menu_digit_len"];
				$ivr_menu_direct_dial = $row["ivr_menu_direct_dial"];
				$ivr_menu_ringback = $row["ivr_menu_ringback"];
				$ivr_menu_cid_prefix = $row["ivr_menu_cid_prefix"];
				$ivr_menu_context = $row["ivr_menu_context"];
				$ivr_menu_enabled = $row["ivr_menu_enabled"];
				$ivr_menu_description = $row["ivr_menu_description"];

				//replace the dash with a space
				$ivr_menu_name = str_replace("-", " ", $ivr_menu_name);

				if (strlen($ivr_menu_exit_app) > 0) {
					$ivr_menu_exit_action = $ivr_menu_exit_app.":".$ivr_menu_exit_data;
				}
			}
		}
		unset($ivr_menus, $row);
	}

//get the ivr menu options
	$sql = "select * from v_ivr_menu_options ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and ivr_menu_uuid = :ivr_menu_uuid ";
	$sql .= "order by ivr_menu_option_digits, ivr_menu_option_order asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['ivr_menu_uuid'] = $ivr_menu_uuid;
	$database = new database;
	$ivr_menu_options = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row to the options array
	if (count($ivr_menu_options) == 0) {
		$rows = $_SESSION['ivr_menu']['option_add_rows']['numeric'];
		$id = 0;
	}
	if (count($ivr_menu_options) > 0) {
		$rows = $_SESSION['ivr_menu']['option_edit_rows']['numeric'];
		$id = count($ivr_menu_options)+1;
	}
	for ($x = 0; $x < $rows; $x++) {
		$ivr_menu_options[$id]['ivr_menu_option_digits'] = '';
		$ivr_menu_options[$id]['ivr_menu_option_action'] = '';
		$ivr_menu_options[$id]['ivr_menu_option_param'] = '';
		$ivr_menu_options[$id]['ivr_menu_option_order'] = '';
		$ivr_menu_options[$id]['ivr_menu_option_description'] = '';
		$id++;
	}

//set the defaults
	if (strlen($ivr_menu_timeout) == 0) { $ivr_menu_timeout = '3000'; }
	if (strlen($ivr_menu_ringback) == 0) { $ivr_menu_ringback = 'local_stream://default'; }
	if (strlen($ivr_menu_invalid_sound) == 0) { $ivr_menu_invalid_sound = 'ivr/ivr-that_was_an_invalid_entry.wav'; }
	//if (strlen($ivr_menu_confirm_key) == 0) { $ivr_menu_confirm_key = '#'; }
	if (strlen($ivr_menu_language_code) == 0) { $ivr_menu_language_code = 'en'; }
	if (strlen($ivr_menu_dialect) == 0) { $ivr_menu_dialect = 'us'; }
	if (strlen($ivr_menu_voice) == 0) { $ivr_menu_voice = 'callie'; }
	if (strlen($ivr_menu_tts_engine) == 0) { $ivr_menu_tts_engine = 'flite'; }
	if (strlen($ivr_menu_tts_voice) == 0) { $ivr_menu_tts_voice = 'rms'; }
	if (strlen($ivr_menu_confirm_attempts) == 0) { $ivr_menu_confirm_attempts = '1'; }
	if (strlen($ivr_menu_inter_digit_timeout) == 0) { $ivr_menu_inter_digit_timeout = '2000'; }
	if (strlen($ivr_menu_max_failures) == 0) { $ivr_menu_max_failures = '1'; }
	if (strlen($ivr_menu_max_timeouts) == 0) { $ivr_menu_max_timeouts = '1'; }
	if (strlen($ivr_menu_digit_len) == 0) { $ivr_menu_digit_len = '5'; }
	if (strlen($ivr_menu_direct_dial) == 0) { $ivr_menu_direct_dial = 'false'; }
	if (!isset($ivr_menu_context)) { $ivr_menu_context = $_SESSION['domain_name']; }
	if (strlen($ivr_menu_enabled) == 0) { $ivr_menu_enabled = 'true'; }
	if (!isset($ivr_menu_exit_action)) { $ivr_menu_exit_action = ''; }

//get installed languages
	$language_paths = glob($_SESSION["switch"]['sounds']['dir']."/*/*/*");
	foreach ($language_paths as $key => $path) {
		$path = str_replace($_SESSION["switch"]['sounds']['dir'].'/', "", $path);
		$path_array = explode('/', $path);
		if (count($path_array) <> 3 || strlen($path_array[0]) <> 2 || strlen($path_array[1]) <> 2) {
			unset($language_paths[$key]);
		}
		$language_paths[$key] = str_replace($_SESSION["switch"]['sounds']['dir']."/","",$language_paths[$key]);
		if (strlen($language_paths[$key]) == 0) {
			unset($language_paths[$key]);
		}
	}

//get the recordings
	$sql = "select recording_name, recording_filename from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$recordings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the phrases
	$sql = "select * from v_phrases ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$phrases = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the sound files
	$file = new file;
	$sound_files = $file->sounds();

//content
	require_once "resources/header.php";
	$document['title'] = $text['title-ivr_menu'];

	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	$('#show_advanced_box').slideToggle();\n";
	echo "	$('#show_advanced').slideToggle();\n";
	echo "}\n";
	echo "</script>";

	echo "<form method='post' name='frm' action=''>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left' valign='top'>";
	echo "		<b>".$text['header-ivr_menu']."</b>";
	echo "		<br><br>";
	echo "	</td>\n";
	echo "	<td align='right' nowrap='nowrap' valign='top'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='ivr_menus.php'\" value='".$text['button-back']."'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"if (confirm('".$text['confirm-copy']."')){window.location='ivr_menu_copy.php?id=".escape($ivr_menu_uuid)."';}\" value='".$text['button-copy']."'>\n";
	echo "		<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='2' align='left' valign='top'>";
	echo "		".$text['description-ivr_menu'];
	echo "		<br><br>";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ivr_menu_name' maxlength='255' value=\"".escape($ivr_menu_name)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ivr_menu_extension' maxlength='255' value='".escape($ivr_menu_extension)."' required='required'>\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <select class='formfld' type='text' name='ivr_menu_language'>\n";
	echo "		<option></option>\n";

	if (empty($ivr_menu_language)) {
		$ivr_menu_language = "$ivr_menu_language_code/$ivr_menu_dialect/$ivr_menu_voice";
		$language_formatted = "$ivr_menu_language_code-$ivr_menu_dialect $ivr_menu_voice";
		echo "		<option value='".escape($ivr_menu_language)."'>".escape($language_formatted)."</option>\n";
	}
	else {
		$language_array = explode ('/', $ivr_menu_language);
		$language_formatted = $language_array[0]."-".$language_array[1]." ".$language_array[2];
		echo "		<option value='".escape($ivr_menu_language)."' selected='selected'>".escape($language_formatted)."</option>\n";
	}
	
	foreach ($language_paths as $key => $language_variables) {
		$language_variables = explode ('/',$language_paths[$key]);
		$language = $language_variables[0];
		$dialect = $language_variables[1];
		$voice = $language_variables[2];
		if ($language_formatted <> "$language-$dialect $voice") {
			echo "		<option value='$language/$dialect/$voice'>$language-$dialect $voice</option>\n";
		}
	}
	echo "<br />\n";
	echo $text['description-language']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-greet_long']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (if_group("superadmin")) {
		$destination_id = "ivr_menu_greet_long";
		$script = "<script>\n";
		$script .= "var objs;\n";
		$script .= "\n";
		$script .= "function changeToInput".$destination_id."(obj){\n";
		$script .= "	tb=document.createElement('INPUT');\n";
		$script .= "	tb.type='text';\n";
		$script .= "	tb.name=obj.name;\n";
		$script .= "	tb.className='formfld';\n";
		$script .= "	tb.setAttribute('id', '".$destination_id."');\n";
		$script .= "	tb.setAttribute('style', '".$select_style."');\n";
		if ($on_change != '') {
			$script .= "	tb.setAttribute('onchange', \"".$on_change."\");\n";
			$script .= "	tb.setAttribute('onkeyup', \"".$on_change."\");\n";
		}
		$script .= "	tb.value=obj.options[obj.selectedIndex].value;\n";
		$script .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.visibility = 'hidden';\n";
		$script .= "	tbb=document.createElement('INPUT');\n";
		$script .= "	tbb.setAttribute('class', 'btn');\n";
		$script .= "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		$script .= "	tbb.type='button';\n";
		$script .= "	tbb.value=$('<div />').html('&#9665;').text();\n";
		$script .= "	tbb.objs=[obj,tb,tbb];\n";
		$script .= "	tbb.onclick=function(){ Replace".$destination_id."(this.objs); }\n";
		$script .= "	obj.parentNode.insertBefore(tb,obj);\n";
		$script .= "	obj.parentNode.insertBefore(tbb,obj);\n";
		$script .= "	obj.parentNode.removeChild(obj);\n";
		$script .= "	Replace".$destination_id."(this.objs);\n";
		$script .= "}\n";
		$script .= "\n";
		$script .= "function Replace".$destination_id."(obj){\n";
		$script .= "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		$script .= "	obj[0].parentNode.removeChild(obj[1]);\n";
		$script .= "	obj[0].parentNode.removeChild(obj[2]);\n";
		$script .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.visibility = 'visible';\n";
		if ($on_change != '') {
			$script .= "	".$on_change.";\n";
		}
		$script .= "}\n";
		$script .= "</script>\n";
		$script .= "\n";
		echo $script;
	}
	echo "<select name='ivr_menu_greet_long' id='ivr_menu_greet_long' class='formfld'>\n";
	echo "	<option></option>\n";
	//misc optgroup
		if (if_group("superadmin")) {
			echo "<optgroup label='Misc'>\n";
			echo "	<option value='say:'>say:</option>\n";
			echo "	<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
	//recordings
		$tmp_selected = false;
		if (is_array($recordings)) {
			echo "<optgroup label='Recordings'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				if ($ivr_menu_greet_long == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($ivr_menu_greet_long) > 0) {
					$tmp_selected = true;
					echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
				}
				else if ($ivr_menu_greet_long == $recording_filename && strlen($ivr_menu_greet_long) > 0) {
					$tmp_selected = true;
					echo "	<option value='".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
				}
				else {
					echo "	<option value='".escape($recording_filename)."'>".escape($recording_name)."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//phrases
		if (is_array($phrases)) {
			echo "<optgroup label='Phrases'>\n";
			foreach ($phrases as &$row) {
				if ($ivr_menu_greet_long == "phrase:".$row["phrase_uuid"]) {
					$tmp_selected = true;
					echo "	<option value='phrase:".escape($row["phrase_uuid"])."' selected='selected'>".escape($row["phrase_name"])."</option>\n";
				}
				else {
					echo "	<option value='phrase:".escape($row["phrase_uuid"])."'>".escape($row["phrase_name"])."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//sounds
		/*
		if (is_array($sound_files)) {
			echo "<optgroup label='Sounds'>\n";
			foreach ($sound_files as $value) {
				if (strlen($value) > 0) {
					if (substr($ivr_menu_greet_long, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$ivr_menu_greet_long = substr($ivr_menu_greet_long, 71);
					}
					if ($ivr_menu_greet_long == $value) {
						$tmp_selected = true;
						echo "	<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
					}
					else {
						echo "	<option value='".escape($value)."'>".escape($value)."</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
		}
		*/
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected && strlen($ivr_menu_greet_long) > 0) {
				echo "<optgroup label='Selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_greet_long)) {
					echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($ivr_menu_greet_long)."' selected='selected'>".escape($ivr_menu_greet_long)."</option>\n";
				}
				else if (substr($ivr_menu_greet_long, -3) == "wav" || substr($ivr_menu_greet_long, -3) == "mp3") {
					echo "	<option value='".escape($ivr_menu_greet_long)."' selected='selected'>".escape($ivr_menu_greet_long)."</option>\n";
				}
				else {
					echo "	<option value='".escape($ivr_menu_greet_long)."' selected='selected'>".escape($ivr_menu_greet_long)."</option>\n";
				}
				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".escape($destination_id)."' class='btn' name='' alt='back' onclick='changeToInput".escape($destination_id)."(document.getElementById(\"".escape($destination_id)."\"));this.style.visibility = \"hidden\";' value='&#9665;'>";
		unset($destination_id);
	}
	echo "	<br />\n";
	echo $text['description-greet_long']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-greet_short']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (if_group("superadmin")) {
		$destination_id = "ivr_menu_greet_short";
		$script = "<script>\n";
		$script .= "var objs;\n";
		$script .= "\n";
		$script .= "function changeToInput".$destination_id."(obj){\n";
		$script .= "	tb=document.createElement('INPUT');\n";
		$script .= "	tb.type='text';\n";
		$script .= "	tb.name=obj.name;\n";
		$script .= "	tb.className='formfld';\n";
		$script .= "	tb.setAttribute('id', '".$destination_id."');\n";
		$script .= "	tb.setAttribute('style', '".$select_style."');\n";
		if ($on_change != '') {
			$script .= "	tb.setAttribute('onchange', \"".$on_change."\");\n";
			$script .= "	tb.setAttribute('onkeyup', \"".$on_change."\");\n";
		}
		$script .= "	tb.value=obj.options[obj.selectedIndex].value;\n";
		$script .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.visibility = 'hidden';\n";
		$script .= "	tbb=document.createElement('INPUT');\n";
		$script .= "	tbb.setAttribute('class', 'btn');\n";
		$script .= "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		$script .= "	tbb.type='button';\n";
		$script .= "	tbb.value=$('<div />').html('&#9665;').text();\n";
		$script .= "	tbb.objs=[obj,tb,tbb];\n";
		$script .= "	tbb.onclick=function(){ Replace".$destination_id."(this.objs); }\n";
		$script .= "	obj.parentNode.insertBefore(tb,obj);\n";
		$script .= "	obj.parentNode.insertBefore(tbb,obj);\n";
		$script .= "	obj.parentNode.removeChild(obj);\n";
		$script .= "	Replace".$destination_id."(this.objs);\n";
		$script .= "}\n";
		$script .= "\n";
		$script .= "function Replace".$destination_id."(obj){\n";
		$script .= "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		$script .= "	obj[0].parentNode.removeChild(obj[1]);\n";
		$script .= "	obj[0].parentNode.removeChild(obj[2]);\n";
		$script .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.visibility = 'visible';\n";
		if ($on_change != '') {
			$script .= "	".$on_change.";\n";
		}
		$script .= "}\n";
		$script .= "</script>\n";
		$script .= "\n";
		echo $script;
	}
	echo "<select name='ivr_menu_greet_short' id='ivr_menu_greet_short' class='formfld'>\n";
	echo "	<option></option>\n";
	//misc
		if (if_group("superadmin")) {
			echo "<optgroup label='Misc'>\n";
			echo "	<option value='say:'>say:</option>\n";
			echo "	<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
	//recordings
		$tmp_selected = false;
		if (is_array($recordings)) {
			echo "<optgroup label='Recordings'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				if ($ivr_menu_greet_short == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".escape($recording_filename) && strlen($ivr_menu_greet_short) > 0) {
					$tmp_selected = true;
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
				}
				else if ($ivr_menu_greet_short == $recording_filename && strlen($ivr_menu_greet_short) > 0) {
					$tmp_selected = true;
					echo "	<option value='".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
				}
				else {
					echo "	<option value='".escape($recording_filename)."'>".escape($recording_name)."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//phrases
		if (is_array($phrases)) {
			echo "<optgroup label='Phrases'>\n";
			foreach ($phrases as &$row) {
				if ($ivr_menu_greet_short == "phrase:".$row["phrase_uuid"]) {
					$tmp_selected = true;
					echo "	<option value='phrase:".escape($row["phrase_uuid"])."' selected='selected'>".escape($row["phrase_name"])."</option>\n";
				}
				else {
					echo "	<option value='phrase:".escape($row["phrase_uuid"])."'>".escape($row["phrase_name"])."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//sounds
		/*
		if (is_array($sound_files)) {
			echo "<optgroup label='Sounds'>\n";
			foreach ($sound_files as $value) {
				if (strlen($value) > 0) {
					if (substr($ivr_menu_greet_short, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$ivr_menu_greet_short = substr($ivr_menu_greet_short, 71);
					}
					if ($ivr_menu_greet_short == $value) {
						$tmp_selected = true;
						echo "	<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
					}
					else {
						echo "	<option value='".escape($value)."'>".escape($value)."</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
		}
		*/
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected && strlen($ivr_menu_greet_short) > 0) {
				echo "<optgroup label='Selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_greet_short)) {
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_greet_short."' selected='selected'>".escape($ivr_menu_greet_short)."</option>\n";
				}
				else if (substr($ivr_menu_greet_short, -3) == "wav" || substr($ivr_menu_greet_short, -3) == "mp3") {
					echo "	<option value='".escape($ivr_menu_greet_short)."' selected='selected'>".escape($ivr_menu_greet_short)."</option>\n";
				}
				else {
					echo "	<option value='".escape($ivr_menu_greet_short)."' selected='selected'>".escape($ivr_menu_greet_short)."</option>\n";
				}
				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".escape($destination_id)."' class='btn' name='' alt='back' onclick='changeToInput".escape($destination_id)."(document.getElementById(\"".escape($destination_id)."\"));this.style.visibility = \"hidden\";' value='&#9665;'>";
		unset($destination_id);
	}
	echo "<br />\n";
	echo $text['description-greet_short']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-options']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<td class='vtable'>".$text['label-option']."</td>\n";
	echo "					<td class='vtable'>".$text['label-destination']."</td>\n";
	echo "					<td class='vtable'>".$text['label-order']."</td>\n";
	echo "					<td class='vtable'>".$text['label-description']."</td>\n";
	echo "					<td></td>\n";
	echo "				</tr>\n";
	if (is_array($ivr_menu_options)) {
		$c = 0;
		foreach($ivr_menu_options as $field) {

			//add the primary key uuid
			if (strlen($field['ivr_menu_option_uuid']) > 0) {
				echo "	<input name='ivr_menu_options[".$c."][ivr_menu_option_uuid]' type='hidden' value=\"".escape($field['ivr_menu_option_uuid'])."\">\n";
			}

			echo "<td class='formfld' align='left'>\n";
			echo "  <input class='formfld' style='width:70px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_digits]' maxlength='255' value='".escape($field['ivr_menu_option_digits'])."'>\n";
			echo "</td>\n";

			echo "<td class='formfld' align='left' nowrap='nowrap'>\n";
			$destination_action = '';
			if (strlen($field['ivr_menu_option_action'].$field['ivr_menu_option_param']) > 0) {
				$destination_action = $field['ivr_menu_option_action'].':'.$field['ivr_menu_option_param'];
			} else { $destination_action = ''; }
			echo $destination->select('ivr', 'ivr_menu_options['.$c.'][ivr_menu_option_param]', $destination_action);
			unset($destination_action);
			echo "</td>\n";

			echo "<td class='formfld' align='left'>\n";
			echo "	<select name='ivr_menu_options[".$c."][ivr_menu_option_order]' class='formfld' style='width:55px'>\n";
			//echo "	<option></option>\n";
			if (strlen(htmlspecialchars($field['ivr_menu_option_order']))> 0) {
				if (strlen($field['ivr_menu_option_order']) == 1) { $field['ivr_menu_option_order'] = "00".$field['ivr_menu_option_order']; }
				if (strlen($field['ivr_menu_option_order']) == 2) { $field['ivr_menu_option_order'] = "0".$field['ivr_menu_option_order']; }
				echo "	<option selected='yes' value='".escape($field['ivr_menu_option_order'])."'>".escape($field['ivr_menu_option_order'])."</option>\n";
			}
			$i=0;
			while($i<=999) {
				if (strlen($i) == 1) { echo "	<option value='00$i'>00$i</option>\n"; }
				if (strlen($i) == 2) { echo "	<option value='0$i'>0$i</option>\n"; }
				if (strlen($i) == 3) { echo "	<option value='$i'>$i</option>\n"; }
				$i++;
			}
			echo "	</select>\n";
			echo "</td>\n";

			echo "<td class='formfld' align='left'>\n";
			echo "	<input class='formfld' style='width:100px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_description]' maxlength='255' value=\"".$field['ivr_menu_option_description']."\">\n";
			echo "</td>\n";

			echo "					<td class='list_control_icons'>";
			if (strlen($field['ivr_menu_option_uuid']) > 0) {
				//echo "						<a href='ivr_menu_option_edit.php?id=".$field['ivr_menu_option_uuid']."&ivr_menu_uuid=".$field['ivr_menu_uuid']."' alt='edit'>$v_link_label_edit</a>";
				echo "						<a href='ivr_menu_option_delete.php?id=".escape($field['ivr_menu_option_uuid'])."&ivr_menu_uuid=".escape($field['ivr_menu_uuid'])."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			else {
				echo "						&nbsp;\n";
			}
			echo "					</td>\n";
			echo "				</tr>\n";

			$c++;
		}
	}
	unset($sql, $result);

	/*
	for ($c = 0; $c < 1; $c++) {
		echo "				<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' style='width:70px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_digits]' maxlength='255' value='$ivr_menu_option_digits'>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' nowrap='nowrap'>\n";
		echo $destination->select('ivr', 'ivr_menu_options['.$c.'][ivr_menu_option_param]', $destination_action);
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select name='ivr_menu_options[".$c."][ivr_menu_option_order]' class='formfld' style='width:55px'>\n";
		//echo "	<option></option>\n";
		if (strlen(htmlspecialchars($ivr_menu_option_order))> 0) {
			echo "	<option selected='yes' value='".escape($ivr_menu_option_order)."'>".escape($ivr_menu_option_order)."</option>\n";
		}
		$i=0;
		while($i<=999) {
			if (strlen($i) == 1) {
				echo "	<option value='00$i'>00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "	<option value='0$i'>0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "	<option value='$i'>$i</option>\n";
			}
			$i++;
		}
		echo "	</select>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:100px' type='text' name='ivr_menu_options[".$c."][ivr_menu_option_description]' maxlength='255' value=\"".escape($ivr_menu_option_description)."\">\n";
		echo "</td>\n";

		echo "					<td>\n";
		echo "						<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
		echo "					</td>\n";
		echo "				</tr>\n";
	}
	*/
	echo "			</table>\n";

	echo "			".$text['description-options']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='ivr_menu_timeout' maxlength='255' min='1' step='1' value='".escape($ivr_menu_timeout)."' required='required'>\n";
	echo "<br />\n";
	echo $text['description-timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-exit_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'ivr_menu_exit_action', $ivr_menu_exit_action);
	echo "	<br />\n";
	echo "	".$text['description-exit_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-direct_dial']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ivr_menu_direct_dial'>\n";
	if ($ivr_menu_direct_dial == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ivr_menu_direct_dial == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-direct_dial']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	 ".$text['label-ring_back']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$ringbacks = new ringbacks;
	echo $ringbacks->select('ivr_menu_ringback', $ivr_menu_ringback);

	echo "<br />\n";
	echo $text['description-ring_back']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ivr_menu_cid_prefix' maxlength='255' value=\"".escape($ivr_menu_cid_prefix)."\">\n";
	echo "<br />\n";
	echo $text['description-caller_id_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//--- begin: show_advanced -----------------------
		echo "	<div id=\"show_advanced_box\">\n";
		echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "		<tr>\n";
		echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
		echo "		<td width=\"70%\" class=\"vtable\">\n";
		echo "			<input type=\"button\" class='btn' onClick=\"show_advanced_config()\" value=\"".$text['button-advanced']."\"></input></a>\n";
		echo "		</td>\n";
		echo "		</tr>\n";
		echo "		</table>\n";
		echo "	</div>\n";

		echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
		echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

		echo "<tr>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-invalid_sound']."\n";
		echo "</td>\n";
		echo "<td width=\"70%\" class='vtable' align='left'>\n";
		echo "<select name='ivr_menu_invalid_sound' class='formfld' style='width: 350px;' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
		//misc optgroup
			if (if_group("superadmin")) {
				echo "<optgroup label='Misc'>\n";
				echo "	<option value='phrase:'>phrase:</option>\n";
				echo "	<option value='say:'>say:</option>\n";
				echo "	<option value='tone_stream:'>tone_stream:</option>\n";
				echo "</optgroup>\n";
			}
		//recordings
			$tmp_selected = false;
			if (is_array($recordings)) {
				echo "<optgroup label='Recordings'>\n";
				foreach ($recordings as &$row) {
					$recording_name = $row["recording_name"];
					$recording_filename = $row["recording_filename"];
					if ($ivr_menu_invalid_sound == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($ivr_menu_invalid_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
					}
					else if ($ivr_menu_invalid_sound == $recording_filename && strlen($ivr_menu_invalid_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
					}
					else {
						echo "	<option value='".escape($recording_filename)."'>".escape($recording_name)."</option>\n";
					}
				}
				echo "</optgroup>\n";
			}
		//phrases
			if (is_array($phrases)) {
				echo "<optgroup label='Phrases'>\n";
				foreach ($result as &$row) {
					if ($ivr_menu_invalid_sound == "phrase:".$row["phrase_uuid"]) {
						$tmp_selected = true;
						echo "	<option value='phrase:".escape($row["phrase_uuid"])."' selected='selected'>".escape($row["phrase_name"])."</option>\n";
					}
					else {
						echo "	<option value='phrase:".escape($row["phrase_uuid"])."'>".escape($row["phrase_name"])."</option>\n";
					}
				}
				unset ($prep_statement);
				echo "</optgroup>\n";
			}
		//sounds
			if (is_array($sound_files)) {
				echo "<optgroup label='Sounds'>\n";
				foreach ($sound_files as $value) {
					if (strlen($value) > 0) {
						if (substr($ivr_menu_invalid_sound, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
							$ivr_menu_invalid_sound = substr($ivr_menu_invalid_sound, 71);
						}
						if ($ivr_menu_invalid_sound == $value) {
							$tmp_selected = true;
							echo "	<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
						}
						else {
							echo "	<option value='".escape($value)."'>".escape($value)."</option>\n";
						}
					}
				}
				echo "</optgroup>\n";
			}
		//select
			if (if_group("superadmin")) {
				if (!$tmp_selected && strlen($ivr_menu_invalid_sound) > 0) {
					echo "<optgroup label='Selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_invalid_sound)) {
						echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($ivr_menu_invalid_sound)."' selected='selected'>".escape($ivr_menu_invalid_sound)."</option>\n";
					}
					else if (substr($ivr_menu_invalid_sound, -3) == "wav" || substr($ivr_menu_invalid_sound, -3) == "mp3") {
						echo "	<option value='".escape($ivr_menu_invalid_sound)."' selected='selected'>".escape($ivr_menu_invalid_sound)."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		echo "</select>\n";
		echo "<br />\n";
		echo $text['description-invalid_sound']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-exit_sound']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "<select name='ivr_menu_exit_sound' class='formfld' style='width: 350px;' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
		echo "	<option value=''></option>\n";
		//misc optgroup
			if (if_group("superadmin")) {
				echo "<optgroup label='Misc'>\n";
				echo "	<option value='phrase:'>phrase:</option>\n";
				echo "	<option value='say:'>say:</option>\n";
				echo "	<option value='tone_stream:'>tone_stream:</option>\n";
				echo "</optgroup>\n";
			}
		//recordings
			$tmp_selected = false;
			if (is_array($recordings)) {
				echo "<optgroup label='Recordings'>\n";
				foreach ($recordings as &$row) {
					$recording_name = $row["recording_name"];
					$recording_filename = $row["recording_filename"];
					if ($ivr_menu_exit_sound == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($ivr_menu_exit_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
					}
					else if ($ivr_menu_exit_sound == $recording_filename && strlen($ivr_menu_exit_sound) > 0) {
						$tmp_selected = true;
						echo "	<option value='".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
					}
					else {
						echo "	<option value='".escape($recording_filename)."'>".escape($recording_name)."</option>\n";
					}
				}
				echo "</optgroup>\n";
			}
		//phrases
			if (is_array($phrases)) {
				echo "<optgroup label='Phrases'>\n";
				foreach ($phrases as &$row) {
					if ($ivr_menu_exit_sound == "phrase:".$row["phrase_uuid"]) {
						$tmp_selected = true;
						echo "	<option value='phrase:".escape($row["phrase_uuid"])."' selected='selected'>".escape($row["phrase_name"])."</option>\n";
					}
					else {
						echo "	<option value='phrase:".escape($row["phrase_uuid"])."'>".escape($row["phrase_name"])."</option>\n";
					}
				}
				unset ($prep_statement);
				echo "</optgroup>\n";
			}
		//sounds
			if (is_array($sound_files)) {
				echo "<optgroup label='Sounds'>\n";
				foreach ($sound_files as $value) {
					if (strlen($value) > 0) {
						if (substr($ivr_menu_exit_sound, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
							$ivr_menu_exit_sound = substr($ivr_menu_exit_sound, 71);
						}
						if ($ivr_menu_exit_sound == $value) {
							$tmp_selected = true;
							echo "	<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
						}
						else {
							echo "	<option value='".escape($value)."'>".escape($value)."</option>\n";
						}
					}
				}
				echo "</optgroup>\n";
			}
		//select
			if (if_group("superadmin")) {
				if (!$tmp_selected && strlen($ivr_menu_exit_sound) > 0) {
					echo "<optgroup label='Selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$ivr_menu_exit_sound)) {
						echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($ivr_menu_exit_sound)."' selected='selected'>".escape($ivr_menu_exit_sound)."</option>\n";
					}
					else if (substr($ivr_menu_exit_sound, -3) == "wav" || substr($ivr_menu_exit_sound, -3) == "mp3") {
						echo "	<option value='".escape($ivr_menu_exit_sound)."' selected='selected'>".escape($ivr_menu_exit_sound)."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		echo "</select>\n";
		echo "<br />\n";
		echo $text['description-exit_sound']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_macro']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_macro' maxlength='255' value=\"".escape($ivr_menu_confirm_macro)."\">\n";
		echo "<br />\n";
		echo $text['description-comfirm_macro']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_key']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_key' maxlength='255' value=\"".escape($ivr_menu_confirm_key)."\">\n";
		echo "<br />\n";
		echo $text['description-comfirm_key']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-tts_engine']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_tts_engine' maxlength='255' value=\"".escape($ivr_menu_tts_engine)."\">\n";
		echo "<br />\n";
		echo $text['description-tts_engine']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-tts_voice']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_tts_voice' maxlength='255' value=\"".escape($ivr_menu_tts_voice)."\">\n";
		echo "<br />\n";
		echo $text['description-tts_voice']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_attempts']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_confirm_attempts' maxlength='255' min='1' step='1' value='".escape($ivr_menu_confirm_attempts)."' required='required'>\n";
		echo "<br />\n";
		echo $text['description-comfirm_attempts']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-inter-digit_timeout']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_inter_digit_timeout' maxlength='255' min='1' step='1' value='".escape($ivr_menu_inter_digit_timeout)."' required='required'>\n";
		echo "<br />\n";
		echo $text['description-inter-digit_timeout']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-max_failures']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_max_failures' maxlength='255' min='0' step='1' value='".escape($ivr_menu_max_failures)."' required='required'>\n";
		echo "<br />\n";
		echo $text['description-max_failures']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-max_timeouts']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_max_timeouts' maxlength='255' min='0' step='1' value='".escape($ivr_menu_max_timeouts)."' required='required'>\n";
		echo "<br />\n";
		echo $text['description-max_timeouts']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-digit_length']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ivr_menu_digit_len' maxlength='255' min='1' step='1' value='".escape($ivr_menu_digit_len)."' required='required'>\n";
		echo "<br />\n";
		echo $text['description-digit_length']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('ivr_menu_domain')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-domain']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='domain_uuid'>\n";
			foreach ($_SESSION['domains'] as $row) {
				if ($row['domain_uuid'] == $domain_uuid) {
					echo "    <option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
				}
				else {
					echo "    <option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
				}
			}
			echo "    </select>\n";
			echo "<br />\n";
			echo $text['description-domain_name']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "	</table>\n";
		echo "	</div>";

	//--- end: show_advanced -----------------------

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	if (permission_exists('ivr_menu_context')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_context' maxlength='255' value=\"".escape($ivr_menu_context)."\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-enter-context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ivr_menu_enabled'>\n";
	if ($ivr_menu_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ivr_menu_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='width: 450px; height: 100px;' name='ivr_menu_description'>".escape($ivr_menu_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if (is_uuid($ivr_menu_uuid)) {
		echo "		<input type='hidden' name='ivr_menu_uuid' value='".escape($ivr_menu_uuid)."'>\n";
		echo "		<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
