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
	if (permission_exists('contact_time_view')) {
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
	$sql = "select ct.*, u.username, u.domain_uuid as user_domain_uuid ";
	$sql .= "from v_contact_times as ct, v_users as u ";
	$sql .= "where ct.user_uuid = u.user_uuid ";
	$sql .= "and ct.domain_uuid = :domain_uuid ";
	$sql .= "and ct.contact_uuid = :contact_uuid ";
	$sql .= "order by ct.time_start desc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_times = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_times) && @sizeof($contact_times) != 0) {

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['header_contact_times']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_time_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_times' name='checkbox_all' onclick=\"edit_all_toggle('times');\" ".($contact_times ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th class='pct-20'>".$text['label-time_user']."</th>\n";
			echo "<th class='pct-20'>".$text['label-time_start']."</th>\n";
			echo "<th class='pct-20'>".$text['label-time_duration']."</th>\n";
			echo "<th class='pct-40 hide-md-dn'>".$text['label-time_description']."</th>\n";
			if (permission_exists('contact_time_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_times) && @sizeof($contact_times) != 0) {
				$x = 0;
				foreach ($contact_times as $row) {
					if ($row["time_start"] != '' && $row['time_stop'] != '') {
						$time_start = strtotime($row["time_start"]);
						$time_stop = strtotime($row['time_stop']);
						$time = gmdate("H:i:s", ($time_stop - $time_start));
					}
					else {
						unset($time);
					}
					$tmp = explode(' ', $row['time_start']);
					$time_start = $tmp[0];
					if (permission_exists('contact_time_edit')) {
						$list_row_url = "contact_time_edit.php?contact_uuid=".urlencode($row['contact_uuid'])."&id=".urlencode($row['contact_time_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_time_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_times[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_times' value='true' onclick=\"edit_delete_action('times');\">\n";
						echo "		<input type='hidden' name='contact_times[$x][uuid]' value='".escape($row['contact_time_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td><span ".($row['user_domain_uuid'] != $domain_uuid ? "title='".$_SESSION['domains'][escape($row['user_domain_uuid'])]['domain_name']."' style='cursor: help;'" : null).">".escape($row["username"])."</span>&nbsp;</td>\n";
					echo "	<td>".$time_start."&nbsp;</td>\n";
					echo "	<td>".$time."&nbsp;</td>\n";
					echo "	<td class='description overflow hide-md-dn'>".escape($row['time_description'])."&nbsp;</td>\n";
					if (permission_exists('contact_time_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
				unset($contact_times);
			}

			echo "</table>\n";
			echo "<br />\n";

	}

?>