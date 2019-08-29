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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
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

	$contact_uuid = $_GET["id"];
}

if (is_uuid($contact_uuid)) {

	//specify tables
		$tables[] = 'contact_addresses';
		$tables[] = 'contact_attachments';
		$tables[] = 'contact_emails';
		$tables[] = 'contact_groups';
		$tables[] = 'contact_notes';
		$tables[] = 'contact_phones';
		$tables[] = 'contact_relations';
		$tables[] = 'contact_settings';
		$tables[] = 'contact_times';
		$tables[] = 'contact_urls';
		$tables[] = 'contact_users';
		$tables[] = 'contacts';

	//create array from tables
		foreach ($tables as $table) {
			$array[$table][0]['contact_uuid'] = $contact_uuid;
			$array[$table][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		}

	//include reciprocal relationships
		$array['contact_relations'][1]['relation_contact_uuid'] = $contact_uuid;
		$array['contact_relations'][1]['domain_uuid'] = $_SESSION['domain_uuid'];

	//grant temp permissions
		$p = new permissions;
		$database = new database;
		foreach ($tables as $table) {
			$p->add($database->singular($table).'_delete', 'temp');
		}

	//execute
		$database = new database;
		$database->app_name = 'contacts';
		$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
		$database->delete($array);
		unset($array);

	//revoke temp permissions
		foreach ($tables as $table) {
			$p->delete($database->singular($table).'_delete', 'temp');
		}

	//set message
		message::add($text['message-delete']);
}

if (!$included) {
	header("Location: contacts.php");
	exit;
}

?>
