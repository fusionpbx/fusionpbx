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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	

//check permissions
	if (permission_exists('multi_node_view')) {
		//echo "access granted";exit;
	}
	else {
		echo "access denied";
		exit;
	}
?>
<style type="text/css">

</style>
<?php
//get the registrations
/**	if (permission_exists('extension_registered')) {
		//create the event socket connection
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if (!$fp) {
			$msg = "<div align='center'>".$text['error-event-socket']."<br /></div>";
		}
		$registrations = get_registrations('internal');
		//order the array
		require_once "resources/classes/array_order.php";
		$order = new array_order();
		$registrations = $order->sort($registrations, 'sip-auth-realm', 'user');
	}**/

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$search = check_str($_GET["search"]);
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//handle search term
	$search = check_str($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "name like '%".$search."%' ";
		$sql_mod .= "or switch_name like '%".$search."%' ";
		$sql_mod .= "or hostname like '%".$search."%' ";
		$sql_mod .= "or virtualhost like '%".$search."%' ";
		$sql_mod .= "or username like '%".$search."%' ";
		$sql_mod .= "or port like '%".$search."%' ";
		$sql_mod .= "or exchange_name like '%".$search."%' ";
		$sql_mod .= "or exchange_type like '%".$search."%' ";
		$sql_mod .= "or circuit_breaker_ms like '%".$search."%' ";
		$sql_mod .= "or reconnect_interval_ms like '%".$search."%' ";
		$sql_mod .= "or send_queue_size like '%".$search."%' ";
		$sql_mod .= "or enable_fallback_format_fields like '%".$search."%' ";
		$sql_mod .= "or format_fields like '%".$search."%' ";
		$sql_mod .= "or event_filter like '%".$search."%' ";
		$sql_mod .= ") ";
	}

//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-extensions'];
	require_once "resources/paging.php";

//get total extension count from the database
	$sql = "select ";
	$sql .= "(select count(*) from v_multinode where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod.") as num_rows ";
	if ($db_type == "pgsql") {
		$sql .= ",(select count(*) as count from v_multinode ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "as numeric_multinode ";

	}

	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_multinodes = $row['num_rows'];
		if (($db_type == "pgsql") or ($db_type == "mysql")) {
			$numeric_extensions = $row['numeric_multinode'];
		}
	}

	unset($prep_statement, $row);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($total_multinodes, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($total_multinodes, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];

//to cast or not to cast
	if ($db_type == "pgsql") {
		$order_text = "hostname asc";
	}
	else {
		$order_text = "hostname asc";
	}

//get the extensions
	$sql = "select * from v_multinode ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= $sql_mod; //add search mod from above
	if (strlen($order_by) > 0) {
		$sql .= ($order_by == 'name') ? "order by $order_text ".$order." " : "order by ".$order_by." ".$order." ";
	}
	else {
		$sql .= "order by $order_text ";
	}
	$sql .= "limit $rows_per_page offset $offset ";

	//echo $sql;exit;

	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$multi_nodes = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

	// die(print_r($multi_nodes));
//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-multinode']." (".$total_multinodes.")</b><br>\n";
	echo "	</td>\n";

	?>
		
	<?php

	echo "		<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	if ($paging_controls_mini != '') {
		echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "			</td>\n";
	echo "		</form>\n";
	echo "  </tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-multinode-title']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	echo "<form name='frm' method='post' action='multi_node_delete.php'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('multi_node_delete') && is_array($multi_nodes)) {
		echo "<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' id='chk_all' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('name', $text['label-tbl-name'], $order_by, $order);
	echo th_order_by('hostname', $text['label-tbl-host-name'], $order_by, $order);
	echo th_order_by('virtualhost', $text['label-tbl-virtual-host'], $order_by, $order);
	echo th_order_by('username', $text['label-tbl-username'], $order_by, $order);
	// echo th_order_by('user_context', $text['label-tbl-password'], $order_by, $order);
	echo th_order_by('port', $text['label-tbl-port'], $order_by, $order);


	echo "<td class='list_control_icon'>\n";
	if (permission_exists('multi_node_add')) {
		if ($_SESSION['limit']['extensions']['numeric'] == '' || ($_SESSION['limit']['extensions']['numeric'] != '' && $total_multinodes < $_SESSION['limit']['extensions']['numeric'])) {
			echo "<a href='multi_node_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	if (permission_exists('multi_node_delete') && is_array($multi_nodes)) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($multi_nodes)) {

		foreach($multi_nodes as $row) {
			$tr_link = (permission_exists('multi_node_edit')) ? " href='multi_node_edit.php?id=".$row['multinode_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			if (permission_exists('multi_node_delete')) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
				echo "		<input type='checkbox' name='id[]' id='checkbox_".$row['multinode_uuid']."' value='".$row['multinode_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all').checked = false; }\">";
				echo "	</td>";
				$ext_ids[] = 'checkbox_'.$row['multinode_uuid'];
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('multi_node_edit')) {
				echo "<a href='multi_node_edit.php?id=".$row['multinode_uuid']."'>".$row['name']."</a>";
			}
			else {
				echo $row['name'];
			}
			echo "</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['hostname']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['voicemail_mail_to']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtualhost']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['username']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['port']."</td>\n";

			/**if (permission_exists('extension_registered')) {
 				echo "	<td valign='top' class='".$row_style[$c]."'>";
 				$found = false;
 				$found_count = 0;
 				foreach ($registrations as $arr) {
 					if (in_array($row['extension'],$arr)) {
 						$found = true;
 						$found_count++;
 					}
 				}
 				if ($found) {
 					echo "Yes ($found_count)";
 				} else {
 					echo "No";
 				}
 				echo "&nbsp;</td>\n";
 			}**/

			// echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['enabled'])."</td>\n";

			echo "	<td class='list_control_icons'>";
			if (permission_exists('multi_node_edit')) {
				echo "<a href='multi_node_edit.php?id=".$row['multinode_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('multi_node_delete')) {
				echo "<a href='multi_node_delete.php?id[]=".$row['multinode_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
		unset($multi_nodes, $row);
	}

	if (is_array($multi_nodes)) {
		echo "<tr>\n";
		echo "	<td colspan='20' class='list_control_icons'>\n";
		if (permission_exists('multi_node_add')) {
			if ($_SESSION['limit']['extensions']['numeric'] == '' || ($_SESSION['limit']['extensions']['numeric'] != '' && $total_multinodes < $_SESSION['limit']['extensions']['numeric'])) {
				echo "<a href='multi_node_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
			}
		}
		if (permission_exists('multi_node_delete')) {
			echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
		}
		echo "	</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "</form>";

	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}

	echo "<br /><br />".((is_array($multi_nodes)) ? "<br /><br />" : null);

	// check or uncheck all checkboxes
	if (sizeof($ext_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		echo "		document.getElementById('chk_all').checked = (what == 'all') ? true : false;\n";
		foreach ($ext_ids as $ext_id) {
			echo "		document.getElementById('".$ext_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	if (is_array($multi_nodes)) {
		// check all checkboxes
		key_press('ctrl+a', 'down', 'document', null, null, "check('all');", true);

		// delete checked
		key_press('delete', 'up', 'document', array('#search'), $text['confirm-delete'], 'document.forms.frm.submit();', true);
	}

//show the footer
	require_once "resources/footer.php";
?>
