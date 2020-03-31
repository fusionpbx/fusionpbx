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
	if (permission_exists('contact_view')) {
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
		$contact_uuid = $_REQUEST["id"];
	}
	else {
		header("Location: contacts.php");
	}

//main contact details
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

//get the available users for this contact
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

//get the assigned users that can view this contact
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

//get the assigned groups that can view this contact
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

//get the available groups for this contact
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

//determine title name
	if ($contact_name_given || $contact_name_family) {
		$contact_name = $contact_name_prefix ? escape($contact_name_prefix).' ' : null;
		$contact_name .= $contact_name_given ? escape($contact_name_given).' ' : null;
		$contact_name .= $contact_name_middle ? escape($contact_name_middle).' ' : null;
		$contact_name .= $contact_name_family ? escape($contact_name_family).' ' : null;
		$contact_name .= $contact_name_suffix ? escape($contact_name_suffix).' ' : null;
	}
	else {
		$contact_name = $contact_organization;
	}

//show the header
	$document['title'] = $text['title-contact-edit'].($contact_name ? ': '.$contact_name : null);
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
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".($contact_name ? $contact_name : $text['header-contact-edit'])."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-sm-dn','style'=>'margin-right: 15px;','link'=>'contacts.php']);
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
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/invoices')) {
		echo button::create(['type'=>'button','label'=>$text['button-invoices'],'icon'=>'file-invoice-dollar','collapse'=>'hide-sm-dn','link'=>'../invoices/invoices.php?id='.urlencode($contact_uuid)]);
	}
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/certificates')) {
		echo button::create(['type'=>'button','label'=>$text['button-certificate'],'icon'=>'certificate','collapse'=>'hide-sm-dn','link'=>'../certificates/index.php?name='.urlencode($contact_name_given." ".$contact_name_family)]);
	}
	if (permission_exists('user_edit') && is_uuid($contact_user_uuid)) {
		echo button::create(['type'=>'button','label'=>$text['button-user'],'icon'=>'user','collapse'=>'hide-sm-dn','link'=>'../../core/users/user_edit.php?id='.urlencode($contact_user_uuid)]);
	}
	if (
		permission_exists('contact_phone_add') ||
		permission_exists('contact_address_add') ||
		permission_exists('contact_email_add') ||
		permission_exists('contact_url_add') ||
		permission_exists('contact_relation_add') ||
		permission_exists('contact_note_add') ||
		permission_exists('contact_time_add') ||
		permission_exists('contact_setting_add') ||
		permission_exists('contact_attachment_add')
		) {
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
	if (permission_exists('contact_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'id'=>'btn_edit','style'=>'margin-left: 15px;','collapse'=>'hide-sm-dn','link'=>'contact_edit.php?id='.urlencode($contact_uuid)]);
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

	if ($contact_title || $contact_organization) {
		echo ($contact_title ? '<i>'.$contact_title.'</i>' : null).($contact_title && $contact_organization ? ', ' : null).($contact_organization ? '<strong>'.$contact_organization.'</strong>' : null)."\n";
	}
	else {
		echo $contact_note."\n";
	}
	echo "<br /><br />\n";

	echo "<div class='grid' style='grid-gap: 15px; grid-template-columns: repeat(auto-fill, minmax(375px, 1fr));'>\n";

//general info
	echo "	<div class='box contact-details'>\n";
	echo "		<div class='grid contact-details'>\n";
	echo "			<div class='box'><b class='fas fa-user fa-fw fa-lg'></b></div>\n";
	echo "			<div class='box'>\n";
	echo "				<div class='grid' style='grid-template-columns: 70px auto;'>\n";
		//nickname
			if ($contact_nickname) {
				echo "<div class='box contact-details-label'>".$text['label-contact_nickname']."</div>\n";
				echo "<div class='box'>\"".escape($contact_nickname)."\"</div>\n";
			}
		//contact type
			if ($contact_type) {
				echo "<div class='box contact-details-label'>".$text['label-contact_type']."</div>\n";
				echo "<div class='box'>";
				if (is_array($_SESSION["contact"]["type"])) {
					sort($_SESSION["contact"]["type"]);
					foreach ($_SESSION["contact"]["type"] as $type) {
						if ($contact_type == $type) {
							echo escape($type);
						}
					}
				}
				else if ($text['option-contact_type_'.$contact_type]) {
					echo $text['option-contact_type_'.$contact_type];
				}
				else {
					echo escape($contact_type);
				}
				echo "</div>\n";
			}
		//category
			if ($contact_category) {
				echo "<div class='box contact-details-label'>".$text['label-contact_category']."</div>\n";
				echo "<div class='box'>";
				if (is_array($_SESSION["contact"]["category"])) {
					sort($_SESSION["contact"]["category"]);
					foreach ($_SESSION["contact"]["category"] as $category) {
						if ($contact_category == $category) {
							echo escape($category);
							break;
						}
					}
				}
				else {
					echo escape($contact_category);
				}
				echo "</div>\n";
			}
		//role
			if ($contact_role) {
				echo "<div class='box contact-details-label'>".$text['label-contact_role']."</div>\n";
				echo "<div class='box'>";
				if (is_array($_SESSION["contact"]["role"])) {
					sort($_SESSION["contact"]["role"]);
					foreach ($_SESSION["contact"]["role"] as $role) {
						if ($contact_role == $role) {
							echo escape($role);
							break;
						}
					}
				}
				else {
					echo escape($contact_role);
				}
				echo "</div>\n";
			}
		//time_zone
			if ($contact_time_zone) {
				echo "<div class='box contact-details-label'>".$text['label-contact_time_zone']."</div>\n";
				echo "<div class='box'>";
				echo $contact_time_zone."<br>\n";
				echo "</div>\n";
			}
		//users (viewing contact)
			if (permission_exists('contact_user_view') && is_array($contact_users_assigned) && @sizeof($contact_users_assigned) != 0) {
				echo "<div class='box contact-details-label'>".$text['label-users']."</div>\n";
				echo "<div class='box'>";
				foreach ($contact_users_assigned as $field) {
					echo escape($field['username'])."<br>\n";
				}
				echo "</div>\n";
			}
		//groups (viewing contact)
			if (permission_exists('contact_group_view') && is_array($contact_groups_assigned) && @sizeof($contact_groups_assigned) != 0) {
				echo "<div class='box contact-details-label'>".$text['label-groups']."</div>\n";
				echo "<div class='box'>";
				foreach ($contact_groups_assigned as $field) {
					echo escape($field['group_name'])."<br>\n";
				}
				echo "</div>\n";
			}
	echo "				</div>\n";
	echo "			</div>\n";
	echo "		</div>\n";
	echo "	</div>\n";

//numbers
	if (permission_exists('contact_phone_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['label-phone_numbers']."\"><b class='fas fa-hashtag fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require 'contact_phones_view.php';
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

//emails
	if (permission_exists('contact_email_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['label-emails']."\"><b class='fas fa-envelope fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require 'contact_emails_view.php';
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

//addresses
	if (permission_exists('contact_address_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['label-addresses']."\"><b class='fas fa-map-marker-alt fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require 'contact_addresses_view.php';
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

//urls
	if (permission_exists('contact_url_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['label-urls']."\"><b class='fas fa-link fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require "contact_urls_view.php";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

//relations
	if (permission_exists('contact_relation_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['header-contact_relations']."\"><b class='fas fa-project-diagram fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require "contact_relations_view.php";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

//attachments
	if (permission_exists('contact_attachment_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['label-attachments']."\"><b class='fas fa-paperclip fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require "contact_attachments_view.php";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

//times
	if (permission_exists('contact_time_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['header_contact_times']."\"><b class='fas fa-clock fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require "contact_times_view.php";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

//extensions
	if (permission_exists('contact_extension_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['label-contact_extensions']."\"><b class='fas fa-fax fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require "contact_extensions_view.php";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

	echo "</div>\n";
	echo "<div class='grid' style='margin-top: 15px; grid-template-columns: auto;'>\n";

//notes
	if (permission_exists('contact_note_view')) {
		echo "	<div class='box contact-details'>\n";
		echo "		<div class='grid contact-details'>\n";
		echo "			<div class='box' title=\"".$text['label-contact_notes']."\"><b class='fas fa-sticky-note fa-fw fa-lg'></b></div>\n";
		echo "			<div class='box'>\n";
		require "contact_notes_view.php";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";
	}

	echo "</div>\n";
	echo "<br><br>\n";

//include the footer
	require_once "resources/footer.php";

?>