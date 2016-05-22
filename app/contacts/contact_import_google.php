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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/functions/google_get_groups.php";
require_once "resources/functions/google_get_contacts.php";

if (permission_exists('contact_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//handle import
if ($_POST['a'] == 'import') {
	if (sizeof($_POST['group_id']) > 0) {
		//get contact ids for those in the submitted groups
		if (sizeof($_SESSION['contact_auth']['google']) > 0) {
			foreach ($_SESSION['contact_auth']['google'] as $contact['id'] => $contact) {
				foreach ($contact['groups'] as $contact_group['id'] => $meh) {
					if (in_array($contact_group['id'], $_POST['group_id'])) {
						$import_ids[] = $contact['id'];
					}
				}
			}
		}
	}

	if (sizeof($_POST['contact_id']) > 0) {
		foreach ($_POST['contact_id'] as $contact_id) {
			$import_ids[] = $contact_id;
		}
	}

	//iterate selected contact ids, insert contact into database
	$contacts_imported = 0;
	$contacts_skipped = 0;
	$contacts_replaced = 0;

	if (sizeof($import_ids) > 0) {

		$import_ids = array_unique($import_ids);
		foreach ($import_ids as $contact_id) {

			//check for duplicate contact (already exists, previously imported, etc)
			$sql = "select contact_uuid from v_contact_settings ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and contact_setting_category = 'google' ";
			$sql .= "and contact_setting_subcategory = 'id' ";
			$sql .= "and contact_setting_value = '".$contact_id."' ";
			$sql .= "and contact_setting_enabled = 'true' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($result['contact_uuid'] != '') {
				$duplicate_exists = true;
				$duplicate_contact_uuid = $result['contact_uuid'];
			}
			else {
				$duplicate_exists = false;
			}
			unset($sql, $prep_statement, $result);

			//skip importing contact
			if ($duplicate_exists && $_POST['import_duplicates'] == 'skip') {
				$contacts_skipped++;
				continue;
			}
			//replace contact (delete before inserts below)
			else if ($duplicate_exists && $_POST['import_duplicates'] == 'replace') {
				$contact_uuid = $duplicate_contact_uuid;
				$included = true;
				require_once "contact_delete.php";
				unset($contact_uuid, $duplicate_contact_uuid);
				$contacts_replaced++;
			}

			//extract contact record from array using contact id
			$contact = $_SESSION['contact_auth']['google'][$contact_id];

			//insert contact
			$contact_uuid = uuid();
			$sql = "insert into v_contacts ";
			$sql .= "( ";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "contact_type, ";
			$sql .= "contact_organization, ";
			$sql .= "contact_name_prefix, ";
			$sql .= "contact_name_given, ";
			$sql .= "contact_name_middle, ";
			$sql .= "contact_name_family, ";
			$sql .= "contact_name_suffix, ";
			$sql .= "contact_nickname, ";
			$sql .= "contact_title, ";
			$sql .= "contact_category, ";
			$sql .= "contact_note ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "( ";
			$sql .= "'".$_SESSION['domain_uuid']."', ";
			$sql .= "'".$contact_uuid."', ";
			$sql .= "'".check_str($_POST['import_type'])."', ";
			$sql .= "'".check_str($contact['organization'])."', ";
			$sql .= "'".check_str($contact['name_prefix'])."', ";
			$sql .= "'".check_str($contact['name_given'])."', ";
			$sql .= "'".check_str($contact['name_middle'])."', ";
			$sql .= "'".check_str($contact['name_family'])."', ";
			$sql .= "'".check_str($contact['name_suffix'])."', ";
			$sql .= "'".check_str($contact['nickname'])."', ";
			$sql .= "'".check_str($contact['title'])."', ";
			$sql .= "'".check_str($_POST['import_category'])."', ";
			$sql .= "'".check_str($contact['notes'])."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			//set sharing
			if ($_POST['import_shared'] != 'true') {
				$sql = "insert into v_contact_groups ";
				$sql .= "( ";
				$sql .= "contact_group_uuid, ";
				$sql .= "domain_uuid, ";
				$sql .= "contact_uuid, ";
				$sql .= "group_uuid ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "( ";
				$sql .= "'".uuid()."', ";
				$sql .= "'".$_SESSION['domain_uuid']."', ";
				$sql .= "'".$contact_uuid."', ";
				$sql .= "'".$_SESSION["user_uuid"]."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);
			}

			//insert emails
			if ($_POST['import_fields']['email'] && sizeof($contact['emails']) > 0) {
				foreach ($contact['emails'] as $contact_email) {
					$sql = "insert into v_contact_emails ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "contact_email_uuid, ";
					$sql .= "email_label, ";
					$sql .= "email_address, ";
					$sql .= "email_primary ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$_SESSION['domain_uuid']."', ";
					$sql .= "'".$contact_uuid."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'".check_str($contact_email['label'])."', ";
					$sql .= "'".check_str($contact_email['address'])."', ";
					$sql .= (($contact_email['primary']) ? 1 : 0)." ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}
			}

			//insert numbers
			if ($_POST['import_fields']['number'] && sizeof($contact['numbers']) > 0) {
				foreach ($contact['numbers'] as $contact_number) {
					$sql = "insert into v_contact_phones ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "contact_phone_uuid, ";
					$sql .= "phone_type_voice, ";
					$sql .= "phone_type_fax, ";
					$sql .= "phone_label, ";
					$sql .= "phone_number, ";
					$sql .= "phone_primary ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$domain_uuid."', ";
					$sql .= "'".$contact_uuid."', ";
					$sql .= "'".uuid()."', ";
					$sql .= ((substr_count(strtoupper($contact_number['label']), strtoupper($text['label-fax'])) == 0) ? 1 : 'null').", ";
					$sql .= ((substr_count(strtoupper($contact_number['label']), strtoupper($text['label-fax'])) != 0) ? 1 : 'null').", ";
					$sql .= "'".check_str($contact_number['label'])."', ";
					$sql .= "'".check_str($contact_number['number'])."', ";
					$sql .= ((sizeof($contact['numbers']) == 1) ? 1 : 0)." ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}
			}

			//insert urls
			if ($_POST['import_fields']['url'] && sizeof($contact['urls']) > 0) {
				foreach ($contact['urls'] as $contact_url) {
					$sql = "insert into v_contact_urls ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "contact_url_uuid, ";
					$sql .= "url_label, ";
					$sql .= "url_address, ";
					$sql .= "url_primary ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$_SESSION['domain_uuid']."', ";
					$sql .= "'".$contact_uuid."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'".check_str($contact_url['label'])."', ";
					$sql .= "'".check_str($contact_url['url'])."', ";
					$sql .= ((sizeof($contact['urls']) == 1) ? 1 : 0)." ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}
			}

			//insert addresses
			if ($_POST['import_fields']['address'] && sizeof($contact['addresses']) > 0) {
				foreach ($contact['addresses'] as $contact_address) {
					$sql = "insert into v_contact_addresses ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "contact_address_uuid, ";
					$sql .= "address_type, ";
					$sql .= "address_label, ";
					$sql .= "address_street, ";
					$sql .= "address_extended, ";
					$sql .= "address_community, ";
					$sql .= "address_locality, ";
					$sql .= "address_region, ";
					$sql .= "address_postal_code, ";
					$sql .= "address_country, ";
					$sql .= "address_primary ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$_SESSION['domain_uuid']."', ";
					$sql .= "'".$contact_uuid."', ";
					$sql .= "'".uuid()."', ";
					if (substr_count(strtoupper($contact_address['label']), strtoupper($text['option-home'])) != 0) {
						$sql .= "'home', "; // vcard address type
					}
					else if (substr_count(strtoupper($contact_address['label']), strtoupper($text['option-work'])) != 0) {
						$sql .= "'work', "; // vcard address type
					}
					else {
						$sql .= "'', ";
					}
					$sql .= "'".check_str($contact_address['label'])."', ";
					$sql .= "'".check_str($contact_address['street'])."', ";
					$sql .= "'".check_str($contact_address['extended'])."', ";
					$sql .= "'".check_str($contact_address['community'])."', ";
					$sql .= "'".check_str($contact_address['locality'])."', ";
					$sql .= "'".check_str($contact_address['region'])."', ";
					$sql .= "'".check_str($contact_address['postal_code'])."', ";
					$sql .= "'".check_str($contact_address['country'])."', ";
					$sql .= ((sizeof($contact['addresses']) == 1) ? 1 : 0)." ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}
			}

			//add google contact id, etag and updated date to contact settings
			$contact['updated'] = str_replace('T', ' ', $contact['updated']);
			$contact['updated'] = str_replace('Z', '', $contact['updated']);
			$sql = "insert into v_contact_settings ";
			$sql .= "(";
			$sql .= "contact_setting_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "contact_setting_category, ";
			$sql .= "contact_setting_subcategory, ";
			$sql .= "contact_setting_name, ";
			$sql .= "contact_setting_value, ";
			$sql .= "contact_setting_order, ";
			$sql .= "contact_setting_enabled ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "('".uuid()."', '".$contact_uuid."', '".$_SESSION['domain_uuid']."', 'sync', 'source', 'array', 'google', 0, 'true' )";
			$sql .= ",('".uuid()."', '".$contact_uuid."', '".$_SESSION['domain_uuid']."', 'google', 'id', 'text', '".check_str($contact_id)."', 0, 'true' )";
			$sql .= ",('".uuid()."', '".$contact_uuid."', '".$_SESSION['domain_uuid']."', 'google', 'updated', 'date', '".check_str($contact['updated'])."', 0, 'true' )";
			$sql .= ",('".uuid()."', '".$contact_uuid."', '".$_SESSION['domain_uuid']."', 'google', 'etag', 'text', '".check_str($contact['etag'])."', 0, 'true' )";
			$db->exec(check_sql($sql));
			unset($sql);

			$contacts_imported++;

		}

		$message = $text['message-contacts_imported']." ".$contacts_imported;
		if ($contacts_replaced > 0) { $message .= " (".$text['message_contacts_imported_replaced']." ".$contacts_replaced.")"; }
		if ($contacts_skipped > 0) { $message .= ", ".$text['message_contacts_imported_skipped']." ".$contacts_skipped; }
		$_SESSION["message"] = $message;
		header("Location: contacts.php");
		exit;

	}
	else {

		// no contacts imported
		$_SESSION['message_mood'] = 'negative';
		$_SESSION["message"] = $text['message-contacts_imported']." ".$contacts_imported;

	}
}

//*******************************************************************************************

//check if authenticated
if ($_SESSION['contact_auth']['token'] == '') {
	$_SESSION['contact_auth']['referer'] = substr($_SERVER["HTTP_REFERER"], strrpos($_SERVER["HTTP_REFERER"],'/')+1);
	header("Location: contact_auth.php?source=google&target=".substr($_SERVER["PHP_SELF"], strrpos($_SERVER["PHP_SELF"],'/')+1));
	exit;
}

unset($_SESSION['contact_auth']['source'], $_SESSION['contact_auth']['target']);

//get groups & contacts
$groups = google_get_groups($_SESSION['contact_auth']['token']);
$contacts = google_get_contacts($_SESSION['contact_auth']['token'], 1000);

//store in session variable for use on import
$_SESSION['contact_auth']['google'] = $contacts;

//include the header
$document['title'] = $text['title-contacts_import_google'];
require_once "resources/header.php";

echo "<table cellpadding='0' cellspacing='0' border='0' align='right'>";
echo "	<tr>";
echo "		<td style='text-align: right;'>";
echo "			<input type='button' class='btn' id='btn_back' onclick=\"document.location.href='contact_import.php';\" value=\"".$text['button-back']."\">";
echo "			<input type='button' class='btn' id='btn_refresh' onclick='document.location.reload();' value=\"".$text['button-reload']."\">";
echo "			<input type='button' class='btn' id='btn_signout' onclick=\"document.location.href='contact_auth.php?source=google&signout'\" value=\"".$text['button-sign_out']."\">";
echo "		</td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td style='text-align: right; white-space: nowrap; padding-top: 8px;'><span style='font-weight: bold; color: #000;'>".$_SESSION['contact_auth']['name']."</a> (<a href='https://www.google.com/contacts/#contacts' target='_blank'>".$_SESSION['contact_auth']['email']."</a>)"."</td>";
echo "	</tr>";
echo "</table>";
echo "<b>".$text['header-contacts_import_google']."</b>";
echo "<br><br>";
echo $text['description-contacts_import_google'];
echo "<br><br><br>";

$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

echo "<form name='frm_import' id='frm_import' method='post'>\n";
echo "<input type='hidden' name='a' value='import'>\n";

echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
echo "	".$text['label-import_fields']."\n";
echo "</td>\n";
echo "<td width='70%' class='vtable' align='left'>\n";
echo "	<input type='checkbox' disabled='disabled' checked>&nbsp;".$text['label-contact_name']."&nbsp;\n";
echo "	<input type='checkbox' disabled='disabled' checked>&nbsp;".$text['label-contact_organization']."&nbsp;\n";
echo "	<input type='checkbox' name='import_fields[email]' id='field_email' value='1' checked><label for='field_email'>&nbsp;".$text['label-contact_email']."</label>&nbsp;\n";
echo "	<input type='checkbox' name='import_fields[number]' id='field_number' value='1' checked><label for='field_number'>&nbsp;".$text['label-phone_number']."</label>&nbsp;\n";
echo "	<input type='checkbox' name='import_fields[url]' id='field_url' value='1' checked><label for='field_url'>&nbsp;".$text['label-contact_url']."</label>&nbsp;\n";
echo "	<input type='checkbox' name='import_fields[address]' id='field_address' value='1' checked><label for='field_address'>&nbsp;".$text['label-address_address']."</label>\n";
echo "<br />\n";
echo $text['description-import_fields']."\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
echo "	".$text['label-contact_type']."\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
if (is_array($_SESSION["contact"]["type"])) {
	sort($_SESSION["contact"]["type"]);
	echo "	<select class='formfld' name='import_type'>\n";
	echo "		<option value=''></option>\n";
	foreach($_SESSION["contact"]["type"] as $row) {
		echo "	<option value='".$row."'>".$row."</option>\n";
	}
	echo "	</select>\n";
}
else {
	echo "	<select class='formfld' name='import_type'>\n";
	echo "		<option value=''></option>\n";
	echo "		<option value='customer'>".$text['option-contact_type_customer']."</option>\n";
	echo "		<option value='contractor'>".$text['option-contact_type_contractor']."</option>\n";
	echo "		<option value='friend'>".$text['option-contact_type_friend']."</option>\n";
	echo "		<option value='lead'>".$text['option-contact_type_lead']."</option>\n";
	echo "		<option value='member'>".$text['option-contact_type_member']."</option>\n";
	echo "		<option value='family'>".$text['option-contact_type_family']."</option>\n";
	echo "		<option value='subscriber'>".$text['option-contact_type_subscriber']."</option>\n";
	echo "		<option value='supplier'>".$text['option-contact_type_supplier']."</option>\n";
	echo "		<option value='provider'>".$text['option-contact_type_provider']."</option>\n";
	echo "		<option value='user'>".$text['option-contact_type_user']."</option>\n";
	echo "		<option value='volunteer'>".$text['option-contact_type_volunteer']."</option>\n";
	echo "	</select>\n";
}
echo "<br />\n";
echo $text['description-contact_type_import']."\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
echo "	".$text['label-contact_category']."\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
if (is_array($_SESSION["contact"]["category"])) {
	sort($_SESSION["contact"]["category"]);
	echo "	<select class='formfld' name='import_category'>\n";
	echo "		<option value=''></option>\n";
	foreach($_SESSION["contact"]["category"] as $row) {
		echo "	<option value='".$row."'>".$row."</option>\n";
	}
	echo "	</select>\n";
}
else {
	echo "	<input class='formfld' type='text' name='import_category' maxlength='255'>\n";
}
echo "<br />\n";
echo $text['description-contact_category_import']."\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
echo "	".$text['label-shared']."\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<select class='formfld' name='import_shared' id='import_shared'>\n";
echo "		<option value='false'>".$text['option-false']."</option>\n";
echo "		<option value='true'>".$text['option-true']."</option>\n";
echo "	</select>\n";
echo "	<br />\n";
echo $text['description-shared_import']."\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
echo "    ".$text['label-import_duplicates']."\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "    <select class='formfld' style='width: 150px;' name='import_duplicates'>\n";
echo "    <option value='skip'>".$text['option-import_duplicates_skip']."</option>\n";
echo "    <option value='replace'>".$text['option-import_duplicates_replace']."</option>\n";
echo "    </select>\n";
echo "<br />\n";
echo $text['description-import_duplicates']."\n";
echo "</td>\n";
echo "</tr>\n";

echo "</table>";
echo "<br><br>";

//display groups
echo "<b>".$text['label-groups']."</b>";
echo "<br><br>";

echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";
echo "	<th style='width: 30px; text-align: center; padding: 0px;'>&nbsp;</th>";
echo "	<th>".$text['label-contact_name']."</th>\n";
echo "</tr>\n";

//determine contact count in groups
foreach ($contacts as $contact) {
	foreach ($contact['groups'] as $group_id => $meh) {
		$groups[$group_id]['count']++;
	}
}

$c = 0;
foreach ($groups as $group['id'] => $group) {
	if ($group['count'] > 0) {
		echo "<tr>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center; padding: 3px 0px 0px 0px;'><input type='checkbox' name='group_id[]' id='group_id_".$group['id']."' value='".$group['id']."'></td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' onclick=\"document.getElementById('group_id_".$group['id']."').checked = (document.getElementById('group_id_".$group['id']."').checked) ? false : true;\">".$group['name']." (".$group['count'].")</td>\n";
		echo "</tr>\n";
		$c=($c)?0:1;
	}
}
echo "</table>\n";
echo "<br>";

echo "<div style='text-align: right;'><input type='submit' class='btn' id='btn_submit' value=\"".$text['button-import']."\"></div>";

echo "<br>";

//display contacts
echo "<b>".$text['header-contacts']."</b>";
echo "<br><br>";

echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";
echo "	<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
echo "	<th>".$text['label-contact_name']."</th>\n";
echo "	<th>".$text['label-contact_organization']."</th>\n";
echo "	<th>".$text['label-contact_email']."</th>\n";
echo "	<th>".$text['label-phone_number']."</th>\n";
echo "	<th>".$text['label-contact_url']."</th>\n";
echo "	<th>".$text['label-address_address']."</th>\n";
echo "	<th>".$text['label-group']."</th>\n";
echo "</tr>\n";
$c = 0;
foreach ($contacts as $contact['id'] => $contact) {
	$contact_ids[] = $contact['id'];
	echo "<tr>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center; padding: 3px 0px 0px 0px;'><input type='checkbox' name='contact_id[]' id='contact_id_".$contact['id']."' value='".$contact['id']."'></td>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' onclick=\"document.getElementById('contact_id_".$contact['id']."').checked = (document.getElementById('contact_id_".$contact['id']."').checked) ? false : true;\">";
	$contact_name[] = $contact['name_prefix'];
	$contact_name[] = $contact['name_given'];
	$contact_name[] = $contact['name_middle'];
	$contact_name[] = $contact['name_family'];
	$contact_name[] = $contact['name_suffix'];
	echo "		".implode(' ', $contact_name)."&nbsp;";
	unset($contact_name);
	echo "	</td>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' style='max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>";
	echo "		".(($contact['title']) ? $contact['title']."<br>" : null).$contact['organization']."&nbsp;";
	echo "	</td>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' style='width: 15%; max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>";
	if (sizeof($contact['emails']) > 0) {
 		foreach ($contact['emails'] as $contact_email) {
 			$contact_emails[] = "<span style='font-size: 80%;'>".$contact_email['label'].":</span> <a href='mailto: ".$contact_email['address']."'>".$contact_email['address']."</a>";
 		}
		echo implode('<br>', $contact_emails);
		unset($contact_emails);
	} else { echo "&nbsp;"; }
	echo "	</td>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' style='width: 15%; max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>";
	if (sizeof($contact['numbers']) > 0) {
		foreach ($contact['numbers'] as $contact_number) {
			$contact_number_part = "<span style='font-size: 80%;'>".$contact_number['label'].":</span> ";
			if (substr_count(strtoupper($contact_number['label']), 'FAX') == 0) {
				$contact_number_part .= "<a href='javascript:void(0);' onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($contact_number['number'])."&src_cid_number=".urlencode($contact_number['number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($contact_number['number'])."&rec=false&ringback=us-ring&auto_answer=true');\">";
			}
			$contact_number_part .= format_phone($contact_number['number']);
			if (substr_count(strtoupper($contact_number['label']), 'FAX') == 0) {
				$contact_number_part .= "</a>";
			}
			$contact_numbers[] = $contact_number_part;
			unset($contact_number_part);
		}
		echo implode('<br>', $contact_numbers);
		unset($contact_numbers);
	} else { echo "&nbsp;"; }
	echo "	</td>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' style='width: 15%; max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>";
	if (sizeof($contact['urls']) > 0) {
		foreach ($contact['urls'] as $contact_url) {
			$contact_urls[] = "<span style='font-size: 80%;'>".$contact_url['label'].":</span> <a href='".$contact_url['url']."' target='_blank'>".str_replace("http://", "", str_replace("https://", "", $contact_url['url']))."</a>";
		}
		echo implode('<br>', $contact_urls);
		unset($contact_urls);
	} else { echo "&nbsp;"; }
	echo "	</td>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' style='width: 15%; max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>";
	if (sizeof($contact['addresses']) > 0) {
		foreach ($contact['addresses'] as $contact_address) {
			if ($contact_address['street'] != '') { $contact_address_parts[] = $contact_address['street']; }
			if ($contact_address['extended'] != '') { $contact_address_parts[] = $contact_address['extended']; }
			if ($contact_address['community'] != '') { $contact_address_parts[] = $contact_address['community']; }
			if ($contact_address['locality'] != '') { $contact_address_parts[] = $contact_address['locality']; }
			if ($contact_address['region'] != '') { $contact_address_parts[] = $contact_address['region']; }
			if ($contact_address['postal_code'] != '') { $contact_address_parts[] = $contact_address['postal_code']; }
			if ($contact_address['country'] != '') { $contact_address_parts[] = $contact_address['country']; }
			$contact_addresses[] = "<span style='font-size: 80%;'>".$contact_address['label'].":</span> ".implode(', ', $contact_address_parts);
			unset($contact_address_parts);
		}
		echo implode('<br>', $contact_addresses);
		unset($contact_addresses);
	} else { echo "&nbsp;"; }
	echo "	</td>\n";
	echo "	<td valign='top' class='".$row_style[$c]."' style='white-space: nowrap;'>";
	foreach ($contact['groups'] as $contact_group['id'] => $contact_group['name']) {
		$contact_groups[] = $contact_group['name'];
	}
	echo "		".implode('<br>', $contact_groups);
	unset($contact_groups);
	echo "	</td>\n";
	echo "</tr>\n";
	$c=($c)?0:1;
}
echo "</table>\n";
echo "<br>";

echo "<div style='text-align: right;'><input type='submit' class='btn' id='btn_submit' value=\"".$text['button-import']."\"></div>";

echo "</form>";
echo "<br><br>";

// check or uncheck all contact checkboxes
if (sizeof($contact_ids) > 0) {
	echo "<script>\n";
	echo "	function check(what) {\n";
	foreach ($contact_ids as $contact_id) {
		echo "	document.getElementById('contact_id_".$contact_id."').checked = (what == 'all') ? true : false;\n";
	}
	echo "	}\n";
	echo "</script>\n";
}

/*
echo "<pre>";
print_r($contacts);
echo "</pre>";
echo "<br><br>";

echo "<hr>";
echo "<br><br><b>SOURCE JSON DECODED ARRAY</b>...<br><br><pre>";
print_r($records);
echo "</pre>";
*/

//include the footer
require_once "resources/footer.php";




// used above
function curl_file_get_contents($url) {
	$curl = curl_init();
	$userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';

	curl_setopt($curl, CURLOPT_URL, $url);	//The URL to fetch. This can also be set when initializing a session with curl_init().
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);	//TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);	//The number of seconds to wait while trying to connect.
	curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);	//The contents of the "User-Agent: " header to be used in a HTTP request.
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);	//To follow any "Location: " header that the server sends as part of the HTTP header.
	curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);	//To automatically set the Referer: field in requests where it follows a Location: redirect.
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);	//The maximum number of seconds to allow cURL functions to execute.
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);	//To stop cURL from verifying the peer's certificate.

	$contents = curl_exec($curl);
	curl_close($curl);
	return $contents;
}
?>