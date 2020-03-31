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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_edit')) {
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
		$contact_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($_POST['contact_uuid'])) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $_POST['contact_uuid'];

				switch ($_POST['action']) {
					case 'delete':
						if (permission_exists('contact_delete')) {
							$obj = new contacts;
							$obj->delete($array);

							header('Location: contacts.php');
							exit;
						}
				}
			}

		$user_uuid = $_POST["user_uuid"];
		$group_uuid = $_POST['group_uuid'];
		$contact_type = $_POST["contact_type"];
		$contact_organization = $_POST["contact_organization"];
		$contact_name_prefix = $_POST["contact_name_prefix"];
		$contact_name_given = $_POST["contact_name_given"];
		$contact_name_middle = $_POST["contact_name_middle"];
		$contact_name_family = $_POST["contact_name_family"];
		$contact_name_suffix = $_POST["contact_name_suffix"];
		$contact_nickname = $_POST["contact_nickname"];
		$contact_title = $_POST["contact_title"];
		$contact_category = $_POST["contact_category"];
		$contact_role = $_POST["contact_role"];
		$contact_time_zone = $_POST["contact_time_zone"];
		$contact_note = $_POST["contact_note"];
		$contact_users_delete = $_POST['contact_users_delete'];
		$contact_groups_delete = $_POST['contact_groups_delete'];
	}

//process the form data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the uuid
			if ($action == "update") {
				$contact_uuid = $_POST["contact_uuid"];
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
			//if (strlen($contact_type) == 0) { $msg .= $text['message-required'].$text['label-contact_type']."<br>\n"; }
			//if (strlen($contact_organization) == 0) { $msg .= $text['message-required'].$text['label-contact_organization']."<br>\n"; }
			//if (strlen($contact_name_prefix) == 0) { $msg .= $text['message-required'].$text['label-contact_name_prefix']."<br>\n"; }
			//if (strlen($contact_name_given) == 0) { $msg .= $text['message-required'].$text['label-contact_name_given']."<br>\n"; }
			//if (strlen($contact_name_middle) == 0) { $msg .= $text['message-required'].$text['label-contact_name_middle']."<br>\n"; }
			//if (strlen($contact_name_family) == 0) { $msg .= $text['message-required'].$text['label-contact_name_family']."<br>\n"; }
			//if (strlen($contact_name_suffix) == 0) { $msg .= $text['message-required'].$text['label-contact_name_suffix']."<br>\n"; }
			//if (strlen($contact_nickname) == 0) { $msg .= $text['message-required'].$text['label-contact_nickname']."<br>\n"; }
			//if (strlen($contact_title) == 0) { $msg .= $text['message-required'].$text['label-contact_title']."<br>\n"; }
			//if (strlen($contact_role) == 0) { $msg .= $text['message-required'].$text['label-contact_role']."<br>\n"; }
			//if (strlen($contact_time_zone) == 0) { $msg .= $text['message-required'].$text['label-contact_time_zone']."<br>\n"; }
			//if (strlen($contact_note) == 0) { $msg .= $text['message-required'].$text['label-contact_note']."<br>\n"; }
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

				//add the contact
					if ($action == "add" && permission_exists('contact_add')) {
						$contact_uuid = uuid();
						$array['contacts'][0]['contact_uuid'] = $contact_uuid;

						message::add($text['message-add']);
					}

				//update the contact
					if ($action == "update") {
						$array['contacts'][0]['contact_uuid'] = $contact_uuid;

						message::add($text['message-update']);
					}

				//create array
					if (is_array($array) && @sizeof($array) != 0) {
						$array['contacts'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contacts'][0]['contact_type'] = $contact_type;
						$array['contacts'][0]['contact_organization'] = $contact_organization;
						$array['contacts'][0]['contact_name_prefix'] = $contact_name_prefix;
						$array['contacts'][0]['contact_name_given'] = $contact_name_given;
						$array['contacts'][0]['contact_name_middle'] = $contact_name_middle;
						$array['contacts'][0]['contact_name_family'] = $contact_name_family;
						$array['contacts'][0]['contact_name_suffix'] = $contact_name_suffix;
						$array['contacts'][0]['contact_nickname'] = $contact_nickname;
						$array['contacts'][0]['contact_title'] = $contact_title;
						$array['contacts'][0]['contact_category'] = $contact_category;
						$array['contacts'][0]['contact_role'] = $contact_role;
						$array['contacts'][0]['contact_time_zone'] = $contact_time_zone;
						$array['contacts'][0]['contact_note'] = $contact_note;
						$array['contacts'][0]['last_mod_date'] = 'now()';
						$array['contacts'][0]['last_mod_user'] = $_SESSION['username'];

						$p = new permissions;
					}

				//assign the contact to the user that added the contact
					if ($action == "add" && !permission_exists('contact_user_add')) {
						$user_uuid = $_SESSION["user_uuid"];
					}

				//add user to contact users table
					if (is_uuid($user_uuid) && (permission_exists('contact_user_add') || $action == "add")) {
						$contact_user_uuid = uuid();
						$array['contact_users'][0]['domain_uuid'] = $domain_uuid;
						$array['contact_users'][0]['contact_user_uuid'] = $contact_user_uuid;
						$array['contact_users'][0]['contact_uuid'] = $contact_uuid;
						$array['contact_users'][0]['user_uuid'] = $user_uuid;

						$p->add('contact_user_add', 'temp');
					}

				//assign the contact to the group
					if (is_uuid($group_uuid) && permission_exists('contact_group_add')) {
						$contact_group_uuid = uuid();
						$array['contact_groups'][0]['contact_group_uuid'] = $contact_group_uuid;
						$array['contact_groups'][0]['domain_uuid'] = $domain_uuid;
						$array['contact_groups'][0]['contact_uuid'] = $contact_uuid;
						$array['contact_groups'][0]['group_uuid'] = $group_uuid;

						$p->add('contact_group_add', 'temp');
					}

				//execute
					if (is_array($array) && @sizeof($array) != 0) {
						$database = new database;
						$database->app_name = 'contacts';
						$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
						$database->save($array);
						unset($array);

						$p->delete('contact_user_add', 'temp');
						$p->delete('contact_group_add', 'temp');
					}

				//delete checked contact properties
					$array = array();
					if (permission_exists('contact_phone_delete')) { $contact_properties['contact_phones'] = $_POST['contact_phones']; }
					if (permission_exists('contact_address_delete')) { $contact_properties['contact_addresses'] = $_POST['contact_addresses']; }
					if (permission_exists('contact_email_delete')) { $contact_properties['contact_emails'] = $_POST['contact_emails']; }
					if (permission_exists('contact_url_delete')) { $contact_properties['contact_urls'] = $_POST['contact_urls']; }
					//if (permission_exists('contact_extension_delete')) { $contact_properties['contact_extensions'] = $_POST['contact_extensions']; }
					if (permission_exists('contact_relation_delete')) { $contact_properties['contact_relations'] = $_POST['contact_relations']; }
					if (permission_exists('contact_note_delete')) { $contact_properties['contact_notes'] = $_POST['contact_notes']; }
					if (permission_exists('contact_time_delete')) { $contact_properties['contact_times'] = $_POST['contact_times']; }
					if (permission_exists('contact_setting_delete')) { $contact_properties['contact_settings'] = $_POST['contact_settings']; }
					if (permission_exists('contact_attachment_delete')) { $contact_properties['contact_attachments'] = $_POST['contact_attachments']; }

					if (@sizeof($contact_properties) != 0) {
						$obj = new contacts;
						$obj->contact_uuid = $contact_uuid;
						$obj->delete_properties($contact_properties);
					}

				//remove checked users
					if (
						$action == 'update'
						&& permission_exists('contact_user_delete')
						&& is_array($contact_users_delete)
						&& @sizeof($contact_users_delete) != 0
						) {
						$obj = new contacts;
						$obj->contact_uuid = $contact_uuid;
						$obj->delete_users($contact_users_delete);
					}

				//remove checked groups
					if (
						$action == 'update'
						&& permission_exists('contact_group_delete')
						&& is_array($contact_groups_delete)
						&& @sizeof($contact_groups_delete) != 0
						) {
						$obj = new contacts;
						$obj->contact_uuid = $contact_uuid;
						$obj->delete_groups($contact_groups_delete);
					}

				//redirect the browser
					header("Location: contact_edit.php?id=".urlencode($contact_uuid));
					exit;

			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$contact_uuid = $_GET["id"];
		$sql = "select * from v_contacts ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_uuid = :contact_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_uuid'] = $contact_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$contact_type = $row["contact_type"];
			$contact_organization = $row["contact_organization"];
			$contact_name_prefix = $row["contact_name_prefix"];
			$contact_name_given = $row["contact_name_given"];
			$contact_name_middle = $row["contact_name_middle"];
			$contact_name_family = $row["contact_name_family"];
			$contact_name_suffix = $row["contact_name_suffix"];
			$contact_nickname = $row["contact_nickname"];
			$contact_title = $row["contact_title"];
			$contact_category = $row["contact_category"];
			$contact_role = $row["contact_role"];
			$contact_time_zone = $row["contact_time_zone"];
			$contact_note = $row["contact_note"];
		}
		unset($sql, $parameters, $row);
	}

//get the users array
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//determine if contact assigned to a user
	if (is_array($users) && sizeof($users) != 0) {
		foreach ($users as $user) {
			if ($user['contact_uuid'] == $contact_uuid) {
				$contact_user_uuid = $user['user_uuid'];
				break;
			}
		}
	}

//get the users assigned to this contact
	$sql = "select u.username, u.user_uuid, a.contact_user_uuid from v_contacts as c, v_users as u, v_contact_users as a ";
	$sql .= "where c.contact_uuid = :contact_uuid ";
	$sql .= "and c.domain_uuid = :domain_uuid ";
	$sql .= "and u.user_uuid = a.user_uuid ";
	$sql .= "and c.contact_uuid = a.contact_uuid ";
	$sql .= "order by u.username asc ";
	$parameters['contact_uuid'] = $contact_uuid;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$contact_users_assigned = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the assigned groups of this contact
	$sql = "select g.*, cg.contact_group_uuid ";
	$sql .= "from v_groups as g, v_contact_groups as cg ";
	$sql .= "where cg.group_uuid = g.group_uuid ";
	$sql .= "and cg.domain_uuid = :domain_uuid ";
	$sql .= "and cg.contact_uuid = :contact_uuid ";
	$sql .= "and cg.group_uuid <> :group_uuid ";
	$sql .= "order by g.group_name asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$parameters['group_uuid'] = $_SESSION["user_uuid"];
	$database = new database;
	$contact_groups_assigned = $database->select($sql, $parameters, 'all');
	if (is_array($contact_groups_assigned) && @sizeof($contact_groups_assigned) != 0) {
		foreach ($contact_groups_assigned as $field) {
			$contact_groups[] = "'".$field['group_uuid']."'";
		}
	}
	unset($sql, $parameters);

//get the available groups to this contact
	$sql = "select group_uuid, group_name from v_groups ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	if (is_array($contact_groups) && @sizeof($contact_groups) != 0) {
		$sql .= "and group_uuid not in (".implode(',', $contact_groups).") ";
	}
	$sql .= "order by group_name asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$contact_groups_available = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $contact_groups);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "update") {
		$document['title'] = $text['title-contact-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-contact-add'];
	}
	require_once "resources/header.php";

//determine qr branding
	if ($_SESSION['theme']['qr_brand_type']['text'] == 'image' && $_SESSION['theme']['qr_brand_image']['text'] != '') {
		echo "<img id='img-buffer' style='display: none;' src='".$_SESSION["theme"]["qr_brand_image"]["text"]."'>";
		$qr_option = "image: $('#img-buffer')[0],";
		$qr_mode = '4';
		$qr_size = '0.2';
	}
	else if ($_SESSION['theme']['qr_brand_type']['text'] == 'text' && $_SESSION['theme']['qr_brand_text']['text'] != '') {
		$qr_option = 'label: "'.$_SESSION['theme']['qr_brand_text']['text'].'"';
		$qr_mode = '2';
		$qr_size = '0.05';
	}
	else {
		echo "<img id='img-buffer' style='display: none;' src='".PROJECT_PATH."/themes/".$_SESSION["domain"]["template"]["name"]."/images/qr_code.png'>";
		$qr_option = "image: $('#img-buffer')[0],";
		$qr_mode = '4';
		$qr_size = '0.2';
	}

//qr code generation
	$_GET['type'] = "text";
	$qr_vcard = true;
	include "contacts_vcard.php";
	echo "<input type='hidden' id='qr_vcard' value=\"".$qr_vcard."\">";
	echo "<style>";
	echo "	#qr_code_container {";
	echo "		z-index: 999999; ";
	echo "		position: absolute; ";
	echo "		left: 0; ";
	echo "		top: 0; ";
	echo "		right: 0; ";
	echo "		bottom: 0; ";
	echo "		text-align: center; ";
	echo "		vertical-align: middle;";
	echo "	}";
	echo "	#qr_code {";
	echo "		display: block; ";
	echo "		width: 650px; ";
	echo "		height: 650px; ";
	echo "		-webkit-box-shadow: 0px 1px 20px #888; ";
	echo "		-moz-box-shadow: 0px 1px 20px #888; ";
	echo "		box-shadow: 0px 1px 20px #888;";
	echo "	}";
	echo "</style>";
	echo "<script src='".PROJECT_PATH."/resources/jquery/jquery-qrcode.min.js'></script>";
	echo "<script language='JavaScript' type='text/javascript'>";
	echo "	$(document).ready(function() {";
	echo "		$('#qr_code').qrcode({ ";
	echo "			render: 'canvas', ";
	echo "			minVersion: 6, ";
	echo "			maxVersion: 40, ";
	echo "			ecLevel: 'H', ";
	echo "			size: 650, ";
	echo "			radius: 0.2, ";
	echo "			quiet: 6, ";
	echo "			background: '#fff', ";
	echo "			mode: ".$qr_mode.", ";
	echo "			mSize: ".$qr_size.", ";
	echo "			mPosX: 0.5, ";
	echo "			mPosY: 0.5, ";
	echo "			text: document.getElementById('qr_vcard').value, ";
	echo "			".$qr_option;
	echo "		});";
	echo "	});";
	echo "</script>";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['header-contact-add']."</b>";
	}
	else if ($action == "update") {
		echo "<b>".$text['header-contact-edit']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-sm-dn','style'=>'margin-right: 15px;','link'=>'contacts.php']);
	if ($action == "update") {
		if (permission_exists('contact_time_add')) {
			//detect timer state (and start time)
			$sql = "select ";
			$sql .= "time_start ";
			$sql .= "from v_contact_times ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and user_uuid = :user_uuid ";
			$sql .= "and contact_uuid = :contact_uuid ";
			$sql .= "and time_start is not null ";
			$sql .= "and time_stop is null ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
			$parameters['contact_uuid'] = $contact_uuid;
			$database = new database;
			$time_start = $database->select($sql, $parameters, 'column');
			$btn_style = $time_start ? 'color: #fff; background-color: #3693df; background-image: none;' : null;
			unset($sql, $parameters);
			echo button::create(['type'=>'button','label'=>$text['button-timer'],'icon'=>'clock','style'=>$btn_style,'title'=>$time_start,'collapse'=>'hide-sm-dn','onclick'=>"window.open('contact_timer.php?domain_uuid=".urlencode($domain_uuid)."&contact_uuid=".urlencode($contact_uuid)."','contact_time_".escape($contact_uuid)."','width=300, height=375, top=30, left='+(screen.width - 350)+', menubar=no, scrollbars=no, status=no, toolbar=no, resizable=no');"]);
		}
		echo button::create(['type'=>'button','label'=>$text['button-qr_code'],'icon'=>'qrcode','collapse'=>'hide-sm-dn','onclick'=>"$('#qr_code_container').fadeIn(400);"]);
		echo button::create(['type'=>'button','label'=>$text['button-vcard'],'icon'=>'address-card','collapse'=>'hide-sm-dn','link'=>'contacts_vcard.php?id='.urlencode($contact_uuid).'&type=download']);
	}
	if ($action == "update" && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/invoices')) {
		echo button::create(['type'=>'button','label'=>$text['button-invoices'],'icon'=>'file-invoice-dollar','collapse'=>'hide-sm-dn','link'=>'../invoices/invoices.php?id='.urlencode($contact_uuid)]);
	}
	if ($action == "update" && is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/certificates')) {
		echo button::create(['type'=>'button','label'=>$text['button-certificate'],'icon'=>'certificate','collapse'=>'hide-sm-dn','link'=>'../certificates/index.php?name='.urlencode($contact_name_given." ".$contact_name_family)]);
	}
	if ($action == "update" && permission_exists('user_edit') && is_uuid($contact_user_uuid)) {
		echo button::create(['type'=>'button','label'=>$text['button-user'],'icon'=>'user','collapse'=>'hide-sm-dn','link'=>'../../core/users/user_edit.php?id='.urlencode($contact_user_uuid)]);
	}
	if (
		$action == "update" && (
		permission_exists('contact_phone_add') ||
		permission_exists('contact_address_add') ||
		permission_exists('contact_email_add') ||
		permission_exists('contact_url_add') ||
		permission_exists('contact_relation_add') ||
		permission_exists('contact_note_add') ||
		permission_exists('contact_time_add') ||
		permission_exists('contact_setting_add') ||
		permission_exists('contact_attachment_add')
		)) {
		echo 		"<select class='formfld' style='width: auto; margin-left: 15px;' id='select_add' onchange=\"document.location.href='contact_' + (this.options[this.selectedIndex].value) + '_edit.php?contact_uuid=".urlencode($contact_uuid)."';\">\n";
		echo "			<option value=''>".$text['button-add']."...</option>\n";
		if (permission_exists('contact_phone_add')) { echo "<option value='phone'>".$text['label-phone_number']."</option>\n"; }
		if (permission_exists('contact_address_add')) { echo "<option value='address'>".$text['label-address_address']."</option>\n"; }
		if (permission_exists('contact_email_add')) { echo "<option value='email'>".$text['label-email']."</option>\n"; }
		if (permission_exists('contact_url_add')) { echo "<option value='url'>".$text['label-url']."</option>\n"; }
		if (permission_exists('contact_relation_add')) { echo "<option value='relation'>".$text['label-contact_relation_label']."</option>\n"; }
		if (permission_exists('contact_note_add')) { echo "<option value='note'>".$text['label-contact_note']."</option>\n"; }
		if (permission_exists('contact_time_add')) { echo "<option value='time'>".$text['label-time_time']."</option>\n"; }
		if (permission_exists('contact_setting_add')) { echo "<option value='setting'>".$text['label-setting']."</option>\n"; }
		if (permission_exists('contact_attachment_add')) { echo "<option value='attachment'>".$text['label-attachment']."</option>\n"; }
		echo "		</select>";
	}
	if (
		$action == "update" && (
		permission_exists('contact_delete') ||
		permission_exists('contact_user_delete') ||
		permission_exists('contact_group_delete') ||
		permission_exists('contact_phone_delete') ||
		permission_exists('contact_address_delete') ||
		permission_exists('contact_email_delete') ||
		permission_exists('contact_url_delete') ||
		permission_exists('contact_relation_delete') ||
		permission_exists('contact_note_delete') ||
		permission_exists('contact_time_delete') ||
		permission_exists('contact_setting_delete') ||
		permission_exists('contact_attachment_delete')
		)) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','collapse'=>'hide-sm-dn','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if (permission_exists('contact_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>($action != 'update' ?: 'margin-left: 15px;'),'collapse'=>'hide-sm-dn','onclick'=>"document.getElementById('frm').submit();"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (
		$action == "update" && (
		permission_exists('contact_delete') ||
		permission_exists('contact_user_delete') ||
		permission_exists('contact_group_delete') ||
		permission_exists('contact_phone_delete') ||
		permission_exists('contact_address_delete') ||
		permission_exists('contact_email_delete') ||
		permission_exists('contact_url_delete') ||
		permission_exists('contact_relation_delete') ||
		permission_exists('contact_note_delete') ||
		permission_exists('contact_time_delete') ||
		permission_exists('contact_setting_delete') ||
		permission_exists('contact_attachment_delete')
		)) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	if ($action == "add") {
		echo $text['description-contact-add']."\n";
	}
	else if ($action == "update") {
		echo $text['description-contact-edit']."\n";
	}
	echo "<br /><br />\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
	echo "<tr>\n";
	echo "<td valign='top' align='left' nowrap='nowrap'>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_type']."\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["type"])) {
			sort($_SESSION["contact"]["type"]);
			echo "	<select class='formfld' name='contact_type'>\n";
			echo "		<option value=''></option>\n";
			foreach($_SESSION["contact"]["type"] as $type) {
				echo "	<option value='".escape($type)."' ".(($type == $contact_type) ? "selected='selected'" : null).">".escape($type)."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<select class='formfld' name='contact_type'>\n";
			echo "		<option value=''></option>\n";
			echo "		<option value='customer' ".(($contact_type == "customer") ? "selected='selected'" : null).">".$text['option-contact_type_customer']."</option>\n";
			echo "		<option value='contractor' ".(($contact_type == "contractor") ? "selected='selected'" : null).">".$text['option-contact_type_contractor']."</option>\n";
			echo "		<option value='friend' ".(($contact_type == "friend") ? "selected='selected'" : null).">".$text['option-contact_type_friend']."</option>\n";
			echo "		<option value='lead' ".(($contact_type == "lead") ? "selected='selected'" : null).">".$text['option-contact_type_lead']."</option>\n";
			echo "		<option value='member' ".(($contact_type == "member") ? "selected='selected'" : null).">".$text['option-contact_type_member']."</option>\n";
			echo "		<option value='family' ".(($contact_type == "family") ? "selected='selected'" : null).">".$text['option-contact_type_family']."</option>\n";
			echo "		<option value='subscriber' ".(($contact_type == "subscriber") ? "selected='selected'" : null).">".$text['option-contact_type_subscriber']."</option>\n";
			echo "		<option value='supplier' ".(($contact_type == "supplier") ? "selected='selected'" : null).">".$text['option-contact_type_supplier']."</option>\n";
			echo "		<option value='provider' ".(($contact_type == "provider") ? "selected='selected'" : null).">".$text['option-contact_type_provider']."</option>\n";
			echo "		<option value='user' ".(($contact_type == "user") ? "selected='selected'" : null).">".$text['option-contact_type_user']."</option>\n";
			echo "		<option value='volunteer' ".(($contact_type == "volunteer") ? "selected='selected'" : null).">".$text['option-contact_type_volunteer']."</option>\n";
			echo "	</select>\n";
		}
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_organization']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_organization' maxlength='255' value=\"".escape($contact_organization)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_prefix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_prefix' maxlength='255' value=\"".escape($contact_name_prefix)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_given']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_given' maxlength='255' value=\"".escape($contact_name_given)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_middle']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_middle' maxlength='255' value=\"".escape($contact_name_middle)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_family']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_family' maxlength='255' value=\"".escape($contact_name_family)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_name_suffix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_name_suffix' maxlength='255' value=\"".escape($contact_name_suffix)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_nickname']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_nickname' maxlength='255' value=\"".escape($contact_nickname)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_title']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["title"])) {
			sort($_SESSION["contact"]["title"]);
			echo "	<select class='formfld' name='contact_title'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["title"] as $title) {
				echo "	<option value='".escape($title)."' ".(($title == $contact_title) ? "selected='selected'" : null).">".escape($title)."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' type='text' name='contact_title' maxlength='255' value=\"".escape($contact_title)."\">\n";
		}
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_category']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["category"])) {
			sort($_SESSION["contact"]["category"]);
			echo "	<select class='formfld' name='contact_category'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["category"] as $category) {
				echo "	<option value='".escape($category)."' ".(($category == $contact_category) ? "selected='selected'" : null).">".escape($category)."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' type='text' name='contact_category' maxlength='255' value=\"".escape($contact_category)."\">\n";
		}
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_role']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($_SESSION["contact"]["role"])) {
			sort($_SESSION["contact"]["role"]);
			echo "	<select class='formfld' name='contact_role'>\n";
			echo "	<option value=''></option>\n";
			foreach($_SESSION["contact"]["role"] as $role) {
				echo "	<option value='".escape($role)."' ".(($role == $contact_role) ? "selected='selected'" : null).">".escape($role)."</option>\n";
			}
			echo "	</select>\n";
		}
		else {
			echo "	<input class='formfld' type='text' name='contact_role' maxlength='255' value=\"".escape($contact_role)."\">\n";
		}
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_time_zone']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='contact_time_zone' maxlength='255' value=\"".escape($contact_time_zone)."\">\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('contact_user_edit')) {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-users']."</td>";
			echo "		<td class='vtable' align='left'>";
			echo "			<table border='0' cellpadding='0' cellspacing='0' style='width: 100%;'>\n";
			if ($action == "update" && is_array($contact_users_assigned) && @sizeof($contact_users_assigned) != 0) {
				echo "				<tr>\n";
				echo "					<td class='vtable'>".$text['label-username']."</td>\n";
				if ($contact_users_assigned && permission_exists('contact_user_delete')) {
					echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_users', 'delete_toggle_users');\" onmouseout=\"swap_display('delete_label_users', 'delete_toggle_users');\">\n";
					echo "						<span id='delete_label_users'>".$text['label-delete']."</span>\n";
					echo "						<span id='delete_toggle_users'><input type='checkbox' id='checkbox_all_users' name='checkbox_all' onclick=\"edit_all_toggle('users');\"></span>\n";
					echo "					</td>\n";
				}
				echo "				</tr>\n";
				foreach ($contact_users_assigned as $x => $field) {
					echo "			<tr>\n";
					echo "				<td class='vtable'>".escape($field['username'])."</td>\n";
					if ($contact_users_assigned && permission_exists('contact_user_delete')) {
						if (is_uuid($field['contact_user_uuid'])) {
							echo "			<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
							echo "				<input type='checkbox' name='contact_users_delete[".$x."][checked]' value='true' class='chk_delete checkbox_users' onclick=\"edit_delete_action('users');\">\n";
							echo "				<input type='hidden' name='contact_users_delete[".$x."][uuid]' value='".escape($field['contact_user_uuid'])."' />\n";
						}
						else {
							echo "			<td>";
						}
						echo "			</td>\n";
					}
					echo "			</tr>\n";
				}
			}
			if (permission_exists('contact_user_add')) {
				echo "			<tr>\n";
				echo "				<td class='vtable' style='border-bottom: none;' colspan='2'>\n";
				echo "					<select name='user_uuid' class='formfld' style='width: auto;'>\n";
				echo "						<option value=''></option>\n";
				foreach ($users as $field) {
					if (in_array($field['user_uuid'], array_column($contact_users_assigned, 'user_uuid'))) { continue; } //skip users already assigned
					echo "						<option value='".escape($field['user_uuid'])."'>".escape($field['username'])."</option>\n";
				}
				echo "					</select>";
				if ($action == "update") {
					echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add']]);
				}
				unset($users);
				echo "				</td>\n";
				echo "			<tr>\n";
			}
			echo "			</table>\n";
			echo "			".$text['description-users']."\n";
			echo "		</td>";
			echo "	</tr>";
		}

		if (permission_exists('contact_group_view')) {
			echo "<tr>";
			echo "	<td class='vncell' valign='top'>".$text['label-groups']."</td>";
			echo "	<td class='vtable'>";
			echo "		<table border='0' cellpadding='0' cellspacing='0' style='width: 100%;'>\n";
			if (is_array($contact_groups_assigned) && @sizeof($contact_groups_assigned) != 0) {
				echo "			<tr>\n";
				echo "				<td class='vtable'>".$text['label-group']."</td>\n";
				if ($contact_groups_assigned && permission_exists('contact_group_delete')) {
					echo "				<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_groups', 'delete_toggle_groups');\" onmouseout=\"swap_display('delete_label_groups', 'delete_toggle_groups');\">\n";
					echo "					<span id='delete_label_groups'>".$text['label-delete']."</span>\n";
					echo "					<span id='delete_toggle_groups'><input type='checkbox' id='checkbox_all_groups' name='checkbox_all' onclick=\"edit_all_toggle('groups');\"></span>\n";
					echo "				</td>\n";
				}
				echo "			</tr>\n";
				foreach ($contact_groups_assigned as $x => $field) {
					if (strlen($field['group_name']) > 0) {
						echo "			<tr>\n";
						echo "				<td class='vtable'>".escape($field['group_name'])."</td>\n";
						if (permission_exists('contact_group_delete')) {
							if (is_uuid($field['contact_group_uuid'])) {
								echo "				<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
								echo "					<input type='checkbox' name='contact_groups_delete[".$x."][checked]' value='true' class='chk_delete checkbox_groups' onclick=\"edit_delete_action('groups');\">\n";
								echo "					<input type='hidden' name='contact_groups_delete[".$x."][uuid]' value='".escape($field['contact_group_uuid'])."' />\n";
							}
							else {
								echo "				<td>";
							}
							echo "				</td>\n";
						}
						echo "			</tr>\n";
					}
				}
			}

			if (permission_exists('contact_group_add')) {
				if (is_array($contact_groups_available) && @sizeof($contact_groups_available) != 0) {
					echo "			<tr>\n";
					echo "				<td class='vtable' style='border-bottom: none;' colspan='2'>\n";
					echo "					<select name='group_uuid' class='formfld' style='width: auto; margin-right: 3px;'>\n";
					echo "						<option value=''></option>\n";
					foreach ($contact_groups_available as $field) {
						if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show superadmin group to superadmins
						if ($field['group_name'] == "admin" && (!if_group("superadmin") && !if_group("admin"))) { continue; }	//only show admin group to admins
						echo "						<option value='".escape($field['group_uuid'])."'>".escape($field['group_name'])."</option>\n";
					}
					echo "					</select>";
					if ($action == "update") {
						echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add']]);
					}
					echo "				</td>\n";
					echo "			</tr>\n";
				}
			}

			echo "		</table>\n";
			echo "		".$text['description-groups']."\n";

			echo "	</td>";
			echo "</tr>";
		}

		echo "<tr>\n";
		echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "		".$text['label-contact_note']."\n";
		echo "	</td>\n";
		echo "	<td class='vtable' align='left'>\n";
		echo "		<textarea class='formfld' style='width: 100%; height: 160px;' name='contact_note'>".$contact_note."</textarea>\n";
		echo "	</td>\n";
		echo "</tr>\n";

		echo "</table>";

	echo "</td>\n";

	if ($action == "update") {
		echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "<td width='100%' valign='top'>\n";

		if (permission_exists('contact_phone_view')) { require "contact_phones.php"; }
		if (permission_exists('contact_address_view')) { require "contact_addresses.php"; }
		if (permission_exists('contact_email_view')) { require "contact_emails.php"; }
		if (permission_exists('contact_url_view')) { require "contact_urls.php"; }
		if (permission_exists('contact_extension_view')) { require "contact_extensions.php"; }
		if (permission_exists('contact_relation_view')) { require "contact_relations.php"; }
		if (permission_exists('contact_note_view')) { require "contact_notes.php"; }
		if (permission_exists('contact_time_view')) { require "contact_times.php"; }
		if (permission_exists('contact_setting_view')) { require "contact_settings.php"; }
		if (permission_exists('contact_attachment_view')) { require "contact_attachments.php"; }

		echo "</td>\n";
	}

	echo "</tr>\n";
	echo "</table>\n";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>";

//hide the delete button when nothing to delete
	if (
		$action == 'update' &&
		!permission_exists('contact_delete') && (
		(!is_array($contact_users_assigned) || @sizeof($contact_users_assigned) == 0) &&
		(!is_array($contact_groups_assigned) || @sizeof($contact_groups_assigned) == 0) &&
		(!is_array($contact_phones) || @sizeof($contact_phones) == 0) &&
		(!is_array($contact_addresses) || @sizeof($contact_addresses) == 0) &&
		(!is_array($contact_emails) || @sizeof($contact_emails) == 0) &&
		(!is_array($contact_urls) || @sizeof($contact_urls) == 0) &&
		(!is_array($contact_extensions) || @sizeof($contact_extensions) == 0) &&
		(!is_array($contact_relations) || @sizeof($contact_relations) == 0) &&
		(!is_array($contact_notes) || @sizeof($contact_notes) == 0) &&
		(!is_array($contact_times) || @sizeof($contact_times) == 0) &&
		(!is_array($contact_settings) || @sizeof($contact_settings) == 0) &&
		(!is_array($contact_attachments) || @sizeof($contact_attachments) == 0)
		)) {
		echo "<script>document.getElementsByName('btn_delete')[0].style.display='none';</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>