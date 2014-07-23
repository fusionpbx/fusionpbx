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

//move the smtp settings from v_settings to the v_default_settings table
if (count($_SESSION['email']) == 0 && $domains_processed == 1) {
	//get the data from the database
		$sql = "select * from v_settings ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_NAMED);
		unset($prep_statement);
	//set the variable
		$smtp[]['smtp_host'] = check_str($row['smtp_host']);
		$smtp[]['smtp_secure'] = check_str($row['smtp_secure']);
		$smtp[]['smtp_auth'] = check_str($row['smtp_auth']);
		$smtp[]['smtp_username'] = check_str($row['smtp_username']);
		$smtp[]['smtp_password'] = check_str($row['smtp_password']);
		$smtp[]['smtp_from'] = check_str($row['smtp_from']);
		$smtp[]['smtp_from_name'] = check_str($row['smtp_from_name']);
	//build the sql inserts
		foreach ($smtp as $row) {
			foreach ($row as $key => $value) {
				//add the provision variable to the default settings table
					$sql = "insert into v_default_settings ";
					$sql .= "(";
					$sql .= "default_setting_uuid, ";
					$sql .= "default_setting_category, ";
					$sql .= "default_setting_subcategory, ";
					$sql .= "default_setting_name, ";
					$sql .= "default_setting_value, ";
					$sql .= "default_setting_enabled, ";
					$sql .= "default_setting_description ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'email', ";
					$sql .= "'".$key."', ";
					$sql .= "'var', ";
					$sql .= "'".check_str($value)."', ";
					$sql .= "'true', ";
					$sql .= "'' ";
					$sql .= ")";
					//echo $sql."\n";
					$db->exec(check_sql($sql));
					unset($sql);
			}
		}
}

?>