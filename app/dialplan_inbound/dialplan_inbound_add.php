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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('inbound_route_add')) {
	//access granted
}
else {
	echo $text['label-access-denied'];
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

//add multi-lingual support
require_once "app_languages.php";
foreach($text as $key => $value) {
	$text[$key] = $value[$_SESSION['domain']['language']['code']];
}

//get the http get values and set them as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];
	$action = $_GET["action"];

//get the http post values and set them as php variables
	if (count($_POST)>0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
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
				}
			}
			unset ($prep_statement);
		}

		if (if_group("superadmin") && $action == "advanced") {
			//allow users in the superadmin group advanced control
		}
		else {
			if (strlen($condition_field_1) == 0) { $condition_field_1 = "destination_number"; }
			if (is_numeric($condition_expression_1)) { 
				//the number is numeric
				$condition_expression_1 = str_replace("+", "\+", $condition_expression_1);
				$condition_expression_1 = '^'.$condition_expression_1.'$';
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
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//remove the invalid characters from the extension name
		$dialplan_name = str_replace(" ", "_", $dialplan_name);
		$dialplan_name = str_replace("/", "", $dialplan_name);

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
		$sql .= "'$public_order', ";
		$sql .= "'public', ";
		$sql .= "'$dialplan_enabled', ";
		$sql .= "'$dialplan_description' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	//add condition public context
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
		$sql .= "'context', ";
		$sql .= "'public', ";
		$sql .= "'10' ";
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
			$sql .= "'30' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set domain
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
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'set', ";
			$sql .= "'domain=".$_SESSION['domain_name']."', ";
			$sql .= "'40' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//set domain_name
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
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'set', ";
			$sql .= "'domain_name=".$_SESSION['domain_name']."', ";
			$sql .= "'50' ";
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
			$sql .= "dialplan_detail_order ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$dialplan_uuid', ";
			$sql .= "'$dialplan_detail_uuid', ";
			$sql .= "'action', ";
			$sql .= "'limit', ";
			$sql .= "'db \${domain} inbound ".$limit." !USER_BUSY', ";
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
				$sql .= "'90' ";
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
			$sql .= "'105' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//commit the atomic transaction
		$count = $db->exec("COMMIT;"); //returns affected rows

	//delete the dialplan context from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_cmd = "memcache delete dialplan:public@".$_SESSION['domain_name'];
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//synchronize the xml config
		save_dialplan_xml();

	//redirect the user
		require_once "includes/header.php";
		echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4\">\n";
		echo "<div align='center'>\n";
		echo "".$text['confirm-update-complete']."\n";
		echo "</div>\n";
		require_once "includes/footer.php";
		return;
} //end if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

?>

<script type="text/javascript">
<!--
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
-->
</script>

<?php
//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";

	echo " 	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left'><span class=\"vexpl\"><span class=\"red\"><strong>".$text['title-dialplan-inbound-add']."\n";
	echo "			</strong></span></span>\n";
	echo "		</td>\n";
	echo "		<td align='right'>\n";
	if (permission_exists("inbound_route_edit") && $action == "advanced") {
		echo "			<input type='button' class='btn' name='' alt='basic' onclick=\"window.location='dialplan_inbound_add.php?action=basic'\" value='".$text['button-basic']."'>\n";
	}
	else {
		echo "			<input type='button' class='btn' name='' alt='advanced' onclick=\"window.location='dialplan_inbound_add.php?action=advanced'\" value='".$text['button-advanced']."'>\n";
	}
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4'\" value='".$text['button-back']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<span class=\"vexpl\">\n";
	echo "			".$text['description-dialplan-inbound-add']."\n";
	echo "		</span>\n";
	echo "		<br />\n";
	echo "			</span>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>";

	echo "<br />\n";
	echo "<br />\n";

	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' style='width: 60%;' type='text' name='dialplan_name' maxlength='255' value=\"$dialplan_name\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists("inbound_route_edit") && $action == "advanced") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-condition_1'].":\n";
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
			tbb.type='button';
			tbb.value='<';
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
		echo "	<table style='width: 60%;' border='0'>\n";
		echo "	<tr>\n";
		echo "	<td style='width: 62px;'>".$text['label-field'].":</td>\n";
		echo "	<td style='width: 35%;' nowrap='nowrap'>\n";

		echo "    <select class='formfld' name='condition_field_1' id='condition_field_1' onchange='changeToInput_condition_field_1(this);this.style.visibility = \"hidden\";' style='width:85%'>\n";
		echo "    <option value=''></option>\n";
		if (strlen($condition_field_1) > 0) {
			echo "    <option value='$condition_field_1' selected>$condition_field_1</option>\n";
		}
		echo "    <option value='context'>context</option>\n";
		echo "    <option value='username'>username</option>\n";
		echo "    <option value='rdnis'>rdnis</option>\n";
		echo "    <option value='destination_number'>destination_number</option>\n";
		echo "    <option value='public'>public</option>\n";
		echo "    <option value='caller_id_name'>caller_id_name</option>\n";
		echo "    <option value='caller_id_number'>caller_id_number</option>\n";
		echo "    <option value='ani'>ani</option>\n";
		echo "    <option value='ani2'>ani2</option>\n";
		echo "    <option value='uuid'>uuid</option>\n";
		echo "    <option value='source'>source</option>\n";
		echo "    <option value='chan_name'>chan_name</option>\n";
		echo "    <option value='network_addr'>network_addr</option>\n";
		echo "    </select>\n";
		echo "    <input type='button' id='btn_select_to_input_condition_field_1' class='btn' name='' alt='back' onclick='changeToInput_condition_field_1(document.getElementById(\"condition_field_1\"));this.style.visibility = \"hidden\";' value='<'>\n";
		echo "    <br />\n";
		echo "	</td>\n";
		echo "	<td style='width: 73px;'>&nbsp; ".$text['label-expression'].":</td>\n";
		echo "	<td>\n";
		echo "		<input class='formfld' type='text' name='condition_expression_1' maxlength='255' style='width:100%' value=\"$condition_expression_1\">\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "	<div id='desc_condition_expression_1'></div>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-condition_2'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		echo "	<table style='width: 60%;' border='0'>\n";
		echo "	<tr>\n";
		echo "	<td align='left' style='width: 62px;'>\n";
		echo "		".$text['label-field'].":\n";
		echo "	</td>\n";
		echo "	<td style='width: 35%;' align='left' nowrap='nowrap'>\n";
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
			tbb.type='button';
			tbb.value='<';
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
		echo "    <option value='context'>context</option>\n";
		echo "    <option value='username'>username</option>\n";
		echo "    <option value='rdnis'>rdnis</option>\n";
		echo "    <option value='destination_number'>destination_number</option>\n";
		echo "    <option value='public'>public</option>\n";
		echo "    <option value='caller_id_name'>caller_id_name</option>\n";
		echo "    <option value='caller_id_number'>caller_id_number</option>\n";
		echo "    <option value='ani'>ani</option>\n";
		echo "    <option value='ani2'>ani2</option>\n";
		echo "    <option value='uuid'>uuid</option>\n";
		echo "    <option value='source'>source</option>\n";
		echo "    <option value='chan_name'>chan_name</option>\n";
		echo "    <option value='network_addr'>network_addr</option>\n";
		echo "	</select>\n";
		echo "  <input type='button' id='btn_select_to_input_condition_field_2' class='btn' name='' alt='back' onclick='changeToInput_condition_field_2(document.getElementById(\"condition_field_2\"));this.style.visibility = \"hidden\";' value='<'>\n";
		echo "	<br />\n";
		echo "	</td>\n";
		echo "	<td style='width: 73px;' align='left'>\n";
		echo "		&nbsp; ".$text['label-expression'].":\n";
		echo "	</td>\n";
		echo "	<td>\n";
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
		echo "	".$text['label-destination-number'].":\n";
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
			echo "	<select name='destination_uuid' id='destination_uuid' class='formfld' style='width: 60%;' >\n";
			echo "	<option></option>\n";
			foreach ($result as &$row) {
				echo "		<option value='".$row["destination_uuid"]."'>".$row["destination_number"]."</option>\n";
			}
			echo "		</select>\n";
			echo "<br />\n";
			echo "".$text['label-select-inbound-destination-number']."\n";
		}
		else {
			echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"Add\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
		}
		unset ($prep_statement);

		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	if (permission_exists("inbound_route_edit") && $action=="advanced") {
		echo "    ".$text['label-action_1'].":\n";
	}
	else {
		echo "    ".$text['label-action'].":\n";
	}
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
	switch_select_destination("dialplan", "", "action_1", $action_1, "width: 60%;", "");

	echo "</td>\n";
	echo "</tr>\n";

	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists("inbound_route_edit") && $action=="advanced") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-action_2'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
		switch_select_destination("dialplan", "", "action_2", $action_2, "width: 60%;", "");

		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-limit'].":\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' style='width: 60%;' type='text' name='limit' maxlength='255' value=\"$limit\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='public_order' class='formfld' style='width: 60%;'>\n";
	if (strlen(htmlspecialchars($public_order))> 0) {
		echo "              <option selected='yes' value='".htmlspecialchars($public_order)."'>".htmlspecialchars($public_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		if (strlen($i) == 1) { echo "              <option value='00$i'>00$i</option>\n"; }
		if (strlen($i) == 2) { echo "              <option value='0$i'>0$i</option>\n"; }
		if (strlen($i) == 3) { echo "              <option value='$i'>$i</option>\n"; }
		$i++;
	}
	echo "              </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled' style='width: 60%;'>\n";
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
	echo "    <input class='formfld' style='width: 60%;' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
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
	require_once "includes/footer.php";
?>