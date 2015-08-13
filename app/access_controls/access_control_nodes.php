<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('access_control_node_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-access_control_nodes']."</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_access_control_nodes ";
		$sql .= "where access_control_uuid = '$access_control_uuid' ";
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
		$rows_per_page = 10;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $page; 

	//get the list
		$sql = "select * from v_access_control_nodes ";
		$sql .= "where access_control_uuid = '$access_control_uuid' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('node_type', $text['label-node_type'], $order_by, $order);
	echo th_order_by('node_cidr', $text['label-node_cidr'], $order_by, $order);
	echo th_order_by('node_domain', $text['label-node_domain'], $order_by, $order);
	echo th_order_by('node_description', $text['label-node_description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('access_control_node_add')) {
		echo "<a href='access_control_node_edit.php?access_control_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			if (permission_exists('access_control_node_edit')) {
				$tr_link = "href='access_control_node_edit.php?access_control_uuid=".$row['access_control_uuid']."&id=".$row['access_control_node_uuid']."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['node_type']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['node_cidr']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['node_domain']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['node_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('access_control_node_edit')) {
				echo "<a href='access_control_node_edit.php?access_control_uuid=".$row['access_control_uuid']."&id=".$row['access_control_node_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('access_control_node_delete')) {
				echo "<a href='access_control_node_delete.php?access_control_uuid=".$row['access_control_uuid']."&id=".$row['access_control_node_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('access_control_node_add')) {
		echo 		"<a href='access_control_node_edit.php?access_control_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo 		"&nbsp;";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>