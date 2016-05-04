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
	Portions created by the Initial Developer are Copyright (C) 2015-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='30%' align='left' nowrap='nowrap'><b>".$text['header-select_language']."</b><br><br></td>\n";
	echo "		<td width='70%' align='right'>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-next']."'/>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "			".$text['label-select_language']."\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "			<table cellpadding='0' cellspacing='0'>";
	foreach($_SESSION['app']['languages'] as $lang_code){
		echo "			<tr>";
		echo "				<td width='15' class='vtable' valign='top' nowrap='nowrap'>\n";
		echo "					<input type='radio' name='install_language' value='$lang_code' id='lang_$lang_code' ";
		if($lang_code == $_SESSION['domain']['language']['code']) {
			echo " checked='checked'";
		}
		echo "/>";
		echo "				</td>";
		echo "				<td class='vtable' align='left' valign='top' nowrap='nowrap'>\n";
		echo "					<img src='<!--{project_path}-->/core/install/resources/images/flags/$lang_code.png' alt='$lang_code'/>&nbsp;".$text["language-$lang_code"];
		echo "				</td>";
		echo "				<td width='100%' class='vtable' valign='top'>\n";
		echo "					&nbsp;\n";
		echo "				</td>";
		echo "			</tr>";
	}
	echo "			</table>";
	echo "			<br />\n";
	echo "			".$text['description-select_language']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br><br>";

?>