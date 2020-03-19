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
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_time_edit') || permission_exists('contact_time_add')) {
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
		$contact_time_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the contact uuid
	if (is_uuid($_GET["contact_uuid"])) {
		$contact_uuid = $_GET["contact_uuid"];
	}

//get http post variables and set them to php variables
	if (is_array($_POST) && @sizeof($_POST) != 0) {
		$time_start = $_POST["time_start"];
		$time_stop = $_POST["time_stop"];
		$time_description = $_POST["time_description"];
	}

//process the form data
	if (is_array($_POST) && @sizeof($_POST) != 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the uuid
			if ($action == "update") {
				$contact_time_uuid = $_POST["contact_time_uuid"];
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

				if ($action == "add" && permission_exists('contact_time_add')) {
					$contact_time_uuid = uuid();
					$array['contact_times'][0]['contact_time_uuid'] = $contact_time_uuid;

					message::add($text['message-add']);
				}

				if ($action == "update" && permission_exists('contact_time_edit')) {
					$array['contact_times'][0]['contact_time_uuid'] = $contact_time_uuid;

					message::add($text['message-update']);
				}

				if (is_array($array) && @sizeof($array) != 0) {
					$array['contact_times'][0]['domain_uuid'] = $domain_uuid;
					$array['contact_times'][0]['contact_uuid'] = $contact_uuid;
					$array['contact_times'][0]['user_uuid'] = $_SESSION["user"]["user_uuid"];
					$array['contact_times'][0]['time_start'] = $time_start;
					$array['contact_times'][0]['time_stop'] = $time_stop;
					$array['contact_times'][0]['time_description'] = $time_description;

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
		$contact_time_uuid = $_GET["id"];
		$sql = "select ct.*, u.username ";
		$sql .= "from v_contact_times as ct, v_users as u ";
		$sql .= "where ct.user_uuid = u.user_uuid ";
		$sql .= "and ct.domain_uuid = :domain_uuid ";
		$sql .= "and ct.contact_uuid = :contact_uuid ";
		$sql .= "and ct.user_uuid = :user_uuid ";
		$sql .= "and contact_time_uuid = :contact_time_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_uuid'] = $contact_uuid;
		$parameters['user_uuid'] = $_SESSION["user"]["user_uuid"];
		$parameters['contact_time_uuid'] = $contact_time_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		$time_start = $row["time_start"];
		$time_stop = $row["time_stop"];
		$time_description = $row["time_description"];
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "update") {
		$document['title'] = $text['title-contact_time_edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact_time_add'];
	}
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "update") {
		echo "<b>".$text['header-contact_time_edit']."</b>";
	}
	else if ($action == "add") {
		echo "<b>".$text['header-contact_time_add']."</b>";
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
	echo "	".$text['label-time_start']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld datetimesecpicker' data-toggle='datetimepicker' data-target='#time_start' type='text' name='time_start' id='time_start' style='min-width: 135px; width: 135px;' value='".$time_start."' onblur=\"$(this).datetimepicker('hide');\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-time_stop']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld datetimesecpicker' data-toggle='datetimepicker' data-target='#time_stop' type='text' name='time_stop' id='time_stop' style='min-width: 135px; width: 135px;' value='".$time_stop."' onblur=\"$(this).datetimepicker('hide');\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-time_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <textarea class='formfld' type='text' name='time_description' id='time_description' style='width: 400px; height: 100px;'>".$time_description."</textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='contact_time_uuid' value='".escape($contact_time_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>