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
 Portions created by the Initial Developer are Copyright (C) 2008-2016
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the ids
	if (is_array($_REQUEST) && sizeof($_REQUEST) > 0) {

		$voicemail_uuids = $_REQUEST["id"];
		foreach($voicemail_uuids as $voicemail_uuid) {
			$voicemail_uuid = check_str($voicemail_uuid);
			if ($voicemail_uuid != '') {
				//delete voicemail messages
					require_once "resources/classes/voicemail.php";
					$voicemail = new voicemail;
					$voicemail->db = $db;
					$voicemail->domain_uuid = $_SESSION['domain_uuid'];
					$voicemail->voicemail_uuid = $voicemail_uuid;
					$result = $voicemail->voicemail_delete();
					unset($voicemail);
			}
		}
	}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: voicemails.php");
	return;

?>