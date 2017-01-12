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

//make sure that prefix-a-leg is set to true in the xml_cdr.conf.xml file

	if ($domains_processed == 1) {
		/*
		$file_contents = file_get_contents($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_cdr.conf.xml");
		$file_contents_new = str_replace("param name=\"prefix-a-leg\" value=\"false\"/", "param name=\"prefix-a-leg\" value=\"true\"/", $file_contents);
		if ($file_contents != $file_contents_new) {
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_cdr.conf.xml","w");
			fwrite($fout, $file_contents_new);
			fclose($fout);
			if ($display_type == "text") {
				echo "	xml_cdr.conf.xml: 	updated\n";
			}
		}
		*/

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_uuid'] = 'dbbadd02-f95d-480b-85d5-2a4113d4cccc';
		$array[$x]['default_setting_category'] = 'cdr';
		$array[$x]['default_setting_subcategory'] = 'format';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'json';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_uuid'] = '23335ac0-9466-4d16-af3f-81aa347159b1';
		$array[$x]['default_setting_category'] = 'cdr';
		$array[$x]['default_setting_subcategory'] = 'storage';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'db';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_uuid'] = 'cdb19dda-234b-407a-9eda-e8af74597d4b';
		$array[$x]['default_setting_category'] = 'cdr';
		$array[$x]['default_setting_subcategory'] = 'limit';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '800';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_uuid'] = 'cea53099-96c0-405f-ada6-219d0b398944';
		$array[$x]['default_setting_category'] = 'cdr';
		$array[$x]['default_setting_subcategory'] = 'http_enabled';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';

	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'cdr' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);

	//find the missing default settings
		$x = 0;
		foreach ($array as $setting) {
			$found = false;
			$missing[$x] = $setting;
			if (is_array($default_settings)) {
				foreach ($default_settings as $row) {
					if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory']) && trim($row['default_setting_name']) == trim($setting['default_setting_name'])) {
						$found = true;
						//remove items from the array that were found
						unset($missing[$x]);
					}
				}
			}
			$x++;
		}

	//get the missing count
		$i = 0;
		if (is_array($missing)) foreach ($missing as $row) { $i++; }
		$missing_count = $i;

	//add the missing default settings
		if (is_array($missing)) {
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
				$sql .= "'".$row['default_setting_uuid']."', ";
				$sql .= "'".$row['default_setting_category']."', ";
				$sql .= "'".$row['default_setting_subcategory']."', ";
				$sql .= "'".$row['default_setting_name']."', ";
				$sql .= "'".$row['default_setting_value']."', ";
				$sql .= "'".$row['default_setting_enabled']."', ";
				$sql .= "'".$row['default_setting_description']."' ";
				$sql .= ")";
				if ($missing_count != $i) {
					$sql .= ",\n";
				}
				$i++;
			}
			$db->exec(check_sql($sql));
			unset($missing);
		}
		unset($sql);
	}

?>
