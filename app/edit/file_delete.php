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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//disable this feature
	exit;

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('edit_save')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the variabls
	$folder = $_REQUEST["folder"];
	$folder = str_replace ("\\", "/", $folder);
	$folder = realpath($folder);
	$file = $_REQUEST["file"];

//delete the file or show the html form
	if (strlen($folder) > 0 && strlen($file) > 0 && isset($_POST['token'])) {
		//compare the tokens
		$key_name = '/app/edit/file_delete';
		$hash = hash_hmac('sha256', $key_name, $_SESSION['keys'][$key_name]);
		if (!hash_equals($hash, $_POST['token'])) {
			echo "access denied";
			exit;
		}

		//delete the file
		unlink($folder.'/'.$file);

		//redirect the browser
		header("Location: file_options.php");
	}
	else {
		//create the token
		$key_name = '/app/edit/file_delete';
		$_SESSION['keys'][$key_name] = bin2hex(random_bytes(32));
		$_SESSION['token'] = hash_hmac('sha256', $key_name, $_SESSION['keys'][$key_name]);

		//display form
		require_once "header.php";
		echo "<br>";
		echo "<div align='left'>";
		echo "	<form method='POST' action=''>";
		echo "		<table>";
		echo "			<tr>";
		echo "				<td>".$text['label-path']."</td>";
		echo "			</tr>";
		echo "			<tr>";
		echo "				<td>".escape($folder)."</td>";
		echo "			</tr>";
		echo "		</table>";
		echo "		<br />";
		echo "		<table>";
		echo "			<tr>";
		echo "				<td>".$text['label-file-name']."</td>";
		echo "			</tr>";
		echo "			<tr>";
		echo "				<td><input type='text' name='file' value='".escape($file)."'></td>";
		echo "			</tr>";
		echo "			<tr>";
		echo "				<td colspan='1' align='right'>";
		echo "					<input type='hidden' name='folder' value='".escape($folder)."'>";
		echo "					<input type='hidden' name='token' id='token' value='". $_SESSION['token']. "'>";
		echo "					<input type='submit' value='".$text['button-del-file']."'>";
		echo "				</td>";
		echo "			</tr>";
		echo "		</table>";
		echo "	</form>";
		echo "</div>";

		//include the footer
		require_once "footer.php";
	}
?>
