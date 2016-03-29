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
	Matthew Vale <github@mafoo.org>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//detect install state
$install_enabled = true;
if (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
	$install_enabled = false;
} elseif (file_exists("/etc/fusionpbx/config.php")) {
	//linux
	$install_enabled = false;
} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
	$install_enabled = false;
}

if($install_enabled) {
	header("Location: ".PROJECT_PATH."/core/install/install.php");
	exit;
}
require_once "resources/check_auth.php";
if (!if_group("superadmin")) {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-install'];

	echo "<b>".$text['header-install']."</b>";
	echo "<br><br>";
	echo $text['description-install'];
	echo "<br><br>";

	echo "<form name='frm' method='post' action='/core/install/install.php'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		<input id='do_ft-install' type='submit' class='btn' value='".$text['label-ft-install']."'/>";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<label for='do_ft-install'>";
	echo "			".$text['description-ft-install'];
	echo "		</label>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>