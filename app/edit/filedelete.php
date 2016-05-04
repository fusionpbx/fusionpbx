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
if (permission_exists('script_editor_save')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

$folder = $_GET["folder"];
$folder = str_replace ("\\", "/", $folder);
if (substr($folder, -1) != "/") { $folder = $folder.'/'; }
$file = $_GET["file"];

if (strlen($folder) > 0 && strlen($file) > 0) {
	unlink($folder.$file);
	header("Location: fileoptions.php");
}
else {
	//display form
	require_once "header.php";
	echo "<br>";
	echo "<div align='left'>";
	echo "<form method='get' action=''>";
	echo "<table>";
	echo "	<tr>";
	echo "		<td>".$text['label-path']."</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$folder.$file."</td>";
	echo "	</tr>";
	echo "</table>";

	echo "<br />";

	echo "<table>";
	echo "	<tr>";
	echo "	  <td>".$text['label-file-name']."</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td><input type='text' name='file' value=''></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='1' align='right'>";
	echo "      <input type='hidden' name='folder' value='$folder'>";
	echo "		  <input type='submit' value='".$text['button-new-file']."'>";
	echo "    </td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "</div>";

	require_once "footer.php";
}
?>