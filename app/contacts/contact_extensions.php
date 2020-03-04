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

//check permissions
	if (permission_exists('contact_extension_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the extension list
	$sql = "select e.extension_uuid, e.extension, e.enabled, e.description ";
	$sql .= "from v_extensions e, v_extension_users eu, v_users u ";
	$sql .= "where e.extension_uuid = eu.extension_uuid ";
	$sql .= "and u.user_uuid = eu.user_uuid ";
	$sql .= "and e.domain_uuid = :domain_uuid ";
	$sql .= "and u.contact_uuid = :contact_uuid ";
	$sql .= "order by e.extension asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_extensions) && @sizeof($contact_extensions) != 0) {

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-contact_extensions']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			echo "<th>".$text['label-extension']."</th>\n";
			echo "<th class='center'>".$text['label-enabled']."</th>\n";
			echo "<th class='hide-md-dn'>".$text['label-description']."</th>\n";
			if (permission_exists('extension_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_extensions) && @sizeof($contact_extensions) != 0) {
				$x = 0;
				foreach ($contact_extensions as $row) {
					if (permission_exists('extension_edit')) {
						$list_row_url = PROJECT_PATH.'/app/extensions/extension_edit.php?id='.urlencode($row['extension_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."' ".($row['url_primary'] ? "style='font-weight: bold;'" : null).">\n";
					echo "	<td>";
					if (permission_exists('extension_edit')) {
						echo 	"<a href='".PROJECT_PATH."/app/extensions/extension_edit.php?id=".urlencode($row['extension_uuid'])."'>".escape($row['extension'])."</a>";
					}
					else {
						echo $row['extension'];
					}
					echo "	</td>\n";
					echo "	<td class='center'>".$text['label-'.escape($row['enabled'])]."&nbsp;</td>\n";
					echo "	<td class='description overflow hide-md-dn'>".$row['description']."&nbsp;</td>\n";
					if (permission_exists('extension_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
			}
			unset($contact_extensions);

			echo "</table>";
			echo "<br />\n";

	}

?>