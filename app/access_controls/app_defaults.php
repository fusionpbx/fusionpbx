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
	Portions created by the Initial Developer are Copyright (C) 2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the number of rows is 0 then read the acl xml into the database
	if ($domains_processed == 1) {

		//add the access control list to the database
		$sql = "select count(*) as num_rows from v_access_controls ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				//find the file
					if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf/autoload_configs')) {
						$xml_file = '/usr/share/examples/fusionpbx/resources/templates/conf/autload_configs/acl.conf.xml';
					}
					elseif (file_exists('/usr/local/share/fusionpbx/resources/templates/conf/autoload_configs')) {
						$xml_file = '/usr/local/share/fusionpbx/resources/templates/conf/autoload_configs/acl.conf.xml';
					}
					else {
						$xml_file = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/conf/autoload_configs/acl.conf.xml';
					}

				//load the xml and save it into an array
					$xml_string = file_get_contents($xml_file);
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
							$sql = "insert into v_access_controls ";
							$sql .= "(";
							$sql .= "access_control_uuid, ";
							$sql .= "access_control_name, ";
							$sql .= "access_control_default ";
							$sql .= ") ";
							$sql .= "values ";
							$sql .= "( ";
							$sql .= "'".$access_control_uuid."', ";
							$sql .= "'".check_str($access_control_name)."', ";
							$sql .= "'".check_str($access_control_default)."' ";
							$sql .= ")";
							//echo $sql."\n";
							$db->exec(check_sql($sql));
							unset($sql);

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
							//replace $${domain}
								if (strlen($node_domain) > 0) {
									$node_domain = str_replace("\$\${domain}", $domain_name, $node_domain);
								}
							//add the profile settings into the database
								$access_control_node_uuid = uuid();
								$sql = "insert into v_access_control_nodes ";
								$sql .= "(";
								$sql .= "access_control_node_uuid, ";
								$sql .= "access_control_uuid, ";
								$sql .= "node_type, ";
								$sql .= "node_cidr, ";
								$sql .= "node_domain ";
								$sql .= ") ";
								$sql .= "values ";
								$sql .= "( ";
								$sql .= "'".$access_control_node_uuid."', ";
								$sql .= "'".$access_control_uuid."', ";
								$sql .= "'".$node_type."', ";
								$sql .= "'".$node_cidr."', ";
								$sql .= "'".$node_domain."' ";
								$sql .= ")";
								//echo $sql."\n";
								$db->exec(check_sql($sql));
						}
				}
				unset($prep_statement);
			}
		}
	}

?>