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
	Portions created by the Initial Developer are Copyright (C) 2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_file_view')) {
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

//get variables used to control the order
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//get fax extension
	if (strlen($_GET['id']) > 0) {
		if (is_uuid($_GET["id"])) {
			$fax_uuid = $_GET["id"];
		}
		if (if_group("superadmin") || if_group("admin")) {
			//show all fax extensions
			$sql = "select * from v_fax ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and fax_uuid = '$fax_uuid' ";
		}
		else {
			//show only assigned fax extensions
			$sql = "select * from v_fax as f, v_fax_users as u ";
			$sql .= "where f.fax_uuid = u.fax_uuid ";
			$sql .= "and f.domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and f.fax_uuid = '$fax_uuid' ";
			$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
		}
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (count($result) == 0) {
			if (if_group("superadmin") || if_group("admin")) {
				//allow access
			}
			else {
				echo "access denied";
				exit;
			}
		}
		foreach ($result as &$row) {
			//set database fields as variables
				$fax_name = $row["fax_name"];
				$fax_extension = $row["fax_extension"];
			//limit to one row
				break;
		}
		unset ($prep_statement);
	}

//set the fax directory
	$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax'.((count($_SESSION["domains"]) > 1) ? '/'.$_SESSION['domain_name'] : null);

//get the fax extension
	if (strlen($fax_extension) > 0) {
		//set the fax directories. example /usr/local/freeswitch/storage/fax/329/inbox
			$dir_fax_inbox = $fax_dir.'/'.$fax_extension.'/inbox';
			$dir_fax_sent = $fax_dir.'/'.$fax_extension.'/sent';
			$dir_fax_temp = $fax_dir.'/'.$fax_extension.'/temp';

		//make sure the directories exist
			if (!is_dir($_SESSION['switch']['storage']['dir'])) {
				mkdir($_SESSION['switch']['storage']['dir']);
				chmod($dir_fax_sent,0774);
			}
			if (!is_dir($fax_dir.'/'.$fax_extension)) {
				mkdir($fax_dir.'/'.$fax_extension,0774,true);
				chmod($fax_dir.'/'.$fax_extension,0774);
			}
			if (!is_dir($dir_fax_inbox)) {
				mkdir($dir_fax_inbox,0774,true);
				chmod($dir_fax_inbox,0774);
			}
			if (!is_dir($dir_fax_sent)) {
				mkdir($dir_fax_sent,0774,true);
				chmod($dir_fax_sent,0774);
			}
			if (!is_dir($dir_fax_temp)) {
				mkdir($dir_fax_temp,0774,true);
				chmod($dir_fax_temp,0774);
			}
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

//show the header
	//$text['title-fax_files']
	//$text['description-fax_file']
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top'>\n";
	if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
		echo "			<b>".$text['header-inbox'].": <span style='color: #000;'>".$fax_name." (".$fax_extension.")</span></b>\n";
	}
	if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
		echo "			<b>".$text['header-sent'].": <span style='color: #000;'>".$fax_name." (".$fax_extension.")</span></b>\n";
	}
	echo "		</td>\n";
	echo "		<td width='70%' align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='fax.php'\" value='".$text['button-back']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

//prepare to page the results
	$sql = "select count(*) as num_rows from v_fax_files ";
	$sql .= "where fax_uuid = '$fax_uuid' ";
	$sql .= "and domain_uuid = '$domain_uuid' ";
	if ($_REQUEST['box'] == 'inbox') {
		$sql .= "and fax_mode = 'rx' ";
	}
	if ($_REQUEST['box'] == 'sent') {
		$sql .= "and fax_mode = 'tx' ";
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
	$rows_per_page = 10;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); 
	$offset = $rows_per_page * $page; 

//get the list
	$sql = "select * from v_fax_files ";
	$sql .= "where fax_uuid = '$fax_uuid' ";
	$sql .= "and domain_uuid = '$domain_uuid' ";
	if ($_REQUEST['box'] == 'inbox') {
		$sql .= "and fax_mode = 'rx' ";
	}
	if ($_REQUEST['box'] == 'sent') {
		$sql .= "and fax_mode = 'tx' ";
	}
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	$sql .= "limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

//show the table and content
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th width=''>".$text['table-file']."</th>\n";
	echo "<th width='10%'>".$text['table-view']."</th>\n";
	echo th_order_by('fax_number', $text['label-fax_number'], $order_by, $order);
	//echo th_order_by('fax_file_type', $text['label-fax_file_type'], $order_by, $order);
	//echo th_order_by('fax_file_path', $text['label-fax_file_path'], $order_by, $order);
	echo th_order_by('fax_caller_id_name', $text['label-fax_caller_id_name'], $order_by, $order);
	echo th_order_by('fax_caller_id_number', $text['label-fax_caller_id_number'], $order_by, $order);
	echo th_order_by('fax_date', $text['label-fax_date'], $order_by, $order);
	//echo th_order_by('fax_epoch', $text['label-fax_epoch'], $order_by, $order);
	//echo th_order_by('fax_base64', $text['label-fax_base64'], $order_by, $order);
	echo "<td>&nbsp;</td>\n";
	echo "<tr>\n";
	if ($result_count > 0) {
		foreach($result as $row) {
			$file = basename($row['fax_file_path']);
			if (strtolower(substr($file, -3)) == "tif" || strtolower(substr($file, -3)) == "pdf") {
				$file_name = substr($file, 0, (strlen($file) -4));
			}
			$file_ext = $row['fax_file_type'];

			//decode the base64
			if (strlen($row['fax_base64']) > 0) {
				if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
					if (!file_exists($dir_fax_inbox.'/'.$file)) {
						file_put_contents($dir_fax_inbox.'/'.$file, base64_decode($row['fax_base64']));
					}
				}
				if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
					if (!file_exists($dir_fax_sent.'/'.$file)) {
						//decode the base64
						file_put_contents($dir_fax_sent.'/'.$file, base64_decode($row['fax_base64']));
					}
				}
				
			}
			//convert the tif to pdf
			if (!file_exists($dir_fax_inbox.'/'.$file_name.".pdf")) {
				if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
					chdir($dir_fax_inbox);
					if (is_file("/usr/local/bin/tiff2pdf")) {
						exec("/usr/local/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_inbox.'/'.$file_name.".tif");
					}
					if (is_file("/usr/bin/tiff2pdf")) {
						exec("/usr/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_inbox.'/'.$file_name.".tif");
					}
				}
				if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
					chdir($dir_fax_sent);
					if (is_file("/usr/local/bin/tiff2pdf")) {
						exec("/usr/local/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_sent.'/'.$file_name.".tif");
					}
					if (is_file("/usr/bin/tiff2pdf")) {
						exec("/usr/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_sent.'/'.$file_name.".tif");
					}
				}
			}

			echo "<tr ".$tr_link.">\n";
			echo "<tr>\n";
			echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
			if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
				echo "	  <a href=\"fax_box.php?id=".$fax_uuid."&a=download&type=fax_inbox&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file)."\">\n";
			}
			if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
				echo "	  <a href=\"fax_box.php?id=".$fax_uuid."&a=download&type=fax_sent&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file)."\">\n";
			}
			echo "    	$file_name";
			echo "	  </a>";
			echo "  </td>\n";
			echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
			if ($_REQUEST['box'] == 'inbox') {
				$dir_fax = $dir_fax_inbox;
			}
			if ($_REQUEST['box'] == 'sent') {
				$dir_fax = $dir_fax_sent;
			}
			if (file_exists($dir_fax.'/'.$file_name.".pdf")) {
				if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
					echo "	  <a href=\"fax_box.php?id=".$fax_uuid."&a=download&type=fax_inbox&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file_name).".pdf\">\n";
				}
				if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
					echo "	  <a href=\"fax_box.php?id=".$fax_uuid."&a=download&type=fax_sent&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file_name).".pdf\">\n";
				}
				echo "    	PDF";
				echo "	  </a>";
			}
			else {
				echo "&nbsp;\n";
			}
			echo "  </td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".basename($row['fax_file_path'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>PDF&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_number']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_file_type']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_caller_id_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_caller_id_number']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".date("F d Y H:i:s", strtotime($row['fax_date']))."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_epoch']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_base64']."&nbsp;</td>\n";
			echo "	<td>";
			//if (permission_exists('fax_file_edit')) {
			//	echo "<a href='fax_file_edit.php?id=".$row['fax_file_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			//}
			if (permission_exists('fax_file_delete')) {
				echo "<a href='fax_file_delete.php?id=".$row['fax_file_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

//show the paging controls
	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	echo 		"&nbsp;";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//close the table and div
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>