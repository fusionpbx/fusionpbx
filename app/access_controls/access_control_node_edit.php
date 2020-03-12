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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('access_control_node_add') && !permission_exists('access_control_node_edit')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$access_control_node_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (is_uuid($_GET["access_control_uuid"])) {
		$access_control_uuid = $_GET["access_control_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$node_type = $_POST["node_type"];
		$node_cidr = $_POST["node_cidr"];
		$node_domain = $_POST["node_domain"];
		$node_description = $_POST["node_description"];
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	//get the uuid
		if ($action == "update" && is_uuid($_POST["access_control_node_uuid"])) {
			$access_control_node_uuid = $_POST["access_control_node_uuid"];
		}

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: access_controls.php');
			exit;
		}

	//check for all required data
		$msg = '';
		if (strlen($node_type) == 0) { $msg .= $text['message-required']." ".$text['label-node_type']."<br>\n"; }
		//if (strlen($node_cidr) == 0) { $msg .= $text['message-required']." ".$text['label-node_cidr']."<br>\n"; }
		//if (strlen($node_domain) == 0) { $msg .= $text['message-required']." ".$text['label-node_domain']."<br>\n"; }
		//if (strlen($node_description) == 0) { $msg .= $text['message-required']." ".$text['label-node_description']."<br>\n"; }

	// check IPv4 and IPv6 CIDR notation
	  	$pattern4 = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([0-9]|[1-2][0-9]|3[0-2]))$/';
		$pattern6 = '/^s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:)))(%.+)?s*(\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8]))$/';

		if ($node_cidr != '' && (preg_match($pattern4, $node_cidr) == 0) && (preg_match($pattern6, $node_cidr) == 0)) {
			$msg .= $text['message-required']." ".$text['label-node_cidr']."<br>\n";
		}
	   
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
			if ($action == "add" && permission_exists('access_control_node_add')) {

				//insert
				$array['access_control_nodes'][0]['access_control_node_uuid'] = uuid();
				$array['access_control_nodes'][0]['access_control_uuid'] = $access_control_uuid;
				$array['access_control_nodes'][0]['node_type'] = $node_type;
				$array['access_control_nodes'][0]['node_cidr'] = $node_cidr;
				$array['access_control_nodes'][0]['node_domain'] = $node_domain;
				$array['access_control_nodes'][0]['node_description'] = $node_description;
				$database = new database;
				$database->app_name = 'access_controls';
				$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
				$database->save($array);
				unset($array);

				//clear the cache
				$cache = new cache;
				$cache->delete("configuration:acl.conf");

				//create the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) { event_socket_request($fp, "api reloadacl"); }

				//add the message
				message::add($text['message-add']);

				//redirect the browser
				header('Location: access_control_edit.php?id='.escape($access_control_uuid));
				return;

			} //if ($action == "add")

			if ($action == "update" && permission_exists('access_control_node_edit')) {

				//update
				$array['access_control_nodes'][0]['access_control_node_uuid'] = $access_control_node_uuid;
				$array['access_control_nodes'][0]['access_control_uuid'] = $access_control_uuid;
				$array['access_control_nodes'][0]['node_type'] = $node_type;
				$array['access_control_nodes'][0]['node_cidr'] = $node_cidr;
				$array['access_control_nodes'][0]['node_domain'] = $node_domain;
				$array['access_control_nodes'][0]['node_description'] = $node_description;
				$database = new database;
				$database->app_name = 'access_controls';
				$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
				$database->save($array);
				unset($array);

				//clear the cache
				$cache = new cache;
				$cache->delete("configuration:acl.conf");

				//create the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) { event_socket_request($fp, "api reloadacl"); }

				//add the message
				message::add($text['message-update']);

				//redirect the browser
				header('Location: access_control_edit.php?id='.escape($access_control_uuid));
				return;

			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true" && is_uuid($_GET["id"])) {
		$access_control_node_uuid = $_GET["id"];
		$sql = "select * from v_access_control_nodes ";
		$sql .= "where access_control_node_uuid = :access_control_node_uuid ";
		$parameters['access_control_node_uuid'] = $access_control_node_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$node_type = $row["node_type"];
			$node_cidr = $row["node_cidr"];
			$node_domain = $row["node_domain"];
			$node_description = $row["node_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-access_control_node'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-access_control_node']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'access_control_edit.php?id='.urlencode($access_control_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_type']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='node_type'>\n";
	if ($node_type == "allow") {
		echo "	<option value='allow' selected='selected'>".$text['label-allow']."</option>\n";
	}
	else {
		echo "	<option value='allow'>".$text['label-allow']."</option>\n";
	}
	if ($node_type == "deny") {
		echo "	<option value='deny' selected='selected'>".$text['label-deny']."</option>\n";
	}
	else {
		echo "	<option value='deny'>".$text['label-deny']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-node_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_cidr']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='node_cidr' maxlength='255' value=\"".escape($node_cidr)."\">\n";
	echo "<br />\n";
	echo $text['description-node_cidr']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_domain']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='node_domain' maxlength='255' value=\"".escape($node_domain)."\">\n";
	echo "<br />\n";
	echo $text['description-node_domain']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='node_description' maxlength='255' value=\"".escape($node_description)."\">\n";
	echo "<br />\n";
	echo $text['description-node_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='access_control_uuid' value='".escape($access_control_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='access_control_node_uuid' value='".escape($access_control_node_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>