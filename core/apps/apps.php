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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (if_group("admin") || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
	$x=0;
	foreach ($config_list as $config_path) {
		include($config_path);
		$x++;
	}

//include the header
	$document['title'] = $text['title-apps'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-apps']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-apps'];
	echo "<br /><br />\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>".$text['label-name']."</th>\n";
	echo "	<th>".$text['label-category']."</th>\n";
	echo "	<th>".$text['label-subcategory']."</th>\n";
	echo "	<th class='center'>".$text['label-version']."</th>\n";
	echo "	<th class='hide-sm-dn'>".$text['label-description']."</th>\n";
	echo "</tr>\n";

	foreach ($apps as $row) {
		if ($row['uuid'] == "d8704214-75a0-e52f-1336-f0780e29fef8") { continue; }

		$description = $row['description'][$_SESSION['domain']['language']['code']];
		if (strlen($description) == 0) { $description = $row['description']['en-us']; }
		if (strlen($description) == 0) { $description = ''; }
		$row['$description'] = $description;

		echo "<tr class='list-row' href='".$list_row_url."'>\n";
		echo "	<td class='no-wrap'>".$row['name']."</td>\n";
		echo "	<td>".escape($row['category'])."&nbsp;</td>\n";
		echo "	<td>".escape($row['subcategory'])."&nbsp;</td>\n";
		echo "	<td class='center'>".escape($row['version'])."&nbsp;</td>\n";
		echo "	<td class='description overflow hide-sm-dn pct-35'>".escape($row['$description'])."</td>\n";
		echo "</tr>\n";
	}
	unset($apps);

	echo "</table>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>