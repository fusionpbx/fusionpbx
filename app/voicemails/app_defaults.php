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

	//iterate and add each, if necessary
		foreach ($array as $index => $default_settings) {
			//add the default setting
			$sql = "select count(*) as num_rows from v_default_settings ";
			$sql .= "where default_setting_category = '".$default_settings['default_setting_category']."' ";
			$sql .= "and default_setting_subcategory = '".$default_settings['default_setting_subcategory']."' ";
			$sql .= "and default_setting_name = '".$default_settings['default_setting_name']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				unset($prep_statement);
				if ($row['num_rows'] == 0) {
					$orm = new orm;
					$orm->name('default_settings');
					$orm->save($array[$index]);
					$message = $orm->message;
					//print_r($message);
				}
				unset($row);
			}
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
				mkdir($path, 0777, true);
			}
		}
		unset ($prep_statement, $sql);

}

?>