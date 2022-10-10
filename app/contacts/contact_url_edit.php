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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_url_edit') || permission_exists('contact_url_add')) {
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
		$contact_url_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the contact uuid
	if (is_uuid($_GET["contact_uuid"])) {
		$contact_uuid = $_GET["contact_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$url_label = $_POST["url_label"];
		$url_label_custom = $_POST["url_label_custom"];
		$url_address = $_POST["url_address"];
		$url_primary = $_POST["url_primary"];
		$url_description = $_POST["url_description"];

		//use custom label if set
		$url_label = $url_label_custom != '' ? $url_label_custom : $url_label;
	}

//process the form data
	if (is_array($_POST) && @sizeof($_POST) != 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the uuid
			if ($action == "update") {
				$contact_url_uuid = $_POST["contact_url_uuid"];
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

				//if primary, unmark other primary urls
				if ($url_primary) {
					$sql = "update v_contact_urls set url_primary = 0 ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and contact_uuid = :contact_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['contact_uuid'] = $contact_uuid;
					$database = new database;
					$database->execute($sql, $parameters);
					unset($sql, $parameters);
				}

				if ($action == "add" && permission_exists('contact_url_add')) {
					$contact_url_uuid = uuid();
					$array['contact_urls'][0]['contact_url_uuid'] = $contact_url_uuid;

					message::add($text['message-add']);
				}

				if ($action == "update" && permission_exists('contact_url_edit')) {
					$array['contact_urls'][0]['contact_url_uuid'] = $contact_url_uuid;

					message::add($text['message-update']);
				}

				if (is_array($array) && @sizeof($array) != 0) {
					$array['contact_urls'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['contact_urls'][0]['contact_uuid'] = $contact_uuid;
					$array['contact_urls'][0]['url_label'] = $url_label;
					$array['contact_urls'][0]['url_address'] = $url_address;
					$array['contact_urls'][0]['url_primary'] = $url_primary ? 1 : 0;
					$array['contact_urls'][0]['url_description'] = $url_description;

					$database = new database;
					$database->app_name = 'contacts';
					$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
					$database->save($array);
					unset($array);
				}

				header("Location: contact_edit.php?id=".$contact_uuid);
				exit;

			}
	}

//pre-populate the form
	if (is_array($_GET) && @sizeof($_GET) != 0 && $_POST["persistformvar"] != "true") {
		$contact_url_uuid = $_GET["id"];
		$sql = "select * from v_contact_urls ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_url_uuid = :contact_url_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_url_uuid'] = $contact_url_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$url_label = $row["url_label"];
			$url_address = $row["url_address"];
			$url_primary = $row["url_primary"];
			$url_description = $row["url_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "update") {
		$document['title'] = $text['title-contact_url-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_url-add'];
	}
	require_once "resources/header.php";

//javascript to toggle input/select boxes
	echo "<script type='text/javascript'>";
	echo "	function toggle_custom(field) {";
	echo "		$('#'+field).toggle();";
	echo "		document.getElementById(field).selectedIndex = 0;";
	echo "		document.getElementById(field+'_custom').value = '';";
	echo "		$('#'+field+'_custom').toggle();";
	echo "		if ($('#'+field+'_custom').is(':visible')) { $('#'+field+'_custom').trigger('focus'); } else { $('#'+field).trigger('focus'); }";
	echo "	}";
	echo "</script>";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-contact_url-edit']."</b>";
	}
	else if ($action == "add") {
		echo "<b>".$text['header-contact_url-add']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contact_edit.php?id='.urlencode($contact_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		echo $text['description-contact_url-edit'];
	}
	else if ($action == "add") {
		echo $text['description-contact_url-add'];
	}
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-url_label']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (is_array($_SESSION["contact"]["url_label"])) {
		sort($_SESSION["contact"]["url_label"]);
		foreach($_SESSION["contact"]["url_label"] as $row) {
			$url_label_options[] = "<option value='".$row."' ".(($row == $url_label) ? "selected='selected'" : null).">".$row."</option>";
		}
		$url_label_found = (in_array($url_label, $_SESSION["contact"]["url_label"])) ? true : false;
	}
	else {
		$selected[$url_label] = "selected";
		$default_labels[] = $text['option-work'];
		$default_labels[] = $text['option-personal'];
		$default_labels[] = $text['option-other'];
		foreach ($default_labels as $default_label) {
			$url_label_options[] = "<option value='".$default_label."' ".$selected[$default_label].">".$default_label."</option>";
		}
		$url_label_found = (in_array($url_label, $default_labels)) ? true : false;
	}
	echo "	<select class='formfld' ".((!$url_label_found && $url_label != '') ? "style='display: none;'" : null)." name='url_label' id='url_label' onchange=\"getElementById('url_label_custom').value='';\">\n";
	echo "		<option value=''></option>\n";
	echo 		(is_array($url_label_options)) ? implode("\n", $url_label_options) : null;
	echo "	</select>\n";
	echo "	<input type='text' class='formfld' ".(($url_label_found || $url_label == '') ? "style='display: none;'" : null)." name='url_label_custom' id='url_label_custom' value=\"".((!$url_label_found) ? htmlentities($url_label) : null)."\">\n";
	echo "	<input type='button' id='btn_toggle_label' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('url_label');\">\n";
	echo "<br />\n";
	echo $text['description-url_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-url_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='url' name='url_address' maxlength='255' value=\"".escape($url_address)."\" placeholder='http://...'>\n";
	echo "<br />\n";
	echo $text['description-url_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-primary']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='url_primary' id='url_primary'>\n";
	echo "		<option value='0'>".$text['option-false']."</option>\n";
	echo "		<option value='1' ".(($url_primary) ? "selected" : null).">".$text['option-true']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-url_primary']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-url_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='url_description' maxlength='255' value=\"".escape($url_description)."\">\n";
	echo "<br />\n";
	echo $text['description-url_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='contact_url_uuid' value='".escape($contact_url_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>