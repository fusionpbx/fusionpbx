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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Lewis Hallam <lewishallam80@gmail.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

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

//set the defaults
	$call_flow_sound = '';
	$call_flow_alternate_sound = '';
	$call_flow_name = '';
	$call_flow_extension = '';
	$call_flow_feature_code = '';
	$call_flow_pin_number = '';
	$call_flow_label = '';
	$call_flow_alternate_label = '';
	$call_flow_description = '';
	$call_flow_status = '';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$call_flow_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//initialize the destinations object
	$destination = new destinations;

//get http post variables and set them to php variables
	if (!empty($_POST)) {

		//set the variables from the http values
			$call_flow_uuid = $_POST["call_flow_uuid"] ?? null;
			$dialplan_uuid = $_POST["dialplan_uuid"] ?? null;
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
			$call_flow_enabled = $_POST["call_flow_enabled"] ?? 'false';
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
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

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
			//if (empty($domain_uuid)) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			//if (empty($call_flow_uuid)) { $msg .= $text['message-required']." ".$text['label-call_flow_uuid']."<br>\n"; }
			//if (empty($dialplan_uuid)) { $msg .= $text['message-required']." ".$text['label-dialplan_uuid']."<br>\n"; }
			//if (empty($call_flow_name)) { $msg .= $text['message-required']." ".$text['label-call_flow_name']."<br>\n"; }
			if (empty($call_flow_extension)) { $msg .= $text['message-required']." ".$text['label-call_flow_extension']."<br>\n"; }
			//if (empty($call_flow_feature_code)) { $msg .= $text['message-required']." ".$text['label-call_flow_feature_code']."<br>\n"; }
			//if (empty($call_flow_context)) { $msg .= $text['message-required']." ".$text['label-call_flow_context']."<br>\n"; }
			//if (empty($call_flow_status)) { $msg .= $text['message-required']." ".$text['label-call_flow_status']."<br>\n"; }
			//if (empty($call_flow_pin_number)) { $msg .= $text['message-required']." ".$text['label-call_flow_pin_number']."<br>\n"; }
			//if (empty($call_flow_label)) { $msg .= $text['message-required']." ".$text['label-call_flow_label']."<br>\n"; }
			//if (empty($call_flow_sound)) { $msg .= $text['message-required']." ".$text['label-call_flow_sound']."<br>\n"; }
			if (empty($call_flow_app)) { $msg .= $text['message-required']." ".($text['label-call_flow_app'] ?? '')."<br>\n"; }
			if (empty($call_flow_data)) { $msg .= $text['message-required']." ".($text['label-call_flow_data'] ?? '')."<br>\n"; }
			//if (empty($call_flow_alternate_label)) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_label']."<br>\n"; }
			//if (empty($call_flow_alternate_sound)) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_sound']."<br>\n"; }
			//if (empty($call_flow_alternate_app)) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_app']."<br>\n"; }
			//if (empty($call_flow_alternate_data)) { $msg .= $text['message-required']." ".$text['label-call_flow_alternate_data']."<br>\n"; }
			//if (empty($call_flow_description)) { $msg .= $text['message-required']." ".$text['label-call_flow_description']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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
			if (empty($call_flow_uuid)) {
				$call_flow_uuid = uuid();
			}

		//add the dialplan_uuid
			if (empty($dialplan_uuid)) {
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
			$dialplan_xml = "<extension name=\"".xml::sanitize($call_flow_name)."\" continue=\"\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
			if (!empty($call_flow_feature_code)) {
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($destination_feature)."$\" break=\"on-true\">\n";
				$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
				$dialplan_xml .= "		<action application=\"sleep\" data=\"200\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"feature_code=true\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"call_flow_uuid=".xml::sanitize($call_flow_uuid)."\"/>\n";
				$dialplan_xml .= "		<action application=\"lua\" data=\"call_flow.lua\"/>\n";
				$dialplan_xml .= "	</condition>\n";
			}
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($destination_extension)."$\">\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"call_flow_uuid=".xml::sanitize($call_flow_uuid)."\"/>\n";
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
			if (!empty($call_flow_uuid)) {
				$database->uuid($call_flow_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		// Update subscribed endpoints
		if (!empty($call_flow_feature_code)) {
			$esl = event_socket::create();
			if ($esl->is_connected()) {
				//send the event
				$event = "sendevent PRESENCE_IN\n";
				$event .= "proto: flow\n";
				$event .= "event_type: presence\n";
				$event .= "alt_event_type: dialog\n";
				$event .= "Presence-Call-Direction: outbound\n";
				$event .= "state: Active (1 waiting)\n";
				$event .= "from: flow+".$call_flow_feature_code."@".$_SESSION['domain_name']."\n";
				$event .= "login: flow+".$call_flow_feature_code."@".$_SESSION['domain_name']."\n";
				$event .= "unique-id: ".$call_flow_uuid."\n";
				if ($call_flow_status == "true") {
					$event .= "answer-state: confirmed\n";
				} else {
					$event .= "answer-state: terminated\n";
				}
				event_socket::command($event);
			}
		}

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
	} //(is_array($_POST) && empty($_POST["persistformvar"]))

//pre-populate the form
	if (!empty($_GET) && empty($_POST["persistformvar"])) {
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
	if (empty($call_flow_context)) {
		$call_flow_context = $_SESSION['domain_name'];
	}

//set the defaults
	if (empty($call_flow_enabled)) { $call_flow_enabled = 'true'; }

//get the sounds
	$sounds = new sounds;
	$audio_files = $sounds->get();

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-call_flow'];
	require_once "resources/header.php";

//show the content
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	function set_playable(id, audio_selected, audio_type) {\n";
		echo "		file_ext = audio_selected.split('.').pop();\n";
		echo "		var mime_type = '';\n";
		echo "		switch (file_ext) {\n";
		echo "			case 'wav': mime_type = 'audio/wav'; break;\n";
		echo "			case 'mp3': mime_type = 'audio/mpeg'; break;\n";
		echo "			case 'ogg': mime_type = 'audio/ogg'; break;\n";
		echo "		}\n";
		echo "		if (mime_type != '' && (audio_type == 'recordings' || audio_type == 'sounds')) {\n";
		echo "			if (audio_type == 'recordings') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../recordings/recordings.php?action=download&type=rec&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			else if (audio_type == 'sounds') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../switch/sounds.php?action=download&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			$('#recording_audio_' + id).attr('type', mime_type);\n";
		echo "			$('#recording_button_' + id).show();\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			$('#recording_button_' + id).hide();\n";
		echo "			$('#recording_audio_' + id).attr('src','').attr('type','');\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";
	}
	if (if_group("superadmin")) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	var objs;\n";
		echo "	function toggle_select_input(obj, instance_id){\n";
		echo "		tb=document.createElement('INPUT');\n";
		echo "		tb.type='text';\n";
		echo "		tb.name=obj.name;\n";
		echo "		tb.className='formfld';\n";
		echo "		tb.setAttribute('id', instance_id);\n";
		echo "		tb.setAttribute('style', 'width: ' + obj.offsetWidth + 'px;');\n";
		if (!empty($on_change)) {
			echo "	tb.setAttribute('onchange', \"".$on_change."\");\n";
			echo "	tb.setAttribute('onkeyup', \"".$on_change."\");\n";
		}
		echo "		tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "		document.getElementById('btn_select_to_input_' + instance_id).style.display = 'none';\n";
		echo "		tbb=document.createElement('INPUT');\n";
		echo "		tbb.setAttribute('class', 'btn');\n";
		echo "		tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "		tbb.type='button';\n";
		echo "		tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "		tbb.objs=[obj,tb,tbb];\n";
		echo "		tbb.onclick=function(){ replace_element(this.objs, instance_id); }\n";
		echo "		obj.parentNode.insertBefore(tb,obj);\n";
		echo "		obj.parentNode.insertBefore(tbb,obj);\n";
		echo "		obj.parentNode.removeChild(obj);\n";
		echo "		replace_element(this.objs, instance_id);\n";
		echo "	}\n";
		echo "	function replace_element(obj, instance_id){\n";
		echo "		obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "		obj[0].parentNode.removeChild(obj[1]);\n";
		echo "		obj[0].parentNode.removeChild(obj[2]);\n";
		echo "		document.getElementById('btn_select_to_input_' + instance_id).style.display = 'inline';\n";
		if (!empty($on_change)) {
			echo "	".$on_change.";\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

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
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
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
		if (!empty($call_flow_label)) {
			echo "	<option value='true' selected='selected'>".escape($call_flow_label)."</option>\n";
		}
		else {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
	}
	else {
		if (!empty($call_flow_label)) {
			echo "	<option value='true'>".escape($call_flow_label)."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
	}
	if ($call_flow_status == "false") {
		if (!empty($call_flow_alternate_label)) {
			echo "	<option value='false' selected='selected'>".escape($call_flow_alternate_label)."</option>\n";
		}
		else {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
	}
	else {
		if (!empty($call_flow_alternate_label)) {
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
	echo !empty($text['description-call_flow_label'])."\n";
	echo "</td>\n";
	echo "</tr>\n";

	$instance_id = 'call_flow_sound';
	$instance_label = 'call_flow_sound';
	$instance_value = $call_flow_sound;
	echo "<tr>\n";
	echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-'.$instance_label]."\n";
	echo "</td>\n";
	echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
	echo "	<option value=''></option>\n";
	$found = $playable = false;
	if (!empty($audio_files) && is_array($audio_files) && @sizeof($audio_files) != 0) {
		foreach ($audio_files as $key => $value) {
			echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
			foreach ($value as $row) {
				if ($key == 'recordings') {
					if (
						!empty($instance_value) &&
						($instance_value == $row["value"] || $instance_value == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.$row["value"]) &&
						file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.pathinfo($row["value"], PATHINFO_BASENAME))
						) {
						$selected = "selected='selected'";
						$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else if ($key == 'sounds') {
					if (!empty($instance_value) && $instance_value == $row["value"]) {
						$selected = "selected='selected'";
						$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else {
					unset($selected);
				}
				echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
			}
			echo "</optgroup>\n";
		}
	}
	if (if_group("superadmin") && !empty($instance_value) && !$found) {
		echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
	}
	unset($selected);
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && !empty($playable)) {
		switch (pathinfo($playable, PATHINFO_EXTENSION)) {
			case 'wav' : $mime_type = 'audio/wav'; break;
			case 'mp3' : $mime_type = 'audio/mpeg'; break;
			case 'ogg' : $mime_type = 'audio/ogg'; break;
		}
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."')"]);
		unset($playable, $mime_type);
	}
	echo "<br />\n";
	echo $text['description-'.$instance_label]."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//set the selected value
	$select_value = '';
	if (!empty($call_flow_app) && !empty($call_flow_data)) {
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

	$instance_id = 'call_flow_alternate_sound';
	$instance_label = 'call_flow_alternate_sound';
	$instance_value = $call_flow_alternate_sound;
	echo "<tr>\n";
	echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-'.$instance_label]."\n";
	echo "</td>\n";
	echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='".$instance_id."' id='".$instance_id."' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
	echo "	<option value=''></option>\n";
	$found = $playable = false;
	if (!empty($audio_files) && is_array($audio_files) && @sizeof($audio_files) != 0) {
		foreach ($audio_files as $key => $value) {
			echo "<optgroup label=".$text['label-'.$key]." data-type='".$key."'>\n";
			foreach ($value as $row) {
				if ($key == 'recordings') {
					if (
						!empty($instance_value) &&
						($instance_value == $row["value"] || $instance_value == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.$row["value"]) &&
						file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].'/'.pathinfo($row["value"], PATHINFO_BASENAME))
						) {
						$selected = "selected='selected'";
						$playable = '../recordings/recordings.php?action=download&type=rec&filename='.pathinfo($row["value"], PATHINFO_BASENAME);
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else if ($key == 'sounds') {
					if (!empty($instance_value) && $instance_value == $row["value"]) {
						$selected = "selected='selected'";
						$playable = '../switch/sounds.php?action=download&filename='.$row["value"];
						$found = true;
					}
					else {
						unset($selected);
					}
				}
				else {
					unset($selected);
				}
				echo "	<option value='".escape($row["value"])."' ".($selected ?? '').">".escape($row["name"])."</option>\n";
			}
			echo "</optgroup>\n";
		}
	}
	if (if_group("superadmin") && !empty($instance_value) && !$found) {
		echo "	<option value='".escape($instance_value)."' selected='selected'>".escape($instance_value)."</option>\n";
	}
	unset($selected);
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".$instance_id."' class='btn' name='' alt='back' onclick='toggle_select_input(document.getElementById(\"".$instance_id."\"), \"".$instance_id."\"); this.style.visibility=\"hidden\";' value='&#9665;'>";
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && (!empty($playable) || empty($instance_value))) {
		switch (pathinfo($playable, PATHINFO_EXTENSION)) {
			case 'wav' : $mime_type = 'audio/wav'; break;
			case 'mp3' : $mime_type = 'audio/mpeg'; break;
			case 'ogg' : $mime_type = 'audio/ogg'; break;
		}
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."')"]);
		unset($playable, $mime_type);
	}
	echo "<br />\n";
	echo $text['description-'.$instance_label]."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_alternate_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_value = '';
	if (!empty($call_flow_alternate_app) && !empty($call_flow_alternate_data)) {
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
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='call_flow_enabled' name='call_flow_enabled' value='true' ".($call_flow_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='call_flow_enabled' name='call_flow_enabled'>\n";
		echo "		<option value='true' ".($call_flow_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($call_flow_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_flow_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_description' maxlength='255' value=\"".escape($call_flow_description)."\">\n";
	echo "<br />\n";
	echo !empty($text['description-call_flow_description'])."\n";
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
