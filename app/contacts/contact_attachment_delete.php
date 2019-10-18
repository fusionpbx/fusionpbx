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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('contact_attachment_delete')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set as variables
	$contact_attachment_uuid = $_GET["id"];
	$contact_uuid = $_GET["contact_uuid"];

//delete the record
	if (is_uuid($contact_attachment_uuid) && is_uuid($contact_uuid)) {
		$array['contact_attachments'][0]['contact_attachment_uuid'] = $contact_attachment_uuid;
		$array['contact_attachments'][0]['domain_uuid'] = $domain_uuid;
		$array['contact_attachments'][0]['contact_uuid'] = $contact_uuid;

		$database = new database;
		$database->app_name = 'contacts';
		$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
		$database->delete($array);
		unset($array);

		message::add($text['message-delete']);
	}

//redirect
	header("Location: contact_edit.php?id=".$contact_uuid);
	exit;

?>