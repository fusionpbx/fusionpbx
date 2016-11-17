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

//include the class
//	require_once "app/wizard/resources/classes/wizard.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('destination_import')) {
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

//get the http get values and set them as php variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);
	$delimiter = check_str($_GET["data_delimiter"]);
	$enclosure = check_str($_GET["data_enclosure"]);

//upload the user csv
	if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
		$start_page = 1;

		//copy the csv file
			if (check_str($_POST['type']) == 'csv') {
				$file_name = $_FILES['ulfile']['name'];
				move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['server']['temp']['dir'].'/'.$_FILES['ulfile']['name']);
				$save_msg = "Uploaded file to ".$_SESSION['server']['temp']['dir']."/". htmlentities($_FILES['ulfile']['name']);
				//system('chmod -R 744 '.$_SESSION['server']['temp']['dir'].'*');
				unset($_POST['txtCommand']);
			}
		//get the contents of the csv file	
			$handle = @fopen($_SESSION['server']['temp']['dir']."/". $_FILES['ulfile']['name'], "r");
			if ($handle) {
				//read the csv file into an array
				$csv = array();
				$header = null;
				$x = 0;
				while(($row = fgetcsv($handle)) !== false){
				    if($header === null){
				        $header = $row;
				        continue;
				    }
				
				    $newRow = array();
				    for($i = 0; $i<count($row); $i++){
				        $newRow[line] = $x + 1;
				        $newRow[$header[$i]] = $row[$i];
				    }
				
				    $csv[] = $newRow;
				    $x++;
				}
			}
				
			if (!feof($handle)) {
				echo "Error: Unable to open the file.\n";
			}
			fclose($handle);
				
			//loop through the array and check for errors	
			$error_flag = 0;
			$error_table = array();
			foreach ($csv as $key => $csv_row) {
				$destination_type = $csv_row['destination_type'];
				$destination_number = $csv_row['destination_number'];
				$destination_caller_id_name = $csv_row['destination_caller_id_name'];
				$destination_caller_id_number = $csv_row['destination_caller_id_number'];
				$destination_cid_name_prefix = $csv_row['destination_cid_name_prefix'];
				$destination_context = $csv_row['destination_context'];
				$destination_app = $csv_row['destination_app'];
				$destination_data = $csv_row['destination_data'];
				$destination_enablede = $csv_row['destination_enabled'];
				$destination_description = $csv_row['destination_description'];
				$destination_accountcode = $csv_row['destination_accountcode'];
				$destination_action = $csv_row['destination_action'];
				$line_number = $csv_row['line'];

				
				
				//check for duplicate destination_number in csv
				$u = 0;
				foreach ($csv as $key => $csv_item)
        			if (isset($csv_item['destination_number']) && $csv_item['destination_number'] == $destination_number) {
            			$u++;
        			}
        		if($u > 1) {
    				$error_line = "Line " . $line_number . ": " . $destination_number . " is a duplicate destination_number in the csv file";
					$error_flag++;
					array_push($error_table, $error_line);	
        		}
				
				//check duplicate destination_number in database
				$database = new database;
				$database->table = "v_destinations";
				$where[0]['name'] = 'domain_uuid';
				$where[0]['operator'] = '=';
				$where[0]['value'] = $_SESSION["domain_uuid"];
				$where[1]['name'] = 'destination_number';
				$where[1]['operator'] = '=';
				$where[1]['value'] = "$destination_number";
				$database->where = $where;
				$result = $database->count();
				if ($result > 0) {
					$error_line = "Line " . $line_number . ": " . $destination_number . " is a duplicate destination_number";
					$error_flag++;
					array_push($error_table, $error_line);
				}
				unset($database,$result,$where);
			}
		
		if($error_flag > 1) {		
		//show the header
			require_once "resources/header.php";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-destinations_import']."</b></td>\n";
			echo "<td width='70%' align='right'>\n";
			echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='destination_import.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left' colspan='2'>\n";
			echo "	".$text['message-errors']."<br /><br />\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
	
		//show the error results
			echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
			echo "<tr>\n";
			echo "	<th>".$error_flag." ".$text['label-error_import']."</th>\n";
			echo "</tr>\n";
			foreach($error_table as $row) {
				echo "<tr>\n";
				echo "	<td style='text-align:left' class='vncell' valign='top' align='left'>\n";
				echo 		$row ."&nbsp;\n";
				echo "	</td>\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			require_once "resources/footer.php";
		//end the script
			//break;
		}		
		else {
			//show the header
				require_once "resources/header.php";
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "<tr>\n";
				echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-destinations_import']."</b></td>\n";
				echo "<td width='70%' align='right'>\n";
				echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='destination_import.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
				echo "	<input type='button' class='btn' name='' alt='".$text['button-submit']."' value='".$text['button-submit']."' onclick=\"window.location='destination_import_add.php?import_file=".$file_name."';\">\n";
				echo "</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "<td align='left' colspan='2'>\n";
				echo "	".$text['message-input']."<br /><br />\n";
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
				//echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
				require_once "resources/footer.php";
		}
	}	 

//begin the Start Page content
	if($start_page == 0) {
		require_once "resources/header.php";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "	<td valign='top' align='left' width='30%' nowrap='nowrap'>\n";
		echo "		<b>".$text['header-destinations_import']."</b><br />\n";
		echo "		".$text['description-destinations_import']."<br />\n";
		echo "		<br />\n";
		//echo "		".$text['description-csv_headers']."<br />\n";
		echo "		".$text['description-csv_header_destination_type']."<br />\n";
		echo "		".$text['description-csv_header_destination_number']."<br />\n";
		echo "		".$text['description-csv_header_destination_caller_id_name']."<br />\n";
		echo "		".$text['description-csv_header_destination_caller_id_number']."<br />\n";
		echo "		".$text['description-csv_header_destination_cid_name_prefix']."<br />\n";
		echo "		".$text['description-csv_header_destination_context']."<br />\n";
		echo "		".$text['description-csv_header_destination_app']."<br />\n";
		echo "		".$text['description-csv_header_destination_data']."<br />\n";
		echo "		".$text['description-csv_header_destination_enabled']."<br />\n";
		echo "		".$text['description-csv_header_destination_description']."<br />\n";
		echo "		".$text['description-csv_header_destination_accountcode']."<br />\n";
		echo "		".$text['description-csv_header_destination_action']."<br />\n";
		echo "	</td>\n";
		echo "	<td valign='top' width='70%' align='right'>\n";
		echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"javascript:history.back();\" value='".$text['button-back']."'>\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "</table>";

		echo "<br />\n";

		echo "<form action='' method='POST' enctype='multipart/form-data' name='frmUpload' onSubmit=''>\n";
		echo "	<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";

		

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' width='10%' nowrap='nowrap'>\n";
		echo "			".$text['label-import_file_upload']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "			<input name='ulfile' type='file' class='formfld fileinput' id='ulfile'>\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "	<tr>\n";
		echo "		<td valign='bottom'>\n";
		echo "		</td>\n";
		echo "		<td valign='bottom' align='right' nowrap>\n";
		echo "			<input name='type' type='hidden' value='csv'>\n";
		echo "			<br />\n";
		echo "			<input name='submit' type='submit' class='btn' id='upload' value=\"".$text['button-upload']."\">\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "<br><br>";
		echo "</form>";

	//include the footer
		require_once "resources/footer.php";
	}
?>
