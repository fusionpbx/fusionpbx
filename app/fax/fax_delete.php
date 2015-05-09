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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_extension_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get value and set it as a php variable
	if (count($_GET)>0) {
		$fax_uuid = check_str($_GET["id"]);
	}

//delete the fax extension
	if (strlen($fax_uuid) > 0) {

		//get the dialplan uuid
			$sql = "select * from v_fax ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and fax_uuid = '$fax_uuid' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			while($row = $prep_statement->fetch(PDO::FETCH_ASSOC)) {
				$dialplan_uuid = $row['dialplan_uuid'];
			}

		//delete the fax entry
			$sql = "delete from v_fax ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and fax_uuid = '$fax_uuid' ";
			$db->query($sql);
			unset($sql);

		//delete the dialplan entry
			$sql = "delete from v_dialplans ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
			//echo $sql."<br>\n";
			$db->query($sql);
			unset($sql);

		//delete the dialplan details
			$sql = "delete from v_dialplan_details ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
			//echo $sql."<br>\n";
			$db->query($sql);
			unset($sql);

		//syncrhonize configuration
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);
	}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: fax.php");
	return;

?>