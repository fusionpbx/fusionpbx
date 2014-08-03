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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('dialplan_add')
	|| permission_exists('dialplan_edit')
	|| permission_exists('inbound_route_add')
	|| permission_exists('inbound_route_edit')
	|| permission_exists('outbound_route_add')
	|| permission_exists('outbound_route_edit')
	|| permission_exists('fifo_edit')
	|| permission_exists('fifo_add')
	|| permission_exists('time_condition_add')
	|| permission_exists('time_condition_edit')) {
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

//set the action as an add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$dialplan_detail_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}
	$dialplan_uuid = check_str($_REQUEST["dialplan_uuid"]);

//get the http values and set them as php variables
	$app_uuid = check_str($_REQUEST["app_uuid"]);
	if (count($_POST)>0) {
		$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
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
		if (strlen($dialplan_detail_tag) == 0) { $msg .= $text['message-required'].$text['label-tag']."<br>\n"; }
		if (strlen($dialplan_detail_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
		//if (strlen($dialplan_detail_type) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($dialplan_detail_data) == 0) { $msg .= $text['message-required'].$text['label-data']."<br>\n"; }
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
					$switch_cmd = "memcache delete dialplan:".$_SESSION["context"];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

				$_SESSION["message"] = $text['message-add'];
				header("Location: dialplan_edit.php?id=".$dialplan_uuid."&app_uuid=".$app_uuid);
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

				$_SESSION["message"] = $text['message-update'];
				header("Location: dialplan_edit.php?id=".$dialplan_uuid."&app_uuid=".$app_uuid);
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
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-dialplan_detail'];

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
	echo "<td align='left' width='30%' nowrap=\"nowrap\"><span class=\"title\">".$text['header-dialplan_detail']."</span></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='dialplan_edit.php?id=".$dialplan_uuid."&app_uuid=".$app_uuid."';\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td>\n";
	echo "		&nbsp;\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-tag'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_detail_tag' class='formfld' id='form_tag'>\n";
	echo "	<option></option>\n";
	echo "	<option value='condition' ".($dialplan_detail_tag == "condition" ? $selected:"").">".$text['option-condition']."</option>\n";
	echo "	<option value='regex' ".($dialplan_detail_tag == "regex" ? $selected:"").">".$text['option-regex']."</option>\n";
	echo "	<option value='action' ".($dialplan_detail_tag == "action" ? $selected:"").">".$text['option-condition']."</option>\n";
	echo "	<option value='anti-action' ".($dialplan_detail_tag == "anti-action" ? $selected:"").">".$text['option-condition']."</option>\n";
	echo "	<option value='param' ".($dialplan_detail_tag == "param" ? $selected:"").">".$text['option-condition']."</option>\n";
	echo "	<option value='condition' ".($dialplan_detail_tag == "condition" ? $selected:"").">".$text['option-condition']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order'].":\n";
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

function change_to_input(obj){
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
	tbb.setAttribute('style', 'margin-left: 4px;');
	tbb.type='button';
	tbb.value=$("<div />").html('&#9665;').text();
	tbb.objs=[obj,tb,tbb];
	tbb.onclick=function(){ replace_param(this.objs); }
	obj.parentNode.insertBefore(tb,obj);
	obj.parentNode.insertBefore(tbb,obj);
	obj.parentNode.removeChild(obj);
	replace_param(this.objs);
}

function replace_param(obj){
	obj[2].parentNode.insertBefore(obj[0],obj[2]);
	obj[0].parentNode.removeChild(obj[1]);
	obj[0].parentNode.removeChild(obj[2]);
	document.getElementById('btn_select_to_input_dialplan_detail_type').style.visibility = 'visible';
}
</script>
<?php
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='dialplan_detail_type' id='dialplan_detail_type' class='formfld' onchange='change_to_input(this);'>\n";
	if (strlen($dialplan_detail_type) > 0) {
		echo "<optgroup label='selected'>\n";
		echo "	<option value='".htmlspecialchars($dialplan_detail_type)."'>".htmlspecialchars($dialplan_detail_type)."</option>\n";
		echo "</optgroup>\n";
	}
	else {
		echo "	<option value=''></option>\n";
	}
	if (strlen($dialplan_detail_tag) == 0 || $dialplan_detail_tag == "condition" || $dialplan_detail_tag == "regex") {
		echo "	<optgroup label='".$text['optgroup-conditions_or_regular_expressions']."'>\n";
		echo "	<option value='context'>".$text['option-context']."</option>\n";
		echo "	<option value='username'>".$text['option-username']."</option>\n";
		echo "	<option value='rdnis'>".$text['option-rdnis']."</option>\n";
		echo "	<option value='destination_number'>".$text['option-destination_number']."</option>\n";
		echo "	<option value='dialplan'>".$text['option-dialplan']."</option>\n";
		echo "	<option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
		echo "	<option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
		echo "	<option value='ani'>".$text['option-ani']."</option>\n";
		echo "	<option value='ani2'>".$text['option-ani2']."</option>\n";
		echo "	<option value='uuid'>".$text['option-uuid']."</option>\n";
		echo "	<option value='source'>".$text['option-source']."</option>\n";
		echo "	<option value='chan_name'>".$text['option-chan_name']."</option>\n";
		echo "	<option value='network_addr'>".$text['option-network_addr']."</option>\n";
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
		echo "<optgroup label='".$text['optgroup-applications']."'>\n";
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
	echo "<input type='button' id='btn_select_to_input_dialplan_detail_type' class='btn' name='' alt='".$text['button-back']."' onclick='change_to_input(document.getElementById(\"dialplan_detail_type\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-data'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_detail_data' value=\"".htmlspecialchars($dialplan_detail_data)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-group'].":\n";
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
			echo "    ".$text['label-break'].":\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "              <select name='dialplan_detail_break' class='formfld'>\n";
			echo "              <option></option>\n";
			if ($dialplan_detail_break == "on-true") {
				echo "              <option selected='selected' value='on-true'>".$text['option-on_true']."</option>\n";
			}
			else {
				echo "              <option value='on-true'>".$text['option-on_true']."</option>\n";
			}
			if ($dialplan_detail_break == "on-false") {
				echo "              <option selected='selected' value='on-false'>".$text['option-on_false']."</option>\n";
			}
			else {
				echo "              <option value='on-false'>".$text['option-on_false']."</option>\n";
			}
			if ($dialplan_detail_break == "always") {
				echo "              <option selected='selected' value='always'>".$text['option-always']."</option>\n";
			}
			else {
				echo "              <option value='always'>".$text['option-always']."</option>\n";
			}
			if ($dialplan_detail_break == "never") {
				echo "              <option selected='selected' value='never'>".$text['option-never']."</option>\n";
			}
			else {
				echo "              <option value='never'>".$text['option-never']."</option>\n";
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
			echo "    ".$text['label-inline'].":\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "              <select name='dialplan_detail_inline' class='formfld'>\n";
			echo "              <option></option>\n";
			if ($dialplan_detail_inline == "true") {
				echo "              <option selected='selected' value='true'>".$text['option-true']."</option>\n";
			}
			else {
				echo "              <option value='true'>".$text['option-true']."</option>\n";
			}
			if ($dialplan_detail_inline == "false") {
				echo "              <option selected='selected' value='false'>".$text['option-false']."</option>\n";
			}
			else {
				echo "              <option value='false'>".$text['option-false']."</option>\n";
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
	echo "				<input type='hidden' name='app_uuid' value='$app_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_detail_uuid' value='$dialplan_detail_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</div>\n";
	echo "</form>";
	echo "</div>";

//include the footer
	require_once "resources/footer.php";
?>