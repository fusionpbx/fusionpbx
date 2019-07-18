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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the domain_uuid from the session
	$domain_uuid = $_SESSION['domain_uuid'];

//handle search term
	$search = $_GET["search"];
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "extension like :search ";
		$sql_mod .= "or description like :search ";
		$sql_mod .= ") ";
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/calls');

//begin the content
	require_once "resources/header.php";
	require_once "resources/paging.php";

//define select count query
	$sql = "select count(extension_uuid) as count from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true' ";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//used to hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$sql .= $sql_mod; //add search mod from above
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	if (strlen($search) > 0) {
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$result_count = $database->select($sql, $parameters, 'column');
	if ($is_included) {
		$rows_per_page = 10;
	}
	else {
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	}
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($result_count, $param, $rows_per_page, true);
	list($paging_controls, $rows_per_page, $var_3) = paging($result_count, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//select the extensions
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true' ";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ") ";
		}
		else {
			//used to hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$sql .= $sql_mod; //add search mod from above
	$sql .= "order by extension asc ";
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($parameters);

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//start the content
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
	echo "	<tr>\n";
	echo "		<td valign='top'>";
	echo "			<b>".$text['header-call_routing']."</b><br />";
	echo "		</td>\n";
	echo "		<td valign='top' style='text-align: right; white-space: nowrap;'>\n";
	if ($result_count > 10 && $is_included) {
		echo "			<input id='btn_viewall_callrouting' type='button' class='btn' value='".$text['button-view_all']."' onclick=\"document.location.href='".PROJECT_PATH."/app/calls/calls.php';\">";
	}
	if (!$is_included) {
		echo "				<form method='get' action='' style='display: inline-block;'>\n";
		echo "				<input type='text' class='txt' style='width: 150px' name='search' value='".escape($search)."'>";
		echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
		echo "				</form>\n";
		if ($paging_controls_mini != '') {
			echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
		}
	}	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' valign='top'>";
	if (!$is_included) {
		echo $text['description-call_routing']."<br />";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['table-extension']."</th>\n";
	if (permission_exists('call_forward')) { echo "<th>".$text['label-call-forward']."</th>\n"; }
	if (permission_exists('follow_me')) { echo "<th>".$text['label-follow-me']."</th>\n"; }
	if (permission_exists('do_not_disturb')) { echo "<th>".$text['label-dnd']."</th>\n"; }
	echo "<th>".$text['label-description']."</th>\n";
	echo "	<td class='list_control_icon'>&nbsp;</td>\n";
	echo "</tr>\n";

	if (is_array($extensions)) {
		foreach($extensions as $row) {
			$tr_url = PROJECT_PATH."/app/calls/call_edit.php?id=".$row['extension_uuid']."&return_url=".urlencode($_SERVER['REQUEST_URI']);
			$tr_link = (permission_exists('call_forward') || permission_exists('follow_me') || permission_exists('do_not_disturb')) ? "href='".$tr_url."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'><a ".$tr_link.">".escape($row['extension'])."</a></td>\n";
			if (permission_exists('call_forward')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".(($row['forward_all_enabled'] == 'true') ? escape(format_phone($row['forward_all_destination'])) : '&nbsp;')."</td>";
			}
			if (permission_exists('follow_me')) {
				if (is_uuid($row['follow_me_uuid'])) {
					//get destination count if enabled
					$follow_me_destination_count = 0;
					if ($row['follow_me_enabled'] == 'true') {
						$sql = "select count(follow_me_destination_uuid) as destination_count ";
						$sql .= "from v_follow_me_destinations ";
						$sql .= "where follow_me_uuid = :follow_me_uuid ";
						$sql .= "and domain_uuid = :domain_uuid ";
						$parameters['follow_me_uuid'] = $row['follow_me_uuid'];
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$database = new database;
						$follow_me_destination_count = $database->select($sql, $parameters, 'column');
					}
				}
				echo "	<td valign='top' class='".$row_style[$c]."'>\n";
				if ($row['follow_me_enabled'] == 'true' && $follow_me_destination_count > 0) {
					echo '		'.$text['label-enabled']." (".$follow_me_destination_count.")\n";
				}
				else {
					echo "		&nbsp;\n";
				}
				echo "</td>\n";
			}
			if (permission_exists('do_not_disturb')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".(($row['do_not_disturb'] == 'true') ? $text['label-enabled'] : '&nbsp;')."</td>";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['description'])."&nbsp;</td>\n";

			echo "	<td class='list_control_icon'><a href='".$tr_url."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a></td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		} //end foreach
		unset($sql, $extensions);
	} //end if results

	echo "</table>";
	echo "<br />";

	if (strlen($paging_controls) > 0 && (!$is_included)) {
		echo "<center>".$paging_controls."</center>\n";
		echo "<br /><br />\n";
	}

	if (!$is_included) {
		echo "<br />";
		require_once "resources/footer.php";
	}

?>
