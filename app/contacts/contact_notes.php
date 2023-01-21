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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_note_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set the uuid
	if (is_uuid($_GET['id'])) {
		$contact_uuid = $_GET['id'];
	}

//get the contact list
	$sql = "select * from v_contact_notes ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by last_mod_date desc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_notes = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_notes) && @sizeof($contact_notes) != 0) {

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-contact_notes']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_note_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_notes' name='checkbox_all' onclick=\"edit_all_toggle('notes');\" ".($contact_notes ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th>".$text['label-note_content']."</th>\n";
			echo "<th class='shrink'>".$text['label-note_user']."</th>\n";
			if (permission_exists('contact_note_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_notes) && @sizeof($contact_notes) != 0) {
				foreach ($contact_notes as $row) {
					$contact_note = $row['contact_note'];
					$contact_note = escape($contact_note);
					$contact_note = str_replace("\n","<br />",$contact_note);
					if (permission_exists('contact_note_add')) {
						$list_row_url = "contact_note_edit.php?contact_uuid=".escape($row['contact_uuid'])."&id=".escape($row['contact_note_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_note_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_notes[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_notes' value='true' onclick=\"edit_delete_action('notes');\">\n";
						echo "		<input type='hidden' name='contact_notes[$x][uuid]' value='".escape($row['contact_note_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td class='overflow'>".$contact_note."</td>\n";
					echo "	<td class='description no-wrap'><strong>".escape($row['last_mod_user'])."</strong>: ".date("j M Y @ H:i:s", strtotime($row['last_mod_date']))."</td>\n";
					if (permission_exists('contact_note_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
			}
			unset($contact_notes);

			echo "</table>";
			echo "<br />\n";

	}

?>