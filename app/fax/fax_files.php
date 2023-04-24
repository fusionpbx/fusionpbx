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
	Portions created by the Initial Developer are Copyright (C) 2018-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

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
	$order_by = $_REQUEST["order_by"];
	$order = $_REQUEST["order"];

//get the http post data
	if (is_array($_POST['fax_files'])) {
		$action = $_POST['action'];
		$fax_uuid = $_POST['fax_uuid'];
		$box = $_POST['box'];
		$fax_files = $_POST['fax_files'];
	}

//process the http post data by action
	if ($action != '' && is_array($fax_files) && @sizeof($fax_files) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('fax_file_delete')) {
					$obj = new fax;
					$obj->fax_uuid = $fax_uuid;
					$obj->box = $box;
					$obj->delete_files($fax_files);
				}
				break;
		}

		header('Location: fax_files.php?orderby='.$order_by.'&order='.$order.'&id='.$fax_uuid.'&box='.$box);
		exit;
	}

//get fax extension
	if (is_uuid($_GET["id"])) {
		$fax_uuid = $_GET["id"];
		if (permission_exists('fax_extension_view_domain')) {
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
			if (!permission_exists('fax_extension_view_domain')) {
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

		//sanitize the values that are used in the file name and path
		$fax_extension = preg_replace('/[^0-9]/', '', $_GET['ext']);
		$fax_filename = preg_replace('/[\/\\\&\%\#]/', '', $_GET['filename']);

		//check if the file is in the inbox or sent directory.
		if ($_GET['type'] == "fax_inbox") {
			if (file_exists($fax_dir.'/'.$fax_extension.'/inbox/'.$fax_filename)) {
				$download_filename = $fax_dir.'/'.$fax_extension.'/inbox/'.$fax_filename;
			}
		}
		else if ($_GET['type'] == "fax_sent") {
			if (file_exists($fax_dir.'/'.$fax_extension.'/sent/'.$_GET['filename'])) {
				$download_filename = $fax_dir.'/'.$fax_extension.'/sent/'.$fax_filename;
			}
		}

		//add the headers and stream the file
		if (strlen($download_filename) > 0) {
			$fd = fopen($download_filename, "rb");
			if ($_GET['t'] == "bin") {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; filename="'.$fax_filename.'"');
			}
			else {
				$file_ext = substr($fax_filename, -3);
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
			header("Content-Length: ".filesize($download_filename));
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
				mkdir($_SESSION['switch']['storage']['dir'], 0770, false);
			}
			if (!is_dir($fax_dir.'/'.$fax_extension)) {
				mkdir($fax_dir.'/'.$fax_extension, 0770, false);
			}
			if (!is_dir($dir_fax_inbox)) {
				mkdir($dir_fax_inbox, 0770, false);
			}
			if (!is_dir($dir_fax_sent)) {
				mkdir($dir_fax_sent, 0770, false);
			}
			if (!is_dir($dir_fax_temp)) {
				mkdir($dir_fax_temp, 0770, false);
			}
	}

//prepare to page the results
	$sql = "select count(fax_file_uuid) ";
	$sql .= "from v_fax_files ";
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
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&id=".$fax_uuid."&box=".$_GET['box']."&order_by=".$_GET['order_by']."&order=".$_GET['order'];
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}
	$parameters['time_zone'] = $time_zone;

//set the time format options: 12h, 24h
	if (isset($_SESSION['domain']['time_format']['text'])) {
		if ($_SESSION['domain']['time_format']['text'] == '12h') {
			$time_format = 'HH12:MI:SS am';
		}
		elseif ($_SESSION['domain']['time_format']['text'] == '24h') {
			$time_format = 'HH24:MI:SS';
		}
	}
	else {
		$time_format = 'HH12:MI:SS am';
	}

//get the list
	$sql = "select domain_uuid, fax_file_uuid, fax_uuid, fax_mode, \n";
	$sql .= "fax_destination, fax_file_type, fax_file_path, fax_caller_id_name, \n";
	$sql .= "fax_caller_id_number, fax_epoch, fax_base64, fax_date, \n";
	$sql .= "to_char(timezone(:time_zone, fax_date), 'DD Mon YYYY') as fax_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, fax_date), '".$time_format."') as fax_time_formatted \n";
	$sql .= "from v_fax_files \n";
	$sql .= "where fax_uuid = :fax_uuid \n";
	$sql .= "and domain_uuid = :domain_uuid \n";
	if ($_REQUEST['box'] == 'inbox') {
		$sql .= "and fax_mode = 'rx' \n";
	}
	if ($_REQUEST['box'] == 'sent') {
		$sql .= "and fax_mode = 'tx' \n";
	}
	$parameters['fax_uuid'] = $fax_uuid;
	$parameters['domain_uuid'] = $domain_uuid;
	$sql .= order_by($order_by, $order, 'fax_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$fax_files = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
		$document['title'] = escape($fax_name)." [".escape($fax_extension)."]: ".$text['title-inbox'];
	}
	if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
		$document['title'] = escape($fax_name)." [".escape($fax_extension)."]: ".$text['title-sent_faxes'];
	}
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
		echo "<b>".escape($fax_name)." [".escape($fax_extension)."]: ".$text['header-inbox']." (".$num_rows.")</b>";
	}
	if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
		echo "<b>".escape($fax_name)." [".escape($fax_extension)."]: ".$text['header-sent_faxes']." (".$num_rows.")</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'fax.php']);
	if (permission_exists('fax_file_delete') && $fax_files) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('fax_file_delete') && $fax_files) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='fax_uuid' value='".escape($fax_uuid)."'>\n";
	echo "<input type='hidden' name='box' value='".escape($_REQUEST['box'])."'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('fax_file_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($fax_files ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('fax_caller_id_name', $text['label-fax_caller_id_name'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$_GET['page']);
	echo th_order_by('fax_caller_id_number', $text['label-fax_caller_id_number'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$_GET['page']);
	if ($_REQUEST['box'] == 'sent') {
		echo th_order_by('fax_destination', $text['label-fax_destination'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$_GET['page']);
	}
	echo "<th>".$text['table-file']."</th>\n";
	echo "<th width='10%'>".$text['table-view']."</th>\n";
	echo th_order_by('fax_date', $text['label-fax_date'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$_GET['page']);
	echo "</tr>\n";

	if (is_array($fax_files) && @sizeof($fax_files) != 0) {
		$x = 0;
		foreach ($fax_files as $row) {
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
				//generate pdf from tif
					$cmd_tif2pdf = "tiff2pdf -u i -p ".$page_size." -w ".$page_width." -l ".$page_height." -f -o ".$dir_fax.'/'.$file_name.".pdf ".$dir_fax.'/'.$file_name.".tif";
					exec($cmd_tif2pdf);
					//echo $cmd_tif2pdf."<br >\n";
				//clean up temporary files, if any
					if (file_exists($dir_fax_temp.'/'.$file_name.'.pdf')) { @unlink($dir_fax_temp.'/'.$file_name.'.pdf'); }
					if (file_exists($dir_fax_temp.'/'.$file_name.'.tif')) { @unlink($dir_fax_temp.'/'.$file_name.'.tif'); }
			}

			if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
				$list_row_url = "fax_files.php?id=".urlencode($fax_uuid)."&a=download&type=fax_inbox&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file);
			}
			if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
				$list_row_url = "fax_files.php?id=".urlencode($fax_uuid)."&a=download&type=fax_sent&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('fax_file_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='fax_files[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='fax_files[$x][uuid]' value='".escape($row['fax_file_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>".escape($row['fax_caller_id_name'])."&nbsp;</td>\n";
			echo "	<td>".escape(format_phone($row['fax_caller_id_number']))."&nbsp;</td>\n";
			if ($_REQUEST['box'] == 'sent') {
				echo "	<td>".escape(format_phone($row['fax_destination']))."&nbsp;</td>\n";
			}
			echo "  <td><a href='".$list_row_url."'>".$file_name."</a></td>\n";
			echo "  <td class='no-link'>\n";
			if ($_REQUEST['box'] == 'inbox') {
				$dir_fax = $dir_fax_inbox;
			}
			if ($_REQUEST['box'] == 'sent') {
				$dir_fax = $dir_fax_sent;
			}
			if (file_exists($dir_fax.'/'.$file_name.".pdf")) {
				if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
					echo "	  <a href=\"fax_files.php?id=".urlencode($fax_uuid)."&a=download&type=fax_inbox&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file_name).".pdf\">PDF</a>\n";
				}
				if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
					echo "	  <a href=\"fax_files.php?id=".urlencode($fax_uuid)."&a=download&type=fax_sent&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file_name).".pdf\">PDF</a>\n";
				}
			}
			echo "  </td>\n";
			echo "	<td>".$row['fax_date_formatted']." ".$row['fax_time_formatted']."&nbsp;</td>\n";
			echo "</tr>\n";
			$x++;
		}
	}
	unset($fax_files);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
