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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2020
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('access_control_add') || permission_exists('access_control_edit')) {
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
		$access_control_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST) && @sizeof($_POST) != 0) {
		$access_control_name = $_POST["access_control_name"];
		$access_control_default = $_POST["access_control_default"];
		$access_control_nodes = $_POST["access_control_nodes"];
		$access_control_description = $_POST["access_control_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: access_controls.php');
				exit;
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				$x = 0;
				foreach ($_POST['access_control_nodes'] as $row) {
					if (is_uuid($row['access_control_uuid']) && $row['checked'] === 'true') {
						$array['access_controls'][$x]['checked'] = $row['checked'];
						$array['access_controls'][$x]['access_control_nodes'][]['access_control_node_uuid'] = $row['access_control_node_uuid'];
						$x++;
					}
				}

				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('access_control_add')) {
							$obj = new database;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('access_control_delete')) {
							$obj = new database;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('access_control_update')) {
							$obj = new database;
							$obj->toggle($array);
						}
						break;
				}

				//clear the cache, reloadacl and redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					//clear the cache
					$cache = new cache;
					$cache->delete("configuration:acl.conf");

					//create the event socket connection
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						event_socket_request($fp, "api reloadacl");
					}

					//redirect the user
					header('Location: access_control_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			if (strlen($access_control_name) == 0) { $msg .= $text['message-required']." ".$text['label-access_control_name']."<br>\n"; }
			if (strlen($access_control_default) == 0) { $msg .= $text['message-required']." ".$text['label-access_control_default']."<br>\n"; }
			//if (strlen($access_control_nodes) == 0) { $msg .= $text['message-required']." ".$text['label-access_control_nodes']."<br>\n"; }
			//if (strlen($access_control_description) == 0) { $msg .= $text['message-required']." ".$text['label-access_control_description']."<br>\n"; }
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

		//add the access_control_uuid
			if (!is_uuid($_POST["access_control_uuid"])) {
				$access_control_uuid = uuid();
			}

		//prepare the array
			$array['access_controls'][0]['access_control_uuid'] = $access_control_uuid;
			$array['access_controls'][0]['access_control_name'] = $access_control_name;
			$array['access_controls'][0]['access_control_default'] = $access_control_default;
			$array['access_controls'][0]['access_control_description'] = $access_control_description;
			$y = 0;
			if (is_array($access_control_nodes)) {
				foreach ($access_control_nodes as $row) {
					if (strlen($row['node_type']) > 0) {
						$array['access_controls'][0]['access_control_nodes'][$y]['access_control_node_uuid'] = $row["access_control_node_uuid"];
						$array['access_controls'][0]['access_control_nodes'][$y]['node_type'] = $row["node_type"];
						$array['access_controls'][0]['access_control_nodes'][$y]['node_cidr'] = $row["node_cidr"];
						$array['access_controls'][0]['access_control_nodes'][$y]['node_domain'] = $row["node_domain"];
						$array['access_controls'][0]['access_control_nodes'][$y]['node_description'] = $row["node_description"];
						$y++;
					}
				}
			}

		//save the data
			$database = new database;
			$database->app_name = 'access controls';
			$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
			$database->save($array);

		//clear the cache
			$cache = new cache;
			$cache->delete("configuration:acl.conf");

		//create the event socket connection
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				event_socket_request($fp, "api reloadacl");
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: access_controls.php');
				header('Location: access_control_edit.php?id='.urlencode($access_control_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_access_controls ";
		$sql .= "where access_control_uuid = :access_control_uuid ";
		$parameters['access_control_uuid'] = $access_control_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$access_control_name = $row["access_control_name"];
			$access_control_default = $row["access_control_default"];
			$access_control_description = $row["access_control_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	if (is_uuid($access_control_uuid)) {
		$sql = "select * from v_access_control_nodes ";
		$sql .= "where access_control_uuid = :access_control_uuid ";
		$sql .= "order by node_cidr asc";
		$parameters['access_control_uuid'] = $access_control_uuid;
		$database = new database;
		$access_control_nodes = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $access_control_node_uuid
	if (!is_uuid($access_control_node_uuid)) {
		$access_control_node_uuid = uuid();
	}

//add an empty row
	if (is_array($access_control_nodes) && @sizeof($access_control_nodes) != 0) {
		$x = count($access_control_nodes);
	}
	else {
		$access_control_nodes = array();
		$x = 0;
	}
	$access_control_nodes[$x]['access_control_uuid'] = $access_control_uuid;
	$access_control_nodes[$x]['access_control_node_uuid'] = uuid();
	$access_control_nodes[$x]['node_type'] = '';
	$access_control_nodes[$x]['node_cidr'] = '';
	$access_control_nodes[$x]['node_domain'] = '';
	$access_control_nodes[$x]['node_description'] = '';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-access_control'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input class='formfld' type='hidden' name='access_control_uuid' value='".escape($access_control_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-access_control']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'access_controls.php']);
	if ($action == 'update') {
		if (permission_exists('access_control_node_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('access_control_node_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-access_controls']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('access_control_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('access_control_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	if ($action == 'update') {
		if (permission_exists('access_control_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('access_control_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-access_control_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='access_control_name' maxlength='255' value='".escape($access_control_name)."'>\n";
	echo "<br />\n";
	echo $text['description-access_control_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-access_control_default']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<select class='formfld' name='access_control_default'>\n";
		echo "		<option value=''></option>\n";
		if ($access_control_default == "allow") {
			echo "		<option value='allow' selected='selected'>".$text['label-allow']."</option>\n";
		}
		else {
			echo "		<option value='allow'>".$text['label-allow']."</option>\n";
		}
		if ($access_control_default == "deny") {
			echo "		<option value='deny' selected='selected'>".$text['label-deny']."</option>\n";
		}
		else {
			echo "		<option value='deny'>".$text['label-deny']."</option>\n";
		}
		echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-access_control_default']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-access_control_nodes']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<th class='vtablereq'>".$text['label-node_type']."</th>\n";
	echo "			<td class='vtable'>".$text['label-node_cidr']."</td>\n";
	echo "			<td class='vtable'>".$text['label-node_domain']."</td>\n";
	echo "			<td class='vtable'>".$text['label-node_description']."</td>\n";
	if (is_array($access_control_nodes) && @sizeof($access_control_nodes) > 1 && permission_exists('access_control_node_delete')) {
		echo "			<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_details', 'delete_toggle_details');\" onmouseout=\"swap_display('delete_label_details', 'delete_toggle_details');\">\n";
		echo "				<span id='delete_label_details'>".$text['label-action']."</span>\n";
		echo "				<span id='delete_toggle_details'><input type='checkbox' id='checkbox_all_details' name='checkbox_all' onclick=\"edit_all_toggle('details'); checkbox_on_change(this);\"></span>\n";
		echo "			</td>\n";
	}
	echo "		</tr>\n";
	$x = 0;
	foreach($access_control_nodes as $row) {
		echo "		<tr>\n";
		echo "			<input type='hidden' name='access_control_nodes[$x][access_control_uuid]' value=\"".escape($row["access_control_uuid"])."\">\n";
		echo "			<input type='hidden' name='access_control_nodes[$x][access_control_node_uuid]' value=\"".escape($row["access_control_node_uuid"])."\">\n";
		echo "			<td class='formfld'>\n";
		echo "				<select class='formfld' name='access_control_nodes[$x][node_type]'>\n";
		echo "					<option value=''></option>\n";
		if ($row['node_type'] == "allow") {
			echo "					<option value='allow' selected='selected'>".$text['label-allow']."</option>\n";
		}
		else {
			echo "					<option value='allow'>".$text['label-allow']."</option>\n";
		}
		if ($row['node_type'] == "deny") {
			echo "					<option value='deny' selected='selected'>".$text['label-deny']."</option>\n";
		}
		else {
			echo "					<option value='deny'>".$text['label-deny']."</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		echo "				<input class='formfld' type='text' name='access_control_nodes[$x][node_cidr]' maxlength='255' value=\"".escape($row["node_cidr"])."\">\n";
		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		echo "				<input class='formfld' type='text' name='access_control_nodes[$x][node_domain]' maxlength='255' value=\"".escape($row["node_domain"])."\">\n";
		echo "			</td>\n";
		echo "			<td class='formfld'>\n";
		echo "				<input class='formfld' type='text' name='access_control_nodes[$x][node_description]' maxlength='255' value=\"".escape($row["node_description"])."\">\n";
		echo "			</td>\n";
		if (is_array($access_control_nodes) && @sizeof($access_control_nodes) > 1 && permission_exists('access_control_node_delete')) {
			if (is_uuid($row['access_control_node_uuid'])) {
				echo "		<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
				echo "			<input type='checkbox' name='access_control_nodes[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
				echo "		</td>\n";
			}
			else {
				echo "		<td></td>\n";
			}
		}
		echo "		</tr>\n";
		$x++;
	}
	echo "	</table>\n";
	echo "<br />\n";
	echo $text['description-node_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-access_control_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='access_control_description' maxlength='255' value='".escape($access_control_description)."'>\n";
	echo "<br />\n";
	echo $text['description-access_control_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
