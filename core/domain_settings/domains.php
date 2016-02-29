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

//redirect admin to app instead
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/") && !permission_exists('domain_parent') && permission_exists('domain_descendants')) {
		header("Location: ".PROJECT_PATH."/app/domains/domains.php");
	}

//check permission
	if (permission_exists('domain_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

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

			//redirect the user
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

//redirect the user
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/app/domains/domains.php")) {
		$href = '/app/domains/domains.php';
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

//prepare to page the results
	$sql = "select count(*) as num_rows from v_domains ";
	if (strlen($search) > 0) {
		$sql .= "where (";
		$sql .= " 	domain_name like '%".$search."%' ";
		$sql .= " 	or domain_description like '%".$search."%' ";
		$sql .= ") ";
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
	$rows_per_page = 100;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the domains
	$sql = "select * from v_domains ";
	if (strlen($search) > 0) {
		$sql .= "where (";
		$sql .= "	domain_name like '%".$search."%' ";
		$sql .= "	or domain_description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (strlen($order_by) == 0) {
		$sql .= "order by domain_name asc ";
	}
	else {
		$sql .= "order by ".$order_by." ".$order." ";
	}
	$sql .= " limit ".$rows_per_page." offset ".$offset." ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

	foreach ($result as $domain) {
		$domains[$domain['domain_uuid']]['name'] = $domain['domain_name'];
		$domains[$domain['domain_uuid']]['parent_uuid'] = $domain['domain_parent_uuid'];
		$domains[$domain['domain_uuid']]['enabled'] = $domain['domain_enabled'];
		$domains[$domain['domain_uuid']]['description'] = $domain['domain_description'];
	}

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the header and the search
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' valign='top' nowrap='nowrap'><b>".$text['header-domains']."</b></td>\n";
	echo "		<td width='50%' align='right' valign='top'>\n";
	echo "			<form method='get' action=''>\n";
	echo "			<input type='text' class='txt' style='width: 150px' name='search' value='$search'>";
	echo "			<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</form>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top' colspan='2'>\n";
	echo "			".$text['description-domains']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

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

	if (count($domains) > 0) {
		foreach ($domains as $domain_uuid => $domain) {
			$tr_link = (permission_exists('domain_edit')) ? "href='domain_edit.php?id=".$domain_uuid."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."' ".(($indent != 0) ? "style='padding-left: ".($indent * 20)."px;'" : null).">";
			echo "		<a href='domain_edit.php?id=".$domain_uuid."'>".$domain['name']."</a>";
			if ($domain['enabled'] != '' && $domain['enabled'] != 'true') {
				echo "	<span style='color: #aaa; font-size: 80%;'>&nbsp;&nbsp;(".$text['label-disabled'].")</span>";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('domain_edit')) {
				echo "<a href='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".$domain_uuid."&domain_change=true'>".$text['label-manage']."</a>";
			}
			echo "	</td>";
			echo "	<td valign='top' class='row_stylebg'>".$domain['description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('domain_edit')) {
				echo "<a href='domain_edit.php?id=".$domain_uuid."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
			}
			if (permission_exists('domain_delete')) {
				if ($_SESSION["groups"][0]["domain_uuid"] != $domain_uuid && count($domains) > 1) {
					echo "<a href='domain_delete.php?id=".$domain_uuid."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
				}
				else {
					echo "<span onclick=\"alert('You cannot delete your own domain.\\n\\nPlease login with a user account under a different domain, then try again.');\">".$v_link_label_delete."</span>";
				}
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c == 0) ? 1 : 0;
		}
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
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>