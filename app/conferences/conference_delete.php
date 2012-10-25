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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('conference_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app/calls/app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];                
	}

if (count($_GET)>0) {
	$id = check_str($_GET["id"]);
}

if (strlen($id)>0) {

	//get the dialplan uuid
		$sql = "select * from v_conferences ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_uuid = '$id' ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		while($row = $prep_statement->fetch(PDO::FETCH_ASSOC)) {
			$dialplan_uuid = $row['dialplan_uuid'];
		}

	//delete conference
		$sql = "delete from v_conferences ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the dialplan entry
		$sql = "delete from v_dialplans ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$db->query($sql);
		unset($sql);

	//delete the dialplan details
		$sql = "delete from v_dialplan_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$db->query($sql);
		unset($sql);

	//syncrhonize configuration
		save_dialplan_xml();

	//apply settings reminder
		$_SESSION["reload_xml"] = true;
}

//redirect the user
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=conferences.php\">\n";
	echo "<div align='center'>\n";
	echo "".$text['confirm-delete']."\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;

?>