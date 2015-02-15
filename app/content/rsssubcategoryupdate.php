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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "config.php";
if (permission_exists('content_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (count($_POST)>0) {
	$rss_sub_category_uuid = check_str($_POST["rss_sub_category_uuid"]);
	$rss_category = check_str($_POST["rss_category"]);
	$rss_sub_category = check_str($_POST["rss_sub_category"]);
	$rss_sub_category_description = check_str($_POST["rss_sub_category_description"]);
	$rss_add_user = check_str($_POST["rss_add_user"]);
	$rss_add_date = check_str($_POST["rss_add_date"]);

	//sql update
	$sql  = "update v_rss_sub_category set ";
	$sql .= "rss_category = '$rss_category', ";
	$sql .= "rss_sub_category = '$rss_sub_category', ";
	$sql .= "rss_sub_category_description = '$rss_sub_category_description', ";
	$sql .= "rss_add_user = '$rss_add_user', ";
	$sql .= "rss_add_date = '$rss_add_date' ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and rss_sub_category_uuid = '$rss_sub_category_uuid' ";
	$count = $db->exec(check_sql($sql));
	//echo "Affected Rows: ".$count;

	$_SESSION["message"] = $text['message-update'];
	header("Location: rss_sub_categorylist.php");
	return;
}
else {
	//get data from the db
	$rss_sub_category_uuid = $_GET["rss_sub_category_uuid"];

	$sql = "";
	$sql .= "select * from v_rss_sub_category ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and rss_sub_category_uuid = '$rss_sub_category_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$rss_category = $row["rss_category"];
		$rss_sub_category = $row["rss_sub_category"];
		$rss_sub_category_description = $row["rss_sub_category_description"];
		$rss_add_user = $row["rss_add_user"];
		$rss_add_date = $row["rss_add_date"];
		break; //limit to 1 row
	}
}

require_once "resources/header.php";

echo "<form method='post' action=''>";
echo "<table>";
echo "	<tr>";
echo "		<td>rss_category</td>";
echo "		<td><input type='text' name='rss_category' value='$rss_category'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_sub_category</td>";
echo "		<td><input type='text' name='rss_sub_category' value='$rss_sub_category'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_sub_category_description</td>";
echo "		<td><input type='text' name='rss_sub_category_description' value='$rss_sub_category_description'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_add_user</td>";
echo "		<td><input type='text' name='rss_add_user' value='$rss_add_user'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_add_date</td>";
echo "		<td><input type='text' name='rss_add_date' value='$rss_add_date'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td colspan='2' align='right'>";
echo "     <input type='hidden' name='rss_sub_category_uuid' value='$rss_sub_category_uuid'>";
echo "		<br><br>";
echo "     <input type='submit' name='submit' value='".$text['button-update']."'>";
echo "		</td>";
echo "	</tr>";
echo "</table>";
echo "<br><br>";
echo "</form>";

  require_once "resources/footer.php";
?>
