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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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

//initialize the destinations object
	$destination = new destinations;

//action add or update
	if (is_uuid($_REQUEST["id"]) || is_uuid($_REQUEST["ivr_menu_uuid"])) {
		$action = "update";
		$ivr_menu_uuid = $_REQUEST["id"];
		if (is_uuid($_REQUEST["ivr_menu_uuid"])) {
			$ivr_menu_uuid = $_REQUEST["ivr_menu_uuid"];
		}
	}
	else {
		$action = "add";
	}

//get total ivr menu count from the database, check limit, if defined
	if (is_numeric($_SESSION['limit']['ivr_menus']['numeric'])) {
		$sql = "select count(*) as num_rows from v_ivr_menus where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$total_ivr_menus = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);

		if ($action == 'add' && $total_ivr_menus >= $_SESSION['limit']['ivr_menus']['numeric']) {
			message::add($text['message-maximum_ivr_menus'].' '.$_SESSION['limit']['ivr_menus']['numeric'], 'negative');
			header('Location: ivr_menus.php');
			exit;
		}
	}

//get http post values and set them to php variables
	if (count($_POST) > 0) {

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($_POST['ivr_menu_uuid'])) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $_POST['ivr_menu_uuid'];

				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('ivr_menu_add')) {
							$obj = new ivr_menu;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('ivr_menu_delete')) {
							$obj = new ivr_menu;
							$obj->delete($array);
						}
						break;
				}

				header('Location: ivr_menus.php');
				exit;
			}

		//get ivr menu
			$ivr_menu_name = $_POST["ivr_menu_name"];
			$ivr_menu_extension = $_POST["ivr_menu_extension"];
			$ivr_menu_parent_uuid = $_POST["ivr_menu_parent_uuid"];
			$ivr_menu_greet_long = $_POST["ivr_menu_greet_long"];
			$ivr_menu_greet_short = $_POST["ivr_menu_greet_short"];
			$ivr_menu_language = $_POST["ivr_menu_language"];
			$ivr_menu_options = $_POST["ivr_menu_options"];
			$ivr_menu_invalid_sound = $_POST["ivr_menu_invalid_sound"];
			$ivr_menu_exit_sound = $_POST["ivr_menu_exit_sound"];
			$ivr_menu_pin_number = $_POST["ivr_menu_pin_number"];
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
			$ivr_menu_options_delete = $_POST["ivr_menu_options_delete"];
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

		//if the user doesn't have the correct permission then 
		//override domain_uuid and ivr_menu_context values
			if ($action == 'update' && is_uuid($ivr_menu_uuid)) {
				$sql = "select * from v_ivr_menus ";
				$sql .= "where ivr_menu_uuid = :ivr_menu_uuid ";
				$parameters['ivr_menu_uuid'] = $ivr_menu_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					if (!permission_exists('ivr_menu_domain')) {
						$domain_uuid = $row["domain_uuid"];
					}
					if (!permission_exists('ivr_menu_context')) {
						$ivr_menu_context = $row["ivr_menu_context"];
					}
				}
				unset($sql, $parameters, $row);
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: ivr_menus.php');
				exit;
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
					if ($action == 'add') {
						$ivr_menu_uuid = uuid();
					}

				//add a uuid to dialplan_uuid if it is empty
					if (!is_uuid($dialplan_uuid)) {
						$dialplan_uuid = uuid();
					}

				//seperate the language components into language, dialect and voice
					$language_array = explode("/",$ivr_menu_language);
					$ivr_menu_language = $language_array[0];
					$ivr_menu_dialect = $language_array[1];
					$ivr_menu_voice = $language_array[2];

				//prepare the array
					$array['ivr_menus'][0]["ivr_menu_uuid"] = $ivr_menu_uuid;
					$array['ivr_menus'][0]["domain_uuid"] = $domain_uuid;
					$array['ivr_menus'][0]["dialplan_uuid"] = $dialplan_uuid;
					$array['ivr_menus'][0]["ivr_menu_name"] = $ivr_menu_name;
					$array['ivr_menus'][0]["ivr_menu_extension"] = $ivr_menu_extension;
					$array['ivr_menus'][0]["ivr_menu_parent_uuid"] = $ivr_menu_parent_uuid;
					$array['ivr_menus'][0]["ivr_menu_language"] = $ivr_menu_language;
					$array['ivr_menus'][0]["ivr_menu_dialect"] = $ivr_menu_dialect;
					$array['ivr_menus'][0]["ivr_menu_voice"] = $ivr_menu_voice;
					$array['ivr_menus'][0]["ivr_menu_greet_long"] = $ivr_menu_greet_long;
					$array['ivr_menus'][0]["ivr_menu_greet_short"] = $ivr_menu_greet_short;
					$array['ivr_menus'][0]["ivr_menu_invalid_sound"] = $ivr_menu_invalid_sound;
					$array['ivr_menus'][0]["ivr_menu_exit_sound"] = $ivr_menu_exit_sound;
					$array['ivr_menus'][0]["ivr_menu_pin_number"] = $ivr_menu_pin_number;
					$array['ivr_menus'][0]["ivr_menu_confirm_macro"] = $ivr_menu_confirm_macro;
					$array['ivr_menus'][0]["ivr_menu_confirm_key"] = $ivr_menu_confirm_key;
					$array['ivr_menus'][0]["ivr_menu_tts_engine"] = $ivr_menu_tts_engine;
					$array['ivr_menus'][0]["ivr_menu_tts_voice"] = $ivr_menu_tts_voice;
					$array['ivr_menus'][0]["ivr_menu_confirm_attempts"] = $ivr_menu_confirm_attempts;
					$array['ivr_menus'][0]["ivr_menu_timeout"] = $ivr_menu_timeout;
					if ($destination->valid($ivr_menu_exit_app.":".$ivr_menu_exit_data)) {
						$array['ivr_menus'][0]["ivr_menu_exit_app"] = $ivr_menu_exit_app;
						$array['ivr_menus'][0]["ivr_menu_exit_data"] = $ivr_menu_exit_data;
					}
					else {
						$ivr_menu_exit_app = "";
					}
					$array['ivr_menus'][0]["ivr_menu_inter_digit_timeout"] = $ivr_menu_inter_digit_timeout;
					$array['ivr_menus'][0]["ivr_menu_max_failures"] = $ivr_menu_max_failures;
					$array['ivr_menus'][0]["ivr_menu_max_timeouts"] = $ivr_menu_max_timeouts;
					$array['ivr_menus'][0]["ivr_menu_digit_len"] = $ivr_menu_digit_len;
					$array['ivr_menus'][0]["ivr_menu_direct_dial"] = $ivr_menu_direct_dial;
					$array['ivr_menus'][0]["ivr_menu_ringback"] = $ivr_menu_ringback;
					$array['ivr_menus'][0]["ivr_menu_cid_prefix"] = $ivr_menu_cid_prefix;
					$array['ivr_menus'][0]["ivr_menu_context"] = $ivr_menu_context;
					$array['ivr_menus'][0]["ivr_menu_enabled"] = $ivr_menu_enabled;
					$array['ivr_menus'][0]["ivr_menu_description"] = $ivr_menu_description;
					$y = 0;
					foreach ($ivr_menu_options as $row) {
						if (strlen($row['ivr_menu_option_digits']) > 0) {
							if (is_uuid($row['ivr_menu_option_uuid'])) {
								$ivr_menu_option_uuid = $row['ivr_menu_option_uuid'];
							}
							else {
								$ivr_menu_option_uuid = uuid();
							}
							if (is_numeric($row["ivr_menu_option_param"])) {
								//add the ivr menu syntax
								$ivr_menu_option_action = "menu-exec-app";
								$ivr_menu_option_param = "transfer ".$row["ivr_menu_option_param"]." XML ".$ivr_menu_context;
							}
							else {
								//seperate the action and the param
								$options_array = explode(":", $row["ivr_menu_option_param"]);
								$ivr_menu_option_action = array_shift($options_array);
								$ivr_menu_option_param = join(':', $options_array);
							}
							$array['ivr_menus'][0]['ivr_menu_options'][$y]["domain_uuid"] = $domain_uuid;
							$array['ivr_menus'][0]['ivr_menu_options'][$y]["ivr_menu_uuid"] = $ivr_menu_uuid;
							$array['ivr_menus'][0]['ivr_menu_options'][$y]["ivr_menu_option_uuid"] = $ivr_menu_option_uuid;
							$array['ivr_menus'][0]['ivr_menu_options'][$y]["ivr_menu_option_digits"] = $row["ivr_menu_option_digits"];
							$array['ivr_menus'][0]['ivr_menu_options'][$y]["ivr_menu_option_action"] = $ivr_menu_option_action;
							if ($destination->valid($ivr_menu_option_action.":".$ivr_menu_option_param, 'ivr')) {
								$array['ivr_menus'][0]['ivr_menu_options'][$y]["ivr_menu_option_param"] = $ivr_menu_option_param;
							}
							$array['ivr_menus'][0]['ivr_menu_options'][$y]["ivr_menu_option_order"] = $row["ivr_menu_option_order"];
							$array['ivr_menus'][0]['ivr_menu_options'][$y]["ivr_menu_option_description"] = $row["ivr_menu_option_description"];
							$y++;
						}
					}

				//build the xml dialplan
					$dialplan_xml = "<extension name=\"".$ivr_menu_name."\" continue=\"false\" uuid=\"".$dialplan_uuid."\">\n";
					$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$ivr_menu_extension."\$\">\n";
					$dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";
					if ($_SESSION['ivr_menu']['answer']['boolean'] == 'true') {
						$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
					}
					$dialplan_xml .= "		<action application=\"sleep\" data=\"1000\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"ringback=".$ivr_menu_ringback."\"/>\n";
					if (strlen($ivr_menu_language) > 0) {
						$dialplan_xml .= "		<action application=\"set\" data=\"default_language=".$ivr_menu_language."\" inline=\"true\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"default_dialect=".$ivr_menu_dialect."\" inline=\"true\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"default_voice=".$ivr_menu_voice ."\" inline=\"true\"/>\n";
					}
					$dialplan_xml .= "		<action application=\"set\" data=\"transfer_ringback=".$ivr_menu_ringback."\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"ivr_menu_uuid=".$ivr_menu_uuid."\"/>\n";

					if ($_SESSION['ivr_menu']['application']['text'] == "lua") {
						$dialplan_xml .= "		<action application=\"lua\" data=\"ivr_menu.lua\"/>\n";
					}
					else {
						if (strlen($ivr_menu_cid_prefix) > 0) {
							$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_name=".$ivr_menu_cid_prefix."#\${caller_id_name}\"/>\n";
							$dialplan_xml .= "		<action application=\"set\" data=\"effective_caller_id_name=\${caller_id_name}\"/>\n";
						}
						$dialplan_xml .= "		<action application=\"ivr\" data=\"".$ivr_menu_uuid."\"/>\n";
					}

					if (strlen($ivr_menu_exit_app) > 0) {
						$dialplan_xml .= "		<action application=\"".$ivr_menu_exit_app."\" data=\"".$ivr_menu_exit_data."\"/>\n";
					}
					$dialplan_xml .= "	</condition>\n";
					$dialplan_xml .= "</extension>\n";

				//build the dialplan array
					$array['dialplans'][0]["domain_uuid"] = $domain_uuid;
					$array['dialplans'][0]["dialplan_uuid"] = $dialplan_uuid;
					$array['dialplans'][0]["dialplan_name"] = $ivr_menu_name;
					$array['dialplans'][0]["dialplan_number"] = $ivr_menu_extension;
					if (isset($ivr_menu_context)) {
						$array['dialplans'][0]["dialplan_context"] = $ivr_menu_context;
					}
					$array['dialplans'][0]["dialplan_continue"] = "false";
					$array['dialplans'][0]["dialplan_xml"] = $dialplan_xml;
					$array['dialplans'][0]["dialplan_order"] = "101";
					$array['dialplans'][0]["dialplan_enabled"] = $ivr_menu_enabled;
					$array['dialplans'][0]["dialplan_description"] = $ivr_menu_description;
					$array['dialplans'][0]["app_uuid"] = "a5788e9b-58bc-bd1b-df59-fff5d51253ab";

				//add the dialplan permission
					$p = new permissions;
					if ($action == "add") {
						$p->add("dialplan_add", "temp");
					}
					else if ($action == "update") {
						$p->add("dialplan_edit", "temp");
					}

				//save to the data
					$database = new database;
					$database->app_name = 'ivr_menus';
					$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
					$database->save($array);
					//$message = $database->message;

				//remove the temporary permission
					$p->delete("dialplan_add", "temp");
					$p->delete("dialplan_edit", "temp");

				//remove checked options
					if (
						$action == 'update'
						&& permission_exists('ivr_menu_option_delete')
						&& is_array($ivr_menu_options_delete)
						&& @sizeof($ivr_menu_options_delete) != 0
						) {
						$obj = new ivr_menu;
						$obj->ivr_menu_uuid = $ivr_menu_uuid;
						$obj->delete_options($ivr_menu_options_delete);
					}

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$_SESSION["context"]);
					$cache->delete("configuration:ivr.conf:".$ivr_menu_uuid);
					//get all ivr parent menus
					$sql = "with recursive ivr_menus as ( ";
					$sql .="	select ivr_menu_parent_uuid ";
					$sql .="	 from v_ivr_menus ";
					$sql .="	 where ivr_menu_parent_uuid = :ivr_menu_parent_uuid ";
					$sql .="	 and ivr_menu_enabled = 'true' ";
					$sql .="	 union all ";
					$sql .="	 select parent.ivr_menu_parent_uuid ";
					$sql .="	 from v_ivr_menus as parent, ivr_menus as child ";
					$sql .="	 where parent.ivr_menu_uuid = child.ivr_menu_parent_uuid ";
					$sql .="	 and parent.ivr_menu_enabled = 'true' ";
					$sql .="	) ";
					$sql .="	select * from ivr_menus ";
					$parameters['ivr_menu_parent_uuid'] = $ivr_menu_parent_uuid;
					$database = new database;
					$parent_uuids = $database->select($sql, $parameters, "all");
					foreach ($parent_uuids as $x => $row) {
						$cache->delete("configuration:ivr.conf:".$row['ivr_menu_parent_uuid']);
					}
				//set the add message
					if ($action == "add" && permission_exists('ivr_menu_add')) {
						message::add($text['message-add']);
					}

				//set the update message
					if ($action == "update" && permission_exists('ivr_menu_edit')) {
						message::add($text['message-update']);
					}

				//redirect the user
					header("Location: ivr_menu_edit.php?id=".urlencode($ivr_menu_uuid));
					return;

			}
	}

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
				$ivr_menu_parent_uuid = $row["ivr_menu_parent_uuid"];
				$ivr_menu_language = $row["ivr_menu_language"];
				$ivr_menu_dialect = $row["ivr_menu_dialect"];
				$ivr_menu_voice = $row["ivr_menu_voice"];
				$ivr_menu_greet_long = $row["ivr_menu_greet_long"];
				$ivr_menu_greet_short = $row["ivr_menu_greet_short"];
				$ivr_menu_invalid_sound = $row["ivr_menu_invalid_sound"];
				$ivr_menu_exit_sound = $row["ivr_menu_exit_sound"];
				$ivr_menu_pin_number = $row["ivr_menu_pin_number"];
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

//get the ivr menus
	$sql = "select * from v_ivr_menus ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by v_ivr_menus asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$ivr_menus = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row to the options array
	if (count($ivr_menu_options) == 0) {
		$rows = $_SESSION['ivr_menu']['option_add_rows']['numeric'];
		$id = 0;
		$show_option_delete = false;
	}
	if (count($ivr_menu_options) > 0) {
		$rows = $_SESSION['ivr_menu']['option_edit_rows']['numeric'];
		$id = count($ivr_menu_options)+1;
		$show_option_delete = true;
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
	if (strlen($ivr_menu_tts_engine) == 0) { $ivr_menu_tts_engine = 'flite'; }
	if (strlen($ivr_menu_tts_voice) == 0) { $ivr_menu_tts_voice = 'rms'; }
	if (strlen($ivr_menu_confirm_attempts) == 0) { 
		if (strlen($_SESSION['ivr_menu']['confirm_attempts']['numeric']) > 0) {
			$ivr_menu_confirm_attempts = $_SESSION['ivr_menu']['confirm_attempts']['numeric'];
		}
		else {
			$ivr_menu_confirm_attempts = '1';
		}
	}
	if (strlen($ivr_menu_inter_digit_timeout) == 0) { 
		if (strlen($_SESSION['ivr_menu']['inter_digit_timeout']['numeric']) > 0) {
			$ivr_menu_inter_digit_timeout = $_SESSION['ivr_menu']['inter_digit_timeout']['numeric'];
		}
		else {
			$ivr_menu_inter_digit_timeout = '2000'; 
		}
	}
	if (strlen($ivr_menu_max_failures) == 0) { 
		if (strlen($_SESSION['ivr_menu']['max_failures']['numeric']) > 0) {
			$ivr_menu_max_failures = $_SESSION['ivr_menu']['max_failures']['numeric'];
		}
		else {
			$ivr_menu_max_failures = '1'; 
		}
	}
	if (strlen($ivr_menu_max_timeouts) == 0) { 
		if (strlen($_SESSION['ivr_menu']['max_timeouts']['numeric']) > 0) {
			$ivr_menu_max_timeouts = $_SESSION['ivr_menu']['max_timeouts']['numeric'];
		}
		else {
			$ivr_menu_max_timeouts = '1'; 
		}
	}
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
	$sound_files = $file->sounds($ivr_menu_language, $ivr_menu_dialect, $ivr_menu_voice);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//included the header
	$document['title'] = $text['title-ivr_menu'];
	require_once "resources/header.php";

//show the content
	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "	function show_advanced_config() {\n";
	echo "		$('#show_advanced_box').slideToggle();\n";
	echo "		$('#show_advanced').slideToggle();\n";
	echo "	}\n";
	echo "</script>";

	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-ivr_menu']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'ivr_menus.php']);
	if ($action == "update") {
		$button_margin = 'margin-left: 15px;';
		if (permission_exists('ivr_menu_add') && (!is_numeric($_SESSION['limit']['ivr_menus']['numeric']) || $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$button_margin,'onclick'=>"modal_open('modal-copy','btn_copy');"]);
			unset($button_margin);
		}
		if (permission_exists('ivr_menu_delete') || permission_exists('ivr_menu_option_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$button_margin,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
			unset($button_margin);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		if (permission_exists('ivr_menu_add') && (!is_numeric($_SESSION['limit']['ivr_menus']['numeric']) || $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('ivr_menu_delete') || permission_exists('ivr_menu_option_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo $text['description-ivr_menu']."\n";
	echo "<br><br>\n";

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

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-ivr_menu_parent_uuid']."</td>";
	echo "		<td class='vtable'>";
	echo "<select name=\"ivr_menu_parent_uuid\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	foreach($ivr_menus as $field) {
		if ($field['ivr_menu_uuid'] != $ivr_menu_uuid) {
			if ($ivr_menu_parent_uuid == $field['ivr_menu_uuid']) {
				echo "<option value='".escape($field['ivr_menu_uuid'])."' selected='selected'>".escape($field['ivr_menu_name'])."</option>\n";
			}
			else {
				echo "<option value='".escape($field['ivr_menu_uuid'])."'>".escape($field['ivr_menu_name'])."</option>\n";
			}
		}
	}
	echo "</select>";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <select class='formfld' type='text' name='ivr_menu_language'>\n";
	echo "		<option></option>\n";
	if (strlen($ivr_menu_language) > 0) {
		$language_formatted = $ivr_menu_language."-".$ivr_menu_dialect." ".$ivr_menu_voice;
		echo "		<option value='".escape($ivr_menu_language.'/'.$ivr_menu_dialect.'/'.$ivr_menu_voice)."' selected='selected'>".escape($language_formatted)."</option>\n";
	}
	foreach ($language_paths as $key => $language_variables) {
		$language_variables = explode ('/',$language_paths[$key]);
		$language = $language_variables[0];
		$dialect = $language_variables[1];
		$voice = $language_variables[2];
		if ($language_formatted <> $language.'-'.$dialect.' '.$voice) {
			echo "		<option value='".$language."/".$dialect."/".$voice."'>".$language."-".$dialect." ".$voice."</option>\n";
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
	echo "					<td class='vtable' style='text-align: center;'>".$text['label-option']."</td>\n";
	echo "					<td class='vtable'>".$text['label-destination']."</td>\n";
	echo "					<td class='vtable'>".$text['label-order']."</td>\n";
	echo "					<td class='vtable'>".$text['label-description']."</td>\n";
	if ($show_option_delete && permission_exists('ivr_menu_option_delete')) {
		echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_options', 'delete_toggle_options');\" onmouseout=\"swap_display('delete_label_options', 'delete_toggle_options');\">\n";
		echo "						<span id='delete_label_options'>".$text['label-delete']."</span>\n";
		echo "						<span id='delete_toggle_options'><input type='checkbox' id='checkbox_all_options' name='checkbox_all' onclick=\"edit_all_toggle('options');\"></span>\n";
		echo "					</td>\n";
	}
	echo "				</tr>\n";
	if (is_array($ivr_menu_options)) {
		$x = 0;
		foreach($ivr_menu_options as $field) {

			//add the primary key uuid
			if (strlen($field['ivr_menu_option_uuid']) > 0) {
				echo "	<input name='ivr_menu_options[".$x."][ivr_menu_option_uuid]' type='hidden' value=\"".escape($field['ivr_menu_option_uuid'])."\">\n";
			}

			echo "<td class='formfld' align='center'>\n";
			echo "  <input class='formfld' style='width: 50px; text-align: center;' type='text' name='ivr_menu_options[".$x."][ivr_menu_option_digits]' maxlength='255' value='".escape($field['ivr_menu_option_digits'])."'>\n";
			echo "</td>\n";

			echo "<td class='formfld' align='left' nowrap='nowrap'>\n";
			$destination_action = '';
			if (strlen($field['ivr_menu_option_action'].$field['ivr_menu_option_param']) > 0) {
				$destination_action = $field['ivr_menu_option_action'].':'.$field['ivr_menu_option_param'];
			} else { $destination_action = ''; }
			echo $destination->select('ivr', 'ivr_menu_options['.$x.'][ivr_menu_option_param]', $destination_action);
			unset($destination_action);
			echo "</td>\n";

			echo "<td class='formfld' align='left'>\n";
			echo "	<select name='ivr_menu_options[".$x."][ivr_menu_option_order]' class='formfld' style='width:55px'>\n";
			//echo "	<option></option>\n";
			if (strlen(htmlspecialchars($field['ivr_menu_option_order']))> 0) {
				if (strlen($field['ivr_menu_option_order']) == 1) { $field['ivr_menu_option_order'] = "00".$field['ivr_menu_option_order']; }
				if (strlen($field['ivr_menu_option_order']) == 2) { $field['ivr_menu_option_order'] = "0".$field['ivr_menu_option_order']; }
				echo "	<option value='".escape($field['ivr_menu_option_order'])."'>".escape($field['ivr_menu_option_order'])."</option>\n";
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
			echo "	<input class='formfld' style='width:100px' type='text' name='ivr_menu_options[".$x."][ivr_menu_option_description]' maxlength='255' value=\"".escape($field['ivr_menu_option_description'])."\">\n";
			echo "</td>\n";

			if ($show_option_delete && permission_exists('ivr_menu_option_delete')) {
				if (is_uuid($field['ivr_menu_option_uuid'])) {
					echo "<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
					echo "	<input type='checkbox' name='ivr_menu_options_delete[".$x."][checked]' value='true' class='chk_delete checkbox_options' onclick=\"edit_delete_action('options');\">\n";
					echo "	<input type='hidden' name='ivr_menu_options_delete[".$x."][uuid]' value='".escape($field['ivr_menu_option_uuid'])."' />\n";
				}
				else {
					echo "<td>";
				}
				echo "</td>\n";
			}

			echo "</tr>\n";

			$x++;
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
		echo button::create(['type'=>'button','label'=>$text['button-advanced'],'icon'=>'tools','onclick'=>'show_advanced_config();']);
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
				foreach ($phrases as &$row) {
					if ($ivr_menu_invalid_sound == "phrase:".$row["phrase_uuid"]) {
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
		echo "	".$text['label-pin_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_pin_number' maxlength='255' value=\"".escape($ivr_menu_pin_number)."\">\n";
		echo "<br />\n";
		echo $text['description-pin_number']."\n";
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
	echo "	<textarea class='formfld' style='width: 450px; height: 100px;' name='ivr_menu_description'>".$ivr_menu_description."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if (is_uuid($ivr_menu_uuid)) {
		echo "<input type='hidden' name='ivr_menu_uuid' value='".escape($ivr_menu_uuid)."'>\n";
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
