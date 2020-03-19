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

//if the number of rows is 0 then read the acl xml into the database
	if ($domains_processed == 1) {

		//add the access control list to the database
		$sql = "select count(*) from v_access_controls ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows == 0) {
			//set the directory
				$xml_dir = $_SESSION["switch"]["conf"]["dir"].'/autoload_configs';
				$xml_file = $xml_dir."/acl.conf.xml";
				$xml_file_alt = $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/resources/templates/conf/autoload_configs/acl.conf';
			//load the xml and save it into an array
				if (file_exists($xml_file)) {
					$xml_string = file_get_contents($xml_file);
				}
				elseif (file_exists($xml_file_alt)) {
					$xml_string = file_get_contents(xml_file_alt);
				}
				else {
					$xml_string = "<configuration name=\"acl.conf\" description=\"Network Lists\">\n";
					$xml_string .= "	<network-lists>\n";
					$xml_string .= "		<list name=\"lan\" default=\"allow\">\n";
					$xml_string .= "			<node type=\"allow\" cidr=\"192.168.42.42/32\"/>\n";
					$xml_string .= "		</list>\n";
					$xml_string .= "		<list name=\"domains\" default=\"deny\">\n";
					$xml_string .= "			<node type=\"allow\" domain=\"".$_SESSION['domain_name']."\"/>\n";
					$xml_string .= "		</list>\n";
					$xml_string .= "	</network-lists>\n";
					$xml_string .= "</configuration>\n";
				}
				$xml_object = simplexml_load_string($xml_string);
				$json = json_encode($xml_object);
				$conf_array = json_decode($json, true);

			//process the array
				foreach($conf_array['network-lists']['list'] as $list) {
					//get the attributes
						$access_control_name = $list['@attributes']['name'];
						$access_control_default = $list['@attributes']['default'];

					//insert the name, description
						$access_control_uuid = uuid();
						$array['access_controls'][0]['access_control_uuid'] = $access_control_uuid;
						$array['access_controls'][0]['access_control_name'] = $access_control_name;
						$array['access_controls'][0]['access_control_default'] = $access_control_default;

						$p = new permissions;
						$p->add('access_control_add', 'temp');

						$database = new database;
						$database->app_name = 'access_controls';
						$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
						$database->save($array);
						unset($array);

						$p->delete('access_control_add', 'temp');

				//normalize the array - needed because the array is inconsistent when there is only one row vs multiple
					if (strlen($list['node']['@attributes']['type']) > 0) {
						$list['node'][]['@attributes'] = $list['node']['@attributes'];
						unset($list['node']['@attributes']);
					}

				//add the nodes
					foreach ($list['node'] as $row) {
						//get the name and value pair
							$node_type = $row['@attributes']['type'];
							$node_cidr = $row['@attributes']['cidr'];
							$node_domain = $row['@attributes']['domain'];
							$node_description = $row['@attributes']['description'];
						//replace $${domain}
							if (strlen($node_domain) > 0) {
								$node_domain = str_replace("\$\${domain}", $domain_name, $node_domain);
							}
						//add the profile settings into the database
							$access_control_node_uuid = uuid();
							$array['access_control_nodes'][0]['access_control_node_uuid'] = $access_control_node_uuid;
							$array['access_control_nodes'][0]['access_control_uuid'] = $access_control_uuid;
							$array['access_control_nodes'][0]['node_type'] = $node_type;
							$array['access_control_nodes'][0]['node_cidr'] = $node_cidr;
							$array['access_control_nodes'][0]['node_domain'] = $node_domain;
							$array['access_control_nodes'][0]['node_description'] = $node_description;

							$p = new permissions;
							$p->add('access_control_node_add', 'temp');

							$database = new database;
							$database->app_name = 'access_controls';
							$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
							$database->save($array);
							unset($array);

							$p->delete('access_control_node_add', 'temp');
					}
				}

			//rename the file
				if (file_exists($xml_dir.'/acl.conf.xml')) {
					rename($xml_dir.'/acl.conf.xml', $xml_dir.'/acl.conf');
				}
		}
		unset($sql, $num_rows);

	}

?>
