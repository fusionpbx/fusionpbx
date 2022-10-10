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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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
	if (!function_exists('str_getcsv')) {
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
	ini_set('max_execution_time', 7200);

//get the http get values and set them as php variables
	$action = $_POST["action"];
	$from_row = $_POST["from_row"];
	$delimiter = $_POST["data_delimiter"];
	$enclosure = $_POST["data_enclosure"];

//save the data to the csv file
	if (isset($_POST['data'])) {
		$file = $_SESSION['server']['temp']['dir']."/contacts-".$_SESSION['domain_name'].".csv";
		file_put_contents($file, $_POST['data']);
		$_SESSION['file'] = $file;
	}

//copy the csv file
	//$_POST['submit'] == "Upload" &&
	if ( is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('contact_upload')) {
		if ($_POST['type'] == 'csv') {
			move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['server']['temp']['dir'].'/'.$_FILES['ulfile']['name']);
			$save_msg = "Uploaded file to ".$_SESSION['server']['temp']['dir']."/". htmlentities($_FILES['ulfile']['name']);
			//system('chmod -R 744 '.$_SESSION['server']['temp']['dir'].'*');
			$file = $_SESSION['server']['temp']['dir'].'/'.$_FILES['ulfile']['name'];
			$_SESSION['file'] = $file;
		}
	}

//get the schema
	if (strlen($delimiter) > 0) {
		//get the first line
			$line = fgets(fopen($_SESSION['file'], 'r'));
			$line_fields = explode($delimiter, $line);

		//get the schema
			$x = 0;
			include "app/contacts/app_config.php";
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
				if ($table_name == "contacts" || $table_name == "contact_addresses" || 
					$table_name == "contact_phones" || $table_name == "contact_emails" || 
					$table_name == "contact_urls") {

					$schema[$i]['table'] = $table_name;
					$schema[$i]['parent'] = $parent_name;
					foreach ($table['fields'] as $row) {
						if ($row['deprecated'] !== 'true') {
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
			$schema[$i]['table'] = 'contact_groups';
			$schema[$i]['parent'] = 'contacts';
			$schema[$i]['fields'][] = 'group_name';
			$i++;
			$schema[$i]['table'] = 'contact_users';
			$schema[$i]['parent'] = 'contacts';
			$schema[$i]['fields'][] = 'username';
	}

//match the column names to the field names
	if (strlen($delimiter) > 0 && file_exists($_SESSION['file']) && $action != 'import') {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: contact_import.php');
				exit;
			}

		//create token
			$object = new token;
			$token = $object->create($_SERVER['PHP_SELF']);

		//include header
			$document['title'] = $text['title-contacts_import'];
			require_once "resources/header.php";

		//form to match the fields to the column names
			echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

			echo "<div class='action_bar' id='action_bar'>\n";
			echo "	<div class='heading'><b>".$text['header-contacts_import']."</b></div>\n";
			echo "	<div class='actions'>\n";
			echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contact_import.php']);
			echo button::create(['type'=>'submit','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'id'=>'btn_save']);
			echo "	</div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo $text['description-contacts_import']."\n";
			echo "<br /><br />\n";

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

			//define phone label options
			if (is_array($_SESSION["contact"]["phone_label"]) && @sizeof($_SESSION["contact"]["phone_label"]) != 0) {
				sort($_SESSION["contact"]["phone_label"]);
				foreach($_SESSION["contact"]["phone_label"] as $row) {
					$label_options[] = "<option value='".$row.">".$row."</option>";
				}
			}
			else {
				$default_labels[] = $text['option-work'];
				$default_labels[] = $text['option-home'];
				$default_labels[] = $text['option-mobile'];
				$default_labels[] = $text['option-main'];
				$default_labels[] = $text['option-fax'];
				$default_labels[] = $text['option-pager'];
				$default_labels[] = $text['option-voicemail'];
				$default_labels[] = $text['option-text'];
				$default_labels[] = $text['option-other'];
				foreach ($default_labels as $default_label) {
					$label_options[] = "<option value='".$default_label."'>".$default_label."</option>";
				}
			}

			//loop through user columns
			$x = 0;
			foreach ($line_fields as $line_field) {
				$line_field = trim(trim($line_field), $enclosure);
				echo "<tr>\n";
				echo "	<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				echo $line_field;
				echo "	</td>\n";
				echo "	<td width='70%' class='vtable' align='left'>\n";
				echo "		<select class='formfld' style='' name='fields[$x]' onchange=\"document.getElementById('labels_$x').style.display = this.options[this.selectedIndex].value == 'contact_phones.phone_number' ? 'inline' : 'none';\">\n";
				echo "			<option value=''></option>\n";
				foreach($schema as $row) {
					echo "			<optgroup label='".$row['table']."'>\n";
					foreach($row['fields'] as $field) {
						//if ($field == 'phone_label') { continue; }
 						//if ($field == 'contact_url') { continue; } // can remove this after field is removed from the table
						$selected = '';
						if ($field == $line_field) {
							$selected = "selected='selected'";
						}
						if (substr($field, -5) != '_uuid') {
							echo "				<option value='".$row['table'].".".$field."' ".$selected.">".$field."</option>\n";
						}
					}
					echo "			</optgroup>\n";
				}
				echo "		</select>\n";
				//echo "		<select class='formfld' style='display: none;' id='labels_$x' name='labels[$x]'>\n";
				//echo 			is_array($label_options) ? implode("\n", $label_options) : null;
				//echo "		</select>\n";
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
	if (file_exists($_SESSION['file']) && $action == 'import') {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: contact_import.php');
				exit;
			}

		//user selected fields, labels
			$fields = $_POST['fields'];
			$labels = $_POST['labels'];
			
		//set the domain_uuid
			$domain_uuid = $_SESSION['domain_uuid'];

		//get the groups
			$sql = "select * from v_groups where domain_uuid is null ";
			$database = new database;
			$groups = $database->select($sql, null, 'all');
			unset($sql);

		//get the users
			$sql = "select * from v_users where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
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
										$result[$key] = preg_replace('{(?!^\+)[\D]}', '', $result[$key]);
									}

									//build the data array
									if (strlen($table_name) > 0) {
										if (strlen($parent) == 0) {
											$array[$table_name][$row_id]['domain_uuid'] = $domain_uuid;
											$array[$table_name][$row_id][$field_name] = $result[$key];
										}
										else {
											if ($field_name != "username" && $field_name != "group_name") {
												$array[$parent][$row_id][$table_name][$id]['domain_uuid'] = $domain_uuid;
												$array[$parent][$row_id][$table_name][$id][$field_name] = $result[$key];
												//if ($field_name == 'phone_number') {
												//	$array[$parent][$row_id][$table_name][$id]['phone_label'] = $labels[$key];
												//}
											}
										}

										if ($field_name == "group_name") {
											foreach ($groups as $field) {
												if ($field['group_name'] == $result[$key]) {
													//$array[$parent][$row_id]['contact_group_uuid'] = uuid();
													$array[$parent][$row_id]['contact_groups'][$id]['domain_uuid'] = $domain_uuid;
													//$array['contact_groups'][$x]['contact_uuid'] = $row['contact_uuid'];
													$array[$parent][$row_id]['contact_groups'][$id]['group_uuid'] = $field['group_uuid'];
												}
											}
										}

										if ($field_name == "username") {
											foreach ($users as $field) {
												if ($field['username'] == $result[$key]) {
													//$array[$parent][$row_id]['contact_users'][$id]['contact_group_uuid'] = uuid();
													$array[$parent][$row_id]['contact_users'][$id]['domain_uuid'] = $domain_uuid;
													//$array['contact_groups'][$x]['contact_uuid'] = $row['contact_uuid'];
													$array[$parent][$row_id]['contact_users'][$id]['user_uuid'] = $field['user_uuid'];
												}
											}
										}
									}
									if (is_array($array[$parent][$row_id])) { $y++; }
								}

							//debug information
								//view_array($field_count);

							//process a chunk of the array
								if ($row_id === 1000) {
									//save to the data
										$database = new database;
										$database->app_name = 'contacts';
										$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
										$database->save($array);

									//clear the array
 										unset($array);

									//set the row id back to 0
										$row_id = 0;
								}

						} //if ($from_row <= $row_number)
						unset($field_count);
						$row_number++;
						$row_id++;
					} //end while
					fclose($handle);

				//debug information
					//view_array($array);

				//save to the data
					if (is_array($array)) {
						$database = new database;
						$database->app_name = 'contacts';
						$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
						$database->save($array);
						unset($array);
					}

				//send the redirect header
					header("Location: contacts.php");
					exit;
			}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-contacts_import'];
	require_once "resources/header.php";

//show content
	echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-contacts_import']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contacts.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>$_SESSION['theme']['button_icon_upload'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-contacts_import']."\n";
	echo "<br /><br />\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_data']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <textarea name='data' id='data' class='formfld' style='width: 100%; min-height: 150px;' wrap='off'>$data</textarea>\n";
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
	echo "			".$text['label-import_file_upload']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "			<input name='ulfile' type='file' class='formfld fileinput' id='ulfile'>\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br />\n";

	if (function_exists('curl_version') && $_SESSION['contact']['google_oauth_client_id']['text'] != '' && $_SESSION['contact']['google_oauth_client_secret']['text'] != '') {
		echo "<a href='contact_import_google.php'><img src='resources/images/icon_gcontacts.png' style='width: 21px; height: 21px; border: none; text-decoration: none; margin-right: 5px;' align='absmiddle'>".$text['header-contacts_import_google']."</a>\n";
	}

	echo "<br />\n";

	echo "<input name='type' type='hidden' value='csv'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
