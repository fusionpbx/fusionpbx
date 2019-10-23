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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//create phrases folder and add include line in xml for each language found
		/*
		if (strlen($_SESSION['switch']['phrases']['dir']) > 0) {
			if (is_readable($_SESSION['switch']['phrases']['dir'])) {
				$conf_lang_folders = glob($_SESSION['switch']['phrases']['dir']."/*");
				foreach ($conf_lang_folders as $conf_lang_folder) {
					//create phrases folder, if necessary
					if (!file_exists($conf_lang_folder."/phrases/")) {
						event_socket_mkdir($conf_lang_folder."/phrases/");
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
		*/

	//if base64, convert existing incompatible phrases
		if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
			$sql = "select phrase_detail_uuid, phrase_detail_data ";
			$sql .= "from v_phrase_details where phrase_detail_function = 'play-file' ";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as $index => &$row) {
					$phrase_detail_uuid = $row['phrase_detail_uuid'];
					$phrase_detail_data = $row['phrase_detail_data'];
					if (substr_count($phrase_detail_data, $_SESSION['switch']['recordings']['dir'].'/'.$domain_name) > 0) {
						$phrase_detail_data = str_replace($_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/', '', $phrase_detail_data);
					}
					//update function and data to be base64 compatible
						$phrase_detail_data = "lua(streamfile.lua ".$phrase_detail_data.")";
						$array['phrase_details'][$index]['phrase_detail_uuid'] = $phrase_detail_uuid;
						$array['phrase_details'][$index]['phrase_detail_function'] = 'execute';
						$array['phrase_details'][$index]['phrase_detail_data'] = $phrase_detail_data;
				}
				if (is_array($array) && @sizeof($array) != 0) {
					$p = new permissions;
					$p->add('phrase_detail_edit', 'temp');

					$database = new database;
					$database->app_name = 'phrases';
					$database->app_uuid = '5c6f597c-9b78-11e4-89d3-123b93f75cba';
					$database->save($array);
					unset($array);

					$p->delete('phrase_detail_edit', 'temp');
				}
			}
			unset($sql, $result, $row);
		}

	//if not base64, revert base64 phrases to standard method
		else if ($_SESSION['recordings']['storage_type']['text'] != 'base64') {
			$sql = "select phrase_detail_uuid, phrase_detail_data ";
			$sql .= "from v_phrase_details where ";
			$sql .= "phrase_detail_function = 'execute' ";
			$sql .= "and phrase_detail_data like 'lua(streamfile.lua %)' ";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as $index => &$row) {
					$phrase_detail_uuid = $row['phrase_detail_uuid'];
					$phrase_detail_data = $row['phrase_detail_data'];
					//update function and data to use standard method
						$phrase_detail_data = str_replace('lua(streamfile.lua ', '', $phrase_detail_data);
						$phrase_detail_data = str_replace(')', '', $phrase_detail_data);
						if (substr_count($phrase_detail_data, '/') === 0) {
							$phrase_detail_data = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/'.$phrase_detail_data;
						}
						$array['phrase_details'][$index]['phrase_detail_uuid'] = $phrase_detail_uuid;
						$array['phrase_details'][$index]['phrase_detail_function'] = 'play-file';
						$array['phrase_details'][$index]['phrase_detail_data'] = $phrase_detail_data;
				}
				if (is_array($array) && @sizeof($array) != 0) {
					$p = new permissions;
					$p->add('phrase_detail_edit', 'temp');

					$database = new database;
					$database->app_name = 'phrases';
					$database->app_uuid = '5c6f597c-9b78-11e4-89d3-123b93f75cba';
					$database->save($array);
					unset($array);

					$p->delete('phrase_detail_edit', 'temp');
				}
			}
			unset($sql, $result, $row);
		}

	//save the xml to the file system if the phrase directory is set
		//require_once "resources/functions/save_phrases_xml.php";
		//save_phrases_xml();

	//delete the phrase from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			//get phrase languages
			$sql = "select distinct phrase_language from v_phrases order by phrase_language asc ";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			//delete memcache var
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as $row) {
					//clear the cache
					$cache = new cache;
					$cache->delete("languages:".$row['phrase_language']);
				}
			}
			unset($sql, $result, $row);
		}
		unset($fp);

}

?>
