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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
if (permission_exists('contact_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//includes and title
	$document['title'] = $text['title-contacts'];
	require_once "resources/header.php";

//get the search criteria
	$search_all = strtolower(check_str($_GET["search_all"]));
	$phone_number = check_str($_GET["phone_number"]);

//get variables used to control the order
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//retrieve current user's assigned groups (uuids)
	foreach ($_SESSION['groups'] as $group_data) {
		$user_group_uuids[] = $group_data['group_uuid'];
	}

//add user's uuid to group uuid list to include private (non-shared) contacts
	$user_group_uuids[] = $_SESSION["user_uuid"];

//get contact sync sources
	$sql = "select ";
	$sql .= "contact_uuid, ";
	$sql .= "contact_setting_value ";
	$sql .= "from ";
	$sql .= "v_contact_settings ";
	$sql .= "where ";
	$sql .= "domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and contact_setting_category = 'sync' ";
	$sql .= "and contact_setting_subcategory = 'source' ";
	$sql .= "and contact_setting_name = 'array' ";
	$sql .= "and contact_setting_value <> '' ";
	$sql .= "and contact_setting_value is not null ";
	if (!(if_group("superadmin") || if_group("admin"))) {
		$sql .= "and ( \n"; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	contact_uuid in ( \n";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where group_uuid in ('".implode("','", $user_group_uuids)."') ";
		$sql .= "		and domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "	) \n";
		$sql .= "	or \n";
		$sql .= "	contact_uuid not in ( \n";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where user_uuid = '".$_SESSION['user_uuid']."' ";
		$sql .= "		and domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "	) \n";
		$sql .= ") \n";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (count($result) > 0) {
		foreach($result as $row) {
			$contact_sync_sources[$row['contact_uuid']][] = $row['contact_setting_value'];
		}
	}
	unset ($sql, $prep_statement, $result);

//build query for paging and list
	$sql = "select count(*) as num_rows ";
	$sql .= "from v_contacts as c ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	if (!(if_group("superadmin") || if_group("admin"))) {
		$sql .= "and ( \n"; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	contact_uuid in ( \n";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where group_uuid in ('".implode("','", $user_group_uuids)."') ";
		$sql .= "		and domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "	) \n";
		$sql .= "	or contact_uuid in ( \n";
		$sql .= "		select contact_uuid from v_contact_users ";
		$sql .= "		where user_uuid = '".$_SESSION['user_uuid']."' ";
		$sql .= "		and domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "";
		$sql .= "	) \n";
		$sql .= ") \n";
	}
	if (strlen($phone_number) > 0) {
		$phone_number = preg_replace('{\D}', '', $phone_number);
		$sql .= "and contact_uuid in ( ";
		$sql .= "	select contact_uuid from v_contact_phones ";
		$sql .= "	where phone_number like '%".$phone_number."%' ";
		$sql .= ") \n";
	}
	else {
		if (strlen($search_all) > 0) {
			if (is_numeric($search_all)) {
				$sql .= "and contact_uuid in ( \n";
				$sql .= "	select contact_uuid from v_contact_phones ";
				$sql .= "	where phone_number like '%".$search_all."%' ";
				$sql .= ") \n";
			}
			else {
				$sql .= "and contact_uuid in ( \n";
				$sql .= "	select contact_uuid from v_contacts ";
				$sql .= "	where domain_uuid = '".$_SESSION['domain_uuid']."' \n";
				$sql .= "	and ( \n";
				$sql .= "		lower(contact_organization) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_name_given) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_name_family) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_nickname) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_title) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_category) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_role) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_url) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_time_zone) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_note) like '%".$search_all."%' or \n";
				$sql .= "		lower(contact_type) like '%".$search_all."%' \n";
				$sql .= "	) \n";
				$sql .= ") \n";
			}
		}
	}
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
	$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*) as num_rows', '*', $sql); // modify query created above
	if (strlen($order_by) > 0) {
		$sql .= "order by ".$order_by." ".$order." ";
	}
	else {
		$sql .= "order by last_mod_date desc ";
		if ($db_type == "pgsql") {
			$sql .= "nulls last ";
		}
	}
	$sql .= "limit ".$rows_per_page." offset ".$offset." ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "	<td align='left' valign='top' width='50%'>\n";
	echo "		<b>".$text['header-contacts']."</b>\n";
	echo "		<br /><br />";
	echo "		".$text['description-contacts']."<br /><br />\n";
	echo "	</td>\n";
	echo "	<td align='right' valign='top' width='50%'>\n";
	echo "		<table border='0' cellpadding='0' cellspacing='0'>\n";
	echo "			<tr>\n";
	echo "				<td valign='middle' nowrap>\n";
	echo "					<form method='get' name='frm_search' action=''>\n";
	echo "					<input class='formfld' style='text-align: right;' type='text' name='search_all' id='search_all' value=\"".$search_all."\">\n";
	echo "					<input class='btn' type='submit' name='submit' value=\"".$text['button-search']."\">\n";
	if (permission_exists('contact_add')) {
		echo 				"<input type='button' class='btn' alt='".$text['button-import']."' onclick=\"window.location='contact_import.php'\" value='".$text['button-import']."'>\n";
	}
	echo "					</form>\n";
	echo "				</td>\n";
	if ($paging_controls_mini != '') {
		echo "			<td valign='middle' nowrap style='padding-left: 15px;'>".$paging_controls_mini."</td>\n";
	}
	echo "			</tr>\n";
	echo "		</table>\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('contact_type', $text['label-contact_type'], $order_by, $order);
	echo th_order_by('contact_organization', $text['label-contact_organization'], $order_by, $order);
	echo th_order_by('contact_name_given', $text['label-contact_name_given'], $order_by, $order);
	echo th_order_by('contact_name_family', $text['label-contact_name_family'], $order_by, $order);
	echo th_order_by('contact_nickname', $text['label-contact_nickname'], $order_by, $order);
	echo th_order_by('contact_title', $text['label-contact_title'], $order_by, $order);
	echo th_order_by('contact_role', $text['label-contact_role'], $order_by, $order);
	echo "<th style='padding: 0px;'>&nbsp;</th>\n";
	echo "<td class='list_control_icons'>";
	echo 	"<a href='contact_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = "href='contact_edit.php?id=".$row['contact_uuid']."&query_string=".urlencode($_SERVER["QUERY_STRING"])."'";
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['contact_type'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='width: 35%; max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'><a href='contact_edit.php?id=".$row['contact_uuid']."&query_string=".urlencode($_SERVER["QUERY_STRING"])."'>".$row['contact_organization']."</a>&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='white-space: nowrap;'><a href='contact_edit.php?id=".$row['contact_uuid']."&query_string=".urlencode($_SERVER["QUERY_STRING"])."'>".$row['contact_name_given']."</a>&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='white-space: nowrap;'><a href='contact_edit.php?id=".$row['contact_uuid']."&query_string=".urlencode($_SERVER["QUERY_STRING"])."'>".$row['contact_name_family']."</a>&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='white-space: nowrap;'>".$row['contact_nickname']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='width: 10%; max-width: 40px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>".$row['contact_title']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='width: 10%; max-width: 40px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>".$row['contact_role']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='padding: 2px 2px; text-align: center; width: 25px;'>";
				if (sizeof($contact_sync_sources[$row['contact_uuid']]) > 0) {
					foreach ($contact_sync_sources[$row['contact_uuid']] as $contact_sync_source) {
						switch ($contact_sync_source) {
							case 'google': echo "<img src='resources/images/icon_gcontacts.png' style='width: 21px; height: 21px; border: none; padding-left: 2px;' alt='".$text['label-contact_google']."'>"; break;
						}
					}
				}
				else { echo "&nbsp;"; }
			echo "	</td>\n";
			echo "	<td class='list_control_icons'>";
			echo 		"<a href='contact_edit.php?id=".$row['contact_uuid']."&query_string=".urlencode($_SERVER["QUERY_STRING"])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			echo 		"<a href='contact_delete.php?id=".$row['contact_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='15' align='right'>\n";
	echo "	<a href='contact_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";

	echo $paging_controls;
	echo "<br /><br />";

	echo "<script>document.getElementById('search_all').focus();</script>";

//include the footer
	require_once "resources/footer.php";
?>