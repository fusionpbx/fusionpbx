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
if (permission_exists('content_add')) {
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
	$rss_sub_category_uuid = uuid();
	$rss_category = check_str($_POST["rss_category"]);
	$rss_sub_category = check_str($_POST["rss_sub_category"]);
	$rss_sub_category_description = check_str($_POST["rss_sub_category_description"]);
	$rss_add_user = check_str($_POST["rss_add_user"]);
	$rss_add_date = check_str($_POST["rss_add_date"]);

	$sql = "insert into v_rss_sub_category ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "rss_sub_category_uuid, ";
	$sql .= "rss_category, ";
	$sql .= "rss_sub_category, ";
	$sql .= "rss_sub_category_description, ";
	$sql .= "rss_add_user, ";
	$sql .= "rss_add_date ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$rss_sub_category_uuid', ";
	$sql .= "'$rss_category', ";
	$sql .= "'$rss_sub_category', ";
	$sql .= "'$rss_sub_category_description', ";
	$sql .= "'$rss_add_user', ";
	$sql .= "'$rss_add_date' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);

	$_SESSION["message"] = $text['message-add'];
	header("Location: rss_sub_categorylist.php");
	return;
}

require_once "resources/header.php";

echo "<form method='post' action=''>";
echo "<table>";
echo "	<tr>";
echo "		<td>".$text['label-rss-category']."</td>";
echo "		<td><input type='text' name='rss_category'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_sub_category</td>";
echo "		<td><input type='text' name='rss_sub_category'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_sub_category_description</td>";
echo "		<td><input type='text' name='rss_sub_category_description'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_add_user</td>";
echo "		<td><input type='text' name='rss_add_user'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td>rss_add_date</td>";
echo "		<td><input type='text' name='rss_add_date'></td>";
echo "	</tr>\n";
echo "	<tr>";
echo "		<td colspan='2' align='right'><input type='submit' name='submit' value='".$text['button-add-title']."'></td>";
echo "	</tr>";
echo "</table>";
echo "</form>";

require_once "resources/footer.php";
?>