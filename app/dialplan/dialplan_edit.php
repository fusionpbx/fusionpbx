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
require_once "resources/paging.php";
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

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$dialplan_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the app uuid
	$app_uuid = check_str($_REQUEST["app_uuid"]);

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_number = check_str($_POST["dialplan_number"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);
		$dialplan_continue = check_str($_POST["dialplan_continue"]);
		$dialplan_details = $_POST["dialplan_details"];
		if (strlen($dialplan_continue) == 0) { $dialplan_continue = "false"; }
		$dialplan_context = check_str($_POST["dialplan_context"]);
		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
	}

	//check for all required data
		if (strlen($dialplan_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		if (strlen($dialplan_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
		if (strlen($dialplan_continue) == 0) { $msg .= $text['message-required'].$text['label-continue']."<br>\n"; }
		if (strlen($dialplan_context) == 0) { $msg .= $text['message-required'].$text['label-context']."<br>\n"; }
		if (strlen($dialplan_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
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
		foreach ($_POST as $key => $value) {
			if ($key == "dialplan_name") {
				$dialplan_name = str_replace(" ", "_", $value);
				$dialplan_name = str_replace("/", "", $dialplan_name);
				$_POST["dialplan_name"] = $dialplan_name;
			}
		}
	//array cleanup
		$x = 0;
		foreach ($_POST["dialplan_details"] as $row) {
			//unset the empty row
				if (strlen($row["dialplan_detail_tag"]) == 0) {
					unset($_POST["dialplan_details"][$x]);
				}
			//unset dialplan_detail_uuid if the field has no value
				if (strlen($row["dialplan_detail_uuid"]) == 0) {
					unset($_POST["dialplan_details"][$x]["dialplan_detail_uuid"]);
				}
			//increment the row
				$x++;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			$orm = new orm;
			$orm->name('dialplans');
			$orm->uuid($dialplan_uuid);
			$orm->save($_POST);
			//$message = $orm->message;
		}

	//delete the cache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_cmd = "memcache delete dialplan:".$dialplan_context;
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//synchronize the xml config
		save_dialplan_xml();

	//set the message
		if ($action == "add") {
			$_SESSION['message'] = $text['message-add'];
		}
		if ($action == "update") {
			$_SESSION['message'] = $text['message-update'];
		}

} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$dialplan_uuid = $_GET["id"];
		$sql = "select * from v_dialplans ";
//		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$app_uuid = $row["app_uuid"];
			$dialplan_name = $row["dialplan_name"];
			$dialplan_number = $row["dialplan_number"];
			$dialplan_order = $row["dialplan_order"];
			$dialplan_continue = $row["dialplan_continue"];
			$dialplan_context = $row["dialplan_context"];
			$dialplan_enabled = $row["dialplan_enabled"];
			$dialplan_description = $row["dialplan_description"];
		}
		unset ($prep_statement);
	}

//get the dialplan details in an array
	$sql = "select * from v_dialplan_details ";
//	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
	$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
	$x = 0;
	$details = '';
	//conditions
		foreach($result as $row) {
			if ($row['dialplan_detail_tag'] == "condition") {
				$group = $row['dialplan_detail_group'];
				foreach ($row as $key => $val) {
					$details[$group][$x][$key] = $val;
				}
			}
			$x++;
		}
	//regex
		foreach($result as $row) {
			if ($row['dialplan_detail_tag'] == "regex") {
				$group = $row['dialplan_detail_group'];
				foreach ($row as $key => $val) {
					$details[$group][$x][$key] = $val;
				}
			}
			$x++;
		}
	//actions
		foreach($result as $row) {
			if ($row['dialplan_detail_tag'] == "action") {
				$group = $row['dialplan_detail_group'];
				foreach ($row as $key => $val) {
					$details[$group][$x][$key] = $val;
				}
			}
			$x++;
		}
	//anti-actions
		foreach($result as $row) {
			if ($row['dialplan_detail_tag'] == "anti-action") {
				$group = $row['dialplan_detail_group'];
				foreach ($row as $key => $val) {
					$details[$group][$x][$key] = $val;
				}
			}
			$x++;
		}
		unset($result);
	//blank row
		foreach($details as $group => $row) {
			//set the array key for the empty row
				$x = "999";
			//get the highest dialplan_detail_order
				foreach ($row as $key => $field) {
					$dialplan_detail_order = 0;
					if ($dialplan_detail_order < $field['dialplan_detail_order']) {
						$dialplan_detail_order = $field['dialplan_detail_order'];
					}
				}
			//increment the highest order by 5
				$dialplan_detail_order = $dialplan_detail_order + 10;
			//set the rest of the empty array
				//$details[$group][$x]['domain_uuid'] = '';
				//$details[$group][$x]['dialplan_uuid'] = '';
				$details[$group][$x]['dialplan_detail_tag'] = '';
				$details[$group][$x]['dialplan_detail_type'] = '';
				$details[$group][$x]['dialplan_detail_data'] = '';
				$details[$group][$x]['dialplan_detail_break'] = '';
				$details[$group][$x]['dialplan_detail_inline'] = '';
				$details[$group][$x]['dialplan_detail_group'] = $group;
				$details[$group][$x]['dialplan_detail_order'] = $dialplan_detail_order;
		}

//show the header
	require_once "resources/header.php";
	$page["title"] = $text['title-dialplan_edit'];

//javascript to change select to input and back again
	?><script language="javascript">
		var objs;

		function change_to_input(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			//tb.setAttribute('id', 'ivr_menu_option_param');
			tb.setAttribute('style', 'width:175px;');
			tb.value=obj.options[obj.selectedIndex].value;
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.type='button';
			tbb.value='<';
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
		}
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

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='30%'>\n";
	echo"			<span class=\"title\">".$text['title-dialplan_edit']."</span><br />\n";
	echo "		</td>\n";
	echo "		<td width='70%' align='right'>\n";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"if (confirm('".$text['confirm-copy']."')){window.location='dialplan_copy.php?id=".$row['dialplan_uuid']."';}\" value='".$text['button-copy']."'>\n";
	if (strlen($app_uuid) > 0) {
		echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='dialplans.php?app_uuid=$app_uuid'\" value='".$text['button-back']."'>\n";
	}
	else {
		echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='dialplans.php'\" value='".$text['button-back']."'>\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-dialplan-edit']."\n";
	echo "			\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br />\n";

	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "    ".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left' width='70%'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"".htmlspecialchars($dialplan_name)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_number' maxlength='255' value=\"".htmlspecialchars($dialplan_number)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-context'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_context' maxlength='255' value=\"$dialplan_context\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-continue'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_continue'>\n";
	echo "    <option value=''></option>\n";
	if ($dialplan_continue == "true") {
		echo "    <option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['option-true']."</option>\n";
	}
	if ($dialplan_continue == "false") {
		echo "    <option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['option-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['header-dialplan_detail'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//dialplan details
	if ($action == "update") {
		//start the table
			echo "<div align='left'>";
			echo "<table width='70%' border='0' cellpadding='0' cellspacing='2'>\n";
			echo "<tr class='border'>\n";
			echo "	<td align=\"center\">\n";

		//define the alternating row styles
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

		?>
		<!--javascript to change select to input and back again-->
			<script language="javascript">

				function label_to_form(label_id, form_id, form_width) {
					if (document.getElementById(label_id) != null) {
						label = document.getElementById(label_id);
						label.parentNode.removeChild(label);
					}
					document.getElementById(form_id).style.visibility='visible';
					document.getElementById(form_id).style.left='0px';
					document.getElementById(form_id).style.width=form_width+'px';
				}

			</script>
		<?php

		//display the results
			echo "<div align='left'>\n";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<th align='center' width='90px;'>".$text['label-tag']."</th>\n";
			echo "<th align='center' width='150px;'>".$text['label-type']."</th>\n";
			echo "<th align='center' width='70%'>".$text['label-data']."</th>\n";
			echo "<th align='center' width='90px'>".$text['label-break']."</th>\n";
			echo "<th align='center' width='90px'>".$text['label-inline']."</th>\n";
			echo "<th align='center' width='90px'>".$text['label-group']."</th>\n";
			echo "<th align='center'>".$text['label-order']."</th>\n";
			echo "<td align='right' width='42'>&nbsp;</td>\n";
			echo "<tr>\n";

			if ($result_count > 0) {
				$x = 0;
				foreach($details as $group) {
					if ($x > 0) {
						echo "</table>";
						echo "</div>";
						echo "<br><br>";

						echo "<div align='left'>\n";
						echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
						echo "<tr>\n";
						echo "<th align='center' width='90px;'>".$text['label-tag']."</th>\n";
						echo "<th align='center' width='150px;'>".$text['label-type']."</th>\n";
						echo "<th align='center' width='70%'>".$text['label-data']."</th>\n";
						echo "<th align='center' width='90px'>".$text['label-break']."</th>\n";
						echo "<th align='center' width='90px'>".$text['label-inline']."</th>\n";
						echo "<th align='center' width='90px'>".$text['label-group']."</th>\n";
						echo "<th align='center'>".$text['label-order']."</th>\n";
						echo "<td align='right' width='42'>&nbsp;</td>\n";
						echo "<tr>\n";
					}

					foreach($group as $row) {

						//get the values from the database and set as variables
							$dialplan_detail_uuid = $row['dialplan_detail_uuid'];
							$dialplan_detail_tag = $row['dialplan_detail_tag'];
							$dialplan_detail_type = $row['dialplan_detail_type'];
							$dialplan_detail_data = $row['dialplan_detail_data'];
							$dialplan_detail_break = $row['dialplan_detail_break'];
							$dialplan_detail_inline = $row['dialplan_detail_inline'];
							$dialplan_detail_group = $row['dialplan_detail_group'];
							$dialplan_detail_order = $row['dialplan_detail_order'];

						//view
							/*
							echo "<tr >\n";
							echo "	<td valign='top' class='vtable'>&nbsp;&nbsp;".$row['dialplan_detail_tag']."</td>\n";
							echo "	<td valign='top' class='vtable'>&nbsp;&nbsp;".$row['dialplan_detail_type']."</td>\n";
							echo "	<td valign='top' class='vtable'>&nbsp;&nbsp;".wordwrap($row['dialplan_detail_data'],180,"<br>",1)."</td>\n";
							echo "	<td valign='top' class='vtable'>&nbsp;&nbsp;".$row['dialplan_detail_break']."</td>\n";
							echo "	<td valign='top' class='vtable'>&nbsp;&nbsp;".$row['dialplan_detail_inline']."</td>\n";
							echo "	<td valign='top' class='vtable'>&nbsp;&nbsp;".$row['dialplan_detail_group']."</td>\n";
							echo "	<td valign='top' class='vtable'>&nbsp;&nbsp;".$row['dialplan_detail_order']."</td>\n";
							echo "	<td valign='top' align='right' nowrap='nowrap'>\n";
							echo "		<a href='dialplan_detail_edit.php?id=".$row['dialplan_detail_uuid']."&dialplan_uuid=".$dialplan_uuid."&app_uuid=".$app_uuid."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
							echo "		<a href='dialplan_detail_delete.php?id=".$row['dialplan_detail_uuid']."&dialplan_uuid=".$dialplan_uuid."&app_uuid=".$app_uuid."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
							echo "	</td>\n";
							echo "</tr>\n";
							*/

						//begin the row
							echo "<tr>\n";
						//determine whether to hide the element
							if (strlen($dialplan_detail_tag) == 0) {
								$element['hidden'] = false;
								$element['visibility'] = "visibility:visible;";
							}
							else {
								$element['hidden'] = true;
								$element['visibility'] = "visibility:hidden;";
							}
						//add the primary key uuid
							if (strlen($dialplan_detail_uuid) > 0) {
								echo "	<input name='dialplan_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$dialplan_detail_uuid."\">\n";
							}
						//tag
							$selected = "selected=\"selected\" ";
							if ($element['hidden']) { $element['width'] = '0'; } else { $element['width'] = '97'; }
							echo "<td class='vtable' onclick=\"label_to_form('label_dialplan_detail_tag_".$x."','dialplan_detail_tag_".$x."','97');\" style='width:".$element['width']."px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_tag_".$x."\">".$dialplan_detail_tag."</label>\n";
							}
							echo "	<select id='dialplan_detail_tag_".$x."' name='dialplan_details[".$x."][dialplan_detail_tag]' class='formfld' style='width:".$element['width']."px; ".$element['visibility']."'>\n";
							echo "	<option></option>\n";
							echo "	<option value='condition' ".($dialplan_detail_tag == "condition" ? $selected:"").">".$text['option-condition']."</option>\n";
							echo "	<option value='regex' ".($dialplan_detail_tag == "regex" ? $selected:"").">".$text['option-regex']."</option>\n";
							echo "	<option value='action' ".($dialplan_detail_tag == "action" ? $selected:"").">".$text['option-action']."</option>\n";
							echo "	<option value='anti-action' ".($dialplan_detail_tag == "anti-action" ? $selected:"").">".$text['option-anti-action']."</option>\n";
							echo "	<option value='param' ".($dialplan_detail_tag == "param" ? $selected:"").">".$text['option-param']."</option>\n";
							//echo "	<option value='condition' ".($dialplan_detail_tag == "condition" ? $selected:"").">".$text['option-condition']."</option>\n";
							echo "	</select>\n";
							echo "</td>\n";
						//type
							if ($element['hidden']) { $element['width'] = '0'; } else { $element['width'] = '185'; }
							echo "<td class='vtable' onclick=\"label_to_form('label_dialplan_detail_type_".$x."','dialplan_detail_type_".$x."','185');\" style='width:185px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_type_".$x."\">".$dialplan_detail_type."</label>\n";
							}
							echo "	<select id='dialplan_detail_type_".$x."' name='dialplan_details[".$x."][dialplan_detail_type]' class='formfld' style='width:".$element['width']."px; ".$element['visibility']."' onchange='change_to_input(this);'>\n";
							if (strlen($dialplan_detail_type) > 0) {
								echo "	<optgroup label='selected'>\n";
								echo "		<option value='".htmlspecialchars($dialplan_detail_type)."'>".htmlspecialchars($dialplan_detail_type)."</option>\n";
								echo "	</optgroup>\n";
							}
							else {
								echo "		<option value=''></option>\n";
							}
							//if (strlen($dialplan_detail_tag) == 0 || $dialplan_detail_tag == "condition" || $dialplan_detail_tag == "regex") {
								echo "		<optgroup label='".$text['optgroup-condition_or_regex']."'>\n";
								echo "		<option value='context'>".$text['option-context']."</option>\n";
								echo "		<option value='username'>".$text['option-username']."</option>\n";
								echo "		<option value='rdnis'>".$text['option-rdnis']."</option>\n";
								echo "		<option value='destination_number'>".$text['option-destination_number']."</option>\n";
								echo "		<option value='dialplan'>".$text['option-dialplan']."</option>\n";
								echo "		<option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
								echo "		<option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
								echo "		<option value='ani'>".$text['option-ani']."</option>\n";
								echo "		<option value='ani2'>".$text['option-ani2']."</option>\n";
								echo "		<option value='uuid'>".$text['option-uuid']."</option>\n";
								echo "		<option value='source'>".$text['option-source']."</option>\n";
								echo "		<option value='chan_name'>".$text['option-chan_name']."</option>\n";
								echo "		<option value='network_addr'>".$text['option-network_addr']."</option>\n";
								echo "		<option value='\${number_alias}'>\${number_alias}</option>\n";
								echo "		<option value='\${sip_from_uri}'>\${sip_from_uri}</option>\n";
								echo "		<option value='\${sip_from_user}'>\${sip_from_user}</option>\n";
								echo "		<option value='\${sip_from_host}'>\${sip_from_host}</option>\n";
								echo "		<option value='\${sip_contact_uri}'>\${sip_contact_uri}</option>\n";
								echo "		<option value='\${sip_contact_user}'>\${sip_contact_user}</option>\n";
								echo "		<option value='\${sip_contact_host}'>\${sip_contact_host}</option>\n";
								echo "		<option value='\${sip_to_uri}'>\${sip_to_uri}</option>\n";
								echo "		<option value='\${sip_to_user}'>\${sip_to_user}</option>\n";
								echo "		<option value='\${sip_to_host}'>\${sip_to_host}</option>\n";
								echo "	</optgroup>\n";
							//}
							//if (strlen($dialplan_detail_tag) == 0 || $dialplan_detail_tag == "action" || $dialplan_detail_tag == "anti-action") {
								echo "	<optgroup label='".$text['optgroup-applications']."'>\n";
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
								echo "	</optgroup>\n";
							//}
							echo "	</select>\n";
							//echo "	<input type='button' id='btn_select_to_input_dialplan_detail_type' class='btn' style='visibility:hidden;' name='' alt='".$text['button-back']."' onclick='change_to_input(document.getElementById(\"dialplan_detail_type\"));this.style.visibility = \"hidden\";' value='<'>\n";
							echo "</td>\n";
						//data
							if ($element['hidden']) { $element['width'] = '0'; } else { $element['width'] = '200'; }
							echo "<td class='vtable' onclick=\"label_to_form('label_dialplan_detail_data_".$x."','dialplan_detail_data_".$x."','200');\" style='width:200px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_data_".$x."\">".$dialplan_detail_data."</label>\n";
							}
							echo "	<input id='dialplan_detail_data_".$x."' name='dialplan_details[".$x."][dialplan_detail_data]' class='formfld' type='text' style='width:".$element['width']."px; ".$element['visibility']."' value=\"".htmlspecialchars($dialplan_detail_data)."\">\n";
							echo "</td>\n";
						//break
							if ($element['hidden']) { $element['width'] = '0'; } else { $element['width'] = '88'; }
							echo "<td class='vtable' onclick=\"label_to_form('label_dialplan_detail_break_".$x."','dialplan_detail_break_".$x."','88');\" style='width:88px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_break_".$x."\">".$dialplan_detail_break."</label>\n";
							}
							echo "	<select id='dialplan_detail_break_".$x."' name='dialplan_details[".$x."][dialplan_detail_break]' class='formfld' style='width:".$element['width']."px; ".$element['visibility']."'>\n";
							echo "	<option></option>\n";
							echo "	<option value='on-true' ".($dialplan_detail_break == "on-true" ? $selected:"").">".$text['option-on_true']."</option>\n";
							echo "	<option value='on-false' ".($dialplan_detail_break == "on-false" ? $selected:"").">".$text['option-on_false']."</option>\n";
							echo "	<option value='always' ".($dialplan_detail_break == "always" ? $selected:"").">".$text['option-always']."</option>\n";
							echo "	<option value='never' ".($dialplan_detail_break == "never" ? $selected:"").">".$text['option-never']."</option>\n";
							echo "	</select>\n";
							echo "</td>\n";
						//inline
							if ($element['hidden']) { $element['width'] = '0'; } else { $element['width'] = '65'; }
							echo "<td class='vtable' onclick=\"label_to_form('label_dialplan_detail_inline_".$x."','dialplan_detail_inline_".$x."','65');\" style='width:65px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_inline_".$x."\">".$dialplan_detail_inline."</label>\n";
							}
							echo "	<select id='dialplan_detail_inline_".$x."' name='dialplan_details[".$x."][dialplan_detail_inline]' class='formfld' style='width:".$element['width']."px; ".$element['visibility']."'>\n";
							echo "	<option></option>\n";
							echo "	<option value='true' ".($dialplan_detail_inline == "true" ? $selected:"").">".$text['option-true']."</option>\n";
							echo "	<option value='false' ".($dialplan_detail_inline == "false" ? $selected:"").">".$text['option-false']."</option>\n";
							echo "	</select>\n";
							echo "</td>\n";
						//group
							if ($element['hidden']) { $element['width'] = '0'; } else { $element['width'] = '30'; }
							echo "<td class='vtable' onclick=\"label_to_form('label_dialplan_detail_group_".$x."','dialplan_detail_group_".$x."','30');\" style='width:30px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_group_".$x."\">".$dialplan_detail_group."</label>\n";
							}
							echo "	<input id='dialplan_detail_group_".$x."' name='dialplan_details[".$x."][dialplan_detail_group]' class='formfld' type='text' style='width:".$element['width']."px; ".$element['visibility']."' value=\"".htmlspecialchars($dialplan_detail_group)."\">\n";
							/*
							echo "	<select id='dialplan_detail_group_".$x."' name='dialplan_details[".$x."][dialplan_detail_group]' class='formfld' style='".$element['width']." ".$element['visibility']."'>\n";
							echo "	<option value=''></option>\n";
							if (strlen($dialplan_detail_group)> 0) {
								echo "	<option $selected value='".htmlspecialchars($dialplan_detail_group)."'>".htmlspecialchars($dialplan_detail_group)."</option>\n";
							}
							$i=0;
							while($i<=999) {
								echo "	<option value='$i'>$i</option>\n";
								$i++;
							}
							echo "	</select>\n";
							*/
							echo "</td>\n";
						//order
							if ($element['hidden']) { $element['width'] = '0'; } else { $element['width'] = '32'; }
							echo "<td class='vtable' onclick=\"label_to_form('label_dialplan_detail_order_".$x."','dialplan_detail_order_".$x."','32');\" style='width:32px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_order_".$x."\">".$dialplan_detail_order."</label>\n";
							}
							echo "	<input id='dialplan_detail_order_".$x."' name='dialplan_details[".$x."][dialplan_detail_order]' class='formfld' type='text' style='width:".$element['width']."px; ".$element['visibility']."' value=\"".htmlspecialchars($dialplan_detail_order)."\">\n";
							/*
							echo "	<select id='dialplan_detail_order_".$x."' name='dialplan_details[".$x."][dialplan_detail_order]' class='formfld' style='".$element['width']." ".$element['visibility']."'>\n";
							if (strlen($dialplan_detail_order)> 0) {
								echo "	<option $selected value='".htmlspecialchars($dialplan_detail_order)."'>".htmlspecialchars($dialplan_detail_order)."</option>\n";
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
							*/
							echo "</td>\n";
						//tools
							echo "	<td style='width:55px;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								//echo "		<a href='dialplan_detail_edit.php?id=".$dialplan_detail_uuid."&dialplan_uuid=".$dialplan_uuid."&app_uuid=".$app_uuid."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
								echo "		<a href='dialplan_detail_delete.php?id=".$dialplan_detail_uuid."&dialplan_uuid=".$dialplan_uuid."&app_uuid=".$app_uuid."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
							}
							echo "	</td>\n";
						//end the row
							echo "</tr>\n";
						//increment the value
							$x++;
					}
					if ($c==0) { $c=1; } else { $c=0; }
					$x++;
				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

			echo "</table>";
			echo "</div>";

			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "</div>";

	} //end if update

	//echo "	<br />\n";
	//echo "	".$text['description-conditions_and_actions']."</td>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-order'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_order' class='formfld'>\n";
	if (strlen(htmlspecialchars($dialplan_order))> 0) {
		echo "		<option selected='yes' value='".htmlspecialchars($dialplan_order)."'>".htmlspecialchars($dialplan_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		if (strlen($i) == 1) {
			echo "		<option value='00$i'>00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "		<option value='0$i'>0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "		<option value='$i'>$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($dialplan_enabled == "true") {
		echo "    <option value='true' SELECTED >".$text['option-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['option-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "    <option value='false' SELECTED >".$text['option-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['option-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='dialplan_description' rows='4'>".htmlspecialchars($dialplan_description)."</textarea>\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "resources/footer.php";
?>