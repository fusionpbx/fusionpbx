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
	Portions created by the Initial Developer are Copyright (C) 2016 - 2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

	if ($domains_processed == 1) {

		//add the music_on_hold list to the database
		$sql = "select count(*) from v_conference_profiles; ";
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
				foreach ($conf_array['profiles']['profile'] as $row) {

					//get the data from the array
						$profile_name = $row['@attributes']['name'];
						//echo $profile_name."<br />\n";

					//insert the data into the database
						$conference_profile_uuid = uuid();
						$array['conference_profiles'][0]['conference_profile_uuid'] = $conference_profile_uuid;
						$array['conference_profiles'][0]['profile_name'] = $profile_name;
						$array['conference_profiles'][0]['profile_enabled'] = 'true';

						$p = new permissions;
						$p->add('conference_profile_add', 'temp');

						$database = new database;
						$database->app_name = 'conference_profiles';
						$database->app_uuid = 'c33e2c2a-847f-44c1-8c0d-310df5d65ba9';
						$database->save($array, false);
						unset($array);

						$p->delete('conference_profile_add', 'temp');

					//insert the profile params
						foreach ($row['param'] as $p) {
							//get the name
								//print_r($p);
								$profile_param_name = $p['@attributes']['name'];
								$profile_param_value = $p['@attributes']['value'];
								$profile_param_enabled = 'true';

							//add the coference profile params
								$conference_profile_param_uuid = uuid();
								$array['conference_profile_params'][0]['conference_profile_uuid'] = $conference_profile_uuid;
								$array['conference_profile_params'][0]['conference_profile_param_uuid'] = $conference_profile_param_uuid;
								$array['conference_profile_params'][0]['profile_param_name'] = $profile_param_name;
								$array['conference_profile_params'][0]['profile_param_value'] = $profile_param_value;
								$array['conference_profile_params'][0]['profile_param_enabled'] = $profile_param_enabled;

								$p = new permissions;
								$p->add('conference_profile_param_add', 'temp');

								$database = new database;
								$database->app_name = 'conference_profiles';
								$database->app_uuid = 'c33e2c2a-847f-44c1-8c0d-310df5d65ba9';
								$database->save($array, false);
								unset($array);

								$p->delete('conference_profile_param_add', 'temp');
						}

				}

		}
		unset($sql, $num_rows);

	}

?>
