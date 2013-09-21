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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Sérgio Reis <uc@wavecom.pt>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
if (permission_exists('extension_add')) {
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

//set the http get/post variable(s) to a php variable
	if (isset($_REQUEST["id"]) && isset($_REQUEST["ext"])) {
		$group_name = check_str($_REQUEST["id"]);
		$group_new = check_str($_REQUEST["ext"]);
	}
	
//get the groups data
	$sql = "select * from v_groups ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and group_name = '$group_name' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$domain_uuid = $row["domain_uuid"];
		$group_description = $row["group_description"];
		$group_name = $row["group_name"];
	}
	unset ($prep_statement);
	
	//copy the groups
	$group_uuid = uuid();
	$sql = "insert into v_groups ";
	$sql .= "(";
	$sql .= "group_uuid, ";
	$sql .= "domain_uuid, ";
	$sql .= "group_name, ";
	$sql .= "group_description ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$group_uuid', ";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$group_new', ";
	$sql .= "'copy_$group_description' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);


//get the group permissions data
	$sql = "select * from v_group_permissions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and group_name = '$group_name' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$domain_uuid = $row["domain_uuid"];
		$permission_name = $row["permission_name"];
		$group_name = $row["group_name"];

		//copy the group permissions
		$group_permission_uuid = uuid();
		$sql = "insert into v_group_permissions ";
		$sql .= "(";
		$sql .= "group_permission_uuid, ";
		$sql .= "domain_uuid, ";
		$sql .= "permission_name, ";
		$sql .= "group_name ";	
		$sql .= ")";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$group_permission_uuid', ";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$permission_name', ";
		$sql .= "'$group_new' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);
	}
	unset ($prep_statement);

//redirect the user
	require_once "resources/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=groups.php\">\n";
	echo "<br />\n";
	echo "<div align='center'>\n";
	echo "	<table width='40%'>\n";
	echo "		<tr>\n";
	echo "			<th align='left'>".$text['message-message']."</th>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<td class='row_style1'><strong>".$text['message-copy']."</strong></td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "	<br />\n";
	echo "</div>\n";
	require_once "resources/footer.php";
	return;

?>