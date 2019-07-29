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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	$action = $_POST["action"];
	$order_by = $_POST["order_by"];
	$order = $_POST["order"];
	$from_row = $_POST["from_row"];
	$delimiter = $_POST["data_delimiter"];
	$enclosure = $_POST["data_enclosure"];
	$destination_type = $_POST["destination_type"];
	$destination_action = $_POST["destination_action"];
	$destination_context = $_POST["destination_context"];
	$destination_record = $_POST["destination_record"];

//set the defaults
	if (strlen($destination_type) == 0) { $destination_type = 'inbound'; }
	if (strlen($destination_context) == 0) { $destination_context = 'public'; }
	if ($destination_type =="outbound" && $destination_context == "public") { $destination_context = $_SESSION['domain_name']; }
	if ($destination_type =="outbound" && strlen($destination_context) == 0) { $destination_context = $_SESSION['domain_name']; }

//save the data to the csv file
	if (isset($_POST['data'])) {
		$file = $_SESSION['server']['temp']['dir']."/destinations-".$_SESSION['domain_name'].".csv";
		file_put_contents($file, $_POST['data']);
		$_SESSION['file'] = $file;
	}

//copy the csv file
	//$_POST['submit'] == "Upload" &&
	if ( is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('destination_upload')) {
		if ($_POST['type'] == 'csv') {
			move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['server']['temp']['dir'].'/'.$_FILES['ulfile']['name']);
			$save_msg = "Uploaded file to ".$_SESSION['server']['temp']['dir']."/". htmlentities($_FILES['ulfile']['name']);
			//system('chmod -R 744 '.$_SESSION['server']['temp']['dir'].'*');
			unset($_POST['txtCommand']);
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
			include ("app/destinations/app_config.php");
			$i = 0;
			foreach($apps[0]['db'] as $table) {
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
				if ($table_name == "destinations") {
					$schema[$i]['table'] = $table_name;
					$schema[$i]['parent'] = $parent_name;
					foreach($table['fields'] as $row) {
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
	}

//get the parent table
	function get_parent($schema,$table_name) {
		foreach ($schema as $row) {
			if ($row['table'] == $table_name) {
				return $row['parent'];
			}
		}
	}

//upload the destination csv
	if (file_exists($_SESSION['file']) && $action == 'add') {

		//form to match the fields to the column names
			//require_once "resources/header.php";

		//user selected fields
			$fields = $_POST['fields'];
			$domain_uuid = $_POST['domain_uuid'];
			$destination_record = $_POST['destination_record'];
			$destination_type = $_POST['destination_type'];
			$destination_context = $_POST['destination_context'];
			$destination_enabled = $_POST['destination_enabled'];

		//set the domain_uuid
			$domain_uuid = $_SESSION['domain_uuid'];

		//get the contents of the csv file and convert them into an array
			$handle = @fopen($_SESSION['file'], "r");
			if ($handle) {
				//pre-set the numbers
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

									//remove formatting from the phone number
									if ($field_name == "phone_number") {
										$result[$key] = preg_replace('{\D}', '', $result[$key]);
									}

									//build the data array
									if (strlen($table_name) > 0) {
										if (strlen($parent) == 0) {
											$array[$table_name][$row_id]['domain_uuid'] = $domain_uuid;
											$array[$table_name][$row_id][$field_name] = $result[$key];
										}
										else {
											$array[$parent][$row_id][$table_name][$y]['domain_uuid'] = $domain_uuid;
											$array[$parent][$row_id][$table_name][$y][$field_name] = $result[$key];
										}
									}

									//get the destination_number
									if ($key === 'destination_number') { $destination_number = $result[$key]; }
									if ($key === 'destination_description') { $destination_description = $result[$key]; }
									if ($key === 'destination_app') { $destination_app = $result[$key]; echo "destination_app $destination_app\n"; }
									if ($key === 'destination_data') { $destination_data = $result[$key]; echo "destination_data $destination_data\n"; }
								}

							//add the actions
								foreach ($array['destinations'] as $row) {

									//get the values
										$destination_number = $row['destination_number'];
										$destination_app = $row['destination_app'];
										$destination_data = $row['destination_data'];
										$destination_accountcode = $row['destination_accountcode'];
										$destination_cid_name_prefix = $row['destination_cid_name_prefix'];
										$destination_description = $row['destination_description'];

									//convert the number to a regular expression
										$destination_number_regex = string_to_regex($destination_number);

									//add the additional fields
										$dialplan_uuid = uuid();
										$array["destinations"][$row_id]['destination_type'] = $destination_type;
										$array["destinations"][$row_id]['destination_record'] = $destination_record;
										$array["destinations"][$row_id]['destination_context'] = $destination_context;
										$array["destinations"][$row_id]['destination_enabled'] = $destination_enabled;
										$array["destinations"][$row_id]['dialplan_uuid'] = $dialplan_uuid;

									//build the dialplan array
										$array["dialplans"][$row_id]["app_uuid"] = "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4";
										$array["dialplans"][$row_id]["dialplan_uuid"] = $dialplan_uuid;
										$array["dialplans"][$row_id]["domain_uuid"] = $domain_uuid;
										$array["dialplans"][$row_id]["dialplan_name"] = ($dialplan_name != '') ? $dialplan_name : format_phone($destination_number);
										$array["dialplans"][$row_id]["dialplan_number"] = $destination_number;
										$array["dialplans"][$row_id]["dialplan_context"] = $destination_context;
										$array["dialplans"][$row_id]["dialplan_continue"] = "false";
										$array["dialplans"][$row_id]["dialplan_order"] = "100";
										$array["dialplans"][$row_id]["dialplan_enabled"] = $destination_enabled;
										$array["dialplans"][$row_id]["dialplan_description"] = $destination_description;
										$dialplan_detail_order = 10;

									//increment the dialplan detail order
										$dialplan_detail_order = $dialplan_detail_order + 10;

									//set the dialplan detail type
										if (strlen($_SESSION['dialplan']['destination']['text']) > 0) {
											$dialplan_detail_type = $_SESSION['dialplan']['destination']['text'];
										}
										else {
											$dialplan_detail_type = "destination_number";
										}

									//build the xml dialplan
										$array["dialplans"][$row_id]["dialplan_xml"] = "<extension name=\"".$dialplan_name."\" continue=\"false\" uuid=\"".$dialplan_uuid."\">\n";
										$array["dialplans"][$row_id]["dialplan_xml"] .= "	<condition field=\"".$dialplan_detail_type."\" expression=\"".$destination_number_regex."\">\n";
										$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
										$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_uuid=".$_SESSION['domain_uuid']."\" inline=\"true\"/>\n";
										$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_name=".$_SESSION['domain_name']."\" inline=\"true\"/>\n";
										if (strlen($destination_cid_name_prefix) > 0) {
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"effective_caller_id_name=".$destination_cid_name_prefix."#\${caller_id_name}\" inline=\"true\"/>\n";
										}
										if (strlen($destination_record) > 0) {
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"record_path=\${recordings_dir}/\${domain_name}/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}\" inline=\"true\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"record_name=\${uuid}.\${record_ext}\" inline=\"true\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"record_append=true\" inline=\"true\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"record_in_progress=true\" inline=\"true\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"recording_follow_transfer=true\" inline=\"true\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"record_session\" data=\"\${record_path}/\${record_name}\" inline=\"false\"/>\n";
										}
										if (strlen($destination_accountcode) > 0) {
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"accountcode=".$destination_accountcode."\" inline=\"true\"/>\n";
										}
										if (strlen($destination_carrier) > 0) {
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"carrier=".$destination_carrier."\" inline=\"true\"/>\n";
										}
										if (strlen($fax_uuid) > 0) {
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"tone_detect_hits=1\" inline=\"true\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"set\" data=\"execute_on_tone_detect=transfer ".$fax_extension." XML \${domain_name}\" inline=\"true\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"tone_detect\" data=\"fax 1100 r +5000\"/>\n";
											$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"sleep\" data=\"3000\"/>\n";
										}
										$array["dialplans"][$row_id]["dialplan_xml"] .= "		<action application=\"".$destination_app."\" data=\"".$destination_data."\"/>\n";
										$array["dialplans"][$row_id]["dialplan_xml"] .= "	</condition>\n";
										$array["dialplans"][$row_id]["dialplan_xml"] .= "</extension>\n";

									//dialplan details
										if ($_SESSION['destinations']['dialplan_details']['boolean'] == "true") {

											//check the destination number
												$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
												$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
												if (strlen($_SESSION['dialplan']['destination']['text']) > 0) {
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = $_SESSION['dialplan']['destination']['text'];
												}
												else {
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
												}
												$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = $destination_number_regex;
												$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
												$y++;

											//increment the dialplan detail order
												$dialplan_detail_order = $dialplan_detail_order + 10;

											//set the caller id name prefix
												if (strlen($destination_cid_name_prefix) > 0) {
													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = "set";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = "effective_caller_id_name=".$destination_cid_name_prefix."#\${caller_id_name}";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;
												}

											//enable call recordings
												if ($destination_record == "true") {

													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = "answer";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = "";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;

													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = "set";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = "record_path=\${recordings_dir}/\${domain_name}/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;

													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = "set";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = "record_name=\${uuid}.\${record_ext}";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;

													//add a variable
													$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
													$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
													$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "recording_follow_transfer=true";
													$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
													$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;

													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = "record_session";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = "\${record_path}/\${record_name}";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_inline"] = "false";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;
												}

											//set the call accountcode
												if (strlen($destination_accountcode) > 0) {
													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = "set";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=".$destination_accountcode;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;
												}

											//set the call accountcode
												if (strlen($destination_app) > 0 && strlen($destination_data) > 0) {
													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = $destination_app;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = $destination_data;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//increment the dialplan detail order
													$dialplan_detail_order = $dialplan_detail_order + 10;
												}

											//set the detail id back to 0
												$y = 0;

										} //end if
								} //foreach

							//process a chunk of the array
								if ($row_id === 1000) {

									//save to the data
										$database = new database;
										$database->app_name = 'destinations';
										$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
										$database->save($array);
										//$message = $database->message;

									//clear the array
										unset($array);

									//set the row id back to 0
										$row_id = 0;
								}

						}
						$row_number++;
						$row_id++;
					}
					fclose($handle);

				//debug info
					//echo "<pre>\n";
					//print_r($array);
					//echo "</pre>\n";
					//exit;

				//save to the data
					if (is_array($array)) {
						$database = new database;
						$database->app_name = 'destinations';
						$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
						$database->save($array);
						$message = $database->message;
					}

				//send the redirect header
					header("Location: destinations.php");
					return;
			}

		//show the header
			require_once "resources/header.php";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-destinations_import']."</b></td>\n";
			echo "<td width='70%' align='right'>\n";
			echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='/app/destinations/destinations.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
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
			echo "	<th>".$text['label-destination_name']."</th>\n";
			echo "	<th>".$text['label-destination_organization']."</th>\n";
			//echo "	<th>".$text['label-destination_email']."</th>\n";
			echo "	<th>".$text['label-destination_url']."</th>\n";
			echo "</tr>\n";
			if ($results) {
				foreach($results as $row) {
					echo "<tr>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 		escape($row['FirstName'])." ".escape($row['LastName']);
					echo "	</td>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 	escape($row['Company'])."&nbsp;\n";
					echo "	</td>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 		escape($row['EmailAddress'])."&nbsp;\n";
					echo "	</td>\n";
					echo "	<td class='vncell' valign='top' align='left'>\n";
					echo 		escape($row['Web Page'])."&nbsp;\n";
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


//upload the destination csv
	if (file_exists($_SESSION['file']) && $action == 'delete') {

		//form to match the fields to the column names
			//require_once "resources/header.php";

		//user selected fields
			$fields = $_POST['fields'];
			$domain_uuid = $_POST['domain_uuid'];
			$destination_type = $_POST['destination_type'];
			$destination_context = $_POST['destination_context'];
			$destination_enabled = $_POST['destination_enabled'];

		//set the domain_uuid
			$domain_uuid = $_SESSION['domain_uuid'];

		//get the contents of the csv file and convert them into an array
			$handle = @fopen($_SESSION['file'], "r");
			if ($handle) {
				//set the starting identifiers
					$row_id = 0;
					$dialplan_id = 0;
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

									//remove formatting from the phone number
									if ($field_name == "phone_number") {
										$result[$key] = preg_replace('{\D}', '', $result[$key]);
									}

									//build the data array
									if (strlen($table_name) > 0) {
										if (strlen($parent) == 0) {
											$array[$table_name][$row_id]['domain_uuid'] = $domain_uuid;
											$array[$table_name][$row_id][$field_name] = $result[$key];
										}
										else {
											$array[$parent][$row_id][$table_name][$y]['domain_uuid'] = $domain_uuid;
											$array[$parent][$row_id][$table_name][$y][$field_name] = $result[$key];
										}
									}

									//get the destination_number
									if ($key === 'destination_number') { $destination_number = $result[$key]; }
									if ($key === 'destination_uuid') { $destination_uuid = $result[$key]; }
									if ($key === 'dialplan_uuid') { $destination_uuid = $result[$key]; }
								}

							//delete the destinations
								$row_number = 0;
								foreach ($array['destinations'] as $row) {
									//get the values
										$domain_uuid = $row['domain_uuid'];
										$destination_number = $row['destination_number'];

									//get the dialplan uuid
										if (strlen($row['destination_number']) == 0 || strlen($row['dialplan_uuid']) == 0 ) {
											$sql = "select * from v_destinations ";
											$sql .= "where domain_uuid = :domain_uuid ";
											$sql .= "and destination_number = :destination_number; ";
											//echo $sql."<br />\n";
											$parameters['domain_uuid'] = $domain_uuid;
											$parameters['destination_number'] = $destination_number;
											$database = new database;
											$destinations = $database->select($sql, $parameters, 'all');
											$row = $destinations[0];

										//add to the array
											//$array['destinations'][$row_id] = $destinations[0];
											$array['destinations'][$row_id]['destination_uuid'] = $destinations[0]['destination_uuid'];
											if (strlen($row['dialplan_uuid']) > 0) {
												$array['destinations'][$row_id]['dialplan_uuid'] = $destinations[0]['dialplan_uuid'];
												//$array['dialplans'][$row_id]['dialplan_uuid'] = $destinations[0]['dialplan_uuid'];
											}
										}
								} //foreach

						} //if ($from_row <= $row_number)
						$row_number++;

					//process a chunk of the array
						if ($row_id === 1000) {
							//delete the destinations
							$row_number = 0;
							foreach ($array['destinations'] as $row) {
								//delete the dialplan
								if (strlen($row['dialplan_uuid']) > 0) {
									$sql = "delete from v_dialplan_details ";
									$sql .= "where dialplan_uuid = :dialplan_uuid ";
									//echo "$sql<br />\n";
									$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
									$database = new database;
									$database->execute($sql, $parameters);

									$sql = "delete from v_dialplans ";
									$sql .= "where dialplan_uuid = :dialplan_uuid ";
									//echo "$sql<br />\n";
									$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
									$database = new database;
									$database->execute($sql, $parameters);
								}

								//delete the destinations
								if (strlen($row['destination_uuid']) > 0) {
									$sql = "delete from v_destinations ";
									$sql .= "where destination_uuid = :destination_uuid ";
									//echo "$sql<br />\n";
									$parameters['destination_uuid'] = $row['destination_uuid'];
									$database = new database;
									$database->execute($sql, $parameters);
								}
							} //foreach

							//delete to the data
							//$database = new database;
							//$database->app_name = 'destinations';
							//$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
							//$database->delete($array);
							//$message = $database->message;

							//clear the array
							unset($array);

							//set the row id back to 0
							$row_id = 0;
						}

						//increment row id
							$row_id++;
					}
					fclose($handle);

				//delete the remaining destinations
					if ($row_id < 1000) {
						foreach ($array['destinations'] as $row) {
							//delete the dialplan
							if (strlen($row['dialplan_uuid']) > 0) {
								$sql = "delete from v_dialplan_details ";
								$sql .= "where dialplan_uuid = :dialplan_uuid ";
								//echo "$sql<br />\n";
								$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
								$database = new database;
								$database->execute($sql, $parameters);

								$sql = "delete from v_dialplans ";
								$sql .= "where dialplan_uuid = :dialplan_uuid ";
								//echo "$sql<br />\n";
								$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
								$database = new database;
								$database->execute($sql, $parameters);
							}

							//delete the destinations
							if (strlen($row['destination_uuid']) > 0) {
								$sql = "delete from v_destinations ";
								$sql .= "where destination_uuid = :destination_uuid ";
								//echo "$sql<br />\n";
								$parameters['destination_uuid'] = $row['destination_uuid'];
								$database = new database;
								$database->execute($sql, $parameters);
							}
						} //foreach
					}

				//debug info
					//echo "<pre>\n";
					//print_r($array);
					//echo "</pre>\n";
					//exit;

				//save to the data
					//if (is_array($array)) {
					//	$database = new database;
					//	$database->app_name = 'destinations';
					//	$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
					//	$database->delete($array);
					//	//$message = $database->message;
					//}

				//send the redirect header
					header("Location: /app/destinations/destinations.php");
					return;
			}
	}

//match the column names to the field names
	if (strlen($delimiter) > 0 && file_exists($_SESSION['file']) && ($action !== 'add' or $action !== 'delete')) {

		//form to match the fields to the column names
			require_once "resources/header.php";

			echo "<form action='' method='POST' enctype='multipart/form-data' name='frmUpload' onSubmit=''>\n";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

			echo "	<tr>\n";
			echo "	<td valign='top' align='left' nowrap='nowrap'>\n";
			echo "		<b>".$text['header-destination_import']."</b><br />\n";
			echo "	</td>\n";
			echo "	<td valign='top' align='right'>\n";
			echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='/app/destinations/destinations.php'\" value='".$text['button-back']."'>\n";
			echo "		<input name='submit' type='submit' class='btn' id='import' value=\"".$text['button-import']."\">\n";
			echo "	</td>\n";
			echo "	</tr>\n";
			echo "	<tr>\n";
			echo "	<td colspan='2' align='left'>\n";
			echo "		".$text['description-destination_import']."\n";
			echo "	</td>\n";
			echo "	</tr>\n";

			//echo "<tr>\n";
			//echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-destinations_import']."</b></td>\n";
			//echo "<td width='70%' align='right'>\n";
			//echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='/app/destinations/destinations.php'\" value='".$text['button-back']."'>\n";
			//echo "</td>\n";
			//echo "</tr>\n";

			//loop through user columns
			$x = 0;
			foreach ($line_fields as $line_field) {
				$line_field = trim(trim($line_field), $enclosure);
				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				//echo "    ".$text['label-zzz']."\n";
				echo $line_field;
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				echo "				<select class='formfld' style='' name='fields[$x]'>\n";
				echo " 				<option value=''></option>\n";
				foreach($schema as $row) {
					echo "			<optgroup label='".$row['table']."'>\n";
					foreach($row['fields'] as $field) {
						$selected = '';
						if ($field == $line_field) {
							$selected = "selected='selected'";
						}
						if ($field !== 'domain_uuid') {
							echo "    			<option value='".escape($row['table']).".".$field."' ".$selected.">".$field."</option>\n";
						}
					}
					echo "			</optgroup>\n";
				}
				echo "				</select>\n";
				//echo "<br />\n";
				//echo $text['description-zzz']."\n";
				echo "			</td>\n";
				echo "		</tr>\n";
				$x++;
			}

			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-destination_type']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='destination_type' id='destination_type' onchange='type_control(this.options[this.selectedIndex].value);'>\n";
			switch ($destination_type) {
				case "inbound" : 	$selected[1] = "selected='selected'";	break;
				case "outbound" : 	$selected[2] = "selected='selected'";	break;
				case "local" : 	$selected[2] = "selected='selected'";	break;
			}
			echo "	<option value='inbound' ".$selected[1].">".$text['option-inbound']."</option>\n";
			echo "	<option value='outbound' ".$selected[2].">".$text['option-outbound']."</option>\n";
			echo "	<option value='local' ".$selected[3].">".$text['option-local']."</option>\n";
			unset($selected);
			echo "	</select>\n";
			echo "<br />\n";
			echo $text['description-destination_type']."\n";
			echo "</td>\n";
			echo "</tr>\n";
			
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-destination_record']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='destination_record' id='destination_record'>\n";
			echo "	<option value=''></option>\n";
			switch ($destination_record) {
				case "true" : 	$selected[1] = "selected='selected'";	break;
				case "false" : 	$selected[2] = "selected='selected'";	break;
			}
			echo "	<option value='true' ".$selected[1].">".$text['option-true']."</option>\n";
			echo "	<option value='false' ".$selected[2].">".$text['option-false']."</option>\n";
			unset($selected);
			echo "	</select>\n";
			echo "<br />\n";
			echo $text['description-destination_record']."\n";
			echo "</td>\n";
			echo "</tr>\n";

			//if (permission_exists('destination_context')) {
				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				echo "	".$text['label-destination_context']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				echo "	<input class='formfld' type='text' name='destination_context' id='destination_context' maxlength='255' value=\"".escape($destination_context)."\">\n";
				echo "<br />\n";
				echo $text['description-destination_context']."\n";
				echo "</td>\n";
				echo "</tr>\n";
			//}

			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-actions']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='action' id='action'>\n";
			echo "	<option value='add' selected='selected'>".$text['label-add']."</option>\n";
			echo "	<option value='delete'>".$text['label-delete']."</option>\n";
			echo "	</select>\n";
			echo "<br />\n";
			echo $text['description-actions']."\n";
			echo "</td>\n";
			echo "</tr>\n";

			if (permission_exists('destination_domain')) {
				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				echo "	".$text['label-domain']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				echo "    <select class='formfld' name='domain_uuid' id='destination_domain' onchange='context_control();'>\n";
				if (strlen($domain_uuid) == 0) {
					echo "    <option value='' selected='selected'>".$text['select-global']."</option>\n";
				}
				else {
					echo "    <option value=''>".$text['select-global']."</option>\n";
				}
				foreach ($_SESSION['domains'] as $row) {
					if ($row['domain_uuid'] == $domain_uuid) {
						echo "    <option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
					}
					else {
						echo "    <option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
					}
				}
				echo "    </select>\n";
				echo "<br />\n";
				echo $text['description-domain_name']."\n";
				echo "</td>\n";
				echo "</tr>\n";
			}
			else {
				echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";
			}

			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-destination_enabled']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='destination_enabled'>\n";
			switch ($destination_enabled) {
				case "true" :	$selected[1] = "selected='selected'";	break;
				case "false" :	$selected[2] = "selected='selected'";	break;
			}
			echo "	<option value='true' ".$selected[1].">".$text['label-true']."</option>\n";
			echo "	<option value='false' ".$selected[2].">".$text['label-false']."</option>\n";
			unset($selected);
			echo "	</select>\n";
			echo "<br />\n";
			echo $text['description-destination_enabled']."\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "		<tr>\n";
			echo "			<td colspan='2' valign='top' align='right' nowrap='nowrap'>\n";
			echo "				<input name='from_row' type='hidden' value='$from_row'>\n";
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
			//$line = str_replace($delimiter."title".$delimiter, $delimiter."destination_title".$delimiter, $line);
			//$line = str_replace("firstname", "name_given", $line);
			//$line = str_replace("lastname", "name_family", $line);
			//$line = str_replace("company", "organization", $line);
			//$line = str_replace("company", "destination_email", $line);

		//end the script
			exit;
	}

//include the header
	require_once "resources/header.php";

//begin the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "	<td valign='top' align='left' width='30%' nowrap='nowrap'>\n";
	echo "		<b>".$text['header-destination_import']."</b><br />\n";
	echo "		".$text['description-destination_import']."\n";
	echo "	</td>\n";
	echo "	<td valign='top' width='70%' align='right'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='/app/destinations/destinations.php?".$_GET["query_string"]."'\" value='".$text['button-back']."'>\n";
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
	echo "    ".$text['label-from_row']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' name='from_row'>\n";
	$i=1;
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

	if (permission_exists('destination_upload')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "			".$text['label-import_file_upload']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "			<input name='ulfile' type='file' class='formfld fileinput' id='ulfile'>\n";
		echo "<br />\n";
		echo $text['description-import_file_upload']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "	<tr>\n";
	echo "		<td valign='bottom'>\n";
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
