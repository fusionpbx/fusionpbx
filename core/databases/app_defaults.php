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

//proccess this only one time
if ($domains_processed == 1) {
	//set the database driver
		$sql = "select * from v_databases ";
		$sql .= "where database_driver is null ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$database_uuid = $row["database_uuid"];
			$database_type = $row["database_type"];
			$database_type_array = explode(":",  $database_type);
			if ($database_type_array[0] == "odbc") {
				$database_driver = $database_type_array[1];
			}
			else {
				$database_driver = $database_type_array[0];
			}
			$sql = "update v_databases set ";
			$sql .= "database_driver = '$database_driver' ";
			$sql .= "where database_uuid = '$database_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	if (strlen($_SESSION['switch']['scripts']['dir']) > 0) {
		//get the odbc information
			$sql = "select count(*) as num_rows from v_databases ";
			$sql .= "where database_driver = 'odbc' ";
			if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] > 0) {
					$odbc_num_rows = $row['num_rows'];

					$sql = "select * from v_databases ";
					$sql .= "where database_driver = 'odbc' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						$dsn_name = $row["database_name"];
						$dsn_username = $row["database_username"];
						$dsn_password = $row["database_password"];
						break; //limit to 1 row
					}
					unset ($prep_statement);
				}
				else {
					$odbc_num_rows = '0';
				}
			}

		//config.lua
			$fout = fopen($_SESSION['switch']['scripts']['dir']."/resources/config.lua","w");
			$tmp = "\n";
			$tmp .= "--switch directories\n";
			if (strlen($_SESSION['switch']['sounds']['dir']) > 0) {
				$tmp .= "	sounds_dir = \"".$_SESSION['switch']['sounds']['dir']."\";\n";
			}
			if (strlen($_SESSION['switch']['recordings']['dir']) > 0) {
				$tmp .= "	recordings_dir = \"".$_SESSION['switch']['recordings']['dir']."\";\n";
			}
			$tmp .= "\n";
			$tmp .= "--database connection info\n";
			if (strlen($db_type) > 0) {
				$tmp .= "	db_type = \"".$db_type."\";\n";
			}
			if (strlen($db_name) > 0) {
				$tmp .= "	db_name = \"".$db_name."\";\n";
			}
			if (strlen($db_path) > 0) {
				$tmp .= "	db_path = \"".$db_path."\";\n";
			}
			if (strlen($dsn_name) > 0) {
				$tmp .= "	dsn_name = \"".$dsn_name."\";\n";
			}
			if (strlen($dsn_username) > 0) {
				$tmp .= "	dsn_username = \"".$dsn_username."\";\n";
			}
			if (strlen($dsn_password) > 0) {
				$tmp .= "	dsn_password = \"".$dsn_password."\";\n";
			}
			$tmp .= "\n";
			$tmp .= "--additional info\n";
			$tmp .= "	domain_count = \"".count($_SESSION["domains"])."\";\n";
			$tmp .= "	tmp_dir = \"".$tmp_dir."\";\n";
			fwrite($fout, $tmp);
			unset($tmp);
			fclose($fout);

		//config.js
			$fout = fopen($_SESSION['switch']['scripts']['dir']."/resources/config.js","w");
			$tmp = "\n";
			$tmp .= "//switch directories\n";
			$tmp .= "	var admin_pin = \"".$row["admin_pin"]."\";\n";
			$tmp .= "	var sounds_dir = \"".$_SESSION['switch']['sounds']['dir']."\";\n";
			$tmp .= "	var recordings_dir = \"".$_SESSION['switch']['recordings']['dir']."\";\n";
			$tmp .= "\n";
			$tmp = "//database connection info\n";
			if (strlen($db_type) > 0) {
				$tmp .= "	var db_type = \"".$db_type."\";\n";
			}
			if (strlen($db_name) > 0) {
				$tmp .= "	var db_name = \"".$db_name."\";\n";
			}
			if (strlen($db_path) > 0) {
				$tmp .= "	var db_path = \"".$db_path."\";\n";
			}
			if (strlen($dsn_name) > 0) {
				$tmp .= "	var dsn_name = \"".$dsn_name."\";\n";
			}
			if (strlen($dsn_username) > 0) {
				$tmp .= "	var dsn_username = \"".$dsn_username."\";\n";
			}
			if (strlen($dsn_password) > 0) {
				$tmp .= "	var dsn_password = \"".$dsn_password."\";\n";
			}
			$tmp .= "\n";
			$tmp .= "//additional info\n";
			$tmp .= "	var domain_count = \"".count($_SESSION["domains"])."\";\n";
			$tmp .= "	var tmp_dir = \"".$tmp_dir."\";\n";
			fwrite($fout, $tmp);
			unset($tmp);
			fclose($fout);
	}
}
?>