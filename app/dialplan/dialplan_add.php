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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('dialplan_add')) {
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

//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-dialplan_add'];
	require_once "resources/paging.php";

//set the variables
	if (count($_POST) > 0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);

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

		$dialplan_context = check_str($_POST["dialplan_context"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);
		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

//set the default
	if (strlen($dialplan_context) == 0) { $dialplan_context = $_SESSION['context']; }

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']."domain_uuid<br>\n"; }
		if (strlen($dialplan_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		if (strlen($condition_field_1) == 0) { $msg .= $text['message-required'].$text['label-condition_1']." ".$text['label-field']."<br>\n"; }
		if (strlen($condition_expression_1) == 0) { $msg .= $text['message-required'].$text['label-condition_1']." ".$text['label-expression']."<br>\n"; }
		if (strlen($action_application_1) == 0) { $msg .= $text['message-required'].$text['label-action_1']."<br>\n"; }
		//if (strlen($dialplan_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		//if (strlen($dialplan_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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

	//start the atomic transaction
		$db->exec("BEGIN;"); //returns affected rows

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
		$sql .= "'742714e5-8cdf-32fd-462c-cbe7e3d655db', ";
		$sql .= "'$dialplan_name', ";
		$sql .= "'$dialplan_order', ";
		$sql .= "'false', ";
		$sql .= "'$dialplan_context', ";
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
			$switch_cmd = "memcache delete dialplan:".$dialplan_context;
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	$_SESSION["message"] = $text['message-update'];
	header("Location: ".PROJECT_PATH."/app/dialplan/dialplans.php");
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
echo "		<td align='left'>\n";
echo "			<span class=\"title\">".$text['header-dialplan-add']."</span>\n";
echo "		</td>\n";
echo "		<td align='right'>\n";
echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='dialplans.php'\" value='".$text['button-back']."'>\n";
echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
echo "		</td>\n";
echo "	</tr>\n";

echo "	<tr>\n";
echo "		<td align='left' colspan='2'>\n";
echo "			<br><span class=\"vexpl\">".$text['description-dialplan_manager-superadmin']."</span>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	</table>";
echo "<br />\n";

echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-name'].":\n";
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
echo "	<table style='width: 60%;' border='0'>\n";
echo "	<tr>\n";
echo "	<td style='width: 62px;'>".$text['label-field'].":</td>\n";
echo "	<td style='width: 35%;' nowrap='nowrap'>\n";
echo "    <select class='formfld' name='condition_field_1' id='condition_field_1' onchange='changeToInput_condition_field_1(this);this.style.visibility = \"hidden\";' style='width:85%'>\n";
echo "    <option value=''></option>\n";
if (strlen($condition_field_1) > 0) {
	echo "    <option value='$condition_field_1' selected='selected'>$condition_field_1</option>\n";
}
echo "	<optgroup label='Field'>\n";
echo "		<option value='context'>".$text['option-context']."</option>\n";
echo "		<option value='username'>".$text['option-username']."</option>\n";
echo "		<option value='rdnis'>".$text['option-rdnis']."</option>\n";
echo "		<option value='destination_number'>".$text['option-destination_number']."</option>\n";
echo "		<option value='public'>".$text['option-public']."</option>\n";
echo "		<option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
echo "		<option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
echo "		<option value='ani'>".$text['option-ani']."</option>\n";
echo "		<option value='ani2'>".$text['option-ani2']."</option>\n";
echo "		<option value='uuid'>".$text['option-uuid']."</option>\n";
echo "		<option value='source'>".$text['option-source']."</option>\n";
echo "		<option value='chan_name'>".$text['option-chan_name']."</option>\n";
echo "		<option value='network_addr'>".$text['option-network_addr']."</option>\n";
echo "	</optgroup>\n";
echo "	<optgroup label='Time'>\n";
echo "		<option value='hour'>".$text['option-hour']."</option>\n";
echo "		<option value='minute'>".$text['option-minute']."</option>\n";
echo "		<option value='minute-of-day'>".$text['option-minute_of_day']."</option>\n";
echo "		<option value='mday'>".$text['option-day_of_month']."</option>\n";
echo "		<option value='mweek'>".$text['option-week_of_month']."</option>\n";
echo "		<option value='mon'>".$text['option-month']."</option>\n";
echo "		<option value='yday'>".$text['option-day_of_year']."</option>\n";
echo "		<option value='year'>".$text['option-year']."</option>\n";
echo "		<option value='wday'>".$text['option-day_of_week']."</option>\n";
echo "		<option value='week'>".$text['option-week']."</option>\n";
echo "	</optgroup>\n";
echo "    </select>\n";
echo "    <input type='button' id='btn_select_to_input_condition_field_1' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_condition_field_1(document.getElementById(\"condition_field_1\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
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
echo "	<optgroup label='Field'>\n";
echo "		<option value='context'>".$text['option-context']."</option>\n";
echo "		<option value='username'>".$text['option-username']."</option>\n";
echo "		<option value='rdnis'>".$text['option-rdnis']."</option>\n";
echo "		<option value='destination_number'>".$text['option-destination_number']."</option>\n";
echo "		<option value='public'>".$text['option-public']."</option>\n";
echo "		<option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
echo "		<option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
echo "		<option value='ani'>".$text['option-ani']."</option>\n";
echo "		<option value='ani2'>".$text['option-ani2']."</option>\n";
echo "		<option value='uuid'>".$text['option-uuid']."</option>\n";
echo "		<option value='source'>".$text['option-source']."</option>\n";
echo "		<option value='chan_name'>".$text['option-chan_name']."</option>\n";
echo "		<option value='network_addr'>".$text['option-network_addr']."</option>\n";
echo "	</optgroup>\n";
echo "	<optgroup label='Time'>\n";
echo "		<option value='hour'>".$text['option-hour']."</option>\n";
echo "		<option value='minute'>".$text['option-minute']."</option>\n";
echo "		<option value='minute-of-day'>".$text['option-minute_of_day']."</option>\n";
echo "		<option value='mday'>".$text['option-day_of_month']."</option>\n";
echo "		<option value='mweek'>".$text['option-week_of_month']."</option>\n";
echo "		<option value='mon'>".$text['option-month']."</option>\n";
echo "		<option value='yday'>".$text['option-day_of_year']."</option>\n";
echo "		<option value='year'>".$text['option-year']."</option>\n";
echo "		<option value='wday'>".$text['option-day_of_week']."</option>\n";
echo "		<option value='week'>".$text['option-week']."</option>\n";
echo "	</optgroup>\n";
echo "	</select>\n";
echo "  <input type='button' id='btn_select_to_input_condition_field_2' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_condition_field_2(document.getElementById(\"condition_field_2\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
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

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-action_1'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";

//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
switch_select_destination("dialplan", "", "action_1", $action_1, "", "");

echo "</td>\n";
echo "</tr>\n";

echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-action_2'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";

//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
switch_select_destination("dialplan", "", "action_2", $action_2, "", "");

echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "	<td class='vncell' valign='top' align='left' nowrap>\n";
echo " 		".$text['label-context'].":\n";
echo "	</td>\n";
echo "	<td colspan='4' class='vtable' align='left'>\n";
echo "		<input class='formfld' style='width: 60%;' type='text' name='dialplan_context' maxlength='255' value=\"$dialplan_context\">\n";
echo "		<br />\n";
echo "	</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "	".$text['label-order'].":\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<select name='dialplan_order' class='formfld'>\n";
//echo "		<option></option>\n";
if (strlen(htmlspecialchars($dialplan_order)) > 0) {
	echo "		 <option selected='selected' value='".htmlspecialchars($dialplan_order)."'>".htmlspecialchars($dialplan_order)."</option>\n";
}
$i = 200;
while($i <= 999) {
	echo "		<option value='$i'>$i</option>\n";
	$i = $i + 10;
}
echo "	</select>\n";
echo "	<br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "	<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "		".$text['label-enabled'].":\n";
echo "	</td>\n";
echo "	<td class='vtable' align='left'>\n";
echo "		<select class='formfld' name='dialplan_enabled'>\n";
if ($dialplan_enabled == "true") {
	echo "			<option value='true' selected='selected' >".$text['option-true']."</option>\n";
}
else {
	echo "			<option value='true'>".$text['option-true']."</option>\n";
}
if ($dialplan_enabled == "false") {
	echo "			<option value='false' selected='selected' >".$text['option-false']."</option>\n";
}
else {
	echo "			<option value='false'>".$text['option-false']."</option>\n";
}
echo "		</select>\n";
echo "		<br />\n";
echo "	</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "	<td class='vncell' valign='top' align='left' nowrap>\n";
echo " 		".$text['label-description'].":\n";
echo "	</td>\n";
echo "	<td colspan='4' class='vtable' align='left'>\n";
echo "		<input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
echo "		<br />\n";
echo "	</td>\n";
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

require_once "resources/footer.php";

?>