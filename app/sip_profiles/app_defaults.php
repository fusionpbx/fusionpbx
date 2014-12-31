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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the number of rows is 0 then read the sip profile xml into the database
	if ($domains_processed == 1) {
		//define the variables
			$source = '';
			$destination = '';
		//check if the directory exists and set the paths
			if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
				//linux
				$source = '/usr/share/examples/fusionpbx/resources/templates/conf/sip_profiles';
				$destination = '/etc/fusionpbx/resources/templates/conf/sip_profiles';
			}
			if (file_exists('/usr/local/share/fusionpbx/resources/templates/conf')) {
				//bsd
				$source = '/usr/local/share/fusionpbx/resources/templates/conf/sip_profiles';
				$destination = '/usr/local/etc/fusionpbx/resources/templates/conf/sip_profiles';
			}
		//copy the conf sip profiles to the /etc/fusionpbx/resources/templates/conf directory
			if (strlen($source) > 0 && strlen($destination) > 0) {
				if (!file_exists($destination)) {
					if (file_exists($source)) {
						//add the directory structure
							mkdir($destination,0777,true);
						//copy from source to destination
							$obj = new install;
							$obj->recursive_copy($source,$destination);
					}
				}
			}

		//add the sip profiles to the database
			$sql = "select count(*) as num_rows from v_sip_profiles ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] == 0) {
					if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf/sip_profiles')) {
						$sip_profile_dir = '/usr/share/examples/fusionpbx/resources/templates/conf/sip_profiles/*.xml';
					}
					elseif (file_exists('/usr/local/share/fusionpbx/resources/templates/conf/sip_profiles')) {
						$sip_profile_dir = '/usr/local/share/fusionpbx/resources/templates/conf/sip_profiles/*.xml';
					}
					else {
						$sip_profile_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/conf/sip_profiles/*.xml';
					}
					$xml_files = glob($sip_profile_dir);
					foreach ($xml_files as &$xml_file) {
						//load the sip profile xml and save it into an array
						$sip_profile_xml = file_get_contents($xml_file);
						$xml = simplexml_load_string($sip_profile_xml);
						$json = json_encode($xml);
						$sip_profile = json_decode($json, true);
						$sip_profile_name = $sip_profile['@attributes']['name'];
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
									$sip_profile_description .= "The External profile external provides anonymous calling in the public context. ";
									$sip_profile_description .= "By default the External profile binds to port 5080. ";
									$sip_profile_description .= "Calls can be sent using a SIP URL \"voip.domain.com:5080\" ";
									break;
								case "lan":
									$sip_profile_description = "The LAN profile is the same as the Internal profile except that it is bound to the LAN IP.\n";
									break;
								default:
									$sip_profile_description .= '';
								}

							//add the sip profile
								$sip_profile_uuid = uuid();
								$sql = "insert into v_sip_profiles";
								$sql .= "(";
								$sql .= "sip_profile_uuid, ";
								$sql .= "sip_profile_name, ";
								$sql .= "sip_profile_description ";
								$sql .= ") ";
								$sql .= "values ";
								$sql .= "( ";
								$sql .= "'".check_str($sip_profile_uuid)."', ";
								$sql .= "'".check_str($sip_profile_name)."', ";
								$sql .= "'".check_str($sip_profile_description)."' ";
								$sql .= ")";
								//echo $sql."\n\n";
								$db->exec(check_sql($sql));
								unset($sql);

							//add the sip profile settings
								foreach ($sip_profile['settings']['param'] as $row) {
									//get the name and value pair
										$sip_profile_setting_name = $row['@attributes']['name'];
										$sip_profile_setting_value = $row['@attributes']['value'];
										//echo "name: $name value: $value\n";
									//add the profile settings into the database
										$sip_profile_setting_uuid = uuid();
										$sql = "insert into v_sip_profile_settings ";
										$sql .= "(";
										$sql .= "sip_profile_setting_uuid, ";
										$sql .= "sip_profile_uuid, ";
										$sql .= "sip_profile_setting_name, ";
										$sql .= "sip_profile_setting_value, ";
										$sql .= "sip_profile_setting_enabled ";
										$sql .= ") ";
										$sql .= "values ";
										$sql .= "( ";
										$sql .= "'".check_str($sip_profile_setting_uuid)."', ";
										$sql .= "'".check_str($sip_profile_uuid)."', ";
										$sql .= "'".check_str($sip_profile_setting_name)."', ";
										$sql .= "'".check_str($sip_profile_setting_value)."', ";
										$sql .= "'true' ";
										$sql .= ")";
										//echo $sql."\n\n";
										$db->exec(check_sql($sql));
								}
						}
					}
				}
				unset($prep_statement);
			}
		}

//if there is more than one domain then disable the force domains sip profile settings
	if ($domains_processed == 1) {
		if (count($_SESSION['domains']) > 1) {
			//disable force domains
				$sql = "update v_sip_profile_settings set ";
				$sql .= "sip_profile_setting_enabled = 'false' ";
				$sql .= "where sip_profile_setting_name = 'force-register-domain'";
				$sql .= "or sip_profile_setting_name = 'force-subscription-domain'";
				$sql .= "or sip_profile_setting_name = 'force-register-db-domain'";
				$db->exec(check_sql($sql));
				unset($sql);

			//save the sip profile xml
				save_sip_profile_xml();

			//apply settings reminder
				$_SESSION["reload_xml"] = true;
		}
	}

?>