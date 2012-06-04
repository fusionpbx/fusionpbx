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

if (if_group("superadmin")) {

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
	echo "<tr>\n";
	echo "	<th colspan='2' align='left'>Backup</th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "	<a href='".PROJECT_PATH."/core/backup/backup.php'>download</a>	\n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "	<br />\n";
	echo "To backup your application click on the download link and then choose  \n";
	echo "a safe location on your computer to save the file. You may want to \n";
	echo "save the backup to more than one computer to prevent the backup from being lost. \n";
	echo "	<br />\n";
	echo "	<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "\n";

	echo "<span  class=\"\" ><strong></strong></span><br>\n";
	echo "<br>";
	echo "<br><br>";

	/*
	echo "<span  class=\"\" >Restore Application</span><br>\n";
	echo "<div class='borderlight' style='padding:10px;'>\n";
	//Browse to  Backup File
	echo "Click on 'Browse' then locate and select the application backup file named '.bak'.  \n";
	echo "Then click on 'Restore.' \n";
	echo "<br><br>";

	echo "<div align='center'>";
	echo "<form name='frmrestore' method='post' action='restore2.php'>";
	echo "	<table border='0' cellpadding='0' cellspacing='0'>";
	echo "	<tr>\n";
	echo "		<td class='' colspan='2' nowrap align='left'>\n";
	echo "          <table width='200'><tr>";
	echo "			<td><input type='file' class='frm' onChange='frmrestore.fileandpath.value = frmrestore.filename.value;' style='font-family: verdana; font-size: 11px;' name='filename'></td>";
	echo "          <td>";
	echo "			<input type='hidden' name='fileandpath' value=''>\n";
	echo "			<input type='submit' class='btn' value='Restore'>\n";
	echo "          </td>";
	echo "          </tr></table>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</form>\n";
	echo "</div>";

	echo "</div>";
	*/

 }

?>
