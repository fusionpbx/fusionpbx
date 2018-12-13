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

		//add the conference controls list to the database
		$sql = "select count(*) as num_rows from v_conference_controls; ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {

				//set the directory
					$xml_dir = $_SESSION["switch"]["conf"]["dir"].'/autoload_configs';
					$xml_file = $xml_dir."/conference.conf";
					$xml_file_alt = $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/resources/templates/conf/autoload_configs/conference.conf';

				//rename the file
					if (file_exists($xml_dir.'/conference.conf.xml.noload')) {
						rename($xml_dir.'/conference.conf.xml.noload', $xml_dir.'/conference.conf');
					}

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
					foreach ($conf_array['caller-controls']['group'] as $row) {

						//get the data from the array
							$control_name = $row['@attributes']['name'];
							//echo $profile_name."<br />\n";

						//insert the data into the database
							$conference_control_uuid = uuid();
							$sql = "insert into v_conference_controls ";
							$sql .= "(";
							//$sql .= "domain_uuid, ";
							$sql .= "conference_control_uuid, ";
							$sql .= "control_name, ";
							$sql .= "control_enabled ";
							$sql .= ") ";
							$sql .= "values ";
							$sql .= "( ";
							//$sql .= "'".$domain_uuid."', ";
							$sql .= "'".$conference_control_uuid."', ";
							$sql .= "'".check_str($control_name)."', ";
							$sql .= "'true' ";
							$sql .= ");";
							//echo $sql."\n";
							$db->exec(check_sql($sql));
							unset($sql);

						//insert the profile params
							foreach ($row['control'] as $p) {

								//get the name
									//print_r($p);
									$control_action = $p['@attributes']['action'];
									$control_digits = $p['@attributes']['digits'];
									$control_data = $p['@attributes']['data'];
									$control_enabled = 'true';

								//add the coference profile params
									$conference_control_detail_uuid = uuid();
									$sql = "insert into v_conference_control_details ";
									$sql .= "(";
									$sql .= "conference_control_uuid, ";
									$sql .= "conference_control_detail_uuid, ";
									$sql .= "control_digits, ";
									$sql .= "control_action, ";
									if (strlen($control_data) > 0) {
										$sql .= "control_data, ";
									}
									$sql .= "control_enabled ";
									$sql .= ") ";
									$sql .= "values ";
									$sql .= "( ";
									$sql .= "'".$conference_control_uuid."', ";
									$sql .= "'".$conference_control_detail_uuid."', ";
									$sql .= "'".$control_digits."', ";
									$sql .= "'".$control_action."', ";
									if (strlen($control_data) > 0) {
										$sql .= "'".$control_data."', ";
									}
									$sql .= "'".$control_enabled."' ";
									$sql .= ");";
									//echo $sql."\n";
									$db->exec(check_sql($sql));
									unset($sql);
							}

					}

			} //if num_rows
		} //if prep_statement

	}

?>
