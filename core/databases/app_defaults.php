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
		unset($prep_statement, $result);
}
?>