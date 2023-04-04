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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
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
	if (permission_exists('contact_relation_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the related contacts
	$sql = "select ";
	$sql .= "cr.contact_relation_uuid, ";
	$sql .= "cr.relation_label, ";
	$sql .= "c.contact_uuid, ";
	$sql .= "c.contact_organization, ";
	$sql .= "c.contact_name_given, ";
	$sql .= "c.contact_name_family ";
	$sql .= "from ";
	$sql .= "v_contact_relations as cr, ";
	$sql .= "v_contacts as c ";
	$sql .= "where ";
	$sql .= "cr.relation_contact_uuid = c.contact_uuid ";
	$sql .= "and cr.domain_uuid = :domain_uuid ";
	$sql .= "and cr.contact_uuid = :contact_uuid ";
	$sql .= "order by ";
	$sql .= "c.contact_organization desc, ";
	$sql .= "c.contact_name_given asc, ";
	$sql .= "c.contact_name_family asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_relations = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_relations) && @sizeof($contact_relations) != 0) {

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['header-contact_relations']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_relation_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_relations' name='checkbox_all' onclick=\"edit_all_toggle('relations');\" ".($contact_relations ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th>".$text['label-contact_relation_label']."</th>\n";
			echo "<th>".$text['label-contact_relation_organization']."</th>\n";
			echo "<th>".$text['label-contact_relation_name']."</th>\n";
			if (permission_exists('contact_relation_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_relations) && @sizeof($contact_relations) != 0) {
				$x = 0;
				foreach ($contact_relations as $row) {
					if (permission_exists('contact_relation_edit')) {
						$list_row_url = "contact_relation_edit.php?contact_uuid=".urlencode($contact_uuid)."&id=".urlencode($row['contact_relation_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_relation_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_relations[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_relations' value='true' onclick=\"edit_delete_action('relations');\">\n";
						echo "		<input type='hidden' name='contact_relations[$x][uuid]' value='".escape($row['contact_relation_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td>".escape($row['relation_label'])."&nbsp;</td>\n";
					echo "	<td class='no-link'><a href='contact_edit.php?id=".urlencode($row['contact_uuid'])."'>".escape($row['contact_organization'])."</a>&nbsp;</td>\n";
					echo "	<td class='no-link'><a href='contact_edit.php?id=".urlencode($row['contact_uuid'])."'>".escape($row['contact_name_given']).(($row['contact_name_given'] != '' && $row['contact_name_family'] != '') ? ' ' : null).escape($row['contact_name_family'])."</a>&nbsp;</td>\n";
					if (permission_exists('contact_relation_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
				unset($contact_relations);
			}

			echo "</table>";
			echo "<br />\n";

	}

?>