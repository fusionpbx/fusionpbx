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
	Ahron Greenberg <ahrongreenberg@gmail.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('page_group_add') || permission_exists('page_group_edit')) {
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
		$page_group_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {

		//set the variables from the http values
			$page_group_uuid = $_POST["page_group_uuid"];
			$dialplan_uuid = $_POST["dialplan_uuid"];
			$page_group_name = $_POST["page_group_name"];
			$page_group_extension = $_POST["page_group_extension"];
			$page_group_pin_number = $_POST["page_group_pin_number"];
			$page_group_destination = $_POST["page_group_destination"];
			$page_group_mute = $_POST["page_group_mute"];
			$page_group_context = $_POST["page_group_context"];
			$page_group_enabled = $_POST["page_group_enabled"];
			$page_group_description = $_POST["page_group_description"];
	}

	
//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$page_group_uuid = $_POST["page_group_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: paging_groups.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($page_group_name) == 0) { $msg .= $text['message-required']." ".$text['label-page_group_name']."<br>\n"; }
			if (strlen($page_group_extension) == 0) { $msg .= $text['message-required']." ".$text['label-page_group_extension']."<br>\n"; }
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

		//add the page_group_uuid
			if (!is_uuid($page_group_uuid)) {
				$page_group_uuid = uuid();
			}

		//add the dialplan_uuid
			if (!is_uuid($dialplan_uuid)) {
				$dialplan_uuid = uuid();
			}

		//set the default context
			if (permission_exists("page_group_context")) {
				//allow a user assigned to super admin to change the page_group_context
			}
			else {
				//if the page_group_context was not set then set the default value
				$page_group_context = $_SESSION['domain_name'];
			}
			
			$page_group_extension_regex =  str_replace("*", "\*", $page_group_extension);
				
		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".$page_group_name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$page_group_extension_regex."$\" break=\"on-true\">\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"page_group_uuid=".$page_group_uuid."\"/>\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"moderator=false\"/>\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"set api_hangup_hook=conference page-\${destination_number} kick all\"/>\n";
			$dialplan_xml .= "		<action application=\"lua\" data=\"page.lua\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//set the row id
			$i = 0;

		//build the dialplan array
			$array["dialplans"][$i]["domain_uuid"] = $_SESSION['domain_uuid'];
			$array["dialplans"][$i]["dialplan_uuid"] = $dialplan_uuid;
			$array["dialplans"][$i]["dialplan_name"] = $page_group_name;
			$array["dialplans"][$i]["dialplan_number"] = $page_group_extension;
			$array["dialplans"][$i]["dialplan_context"] = $page_group_context;
			$array["dialplans"][$i]["dialplan_continue"] = "false";
			$array["dialplans"][$i]["dialplan_xml"] = $dialplan_xml;
			$array["dialplans"][$i]["dialplan_order"] = "240";
			$array["dialplans"][$i]["dialplan_enabled"] = $page_group_enabled;
			$array["dialplans"][$i]["dialplan_description"] = $page_group_description;
			$array["dialplans"][$i]["app_uuid"] = "b1b70f85-6b42-429b-8c5a-60c8b02b7d14";

			$array["page_groups"][$i]["page_group_uuid"] =  $page_group_uuid;
			$array["page_groups"][$i]["domain_uuid"] = $_SESSION['domain_uuid'];
			$array["page_groups"][$i]["dialplan_uuid"] = $dialplan_uuid;
			$array["page_groups"][$i]["page_group_name"] = $page_group_name;
			$array["page_groups"][$i]["page_group_extension"] = $page_group_extension;
			$array["page_groups"][$i]["page_group_pin_number"] = $page_group_pin_number;
			$array["page_groups"][$i]["page_group_mute"] = $page_group_mute;
			$array["page_groups"][$i]["page_group_context"] = $page_group_context;
			$array["page_groups"][$i]["page_group_enabled"] = $page_group_enabled;
			$array["page_groups"][$i]["page_group_description"] = $page_group_description;
			
			if (strlen($_POST['page_group_destination'])) {
				$array["page_group_destinations"][$i]['page_group_destination_uuid'] = uuid();;
				$array["page_group_destinations"][$i]['domain_uuid'] = $domain_uuid;
				$array["page_group_destinations"][$i]['page_group_uuid'] = $page_group_uuid;
				$array["page_group_destinations"][$i]['page_group_destination'] = $page_group_destination;
			}

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");
		//add the destination permission
			$p->add("page_group_destination_add","temp");
		//save to the data
			$database = new database;
			$database->app_name = 'page_groups';
			$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
			if (strlen($page_group_uuid) > 0) {
				$database->uuid($page_group_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");
			$p->delete("page_group_destination_add","temp");

		//debug info
			//echo "<pre>";
			//print_r($message);
			//echo "</pre>";
			//exit;

		//save the xml
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$page_group_context);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
				header("Location: page_group_edit.php?id=".urlencode($page_group_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$page_group_uuid = $_GET["id"];
		$sql = "select * from v_page_groups ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and page_group_uuid = :page_group_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['page_group_uuid'] = $page_group_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		foreach ($result as $row) {
			//set the php variables
				$page_group_uuid = $row["page_group_uuid"];
				$dialplan_uuid = $row["dialplan_uuid"];
				$page_group_name = $row["page_group_name"];
				$page_group_extension = $row["page_group_extension"];
				$page_group_pin_number = $row["page_group_pin_number"];
				$page_group_mute = $row["page_group_mute"];
				$page_group_context = $row["page_group_context"];
				$page_group_enabled = $row["page_group_enabled"];
				$page_group_description = $row["page_group_description"];
		}
		
		unset ($sql, $parameters, $result, $row);
	}

//set the context for users that are not in the superadmin group
	if (strlen($page_group_context) == 0) {
		$page_group_context = $_SESSION['domain_name'];
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-page_group']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='page_groups.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-page_group_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='page_group_name' maxlength='255' value=\"".escape($page_group_name)."\">\n";
	echo "<br />\n";
	echo $text['description-page_group_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-page_group_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='page_group_extension' maxlength='255' value=\"".escape($page_group_extension)."\">\n";
	echo "<br />\n";
	echo $text['description-page_group_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	//get the destinations
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-page_group_destinations']."</td>";
		echo "		<td class='vtable'>";
	
		$sql = "select * from v_page_group_destinations ";
		$sql .= "where page_group_uuid = :page_group_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$sql .= "order by page_group_destination asc ";
		$parameters['page_group_uuid'] = $page_group_uuid;
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$destinations = $database->select($sql, $parameters, 'all');
		if (is_array($destinations) && @sizeof($destinations) != 0) {
			echo "		<table width='52%'>\n";
			foreach($destinations as $row) {
				echo "		<tr>\n";
				echo "			<td class='vtable'>".escape($row['page_group_destination'])."</td>\n";
				echo "			<td>\n";
				echo "				<a href='page_group_destination_delete.php?id=".urlencode($row['page_group_destination_uuid'])."&page_group_uuid=".urlencode($page_group_uuid)."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
				$page_group_destinations_copied[] = $row['page_group_destination'];
			}
			echo "		</table>\n";
			echo "		<br />\n";
		}
		unset($sql, $parameters, $destinations, $row);
		
		if (is_array($page_group_destinations_copied) && @sizeof($page_group_destinations_copied) != 0) {
			// modify sql to remove already copied destinayions from the list
			foreach ($page_group_destinations_copied as $x => $destination_copied) {
				$sql_where_and[] = 'extension <> :destination_'.$x;
				$parameters['destination_'.$x] = $destination_copied;
			}
			if (is_array($sql_where_and) && @sizeof($sql_where_and) != 0) {
				$sql_where = ' and '.implode(' and ', $sql_where_and);
			}
			unset($page_group_destinations_copied, $x, $destination, $sql_where_and);
		}
	
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and enabled = 'true' ";
		$sql .= $sql_where;
		$sql .= " order by extension asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$extensions = $database->select($sql, $parameters, 'all');
		$message = $database->message;
		
		echo "			<select name='page_group_destination' class='formfld' style='width: auto;'>\n";
		echo "			<option value=''></option>\n";
		if (is_array($extensions) && @sizeof($extensions) != 0) {
			foreach($extensions as $row) {
				echo "			<option value='".escape($row['extension'])."'>".escape($row['extension'])."</option>\n";
			}
		}
		unset($sql, $parameters, $result, $row);
		echo "			</select>";
		echo "			<input type='button' class='btn' value=\"".$text['button-add']."\" onclick='submit_form();'>\n";
		echo "			<br>\n";
		echo "			".$text['description-page_group_destinations']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-page_group_pin_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='page_group_pin_number' maxlength='255' value=\"".escape($page_group_pin_number)."\">\n";
	echo "<br />\n";
	echo $text['description-page_group_pin_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-page_group_mute']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='page_group_mute'>\n";
	
	if ($page_group_mute == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($page_group_mute == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-page_group_mute']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('page_group_context')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-page_group_context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='page_group_context' maxlength='255' value=\"".escape($page_group_context)."\">\n";
		echo "<br />\n";
		echo $text['description-page_group_context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='page_group_enabled'>\n";
	if ($page_group_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($page_group_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-page_group_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='page_group_description' maxlength='255' value=\"".escape($page_group_description)."\">\n";
	echo "<br />\n";
	echo $text['description-page_group_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "			<input type='hidden' name='page_group_uuid' value='".escape($page_group_uuid)."'>\n";
		echo "			<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";
	
	echo "<script>\n";
	echo "	function submit_form() {\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
