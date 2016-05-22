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
	$language = new text;
	$text = $language->get();

//check for the ids
	if (is_array($_REQUEST) && sizeof($_REQUEST) > 0) {

		$extension_uuids = $_REQUEST["id"];
		foreach($extension_uuids as $extension_uuid) {
			$extension_uuid = check_str($extension_uuid);
			if ($extension_uuid != '') {
				//get the user_context
					$sql = "select extension, user_context from v_extensions ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and extension_uuid = '".$extension_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						$extension = $row["extension"];
						$user_context = $row["user_context"];
					}
					unset ($prep_statement);

				//delete the extension
					$sql = "delete from v_extensions ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and extension_uuid = '".$extension_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);

					$sql = "delete from v_extension_users ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and extension_uuid = '".$extension_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);
			}
		}

		//clear the cache
			$cache = new cache;
			$cache->delete("directory:".$extension."@".$user_context);

		//synchronize configuration
			if (is_readable($_SESSION['switch']['extensions']['dir'])) {
				$extension = new extension;
				$extension->xml();
			}
	}

//redirect the browser
	$_SESSION["message"] = $text['message-delete'];
	header("Location: extensions.php");
	return;

?>