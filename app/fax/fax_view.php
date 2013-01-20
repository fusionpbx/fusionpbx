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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('fax_extension_view')) {
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
	if (strlen($_GET['id']) > 0 && $_POST["persistformvar"] != "true") {
		$fax_uuid = check_str($_GET["id"]);
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

//delete a fax
	if ($_GET['a'] == "del" && permission_exists('fax_inbox_delete')) {
		$file_name = substr(check_str($_GET['filename']), 0, -4);
		$file_ext = substr(check_str($_GET['filename']), -3);
		if ($_GET['type'] == "fax_inbox") {
			unlink($fax_dir.'/'.$fax_extension.'/inbox/'.$file_name.".tif");
			unlink($fax_dir.'/'.$fax_extension.'/inbox/'.$file_name.".pdf");
		}
		if ($_GET['type'] == "fax_sent") {
			unlink($fax_dir.'/'.$fax_extension.'/sent/'.$file_name.".tif");
			unlink($fax_dir.'/'.$fax_extension.'/sent/'.$file_name.".pdf");
		}
		unset($file_name);
		unset($file_ext);
	}

//download the fax
	if ($_GET['a'] == "download") {
		session_cache_limiter('public');
		//test to see if it is in the inbox or sent directory.
		if ($_GET['type'] == "fax_inbox") {
			if (file_exists($fax_dir.'/'.check_str($_GET['ext']).'/inbox/'.check_str($_GET['filename']))) {
				$tmp_faxdownload_file = "".$fax_dir.'/'.check_str($_GET['ext']).'/inbox/'.check_str($_GET['filename']);
			}
		}
		else if ($_GET['type'] == "fax_sent") {
			if  (file_exists($fax_dir.'/'.check_str($_GET['ext']).'/sent/'.check_str($_GET['filename']))) {
				$tmp_faxdownload_file = "".$fax_dir.'/'.check_str($_GET['ext']).'/sent/'.check_str($_GET['filename']);
			}
		}
		//let's see if we found it.
		if (strlen($tmp_faxdownload_file) > 0) {
			$fd = fopen($tmp_faxdownload_file, "rb");
			if ($_GET['t'] == "bin") {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; filename="'.check_str($_GET['filename']).'"');
			}
			else {
				$file_ext = substr(check_str($_GET['filename']), -3);
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
			header("Content-Length: " . filesize($tmp_faxdownload_file));
			fpassthru($fd);
		}
		else {
			echo "".$text['label-file']."";
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

//upload and send the fax
	if (($_POST['type'] == "fax_send") && is_uploaded_file($_FILES['fax_file']['tmp_name'])) {

		$fax_number = check_str($_POST['fax_number']);
		if (strlen($fax_number) > 0) {
			$fax_number = preg_replace("~[^0-9]~", "",$fax_number);
		}
		$fax_name = $_FILES['fax_file']['name'];
		$fax_name = str_replace(" ", "_", $fax_name);
		$fax_name = str_replace(".tif", "", $fax_name);
		$fax_name = str_replace(".tiff", "", $fax_name);
		$fax_name = str_replace(".pdf", "", $fax_name);
		$provider_type = check_str($_POST['provider_type']);
		$fax_uuid = check_str($_POST["id"]);

		$fax_caller_id_name = check_str($_POST['fax_caller_id_name']);
		$fax_caller_id_number = check_str($_POST['fax_caller_id_number']);
		$fax_forward_number = check_str($_POST['fax_forward_number']);
		if (strlen($fax_forward_number) > 0) {
			$fax_forward_number = preg_replace("~[^0-9]~", "",$fax_forward_number);
		}

		//get the fax file extension
			$fax_file_extension = substr($dir_fax_temp.'/'.$_FILES['fax_file']['name'], -4);
			if ($fax_file_extension == "tiff") { $fax_file_extension = ".tif"; }

		//upload the file
			move_uploaded_file($_FILES['fax_file']['tmp_name'], $dir_fax_temp.'/'.$fax_name.$fax_file_extension);

			if ($fax_file_extension == ".pdf") {
				chdir($dir_fax_temp);
				exec("gs -q -sDEVICE=tiffg3 -r204x196 -g1728x2156 -dNOPAUSE -sOutputFile=".$fax_name.".tif -- ".$fax_name.".pdf -c quit");
				//exec("rm ".$dir_fax_temp.'/'.$fax_name.".pdf");
			}
		//get some more info to send the fax
			$mailfrom_address = $_SESSION['email']['smtp_from']['var'];

			$sql = "select fax_email from v_fax where fax_uuid = '".$fax_uuid."'; ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			$mailto_address_fax = $result["fax_email"];
			echo $mailto_address_fax;

			$sql = "select contact_uuid from v_users where user_uuid = '".$_SESSION['user_uuid']."'; ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			//print_r($result);

			$sql = "select contact_email from v_contacts where contact_uuid = '".$result["contact_uuid"]."'; ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			//print_r($result);
			$mailto_address_user = $result["contact_email"];
			echo $mailto_address_user;

			if ($mailto_address_user != $mailto_address_fax) {
				$mailto_address = "'".$mailto_address_fax."\,".$mailto_address_user."'";
			}		
			else {
			$mailto_address = $mailto_address_user;
			}

		//send the fax
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				//prepare the fax  command
					$route_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $fax_number);
					$fax_file = $dir_fax_temp."/".$fax_name.".tif";
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
					$cmd = "api originate {mailto_address='".$mailto_address."',mailfrom_address='".$mailfrom_address."',origination_caller_id_name='".$fax_caller_id_name."',origination_caller_id_number='".$fax_caller_id_number."',fax_ident='".$fax_caller_id_number."',fax_header='".$fax_caller_id_name."',fax_uri=".$fax_uri.",fax_file='".$fax_file."',fax_retry_attempts=1,fax_retry_limit=20,fax_retry_sleep=180,fax_verbose=true,fax_use_ecm=off,".$t38."api_hangup_hook='lua fax_retry.lua'}".$fax_uri." &txfax('".$fax_file."')";
				//send the command to event socket
					$response = event_socket_request($fp, $cmd);
					$response = str_replace("\n", "", $response);
					$uuid = str_replace("+OK ", "", $response);
					fclose($fp);
			}

		//wait for a few seconds
			sleep(5);

		//copy the .tif to the sent directory
			exec("cp ".$dir_fax_temp.'/'.$fax_name.".tif ".$dir_fax_sent.'/'.$fax_name.".tif");

		//convert the tif to pdf
			chdir($dir_fax_sent);
			exec("gs -q -sDEVICE=tiffg3 -g1728x1078 -dNOPAUSE -sOutputFile=".$fax_name.".pdf -- ".$fax_name.".tif -c quit");

		//delete the .tif from the temp directory
			//exec("rm ".$dir_fax_temp.'/'.$fax_name.".tif");

		//convert the tif to pdf and png
			chdir($dir_fax_sent);
			//which tiff2pdf
			if (is_file("/usr/local/bin/tiff2png")) {
				exec($_SESSION['switch']['bin']['dir']."/tiff2png ".$dir_fax_sent.$fax_name.".tif");
				exec($_SESSION['switch']['bin']['dir']."/tiff2pdf -f -o ".$fax_name.".pdf ".$dir_fax_sent.$fax_name.".tif");
			}

		header("Location: fax_view.php?id=".$fax_uuid."&msg=".$response);
		exit;
	} //end upload and send fax

//delete the fax
	if ($_GET['a'] == "del") {
		$fax_extension = check_str($_GET["fax_extension"]);
		if ($_GET['type'] == "fax_inbox" && permission_exists('fax_inbox_delete')) {
			unlink($fax_dir.'/'.$fax_extension.'/inbox/'.check_str($_GET['filename']));
		}
		if ($_GET['type'] == "fax_sent" && permission_exists('fax_sent_delete')) {
			unlink($fax_dir.'/'.$fax_extension.'/sent/'.check_str($_GET['filename']));
		}
	}


//show the header
	require_once "includes/header.php";

//fax extension form
	echo "<div align='center'>";
	echo "<table border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "		<td align='left' width='30%'>\n";
	echo "			<span class=\"vexpl\"><span class=\"red\"><strong>".$text['title']."</strong></span>\n";
	echo "		</td>\n";
	echo "		<td width='70%' align='right'>\n";
	if (permission_exists('fax_extension_add') || permission_exists('fax_extension_edit')) {
		echo "			<input type='button' class='btn' name='' alt='settings' onclick=\"window.location='fax_edit.php?id=$fax_uuid'\" value='".$text['button-settings']."'>\n";
	}
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='fax.php'\" value='".$text['button-back']."'>\n";
	echo "		</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>\n";

	echo "<form action=\"\" method=\"POST\" enctype=\"multipart/form-data\" name=\"frmUpload\" onSubmit=\"\">\n";
	echo "<div align='center'>\n";
	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='left'>\n";
	//pkg_add -r ghostscript8-nox11; rehash
	echo "			".$text['description-2']." \n";
	echo "			".$text['description-3']."\n";
	echo "			<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "		".$text['label-fax-number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "		<input type=\"text\" name=\"fax_number\" class='formfld' style='' value=\"\">\n";
	echo "<br />\n";
	echo "".$text['description-fax-number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-upload'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input name=\"id\" type=\"hidden\" value=\"\$id\">\n";
	echo "	<input name=\"type\" type=\"hidden\" value=\"fax_send\">\n";
	echo "	<input name=\"fax_file\" type=\"file\" class=\"btn\" id=\"fax_file\" accept=\"image/tiff,application/pdf\">\n";
	echo "	<br />\n";
	echo "	".$text['description-upload']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type=\"hidden\" name=\"fax_caller_id_name\" value=\"".$fax_caller_id_name."\">\n";
	echo "			<input type=\"hidden\" name=\"fax_caller_id_number\" value=\"".$fax_caller_id_number."\">\n";
	echo "			<input type=\"hidden\" name=\"fax_extension\" value=\"".$fax_extension."\">\n";
	echo "			<input type=\"hidden\" name=\"id\" value=\"".$fax_uuid."\">\n";
	echo "			<input name=\"submit\" type=\"submit\" class=\"btn\" id=\"upload\" value=\"".$text['button-send']."\">\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</div>\n";
	echo "</form>\n";

//show the inbox
	if (permission_exists('fax_inbox_view')) {
		echo "\n";
		echo "\n";
		echo "	<br />\n";
		echo "\n";
		echo "	<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
		echo "	<tr>\n";
		echo "		<td align='left'>\n";
		echo "			<span class=\"vexpl\"><span class=\"red\"><strong>Inbox $fax_extension</strong></span>\n";
		echo "		</td>\n";
		echo "		<td align='right'>";
		if ($v_path_show) {
			echo "<b>".$text['label-location'].":</b>&nbsp;";
			echo $dir_fax_inbox."&nbsp; &nbsp; &nbsp;";
		}
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "    </table>\n";
		echo "\n";

		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		echo "	<div id=\"\">\n";
		echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "	<tr>\n";
		echo "		<th width=\"60%\" class=\"listhdrr\">".$text['table-file']."</td>\n";
		echo "		<th width=\"10%\" class=\"listhdrr\">".$text['table-view']."</td>\n";
		echo "		<th width=\"20%\" class=\"listhdr\">".$text['table-modified']."</td>\n";
		echo "		<th width=\"10%\" class=\"listhdr\" nowrap>Size".$text['table-size']."</td>\n";
		echo "	</tr>";


		if ($handle = opendir($dir_fax_inbox)) {
			//build an array of the files in the inbox
				$i = 0;
				$files = array();
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != ".." && is_file($dir_fax_inbox.'/'.$file)) {
						$file_path = $dir_fax_inbox.'/'.$file;
						$modified = filemtime($file_path);
						$index = $modified.$file;
						$files[$index]['file'] = $file;
						$files[$index]['name'] = substr($file, 0, -4);
						$files[$index]['ext'] = substr($file, -3);
						//$files[$index]['path'] = $file_path;
						$files[$index]['size'] = filesize($file_path);
						$files[$index]['size_bytes'] = byte_convert(filesize($file_path));
						$files[$index]['modified'] = filemtime($file_path);
						$file_name_array[$i++] = $index;
					}
				}
				closedir($handle);
			//order the index array
				sort($file_name_array,SORT_STRING);

			//loop through the file array
				foreach($file_name_array as $i) {
					if (strtolower($files[$i]['ext']) == "tif") {
						$file = $files[$i]['file'];
						$file_name = $files[$i]['name'];
						$file_ext = $files[$i]['ext'];
						$file_modified = $files[$i]['modified'];
						$file_size_bytes = byte_convert($files[$i]['size']);
						if (!file_exists($dir_fax_inbox.'/'.$file_name.".pdf")) {
							//convert the tif to pdf
								chdir($dir_fax_inbox);
								if (is_file("/usr/local/bin/tiff2pdf")) {
									exec("/usr/local/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_inbox.'/'.$file_name.".tif");
								}
								if (is_file("/usr/bin/tiff2pdf")) {
									exec("/usr/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_inbox.'/'.$file_name.".tif");
								}
						}
						//if (!file_exists($dir_fax_inbox.'/'.$file_name.".jpg")) {
						//	//convert the tif to jpg
						//		chdir($dir_fax_inbox);
						//		if (is_file("/usr/local/bin/tiff2rgba")) {
						//			exec("/usr/local/bin/tiff2rgba ".$file_name.".tif ".$dir_fax_inbox.'/'.$file_name.".jpg");
						//		}
						//		if (is_file("/usr/bin/tiff2rgba")) {
						//			exec("/usr/bin/tiff2rgba ".$file_name.".tif ".$dir_fax_inbox.'/'.$file_name.".jpg");
						//		}
						//}
						echo "<tr>\n";
						echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						echo "	  <a href=\"fax_view.php?id=".$fax_uuid."&a=download&type=fax_inbox&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file)."\">\n";
						echo "    	$file_name";
						echo "	  </a>";
						echo "  </td>\n";

						echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						if (file_exists($dir_fax_inbox.'/'.$file_name.".pdf")) {
							echo "	  <a href=\"fax_view.php?id=".$fax_uuid."&a=download&type=fax_inbox&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file_name).".pdf\">\n";
							echo "    	PDF";
							echo "	  </a>";
						}
						else {
							echo "&nbsp;\n";
						}
						echo "  </td>\n";

						//echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						//if (file_exists($dir_fax_inbox.'/'.$file_name.".jpg")) {
						//	echo "	  <a href=\"fax_view.php?id=".$fax_uuid."&a=download&type=fax_inbox&t=jpg&ext=".$fax_extension."&filename=".$file_name.".jpg\" target=\"_blank\">\n";
						//	echo "    	jpg";
						//	echo "	  </a>";
						//}
						//else {
						//	echo "&nbsp;\n";
						//}
						//echo "  &nbsp;</td>\n";

						echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						echo "		".date("F d Y H:i:s", $file_modified);
						echo "  </td>\n";

						echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						echo "	".$file_size_bytes;
						echo "  </td>\n";

						echo "  <td valign=\"middle\" nowrap class=\"list\">\n";
						echo "    <table border=\"0\" cellspacing=\"0\" cellpadding=\"1\">\n";
						echo "      <tr>\n";
						if (permission_exists('fax_inbox_delete')) {
							echo "        <td><a href=\"fax_view.php?id=".$fax_uuid."&type=fax_inbox&a=del&fax_extension=".urlencode($fax_extension)."&filename=".urlencode($file)."\" onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a></td>\n";
						}
						echo "      </tr>\n";
						echo "   </table>\n";
						echo "  </td>\n";
						echo "</tr>\n";
					}
				}
		}
		echo "	<tr>\n";
		echo "		<td class=\"list\" colspan=\"3\"></td>\n";
		echo "		<td class=\"list\"></td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "\n";
		echo "	<br />\n";
		echo "	<br />\n";
		echo "\n";
	}

//show the sent box
	if (permission_exists('fax_sent_view')) {
		echo "  <table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
		echo "	<tr>\n";
		echo "		<td align='left'>\n";
		echo "			<span class=\"vexpl\"><span class=\"red\"><strong>Sent</strong></span>\n";
		echo "		</td>\n";
		echo "		<td align='right'>\n";
		if ($v_path_show) {
			echo "<b>".$text['label-location'].": </b>\n";
			echo $dir_fax_sent."&nbsp; &nbsp; &nbsp;\n";
		}
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "    </table>\n";
		echo "\n";
		echo "    <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		echo "    <tr>\n";
		echo "		<th width=\"60%\">".$text['table-file']."</td>\n";
		echo "		<th width=\"10%\">".$text['table-view']."</td>\n";
		echo "		<th width=\"20%\">".$text['table-modified']."</td>\n";
		echo "		<th width=\"10%\" nowrap>".$text['table-size']."</td>\n";
		echo "		</tr>";

		if ($handle = opendir($dir_fax_sent)) {
			//build an array of the files in the inbox
				$i = 0;
				$files = array();
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != ".." && is_file($dir_fax_sent.'/'.$file)) {
						$file_path = $dir_fax_sent.'/'.$file;
						$modified = filemtime($file_path);
						$index = $modified.$file;
						$files[$index]['file'] = $file;
						$files[$index]['name'] = substr($file, 0, -4);
						$files[$index]['ext'] = substr($file, -3);
						//$files[$index]['path'] = $file_path;
						$files[$index]['size'] = filesize($file_path);
						$files[$index]['size_bytes'] = byte_convert(filesize($file_path));
						$files[$index]['modified'] = filemtime($file_path);
						$file_name_array[$i++] = $index;
					}
				}
				closedir($handle);
			//order the index array
				sort($file_name_array,SORT_STRING);

			//loop through the file array
				foreach($file_name_array as $i) {
					if (strtolower($files[$i]['ext']) == "tif") {
						$file = $files[$i]['file'];
						$file_name = $files[$i]['name'];
						$file_ext = $files[$i]['ext'];
						$file_modified = $files[$i]['modified'];
						$file_size_bytes = byte_convert($files[$i]['size']);

						if (!file_exists($dir_fax_sent.'/'.$file_name.".pdf")) {
							//convert the tif to pdf
								chdir($dir_fax_sent);
								if (is_file("/usr/local/bin/tiff2pdf")) {
									exec("/usr/local/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_sent.'/'.$file_name.".tif");
								}
								if (is_file("/usr/bin/tiff2pdf")) {
									exec("/usr/bin/tiff2pdf -f -o ".$file_name.".pdf ".$dir_fax_sent.'/'.$file_name.".tif");
								}
						}
						if (!file_exists($dir_fax_sent.'/'.$file_name.".jpg")) {
							//convert the tif to jpg
								//chdir($dir_fax_sent);
								//if (is_file("/usr/local/bin/tiff2rgba")) {
								//	exec("/usr/local/bin/tiff2rgba -c jpeg -n ".$file_name.".tif ".$dir_fax_sent.'/'.$file_name.".jpg");
								//}
								//if (is_file("/usr/bin/tiff2rgba")) {
								//	exec("/usr/bin/tiff2rgba -c lzw -n ".$file_name.".tif ".$dir_fax_sent.'/'.$file_name.".jpg");
								//}
						}
						echo "<tr>\n";
						echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						echo "	  <a href=\"fax_view.php?id=".$fax_uuid."&a=download&type=fax_sent&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file)."\">\n";
						echo "    	$file";
						echo "	  </a>";
						echo "  </td>\n";
						echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						if (file_exists($dir_fax_sent.'/'.$file_name.".pdf")) {
							echo "	  <a href=\"fax_view.php?id=".$fax_uuid."&a=download&type=fax_sent&t=bin&ext=".urlencode($fax_extension)."&filename=".urlencode($file_name).".pdf\">\n";
							echo "    	PDF";
							echo "	  </a>";
						}
						else {
							echo "&nbsp;\n";
						}
						echo "  </td>\n";
						//echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						//if (file_exists($dir_fax_sent.'/'.$file_name.".jpg")) {
						//	echo "	  <a href=\"fax_view.php?id=".$fax_uuid."&a=download&type=fax_sent&t=jpg&ext=".$fax_extension."&filename=".$file_name.".jpg\" target=\"_blank\">\n";
						//	echo "    	jpg";
						//	echo "	  </a>";
						//}
						//else {
						//	echo "&nbsp;\n";
						//}
						//echo "  </td>\n";
						echo "  <td class='".$row_style[$c]."' ondblclick=\"\">\n";
						echo "		".date("F d Y H:i:s", $file_modified);
						echo "  </td>\n";

						echo "  <td class=\"".$row_style[$c]."\" ondblclick=\"list\">\n";
						echo "	".$file_size_bytes;
						echo "  </td>\n";

						echo "  <td class='' valign=\"middle\" nowrap>\n";
						echo "    <table border=\"0\" cellspacing=\"0\" cellpadding=\"1\">\n";
						echo "      <tr>\n";
						if (permission_exists('fax_sent_delete')) {
							echo "        <td><a href=\"fax_view.php?id=".$fax_uuid."&type=fax_sent&a=del&fax_extension=".urlencode($fax_extension)."&filename=".urlencode($file)."\" onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a></td>\n";
						}
						echo "      </tr>\n";
						echo "   </table>\n";
						echo "  </td>\n";
						echo "</tr>\n";
						if ($c==0) { $c=1; } else { $c=0; }
					} //check if the file is a .tif file
				}
		}
		echo "     <tr>\n";
		echo "       <td class=\"list\" colspan=\"3\"></td>\n";
		echo "       <td class=\"list\"></td>\n";
		echo "     </tr>\n";
		echo "     </table>\n";
		echo "\n";
		echo "	<br />\n";
		echo "	<br />\n";
		echo "	<br />\n";
		echo "	<br />\n";
	}
	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";
?>
