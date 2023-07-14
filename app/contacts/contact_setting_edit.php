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
 Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_setting_edit') || permission_exists('contact_setting_add')) {
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
	$contact_setting_category = '';
	$contact_setting_subcategory = '';
	$contact_setting_name = '';
	$contact_setting_value = '';
	$contact_setting_order = '';
	$contact_setting_enabled = '';
	$contact_setting_description = '';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$contact_setting_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the contact uuid
	if (!empty($_GET["contact_uuid"]) && is_uuid($_GET["contact_uuid"])) {
		$contact_uuid = $_GET["contact_uuid"];
	}

//set the session domain uuid as a variable
	$domain_uuid = $_SESSION['domain_uuid'];

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$contact_setting_category = strtolower($_POST["contact_setting_category"]);
		$contact_setting_subcategory = strtolower($_POST["contact_setting_subcategory"]);
		$contact_setting_name = strtolower($_POST["contact_setting_name"]);
		$contact_setting_value = $_POST["contact_setting_value"];
		$contact_setting_order = $_POST["contact_setting_order"] ?? null;
		$contact_setting_enabled = strtolower($_POST["contact_setting_enabled"]);
		$contact_setting_description = $_POST["contact_setting_description"];
	}

//process the form data
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//set the uuid
			if ($action == "update") {
				$contact_setting_uuid = $_POST["contact_setting_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: contacts.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (empty($domain_setting_category)) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
			//if (empty($domain_setting_subcategory)) { $msg .= $text['message-required'].$text['label-subcategory']."<br>\n"; }
			//if (empty($domain_setting_name)) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
			//if (empty($domain_setting_value)) { $msg .= $text['message-required'].$text['label-value']."<br>\n"; }
			//if (empty($domain_setting_order)) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
			//if (empty($domain_setting_enabled)) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			//if (empty($domain_setting_description)) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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

		//add or update the database
			if (empty($_POST["persistformvar"])) {

				//set the order
					$contact_setting_order = $contact_setting_order != '' ? $contact_setting_order : null;

				//update last modified
					$array['contacts'][0]['contact_uuid'] = $contact_uuid;
					$array['contacts'][0]['domain_uuid'] = $domain_uuid;
					$array['contacts'][0]['last_mod_date'] = 'now()';
					$array['contacts'][0]['last_mod_user'] = $_SESSION['username'];

					$p = new permissions;
					$p->add('contact_edit', 'temp');

					$database = new database;
					$database->app_name = 'contacts';
					$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
					$database->save($array);
					unset($array);

					$p->delete('contact_edit', 'temp');

				//add the setting
					if ($action == "add" && permission_exists('contact_setting_add')) {
						$contact_setting_uuid = uuid();
						$array['contact_settings'][0]['contact_setting_uuid'] = $contact_setting_uuid;

						message::add($text['message-add']);
					}

				//update the setting
					if ($action == "update" && permission_exists('contact_setting_edit')) {
						$array['contact_settings'][0]['contact_setting_uuid'] = $contact_setting_uuid;

						message::add($text['message-update']);
					}

				//execute
					if (!empty($array)) {
						$array['contact_settings'][0]['contact_uuid'] = $contact_uuid;
						$array['contact_settings'][0]['domain_uuid'] = $domain_uuid;
						$array['contact_settings'][0]['contact_setting_category'] = $contact_setting_category;
						$array['contact_settings'][0]['contact_setting_subcategory'] = $contact_setting_subcategory;
						$array['contact_settings'][0]['contact_setting_name'] = $contact_setting_name;
						$array['contact_settings'][0]['contact_setting_value'] = $contact_setting_value;
						$array['contact_settings'][0]['contact_setting_order'] = $contact_setting_order;
						$array['contact_settings'][0]['contact_setting_enabled'] = $contact_setting_enabled;
						$array['contact_settings'][0]['contact_setting_description'] = $contact_setting_description;

						$database = new database;
						$database->app_name = 'contacts';
						$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
						$database->save($array);
						unset($array);
					}

				//redirect the browser
					header("Location: contact_edit.php?id=".escape($contact_uuid));
					exit;
			}
	}

//pre-populate the form
	if (!empty($_GET) && empty($_POST["persistformvar"])) {
		$contact_setting_uuid = $_GET["id"] ?? '';
		$sql = "select * from v_contact_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_setting_uuid = :contact_setting_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_setting_uuid'] = $contact_setting_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (!empty($row)) {
			$contact_setting_category = escape($row["contact_setting_category"]);
			$contact_setting_subcategory = escape($row["contact_setting_subcategory"]);
			$contact_setting_name = escape($row["contact_setting_name"]);
			$contact_setting_value = escape($row["contact_setting_value"]);
			$contact_setting_order = escape($row["contact_setting_order"]);
			$contact_setting_enabled = escape($row["contact_setting_enabled"]);
			$contact_setting_description = escape($row["contact_setting_description"]);
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "update") {
		$document['title'] = $text['title-contact_setting_edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-contact_setting_add'];
	}
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-contact_setting_edit']."</b>";
	}
	else if ($action == "add") {
		echo "<b>".$text['header-contact_setting_add']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contact_edit.php?id='.urlencode($contact_uuid ?? '')]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		echo $text['description-contact_setting_edit'];
	}
	if ($action == "add") {
		echo $text['description-contact_setting_add'];
	}
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_category' maxlength='255' value=\"".escape($contact_setting_category)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-contact_setting_category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_subcategory']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_subcategory' maxlength='255' value=\"".escape($contact_setting_subcategory)."\">\n";
	echo "<br />\n";
	echo $text['description-contact_setting_subcategory']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_name' maxlength='255' value=\"".escape($contact_setting_name)."\">\n";
	echo "<br />\n";
	echo $text['description-contact_setting_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_setting_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_value' maxlength='255' value=\"".escape($contact_setting_value)."\">\n";
	echo "<br />\n";
	echo $text['description-contact_setting_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($contact_setting_name == "array") {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
		echo "    ".$text['label-order']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select name='contact_setting_order' class='formfld'>\n";
		$i=0;
		while($i<=999) {
			$selected = ($i == $contact_setting_order) ? "selected" : null;
			if (strlen($i) == 1) {
				echo "		<option value='00$i' ".$selected.">00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "		<option value='0$i' ".$selected.">0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "		<option value='$i' ".$selected.">$i</option>\n";
			}
			$i++;
		}
		echo "	</select>\n";
		echo "	<br />\n";
		echo $text['description-order']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='contact_setting_enabled'>\n";
	if ($contact_setting_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($contact_setting_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='contact_setting_description' maxlength='255' value=\"".escape($contact_setting_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='contact_uuid' value='".!empty($contact_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='contact_setting_uuid' value='".$contact_setting_uuid."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
