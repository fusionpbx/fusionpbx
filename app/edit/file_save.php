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

//compare the tokens
	$key_name = '/app/edit/'.$_POST['mode'];
	$hash = hash_hmac('sha256', $key_name, $_SESSION['keys'][$key_name]);
	if (!hash_equals($hash, $_POST['token'])) {
		echo "access denied";
		exit;
	}

//get the directory
	if (!isset($_SESSION)) { session_start(); }
	switch ($_SESSION["app"]["edit"]["dir"]) {
		case 'scripts':
			$edit_directory = $_SESSION['switch']['scripts']['dir'];
			break;
		case 'php':
			$edit_directory = $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH;
			break;
		case 'grammar':
			$edit_directory = $_SESSION['switch']['grammar']['dir'];
			break;
		case 'provision':
			switch (PHP_OS) {
				case "Linux":
					if (file_exists('/etc/fusionpbx/resources/templates/provision')) {
						$edit_directory = '/etc/fusionpbx/resources/templates/provision';
					}
					else {
						$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					}
					break;
				case "FreeBSD":
					if (file_exists('/usr/local/etc/fusionpbx/resources/templates/provision')) {
						$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					}
					else {
						$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					}
					break;
				case "NetBSD":
					$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					break;
				case "OpenBSD":
					$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					break;
				default:
					$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
			}
			break;
		case 'xml':
			$edit_directory = $_SESSION['switch']['conf']['dir'];
			break;
	}
	if (!isset($edit_directory)) {
		foreach ($_SESSION['editor']['path'] as $path) {
			if ($_SESSION["app"]["edit"]["dir"] == $path) {
				$edit_directory = $path;
				break;
			}
		}
	}

//set the file variable
	$file_path = $_POST["filepath"];

//remove attempts to change the directory
	$file_path = str_replace('..', '', $file_path);
	$file_path = str_replace ("\\", "/", $file_path);

//break the path into an array
	$path_array = pathinfo($file_path);
	$path_prefix = substr($path_array['dirname'], 0, strlen($edit_directory));

//validate the path
	if (realpath($path_prefix) == realpath($edit_directory)) {
		if ($file_path != '') {
			try {
				//save file content
					$file_path = realpath($file_path);
					$file_path = str_replace ('//', '/', $file_path);
					$file_path = str_replace ("\\", "/", $file_path);
					if (file_exists($file_path)) {
						$handle = fopen($file_path, 'wb');
						if (!$handle) {
							throw new Exception('Write Failed - Check File Owner & Permissions');
						}
						fwrite($handle, $_POST["content"]);
						fclose($handle);
					}

				//set the reload_xml value to true
					$_SESSION["reload_xml"] = true;

				//alert user of success
					echo "Changes Saved";
			}
			catch(Exception $e) {
				//alert error
				echo $e->getMessage();
			}
		}
	}

?>
