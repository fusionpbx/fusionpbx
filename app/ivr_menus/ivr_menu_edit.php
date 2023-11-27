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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

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

//set the defaults
	$ivr_menu_name = '';
	$ivr_menu_extension = '';
	$ivr_menu_cid_prefix = '';
	$ivr_menu_description = '';

//initialize the destinations object
	$destination = new destinations;

//initialize the ringbacks object
	$ringbacks = new ringbacks;

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"]) || !empty($_REQUEST["ivr_menu_uuid"]) &&  is_uuid($_REQUEST["ivr_menu_uuid"])) {
		$action = "update";
		$ivr_menu_uuid = $_REQUEST["id"];
		if (!empty($_REQUEST["ivr_menu_uuid"])) {
			$ivr_menu_uuid = $_REQUEST["ivr_menu_uuid"];
		}
	}
	else {
		$action = "add";
	}

//get total ivr menu count from the database, check limit, if defined
	if (!empty($_SESSION['limit']['ivr_menus']['numeric'])) {
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
	if (!empty($_POST)) {

		//process the http post data by submitted action
			if (!empty($_POST['action']) && is_uuid($_POST['ivr_menu_uuid'])) {
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
			$ivr_menu_pin_number = $_POST["ivr_menu_pin_number"] ?? '';
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
			$ivr_menu_enabled = $_POST["ivr_menu_enabled"] ?? 'false';
			$ivr_menu_description = $_POST["ivr_menu_description"];
			$ivr_menu_options_delete = $_POST["ivr_menu_options_delete"] ?? null;
			$dialplan_uuid = $_POST["dialplan_uuid"] ?? null;

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
			if (empty($ivr_menu_option_action)) {
				$ivr_menu_option_action = "menu-exec-app";
			}
	}

//process the http data
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

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
				if (!empty($row)) {
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
			if (empty($ivr_menu_name)) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			if (empty($ivr_menu_extension)) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (empty($ivr_menu_greet_long)) { $msg .= $text['message-required'].$text['label-greet_long']."<br>\n"; }
			//if (empty($ivr_menu_greet_short)) { $msg .= $text['message-required'].$text['label-greet_short']."<br>\n"; }
			//if (empty($ivr_menu_invalid_sound)) { $msg .= $text['message-required'].$text['label-invalid_sound']."<br>\n"; }
			//if (empty($ivr_menu_exit_sound)) { $msg .= $text['message-required'].$text['label-exit_sound']."<br>\n"; }
			//if (empty($ivr_menu_confirm_macro)) { $msg .= $text['message-required'].$text['label-comfirm_macro']."<br>\n"; }
			//if (empty($ivr_menu_confirm_key)) { $msg .= $text['message-required'].$text['label-comfirm_key']."<br>\n"; }
			//if (empty($ivr_menu_tts_engine)) { $msg .= $text['message-required'].$text['label-tts_engine']."<br>\n"; }
			//if (empty($ivr_menu_tts_voice)) { $msg .= $text['message-required'].$text['label-tts_voice']."<br>\n"; }
			if (empty($ivr_menu_confirm_attempts)) { $msg .= $text['message-required'].$text['label-comfirm_attempts']."<br>\n"; }
			if (empty($ivr_menu_timeout)) { $msg .= $text['message-required'].$text['label-timeout']."<br>\n"; }
			//if (empty($ivr_menu_exit_app)) { $msg .= $text['message-required'].$text['label-exit_action']."<br>\n"; }
			if (empty($ivr_menu_inter_digit_timeout)) { $msg .= $text['message-required'].$text['label-inter_digit_timeout']."<br>\n"; }
			if (empty($ivr_menu_max_failures)) { $msg .= $text['message-required'].$text['label-max_failures']."<br>\n"; }
			if (empty($ivr_menu_max_timeouts)) { $msg .= $text['message-required'].$text['label-max_timeouts']."<br>\n"; }
			if (empty($ivr_menu_digit_len)) { $msg .= $text['message-required'].$text['label-digit_length']."<br>\n"; }
			if (empty($ivr_menu_direct_dial)) { $msg .= $text['message-required'].$text['label-direct_dial']."<br>\n"; }
			//if (empty($ivr_menu_ringback)) { $msg .= $text['message-required'].$text['label-ring_back']."<br>\n"; }

			//if (empty($ivr_menu_description)) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
			if (!empty($msg) && !empty($_POST["persistformvar"])) {
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
			if (empty($_POST["persistformvar"])) {

				//used for debugging
					if (!empty($_POST["debug"]) && $_POST["debug"] == "true") {
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
					$ivr_menu_language = $language_array[0] ?? 'en';
					$ivr_menu_dialect = $language_array[1] ?? 'us';
					$ivr_menu_voice = $language_array[2] ?? 'callie';

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
					if (!empty($ivr_menu_ringback) && $ringbacks->valid($ivr_menu_ringback)) {
						$array['ivr_menus'][0]["ivr_menu_ringback"] = $ivr_menu_ringback;
					}
					$array['ivr_menus'][0]["ivr_menu_cid_prefix"] = $ivr_menu_cid_prefix;
					$array['ivr_menus'][0]["ivr_menu_context"] = $ivr_menu_context;
					$array['ivr_menus'][0]["ivr_menu_enabled"] = $ivr_menu_enabled;
					$array['ivr_menus'][0]["ivr_menu_description"] = $ivr_menu_description;
					$y = 0;
					foreach ($ivr_menu_options as $row) {
						if (isset($row['ivr_menu_option_digits']) && $row['ivr_menu_option_digits'] != '') {
							if (!empty($row['ivr_menu_option_uuid']) && is_uuid($row['ivr_menu_option_uuid'])) {
								$ivr_menu_option_uuid = $row['ivr_menu_option_uuid'];
							}
							else {
								$ivr_menu_option_uuid = uuid();
							}
							if (isset($row["ivr_menu_option_param"]) && is_numeric($row["ivr_menu_option_param"])) {
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
							$array['ivr_menus'][0]["ivr_menu_options"][$y]["ivr_menu_option_enabled"] = !empty($row['ivr_menu_option_enabled']) ?? 'false';
							$y++;
						}
					}

				//build the xml dialplan
					$dialplan_xml = "<extension name=\"".xml::sanitize($ivr_menu_name)."\" continue=\"false\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
					$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($ivr_menu_extension)."\$\">\n";
					$dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";
					if ($_SESSION['ivr_menu']['answer']['boolean'] == 'true') {
						$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
					}
					$dialplan_xml .= "		<action application=\"sleep\" data=\"1000\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
					if (!empty($ivr_menu_ringback) && $ringbacks->valid($ivr_menu_ringback)) {
						$dialplan_xml .= "		<action application=\"set\" data=\"ringback=".$ivr_menu_ringback."\"/>\n";
					}
					if (!empty($ivr_menu_language)) {
						$dialplan_xml .= "		<action application=\"set\" data=\"sound_prefix=\$\${sounds_dir}/".xml::sanitize($ivr_menu_language)."/".xml::sanitize($ivr_menu_dialect)."/".xml::sanitize($ivr_menu_voice)."\" inline=\"true\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"default_language=".xml::sanitize($ivr_menu_language)."\" inline=\"true\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"default_dialect=".xml::sanitize($ivr_menu_dialect)."\" inline=\"true\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"default_voice=".xml::sanitize($ivr_menu_voice)."\" inline=\"true\"/>\n";
					}
					if (!empty($ivr_menu_ringback) && $ringbacks->valid($ivr_menu_ringback)) {
						$dialplan_xml .= "		<action application=\"set\" data=\"transfer_ringback=".$ivr_menu_ringback."\"/>\n";
					}
					$dialplan_xml .= "		<action application=\"set\" data=\"ivr_menu_uuid=".xml::sanitize($ivr_menu_uuid)."\"/>\n";

					if (!empty($_SESSION['ivr_menu']['application']['text']) && $_SESSION['ivr_menu']['application']['text'] == "lua") {
						$dialplan_xml .= "		<action application=\"lua\" data=\"ivr_menu.lua\"/>\n";
					}
					else {
						if (!empty($ivr_menu_cid_prefix)) {
							$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_name=".xml::sanitize($ivr_menu_cid_prefix)."#\${caller_id_name}\"/>\n";
							$dialplan_xml .= "		<action application=\"set\" data=\"effective_caller_id_name=\${caller_id_name}\"/>\n";
						}
						$dialplan_xml .= "		<action application=\"ivr\" data=\"".xml::sanitize($ivr_menu_uuid)."\"/>\n";
					}

					if (!empty($ivr_menu_exit_app)) {
						$dialplan_xml .= "		<action application=\"".xml::sanitize($ivr_menu_exit_app)."\" data=\"".xml::sanitize($ivr_menu_exit_data)."\"/>\n";
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
						&& !empty($ivr_menu_options_delete)
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
					if (!empty($parent_uuids)) {
						foreach ($parent_uuids as $x => $row) {
							$cache->delete("configuration:ivr.conf:".$row['ivr_menu_parent_uuid']);
						}
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
	if (empty($ivr_menu_uuid)) { $ivr_menu_uuid = $_REQUEST["id"] ?? null; }
	if (!empty($ivr_menu_uuid) && is_uuid($ivr_menu_uuid) && empty($_POST["persistformvar"])) {
		$ivr = new ivr_menu;
		$ivr->domain_uuid = $_SESSION["domain_uuid"];
		$ivr->ivr_menu_uuid = $ivr_menu_uuid;
		$ivr_menus = $ivr->find();
		if (!empty($ivr_menus)) {
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

				if (!empty($ivr_menu_exit_app)) {
					$ivr_menu_exit_action = $ivr_menu_exit_app.":".$ivr_menu_exit_data;
				}
			}
		}
		unset($ivr_menus, $row);
	}

//set defaults
	$ivr_menu_language = $ivr_menu_language ?? '';
	$ivr_menu_dialect = $ivr_menu_dialect ?? '';
	$ivr_menu_voice = $ivr_menu_voice ?? '';
	$select_style = $select_style ?? '';
	$onkeyup = $onkeyup ?? '';
	
//get the ivr menu options
	$sql = "select * from v_ivr_menu_options ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and ivr_menu_uuid = :ivr_menu_uuid ";
	$sql .= "order by ivr_menu_option_order, ivr_menu_option_digits asc ";
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
		$ivr_menu_options[$id]['ivr_menu_option_enabled'] = '';
		$id++;
	}

//set the defaults
	if (empty($ivr_menu_timeout)) { $ivr_menu_timeout = '3000'; }
	if (empty($ivr_menu_ringback)) { $ivr_menu_ringback = 'local_stream://default'; }
	if (empty($ivr_menu_invalid_sound)) { $ivr_menu_invalid_sound = 'ivr/ivr-that_was_an_invalid_entry.wav'; }
	//if (empty($ivr_menu_confirm_key)) { $ivr_menu_confirm_key = '#'; }
	if (empty($ivr_menu_tts_engine)) { $ivr_menu_tts_engine = 'flite'; }
	if (empty($ivr_menu_tts_voice)) { $ivr_menu_tts_voice = 'rms'; }
	if (empty($ivr_menu_confirm_attempts)) {
		if (!empty($_SESSION['ivr_menu']['confirm_attempts']['numeric'])) {
			$ivr_menu_confirm_attempts = $_SESSION['ivr_menu']['confirm_attempts']['numeric'];
		}
		else {
			$ivr_menu_confirm_attempts = '1';
		}
	}
	if (empty($ivr_menu_inter_digit_timeout)) {
		if (!empty($_SESSION['ivr_menu']['inter_digit_timeout']['numeric'])) {
			$ivr_menu_inter_digit_timeout = $_SESSION['ivr_menu']['inter_digit_timeout']['numeric'];
		}
		else {
			$ivr_menu_inter_digit_timeout = '2000'; 
		}
	}
	if (empty($ivr_menu_max_failures)) {
		if (!empty($_SESSION['ivr_menu']['max_failures']['numeric'])) {
			$ivr_menu_max_failures = $_SESSION['ivr_menu']['max_failures']['numeric'];
		}
		else {
			$ivr_menu_max_failures = '1'; 
		}
	}
	if (empty($ivr_menu_max_timeouts)) {
		if (!empty($_SESSION['ivr_menu']['max_timeouts']['numeric'])) {
			$ivr_menu_max_timeouts = $_SESSION['ivr_menu']['max_timeouts']['numeric'];
		}
		else {
			$ivr_menu_max_timeouts = '1'; 
		}
	}
	if (empty($ivr_menu_digit_len)) { $ivr_menu_digit_len = '5'; }
	if (empty($ivr_menu_direct_dial)) { $ivr_menu_direct_dial = 'false'; }
	if (!isset($ivr_menu_context)) { $ivr_menu_context = $_SESSION['domain_name']; }
	if (empty($ivr_menu_enabled)) { $ivr_menu_enabled = 'true'; }
	if (!isset($ivr_menu_exit_action)) { $ivr_menu_exit_action = ''; }

//get installed languages
	$language_paths = glob($_SESSION["switch"]['sounds']['dir']."/*/*/*");
	foreach ($language_paths as $key => $path) {
		$path = str_replace($_SESSION["switch"]['sounds']['dir'].'/', "", $path);
		$path_array = explode('/', $path);
		if (count($path_array) <> 3 || strlen($path_array[0]) <> 2 || strlen($path_array[1]) <> 2) {
			unset($language_paths[$key]);
		}
		$language_paths[$key] = str_replace($_SESSION["switch"]['sounds']['dir']."/","",$language_paths[$key] ?? '');
		if (empty($language_paths[$key])) {
			unset($language_paths[$key]);
		}
	}

//get the sounds
	$sounds = new sounds;
	$sounds->sound_types = ['miscellaneous','recordings','phrases'];
	$audio_files[0] = $sounds->get();
	unset($sounds);

	$sounds = new sounds;
	$audio_files[1] = $sounds->get();
	unset($sounds);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//included the header
	$document['title'] = $text['title-ivr_menu'];
	require_once "resources/header.php";

//show the content
	echo "<script type='text/javascript' language='JavaScript'>\n";
	echo "	function show_advanced_config() {\n";
	echo "		$('#show_advanced_box').slideToggle();\n";
	echo "		$('#show_advanced').slideToggle();\n";
	echo "	}\n";
	echo "</script>\n";

	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	function set_playable(id, audio_selected, audio_type) {\n";
		echo "		file_ext = audio_selected.split('.').pop();\n";
		echo "		var mime_type = '';\n";
		echo "		switch (file_ext) {\n";
		echo "			case 'wav': mime_type = 'audio/wav'; break;\n";
		echo "			case 'mp3': mime_type = 'audio/mpeg'; break;\n";
		echo "			case 'ogg': mime_type = 'audio/ogg'; break;\n";
		echo "		}\n";
		echo "		if (mime_type != '' && (audio_type == 'recordings' || audio_type == 'sounds')) {\n";
		echo "			if (audio_type == 'recordings') {\n";
		echo "				if (audio_selected.includes('/')) {\n";
		echo "					audio_selected = audio_selected.split('/').pop()\n";
		echo "				}\n";
		echo "				$('#recording_audio_' + id).attr('src', '../recordings/recordings.php?action=download&type=rec&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			else if (audio_type == 'sounds') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../switch/sounds.php?action=download&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			$('#recording_audio_' + id).attr('type', mime_type);\n";
		echo "			$('#recording_button_' + id).show();\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			$('#recording_button_' + id).hide();\n";
		echo "			$('#recording_audio_' + id).attr('src','').attr('type','');\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";
	}
	if (if_group("superadmin")) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	var objs;\n";
		echo "	function toggle_select_input(obj, instance_id){\n";
		echo "		tb=document.createElement('INPUT');\n";
		echo "		tb.type='text';\n";
		echo "		tb.name=obj.name;\n";
		echo "		tb.className='formfld';\n";
		echo "		tb.setAttribute('id', instance_id);\n";
		echo "		tb.setAttribute('style', 'width: ' + obj.offsetWidth + 'px;');\n";
		if (!empty($on_change)) {
			echo "	tb.setAttribute('onchange', \"".$on_change."\");\n";
			echo "	tb.setAttribute('onkeyup', \"".$on_change."\");\n";
		}
		echo "		tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "		document.getElementById('btn_select_to_input_' + instance_id).style.display = 'none';\n";
		echo "		tbb=document.createElement('INPUT');\n";
		echo "		tbb.setAttribute('class', 'btn');\n";
		echo "		tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "		tbb.type='button';\n";
		echo "		tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "		tbb.objs=[obj,tb,tbb];\n";
		echo "		tbb.onclick=function(){ replace_element(this.objs, instance_id); }\n";
		echo "		obj.parentNode.insertBefore(tb,obj);\n";
		echo "		obj.parentNode.insertBefore(tbb,obj);\n";
		echo "		obj.parentNode.removeChild(obj);\n";
		echo "		replace_element(this.objs, instance_id);\n";
		echo "	}\n";
		echo "	function replace_element(obj, instance_id){\n";
		echo "		obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "		obj[0].parentNode.removeChild(obj[1]);\n";
		echo "		obj[0].parentNode.removeChild(obj[2]);\n";
		echo "		document.getElementById('btn_select_to_input_' + instance_id).style.display = 'inline';\n";
		if (!empty($on_change)) {
			echo "	".$on_change.";\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-ivr_menu']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'ivr_menus.php']);
	if ($action == "update") {
		if (permission_exists('ivr_menu_add') && (empty($_SESSION['limit']['ivr_menus']['numeric']) || $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
			$button_margin = 'margin-left: 15px;';
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$button_margin,'onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('ivr_menu_delete') || permission_exists('ivr_menu_option_delete')) {
			$button_margin = 'margin-left: 0px;';
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$button_margin,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		if (permission_exists('ivr_menu_add') && (empty($_SESSION['limit']['ivr_menus']['numeric']) || $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
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
	if (!empty($ivr_menus)) {
		foreach($ivr_menus as $field) {
			if ($field['ivr_menu_uuid'] != $ivr_menu_uuid) {
				if (!empty($ivr_menu_parent_uuid) && $ivr_menu_parent_uuid == $field['ivr_menu_uuid']) {
					echo "<option value='".escape($field['ivr_menu_uuid'])."' selected='selected'>".escape($field['ivr_menu_name'])."</option>\n";
				}
				else {
					echo "<option value='".escape($field['ivr_menu_uuid'])."'>".escape($field['ivr_menu_name'])."</option>\n";
				}
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
	if (!empty($ivr_menu_language) && !empty($ivr_menu_dialect) && !empty($ivr_menu_voice)) {
		$language_formatted = $ivr_menu_language."-".$ivr_menu_dialect." ".$ivr_menu_voice;
		echo "		<option value='".escape($ivr_menu_language.'/'.$ivr_menu_dialect.'/'.$ivr_menu_voice)."' selected='selected'>".escape($language_formatted)."</option>\n";
	}
	if (!empty($language_paths)) {
		foreach ($language_paths as $key => $language_variables) {
			$language_variables = explode('/',$language_paths[$key]);
			$language = $language_variables[0];
			$dialect = $language_variables[1];
			$voice = $language_variables[2];
			if (empty($language_formatted) || $language_formatted != $language.'-'.$dialect.' '.$voice) {
				echo "		<option value='".$language."/".$dialect."/".$voice."'>".$language."-".$dialect." ".$voice."</option>\n";
			}
		}
	}
	echo "<br />\n";
	//echo $text['description-language']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	$instance_id = 'ivr_menu_greet_long';
	$instance_label = 'greet_long';
	$instance_value = $ivr_menu_greet_long;
	echo "<tr>\n";
	echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-'.$instance_label]."\n";
	echo "</td>\n";
	echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
	echo "	<option value=''></option>\n";
	$found = $playable = false;
	if (!empty($audio_files[0]) && is_array($audio_files[0]) && @sizeof($audio_files[0]) != 0) {
		foreach ($audio_files[0] as $key => $value) {
			echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
			foreach ($value as $row) {
				if ($key == 'recordings') {
					if (
						!empty($instance_value) &&
						($instance_value == $row["value"] || $instance_value == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.$row["value"]) &&
						file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.pathinfo($row["value"], PATHINFO_BASENAME))
						) {
						$selected = "selected='selected'";
						$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else if ($key == 'sounds') {
					if (!empty($instance_value) && $instance_value == $row["value"]) {
						$selected = "selected='selected'";
						$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else {
					unset($selected);
				}
				echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
			}
			echo "</optgroup>\n";
		}
	}
	if (if_group("superadmin") && !empty($instance_value) && !$found) {
		echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
	}
	unset($selected);
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && (!empty($playable) || empty($instance_value))) {
		switch (pathinfo($playable, PATHINFO_EXTENSION)) {
			case 'wav' : $mime_type = 'audio/wav'; break;
			case 'mp3' : $mime_type = 'audio/mpeg'; break;
			case 'ogg' : $mime_type = 'audio/ogg'; break;
		}
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."')"]);
		unset($playable, $mime_type);
	}
	echo "<br />\n";
	echo $text['description-'.$instance_label]."\n";
	echo "</td>\n";
	echo "</tr>\n";

	$instance_id = 'ivr_menu_greet_short';
	$instance_label = 'greet_short';
	$instance_value = $ivr_menu_greet_short;
	echo "<tr>\n";
	echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-'.$instance_label]."\n";
	echo "</td>\n";
	echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
	echo "	<option value=''></option>\n";
	$found = $playable = false;
	if (!empty($audio_files[0]) && is_array($audio_files[0]) && @sizeof($audio_files[0]) != 0) {
		foreach ($audio_files[0] as $key => $value) {
			echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
			foreach ($value as $row) {
				if ($key == 'recordings') {
					if (
						!empty($instance_value) &&
						($instance_value == $row["value"] || $instance_value == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.$row["value"]) &&
						file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.pathinfo($row["value"], PATHINFO_BASENAME))
						) {
						$selected = "selected='selected'";
						$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else if ($key == 'sounds') {
					if (!empty($instance_value) && $instance_value == $row["value"]) {
						$selected = "selected='selected'";
						$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else {
					unset($selected);
				}
				echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
			}
			echo "</optgroup>\n";
		}
	}
	if (if_group("superadmin") && !empty($instance_value) && !$found) {
		echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
	}
	unset($selected);
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && (!empty($playable) || empty($instance_value))) {
		switch (pathinfo($playable, PATHINFO_EXTENSION)) {
			case 'wav' : $mime_type = 'audio/wav'; break;
			case 'mp3' : $mime_type = 'audio/mpeg'; break;
			case 'ogg' : $mime_type = 'audio/ogg'; break;
		}
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."')"]);
		unset($playable, $mime_type);
	}
	echo "<br />\n";
	echo $text['description-'.$instance_label]."\n";
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
	echo "					<td class='vtable'>".$text['label-enabled']."</td>\n";
	if ($show_option_delete && permission_exists('ivr_menu_option_delete')) {
		echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_options', 'delete_toggle_options');\" onmouseout=\"swap_display('delete_label_options', 'delete_toggle_options');\">\n";
		echo "						<span id='delete_label_options'>".$text['label-delete']."</span>\n";
		echo "						<span id='delete_toggle_options'><input type='checkbox' id='checkbox_all_options' name='checkbox_all' onclick=\"edit_all_toggle('options');\"></span>\n";
		echo "					</td>\n";
	}
	echo "				</tr>\n";
	if (!empty($ivr_menu_options)) {
		$x = 0;
		foreach($ivr_menu_options as $field) {

			//add the primary key uuid
			if (!empty($field['ivr_menu_option_uuid'])) {
				echo "	<input name='ivr_menu_options[".$x."][ivr_menu_option_uuid]' type='hidden' value=\"".escape($field['ivr_menu_option_uuid'])."\">\n";
			}

			echo "<td class='formfld' align='center'>\n";
			if (empty($field['ivr_menu_option_uuid'])) { // new record
				if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
					$onkeyup = "onkeyup=\"document.getElementById('ivr_menu_options_".$x."_ivr_menu_option_enabled').checked = (this.value != '' ? true : false);\""; // switch
				}
				else {
					$onkeyup = "onkeyup=\"document.getElementById('ivr_menu_options_".$x."_ivr_menu_option_enabled').value = (this.value != '' ? true : false);\""; // select
				}
			}
			echo "  <input class='formfld' style='width: 50px; text-align: center;' type='text' name='ivr_menu_options[".$x."][ivr_menu_option_digits]' maxlength='255' value='".escape($field['ivr_menu_option_digits'])."' ".$onkeyup.">\n";
			echo "</td>\n";

			echo "<td class='formfld' align='left' nowrap='nowrap'>\n";
			$destination_action = '';
			if (!empty($field['ivr_menu_option_action'].$field['ivr_menu_option_param'])) {
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
			echo "<td class='formfld'>\n";
			// switch
			if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
				echo "	<label class='switch'>\n";
				echo "		<input type='checkbox' id='ivr_menu_options_".$x."_ivr_menu_option_enabled' name='ivr_menu_options[".$x."][ivr_menu_option_enabled]' value='true' ".($field['ivr_menu_option_enabled'] == 'true' ? "checked='checked'" : null).">\n";
				echo "		<span class='slider'></span>\n";
				echo "	</label>\n";
			}
			// select
			else {
				echo "	<select class='formfld' id='ivr_menu_options_".$x."_ivr_menu_option_enabled' name='ivr_menu_options[".$x."][ivr_menu_option_enabled]'>\n";
				echo "		<option value='false'>".$text['option-false']."</option>\n";
				echo "		<option value='true' ".(!empty($field['ivr_menu_option_enabled']) && $field['ivr_menu_option_enabled'] == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
				echo "	</select>\n";
			}
			echo "</td>\n";
			if ($show_option_delete && permission_exists('ivr_menu_option_delete')) {
				if (!empty($field['ivr_menu_option_uuid']) && is_uuid($field['ivr_menu_option_uuid'])) {
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

	//--- begin: advanced -----------------------

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

		$instance_id = 'ivr_menu_invalid_sound';
		$instance_label = 'invalid_sound';
		$instance_value = $ivr_menu_invalid_sound;
		echo "<tr>\n";
		echo "<td width='30%' class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-'.$instance_label]."\n";
		echo "</td>\n";
		echo "<td width='70%'class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
		echo "	<option value=''></option>\n";
		$found = $playable = false;
		if (!empty($audio_files[1]) && is_array($audio_files[1]) && @sizeof($audio_files[1]) != 0) {
			foreach ($audio_files[1] as $key => $value) {
				echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
				foreach ($value as $row) {
					if ($key == 'recordings') {
						if (
							!empty($instance_value) &&
							($instance_value == $row["value"] || $instance_value == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.$row["value"]) &&
							file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.pathinfo($row["value"], PATHINFO_BASENAME))
							) {
							$selected = "selected='selected'";
							$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
							$found = true;
						}
						else {
							unset($selected);
						}
					}
					else if ($key == 'sounds') {
						if (!empty($instance_value) && $instance_value == $row["value"]) {
							$selected = "selected='selected'";
							$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
							$found = true;
						}
						else {
							unset($selected);
						}
					}
					else {
						unset($selected);
					}
					echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
				}
				echo "</optgroup>\n";
			}
		}
		if (if_group("superadmin") && !empty($instance_value) && !$found) {
			echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
		}
		unset($selected);
		echo "	</select>\n";
		if (if_group("superadmin")) {
			echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
		}
		if ((permission_exists('recording_play') || permission_exists('recording_download')) && (!empty($playable) || empty($instance_value))) {
			switch (pathinfo($playable, PATHINFO_EXTENSION)) {
				case 'wav' : $mime_type = 'audio/wav'; break;
				case 'mp3' : $mime_type = 'audio/mpeg'; break;
				case 'ogg' : $mime_type = 'audio/ogg'; break;
			}
			echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
			echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."')"]);
			unset($playable, $mime_type);
		}
		echo "<br />\n";
		echo $text['description-'.$instance_label]."\n";
		echo "</td>\n";
		echo "</tr>\n";

		$instance_id = 'ivr_menu_exit_sound';
		$instance_label = 'exit_sound';
		$instance_value = $ivr_menu_exit_sound;
		echo "<tr>\n";
		echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-'.$instance_label]."\n";
		echo "</td>\n";
		echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
		echo "	<option value=''></option>\n";
		$found = $playable = false;
		if (!empty($audio_files[1]) && is_array($audio_files[1]) && @sizeof($audio_files[1]) != 0) {
			foreach ($audio_files[1] as $key => $value) {
				echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
				foreach ($value as $row) {
					if ($key == 'recordings') {
						if (
							!empty($instance_value) &&
							($instance_value == $row["value"] || $instance_value == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.$row["value"]) &&
							file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.pathinfo($row["value"], PATHINFO_BASENAME))
							) {
							$selected = "selected='selected'";
							$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
							$found = true;
						}
						else {
							unset($selected);
						}
					}
					else if ($key == 'sounds') {
						if (!empty($instance_value) && $instance_value == $row["value"]) {
							$selected = "selected='selected'";
							$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
							$found = true;
						}
						else {
							unset($selected);
						}
					}
					else {
						unset($selected);
					}
					echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
				}
				echo "</optgroup>\n";
			}
		}
		if (if_group("superadmin") && !empty($instance_value) && !$found) {
			echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
		}
		unset($selected);
		echo "	</select>\n";
		if (if_group("superadmin")) {
			echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
		}
		if ((permission_exists('recording_play') || permission_exists('recording_download')) && (!empty($playable) || empty($instance_value))) {
			switch (pathinfo($playable, PATHINFO_EXTENSION)) {
				case 'wav' : $mime_type = 'audio/wav'; break;
				case 'mp3' : $mime_type = 'audio/mpeg'; break;
				case 'ogg' : $mime_type = 'audio/ogg'; break;
			}
			echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
			echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."')"]);
			unset($playable, $mime_type);
		}
		echo "<br />\n";
		echo $text['description-'.$instance_label]."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-pin_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_pin_number' maxlength='255' value=\"".escape($ivr_menu_pin_number ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-pin_number']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_macro']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_macro' maxlength='255' value=\"".escape($ivr_menu_confirm_macro ?? '')."\">\n";
		echo "<br />\n";
		echo $text['description-comfirm_macro']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-comfirm_key']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_key' maxlength='255' value=\"".escape($ivr_menu_confirm_key ?? '')."\">\n";
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

	//--- end: advanced -----------------------

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
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='ivr_menu_enabled' name='ivr_menu_enabled' value='true' ".($ivr_menu_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='ivr_menu_enabled' name='ivr_menu_enabled'>\n";
		echo "		<option value='true' ".($ivr_menu_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($ivr_menu_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
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