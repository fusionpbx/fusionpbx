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

		//add cdr -> format and cdr -> storage to default settings
			$sql = "select count(*) as num_rows from v_default_settings ";
			$sql .= "where default_setting_category = 'cdr' ";
			$sql .= "and default_setting_subcategory = 'format' ";
			$sql .= "and default_setting_name = 'text' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				unset($prep_statement);
				if ($row['num_rows'] == 0) {
					$x = 0;
					$array[$x]['default_setting_category'] = 'cdr';
					$array[$x]['default_setting_subcategory'] = 'format';
					$array[$x]['default_setting_name'] = 'text';
					$array[$x]['default_setting_value'] = 'json';
					$array[$x]['default_setting_enabled'] = 'true';
					$array[$x]['default_setting_description'] = '';
					$x++;
					$array[$x]['default_setting_category'] = 'cdr';
					$array[$x]['default_setting_subcategory'] = 'storage';
					$array[$x]['default_setting_name'] = 'text';
					$array[$x]['default_setting_value'] = 'db';
					$array[$x]['default_setting_enabled'] = 'true';
					$array[$x]['default_setting_description'] = '';
					$x++;
					$array[$x]['default_setting_category'] = 'cdr';
					$array[$x]['default_setting_subcategory'] = 'limit';
					$array[$x]['default_setting_name'] = 'numeric';
					$array[$x]['default_setting_value'] = '800';
					$array[$x]['default_setting_enabled'] = 'true';
					$array[$x]['default_setting_description'] = '';
					$x++;
					$orm = new orm;
					$orm->name('default_settings');
					foreach ($array as $index => $null) {
						$orm->save($array[$index]);
					}
					$message = $orm->message;
					//print_r($message);
				}
				unset($row);
			}
	}

?>
