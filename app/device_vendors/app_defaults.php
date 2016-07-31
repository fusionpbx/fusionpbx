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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

	if ($domains_processed == 1) {

		//add the music_on_hold list to the database
		$sql = "select count(*) as num_rows from v_device_vendors; ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {

				//get the vendor array
					require_once $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/app/device_vendors/app_config.php';

				//process the array
					foreach ($vendors as $vendor) {

						//insert the data into the database
							$device_vendor_uuid = uuid();
							$sql = "insert into v_device_vendors ";
							$sql .= "(";
							$sql .= "device_vendor_uuid, ";
							$sql .= "name, ";
							$sql .= "enabled ";
							$sql .= ") ";
							$sql .= "values ";
							$sql .= "( ";
							$sql .= "'".$device_vendor_uuid."', ";
							$sql .= "'".$vendor['name']."', ";
							$sql .= "'true' ";
							$sql .= ");";
							//echo $sql."\n";
							$db->exec(check_sql($sql));
							unset($sql);

						//add the vendor functions
							foreach ($vendor['functions'] as $function) {

								//add the coference profile params
									$device_vendor_function_uuid = uuid();
									$sql = "insert into v_device_vendor_functions ";
									$sql .= "(";
									$sql .= "device_vendor_uuid, ";
									$sql .= "device_vendor_function_uuid, ";
									$sql .= "name, ";
									$sql .= "value, ";
									$sql .= "enabled, ";
									$sql .= "description ";
									$sql .= ") ";
									$sql .= "values ";
									$sql .= "( ";
									$sql .= "'".$device_vendor_uuid."', ";
									$sql .= "'".$device_vendor_function_uuid."', ";
									$sql .= "'".$function['name']."', ";
									$sql .= "'".$function['value']."', ";
									$sql .= "'true', ";
									$sql .= "'".$function['description']."' ";
									$sql .= ");";
									//echo $sql."\n";
									$db->exec(check_sql($sql));
									unset($sql);
							}

					}
			} //if num_rows
		} //if prep_statement

	}

?>