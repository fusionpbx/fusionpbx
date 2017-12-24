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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('number_translation_delete')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the variables
	if (count($_GET) > 0) {
		$number_translation_detail_uuid = check_str($_GET["id"]);
		$number_translation_uuid = check_str($_REQUEST["number_translation_uuid"]);
	}

//delete the number_translation detail
	if (strlen($number_translation_detail_uuid) > 0) {
		//delete child data
			$sql = "delete from v_number_translation_details ";
			$sql .= "where number_translation_detail_uuid = '$number_translation_detail_uuid' ";
			$db->query($sql);
			unset($sql);

		//update the number_translation xml
			$number_translations = new number_translation;
			$number_translations->xml();
	}

//save the message to a session variable
	messages::add($text['message-delete']);

//redirect the browser
	header("Location: number_translation_edit.php?id=$number_translation_uuid");
	exit;

?>
