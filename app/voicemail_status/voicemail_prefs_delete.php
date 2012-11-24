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
include "root.php";
require "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('voicemail_status_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (count($_GET)>0) {
	$id = $_GET["id"];
}

//pdo voicemail database connection
	include "includes/lib_pdo_vm.php";

//delete the data
	if (strlen($id)>0) {
		$sql = "delete from voicemail_prefs ";
		$sql .= "where domain = '".$_SESSION['domains'][$domain_uuid]['domain_name']."' ";
		$sql .= "and username = '$domain_uuid' ";
		$count = $db->exec(check_sql($sql));
		unset($sql);
	}

//add multi-lingual support
	echo "<!--\n";
	require_once "app_languages.php";
	echo "-->\n";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//redirect the user
	require "includes/require.php";
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=voicemail.php\">\n";
	echo "<div align='center'>\n";
	echo $text['label-prefs-delete']."\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;

?>