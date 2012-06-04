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
/*
if ($db_type == "sqlite") {
	try {
		if (strlen($dbfilename) == 0) {
			//if (strlen($_SERVER["SERVER_NAME"]) == 0) { $_SERVER["SERVER_NAME"] = "http://localhost"; }
			$server_name = $_SERVER["SERVER_NAME"];
			$server_name = str_replace ("www.", "", $server_name);
			$server_name = str_replace ("example.net", "example.com", $server_name);
			//$server_name = str_replace (".", "_", $server_name);
			$dbfilenameshort = $server_name;
			$dbfilename = $server_name.'.db';
		}
		else {
			$dbfilenameshort = $dbfilename;
		}
		$db_file_path = str_replace("\\", "/", $db_file_path);


		if (file_exists($db_file_path.'/'.$dbfilename)) {
			//echo "main file exists<br>";
		}
		else { //file doese not exist

			//--- begin: create the sqlite db file -----------------------------------------
				$filename = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/includes/install/sql/sqlite.sql';
				$file_contents = file_get_contents($filename);
				//echo "<pre>\n";
				//echo $file_contents;
				//echo "</pre>\n";
				//exit;

				//replace \r\n with \n then explode on \n
					$file_contents = str_replace("\r\n", "\n", $file_contents);

				//loop line by line through all the lines of sql code
					$stringarray = explode("\n", $file_contents);
					$x = 0;
					foreach($stringarray as $sql) {
						//create the call detail records database
						if (strtolower(substr($sql, 0, 18)) == "create table v_cdr") {
							try {
								$dbcdr = new PDO('sqlite:'.$db_file_path.'/'.$dbfilenameshort.'.cdr.db'); //sqlite 3
								$dbcdr->query($sql);
								unset($dbcdr);
							}
							catch (PDOException $error) {
								print "error: " . $error->getMessage() . "<br/>";
								die();
							}
						}
						$x++;
					}
					unset ($file_contents, $sql);
			//--- end: create the sqlite db -----------------------------------------

			if (is_writable($db_file_path.'/'.$dbfilename)) { //is writable
				//use database in current location
			}
			else { //not writable
				echo "The database ".$db_file_path."/".$dbfilename." is not writeable2.";
				exit;
			}
		}

		unset($db);
		//$db = new PDO('sqlite::memory:'); //sqlite 3
		$db = new PDO('sqlite:'.$db_file_path.'/'.$dbfilenameshort.'.cdr.db'); //sqlite 3
	}
	catch (PDOException $error) {
		print "error: " . $error->getMessage() . "<br/>";
		die();
	}
}
*/
?>