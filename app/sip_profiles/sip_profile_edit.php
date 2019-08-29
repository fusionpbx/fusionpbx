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
	Portions created by the Initial Developer are Copyright (C) 2016-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('sip_profile_add') || permission_exists('sip_profile_edit')) {
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
		$sip_profile_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$sip_profile_uuid = $_POST["sip_profile_uuid"];
		$sip_profile_name = $_POST["sip_profile_name"];
		$sip_profile_hostname = $_POST["sip_profile_hostname"];
		$sip_profile_enabled = $_POST["sip_profile_enabled"];
		$sip_profile_description = $_POST["sip_profile_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$sip_profile_uuid = $_POST["sip_profile_uuid"];
			}

		//check for all required data
			$msg = '';
			//if (strlen($sip_profile_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_uuid']."<br>\n"; }
			if (strlen($sip_profile_name) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_name']."<br>\n"; }
			//if (strlen($sip_profile_hostname) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_hostname']."<br>\n"; }
			if (strlen($sip_profile_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_enabled']."<br>\n"; }
			if (strlen($sip_profile_description) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_description']."<br>\n"; }
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

		//add the sip_profile_uuid
			if (!is_uuid($_POST["sip_profile_uuid"])) {
				$sip_profile_uuid = uuid();
				$_POST["sip_profile_uuid"] = $sip_profile_uuid;
			}

		//cleanup the array
			foreach ($_POST["sip_profile_domains"] as $index => $row) {
				//populate sip profile uuid
					if (!is_uuid($row['sip_profile_uuid'])) {
						$_POST["sip_profile_domains"][$index]['sip_profile_uuid'] = $sip_profile_uuid;
					}
				//unset the empty row
					if (strlen($row["sip_profile_domain_name"]) == 0) {
						unset($_POST["sip_profile_domains"][$index]);
					}
					if (strlen($row["sip_profile_domain_alias"]) == 0) {
						unset($_POST["sip_profile_domains"][$index]);
					}
					if (strlen($row["sip_profile_domain_parse"]) == 0) {
						unset($_POST["sip_profile_domains"][$index]);
					}
			}

		//cleanup the array
			foreach ($_POST["sip_profile_settings"] as $index => $row) {
				//populate sip profile uuid
					if (!is_uuid($row['sip_profile_uuid'])) {
						$_POST["sip_profile_settings"][$index]['sip_profile_uuid'] = $sip_profile_uuid;
					}
				//unset the empty row
					if (strlen($row["sip_profile_setting_name"]) == 0) {
						unset($_POST["sip_profile_settings"][$index]);
					}
					//if (strlen($row["sip_profile_setting_value"]) == 0) {
					//	unset($_POST["sip_profile_settings"][$index]);
					//}
					if (strlen($row["sip_profile_setting_enabled"]) == 0) {
						unset($_POST["sip_profile_settings"][$index]);
					}
			}

		//prepare the array
			$array['sip_profiles'][] = $_POST;

		//grant temporary permissions
			$p = new permissions;
			$p->add('sip_profile_domain_add', 'temp');
			$p->add('sip_profile_setting_add', 'temp');

		//save to the data
			$database = new database;
			$database->app_name = 'sip_profiles';
			$database->app_uuid = '159a8da8-0e8c-a26b-6d5b-19c532b6d470';
			$database->save($array);
			$message = $database->message;

			//get the hostname
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_cmd = "switchname";
				$sip_profile_hostname = event_socket_request($fp, 'api '.$switch_cmd);
			}

		//revoke temporary permissions
			$p->delete('sip_profile_domain_add', 'temp');
			$p->delete('sip_profile_setting_add', 'temp');

		//clear the cache
			$cache = new cache;
			$cache->delete("configuration:sofia.conf:".$sip_profile_hostname);

		//save the sip profile xml
			save_sip_profile_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//redirect the user
			if ($action == "add") {
				message::add($text['message-add']);
			}
			if ($action == "update") {
				message::add($text['message-update']);
			}
			header('Location: sip_profile_edit.php?id='.urlencode($sip_profile_uuid));
			exit;
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sip_profile_uuid = $_GET["id"];
		$sql = "select * from v_sip_profiles ";
		$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
		$parameters['sip_profile_uuid'] = $sip_profile_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$sip_profile_name = $row["sip_profile_name"];
			$sip_profile_hostname = $row["sip_profile_hostname"];
			$sip_profile_enabled = $row["sip_profile_enabled"];
			$sip_profile_description = $row["sip_profile_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	$sql = "select * from v_sip_profile_settings ";
	$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
	$sql .= "order by sip_profile_setting_name ";
	$parameters['sip_profile_uuid'] = $sip_profile_uuid;
	$database = new database;
	$sip_profile_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row
	$x = count($sip_profile_settings);
	$sip_profile_settings[$x]['sip_profile_setting_uuid'] = uuid();
	$sip_profile_settings[$x]['sip_profile_uuid'] = $sip_profile_uuid;
	$sip_profile_settings[$x]['sip_profile_setting_name'] = '';
	$sip_profile_settings[$x]['sip_profile_setting_value'] = '';
	$sip_profile_settings[$x]['sip_profile_setting_enabled'] = '';
	$sip_profile_settings[$x]['sip_profile_setting_description'] = '';

//get the child data
	$sql = "select * from v_sip_profile_domains ";
	$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
	$parameters['sip_profile_uuid'] = $sip_profile_uuid;
	$database = new database;
	$sip_profile_domains = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row
	$x = count($sip_profile_domains);
	$sip_profile_domains[$x]['sip_profile_domain_uuid'] = uuid();
	$sip_profile_domains[$x]['sip_profile_uuid'] = $sip_profile_uuid;
	$sip_profile_domains[$x]['sip_profile_domain_name'] = '';
	$sip_profile_domains[$x]['sip_profile_domain_alias'] = '';
	$sip_profile_domains[$x]['sip_profile_domain_parse'] = '';

//show the header
	require_once "resources/header.php";
	
//label to form input
	echo "<script language='javascript'>\n";
	echo "	function label_to_form(label_id, form_id) {\n";
	echo "		if (document.getElementById(label_id) != null) {\n";
	echo "			label = document.getElementById(label_id);\n";
	echo "			label.parentNode.removeChild(label);\n";
	echo "		}\n";
	echo "		document.getElementById(form_id).style.display='';\n";
	echo "	}\n";
	echo "</script>\n";
	
//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-sip_profile']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='sip_profiles.php'\" value='".$text['button-back']."'>";
	if (permission_exists('dialplan_add')
		|| permission_exists('inbound_route_add')
		|| permission_exists('outbound_route_add')
		|| permission_exists('time_condition_add')) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"var name = prompt('".$text['label-new_sip_profile_name']."'); if (name != null) { window.location='sip_profile_copy.php?id=".urlencode($sip_profile_uuid)."&name=' + name; }\" value='".$text['button-copy']."'>\n";
	}
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_name' maxlength='255' value=\"".escape($sip_profile_name)."\">\n";
	echo "<br />\n";
	echo $text['description-sip_profile_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td class='vncell' align='left'>\n";
	echo "			".$text['title-sip_profile_domains']."\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "			<table>\n";
	echo "				<tr>\n";
	echo "					<th class='vtable' style='width:80px; text-align: left;'>&nbsp;".$text['label-sip_profile_domain_name']."</th>\n";
	echo "					<th class='vtable' style='width:70px; text-align: left;'>".$text['label-sip_profile_domain_alias']."</th>\n";
	echo "					<th class='vtable' style='width:70px; text-align: left;'>".$text['label-sip_profile_domain_parse']."</th>\n";
	echo "				</tr>\n";
	$x = 0;
	foreach ($sip_profile_domains as $row) {
		echo "			<tr>\n";
		if (is_uuid($row["sip_profile_domain_uuid"])) {
			$sip_profile_domain_uuid = $row["sip_profile_domain_uuid"];
		}
		if (is_uuid($row["sip_profile_uuid"])) {
			$sip_profile_uuid = $row["sip_profile_uuid"];
		}
		echo "				<input type='hidden' name='sip_profile_domains[$x][sip_profile_domain_uuid]' value=\"".escape($sip_profile_domain_uuid)."\">\n";
		echo "				<input type='hidden' name='sip_profile_domains[$x][sip_profile_uuid]' value=\"".escape($sip_profile_uuid)."\">\n";
		echo "				<td class=\"vtablerow\" style=\"\" onclick=\"label_to_form('label_sip_profile_domain_name_$x','sip_profile_domain_name_$x');\" nowrap=\"nowrap\">\n";
		echo "					&nbsp; <label id='label_sip_profile_domain_name_$x'>".escape($row["sip_profile_domain_name"])."</label>\n";
		echo "					<input id='sip_profile_domain_name_$x' class='formfld' style='display: none;' type='text' name='sip_profile_domains[$x][sip_profile_domain_name]' maxlength='255' value=\"".escape($row["sip_profile_domain_name"])."\">\n";
		echo "				</td>\n";
		echo "				<td class=\"vtablerow\" style=\"\" onclick=\"label_to_form('label_sip_profile_domain_alias_$x','sip_profile_domain_alias_$x');\" nowrap=\"nowrap\">\n";
		echo "					<label id='label_sip_profile_domain_alias_$x'>".escape($row["sip_profile_domain_alias"])."</label>\n";
		echo "					<select id='sip_profile_domain_alias_$x' class='formfld' style='display: none;' name='sip_profile_domains[$x][sip_profile_domain_alias]'>\n";
		echo "						<option value=''></option>\n";
		if ($row["sip_profile_domain_alias"] == "true") {
			echo "						<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "						<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($row["sip_profile_domain_alias"] == "false") {
			echo "						<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "						<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "					</select>\n";

		echo "				</td>\n";
		echo "				<td class=\"vtablerow\" style=\"\" onclick=\"label_to_form('label_sip_profile_domain_parse_$x','sip_profile_domain_parse_$x');\" nowrap=\"nowrap\">\n";
		echo "					<label id='label_sip_profile_domain_parse_$x'>".escape($row["sip_profile_domain_parse"])."</label>\n";
		echo "					<select id='sip_profile_domain_parse_$x' class='formfld' style='display: none;' name='sip_profile_domains[$x][sip_profile_domain_parse]'>\n";
		echo "						<option value=''></option>\n";
		if ($row["sip_profile_domain_parse"] == "true") {
			echo "						<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "						<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($row["sip_profile_domain_parse"] == "false") {
			echo "						<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "						<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "					</select>\n";
		echo "				</td>\n";
		echo "				<td class='list_control_icons' style='width: 25px;'>\n";
		if (strlen($row["sip_profile_domain_name"]) > 0) {
			echo "				<a href=\"sip_profile_domain_delete.php?id=".urlencode($row["sip_profile_domain_uuid"])."&amp;sip_profile_domain_uuid=".urlencode($row["sip_profile_domain_uuid"])."&amp;a=delete\" alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">".$v_link_label_delete."</a>\n";
		}
		echo "				</td>\n";
		echo "			</tr>\n";
		//convert last empty labels to form elements
		if ($row["sip_profile_domain_name"] == '' && $row["sip_profile_domain_alias"] == '' && $row["sip_profile_domain_parse"] == '') {
			echo "<script>\n";
			echo "	label_to_form('label_sip_profile_domain_name_$x','sip_profile_domain_name_$x');\n";
			echo "	label_to_form('label_sip_profile_domain_alias_$x','sip_profile_domain_alias_$x');\n";
			echo "	label_to_form('label_sip_profile_domain_parse_$x','sip_profile_domain_parse_$x');\n";
			echo "</script>\n";
		}
		$x++;
	}
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "		<td class='vncellreq' align='left'>\n";
	echo "			".$text['label-sip_profile_settings']."\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "			<table>\n";
	echo "				<tr>\n";
	echo "					<th class='vtable' style='text-align: left;'>&nbsp;".$text['label-sip_profile_setting_name']."</th>\n";
	echo "					<th class='vtable' style='text-align: left;'>".$text['label-sip_profile_setting_value']."</th>\n";
	echo "					<th class='vtable' style='width:70px; text-align: left;'>".$text['label-sip_profile_setting_enabled']."</th>\n";
	echo "					<th class='vtable' style='text-align: left;'>".$text['label-sip_profile_setting_description']."</th>\n";
	echo "				</tr>\n";
	$x = 0;
	foreach ($sip_profile_settings as $row) {
		echo "			<tr>\n";
		echo "				<input type='hidden' name='sip_profile_settings[$x][sip_profile_setting_uuid]' value=\"".escape($row["sip_profile_setting_uuid"])."\">\n";
		echo "				<input type='hidden' name='sip_profile_settings[$x][sip_profile_uuid]' value=\"".escape($row["sip_profile_uuid"])."\">\n";
		echo "				<td class=\"vtablerow\" style=\"\" onclick=\"label_to_form('label_sip_profile_setting_name_$x','sip_profile_setting_name_$x');\" nowrap=\"nowrap\">\n";
		echo "					&nbsp; <label id='label_sip_profile_setting_name_$x'>".escape($row["sip_profile_setting_name"])."</label>\n";
		echo "					<input id='sip_profile_setting_name_$x' class='formfld' style='display: none;' type='text' name='sip_profile_settings[$x][sip_profile_setting_name]' maxlength='255' value=\"".escape($row["sip_profile_setting_name"])."\">\n";
		echo "				</td>\n";
		echo "				<td class=\"vtablerow\" style=\"\" onclick=\"label_to_form('label_sip_profile_setting_value_$x','sip_profile_setting_value_$x');\" nowrap=\"nowrap\">\n";
		echo "					<label id='label_sip_profile_setting_value_$x'>".escape(substr($row["sip_profile_setting_value"],0,22))." &nbsp;</label>\n";
		echo "					<input id='sip_profile_setting_value_$x' class='formfld' style='display: none;' type='text' name='sip_profile_settings[$x][sip_profile_setting_value]' maxlength='255' value=\"".escape($row["sip_profile_setting_value"])."\">\n";
		echo "				</td>\n";
		echo "				<td class=\"vtablerow\" style=\"\" onclick=\"label_to_form('label_sip_profile_setting_enabled_$x','sip_profile_setting_enabled_$x');\" nowrap=\"nowrap\">\n";
		echo "					<label id='label_sip_profile_setting_enabled_$x'>".escape($row["sip_profile_setting_enabled"])."</label>\n";
		echo "					<select id='sip_profile_setting_enabled_$x' class='formfld' style='display: none;' name='sip_profile_settings[$x][sip_profile_setting_enabled].'>\n";
		echo "						<option value=''></option>\n";
		if ($row['sip_profile_setting_enabled'] == "true") {
			echo "						<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "						<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($row['sip_profile_setting_enabled'] == "false") {
			echo "						<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "						<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "					</select>\n";
		echo "				</td>\n";
		echo "				<td class=\"vtablerow\" style=\"\" onclick=\"label_to_form('label_sip_profile_setting_description_$x','sip_profile_setting_description_$x');\" nowrap=\"nowrap\">\n";
		echo "					<label id='label_sip_profile_setting_description_$x'>".escape($row["sip_profile_setting_description"])."&nbsp;</label>\n";
		echo "					<input id='sip_profile_setting_description_$x' class='formfld' style='display: none;' type='text' name='sip_profile_settings[$x][sip_profile_setting_description]' maxlength='255' value=\"".escape($row["sip_profile_setting_description"])."\">\n";
		echo "				</td>\n";
		echo "				<td class='list_control_icons' style='width: 25px;'>\n";
		if (strlen($row["sip_profile_setting_name"]) > 0) {
			echo "					<a href=\"sip_profile_setting_delete.php?id=".escape($row["sip_profile_setting_uuid"])."&amp;sip_profile_uuid=".urlencode($sip_profile_uuid)."&amp;a=delete\" alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">".$v_link_label_delete."</a>\n";
		}
		echo "				</td>\n";
		echo "			</tr>\n";
		//convert last empty labels to form elements
		if ($row["sip_profile_setting_name"] == '' && $row["sip_profile_setting_value"] == '' && $row["sip_profile_setting_enabled"] == '' && $row["sip_profile_setting_description"] == '') {
			echo "<script>\n";
			echo "	label_to_form('label_sip_profile_setting_name_$x','sip_profile_setting_name_$x');\n";
			echo "	label_to_form('label_sip_profile_setting_value_$x','sip_profile_setting_value_$x');\n";
			echo "	label_to_form('label_sip_profile_setting_enabled_$x','sip_profile_setting_enabled_$x');\n";
			echo "	label_to_form('label_sip_profile_setting_description_$x','sip_profile_setting_description_$x');\n";
			echo "</script>\n";
		}
		$x++;
		$x++;
	}
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_hostname']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_hostname' maxlength='255' value=\"".escape($sip_profile_hostname)."\">\n";
	echo "<br />\n";
	echo $text['description-sip_profile_hostname']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='sip_profile_enabled'>\n";
	if ($sip_profile_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($sip_profile_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-sip_profile_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <textarea class='formfld' type='text' name='sip_profile_description'>".escape($sip_profile_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-sip_profile_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='sip_profile_uuid' value='".escape($sip_profile_uuid)."'>\n";
	}
	echo "				<br>\n";
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
