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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
if ($domains_processed == 1) {

	//make sure the voicemail directory exists
	$sql = "select d.domain_name, v.voicemail_id \n";
	$sql .= "from v_voicemails as v, v_domains as d \n";
	$sql .= "where voicemail_enabled = 'true' \n";
	$sql .= "and v.domain_uuid = d.domain_uuid \n";
	$database = new database;
	$voicemails = $database->select($sql, null, 'all');
	unset($sql, $parameters);
	if (!empty($voicemails) && is_array($voicemails)) {
		foreach($voicemails as $row) {
			if (!empty($setting->get('switch','voicemail')) && !empty($row['voicemail_id']) && is_numeric($row['voicemail_id'])) {
				if (!file_exists($setting->get('switch','voicemail')."/default/".$row['domain_name']."/".$row['voicemail_id'])) {
					mkdir($setting->get('switch','voicemail')."/default/".$row['domain_name']."/".$row['voicemail_id'], 0770, true);
				}
			}
		}
	}

	//define initial, get current, define correct languages folder paths
	$switch_configuration_dir = !empty($setting->get('switch','conf')) ? $setting->get('switch','conf') : '/etc/freeswitch';
	$switch_languages_dir_initial = $switch_configuration_dir.'/lang';
	$switch_languages_dir_current = $setting->get('switch','languages') ?? '';
	$switch_languages_dir_correct = $switch_configuration_dir.'/languages';

	//ensure switch using languages (not lang) folder
	if ($switch_languages_dir_current == $switch_languages_dir_initial) {
		//rename languages folder, if necessary
		if (file_exists($switch_languages_dir_current) && !file_exists($switch_languages_dir_correct)) {
			rename($switch_languages_dir_current, $switch_languages_dir_correct);
		}

		//update default setting value
		if (file_exists($switch_languages_dir_correct)) {
			//update languages with the correct path
			$sql = "update v_default_settings ";
			$sql .= "set default_setting_value = '".$switch_languages_dir_correct."', ";
			$sql .= "default_setting_enabled = true ";
			$sql .= "where default_setting_category = 'switch' ";
			$sql .= "and default_setting_subcategory = 'languages' ";
			$sql .= "and default_setting_name = 'dir' ";
			$database = new database;
			$database->execute($sql);
			unset($sql);
		}

	}

	if (file_exists($switch_languages_dir_correct)) {
		//update language path in main switch xml file
		if (file_exists($switch_configuration_dir.'/freeswitch.xml')) {
			$switch_xml_content = file_get_contents($switch_configuration_dir.'/freeswitch.xml');
			$switch_xml_content = str_replace('data="lang/', 'data="languages/', $switch_xml_content);
			@file_put_contents($switch_configuration_dir.'/freeswitch.xml', $switch_xml_content);
		}
		$folder_contents = scandir($switch_languages_dir_correct);
		if (!empty($folder_contents) && is_array($folder_contents) && @sizeof($folder_contents) != 0) {
			foreach ($folder_contents as $language_abbreviation) {
				if ($language_abbreviation == '.' || $language_abbreviation == '..') { continue; }
				// adjust language xml file to include all xml phrase files in the vm folder
				$language_xml_path = $switch_languages_dir_correct.'/'.$language_abbreviation.'/'.$language_abbreviation.'.xml';
				if (file_exists($language_xml_path)) {
					$language_xml_content = file_get_contents($language_xml_path);
					$language_xml_content = str_replace('data="vm/sounds.xml"', 'data="vm/*.xml"', $language_xml_content);
					@file_put_contents($language_xml_path, $language_xml_content);
				}
				// copy voicemail.xml to languages/xx/vm folders
				$voicemail_xml_source = $_SERVER['PROJECT_ROOT'].'/app/voicemails/resources/switch/languages/'.$language_abbreviation.'/vm/voicemail.xml';
				$voicemail_xml_target = $switch_languages_dir_correct.'/'.$language_abbreviation.'/vm/voicemail.xml';
				if (!file_exists($voicemail_xml_target)) {
					copy($voicemail_xml_source, $voicemail_xml_target);
				}
			}
		}
	}

	// clear variables
	unset($switch_configuration_dir, $switch_languages_dir_initial, $switch_languages_dir_current, $switch_languages_dir_correct);
	unset($switch_xml_content, $folder_contents, $language_abbreviation, $language_xml_path, $language_xml_content, $voicemail_xml_source, $voicemail_xml_target);

	//delete the switch phrases in favor of switch languages
	$sql = "delete from v_default_settings ";
	$sql .= "where default_setting_category = 'switch' ";
	$sql .= "and default_setting_subcategory = 'phrases' ";
	$sql .= "and default_setting_name = 'dir' ";
	$database = new database;
	$database->execute($sql);
	unset($sql);

	//set default value of voicemail_recording_instructions to true
	$sql = "update v_voicemails set voicemail_recording_instructions = 'true' where voicemail_recording_instructions is null";
	$database = new database;
	$database->execute($sql);
	unset($sql);

	//set default value of voicemail_recording_options to true
	$sql = "update v_voicemails set voicemail_recording_options = 'true' where voicemail_recording_options is null";
	$database = new database;
	$database->execute($sql);
	unset($sql);

}

?>