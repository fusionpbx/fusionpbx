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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'ivr_menu';
		$array[$x]['default_setting_subcategory'] = 'option_add_rows';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '5';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_category'] = 'ivr_menu';
		$array[$x]['default_setting_subcategory'] = 'option_edit_rows';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '1';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;

	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);

	//find the missing default settings
		$x = 0;
		foreach ($array as $setting) {
			$found = false;
			$missing[$x] = $setting;
			foreach ($default_settings as $row) {
				if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory']) && trim($row['default_setting_name']) == trim($setting['default_setting_name'])) {
					$found = true;
					//remove items from the array that were found
					unset($missing[$x]);
				}
			}
			$x++;
		}

	//get the missing count
		$i = 0;
		foreach ($missing as $row) { $i++; }
		$missing_count = $i;

	//add the missing default settings
		if (count($missing) > 0) {
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
		}

	//move the dynamic provision variables that from v_vars table to v_default_settings
		if (count($_SESSION['provision']) == 0) {
			$sql = "select * from v_vars ";
			$sql .= "where var_cat = 'Provision' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				//set the variable
					$var_name = check_str($row['var_name']);
				//remove the 'v_' prefix from the variable name
					if (substr($var_name, 0, 2) == "v_") {
						$var_name = substr($var_name, 2);
					}
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
					$sql .= "'provision', ";
					$sql .= "'".$var_name."', ";
					$sql .= "'var', ";
					$sql .= "'".check_str($row['var_value'])."', ";
					$sql .= "'".check_str($row['var_enabled'])."', ";
					$sql .= "'".check_str($row['var_description'])."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
			}
			unset($prep_statement);
			//delete the provision variables from system -> variables
			//$sql = "delete from v_vars ";
			//$sql .= "where var_cat = 'Provision' ";
			//echo $sql ."\n";
			//$db->exec(check_sql($sql));
			//echo "$var_name $var_value \n";
		}

}

?>
