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
if (permission_exists('music_on_hold_view') || permission_exists('music_on_hold_default_view')) {
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

require_once "includes/paging.php";

$sampling_rate_dirs = Array(8000, 16000, 32000, 48000);
$music_on_hold_dir = $_SESSION['switch']['sounds']['dir'].'/music';
ini_set(max_execution_time,7200);

$order_by = $_GET["order_by"];
$order = $_GET["order"];

if ($_GET['a'] == "download") {
	$category_dir = $_GET['category'];
	$sampling_rate_dir = $_GET['sampling_rate'];

	if ($category_dir != '') {
		$path_mod = $category_dir."/";
		if (count($_SESSION['domains']) > 1) {
			$path_mod = $_SESSION["domain_name"]."/".$path_mod;
		}
	}

	session_cache_limiter('public');
	if ($_GET['type'] = "moh") {
		if (file_exists($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name']))) {
			$fd = fopen($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name']), "rb");
			if ($_GET['t'] == "bin") {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; file_name="'.base64_decode($_GET['file_name']).'"');
			}
			else {
				$file_ext = substr(base64_decode($_GET['file_name']), -3);
				if ($file_ext == "wav") {
					header("Content-Type: audio/x-wav");
				}
				if ($file_ext == "mp3") {
					header("Content-Type: audio/mp3");
				}
			}
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			header("Content-Length: " . filesize($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name'])));
			fpassthru($fd);
		}
	}
	exit;
}

if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['upload_file']['tmp_name'])) {
	$file_ext = strtolower(pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION));
	if ($file_ext == 'wav' || $file_ext == 'mp3') {
		if ($_POST['type'] == 'moh' && permission_exists('music_on_hold_add')) {

			// replace any spaces in the file_name with dashes
				$new_file_name = str_replace(' ', '-', $_FILES['upload_file']['name']);

			// convert sampling rate from value passed by form
				$sampling_rate_dir = $_POST['upload_sampling_rate'] * 1000;

			// if multi-tenant, modify directory paths
				if (count($_SESSION['domains']) > 1) {
					$path_mod = $_SESSION["domain_name"]."/";
				}

			// create new category, if necessary
				if ($_POST['upload_category'] == '_NEW_CAT_' && $_POST['upload_category_new'] != '') {
					$new_category_name = str_replace(' ', '_', $_POST['upload_category_new']);
					if (!is_dir($music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir)) {
						@mkdir($music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir, 0777, true);
					}
					if (is_dir($music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir)) {
						move_uploaded_file($_FILES['upload_file']['tmp_name'], $music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir."/".$new_file_name);
						$target_dir = $music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir;
					}
				}
			// use existing category directory
				else if ($_POST['upload_category'] != '' && $_POST['upload_category'] != '_NEW_CAT_') {
					if (!is_dir($music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir)) {
						@mkdir($music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir, 0777, true);
					}
					if (is_dir($music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir)) {
						move_uploaded_file($_FILES['upload_file']['tmp_name'], $music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir."/".$new_file_name);
						$target_dir = $music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir;
					}
				}
			// use default directory
				else if ($_POST['upload_category'] == '') {
					if (permission_exists('music_on_hold_default_add')) {
						if (!is_dir($music_on_hold_dir."/".$sampling_rate_dir)) {
							@mkdir($music_on_hold_dir."/".$sampling_rate_dir, 0777, true);
						}
						if (is_dir($music_on_hold_dir."/".$sampling_rate_dir)) {
							move_uploaded_file($_FILES['upload_file']['tmp_name'], $music_on_hold_dir."/".$sampling_rate_dir."/".$new_file_name);
							$target_dir = $music_on_hold_dir."/".$sampling_rate_dir;
						}
					}
				}
				else { 
					exit();
				}

			//build and save the XML
				require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
				$moh = new switch_music_on_hold;
				$moh->xml();
				$moh->save();

			//set an upload message
				$save_msg = "Uploaded file to ".$target_dir."/".htmlentities($_FILES['upload_file']['name']);
		}
	}
}

if ($_GET['act'] == "del" && permission_exists('music_on_hold_delete')) {
	if ($_GET['type'] == 'moh') {
		//set the variables
			$sampling_rate_dir = $_GET['sampling_rate'];
			$category_dir = $_GET['category'];
		//default category
			if ($category_dir == "") {
				if (!permission_exists('music_on_hold_default_delete')) {
					echo "access denied";
					exit;
				}
			}
		//other categories
			if ($category_dir != "") {
				$path_mod = $category_dir."/";

				if (count($_SESSION['domains']) > 1) {
					$path_mod = $_SESSION["domain_name"]."/".$path_mod;
				}
			}
		//remove the directory
			unlink($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name']));

		//build and save the XML
			require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
			$moh = new switch_music_on_hold;
			$moh->xml();
			$moh->save();

		//redirect the browser
			header("Location: music_on_hold.php");
			exit;
	}

	if ($_GET['type'] == 'cat') {
		$category_dir = $_GET['category'];
		if (strlen($category_dir) > 0) {
			// adjus the path for multiple domains
				if (count($_SESSION['domains']) > 1) {
					$path_mod = $_SESSION["domain_name"]."/";
				}

			// remove sampling rate directories (if any)
				foreach ($sampling_rate_dirs as $sampling_rate_dir) {
					rmdir($music_on_hold_dir."/".$path_mod.(base64_decode($category_dir))."/".$sampling_rate_dir);
				}

			// remove category directory
				if (rmdir($music_on_hold_dir."/".$path_mod.(base64_decode($category_dir)))) {
					sleep(5); // allow time for the OS to catch up (at least Windows, anyway)
				}
		}

		//build and save the XML
			require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
			$moh = new switch_music_on_hold;
			$moh->xml();
			$moh->save();

		//redirect the browser
			header("Location: music_on_hold.php");
			exit;
	}
}

//include the header
	require_once "includes/header.php";

//show the title and description
	echo "<script language='JavaScript' type='text/javascript' src='".PROJECT_PATH."/includes/javascript/reset_file_input.js'></script>\n";
	echo "<script>\n";
	echo "function EvalSound(soundobj) {\n";
	echo "	var thissound= eval(\"document.\"+soundobj);\n";
	echo "	thissound.Play();\n";
	echo "}\n";
	echo "</script>";

	echo "<br />\n";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<p><span class=\"vexpl\">\n";
	echo "			<strong>".$text['label-moh']."</strong><br><br>\n";
	echo "			".$text['desc-moh']."\n";
	echo "			</span></p>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "\n";
	echo "<br><br>\n";
	echo "\n";

//show the upload form
	if (permission_exists('music_on_hold_add')) {
		echo "<b>".$text['label-upload-moh']."</b>\n";
		echo "<br><br>\n";
		echo "<form action='' method='POST' enctype='multipart/form-data' name='frmUpload' id='frmUpload' onSubmit=''>\n";
		echo "<input name='type' type='hidden' value='moh'>\n";
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		echo "	<tr>\n";
		echo "		<td style='padding-right: 5px;' nowrap>\n";
		echo "			".$text['label-file-path']."<br>\n";
		echo "			<input name='upload_file' type='file' class='button' size='50' id='upload_file'><input type='button' class='button' value='".$text['button-clear']."' onclick=\"reset_file_input('upload_file');\">\n";
		echo "		</td>\n";
		echo "		<td style='padding-right: 5px;' nowrap>".$text['label-sampling']."<br>\n";
		echo "			<select id='upload_sampling_rate' name='upload_sampling_rate' class='formfld' style='width: auto;'>\n";
		echo "				<option value='8'>8 kHz</option>\n";
		echo "				<option value='16'>16 kHz</option>\n";
		echo "				<option value='32'>32 kHz</option>\n";
		echo "				<option value='48'>48 kHz</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
		echo "		<td nowrap>".$text['label-category']."<br>\n";
		echo "			<select id='upload_category' name='upload_category' class='formfld' style='width: auto;' onchange=\"if (this.options[this.selectedIndex].value == '_NEW_CAT_') { this.style.display='none'; document.getElementById('upload_category_new').style.display=''; document.getElementById('upload_category_return').style.display=''; document.getElementById('upload_category_new').focus(); }\">\n";
		if (permission_exists('music_on_hold_default_add')) {
			echo "				<option value='' style='font-style: italic;'>".$text['opt-default']."</option>\n";
		}

		if (count($_SESSION['domains']) > 1) {
			$music_on_hold_category_parent_dir = $music_on_hold_dir."/".$_SESSION['domain_name'];
		}
		else {
			$music_on_hold_category_parent_dir = $music_on_hold_dir;
		}

		if ($handle = opendir($music_on_hold_category_parent_dir)) {
			while (false !== ($directory = readdir($handle))) {
				if (
					$directory != "." &&
					$directory != ".." &&
					$directory != "8000" &&
					$directory != "16000" &&
					$directory != "32000" &&
					$directory != "48000" &&
					is_dir($music_on_hold_category_parent_dir."/".$directory)
					) {
					echo "<option value='".$directory."'>".(str_replace('_', ' ', $directory))."</option>\n";
					$category_dirs[] = $directory; // array used to output category directory contents below
				}
			}
			closedir($handle);
		}

		echo "				<option value='_NEW_CAT_' style='font-style: italic;'>".$text['opt-new']."</option>\n";
		echo "			</select>\n";
		echo "			<input class='formfld' style='width: 150px; display: none;' type='text' name='upload_category_new' id='upload_category_new' maxlength='255' value=''>";
		echo "		</td>\n";
		echo "		<td>&nbsp;<br>\n";
		echo "			<input id='upload_category_return' type='button' class='button' style='display: none;' value='<' onclick=\"this.style.display='none'; document.getElementById('upload_category_new').style.display='none'; document.getElementById('upload_category_new').value=''; document.getElementById('upload_category').style.display=''; document.getElementById('upload_category').selectedIndex = 0;\" title='".$text['message-click-select']."'>";
		echo "		</td>\n";
		echo "		<td style='padding-left: 5px;'>&nbsp;<br>\n";
		echo "			<input name='submit' type='submit' class='btn' id='upload' value='".$text['button-upload']."'>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
		echo "<br><br>\n";
	}

//set the row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the default category
	if (permission_exists('music_on_hold_default_view')) {
		echo "<b><i>".$text['label-default']."</i></b>\n";
		if (count($_SESSION['domains']) > 1) {
			echo "&nbsp;&nbsp;- ".$text['message-available-to-all']."\n";
		}

		echo "<br><br>\n";
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin-bottom: 3px;\">\n";
		echo "	<tr>\n";
		echo "		<th width=\"30%\" class=\"listhdrr\">".$text['label-download']."</th>\n";
		echo "		<th width=\"30%\" class=\"listhdrr\">".$text['label-play']."</th>\n";
		echo "		<th width=\"30%\" class=\"listhdr\">".$text['label-uploaded']."</th>\n";
		echo "		<th width=\"10%\" class=\"listhdr\" nowrap=\"nowrap\">".$text['label-file-size']."</th>\n";
		echo "		<th width=\"10%\" class=\"listhdr\" nowrap=\"nowrap\">".$text['label-sampling']."</th>\n";
		echo "		<td width='22px' align=\"center\"></td>\n";
		echo "	</tr>";

		foreach ($sampling_rate_dirs as $sampling_rate_dir) {
			if ($handle = opendir($music_on_hold_dir."/".$sampling_rate_dir)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != ".." && is_file($music_on_hold_dir."/".$sampling_rate_dir."/".$file)) {
						$file_size = filesize($music_on_hold_dir."/".$sampling_rate_dir."/".$file);
						$file_size = byte_convert($file_size);

						echo "<tr>\n";
						echo "	<td class='".$row_style[$c]."'><a href=\"music_on_hold.php?a=download&sampling_rate=".$sampling_rate_dir."&type=moh&t=bin&file_name=".base64_encode($file)."\">".$file."</a></td>\n";
						echo "	<td class='".$row_style[$c]."'>\n";
						echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('music_on_hold_play.php?a=download&sampling_rate=".$sampling_rate_dir."&type=moh&file_name=".base64_encode($file)."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
						$tmp_file_array = explode("\.",$file);
						echo "		".$tmp_file_array[0];
						echo "		</a>";
						echo "	</td>\n";
						echo "	<td class='".$row_style[$c]."'>".date ("F d Y H:i:s", filemtime($music_on_hold_dir."/".$sampling_rate_dir."/".$file))."</td>\n";
						echo "	<td class='".$row_style[$c]."'>".$file_size."</td>\n";
						echo "	<td class='".$row_style[$c]."'>".($sampling_rate_dir / 1000)." kHz</td>\n";
						echo "	<td align=\"center\" width='22' nowrap=\"nowrap\" class=\"list\">\n";
						if (permission_exists('music_on_hold_default_delete')) {
							echo "	<a href=\"music_on_hold.php?type=moh&act=del&sampling_rate=".$sampling_rate_dir."&file_name=".base64_encode($file)."\" onclick=\"return confirm('Do you really want to delete this file?')\">$v_link_label_delete</a>\n";
						}
						echo "	</td>\n";
						echo "</tr>\n";
						$c = ($c==0) ? 1 : 0;
					}
				}
				closedir($handle);
			}
		}
		echo "</table>\n";
	}

	if ($v_path_show) {
		echo "<div style='font-size: 10px; text-align: right; margin-right: 25px;'><b>".$text['label-location'].":</b> ".$music_on_hold_dir."</div>\n";
	}
	echo "<br><br>\n";

//show additional categories
	foreach ($category_dirs as $category_number => $category_dir) {
		$c = 0;

		echo "<b>".(str_replace('_', ' ', $category_dir))."</b>\n";
		echo "<br><br>\n";
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin-bottom: 3px;\">\n";
		echo "	<tr>\n";
		echo "		<th width=\"30%\" class=\"listhdrr\">".$text['label-download']."</th>\n";
		echo "		<th width=\"30%\" class=\"listhdrr\">".$text['label-play']."</th>\n";
		echo "		<th width=\"30%\" class=\"listhdr\">".$text['label-uploaded']."</th>\n";
		echo "		<th width=\"10%\" class=\"listhdr\" nowrap=\"nowrap\">".$text['label-file-size']."</th>\n";
		echo "		<th width=\"10%\" class=\"listhdr\" nowrap=\"nowrap\">".$text['label-sampling']."</th>\n";
		echo "		<td width='22px' align=\"center\" style=\"padding: 2px;\"><span id='category_".$category_number."_delete_icon'></span></td>\n";
		echo "	</tr>";

		$moh_found = false;

		foreach ($sampling_rate_dirs as $sampling_rate_dir) {
			if ($handle = opendir($music_on_hold_category_parent_dir."/".$category_dir."/".$sampling_rate_dir)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != ".." && is_file($music_on_hold_category_parent_dir."/".$category_dir."/".$sampling_rate_dir."/".$file)) {

						$file_size = filesize($music_on_hold_category_parent_dir."/".$category_dir."/".$sampling_rate_dir."/".$file);
						$file_size = byte_convert($file_size);

						echo "<tr>\n";
						echo "	<td class='".$row_style[$c]."'><a href=\"music_on_hold.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=moh&t=bin&file_name=".base64_encode($file)."\">".$file."</a></td>\n";
						echo "	<td class='".$row_style[$c]."'>\n";
						echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('music_on_hold_play.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=moh&file_name=".base64_encode($file)."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
						$tmp_file_array = explode("\.",$file);
						echo "		".$tmp_file_array[0];
						echo "		</a>";
						echo "	</td>\n";
						echo "	<td class='".$row_style[$c]."'>".date ("F d Y H:i:s", filemtime($music_on_hold_category_parent_dir."/".$category_dir."/".$sampling_rate_dir."/".$file))."</td>\n";
						echo "	<td class='".$row_style[$c]."'>".$file_size."</td>\n";
						echo "	<td class='".$row_style[$c]."'>".($sampling_rate_dir / 1000)." kHz</td>\n";
						echo "	<td align=\"center\" width='22' nowrap=\"nowrap\" class=\"list\">\n";
						if (permission_exists('music_on_hold_delete')) {
							echo "	<a href=\"music_on_hold.php?type=moh&act=del&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&file_name=".base64_encode($file)."\" onclick=\"return confirm('".$text['message-delete']."')\">$v_link_label_delete</a>\n";
						}
						echo "	</td>\n";
						echo "</tr>\n";
						$c = ($c==0) ? 1 : 0;

						$moh_found = true;
					}
				}
				closedir($handle);
			}
		}

		if (!$moh_found) {
			echo "<tr>\n";
			echo "	<td colspan='5' align='left' class='".$row_style[$c]."'>\n";
			echo "		".$text['message-nofiles']."";
			echo "		<script>document.getElementById('category_".$category_number."_delete_icon').innerHTML = \"<a href='music_on_hold.php?type=cat&act=del&category=".base64_encode($category_dir)."' title='".$text['label-delete-category']."'>".$v_link_label_delete."</a>\";</script>\n";
			echo "	</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		if ($v_path_show) {
			echo "<div style='font-size: 10px; text-align: right; margin-right: 25px;'><b>Location:</b> ".$music_on_hold_category_parent_dir."/".$category_dir."</div>\n";
		}
		echo "<br><br>\n";
	}

//include the footer
	require_once "includes/footer.php";

?>