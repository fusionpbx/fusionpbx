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

	Call Block is written by Gerrit Visser <gerrit308@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('call_block_edit') || permission_exists('call_block_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add from cdr
	if (isset($_REQUEST["cdr_id"])) {

		$action = "cdr_add";
		$cdr_uuid = check_str($_REQUEST["cdr_id"]);
		$call_block_name = check_str($_REQUEST["name"]);

		// get the caller id info from cdr that user chose
		$sql = "select ";
		if ($call_block_name == '') {
			$sql .= "caller_id_name, ";
		}
		$sql .= "caller_id_number ";
		$sql .= "from v_xml_cdr ";
		$sql .= "where uuid = '".$cdr_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetch();
		unset ($prep_statement);

		$call_block_name = ($call_block_name == '') ? $result["caller_id_name"] : $call_block_name;
		$call_block_number = $result["caller_id_number"];
		$call_block_enabled = "true";
		$block_call_action = "Reject";

		//ensure call block is enabled in the dialplan
		$sql = "update v_dialplans set ";
		$sql .= "dialplan_enabled = 'true' ";
		$sql .= "where ";
		$sql .= "app_uuid = 'b1b31930-d0ee-4395-a891-04df94599f1f' and ";
		$sql .= "domain_uuid = '".$domain_uuid."' and ";
		$sql .= "dialplan_enabled <> 'true' ";
		$db->exec(check_sql($sql));
		unset($sql);

		// insert call block record
		$sql = "insert into v_call_block ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "call_block_uuid, ";
		$sql .= "call_block_name, ";
		$sql .= "call_block_number, ";
		$sql .= "call_block_count, ";
		$sql .= "call_block_action, ";
		$sql .= "call_block_enabled, ";
		$sql .= "date_added ";
		$sql .= ") ";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'".$_SESSION['domain_uuid']."', ";
		$sql .= "'".uuid()."', ";
		$sql .= "'".$call_block_name."', ";
		$sql .= "'".$call_block_number."', ";
		$sql .= "0, ";
		$sql .= "'".$block_call_action."', ";
		$sql .= "'".$call_block_enabled."', ";
		$sql .= "'".time()."' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

		$_SESSION["message"] = $text['label-add-complete'];

	}

header("Location: call_block.php");
?>