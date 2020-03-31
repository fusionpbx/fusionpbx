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
 Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_setting_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the list
	$sql = "select * from v_contact_settings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by ";
	$sql .= "contact_setting_category asc ";
	$sql .= ", contact_setting_subcategory asc ";
	$sql .= ", contact_setting_order asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_settings) && @sizeof($contact_settings) != 0) {

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-contact_settings']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_setting_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_settings' name='checkbox_all' onclick=\"edit_all_toggle('settings');\" ".($contact_settings ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th class='pct-15'>".$text['label-contact_setting_category']."</th>";
			echo "<th>".$text['label-contact_setting_subcategory']."</th>";
			echo "<th>".$text['label-contact_setting_type']."</th>";
			echo "<th>".$text['label-contact_setting_value']."</th>";
			echo "<th class='center'>".$text['label-enabled']."</th>";
			echo "<th class='hide-md-dn'>".$text['label-description']."</th>";
			if (permission_exists('contact_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_settings) && @sizeof($contact_settings) != 0) {
				$x = 0;
				foreach ($contact_settings as $row) {
					if (permission_exists('contact_setting_edit')) {
						$list_row_url = "contact_setting_edit.php?contact_uuid=".urlencode($contact_uuid)."&id=".urlencode($row['contact_setting_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_setting_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_settings[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_settings' value='true' onclick=\"edit_delete_action('settings');\">\n";
						echo "		<input type='hidden' name='contact_settings[$x][uuid]' value='".escape($row['contact_setting_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td>".escape($row['contact_setting_category'])."&nbsp;</td>\n";
					echo "	<td><a href='".$list_row_url."'>".escape($row['contact_setting_subcategory'])."</a></td>\n";
					echo "	<td>".escape($row['contact_setting_name'])."&nbsp;</td>\n";
					echo "	<td>\n";
					$category = escape($row['contact_setting_category']);
					$subcategory = escape($row['contact_setting_subcategory']);
					$name = escape($row['contact_setting_name']);
					if ($category == "callingcard" && $subcategory == "username" && $name == "var" ) {
						echo "		********\n";
					}
					else if ($category == "callingcard" && $subcategory == "password" && $name == "var" ) {
						echo "		********\n";
					}
					else {
						echo escape($row['contact_setting_value']);
					}
					echo "	</td>\n";
					echo "	<td class='center'>".$text['label-'.escape($row['contact_setting_enabled'])]."&nbsp;</td>\n";
					echo "	<td class='description overflow hide-md-dn'>".$row['contact_setting_description']."&nbsp;</td>\n";
					if (permission_exists('contact_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
				unset($contact_settings);
			}

			echo "</table>";
			echo "<br />\n";

	}

?>