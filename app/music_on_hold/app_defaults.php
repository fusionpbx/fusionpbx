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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

	if ($domains_processed == 1) {

		//set the directory
			if (is_array($_SESSION["switch"]["conf"])) {
				$xml_dir = $_SESSION["switch"]["conf"]["dir"].'/autoload_configs';
				$xml_file = $xml_dir."/local_stream.conf";
			}

		//rename the file
			if (is_array($_SESSION["switch"]["conf"])) {
				if (file_exists($xml_dir.'/local_stream.conf.xml')) {
					rename($xml_dir.'/local_stream.conf', $xml_dir.'/'.$xml_file);
				}
				if (file_exists($xml_dir.'/local_stream.conf.xml.noload')) {
					rename($xml_dir.'/local_stream.conf', $xml_dir.'/'.$xml_file);
				}
			}

		//add the music_on_hold list to the database
			if (is_array($_SESSION["switch"]["conf"])) {
				$sql = "select count(music_on_hold_uuid) as num_rows from v_music_on_hold; ";
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] == 0) {

						//set the alternate directory
							$xml_file_alt = $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/resources/templates/conf/autoload_configs/local_stream.conf';

						//load the xml and save it into an array
							if (file_exists($xml_file)) {
								$xml_string = file_get_contents($xml_file);
							}
							elseif (file_exists($xml_file_alt)) {
								$xml_string = file_get_contents($xml_file_alt);
							}
							$xml_object = simplexml_load_string($xml_string);
							$json = json_encode($xml_object);
							$conf_array = json_decode($json, true);

						//process the array
							foreach ($conf_array['directory'] as $row) {
								//get the data from the array
									$stream_name = $row['@attributes']['name'];
									$stream_path = $row['@attributes']['path'];
									foreach ($row['param'] as $p) {
										$name = $p['@attributes']['name'];
										$name = str_replace("-", "_", $name);
										$$name = $p['@attributes']['value'];
										$attributes[] = $name;
									}

								//strip the domain name and rate from the name
									$array = explode('/', $stream_name);
									if (count($array) == 3) { $stream_name = $array[1]; }
									if (count($array) == 2) { $stream_name = $array[0]; }

								//insert the data into the database
									$music_on_hold_uuid = uuid();
									$sql = "insert into v_music_on_hold ";
									$sql .= "(";
									$sql .= "music_on_hold_uuid, ";
									$sql .= "music_on_hold_name, ";
									if (isset($rate)) $sql .= "music_on_hold_rate, ";
									if (isset($shuffle)) $sql .= "music_on_hold_shuffle, ";
									if (isset($timer_name)) $sql .= "music_on_hold_timer_name, ";
									if (isset($chime_list)) $sql .= "music_on_hold_chime_list, ";
									if (isset($chime_freq)) $sql .= "music_on_hold_chime_freq, ";
									if (isset($chime_max)) $sql .= "music_on_hold_chime_max, ";
									$sql .= "music_on_hold_path ";
									$sql .= ") ";
									$sql .= "values ";
									$sql .= "( ";
									$sql .= "'".$music_on_hold_uuid."', ";
									$sql .= "'".check_str($stream_name)."', ";
									if (isset($rate)) $sql .= "'".check_str($rate)."', ";
									if (isset($shuffle)) $sql .= "'".check_str($shuffle)."', ";
									if (isset($timer_name)) $sql .= "'".check_str($timer_name)."', ";
									if (isset($chime_list)) $sql .= "'".check_str($chime_list)."', ";
									if (isset($chime_max)) $sql .= "'".check_str($chime_max)."', ";
									$sql .= "'".check_str($stream_path)."' ";
									$sql .= ");";
									//echo $sql."\n";
									$db->exec(check_sql($sql));
									unset($sql);

								//unset the attribute variables
									foreach ($attributes as $value) {
										unset($$value);
									}
							}

					} //if num_rows
				} //if prep_statement
			} //if is_array
	}

?>
