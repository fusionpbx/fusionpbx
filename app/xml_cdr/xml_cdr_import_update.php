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
*/

//check the permission
	if(defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/mod\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		require_once "resources/require.php";
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		$display_type = 'text'; //html, text
	}
	else {
		echo "access denied";
		exit;
	}

//determine where the xml cdr will be archived
	$sql = "select * from v_vars ";
	$sql .= "where domain_uuid  = '1' ";
	$sql .= "and var_name = 'xml_cdr_archive' ";
	$row = $db->query($sql)->fetch();
	$var_value = trim($row["var_value"]);
	switch ($var_value) {
	case "dir":
			$xml_cdr_archive = 'dir';
			break;
	case "db":
			$xml_cdr_archive = 'db';
			break;
	case "none":
			$xml_cdr_archive = 'none';
			break;
	default:
			$xml_cdr_archive = 'dir';
			break;
	}

//get the list of installed apps from the core and mod directories
	if ($xml_cdr_archive == "db") {
		//get the xml cdr list
			$sql = "select xml_cdr, uuid from v_xml_cdr ";
			$sql .= "where waitsec is null ";
			//$sql .= "limit 5000 ";
		//start the transaction
			$db->beginTransaction();
		//loop through the results
			$x = 0;
			foreach ($db->query($sql,PDO::FETCH_ASSOC) as $row) {
				//get the values from the db
					$uuid = $row['uuid'];
					$xml_string = $row['xml_cdr'];
				//save each set of records and begin a new transaction
					if ($x > 5000) {
						//save the transaction
							$db->commit();
						//start the transaction
							$db->beginTransaction();
						//reset the count
							$x = 0;
					}
				//parse the xml to get the call detail record info
					try {
						$xml = simplexml_load_string($xml_string);
					}
					catch(Exception $e) {
						echo $e->getMessage();
					}
				//get the values from the xml and set at variables
					$uuid = urldecode($xml->variables->uuid);
					$waitsec = urldecode($xml->variables->waitsec);
				//update the database
					if (strlen($waitsec) > 0) {
						$sql = "update v_xml_cdr ";
						$sql .= "set waitsec = '$waitsec' ";
						$sql .= "where uuid = '$uuid' ";
						echo $sql."\n";
						$db->exec($sql);
						$x++;
					}
			}
		//save the transaction
			$db->commit();
		//echo finished
			echo "completed\n";
	}
	if ($xml_cdr_archive == "dir") {
		$xml_cdr_list = glob($_SESSION['switch']['log']['dir']."/xml_cdr/archive/*/*/*/*.xml");
		echo "count: ".count($xml_cdr_list)."\n";
		//print_r($xml_cdr_list);
		$x = 0;
		//start the transaction
			$db->beginTransaction();
		//loop through the xml cdr records
			foreach ($xml_cdr_list as $xml_cdr) {
				//save each set of records and begin a new transaction
					if ($x > 5000) {
						//save the transaction
							$db->commit();
						//start the transaction
							$db->beginTransaction();
						//reset the count
							$x = 0;
					}
				//get the xml cdr string
					$xml_string = file_get_contents($xml_cdr);
				//parse the xml to get the call detail record info
					try {
						$xml = simplexml_load_string($xml_string);
					}
					catch(Exception $e) {
						echo $e->getMessage();
					}
				//get the values from the xml and set at variables
					$uuid = urldecode($xml->variables->uuid);
					$waitsec = urldecode($xml->variables->waitsec);
				//update the database
					//if ($num_rows == "0" && strlen($waitsec) > 0) {
					if (strlen($waitsec) > 0) {
						$sql = "";
						$sql .= "update v_xml_cdr ";
						$sql .= "set waitsec = '$waitsec' ";
						$sql .= "where uuid = '$uuid' ";
						echo $sql."\n";
						$db->exec($sql);
						$x++;
					}
			}
		//save the transaction
			$db->commit();
		//echo finished
			echo "completed\n";
	}
?>