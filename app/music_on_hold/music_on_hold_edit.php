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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('music_on_hold_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get current moh record, build array
	$sql = "select * from v_music_on_hold ";
	$sql .= "where music_on_hold_uuid = '".$_GET['id']."' ";
	if (!permission_exists('music_on_hold_global_edit')) {
		$sql .= "and domain_uuid = '".$domain_uuid."' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$row = $prep_statement->fetch(PDO::FETCH_NAMED);
	foreach ($row as $index => $value) {
		$moh[str_replace('music_on_hold_','',$index)] = $value;
		$moh['name_only'] = (substr_count($moh['name'], '/') > 0) ? substr($moh['name'], 0, strpos($moh['name'],'/')) : $moh['name'];
	}
	unset($sql, $prep_statement, $row);
	//echo "<pre>".print_r($moh, true)."</pre>"; exit;

if (is_array($_POST) && sizeof($_POST) > 0) {
	//retrieve posted values
		$moh = $_POST;

	//check required fields
		if (permission_exists('music_on_hold_name') && $moh['name'] == '') { $missing_fields[] = $text['label-name']; }
		if (permission_exists('music_on_hold_path') && $moh['path'] == '') { $missing_fields[] = $text['label-path']; }
		if (is_array($missing_fields) && sizeof($missing_fields > 0)) {
			//set message
				$_SESSION["message_mood"] = 'negative';
				$_SESSION["message"] = $text['message-missing_required_fields'].': '.implode(', ', $missing_fields);
		}
		else {
			//check strings
				foreach ($_POST as $field => $value) {
					$moh[$field] = check_str($value);
				}

			//update the moh record
				$sql = "update v_music_on_hold set ";
				if (permission_exists('music_on_hold_domain')) {
					$sql .= "domain_uuid = ".(($moh['domain_uuid'] != '') ? "'".$moh['domain_uuid']."'" : 'null').", ";
				}
				if (permission_exists('music_on_hold_name')) {
					$sql .= "music_on_hold_name = '".$moh['name']."', ";
				}
				if (permission_exists('music_on_hold_path')) {
					$sql .= "music_on_hold_path = ".(($moh['path'] != '') ? "'".$moh['path']."'" : '$${sounds_dir}/music').", ";
				}
				$sql .= "music_on_hold_shuffle = '".$moh['shuffle']."', ";
				$sql .= "music_on_hold_channels = ".$moh['channels'].", ";
				$sql .= "music_on_hold_interval = ".(($moh['interval'] != '') ? $moh['interval'] : '20').", ";
				$sql .= "music_on_hold_timer_name = 'soft', ";
				$sql .= "music_on_hold_chime_list = '".$moh['chime_list']."', ";
				$sql .= "music_on_hold_chime_freq = ".(($moh['chime_freq'] != '') ? $moh['chime_freq'] : 'null').", ";
				$sql .= "music_on_hold_chime_max = ".(($moh['chime_max'] != '') ? $moh['chime_max'] : 'null')." ";
				$sql .= "where music_on_hold_uuid = '".$moh['uuid']."' ";
				if (!permission_exists('music_on_hold_domain')) {
					$sql .= "and domain_uuid = '".$domain_uuid."' ";
				}
				//echo $sql."<br>"; exit;
				$db->exec(check_sql($sql));
				unset($sql);

			//set message
				$_SESSION["message"] = $text['message-update'];

			//redirect
				header("Location: music_on_hold.php");
				exit;
		}
}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-moh_settings'];

//show the content
	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		echo "	tb.setAttribute('style', 'width: 380px;');\n";
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

	echo "<form method='post' name='frm'>\n";
	echo "<input type='hidden' name='uuid' value='".$moh['uuid']."'>\n";

	echo "<div style='float: right;'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='music_on_hold.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</div>\n";
	echo "<b>".$text['header-moh_settings'].": ".$moh['name_only']." (".($moh['rate']/1000).' kHz'.(($moh['rate'] == '48000') ? ' / '.$text['option-default'] : null).")</b>";
	echo "<br /><br />\n\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	if (permission_exists('music_on_hold_name')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' width='30%'>\n";
		echo "	".$text['label-name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' width='70%'>\n";
		echo "	<input class='formfld' type='text' name='name' value='".$moh['name']."'>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' width='30%'>\n";
	echo "	".$text['label-shuffle']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left' width='70%'>\n";
	echo "	<select name='shuffle' class='formfld'>\n";
	echo "		<option value='false' ".(($moh['shuffle'] == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "		<option value='true' ".(($moh['shuffle'] == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
	echo "	</select>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell'>\n";
	echo "	".$text['label-channels']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='channels' class='formfld'>\n";
	echo "		<option value='1'>".$text['label-mono']."</option>\n";
	echo "		<option value='2' ".(($moh['channels'] == '2') ? 'selected' : null).">".$text['label-stereo']."</option>\n";
	echo "	</select>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell'>\n";
	echo "	".$text['label-interval']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='interval' maxlength='4' style='max-width: 50px;' value='".$moh['interval']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell'>\n";
	echo "	".$text['label-chime_list']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='chime_list' class='formfld' style='width: 350px;' ".((permission_exists('music_on_hold_path')) ? "onchange='changeToInput(this);'" : null).">\n";
	echo "		<option value=''></option>\n";
	//misc optgroup
		/*
		if (if_group("superadmin")) {
			echo "<optgroup label='Misc'>\n";
			echo "	<option value='phrase:'>phrase:</option>\n";
			echo "	<option value='say:'>say:</option>\n";
			echo "	<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
		*/
	//recordings
		$tmp_selected = false;
		$sql = "select * from v_recordings where domain_uuid = '".$domain_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$recordings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (count($recordings) > 0) {
			echo "<optgroup label='Recordings'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				if ($moh['chime_list'] == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($moh['chime_list']) > 0) {
					$tmp_selected = true;
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
				}
				else if ($moh['chime_list'] == $recording_filename && strlen($moh['chime_list']) > 0) {
					$tmp_selected = true;
					echo "	<option value='".$recording_filename."' selected='selected'>".$recording_name."</option>\n";
				}
				else {
					echo "	<option value='".$recording_filename."'>".$recording_name."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//phrases
		$sql = "select * from v_phrases where domain_uuid = '".$domain_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (count($result) > 0) {
			echo "<optgroup label='Phrases'>\n";
			foreach ($result as &$row) {
				if ($moh['chime_list'] == "phrase:".$row["phrase_uuid"]) {
					$tmp_selected = true;
					echo "	<option value='phrase:".$row["phrase_uuid"]."' selected='selected'>".$row["phrase_name"]."</option>\n";
				}
				else {
					echo "	<option value='phrase:".$row["phrase_uuid"]."'>".$row["phrase_name"]."</option>\n";
				}
			}
			unset ($prep_statement);
			echo "</optgroup>\n";
		}
	//sounds
		$dir_path = $_SESSION['switch']['sounds']['dir'];
		recur_sounds_dir($_SESSION['switch']['sounds']['dir']);
		if (count($dir_array) > 0) {
			echo "<optgroup label='Sounds'>\n";
			foreach ($dir_array as $key => $value) {
				if (strlen($value) > 0) {
					if (substr($moh['chime_list'], 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$moh['chime_list'] = substr($moh['chime_list'], 71);
					}
					if ($moh['chime_list'] == $key) {
						$tmp_selected = true;
						echo "	<option value='$key' selected='selected'>$key</option>\n";
					}
					else {
						echo "	<option value='$key'>$key</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
		}
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected && strlen($moh['chime_list']) > 0) {
				echo "<optgroup label='Selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$moh['chime_list'])) {
					echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$moh['chime_list']."' selected='selected'>".$moh['chime_list']."</option>\n";
				}
				else if (substr($moh['chime_list'], -3) == "wav" || substr($moh['chime_list'], -3) == "mp3") {
					echo "	<option value='".$moh['chime_list']."' selected='selected'>".$moh['chime_list']."</option>\n";
				}
				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "	</select>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell'>\n";
	echo "	".$text['label-chime_frequency']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='chime_freq' maxlength='4' style='max-width: 50px;' value='".$moh['chime_freq']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell'>\n";
	echo "	".$text['label-chime_maximum']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='chime_max' maxlength='4' style='max-width: 50px;' value='".$moh['chime_max']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('music_on_hold_domain')) {
		echo "	<tr>\n";
		echo "		<td class='vncell' valign='top' nowrap='nowrap'>\n";
		echo "			".$text['label-domain']."\n";
		echo "		</td>\n";
		echo "		<td class='vtable' align='left'>\n";
		echo "			<select name='domain_uuid' class='formfld'>\n";
		if (permission_exists('music_on_hold_global_view') && permission_exists('music_on_hold_global_add')) {
			echo "			<option value=''>".$text['label-global']."</option>\n";
		}
		foreach ($_SESSION['domains'] as $row) {
			echo "    		<option value='".$row['domain_uuid']."' ".(($row['domain_uuid'] == $moh['domain_uuid']) ? "selected='selected'" : null).">".$row['domain_name']."</option>\n";
		}
		echo "    		</select>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
	}

	if (permission_exists('music_on_hold_path')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' width='30%'>\n";
		echo "	".$text['label-path']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' width='70%'>\n";
		echo "	<input class='formfld' type='text' name='path' value='".$moh['path']."'>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "<br>";

	echo "<div style='float: right;'>\n";
	echo "<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</div>\n";
	echo "<br><br>";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
