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
*/

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/functions/save_phrases_xml.php";

if (permission_exists('phrase_add') || permission_exists('phrase_edit')) {
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
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$phrase_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the form value and set to php variables
	if (count($_POST)>0) {
		$phrase_name = check_str($_POST["phrase_name"]);
		$phrase_language = check_str($_POST["phrase_language"]);
		$phrase_enabled = check_str($_POST["phrase_enabled"]);
		$phrase_description = check_str($_POST["phrase_description"]);

		//clean the name
		$phrase_name = str_replace(" ", "_", $phrase_name);
		$phrase_name = str_replace("'", "", $phrase_name);
	}


if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$phrase_uuid = check_str($_POST["phrase_uuid"]);
	}

	//check for all required data
		if (strlen($phrase_name) == 0) { $msg .= $text['message-required']." ".$text['label-name']."<br>\n"; }
		if (strlen($phrase_language) == 0) { $msg .= $text['message-required']." ".$text['label-language']."<br>\n"; }
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
		if ($action == "add" && permission_exists('phrase_add')) {
			//add the phrase to the database
				$phrase_uuid = uuid();
				$sql = "insert into v_phrases ";
				$sql .= "( ";
				$sql .= "domain_uuid, ";
				$sql .= "phrase_uuid, ";
				$sql .= "phrase_name, ";
				$sql .= "phrase_language, ";
				$sql .= "phrase_enabled, ";
				$sql .= "phrase_description ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "( ";
				$sql .= "'".$domain_uuid."', ";
				$sql .= "'".$phrase_uuid."', ";
				$sql .= "'".$phrase_name."', ";
				$sql .= "'".$phrase_language."', ";
				$sql .= "'".$phrase_enabled."', ";
				$sql .= "'".$phrase_description."' ";
				$sql .= ") ";
				//echo $sql."<br><br>";
				$db->exec(check_sql($sql));
				unset($sql);

				if ($_POST['phrase_detail_function'] != '') {
					$_POST['phrase_detail_tag'] = 'action'; // default, for now
					$_POST['phrase_detail_group'] = "0"; // one group, for now

					if ($_POST['phrase_detail_data'] != '') {
						$phrase_detail_uuid = uuid();
						$sql = "insert into v_phrase_details ";
						$sql .= "( ";
						$sql .= "phrase_detail_uuid, ";
						$sql .= "phrase_uuid, ";
						$sql .= "domain_uuid, ";
						$sql .= "phrase_detail_order, ";
						$sql .= "phrase_detail_tag, ";
						$sql .= "phrase_detail_pattern, ";
						$sql .= "phrase_detail_function, ";
						$sql .= "phrase_detail_data, ";
						$sql .= "phrase_detail_method, ";
						$sql .= "phrase_detail_type, ";
						$sql .= "phrase_detail_group ";
						$sql .= " ) ";
						$sql .= "values ";
						$sql .= "( ";
						$sql .= "'".$phrase_detail_uuid."', ";
						$sql .= "'".$phrase_uuid."', ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".check_str($_POST['phrase_detail_order'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_tag'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_pattern'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_function'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_data'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_method'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_type'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_group'])."' ";
						$sql .= ") ";
						//echo $sql."<br><br>";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				}

			//save the xml to the file system if the phrase directory is set
				save_phrases_xml();

			//delete the phrase from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd .= "memcache delete languages:".$phrase_language;
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

			//send a redirect
				$_SESSION["message"] = $text['message-add'];
				header("Location: phrase_edit.php?id=".$phrase_uuid);
				return;
		} //if ($action == "add")

		if ($action == "update" && permission_exists('phrase_edit')) {
			//update the database with the new data
				$sql = "update v_phrases set ";
				$sql .= "phrase_name = '".$phrase_name."', ";
				$sql .= "phrase_language = '".$phrase_language."', ";
				$sql .= "phrase_enabled = '".$phrase_enabled."', ";
				$sql .= "phrase_description = '".$phrase_description."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and phrase_uuid = '".$phrase_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);

				if ($_POST['phrase_detail_function'] != '') {
					$_POST['phrase_detail_tag'] = 'action'; // default, for now
					$_POST['phrase_detail_group'] = "0"; // one group, for now

					if ($_POST['phrase_detail_data'] != '') {
						$phrase_detail_uuid = uuid();
						$sql = "insert into v_phrase_details ";
						$sql .= "( ";
						$sql .= "phrase_detail_uuid, ";
						$sql .= "phrase_uuid, ";
						$sql .= "domain_uuid, ";
						$sql .= "phrase_detail_order, ";
						$sql .= "phrase_detail_tag, ";
						$sql .= "phrase_detail_pattern, ";
						$sql .= "phrase_detail_function, ";
						$sql .= "phrase_detail_data, ";
						$sql .= "phrase_detail_method, ";
						$sql .= "phrase_detail_type, ";
						$sql .= "phrase_detail_group ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "( ";
						$sql .= "'".$phrase_detail_uuid."', ";
						$sql .= "'".$phrase_uuid."', ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".check_str($_POST['phrase_detail_order'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_tag'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_pattern'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_function'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_data'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_method'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_type'])."', ";
						$sql .= "'".check_str($_POST['phrase_detail_group'])."' ";
						$sql .= ") ";
						//echo $sql."<br><br>";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				}

			//save the xml to the file system if the phrase directory is set
				save_phrases_xml();

			//delete the phrase from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd .= "memcache delete languages:".$phrase_language;
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

			//send a redirect
				$_SESSION["message"] = $text['message-update'];
				header("Location: phrase_edit.php?id=".$phrase_uuid);
				return;

		} //if ($action == "update")

	} //if ($_POST["persistformvar"] != "true")

} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$phrase_uuid = check_str($_GET["id"]);
		$sql = "select * from v_phrases ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and phrase_uuid = '".$phrase_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$phrase_name = $row["phrase_name"];
			$phrase_language = $row["phrase_language"];
			$phrase_enabled = $row["phrase_enabled"];
			$phrase_description = $row["phrase_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";
	if ($action == 'add') { $document['title'] = $text['title-add_phrase']; }
	if ($action == 'update') { $document['title'] = $text['title-edit_phrase']; }

//js to control action form input
	echo "<script type='text/javascript'>\n";

	echo "function load_action_options(selected_index) {\n";
	echo "	var obj_action = document.getElementById('phrase_detail_data');\n";
	echo "	if (selected_index == 0 || selected_index == 1) {\n";
	echo "		if (obj_action.type == 'text') {\n";
	echo "			action_to_select();\n";
	echo "			var obj_action = document.getElementById('phrase_detail_data');\n";
	echo "			obj_action.setAttribute('style', 'width: 300px; min-width: 300px; max-width: 300px;');\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			clear_action_options();\n";
	echo "		}\n";
	echo "	}\n";
	if (if_group("superadmin")) {
		echo "	else {\n";
		echo "		document.getElementById('phrase_detail_data_switch').style.display='none';\n";
		echo "		obj_action.setAttribute('style', 'width: 300px; min-width: 300px; max-width: 300px;');\n";
		echo "	}\n";
	}
	echo "	if (selected_index == 0) {\n"; //play
	echo "		obj_action.options[obj_action.options.length] = new Option('', '');\n"; //blank option
	//recordings
		$sql = "select * from v_recordings ";
		$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
		$sql .= "order by recording_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$recordings = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		$tmp_selected = false;
		if (count($recordings) > 0) {
			echo "var opt_group = document.createElement('optgroup');\n";
			echo "opt_group.label = \"".$text['label-recordings']."\";\n";
			foreach ($recordings as &$row) {
				if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
					echo "opt_group.appendChild(new Option(\"".$row["recording_name"]."\", \"lua(streamfile.lua ".$row["recording_filename"].")\"));\n";
				}
				else {
					echo "opt_group.appendChild(new Option(\"".$row["recording_name"]."\", \"".$_SESSION['switch']['recordings']['dir'].'/'.$row["recording_filename"]."\"));\n";
				}
			}
			echo "obj_action.appendChild(opt_group);\n";
		}
		unset($sql, $prep_statement, $recordings);
	//sounds
		$dir_path = $_SESSION['switch']['sounds']['dir'];
		recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		if (count($dir_array) > 0) {
			echo "var opt_group = document.createElement('optgroup');\n";
			echo "opt_group.label = \"".$text['label-sounds']."\";\n";
			foreach ($dir_array as $key => $value) {
				if (strlen($value) > 0) {
					if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
						echo "opt_group.appendChild(new Option(\"".$key."\", \"lua(streamfile.lua ".$key.")\"));\n";
					}
					else {
						echo "opt_group.appendChild(new Option(\"".$key."\", \"".$key."\"));\n";
					}
				}
			}
			echo "obj_action.appendChild(opt_group);\n";
		}
	echo "	}\n";
	echo "	else if (selected_index == 1) {\n"; //pause
	echo "		obj_action.options[obj_action.options.length] = new Option('', '');\n"; //blank option
	for ($s = 0.1; $s <= 5; $s = $s + 0.1) {
		echo "	obj_action.options[obj_action.options.length] = new Option('".$s."s', 'sleep(".($s * 1000).")');\n";
	}
	echo "	}\n";
	if (if_group("superadmin")) {
		echo "	else if (selected_index == 2) {\n"; //execute
		echo "		action_to_input();\n";
		echo "	}\n";
	}
	echo "}\n";

	echo "function clear_action_options() {\n";
	echo "	var len, groups, par;\n";
	echo "	sel = document.getElementById('phrase_detail_data');\n";
	echo "	groups = sel.getElementsByTagName('optgroup');\n";
	echo "	len = groups.length;\n";
	echo "	for (var i=len; i; i--) {\n";
	echo "		sel.removeChild( groups[i-1] );\n";
	echo "	}\n";
	echo "	len = sel.options.length;\n";
	echo "	for (var i=len; i; i--) {\n";
	echo "		par = sel.options[i-1].parentNode;\n";
	echo "		par.removeChild( sel.options[i-1] );\n";
	echo "	}\n";
	echo "}\n";

	if (if_group("superadmin")) {
		echo "function action_to_input() {\n";
		echo "	obj = document.getElementById('phrase_detail_data');\n";
		echo "	tb = document.createElement('INPUT');\n";
		echo "	tb.type = 'text';\n";
		echo "	tb.name = obj.name;\n";
		echo "	tb.id = obj.id;\n";
		echo "	tb.value = obj.options[obj.selectedIndex].value;\n";
		echo "	tb.className = 'formfld';\n";
		echo "	tb_width = (document.getElementById('phrase_detail_function').selectedIndex == 2) ? '300px' : '267px';\n";
		echo "	tb.setAttribute('style', 'width: '+tb_width+'; min-width: '+tb_width+'; max-width: '+tb_width+';');\n";
		echo "	obj.parentNode.insertBefore(tb, obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "	if (document.getElementById('phrase_detail_function').selectedIndex != 2) {\n";
		echo "		tb.setAttribute('style', 'width: 263px; min-width: 263px; max-width: 263px;');\n";
		echo "		document.getElementById('phrase_detail_data_switch').style.display='';\n";
		echo "	}\n";
		echo "	else {\n";
		echo "		tb.focus();\n";
		echo "	}\n";
		echo "}\n";

		echo "function action_to_select() {\n";
		echo "	obj = document.getElementById('phrase_detail_data');\n";
		echo "	sb = document.createElement('SELECT');\n";
		echo "	sb.name = obj.name;\n";
		echo "	sb.id = obj.id;\n";
		echo "	sb.className = 'formfld';\n";
		echo "	sb.setAttribute('style', 'width: 300px; min-width: 300px; max-width: 300px;');\n";
		echo "	sb.setAttribute('onchange', 'action_to_input();');\n";
		echo "	obj.parentNode.insertBefore(sb, obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "	document.getElementById('phrase_detail_data_switch').style.display='none';\n";
		echo "	clear_action_options();\n";
		echo "}\n";
	}
	echo "</script>\n";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "	<td align='left' width='30%' nowrap valign='top'>";
	if ($action == "add") { echo "<b>".$text['title-add_phrase']."</b>"; }
	if ($action == "update") { echo "<b>".$text['title-edit_phrase']."</b>"; }
	echo "	<br /><br />";
	echo "	</td>\n";
	echo "<td width='70%' align='right' valign='top'>";
	echo "	<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='phrases.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phrase_name' maxlength='255' value=\"$phrase_name\">\n";
	echo "	<br />\n";
	echo "	".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phrase_language' maxlength='255' value=\"$phrase_language\">\n";
	echo "	<br />\n";
	echo "	".$text['description-language']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>";
	echo "<td class='vncell' valign='top'>".$text['label-structure']."</td>";
	echo "<td class='vtable' align='left'>";
	echo "	<table width='59%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-function']."</td>\n";
	echo "			<td class='vtable'>".$text['label-action']."</td>\n";
	echo "			<td class='vtable' style='text-align: center;'>".$text['label-order']."</td>\n";
	echo "			<td></td>\n";
	echo "		</tr>\n";
	if (strlen($phrase_uuid) > 0) {
		$sql = "select * from v_phrase_details ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and phrase_uuid = '".$phrase_uuid."' ";
		$sql .= "order by phrase_detail_order asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			//clean up output for display
			if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
				if ($field['phrase_detail_function'] == 'execute' && substr($field['phrase_detail_data'], 0, 19) == 'lua(streamfile.lua ') {
					$phrase_detail_function = $text['label-play'];
					$phrase_detail_data = str_replace('lua(streamfile.lua ', '', $field['phrase_detail_data']);
					$phrase_detail_data = str_replace(')', '', $phrase_detail_data);
				}
			}
			if ($field['phrase_detail_function'] == 'execute' && substr($field['phrase_detail_data'], 0, 6) == 'sleep(') {
				$phrase_detail_function = $text['label-pause'];
				$phrase_detail_data = str_replace('sleep(', '', $field['phrase_detail_data']);
				$phrase_detail_data = str_replace(')', '', $phrase_detail_data);
				$phrase_detail_data = ($phrase_detail_data / 1000).'s'; // seconds
			}
			if ($field['phrase_detail_function'] == 'play-file') {
				$phrase_detail_function = $text['label-play'];
				$phrase_detail_data = str_replace($_SESSION['switch']['recordings']['dir'].'/', '', $field['phrase_detail_data']);
			}
			echo "<tr>\n";
			echo "	<td class='vtable'>".$phrase_detail_function."&nbsp;</td>\n";
			echo "	<td class='vtable'>".$phrase_detail_data."&nbsp;</td>\n";
			echo "	<td class='vtable' style='text-align: center;'>".$field['phrase_detail_order']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons' style='text-align: left;'>";
			echo 		"<a href='phrase_detail_delete.php?pdid=".$field['phrase_detail_uuid']."&pid=".$phrase_uuid."&a=delete&lang=".$phrase_language."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			echo "	</td>\n";
			echo "</tr>\n";
		}
	}
	unset($sql, $result);
	echo "<tr>\n";
	echo "	<td class='vtable' align='left' nowrap='nowrap'>\n";
	echo "		<select name='phrase_detail_function' id='phrase_detail_function' class='formfld' onchange=\"load_action_options(this.selectedIndex);\">\n";
	if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
		echo "		<option value='execute'>".$text['label-play']."</option>\n";
	}
	else {
		echo "		<option value='play-file'>".$text['label-play']."</option>\n";
	}
	echo "			<option value='execute'>".$text['label-pause']."</option>\n";
	if (if_group("superadmin")) {
		echo "		<option value='execute'>".$text['label-execute']."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left' nowrap='nowrap'>\n";
	echo "		<select name='phrase_detail_data' id='phrase_detail_data' class='formfld' style='width: 300px; min-width: 300px; max-width: 300px;' ".((if_group("superadmin")) ? "onchange='action_to_input();'" : null)."></select>";
	if (if_group("superadmin")) {
		echo "	<input id='phrase_detail_data_switch' type='button' class='btn' style='margin-left: 4px; display: none;' value='&#9665;' onclick=\"action_to_select(); load_action_options(document.getElementById('phrase_detail_function').selectedIndex);\">\n";
	}
	echo "		<script>load_action_options(0);</script>\n";
	echo "	</td>\n";
	echo "	<td class='vtable'>\n";
	echo "		<select name='phrase_detail_order' class='formfld'>\n";
	for ($i = 0; $i <= 999; $i++) {
		$i_padded = str_pad($i, 3, '0', STR_PAD_LEFT);
		echo "		<option value='".$i_padded."'>".$i_padded."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	<td>\n";
	echo "		<input type='submit' class='btn' alt=\"".$text['button-add']."\" value=\"".$text['button-add']."\">\n";
	echo "	</td>\n";

	echo "	</tr>\n";
	echo "</table>\n";

	echo "	".$text['description-structure']."\n";
	echo "	<br />\n";
	echo "</td>";
	echo "</tr>";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='phrase_enabled'>\n";
	echo "		<option value='true'>".$text['label-true']."</option>\n";
	echo "		<option value='false' ".(($phrase_enabled == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "	<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phrase_description' maxlength='255' value=\"".$phrase_description."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='phrase_uuid' value='".$phrase_uuid."'>\n";
	}
	echo "		<br />";
	echo "		<input type='submit' name='submit' class='btn' alt=\"".$text['button-save']."\" value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>