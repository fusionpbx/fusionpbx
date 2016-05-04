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
if (permission_exists('script_editor_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

$title = $text['title-manage-clips'];
require_once "header.php";

echo "<table cellpadding='0' cellspacing='0' border='0' style='height: 100%; width: 100%;'>\n";
echo "<form method='post' name='frm' action=''>";
echo "<tr><td colspan='2' style='padding-bottom: 3px;'><div id='selectedclip'>".$text['label-selected-clip']." <input type='text' name='clip_name' id='clip_name' value=''><input type='hidden' name='clip_uuid' id='clip_uuid' value=''></div></td></tr>\n";
echo "</form>";
echo "<tr>\n";
echo "<td valign='top' width='200' nowrap>";
echo "  <iframe src='clipoptionslist.php' style='border: solid 1px #ccc; height: calc(100% - 3px); width: calc(100% - 4px);'></iframe>";
echo "</td>\n";
echo "<td valign='top' style='height: 100%; padding: 0;'>";

echo "<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>";
echo "  <tr><td><input type='button' class='btn' onclick=\"window.location='clipadd.php'\" value='".$text['button-add-clip']."'></td></tr>\n";
echo "  <tr><td><input type='button' class='btn' onclick=\"window.location='clipupdate.php?id='+document.getElementById('clip_uuid').value;\" value='".$text['button-edit-clip']."'></td></tr>\n";
echo "  <tr><td><input type='button' class='btn' onclick=\"if (confirm('".$text['message-delete-clip']."')){ window.location='clipdelete.php?id='+document.getElementById('clip_uuid').value; }\" value='".$text['button-delete']."'></td></tr>\n";
echo "  <tr><td height='100%'>&nbsp;</td></tr>\n";
echo "  <tr><td><input type='button' class='btn' onclick='opener.location.reload(); self.close();' value='".$text['button-close']."'></td></tr>\n";
echo "</table>";

echo "</td>\n";
echo "</tr>\n";
echo "</table>";

require_once "footer.php";
?>