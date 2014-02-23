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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
if (permission_exists('ivr_menu_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//set the http get/post variable(s) to a php variable
	if (isset($_REQUEST["id"])) {
		$ivr_menu_uuid = $_GET["id"];
	}

//get the ivr_menus data
	$sql = "select * from v_ivr_menus ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and ivr_menu_uuid = '$ivr_menu_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$ivr_menu_name = 'copy-'.$row["ivr_menu_name"];
		$ivr_menu_extension = $row["ivr_menu_extension"];
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
		$ivr_menu_inter_digit_timeout = $row["ivr_menu_inter_digit_timeout"];
		$ivr_menu_max_failures = $row["ivr_menu_max_failures"];
		$ivr_menu_max_timeouts = $row["ivr_menu_max_timeouts"];
		$ivr_menu_digit_len = $row["ivr_menu_digit_len"];
		$ivr_menu_direct_dial = $row["ivr_menu_direct_dial"];
		$ivr_menu_enabled = $row["ivr_menu_enabled"];
		$ivr_menu_description = 'copy: '.$row["ivr_menu_description"];
	}
	unset ($prep_statement);

//get the the ivr menu options
	$sql = "select * from v_ivr_menu_options ";
	$sql .= "where ivr_menu_uuid = '$ivr_menu_uuid' ";
	$sql .= "and domain_uuid = '$domain_uuid' ";
	$sql .= "order by ivr_menu_uuid asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result_options = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//copy the ivr_menus
	$ivr_menu_uuid = uuid();
	$sql = "insert into v_ivr_menus ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "ivr_menu_uuid, ";
	$sql .= "ivr_menu_name, ";
	$sql .= "ivr_menu_extension, ";
	$sql .= "ivr_menu_greet_long, ";
	$sql .= "ivr_menu_greet_short, ";
	$sql .= "ivr_menu_invalid_sound, ";
	$sql .= "ivr_menu_exit_sound, ";
	$sql .= "ivr_menu_confirm_macro, ";
	$sql .= "ivr_menu_confirm_key, ";
	$sql .= "ivr_menu_tts_engine, ";
	$sql .= "ivr_menu_tts_voice, ";
	$sql .= "ivr_menu_confirm_attempts, ";
	$sql .= "ivr_menu_timeout, ";
	$sql .= "ivr_menu_inter_digit_timeout, ";
	$sql .= "ivr_menu_max_failures, ";
	$sql .= "ivr_menu_max_timeouts, ";
	$sql .= "ivr_menu_digit_len, ";
	$sql .= "ivr_menu_direct_dial, ";
	$sql .= "ivr_menu_enabled, ";
	$sql .= "ivr_menu_description ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$ivr_menu_uuid', ";
	$sql .= "'$ivr_menu_name', ";
	$sql .= "'$ivr_menu_extension', ";
	$sql .= "'$ivr_menu_greet_long', ";
	$sql .= "'$ivr_menu_greet_short', ";
	$sql .= "'$ivr_menu_invalid_sound', ";
	$sql .= "'$ivr_menu_exit_sound', ";
	$sql .= "'$ivr_menu_confirm_macro', ";
	$sql .= "'$ivr_menu_confirm_key', ";
	$sql .= "'$ivr_menu_tts_engine', ";
	$sql .= "'$ivr_menu_tts_voice', ";
	$sql .= "'$ivr_menu_confirm_attempts', ";
	$sql .= "'$ivr_menu_timeout', ";
	$sql .= "'$ivr_menu_inter_digit_timeout', ";
	$sql .= "'$ivr_menu_max_failures', ";
	$sql .= "'$ivr_menu_max_timeouts', ";
	$sql .= "'$ivr_menu_digit_len', ";
	$sql .= "'$ivr_menu_direct_dial', ";
	$sql .= "'$ivr_menu_enabled', ";
	$sql .= "'$ivr_menu_description' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);

//get the the ivr menu options
	foreach ($result_options as &$row) {
		$ivr_menu_option_digits = $row["ivr_menu_option_digits"];
		$ivr_menu_option_action = $row["ivr_menu_option_action"];
		$ivr_menu_option_param = $row["ivr_menu_option_param"];
		$ivr_menu_option_order = $row["ivr_menu_option_order"];
		$ivr_menu_option_description = $row["ivr_menu_option_description"];

		//copy the ivr menu options
			$ivr_menu_option_uuid = uuid();
			$sql = "insert into v_ivr_menu_options ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "ivr_menu_uuid, ";
			$sql .= "ivr_menu_option_uuid, ";
			$sql .= "ivr_menu_option_digits, ";
			$sql .= "ivr_menu_option_action, ";
			$sql .= "ivr_menu_option_param, ";
			$sql .= "ivr_menu_option_order, ";
			$sql .= "ivr_menu_option_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$ivr_menu_uuid', ";
			$sql .= "'$ivr_menu_option_uuid', ";
			$sql .= "'$ivr_menu_option_digits', ";
			$sql .= "'$ivr_menu_option_action', ";
			$sql .= "'$ivr_menu_option_param', ";
			$sql .= "'$ivr_menu_option_order', ";
			$sql .= "'$ivr_menu_option_description' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
	}

//synchronize the xml config
	save_dialplan_xml();

//delete the dialplan context from memcache
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$switch_cmd = "memcache delete dialplan:".$_SESSION["context"];
		$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
	}

//redirect the user
	$_SESSION["message"] = $text['message-copy'];
	header("Location: ivr_menus.php");
	return;

?>