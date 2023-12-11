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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2023
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('bridge_add') && !permission_exists('bridge_edit')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$bridge_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the defaults
	$bridge_uuid = '';
	$bridge_name = '';
	$bridge_destination = '';
	$bridge_enabled = '';
	$bridge_description = '';

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$bridge_uuid = $_POST["bridge_uuid"];
		$bridge_name = $_POST["bridge_name"];
		$bridge_action = $_POST["bridge_action"];
		$bridge_profile = $_POST["bridge_profile"];
		$bridge_variables = $_POST["bridge_variables"];
		$bridge_gateways = $_POST["bridge_gateways"];
		$destination_number = $_POST["destination_number"];
		$bridge_destination = $_POST["bridge_destination"];
		$bridge_enabled = $_POST["bridge_enabled"] ?? 'false';
		$bridge_description = $_POST["bridge_description"];
	}

//process the user data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//delete the bridge
			if (permission_exists('bridge_delete')) {
				if ($_POST['action'] == 'delete' && is_uuid($bridge_uuid)) {
					//prepare
						$array[0]['checked'] = 'true';
						$array[0]['uuid'] = $bridge_uuid;
					//delete
						$obj = new bridges;
						$obj->delete($array);
					//redirect
						header('Location: bridges.php');
						exit;
				}
			}

		//get the uuid from the POST
			if ($action == "update") {
				$bridge_uuid = $_POST["bridge_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: bridges.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (empty($bridge_name)) { $msg .= $text['message-required']." ".$text['label-bridge_name']."<br>\n"; }
			//if (empty($bridge_destination)) { $msg .= $text['message-required']." ".$text['label-bridge_destination']."<br>\n"; }
			if (empty($bridge_enabled)) { $msg .= $text['message-required']." ".$text['label-bridge_enabled']."<br>\n"; }
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

			//add the bridge_uuid
			if (empty($bridge_uuid)) {
				$bridge_uuid = uuid();
			}

			//build the bridge statement for action user
			if ($bridge_action == 'user' || $bridge_action == 'loopback') {
				$bridge_destination = $bridge_action.'/'.$destination_number;
			}

			//build the bridge statement for gateway, or profiles - build the bridge statement
			if ($bridge_action == 'gateway' || $bridge_action == 'profile') {
				//create the main bridge statement
				$bridge_base = '';
				if (!empty($bridge_gateways)) {
					foreach($bridge_gateways as $gateway) {
						if (!empty($gateway)) {
							$gateway_array = explode(':', $gateway);
							$bridge_base .= ',sofia/gateway/'.$gateway_array[0].'/'.$destination_number;
						}
					}
					if (!empty($bridge_base)) {
						$bridge_destination = trim($bridge_base, ',');
					}
				}
				if ($bridge_action == 'profile' && empty($bridge_destination)) {
					$bridge_destination = 'sofia/'.$bridge_profile.'/'.$destination_number;
				}

				//add the variables back into the bridge_destination value
				if (!empty($bridge_variables)) {
					$variables = '';
					foreach($bridge_variables as $key => $value) {
						if (!empty($value)) {
							$variables .= ','.trim($key).'='.trim($value);
						}
					}
					if (!empty($variables)) {
						$bridge_destination = '{'.trim($variables, ',').'}'.$bridge_destination;
					}
				}
			}

		//prepare the array
			$array['bridges'][0]['bridge_uuid'] = $bridge_uuid;
			$array['bridges'][0]['domain_uuid'] = $_SESSION["domain_uuid"];
			$array['bridges'][0]['bridge_name'] = $bridge_name;
			$array['bridges'][0]['bridge_destination'] = $bridge_destination;
			$array['bridges'][0]['bridge_enabled'] = $bridge_enabled;
			$array['bridges'][0]['bridge_description'] = $bridge_description;

		//save to the data
			$database = new database;
			$database->app_name = 'bridges';
			$database->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
			$database->save($array);
			$message = $database->message;

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: bridges.php');
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET) && is_array($_GET) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$bridge_uuid = $_GET["id"];
		$sql = "select * from v_bridges ";
		$sql .= "where bridge_uuid = :bridge_uuid ";
		$parameters['bridge_uuid'] = $bridge_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters ?? null, 'row');
		if (!empty($row)) {
			$bridge_name = $row["bridge_name"];
			$bridge_destination = $row["bridge_destination"];
			$bridge_enabled = $row["bridge_enabled"];
			$bridge_description = $row["bridge_description"];
		}
		unset($sql, $parameters, $row);
	}

//build the bridge_actions array from the session actions
	$i = 0;
	foreach($_SESSION['bridge']['action'] as $variable) {
		$bridge_actions[$i]['action'] = $variable;
		$bridge_actions[$i]['label'] = ucwords($variable);
		$i++;
	}

//initialize the bridge_variables array from session bridge variables
	$session_variables = []; $i = 0;
	if (!empty($_SESSION['bridge']['variable'])) {
		foreach($_SESSION['bridge']['variable'] as $variable) {
			if (!empty($variable)) {
				$variable = explode("=", $variable);
				$session_variables[$i]['name'] = $variable[0];
				$session_variables[$i]['value'] = $variable[1] ?? '';
				$session_variables[$i]['label'] = ucwords(str_replace('_', ' ', $variable[0]));
				$session_variables[$i]['label'] = str_replace('Effective Caller Id', 'Caller ID', $session_variables[$i]['label']);
				$i++;
			}
		}
	}

//get the bridge variables from the database bridge_destination value
	$database_variables = []; $x = 0;
	if (!empty($bridge_destination)) {
		//get the variables from inside the { and } brackets
		preg_match('/^\{([^}]+)\}/', $bridge_destination, $matches);

		if (!empty($matches) && is_array($matches) && @sizeof($matches) != 0) {

			//create a variables array from the comma delimitted string
			$variables = explode(",", $matches[1]);

			//strip the variables from the $bridge_destination variable
			$bridge_destination = str_replace("{$matches[0]}", '', $bridge_destination);

		}

		//build a bridge variables data set
		$x = 0;
		if (!empty($variables) && is_array($variables)) {
			foreach($variables as $variable) {
				$pairs = explode("=", $variable);
				$database_variables[$x]['name'] = $pairs[0];
				$database_variables[$x]['value'] = $pairs[1];
				$database_variables[$x]['label'] = ucwords(str_replace('_', ' ', $pairs[0]));
				$database_variables[$x]['label'] = str_replace('Effective Caller Id', 'Caller ID', $database_variables[$x]['label']);
				$x++;
			}
		}
	}

//get the bridge_action from the bridge_destination
	if (!empty($bridge_destination)) {
		if (substr($bridge_destination, 0, 1) == '{') {
			$bridge_parts = explode('}', $bridge_destination);

			//get the variables from inside the { and } brackets
			preg_match('/^\{([^}]+)\}/', $bridge_destination, $matches);

			//strip the variables from the $bridge_destination variable
			$bridge_destination = str_replace("{$matches[0]}", '', $bridge_destination);
		}
		$bridge_array = explode("/", $bridge_destination);
		if ($bridge_array[0] == 'sofia') {
			if ($bridge_array[1] == 'gateway') {
				$bridge_action = 'gateway';
			}
			else {
				$bridge_action = 'profile';
				$bridge_profile = $bridge_array[1];
				$destination_number = $bridge_array[2];
			}
		}
		elseif ($bridge_array[0] == 'user') {
			$bridge_action = 'user';
			$destination_number = $bridge_array[1];
		}
		elseif ($bridge_array[0] == 'loopback') {
			$bridge_action = 'loopback';
			$destination_number = $bridge_array[1];
		}
	}

//merge the session and database bridge arrays together
	$bridge_variables = $session_variables;
	foreach($database_variables as $row) {
		$found = false;
		$i = 0;
		foreach($bridge_variables as $field) {
			if ($row['name'] == $field['name']) {
				//matching row found
				$found = true;

				//override session value with the value from the database
				if (!empty($row['value'])) {
					$bridge_variables[$i]['value'] = $row['value'];
				}
			}
			$i++;
		}
		if (!$found) {
			if (!empty($row['name'])) {
				$bridge_variables[] = $row;
			}
		}
	}

//get the gateways
	$actions = explode(',', $bridge_destination);
	foreach ($actions as $action) {
		$action_array = explode('/',$action);
		if (!empty($action_array) && is_array($action_array) && !empty($action_array[1]) && $action_array[1] == 'gateway') {
			$bridge_gateways[] = $action_array[2];
			$destination_number = $action_array[3];
		}
	}

//get the gateways
	$sql = "select * from v_gateways ";
	$sql .= "where enabled = 'true' ";
	if (permission_exists('outbound_route_any_gateway')) {
		$sql .= "order by domain_uuid = :domain_uuid DESC, gateway ";
	}
	else {
		$sql .= "and domain_uuid = :domain_uuid ";
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$gateways = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the domains
	$sql = "select * from v_domains ";
	$sql .= "where domain_enabled = 'true' ";
	$database = new database;
	$domains = $database->select($sql, null, 'all');
	unset($sql);

//get the sip profiles
	$sql = "select sip_profile_name ";
	$sql .= "from v_sip_profiles ";
	$sql .= "where sip_profile_enabled = 'true' ";
	$sql .= "order by sip_profile_name asc ";
	$database = new database;
	$sip_profiles = $database->select($sql, null, 'all');
	unset($sql);

//set the defaults
	if (empty($bridge_enabled)) { $bridge_enabled = 'true'; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-bridge'];
	require_once "resources/header.php";

//show or hide form elements based on the bridge action
	echo "<script type='text/javascript'>\n";
	echo "	function action_control(action) {\n";
	echo "		if (action == 'gateway') {\n";
	echo "			if (document.getElementById('tr_bridge_gateways')) { document.getElementById('tr_bridge_gateways').style.display = ''; }\n";
	echo "			if (document.getElementById('tr_bridge_profile')) { document.getElementById('tr_bridge_profile').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_bridge_variables')) { document.getElementById('tr_bridge_variables').style.display = ''; }\n";
	echo "		}\n";
	echo "		else if (action == 'user') {\n";
	echo "			if (document.getElementById('tr_bridge_gateways')) { document.getElementById('tr_bridge_gateways').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_bridge_profile')) { document.getElementById('tr_bridge_profile').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_bridge_variables')) { document.getElementById('tr_bridge_variables').style.display = 'none'; }\n";
	echo "		}\n";
	echo "		else if (action == 'profile') {\n";
	echo "			if (document.getElementById('tr_bridge_gateways')) { document.getElementById('tr_bridge_gateways').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_bridge_profile')) { document.getElementById('tr_bridge_profile').style.display = ''; }\n";
	echo "			if (document.getElementById('tr_bridge_variables')) { document.getElementById('tr_bridge_variables').style.display = ''; }\n";
	echo "		}\n";
	echo "		else if (action == 'loopback') {\n";
	echo "			if (document.getElementById('tr_bridge_gateways')) { document.getElementById('tr_bridge_gateways').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_bridge_profile')) { document.getElementById('tr_bridge_profile').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_bridge_variables')) { document.getElementById('tr_bridge_variables').style.display = 'none'; }\n";
	echo "		}\n";
	echo "		";
	echo "	}\n";
	echo "\n";
	if (!empty($bridge_action)) {
		echo "	window.onload = function() {\n";
		echo "		action_control('".$bridge_action."');\n";
		echo "	};\n";
	}
	echo "</script>\n";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-bridge']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'bridges.php']);
	if ($action == 'update' && permission_exists('bridge_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','name'=>'action','value'=>'save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('bridge_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='bridge_name' maxlength='255' value='".escape($bridge_name)."'>\n";
	echo "<br />\n";
	echo $text['description-bridge_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_action']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' id='bridge_action' name='bridge_action' onchange='action_control(this.options[this.selectedIndex].value);'>\n";
	echo "		<option value=''></option>\n";
	$i = 0;
	foreach($bridge_actions as $row) {
		echo "		<option value='".$row['action']."' ".(!empty($bridge_action) && $bridge_action == $row['action'] ? "selected='selected'" : null).">".$row['label']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-bridge_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (!empty($bridge_variables)) {
		echo "<tr id='tr_bridge_variables'>\n";
		echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-bridge_variables']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		$i = 0;
		foreach($bridge_variables as $row) {
			if ($i > 0) { echo "<br >\n"; }
			echo "	<input class='formfld' type='text' name='bridge_variables[".$row['name']."]' placeholder='".$row['label']."' maxlength='255' value='".escape($row['value'])."'>\n";
			$i++;
		}
		echo "<br />\n";
		echo $text['description-bridge_variables']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr id='tr_bridge_profile'>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-bridge_profile']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='bridge_profile'>\n";
	echo "		<option value=''></option>\n";
	foreach ($sip_profiles as $row) {
		if (!empty($bridge_profile) && $bridge_profile == $row["sip_profile_name"]) {
			echo "	<option value='".$row['sip_profile_name']."' selected='selected'>".escape($row["sip_profile_name"])."</option>\n";
		}
		else {
			echo "	<option value='".escape($row["sip_profile_name"])."'>".escape($row["sip_profile_name"])."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-bridge_profile']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_bridge_gateways'>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_gateways']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	for ($x = 0; $x <= 2; $x++) {
		if ($x > 0) { echo "<br />\n"; }
		echo "<select name='bridge_gateways[]' id='gateway' class='formfld' ".($onchange ?? '').">\n";
		echo "<option value=''></option>\n";
		echo "<optgroup label='".$text['label-bridge_gateways']."'>\n";
		$previous_domain_uuid = '';
		foreach($gateways as $row) {
			if (permission_exists('outbound_route_any_gateway')) {
				if ($previous_domain_uuid != $row['domain_uuid']) {
					$domain_name = '';
					foreach($domains as $field) {
						if ($row['domain_uuid'] == $field['domain_uuid']) {
							$domain_name = $field['domain_name'];
							break;
						}
					}
					if (empty($domain_name)) { $domain_name = $text['label-global']; }
					echo "</optgroup>";
					echo "<optgroup label='&nbsp; &nbsp;".$domain_name."'>\n";
				}
				if (!empty($bridge_gateways) && is_array($bridge_gateways) && $row['gateway_uuid'] == $bridge_gateways[$x]) {
					echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" selected=\"selected\">".escape($row['gateway'])."</option>\n"; //." db:".$row['gateway_uuid']." bg:".$bridge_gateways[$x]
				}
				else {
					echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
				}
			}
			else {
				if (!empty($bridge_gateways) && is_array($bridge_gateways) && $row['gateway_uuid'] == $bridge_gateways[$x]) {
					echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" $onchange selected=\"selected\">".escape($row['gateway'])."</option>\n";
				}
				else {
					echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
				}
			}
			$previous_domain_uuid = $row['domain_uuid'];
		}
		//echo "	</optgroup>\n";
		//echo "	<optgroup label='".$text['label-add-options']."Options'>\n";
		//echo "		<option value=\"loopback\">loopback</option>\n";
		//echo "		<option value=\"freetdm\">freetdm</option>\n";
		//echo "		<option value=\"xmpp\">xmpp</option>\n";
		//echo "	</optgroup>\n";
		echo "</select>\n";
	}
	echo "<br />\n";
	echo $text['description-bridge_gateways']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='destination_number'>".escape($destination_number ?? '')."</textarea>\n";
	echo "<br />\n";
	echo $text['description-destination_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	".$text['label-bridge_destination']."\n";
	//echo "</td>\n";
	//echo "<td class='vtable' style='position: relative;' align='left'>\n";
	//echo "	<textarea class='formfld' name='bridge_destination'>".escape($bridge_destination)."</textarea>\n";
	//echo "<br />\n";
	//echo $text['description-bridge_destination']."\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='bridge_enabled' name='bridge_enabled' value='true' ".(!empty($bridge_enabled) && $bridge_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='bridge_enabled' name='bridge_enabled'>\n";
		echo "		<option value='true' ".($bridge_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($bridge_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-bridge_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='bridge_description' maxlength='255' value=\"".escape($bridge_description)."\">\n";
	echo "<br />\n";
	echo $text['description-bridge_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br /><br />\n";

	if (!empty($bridge_uuid)) {
		echo "<input type='hidden' name='bridge_uuid' value='".escape($bridge_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
