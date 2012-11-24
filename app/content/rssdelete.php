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
require_once "includes/require.php";
require_once "includes/checkauth.php";
require_once "config.php";
if (permission_exists('content_delete')) {
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

if (count($_GET)>0) {
	$rss_uuid = check_str($_GET["rss_uuid"]);

	//mark the the item as deleted and who deleted it
	$sql  = "update v_rss set ";
	$sql .= "rss_del_date = now(), ";
	$sql .= "rss_del_user = '".$_SESSION["username"]."' ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and rss_uuid = '$rss_uuid' ";
	$sql .= "and rss_category  = '$rss_category' ";
	$db->exec(check_sql($sql));
	unset($sql);

	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=rsslist.php?rss_uuid=$rss_uuid\">\n";
	echo "<div align='center'>";
	echo $text['message-delete-done'];
	echo "</div>";
	require_once "includes/footer.php";
	return;
}


?>
