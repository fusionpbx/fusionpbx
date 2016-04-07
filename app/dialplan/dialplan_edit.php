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
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
require_once "resources/classes/orm.php";
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
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$dialplan_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}
	if (strlen($_REQUEST["app_uuid"]) > 0) {
		$app_uuid = $_REQUEST["app_uuid"];
	}

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

//get the list of applications
	if (count($_SESSION['switch']['applications']) == 0) {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$result = event_socket_request($fp, 'api show application');
			$_SESSION['switch']['applications'] = explode("\n\n", $result);
			$_SESSION['switch']['applications'] = explode("\n", $_SESSION['switch']['applications'][0]);
			unset($result);
			unset($fp);
		} else {
			$_SESSION['switch']['applications'] = Array();
		}
	}

//process and save the data
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

		//remove the invalid characters from the dialplan name
			$dialplan_name = $_POST["dialplan_name"];
			$dialplan_name = str_replace(" ", "_", $dialplan_name);
			$dialplan_name = str_replace("/", "", $dialplan_name);

		//build the array
			if (strlen($row["dialplan_uuid"]) > 0) {
				$array['dialplan_uuid'] = $_POST["dialplan_uuid"];
			}
			if (isset($_POST["domain_uuid"])) {
				$array['domain_uuid'] = $_POST['domain_uuid'];
			}
			else {
				$array['domain_uuid'] = $_SESSION['domain_uuid'];
			}
			$array['dialplan_name'] = $dialplan_name;
			$array['dialplan_number'] = $_POST["dialplan_number"];
			$array['dialplan_context'] = $_POST["dialplan_context"];
			$array['dialplan_continue'] = $_POST["dialplan_continue"];
			$array['dialplan_order'] = $_POST["dialplan_order"];
			$array['dialplan_enabled'] = $_POST["dialplan_enabled"];
			$array['dialplan_description'] = $_POST["dialplan_description"];
			$x = 0;
			foreach ($_POST["dialplan_details"] as $row) {
				if (strlen($row["dialplan_detail_tag"]) > 0) {
					if (strlen($row["dialplan_detail_uuid"]) > 0) {
						$array['dialplan_details'][$x]['dialplan_detail_uuid'] = $row["dialplan_detail_uuid"];
					}
					$array['dialplan_details'][$x]['domain_uuid'] = $array['domain_uuid'];
					$array['dialplan_details'][$x]['dialplan_detail_tag'] = $row["dialplan_detail_tag"];
					$array['dialplan_details'][$x]['dialplan_detail_type'] = $row["dialplan_detail_type"];
					$array['dialplan_details'][$x]['dialplan_detail_data'] = $row["dialplan_detail_data"];
					$array['dialplan_details'][$x]['dialplan_detail_break'] = $row["dialplan_detail_break"];
					$array['dialplan_details'][$x]['dialplan_detail_inline'] = $row["dialplan_detail_inline"];
					$array['dialplan_details'][$x]['dialplan_detail_group'] = ($row["dialplan_detail_group"] != '') ? $row["dialplan_detail_group"] : '0';
					$array['dialplan_details'][$x]['dialplan_detail_order'] = $row["dialplan_detail_order"];
				}
				$x++;
			}

		//add or update the database
			if ($_POST["persistformvar"] != "true") {
				$orm = new orm;
				$orm->name('dialplans');
				$orm->uuid($dialplan_uuid);
				$orm->save($array);
				//$message = $orm->message;
			}

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$dialplan_context);

		//synchronize the xml config
			save_dialplan_xml();

		//set the message
			if ($action == "add") {
				$_SESSION['message'] = $text['message-add'];
			}
			else if ($action == "update") {
				$_SESSION['message'] = $text['message-update'];
			}
			header("Location: ?id=".$dialplan_uuid.(($app_uuid != '') ? "&app_uuid=".$app_uuid : null));
			exit;

	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_dialplans ";
		$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			//$app_uuid = $row["app_uuid"];
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
	//sort the details array by group number
		ksort($details);

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-dialplan_edit'];

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
		}
	</script>
<?php

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<input type='hidden' name='app_uuid' value='".$app_uuid."'>\n";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='30%'>\n";
	echo"			<span class=\"title\">".$text['title-dialplan_edit']."</span><br />\n";
	echo "		</td>\n";
	echo "		<td width='70%' align='right'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='dialplans.php".((strlen($app_uuid) > 0) ? "?app_uuid=".$app_uuid : null)."';\" value='".$text['button-back']."'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"if (confirm('".$text['confirm-copy']."')){window.location='dialplan_copy.php?id=".$dialplan_uuid."';}\" value='".$text['button-copy']."'>\n";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
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

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' style='vertical-align: top;'>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
		echo "    ".$text['label-name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' width='70%' align='left'>\n";
		echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' placeholder='' value=\"".htmlspecialchars($dialplan_name)."\" required='required'>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='text' name='dialplan_number' maxlength='255' placeholder='' value=\"".htmlspecialchars($dialplan_number)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
		echo "    ".$text['label-context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' width='70%'>\n";
		echo "    <input class='formfld' type='text' name='dialplan_context' maxlength='255' placeholder='' value=\"$dialplan_context\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-continue']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='dialplan_continue'>\n";
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
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

	echo "</td>";
	echo "<td width='50%' style='vertical-align: top;'>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
		echo "    ".$text['label-order']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' width='70%'>\n";
		echo "	<select name='dialplan_order' class='formfld'>\n";
		$i=0;
		while($i<=999) {
			$selected = ($i == $dialplan_order) ? "selected" : null;
			if (strlen($i) == 1) {
				echo "		<option value='00$i' ".$selected.">00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "		<option value='0$i' ".$selected.">0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "		<option value='$i' ".$selected.">$i</option>\n";
			}
			$i++;
		}
		echo "	</select>\n";
		echo "	<br />\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('dialplan_domain')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-domain']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='domain_uuid'>\n";
			if (strlen($domain_uuid) == 0) {
				echo "    <option value='' selected='selected'>".$text['select-global']."</option>\n";
			}
			else {
				echo "    <option value=''>".$text['select-global']."</option>\n";
			}
			foreach ($_SESSION['domains'] as $row) {
				if ($row['domain_uuid'] == $domain_uuid) {
					echo "    <option value='".$row['domain_uuid']."' selected='selected'>".$row['domain_name']."</option>\n";
				}
				else {
					echo "    <option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
				}
			}
			echo "    </select>\n";
			echo "<br />\n";
			echo $text['description-domain_name']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='dialplan_enabled'>\n";
		if ($dialplan_enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['option-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['option-true']."</option>\n";
		}
		if ($dialplan_enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['option-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['option-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
		echo "    ".$text['label-description']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' width='70%'>\n";
		echo "    <textarea class='formfld' style='width: 250px; height: 68px;' name='dialplan_description'>".htmlspecialchars($dialplan_description)."</textarea>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br><br>";

	//dialplan details
	if ($action == "update") {
		?>
		<!--javascript to change select to input and back again-->
			<script language="javascript">

				function label_to_form(label_id, form_id) {
					if (document.getElementById(label_id) != null) {
						label = document.getElementById(label_id);
						label.parentNode.removeChild(label);
					}
					document.getElementById(form_id).style.display='';
				}

			</script>
		<?php

		//display the results
			if ($result_count > 0) {

				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' style='margin: -2px; border-spacing: 2px;'>\n";

				$x = 0;
				foreach($details as $group) {

					if ($x != 0) {
						echo "<tr><td colspan='7'><br><br></td></tr>";
					}

					echo "<tr>\n";
					echo "<td class='vncellcolreq'>".$text['label-tag']."</td>\n";
					echo "<td class='vncellcolreq'>".$text['label-type']."</td>\n";
					echo "<td class='vncellcol' width='70%'>".$text['label-data']."</td>\n";
					echo "<td class='vncellcol'>".$text['label-break']."</td>\n";
					echo "<td class='vncellcol' style='text-align: center;'>".$text['label-inline']."</td>\n";
					echo "<td class='vncellcolreq' style='text-align: center;'>".$text['label-group']."</td>\n";
					echo "<td class='vncellcolreq' style='text-align: center;'>".$text['label-order']."</td>\n";
					echo "<td>&nbsp;</td>\n";
					echo "</tr>\n";

					foreach($group as $index => $row) {

						//get the values from the database and set as variables
							$dialplan_detail_uuid = $row['dialplan_detail_uuid'];
							$dialplan_detail_tag = $row['dialplan_detail_tag'];
							$dialplan_detail_type = $row['dialplan_detail_type'];
							$dialplan_detail_data = $row['dialplan_detail_data'];
							$dialplan_detail_break = $row['dialplan_detail_break'];
							$dialplan_detail_inline = $row['dialplan_detail_inline'];
							$dialplan_detail_group = $row['dialplan_detail_group'];
							$dialplan_detail_order = $row['dialplan_detail_order'];

						//no border on last row
							$no_border = ($index == 999) ? "border: none;" : null;

						//begin the row
							echo "<tr>\n";
						//determine whether to hide the element
							if (strlen($dialplan_detail_tag) == 0) {
								$element['hidden'] = false;
								$element['visibility'] = "";
							}
							else {
								$element['hidden'] = true;
								$element['visibility'] = "display: none;";
							}
						//add the primary key uuid
							if (strlen($dialplan_detail_uuid) > 0) {
								echo "	<input name='dialplan_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$dialplan_detail_uuid."\">\n";
							}
						//tag
							$selected = "selected=\"selected\" ";
							echo "<td class='vtablerow' style='".$no_border."' onclick=\"label_to_form('label_dialplan_detail_tag_".$x."','dialplan_detail_tag_".$x."');\" nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_tag_".$x."\">".$dialplan_detail_tag."</label>\n";
							}
							echo "	<select id='dialplan_detail_tag_".$x."' name='dialplan_details[".$x."][dialplan_detail_tag]' class='formfld' style='width: 97px; ".$element['visibility']."'>\n";
							echo "	<option></option>\n";
							echo "	<option value='condition' ".($dialplan_detail_tag == "condition" ? $selected : null).">".$text['option-condition']."</option>\n";
							echo "	<option value='regex' ".($dialplan_detail_tag == "regex" ? $selected : null).">".$text['option-regex']."</option>\n";
							echo "	<option value='action' ".($dialplan_detail_tag == "action" ? $selected : null).">".$text['option-action']."</option>\n";
							echo "	<option value='anti-action' ".($dialplan_detail_tag == "anti-action" ? $selected : null).">".$text['option-anti-action']."</option>\n";
							echo "	</select>\n";
							echo "</td>\n";
						//type
							echo "<td class='vtablerow' style='".$no_border."' onclick=\"label_to_form('label_dialplan_detail_type_".$x."','dialplan_detail_type_".$x."');\" nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_type_".$x."\">".$dialplan_detail_type."</label>\n";
							}
							echo "	<select id='dialplan_detail_type_".$x."' name='dialplan_details[".$x."][dialplan_detail_type]' class='formfld' style='width: auto; ".$element['visibility']."' onchange='change_to_input(this);'>\n";
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
								foreach ($_SESSION['switch']['applications'] as $row) {
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
							//echo "	<input type='button' id='btn_select_to_input_dialplan_detail_type' class='btn' style='visibility:hidden;' name='' alt='".$text['button-back']."' onclick='change_to_input(document.getElementById(\"dialplan_detail_type\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
							echo "</td>\n";
						//data
							echo "<td class='vtablerow' onclick=\"label_to_form('label_dialplan_detail_data_".$x."','dialplan_detail_data_".$x."');\" style='".$no_border." width: 100%; max-width: 150px; overflow: hidden; _text-overflow: ellipsis; white-space: nowrap;' nowrap='nowrap'>\n";
							if ($element['hidden']) {
								$dialplan_detail_data_mod = $dialplan_detail_data;
								if ($dialplan_detail_type == 'bridge') {
									// parse out gateway uuid
									$bridge_statement = explode('/', $dialplan_detail_data);
									if ($bridge_statement[0] == 'sofia' && $bridge_statement[1] == 'gateway' && is_uuid($bridge_statement[2])) {
										// retrieve gateway name from db
										$sql = "select gateway from v_gateways where gateway_uuid = '".$bridge_statement[2]."' ";
										$prep_statement = $db->prepare(check_sql($sql));
										$prep_statement->execute();
										$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
										if (count($result) > 0) {
											$gateway_name = $result[0]['gateway'];
											$dialplan_detail_data_mod = str_replace($bridge_statement[2], $gateway_name, $dialplan_detail_data);
										}
										unset ($prep_statement, $sql, $bridge_statement);
									}
								}
								echo "	<label id=\"label_dialplan_detail_data_".$x."\">".htmlspecialchars($dialplan_detail_data_mod)."</label>\n";
							}
							echo "	<input id='dialplan_detail_data_".$x."' name='dialplan_details[".$x."][dialplan_detail_data]' class='formfld' type='text' style='width: calc(100% - 2px); min-width: calc(100% - 2px); max-width: calc(100% - 2px); ".$element['visibility']."' placeholder='' value=\"".htmlspecialchars($dialplan_detail_data)."\">\n";
							echo "</td>\n";
						//break
							echo "<td class='vtablerow' style='".$no_border."' onclick=\"label_to_form('label_dialplan_detail_break_".$x."','dialplan_detail_break_".$x."');\" nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_break_".$x."\">".$dialplan_detail_break."</label>\n";
							}
							echo "	<select id='dialplan_detail_break_".$x."' name='dialplan_details[".$x."][dialplan_detail_break]' class='formfld' style='width: auto; ".$element['visibility']."'>\n";
							echo "	<option></option>\n";
							echo "	<option value='on-true' ".($dialplan_detail_break == "on-true" ? $selected : null).">".$text['option-on_true']."</option>\n";
							echo "	<option value='on-false' ".($dialplan_detail_break == "on-false" ? $selected : null).">".$text['option-on_false']."</option>\n";
							echo "	<option value='always' ".($dialplan_detail_break == "always" ? $selected : null).">".$text['option-always']."</option>\n";
							echo "	<option value='never' ".($dialplan_detail_break == "never" ? $selected : null).">".$text['option-never']."</option>\n";
							echo "	</select>\n";
							echo "</td>\n";
						//inline
							echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_dialplan_detail_inline_".$x."','dialplan_detail_inline_".$x."');\" nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_inline_".$x."\">".$dialplan_detail_inline."</label>\n";
							}
							echo "	<select id='dialplan_detail_inline_".$x."' name='dialplan_details[".$x."][dialplan_detail_inline]' class='formfld' style='width: auto; ".$element['visibility']."'>\n";
							echo "	<option></option>\n";
							echo "	<option value='true' ".($dialplan_detail_inline == "true" ? $selected : null).">".$text['option-true']."</option>\n";
							echo "	<option value='false' ".($dialplan_detail_inline == "false" ? $selected : null).">".$text['option-false']."</option>\n";
							echo "	</select>\n";
							echo "</td>\n";
						//group
							echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_dialplan_detail_group_".$x."','dialplan_detail_group_".$x."');\" nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_group_".$x."\">".$dialplan_detail_group."</label>\n";
							}
							echo "	<input id='dialplan_detail_group_".$x."' name='dialplan_details[".$x."][dialplan_detail_group]' class='formfld' type='number' min='0' step='1' style='width: 30px; text-align: center; ".$element['visibility']."' placeholder='' value=\"".htmlspecialchars($dialplan_detail_group)."\" onclick='this.select();'>\n";
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
							echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_dialplan_detail_order_".$x."','dialplan_detail_order_".$x."');\" nowrap='nowrap'>\n";
							if ($element['hidden']) {
								echo "	<label id=\"label_dialplan_detail_order_".$x."\">".$dialplan_detail_order."</label>\n";
							}
							echo "	<input id='dialplan_detail_order_".$x."' name='dialplan_details[".$x."][dialplan_detail_order]' class='formfld' type='number' min='0' step='1' style='width: 32px; text-align: center; ".$element['visibility']."' placeholder='' value=\"".htmlspecialchars($dialplan_detail_order)."\" onclick='this.select();'>\n";
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
							echo "	<td class='list_control_icon'>\n";
							if ($element['hidden']) {
								//echo "		<a href='dialplan_detail_edit.php?id=".$dialplan_detail_uuid."&dialplan_uuid=".$dialplan_uuid."&app_uuid=".$app_uuid."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
								echo "		<a href='dialplan_detail_delete.php?id=".$dialplan_detail_uuid."&dialplan_uuid=".$dialplan_uuid.(($app_uuid != '') ? "&app_uuid=".$app_uuid : null)."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
							}
							echo "	</td>\n";
						//end the row
							echo "</tr>\n";
						//increment the value
							$x++;
					}
					$x++;
				} //end foreach
				unset($sql, $result, $row_count);

				echo "</table>";

			} //end if results

	} //end if update

	echo "<br>\n";
	echo "<div align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</div>\n";
	echo "<br><br>\n";
	echo "</form>";

	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php")){
		echo "<p>".$text['billing-warning']."</p>";
	}

//show the footer
	require_once "resources/footer.php";

?>