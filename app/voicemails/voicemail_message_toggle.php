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
if (permission_exists('voicemail_message_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get submitted variables
	$voicemail_messages = $_REQUEST["voicemail_messages"];

//toggle the voicemail message
	$toggled = 0;
	if (is_array($voicemail_messages) && sizeof($voicemail_messages) > 0) {
		require_once "resources/classes/voicemail.php";
		foreach ($voicemail_messages as $voicemail_uuid => $voicemail_message_uuids) {
			foreach ($voicemail_message_uuids as $voicemail_message_uuid) {
				if (is_uuid($voicemail_uuid) && is_uuid($voicemail_message_uuid)) {
					$voicemail = new voicemail;
					$voicemail->db = $db;
					$voicemail->domain_uuid = $_SESSION['domain_uuid'];
					$voicemail->voicemail_uuid = $voicemail_uuid;
					$voicemail->voicemail_message_uuid = $voicemail_message_uuid;
					$result = $voicemail->message_toggle();
					unset($voicemail);
					$toggled++;
				}
			}
		}
	}

//set the referrer
	$http_referer = parse_url($_SERVER["HTTP_REFERER"]);
	$referer_path = $http_referer['path'];
	$referer_query = $http_referer['query'];

//redirect the user
	if ($toggled > 0) {
		message::add($text['message-toggled'].': '.$toggled);
	}
	if ($referer_path == PROJECT_PATH."/app/voicemails/voicemail_messages.php") {
		header("Location: voicemail_messages.php?".$referer_query);
	}
	else {
		header("Location: voicemails.php");
	}

?>