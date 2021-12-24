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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the number of rows is 0 then read the sip profile xml into the database
	if ($domains_processed == 1) {

		//add the sip profiles to the database
			$sql = "select count(*) from v_sip_profiles ";
			$database = new database;
			$num_rows = $database->select($sql, null, 'column');
			unset($sql);

			if ($num_rows == 0) {
				if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf/sip_profiles')) {
					$sip_profile_dir = '/usr/share/examples/fusionpbx/resources/templates/conf/sip_profiles/*.xml.noload';
				}
				elseif (file_exists('/usr/local/share/fusionpbx/resources/templates/conf/sip_profiles')) {
					$sip_profile_dir = '/usr/local/share/fusionpbx/resources/templates/conf/sip_profiles/*.xml.noload';
				}
				else {
					$sip_profile_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/conf/sip_profiles/*.xml.noload';
				}

				$xml_files = glob($sip_profile_dir);
				foreach ($xml_files as $x => &$xml_file) {
					//load the sip profile xml and save it into an array
					$sip_profile_xml = file_get_contents($xml_file);
					$xml = simplexml_load_string($sip_profile_xml);
					$json = json_encode($xml);
					$sip_profile = json_decode($json, true);
					$sip_profile_name = $sip_profile['@attributes']['name'];
					$sip_profile_enabled = $sip_profile['@attributes']['enabled'];
					//echo "sip profile name: ".$sip_profile_name."\n";

					if ($sip_profile_name != "{v_sip_profile_name}") {

						//prepare the description
							switch ($sip_profile_name) {
								case "internal":
									$sip_profile_description = "The Internal profile by default requires registration which is used by the endpoints. ";
									$sip_profile_description .= "By default the Internal profile binds to port 5060. ";
									break;
								case "internal-ipv6":
									$sip_profile_description = "The Internal IPV6 profile binds to the IP version 6 address and is similar to the Internal profile.\n";
									break;
								case "external":
									$sip_profile_description = "The External profile external provides anonymous calling in the public context. ";
									$sip_profile_description .= "By default the External profile binds to port 5080. ";
									$sip_profile_description .= "Calls can be sent using a SIP URL \"voip.domain.com:5080\" ";
									break;
								case "external-ipv6":
									$sip_profile_description = "The External IPV6 profile binds to the IP version 6 address and is similar to the External profile.\n";
									break;
								case "lan":
									$sip_profile_description = "The LAN profile is the same as the Internal profile except that it is bound to the LAN IP.\n";
									break;
								default:
									$sip_profile_description = '';
							}

						//add the sip profile if it is not false
							if ($sip_profile_enabled != "false") {

								//add profile name and description
									$sip_profile_uuid = uuid();
									$array['sip_profiles'][$x]['sip_profile_uuid'] = $sip_profile_uuid;
									$array['sip_profiles'][$x]['sip_profile_name'] = $sip_profile_name;
									$array['sip_profiles'][$x]['sip_profile_description'] = $sip_profile_description;

								//add the sip profile domains name, alias and parse
									$sip_profile_domain_uuid = uuid();
									$array['sip_profiles'][$x]['sip_profile_domains'][$x]['sip_profile_domain_uuid'] = $sip_profile_domain_uuid;
									$array['sip_profiles'][$x]['sip_profile_domains'][$x]['sip_profile_uuid'] = $sip_profile_uuid;
									$array['sip_profiles'][$x]['sip_profile_domains'][$x]['sip_profile_domain_name'] = $sip_profile['domains']['domain']['@attributes']['name'];
									$array['sip_profiles'][$x]['sip_profile_domains'][$x]['sip_profile_domain_alias'] = $sip_profile['domains']['domain']['@attributes']['alias'];
									$array['sip_profiles'][$x]['sip_profile_domains'][$x]['sip_profile_domain_parse'] = $sip_profile['domains']['domain']['@attributes']['parse'];

								//add the profile settings
									foreach ($sip_profile['settings']['param'] as $y => $row) {
										$sip_profile_setting_uuid = uuid();
										$array['sip_profiles'][$x]['sip_profile_settings'][$y]['sip_profile_setting_uuid'] = $sip_profile_setting_uuid;
										$array['sip_profiles'][$x]['sip_profile_settings'][$y]['sip_profile_uuid'] = $sip_profile_uuid;
										$array['sip_profiles'][$x]['sip_profile_settings'][$y]['sip_profile_setting_name'] = $row['@attributes']['name'];
										$array['sip_profiles'][$x]['sip_profile_settings'][$y]['sip_profile_setting_value'] = $row['@attributes']['value'];
										$array['sip_profiles'][$x]['sip_profile_settings'][$y]['sip_profile_setting_enabled'] = $row['@attributes']['enabled'] != 'false' ? 'true' : $row['@attributes']['enabled'];
									}

							}

					}
				}

				//execute inserts
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('sip_profile_add', 'temp');
							$p->add('sip_profile_domain_add', 'temp');
							$p->add('sip_profile_setting_add', 'temp');

						//execute insert
							$database = new database;
							$database->app_name = 'sip_profiles';
							$database->app_uuid = '159a8da8-0e8c-a26b-6d5b-19c532b6d470';
							$database->save($array, false);
							unset($array);

						//revoke temporary permissions
							$p->delete('sip_profile_add', 'temp');
							$p->delete('sip_profile_domain_add', 'temp');
							$p->delete('sip_profile_setting_add', 'temp');
					}

				//save the sip profile xml
					save_sip_profile_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;
			}


		//upgrade - add missing sip profiles domain settings
			$sql = "select count(*) from v_sip_profile_domains ";
			$database = new database;
			$num_rows = $database->select($sql, null, 'column');
			unset($sql);

			if ($num_rows == 0) {
				if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf/sip_profiles')) {
					$sip_profile_dir = '/usr/share/examples/fusionpbx/resources/templates/conf/sip_profiles/*.xml.noload';
				}
				elseif (file_exists('/usr/local/share/fusionpbx/resources/templates/conf/sip_profiles')) {
					$sip_profile_dir = '/usr/local/share/fusionpbx/resources/templates/conf/sip_profiles/*.xml.noload';
				}
				else {
					$sip_profile_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/conf/sip_profiles/*.xml.noload';
				}

				$xml_files = glob($sip_profile_dir);
				foreach ($xml_files as $x => &$xml_file) {
					//load the sip profile xml and save it into an array
						$sip_profile_xml = file_get_contents($xml_file);
						$xml = simplexml_load_string($sip_profile_xml);
						$json = json_encode($xml);
						$sip_profile = json_decode($json, true);
						$sip_profile_name = $sip_profile['@attributes']['name'];
						$sip_profile_enabled = $sip_profile['@attributes']['enabled'];

					//get the sip_profile_uuid using the sip profile name
						$sql = "select sip_profile_uuid from v_sip_profiles ";
						$sql .= "where sip_profile_name = :sip_profile_name ";
						$parameters['sip_profile_name'] = $sip_profile_name;
						$database = new database;
						$sip_profile_uuid = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

					//add the sip profile domains name, alias and parse
						if (is_uuid($sip_profile_uuid)) {
							$sip_profile_domain_uuid = uuid();
							$array['sip_profile_domains'][$x]['sip_profile_domain_uuid'] = $sip_profile_domain_uuid;
							$array['sip_profile_domains'][$x]['sip_profile_uuid'] = $sip_profile_uuid;
							$array['sip_profile_domains'][$x]['sip_profile_domain_name'] = $sip_profile['domains']['domain']['@attributes']['name'];
							$array['sip_profile_domains'][$x]['sip_profile_domain_alias'] = $sip_profile['domains']['domain']['@attributes']['alias'];
							$array['sip_profile_domains'][$x]['sip_profile_domain_parse'] = $sip_profile['domains']['domain']['@attributes']['parse'];
						}

					//unset the sip_profile_uuid
						unset($sip_profile_uuid);
				}

				//execute inserts
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('sip_profile_domain_add', 'temp');

						//execute insert
							$database = new database;
							$database->app_name = 'sip_profiles';
							$database->app_uuid = '159a8da8-0e8c-a26b-6d5b-19c532b6d470';
							$database->save($array, false);
							unset($array);

						//revoke temporary permissions
							$p->delete('sip_profile_domain_add', 'temp');
					}

				//save the sip profile xml
					save_sip_profile_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;
			}


		//if empty, set enabled to true
			$sql = "update v_sip_profiles set ";
			$sql .= "sip_profile_enabled = 'true' ";
			$sql .= "where sip_profile_enabled is null ";
			$sql .= "or sip_profile_enabled = '' ";
			$database = new database;
			$database->execute($sql);
			unset($sql);

	}

?>
