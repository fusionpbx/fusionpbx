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

//only run the following code if the directory exists
	/*
	if (is_dir($_SESSION['switch']['dialplan']['dir'])) {
		//write the dialplan/default.xml if it does not exist
			//set the path
				if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
					$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
				}
				else {
					$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/conf';
				}

			//get the contents of the dialplan/default.xml
				$file_default_path = $path.'/dialplan/default.xml';
				$file_default_contents = file_get_contents($file_default_path);

			//prepare the file contents and the path
				//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
					$file_default_contents = str_replace("{v_domain}", $context, $file_default_contents);
				//set the file path
					$file_path = $_SESSION['switch']['conf']['dir'].'/dialplan/'.$context.'.xml';

			//write the default dialplan
				if (!file_exists($file_path)) {
					$fh = fopen($file_path,'w') or die('Unable to write to '.$file_path.'. Make sure the path exists and permissions are set correctly.');
					fwrite($fh, $file_default_contents);
					fclose($fh);
				}
	}
	*/

//get the $apps array from the installed apps from the core and mod directories
	if ($domains_processed == 1) {
		//get the array of xml files
			$xml_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/resources/switch/conf/dialplan/*.xml");

		//dialplan class
			$dialplan = new dialplan;

		//process the xml files
			foreach ($xml_list as &$xml_file) {
				//get and parse the xml
					$xml_string = file_get_contents($xml_file);
				//get the order number prefix from the file name
					$name_array = explode('_', basename($xml_file));
					if (is_numeric($name_array[0])) {
						$dialplan_order = $name_array[0];
					}
					else {
						$dialplan_order = 0;
					}
					$dialplan->dialplan_order = $dialplan_order;
					if ($display_type == "text") {
						$dialplan->display_type = 'text';
					}
					$dialplan->xml = $xml_string;
					$dialplan->import();
			}

		//update the dialplan order
			$sql = "update v_dialplans set dialplan_order = '870' where dialplan_order = '980' and dialplan_name = 'cidlookup';\n";
			$db->query($sql);
			$sql = "update v_dialplans set dialplan_order = '880' where dialplan_order = '990' and dialplan_name = 'call_screen';\n";
			$db->query($sql);
			$sql = "update v_dialplans set dialplan_order = '890' where dialplan_order = '999' and dialplan_name = 'local_extension';\n";
			$db->query($sql);
			unset($sql);

		//set empty strings to null
			$sql = "update v_device_lines set outbound_proxy_primary = null where outbound_proxy_primary = '';\n";
			$db->query($sql);
			$sql = "update v_device_lines set outbound_proxy_secondary = null where outbound_proxy_secondary = '';\n";
			$db->query($sql);
			unset($sql);
	}

//add xml for each dialplan where the dialplan xml is empty
	if ($domains_processed == 1) {
		$dialplans = new dialplan;
		$dialplans->source = "details";
		$dialplans->destination = "database";
		$dialplans->is_empty = "dialplan_xml";
		$array = $dialplans->xml();
		//print_r($array);
	}

//add not found dialplan to inbound routes
	/*
	if ($domains_processed == 1) {
		if (is_readable($_SESSION['switch']['dialplan']['dir'])) {
			$sql = "select count(*) as num_rows from v_dialplans ";
			$sql .= "where dialplan_uuid = 'ea5339de-1982-46ca-9695-c35176165314' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] == 0) {
					$sql = "INSERT INTO v_dialplans ";
					$sql .= "(dialplan_uuid, app_uuid, dialplan_context, dialplan_name, dialplan_continue, dialplan_order, dialplan_enabled) ";
					$sql .= "VALUES ('ea5339de-1982-46ca-9695-c35176165314', 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4', 'public', 'not-found', 'false', '999', 'false');";
					$db->query($sql);

					$sql = "INSERT INTO v_dialplan_details ";
					$sql .= "(dialplan_uuid, dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data, dialplan_detail_order) ";
					$sql .= "VALUES ('ea5339de-1982-46ca-9695-c35176165314', '8a21744d-b381-4cb0-9930-55b776e4e461', 'condition', 'context', 'public', '10');";
					$db->query($sql);

					$sql = "INSERT INTO v_dialplan_details ";
					$sql .= "(dialplan_uuid, dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data, dialplan_detail_order) ";
					$sql .= "VALUES ('ea5339de-1982-46ca-9695-c35176165314', 'e391530c-4078-4b49-bc11-bda4a23ad566', 'action', 'log', 'WARNING [inbound routes] 404 not found \${sip_network_ip}', '20');";
					$db->query($sql);
					unset($sql);
				}
				unset($prep_statement);
			}
		}
	}
	*/

?>
