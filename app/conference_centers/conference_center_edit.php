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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('conference_center_add') || permission_exists('conference_center_edit')) {
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

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$conference_center_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
		$conference_center_name = check_str($_POST["conference_center_name"]);
		$conference_center_extension = check_str($_POST["conference_center_extension"]);
		$conference_center_greeting = check_str($_POST["conference_center_greeting"]);
		$conference_center_pin_length = check_str($_POST["conference_center_pin_length"]);
		$conference_center_description = check_str($_POST["conference_center_description"]);
		$conference_center_enabled = check_str($_POST["conference_center_enabled"]);

		//sanitize the conference name
		$conference_center_name = preg_replace("/[^A-Za-z0-9\- ]/", "", $conference_center_name);
		$conference_center_name = str_replace(" ", "-", $conference_center_name);
	}

/*
//delete the user from the v_conference_center_users
	if ($_GET["a"] == "delete" && permission_exists("conference_center_delete")) {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$conference_center_uuid = check_str($_REQUEST["id"]);
		//delete the group from the users
			$sql = "delete from v_conference_center_users ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and conference_center_uuid = '".$conference_center_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
		//redirect the browser
			require_once "resources/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_center_edit.php?id=$conference_center_uuid\">\n";
			echo "<div align='center'>Delete Complete</div>";
			require_once "resources/footer.php";
			return;
	}

//add the user to the v_conference_center_users
	if (strlen($_REQUEST["user_uuid"]) > 0 && strlen($_REQUEST["id"]) > 0 && $_GET["a"] != "delete") {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$conference_center_uuid = check_str($_REQUEST["id"]);
		//assign the user to the extension
			$sql_insert = "insert into v_conference_center_users ";
			$sql_insert .= "(";
			$sql_insert .= "conference_user_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "conference_center_uuid, ";
			$sql_insert .= "user_uuid ";
			$sql_insert .= ")";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= "'".uuid()."', ";
			$sql_insert .= "'".$_SESSION['domain_uuid']."', ";
			$sql_insert .= "'".$conference_center_uuid."', ";
			$sql_insert .= "'".$user_uuid."' ";
			$sql_insert .= ")";
			$db->exec($sql_insert);
		//redirect the browser
			require_once "resources/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_center_edit.php?id=$conference_center_uuid\">\n";
			echo "<div align='center'>Add Complete</div>";
			require_once "resources/footer.php";
			return;
	}
*/

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$conference_center_uuid = check_str($_POST["conference_center_uuid"]);
	}

	//check for all required data
		//if (strlen($dialplan_uuid) == 0) { $msg .= "Please provide: Dialplan UUID<br>\n"; }
		if (strlen($conference_center_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($conference_center_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		if (strlen($conference_center_pin_length) == 0) { $msg .= "Please provide: PIN Length<br>\n"; }
		//if (strlen($conference_center_order) == 0) { $msg .= "Please provide: Order<br>\n"; }
		//if (strlen($conference_center_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($conference_center_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
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
			if ($action == "add") {
				//prepare the uuids
					$conference_center_uuid = uuid();
					$dialplan_uuid = uuid();
				//add the conference
					$sql = "insert into v_conference_centers ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "conference_center_uuid, ";
					$sql .= "dialplan_uuid, ";
					$sql .= "conference_center_name, ";
					$sql .= "conference_center_extension, ";
					$sql .= "conference_center_pin_length, ";
					$sql .= "conference_center_greeting, ";
					$sql .= "conference_center_description, ";
					$sql .= "conference_center_enabled ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$conference_center_uuid', ";
					$sql .= "'$dialplan_uuid', ";
					$sql .= "'$conference_center_name', ";
					$sql .= "'$conference_center_extension', ";
					$sql .= "'$conference_center_pin_length', ";
					$sql .= "'$conference_center_greeting', ";
					$sql .= "'$conference_center_description', ";
					$sql .= "'$conference_center_enabled' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//create the dialplan entry
					$dialplan_name = $conference_center_name;
					$dialplan_order ='333';
					$dialplan_context = $_SESSION['context'];
					$dialplan_enabled = 'true';
					$dialplan_description = $conference_center_description;
					$app_uuid = 'b81412e8-7253-91f4-e48e-42fc2c9a38d9';
					dialplan_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

					//<condition destination_number="500" />
					$dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan_detail_type = 'destination_number';
					$dialplan_detail_data = '^'.$conference_center_extension.'$';
					$dialplan_detail_order = '010';
					$dialplan_detail_group = '2';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					//<action application="lua" />
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'lua';
					$dialplan_detail_data = 'app.lua conference_center';
					$dialplan_detail_order = '020';
					$dialplan_detail_group = '2';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

				//save the xml
					save_dialplan_xml();

				$_SESSION["message"] = $text['message-add'];
				header("Location: conference_centers.php");
				return;
			} //if ($action == "add")

			if ($action == "update") {
				//update the conference center extension
					$sql = "update v_conference_centers set ";
					$sql .= "conference_center_name = '$conference_center_name', ";
					$sql .= "conference_center_extension = '$conference_center_extension', ";
					$sql .= "conference_center_pin_length = '$conference_center_pin_length', ";
					$sql .= "conference_center_greeting = '$conference_center_greeting', ";
					$sql .= "conference_center_description = '$conference_center_description', ";
					$sql .= "conference_center_enabled = '$conference_center_enabled' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and conference_center_uuid = '$conference_center_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);

				//udpate the conference center dialplan
					$sql = "update v_dialplans set ";
					$sql .= "dialplan_name = '$conference_center_name', ";
					if (strlen($dialplan_order) > 0) {
						$sql .= "dialplan_order = '333', ";
					}
					$sql .= "dialplan_context = '".$_SESSION['context']."', ";
					$sql .= "dialplan_enabled = 'true', ";
					$sql .= "dialplan_description = '$conference_center_description' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);
					unset($sql);

				//update dialplan detail condition
					$sql = "update v_dialplan_details set ";
					$sql .= "dialplan_detail_data = '^".$conference_center_extension."$' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_detail_tag = 'condition' ";
					$sql .= "and dialplan_detail_type = 'destination_number' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);
					unset($sql);

				//update dialplan detail action
					$dialplan_detail_type = 'lua';
					$dialplan_detail_data = 'app.lua conference_center';
					$sql = "update v_dialplan_details set ";
					$sql .= "dialplan_detail_type = '".$dialplan_detail_type."', ";
					$sql .= "dialplan_detail_data = '".$dialplan_detail_data."' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_detail_tag = 'action' ";
					$sql .= "and dialplan_detail_type = 'lua' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);

				//delete the dialplan context from memcache
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}

				//save the xml
					save_dialplan_xml();

				$_SESSION["message"] = $text['message-update'];
				header("Location: conference_centers.php");
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//function to show the list of sound files
	function recur_sounds_dir($dir) {
		global $dir_array;
		global $dir_path;
		$dir_list = opendir($dir);
		while ($file = readdir ($dir_list)) {
			if ($file != '.' && $file != '..') {
				$newpath = $dir.'/'.$file;
				$level = explode('/',$newpath);
				if (substr($newpath, -4) == ".svn") {
					//ignore .svn dir and subdir
				}
				else {
					if (is_dir($newpath)) { //directories
						recur_sounds_dir($newpath);
					}
					else { //files
						if (strlen($newpath) > 0) {
							//make the path relative
								$relative_path = substr($newpath, strlen($dir_path), strlen($newpath));
							//remove the 8000-48000 khz from the path
								$relative_path = str_replace("/8000/", "/", $relative_path);
								$relative_path = str_replace("/16000/", "/", $relative_path);
								$relative_path = str_replace("/32000/", "/", $relative_path);
								$relative_path = str_replace("/48000/", "/", $relative_path);
							//remove the default_language, default_dialect, and default_voice (en/us/callie) from the path
								$file_array = explode( "/", $relative_path );
								$x = 1;
								$relative_path = '';
								foreach( $file_array as $tmp) {
									if ($x == 5) { $relative_path .= $tmp; }
									if ($x > 5) { $relative_path .= '/'.$tmp; }
									$x++;
								}
							//add the file if it does not exist in the array
								if (isset($dir_array[$relative_path])) {
									//already exists
								}
								else {
									//add the new path
										if (strlen($relative_path) > 0) { $dir_array[$relative_path] = '0'; }
								}
						}
					}
				}
			}
		}
		closedir($dir_list);
	}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$conference_center_uuid = $_GET["id"];
		$sql = "select * from v_conference_centers ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_center_uuid = '$conference_center_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$conference_center_name = $row["conference_center_name"];
			$conference_center_greeting = $row["conference_center_greeting"];
			$conference_center_extension = $row["conference_center_extension"];
			$conference_center_pin_length = $row["conference_center_pin_length"];
			$conference_center_order = $row["conference_center_order"];
			$conference_center_description = $row["conference_center_description"];
			$conference_center_enabled = $row["conference_center_enabled"];
			$conference_center_name = str_replace("-", " ", $conference_center_name);
		}
		unset ($prep_statement);
	}

//set defaults
	if (strlen($conference_center_enabled) == 0) { $conference_center_enabled = "true"; }
	if (strlen($conference_center_pin_length) == 0) { $conference_center_pin_length = 9; }

//show the header
	require_once "resources/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-conference-center']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='conference_centers.php'\" value='".$text['button-back']."'>\n";
	if (permission_exists('conference_active_advanced_view')) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-view']."' onclick=\"window.location='".PROJECT_PATH."/app/conferences_active/conferences_active.php'\" value='".$text['button-view']."'>\n";
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	".$text['description-conference-center']."\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_name' maxlength='255' value=\"$conference_center_name\">\n";
	echo "	<br />\n";
	echo "	".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_extension' maxlength='255' value=\"$conference_center_extension\">\n";
	echo "	<br />\n";
	echo " ".$text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-greeting'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
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
	if (if_group("superadmin")) {
		echo "		<select name='conference_center_greeting' class='formfld' onchange='changeToInput(this);'>\n";
	}
	else {
		echo "		<select name='conference_center_greeting' class='formfld'>\n";
	}
	echo "		<option></option>\n";
	//recordings
		if($dh = opendir($_SESSION['switch']['recordings']['dir']."/")) {
			$tmp_selected = false;
			$files = Array();
			echo "<optgroup label='recordings'>\n";
			while($file = readdir($dh)) {
				if($file != "." && $file != ".." && $file[0] != '.') {
					if(is_dir($_SESSION['switch']['recordings']['dir'] . "/" . $file)) {
						//this is a directory
					}
					else {
						if ($conference_center_greeting == $_SESSION['switch']['recordings']['dir']."/".$file && strlen($conference_center_greeting) > 0) {
							$tmp_selected = true;
							echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$file."' selected=\"selected\">".$file."</option>\n";
						}
						else {
							echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$file."'>".$file."</option>\n";
						}
					}
				}
			}
			closedir($dh);
			echo "</optgroup>\n";
		}
	//sounds
		$dir_path = $_SESSION['switch']['sounds']['dir'];
		recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		echo "<optgroup label='sounds'>\n";
		foreach ($dir_array as $key => $value) {
			if (strlen($value) > 0) {
				if (substr($conference_center_greeting, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
					$conference_center_greeting = substr($conference_center_greeting, 71);
				}
				if ($conference_center_greeting == $key) {
					$tmp_selected = true;
					echo "		<option value='$key' selected='selected'>$key</option>\n";
				} else {
					echo "		<option value='$key'>$key</option>\n";
				}
			}
		}
		echo "</optgroup>\n";
	//select
		if (strlen($conference_center_greeting) > 0) {
			if (if_group("superadmin")) {
				if (!$tmp_selected) {
					echo "<optgroup label='selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$conference_center_greeting)) {
						echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$conference_center_greeting."' selected='selected'>".$ivr_menu_greet_long."</option>\n";
					} elseif (substr($conference_center_greeting, -3) == "wav" || substr($conference_center_greeting, -3) == "mp3") {
						echo "		<option value='".$conference_center_greeting."' selected='selected'>".$conference_center_greeting."</option>\n";
					} else {
						echo "		<option value='".$conference_center_greeting."' selected='selected'>".$conference_center_greeting."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		}
	echo "		</select>\n";
	echo "<br />\n";
	echo $text['description-greeting']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-pin-length'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_pin_length' maxlength='255' value=\"$conference_center_pin_length\">\n";
	echo "	<br />\n";
	echo "	".$text['description-pin-length']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='conference_center_enabled'>\n";
	if ($conference_center_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($conference_center_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "	".$text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_description' maxlength='255' value=\"$conference_center_description\">\n";
	echo "	<br />\n";
	echo "	".$text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value=\"$dialplan_uuid\">\n";
		echo "				<input type='hidden' name='conference_center_uuid' value='$conference_center_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "resources/footer.php";
?>