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
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_note_edit') || permission_exists('contact_note_add')) {
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
		$contact_note_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the primary id for the contact
	if (is_uuid($_GET["contact_uuid"])) {
		$contact_uuid = $_GET["contact_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$contact_note = $_POST["contact_note"];
		$last_mod_date = $_POST["last_mod_date"];
		$last_mod_user = $_POST["last_mod_user"];
	}

//process the form data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the primary id for the contact note
			if ($action == "update") {
				$contact_note_uuid = $_POST["contact_note_uuid"];
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

				//add the note
					if ($action == "add" && permission_exists('contact_note_add')) {
						$contact_note_uuid = uuid();
						$array['contact_notes'][0]['contact_note_uuid'] = $contact_note_uuid;

						message::add($text['message-add']);
					}

				//update the note
					if ($action == "update" && permission_exists('contact_note_edit')) {
						$array['contact_notes'][0]['contact_note_uuid'] = $contact_note_uuid;

						message::add($text['message-update']);
					}

				//execute
					if (is_array($array) && @sizeof($array) != 0) {
						$array['contact_notes'][0]['contact_uuid'] = $contact_uuid;
						$array['contact_notes'][0]['domain_uuid'] = $domain_uuid;
						$array['contact_notes'][0]['contact_note'] = $contact_note;
						$array['contact_notes'][0]['last_mod_date'] = 'now()';
						$array['contact_notes'][0]['last_mod_user'] = $_SESSION['username'];

						$database = new database;
						$database->app_name = 'contacts';
						$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
						$database->save($array);
						unset($array);
					}

				//redirect
					header("Location: contact_edit.php?id=".escape($contact_uuid));
					exit;

			}
	}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$contact_note_uuid = $_GET["id"];
		$sql = "select * from v_contact_notes ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_note_uuid = :contact_note_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_note_uuid'] = $contact_note_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$contact_note = $row["contact_note"];
			$last_mod_date = $row["last_mod_date"];
			$last_mod_user = $row["last_mod_user"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "update") {
		$document['title'] = $text['title-contact_notes-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_notes-add'];
	}
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-contact_notes-edit']."</b>";
	}
	else if ($action == "add") {
		echo "<b>".$text['header-contact_notes-add']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contact_edit.php?id='.urlencode($contact_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_note']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "  <textarea class='formfld' name='contact_note' style='min-width: 100%; height: 400px;'>".$contact_note."</textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='contact_note_uuid' value='".escape($contact_note_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>