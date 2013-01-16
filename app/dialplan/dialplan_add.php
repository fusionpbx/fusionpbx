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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('dialplan_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

$order_by = $_GET["order_by"];
$order = $_GET["order"];


//POST to PHP variables
	if (count($_POST)>0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);
		$condition_field_1 = check_str($_POST["condition_field_1"]);
		$condition_expression_1 = check_str($_POST["condition_expression_1"]);
		$condition_field_2 = check_str($_POST["condition_field_2"]);
		$condition_expression_2 = check_str($_POST["condition_expression_2"]);

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

		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($dialplan_name) == 0) { $msg .= "Please provide: Extension Name<br>\n"; }
		if (strlen($condition_field_1) == 0) { $msg .= "Please provide: Condition Field<br>\n"; }
		if (strlen($condition_expression_1) == 0) { $msg .= "Please provide: Condition Expression<br>\n"; }
		if (strlen($action_application_1) == 0) { $msg .= "Please provide: Action Application<br>\n"; }
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
		$db->exec("BEGIN;"); //returns affected rows

	//add the main dialplan include entry
		$dialplan_context = $_SESSION['context'];
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
		$sql .= "'742714e5-8cdf-32fd-462c-cbe7e3d655db', ";
		$sql .= "'$dialplan_name', ";
		$sql .= "'$dialplan_order', ";
		$sql .= "'false', ";
		$sql .= "'".$_SESSION['context']."', ";
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
		$sql .= "'1' ";
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
			$sql .= "'2' ";
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
			$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//redirect the browser
		require_once "includes/header.php";
		echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php\">\n";
		echo "<div align='center'>\n";
		echo "Update Complete\n";
		echo "</div>\n";
		require_once "includes/footer.php";
		return;
} //end if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

?><script type="text/javascript">
<!--
function type_onchange(dialplan_detail_type) {
	var field_value = document.getElementById(dialplan_detail_type).value;
	if (dialplan_detail_type == "condition_field_1") {
		if (field_value == "destination_number") {
			document.getElementById("desc_condition_expression_1").innerHTML = "expression: ^12081231234$";
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
			document.getElementById("desc_condition_expression_2").innerHTML = "expression: ^12081231234$";
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
echo "<div align='center'>";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";

echo "<tr class='border'>\n";
echo "	<td align=\"left\">\n";
echo "		<br>";

echo "<form method='post' name='frm' action=''>\n";
echo "<div align='center'>\n";

echo " 	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "	<tr>\n";
echo "		<td align='left'><span class=\"vexpl\"><span class=\"red\"><strong>Dialplan\n";
echo "			</strong></span></span>\n";
echo "		</td>\n";
echo "		<td align='right'>\n";
echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='dialplans.php'\" value='Back'>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td align='left' colspan='2'>\n";
echo "			<span class=\"vexpl\">\n";
echo "				The dialplan is used to setup call destinations based on conditions and context.\n";
echo "				You can use the dialplan to send calls to gateways, auto attendants, external numbers,\n";
echo "				to scripts, or any destination.\n";
echo "			</span>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	</table>";

echo "<br />\n";
echo "<br />\n";

echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    Name:\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "    <input class='formfld' style='width: 60%;' type='text' name='dialplan_name' maxlength='255' value=\"$dialplan_name\">\n";
echo "<br />\n";
echo "\n";
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
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
echo "	Condition 1:\n";
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
echo "	<td style='width: 62px;'>Field:</td>\n";
echo "	<td style='width: 35%;' nowrap='nowrap'>\n";
echo "    <select class='formfld' name='condition_field_1' id='condition_field_1' onchange='changeToInput_condition_field_1(this);this.style.visibility = \"hidden\";' style='width:85%'>\n";
echo "    <option value=''></option>\n";
if (strlen($condition_field_1) > 0) {
	echo "    <option value='$condition_field_1' selected='selected'>$condition_field_1</option>\n";
}
echo "	<optgroup label='Field'>\n";
echo "		<option value='context'>context</option>\n";
echo "		<option value='username'>username</option>\n";
echo "		<option value='rdnis'>rdnis</option>\n";
echo "		<option value='destination_number'>destination_number</option>\n";
echo "		<option value='public'>public</option>\n";
echo "		<option value='caller_id_name'>caller_id_name</option>\n";
echo "		<option value='caller_id_number'>caller_id_number</option>\n";
echo "		<option value='ani'>ani</option>\n";
echo "		<option value='ani2'>ani2</option>\n";
echo "		<option value='uuid'>uuid</option>\n";
echo "		<option value='source'>source</option>\n";
echo "		<option value='chan_name'>chan_name</option>\n";
echo "		<option value='network_addr'>network_addr</option>\n";
echo "	</optgroup>\n";
echo "	<optgroup label='Time'>\n";
echo "		<option value='hour'>hour</option>\n";
echo "		<option value='minute'>minute</option>\n";
echo "		<option value='minute-of-day'>minute of day</option>\n";
echo "		<option value='mday'>day of month</option>\n";
echo "		<option value='mweek'>week of month</option>\n";
echo "		<option value='mon'>month</option>\n";
echo "		<option value='yday'>day of year</option>\n";
echo "		<option value='year'>year</option>\n";
echo "		<option value='wday'>day of week</option>\n";
echo "		<option value='week'>week</option>\n";
echo "	</optgroup>\n";
echo "    </select>\n";
echo "    <input type='button' id='btn_select_to_input_condition_field_1' class='btn' name='' alt='back' onclick='changeToInput_condition_field_1(document.getElementById(\"condition_field_1\"));this.style.visibility = \"hidden\";' value='<'>\n";
echo "    <br />\n";
echo "	</td>\n";
echo "	<td style='width: 73px;'>&nbsp; Expression:</td>\n";
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
echo "	Condition 2:\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";

echo "	<table style='width: 60%;' border='0'>\n";
echo "	<tr>\n";
echo "	<td align='left' style='width: 62px;'>\n";
echo "		Field:\n";
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
echo "	<optgroup label='Field'>\n";
echo "		<option value='context'>context</option>\n";
echo "		<option value='username'>username</option>\n";
echo "		<option value='rdnis'>rdnis</option>\n";
echo "		<option value='destination_number'>destination_number</option>\n";
echo "		<option value='public'>public</option>\n";
echo "		<option value='caller_id_name'>caller_id_name</option>\n";
echo "		<option value='caller_id_number'>caller_id_number</option>\n";
echo "		<option value='ani'>ani</option>\n";
echo "		<option value='ani2'>ani2</option>\n";
echo "		<option value='uuid'>uuid</option>\n";
echo "		<option value='source'>source</option>\n";
echo "		<option value='chan_name'>chan_name</option>\n";
echo "		<option value='network_addr'>network_addr</option>\n";
echo "	</optgroup>\n";
echo "	<optgroup label='Time'>\n";
echo "		<option value='hour'>hour</option>\n";
echo "		<option value='minute'>minute</option>\n";
echo "		<option value='minute-of-day'>minute of day</option>\n";
echo "		<option value='mday'>day of month</option>\n";
echo "		<option value='mweek'>week of month</option>\n";
echo "		<option value='mon'>month</option>\n";
echo "		<option value='yday'>day of year</option>\n";
echo "		<option value='year'>year</option>\n";
echo "		<option value='wday'>day of week</option>\n";
echo "		<option value='week'>week</option>\n";
echo "	</optgroup>\n";
echo "	</select>\n";
echo "  <input type='button' id='btn_select_to_input_condition_field_2' class='btn' name='' alt='back' onclick='changeToInput_condition_field_2(document.getElementById(\"condition_field_2\"));this.style.visibility = \"hidden\";' value='<'>\n";
echo "	<br />\n";
echo "	</td>\n";
echo "	<td style='width: 73px;' align='left'>\n";
echo "		&nbsp; Expression:\n";
echo "	</td>\n";
echo "	<td>\n";
echo "		<input class='formfld' type='text' name='condition_expression_2' maxlength='255' style='width:100%' value=\"$condition_expression_2\">\n";
echo "	</td>\n";
echo "	</tr>\n";
echo "	</table>\n";
echo "	<div id='desc_condition_expression_2'></div>\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    Action 1:\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";

//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
switch_select_destination("dialplan", "", "action_1", $action_1, "width: 60%;", "");

/*
echo "	<table style='width: 60%;' border='0' >\n";
echo "	<tr>\n";
echo "	<td style='width: 62px;'>Application: </td>\n";
echo "	<td style='width: 35%;'>\n";
echo "    <select class='formfld' style='width:100%' id='action_application_1' name='action_application_1' onchange='type_onchange(\"action_application_1\");'>\n";
echo "    <option value=''></option>\n";
if (strlen($action_application_1) > 0) {
	echo "    <option value='$action_application_1' selected>$action_application_1</option>\n";
}
echo "    <option value='answer'>answer</option>\n";
echo "    <option value='bridge'>bridge</option>\n";
echo "    <option value='cond'>cond</option>\n";
echo "    <option value='db'>db</option>\n";
echo "    <option value='global_set'>global_set</option>\n";
echo "    <option value='group'>group</option>\n";
echo "    <option value='expr'>expr</option>\n";
echo "    <option value='export'>export</option>\n";
echo "    <option value='hangup'>hangup</option>\n";
echo "    <option value='info'>info</option>\n";
echo "    <option value='javascript'>javascript</option>\n";
echo "    <option value='read'>read</option>\n";
echo "    <option value='reject'>reject</option>\n";
echo "    <option value='playback'>playback</option>\n";
echo "    <option value='reject'>reject</option>\n";
echo "    <option value='respond'>respond</option>\n";
echo "    <option value='ring_ready'>ring_ready</option>\n";
echo "    <option value='set'>set</option>\n";
echo "    <option value='set_user'>set_user</option>\n";
echo "    <option value='sleep'>sleep</option>\n";
echo "    <option value='sofia_contact'>sofia_contact</option>\n";
echo "    <option value='transfer'>transfer</option>\n";
echo "    <option value='voicemail'>voicemail</option>\n";
echo "    <option value='conference'>conference</option>\n";
echo "    <option value='conference_set_auto_outcall'>conference_set_auto_outcall</option>\n";
echo "    </select><br />\n";
echo "	</td>\n";
echo "	<td style='width: 73px;'>\n";
echo "		&nbsp; Data: \n";
echo "	</td>\n";
echo "	<td>\n";
echo "		<input class='formfld' style='width: 100%;' type='text' name='action_data_1' maxlength='255' value=\"$action_data_1\">\n";
echo "	</td>\n";
echo "	</tr>\n";
echo "	</table>\n";
echo "	<div id='desc_action_data_1'></div>\n";
*/
echo "</td>\n";
echo "</tr>\n";

echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "    Action 2:\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";

//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
switch_select_destination("dialplan", "", "action_2", $action_2, "width: 60%;", "");

/*
echo "	<table style='width: 60%;' border='0' >\n";
echo "	<tr>\n";
echo "	<td style='width: 62px;'>Application: </td>\n";
echo "	<td style='width: 35%;'>\n";
echo "    <select class='formfld' style='width:100%' id='action_application_2' name='action_application_2' onchange='type_onchange(\"action_application_2\");'>\n";
echo "    <option value=''></option>\n";
if (strlen($action_application_2) > 0) {
	echo "    <option value='$action_application_2' selected>$action_application_2</option>\n";
}
echo "    <option value='answer'>answer</option>\n";
echo "    <option value='bridge'>bridge</option>\n";
echo "    <option value='cond'>cond</option>\n";
echo "    <option value='db'>db</option>\n";
echo "    <option value='global_set'>global_set</option>\n";
echo "    <option value='group'>group</option>\n";
echo "    <option value='expr'>expr</option>\n";
echo "    <option value='export'>export</option>\n";
echo "    <option value='hangup'>hangup</option>\n";
echo "    <option value='info'>info</option>\n";
echo "    <option value='javascript'>javascript</option>\n";
echo "    <option value='read'>read</option>\n";
echo "    <option value='reject'>reject</option>\n";
echo "    <option value='playback'>playback</option>\n";
echo "    <option value='reject'>reject</option>\n";
echo "    <option value='respond'>respond</option>\n";
echo "    <option value='ring_ready'>ring_ready</option>\n";
echo "    <option value='set'>set</option>\n";
echo "    <option value='set_user'>set_user</option>\n";
echo "    <option value='sleep'>sleep</option>\n";
echo "    <option value='sofia_contact'>sofia_contact</option>\n";
echo "    <option value='transfer'>transfer</option>\n";
echo "    <option value='voicemail'>voicemail</option>\n";
echo "    <option value='conference'>conference</option>\n";
echo "    <option value='conference_set_auto_outcall'>conference_set_auto_outcall</option>\n";
echo "    </select><br />\n";
echo "	</td>\n";
echo "	<td style='width: 73px;'>\n";
echo "		&nbsp; Data: \n";
echo "	</td>\n";
echo "	<td>\n";
echo "		<input class='formfld' style='width: 100%;' type='text' name='action_data_2' maxlength='255' value=\"$action_data_2\">\n";
echo "	</td>\n";
echo "	</tr>\n";
echo "	</table>\n";
echo "	<div id='desc_action_data_2'></div>\n";
*/
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    Order:\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "              <select name='dialplan_order' class='formfld' style='width: 60%;'>\n";
//echo "              <option></option>\n";
if (strlen(htmlspecialchars($dialplan_order))> 0) {
	echo "              <option selected='yes' value='".htmlspecialchars($dialplan_order)."'>".htmlspecialchars($dialplan_order)."</option>\n";
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
echo "    Enabled:\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "    <select class='formfld' name='dialplan_enabled' style='width: 60%;'>\n";
//echo "    <option value=''></option>\n";
if ($dialplan_enabled == "true") { 
	echo "    <option value='true' SELECTED >true</option>\n";
}
else {
	echo "    <option value='true'>true</option>\n";
}
if ($dialplan_enabled == "false") { 
	echo "    <option value='false' SELECTED >false</option>\n";
}
else {
	echo "    <option value='false'>false</option>\n";
}
echo "    </select>\n";
echo "<br />\n";
echo "\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "    Description:\n";
echo "</td>\n";
echo "<td colspan='4' class='vtable' align='left'>\n";
//echo "    <textarea class='formfld' name='dialplan_description' rows='4'>$dialplan_description</textarea>\n";
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
echo "			<input type='submit' name='submit' class='btn' value='Save'>\n";
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

require_once "includes/footer.php";

?>