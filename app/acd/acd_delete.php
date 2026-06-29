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
	Portions created by the Initial Developer are Copyright (C) 2010-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	BlueCloud <support@blueuc.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('acd_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//validate the token
	$token = new token;
	if (!$token->validate($_SERVER['PHP_SELF'])) {
		message::add($text['message-invalid_token'], 'negative');
		header('Location: acd.php');
		exit;
	}

//accept queue_uuid(s) from GET
	if (!empty($_GET['id'])) {
		$id_list = $_GET['id'];

		//build records array matching the format the class delete() method expects
		$records = [];
		$ids = explode(',', $id_list);
		foreach ($ids as $idx => $raw_uuid) {
			$clean_uuid = trim($raw_uuid);
			if (is_uuid($clean_uuid)) {
				$records[$idx]['checked'] = 'true';
				$records[$idx]['uuid']    = $clean_uuid;
			}
		}

		//call delete
		if (!empty($records)) {
			$obj = new acd;
			$obj->delete($records);
		}
	}

//redirect back to the list
	header('Location: acd.php');
	exit;

?>
