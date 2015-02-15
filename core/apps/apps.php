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
require_once "resources/require.php";
require_once "resources/check_auth.php";
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

require_once "resources/header.php";
$document['title'] = $text['title-apps'];

require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
	$x=0;
	foreach ($config_list as $config_path) {
		include($config_path);
		$x++;
	}

//show the content
	echo "<b>".$text['header-apps']."</b>\n";
	echo "<br /><br />\n";
	echo $text['description-apps'];
	echo "<br /><br />\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th>".$text['label-name']."</th>\n";
	echo "	<th>".$text['label-category']."</th>\n";
	echo "	<th>".$text['label-subcategory']."</th>\n";
	echo "	<th>".$text['label-version']."</th>\n";
	echo "	<th>".$text['label-description']."</th>\n";
	/*
	echo "<td class='list_control_icons'>\n";
	if (permission_exists('app_add')) {
		echo "	<a href='apps_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	*/
	echo "<tr>\n";

	foreach($apps as $row) {
		if ($row['uuid'] != "d8704214-75a0-e52f-1336-f0780e29fef8") {
			/*
			$tr_link = (permission_exists('app_edit')) ? "href='apps_edit.php?id=".$row['uuid']."'" : null;
			*/
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."' nowrap='nowrap'>";
			/*
			if (permission_exists('app_edit')) {
				echo "	<a href='apps_edit.php?id=".$row['uuid']."'>".$row['name']."</a>";
			}
			else {
			*/
				echo $row['name'];
			/*
			}
			*/
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['category']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['subcategory']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['version']."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='35%'>".$row['description']['en-us']."&nbsp;</td>\n";
			/*  // temporarily disabled
			echo "	<td class='list_control_icons'>";
			if (permission_exists('app_edit')) {
				echo "	<a href='apps_edit.php?id=".$row['uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('app_delete')) {
				echo "	<a href='apps_delete.php?id=".$row['uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			*/
			echo "</tr>\n";
		}
		if ($c==0) { $c=1; } else { $c=0; }
	} //end foreach
	unset($sql, $result, $row_count);

	echo "<tr>\n";
	echo "	<td colspan='5'>&nbsp;</td>\n";
	/*
	echo "<td class='list_control_icons'>\n";
	if (permission_exists('app_add')) {
		echo "	<a href='apps_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	*/
	echo "</tr>";
	echo "</table>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>