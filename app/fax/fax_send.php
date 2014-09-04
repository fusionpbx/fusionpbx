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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_send')) {
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

//get the fax_extension and save it as a variable
	if (strlen($_REQUEST["fax_extension"]) > 0) {
		$fax_extension = check_str($_REQUEST["fax_extension"]);
	}

//pre-populate the form
	if (strlen($_REQUEST['id']) > 0 && $_POST["persistformvar"] != "true") {
		$fax_uuid = check_str($_REQUEST["id"]);
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
				$fax_extension = $row["fax_extension"];
				$fax_name = $row["fax_name"];
				$fax_email = $row["fax_email"];
				$fax_pin_number = $row["fax_pin_number"];
				$fax_caller_id_name = $row["fax_caller_id_name"];
				$fax_caller_id_number = $row["fax_caller_id_number"];
				$fax_forward_number = $row["fax_forward_number"];
				$fax_description = $row["fax_description"];
			//limit to one row
				break;
		}
		unset ($prep_statement);
	}

//set the fax directory
	if (count($_SESSION["domains"]) > 1) {
		$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'];
	}
	else {
		$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax';
	}

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

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$fax_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http post values and set them as php variables
	if (count($_POST)>0) {
		$fax_name = check_str($_POST["fax_name"]);
		$fax_email = check_str($_POST["fax_email"]);
		$fax_pin_number = check_str($_POST["fax_pin_number"]);
		$fax_caller_id_name = check_str($_POST["fax_caller_id_name"]);
		$fax_caller_id_number = check_str($_POST["fax_caller_id_number"]);
		$fax_forward_number = check_str($_POST["fax_forward_number"]);
		if (strlen($fax_forward_number) > 0) {
			$fax_forward_number = preg_replace("~[^0-9]~", "",$fax_forward_number);
		}
		$fax_description = check_str($_POST["fax_description"]);
	}

//clear file status cache
	clearstatcache();

//upload (if necessary) and send the fax
	if (($_POST['action'] == "send")) {

		$fax_number = check_str($_POST['fax_number']);
		if (strlen($fax_number) > 0) {
			$fax_number = preg_replace("~[^0-9]~", "",$fax_number);
		}

		$provider_type = check_str($_POST['provider_type']);
		$fax_uuid = check_str($_POST["id"]);

		$fax_caller_id_name = check_str($_POST['fax_caller_id_name']);
		$fax_caller_id_number = check_str($_POST['fax_caller_id_number']);
		$fax_forward_number = check_str($_POST['fax_forward_number']);
		if (strlen($fax_forward_number) > 0) {
			$fax_forward_number = preg_replace("~[^0-9]~", "",$fax_forward_number);
		}

		$fax_sender = check_str($_POST['fax_sender']);
		$fax_contact_info = check_str($_POST['fax_contact_info']);
		$fax_recipient = check_str($_POST['fax_recipient']);
		$fax_subject = check_str($_POST['fax_subject']);
		$fax_message = check_str($_POST['fax_message']);
		$fax_resolution = check_str($_POST['fax_resolution']);
		$fax_page_size = check_str($_POST['fax_page_size']);
		$fax_footer = check_str($_POST['fax_footer']);

		// process uploaded files, if any
		foreach ($_FILES['fax_files']['tmp_name'] as $index => $fax_tmp_name) {
			if (
				is_uploaded_file($fax_tmp_name)
				&& $_FILES['fax_files']['error'][$index] == 0
				&& $_FILES['fax_files']['size'][$index] > 0
				) {

				//get the file extension
				$fax_file_extension = strtolower(pathinfo($_FILES['fax_files']['name'][$index], PATHINFO_EXTENSION));
				if ($fax_file_extension == "tiff" || $fax_file_extension == "tif") { $fax_file_extension = "tif"; }

				//skip files other than pdf and tif (for now)
				if ($fax_file_extension != 'pdf' && $fax_file_extension != 'tif') { continue; }

				$fax_name = $_FILES['fax_files']['name'][$index];
				$fax_name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fax_name);
				$fax_name = str_replace(" ", "_", $fax_name);

				//lua doesn't seem to like special chars with env:GetHeader
				$fax_name = str_replace(";", "_", $fax_name);
				$fax_name = str_replace(",", "_", $fax_name);
				$fax_name = str_replace("'", "_", $fax_name);
				$fax_name = str_replace("!", "_", $fax_name);
				$fax_name = str_replace("@", "_", $fax_name);
				$fax_name = str_replace("#", "_", $fax_name);
				$fax_name = str_replace("$", "_", $fax_name);
				$fax_name = str_replace("%", "_", $fax_name);
				$fax_name = str_replace("^", "_", $fax_name);
				$fax_name = str_replace("`", "_", $fax_name);
				$fax_name = str_replace("~", "_", $fax_name);
				$fax_name = str_replace("&", "_", $fax_name);
				$fax_name = str_replace("(", "_", $fax_name);
				$fax_name = str_replace(")", "_", $fax_name);
				$fax_name = str_replace("+", "_", $fax_name);
				$fax_name = str_replace("=", "_", $fax_name);

				//move uploaded file
				move_uploaded_file($_FILES['fax_files']['tmp_name'][$index], $dir_fax_temp.'/'.$fax_name.'.'.$fax_file_extension);

				//convert uploaded file to pdf, if necessary
				if ($fax_file_extension != "pdf") {
					chdir($dir_fax_temp);
					exec("export HOME=/tmp && libreoffice --headless --convert-to pdf --outdir ".$dir_fax_temp." ".$dir_fax_temp.'/'.$fax_name.'.'.$fax_file_extension);
				}

				//add file to arrays
				$uploaded_file_names[] = $fax_name.'.'.$fax_file_extension;
				$pdf_files[] = $dir_fax_temp.'/'.$fax_name.'.pdf';

			} //if
		} //foreach

		//load pdf libraries
		require_once("resources/tcpdf/tcpdf.php");
		require_once("resources/fpdi/fpdi.php");

		$pdf = new FPDI('P', 'in');
		$pdf -> SetAutoPageBreak(false);
		$pdf -> setPrintHeader(false);
		$pdf -> setPrintFooter(false);
		$pdf -> SetMargins(0, 0, 0, true);

		$page_count = 0;
		if (is_array($pdf_files) && sizeof($pdf_files) > 0) {
			//determine total pages
			foreach ($pdf_files as $pdf_file) {
				$page_count += $pdf -> setSourceFile($pdf_file);
			}
		}

		//determine page size
		switch ($fax_page_size) {
			case 'auto' :
				if ($page_count > 0) {
					// retrieve from uploaded file
					$pdf -> setSourceFile($pdf_files[0]);
					$tmpl = $pdf -> ImportPage(1);
					$page_size = $pdf -> getTemplateSize($tmpl);
					$page_width = round($page_size['w'], 2, PHP_ROUND_HALF_DOWN);
					$page_height = round($page_size['h'], 2, PHP_ROUND_HALF_DOWN);
				}
				else {
					$page_width = 8.5; //in
					$page_height = 11; //in
				}
				break;
			case 'a4' :
				$page_width = 8.3; //in
				$page_height = 11.7; //in
				break;
			case 'legal' :
				$page_width = 8.5; //in
				$page_height = 14; //in
				break;
			case 'letter' :
			default	:
				$page_width = 8.5; //in
				$page_height = 11; //in
		}

		// unique id for this fax
		$fax_instance_uuid = uuid();

		//generate cover page, merge with pdf
		if ($fax_subject != '' || $fax_message != '') {

			//add blank page
			$pdf -> AddPage('P', array($page_width, $page_height));

			// content offset, if necessary
			$x = 0;
			$y = 0;

			// output grid
			//showgrid($pdf);

			//logo
			$display_logo = false;
			if (!isset($_SESSION['fax']['cover_logo']['text'])) {
				$logo = PROJECT_PATH."/app/fax/logo.jpg";
				$display_logo = true;
			}
			else if (isset($_SESSION['fax']['cover_logo']['text']) && $_SESSION['fax']['cover_logo']['text'] != '') {
				$logo = $_SESSION['fax']['cover_logo']['text'];
				if (substr($logo, 0, 4) == 'http') {
					$remote_filename = strtolower(pathinfo($logo, PATHINFO_BASENAME));
					$remote_fileext = pathinfo($remote_filename, PATHINFO_EXTENSION);
					if ($remote_fileext == 'gif' || $remote_fileext == 'jpg' || $remote_fileext == 'jpeg' || $remote_fileext == 'png' || $remote_fileext == 'bmp') {
						if (!file_exists($dir_fax_temp.'/'.$remote_filename)) {
							$raw = file_get_contents($logo);
							if (file_put_contents($dir_fax_temp.'/'.$remote_filename, $raw)) {
								$logo = $dir_fax_temp.'/'.$remote_filename;
							}
							else {
								unset($logo);
							}
						}
						else {
							$logo = $dir_fax_temp.'/'.$remote_filename;
						}
					}
					else {
						unset($logo);
					}
				}
				$display_logo = true;
			}

			if ($display_logo) {
				$pdf -> Image($logo, 0.5, 0.4, 2.5, 0.9, null, null, 'N', true, 300, null, false, false, 0, true);
			}
			else {
				//set position for contact info, if enabled
				$pdf -> SetXY($x + 0.5, $y + 0.4);
			}

			//contact info
			if (isset($_SESSION['fax']['cover_contact_info']['text'])) {
				$pdf -> SetLeftMargin(0.5);
				$pdf -> SetFont("times", "", 10);
				$pdf -> Write(0.3, $fax_contact_info);
			}

			//fax, cover sheet
			$pdf -> SetTextColor(0,0,0);
			$pdf -> SetFont("times", "B", 55);
			$pdf -> SetXY($x + 4.55, $y + 0.25);
			$pdf -> Cell($x + 3.50, $y + 0.4, $text['label-fax-fax'], 0, 0, 'R', false, null, 0, false, 'T', 'T');
			$pdf -> SetFont("times", "", 12);
			$pdf -> SetFontSpacing(0.0425);
			$pdf -> SetXY($x + 4.55, $y + 1.0);
			$pdf -> Cell($x + 3.50, $y + 0.4, $text['label-fax-cover-sheet'], 0, 0, 'R', false, null, 0, false, 'T', 'T');
			$pdf -> SetFontSpacing(0);

			//field labels
			$pdf -> SetFont("times", "B", 12);
			if ($fax_recipient != '' || $fax_number != '') {
				$pdf -> Text($x + 0.5, $y + 2.0, strtoupper($text['label-fax-recipient']).":");
			}
			if ($fax_sender != '' || $fax_caller_id_number != '') {
				$pdf -> Text($x + 0.5, $y + 2.3, strtoupper($text['label-fax-sender']).":");
			}
			if ($page_count > 0) {
				$pdf -> Text($x + 0.5, $y + 2.6, strtoupper($text['label-fax-attached']).":");
			}
			if ($fax_subject != '') {
				$pdf -> Text($x + 0.5, $y + 2.9, strtoupper($text['label-fax-subject']).":");
			}

			//field values
			$pdf -> SetFont("times", "", 12);
			$pdf -> SetXY($x + 2.0, $y + 1.95);
			if ($fax_recipient != '') {
				$pdf -> Write(0.3, $fax_recipient);
				if ($fax_number != '') {
					$pdf -> Write(0.3, '  ('.format_phone($fax_number).')');
				}
			}
			else {
				if ($fax_number != '') {
					$pdf -> Write(0.3, format_phone($fax_number));
				}
			}
			$pdf -> SetXY($x + 2.0, $y + 2.25);
			if ($fax_sender != '') {
				$pdf -> Write(0.3, $fax_sender);
				if ($fax_caller_id_number != '') {
					$pdf -> Write(0.3, '  ('.format_phone($fax_caller_id_number).')');
				}
			}
			else {
				if ($fax_caller_id_number != '') {
					$pdf -> Write(0.3, format_phone($fax_caller_id_number));
				}
			}
			if ($page_count > 0) {
				$pdf -> Text($x + 2.0, $y + 2.6, $page_count.' '.$text['label-fax-page'.(($page_count > 1) ? 's' : null)]);
			}
			if ($fax_subject != '') {
				$pdf -> Text($x + 2.0, $y + 2.9, $fax_subject);
			}

			//message
			$pdf -> Rect($x + 0.5, $y + 3.4, 7.5, 6.25, 'D');
			if ($fax_message != '') {
				$pdf -> SetFont("times", "", 12);
				$pdf -> SetXY($x + 0.75, $y + 3.65);
				$pdf -> MultiCell(7, 5.75, $fax_message, 0, 'L', false);
			}

			//footer
			if ($fax_footer != '') {
				$pdf -> SetFont("helvetica", "", 8);
				$pdf -> SetXY($x + 0.5, $y + 9.9);
				$pdf -> MultiCell(7.5, 0.75, $fax_footer, 0, 'C', false);
			}

			// save cover pdf file and add to array of pages
			if (is_array($pdf_files) && sizeof($pdf_files) > 0) {
				array_unshift($pdf_files, $dir_fax_temp.'/'.$fax_instance_uuid.'_cover.pdf');
			}
			else {
				$pdf_files[] = $dir_fax_temp.'/'.$fax_instance_uuid.'_cover.pdf';
			}
			$pdf -> Output($dir_fax_temp.'/'.$fax_instance_uuid.'_cover.pdf', "F");	// Display [I]nline, Save to [F]ile, [D]ownload
		}

		//create new pdf object
		unset($pdf);
		$pdf = new FPDI('P', 'in');
		$pdf -> SetAutoPageBreak(false);
		$pdf -> setPrintHeader(false);
		$pdf -> setPrintFooter(false);
		$pdf -> SetMargins(0, 0, 0, true);

		//combine pages into single pdf, delete temporary cover pdf (if exists)
		if (is_array($pdf_files) && sizeof($pdf_files) > 0) {
			foreach ($pdf_files as $pdf_file) {
				$pdf_file_pages = $pdf -> setSourceFile($pdf_file);
				for ($p = 1; $p <= $pdf_file_pages; $p++) {
					$tmpl = $pdf -> ImportPage($p);
					if ($fax_page_size == 'auto') {
						//use individual page dimensions
						$page_size = $pdf -> getTemplateSize($tmpl);
						$page_width = round($page_size['w'], 2, PHP_ROUND_HALF_DOWN);
						$page_height = round($page_size['h'], 2, PHP_ROUND_HALF_DOWN);
					}
					$pdf -> AddPage('P', array($page_width, $page_height));
					$pdf -> useTemplate($tmpl);
				}
			}
			$pdf -> Output($dir_fax_temp.'/'.$fax_instance_uuid.'.pdf', "F");	// Display [I]nline, Save to [F]ile, [D]ownload (or a combination - eg. 'FI' or 'FD')
			@unlink($dir_fax_temp.'/'.$fax_instance_uuid.'_cover.pdf');
		}
		else {
			//nothing to send, redirect the browser
			$_SESSION['message_mood'] = 'negative';
			$_SESSION["message"] = $text['message-invalid-fax'];
			header("Location: fax_send.php?id=".$fax_uuid);
			exit;
		}

		//convert pdf to tif
		if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.'.pdf')) {
			switch ($fax_resolution) {
				case 'normal':
					$r = '204x98'; $g = ((int) ($page_width * 204)).'x'.((int) ($page_height * 98));
					break;
				case 'fine':
					$r = '204x196'; $g = ((int) ($page_width * 204)).'x'.((int) ($page_height * 196));
					break;
				case 'superfine':
					$r = '408x391';	$g = ((int) ($page_width * 408)).'x'.((int) ($page_height * 391));
					break;
			}
			chdir($dir_fax_temp);
			exec("gs -q -sDEVICE=tiffg3 -r".$r." -g".$g." -dNOPAUSE -sOutputFile=".$fax_instance_uuid.".tif -- ".$fax_instance_uuid.".pdf -c quit");
		}

		//preview, if requested
		if ($_REQUEST['submit'] == $text['button-preview']) {
			if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.'.tif')) {
				//delete pdf and uploaded files
				@unlink($dir_fax_temp.'/'.$fax_instance_uuid.'.pdf');
				foreach ($uploaded_file_names as $uploaded_file_name) {
					@unlink($dir_fax_temp.'/'.$uploaded_file_name);
				}
				//push download
				$fd = fopen($dir_fax_temp.'/'.$fax_instance_uuid.'.tif', "rb");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; filename="'.$fax_instance_uuid.'.tif"');
				header("Content-Type: image/tiff");
				header('Accept-Ranges: bytes');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
				header("Content-Length: ".filesize($dir_fax_temp.'/'.$fax_instance_uuid.'.tif'));
				fpassthru($fd);
			}
			exit;
		}

		//get some more info to send the fax
		if (isset($_SESSION['fax']['smtp_from']['var'])) {
			$mailfrom_address = $_SESSION['fax']['smtp_from']['var'];
		}
		else {
			$mailfrom_address = $_SESSION['email']['smtp_from']['var'];
		}

		$sql = "select fax_email from v_fax where fax_uuid = '".$fax_uuid."'; ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetch(PDO::FETCH_NAMED);
		$mailto_address_fax = $result["fax_email"];

		$sql = "select contact_uuid from v_users where user_uuid = '".$_SESSION['user_uuid']."'; ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetch(PDO::FETCH_NAMED);

		$sql = "select contact_email from v_contacts where contact_uuid = '".$result["contact_uuid"]."'; ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetch(PDO::FETCH_NAMED);
		$mailto_address_user = $result["contact_email"];

		if ($mailto_address_user != $mailto_address_fax) {
			$mailto_address = "'".$mailto_address_fax."\,".$mailto_address_user."'";
		}
		else {
			$mailto_address = $mailto_address_user;
		}

		//send the fax
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			//prepare the fax command
			$route_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $fax_number);
			$fax_file = $dir_fax_temp."/".$fax_instance_uuid.".tif";
			if (count($route_array) == 0) {
				//send the internal call to the registered extension
					$fax_uri = "user/".$fax_number."@".$_SESSION['domain_name'];
					$t38 = "";
			}
			else {
				//send the external call
					$fax_uri = $route_array[0];
					$t38 = "fax_enable_t38=true,fax_enable_t38_request=true,";
			}
			$cmd = "api originate {domain_uuid=".$_SESSION["domain_uuid"].",domain_name=".$_SESSION["domain_name"].",mailto_address='".$mailto_address."',mailfrom_address='".$mailfrom_address."',origination_caller_id_name='".$fax_caller_id_name."',origination_caller_id_number='".$fax_caller_id_number."',fax_ident='".$fax_caller_id_number."',fax_header='".$fax_caller_id_name."',fax_uri=".$fax_uri.",fax_file='".$fax_file."',fax_retry_attempts=1,fax_retry_limit=20,fax_retry_sleep=180,fax_verbose=true,fax_use_ecm=off,".$t38."api_hangup_hook='lua fax_retry.lua'}".$fax_uri." &txfax('".$fax_file."')";
			//send the command to event socket
			$response = event_socket_request($fp, $cmd);
			$response = str_replace("\n", "", $response);
			$uuid = str_replace("+OK ", "", $response);
			fclose($fp);
		}

		//wait for a few seconds
		sleep(5);

		/*
		//handled with lua retry script

		//move the generated tif and pdf files to the sent directory
		exec("cp ".$dir_fax_temp.'/'.$fax_instance_uuid.".tif ".$dir_fax_sent.'/'.$fax_instance_uuid.".tif");
		if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.".pdf")) {
			exec("cp ".$dir_fax_temp.'/'.$fax_instance_uuid.".pdf ".$dir_fax_sent.'/'.$fax_instance_uuid.".pdf");
		}
		*/

		//copy the original uploaded file to the sent box
		foreach ($_SESSION['fax']['save'] as $row) {
			if ($row == "all" || $row == "original") {
				if (is_array($uploaded_file_names) && sizeof($uploaded_file_names) > 0) {
					foreach ($uploaded_file_names as $uploaded_file_name) {
						exec('cp '.$dir_fax_temp.'/'.$uploaded_file_name.' '.$dir_fax_sent.'/'.$uploaded_file_name);
					}
				}
			}
		}

		//delete uploaded files from temp
		foreach ($uploaded_file_names as $uploaded_file_name) {
			@unlink($dir_fax_temp.'/'.$uploaded_file_name);
		}

		//redirect the browser
		$_SESSION["message"] = $response;
		header("Location: fax_box.php?id=".$fax_uuid."&box=sent");
		exit;

	} //end upload and send fax

//show the header
	require_once "resources/header.php";

//fax extension form

	echo "<form action='' method='POST' enctype='multipart/form-data' name='frmUpload' onSubmit=''>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left' width='30%'>\n";
	echo "		<span class='title'>".$text['header-send']."</span>\n";
	echo "	</td>\n";
	echo "	<td width='70%' align='right'>\n";
	echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='fax.php'\" value='".$text['button-back']."'>\n";
	echo "		<input type='submit' name='submit' class='btn' id='preview' value='".$text['button-preview']."'>\n";
	echo "		<input name='submit' type='submit' class='btn' id='upload' value='".$text['button-send']."'>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table width='100%' border='0' cellspacing='0' cellpadding='3'>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='left'>\n";
	//pkg_add -r ghostscript8-nox11; rehash
	echo "			".$text['description-2']." \n";
	echo "			<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-fax-sender']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input type='text' name='fax_sender' class='formfld' style='' value='".$fax_caller_id_name."'>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-sender']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-fax-contact-info']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input type='text' name='fax_contact_info' class='formfld' style='' value='".$_SESSION['fax']['cover_contact_info']['text']."'>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-contact-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-fax-recipient']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input type='text' name='fax_recipient' class='formfld' style='' value=''>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-recipient']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "		".$text['label-fax-number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input type='text' name='fax_number' class='formfld' style='' value=''>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-fax_files']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<script>";
	echo "	function list_selected_files(file_input_number) {";
	echo "		var inp = document.getElementById('fax_files_'+file_input_number);";
	echo "		var files_selected = [];";
	echo "		for (var i = 0; i < inp.files.length; ++i) {";
  	echo "			var file_name = inp.files.item(i).name;";
  	echo "			files_selected.push(file_name);";
  	echo "		}";
	echo "		if (files_selected.length > 1) {";
	echo "			document.getElementById('file_list_'+file_input_number).innerHTML = '<strong>Selected</strong>: ';";
  	echo "			document.getElementById('file_list_'+file_input_number).innerHTML += files_selected.join(', ');";
  	echo "			document.getElementById('file_list_'+file_input_number).innerHTML += '<br />';";
  	echo "		}";
	echo "	}";
	echo "	</script>";
	for ($f = 1; $f <= 3; $f++) {
		echo "	<span id='fax_file_".$f."' ".(($f > 1) ? "style='display: none;'" : null).">";
		echo "	<input name='fax_files[]' id='fax_files_".$f."' type='file' class='formfld fileinput' ".(($f > 1) ? "style='margin-top: 3px;'" : null)." accept='image/tiff,application/pdf' onchange=\"".(($f < 3) ? "document.getElementById('fax_file_".($f+1)."').style.display='';" : null)." list_selected_files(".$f.");\" multiple='multiple'><br />";
		echo "	<span id='file_list_".$f."'></span>";
		echo "	</span>\n";
	}
	echo "	".$text['description-fax_files']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "		".$text['label-fax-resolution']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='fax_resolution' class='formfld'>\n";
	echo "		<option value='normal' ".(($_SESSION['fax']['resolution']['text'] == 'normal') ? 'selected' : null).">".$text['option-fax-resolution-normal']."</option>\n";
	echo "		<option value='fine' ".(($_SESSION['fax']['resolution']['text'] == 'fine') ? 'selected' : null).">".$text['option-fax-resolution-fine']."</option>\n";
	echo "		<option value='superfine' ".(($_SESSION['fax']['resolution']['text'] == 'superfine') ? 'selected' : null).">".$text['option-fax-resolution-superfine']."</option>\n";
	echo "	</select>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-resolution']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "		".$text['label-fax-page-size']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='fax_page_size' class='formfld'>\n";
	echo "		<option value='auto' ".(($_SESSION['fax']['page_size']['text'] == 'auto') ? 'selected' : null).">".$text['option-fax-page-size-auto']."</option>\n";
	echo "		<option value='letter' ".(($_SESSION['fax']['page_size']['text'] == 'letter') ? 'selected' : null).">Letter</option>\n";
	echo "		<option value='legal' ".(($_SESSION['fax']['page_size']['text'] == 'legal') ? 'selected' : null).">Legal</option>\n";
	echo "		<option value='a4' ".(($_SESSION['fax']['page_size']['text'] == 'a4') ? 'selected' : null).">A4</option>\n";
	echo "	</select>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-page-size']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-fax-subject']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input type='text' name='fax_subject' class='formfld' style='' value=''>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-subject']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "		".$text['label-fax-message']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea type='text' name='fax_message' class='formfld' style='width: 65%; height: 175px;'></textarea>\n";
	echo "<br />\n";
	echo "	".$text['description-fax-message']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-fax-footer']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea type='text' name='fax_footer' class='formfld' style='width: 65%; height: 100px;'>".$_SESSION['fax']['cover_footer']['text']."</textarea>\n";
	echo "	<br />\n";
	echo "	".$text['description-fax-footer']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='fax_caller_id_name' value='".$fax_caller_id_name."'>\n";
	echo "			<input type='hidden' name='fax_caller_id_number' value='".$fax_caller_id_number."'>\n";
	echo "			<input type='hidden' name='fax_extension' value='".$fax_extension."'>\n";
	echo "			<input type='hidden' name='id' value='".$fax_uuid."'>\n";
	echo "			<input type='hidden' name='action' value='send'>\n";
	echo "			<input type='submit' name='submit' class='btn' id='preview' value='".$text['button-preview']."'>\n";
	echo "			<input type='submit' name='submit' class='btn' id='upload' value='".$text['button-send']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";



// ******************************************************************
/*  potentially used by pdf generation */

function showgrid($pdf) {
	// generate a grid for placement
	for ($x=0; $x<=8.5; $x+=0.1) {
		for ($y=0; $y<=11; $y+=0.1) {
			$pdf -> SetTextColor(0,0,0);
			$pdf -> SetFont("courier", "", 3);
			$pdf -> Text($x-0.01,$y-0.01,".");
		}
	}
	for ($x=0; $x<=9; $x+=1) {
		for ($y=0; $y<=11; $y+=1) {
			$pdf -> SetTextColor(255,0,0);
			$pdf -> SetFont("times", "", 10);
			$pdf -> Text($x-.02,$y-.01,".");
			$pdf -> SetFont("courier", "", 4);
			$pdf -> Text($x+0.01,$y+0.035,$x.",".$y);
		}
	}
}
?>