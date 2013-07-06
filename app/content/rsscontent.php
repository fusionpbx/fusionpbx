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
require_once "config.php";
if (permission_exists('content_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}


//get data from the db
$rss_uuid = $_REQUEST["rss_uuid"];

$sql = "";
$sql .= "select * from v_rss ";
$sql .= "where domain_uuid = '$domain_uuid' ";
$sql .= "and rss_uuid = '$rss_uuid' ";
//echo $sql;
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
foreach ($result as &$row) {
	$rss_category = $row["rss_category"];
	$rss_sub_category = $row["rss_sub_category"];
	$rss_title = $row["rss_title"];
	$rss_link = $row["rss_link"];
	$rss_description = $row["rss_description"];
	$rss_img = $row["rss_img"];
	$rss_optional_1 = $row["rss_optional_1"];
	$rss_optional_2 = $row["rss_optional_2"];
	$rss_optional_3 = $row["rss_optional_3"];
	$rss_optional_4 = $row["rss_optional_4"];
	$rss_optional_5 = $row["rss_optional_5"];
	$rss_add_date = $row["rss_add_date"];
	$rss_add_user = $row["rss_add_user"];
	$rss_group = $row["rss_group"];
	$rss_order = $row["rss_order"];
	//$rss_description = str_replace ("\r\n", "<br>", $rss_description);

	echo $rss_description;
	//return;

	break; //limit to 1 row
}

?>
