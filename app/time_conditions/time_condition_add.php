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
if (permission_exists('time_condition_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "resources/header.php";
require_once "resources/paging.php";

//add multi-lingual support
require_once "app_languages.php";
foreach($text as $key => $value) {
	$text[$key] = $value[$_SESSION['domain']['language']['code']];
}

//set the variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//get the post form variables and se them to php variables
	if (count($_POST)>0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_number = check_str($_POST["dialplan_number"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);
		$condition_hour = check_str($_POST["condition_hour"]);
		$condition_minute = check_str($_POST["condition_minute"]);
		$condition_minute_of_day = check_str($_POST["condition_minute_of_day"]);
		$condition_mday = check_str($_POST["condition_mday"]);
		$condition_mweek = check_str($_POST["condition_mweek"]);
		$condition_mon = check_str($_POST["condition_mon"]);
		$condition_yday = check_str($_POST["condition_yday"]);
		$condition_year = check_str($_POST["condition_year"]);
		$condition_wday = check_str($_POST["condition_wday"]);
		$condition_week = check_str($_POST["condition_week"]);

		$action_1 = check_str($_POST["action_1"]);
		//$action_1 = "transfer:1001 XML default";
		$action_1_array = explode(":", $action_1);
		$action_application_1 = array_shift($action_1_array);
		$action_data_1 = join(':', $action_1_array);

		$anti_action_1 = check_str($_POST["anti_action_1"]);
		//$anti_action_1 = "transfer:1001 XML default";
		$anti_action_1_array = explode(":", $anti_action_1);
		$anti_action_application_1 = array_shift($anti_action_1_array);
		$anti_action_data_1 = join(':', $anti_action_1_array);

		//$action_application_1 = check_str($_POST["action_application_1"]);
		//$action_data_1 = check_str($_POST["action_data_1"]);
		//$anti_action_application_1 = check_str($_POST["anti_action_application_1"]);
		//$anti_action_data_1 = check_str($_POST["anti_action_data_1"]);
		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "".$text['label-required-domain_uuid']."<br>\n"; }
		if (strlen($dialplan_name) == 0) { $msg .= "".$text['label-required-dialplan_name']."<br>\n"; }
		if (strlen($dialplan_number) == 0) { $msg .= "".$text['label-required-dialplan_number']."<br>\n"; }
		//if (strlen($condition_field_1) == 0) { $msg .= "Please provide: Condition Field<br>\n"; }
		//if (strlen($condition_expression_1) == 0) { $msg .= "Please provide: Condition Expression<br>\n"; }
		//if (strlen($action_application_1) == 0) { $msg .= "Please provide: Action Application<br>\n"; }
		//if (strlen($enabled) == 0) { $msg .= "Please provide: Enabled True or False<br>\n"; }
		//if (strlen($dialplan_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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

	//start the atomic transaction
		$count = $db->exec("BEGIN;"); //returns affected rows


	//add the main dialplan include entry
		$dialplan_uuid = uuid();
		$sql = "insert into v_dialplans ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "dialplan_uuid, ";
		$sql .= "app_uuid, ";
		$sql .= "dialplan_name, ";
		$sql .= "dialplan_order, ";
		$sql .= "dialplan_continue, ";
		$sql .= "dialplan_context, ";
		$sql .= "dialplan_enabled, ";
		$sql .= "dialplan_description ";
		$sql .= ") ";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$dialplan_uuid', ";
		$sql .= "'4b821450-926b-175a-af93-a03c441818b1', ";
		$sql .= "'$dialplan_name', ";
		$sql .= "'$dialplan_order', ";
		$sql .= "'true', ";
		$sql .= "'".$_SESSION['context']."', ";
		$sql .= "'$dialplan_enabled', ";
		$sql .= "'$dialplan_description' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	//add a destination number
		if (strlen($dialplan_number) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'destination_number', ";
			$sql .= "'^$dialplan_number$', ";
			$sql .= "'1' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//add time based conditions
		if (strlen($condition_wday) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'wday', ";
			$sql .= "'$condition_wday', ";
			$sql .= "'1' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_minute_of_day) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'minute-of-day', ";
			$sql .= "'$condition_minute_of_day', ";
			$sql .= "'2' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_mday) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'mday', ";
			$sql .= "'$condition_mday', ";
			$sql .= "'3' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_mweek) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'mweek', ";
			$sql .= "'$condition_mweek', ";
			$sql .= "'4' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_mon) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'mon', ";
			$sql .= "'$condition_mon', ";
			$sql .= "'5' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_hour) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'hour', ";
			$sql .= "'$condition_hour', ";
			$sql .= "'6' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_minute) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'minute', ";
			$sql .= "'$condition_minute', ";
			$sql .= "'7' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_week) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'week', ";
			$sql .= "'$condition_week', ";
			$sql .= "'8' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_yday) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'yday', ";
			$sql .= "'$condition_yday', ";
			$sql .= "'9' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		if (strlen($condition_year) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'year', ";
			$sql .= "'$condition_year', ";
			$sql .= "'10' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//add action 1
		$dialplan_detail_uuid = uuid();
		$sql = "insert into v_dialplan_details ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "dialplan_uuid, ";
		$sql .= "dialplan_detail_uuid, ";
		$sql .= "dialplan_detail_tag, ";
		$sql .= "dialplan_detail_type, ";
		$sql .= "dialplan_detail_data, ";
		$sql .= "dialplan_detail_order ";
		$sql .= ") ";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$dialplan_uuid', ";
		$sql .= "'$dialplan_detail_uuid', ";
		$sql .= "'action', ";
		$sql .= "'$action_application_1', ";
		$sql .= "'$action_data_1', ";
		$sql .= "'3' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	//add anti-action 1
		if (strlen($anti_action_application_1) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'anti-action', ";
			$sql .= "'$anti_action_application_1', ";
			$sql .= "'$anti_action_data_1', ";
			$sql .= "'4' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//commit the atomic transaction
		$count = $db->exec("COMMIT;"); //returns affected rows

	//synchronize the xml config
		save_dialplan_xml();

	//delete the dialplan context from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_cmd = "memcache delete dialplan:".$_SESSION["context"];
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//redirect the browser
		$_SESSION["message"] = $text['confirm-update-complete'];
		header("Location: ".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1");
		return;

} //end if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

?><script type="text/javascript">
<!--

function show_advanced_config() {
	$('#show_advanced_box').slideToggle();
	$('#show_advanced').slideToggle();
}

function template_onchange(tmp_object) {
	var template = tmp_object.value;
	if (template == "Office Hours Mon-Fri 8am-5pm") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '480-1020';
		document.getElementById("condition_mday").value = '';
		document.getElementById("condition_mweek").value = '';
		document.getElementById("condition_mon").value = '';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '2-6';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Office Hours Mon-Fri 9am-6pm") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '540-1080';
		document.getElementById("condition_mday").value = '';
		document.getElementById("condition_mweek").value = '';
		document.getElementById("condition_mon").value = '';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '2-6';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "New Year's Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '1';
		document.getElementById("condition_mweek").value = '';
		document.getElementById("condition_mon").value = '1';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Martin Luther King Jr Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '';
		document.getElementById("condition_mweek").value = '3';
		document.getElementById("condition_mon").value = '1';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '2';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Presidents Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '';
		document.getElementById("condition_mweek").value = '3';
		document.getElementById("condition_mon").value = '2';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '2';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Memorial Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '25-31';
		document.getElementById("condition_mweek").value = '';
		document.getElementById("condition_mon").value = '5';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '2';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Independence Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '4';
		document.getElementById("condition_mweek").value = '';
		document.getElementById("condition_mon").value = '7';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Labor Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '';
		document.getElementById("condition_mweek").value = '1';
		document.getElementById("condition_mon").value = '9';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '2';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Columbus Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '';
		document.getElementById("condition_mweek").value = '2';
		document.getElementById("condition_mon").value = '10';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '2';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Veteran's Day") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '11';
		document.getElementById("condition_mweek").value = '';
		document.getElementById("condition_mon").value = '11';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Thanksgiving") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '';
		document.getElementById("condition_mweek").value = '4';
		document.getElementById("condition_mon").value = '11';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '5-6';
		document.getElementById("condition_week").value = '';
	}
	else if (template == "Christmas") {
		document.getElementById("condition_hour").value = '';
		document.getElementById("condition_minute").value = '';
		document.getElementById("condition_minute_of_day").value = '';
		document.getElementById("condition_mday").value = '25';
		document.getElementById("condition_mweek").value = '';
		document.getElementById("condition_mon").value = '12';
		document.getElementById("condition_yday").value = '';
		document.getElementById("condition_year").value = '';
		document.getElementById("condition_wday").value = '';
		document.getElementById("condition_week").value = '';
	}
}

function type_onchange(dialplan_detail_type) {
	var field_value = document.getElementById(dialplan_detail_type).value;
	//desc_action_data_1
	//desc_anti_action_data

	if (dialplan_detail_type == "action_application_1") {
		if (field_value == "transfer") {
			document.getElementById("desc_action_data_1").innerHTML = "Transfer the call through the dialplan to the destination. data: 1001 XML default";
		}
		else if (field_value == "bridge") {
			var tmp = "Bridge the call to a destination. <br />";
			tmp += "sip uri (voicemail): sofia/internal/*98@${domain}<br />\n";
			tmp += "sip uri (external number): sofia/gateway/gatewayname/12081231234<br />\n";
			tmp += "sip uri (hunt group): sofia/internal/7002@${domain}<br />\n";
			tmp += "sip uri (auto attendant): sofia/internal/5002@${domain}<br />\n";
			//tmp += "sip uri (user): /user/1001@${domain}<br />\n";
			document.getElementById("desc_action_data_1").innerHTML = tmp;
		}
		else if (field_value == "global_set") {
			document.getElementById("desc_action_data_1").innerHTML = "Sets a global variable. data: var1=1234";
		}
		else if (field_value == "javascript") {
			document.getElementById("desc_action_data_1").innerHTML = "Direct the call to a javascript file. data: disa.js";
		}
		else if (field_value == "set") {
			document.getElementById("desc_action_data_1").innerHTML = "Sets a variable. data: var2=1234";
		}
		else if (field_value == "voicemail") {
			document.getElementById("desc_action_data_1").innerHTML = "Send the call to voicemail. data: default ${domain} 1001";
		}
		else {
			document.getElementById("desc_action_data_1").innerHTML = "";
		}
	}
	if (dialplan_detail_type == "anti_action_application_1") {
		if (field_value == "transfer") {
			document.getElementById("desc_anti_action_data_1").innerHTML = "Transfer the call through the dialplan to the destination. data: 1001 XML default";
		}
		else if (field_value == "bridge") {
			var tmp = "Bridge the call to a destination. <br />";
			tmp += "sip uri (voicemail): sofia/internal/*98@${domain}<br />\n";
			tmp += "sip uri (external number): sofia/gateway/gatewayname/12081231234<br />\n";
			tmp += "sip uri (hunt group): sofia/internal/7002@${domain}<br />\n";
			tmp += "sip uri (auto attendant): sofia/internal/5002@${domain}<br />\n";
			//tmp += "sip uri (user): /user/1001@${domain}<br />\n";
			document.getElementById("desc_anti_action_data_1").innerHTML = tmp;
		}
		else if (field_value == "global_set") {
			document.getElementById("desc_anti_action_data_1").innerHTML = "Sets a global variable. data: var1=1234";
		}
		else if (field_value == "javascript") {
			document.getElementById("desc_anti_action_data_1").innerHTML = "Direct the call to a javascript file. data: disa.js";
		}
		else if (field_value == "set") {
			document.getElementById("desc_anti_action_data_1").innerHTML = "Sets a variable. data: var2=1234";
		}
		else if (field_value == "voicemail") {
			document.getElementById("desc_anti_action_data_1").innerHTML = "Send the call to voicemail. data: default ${domain} 1001";
		}
		else {
			document.getElementById("desc_anti_action_data_1").innerHTML = "";
		}
	}

}
-->
</script>

<?php
echo "<div align='center'>";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";

echo "<tr class='border'>\n";
echo "	<td align=\"left\">\n";

echo "<form method='post' name='frm' action=''>\n";
echo "<div align='center'>\n";

echo " 	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "	<tr>\n";
echo "		<td align='left'>\n";
echo "			<span class=\"title\">".$text['title-time-condition-add']."</span><br />\n";
echo "		</td>\n";
echo "		<td align='right'>\n";
echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1'\" value='".$text['button-back']."'>\n";
echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td align='left' colspan='2'>\n";
echo "			<span class=\"vexpl\">\n";
echo "			".$text['description-time-condition-add']."\n";
echo "			</span>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	</table>";

echo "<br />\n";
echo "<br />\n";

echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td width='20%' class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-name'].":\n";
echo "</td>\n";
echo "<td width='80%' class='vtable' align='left'>\n";
echo "    <input class='formfld' style='width: 60%;' type='text' name='dialplan_name' maxlength='255' value=\"$dialplan_name\">\n";
echo "	<br />\n";
echo "	".$text['description-name']."\n";
echo "<br />\n";
echo "\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "	".$text['label-extension'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='width: 60%;' type='text' name='dialplan_number' id='dialplan_number' maxlength='255' value=\"$dialplan_number\">\n";
echo "	<br />\n";
echo "	".$text['description-extension']."<br />\n";
echo "</td>\n";
echo "</tr>\n";

//echo "<tr>\n";
//echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
//echo "    Continue:\n";
//echo "</td>\n";
//echo "<td class='vtable' align='left'>\n";
//echo "    <select class='formfld' name='dialplan_continue' style='width: 60%;'>\n";
//echo "    <option value=''></option>\n";
//if ($dialplan_continue == "true") {
//	echo "    <option value='true' SELECTED >true</option>\n";
//}
//else {
//	echo "    <option value='true'>true</option>\n";
//}
//if ($dialplan_continue == "false") {
//	echo "    <option value='false' SELECTED >false</option>\n";
//}
//else {
//	echo "    <option value='false'>false</option>\n";
//}
//echo "    </select>\n";
//echo "<br />\n";
//echo "Extension Continue in most cases this is false. default: false\n";
//echo "</td>\n";
//echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-template'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<select class='formfld' name='template2' id='template' onchange='template_onchange(this);'>\n";
echo "		<option value=''></option>\n";
echo "	<optgroup label='".$text['label-optgroup-office']."'>\n";
echo "		<option value='Office Hours Mon-Fri 8am-5pm'>".$text['label-option-office-8am-5pm']."</option>\n";
echo "		<option value='Office Hours Mon-Fri 9am-6pm'>".$text['label-option-office-9am-6pm']."</option>\n";
echo "	</optgroup>\n";
echo "	<optgroup label='".$text['label-optgroup-us-holidays']."'>\n";
echo "		<option value=\"New Year's Day\">".$text['label-option-us-holidays-new-year']."</option>\n";
echo "		<option value='Martin Luther King Jr Day'>".$text['label-option-us-holidays-martin-luther-king']."</option>\n";
echo "		<option value='Presidents Day'>".$text['label-option-us-holidays-president']."</option>\n";
echo "		<option value='Memorial Day'>".$text['label-option-us-holidays-memorial']."</option>\n";
echo "		<option value='Independence Day'>".$text['label-option-us-holidays-independence']."</option>\n";
echo "		<option value='Labor Day'>".$text['label-option-us-holidays-labor']."</option>\n";
echo "		<option value='Columbus Day'>".$text['label-option-us-holidays-columbus']."</option>\n";
echo "		<option value=\"Veteran's Day\">".$text['label-option-us-holidays-veteran']."</option>\n";
echo "		<option value='Thanksgiving'>".$text['label-option-us-holidays-thanksgiving']."</option>\n";
echo "		<option value='Christmas'>".$text['label-option-us-holidays-christmas']."</option>\n";
echo "	</optgroup>\n";
echo "</select>\n";
echo "<br />\n";
echo "".$text['description-template']."\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "	".$text['label-day-of-month'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_mday' id='condition_mday' maxlength='255' value=\"$condition_mday\">\n";
echo "	<br />\n";
echo "	".$text['description-day-of-month']." 1-31 <i>mday</i><br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "	".$text['label-day-of-week'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_wday' id='condition_wday' maxlength='255' value=\"$condition_wday\">\n";
echo "	<br />\n";
echo "	".$text['description-day-of-week']." <i>wday</i>\n";
echo "	<br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "	".$text['label-minute-of-day'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_minute_of_day' id='condition_minute_of_day' maxlength='255' value=\"$condition_minute_of_day\">\n";
echo "	<br />\n";
echo "	".$text['description-minute-of-day']." <i>minute-of-day</i><br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "	".$text['label-month'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_mon' id='condition_mon' maxlength='255' value=\"$condition_mon\">\n";
echo "	<br />\n";
echo "	".$text['description-month']." <i>mon</i><br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "	".$text['label-week-of-month'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_mweek' id='condition_mweek' maxlength='255' value=\"$condition_mweek\">\n";
echo "	<br />\n";
echo "	".$text['description-week-of-month']." 1-6 <i>mweek</i><br />\n";
echo "	<br />\n";
echo "</td>\n";
echo "</tr>\n";

//begin: show_advanced
	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

	echo "	<div id=\"show_advanced_box\">\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "		<td width=\"20%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
	echo "		<td width=\"80%\" class=\"vtable\">\n";
	echo "			<input type=\"button\" class='btn' onClick=\"show_advanced_config()\" value=\"".$text['button-advanced']."\"></input></a>\n";
	echo "		</td>\n";
	echo "		</tr>\n";
	echo "		</table>\n";
	echo "	</div>\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

	echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";

	echo "<tr>\n";
	echo "<td width='20%' class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-day-of-year'].":\n";
	echo "</td>\n";
	echo "<td width='80%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_yday' id='condition_yday' maxlength='255' value=\"$condition_yday\">\n";
	echo "	<br />\n";
	echo "	".$text['description-day-of-year']." 1-365 <i>yday</i>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-hour'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_hour' id='condition_hour' maxlength='255' value=\"$condition_hour\">\n";
	echo "	<br />\n";
	echo "	".$text['description-hour']." 0-23 <i>hour</i>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-minute'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_minute' id='condition_minute' maxlength='255' value=\"$condition_minute\">\n";
	echo "	<br />\n";
	echo "	".$text['description-minute']." 0-59 <i>minute</i>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-week'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_week' id='condition_week' maxlength='255' value=\"$condition_week\">\n";
	echo "	<br />\n";
	echo "	".$text['description-week']." 1-52 <i>week</i>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-year'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' style='width: 60%;' type='text' name='condition_year' id='condition_year' maxlength='255' value=\"$condition_year\">\n";
	echo "	<br />\n";
	echo "	".$text['description-year']." 0-9999 <i>year</i>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	</table>\n";
	echo "	</div>";

	echo "</td>\n";
	echo "</tr>\n";
//end: show_advanced

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-action-when-true'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";

//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
switch_select_destination("dialplan", $action_1, "action_1", $action_1, "", "");

echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-action-when-false'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";

//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
switch_select_destination("dialplan", $anti_action_1, "anti_action_1", $anti_action_1, "", "");

echo "	<div id='desc_anti_action_data_1'></div>\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-order'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<select name='dialplan_order' class='formfld'>\n";
$i = 300;
while($i <= 999) {
	$selected = ($dialplan_order == $i) ? "selected" : null;
	if (strlen($i) == 1) { echo "<option value='00$i' ".$selected.">00$i</option>\n"; }
	if (strlen($i) == 2) { echo "<option value='0$i' ".$selected.">0$i</option>\n"; }
	if (strlen($i) == 3) { echo "<option value='$i' ".$selected.">$i</option>\n"; }
	$i = $i + 10;
}
echo "	</select>\n";
echo "	<br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-enabled'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "    <select class='formfld' name='dialplan_enabled'>\n";
if ($dialplan_enabled == "true") {
	echo "    <option value='true' SELECTED >".$text['label-true']."</option>\n";
}
else {
	echo "    <option value='true'>".$text['label-true']."</option>\n";
}
if ($dialplan_enabled == "false") {
	echo "    <option value='false' SELECTED >".$text['label-false']."</option>\n";
}
else {
	echo "    <option value='false'>".$text['label-false']."</option>\n";
}
echo "    </select>\n";
echo "<br />\n";
echo "\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-description'].":\n";
echo "</td>\n";
echo "<td colspan='4' class='vtable' align='left'>\n";
echo "    <input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
echo "<br />\n";
echo "\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "	<td colspan='5' align='right'>\n";
if ($action == "update") {
	echo "			<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
}
echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
echo "	</td>\n";
echo "</tr>";

echo "</table>";
echo "</div>";
echo "</form>";

echo "</td>\n";
echo "</tr>";
echo "</table>";
echo "</div>";

echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>