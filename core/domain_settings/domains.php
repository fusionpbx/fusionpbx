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
 Portions created by the Initial Developer are Copyright (C) 2008-2012
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('domain_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//change the domain
	if (strlen(check_str($_GET["domain_uuid"])) > 0 && check_str($_GET["domain_change"]) == "true") {
		if (permission_exists('domain_select')) {
			//get the domain_uuid
				$sql = "select * from v_domains ";
				$sql .= "order by domain_name asc ";
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach($result as $row) {
					if (count($result) == 0) {
						$_SESSION["domain_uuid"] = $row["domain_uuid"];
						$_SESSION["domain_name"] = $row['domain_name'];
					}
					else {
						if ($row['domain_name'] == $domain_array[0] || $row['domain_name'] == 'www.'.$domain_array[0]) {
							$_SESSION["domain_uuid"] = $row["domain_uuid"];
							$_SESSION["domain_name"] = $row['domain_name'];
						}
						$_SESSION['domains'][$row['domain_uuid']]['domain_uuid'] = $row['domain_uuid'];
						$_SESSION['domains'][$row['domain_uuid']]['domain_name'] = $row['domain_name'];
						$_SESSION['domains'][$row['domain_uuid']]['domain_description'] = $row['domain_description'];
					}
				}
				unset($result, $prep_statement);

			//update the domain session variables
				$domain_uuid = check_str($_GET["domain_uuid"]);
				$_SESSION['domain_uuid'] = $domain_uuid;
				$_SESSION["domain_name"] = $_SESSION['domains'][$domain_uuid]['domain_name'];
				$_SESSION['domain']['template']['name'] = $_SESSION['domains'][$domain_uuid]['template_name'];

			//clear the extension array so that it is regenerated for the selected domain
				unset($_SESSION['extension_array']);

			//set the setting arrays
				$domain = new domains();
				$domain->db = $db;
				$domain->set();

			// on domain change, redirect user
				if ($_SESSION["login"]["destination"] != '') {
					// to default, or domain specific, login destination
					header("Location: ".PROJECT_PATH.$_SESSION["login"]["destination"]["url"]);
				}
				else {
					header("Location: ".PROJECT_PATH."/core/user_settings/user_dashboard.php");
				}
				return;
		}
	}

//includes
	require_once "resources/header.php";
	$document['title'] = $text['title-domains'];

	require_once "resources/paging.php";

//get the http values and set them as variables
	$search = check_str($_GET["search"]);
	if (isset($_GET["order_by"])) {
		$order_by = check_str($_GET["order_by"]);
		$order = check_str($_GET["order"]);
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

//show the header and the search
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-domains']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "		<td width='50%' align='right'>\n";
	echo "			<input type='text' class='txt' style='width: 150px' name='search' value='$search'>";
	echo "			<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "		</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-domains']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

//prepare to page the results
	$sql = "select count(*) as num_rows from v_domains ";
	if (strlen($search) > 0) {
		$sql .= "where (";
		$sql .= " 	domain_name like '%".$search."%' ";
		$sql .= " 	or domain_description like '%".$search."%' ";
		$sql .= ") ";
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
	$rows_per_page = 100;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the  list
	$sql = "select * from v_domains ";
	if (strlen($search) > 0) {
		$sql .= "where (";
		$sql .= " 	domain_name like '%".$search."%' ";
		$sql .= " 	or domain_description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (strlen($order_by) == 0) {
		$sql .= "order by domain_name asc ";
	}
	else {
		$sql .= "order by $order_by $order ";
	}
	$sql .= " limit $rows_per_page offset $offset ";
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
	echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	echo "<th>".$text['label-tools']."</th>";
	echo th_order_by('domain_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('domain_add')) {
		echo "<a href='domain_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('domain_edit')) ? "href='domain_edit.php?id=".$row['domain_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo "		<a href='domain_edit.php?id=".$row['domain_uuid']."'>".$row['domain_name']."</a>";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('domain_edit')) {
				echo "<a href='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".$row['domain_uuid']."&domain_change=true'>".$text['label-manage']."</a>";
			}
			echo "	</td>";
			echo "	<td valign='top' class='row_stylebg'>".$row['domain_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('domain_edit')) {
				echo "<a href='domain_edit.php?id=".$row['domain_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('domain_delete')) {
				if ($_SESSION["groups"][0]["domain_uuid"] != $row['domain_uuid'] && $result_count > 1) {
					echo "<a href='domain_delete.php?id=".$row['domain_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
				}
				else {
					echo "<span onclick=\"alert('You cannot delete your own domain.\\n\\nPlease login with a user account under a different domain, then try again.');\">".$v_link_label_delete."</span>";
				}
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='4' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('domain_add')) {
		echo "<a href='domain_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";
	echo "<br /><br />";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>