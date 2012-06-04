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
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('ivr_menu_add') || permission_exists('ivr_menu_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
/*
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
*/

//action add or update
if (isset($_REQUEST["id"])) {
	$action = "update";
	$ivr_menu_uuid = check_str($_REQUEST["id"]);
}
else {
	$action = "add";
}

//get http post values and set them to php variables
if (count($_POST)>0) {
	$ivr_menu_name = check_str($_POST["ivr_menu_name"]);
	$ivr_menu_extension = check_str($_POST["ivr_menu_extension"]);
	$ivr_menu_greet_long = check_str($_POST["ivr_menu_greet_long"]);
	$ivr_menu_greet_short = check_str($_POST["ivr_menu_greet_short"]);
	$ivr_menu_invalid_sound = check_str($_POST["ivr_menu_invalid_sound"]);
	$ivr_menu_exit_sound = check_str($_POST["ivr_menu_exit_sound"]);
	$ivr_menu_confirm_macro = check_str($_POST["ivr_menu_confirm_macro"]);
	$ivr_menu_confirm_key = check_str($_POST["ivr_menu_confirm_key"]);
	$ivr_menu_tts_engine = check_str($_POST["ivr_menu_tts_engine"]);
	$ivr_menu_tts_voice = check_str($_POST["ivr_menu_tts_voice"]);
	$ivr_menu_confirm_attempts = check_str($_POST["ivr_menu_confirm_attempts"]);
	$ivr_menu_timeout = check_str($_POST["ivr_menu_timeout"]);
	$ivr_menu_inter_digit_timeout = check_str($_POST["ivr_menu_inter_digit_timeout"]);
	$ivr_menu_max_failures = check_str($_POST["ivr_menu_max_failures"]);
	$ivr_menu_max_timeouts = check_str($_POST["ivr_menu_max_timeouts"]);
	$ivr_menu_digit_len = check_str($_POST["ivr_menu_digit_len"]);
	$ivr_menu_direct_dial = check_str($_POST["ivr_menu_direct_dial"]);
	$ivr_menu_enabled = check_str($_POST["ivr_menu_enabled"]);
	$ivr_menu_description = check_str($_POST["ivr_menu_description"]);

	$ivr_menu_exit_action = check_str($_POST["ivr_menu_exit_action"]);
	//$ivr_menu_exit_action = "transfer:1001 XML default";
	$timeout_action_array = explode(":", $ivr_menu_exit_action);
	$ivr_menu_exit_app = array_shift($timeout_action_array);
	$ivr_menu_exit_data = join(':', $timeout_action_array);
}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$ivr_menu_uuid = check_str($_POST["ivr_menu_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($ivr_menu_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($ivr_menu_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		if (strlen($ivr_menu_greet_long) == 0) { $msg .= "Please provide: Greet Long<br>\n"; }
		//if (strlen($ivr_menu_greet_short) == 0) { $msg .= "Please provide: Greet Short<br>\n"; }
		if (strlen($ivr_menu_invalid_sound) == 0) { $msg .= "Please provide: Invalid Sound<br>\n"; }
		//if (strlen($ivr_menu_exit_sound) == 0) { $msg .= "Please provide: Exit Sound<br>\n"; }
		//if (strlen($ivr_menu_confirm_macro) == 0) { $msg .= "Please provide: Confirm Macro<br>\n"; }
		//if (strlen($ivr_menu_confirm_key) == 0) { $msg .= "Please provide: Confirm Key<br>\n"; }
		//if (strlen($ivr_menu_tts_engine) == 0) { $msg .= "Please provide: TTS Engine<br>\n"; }
		//if (strlen($ivr_menu_tts_voice) == 0) { $msg .= "Please provide: TTS Voice<br>\n"; }
		if (strlen($ivr_menu_confirm_attempts) == 0) { $msg .= "Please provide: Confirm Attempts<br>\n"; }
		if (strlen($ivr_menu_timeout) == 0) { $msg .= "Please provide: Timeout<br>\n"; }
		//if (strlen($ivr_menu_exit_app) == 0) { $msg .= "Please provide: Exit Action<br>\n"; }
		//if (strlen($ivr_menu_exit_data) == 0) { $msg .= "Please provide: Timeout Data<br>\n"; }
		if (strlen($ivr_menu_inter_digit_timeout) == 0) { $msg .= "Please provide: Inter Digit Timeout<br>\n"; }
		if (strlen($ivr_menu_max_failures) == 0) { $msg .= "Please provide: Max Failures<br>\n"; }
		if (strlen($ivr_menu_max_timeouts) == 0) { $msg .= "Please provide: Max Timeouts<br>\n"; }
		if (strlen($ivr_menu_digit_len) == 0) { $msg .= "Please provide: Digit Length<br>\n"; }
		if (strlen($ivr_menu_direct_dial) == 0) { $msg .= "Please provide: Direct Dial<br>\n"; }
		if (strlen($ivr_menu_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($ivr_menu_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
			//prepare the object
				require_once "includes/classes/database.php";
				require_once "includes/classes/switch_ivr_menu.php";
				$ivr = new switch_ivr_menu;
				$ivr->domain_uuid = $_SESSION["domain_uuid"];
				$ivr->ivr_menu_name = $ivr_menu_name;
				$ivr->ivr_menu_extension = $ivr_menu_extension;
				$ivr->ivr_menu_greet_long = $ivr_menu_greet_long;
				$ivr->ivr_menu_greet_short = $ivr_menu_greet_short;
				$ivr->ivr_menu_invalid_sound = $ivr_menu_invalid_sound;
				$ivr->ivr_menu_exit_sound = $ivr_menu_exit_sound;
				$ivr->ivr_menu_confirm_macro = $ivr_menu_confirm_macro;
				$ivr->ivr_menu_confirm_key = $ivr_menu_confirm_key;
				$ivr->ivr_menu_tts_engine = $ivr_menu_tts_engine;
				$ivr->ivr_menu_tts_voice = $ivr_menu_tts_voice;
				$ivr->ivr_menu_confirm_attempts = $ivr_menu_confirm_attempts;
				$ivr->ivr_menu_timeout = $ivr_menu_timeout;
				$ivr->ivr_menu_exit_app = $ivr_menu_exit_app;
				$ivr->ivr_menu_exit_data = $ivr_menu_exit_data;
				$ivr->ivr_menu_inter_digit_timeout = $ivr_menu_inter_digit_timeout;
				$ivr->ivr_menu_max_failures = $ivr_menu_max_failures;
				$ivr->ivr_menu_max_timeouts = $ivr_menu_max_timeouts;
				$ivr->ivr_menu_max_timeouts = $ivr_menu_max_timeouts;
				$ivr->ivr_menu_digit_len = $ivr_menu_digit_len;
				$ivr->ivr_menu_digit_len = $ivr_menu_digit_len;
				$ivr->ivr_menu_direct_dial = $ivr_menu_direct_dial;
				$ivr->ivr_menu_direct_dial = $ivr_menu_direct_dial;
				$ivr->ivr_menu_enabled = $ivr_menu_enabled;
				$ivr->ivr_menu_description = $ivr_menu_description;

			//add the data
				if ($action == "add" && permission_exists('ivr_menu_add')) {
					$ivr->ivr_menu_uuid = uuid();
					$ivr->dialplan_uuid = uuid();
					$ivr->add();

					//synchronize the xml config
						save_ivr_menu_xml();

					//synchronize the xml config
						save_dialplan_xml();

					//redirect the user
						require_once "includes/header.php";
						echo "<meta http-equiv=\"refresh\" content=\"2;url=v_ivr_menu.php\">\n";
						echo "<div align='center'>\n";
						echo "Add Complete\n";
						echo "</div>\n";
						require_once "includes/footer.php";
						return;
				}
			//update the data
				if ($action == "update" && permission_exists('ivr_menu_edit')) {
					$ivr->ivr_menu_uuid = $ivr_menu_uuid;
					$ivr->update();

					//synchronize the xml config
						save_ivr_menu_xml();

					//synchronize the xml config
						save_dialplan_xml();

					//redirect the user
						require_once "includes/header.php";
						echo "<meta http-equiv=\"refresh\" content=\"2;url=v_ivr_menu.php\">\n";
						echo "<div align='center'>\n";
						echo "Update Complete\n";
						echo "</div>\n";
						require_once "includes/footer.php";
						return;
				}
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$ivr_menu_uuid = $_GET["id"];
		require_once "includes/classes/switch_ivr_menu.php";
		$ivr = new switch_ivr_menu;
		$ivr->domain_uuid = $_SESSION["domain_uuid"];
		$ivr->ivr_menu_uuid = $ivr_menu_uuid;
		$result = $ivr->find();
		$result_count = count($result);
		foreach ($result as &$row) {
			$ivr_menu_name = $row["ivr_menu_name"];
			$ivr_menu_extension = $row["ivr_menu_extension"];
			$ivr_menu_greet_long = $row["ivr_menu_greet_long"];
			$ivr_menu_greet_short = $row["ivr_menu_greet_short"];
			$ivr_menu_invalid_sound = $row["ivr_menu_invalid_sound"];
			$ivr_menu_exit_sound = $row["ivr_menu_exit_sound"];
			$ivr_menu_confirm_macro = $row["ivr_menu_confirm_macro"];
			$ivr_menu_confirm_key = $row["ivr_menu_confirm_key"];
			$ivr_menu_tts_engine = $row["ivr_menu_tts_engine"];
			$ivr_menu_tts_voice = $row["ivr_menu_tts_voice"];
			$ivr_menu_confirm_attempts = $row["ivr_menu_confirm_attempts"];
			$ivr_menu_timeout = $row["ivr_menu_timeout"];
			$ivr_menu_exit_app = $row["ivr_menu_exit_app"];
			$ivr_menu_exit_data = $row["ivr_menu_exit_data"];
			$ivr_menu_inter_digit_timeout = $row["ivr_menu_inter_digit_timeout"];
			$ivr_menu_max_failures = $row["ivr_menu_max_failures"];
			$ivr_menu_max_timeouts = $row["ivr_menu_max_timeouts"];
			$ivr_menu_digit_len = $row["ivr_menu_digit_len"];
			$ivr_menu_direct_dial = $row["ivr_menu_direct_dial"];
			$ivr_menu_enabled = $row["ivr_menu_enabled"];
			$ivr_menu_description = $row["ivr_menu_description"];

			if (strlen($ivr_menu_exit_app) > 0) {
				$ivr_menu_exit_action = $ivr_menu_exit_app.":".$ivr_menu_exit_data;
			}
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//set defaults
	if (strlen($ivr_menu_timeout) == 0) { $ivr_menu_timeout = '3000'; }
	if (strlen($ivr_menu_invalid_sound) == 0) { $ivr_menu_invalid_sound = 'ivr/ivr-that_was_an_invalid_entry.wav'; }
	if (strlen($ivr_menu_tts_engine) == 0) { $ivr_menu_tts_engine = 'flite'; }
	if (strlen($ivr_menu_tts_voice) == 0) { $ivr_menu_tts_voice = 'rms'; }
	if (strlen($ivr_menu_confirm_attempts) == 0) { $ivr_menu_confirm_attempts = '3'; }
	if (strlen($ivr_menu_inter_digit_timeout) == 0) { $ivr_menu_inter_digit_timeout = '2000'; }
	if (strlen($ivr_menu_max_failures) == 0) { $ivr_menu_max_failures = '3'; }
	if (strlen($ivr_menu_max_timeouts) == 0) { $ivr_menu_max_timeouts = '3'; }
	if (strlen($ivr_menu_digit_len) == 0) { $ivr_menu_digit_len = '5'; }
	if (strlen($ivr_menu_direct_dial) == 0) { $ivr_menu_direct_dial = 'false'; }
	if (strlen($ivr_menu_enabled) == 0) { $ivr_menu_enabled = 'true'; }

//content
	require_once "includes/header.php";

	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "\n";
	echo "function hide_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "</script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>IVR Menu Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>IVR Menu Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'>\n";
	echo "		<input type='button' class='btn' name='' alt='copy' onclick=\"if (confirm('Do you really want to copy this?')){window.location='v_ivr_menu_copy.php?id=".$ivr_menu_uuid."';}\" value='Copy'>\n";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_ivr_menu.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "The IVR Menu plays a recording or a pre-defined phrase that presents the caller with options to choose from. Each option has a corresponding destination. The destinations can be extensions, voicemail, IVR menus, hunt groups, FAX extensions, and more. <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ivr_menu_name' maxlength='255' value=\"$ivr_menu_name\">\n";
	echo "<br />\n";
	echo "Enter a name for the IVR menu.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ivr_menu_extension' maxlength='255' value='$ivr_menu_extension'>\n";
	echo "<br />\n";
	echo "Enter the extension number. \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Greet Long:\n";
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
		echo "	tbb.type='button';\n";
		echo "	tbb.value='<';\n";
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
		echo "		<select name='ivr_menu_greet_long' class='formfld' onchange='changeToInput(this);'>\n";
	}
	else {
		echo "		<select name='ivr_menu_greet_long' class='formfld'>\n";
	}
	echo "		<option></option>\n";
	//misc optgroup
		if (if_group("superadmin")) {
			echo "<optgroup label='misc'>\n";
			echo "		<option value='phrase:'>phrase:</option>\n";
			echo "		<option value='say:'>say:</option>\n";
			echo "		<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
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
						if ($ivr_menu_greet_long == $_SESSION['switch']['recordings']['dir']."/".$file && strlen($ivr_menu_greet_long) > 0) {
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
		//$dir_path = $_SESSION['switch']['sounds']['dir'];
		//recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		//echo "<optgroup label='sounds'>\n";
		//foreach ($dir_array as $key => $value) {
		//	if (strlen($value) > 0) {
		//		$tmp_dir = "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}";
		//		if ($ivr_menu_greet_long == $tmp_dir.'/'.$key) {
		//			$tmp_selected = true;
		//			echo "		<option value='$tmp_dir/$key' selected>$key</option>\n";
		//		}
		//		else {
		//			echo "		<option value='$tmp_dir/$key'>$key</option>\n";
		//		}
		//	}
		//}
		//echo "</optgroup>\n";
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected) {
				echo "<optgroup label='selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$ivr_menu_greet_long)) {
					echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$ivr_menu_greet_long."' selected>".$ivr_menu_greet_long."</option>\n";
				} elseif (substr($ivr_menu_greet_long, -3) == "wav" || substr($ivr_menu_greet_long, -3) == "mp3") {
					$tmp_dir = "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}";
					echo "		<option value='".$tmp_dir."/".$ivr_menu_greet_long."' selected>".$ivr_menu_greet_long."</option>\n";
				} else {
					echo "		<option value='".$ivr_menu_greet_long."' selected>".$ivr_menu_greet_long."</option>\n";
				}

				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "		</select>\n";

	echo "<br />\n";
	echo "The long greeting is played when entering the menu.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Greet Short:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "\n";
	echo "		<select name='ivr_menu_greet_short' class='formfld' onchange='changeToInput(this);'\">\n";
	echo "		<option></option>\n";
	//misc
		if (if_group("superadmin")) {
			echo "<optgroup label='misc'>\n";
			echo "		<option value='phrase:'>phrase:</option>\n";
			echo "		<option value='say:'>say:</option>\n";
			echo "		<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
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
						if ($ivr_menu_greet_short == $_SESSION['switch']['recordings']['dir']."/".$file && strlen($ivr_menu_greet_short) > 0) {
							$tmp_selected = true;
							echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$file."' selected='selected'>".$file."</option>\n";
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
		//$dir_path = $_SESSION['switch']['sounds']['dir'];
		//recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		//echo "<optgroup label='sounds'>\n";
		//foreach ($dir_array as $key => $value) {
		//	if (strlen($value) > 0) {
		//		$tmp_dir = "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}";
		//		if ($ivr_menu_greet_short == $tmp_dir.'/'.$key) {
		//			$tmp_selected = true;
		//			echo "		<option value='$tmp_dir/$key' selected>$key</option>\n";
		//		}
		//		else {
		//			echo "		<option value='$tmp_dir/$key'>$key</option>\n";
		//		}
		//	}
		//}
		//echo "</optgroup>\n";
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected) {
				echo "<optgroup label='selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$ivr_menu_greet_short)) {
					echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$ivr_menu_greet_short."' selected>".$ivr_menu_greet_short."</option>\n";
				} elseif (substr($ivr_menu_greet_short, -3) == "wav" || substr($ivr_menu_greet_short, -3) == "mp3") {
					$tmp_dir = "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}";
					echo "		<option value='".$tmp_dir."/".$ivr_menu_greet_short."' selected>".$ivr_menu_greet_short."</option>\n";
				} else {
					echo "		<option value='".$ivr_menu_greet_short."' selected>".$ivr_menu_greet_short."</option>\n";
				}
				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "		</select>\n";

	echo "<br />\n";
	echo "The short greeting is played when returning to the menu.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Timeout:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ivr_menu_timeout' maxlength='255' value='$ivr_menu_timeout'>\n";
	echo "<br />\n";
	echo "The number of milliseconds to wait after playing the greeting or the confirm macro.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Exit Action:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
	switch_select_destination("dialplan", "", "ivr_menu_exit_action", $ivr_menu_exit_action, "", "");
	echo "	<br />\n";
	echo "	Select the exit action to be performed if the IVR exits.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Direct Dial:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ivr_menu_direct_dial'>\n";
	echo "	<option value=''></option>\n";
	if ($ivr_menu_direct_dial == "true") { 
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($ivr_menu_direct_dial == "false") { 
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Define whether callers can dial directly to extensions and feature codes.\n";
	echo "</td>\n";
	echo "</tr>\n";

	//--- begin: show_advanced -----------------------
		echo "<tr>\n";
		echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

		echo "	<div id=\"show_advanced_box\">\n";
		echo "		<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
		echo "		<tr>\n";
		echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">Show Advanced</td>\n";
		echo "		<td width=\"70%\" class=\"vtable\">\n";
		echo "			<input type=\"button\" class='btn' onClick=\"show_advanced_config()\" value=\"Advanced\"></input></a>\n";
		echo "		</td>\n";
		echo "		</tr>\n";
		echo "		</table>\n";
		echo "	</div>\n";

		echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
		echo "	<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	Invalid Sound:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_invalid_sound' maxlength='255' value=\"$ivr_menu_invalid_sound\">\n";
		echo "<br />\n";
		echo "Played when and invalid option is chosen.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Exit Sound:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_exit_sound' maxlength='255' value=\"$ivr_menu_exit_sound\">\n";
		echo "<br />\n";
		echo "Played when leaving the menu.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Confirm Macro:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_macro' maxlength='255' value=\"$ivr_menu_confirm_macro\">\n";
		echo "<br />\n";
		echo "Enter the confirm macro.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Confirm Key:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_confirm_key' maxlength='255' value=\"$ivr_menu_confirm_key\">\n";
		echo "<br />\n";
		echo "Enter the confirm key.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	TTS Engine:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_tts_engine' maxlength='255' value=\"$ivr_menu_tts_engine\">\n";
		echo "<br />\n";
		echo "Text to speech engine.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	TTS Voice:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ivr_menu_tts_voice' maxlength='255' value=\"$ivr_menu_tts_voice\">\n";
		echo "<br />\n";
		echo "Text to speech voice.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	Confirm Attempts:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='ivr_menu_confirm_attempts' maxlength='255' value='$ivr_menu_confirm_attempts'>\n";
		echo "<br />\n";
		echo "The maximum number of confirm attempts allowed.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	Inter Digit Timeout:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='ivr_menu_inter_digit_timeout' maxlength='255' value='$ivr_menu_inter_digit_timeout'>\n";
		echo "<br />\n";
		echo "The number of milliseconds to wait between digits.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	Max Failures:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='ivr_menu_max_failures' maxlength='255' value='$ivr_menu_max_failures'>\n";
		echo "<br />\n";
		echo "Maximum number of retries before exit.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	Max Timeouts:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='ivr_menu_max_timeouts' maxlength='255' value='$ivr_menu_max_timeouts'>\n";
		echo "<br />\n";
		echo "Maximum number of timeouts before exit.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	Digit Length:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='ivr_menu_digit_len' maxlength='255' value='$ivr_menu_digit_len'>\n";
		echo "<br />\n";
		echo "Maximum number of digits allowed.\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "	</table>\n";
		echo "	</div>";

		echo "</td>\n";
		echo "</tr>\n";
	//--- end: show_advanced -----------------------

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ivr_menu_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($ivr_menu_enabled == "true") {
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($ivr_menu_enabled == "false") {
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Define whether the IVR Menu is enabled or disabled.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ivr_menu_description' maxlength='255' value=\"$ivr_menu_description\">\n";
	echo "<br />\n";
	echo "Enter a description.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='ivr_menu_uuid' value='$ivr_menu_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	if ($action == "update") {
		require "v_ivr_menu_options.php";
	}

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

require_once "includes/footer.php";
?>