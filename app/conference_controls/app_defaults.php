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
	Portions created by the Initial Developer are Copyright (C) 2016-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

	if ($domains_processed == 1) {

		//add the conference controls list to the database
		$sql = "select count(*) from v_conference_controls; ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows == 0) {

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
						$array['conference_controls'][0]['conference_control_uuid'] = $conference_control_uuid;
						$array['conference_controls'][0]['control_name'] = $control_name;
						$array['conference_controls'][0]['control_enabled'] = 'true';

						$p = new permissions;
						$p->add('conference_control_add', 'temp');

						$database = new database;
						$database->app_name = 'conference_controls';
						$database->app_uuid = 'e1ad84a2-79e1-450c-a5b1-7507a043e048';
						$database->save($array);
						unset($array);

						$p->delete('conference_control_add', 'temp');

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
								$array['conference_control_details'][0]['conference_control_uuid'] = $conference_control_uuid;
								$array['conference_control_details'][0]['conference_control_detail_uuid'] = $conference_control_detail_uuid;
								$array['conference_control_details'][0]['control_digits'] = $control_digits;
								$array['conference_control_details'][0]['control_action'] = $control_action;
								if (strlen($control_data) > 0) {
									$array['conference_control_details'][0]['control_data'] = $control_data;
								}
								$array['conference_control_details'][0]['control_enabled'] = $control_enabled;

								$p = new permissions;
								$p->add('conference_control_detail_add', 'temp');

								$database = new database;
								$database->app_name = 'conference_controls';
								$database->app_uuid = 'e1ad84a2-79e1-450c-a5b1-7507a043e048';
								$database->save($array);
								unset($array);

								$p->delete('conference_control_detail_add', 'temp');

						}

				}

		}
		unset($sql, $num_rows);

	}

?>
