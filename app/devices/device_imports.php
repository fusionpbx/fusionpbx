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
	Portions created by the Initial Developer are Copyright (C) 2018-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('device_add')) {
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
	if (!function_exists('str_getcsv')) {
		function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
			$fp = fopen("php://memory", 'r+');
			fputs($fp, $input);
			rewind($fp);
			$data = fgetcsv($fp, null, $delimiter, $enclosure, $escape);
			fclose($fp);
			return $data;
		}
	}

//set the max php execution time
	ini_set('max_execution_time',7200);

//get the http get values and set them as php variables
	$action = $_POST["action"] ?? null;
	$from_row = $_POST["from_row"] ?? null;
	$delimiter = $_POST["data_delimiter"] ?? null;
	$enclosure = $_POST["data_enclosure"] ?? null;

//save the data to the csv file
	if (isset($_POST['data'])) {
		$file = $_SESSION['server']['temp']['dir']."/devices-".$_SESSION['domain_name'].".csv";
		if (file_put_contents($file, $_POST['data'])) {
			$_SESSION['file'] = $file;
		}
	}

//copy the csv file
	//$_POST['submit'] == "Upload" &&
	if (!empty($_FILES['ulfile']['tmp_name']) && is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('device_import')) {
		if ($_POST['type'] == 'csv') {
			$file = $_SESSION['server']['temp']['dir']."/devices-".$_SESSION['domain_name'].".csv";
			if (move_uploaded_file($_FILES['ulfile']['tmp_name'], $file)) {
				$_SESSION['file'] = $file;
			}
		}
	}

//get the schema
	if (!empty($delimiter) && file_exists($_SESSION['file'] ?? '')) {
		//get the first line
			$line = fgets(fopen($_SESSION['file'], 'r'));
			$line_fields = explode($delimiter, $line);

		//get the schema
			$x = 0;
			include "app/devices/app_config.php";
			$i = 0;
			foreach ($apps[0]['db'] as $table) {
				//get the table name and parent name
				$table_name = $table["table"]['name'];
				$parent_name = $table["table"]['parent'];

				//remove the v_ table prefix
				if (substr($table_name, 0, 2) == 'v_') {
						$table_name = substr($table_name, 2);
				}
				if (substr($parent_name, 0, 2) == 'v_') {
						$parent_name = substr($parent_name, 2);
				}

				//filter for specific tables and build the schema array
				if ($table_name == "devices" || $table_name == "device_lines" || 
					$table_name == "device_keys" || $table_name == "device_settings") {
					$schema[$i]['table'] = $table_name;
					$schema[$i]['parent'] = $parent_name;
					foreach ($table['fields'] as $row) {
						if (empty($row['deprecated']) || $row['deprecated'] !== 'true') {
							if (is_array($row['name'])) {
								$field_name = $row['name']['text'];
							}
							else {
								$field_name = $row['name'];
							}
							$schema[$i]['fields'][] = $field_name;
						}
					}
					$i++;
				}
			}

			$i++;
			$schema[$i]['table'] = 'devices';
			$schema[$i]['parent'] = '';
			$schema[$i]['fields'][] = 'username';
	}

//match the column names to the field names
	if (!empty($delimiter) && file_exists($_SESSION['file'] ?? '') && $action != 'import') {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: device_imports.php');
				exit;
			}

		//create token
			$object = new token;
			$token = $object->create($_SERVER['PHP_SELF']);

		//include header
			$document['title'] = $text['title-device_import'];
			require_once "resources/header.php";

		//form to match the fields to the column names
			echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

			echo "<div class='action_bar' id='action_bar'>\n";
			echo "	<div class='heading'><b>".$text['header-device_import']."</b></div>\n";
			echo "	<div class='actions'>\n";
			echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'device_imports.php']);
			echo button::create(['type'=>'submit','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'id'=>'btn_save']);
			echo "	</div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo $text['description-import']."\n";
			echo "<br /><br />\n";

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

			//loop through user columns
			$x = 0;
			foreach ($line_fields as $line_field) {
				$line_field = preg_replace('#[^a-zA-Z0-9_]#', '', $line_field);
				echo "<tr>\n";
				echo "	<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				//echo "    ".$text['label-zzz']."\n";
				echo $line_field;
				echo "	</td>\n";
				echo "	<td width='70%' class='vtable' align='left'>\n";
				echo "		<select class='formfld' style='' name='fields[$x]'>\n";
				echo "			<option value=''></option>\n";
				foreach($schema as $row) {
					echo "			<optgroup label='".$row['table']."'>\n";
					foreach($row['fields'] as $field) {
						$selected = '';
						if ($field == $line_field) {
							$selected = "selected='selected'";
						}
						if ($field !== 'domain_uuid') {
							echo "    			<option value='".$row['table'].".".$field."' ".$selected.">".$field."</option>\n";
						}
					}
					echo "			</optgroup>\n";
				}
				echo "    	</select>\n";
				//echo "<br />\n";
				//echo $text['description-zzz']."\n";
				echo "	</td>\n";
				echo "</tr>\n";
				$x++;
			}

			echo "</table>\n";
			echo "<br /><br />\n";

			echo "<input name='action' type='hidden' value='import'>\n";
			echo "<input name='from_row' type='hidden' value='$from_row'>\n";
			echo "<input name='data_delimiter' type='hidden' value='$delimiter'>\n";
			echo "<input name='data_enclosure' type='hidden' value='$enclosure'>\n";
			echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

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

		//end the script
			exit;
	}

//get the parent table
	function get_parent($schema,$table_name) {
		foreach ($schema as $row) {
			if ($row['table'] == $table_name) {
				return $row['parent'];
			}
		}
	}

//upload the csv
	if (file_exists($_SESSION['file'] ?? '') && $action == 'import') {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: device_imports.php');
				exit;
			}

		//user selected fields
			$fields = $_POST['fields'];
			
		//set the domain_uuid
			$domain_uuid = $_SESSION['domain_uuid'];

		//open the database
			$database = new database;
			$database->app_name = 'devices';
			$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';

		//get the users
			$sql = "select * from v_users where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$users = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

		//get the contents of the csv file and convert them into an array
			$handle = @fopen($_SESSION['file'], "r");
			if ($handle) {
				//set the starting identifiers
					$row_id = 0;
					$row_number = 1;

				//loop through the array
					while (($line = fgets($handle, 4096)) !== false) {
						if ($from_row <= $row_number) {
							//format the data
								$y = 0;
								foreach ($fields as $key => $value) {
									//get the line
									$result = str_getcsv($line, $delimiter, $enclosure);

									//get the table and field name
									$field_array = explode(".",$value);
									$table_name = $field_array[0];
									$field_name = $field_array[1];
									//echo "value: $value<br />\n";
									//echo "table_name: $table_name<br />\n";
									//echo "field_name: $field_name<br />\n";
									
									//get the parent table name
									$parent = get_parent($schema, $table_name);

									//count the field names
									if (isset($field_count[$table_name][$field_name])) {
										$field_count[$table_name][$field_name]++;
									}
									else {
										$field_count[$table_name][$field_name] = 0;
									}

									//set the ordinal ID
									$id = $field_count[$table_name][$field_name];

									//remove formatting from the phone number
									if ($field_name == "phone_number") {
										$result[$key] = preg_replace('{\D}', '', $result[$key]);
									}

									//normalize the device address
									if ($field_name == "device_address") {
										$result[$key] = strtolower($result[$key]);
										$result[$key] = preg_replace('#[^a-fA-F0-9./]#', '', $result[$key]);
									}

									//build the data array
									if (!empty($table_name)) {
										if (empty($parent)) {
											if ($field_name != "username") {
												$array[$table_name][$row_id]['domain_uuid'] = $domain_uuid;
												$array[$table_name][$row_id][$field_name] = $result[$key];
											}
										}
										else {
											$array[$parent][$row_id][$table_name][$id]['domain_uuid'] = $domain_uuid;
											$array[$parent][$row_id][$table_name][$id][$field_name] = $result[$key];
										}

										if ($field_name == "username") {
											foreach ($users as $field) {
												if ($field['username'] == $result[$key]) {
													$array[$parent][$table_name][$id]['device_user_uuid'] = $field['user_uuid'];
												}
											}
										}
									}
								}

							// Do not duplicate device addresses, get the device UUID from the database and set it in the array
								if (isset($array['devices']) && !isset($array['devices'][$row_id]['device_uuid']) &&
									isset($array['devices'][$row_id]['device_address'])) {
									$sql = "SELECT device_uuid, domain_uuid FROM v_devices ";
									$sql .= "WHERE device_address = :mac ";
									$parameters['mac'] = $array['devices'][$row_id]['device_address'];
									$row = $database->select($sql, $parameters, 'row');
									if (is_array($row)) {
										// Validate that the hit we got is for the same domain, if not add a message stating the fact
										if ($array['devices'][$row_id]['domain_uuid'] == $row['domain_uuid']) {
											$array['devices'][$row_id]['device_uuid'] = $row['device_uuid'];
										} else {
											// Maybe add in a better new message stating that it was found in a different domain?
											message::add($text['message-duplicate'] . ": " . $parameters['mac']);
											unset($array['devices'][$row_id]);
										}
									}
									unset($sql, $parameters);
								}

							//debug information
								//view_array($field_count);

							//process a chunk of the array
								if ($row_id === 1000) {

									//remove sub table data if it doesn't have more details than domain_uuid an device_uuid
										$x = 0;
										foreach ($array['devices'] as $row) {
											//remove empty device keys
											if (isset($row['device_keys'])) {
												$y = 0;
												foreach ($row['device_keys'] as &$sub_row) {
													if (count($sub_row) == 2) {
														unset($array['devices'][$x]['device_keys']);
													}
													$y++;
												}
											}

											//remove empty device lines
											if (isset($row['device_lines'])) {
												$y = 0;
												foreach ($row['device_lines'] as &$sub_row) {
													if (count($sub_row) == 2) {
														unset($array['devices'][$x]['device_lines']);
													}
													$y++;
												}
											}

											//increment device id
											$x++;
										}

									//save to the data
										$database->save($array);
										//$message = $database->message;

									//clear the array
										unset($array);

									//set the row id back to 0
										$row_id = 0;
								}

						} //if ($from_row <= $row_id)
						unset($field_count);
						$row_number++;
						$row_id++;
					} //end while
					fclose($handle);

				//remove sub table data if it doesn't have more details than domain_uuid an device_uuid
					$x = 0;
					foreach ($array['devices'] as $row) {
						//remove empty device keys
						if (isset($row['device_keys'])) {
							$y = 0;
							foreach ($row['device_keys'] as &$sub_row) {
								if (count($sub_row) == 2) {
									unset($array['devices'][$x]['device_keys']);
								}
								$y++;
							}
						}

						//remove empty device lines
						if (isset($row['device_lines'])) {
							$y = 0;
							foreach ($row['device_lines'] as &$sub_row) {
								if (count($sub_row) == 2) {
									unset($array['devices'][$x]['device_lines']);
								}
								$y++;
							}
						}

						//increment device id
						$x++;
					}

				//debug info
					//view_array($array);

				//save to the data
					if (is_array($array)) {
						$database->save($array);
						//$message = $database->message;
						//view_array($message);
					}
				
					if (!empty($_SESSION['provision']['path']['text'])) {
						$prov = new provision;
						$prov->domain_uuid = $domain_uuid;
						$response = $prov->write();
					}

				//send the redirect header
					header("Location: devices.php");
					return;
			}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-device_import'];
	require_once "resources/header.php";

//show content
	echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-device_import']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'devices.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>$_SESSION['theme']['button_icon_upload'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-import']."\n";
	echo "<br /><br />\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_data']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <textarea name='data' id='data' class='formfld' style='width: 100%; min-height: 150px;' wrap='off'>".($data ?? '')."</textarea>\n";
	echo "<br />\n";
	echo $text['description-import_data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-from_row']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' name='from_row'>\n";
	$i=2;
	while($i<=99) {
		$selected = ($i == $from_row) ? "selected" : null;
		echo "			<option value='$i' ".$selected.">$i</option>\n";
		$i++;
	}
	echo "		</select>\n";
	echo "<br />\n";
	echo $text['description-from_row']."\n";
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
	echo "	".$text['label-import_file_upload']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input name='ulfile' type='file' class='formfld fileinput' id='ulfile'>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>";

	echo "<input name='type' type='hidden' value='csv'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
