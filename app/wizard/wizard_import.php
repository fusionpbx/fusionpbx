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
	require_once "app/wizard/resources/classes/wizard.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('wizard_import')) {
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

//get the template directory
	$prov = new provision;
	$prov->domain_uuid = $domain_uuid;
	$template_dir = $prov->template_dir;
	$files = glob($template_dir.'/'.$device_template.'/*');
	$templates = scandir($template_dir);

	$start_page = 0;
	
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
				        $newRow[Line] = $x + 1;
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
				$username = $csv_row['Username'];
				$first_name = $csv_row['FirstName'];
				$last_name = $csv_row['LastName'];
				$email = $csv_row['Email'];
				$wizard_template_name = $csv_row['WizardTemplate'];
				$extension = $csv_row['Extension'];
				$mac_address = $csv_row['MAC'];
				$device_template = $csv_row['DeviceTemplate'];
				$device_profile = $csv_row['DeviceProfile'];
				$user_password = $csv_row['Password'];
				$voicemail_pin = $csv_row['PIN'];
				$line_number = $csv_row['Line'];
				$valid_mac =  wizard::normalize_mac($mac_address);
				
				
				//check for duplicate extension in csv
				$u = 0;
				foreach ($csv as $key => $csv_item)
        			if (isset($csv_item['Extension']) && $csv_item['Extension'] == $extension) {
            			$u++;
        			}
        		if($u > 1) {
    				$error_line = "Line " . $line_number . ": " . $extension . " is a duplicate extension in the csv file";
					$error_flag++;
					array_push($error_table, $error_line);	
        		}
				
				//check duplicate extension in database
				$database = new database;
				$database->table = "v_extensions";
				$where[0]['name'] = 'domain_uuid';
				$where[0]['operator'] = '=';
				$where[0]['value'] = $_SESSION["domain_uuid"];
				$where[1]['name'] = 'extension';
				$where[1]['operator'] = '=';
				$where[1]['value'] = "$extension";
				$database->where = $where;
				$result = $database->count();
				if ($result > 0) {
					$error_line = "Line " . $line_number . ": " . $extension . " is a duplicate extension";
					$error_flag++;
					array_push($error_table, $error_line);
				}
				unset($database,$result,$where);

				//check duplicate voicemail
				$database = new database;
				$database->table = "v_voicemails";
				$where[0]['name'] = 'domain_uuid';
				$where[0]['operator'] = '=';
				$where[0]['value'] = $_SESSION["domain_uuid"];
				$where[1]['name'] = 'voicemail_id';
				$where[1]['operator'] = '=';
				$where[1]['value'] = "$extension";
				$database->where = $where;
				$result = $database->count();
				if ($result > 0) {				
					$error_line = "Line " . $line_number . ": " . $extension . " is a duplicate voicemailbox";
					$error_flag++;
					array_push($error_table, $error_line);				
				}
				unset($database,$result,$where);
				
				//check valid mac
				if (!wizard::is_valid_mac($mac_address)) {
					$error_line = "Line " . $line_number . ": " . $mac_address . " is an invalid MAC Address format";
					$error_flag++;
					array_push($error_table, $error_line);
				}
				
				//check duplicate mac in the database across all domains
				$database = new database;
				$database->table = "v_devices";
				$where[0]['name'] = 'device_mac_address';
				$where[0]['operator'] = '=';
				$where[0]['value'] = "$valid_mac";
				$database->where = $where;
				$result = $database->count();				
				if ($result > 0) {				
					$error_line = "Line " . $line_number . ": " . $mac_address . " is a duplicate MAC Address";
					$error_flag++;
					array_push($error_table, $error_line);				
				}				
				unset($database,$result,$where);
				
				//check for duplicate mac address in csv
				$u = 0;
				foreach ($csv as $key => $csv_item)
        			if (isset($csv_item['MAC']) && $csv_item['MAC'] == $mac_address) {
            			$u++;
        			}
        		if($u > 1) {
    				$error_line = "Line " . $line_number . ": " . $mac_address . " is a duplicate extension in the csv file";
					$error_flag++;
					array_push($error_table, $error_line);	
        		}
				
				//check valid email
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$error_line = "Line " . $line_number . ": " . $email . " is invalid";
					$error_flag++;
					array_push($error_table, $error_line);
				}
				
				//check valid device template
				$valid_dir = 0;
				foreach($templates as $dir) {
					if($file != "." && $dir != ".." && $dir[0] != '.') {
						if(is_dir($template_dir . "/" . $dir)) {
							//echo "<optgroup label='$dir'>";
							$dh_sub=$template_dir . "/" . $dir;
							if(is_dir($dh_sub)) {
								$templates_sub = scandir($dh_sub);
								foreach($templates_sub as $dir_sub) {
									if($file_sub != '.' && $dir_sub != '..' && $dir_sub[0] != '.') {
										if(is_dir($template_dir . '/' . $dir .'/'. $dir_sub)) {
											$working_dir = $dir."/".$dir_sub;
											if($device_template == $working_dir) {
												$valid_dir = 1;
											}
										}
									}
								}
							}
						}
					}
				}
				if($valid_dir == 0) {
					$error_line = "Line " . $line_number . ": " . $device_template . " is an invalid device template";
					$error_flag++;
					array_push($error_table, $error_line);	
				}
			
				//check valid device profile
				if($device_profile != '') {
					$database = new database;
					$database->table = "v_device_profiles";
					$where[0]['name'] = 'device_profile_name';
					$where[0]['operator'] = '=';
					$where[0]['value'] = "$device_profile";
					$database->where = $where;
					$result = $database->count();
					if ($result == 0) {
						$error_line = "Line " . $line_number . ": " . $device_profile . " is an invalid device profile";
						$error_flag++;
						array_push($error_table, $error_line);
					}
					unset($database,$result,$where);
				}

				//check for duplicate usernames
				$database = new database;
				$database->table = "v_users";
				$where[0]['name'] = 'domain_uuid';
				$where[0]['operator'] = '=';
				$where[0]['value'] = $_SESSION["domain_uuid"];
				$where[1]['name'] = 'username';
				$where[1]['operator'] = '=';
				$where[1]['value'] = "$username";
				$database->where = $where;
				$result = $database->count();
				if ($result > 0) {
					$error_line = "Line " . $line_number . ": " . $username . " is a duplicate username";
					$error_flag++;
					array_push($error_table, $error_line);
				}
				unset($database,$result,$where);
				
				//check for duplicate contact
				$database = new database;
				$database->table = "v_contacts";
				$where[0]['name'] = 'domain_uuid';
				$where[0]['operator'] = '=';
				$where[0]['value'] = $_SESSION["domain_uuid"];
				$where[1]['name'] = 'contact_nickname';
				$where[1]['operator'] = '=';
				$where[1]['value'] = "$extension";
				$database->where = $where;
				$result = $database->count();
				if ($result > 0) {
					$error_line = "Line " . $line_number . ": " . $extension . " is a duplicate contact";
					$error_flag++;
					array_push($error_table, $error_line);
				}
				unset($database,$result,$where);				
				
				//check for valid wizard template
				$database = new database;
				$database->table = "v_wizard_templates";
				$where[0]['name'] = 'domain_uuid';
				$where[0]['operator'] = '=';
				$where[0]['value'] = $_SESSION["domain_uuid"];
				$where[1]['name'] = 'wizard_template_name';
				$where[1]['operator'] = '=';
				$where[1]['value'] = "$wizard_template_name";
				$database->where = $where;
				$result = $database->count();
				if ($result == 0) {
					$error_line = "Line " . $line_number . ": " . $wizard_template_name . " is an invalid wizard template";
					$error_flag++;
					array_push($error_table, $error_line);
				}
				unset($database,$result,$where);
				
				//unset($csv);	
			}
		
		if($error_flag > 1) {		
		//show the header
			require_once "resources/header.php";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-extensions_import']."</b></td>\n";
			echo "<td width='70%' align='right'>\n";
			echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='wizard_import.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
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
				echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-extensions_import']."</b></td>\n";
				echo "<td width='70%' align='right'>\n";
				echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='wizard_import.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
				echo "	<input type='button' class='btn' name='' alt='".$text['button-submit']."' value='".$text['button-submit']."' onclick=\"window.location='wizard_import_add.php?import_file=".$file_name."';\">\n";
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
		echo "		<b>".$text['header-extension_import']."</b><br />\n";
		echo "		".$text['description-extension_import']."<br />\n";
		echo "		<br />\n";
		echo "		".$text['description-csv_headers']."\n";
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