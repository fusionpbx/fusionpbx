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
	Portions created by the Initial Developer are Copyright (C) 2008-2010
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//proccess this only one time
if ($domains_processed == 1) {

	//ensure that the language code is set
		$sql = "select count(*) as num_rows from v_default_settings ";
		$sql .= "where default_setting_category = 'domain' ";
		$sql .= "and default_setting_subcategory = 'language' ";
		$sql .= "and default_setting_name = 'code' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
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
				$sql .= "'".uuid()."', ";
				$sql .= "'domain', ";
				$sql .= "'language', ";
				$sql .= "'code', ";
				$sql .= "'en-us', ";
				$sql .= "'true', ";
				$sql .= "'' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);
			}
			unset($prep_statement, $row);
		}

	//ensure that the default password length and strength are set
		$sql = "select count(*) as num_rows from v_default_settings ";
		$sql .= "where ( ";
		$sql .= "default_setting_category = 'security' ";
		$sql .= "and default_setting_subcategory = 'password_length' ";
		$sql .= "and default_setting_name = 'var' ";
		$sql .= ") or ( ";
		$sql .= "default_setting_category = 'security' ";
		$sql .= "and default_setting_subcategory = 'password_strength' ";
		$sql .= "and default_setting_name = 'var' ";
		$sql .= ") ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				$sql = "insert into v_default_settings ";
				$sql .= "( ";
				$sql .= "default_setting_uuid, ";
				$sql .= "default_setting_category, ";
				$sql .= "default_setting_subcategory, ";
				$sql .= "default_setting_name, ";
				$sql .= "default_setting_value, ";
				$sql .= "default_setting_enabled, ";
				$sql .= "default_setting_description ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "( ";
				$sql .= "'".uuid()."', ";
				$sql .= "'security', ";
				$sql .= "'password_length', ";
				$sql .= "'var', ";
				$sql .= "'10', ";
				$sql .= "'true', ";
				$sql .= "'Sets the default length for system generated passwords.' ";
				$sql .= "), ( ";
				$sql .= "'".uuid()."', ";
				$sql .= "'security', ";
				$sql .= "'password_strength', ";
				$sql .= "'var', ";
				$sql .= "'4', ";
				$sql .= "'true', ";
				$sql .= "'Sets the default strength for system generated passwords.  Valid Options: 1 - Numeric Only, 2 - Include Lower Apha, 3 - Include Upper Alpha, 4 - Include Special Characters' ";
				$sql .= ") ";
				$db->exec(check_sql($sql));
				unset($sql);
			}
			unset($prep_statement, $row);
		}

//set the sip_profiles directory for older installs
	if (isset($_SESSION['switch']['gateways']['dir'])) {
		$orm = new orm;
		$orm->name('default_settings');
		$orm->uuid($_SESSION['switch']['gateways']['uuid']);
		$array['default_setting_category'] = 'switch';
		$array['default_setting_subcategory'] = 'sip_profiles';
		$array['default_setting_name'] = 'dir';
		//$array['default_setting_value'] = '';
		//$array['default_setting_enabled'] = 'true';
		$orm->save($array);
		unset($array);
	}
}

?>