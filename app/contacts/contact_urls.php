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
	if (permission_exists('contact_url_view')) {
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
	$sql = "select * from v_contact_urls ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by url_primary desc, url_label asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_urls = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_urls) && @sizeof($contact_urls) != 0) {

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-urls']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_url_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_urls' name='checkbox_all' onclick=\"edit_all_toggle('urls');\" ".($contact_urls ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th class='pct-15'>".$text['label-url_label']."</th>\n";
			echo "<th>".$text['label-url_address']."</th>\n";
			echo "<th class='hide-md-dn'>".$text['label-url_description']."</th>\n";
			if (permission_exists('contact_url_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_urls) && @sizeof($contact_urls) != 0) {
				$x = 0;
				foreach ($contact_urls as $row) {
					if (permission_exists('contact_url_edit')) {
						$list_row_url = "contact_url_edit.php?contact_uuid=".urlencode($row['contact_uuid'])."&id=".urlencode($row['contact_url_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_url_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_urls[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_urls' value='true' onclick=\"edit_delete_action('urls');\">\n";
						echo "		<input type='hidden' name='contact_urls[$x][uuid]' value='".escape($row['contact_url_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td>".escape($row['url_label'])." ".($row['url_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
					echo "	<td class='no-link overflow no-wrap'><a href='".escape($row['url_address'])."' target='_blank'>".str_replace("http://", "", str_replace("https://", "", escape($row['url_address'])))."</a></td>\n";
					echo "	<td class='description overflow hide-md-dn'>".escape($row['url_description'])."&nbsp;</td>\n";
					if (permission_exists('contact_url_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
			}
			unset($contact_urls);

			echo "</table>\n";
			echo "<br />\n";

	}

?>