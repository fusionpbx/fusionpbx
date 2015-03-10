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

//if there is more than one domain then set the default context to the domain name
	/*
	if (count($_SESSION['domains']) > 1) {
		$sql = "select * from v_dialplans ";
		$sql .= "where dialplan_context = 'default' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$dialplan_uuid = $row["dialplan_uuid"];
			$dialplan_context = $_SESSION['domains'][$domain_uuid]['domain_name'];
			$sql = "update v_dialplans set ";
			$sql .= "dialplan_context = '$dialplan_context' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}
	}
	*/

//remove the global dialplan that calls app.lua dialplan
	if (count($_SESSION['domains']) > 1) {
		//get the dialplan data
			$sql = "select * from v_dialplans ";
			$sql .= "where app_uuid = '34dd307b-fffe-4ead-990c-3d070e288126' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$dialplan_uuid = $row["dialplan_uuid"];
			}
			unset($prep_statement);
		//delete child data
			if (isset($dialplan_uuid)) {
				$sql = "delete from v_dialplan_details ";
				$sql .= "where dialplan_uuid = '".$dialplan_uuid."'; ";
				$db->query($sql);
				unset($sql);
			}
		//delete parent data
			if (isset($dialplan_uuid)) {
				$sql = "delete from v_dialplans ";
				$sql .= "where dialplan_uuid = '".$dialplan_uuid."'; ";
				$db->query($sql);
				unset($sql,$dialplan_uuid);
			}
	}

//only run the following code if the directory exists
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

//get the $apps array from the installed apps from the core and mod directories
	$xml_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/resources/switch/conf/dialplan/*.xml");
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
		//dialplan class
			$dialplan = new dialplan;
			$dialplan->domain_uuid = $domain_uuid;
			$dialplan->dialplan_order = $dialplan_order;
			$dialplan->default_context = $domain_name;
			if ($display_type == "text") {
				$dialplan->display_type = 'text';
			}
			$dialplan->xml = $xml_string;
			$dialplan->import();
	}

//add the global dialplan to inbound routes
	/*
	if ($domains_processed == 1) {
		$sql = "select count(*) as num_rows from v_dialplans ";
		$sql .= "where dialplan_uuid = 'd4e06654-e394-444a-b3af-4c3d54aebbec' ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				$sql = "INSERT INTO v_dialplans ";
				$sql .= "(dialplan_uuid, app_uuid, dialplan_context, dialplan_name, dialplan_continue, dialplan_order, dialplan_enabled) ";
				$sql .= "VALUES ('d4e06654-e394-444a-b3af-4c3d54aebbec', 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4', 'public', 'global', 'true', '0', 'false');";
				$db->query($sql);

				$sql = "INSERT INTO v_dialplan_details ";
				$sql .= "(dialplan_uuid, dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data, dialplan_detail_order) ";
				$sql .= "VALUES ('d4e06654-e394-444a-b3af-4c3d54aebbec', '5e1062d8-6842-4890-a78a-388e8dd5bbaf', 'condition', 'context', 'public', '10');";
				$db->query($sql);

				$sql = "INSERT INTO v_dialplan_details ";
				$sql .= "(dialplan_uuid, dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data, dialplan_detail_order) ";
				$sql .= "VALUES ('d4e06654-e394-444a-b3af-4c3d54aebbec', 'bdafd4aa-6633-48fc-970e-bc2778f3f022', 'action', 'lua', 'app.lua dialplan', '20');";
				$db->query($sql);
				unset($sql);
			}
			unset($prep_statement);
		}
	}
	*/

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
					$sql .= "VALUES ('ea5339de-1982-46ca-9695-c35176165314', 'e391530c-4078-4b49-bc11-bda4a23ad566', 'action', 'log', '[inbound routes] 404 not found \${sip_network_ip}', '20');";
					$db->query($sql);
					unset($sql);
				}
				unset($prep_statement);
			}
		}
	}
	*/
?>