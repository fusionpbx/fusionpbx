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

if ($domains_processed == 1) {

	//ensure the login message is set, if new message exists
		$sql = "select count(*) as num_rows from v_default_settings ";
		$sql .= "where default_setting_category = 'login' ";
		$sql .= "and default_setting_subcategory = 'message' ";
		$sql .= "and default_setting_name = 'text' ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows == 0) {

			// insert message
			$sql = "insert into v_default_settings ";
			$sql .= "(";
			$sql .= "default_setting_uuid, ";
			$sql .= "default_setting_category, ";
			$sql .= "default_setting_subcategory, ";
			$sql .= "default_setting_name, ";
			$sql .= "default_setting_value, ";
			$sql .= "default_setting_enabled, ";
			$sql .= "default_setting_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'e2bff94b-2c68-45ee-9141-d4cdb437c644', ";
			$sql .= "'login', ";
			$sql .= "'message', ";
			$sql .= "'text', ";
			$sql .= ":default_setting_value, ";
			$sql .= "'true', ";
			$sql .= "'' ";
			$sql .= ")";
			$parameters['default_setting_value'] = $text['login-message_text'];
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);

		}
		else {

			// get current message value
			$sql = "select default_setting_uuid, default_setting_value ";
			$sql .= "from v_default_settings ";
			$sql .= "where default_setting_category = 'login' ";
			$sql .= "and default_setting_subcategory = 'message' ";
			$sql .= "and default_setting_name = 'text' ";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			if (is_array($result) && count($result) > 0) {
				foreach($result as $row) {
					$current_default_setting_uuid = $row["default_setting_uuid"];
					$current_default_setting_value = $row["default_setting_value"];
					break;
				}

				// compare to message in language file, update and enable if different
				$new_default_setting_value = str_replace("''", "'", $text['login-message_text']);
				if ($current_default_setting_value != $new_default_setting_value) {
					$sql = "update v_default_settings set ";
					$sql .= "default_setting_value = :default_setting_value, ";
					$sql .= "default_setting_enabled = 'true' ";
					$sql .= "where default_setting_uuid = :default_setting_uuid ";
					$parameters['default_setting_value'] = $text['login-message_text'];
					$parameters['default_setting_uuid'] = $current_default_setting_uuid;
					$database = new database;
					$database->execute($sql, $parameters);
					unset($sql, $parameters);
				}
			}
			unset($sql, $result, $row);
		}

}

?>
