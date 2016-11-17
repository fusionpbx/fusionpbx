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
 KonradSC <konrd@yahoo.com>
 */

//includes
	require_once "root.php";
	require_once "resources/require.php";

//include the device class//
//	require_once "app/wizard/resources/classes/wizard.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('destination_import') && $_GET['import_file'] != '') {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//built in str_getcsv requires PHP 5.3 or higher, this function can be used to reproduct the functionality but requirs PHP 5.1.0 or higher
	if(!function_exists('str_getcsv')) {
		function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
			$fp = fopen("php://memory", 'r+');
			fputs($fp, $input);
			rewind($fp);
			$data = fgetcsv($fp, null, $delimiter, $enclosure); // $escape only got added in 5.3.0
			fclose($fp);
			return $data;
		}
	}

//set the max php execution time
	ini_set(max_execution_time,7200);

	$file = check_str($_GET["import_file"]);
	
//get the contents of the csv file	
	$handle = @fopen($_SESSION['server']['temp']['dir']."/". $file, "r");
	if ($handle) {
		//read the csv file into an array
		$csv = array();
		$header = null;
		$x = 0;
		echo "file is open";
		while(($row = fgetcsv($handle)) !== false){
		    if($header === null){
		        $header = $row;
		        continue;
		    }
		
		    $newRow = array();
		    for($i = 0; $i<count($row); $i++){
		        $newRow[$header[$i]] = $row[$i];
		        //$newRow[Line] = $x + 1;
		    }
		
		    $csv[] = $newRow;
		    $x++;
		}
	}
					
	if (!feof($handle)) {
		echo "Error: Unable to open the file.\n";
	}
	fclose($handle);

//add the dialplan permission
	$p = new permissions;
	$p->add("dialplan_add", 'temp');
	$p->add("dialplan_detail_add", 'temp');
	$p->add("dialplan_edit", 'temp');
	$p->add("dialplan_detail_edit", 'temp');
	
	//cycle through the rows
	foreach ($csv as $key => $csv_row) {
		//set the variables
			$destination_type = $csv_row['destination_type'];
			$destination_number = $_SESSION["destination"]["destination_prefix"]["text"].$csv_row['destination_number'];
			$destination_number_regex = string_to_regex($destination_number);
			$destination_caller_id_name = $csv_row['destination_caller_id_name'];
			$destination_caller_id_number = $csv_row['destination_caller_id_number'];
			$destination_cid_name_prefix = $csv_row['destination_cid_name_prefix'];
			$destination_context = $csv_row['destination_context'];
			$destination_app = $csv_row['destination_app'];
			$destination_data = $csv_row['destination_data'];
			$destination_enabled = strtolower($csv_row['destination_enabled']);
			$destination_description = $csv_row['destination_description'];
			$destination_accountcode = $csv_row['destination_accountcode'];
			$destination_action = $csv_row['destination_action'];
			$destination_uuid = uuid();
			$dialplan_uuid = uuid();
			$domain_name = $_SESSION['domain_name'];
			$domain_uuid = $_SESSION['domain_uuid'];
			$line_number = $csv_row['line'];
	



		
		//build the array
			$i=0;
			//v_destinations
				$array["destinations"][$i]["destination_uuid"] = $destination_uuid;
				$array["destinations"][$i]["domain_uuid"] = $domain_uuid;
				$array["destinations"][$i]["destination_type"] = $destination_type;
				$array["destinations"][$i]["destination_number"] = $destination_number;
				$array["destinations"][$i]["destination_caller_id_name"] = $destination_caller_id_name;
				$array["destinations"][$i]["destination_cid_name_prefix"] = $destination_cid_name_prefix;
				$array["destinations"][$i]["destination_caller_id_number"] = $destination_caller_id_number;
				$array["destinations"][$i]["destination_context"] = $destination_context;
				$array["destinations"][$i]["destination_app"] = $destination_app;
				$array["destinations"][$i]["destination_data"] = $destination_data;
				$array["destinations"][$i]["destination_enabled"] = $destination_enabled;
				$array["destinations"][$i]["destination_description"] = $destination_description;
				$array["destinations"][$i]["destination_accountcode"] = $destination_accountcode;
				$array["destinations"][$i]["dialplan_uuid"] = $dialplan_uuid;
				$array["destinations"][$y]["destination_number_regex"] = $destination_number_regex;
				
				

			//build the dialplan array
				$array["dialplans"][$y]["app_uuid"] = "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4";
				$array["dialplans"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplans"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplans"][$y]["dialplan_name"] = format_phone($destination_number);
				$array["dialplans"][$y]["dialplan_number"] = $destination_number;
				$array["dialplans"][$y]["dialplan_context"] = $destination_context;
				$array["dialplans"][$y]["dialplan_continue"] = "false";
				$array["dialplans"][$y]["dialplan_order"] = "100";
				$array["dialplans"][$y]["dialplan_enabled"] = $destination_enabled;
				$array["dialplans"][$y]["dialplan_description"] = $destination_description;

				$dialplan_detail_order = 10;
	
			//increment the dialplan detail order
				$dialplan_detail_order = $dialplan_detail_order + 10;
	
			//build the condition	
				$dialplan_detail_uuid = uuid();
				$array["dialplan_details"][$y]["dialplan_detail_uuid"] = $dialplan_detail_uuid;
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = $destination_number_regex;
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
				$dialplan_detail_order = $dialplan_detail_order + 10;				
				$y++;
			
			//set the caller id name prefix
				if (strlen($destination_cid_name_prefix) > 0) {
					$dialplan_detail_uuid = uuid();
					$array["dialplan_details"][$y]["dialplan_detail_uuid"] = $dialplan_detail_uuid;
					$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
					$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
					$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					$array["dialplan_details"][$y]["dialplan_detail_data"] = "effective_caller_id_name=".$destination_cid_name_prefix."#\${caller_id_name}";
					$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
					$y++;
	
					//increment the dialplan detail order
					$dialplan_detail_order = $dialplan_detail_order + 10;
				}
		
			//set the call accountcode
				if (strlen($destination_accountcode) > 0) {
					$dialplan_detail_uuid = uuid();
					$array["dialplan_details"][$y]["dialplan_detail_uuid"] = $dialplan_detail_uuid;
					$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
					$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
					$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$array["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					$array["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=".$destination_accountcode;
					$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
					$y++;
	
					//increment the dialplan detail order
					$dialplan_detail_order = $dialplan_detail_order + 10;
				}
			
			//build the transfer action
				$dialplan_detail_uuid = uuid();
				$array["dialplan_details"][$y]["dialplan_detail_uuid"] = $dialplan_detail_uuid;
				$array["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
				$array["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
				$array["dialplan_details"][$y]["dialplan_detail_type"] = "transfer";
				$array["dialplan_details"][$y]["dialplan_detail_data"] = $destination_action. " XML " . $domain_name;
				$array["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
			

			//save to the datbase
				$database = new database;
				$database->app_name = 'destinations';
				$database->app_uuid = null;
				$database->save($array);
				$message = $database->message;
				//echo "<pre>".print_r($message, true)."<pre>\n";
				//exit;
			unset($database,$array,$i);
	
		//end of loop for one line of csv
	}

//remove the temporary permission
	$p->delete("dialplan_add", 'temp');
	$p->delete("dialplan_detail_add", 'temp');
	$p->delete("dialplan_edit", 'temp');
	$p->delete("dialplan_detail_edit", 'temp');

//synchronize the xml config
	save_dialplan_xml();

//clear the cache
	$cache = new cache;
	$cache->delete("dialplan:".$destination_context);


//show the header
	require_once "resources/header.php";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-destination_import_success']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='destination_import.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	".$text['message-input_sucess']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

//show the valid input results
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
	echo "<tr>\n";
	//$first_row = array();
	$first_row = $csv[0];
	foreach ($first_row as $key =>$row) {
		echo "	<th>".$key."</th>\n";
	}
	echo "</tr>\n";
	
	foreach ($csv as $key => $csv_row) {
		echo "<tr>\n";
		
		foreach ($csv_row as $csv_item){
			echo "	<td style='text-align:left' class='vncell' valign='top' align='right'>\n";
			echo 		$csv_item ."&nbsp;\n";
			echo "	</td>\n";
		}
		//echo "	</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	unset($csv);
	require_once "resources/footer.php";

?>
