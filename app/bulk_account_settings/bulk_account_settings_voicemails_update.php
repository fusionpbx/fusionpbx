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
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('bulk_account_settings_voicemails')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//check for the ids
	if (is_array($_REQUEST) && sizeof($_REQUEST) > 0) {

		$voicemail_uuids = $_REQUEST["id"];
		$option_selected = $_REQUEST["option_selected"];
		$new_setting = $_REQUEST["new_setting"];
		foreach($voicemail_uuids as $voicemail_uuid) {
			$voicemail_uuid = check_str($voicemail_uuid);
			if ($voicemail_uuid != '') {
				//get the voicemails array
					$sql = "select * from v_voicemails ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and voicemail_uuid = '".$voicemail_uuid."' ";
					$database = new database;
					$database->select($sql);
					$voicemails = $database->result;
					if (is_array($voicemails)) { 
						foreach ($voicemails as &$row) {
							$voicemail = $row["voicemail"];
							//$user_context = $row["user_context"];
						}
						unset ($prep_statement);
					}

					$array["voicemails"][$i]["domain_uuid"] = $domain_uuid;
					$array["voicemails"][$i]["voicemail_uuid"] = $voicemail_uuid;
					$array["voicemails"][$i][$option_selected] = $new_setting;

					$database = new database;
					$database->app_name = 'bulk_account_settings';
					$database->app_uuid = null;
					$database->save($array);
					$message = $database->message;
				
					//echo "<pre>".print_r($message, true)."<pre>\n";
					//exit;
					
					unset($database,$array,$i);
			}
		}
	}

//redirect the browser
	$_SESSION["message"] = $text['message-update'];
	header("Location: bulk_account_settings_voicemails.php?option_selected=".$option_selected."");
	return;

?>
