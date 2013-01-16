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
if (permission_exists('dialplan_add') 
	|| permission_exists('dialplan_edit') 
	|| permission_exists('inbound_route_add') 
	|| permission_exists('inbound_route_edit') 
	|| permission_exists('outbound_route_add') 
	|| permission_exists('outbound_route_edit') 
	|| permission_exists('time_conditions_add') 
	|| permission_exists('time_conditions_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set the action as an add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$dialplan_detail_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
		$dialplan_uuid = check_str($_REQUEST["id2"]);
	}
	if (isset($_REQUEST["id2"])) {
		$dialplan_uuid = check_str($_REQUEST["id2"]);
	}

//get the http values and set them as php variables
	if (count($_POST)>0) {
		if (isset($_REQUEST["dialplan_uuid"])) {
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
		}
		$dialplan_detail_tag = check_str($_POST["dialplan_detail_tag"]);
		$dialplan_detail_order = check_str($_POST["dialplan_detail_order"]);
		$dialplan_detail_type = check_str($_POST["dialplan_detail_type"]);
		$dialplan_detail_data = check_str($_POST["dialplan_detail_data"]);
		$dialplan_detail_break = check_str($_POST["dialplan_detail_break"]);
		$dialplan_detail_inline = check_str($_POST["dialplan_detail_inline"]);
		$dialplan_detail_group = check_str($_POST["dialplan_detail_group"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$dialplan_detail_uuid = check_str($_POST["dialplan_detail_uuid"]);
	}

	//check for all required data
		if (strlen($dialplan_detail_tag) == 0) { $msg .= "Please provide: Tag<br>\n"; }
		if (strlen($dialplan_detail_order) == 0) { $msg .= "Please provide: Order<br>\n"; }
		//if (strlen($dialplan_detail_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($dialplan_detail_data) == 0) { $msg .= "Please provide: Data<br>\n"; }
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

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('dialplan_add')) {
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_order, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_break, ";
				$sql .= "dialplan_detail_inline, ";
				$sql .= "dialplan_detail_group, ";
				$sql .= "domain_uuid ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$dialplan_uuid', ";
				$sql .= "'$dialplan_detail_uuid', ";
				$sql .= "'$dialplan_detail_tag', ";
				$sql .= "'$dialplan_detail_order', ";
				$sql .= "'$dialplan_detail_type', ";
				$sql .= "'$dialplan_detail_data', ";
				$sql .= "'$dialplan_detail_break', ";
				$sql .= "'$dialplan_detail_inline', ";
				if (strlen($dialplan_detail_group) == 0) {
					$sql .= "null, ";
				}
				else {
					$sql .= "'$dialplan_detail_group', ";
				}
				$sql .= "'".$_SESSION['domain_uuid']."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				//synchronize the xml config
				save_dialplan_xml();

				//delete the dialplan context from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=dialplan_edit.php?id=".$dialplan_uuid."\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('dialplan_edit')) {
				$sql = "update v_dialplan_details set ";
				$sql .= "dialplan_uuid = '$dialplan_uuid', ";
				$sql .= "dialplan_detail_tag = '$dialplan_detail_tag', ";
				$sql .= "dialplan_detail_order = '$dialplan_detail_order', ";
				$sql .= "dialplan_detail_type = '$dialplan_detail_type', ";
				$sql .= "dialplan_detail_data = '$dialplan_detail_data', ";
				$sql .= "dialplan_detail_break = '$dialplan_detail_break', ";
				$sql .= "dialplan_detail_inline = '$dialplan_detail_inline', ";
				if (strlen($dialplan_detail_group) == 0) {
					$sql .= "dialplan_detail_group = null ";
				}
				else {
					$sql .= "dialplan_detail_group = '$dialplan_detail_group' ";
				}
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and dialplan_detail_uuid = '$dialplan_detail_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				//synchronize the xml config
				save_dialplan_xml();

				//delete the dialplan context from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=dialplan_edit.php?id=".$dialplan_uuid."\">\n";
				echo "<div align='center'>\n";
				echo "Update Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
		   } //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true") { 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$dialplan_detail_uuid = $_GET["id"];
		$sql = "select * from v_dialplan_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and dialplan_detail_uuid = '$dialplan_detail_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$dialplan_detail_tag = $row["dialplan_detail_tag"];
			$dialplan_detail_order = $row["dialplan_detail_order"];
			$dialplan_detail_type = $row["dialplan_detail_type"];
			$dialplan_detail_data = $row["dialplan_detail_data"];
			$dialplan_detail_break = $row["dialplan_detail_break"];
			$dialplan_detail_inline = $row["dialplan_detail_inline"];
			$dialplan_detail_group = $row["dialplan_detail_group"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap><b>Dialplan Detail</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='dialplan_edit.php?id=".$dialplan_uuid."'\" value='Back'></td>\n";
	echo "</tr>\n";

	?>
	<script type="text/javascript">
	function public_include_details_tag_onchange() {
		var dialplan_detail_tag = document.getElementById("form_tag").value;
		if (dialplan_detail_tag == "condition") {
		  document.getElementById("label_field_type").innerHTML = "Field";
		  document.getElementById("label_field_data").innerHTML = "Expression";
		}
		else if (dialplan_detail_tag == "regex") {
		  document.getElementById("label_field_type").innerHTML = "Field";
		  document.getElementById("label_field_data").innerHTML = "Expression";
		}
		else if (dialplan_detail_tag == "action") {
		  document.getElementById("label_field_type").innerHTML = "Application";
		  document.getElementById("label_field_data").innerHTML = "Data";
		}
		else if (dialplan_detail_tag == "anti-action") {
		  document.getElementById("label_field_type").innerHTML = "Application";
		  document.getElementById("label_field_data").innerHTML = "Data";
		}
		else if (dialplan_detail_tag == "param") {
		  document.getElementById("label_field_type").innerHTML = "Name";
		  document.getElementById("label_field_data").innerHTML = "Value";
		}
		if (dialplan_detail_tag == "") {
		  document.getElementById("label_field_type").innerHTML = "Type";
		  document.getElementById("label_field_data").innerHTML = "Data";
		}
	}
	</script>
	<?php

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Tag:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "                <select name='dialplan_detail_tag' class='formfld' id='form_tag' onchange='public_include_details_tag_onchange();'>\n";
	echo "                <option></option>\n";
	switch ($dialplan_detail_tag) {
	case "condition":
		echo "                <option selected='yes'>condition</option>\n";
		echo "                <option>action</option>\n";
		echo "                <option>anti-action</option>\n";
		echo "                <option>regex</option>\n";
		//echo "                <option>param</option>\n";
		break;
	case "regex":
		echo "                <option>condition</option>\n";
		echo "                <option>action</option>\n";
		echo "                <option>anti-action</option>\n";
		echo "                <option selected='yes'>regex</option>\n";
		//echo "                <option>param</option>\n";
		break;
	case "action":
		echo "                <option>condition</option>\n";
		echo "                <option selected='yes'>action</option>\n";
		echo "                <option>anti-action</option>\n";
		echo "                <option>regex</option>\n";
		//echo "                <option>param</option>\n";
		break;
	case "anti-action":
		echo "                <option>condition</option>\n";
		echo "                <option>action</option>\n";
		echo "                <option selected='yes'>anti-action</option>\n";
		echo "                <option>regex</option>\n";
		//echo "                <option>param</option>\n";
		break;
	case "param":
		echo "                <option>condition</option>\n";
		echo "                <option>action</option>\n";
		echo "                <option>anti-action</option>\n";
		echo "                <option>regex</option>\n";
		//echo "                <option selected='yes'>param</option>\n";
		break;
	default:
		echo "                <option>condition</option>\n";
		echo "                <option>action</option>\n";
		echo "                <option>anti-action</option>\n";
		echo "                <option>regex</option>\n";
		//echo "                <option>param</option>\n";
	}
	echo "                </select>\n";

	//condition
		//field expression
	//action
		//application
		//data
	//antiaction
		//application
		//data
	//param
		//name
		//value
	//echo "    <input class='formfld' type='text' name='dialplan_detail_tag' maxlength='255' value=\"$dialplan_detail_tag\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Order:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='dialplan_detail_order' class='formfld'>\n";
	if (strlen($dialplan_detail_order)> 0) {
		echo "              <option selected='selected' value='".htmlspecialchars($dialplan_detail_order)."'>".htmlspecialchars($dialplan_detail_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		if (strlen($i) == 1) {
			echo "              <option value='00$i'>00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "              <option value='0$i'>0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "              <option value='$i'>$i</option>\n";
		}
		$i++;
	}
	echo "              </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
?>
<script language="javascript">
var Objs;

function changeToInput_dialplan_detail_type(obj){
	tb=document.createElement('INPUT');
	tb.type='text';
	tb.name=obj.name;
	tb.className='formfld';
	tb.setAttribute('id', 'ivr_menu_option_param');
	tb.setAttribute('style', '');
	tb.value=obj.options[obj.selectedIndex].value;
	document.getElementById('btn_select_to_input_dialplan_detail_type').style.visibility = 'hidden';
	tbb=document.createElement('INPUT');
	tbb.setAttribute('class', 'btn');
	tbb.type='button';
	tbb.value='<';
	tbb.objs=[obj,tb,tbb];
	tbb.onclick=function(){ Replaceivr_menu_option_param(this.objs); }
	obj.parentNode.insertBefore(tb,obj);
	obj.parentNode.insertBefore(tbb,obj);
	obj.parentNode.removeChild(obj);
	Replaceivr_menu_option_param(this.objs);
}

function Replaceivr_menu_option_param(obj){
	obj[2].parentNode.insertBefore(obj[0],obj[2]);
	obj[0].parentNode.removeChild(obj[1]);
	obj[0].parentNode.removeChild(obj[2]);
	document.getElementById('btn_select_to_input_dialplan_detail_type').style.visibility = 'visible';
}
</script>
<?php
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='dialplan_detail_type' id='dialplan_detail_type' class='formfld' onchange='changeToInput_dialplan_detail_type(this);'>\n";
	if (strlen($dialplan_detail_type) > 0) {
		echo "<optgroup label='selected'>\n";
		echo "	<option value='".htmlspecialchars($dialplan_detail_type)."'>".htmlspecialchars($dialplan_detail_type)."</option>\n";
		echo "</optgroup>\n";
	}
	else {
		echo "	<option value=''></option>\n";
	}
	if (strlen($dialplan_detail_tag) == 0 || $dialplan_detail_tag == "condition" || $dialplan_detail_tag == "regex") {
		echo "	<optgroup label='conditions or regex'>\n";
		echo "	<option value='context'>context</option>\n";
		echo "	<option value='username'>username</option>\n";
		echo "	<option value='rdnis'>rdnis</option>\n";
		echo "	<option value='destination_number'>destination_number</option>\n";
		echo "	<option value='dialplan'>dialplan</option>\n";
		echo "	<option value='caller_id_name'>caller_id_name</option>\n";
		echo "	<option value='caller_id_number'>caller_id_number</option>\n";
		echo "	<option value='ani'>ani</option>\n";
		echo "	<option value='ani2'>ani2</option>\n";
		echo "	<option value='uuid'>uuid</option>\n";
		echo "	<option value='source'>source</option>\n";
		echo "	<option value='chan_name'>chan_name</option>\n";
		echo "	<option value='network_addr'>network_addr</option>\n";
		echo "	<option value='\${number_alias}'>\${number_alias}</option>\n";
		echo "	<option value='\${sip_from_uri}'>\${sip_from_uri}</option>\n";
		echo "	<option value='\${sip_from_user}'>\${sip_from_user}</option>\n";
		echo "	<option value='\${sip_from_host}'>\${sip_from_host}</option>\n";
		echo "	<option value='\${sip_contact_uri}'>\${sip_contact_uri}</option>\n";
		echo "	<option value='\${sip_contact_user}'>\${sip_contact_user}</option>\n";
		echo "	<option value='\${sip_contact_host}'>\${sip_contact_host}</option>\n";
		echo "	<option value='\${sip_to_uri}'>\${sip_to_uri}</option>\n";
		echo "	<option value='\${sip_to_user}'>\${sip_to_user}</option>\n";
		echo "	<option value='\${sip_to_host}'>\${sip_to_host}</option>\n";
		echo "</optgroup>\n";
	}
	if (strlen($dialplan_detail_tag) == 0 || $dialplan_detail_tag == "action" || $dialplan_detail_tag == "anti-action") {
		echo "<optgroup label='applications'>\n";
		//get the list of applications
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		$result = event_socket_request($fp, 'api show application');
		$tmp = explode("\n\n", $result);
		$tmp = explode("\n", $tmp[0]);
		foreach ($tmp as $row) {
			if (strlen($row) > 0) {
				$application = explode(",", $row);
				if ($application[0] != "name" && stristr($application[0], "[") != true) {
					echo "	<option value='".$application[0]."'>".$application[0]."</option>\n";
				}
			}
		}
		echo "</optgroup>\n";
	}
	echo "<input type='button' id='btn_select_to_input_dialplan_detail_type' class='btn' name='' alt='back' onclick='changeToInput_dialplan_detail_type(document.getElementById(\"dialplan_detail_type\"));this.style.visibility = \"hidden\";' value='<'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Data:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_detail_data' value=\"".htmlspecialchars($dialplan_detail_data)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Group:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='dialplan_detail_group' class='formfld'>\n";
	echo "              <option value=''></option>\n";
	if (strlen($dialplan_detail_group)> 0) {
		echo "              <option selected='selected' value='".htmlspecialchars($dialplan_detail_group)."'>".htmlspecialchars($dialplan_detail_group)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		echo "              <option value='$i'>$i</option>\n";
		$i++;
	}
	echo "              </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		if ($dialplan_detail_tag == "condition") {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "    Break:\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "              <select name='dialplan_detail_break' class='formfld'>\n";
			echo "              <option></option>\n";
			if ($dialplan_detail_break == "on-true") {
				echo "              <option selected='selected' value='on-true'>on-true</option>\n";
			}
			else {
				echo "              <option value='on-true'>on-true</option>\n";
			}
			if ($dialplan_detail_break == "on-false") {
				echo "              <option selected='selected' value='on-false'>on-false</option>\n";
			}
			else {
				echo "              <option value='on-false'>on-false</option>\n";
			}
			if ($dialplan_detail_break == "always") {
				echo "              <option selected='selected' value='always'>always</option>\n";
			}
			else {
				echo "              <option value='always'>always</option>\n";
			}
			if ($dialplan_detail_break == "never") {
				echo "              <option selected='selected' value='never'>never</option>\n";
			}
			else {
				echo "              <option value='never'>never</option>\n";
			}
			echo "              </select>\n";
			echo "<br />\n";
			echo "\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		if ($dialplan_detail_tag == "action") {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "    Inline:\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "              <select name='dialplan_detail_inline' class='formfld'>\n";
			echo "              <option></option>\n";
			if ($dialplan_detail_inline == "true") {
				echo "              <option selected='selected' value='true'>true</option>\n";
			}
			else {
				echo "              <option value='true'>true</option>\n";
			}
			if ($dialplan_detail_inline == "false") {
				echo "              <option selected='selected' value='false'>false</option>\n";
			}
			else {
				echo "              <option value='false'>false</option>\n";
			}
			echo "              </select>\n";
			echo "<br />\n";
			echo "\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_detail_uuid' value='$dialplan_detail_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</div>\n";
	echo "</form>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>