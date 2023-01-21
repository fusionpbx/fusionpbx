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
	if (permission_exists('contact_email_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the contact list
	$sql = "select * from v_contact_emails ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by email_primary desc, email_label asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_emails = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_emails) && @sizeof($contact_emails) != 0) {

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-emails']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_email_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_emails' name='checkbox_all' onclick=\"edit_all_toggle('emails');\" ".($contact_emails ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th class='pct-15'>".$text['label-email_label']."</th>\n";
			echo "<th>".$text['label-email_address']."</th>\n";
			echo "<th class='hide-md-dn'>".$text['label-email_description']."</th>\n";
			if (permission_exists('contact_email_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_emails) && @sizeof($contact_emails) != 0) {
				$x = 0;
				foreach ($contact_emails as $row) {
					if (permission_exists('contact_email_edit')) {
						$list_row_url = "contact_email_edit.php?contact_uuid=".urlencode($row['contact_uuid'])."&id=".urlencode($row['contact_email_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_email_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_emails[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_emails' value='true' onclick=\"edit_delete_action('emails');\">\n";
						echo "		<input type='hidden' name='contact_emails[$x][uuid]' value='".escape($row['contact_email_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td>".escape($row['email_label'])." ".($row['email_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
					echo "	<td class='no-link'><a href='mailto:".escape($row['email_address'])."'>".escape($row['email_address'])."</a>&nbsp;</td>\n";
					echo "	<td class='description overflow hide-md-dn'>".escape($row['email_description'])."&nbsp;</td>\n";
					if (permission_exists('contact_email_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
			}
			unset($contact_emails);

			echo "</table>";
			echo "<br />\n";

	}

?>