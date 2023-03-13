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
		$sql = "select count(*) from v_vars ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		unset($sql);

		if ($num_rows == 0) {
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
					$data = explode('=', $variable['@attributes']['data'], 2);
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

			//grant temporary permissions
				$p = new permissions;
				$p->add("var_add", "temp");
				$p->add("var_edit", "temp");

			//execute insert
				$database = new database;
				$database->app_name = 'vars';
				$database->app_uuid = '54e08402-c1b8-0a9d-a30a-f569fc174dd8';
				$database->save($array, false);
				$message = $database->message;

			//revoke temporary permissions
				$p->delete("var_add", "temp");
				$p->delete("var_edit", "temp");
		}


	//set country depend variables as country code and international direct dialing code (exit code)
		if (!function_exists('set_country_vars')) {
			function set_country_vars($x) {
				require "resources/countries.php";
	
				//$country_iso=$_SESSION['domain']['country']['iso_code'];
	
				$sql = "select default_setting_value ";
				$sql .= "from v_default_settings ";
				$sql .= "where default_setting_name = 'iso_code' ";
				$sql .= "and default_setting_category = 'domain' ";
				$sql .= "and default_setting_subcategory = 'country' ";
				$sql .= "and default_setting_enabled = 'true';";
				$database = new database;
				$country_iso = $database->select($sql, null, 'column');
				unset($sql);

				if ($country_iso === null ) {
					return;
				}

				if (isset($countries[$country_iso])) {
					$country = $countries[$country_iso];

					//set default country iso code
					$sql = "select count(*) from v_vars ";
					$sql .= "where var_name = 'default_country' ";
					$sql .= "and var_category = 'Defaults' ";
					$database = new database;
					$num_rows = $database->select($sql, null, 'column');
					unset($sql);

					if ($num_rows == 0) {
						$array['vars'][$x]['var_uuid'] = uuid();
						$array['vars'][$x]['var_name'] = 'default_country';
						$array['vars'][$x]['var_value'] = $country["isocode"];
						$array['vars'][$x]['var_category'] = 'Defaults';
						$array['vars'][$x]['var_enabled'] = 'true';
						$array['vars'][$x]['var_order'] = $x;
						$array['vars'][$x]['var_description'] = null;
						$x++;
					}
					unset($num_rows);

					//set default country code
					$sql = "select count(*) from v_vars ";
					$sql .= "where var_name = 'default_countrycode' ";
					$sql .= "and var_category = 'Defaults' ";
					$database = new database;
					$num_rows = $database->select($sql, null, 'column');
					unset($sql);

					if ($num_rows == 0) {
						$array['vars'][$x]['var_uuid'] = uuid();
						$array['vars'][$x]['var_name'] = 'default_countrycode';
						$array['vars'][$x]['var_value'] = $country["countrycode"];
						$array['vars'][$x]['var_category'] = 'Defaults';
						$array['vars'][$x]['var_enabled'] = 'true';
						$array['vars'][$x]['var_order'] = $x;
						$array['vars'][$x]['var_description'] = null;
						$x++;
					}
					unset($num_rows);

					//set default international direct dialing code
					$sql = "select count(*) from v_vars ";
					$sql .= "where var_name = 'default_exitcode' ";
					$sql .= "and var_category = 'Defaults' ";
					$database = new database;
					$num_rows = $database->select($sql, null, 'column');
					unset($sql);

					if ($num_rows == 0) {
						$array['vars'][$x]['var_uuid'] = uuid();
						$array['vars'][$x]['var_name'] = 'default_exitcode';
						$array['vars'][$x]['var_value'] = $country["exitcode"];
						$array['vars'][$x]['var_category'] = 'Defaults';
						$array['vars'][$x]['var_enabled'] = 'true';
						$array['vars'][$x]['var_order'] = $x;
						$array['vars'][$x]['var_description'] = null;
						$x++;
					}
					unset($num_rows, $countries);
				}

				if (is_array($array) && @sizeof($array) != 0) {
					//grant temporary permissions
						$p = new permissions;
						$p->add("var_add", "temp");
					//execute inserts
						$database = new database;
						$database->app_name = 'vars';
						$database->app_uuid = '54e08402-c1b8-0a9d-a30a-f569fc174dd8';
						$database->save($array, false);
						unset($array);
					//revoke temporary permissions
						$p->delete("var_add", "temp");
				}
			}
		}

	//set country code variables
		set_country_vars($db, $x);

	//save the vars.xml file
		save_var_xml();
}

?>
