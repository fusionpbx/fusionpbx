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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('grammar_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "header.php";

$file = $_GET["file"];
$file = str_replace ("\\", "/", $file);
$folder = $_GET["folder"];
$folder = str_replace ($file, "", $folder);
$urlpath = str_replace ($_SERVER["DOCUMENT_ROOT"], "", $folder);

echo "<div align='left'>";
echo "<table border='0' style=\"height: 100%; width: 100%;\">\n";
echo "<tr>";
echo "<td colspan='2'>";

echo "<table border='0'>";
echo "<form method='post' name='frm' action=''>";
echo "<tr><td>Path:</td><td width='95%'><input type='text' name='folder' id='folder' style=\"width: 100%;\" value=''></td><tr>\n";
echo "<tr><td>File:</td><td width='95%' style=\"width: 60%;\"><input type='text' name='filename' id='filename' style=\"width: 100%;\" value=''></div></td></tr>\n";
echo "</form>";
echo "</table>";

echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td valign='top' width='200' nowrap>";
echo "  <IFRAME SRC='fileoptionslist.php' style='border: solid 1px #CCCCCC; height: 100%; width: 100%;' TITLE=''>\n";
echo "  <!-- Clip List: Requires IFRAME support -->\n";
echo "  </IFRAME>";
echo "</td>\n";
echo "<td valign='top' style=\"height: 100%;\">";

echo "<div align='left'>";
echo "<table width='100%' class='border'>";
echo "  <tr><td><input type='button' class='btn' name='' onclick=\"window.location='filenew.php?folder='+document.getElementById('folder').value;\" value='Add File'></td></tr>\n";
echo "  <tr><td><input type='button' class='btn' name='' onclick=\"window.location='foldernew.php?folder='+document.getElementById('folder').value;\" value='Add Dir'></td></tr>\n";
echo "  <tr><td><input type='button' class='btn' name='' onclick=\"window.location='filerename.php?folder='+document.getElementById('folder').value+'&filename='+document.getElementById('filename').value;\" value='Rename File'></td></tr>\n";
echo "  <tr><td><input type='button' class='btn' name='' onclick=\"if (confirm('Are you sure you want to delete the selected file?')){ window.location='filedelete.php?folder='+document.getElementById('folder').value+'&file='+document.getElementById('filename').value; }\" value='Delete File'></td></tr>\n";
echo "  <tr><td><input type='button' class='btn' name='' onclick=\"if (confirm('Are you sure you want to delete the selected folder?')){ window.location='folderdelete.php?folder='+document.getElementById('folder').value; }\" value='Delete Dir'></td></tr>\n";
echo "  <tr><td><br><br><br><br><br></td></tr>\n";
echo "  <tr><td><input type='button' class='btn' name='' onclick='javascript:self.close();' value='Close'></td></tr>\n";
echo "</table>";
echo "</div>";

echo "</td>\n";
echo "</tr>\n";
echo "</table>";
echo "</div>";

require_once "footer.php";
?>
