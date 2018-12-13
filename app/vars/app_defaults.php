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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//add the variables to the database
		$sql = "select count(*) as num_rows from v_vars ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				//get the xml
					if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf/vars.xml')) {
						$xml_file = '/usr/share/examples/fusionpbx/resources/templates/conf/vars.xml';
					}
					elseif (file_exists('/usr/local/share/fusionpbx/resources/templates/conf/vars.xml')) {
						$xml_file = '/usr/local/share/fusionpbx/resources/templates/conf/vars.xml';
					}
					else {
						$xml_file = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/conf/vars.xml';
					}

				//load the xml and save it into an array
					$xml_string = file_get_contents($xml_file);
					$xml = simplexml_load_string($xml_string);
					$json = json_encode($xml);
					$variables = json_decode($json, true);
					//<X-PRE-PROCESS cmd="set" data="global_codec_prefs=G7221@32000h,G7221@16000h,G722,PCMU,PCMA" category="Codecs" enabled="true"/>
					$x = 0;
					foreach ($variables['X-PRE-PROCESS'] as $variable) {
						$var_category = $variable['@attributes']['category'];
						$data = explode('=', $variable['@attributes']['data']);
						$var_name = $data[0];
						$var_value = $data[1];
						$var_command = $variable['@attributes']['cmd'];
						$var_enabled = $variable['@attributes']['enabled'];
						$var_order = '';
						$var_description = '';

						$array['vars'][$x]['var_category'] = $var_category;
						$array['vars'][$x]['var_uuid'] = uuid();
						$array['vars'][$x]['var_name'] = $var_name;
						$array['vars'][$x]['var_value'] = $var_value;
						$array['vars'][$x]['var_command'] = $var_command;
						$array['vars'][$x]['var_enabled'] = $var_enabled;
						$array['vars'][$x]['var_order'] = $var_order;
						$array['vars'][$x]['var_description'] = $var_description;
						$x++;
					}

				//add the dialplan permission
					$p = new permissions;
					$p->add("var_add", "temp");
					$p->add("var_edit", "temp");

				//save to the data
					$database = new database;
					$database->app_name = 'vars';
					$database->app_uuid = '54e08402-c1b8-0a9d-a30a-f569fc174dd8';
					$database->save($array);
					$message = $database->message;

				//remove the temporary permission
					$p->delete("var_add", "temp");
					$p->delete("var_edit", "temp");
	
			}
		}

	// Set country depend variables as country code and international direct dialing code (exit code)
		if (!function_exists('set_country_vars')) {
			function set_country_vars($db, $x) {
				require "resources/countries.php";
	
				//$country_iso=$_SESSION['domain']['country']['iso_code'];
	
				$sql = "select default_setting_value as value from v_default_settings ";
				$sql .= "where default_setting_name = 'iso_code' ";
				$sql .= "and default_setting_category = 'domain' ";
				$sql .= "and default_setting_subcategory = 'country' ";
				$sql .= "and default_setting_enabled = 'true';";
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					if ( count($result)> 0) {
						$country_iso = $result[0]["value"];
					}
				}
				unset($prep_statement, $sql, $result);

				if ( $country_iso===NULL ) {
					return;
				}

				if(isset($countries[$country_iso])){
					$country = $countries[$country_iso];

					// Set default Country ISO code
					$sql = "select count(*) as num_rows from v_vars ";
					$sql .= "where var_name = 'default_country' ";
					$sql .= "and var_category = 'Defaults' ";
					$prep_statement = $db->prepare(check_sql($sql));
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
	
						if ($row['num_rows'] == 0) {
							$sql = "insert into v_vars ";
							$sql .= "(";
							$sql .= "var_uuid, ";
							$sql .= "var_name, ";
							$sql .= "var_value, ";
							$sql .= "var_category, ";
							$sql .= "var_enabled, ";
							$sql .= "var_order, ";
							$sql .= "var_description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							$sql .= "'default_country', ";
							$sql .= "'".$country["isocode"]."', ";
							$sql .= "'Defaults', ";
							$sql .= "'true', ";
							$sql .= "'".$x."', ";
							$sql .= "'' ";
							$sql .= ");";
							$db->exec(check_sql($sql));
							unset($sql, $row);
							$x++;
						}
					}
					unset($prep_statement, $sql);

					//Set default Country code
					$sql = "select count(*) as num_rows from v_vars ";
					$sql .= "where var_name = 'default_countrycode' ";
					$sql .= "and var_category = 'Defaults' ";
					$prep_statement = $db->prepare(check_sql($sql));
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] == 0) {
							$sql = "insert into v_vars ";
							$sql .= "(";
							$sql .= "var_uuid, ";
							$sql .= "var_name, ";
							$sql .= "var_value, ";
							$sql .= "var_category, ";
							$sql .= "var_enabled, ";
							$sql .= "var_order, ";
							$sql .= "var_description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							$sql .= "'default_countrycode', ";
							$sql .= "'".$country["countrycode"]."', ";
							$sql .= "'Defaults', ";
							$sql .= "'true', ";
							$sql .= "'".$x."', ";
							$sql .= "'' ";
							$sql .= ");";
							$db->exec(check_sql($sql));
							unset($sql, $row);
							$x++;
						}
					}
					unset($prep_statement, $sql);

					// Set default International Direct Dialing code
					$sql = "select count(*) as num_rows from v_vars ";
					$sql .= "where var_name = 'default_exitcode' ";
					$sql .= "and var_category = 'Defaults' ";
					$prep_statement = $db->prepare(check_sql($sql));
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] == 0) {
							$sql = "insert into v_vars ";
							$sql .= "(";
							$sql .= "var_uuid, ";
							$sql .= "var_name, ";
							$sql .= "var_value, ";
							$sql .= "var_category, ";
							$sql .= "var_enabled, ";
							$sql .= "var_order, ";
							$sql .= "var_description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							$sql .= "'default_exitcode', ";
							$sql .= "'".$country["exitcode"]."', ";
							$sql .= "'Defaults', ";
							$sql .= "'true', ";
							$sql .= "'".$x."', ";
							$sql .= "'' ";
							$sql .= ");";
							$db->exec(check_sql($sql));
							unset($sql, $row);
							$x++;
						}
					}
					unset($prep_statement, $sql, $countries);
				}
			}
		}

	//adjust the variables required variables
		//set variables that depend on the number of domains
			if (count($_SESSION['domains']) > 1) {
				//disable the domain and domain_uuid for systems with multiple domains
					$sql = "update v_vars set ";
					$sql .= "var_enabled = 'false' ";
					$sql .= "where (var_name = 'domain' or var_name = 'domain_uuid') ";
					$db->exec(check_sql($sql));
					unset($sql);
			}
			else {
				//set the domain_uuid
					$sql = "select count(*) as num_rows from v_vars ";
					$sql .= "where var_name = 'domain_uuid' ";
					$prep_statement = $db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] == 0) {
							$sql = "insert into v_vars ";
							$sql .= "(";
							$sql .= "var_uuid, ";
							$sql .= "var_name, ";
							$sql .= "var_value, ";
							$sql .= "var_category, ";
							$sql .= "var_enabled, ";
							$sql .= "var_order, ";
							$sql .= "var_description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							$sql .= "'domain_uuid', ";
							$sql .= "'".$domain_uuid."', ";
							$sql .= "'Defaults', ";
							$sql .= "'true', ";
							$sql .= "'999', ";
							$sql .= "'' ";
							$sql .= ");";
							$db->exec(check_sql($sql));
							unset($sql);
						}
						unset($prep_statement, $row);
					}
			}

		//set country code variables
			set_country_vars($db, $x);

		//save the vars.xml file
			save_var_xml();
}

?>
