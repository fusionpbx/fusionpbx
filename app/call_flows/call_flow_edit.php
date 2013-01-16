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
if (permission_exists('call_flow_add') || permission_exists('call_flow_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$call_flow_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		//set the variables from the http values
			$call_flow_name = check_str($_POST["call_flow_name"]);
			$call_flow_extension = check_str($_POST["call_flow_extension"]);
			$call_flow_feature_code = check_str($_POST["call_flow_feature_code"]);
			$call_flow_context = check_str($_POST["call_flow_context"]);
			$call_flow_status = check_str($_POST["call_flow_status"]);
			$call_flow_pin_number = check_str($_POST["call_flow_pin_number"]);
			$call_flow_label = check_str($_POST["call_flow_label"]);
			$call_flow_destination = check_str($_POST["call_flow_destination"]);
			$call_flow_anti_label = check_str($_POST["call_flow_anti_label"]);
			$call_flow_alternate_destination = check_str($_POST["call_flow_alternate_destination"]);
			$call_flow_description = check_str($_POST["call_flow_description"]);
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);

		//seperate the action and the param
			$destination_array = explode(":", $call_flow_destination);
			$call_flow_app = array_shift($destination_array);
			$call_flow_data = join(':', $destination_array);

		//seperate the action and the param call_flow_anti_app
			$alternate_destination_array = explode(":", $call_flow_alternate_destination);
			$call_flow_anti_app = array_shift($alternate_destination_array);
			$call_flow_anti_data = join(':', $alternate_destination_array);

		//set the context for users that are not in the superadmin group
			if (!if_group("superadmin")) {
				if (count($_SESSION["domains"]) > 1) {
					$call_flow_context = $_SESSION['domain_name'];
				}
				else {
					$call_flow_context = "default";
				}
			}

	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$call_flow_uuid = check_str($_POST["call_flow_uuid"]);
	}

	//check for all required data
		if (strlen($call_flow_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($call_flow_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		//if (strlen($call_flow_feature_code) == 0) { $msg .= "Please provide: Feature Code<br>\n"; }
		if (strlen($call_flow_context) == 0) { $msg .= "Please provide: Context<br>\n"; }
		//if (strlen($call_flow_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($call_flow_pin_number) == 0) { $msg .= "Please provide: PIN Number<br>\n"; }
		//if (strlen($call_flow_status) == 0) { $msg .= "Please provide: Status<br>\n"; }
		//if (strlen($call_flow_label) == 0) { $msg .= "Please provide: Destination Label<br>\n"; }
		//if (strlen($call_flow_app) == 0) { $msg .= "Please provide: Application<br>\n"; }
		//if (strlen($call_flow_data) == 0) { $msg .= "Please provide: Application Data<br>\n"; }
		//if (strlen($call_flow_anti_label) == 0) { $msg .= "Please provide: Alternate Destination Label<br>\n"; }
		//if (strlen($call_flow_anti_app) == 0) { $msg .= "Please provide: Alternate  Application<br>\n"; }
		//if (strlen($call_flow_anti_data) == 0) { $msg .= "Please provide: Application Data<br>\n"; }
		//if (strlen($call_flow_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
			if ($action == "add" && permission_exists('call_flow_add')) {
				//prepare the uuids
					$call_flow_uuid = uuid();
					$dialplan_uuid = uuid();
				//add the call flow
					$sql = "insert into v_call_flows ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "call_flow_uuid, ";
					$sql .= "dialplan_uuid, ";
					$sql .= "call_flow_name, ";
					$sql .= "call_flow_extension, ";
					$sql .= "call_flow_feature_code, ";
					$sql .= "call_flow_context, ";
					$sql .= "call_flow_status, ";
					$sql .= "call_flow_pin_number, ";
					$sql .= "call_flow_label, ";
					$sql .= "call_flow_app, ";
					$sql .= "call_flow_data, ";
					$sql .= "call_flow_anti_label, ";
					$sql .= "call_flow_anti_app, ";
					$sql .= "call_flow_anti_data, ";
					$sql .= "call_flow_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'".$call_flow_uuid."', ";
					$sql .= "'".$dialplan_uuid."', ";
					$sql .= "'$call_flow_name', ";
					$sql .= "'$call_flow_extension', ";
					$sql .= "'$call_flow_feature_code', ";
					$sql .= "'$call_flow_context', ";
					$sql .= "'$call_flow_status', ";
					$sql .= "'$call_flow_pin_number', ";
					$sql .= "'$call_flow_label', ";
					$sql .= "'$call_flow_app', ";
					$sql .= "'$call_flow_data', ";
					$sql .= "'$call_flow_anti_label', ";
					$sql .= "'$call_flow_anti_app', ";
					$sql .= "'$call_flow_anti_data', ";
					$sql .= "'$call_flow_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "add")

			if ($action == "update" && permission_exists('call_flow_edit')) {
				//prepare the uuids
					if (strlen($dialplan_uuid) == 0) {
						$dialplan_uuid = uuid();
					}
				//add the call flow
					$sql = "update v_call_flows set ";
					$sql .= "dialplan_uuid = '$dialplan_uuid', ";
					$sql .= "call_flow_name = '$call_flow_name', ";
					$sql .= "call_flow_extension = '$call_flow_extension', ";
					$sql .= "call_flow_feature_code = '$call_flow_feature_code', ";
					$sql .= "call_flow_context = '$call_flow_context', ";
					$sql .= "call_flow_status = '$call_flow_status', ";
					$sql .= "call_flow_pin_number = '$call_flow_pin_number', ";
					$sql .= "call_flow_label = '$call_flow_label', ";
					$sql .= "call_flow_app = '$call_flow_app', ";
					$sql .= "call_flow_data = '$call_flow_data', ";
					$sql .= "call_flow_anti_label = '$call_flow_anti_label', ";
					$sql .= "call_flow_anti_app = '$call_flow_anti_app', ";
					$sql .= "call_flow_anti_data = '$call_flow_anti_data', ";
					$sql .= "call_flow_description = '$call_flow_description' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and call_flow_uuid = '$call_flow_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "update")

			if ($action == "add" || $action == "update") {

				//delete the dialplan
					$sql = "delete from v_dialplans ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
					$db->query(check_sql($sql));

				//delete the dialplan details
					$sql = "delete from v_dialplan_details ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);
					unset($sql);

				//add the dialplan entry
					$dialplan_name = $call_flow_name;
					$dialplan_order ='333';
					$dialplan_context = $call_flow_context;
					$dialplan_enabled = 'true';
					$dialplan_description = $call_flow_description;
					$app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
					dialplan_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

				//dialplan group 1
					require_once "includes/classes/switch_dialplan.php";

					//<condition destination_number="300" break="on-true"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'destination_number';
					$dialplan->dialplan_detail_data = '^'.str_replace('*', '\*', $call_flow_feature_code).'$';
					$dialplan->dialplan_detail_break = 'on-true';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '000';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="call_flow_uuid="/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'set';
					$dialplan->dialplan_detail_data = 'call_flow_uuid='.$call_flow_uuid;
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '010';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="feature_code=true"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'set';
					$dialplan->dialplan_detail_data = 'feature_code=true';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '020';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="lua" data="call_flow.lua"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'lua';
					$dialplan->dialplan_detail_data = 'call_flow.lua';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '1';
					$dialplan->dialplan_detail_order = '030';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

				//dialplan group 2
					//<condition destination_number="301"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'destination_number';
					$dialplan->dialplan_detail_data = '^'.str_replace('*', '\*', $call_flow_extension).'$';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '2';
					$dialplan->dialplan_detail_order = '000';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="call_flow_uuid="/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'set';
					$dialplan->dialplan_detail_data = 'call_flow_uuid='.$call_flow_uuid;
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '2';
					$dialplan->dialplan_detail_order = '010';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

					//<action application="set" data="ringback=${us-ring}"/>
					//$dialplan = new dialplan;
					//$dialplan->domain_uuid = $domain_uuid;
					//$dialplan->dialplan_uuid = $dialplan_uuid;
					//$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					//$dialplan->dialplan_detail_type = 'set';
					//$dialplan->dialplan_detail_data = 'ringback=${us-ring}';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					//$dialplan->dialplan_detail_group = '2';
					//$dialplan->dialplan_detail_order = '020';
					//$dialplan->dialplan_detail_add();
					//unset($dialplan);

					//<action application="lua" data="call_flow.lua"/>
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $domain_uuid;
					$dialplan->dialplan_uuid = $dialplan_uuid;
					$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan->dialplan_detail_type = 'lua';
					//$dialplan->dialplan_detail_data = $call_flow_extension . ' LUA call_flow.lua';
					$dialplan->dialplan_detail_data = 'call_flow.lua';
					//$dialplan->dialplan_detail_break = '';
					//$dialplan->dialplan_detail_inline = '';
					$dialplan->dialplan_detail_group = '2';
					$dialplan->dialplan_detail_order = '030';
					$dialplan->dialplan_detail_add();
					unset($dialplan);

				//save the xml
					save_dialplan_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;

				//delete the dialplan context from memcache
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}

				//redirect the browser
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=call_flows.php\">\n";
					echo "<div align='center'>\n";
					if ($action == "add") {
						echo "Add Complete\n";
					}
					if ($action == "update") {
						echo "Update Complete\n";
					}
					echo "</div>\n";
					require_once "includes/footer.php";
					return;
			}
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$call_flow_uuid = check_str($_GET["id"]);
		$sql = "select * from v_call_flows ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_flow_uuid = '$call_flow_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			//set the php variables
				$call_flow_name = $row["call_flow_name"];
				$call_flow_extension = $row["call_flow_extension"];
				$call_flow_feature_code = $row["call_flow_feature_code"];
				$call_flow_context = $row["call_flow_context"];
				$call_flow_status = $row["call_flow_status"];
				$call_flow_label = $row["call_flow_label"];
				$call_flow_app = $row["call_flow_app"];
				$call_flow_pin_number = $row["call_flow_pin_number"];
				$call_flow_data = $row["call_flow_data"];
				$call_flow_anti_label = $row["call_flow_anti_label"];
				$call_flow_anti_app = $row["call_flow_anti_app"];
				$call_flow_anti_data = $row["call_flow_anti_data"];
				$call_flow_description = $row["call_flow_description"];
				$dialplan_uuid = $row["dialplan_uuid"];

			//if superadmin show both the app and data
				if (if_group("superadmin")) {
					$destination_label = $call_flow_app.':'.$call_flow_data;
				}
				else {
					$destination_label = $call_flow_data;
				}

			//if superadmin show both the app and data
				if (if_group("superadmin")) {
					$alternate_destination_label = $call_flow_anti_app.':'.$call_flow_anti_data;
				}
				else {
					$alternate_destination_label = $call_flow_anti_data;
				}
		}
		unset ($prep_statement);
	}

	//set the context for users that are not in the superadmin group
		if (strlen($call_flow_context) == 0) {
			if (count($_SESSION["domains"]) > 1) {
				$call_flow_context = $_SESSION['domain_name'];
			}
			else {
				$call_flow_context = "default";
			}
		}

//show the header
	require_once "includes/header.php";

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
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Call Flow</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='call_flows.php'\" value='Back'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_name' maxlength='255' value=\"$call_flow_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_extension' maxlength='255' value=\"$call_flow_extension\">\n";
	echo "<br />\n";
	echo "Enter the extension number.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Feature Code:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_feature_code' maxlength='255' value=\"$call_flow_feature_code\">\n";
	echo "<br />\n";
	echo "Enter the feature code.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Context:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_context' maxlength='255' value=\"$call_flow_context\">\n";
	echo "<br />\n";
	echo "Enter the context.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Status:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_flow_status'>\n";
	echo "	<option value=''></option>\n";
	if ($call_flow_status == "true") {
		if (strlen($call_flow_label) > 0) {
			echo "	<option value='true' selected='selected'>$call_flow_label</option>\n";
		}
		else {
			echo "	<option value='true' selected='selected'>true</option>\n";
		}
	}
	else {
		if (strlen($call_flow_label) > 0) {
			echo "	<option value='true'>$call_flow_label</option>\n";
		}
		else {
			echo "	<option value='true'>true</option>\n";
		}
	}
	if ($call_flow_status == "false") {
		if (strlen($call_flow_anti_label) > 0) {
			echo "	<option value='false' selected='selected'>$call_flow_anti_label</option>\n";
		}
		else {
			echo "	<option value='false' selected='selected'>false</option>\n";
		}
	}
	else {
		if (strlen($call_flow_anti_label) > 0) {
			echo "	<option value='false'>$call_flow_anti_label</option>\n";
		}
		else {
			echo "	<option value='false'>false</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the status.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	PIN Number:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_pin_number' maxlength='255' value=\"$call_flow_pin_number\">\n";
	echo "<br />\n";
	echo "Enter the pin number\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Destination Label:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_label' maxlength='255' value=\"$call_flow_label\">\n";
	echo "<br />\n";
	echo "Enter the destination label\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Destination:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$select_value = '';
	//set the selected value
	if (strlen($call_flow_app.$call_flow_data) > 0) {
		$select_value = $call_flow_app.':'.$call_flow_data;
	}
	//show the destination list
	//switch_select_destination($select_type, $select_label, $select_name, $select_value, $select_style, $action='')
	switch_select_destination("dialplan", $destination_label, "call_flow_destination", $select_value, "", $call_flow_data);
	unset($select_value);
	echo "<br />\n";
	echo "Select the destination.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Alternate Label:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_anti_label' maxlength='255' value=\"$call_flow_anti_label\">\n";
	echo "<br />\n";
	echo "Enter the alternate destination label\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Alternate Destination:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//switch_select_destination($select_type, $select_label, $select_name, $select_value, $select_style, $action='')
	$select_value = '';
	if (strlen($call_flow_anti_app.$call_flow_anti_data) > 0) {
		$select_value = $call_flow_anti_app.':'.$call_flow_anti_data;
	}
	switch_select_destination("dialplan", $alternate_destination_label, "call_flow_alternate_destination", $select_value, "", $call_flow_anti_data);
	unset($select_value);
	echo "<br />\n";
	echo "Select the alternate destination.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_flow_description' maxlength='255' value=\"$call_flow_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='call_flow_uuid' value='$call_flow_uuid'>\n";
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>