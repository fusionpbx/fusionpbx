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
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('edit_view')) {
		//access granted
	}
	else {
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
	$file_name = $_POST["file"];

//remove attempts to change the directory
	$file_name = str_replace('..', '', $file_name);
	$file_name = str_replace ("\\", "/", $file_name);

//break the path into an array
	$path_array = pathinfo($file_name);
	$path_prefix = substr($path_array['dirname'], 0, strlen($edit_directory));

//validate the path
	if (realpath($path_prefix) == realpath($edit_directory)) {

		//get the contents of the file
		$handle = fopen($file_name, "r");
		if ($handle) {
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				echo $buffer;
			}
			fclose($handle);
		}

	}

?>
