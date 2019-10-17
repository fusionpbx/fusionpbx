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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('fax_file_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get fax extension
	if (is_uuid($_GET["id"])) {
		$fax_uuid = $_GET["id"];
		if (if_group("superadmin") || if_group("admin")) {
			//show all fax extensions
			$sql = "select fax_name, fax_extension from v_fax ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and fax_uuid = :fax_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
		}
		else {
			//show only assigned fax extensions
			$sql = "select fax_name, fax_extension from v_fax as f, v_fax_users as u ";
			$sql .= "where f.fax_uuid = u.fax_uuid ";
			$sql .= "and f.domain_uuid = :domain_uuid ";
			$sql .= "and f.fax_uuid = :fax_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
			$parameters['user_uuid'] = $_SESSION['user_uuid'];
		}
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			//set database fields as variables
				$fax_name = $row["fax_name"];
				$fax_extension = $row["fax_extension"];
		}
		else {
			if (!if_group("superadmin") && !if_group("admin")) {
				echo "access denied";
				exit;
			}
		}
		unset($sql, $parameters, $row);
	}

//set the fax directory
	$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'];

//download the fax
	if ($_GET['a'] == "download") {
		session_cache_limiter('public');
		//test to see if it is in the inbox or sent directory.
		if ($_GET['type'] == "fax_inbox") {
			if (file_exists($fax_dir.'/'.$_GET['ext'].'/inbox/'.$_GET['filename'])) {
				$tmp_faxdownload_file = $fax_dir.'/'.$_GET['ext'].'/inbox/'.$_GET['filename'];
			}
		}
		else if ($_GET['type'] == "fax_sent") {
			if  (file_exists($fax_dir.'/'.$_GET['ext'].'/sent/'.$_GET['filename'])) {
				$tmp_faxdownload_file = $fax_dir.'/'.$_GET['ext'].'/sent/'.$_GET['filename'];
			}
		}
		//let's see if we found it
		if (strlen($tmp_faxdownload_file) > 0) {
			$fd = fopen($tmp_faxdownload_file, "rb");
			if ($_GET['t'] == "bin") {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; filename="'.$_GET['filename'].'"');
			}
			else {
				$file_ext = substr($_GET['filename'], -3);
				if ($file_ext == "tif") {
					header("Content-Type: image/tiff");
				}
				else if ($file_ext == "png") {
					header("Content-Type: image/png");
				}
				else if ($file_ext == "jpg") {
					header('Content-Type: image/jpeg');
				}
				else if ($file_ext == "pdf") {
					header("Content-Type: application/pdf");
				}
			}
			header('Accept-Ranges: bytes');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
			header("Content-Length: ".filesize($tmp_faxdownload_file));
			fpassthru($fd);
		}
		else {
			echo $text['label-file'];
		}
		exit;
	}

//get the fax extension
	if (strlen($fax_extension) > 0) {
		//set the fax directories. example /usr/local/freeswitch/storage/fax/329/inbox
			$dir_fax_inbox = $fax_dir.'/'.$fax_extension.'/inbox';
			$dir_fax_sent = $fax_dir.'/'.$fax_extension.'/sent';
			$dir_fax_temp = $fax_dir.'/'.$fax_extension.'/temp';

		//make sure the directories exist
			if (!is_dir($_SESSION['switch']['storage']['dir'])) {
				event_socket_mkdir($_SESSION['switch']['storage']['dir']);
			}
			if (!is_dir($fax_dir.'/'.$fax_extension)) {
				event_socket_mkdir($fax_dir.'/'.$fax_extension);
			}
			if (!is_dir($dir_fax_inbox)) {
				event_socket_mkdir($dir_fax_inbox);
			}
			if (!is_dir($dir_fax_sent)) {
				event_socket_mkdir($dir_fax_sent);
			}
			if (!is_dir($dir_fax_temp)) {
				event_socket_mkdir($dir_fax_temp);
			}
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) from v_fax_files ";
	$sql .= "where fax_uuid = :fax_uuid ";
	$sql .= "and domain_uuid = :domain_uuid ";
	if ($_REQUEST['box'] == 'inbox') {
		$sql .= "and fax_mode = 'rx' ";
	}
	if ($_REQUEST['box'] == 'sent') {
		$sql .= "and fax_mode = 'tx' ";
	}
	$parameters['fax_uuid'] = $fax_uuid;
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&id=".$_GET['id']."&box=".$_GET['box']."&order_by=".$_GET['order_by']."&order=".$_GET['order'];
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'fax_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$fax_files = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $num_rows);

//show the header
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top'>\n";
	if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
		echo "			<b>".$text['header-inbox'].": ".escape($fax_name)." (".escape($fax_extension).")</b>\n";
	}
	if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
		echo "			<b>".$text['header-sent'].": ".escape($fax_name)." (".escape($fax_extension).")</b>\n";
	}
	echo "		</td>\n";
	echo "		<td width='70%' align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='fax.php'\" value='".$text['button-back']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

//show the table and content
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the fax files
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('fax_caller_id_name', $text['label-fax_caller_id_name'], $order_by, $order, "&id=".$_GET['id']."&box=".$_GET['box']."&page=".$_GET['page']);
	echo th_order_by('fax_caller_id_number', $text['label-fax_caller_id_number'], $order_by, $order, "&id=".$_GET['id']."&box=".$_GET['box']."&page=".$_GET['page']);
	if ($_REQUEST['box'] == 'sent') {
		echo th_order_by('fax_destination', $text['label-fax_destination'], $order_by, $order, "&id=".$_GET['id']."&box=".$_GET['box']."&page=".$_GET['page']);
	}
	echo "<th width=''>".$text['table-file']."</th>\n";
	echo "<th width='10%'>".$text['table-view']."</th>\n";
	echo th_order_by('fax_date', $text['label-fax_date'], $order_by, $order, "&id=".$_GET['id']."&box=".$_GET['box']."&page=".$_GET['page']);
	echo "<td style='width: 25px;' class='list_control_icons'>&nbsp;</td>\n";
	echo "</tr>\n";
	if (is_array($fax_files) && @sizeof($fax_files) != 0) {
		foreach($fax_files as $row) {
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
			unset($dir_fax);
			if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
				if (!file_exists($dir_fax_inbox.'/'.$file_name.".pdf")) {
					$dir_fax = $dir_fax_inbox;
				}
			}
			if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
				if (!file_exists($dir_fax_sent.'/'.$file_name.".pdf")) {
					$dir_fax = $dir_fax_sent;
				}
			}
			if ($dir_fax != '') {
				chdir($dir_fax);
				//get fax resolution (ppi, W & H)
					$resp = exec("tiffinfo ".$file_name.".tif | grep 'Resolution:'");
					$resp_array = explode(' ', trim($resp));
					$ppi_w = (int) $resp_array[1];
					$ppi_h = (int) $resp_array[2];
					unset($resp_array);
					$gs_r = $ppi_w.'x'.$ppi_h; //used by ghostscript
				//get page dimensions/size (pixels/inches, W & H)
					$resp = exec("tiffinfo ".$file_name.".tif | grep 'Image Width:'");
					$resp_array = explode(' ', trim($resp));
					$pix_w = $resp_array[2];
					$pix_h = $resp_array[5];
					unset($resp_array);
					$gs_g = $pix_w.'x'.$pix_h; //used by ghostscript
					$page_width = $pix_w / $ppi_w;
					$page_height = $pix_h / $ppi_h;
					if ($page_width > 8.4 && $page_height > 13) {
						$page_width = 8.5;
						$page_height = 14;
						$page_size = 'legal';
					}
					else if ($page_width > 8.4 && $page_height < 12) {
						$page_width = 8.5;
						$page_height = 11;
						$page_size = 'letter';
					}
					else if ($page_width < 8.4 && $page_height > 11) {
						$page_width = 8.3;
						$page_height = 11.7;
						$page_size = 'a4';
					}
				//generate pdf (a work around, as tiff2pdf improperly inverts the colors)
					$cmd_tif2pdf = "tiff2pdf -i -u i -p ".$page_size." -w ".$page_width." -l ".$page_height." -f -o ".$dir_fax_temp.'/'.$file_name.".pdf ".$dir_fax.'/'.$file_name.".tif";
					//echo $cmd_tif2pdf."<br>";
					exec($cmd_tif2pdf);
					chdir($dir_fax_temp);
					$cmd_pdf2tif = "gs -q -sDEVICE=tiffg3 -r".$gs_r." -g".$gs_g." -dNOPAUSE -sOutputFile=".$file_name."_temp.tif -- ".$file_name.".pdf -c quit";
					//echo $cmd_pdf2tif."<br>";
					exec($cmd_pdf2tif); //convert pdf to tif
					@unlink($dir_fax_temp.'/'.$file_name.".pdf");
					$cmd_tif2pdf = "tiff2pdf -i -u i -p ".$page_size." -w ".$page_width." -l ".$page_height." -f -o ".$dir_fax.'/'.$file_name.".pdf ".$dir_fax_temp.'/'.$file_name."_temp.tif";
					//echo $cmd_tif2pdf."<br>";
					exec($cmd_tif2pdf);
					@unlink($dir_fax_temp.'/'.$file_name."_temp.tif");
			}
			echo "</td></tr>";
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['fax_caller_id_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape(format_phone($row['fax_caller_id_number']))."&nbsp;</td>\n";
			if ($_REQUEST['box'] == 'sent') {
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape(format_phone($row['fax_destination']))."&nbsp;</td>\n";
			}
			echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
			if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
				echo "	  <a href=\"fax_files.php?id=".$fax_uuid."&a=download&type=fax_inbox&t=bin&ext=".escape(urlencode($fax_extension))."&filename=".escape(urlencode($file))."\">\n";
			}
			if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
				echo "	  <a href=\"fax_files.php?id=".$fax_uuid."&a=download&type=fax_sent&t=bin&ext=".escape(urlencode($fax_extension))."&filename=".escape(urlencode($file))."\">\n";
			}
			echo "    	$file_name";
			echo "	  </a>";
			echo "  </td>\n";
			echo "  <td class='".$row_style[$c]."' ondblclick=''>\n";
			if ($_REQUEST['box'] == 'inbox') {
				$dir_fax = $dir_fax_inbox;
			}
			if ($_REQUEST['box'] == 'sent') {
				$dir_fax = $dir_fax_sent;
			}
			if (file_exists($dir_fax.'/'.$file_name.".pdf")) {
				if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
					echo "	  <a href=\"fax_files.php?id=".escape($fax_uuid)."&a=download&type=fax_inbox&t=bin&ext=".escape(urlencode($fax_extension))."&filename=".escape(urlencode($file_name)).".pdf\">PDF</a>\n";
				}
				if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
					echo "	  <a href=\"fax_files.php?id=".escape($fax_uuid)."&a=download&type=fax_sent&t=bin&ext=".escape(urlencode($fax_extension))."&filename=".escape(urlencode($file_name)).".pdf\">PDF</a>\n";
				}
			}
			else {
				echo "&nbsp;\n";
			}
			$fax_date = ($_SESSION['domain']['time_format']['text'] == '12h') ? date("F d Y H:i", $row['fax_epoch']) : date("F d Y H:i", $row['fax_epoch']);
			
			echo "  </td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$fax_date."&nbsp;</td>\n";
			echo "	<td style='width: 25px;' class='list_control_icons'>";
			if (permission_exists('fax_file_delete')) {
				echo "<a href='fax_file_delete.php?id=".escape($row['fax_file_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
	}
	unset($fax_files, $row);

//show the paging controls
	echo "</table>";
	echo "<br /><br />";

	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
