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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

// check if included in another file
if (!$included) {
	//add multi-lingual support
	$language = new text;
	$text = $language->get();

	if (count($_GET)>0) {
		$contact_uuid = check_str($_GET["id"]);
	}
}

if (strlen($contact_uuid) > 0) {
	//delete addresses
		$sql = "delete from v_contact_addresses ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete phones
		$sql = "delete from v_contact_phones ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete emails
		$sql = "delete from v_contact_emails ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete urls
		$sql = "delete from v_contact_urls ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete notes
		$sql = "delete from v_contact_notes ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete relations
		$sql = "delete from v_contact_relations ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and ";
		$sql .= "( ";
		$sql .= "	contact_uuid = '".$contact_uuid."' ";
		$sql .= "	or relation_contact_uuid = '".$contact_uuid."' ";
		$sql .= ") ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete settings
		$sql = "delete from v_contact_settings ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete groups
		$sql = "delete from v_contact_groups ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);

	//delete a contact
		$sql = "delete from v_contacts ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '".$contact_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);
}

if (!$included) {
	$_SESSION["message"] = $text['message-delete'];
	header("Location: contacts.php");
	return;
}

?>
