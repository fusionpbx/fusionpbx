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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_block_delete')) {
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

//set the variable
	if (count($_GET)>0) {
		$id = $_GET["id"];
	}

//delete the extension
	if (strlen($id)>0) {
		$sql = "delete from v_call_block ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and blocked_caller_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);
	}

//redirect the user
	require_once "resources/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=call_block.php\">\n";
	echo "<br />\n";
	echo "<div align='center'>\n";
	echo "	<table width='40%'>\n";
	echo "		<tr>\n";
	echo "			<th align='left'>".$text['label-message']."</th>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<td class='row_style1'><strong>".$text['label-delete-complete']."</strong></td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "	<br />\n";
	echo "</div>\n";
	require_once "resources/footer.php";
	return;

?>