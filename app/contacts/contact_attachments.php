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
	if (permission_exists('contact_attachment_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the contact attachment list
	$sql = "select *, length(decode(attachment_content,'base64')) as attachment_size from v_contact_attachments ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by attachment_primary desc, attachment_filename asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_attachments = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_attachments) && @sizeof($contact_attachments) != 0) {

		//styles and attachment layer
			echo "<style>\n";
			echo "	#contact_attachment_layer {\n";
			echo "		z-index: 999999;\n";
			echo "		position: absolute;\n";
			echo "		left: 0px;\n";
			echo "		top: 0px;\n";
			echo "		right: 0px;\n";
			echo "		bottom: 0px;\n";
			echo "		text-align: center;\n";
			echo "		vertical-align: middle;\n";
			echo "	}\n";
			echo "</style>\n";
			echo "<div id='contact_attachment_layer' style='display: none;'></div>\n";

		//script
			echo "<script>\n";
			echo "	function display_attachment(id) {\n";
			echo "		$('#contact_attachment_layer').load('contact_attachment.php?id=' + id + '&action=display', function(){\n";
			echo "			$('#contact_attachment_layer').fadeIn(200);\n";
			echo "		});\n";
			echo "	}\n";
			echo "</script>\n";

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-attachments']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_attachment_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_attachments' name='checkbox_all' onclick=\"edit_all_toggle('attachments');\" ".($contact_attachments ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th class='pct-15'>".$text['label-type']."</th>\n";
			echo "<th>".$text['label-attachment_filename']."</th>\n";
			echo "<th>".$text['label-attachment_size']."</th>\n";
			echo "<th>".$text['label-tools']."</th>\n";
			echo "<th class='hide-md-dn'>".$text['label-attachment_description']."</th>\n";
			if (permission_exists('contact_attachment_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_attachments) && @sizeof($contact_attachments) != 0) {
				$x = 0;
				foreach ($contact_attachments as $row) {
					$attachment_type = strtolower(pathinfo($row['attachment_filename'], PATHINFO_EXTENSION));
					$attachment_type_label = $attachment_type == 'jpg' || $attachment_type == 'jpeg' || $attachment_type == 'gif' || $attachment_type == 'png' ? $text['label-image'] : $text['label-file'];
					if (permission_exists('contact_attachment_edit')) {
						$list_row_url = "contact_attachment_edit.php?contact_uuid=".urlencode($row['contact_uuid'])."&id=".urlencode($row['contact_attachment_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_attachment_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_attachments[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_attachments' value='true' onclick=\"edit_delete_action('attachments');\">\n";
						echo "		<input type='hidden' name='contact_attachments[$x][uuid]' value='".escape($row['contact_attachment_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td>".$attachment_type_label." ".($row['attachment_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
					echo "	<td><a href='".$list_row_url."'>".escape($row['attachment_filename'])."</a></td>\n";
					echo "	<td>".strtoupper(byte_convert($row['attachment_size']))."</td>\n";
					echo "	<td class='no-link' style='cursor: pointer;'>";
					if ($attachment_type == 'jpg' || $attachment_type == 'jpeg' || $attachment_type == 'gif' || $attachment_type == 'png') {
						echo button::create(['type'=>'button','class'=>'link','label'=>$text['button-view'],'onclick'=>"display_attachment('".escape($row['contact_attachment_uuid'])."');"]);
					}
					else {
						echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-download'],'onclick'=>"window.location='contact_attachment.php?id=".urlencode($row['contact_attachment_uuid'])."&action=download';"]);
					}
					echo "	</td>\n";
					echo "	<td class='description overflow hide-md-dn'>".escape($row['attachment_description'])."</td>\n";
					if (permission_exists('contact_attachment_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
			}
			unset($contact_attachments);

			echo "</table>";
			echo "<br />\n";

	}

?>