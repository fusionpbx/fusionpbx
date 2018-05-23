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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('ivr_menu_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	if (is_array($_GET)) {
		$id = check_str($_GET["id"]);
	}

//delete the ivr menu
	if (is_uuid($id)) {

		//get the dialplan_uuid
			$sql = "select * from v_ivr_menus ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and ivr_menu_uuid = '".$id."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			if (is_array($result)) {
				foreach ($result as &$row) {
					$dialplan_uuid = $row["dialplan_uuid"];
				}
				unset ($sql,$result,$prep_statement);
			}

		//delete the dialplan
			$sql = "delete from v_dialplans ";
			$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
			$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset ($sql,$prep_statement);

		//delete the ivr menu options
			$sql = "delete from v_ivr_menu_options ";
			$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
			$sql .= "and ivr_menu_uuid = '".$id."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset ($sql,$prep_statement);

		//delete the ivr menu
			$sql = "delete from v_ivr_menus ";
			$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
			$sql .= "and ivr_menu_uuid = '".$id."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset ($sql,$prep_statement);

		//synchronize the xml config
			save_dialplan_xml();

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);
	}

//redirect the user
	messages::add($text['message-delete']);
	header("Location: ivr_menus.php");

?>
