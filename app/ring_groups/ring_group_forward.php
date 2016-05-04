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
	Portions created by the Initial Developer are Copyright (C) 2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ring_group_edit') || permission_exists('ring_group_forward')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/ring_groups');

require_once "resources/header.php";
require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//update ring group forwarding
	if (sizeof($_POST) > 0) {
		$ring_groups = $_POST['ring_group_forward_enabled'];
		$destinations = $_POST['ring_group_forward_destination'];

		if (is_array($ring_groups) && sizeof($ring_groups) > 0) {
			foreach ($ring_groups as $ring_group_uuid => $ring_group_forward_enabled) {
			//remove non-numeric characters
				$ring_group_foreward_destination = preg_replace("~[^0-9]~", "", $destinations[$ring_group_uuid]);
			//update the ring group
				$sql = "update v_ring_groups set ";
				$sql .= "ring_group_forward_enabled = '".check_str($ring_group_forward_enabled)."', ";
				$sql .= "ring_group_forward_destination = '".check_str($ring_group_foreward_destination)."' ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and ring_group_uuid = '".$ring_group_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);
			}
			//redirect the user
				$_SESSION["message"] = $text['message-update'];
				header("Location: ".$_REQUEST['return_url']);
				exit;
		}
	}

//prepare to page the results
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		//show all ring groups
		$sql = "select count(*) as num_rows from v_ring_groups ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
	}
	else {
		//show only assigned fax extensions
		$sql = "select count(*) as num_rows from v_ring_groups as r, v_ring_group_users as u ";
		$sql .= "where r.ring_group_uuid = u.ring_group_uuid ";
		$sql .= "and r.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
	}
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
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
	if ($is_included) {
		$rows_per_page = 10;
	}
	else {
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	}
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		//show all ring groups
		$sql = "select * from v_ring_groups ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
	}
	else {
		//show only assigned ring groups
		$sql = "select r.ring_group_uuid, r.ring_group_extension, r.ring_group_description from v_ring_groups as r, v_ring_group_users as u ";
		$sql .= "where r.ring_group_uuid = u.ring_group_uuid ";
		$sql .= "and r.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
	}
	if (strlen($order_by) == 0) {
		$sql .= "order by ring_group_extension asc ";
	}
	else {
		$sql .= "order by ".$order_by." ".$order." ";
	}
	$sql .= " limit ".$rows_per_page." offset ".$offset." ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	$result_count = count($result);
	unset ($prep_statement, $sql);

	if ($num_rows > 10) {
		echo "	<div style='float: right;'><input id='btn_viewall_ringgroups' type='button' class='btn' value='".$text['button-view_all']."' onclick=\"document.location.href='".PROJECT_PATH."/app/ring_groups/ring_group_forward.php';\"></div>\n";
	}
	echo "	<div style='float: left;'>";
	echo "		<b>".$text['header-ring-group-forward']."</b><br />";
	if (!$is_included) {
		echo "	".$text['description-ring-group-forward']."<br />";
	}
	echo "	<br />";
	echo "	</div>\n";

	echo "<form method='post' name='frm' action='".PROJECT_PATH."/app/ring_groups/ring_group_forward.php'>\n";
	echo "<input type='hidden' name='return_url' value='".$_SERVER['REQUEST_URI']."'>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('ring_group_name', $text['label-name'], $order_by, $order);
	echo th_order_by('ring_group_extension', $text['label-extension'], $order_by, $order);
	echo "<th>".$text['label-forwarding']."</th>";
	if (!$is_included) {
		echo th_order_by('ring_group_description', $text['label-description'], $order_by, $order);
	}
	echo "</tr>\n";

	$c = 0;
	if ($result_count > 0) {
		foreach($result as $row) {
			$onclick = "onclick=\"document.getElementById('".$row['ring_group_uuid']."').selectedIndex = (document.getElementById('".$row['ring_group_uuid']."').selectedIndex) ? 0 : 1; if (document.getElementById('".$row['ring_group_uuid']."').selectedIndex) { document.getElementById('destination').focus(); }\"";
			echo "<tr>\n";
			echo "	<td valign='top' class='row_style".$c."' ".$onclick.">".$row['ring_group_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_style".$c."' ".$onclick.">".$row['ring_group_extension']."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_style".$c." row_style_slim' width='5'>";
			echo "		<select class='formfld' name='ring_group_forward_enabled[".$row['ring_group_uuid']."]' id='".$row['ring_group_uuid']."' onchange=\"(this.selectedIndex == 1) ? document.getElementById('destination').focus() : null;\">";
			echo "			<option value='false'>".$text['option-disabled']."</option>";
			echo "			<option value='true' ".(($row["ring_group_forward_enabled"] == 'true') ? "selected='selected'" : null).">".$text['option-enabled']."</option>";
			echo "		</select>";
			echo 		"<input class='formfld' style='min-width: 95px;' type='text' name='ring_group_forward_destination[".$row['ring_group_uuid']."]' id='destination' placeholder=\"".$text['label-forward_destination']."\" maxlength='255' value=\"".$row["ring_group_forward_destination"]."\">";
			echo "	</td>\n";
			if (!$is_included) {
				echo "	<td valign='top' class='row_stylebg tr_link_void' ".$onclick.">".$row['ring_group_description']."&nbsp;</td>\n";
			}
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
		unset($sql, $result, $row_count);
	}

	echo "</table>";
	echo "<br>";

	if ($result_count > 0) {
		echo "<div style='float: right;'><input type='submit' class='btn' value='".$text['button-save']."'></div>\n";
		echo "<br><br>";
	}

	echo "</form>";

	if (!$is_included) {
		echo "<center>".$paging_controls."</center>\n";
		echo "<br><br>";
	}

//include the footer
	require_once "resources/footer.php";
?>