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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
if ($domains_processed == 1) {

	//define array of settings
		$array[$x]['default_setting_category'] = 'follow_me';
		$array[$x]['default_setting_subcategory'] = 'max_destinations';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '5';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_order'] = '0';
		$array[$x]['default_setting_description'] = 'Set the maximum number of Follow Me Destinations.';
		$x++;
		$array[$x]['default_setting_category'] = 'follow_me';
		$array[$x]['default_setting_subcategory'] = 'timeout';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '30';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_order'] = '0';
		$array[$x]['default_setting_description'] = 'Set the default Follow Me Timeout value.';
		$x++;

	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'follow_me' ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);

	//find the missing default settings
		$i = 0;
		foreach ($array as $setting) {
			$found = false;
			$missing[$i] = $setting;
			foreach ($default_settings as $row) {
				if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory'])) {
					$found = true;
					//remove items from the array that were found
					unset($missing[$i]);
				}
			}
			$i++;
		}

	//get the missing count
		$i = 0;
		foreach ($missing as $row) { $i++; }
		$missing_count = $i;

	//add the missing default settings
		$sql = "insert into v_default_settings (";
		$sql .= "default_setting_uuid, ";
		$sql .= "default_setting_category, ";
		$sql .= "default_setting_subcategory, ";
		$sql .= "default_setting_name, ";
		$sql .= "default_setting_value, ";
		$sql .= "default_setting_enabled, ";
		$sql .= "default_setting_description ";
		$sql .= ") values \n";
		$i = 1;
		foreach ($missing as $row) {
			$sql .= "(";
			$sql .= "'".uuid()."', ";
			$sql .= "'".check_str($row['default_setting_category'])."', ";
			$sql .= "'".check_str($row['default_setting_subcategory'])."', ";
			$sql .= "'".check_str($row['default_setting_name'])."', ";
			$sql .= "'".check_str($row['default_setting_value'])."', ";
			$sql .= "'".check_str($row['default_setting_enabled'])."', ";
			$sql .= "'".check_str($row['default_setting_description'])."' ";
			$sql .= ")";
			if ($missing_count != $i) {
				$sql .= ",\n";
			}
			$i++;
		}
		$db->exec(check_sql($sql));
		unset($missing);

	//unset the array variable
		unset($array);

}
?>