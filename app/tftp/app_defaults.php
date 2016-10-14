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
	Sebastian Krupinski <sebastian@ksacorp.com>
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Sebastian Krupinski <sebastian@ksacorp.com>
*/

//process this code online once
	if ($domains_processed == 1) {

		//define array of settings
			$x = 0;
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'tftp_service_address';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '0.0.0.0';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = 'the address for the TFTP service to listen for connection on';
			$x++;
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'tftp_service_port';
			$array[$x]['default_setting_name'] = 'numeric';
			$array[$x]['default_setting_value'] = '69';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = 'the port for the TFTP service to listen for connection on';
			$x++;
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'tftp_service_file_path';
			$array[$x]['default_setting_name'] = 'numeric';
			$array[$x]['default_setting_value'] = '/tmp';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = 'the location for static files e.g. firmware';
    
    //get an array of the default settings
			$sql = "SELECT * FROM v_default_settings ";
			$sql .= "WHERE default_setting_category = 'provision' AND default_setting_subcategory LIKE 'tftp_service_%'";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			unset ($prep_statement, $sql);

		//find the missing default settings
			$missing = array();
			foreach ($array as $setting) {
				$found = false;
				foreach ($default_settings as $row) {
					if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory'])) { 
						$found = true;
						break;
					}
				}

				if (!$found) $missing[] = $setting;
			}

		//add the missing default settings
			foreach ($missing as $row) {
				//add the default settings
				$orm = new orm;
				$orm->name('default_settings');
				$orm->save($row);
				$message = $orm->message;
				unset($orm);
				//print_r($message);
			}
			unset($missing);

    }
?>