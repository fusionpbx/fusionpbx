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

if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'switch';
		$array[$x]['default_setting_subcategory'] = 'phrases';
		$array[$x]['default_setting_name'] = 'dir';
		$array[$x]['default_setting_value'] = '/usr/local/freeswitch/conf/lang';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;

	//iterate and add each, if necessary
		foreach ($array as $index => $default_settings) {

		//add default settings
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


	//create phrases folder and add include line in xml for each language found
		if (strlen($_SESSION['switch']['phrases']['dir']) > 0) {
			if (is_readable($_SESSION['switch']['phrases']['dir'])) {
				$conf_lang_folders = glob($_SESSION['switch']['phrases']['dir']."/*");
				foreach ($conf_lang_folders as $conf_lang_folder) {
					//create phrases folder, if necessary
					if (!file_exists($conf_lang_folder."/phrases/")) {
						mkdir($conf_lang_folder."/phrases/", 0777);
					}
					//parse language, open xml file
					$conf_lang = substr($conf_lang_folder, -2);
					if (file_exists($conf_lang_folder."/".$conf_lang.".xml")) {
						$conf_lang_xml_file_lines = file($conf_lang_folder."/".$conf_lang.".xml");
						//check for phrases inclusion
						$phrases_include_found = false;
						foreach ($conf_lang_xml_file_lines as $conf_lang_xml_file_line) {
							if (substr_count($conf_lang_xml_file_line, "phrases/*.xml") > 0) { $phrases_include_found = true; }
						}
						if (!$phrases_include_found) {
							//loop through lines to find closing macros index
							foreach ($conf_lang_xml_file_lines as $conf_lang_xml_file_line_index => $conf_lang_xml_file_line) {
								if (substr_count($conf_lang_xml_file_line, "</macros>") > 0) {
									array_splice($conf_lang_xml_file_lines, $conf_lang_xml_file_line_index, 0, "\t\t\t\t<X-PRE-PROCESS cmd=\"include\" data=\"phrases/*.xml\"/>\n");
								}
							}
							//re-write xml file contents
							$conf_lang_xml_str = implode("", $conf_lang_xml_file_lines);
							$fh = fopen($conf_lang_folder."/".$conf_lang.".xml", "w");
							fwrite($fh, $conf_lang_xml_str);
							fclose($fh);
						}
					} //if
				} //foreach
			} //if
		} //if

}

?>