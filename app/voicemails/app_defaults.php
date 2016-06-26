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

//proccess this only one time
if ($domains_processed == 1) {

	//migrate existing attachment preferences to new column, where appropriate
		$sql = "update v_voicemails set voicemail_file = 'attach' where voicemail_attach_file = 'true'";
		$db->exec(check_sql($sql));
		unset($sql);

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'voicemail';
		$array[$x]['default_setting_subcategory'] = 'voicemail_file';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'attach';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Define whether to attach voicemail files to email notifications, or only include a link.';
		$x++;
		$array[$x]['default_setting_category'] = 'voicemail';
		$array[$x]['default_setting_subcategory'] = 'keep_local';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Define whether to keep voicemail files on the local system after sending attached via email.';
		$x++;
		$array[$x]['default_setting_category'] = 'voicemail';
		$array[$x]['default_setting_subcategory'] = 'storage_type';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'base64';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Define which storage type (base_64 stores in the database).';
		$x++;
		$array[$x]['default_setting_category'] = 'voicemail';
		$array[$x]['default_setting_subcategory'] = 'message_max_length';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '300';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Maximum length of a voicemail (in seconds).';
		$x++;
		$array[$x]['default_setting_category'] = 'voicemail';
		$array[$x]['default_setting_subcategory'] = 'greeting_max_length';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '90';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Maximum length of a voicemail greeting (in seconds).';

	//get an array of the default settings
		if (!is_array($default_settings)) {
			$sql = "select * from v_default_settings ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			unset ($prep_statement, $sql);
		}

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

	//add that the directory structure for voicemail each domain and voicemail id is
		$sql = "select d.domain_name, v.voicemail_id ";
		$sql .= "from v_domains as d, v_voicemails as v ";
		$sql .= "where v.domain_uuid = d.domain_uuid ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$voicemails = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($voicemails as $row) {
			$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$row['domain_name'].'/'.$row['voicemail_id'];
			if (!file_exists($path)) {
				mkdir($path, 02770, true);
			}
		}
		unset ($prep_statement, $sql);

}

?>