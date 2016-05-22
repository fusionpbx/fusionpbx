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

	echo "<form method='post' name='frm' action=''>\n";
	echo "<input type='hidden' name='install_language' value='".$_SESSION['domain']['language']['code']."'/>\n";
	echo "<input type='hidden' name='return_install_step' value='config_detail'/>\n";
	echo "<input type='hidden' name='install_step' value='config_database'/>\n";

	echo "<input type='hidden' name='event_host' value='$event_host'/>\n";
	echo "<input type='hidden' name='event_port' value='$event_port'/>\n";
	echo "<input type='hidden' name='event_password' value='$event_password'/>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap><b>".$text['header-config_detail']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' name='back' class='btn' onclick=\"history.go(-1);\" value='".$text['button-back']."'/>\n";
	echo "	<input type='submit' name='next' class='btn' value='".$text['button-next']."'/>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Username\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='admin_username' maxlength='255' value=\"$admin_username\"><br />\n";
	echo "	Enter the username to use when logging in with the browser.<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Password\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='admin_password' maxlength='255' value=\"$admin_password\"><br />\n";
	echo "	Enter the password to use when logging in with the browser.<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Country\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "		<select id='install_default_country' name='install_default_country' class='formfld' style=''>\n";
	require "resources/countries.php";

	foreach ($countries as $iso_code => $country ){
		if($iso_code == $install_default_country){
			echo "			<option value='$iso_code' selected='selected'>".$country['country']."</option>\n";
		}else{
			echo "			<option value='$iso_code'>".$country['country']."</option>\n";
		}
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "	Select ISO country code used to initialize calling contry code variables.<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncellreq\" align='left' nowrap='nowrap'>\n";
	echo "		Theme: \n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='install_template_name' name='install_template_name' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	//add all the themes to the list
		$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && $dir_name != ".git" && $dir_name != "flags" && is_readable($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == 'enhanced') {
						echo "		<option value='$dir_name' selected='selected'>$dir_label</option>\n";
					}
					else {
						echo "		<option value='$dir_name'>$dir_label</option>\n";
					}
				}
			}
			closedir($handle);
		}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		Select a theme to set as the default.<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "		Domain name\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "		<input class='formfld' type='text' name='domain_name' maxlength='255' value=\"$domain_name\"><br />\n";
	echo "		Enter the default domain name. \n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Database Type\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='db_type' id='db_type' class='formfld' id='form_tag' onchange='db_type_onchange();'>\n";
	if (extension_loaded('pdo_pgsql')) {	echo "	<option value='pgsql'>postgresql</option>\n"; }
	if (extension_loaded('pdo_mysql')) {	echo "	<option value='mysql'>mysql</option>\n"; }
	if (extension_loaded('pdo_sqlite')) {	echo "	<option value='sqlite' selected='selected'>sqlite</option>\n"; } //set sqlite as the default
	echo "	</select><br />\n";
	echo "		Select the database type.\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	//echo "	<div style='text-align:right'>\n";
	//echo "		<input type='button' name='back' class='btn' onclick=\"history.go(-1);\" value='".$text['button-back']."'/>\n";
	//echo "		<input type='submit' class='btn' name='execute' name='".$text['button-next']."'>\n";
	//echo "	</div>\n";
	echo "</form>\n";
?>