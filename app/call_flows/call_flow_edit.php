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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Lewis Hallam <lewishallam80@gmail.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('call_flow_add') || permission_exists('call_flow_edit')) {
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
		$call_flow_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//initialize the destinations object
	$destination = new destinations;

//get http post variables and set them to php variables
	if (is_array($_POST)) {

		//set the variables from the http values
			$call_flow_uuid = $_POST["call_flow_uuid"];
			$dialplan_uuid = $_POST["dialplan_uuid"];
			$call_flow_name = $_POST["call_flow_name"];
			$call_flow_extension = $_POST["call_flow_extension"];
			$call_flow_feature_code = $_POST["call_flow_feature_code"];
			$call_flow_status = $_POST["call_flow_status"];
			$call_flow_pin_number = $_POST["call_flow_pin_number"];
			$call_flow_label = $_POST["call_flow_label"];
			$call_flow_sound = $_POST["call_flow_sound"];
			$call_flow_destination = $_POST["call_flow_destination"];
			$call_flow_alternate_label = $_POST["call_flow_alternate_label"];
			$call_flow_alternate_sound = $_POST["call_flow_alternate_sound"];
			$call_flow_alternate_destination = $_POST["call_flow_alternate_destination"];
			$call_flow_context = $_POST["call_flow_context"];
			$call_flow_enabled = $_POST["call_flow_enabled"];
			$call_flow_description = $_POST["call_flow_description"];

		//seperate the action and the param
			$destination_array = explode(":", $call_flow_destination);
			$call_flow_app = array_shift($destination_array);
			$call_flow_data = join(':', $destination_array);

		//seperate the action and the param call_flow_alternate_app
			$alternate_destination_array = explode(":", $call_flow_alternate_destination);
			$call_flow_alternate_app = array_shift($alternate_destination_array);
			$call_flow_alternate_data = join(':', $alternate_destination_array);
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$call_flow_uuid = $_POST["call_flow_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: call_flows.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			//if (strlen($call_flow_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_uuid']."<br>\n"; }
			//if (strlen($dialplan_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-dialplan_uuid']."<br>\n"; }
			//if (strlen($call_flow_name) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_name']."<br>\n"; }
			if (strlen($call_flow_extension) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_extension']."<br>\n"; }
			if (strlen($call_flow_feature_code) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_feature_code']."<br>\n"; }
			//if (strlen($call_flow_context) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_context']."<br>\n"; }
			//if (strlen($call_flow_status) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_status']."<br>\n"; }
			//if (strlen($call_flow_pin_number) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_pin_number']."<br>\n"; }
			//if (strlen($call_flow_label) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_label']."<br>\n"; }
			//if (strlen($call_flow_sound) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_sound']."<br>\n"; }
			if (strlen($call_flow_app) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_app']."<br>\n"; }
			if (strlen($call_flow_data) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_data']."<br>\n"; }
			//if (strlen($call_flow_alternate_label) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_label']."<br>\n"; }
			//if (strlen($call_flow_alternate_sound) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_sound']."<br>\n"; }
			//if (strlen($call_flow_alternate_app) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_app']."<br>\n"; }
			//if (strlen($call_flow_alternate_data) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_data']."<br>\n"; }
			//if (strlen($call_flow_description) == 0) { $msg .= $text['message-required']." ".$text['label-call_flow_description']."<br>\n"; }
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

		//add the call_flow_uuid
			if (!is_uuid($call_flow_uuid)) {
				$call_flow_uuid = uuid();
			}

		//add the dialplan_uuid
			if (!is_uuid($dialplan_uuid)) {
				$dialplan_uuid = uuid();
			}

		//set the default context
			if (permission_exists("call_flow_context")) {
				//allow a user assigned to super admin to change the call_flow_context
			}
			else {
				//if the call_flow_context was not set then set the default value
				$call_flow_context = $_SESSION['domain_name'];
			}

		//escape special characters
			$destination_extension = $call_flow_extension;
			$destination_extension = str_replace("*", "\*", $destination_extension);
			$destination_extension = str_replace("+", "\+", $destination_extension);

			$destination_feature = $call_flow_feature_code;
			// Allows dial feature code as `flow+<feature_code>`
			if (substr($destination_feature, 0, 5) != 'flow+') {
				$destination_feature = '(?:flow+)?' . $destination_feature;
			}
			$destination_feature = str_replace("*", "\*", $destination_feature);
			$destination_feature = str_replace("+", "\+", $destination_feature);

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".$call_flow_name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$destination_feature."$\" break=\"on-true\">\n";
			$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
			$dialplan_xml .= "		<action application=\"sleep\" data=\"200\"/>\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"feature_code=true\"/>\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"call_flow_uuid=".$call_flow_uuid."\"/>\n";
			$dialplan_xml .= "		<action application=\"lua\" data=\"call_flow.lua\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$destination_extension."$\">\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"call_flow_uuid=".$call_flow_uuid."\"/>\n";
			$dialplan_xml .= "		<action application=\"lua\" data=\"call_flow.lua\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//set the row id
			$i = 0;

		//build the dialplan array
			$array["dialplans"][$i]["domain_uuid"] = $_SESSION['domain_uuid'];
			$array["dialplans"][$i]["dialplan_uuid"] = $dialplan_uuid;
			$array["dialplans"][$i]["dialplan_name"] = $call_flow_name;
			$array["dialplans"][$i]["dialplan_number"] = $call_flow_extension;
			$array["dialplans"][$i]["dialplan_context"] = $call_flow_context;
			$array["dialplans"][$i]["dialplan_continue"] = "false";
			$array["dialplans"][$i]["dialplan_xml"] = $dialplan_xml;
			$array["dialplans"][$i]["dialplan_order"] = "333";
			$array["dialplans"][$i]["dialplan_enabled"] = $call_flow_enabled;
			$array["dialplans"][$i]["dialplan_description"] = $call_flow_description;
			$array["dialplans"][$i]["app_uuid"] = "b1b70f85-6b42-429b-8c5a-60c8b02b7d14";

			$array["call_flows"][$i]["call_flow_uuid"] =  $call_flow_uuid;
			$array["call_flows"][$i]["domain_uuid"] = $_SESSION['domain_uuid'];
			$array["call_flows"][$i]["dialplan_uuid"] = $dialplan_uuid;
			$array["call_flows"][$i]["call_flow_name"] = $call_flow_name;
			$array["call_flows"][$i]["call_flow_extension"] = $call_flow_extension;
			$array["call_flows"][$i]["call_flow_feature_code"] = $call_flow_feature_code;
			$array["call_flows"][$i]["call_flow_status"] = $call_flow_status;
			$array["call_flows"][$i]["call_flow_pin_number"] = $call_flow_pin_number;
			$array["call_flows"][$i]["call_flow_label"] = $call_flow_label;
			$array["call_flows"][$i]["call_flow_sound"] = $call_flow_sound;
			$array["call_flows"][$i]["call_flow_alternate_label"] = $call_flow_alternate_label;
			$array["call_flows"][$i]["call_flow_alternate_sound"] = $call_flow_alternate_sound;
			if ($destination->valid($call_flow_app.':'.$call_flow_data)) {
				$array["call_flows"][$i]["call_flow_app"] = $call_flow_app;
				$array["call_flows"][$i]["call_flow_data"] = $call_flow_data;
			}
			if ($destination->valid($call_flow_alternate_app.':'.$call_flow_alternate_data)) {
				$array["call_flows"][$i]["call_flow_alternate_app"] = $call_flow_alternate_app;
				$array["call_flows"][$i]["call_flow_alternate_data"] = $call_flow_alternate_data;
			}
			$array["call_flows"][$i]["call_flow_context"] = $call_flow_context;
			$array["call_flows"][$i]["call_flow_enabled"] = $call_flow_enabled;
			$array["call_flows"][$i]["call_flow_description"] = $call_flow_description;

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");

		//save to the data
			$database = new database;
			$database->app_name = 'call_flows';
			$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
			if (strlen($call_flow_uuid) > 0) {
				$database->uuid($call_flow_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		//debug info
			//echo "<pre>";
			//print_r($message);
			//echo "</pre>";
			//exit;

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$call_flow_context);

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
				header("Location: call_flows.php");
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$call_flow_uuid = $_GET["id"];
		$sql = "select * from v_call_flows ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_flow_uuid = :call_flow_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['call_flow_uuid'] = $call_flow_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		foreach ($result as $row) {
			//set the php variables
				$call_flow_uuid = $row["call_flow_uuid"];
				$dialplan_uuid = $row["dialplan_uuid"];
				$call_flow_name = $row["call_flow_name"];
				$call_flow_extension = $row["call_flow_extension"];
				$call_flow_feature_code = $row["call_flow_feature_code"];
				$call_flow_context = $row["call_flow_context"];
				$call_flow_status = $row["call_flow_status"];
				$call_flow_pin_number = $row["call_flow_pin_number"];
				$call_flow_label = $row["call_flow_label"];
				$call_flow_sound = $row["call_flow_sound"];
				$call_flow_app = $row["call_flow_app"];
				$call_flow_data = $row["call_flow_data"];
				$call_flow_alternate_label = $row["call_flow_alternate_label"];
				$call_flow_alternate_sound = $row["call_flow_alternate_sound"];
				$call_flow_alternate_app = $row["call_flow_alternate_app"];
				$call_flow_alternate_data = $row["call_flow_alternate_data"];
				$call_flow_enabled = $row["call_flow_enabled"];
				$call_flow_description = $row["call_flow_description"];

			//if superadmin show both the app and data
				if (if_group("superadmin")) {
					$destination_label = $call_flow_app.':'.$call_flow_data;
				}
				else {
					$destination_label = $call_flow_data;
				}

			//if superadmin show both the app and data
				if (if_group("superadmin")) {
					$alternate_destination_label = $call_flow_alternate_app.':'.$call_flow_alternate_data;
				}
				else {
					$alternate_destination_label = $call_flow_alternate_data;
				}
		}
		unset ($sql, $parameters, $result, $row);
	}

//set the context for users that are not in the superadmin group
	if (strlen($call_flow_context) == 0) {
		$call_flow_context = $_SESSION['domain_name'];
	}

//get the recordings
	$sql = "select recording_name, recording_filename from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$recordings = $database->select($sql, $parameters, 'all');
	unset($parameters, $sql);

	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		//echo "	tb.setAttribute('style', 'width: 380px;');\n";
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

	function sound_select_list($var, $name, $description_name, $load_sound=false) {
		global $text, $recordings, $db;

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-' . $description_name]."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		echo "<select name='".escape($name)."' class='formfld' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
		echo "	<option value=''></option>\n";
		//misc optgroup
			if (if_group("superadmin")) {
				echo "<optgroup label=".$text['miscellaneous'].">\n";
				echo "	<option value='say:'>say:</option>\n";
				echo "	<option value='tone_stream:'>tone_stream:</option>\n";
				echo "</optgroup>\n";
			}
		//recordings
			$tmp_selected = false;
			if (count($recordings) > 0) {
				echo "<optgroup label=".$text['recordings'].">\n";
				foreach ($recordings as &$row) {
					$recording_name = $row["recording_name"];
					$recording_filename = $row["recording_filename"];
					if ($var == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($var) > 0) {
						$tmp_selected = true;
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
					}
					else if ($var == $recording_filename && strlen($var) > 0) {
						$tmp_selected = true;
						echo "	<option value='".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
					}
					else {
						echo "	<option value='".escape($recording_filename)."'>".escape($recording_name)."</option>\n";
					}
				}
				echo "</optgroup>\n";
			}
		//phrases
			$sql = "select * from v_phrases where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');
			unset($parameters, $sql);
			if (is_array($result)) {
				echo "<optgroup label='Phrases'>\n";
				foreach ($result as &$row) {
					if ($var == "phrase:".$row["phrase_uuid"]) {
						$tmp_selected = true;
						echo "	<option value='phrase:".escape($row["phrase_uuid"])."' selected='selected'>".escape($row["phrase_name"])."</option>\n";
					}
					else {
						echo "	<option value='phrase:".escape($row["phrase_uuid"])."'>".escape($row["phrase_name"])."</option>\n";
					}
				}
				echo "</optgroup>\n";
			}
		//sounds
			if ($load_sound) {
				$file = new file;
				$sound_files = $file->sounds();
				if (is_array($sound_files)) {
					echo "<optgroup label=".$text["sounds"].">\n";
					foreach ($sound_files as $value) {
						if (strlen($value) > 0) {
							if (substr($var, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
								$var = substr($var, 71);
							}
							if ($var == $value) {
								$tmp_selected = true;
								echo "	<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
							}
							else {
								echo "	<option value='".escape($value)."'>".escape($value)."</option>\n";
							}
						}
					}
					echo "</optgroup>\n";
				}
			}
		//select
			if (if_group("superadmin")) {
				if (!$tmp_selected && strlen($var) > 0) {
					echo "<optgroup label='Selected'>\n";
					if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$var)) {
						echo "	<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".escape($var)."' selected='selected'>".escape($var)."</option>\n";
					}
					else if (substr($var, -3) == "wav" || substr($var, -3) == "mp3") {
						echo "	<option value='".escape($var)."' selected='selected'>".escape($var)."</option>\n";
					}
					else {
						echo "	<option value='".escape($var)."' selected='selected'>".escape($var)."</option>\n";
					}
					echo "</optgroup>\n";
				}
				unset($tmp_selected);
			}
		echo "	</select>\n";
		echo "	<br />\n";
		echo $text['description-' . $description_name]."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-call_flow'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_flow']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'call_flows.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_name' maxlength='255' value=\"".escape($call_flow_name)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_extension' maxlength='255' value=\"".escape($call_flow_extension)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_feature_code']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_feature_code' maxlength='255' value=\"".escape($call_flow_feature_code)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_feature_code']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_status']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_flow_status'>\n";
	echo "	<option value=''></option>\n";
	if ($call_flow_status == "true") {
		if (strlen($call_flow_label) > 0) {
			echo "	<option value='true' selected='selected'>".escape($call_flow_label)."</option>\n";
		}
		else {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
	}
	else {
		if (strlen($call_flow_label) > 0) {
			echo "	<option value='true'>".escape($call_flow_label)."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
	}
	if ($call_flow_status == "false") {
		if (strlen($call_flow_alternate_label) > 0) {
			echo "	<option value='false' selected='selected'>".escape($call_flow_alternate_label)."</option>\n";
		}
		else {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
	}
	else {
		if (strlen($call_flow_alternate_label) > 0) {
			echo "	<option value='false'>".escape($call_flow_alternate_label)."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-call_flow_status']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_pin_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_pin_number' maxlength='255' value=\"".escape($call_flow_pin_number)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_pin_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_label' maxlength='255' value=\"".escape($call_flow_label)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	sound_select_list($call_flow_sound, 'call_flow_sound', 'call_flow_sound', true);

	/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_sound']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_sound' maxlength='255' value=\"".escape($call_flow_sound)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_sound']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	*/

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_value = '';
	//set the selected value
	if (strlen($call_flow_app.$call_flow_data) > 0) {
		$select_value = $call_flow_app.':'.$call_flow_data;
	}
	//show the destination list
	echo $destination->select('dialplan', 'call_flow_destination', $select_value);
	unset($select_value);
	echo "<br />\n";
	echo $text['description-call_flow_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_alternate_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_alternate_label' maxlength='255' value=\"".escape($call_flow_alternate_label)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_alternate_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	sound_select_list($call_flow_alternate_sound, 'call_flow_alternate_sound', 'call_flow_alternate_sound', true);

	/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_alternate_sound']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_alternate_sound' maxlength='255' value=\"".escape($call_flow_alternate_sound)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_alternate_sound']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	*/

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_alternate_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_value = '';
	if (strlen($call_flow_alternate_app.$call_flow_alternate_data) > 0) {
		$select_value = $call_flow_alternate_app.':'.$call_flow_alternate_data;
	}
	echo $destination->select('dialplan', 'call_flow_alternate_destination', $select_value);
	unset($select_value);
	echo "<br />\n";
	echo $text['description-call_flow_alternate_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('call_flow_context')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-call_flow_context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='call_flow_context' maxlength='255' value=\"".escape($call_flow_context)."\">\n";
		echo "<br />\n";
		echo $text['description-call_flow_context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_flow_enabled'>\n";
	if ($call_flow_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($call_flow_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_description' maxlength='255' value=\"".escape($call_flow_description)."\">\n";
	echo "<br />\n";
	echo $text['description-call_flow_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='call_flow_uuid' value='".escape($call_flow_uuid)."'>\n";
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
