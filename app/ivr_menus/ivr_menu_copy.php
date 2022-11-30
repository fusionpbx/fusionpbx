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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('ivr_menu_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the http get/post variable(s) to a php variable
	if (isset($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$ivr_menu_uuid = $_GET["id"];
	}
	else {
		echo "access denied";
		exit;
	}	

//get the ivr_menus data
	$sql = "select * from v_ivr_menus ";
	$sql .= "where ivr_menu_uuid = '$ivr_menu_uuid' ";
	$sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$ivr_menus = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (!is_array($ivr_menus)) {
		echo "access denied 63";
		exit;
	}

//get the the ivr menu options
	$sql = "select * from v_ivr_menu_options ";
	$sql .= "where ivr_menu_uuid = '$ivr_menu_uuid' ";
	$sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by ivr_menu_uuid asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$ivr_menu_options = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//create the uuids
	$ivr_menu_uuid = uuid();
	$dialplan_uuid = uuid();

//set the row id
	$x = 0;

//set the variables
	$ivr_menu_name = 'copy-'.$ivr_menus[$x]['ivr_menu_name'];
	$ivr_menu_extension = $ivr_menus[$x]['ivr_menu_extension'];
	$ivr_menu_ringback = $ivr_menus[$x]['ivr_menu_ringback'];
	$ivr_menu_description = 'copy-'.$ivr_menus[$x]['ivr_menu_description'];

//prepare the ivr menu array
	$ivr_menus[$x]['ivr_menu_uuid'] = $ivr_menu_uuid;
	$ivr_menus[$x]['dialplan_uuid'] = $dialplan_uuid;
	$ivr_menus[$x]['ivr_menu_name'] = $ivr_menu_name;
	$ivr_menus[$x]['ivr_menu_description'] = $ivr_menu_description;

//get the the ivr menu options
	$y = 0;
	foreach ($ivr_menu_options as &$row) {

		//update the uuids
			$row['ivr_menu_uuid'] = $ivr_menu_uuid;
			$row['ivr_menu_option_uuid'] = uuid();
		
		//add the row to the array
			$ivr_menus[$x]["ivr_menu_options"][$y] = $row; 
		
		//increment the ivr menu option row id
			$y++;

	}

//build the xml dialplan
	$dialplan_xml = "<extension name=\"".$ivr_menu_name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
	$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$ivr_menu_extension."\">\n";
	$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
	$dialplan_xml .= "		<action application=\"sleep\" data=\"1000\"/>\n";
	$dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
	$dialplan_xml .= "		<action application=\"set\" data=\"ringback=".$ivr_menu_ringback."\"/>\n";
	$dialplan_xml .= "		<action application=\"set\" data=\"transfer_ringback=".$ivr_menu_ringback."\"/>\n";
	$dialplan_xml .= "		<action application=\"set\" data=\"ivr_menu_uuid=".$ivr_menu_uuid."\"/>\n";
	$dialplan_xml .= "		<action application=\"ivr\" data=\"".$ivr_menu_uuid."\"/>\n";
	$dialplan_xml .= "		<action application=\"hangup\" data=\"\"/>\n";
	$dialplan_xml .= "	</condition>\n";
	$dialplan_xml .= "</extension>\n";

//build the dialplan array
	$dialplan[$x]["domain_uuid"] = $_SESSION['domain_uuid'];
	$dialplan[$x]["dialplan_uuid"] = $dialplan_uuid;
	$dialplan[$x]["dialplan_name"] = $ivr_menu_name;
	$dialplan[$x]["dialplan_number"] = $ivr_menu_extension;
	$dialplan[$x]["dialplan_context"] = $_SESSION["context"];
	$dialplan[$x]["dialplan_continue"] = "false";
	$dialplan[$x]["dialplan_xml"] = $dialplan_xml;
	$dialplan[$x]["dialplan_order"] = "101";
	$dialplan[$x]["dialplan_enabled"] = "true";
	$dialplan[$x]["dialplan_description"] = $ivr_menu_description;
	$dialplan[$x]["app_uuid"] = "a5788e9b-58bc-bd1b-df59-fff5d51253ab";

//prepare the array
	$array['ivr_menus'] = $ivr_menus;
	$array['dialplans'] = $dialplan;

//add the dialplan permission
	$p = new permissions;
	$p->add("dialplan_add", "temp");
	$p->add("dialplan_edit", "temp");

//save the array to the database
	$database = new database;
	$database->app_name = 'ivr_menus';
	$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
	if (strlen($ivr_menu_uuid) > 0) {
		$database->uuid($ivr_menu_uuid);
	}
	$database->save($array);
	$message = $database->message;

//remove the temporary permission
	$p->delete("dialplan_add", "temp");
	$p->delete("dialplan_edit", "temp");

//synchronize the xml config
	save_dialplan_xml();

//delete the dialplan context from memcache
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$switch_cmd = "memcache delete dialplan:".$_SESSION["context"];
		$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
	}

//redirect the user
	messages::add($text['message-copy']);
	header("Location: ivr_menus.php");
	return;

?>
