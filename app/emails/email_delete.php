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
	Copyright (C) 2008-2015
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('email_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted values, if any
	$email_uuid = $_REQUEST["id"];

	if ($email_uuid != '') {
		$sql = "delete from v_emails ";
		$sql .= "where email_uuid = '".$email_uuid."' ";
		if (permission_exists('emails_all') && $_REQUEST['showall'] == 'true') {
			$sql .= "";
		} else {
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
		}
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql, $prep_statement);

	//set message
		if ($_SESSION["message"] == '') {
			$_SESSION["message"] = $text['message-delete'];
		}
	}

//redirect user
	header("Location: emails.php");

?>