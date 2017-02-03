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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_add')) {
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
	$action = check_str($_POST["action"]);
	$order_by = check_str($_POST["order_by"]);
	$order = check_str($_POST["order"]);
	$delimiter = check_str($_POST["data_delimiter"]);
	$enclosure = check_str($_POST["data_enclosure"]);

//save the data to the csv file
	if (isset($_POST['data'])) {
		$file = $_SESSION['server']['temp']['dir']."/contacts-".$_SESSION['domain_name'].".csv";
		file_put_contents($file, $_POST['data']);
		$_SESSION['file'] = $file;
	}

//copy the csv file
	if ($_POST['submit'] == "Upload" && is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('contact_upload')) {
		if (check_str($_POST['type']) == 'csv') {
			move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['server']['temp']['dir'].'/'.$_FILES['ulfile']['name']);
			$save_msg = "Uploaded file to ".$_SESSION['server']['temp']['dir']."/". htmlentities($_FILES['ulfile']['name']);
			//system('chmod -R 744 '.$_SESSION['server']['temp']['dir'].'*');
			unset($_POST['txtCommand']);
			$file = $_SESSION['server']['temp']['dir'].'/'.$_FILES['ulfile']['name'];
			$_SESSION['file'] = $file;
		}
	}

//match the column names to the field names
	if (strlen($delimiter) > 0 && file_exists($_SESSION['file']) && $action != 'import') {
		//get the first line
			$line = fgets(fopen($_SESSION['file'], 'r'));
			$line_fields = explode($delimiter, $line);

		//column array
			$fields[] = 'contact_title';
			$fields[] = 'contact_name_given';
			$fields[] = 'contact_name_family';
			$fields[] = 'contact_organization';
			$fields[] = 'phone_number'; 
			$fields[] = 'email_address';
			$fields[] = 'url_address';
			$fields[] = 'contact_note'; 

		//form to match the fields to the column names
			require_once "resources/header.php";

			echo "<form action='contact_import.php' method='POST' enctype='multipart/form-data' name='frmUpload' onSubmit=''>\n";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

			echo "	<tr>\n";
			echo "	<td valign='top' align='left' nowrap='nowrap'>\n";
			echo "		<b>".$text['header-contacts_import']."</b><br />\n";
			echo "	</td>\n";
			echo "	<td valign='top' align='right'>\n";
			echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_import.php'\" value='".$text['button-back']."'>\n";
			echo "		<input name='submit' type='submit' class='btn' id='import' value=\"".$text['button-import']."\">\n";
			echo "	</td>\n";
			echo "	</tr>\n";
			echo "	<tr>\n";
			echo "	<td colspan='2' align='left'>\n";
			echo "		".$text['description-contacts_import']."\n";
			echo "	</td>\n";
			echo "	</tr>\n";

			//echo "<tr>\n";
			//echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-contacts_import']."</b></td>\n";
			//echo "<td width='70%' align='right'>\n";
			//echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contact_import.php'\" value='".$text['button-back']."'>\n";
			//echo "</td>\n";
			//echo "</tr>\n";
			$x = 0;
			foreach ($line_fields as $line_field) {
				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				//echo "    ".$text['label-import_delimiter']."\n";
				echo $line_field;
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				echo "    			<select class='formfld' style='' name='fields[$x]'>\n";
				foreach($fields as $field) {
					echo "    			<option value='$field'>$field</option>\n";
				}
				echo "    			</select>\n";
				//echo "<br />\n";
				//echo $text['description-import_delimiter']."\n";
				echo "			</td>\n";
				echo "		</tr>\n";
				$x++;
			}
			
			echo "		<tr>\n";
			echo "			<td colspan='2' valign='top' align='right' nowrap='nowrap'>\n";
			echo "				<input name='action' type='hidden' value='import'>\n";
			echo "				<input name='data_delimiter' type='hidden' value='$delimiter'>\n";
			echo "				<input name='data_enclosure' type='hidden' value='$enclosure'>\n";
			echo "				<input type='submit' class='btn' id='import' value=\"".$text['button-import']."\">\n";
			echo "			</td>\n";
			echo "		</tr>\n";

			echo "	</table>\n";
			echo "</form>\n";
			require_once "resources/footer.php";

		//normalize the column names
			//$line = strtolower($line);
			//$line = str_replace("-", "_", $line);
			//$line = str_replace($delimiter."title".$delimiter, $delimiter."contact_title".$delimiter, $line);
			//$line = str_replace("firstname", "name_given", $line);
			//$line = str_replace("lastname", "name_family", $line);
			//$line = str_replace("company", "organization", $line);
			//$line = str_replace("company", "contact_email", $line);

		exit;
	}

//upload the contact csv
	if (file_exists($_SESSION['file']) && $action == 'import') {


echo "<pre>\n";
print_r($_POST);
$fields = implode($delimiter, $_POST['fields']);
echo $fields;
echo "</pre>\n";
exit;

		//get the contents of the csv file
			$handle = @fopen($file, "r");
			if ($handle) {
				$x = 0;
				while (($line = fgets($handle, 4096)) !== false) {
					if ($x == 0) {

						//set the variables
							/*
							$contact_title = $data['Title'];
							$contact_name_given = $data['FirstName'];
							$contact_name_family = $data['LastName'];
							$contact_organization = $data['Company'];
							//$contact_email = $data['EmailAddress'];
							$contact_note = $data['Notes'];
							$contact_url = $data['Web Page'];

							$address_array[$x]['address_street'] = $data['BusinessStreet'];
							$address_array[$x]['address_locality'] = $data['BusinessCity'];
							$address_array[$x]['address_region'] = $data['BusinessState'];
							$address_array[$x]['address_postal_code'] = $data['BusinessPostalCode'];
							$address_array[$x]['address_country'] = $data['BusinessCountry'];
							$address_array[$x]['address_type'] = 'work';

							$address_array[$x]['address_street'] = $data['HomeStreet'];
							$address_array[$x]['address_locality'] = $data['HomeCity'];
							$address_array[$x]['address_region'] = $data['HomeState'];
							$address_array[$x]['address_postal_code'] = $data['HomePostalCode'];
							$address_array[$x]['address_country'] = $data['HomeCountry'];
							$address_array[$x]['address_type'] = 'home';

							$address_array[$x]['address_street'] = $data['OtherStreet'];
							$address_array[$x]['address_locality'] = $data['OtherCity'];
							$address_array[$x]['address_region'] = $data['OtherState'];
							$address_array[$x]['address_postal_code'] = $data['OtherPostalCode'];
							$address_array[$x]['address_country'] = $data['OtherCountry'];

							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['BusinessFax']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['BusinessPhone']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['BusinessPhone2']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['CompanyMainPhone']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['HomeFax']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['HomePhone']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['HomePhone2']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['MobilePhone']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['OtherFax']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['OtherPhone']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['Pager']);
							$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['PrimaryPhone']);
							*/

						//set the column array
							$column_array = str_getcsv($line, $delimiter, $enclosure);

echo $line;
print_r($column_array);
exit;
					}
					else {
						//format the data
							$y = 0;
							foreach ($column_array as $column) {
								$result = str_getcsv($line, $delimiter, $enclosure);
								$data[$column] = $result[$y];
								$y++;
							}

						//set the variables
							//$contact_type = '';
							$contact_title = $data['Title'];
							$contact_name_given = $data['FirstName'];
							$contact_name_family = $data['LastName'];
							$contact_organization = $data['Company'];
							//$contact_email = $data['EmailAddress'];
							$contact_note = $data['Notes'];
							$contact_url = $data['Web Page'];

						//add the contact
							$contact_uuid = uuid();
							$sql = "insert into v_contacts ";
							$sql .= "(";
							$sql .= "domain_uuid, ";
							$sql .= "contact_uuid, ";
							$sql .= "contact_type, ";
							$sql .= "contact_organization, ";
							$sql .= "contact_name_given, ";
							$sql .= "contact_name_family, ";
							//$sql .= "contact_nickname, ";
							$sql .= "contact_title, ";
							//$sql .= "contact_role, ";
							$sql .= "contact_url, ";
							//$sql .= "contact_time_zone, ";
							$sql .= "contact_note ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".$_SESSION['domain_uuid']."', ";
							$sql .= "'$contact_uuid', ";
							$sql .= "'$contact_type', ";
							$sql .= "'$contact_organization', ";
							$sql .= "'$contact_name_given', ";
							$sql .= "'$contact_name_family', ";
							//$sql .= "'$contact_nickname', ";
							$sql .= "'$contact_title', ";
							//$sql .= "'$contact_role', ";
							$sql .= "'$contact_url', ";
							//$sql .= "'$contact_time_zone', ";
							$sql .= "'$contact_note' ";
							$sql .= ")";
							$db->exec(check_sql($sql));
							unset($sql);

						//add the contact addresses
							$x=0;
							if (strlen($data['BusinessStreet']) > 0 && strlen($data['BusinessCity']) > 0 && strlen($data['BusinessState']) > 0) {
								$address_array[$x]['address_street'] = $data['BusinessStreet'];
								$address_array[$x]['address_locality'] = $data['BusinessCity'];
								$address_array[$x]['address_region'] = $data['BusinessState'];
								$address_array[$x]['address_postal_code'] = $data['BusinessPostalCode'];
								$address_array[$x]['address_country'] = $data['BusinessCountry'];
								$address_array[$x]['address_type'] = 'work';
								$x++;
							}
							if (strlen($data['HomeStreet']) > 0 && strlen($data['HomeCity']) > 0 && strlen($data['HomeState']) > 0) {
								$address_array[$x]['address_street'] = $data['HomeStreet'];
								$address_array[$x]['address_locality'] = $data['HomeCity'];
								$address_array[$x]['address_region'] = $data['HomeState'];
								$address_array[$x]['address_postal_code'] = $data['HomePostalCode'];
								$address_array[$x]['address_country'] = $data['HomeCountry'];
								$address_array[$x]['address_type'] = 'home';
								$x++;
							}
							if (strlen($data['OtherStreet']) > 0 && strlen($data['OtherCity']) > 0 && strlen($data['OtherState']) > 0) {
								$address_array[$x]['address_street'] = $data['OtherStreet'];
								$address_array[$x]['address_locality'] = $data['OtherCity'];
								$address_array[$x]['address_region'] = $data['OtherState'];
								$address_array[$x]['address_postal_code'] = $data['OtherPostalCode'];
								$address_array[$x]['address_country'] = $data['OtherCountry'];
								$address_array[$x]['address_type'] = 'work';
							}
							foreach ($address_array as $row) {
								$contact_address_uuid = uuid();
								$sql = "insert into v_contact_addresses ";
								$sql .= "(";
								$sql .= "domain_uuid, ";
								$sql .= "contact_uuid, ";
								$sql .= "contact_address_uuid, ";
								$sql .= "address_type, ";
								$sql .= "address_street, ";
								//$sql .= "address_extended, ";
								$sql .= "address_locality, ";
								$sql .= "address_region, ";
								$sql .= "address_postal_code, ";
								$sql .= "address_country ";
								//$sql .= "address_latitude, ";
								//$sql .= "address_longitude ";
								$sql .= ")";
								$sql .= "values ";
								$sql .= "(";
								$sql .= "'".$_SESSION['domain_uuid']."', ";
								$sql .= "'$contact_uuid', ";
								$sql .= "'$contact_address_uuid', ";
								$sql .= "'".$row['address_type']."', ";
								$sql .= "'".$row['address_street']."', ";
								//$sql .= "'$address_extended', ";
								$sql .= "'".$row['address_locality']."', ";
								$sql .= "'".$row['address_region']."', ";
								$sql .= "'".$row['address_postal_code']."', ";
								$sql .= "'".$row['address_country']."' ";
								//$sql .= "'$address_latitude', ";
								//$sql .= "'$address_longitude' ";
								$sql .= ")";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($address_array);

						//add the contact phone numbers
							$x = 0;
							if (strlen($data['BusinessFax']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['BusinessFax']);
								$phone_array[$x]['phone_type_fax'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-fax'];
								$phone_array[$x]['phone_description'] = $text['option-work'];
								$x++;
							}
							if (strlen($data['BusinessPhone']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['BusinessPhone']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-work'];
								$x++;
							}
							if (strlen($data['BusinessPhone2']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['BusinessPhone2']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-work'];
								$x++;
							}
							if (strlen($data['CompanyMainPhone']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['CompanyMainPhone']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-main'];
								$x++;
							}
							if (strlen($data['HomeFax']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['HomeFax']);
								$phone_array[$x]['phone_type_fax'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-fax'];
								$phone_array[$x]['phone_description'] = $text['option-home'];
								$x++;
							}
							if (strlen($data['HomePhone']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['HomePhone']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-home'];
								$x++;
							}
							if (strlen($data['HomePhone2']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['HomePhone2']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-home'];
								$x++;
							}
							if (strlen($data['MobilePhone']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['MobilePhone']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-mobile'];
								$x++;
							}
							if (strlen($data['OtherFax']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['OtherFax']);
								$phone_array[$x]['phone_type_fax'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-fax'];
								$x++;
							}
							if (strlen($data['OtherPhone']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['OtherPhone']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-other'];
								$x++;
							}
							if (strlen($data['Pager']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['Pager']);
								$phone_array[$x]['phone_type_text'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-pager'];
								$x++;
							}
							if (strlen($data['PrimaryPhone']) > 0) {
								$phone_array[$x]['phone_number'] = preg_replace('{\D}', '', $data['PrimaryPhone']);
								$phone_array[$x]['phone_type_voice'] = 1;
								$phone_array[$x]['phone_label'] = $text['option-main'];
								$x++;
							}
							foreach ($phone_array as $row) {
								$contact_phone_uuid = uuid();
								$sql = "insert into v_contact_phones ";
								$sql .= "(";
								$sql .= "domain_uuid, ";
								$sql .= "contact_uuid, ";
								$sql .= "contact_phone_uuid, ";
								$sql .= "phone_type_voice, ";
								$sql .= "phone_type_fax, ";
								$sql .= "phone_type_video, ";
								$sql .= "phone_type_text, ";
								$sql .= "phone_label, ";
								$sql .= "phone_number, ";
								$sql .= "phone_description ";
								$sql .= ")";
								$sql .= "values ";
								$sql .= "(";
								$sql .= "'$domain_uuid', ";
								$sql .= "'$contact_uuid', ";
								$sql .= "'$contact_phone_uuid', ";
								$sql .= (($row['phone_type_voice']) ? 1 : 0).", ";
								$sql .= (($row['phone_type_fax']) ? 1 : 0).", ";
								$sql .= (($row['phone_type_video']) ? 1 : 0).", ";
								$sql .= (($row['phone_type_text']) ? 1 : 0).", ";
								$sql .= "'".$row['phone_label']."', ";
								$sql .= "'".$row['phone_number']."', ";
								$sql .= "'".$row['phone_description']."' ";
								$sql .= ")";
								$db->exec(check_sql($sql));
								unset($sql);
							}
							unset($phone_array);
						//save the results into an array
							$results[] = $data;
						//clear the array
							unset($data);
					}
					//increment $x
						$x++;
				}
				if (!feof($handle)) {
					echo "Error: Unable to open the file.\n";
				}
				fclose($handle);
			}

		//show the header
			require_once "resources/header.php";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-contacts_import']."</b></td>\n";
			echo "<td width='70%' align='right'>\n";
			echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contacts.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left' colspan='2'>\n";
			echo "	".$text['message-results']."<br /><br />\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

		//show the results
			echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
			echo "<tr>\n";
			echo "	<th>".$text['label-contact_name']."</th>\n";
			echo "	<th>".$text['label-contact_organization']."</th>\n";
			//echo "	<th>".$text['label-contact_email']."</th>\n";
			echo "	<th>".$text['label-contact_url']."</th>\n";
			echo "</tr>\n";
			if ($results) {
				foreach($results as $row) {
					echo "<tr>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 		$row['FirstName'] ." ".$row['LastName'];
					echo "	</td>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 	$row['Company']."&nbsp;\n";
					echo "	</td>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 		$row['EmailAddress']."&nbsp;\n";
					echo "	</td>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 		$row['Web Page']."&nbsp;\n";
					echo "	</td>\n";
					echo "</tr>\n";
				}
			}
			echo "</table>\n";

		//include the footer
			require_once "resources/footer.php";

		//end the script
			exit;
	}

//include the header
	require_once "resources/header.php";

//begin the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "	<td valign='top' align='left' width='30%' nowrap='nowrap'>\n";
	echo "		<b>".$text['header-contacts_import']."</b><br />\n";
	echo "		".$text['description-contacts_import']."\n";
	echo "	</td>\n";
	echo "	<td valign='top' width='70%' align='right'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='contacts.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
	//echo "		<input name='submit' type='submit' class='btn' id='import' value=\"".$text['button-import']."\">\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "</table>";

	echo "<br />\n";

	echo "<form action='' method='POST' enctype='multipart/form-data' name='frmUpload' onSubmit=''>\n";
	echo "	<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_data']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea name='data' id='data' rows='7' class='formfld' style='width: 100%;' wrap='off'>$data</textarea>\n";
	echo "<br />\n";
	echo $text['description-import_data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_delimiter']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' style='width:40px;' name='data_delimiter'>\n";
	echo "    <option value=','>,</option>\n";
	echo "    <option value='|'>|</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-import_delimiter']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_enclosure']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' style='width:40px;' name='data_enclosure'>\n";
	echo "    <option value='\"'>\"</option>\n";
	echo "    <option value=''></option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-import_enclosure']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "			".$text['label-import_file_upload']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "			<input name='ulfile' type='file' class='formfld fileinput' id='ulfile'>\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td valign='bottom'>\n";
	if (function_exists('curl_version') && $_SESSION['contact']['google_oauth_client_id']['text'] != '' && $_SESSION['contact']['google_oauth_client_secret']['text'] != '') {
		echo "		<a href='contact_import_google.php'><img src='resources/images/icon_gcontacts.png' style='width: 21px; height: 21px; border: none; text-decoration: none; margin-right: 5px;' align='absmiddle'>".$text['header-contacts_import_google']."</a>\n";
	}
	echo "		</td>\n";
	echo "		<td valign='bottom' align='right' nowrap>\n";
	echo "			<input name='type' type='hidden' value='csv'>\n";
	echo "			<br />\n";
	echo "			<input name='submit' type='submit' class='btn' id='import' value=\"".$text['button-import']."\">\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
