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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_center_add') || permission_exists('conference_center_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$conference_center_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//delete the conference center
			if ($_POST['action'] == 'delete' && permission_exists('conference_center_delete') && is_uuid($conference_center_uuid)) {
				//prepare
					$array[0]['checked'] = 'true';
					$array[0]['uuid'] = $conference_center_uuid;
				//delete
					$obj = new conference_centers;
					$obj->delete_conference_centers($array);
				//redirect
					header('Location: conference_centers.php');
					exit;
			}

		//get http post variables and set them to php variables
			$conference_center_uuid = $_POST["conference_center_uuid"];
			$dialplan_uuid = $_POST["dialplan_uuid"];
			$conference_center_name = $_POST["conference_center_name"];
			$conference_center_extension = $_POST["conference_center_extension"];
			$conference_center_greeting = $_POST["conference_center_greeting"];
			$conference_center_pin_length = $_POST["conference_center_pin_length"];
			$conference_center_enabled = $_POST["conference_center_enabled"];
			$conference_center_description = $_POST["conference_center_description"];

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: conference_centers.php');
				exit;
			}

		//check for all required data
			$msg = '';
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

		//add the conference_center_uuid
			if (!is_uuid($_POST["conference_center_uuid"])) {
				$conference_center_uuid = uuid();
			}

		//add the dialplan_uuid
			if (!is_uuid($_POST["dialplan_uuid"])) {
				$dialplan_uuid = uuid();
			}

		//prepare the array
		    $array['conference_centers'][0]['domain_uuid'] = $_SESSION['domain_uuid'];;
		    $array['conference_centers'][0]['conference_center_uuid'] = $conference_center_uuid;
		    $array['conference_centers'][0]['dialplan_uuid'] = $dialplan_uuid;
		    $array['conference_centers'][0]['conference_center_name'] = $conference_center_name;
		    $array['conference_centers'][0]['conference_center_extension'] = $conference_center_extension;
		    $array['conference_centers'][0]['conference_center_greeting'] = $conference_center_greeting;
		    $array['conference_centers'][0]['conference_center_pin_length'] = $conference_center_pin_length;
		    $array['conference_centers'][0]['conference_center_enabled'] = $conference_center_enabled;
		    $array['conference_centers'][0]['conference_center_description'] = $conference_center_description;

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".$conference_center_name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
			if ($conference_center_pin_length > 1 && $conference_center_pin_length < 4) {
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^(".$conference_center_extension.")(\d{".$conference_center_pin_length."})$\" break=\"on-true\">\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"destination_number=$1\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"pin_number=$2\"/>\n";
				$dialplan_xml .= "		<action application=\"lua\" data=\"app.lua conference_center\"/>\n";
				$dialplan_xml .= "	</condition>\n";
			}
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$conference_center_extension."$\">\n";
			$dialplan_xml .= "		<action application=\"lua\" data=\"app.lua conference_center\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$array['dialplans'][0]["domain_uuid"] = $_SESSION['domain_uuid'];
			$array['dialplans'][0]["dialplan_uuid"] = $dialplan_uuid;
			$array['dialplans'][0]["dialplan_name"] = $conference_center_name;
			$array['dialplans'][0]["dialplan_number"] = $conference_center_extension;
			$array['dialplans'][0]["dialplan_context"] = $_SESSION['domain_name'];
			$array['dialplans'][0]["dialplan_continue"] = "false";
			$array['dialplans'][0]["dialplan_xml"] = $dialplan_xml;
			$array['dialplans'][0]["dialplan_order"] = "333";
			$array['dialplans'][0]["dialplan_enabled"] = $conference_center_enabled;
			$array['dialplans'][0]["dialplan_description"] = $conference_center_description;
			$array['dialplans'][0]["app_uuid"] = "b81412e8-7253-91f4-e48e-42fc2c9a38d9";

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");

		//save to the data
			$database = new database;
			$database->app_name = "conference_centers";
			$database->app_uuid = "b81412e8-7253-91f4-e48e-42fc2c9a38d9";
			$database->save($array);
			$message = $database->message;
			unset($array);

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		//debug information
			//echo "<pre>\n";
			//print_r($message);
			//echo "</pre>\n";
			//exit;

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["domain_name"]);

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
				header("Location: conference_centers.php");
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$conference_center_uuid = $_GET["id"];
		$sql = "select * from v_conference_centers ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and conference_center_uuid = :conference_center_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['conference_center_uuid'] = $conference_center_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$conference_center_uuid = $row["conference_center_uuid"];
			$dialplan_uuid = $row["dialplan_uuid"];
			$conference_center_name = $row["conference_center_name"];
			$conference_center_extension = $row["conference_center_extension"];
			$conference_center_greeting = $row["conference_center_greeting"];
			$conference_center_pin_length = $row["conference_center_pin_length"];
			$conference_center_enabled = $row["conference_center_enabled"];
			$conference_center_description = $row["conference_center_description"];
		}
		unset($sql, $parameters, $row);
	}

//set defaults
	if (strlen($conference_center_enabled) == 0) { $conference_center_enabled = "true"; }
	if (strlen($conference_center_pin_length) == 0) { $conference_center_pin_length = 9; }

//get the recordings
	$sql = "select recording_name, recording_filename from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$recordings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the phrases
	$sql = "select * from v_phrases ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$phrases = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the streams
	$sql = "select * from v_streams ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= "and stream_enabled = 'true' ";
	$sql .= "order by stream_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$streams = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-conference_center'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_center']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'conference_centers.php']);
	if ($action == 'update' && permission_exists('conference_center_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('conference_center_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_name' maxlength='255' value=\"".escape($conference_center_name)."\">\n";
	echo "<br />\n";
	echo $text['description-conference_center_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_extension' maxlength='255' value=\"".escape($conference_center_extension)."\">\n";
	echo "<br />\n";
	echo $text['description-conference_center_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_greeting']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//echo "	<input class='formfld' type='text' name='conference_center_greeting' maxlength='255' value=\"".escape($conference_center_greeting)."\">\n";
	if (permission_exists('conference_center_add') || permission_exists('conference_center_edit')) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		echo "	tb.setAttribute('style', 'width: 350px;');\n";
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
	echo "	<select name='conference_center_greeting' class='formfld' ".((permission_exists('conference_center_add') || permission_exists('conference_center_edit')) ? "onchange='changeToInput(this);'" : null).">\n";
	echo "		<option></option>\n";
	//recordings
		$tmp_selected = false;
		if (is_array($recordings)) {
			echo "<optgroup label='".$text['label-recordings']."'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				$recording_path = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'];
				$selected = '';
				if ($conference_center_greeting == $recording_path."/".$recording_filename) {
					$selected = "selected='selected'";
				}
				echo "	<option value='".escape($recording_path)."/".escape($recording_filename)."' ".escape($selected).">".escape($recording_name)."</option>\n";
				unset($selected);
			}
			echo "</optgroup>\n";
		}
	//phrases
		if (count($phrases) > 0) {
			echo "<optgroup label='".$text['label-phrases']."'>\n";
			foreach ($phrases as &$row) {
				$selected = ($conference_center_greeting == "phrase:".$row["phrase_uuid"]) ? true : false;
				echo "	<option value='phrase:".escape($row["phrase_uuid"])."' ".(($selected) ? "selected='selected'" : null).">".escape($row["phrase_name"])."</option>\n";
				if ($selected) { $tmp_selected = true; }
			}
			echo "</optgroup>\n";
		}
	//sounds
		$file = new file;
		$sound_files = $file->sounds();
		if (is_array($sound_files)) {
			echo "<optgroup label='".$text['label-sounds']."'>\n";
			foreach ($sound_files as $key => $value) {
				if (strlen($value) > 0) {
					if (substr($conference_center_greeting, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$conference_center_greeting = substr($conference_center_greeting, 71);
					}
					$selected = ($conference_center_greeting == $value) ? true : false;
					echo "	<option value='".escape($value)."' ".(($selected) ? "selected='selected'" : null).">".escape($value)."</option>\n";
					if ($selected) { $tmp_selected = true; }
				}
			}
			echo "</optgroup>\n";
		}
	//select
		if (strlen($conference_center_greeting) > 0) {
			if (permission_exists('conference_center_add') || permission_exists('conference_center_edit')) {
				if (!$tmp_selected) {
					echo "<optgroup label='selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$conference_center_greeting)) {
						echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".escape($conference_center_greeting)."' selected='selected'>".escape($ivr_menu_greet_long)."</option>\n";
					}
					else if (substr($conference_center_greeting, -3) == "wav" || substr($conference_center_greeting, -3) == "mp3") {
						echo "		<option value='".escape($conference_center_greeting)."' selected='selected'>".escape($conference_center_greeting)."</option>\n";
					}
					else {
						echo "		<option value='".escape($conference_center_greeting)."' selected='selected'>".escape($conference_center_greeting)."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "	".$text['description-conference_center_greeting']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_pin_length']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='conference_center_pin_length' maxlength='255' value='".escape($conference_center_pin_length)."'>\n";
	echo "<br />\n";
	echo $text['description-conference_center_pin_length']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='conference_center_enabled'>\n";
	echo "		<option value='true'>".$text['label-true']."</option>\n";
	echo "		<option value='false' ".($conference_center_enabled == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-conference_center_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_description' maxlength='255' value=\"".escape($conference_center_description)."\">\n";
	echo "<br />\n";
	echo $text['description-conference_center_description']."\n";
	echo "</td>\n";

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
		echo "<input type='hidden' name='conference_center_uuid' value='".escape($conference_center_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
