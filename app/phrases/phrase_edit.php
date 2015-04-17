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
						$sql .= "'".$_POST['phrase_detail_order']."', ";
						$sql .= "'".$_POST['phrase_detail_tag']."', ";
						$sql .= "'".$_POST['phrase_detail_pattern']."', ";
						$sql .= "'".$_POST['phrase_detail_function']."', ";
						$sql .= "'".$_POST['phrase_detail_data']."', ";
						$sql .= "'".$_POST['phrase_detail_method']."', ";
						$sql .= "'".$_POST['phrase_detail_type']."', ";
						$sql .= "'".$_POST['phrase_detail_group']."' ";
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
						$sql .= "'".$_POST['phrase_detail_order']."', ";
						$sql .= "'".$_POST['phrase_detail_tag']."', ";
						$sql .= "'".$_POST['phrase_detail_pattern']."', ";
						$sql .= "'".$_POST['phrase_detail_function']."', ";
						$sql .= "'".$_POST['phrase_detail_data']."', ";
						$sql .= "'".$_POST['phrase_detail_method']."', ";
						$sql .= "'".$_POST['phrase_detail_type']."', ";
						$sql .= "'".$_POST['phrase_detail_group']."' ";
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
		$phrase_uuid = $_GET["id"];
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

	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		echo "	tb.setAttribute('style', 'width: 280px;');\n";
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "	tbb.objs=[obj,tb,tbb];\n";
		echo "	tbb.onclick=function(){ Replace(this.objs); }\n";
		echo "	obj.parentNode.insertBefore(tb,obj);\n";
		echo "	obj.parentNode.insertBefore(tbb,obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "}\n";
		echo "\n";
		echo "function Replace(obj){\n";
		echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "	obj[0].parentNode.removeChild(obj[1]);\n";
		echo "	obj[0].parentNode.removeChild(obj[2]);\n";
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}
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
			echo "<tr>\n";
			echo "	<td class='vtable'>".$field['phrase_detail_function']."&nbsp;</td>\n";
			echo "	<td class='vtable'>".$field['phrase_detail_data']."&nbsp;</td>\n";
			echo "	<td class='vtable' style='text-align: center;'>".$field['phrase_detail_order']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons' style='text-align: left;'>";
			echo 		"<a href='phrase_detail_delete.php?pdid=".$field['phrase_detail_uuid']."&pid=".$phrase_uuid."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			echo "	</td>\n";
			echo "</tr>\n";
		}
	}
	unset($sql, $result);
	echo "<tr>\n";
	echo "	<td class='vtable' align='left' nowrap='nowrap'>\n";
	echo "		<select name='phrase_detail_function' class='formfld' onchange=\"if (this.selectedIndex == 2) { changeToInput(getElementById('phrase_detail_data')); }\">\n";
	echo "			<option value='play-file'>".$text['label-play']."</option>\n";
	if (if_group("superadmin")) {
		echo "		<option value='execute'>".$text['label-execute']."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left' nowrap='nowrap'>\n";
	echo "		<select name='phrase_detail_data' id='phrase_detail_data' class='formfld' style='width: 300px' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
	echo "			<option value=''></option>\n";
	//recordings
		if($dh = opendir($_SESSION['switch']['recordings']['dir']."/")) {
			$tmp_selected = false;
			$files = Array();
			echo "	<optgroup label='Recordings'>\n";
			while($file = readdir($dh)) {
				if($file != "." && $file != ".." && $file[0] != '.') {
					if(is_dir($_SESSION['switch']['recordings']['dir'] . "/" . $file)) {
						//this is a directory
					}
					else {
						if ($ivr_menu_greet_short == $_SESSION['switch']['recordings']['dir']."/".$file && strlen($ivr_menu_greet_short) > 0) {
							$tmp_selected = true;
							echo "<option value='".$_SESSION['switch']['recordings']['dir']."/".$file."' selected='selected'>".$file."</option>\n";
						}
						else {
							echo "<option value='".$_SESSION['switch']['recordings']['dir']."/".$file."'>".$file."</option>\n";
						}
					}
				}
			}
			closedir($dh);
			echo "	</optgroup>\n";
		}
	//sounds
		$dir_path = $_SESSION['switch']['sounds']['dir'];
		recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		if (count($dir_array) > 0) {
			echo "		<optgroup label='Sounds'>\n";
			foreach ($dir_array as $key => $value) {
				if (strlen($value) > 0) {
					if (substr($ivr_menu_greet_long, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$ivr_menu_greet_long = substr($ivr_menu_greet_long, 71);
					}
					if ($ivr_menu_greet_long == $key) {
						$tmp_selected = true;
						echo "<option value='$key' selected='selected'>$key</option>\n";
					}
					else {
						echo "<option value='$key'>$key</option>\n";
					}
				}
			}
			echo "		</optgroup>\n";
		}
	echo "		</select>\n";
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