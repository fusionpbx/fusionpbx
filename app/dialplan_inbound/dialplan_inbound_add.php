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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Riccardo Granchi <riccardo.granchi@nems.it>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('inbound_route_add')) {
	//access granted
}
else {
	echo $text['label-access-denied'];
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-dialplan-inbound-add'];
	require_once "resources/paging.php";

//get the http get values and set them as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];
	$action = $_GET["action"];

//get the http post values and set them as php variables
	if (count($_POST)>0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$redial_outbound_prefix = check_str($_POST["redial_outbound_prefix"]);
		$limit = check_str($_POST["limit"]);
		$public_order = check_str($_POST["public_order"]);
		$condition_field_1 = check_str($_POST["condition_field_1"]);
		$condition_expression_1 = check_str($_POST["condition_expression_1"]);
		$condition_field_2 = check_str($_POST["condition_field_2"]);
		$condition_expression_2 = check_str($_POST["condition_expression_2"]);
		$destination_uuid = check_str($_POST["destination_uuid"]);

 		$action_1 = check_str($_POST["action_1"]);
		//$action_1 = "transfer:1001 XML default";
		$action_1_array = explode(":", $action_1);
		$action_application_1 = array_shift($action_1_array);
		$action_data_1 = join(':', $action_1_array);

 		$action_2 = check_str($_POST["action_2"]);
		//$action_2 = "transfer:1001 XML default";
		$action_2_array = explode(":", $action_2);
		$action_application_2 = array_shift($action_2_array);
		$action_data_2 = join(':', $action_2_array);

		//$action_application_1 = check_str($_POST["action_application_1"]);
		//$action_data_1 = check_str($_POST["action_data_1"]);
		//$action_application_2 = check_str($_POST["action_application_2"]);
		//$action_data_2 = check_str($_POST["action_data_2"]);

		$destination_carrier = '';
		$destination_accountcode = '';

		//use the destination_uuid to set the condition_expression_1
		if (strlen($destination_uuid) > 0) {
			$sql = "select * from v_destinations ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and destination_uuid = '$destination_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			if (count($result) > 0) {
				foreach ($result as &$row) {
					$condition_expression_1 = $row["destination_number"];
					$fax_uuid = $row["fax_uuid"];
					$destination_carrier = $row["destination_carrier"];
					$destination_accountcode = $row["destination_accountcode"];
				}
			}
			unset ($prep_statement);
		}

		if (permission_exists("inbound_route_advanced") && $action == "advanced") {
			//allow users with group advanced control, not always superadmin. You may change this in group permissions
		}
		else {
			if (strlen($condition_field_1) == 0) { $condition_field_1 = "destination_number"; }
			if (is_numeric($condition_expression_1)) {
				//the number is numeric
				$condition_expression_1 = str_replace("+", "\+", $condition_expression_1);
				$condition_expression_1 = '^('.$condition_expression_1.')$';
			}
		}
		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "".$text['label-required-domain_uuid']."<br>\n"; }
		if (strlen($dialplan_name) == 0) { $msg .= "".$text['label-required-dialplan_name']."<br>\n"; }
		if (strlen($condition_field_1) == 0) { $msg .= "".$text['label-required-condition_field_1']."<br>\n"; }
		if (strlen($condition_expression_1) == 0) { $msg .= "".$text['label-required-condition_expression_1']."<br>\n"; }
		if (strlen($action_application_1) == 0) { $msg .= "".$text['label-required-action_application_1']."<br>\n"; }
		//if (strlen($limit) == 0) { $msg .= "Please provide: Limit<br>\n"; }
		//if (strlen($dialplan_enabled) == 0) { $msg .= "Please provide: Enabled True or False<br>\n"; }
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

	//remove the invalid characters from the extension name
		$dialplan_name = str_replace(" ", "_", $dialplan_name);
		$dialplan_name = str_replace("/", "", $dialplan_name);

	//set the context
		$context = '$${domain_name}';

	//start the atomic transaction
		$count = $db->exec("BEGIN;"); //returns affected rows

	//add the main dialplan entry
		$dialplan_uuid = uuid();
		$sql = "insert into v_dialplans ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "dialplan_uuid, ";
		$sql .= "app_uuid, ";
		$sql .= "dialplan_name, ";
		$sql .= "dialplan_continue, ";
		$sql .= "dialplan_order, ";
		$sql .= "dialplan_context, ";
		$sql .= "dialplan_enabled, ";
		$sql .= "dialplan_description ";
		$sql .= ") ";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$dialplan_uuid', ";
		$sql .= "'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4', ";
		$sql .= "'$dialplan_name', ";
		$sql .= "'false', ";
		$sql .= "'$public_order', ";
		$sql .= "'public', ";
		$sql .= "'$dialplan_enabled', ";
		$sql .= "'$dialplan_description' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	//add condition 1
		$dialplan_detail_uuid = uuid();
		$sql = "insert into v_dialplan_details ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "dialplan_uuid, ";
		$sql .= "dialplan_detail_uuid, ";
		$sql .= "dialplan_detail_tag, ";
		$sql .= "dialplan_detail_type, ";
		$sql .= "dialplan_detail_data, ";
		$sql .= "dialplan_detail_group, ";
		$sql .= "dialplan_detail_order ";
		$sql .= ") ";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$dialplan_uuid', ";
		$sql .= "'$dialplan_detail_uuid', ";
		$sql .= "'condition', ";
		$sql .= "'$condition_field_1', ";
		$sql .= "'$condition_expression_1', ";
		$sql .= "'0', ";
		$sql .= "'20' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	//add condition 2
		if (strlen($condition_field_2) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'condition', ";
			$sql .= "'$condition_field_2', ";
			$sql .= "'$condition_expression_2', ";
			$sql .= "'0', ";
			$sql .= "'30' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//export alert-info for distinctive ringtones
		if (count($_SESSION["domains"]) > 1) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'export', ";
			$sql .= "'alert_info=http://www.notused.com;info=alert-external;x-line-id=0', ";
			$sql .= "'0', ";
			$sql .= "'45' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set call_direction
		if (count($_SESSION["domains"]) > 1) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'set', ";
			$sql .= "'call_direction=inbound', ";
			$sql .= "'0', ";
			$sql .= "'50' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set accountcode
		if (strlen($destination_accountcode) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'set', ";
			$sql .= "'accountcode=$destination_accountcode', ";
			$sql .= "'0', ";
			$sql .= "'55' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set carrier
		if (strlen($destination_carrier) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'set', ";
			$sql .= "'carrier=$destination_carrier', ";
			$sql .= "'0', ";
			$sql .= "'60' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set limit
		if (strlen($limit) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'limit', ";
			$sql .= "'hash \${domain_name} inbound ".$limit." !USER_BUSY', ";
			$sql .= "'0', ";
			$sql .= "'65' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set redial outbound prefix
		if (strlen($redial_outbound_prefix) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'set', ";
			$sql .= "'effective_caller_id_number=".$redial_outbound_prefix."\${caller_id_number}', ";
			$sql .= "'0', ";
			$sql .= "'70' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set fax_uuid
		if (strlen($fax_uuid) > 0) {
			//get the fax information
				$sql = "select * from v_fax ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and fax_uuid = '".$fax_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$fax_extension = $row["fax_extension"];
					$fax_destination_number = $row["fax_destination_number"];
					$fax_name = $row["fax_name"];
					$fax_email = $row["fax_email"];
					$fax_pin_number = $row["fax_pin_number"];
					$fax_caller_id_name = $row["fax_caller_id_name"];
					$fax_caller_id_number = $row["fax_caller_id_number"];
					$fax_forward_number = $row["fax_forward_number"];
					$fax_description = $row["fax_description"];
				}
				unset ($prep_statement);

			//add set codec_string=PCMU,PCMA
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_group, ";
				$sql .= "dialplan_detail_order ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'$dialplan_detail_uuid', ";
				$sql .= "'action', ";
				$sql .= "'set', ";
				$sql .= "'codec_string=PCMU,PCMA', ";
				$sql .= "'0', ";
				$sql .= "'73' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//add set tone_detect_hits=1
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_group, ";
				$sql .= "dialplan_detail_order ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'$dialplan_detail_uuid', ";
				$sql .= "'action', ";
				$sql .= "'set', ";
				$sql .= "'tone_detect_hits=1', ";
				$sql .= "'0', ";
				$sql .= "'75' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//add execute_on_tone_detect
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_group, ";
				$sql .= "dialplan_detail_order ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'$dialplan_detail_uuid', ";
				$sql .= "'action', ";
				$sql .= "'set', ";
				$sql .= "'execute_on_tone_detect=transfer ".$fax_extension." XML ".$_SESSION["context"]."', ";
				$sql .= "'0', ";
				$sql .= "'80' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//add tone_detect fax 1100 r +5000
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_group, ";
				$sql .= "dialplan_detail_order ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'$dialplan_detail_uuid', ";
				$sql .= "'action', ";
				$sql .= "'tone_detect', ";
				$sql .= "'fax 1100 r +5000', ";
				$sql .= "'0', ";
				$sql .= "'85' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//add sleep to provide time for fax detection
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_group, ";
				$sql .= "dialplan_detail_order ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'$dialplan_detail_uuid', ";
				$sql .= "'action', ";
				$sql .= "'sleep', ";
				$sql .= "'3000', ";
				$sql .= "'0', ";
				$sql .= "'90' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

			//set codec_string=${ep_codec_string}
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_group, ";
				$sql .= "dialplan_detail_order ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'$dialplan_detail_uuid', ";
				$sql .= "'action', ";
				$sql .= "'export', ";
				$sql .= "'codec_string=\${ep_codec_string}', ";
				$sql .= "'0', ";
				$sql .= "'93' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);
		}

	//set answer
		$tmp_app = false;
		if ($action_application_1 == "ivr") { $tmp_app = true; }
		if ($action_application_2 == "ivr") { $tmp_app = true; }
		if ($action_application_1 == "conference") { $tmp_app = true; }
		if ($action_application_2 == "conference") { $tmp_app = true; }
		if ($tmp_app) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'answer', ";
			$sql .= "'', ";
			$sql .= "'0', ";
			$sql .= "'95' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		unset($tmp_app);

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
		$sql .= "dialplan_detail_group, ";
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
		$sql .= "'0', ";
		$sql .= "'100' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	//add action 2
		if (strlen($action_application_2) > 0) {
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'$action_application_2', ";
			$sql .= "'$action_data_2', ";
			$sql .= "'0', ";
			$sql .= "'105' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//update the destination dialplan_uuid
		if (strlen($destination_uuid) > 0) {
			$sql = "update v_destinations set ";
			$sql .= "dialplan_uuid = '".$dialplan_uuid."' ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and destination_uuid = '".$destination_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//commit the atomic transaction
		$count = $db->exec("COMMIT;"); //returns affected rows

	//clear the cache
		$cache = new cache;
		$cache->delete("dialplan:public");

	//synchronize the xml config
		save_dialplan_xml();

	//redirect message
		$_SESSION["message"] = $text['confirm-update-complete'];
		header("Location: ".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4");
		return;
} //end if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
$destination = new destinations;

?>

<script type="text/javascript">
	function type_onchange(dialplan_detail_type) {
		var field_value = document.getElementById(dialplan_detail_type).value;
		if (dialplan_detail_type == "condition_field_1") {
			if (field_value == "destination_number") {
				document.getElementById("desc_condition_expression_1").innerHTML = "expression: 5551231234";
			}
			else if (field_value == "zzz") {
				document.getElementById("desc_condition_expression_1").innerHTML = "";
			}
			else {
				document.getElementById("desc_condition_expression_1").innerHTML = "";
			}
		}
		if (dialplan_detail_type == "condition_field_2") {
			if (field_value == "destination_number") {
				document.getElementById("desc_condition_expression_2").innerHTML = "expression: 5551231234";
			}
			else if (field_value == "zzz") {
				document.getElementById("desc_condition_expression_2").innerHTML = "";
			}
			else {
				document.getElementById("desc_condition_expression_2").innerHTML = "";
			}
		}
	}
</script>

<?php
//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<span class=\"title\">".$text['title-dialplan-inbound-add']."</span>\n";
	echo "		</td>\n";
	echo "		<td align='right'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4'\" value='".$text['button-back']."'>\n";
	if (permission_exists("inbound_route_advanced")) {
		if (permission_exists("inbound_route_edit") && $action == "advanced") {
			echo "			<input type='button' class='btn' name='' alt='".$text['button-basic']."' onclick=\"window.location='dialplan_inbound_add.php?action=basic'\" value='".$text['button-basic']."'>\n";
		}
		else {
			echo "			<input type='button' class='btn' name='' alt='".$text['button-advanced']."' onclick=\"window.location='dialplan_inbound_add.php?action=advanced'\" value='".$text['button-advanced']."'>\n";
		}
	}
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<br />";
	echo "			".$text['description-dialplan-inbound-add']."\n";
	echo "			<br />\n";
	echo "			</span>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>";
	echo "<br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"$dialplan_name\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists("inbound_route_edit") && $action == "advanced" && permission_exists("inbound_route_advanced")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-condition_1']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		?>
		<script>
		var Objs;
		function changeToInput_condition_field_1(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			tb.setAttribute('id', 'condition_field_1');
			tb.setAttribute('style', 'width: 85%;');
			tb.value=obj.options[obj.selectedIndex].value;
			document.getElementById('btn_select_to_input_condition_field_1').style.visibility = 'hidden';
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.setAttribute('style', 'margin-left: 4px;');
			tbb.type='button';
			tbb.value=$("<div />").html('&#9665;').text();
			tbb.objs=[obj,tb,tbb];
			tbb.onclick=function(){ Replace_condition_field_1(this.objs); }
			obj.parentNode.insertBefore(tb,obj);
			obj.parentNode.insertBefore(tbb,obj);
			obj.parentNode.removeChild(obj);
			Replace_condition_field_1(this.objs);
		}

		function Replace_condition_field_1(obj){
			obj[2].parentNode.insertBefore(obj[0],obj[2]);
			obj[0].parentNode.removeChild(obj[1]);
			obj[0].parentNode.removeChild(obj[2]);
			document.getElementById('btn_select_to_input_condition_field_1').style.visibility = 'visible';
		}
		</script>
		<?php
		echo "	<table width='70%' border='0'>\n";
		echo "	<tr>\n";
		echo "	<td>".$text['label-field']."</td>\n";
		echo "	<td width='50%' nowrap='nowrap'>\n";

		echo "    <select class='formfld' name='condition_field_1' id='condition_field_1' onchange='changeToInput_condition_field_1(this);this.style.visibility = \"hidden\";' style='width:85%'>\n";
		echo "    <option value=''></option>\n";
		if (strlen($condition_field_1) > 0) {
			echo "    <option value='$condition_field_1' selected>$condition_field_1</option>\n";
		}
		echo "    <option value='context'>".$text['option-context']."</option>\n";
		echo "    <option value='username'>".$text['option-username']."</option>\n";
		echo "    <option value='rdnis'>".$text['option-rdnis']."</option>\n";
		echo "    <option value='destination_number'>".$text['option-destination_number']."</option>\n";
		echo "    <option value='public'>".$text['option-public']."</option>\n";
		echo "    <option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
		echo "    <option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
		echo "    <option value='ani'>".$text['option-ani']."</option>\n";
		echo "    <option value='ani2'>".$text['option-ani2']."</option>\n";
		echo "    <option value='uuid'>".$text['option-uuid']."</option>\n";
		echo "    <option value='source'>".$text['option-source']."</option>\n";
		echo "    <option value='chan_name'>".$text['option-chan_name']."</option>\n";
		echo "    <option value='network_addr'>".$text['option-network_addr']."</option>\n";
		echo "    </select>\n";
		echo "    <input type='button' id='btn_select_to_input_condition_field_1' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_condition_field_1(document.getElementById(\"condition_field_1\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
		echo "    <br />\n";
		echo "	</td>\n";
		echo "	<td>&nbsp;&nbsp;&nbsp;".$text['label-expression']."</td>\n";
		echo "	<td width='50%'>\n";
		echo "		<input class='formfld' type='text' name='condition_expression_1' maxlength='255' style='width:100%' value=\"$condition_expression_1\">\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "	<div id='desc_condition_expression_1'></div>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-condition_2']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		echo "	<table width='70%' border='0'>\n";
		echo "	<tr>\n";
		echo "	<td align='left'>".$text['label-field']."</td>\n";
		echo "	<td width='50%' align='left' nowrap='nowrap'>\n";
		?>
		<script>
		var Objs;
		function changeToInput_condition_field_2(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			tb.setAttribute('id', 'condition_field_2');
			tb.setAttribute('style', 'width: 85%;');
			tb.value=obj.options[obj.selectedIndex].value;
			document.getElementById('btn_select_to_input_condition_field_2').style.visibility = 'hidden';
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.setAttribute('style', 'margin-left: 4px;');
			tbb.type='button';
			tbb.value=$("<div />").html('&#9665;').text();
			tbb.objs=[obj,tb,tbb];
			tbb.onclick=function(){ Replace_condition_field_2(this.objs); }
			obj.parentNode.insertBefore(tb,obj);
			obj.parentNode.insertBefore(tbb,obj);
			obj.parentNode.removeChild(obj);
			Replace_condition_field_2(this.objs);
		}

		function Replace_condition_field_2(obj){
			obj[2].parentNode.insertBefore(obj[0],obj[2]);
			obj[0].parentNode.removeChild(obj[1]);
			obj[0].parentNode.removeChild(obj[2]);
			document.getElementById('btn_select_to_input_condition_field_2').style.visibility = 'visible';
		}
		</script>
		<?php
		echo "    <select class='formfld' name='condition_field_2' id='condition_field_2' onchange='changeToInput_condition_field_2(this);this.style.visibility = \"hidden\";' style='width:85%'>\n";
		echo "    <option value=''></option>\n";
		if (strlen($condition_field_2) > 0) {
			echo "    <option value='$condition_field_2' selected>$condition_field_2</option>\n";
		}
		echo "    <option value='context'>".$text['option-context']."</option>\n";
		echo "    <option value='username'>".$text['option-username']."</option>\n";
		echo "    <option value='rdnis'>".$text['option-rdnis']."</option>\n";
		echo "    <option value='destination_number'>".$text['option-destination_number']."</option>\n";
		echo "    <option value='public'>".$text['option-public']."</option>\n";
		echo "    <option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
		echo "    <option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
		echo "    <option value='ani'>".$text['option-ani']."</option>\n";
		echo "    <option value='ani2'>".$text['option-ani2']."</option>\n";
		echo "    <option value='uuid'>".$text['option-uuid']."</option>\n";
		echo "    <option value='source'>".$text['option-source']."</option>\n";
		echo "    <option value='chan_name'>".$text['option-chan_name']."</option>\n";
		echo "    <option value='network_addr'>".$text['option-network_addr']."</option>\n";
		echo "	</select>\n";
		echo "  <input type='button' id='btn_select_to_input_condition_field_2' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_condition_field_2(document.getElementById(\"condition_field_2\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
		echo "	<br />\n";
		echo "	</td>\n";
		echo "	<td align='left'>&nbsp;&nbsp;&nbsp;".$text['label-expression']."\n";
		echo "	</td>\n";
		echo "	<td width='50%'>\n";
		echo "		<input class='formfld' type='text' name='condition_expression_2' maxlength='255' style='width:100%' value=\"$condition_expression_2\">\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "	<div id='desc_condition_expression_2'></div>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-destination-number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		$sql = "select * from v_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and destination_type = 'inbound' ";
		$sql .= "order by destination_number asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if (count($result) > 0) {
			echo "	<select name='destination_uuid' id='destination_uuid' class='formfld' >\n";
			echo "	<option></option>\n";
			foreach ($result as &$row) {
				if (strlen($row["dialplan_uuid"]) == 0) {
					echo "		<option value='".$row["destination_uuid"]."' style=\"font-weight:bold;\">".$row["destination_number"]." ".$row["destination_description"]."</option>\n";
				}
				else {
					echo "		<option value='".$row["destination_uuid"]."'>".$row["destination_number"]." ".$row["destination_description"]."</option>\n";
				}
			}
			echo "		</select>\n";
			echo "<br />\n";
			echo "".$text['label-select-inbound-destination-number']."\n";
		}
		else {
			echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
		}
		unset ($prep_statement);

		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	if (permission_exists("inbound_route_edit") && $action=="advanced") {
		echo "    ".$text['label-action_1']."\n";
	}
	else {
		echo "    ".$text['label-action']."\n";
	}
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'action_1', $action_1);
	echo "</td>\n";
	echo "</tr>\n";

	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists("inbound_route_edit") && $action=="advanced") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-action_2']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo $destination->select('dialplan', 'action_2', $action_2);
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-limit']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit' maxlength='255' value=\"$limit\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-redial-outbound-prefix']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='redial_outbound_prefix' maxlength='255' value=\"$limit\">\n";
	echo "<br />\n";
	echo "".$text['description-redial-outbound-prefix']."<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='public_order' class='formfld'>\n";
	if (strlen(htmlspecialchars($public_order))> 0) {
		echo "		<option selected='yes' value='".htmlspecialchars($public_order)."'>".htmlspecialchars($public_order)."</option>\n";
	}
	$i = 100;
	while($i <= 999) {
		if (strlen($i) == 1) { echo "		<option value='00$i'>00$i</option>\n"; }
		if (strlen($i) == 2) { echo "		<option value='0$i'>0$i</option>\n"; }
		if (strlen($i) == 3) { echo "		<option value='$i'>$i</option>\n"; }
		$i = $i + 10;
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "	\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
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
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td colspan='5' align='right'>\n";
	if ($action == "update" && permission_exists("inbound_route_edit")) {
		echo "	<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "		<br>";
	echo "		<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	</td>\n";
	echo "</tr>";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
