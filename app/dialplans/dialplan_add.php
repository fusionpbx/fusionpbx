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
//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('dialplan_add')) {
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

//set the variables
	if (count($_POST) > 0) {
		$dialplan_name = $_POST["dialplan_name"];

		$condition_field_1 = $_POST["condition_field_1"];
		$condition_expression_1 = $_POST["condition_expression_1"];
		$condition_field_2 = $_POST["condition_field_2"];
		$condition_expression_2 = $_POST["condition_expression_2"];

 		$action_1 = $_POST["action_1"];
		//$action_1 = "transfer:1001 XML default";
		$action_1_array = explode(":", $action_1);
		$action_application_1 = array_shift($action_1_array);
		$action_data_1 = join(':', $action_1_array);

 		$action_2 = $_POST["action_2"];
		//$action_2 = "transfer:1001 XML default";
		$action_2_array = explode(":", $action_2);
		$action_application_2 = array_shift($action_2_array);
		$action_data_2 = join(':', $action_2_array);

		//$action_application_1 = $_POST["action_application_1"];
		//$action_data_1 = $_POST["action_data_1"];
		//$action_application_2 = $_POST["action_application_2"];
		//$action_data_2 = $_POST["action_data_2"];

		$dialplan_context = $_POST["dialplan_context"];
		$dialplan_order = $_POST["dialplan_order"];
		$dialplan_enabled = $_POST["dialplan_enabled"];
		$dialplan_description = $_POST["dialplan_description"];
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

//set the default
	if (strlen($dialplan_context) == 0) { $dialplan_context = $_SESSION['domain_name']; }

//add or update data from http post
	if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dialplans.php');
				exit;
			}

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
	
		//add the main dialplan include entry
			$dialplan_uuid = uuid();
			$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
			$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][0]['app_uuid'] = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
			$array['dialplans'][0]['dialplan_name'] = $dialplan_name;
			$array['dialplans'][0]['dialplan_order'] = $dialplan_order;
			$array['dialplans'][0]['dialplan_continue'] = 'false';
			$array['dialplans'][0]['dialplan_context'] = $dialplan_context;
			$array['dialplans'][0]['dialplan_enabled'] = $dialplan_enabled;
			$array['dialplans'][0]['dialplan_description'] = $dialplan_description;

		//add condition 1
			$dialplan_detail_uuid = uuid();
			$array['dialplan_details'][0]['domain_uuid'] = $domain_uuid;
			$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
			$array['dialplan_details'][0]['dialplan_detail_tag'] = 'condition';
			$array['dialplan_details'][0]['dialplan_detail_type'] = $condition_field_1;
			$array['dialplan_details'][0]['dialplan_detail_data'] = $condition_expression_1;
			$array['dialplan_details'][0]['dialplan_detail_order'] = '1';

		//add condition 2
			if (strlen($condition_field_2) > 0) {
				$dialplan_detail_uuid = uuid();
				$array['dialplan_details'][1]['domain_uuid'] = $domain_uuid;
				$array['dialplan_details'][1]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplan_details'][1]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
				$array['dialplan_details'][1]['dialplan_detail_tag'] = 'condition';
				$array['dialplan_details'][1]['dialplan_detail_type'] = $condition_field_2;
				$array['dialplan_details'][1]['dialplan_detail_data'] = $condition_expression_2;
				$array['dialplan_details'][1]['dialplan_detail_order'] = '2';
			}
	
		//add action 1
			$dialplan_detail_uuid = uuid();
			$array['dialplan_details'][2]['domain_uuid'] = $domain_uuid;
			$array['dialplan_details'][2]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplan_details'][2]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
			$array['dialplan_details'][2]['dialplan_detail_tag'] = 'action';
			if ($destination->valid($action_application_1.':'.$action_data_1)) {
				$array['dialplan_details'][2]['dialplan_detail_type'] = $action_application_1;
				$array['dialplan_details'][2]['dialplan_detail_data'] = $action_data_1;
			}
			$array['dialplan_details'][2]['dialplan_detail_order'] = '3';
	
		//add action 2
			if (strlen($action_application_2) > 0) {
				$dialplan_detail_uuid = uuid();
				$array['dialplan_details'][3]['domain_uuid'] = $domain_uuid;
				$array['dialplan_details'][3]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplan_details'][3]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
				$array['dialplan_details'][3]['dialplan_detail_tag'] = 'action';
				if ($destination->valid($action_application_2.':'.$action_data_2)) {
					$array['dialplan_details'][3]['dialplan_detail_type'] = $action_application_2;
					$array['dialplan_details'][3]['dialplan_detail_data'] = $action_data_2;
				}
				$array['dialplan_details'][3]['dialplan_detail_order'] = '4';
			}
	
		//execute inserts
			$database = new database;
			$database->app_name = 'dialplans';
			$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
			$database->save($array);
			unset($array);
	
		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);
	
		//send a message and redirect the user
			message::add($text['message-update']);
			header("Location: ".PROJECT_PATH."/app/dialplans/dialplans.php");
			exit;
	}

//javascript type on change
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
	}
	-->
	</script>
	<?php

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-dialplan_add'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-dialplan-add']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'dialplans.php']);
	echo button::create(['type'=>'button','label'=>$text['button-advanced'],'icon'=>'tools','style'=>'margin-left: 15px;','link'=>'dialplan_edit.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-dialplan_manager-superadmin']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"".escape($dialplan_name)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	//echo "<tr>\n";
	//echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	//echo "    Continue\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "    <select class='formfld' name='dialplan_continue' style='width: 60%;'>\n";
	//echo "    <option value=''></option>\n";
	//if ($dialplan_continue == "true") {
	//	echo "    <option value='true' selected='selected'>true</option>\n";
	//}
	//else {
	//	echo "    <option value='true'>true</option>\n";
	//}
	//if ($dialplan_continue == "false") {
	//	echo "    <option value='false' selected='selected'>false</option>\n";
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
	echo "	<table border='0'>\n";
	echo "	<tr>\n";
	//echo "	<td nowrap='nowrap'>".$text['label-field']."</td>\n";
	echo "	<td nowrap='nowrap'>\n";
	echo "    <select class='formfld' name='condition_field_1' id='condition_field_1' onchange='changeToInput_condition_field_1(this);this.style.visibility = \"hidden\";'>\n";
	echo "    <option value=''></option>\n";
	if (strlen($condition_field_1) > 0) {
		echo "    <option value='".escape($condition_field_1)."' selected='selected'>".escape($condition_field_1)."</option>\n";
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
	echo "	<input type='button' id='btn_select_to_input_condition_field_1' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_condition_field_1(document.getElementById(\"condition_field_1\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
	echo "	<br />\n";
	echo "	</td>\n";
	//echo "	<td>&nbsp;&nbsp;&nbsp;".$text['label-expression']."</td>\n";
	echo "	<td>\n";
	echo "		&nbsp;<input class='formfld' type='text' name='condition_expression_1' maxlength='255' value=\"".escape($condition_expression_1)."\">\n";
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
	
	echo "	<table border='0'>\n";
	echo "	<tr>\n";
	//echo "	<td align='left'>".$text['label-field']."</td>\n";
	echo "	<td nowrap='nowrap'>\n";
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
	echo "    <select class='formfld' name='condition_field_2' id='condition_field_2' onchange='changeToInput_condition_field_2(this);this.style.visibility = \"hidden\";'>\n";
	echo "    <option value=''></option>\n";
	if (strlen($condition_field_2) > 0) {
		echo "    <option value='".escape($condition_field_2)."' selected>".escape($condition_field_2)."</option>\n";
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
	//echo "	<td>&nbsp;&nbsp;&nbsp;".$text['label-expression']."</td>\n";
	echo "	<td>\n";
	echo "		&nbsp;<input class='formfld' type='text' name='condition_expression_2' maxlength='255' value=\"".escape($condition_expression_2)."\">\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "	<div id='desc_condition_expression_2'></div>\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-action_1']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'action_1', escape($action_1));
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-action_2']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'action_2', escape($action_2));
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap>\n";
	echo " 		".$text['label-context']."\n";
	echo "	</td>\n";
	echo "	<td colspan='4' class='vtable' align='left'>\n";
	echo "		<input class='formfld' style='width: 60%;' type='text' name='dialplan_context' maxlength='255' value=\"".escape($dialplan_context)."\">\n";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_order' class='formfld'>\n";
	//echo "		<option></option>\n";
	if (strlen($dialplan_order) > 0) {
		echo "		 <option selected='selected' value='".escape($dialplan_order)."'>".escape($dialplan_order)."</option>\n";
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
	echo "		".$text['label-enabled']."\n";
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
	echo " 		".$text['label-description']."\n";
	echo "	</td>\n";
	echo "	<td colspan='4' class='vtable' align='left'>\n";
	echo "		<input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"".escape($dialplan_description)."\">\n";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
