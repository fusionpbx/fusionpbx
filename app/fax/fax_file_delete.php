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
	Portions created by the Initial Developer are Copyright (C) 2015 - 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('fax_file_delete')) {
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

//get the id
	$fax_file_uuid = $_REQUEST["id"];

//validate the id
	if (is_uuid($fax_file_uuid)) {
		//get the fax file data
			$sql = "select * from v_fax_files ";
			$sql .= "where fax_file_uuid = :fax_file_uuid ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$parameters['fax_file_uuid'] = $fax_file_uuid;
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$fax_uuid = $row["fax_uuid"];
				$fax_mode = $row["fax_mode"];
				$fax_file_path = $row["fax_file_path"];
				$fax_file_type = $row["fax_file_type"];
			}
			unset($sql, $parameters, $row);

		//set the type
			if ($fax_mode == 'rx') { $type = 'inbox'; }
			if ($fax_mode == 'tx') { $type = 'sent'; }

		//delete fax file(s)
			if (substr_count($fax_file_path, '/temp/') > 0) {
				$fax_file_path = str_replace('/temp/', '/'.$type.'/', $fax_file_path);
			}
			if (file_exists($fax_file_path)) {
				@unlink($fax_file_path);
			}
			if ($fax_file_type == 'tif') {
				$fax_file_path = str_replace('.tif', '.pdf', $fax_file_path);
				if (file_exists($fax_file_path)) {
					@unlink($fax_file_path);
				}
			}
			else if ($fax_file_type == 'pdf') {
				$fax_file_path = str_replace('.pdf', '.tif', $fax_file_path);
				if (file_exists($fax_file_path)) {
					@unlink($fax_file_path);
				}
			}

		//delete fax file record
			$array['fax_files'][0]['fax_file_uuid'] = $fax_file_uuid;
			$array['fax_files'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

			$database = new database;
			$database->app_name = 'fax';
			$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
			$database->delete($array);
			unset($array);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header('Location: fax_files.php?id='.$fax_uuid.'&box='.$type);
	exit;

?>
