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
if (permission_exists('group_add')) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the http values and set them as variables
	$path = check_str($_GET["path"]);
	$msg = check_str($_GET["msg"]);
	$group_name = check_str($_POST["group_name"]);
	$group_description = check_str($_POST["group_description"]);

if (strlen($group_name) > 0) {
	$sql_insert = "insert into v_groups ";
	$sql_insert .= "(";
	$sql_insert .= "domain_uuid, ";
	$sql_insert .= "group_uuid, ";
	$sql_insert .= "group_name, ";
	$sql_insert .= "group_description ";
	$sql_insert .= ")";
	$sql_insert .= "values ";
	$sql_insert .= "(";
	$sql_insert .= "'$domain_uuid', ";
	$sql_insert .= "'".uuid()."', ";
	$sql_insert .= "'$group_name', ";
	$sql_insert .= "'$group_description' ";
	$sql_insert .= ")";
	if (!$db->exec($sql_insert)) {
		//echo $db->errorCode() . "<br>";
		$info = $db->errorInfo();
		print_r($info);
		// $info[0] == $db->errorCode() unified error code
		// $info[1] is the driver specific error code
		// $info[2] is the driver specific error string
	}

	//redirect the user
		$_SESSION["message"] = $text['message-add'];
		header("Location: groups.php");
		return;
}

//include the header
	include "resources/header.php";
	$document['title'] = $text['title-group_add'];

//show the content
	echo "<div align='center'>";

	echo "<table width='100%' cellpadding='6' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<b>".$text['header-group_add']."</b>\n";
	echo "			<br><br>\n";
	echo "			".$text['description-group_add']."\n";
	echo "		</td>\n";
	echo "		<td align='right'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'> ";
	echo "  		<input type=\"submit\" class='btn' value=\"".$text['button-save']."\">\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";

	echo "<form name='login' METHOD=\"POST\" action=\"groupadd.php\">\n";
	echo "<table width='100%' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq'>\n";
	echo $text['label-group_name'].":\n";
	echo "</td>\n";
	echo "<td width='70%' align='left' class='vtable'>\n";
	echo "  <input type=\"text\" class='formfld' name=\"group_name\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq'>\n";
	echo $text['label-group_description'].":\n";
	echo "</td>\n";
	echo "<td align='left' class='vtable'>\n";
	echo "<textarea name='group_description' class='formfld'></textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	echo "</td>\n";
	echo "<td align=\"right\">\n";
	echo "  <input type=\"hidden\" name=\"path\" value=\"$path\">\n";
	echo "  <input type=\"submit\" class='btn' value=\"".$text['button-save']."\">\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>";
	echo "</div>";

	echo "<br><br>";
	echo "<br><br>";

//include the footer
	include "resources/footer.php";

?>
