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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if there is more than one domain then set the default context to the domain name
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

//only run the following code if the directory exists
	if (is_dir($_SESSION['switch']['dialplan']['dir'])) {
		//write the dialplan/default.xml if it does not exist
			//get the contents of the dialplan/default.xml
				$file_default_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/includes/templates/conf/dialplan/default.xml';
				$file_default_contents = file_get_contents($file_default_path);

			//prepare the file contents and the path
				//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
					$file_default_contents = str_replace("{v_domain}", $context, $file_default_contents);
				//set the file path
					$file_path = $_SESSION['switch']['conf']['dir'].'/dialplan/'.$context.'.xml';

			//write the default dialplan
				if (!file_exists($file_path)) {
					$fh = fopen($file_path,'w') or die('Unable to write to '.$file_path.'. Make sure the path exists and permissons are set correctly.');
					fwrite($fh, $file_default_contents);
					fclose($fh);
				}
	}

//get the $apps array from the installed apps from the core and mod directories
	$xml_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/resources/xml/dialplan/*.xml");
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
			require_once "includes/classes/switch_dialplan.php";
			$dialplan = new dialplan;
			$dialplan->domain_uuid = $domain_uuid;
			$dialplan->dialplan_order = $dialplan_order;
			$dialplan->default_context = $context;
			if ($display_type == "text") {
				$dialplan->display_type = 'text';
			}
			$dialplan->xml = $xml_string;
			$dialplan->import();
	}

?>