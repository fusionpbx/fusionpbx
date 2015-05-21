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
	James Rose <james.o.rose@gmail.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Errol Samuels <voiptology@gmail.com>
*/

if (!isset($included)) { $included = false; }

if (!$included) {

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
		$language = new text;
		$text = $language->get();

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
					$fax_caller_id_name = $row["fax_caller_id_name"];
					$fax_caller_id_number = $row["fax_caller_id_number"];
					$fax_accountcode = $row["accountcode"];
				//limit to one row
					break;
			}
			unset ($prep_statement);
		}

	//set the fax directory
		$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax'.((count($_SESSION["domains"]) > 1) ? '/'.$_SESSION['domain_name'] : null);

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
				chmod($_SESSION['switch']['storage']['dir'],0774);
			}
			if (!is_dir($_SESSION['switch']['storage']['dir'].'/fax')) {
				mkdir($_SESSION['switch']['storage']['dir'].'/fax');
				chmod($_SESSION['switch']['storage']['dir'].'/fax',0774);
			}
			if (count($_SESSION["domains"]) > 1 && !is_dir($_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'])) {
				mkdir($_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name']);
				chmod($_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'],0774);
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

//clear file status cache
	clearstatcache();


//send the fax
	$continue = false;

	if (!$included) {
		if (($_POST['action'] == "send")) {

			$fax_numbers = $_POST['fax_numbers'];
			if (sizeof($fax_numbers) > 0) {
				foreach ($fax_numbers as $index => $fax_number) {
					$fax_numbers[$index] = preg_replace("~[^0-9]~", "", $fax_number);
					if ($fax_numbers[$index] == '') { unset($fax_numbers[$index]); }
				}
				sort($fax_numbers);
			}

			$fax_uuid = check_str($_POST["id"]);
			$fax_caller_id_name = check_str($_POST['fax_caller_id_name']);
			$fax_caller_id_number = check_str($_POST['fax_caller_id_number']);
			$fax_header = check_str($_POST['fax_header']);
			$fax_sender = check_str($_POST['fax_sender']);
			$fax_recipient = check_str($_POST['fax_recipient']);
			$fax_subject = check_str($_POST['fax_subject']);
			$fax_message = check_str($_POST['fax_message']);
			$fax_resolution = check_str($_POST['fax_resolution']);
			$fax_page_size = check_str($_POST['fax_page_size']);
			$fax_footer = check_str($_POST['fax_footer']);

			$continue = true;
		}
	}
	else {
		//all necessary local and session variables should
		//be already set by now by file including this one
		$continue = true;
	}

	if ($continue) {
		//determine page size
		switch ($fax_page_size) {
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

		//set resolution
		switch ($fax_resolution) {
			case 'fine':
				$gs_r = '204x196';
				$gs_g = ((int) ($page_width * 204)).'x'.((int) ($page_height * 196));
				break;
			case 'superfine':
				$gs_r = '408x391';
				$gs_g = ((int) ($page_width * 408)).'x'.((int) ($page_height * 391));
				break;
			case 'normal':
			default:
				$gs_r = '204x98';
				$gs_g = ((int) ($page_width * 204)).'x'.((int) ($page_height * 98));
				break;
		}

		// process uploaded or emailed files (if any)
		$fax_page_count = 0;
		$_files = (!$included) ? $_FILES['fax_files'] : $emailed_files;
		foreach ($_files['tmp_name'] as $index => $fax_tmp_name) {
			$uploaded_file = (!$included) ? is_uploaded_file($fax_tmp_name) : true;
			if ( $uploaded_file && $_files['error'][$index] == 0 && $_files['size'][$index] > 0 ) {
				//get the file extension
				$fax_file_extension = strtolower(pathinfo($_files['name'][$index], PATHINFO_EXTENSION));
				if ($fax_file_extension == "tiff") { $fax_file_extension = "tif"; }

				//block unauthorized files
				$disallowed_file_extensions = explode(',','sh,ssh,so,dll,exe,bat,vbs,zip,rar,z,tar,tbz,tgz,gz');
				if (in_array($fax_file_extension, $disallowed_file_extensions) || $fax_file_extension == '') { continue; }

				$fax_name = $_files['name'][$index];
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

				if (!$included) {
					//move uploaded file
					move_uploaded_file($_files['tmp_name'][$index], $dir_fax_temp.'/'.$fax_name.'.'.$fax_file_extension);
				}

				//convert uploaded file to pdf, if necessary
				if ($fax_file_extension != "pdf" && $fax_file_extension != "tif") {
					chdir($dir_fax_temp);
					exec("libreoffice --headless --convert-to pdf --outdir ".$dir_fax_temp." ".$dir_fax_temp.'/'.$fax_name.'.'.$fax_file_extension);
					@unlink($dir_fax_temp.'/'.$fax_name.'.'.$fax_file_extension);
				}

				//convert uploaded pdf to tif
				if (file_exists($dir_fax_temp.'/'.$fax_name.'.pdf')) {
					chdir($dir_fax_temp);
					exec("gs -q -sDEVICE=tiffg3 -r".$gs_r." -g".$gs_g." -dNOPAUSE -sOutputFile=".$fax_name.".tif -- ".$fax_name.".pdf -c quit"); //convert pdf to tif
					@unlink($dir_fax_temp.'/'.$fax_name.'.pdf');
				}

				$tif_page_count = exec("tiffinfo ".$dir_fax_temp.'/'.$fax_name.".tif | grep \"Page Number\" | grep -c \"P\"");
				if ($tif_page_count != '') {
					$fax_page_count += $tif_page_count;
				}

				//add file to array
				$tif_files[] = $dir_fax_temp.'/'.$fax_name.'.tif';
			} //if
		} //foreach

		// unique id for this fax
		$fax_instance_uuid = uuid();

		//generate cover page, merge with pdf
		if ($fax_subject != '' || $fax_message != '') {

			//load pdf libraries
			require_once("resources/tcpdf/tcpdf.php");
			require_once("resources/fpdi/fpdi.php");

			// initialize pdf
			$pdf = new FPDI('P', 'in');
			$pdf -> SetAutoPageBreak(false);
			$pdf -> setPrintHeader(false);
			$pdf -> setPrintFooter(false);
			$pdf -> SetMargins(0, 0, 0, true);

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
				$logo = PROJECT_PATH."/app/fax/resources/images/logo.jpg";
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
				//set position for header text, if enabled
				$pdf -> SetXY($x + 0.5, $y + 0.4);
			}

			//header
			if ($fax_header != '') {
				$pdf -> SetLeftMargin(0.5);
				$pdf -> SetFont("times", "", 10);
				$pdf -> Write(0.3, $fax_header);
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
			if ($fax_recipient != '' || sizeof($fax_numbers) > 0) {
				$pdf -> Text($x + 0.5, $y + 2.0, strtoupper($text['label-fax-recipient']).":");
			}
			if ($fax_sender != '' || $fax_caller_id_number != '') {
				$pdf -> Text($x + 0.5, $y + 2.3, strtoupper($text['label-fax-sender']).":");
			}
			if ($fax_page_count > 0) {
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
			}
			if (sizeof($fax_numbers) > 0) {
				$fax_number_string = ($fax_recipient != '') ? ' (' : null;
				$fax_number_string .= format_phone($fax_numbers[0]);
				if (sizeof($fax_numbers) > 1) {
					for ($n = 1; $n <= sizeof($fax_numbers); $n++) {
						if ($n == 4) { break; }
						$fax_number_string .= ', '.format_phone($fax_numbers[$n]);
					}
				}
				$fax_number_string .= (sizeof($fax_numbers) > 4) ? ', +'.(sizeof($fax_numbers) - 4) : null;
				$fax_number_string .= ($fax_recipient != '') ? ')' : null;
				$pdf -> Write(0.3, $fax_number_string);
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
			if ($fax_page_count > 0) {
				$pdf -> Text($x + 2.0, $y + 2.6, $fax_page_count.' '.$text['label-fax-page'.(($fax_page_count > 1) ? 's' : null)]);
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

			// save cover pdf
			$pdf -> Output($dir_fax_temp.'/'.$fax_instance_uuid.'_cover.pdf', "F");	// Display [I]nline, Save to [F]ile, [D]ownload

			//convert pdf to tif, add to array of pages, delete pdf
			if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.'_cover.pdf')) {
				chdir($dir_fax_temp);
				exec("gs -q -sDEVICE=tiffg3 -r".$gs_r." -g".$gs_g." -dNOPAUSE -sOutputFile=".$fax_instance_uuid."_cover.tif -- ".$fax_instance_uuid."_cover.pdf -c quit");
				if (is_array($tif_files) && sizeof($tif_files) > 0) {
					array_unshift($tif_files, $dir_fax_temp.'/'.$fax_instance_uuid.'_cover.tif');
				}
				else {
					$tif_files[] = $dir_fax_temp.'/'.$fax_instance_uuid.'_cover.tif';
				}
				@unlink($dir_fax_temp.'/'.$fax_instance_uuid.'_cover.pdf');
			}
		}

		//combine tif files into single multi-page tif
		if (is_array($tif_files) && sizeof($tif_files) > 0) {
			$cmd = "tiffcp -c none ";
			foreach ($tif_files as $tif_file) {
				$cmd .= $tif_file.' ';
			}
			$cmd .= $dir_fax_temp.'/'.$fax_instance_uuid.'.tif';
			exec($cmd);
			foreach ($tif_files as $tif_file) {
				@unlink($tif_file);
			}
			//generate pdf (a work around, as tiff2pdf was improperly inverting the colors)
			exec("tiff2pdf -u i -p ".$fax_page_size." -w ".$page_width." -l ".$page_height." -f -o ".$dir_fax_temp.'/'.$fax_instance_uuid.".pdf ".$dir_fax_temp.'/'.$fax_instance_uuid.".tif");
			chdir($dir_fax_temp);
			exec("gs -q -sDEVICE=tiffg3 -r".$gs_r." -g".$gs_g." -dNOPAUSE -sOutputFile=".$fax_instance_uuid."_temp.tif -- ".$fax_instance_uuid.".pdf -c quit"); //convert pdf to tif
			@unlink($dir_fax_temp.'/'.$fax_instance_uuid.".pdf");
			exec("tiff2pdf -u i -p ".$fax_page_size." -w ".$page_width." -l ".$page_height." -f -o ".$dir_fax_temp.'/'.$fax_instance_uuid.".pdf ".$dir_fax_temp.'/'.$fax_instance_uuid."_temp.tif");
			@unlink($dir_fax_temp.'/'.$fax_instance_uuid."_temp.tif");
		}
		else {
			if (!$included) {
				//nothing to send, redirect the browser
				$_SESSION['message_mood'] = 'negative';
				$_SESSION["message"] = $text['message-invalid-fax'];
				header("Location: fax_send.php?id=".$fax_uuid);
				exit;
			}
		}

		//preview, if requested
		if ($_REQUEST['submit'] == $text['button-preview']) {
			unset($file_type);
			if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.'.pdf')) {
				$file_type = 'pdf';
				$content_type = 'application/pdf';
				@unlink($dir_fax_temp.'/'.$fax_instance_uuid.".tif");
			}
			else if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.'.tif')) {
				$file_type = 'tif';
				$content_type = 'image/tiff';
				@unlink($dir_fax_temp.'/'.$fax_instance_uuid.".pdf");
			}
			if ($file_type != '') {
				//push download
				$fd = fopen($dir_fax_temp.'/'.$fax_instance_uuid.'.'.$file_type, "rb");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; filename="'.$fax_instance_uuid.'.'.$file_type.'"');
				header("Content-Type: ".$content_type);
				header('Accept-Ranges: bytes');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
				header("Content-Length: ".filesize($dir_fax_temp.'/'.$fax_instance_uuid.'.'.$file_type));
				fpassthru($fd);
				@unlink($dir_fax_temp.'/'.$fax_instance_uuid.".".$file_type);
			}
			exit;
		}

		//get some more info to send the fax
		$mailfrom_address = (isset($_SESSION['fax']['smtp_from']['var'])) ? $_SESSION['fax']['smtp_from']['var'] : $_SESSION['email']['smtp_from']['var'];

		$sql = "select * from v_fax where fax_uuid = '".$fax_uuid."'; ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetch(PDO::FETCH_NAMED);
		$mailto_address_fax = $result["fax_email"];
		$fax_prefix = $result["fax_prefix"];

		if (!$included) {
			$sql = "select contact_uuid from v_users where user_uuid = '".$_SESSION['user_uuid']."'; ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);

			$sql = "select email_address from v_contact_emails where contact_uuid = '".$result["contact_uuid"]."' order by email_primary desc;";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			$mailto_address_user = $result["email_address"];
		}
		else {
			//use email-to-fax from address
		}

		if ($mailto_address_fax != '' && $mailto_address_user != $mailto_address_fax) {
			$mailto_address = $mailto_address_fax.",".$mailto_address_user;
		}
		else {
			$mailto_address = $mailto_address_user;
		}

		//send the fax
		foreach ($fax_numbers as $fax_number) {
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				//prepare the fax command
				$route_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $fax_prefix.$fax_number);
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
				$cmd = "api originate {for_fax=1,absolute_codec_string='PCMU,PCMA',accountcode='".$fax_accountcode."',sip_h_X-accountcode='".$fax_accountcode."',domain_uuid=".$_SESSION["domain_uuid"].",domain_name=".$_SESSION["domain_name"].",mailto_address='".$mailto_address."',mailfrom_address='".$mailfrom_address."',origination_caller_id_name='".$fax_caller_id_name."',origination_caller_id_number='".$fax_caller_id_number."',fax_ident='".$fax_caller_id_number."',fax_header='".$fax_caller_id_name."',fax_uri=".$fax_uri.",fax_file='".$fax_file."',fax_retry_attempts=1,fax_retry_limit=20,fax_retry_sleep=180,fax_verbose=true,fax_use_ecm=off,".$t38."api_hangup_hook='lua fax_retry.lua'}".$fax_uri." &txfax('".$fax_file."')";
				//send the command to event socket
				$response = event_socket_request($fp, $cmd);
				$response = str_replace("\n", "", $response);
				$uuid = str_replace("+OK ", "", $response);
				fclose($fp);
			}
		}

		//wait for a few seconds
		sleep(5);

		//move the generated tif (and pdf) files to the sent directory
		exec("cp ".$dir_fax_temp.'/'.$fax_instance_uuid.".tif ".$dir_fax_sent.'/'.$fax_instance_uuid.".tif");
		if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.".pdf")) {
			exec("cp ".$dir_fax_temp.'/'.$fax_instance_uuid.".pdf ".$dir_fax_sent.'/'.$fax_instance_uuid.".pdf");
		}

		if (!$included) {
			//redirect the browser
			$_SESSION["message"] = $response;
			header("Location: fax_files.php?id=".$fax_uuid."&box=sent");
			exit;
		}

	} //end upload and send fax


if (!$included) {

	//show the header
		require_once "resources/header.php";

	//javascript to toggle input/select boxes, add fax numbers
		echo "<script language='JavaScript' type='text/javascript' src='".PROJECT_PATH."/resources/javascript/reset_file_input.js'></script>\n";
		echo "<script language='JavaScript' type='text/javascript'>";

		echo "	function toggle(field) {";
		echo "		if (field == 'fax_recipient') {";
		echo "			document.getElementById('fax_recipient_select').selectedIndex = 0;";
		echo "			$('#fax_recipient_select').toggle();";
		echo "			$('#fax_recipient').toggle();";
		echo "			if ($('#fax_recipient').is(':visible')) { $('#fax_recipient').focus(); } else { $('#fax_recipient_select').focus(); }";
		echo "		}";
		echo "	}";

		echo "	function contact_load(obj_sel) {";
		echo "		obj_sel.style.display='none';";
		echo "		document.getElementById('fax_recipient').style.display='';";
		echo "		var selected_option_value = obj_sel.options[obj_sel.selectedIndex].value;";
		echo "		var selected_option_values = selected_option_value.split('|', 2);";
		echo "		document.getElementById('fax_recipient').value = selected_option_values[1];";
		echo "		document.getElementById('fax_number').value = selected_option_values[0];";
		echo "		$('#fax_recipient').css({width: '50%'});";
		echo "		$('#fax_number').css({width: '120px'});";
		echo "	}";

		echo "	function list_selected_files(file_input_number) {";
		echo "		var inp = document.getElementById('fax_files_'+file_input_number);";
		echo "		var files_selected = [];";
		echo "		for (var i = 0; i < inp.files.length; ++i) {";
		echo "			var file_name = inp.files.item(i).name;";
		echo "			files_selected.push(file_name);";
		echo "		}";
		echo "		document.getElementById('file_list_'+file_input_number).innerHTML = '';";
		echo "		if (files_selected.length > 1) {";
		echo "			document.getElementById('file_list_'+file_input_number).innerHTML = '<strong>".$text['label-selected']."</strong>: ';";
		echo "			document.getElementById('file_list_'+file_input_number).innerHTML += files_selected.join(', ');";
		echo "			document.getElementById('file_list_'+file_input_number).innerHTML += '<br />';";
		echo "		}";
		echo "	}";

		echo "	function add_fax_number() {\n";
		echo "		var newdiv = document.createElement('div');\n";
		echo "		newdiv.innerHTML = \"<input type='text' name='fax_numbers[]' class='formfld' style='width: 150px; min-width: 150px; max-width: 150px; margin-top: 3px;' maxlength='25'>\";";
		echo "		document.getElementById('fax_numbers').appendChild(newdiv);";
		echo "	}\n";

		echo "</script>";

	//fax extension form
		echo "<form action='' method='POST' enctype='multipart/form-data' name='frmUpload' onSubmit=''>\n";
		echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "	<td align='left' valign='top' width='30%'>\n";
		echo "		<span class='title'>".$text['header-send']."</span>\n";
		echo "	</td>\n";
		echo "	<td width='70%' align='right' valign='top'>\n";
		echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='fax.php'\" value='".$text['button-back']."'>\n";
		echo "		<input type='submit' name='submit' class='btn' id='preview' value='".$text['button-preview']."'>\n";
		echo "		<input name='submit' type='submit' class='btn' id='upload' value='".$text['button-send']."'>\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<br>\n";

		echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
		echo "	<tr>\n";
		echo "		<td colspan='2' align='left'>\n";
		echo "			".$text['description-2']." ".((if_group('superadmin')) ? $text['description-3'] : null)." \n";
		echo "			<br /><br />\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-fax-header']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input type='text' name='fax_header' class='formfld' style='' value='".$_SESSION['fax']['cover_header']['text']."'>\n";
		echo "	<br />\n";
		echo "	".$text['description-fax-header']."\n";
		echo "</td>\n";
		echo "</tr>\n";

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
		echo "	".$text['label-fax-recipient']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		//retrieve current user's assigned groups (uuids)
		foreach ($_SESSION['groups'] as $group_data) {
			$user_group_uuids[] = $group_data['group_uuid'];
		}
		//add user's uuid to group uuid list to include private (non-shared) contacts
		$user_group_uuids[] = $_SESSION["user_uuid"];
		$sql = "select ";
		$sql .= "c.contact_organization, ";
		$sql .= "c.contact_name_given, ";
		$sql .= "c.contact_name_family, ";
		$sql .= "c.contact_nickname, ";
		$sql .= "cp.phone_number ";
		$sql .= "from ";
		$sql .= "v_contacts as c, ";
		$sql .= "v_contact_phones as cp ";
		$sql .= "where ";
		$sql .= "c.contact_uuid = cp.contact_uuid and  ";
		$sql .= "c.domain_uuid = '".$_SESSION['domain_uuid']."' and ";
		$sql .= "cp.domain_uuid = '".$_SESSION['domain_uuid']."' and ";
		$sql .= "cp.phone_type_fax = 1 and ";
		$sql .= "cp.phone_number is not null and ";
		$sql .= "cp.phone_number <> '' ";
		if (sizeof($user_group_uuids) > 0) {
			//only show contacts assigned to current user's group(s) and those not assigned to any group
			$sql .= "and ( \n";
			$sql .= "	c.contact_uuid in ( \n";
			$sql .= "		select contact_uuid from v_contact_groups ";
			$sql .= "		where group_uuid in ('".implode("','", $user_group_uuids)."') ";
			$sql .= "		and domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "	) \n";
			$sql .= "	or \n";
			$sql .= "	c.contact_uuid not in ( \n";
			$sql .= "		select contact_uuid from v_contact_groups ";
			$sql .= "		where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "	) \n";
			$sql .= ") \n";
		}
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement -> execute();
		$result = $prep_statement -> fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		if ($result_count > 0) {
			foreach ($result as &$row) {
				if ($row['contact_organization'] != '') {
					$contact_option_label = $row['contact_organization'];
				}
				if ($row['contact_name_given'] != '' || $row['contact_name_family'] != '' || $row['contact_nickname'] != '') {
					$contact_option_label .= ($row['contact_organization'] != '') ? "," : null;
					$contact_option_label .= ($row['contact_name_given'] != '') ? (($row['contact_organization'] != '') ? " " : null).$row['contact_name_given'] : null;
					$contact_option_label .= ($row['contact_name_family'] != '') ? (($row['contact_organization'] != '' || $row['contact_name_given'] != '') ? " " : null).$row['contact_name_family'] : null;
					$contact_option_label .= ($row['contact_nickname'] != '') ? (($row['contact_organization'] != '' || $row['contact_name_given'] != '' || $row['contact_name_family'] != '') ? " (".$row['contact_nickname'].")" : $row['contact_nickname']) : null;
				}
				$contact_option_value_recipient = $contact_option_label;
				$contact_option_value_faxnumber = $row['phone_number'];
				$contact_option_label .= ":&nbsp;&nbsp;".format_phone($row['phone_number']);
				$contact_labels[] = $contact_option_label;
				$contact_values[] = $contact_option_value_faxnumber."|".$contact_option_value_recipient;
				unset($contact_option_label);
			}
			asort($contact_labels, SORT_NATURAL); // sort by name(s)
			echo "	<select class='formfld' style='display: none;' id='fax_recipient_select' onchange='contact_load(this);'>\n";
			echo "		<option value=''></option>\n";
			foreach ($contact_labels as $index => $contact_label) {
				echo "	<option value=\"".$contact_values[$index]."\">".$contact_label."</option>\n";
			}
			echo "	</select>\n";
		}
		unset ($prep_statement);
		echo "	<input type='text' name='fax_recipient' id='fax_recipient' class='formfld' style='max-width: 250px;' value=''>\n";
		if ($result_count > 0) {
			echo "	<input type='button' id='btn_toggle_recipient' class='btn' name='' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle('fax_recipient');\">\n";
		}
		echo "	<br />\n";
		echo "	".$text['description-fax-recipient']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "		".$text['label-fax-number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<table cellpadding='0' cellspacing='0' border='0'>";
		echo "		<tr>";
		echo "			<td id='fax_numbers'>";
		echo "				<input type='text' name='fax_numbers[]' id='fax_number' class='formfld' style='width: 150px; min-width: 150px; max-width: 150px;' maxlength='25'>\n";
		echo "			</td>";
		echo "			<td style='vertical-align: bottom;'>";
		echo "				<a href='javascript:void(0);' onclick='add_fax_number();'>$v_link_label_add</a>";
		echo "			</td>";
		echo "		</tr>";
		echo "	</table>";
		echo "	".$text['description-fax-number']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-fax_files']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		for ($f = 1; $f <= 3; $f++) {
			echo "	<span id='fax_file_".$f."' ".(($f > 1) ? "style='display: none;'" : null).">";
			echo "	<input name='fax_files[]' id='fax_files_".$f."' type='file' class='formfld fileinput' style='margin-right: 3px; ".(($f > 1) ? "margin-top: 3px;" : null)."' onchange=\"".(($f < 3) ? "document.getElementById('fax_file_".($f+1)."').style.display='';" : null)." list_selected_files(".$f.");\" multiple='multiple'><input type='button' class='btn' value='".$text['button-clear']."' onclick=\"reset_file_input('fax_files_".$f."'); document.getElementById('file_list_".$f."').innerHTML='';\"><br />";
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
		echo "			<br>\n";
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
		echo "<br />\n";

	//show the footer
		require_once "resources/footer.php";

}

// used for initial element alignment during pdf generation
/*
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
*/
?>
