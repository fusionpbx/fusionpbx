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
	Portions created by the Initial Developer are Copyright (C) 2018-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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

//create the database object
	$database = new database;

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_REQUEST["order_by"] ?? null;
	$order = $_REQUEST["order"] ?? null;

//get the http post data
	if (!empty($_POST['fax_files']) && is_array($_POST['fax_files'])) {
		$action = $_POST['action'];
		$fax_uuid = $_POST['fax_uuid'];
		$box = $_POST['box'];
		$fax_files = $_POST['fax_files'];
	}

//process the http post data by action
	if (!empty($action) && !empty($fax_files) && is_array($fax_files) && @sizeof($fax_files) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('fax_file_edit')) {
					$fax = new fax;
					$fax->domain_uuid = $_SESSION['domain_uuid'];
					$fax_files_toggled = $fax->fax_file_toggle($fax_files);
					unset($fax, $fax_files);

					if ($fax_files_toggled != 0) {
						message::add($text['message-toggle'].': '.$fax_files_toggled);
					}
				}
				break;
			case 'delete':
				if (permission_exists('fax_file_delete')) {
					$obj = new fax;
					$obj->fax_uuid = $fax_uuid;
					$obj->box = $box;
					$obj->delete_files($fax_files);
				}
				break;
		}

		header('Location: fax_files.php?order_by='.$order_by.'&order='.$order.'&id='.$fax_uuid.'&box='.$box);
		exit;
	}

//get fax extension
	if (!empty($_GET["id"]) && is_uuid($_GET["id"])) {
		$fax_uuid = $_GET["id"];
		if (permission_exists('fax_extension_view_domain')) {
			//show all fax extensions
			$sql = "select fax_name, fax_extension ";
			$sql .= "from v_fax ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and fax_uuid = :fax_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
		}
		else {
			//show only assigned fax extensions
			$sql = "select fax_name, fax_extension ";
			$sql .= "from v_fax as f, v_fax_users as u ";
			$sql .= "where f.fax_uuid = u.fax_uuid ";
			$sql .= "and f.domain_uuid = :domain_uuid ";
			$sql .= "and f.fax_uuid = :fax_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
			$parameters['user_uuid'] = $_SESSION['user_uuid'];
		}
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
	if (!empty($_SESSION['switch']['storage']['dir'])) {
		$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'];
	}

//download the fax
	if (isset($fax_dir) && !empty($_GET['a']) && ($_GET['a'] == "download" || $_GET['a'] == 'download_link')) {

		//sanitize the values that are used in the file name and path
		$fax_extension = preg_replace('/[^0-9]/', '', $_GET['ext']);
		$fax_filename = preg_replace('/[\/\\\&\%\#]/', '', $_GET['filename']);

		//check if the file is in the inbox or sent directory
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

		//mark fax file as read if in inbox
		if ($_GET['type'] == "fax_inbox" && !empty($_GET['fax_file_uuid']) && is_uuid($_GET['fax_file_uuid'])) {
			$fax_files[0] = ['checked'=>'true','uuid'=>$_GET['fax_file_uuid']];
			$fax = new fax;
			$fax->domain_uuid = $_SESSION['domain_uuid'];
			$fax->fax_uuid = $_GET['id'] ?? '';
			$fax->order_by = $_GET['order_by'] ?? '';
			$fax->order = $_GET['order'] ?? '';
			$fax->box = $_GET['box'] ?? '';
			$fax->download = $_GET['a'] == 'download_link' ? true : false;
			$fax->fax_file_toggle($fax_files);
			unset($fax, $fax_files);
		}

		//add the headers and stream the file
		if (!empty($download_filename)) {
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
	if (isset($fax_dir) && !empty($fax_extension)) {
		//set the fax directories. example /usr/local/freeswitch/storage/fax/329/inbox
			$dir_fax_inbox = $fax_dir.'/'.$fax_extension.'/inbox';
			$dir_fax_sent = $fax_dir.'/'.$fax_extension.'/sent';
			$dir_fax_temp = $fax_dir.'/'.$fax_extension.'/temp';

		//make sure the directories exist
			if (!empty($_SESSION['switch']['storage']['dir']) && !is_dir($_SESSION['switch']['storage']['dir'])) {
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
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	$param = "&id=".$fax_uuid."&box=".$_GET['box'].(!empty($_GET['order_by']) ? "&order_by=".$_GET['order_by'] : null).(!empty($_GET['order']) ? "&order=".$_GET['order'] : null);
	$page = !empty($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 0;
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
	$sql .= "fax_caller_id_number, fax_recipient, fax_epoch, fax_base64, fax_date, \n";
	$sql .= "to_char(timezone(:time_zone, fax_date), 'DD Mon YYYY') as fax_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, fax_date), '".$time_format."') as fax_time_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, read_date), 'YYYY-MM-DD') as read_date_formatted \n";
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

//pdf script, style and container
	echo "<script>\n";
	echo "	function fade_in(id, url) {\n";
	echo "		var pdf_container = document.getElementById(id);\n";
	echo "		pdf_container.style.opacity = 1;\n";
	echo "		pdf_container.style.zIndex = 999999;\n";
	echo "		document.getElementById('pdf-iframe').src = url;";
	echo "	}\n";
	echo "	function fade_out(pdf_container) {\n";
	echo "		pdf_container.style.opacity = 0;\n";
	echo "		setTimeout(function(){ pdf_container.style.zIndex = -1; }, 1000);\n";
	echo "	}\n";
	echo "</script>\n";
	echo "\n";
	echo "<style>\n";
	echo "	div#pdf-container {\n";
	echo "		z-index: -1;\n";
	echo "		position: fixed;\n";
	echo "		top: 0;\n";
	echo "		left: 0;\n";
	echo "		width: 100%;\n";
	echo "		height: 100%;\n";
	echo "		opacity: 0;\n";
	echo "		transition: opacity .5s;\n";
	echo "		padding: 20px;\n";
	echo "	}\n";
	echo "	div#pdf-div {\n";
	echo "		display: block;\n";
	//echo "		margin: max(0px, 0px) auto;\n";
	echo "		width: 100%;\n";
	echo "		max-width: 600px;\n";
	echo "		min-width: 300px;\n";
	echo "		height: auto;\n";
	echo "		max-height: 800px;\n";
	echo "		margin:0 auto; \n";
	echo "		padding:0px;\n";
	//echo "		-webkit-box-shadow: 0px 1px 20px #888;\n";
	//echo "		-moz-box-shadow: 0px 1px 20px #888;\n";
	//echo "		box-shadow: 0px 1px 20px #888;\n";
	//echo "		border: 0px solid #fff;\n";
	echo "	}\n";
	echo "</style>";
	echo "<div id='pdf-container' onclick='fade_out(this);'>\n";
	echo "	<div id='pdf-div'>\n";
	echo "		<iframe id=\"pdf-iframe\" src=\"\" width=\"600\" height=\"800\"></iframe>\n";
	echo "	</div>\n";
	echo "</div>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($_REQUEST['box'] == 'inbox' && permission_exists('fax_inbox_view')) {
		echo "<b>".escape($fax_name)." [".escape($fax_extension)."]: ".$text['header-inbox']."</b><div class='count'>".number_format($num_rows)."</div>";
	}
	if ($_REQUEST['box'] == 'sent' && permission_exists('fax_sent_view')) {
		echo "<b>".escape($fax_name)." [".escape($fax_extension)."]: ".$text['header-sent_faxes']."</b><div class='count'>".number_format($num_rows)."</div>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";

	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','link'=>'fax.php']);
	$margin_left = false;
	if (permission_exists('fax_file_edit') && $_REQUEST['box'] == 'inbox' && $fax_files) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','collapse'=>'hide-xs','style'=>'display: none; margin-left: 15px;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
		$margin_left = true;
	}
	if (permission_exists('fax_file_delete') && $fax_files) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; '.(!$margin_left ? 'margin-left: 15px;' : null),'onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('fax_file_edit') && $_REQUEST['box'] == 'inbox' && $fax_files) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('fax_file_delete') && $fax_files) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='fax_uuid' value='".escape($fax_uuid)."'>\n";
	echo "<input type='hidden' name='box' value='".escape($_REQUEST['box'])."'>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('fax_file_delete') || permission_exists('fax_file_edit')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($fax_files) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('fax_caller_id_name', $text['label-fax_caller_id_name'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$page);
	echo th_order_by('fax_caller_id_number', $text['label-fax_caller_id_number'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$page);
	if ($_REQUEST['box'] == 'sent') {
		if (permission_exists('fax_sent_recipient')) {
			echo th_order_by('fax_recipient', $text['label-fax_recipient'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$page);
		}
		echo th_order_by('fax_destination', $text['label-fax_destination'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$page);
	}
	if (permission_exists('fax_download_view')) {
		echo "<th>".$text['table-file']."</th>\n";
	}
	echo "<th width='10%'>".$text['table-view']."</th>\n";
	echo th_order_by('fax_date', $text['label-fax_date'], $order_by, $order, "&id=".$fax_uuid."&box=".$_GET['box']."&page=".$page);
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
			if (!empty($row['fax_base64'])) {
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
			if (!empty($dir_fax)) {
				//change the working directory
				chdir($dir_fax);

				//get fax resolution (ppi, W & H)
				$resp = exec("tiffinfo ".$file_name.".tif | grep 'Resolution:'");
				$resp_array = explode(' ', trim($resp));
				$ppi_w = (int) $resp_array[1];
				$ppi_h = (int) $resp_array[2];
				unset($resp_array);
				$gs_r = $ppi_w.'x'.$ppi_h; //used by ghostscript

				//get page dimensions/size (pixels/inches, W & H)
				$response = exec("tiffinfo ".$file_name.".tif | grep 'Image Width:'");
				if (!empty($response)) {
					$response_array = explode(' ', trim($response));
					$pix_w = $response_array[2];
					$pix_h = $response_array[5];
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
				}

				//clean up temporary files, if any
				if (file_exists($dir_fax_temp.'/'.$file_name.'.pdf')) { @unlink($dir_fax_temp.'/'.$file_name.'.pdf'); }
				if (file_exists($dir_fax_temp.'/'.$file_name.'.tif')) { @unlink($dir_fax_temp.'/'.$file_name.'.tif'); }
			}

			//set fax as bold if unread and normal font weight if read
			$bold = $_REQUEST['box'] == 'inbox' && empty($row['read_date_formatted']) ? 'font-weight: bold;' : null;

			$list_row_url = null;
			if (permission_exists('fax_inbox_view') || permission_exists('fax_sent_view')) {
				$list_row_url = "fax_files.php?id=".urlencode($fax_uuid);
				$list_row_url .= "&fax_file_uuid=".urlencode($row['fax_file_uuid']);
				$list_row_url .= "&a=download";
				$list_row_url .= "&type=fax_".urlencode($_REQUEST['box']);
				$list_row_url .= "&t=bin";
				$list_row_url .= "&order_by=".urlencode($_REQUEST['order_by']);
				$list_row_url .= "&order=".urlencode($_REQUEST['order']);
				$list_row_url .= "&box=".urlencode($_REQUEST['box']);
				$list_row_url .= "&ext=".urlencode($fax_extension);
				$list_row_url .= "&".$token['name']."=".$token['hash'];
				$list_row_url .= "&filename=".urlencode($file);
			}

			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('fax_file_delete') || permission_exists('fax_file_edit')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='fax_files[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; } checkbox_on_change(this);\">\n";
				echo "		<input type='hidden' name='fax_files[$x][uuid]' value='".escape($row['fax_file_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td style='".$bold."'>".escape($row['fax_caller_id_name'])."&nbsp;</td>\n";
			echo "	<td style='".$bold."'>".escape(format_phone($row['fax_caller_id_number']))."&nbsp;</td>\n";
			if ($_REQUEST['box'] == 'sent') {
				if (permission_exists('fax_sent_recipient')) {
					echo "	<td>".escape($row['fax_recipient'])."&nbsp;</td>\n";
				}
				echo "	<td>".escape(format_phone($row['fax_destination']))."&nbsp;</td>\n";
			}
			if (permission_exists('fax_download_view')) {
				echo "  <td style='".$bold."'>\n";
				echo "		<a href='".$list_row_url."'>".$file_name."</a>";
				echo "	</td>\n";
			}
			echo "  <td class='no-link' style='".$bold."'>\n";
			if ($_REQUEST['box'] == 'inbox') {
				$dir_fax = $dir_fax_inbox;
			}
			if ($_REQUEST['box'] == 'sent') {
				$dir_fax = $dir_fax_sent;
			}
			if ((permission_exists('fax_inbox_view') || permission_exists('fax_sent_view')) && file_exists($dir_fax.'/'.$file_name.".pdf")) {
				echo "		<a href=\"javascript:void(0);\" onclick=\"fade_in('pdf-container', '".substr(str_replace("&t=bin", "", $list_row_url), 0, -4).".pdf');\">View</a>\n";
				echo "		&nbsp;&nbsp;\n";
				echo "		<a href=\"".substr($list_row_url, 0, -4).".pdf\">PDF</a>\n";
			}
			echo "  </td>\n";
			echo "	<td style='".$bold."'>".$row['fax_date_formatted']." ".$row['fax_time_formatted']."&nbsp;</td>\n";
			echo "</tr>\n";
			$x++;
		}
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//unbold new fax rows when clicked/downloaded
	if ($_REQUEST['box'] == 'inbox') {
		echo "<script>\n";
		echo "	$(document).ready(function() {\n";
		echo "		$('.list-row').each(function(i,e) {\n";
		echo "			$(e).children('td:not(.checkbox)').on('click',function() {\n";
		echo "				$(this).closest('tr').children('td').css('font-weight','normal');\n";
		echo "			});\n";
		echo "			$(e).children('td').children('button').on('click',function() {\n";
		echo "				$(this).closest('tr').children('td').css('font-weight','normal');\n";
		echo "			});\n";
		echo "		});\n";
		echo "	});\n";
		echo "</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>
