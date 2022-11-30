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
	$search = $_REQUEST['search'];
	$default_setting_uuids = $_REQUEST["id"];

//toggle the setting
	$toggled = 0;
	if (is_array($default_setting_uuids) && sizeof($default_setting_uuids) > 0) {
		foreach ($default_setting_uuids as $default_setting_uuid) {
			//get current status
				$sql = "select default_setting_enabled from v_default_settings where default_setting_uuid = '".check_str($default_setting_uuid)."'";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_NAMED);
				$new_status = ($row['default_setting_enabled'] == 'true') ? 'false' : "true";
				unset ($sql, $prep_statement, $row);
			//set new status
				$sql = "update v_default_settings set default_setting_enabled = '".$new_status."' where default_setting_uuid = '".check_str($default_setting_uuid)."'";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset ($sql, $prep_statement);

			$toggled++;
		}
	}

//redirect the user
	if ($toggled > 0) {
		$_SESSION["message"] = $text['message-toggled'].': '.$toggled;
	}
	header("Location: default_settings.php".(($search != '') ? '?search='.$search : null));

?>