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

//move down more than one level at a time
//update v_rss set rss_order = (rss_order+1) where rss_order > 2 or rss_order = 2

if (count($_GET)>0) {
	$rss_uuid = check_str($_GET["rss_uuid"]);
	$rss_order = check_str($_GET["rss_order"]);

	$sql = "SELECT rss_order FROM v_rss ";
	$sql .= "where domain_uuid  = '$domain_uuid' ";
	$sql .= "and rss_category  = '$rss_category' ";
	$sql .= "order by rss_order desc ";
	$sql .= "limit 1 ";
	//echo $sql."<br><br>";
	//return;
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		//print_r( $row );
		$highestrss_order = $row[rss_order];
	}
	unset($prep_statement);

	if ($rss_order != $highestrss_order) {
		//move the current item's order number up
		$sql  = "update v_rss set ";
		$sql .= "rss_order = (rss_order-1) "; //move down
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and rss_order = ".($rss_order+1)." ";
		$sql .= "and rss_category  = '$rss_category' ";
		//echo $sql."<br><br>";
		$db->exec(check_sql($sql));
		unset($sql);

		//move the selected item's order number down
		$sql  = "update v_rss set ";
		$sql .= "rss_order = (rss_order+1) "; //move up
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and rss_uuid = '$rss_uuid' ";
		$sql .= "and rss_category  = '$rss_category' ";
		//echo $sql."<br><br>";
		$db->exec(check_sql($sql));
		unset($sql);
	}

	$_SESSION["message"] = $text['message-item-down'];
	header("Location: rsslist.php?rss_uuid=".$rss_uuid);
	return;
}


?>
