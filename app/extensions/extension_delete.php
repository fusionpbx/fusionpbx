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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('extension_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//check for the id
	if (count($_GET)>0) {
		$id = $_GET["id"];
	}
	if (strlen($id)>0) {
		//delete the extension
			$sql = "delete from v_extensions ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and extension_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($prep_statement, $sql);

			$sql = "delete from v_extension_users ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and extension_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($prep_statement, $sql);

		//synchronize configuration
			if (is_readable($_SESSION['switch']['extensions']['dir'])) {
				require_once "app/extensions/resources/classes/extension.php";
				$extension = new extension;
				$extension->xml();
			}
	}


$_SESSION["message"] = $text['message-delete'];
header("Location: extensions.php");
return;

?>