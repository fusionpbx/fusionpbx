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
	Portions created by the Initial Developer are Copyright (C) 2018-2022
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

//get the http get values and set them as php variables
	$action = $_POST["action"];
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
	if (strlen($from_row) == 0) { $from_row = '2'; }

//save the data to the csv file
	if (isset($_POST['data'])) {
		$file = $_SESSION['server']['temp']['dir']."/destinations-".$_SESSION['domain_name'].".csv";
		file_put_contents($file, $_POST['data']);
		$_SESSION['file'] = $file;
		$_SESSION['file_name'] = $_FILES['ulfile']['name'];
	}

//copy the csv file
	//$_POST['submit'] == "Upload" &&
	if (is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('destination_upload')) {
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

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: destination_imports.php');
				exit;
			}

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

									//build the array
										$actions[0]['destination_app'] = $row['destination_app'];
										$actions[0]['destination_data'] =  $row['destination_data'];
										$destination_actions = json_encode($actions);

									//get the values
										$destination_number = $row['destination_number'];
										$destination_app = $row['destination_app'];
										$destination_data = $row['destination_data'];
										$destination_prefix = $row['destination_prefix'];
										$destination_accountcode = $row['destination_accountcode'];
										$destination_cid_name_prefix = $row['destination_cid_name_prefix'];
										$destination_description = $row['destination_description'];

									//convert the number to a regular expression
										if (isset($destination_prefix) && strlen($destination_prefix) > 0) {
											$destination_number_regex = string_to_regex($destination_number, $destination_prefix);
										}
										else {
											$destination_number_regex = string_to_regex($destination_number);
										}

									//add the additional fields
										$dialplan_uuid = uuid();
										$array["destinations"][$row_id]['destination_actions'] = $destination_actions;
										$array["destinations"][$row_id]['destination_type'] = $destination_type;
										$array["destinations"][$row_id]['destination_record'] = $destination_record;
										$array["destinations"][$row_id]['destination_context'] = $destination_context;
										$array["destinations"][$row_id]['destination_number_regex'] = $destination_number_regex;
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

											//set the destination app and data
												if (strlen($destination_app) > 0 && strlen($destination_data) > 0) {
													$array["dialplans"][$row_id]["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_type"] = $destination_app;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_data"] = $destination_data;
													$array["dialplans"][$row_id]["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
													$y++;

													//set inline to true
													if ($action_app == 'set' || $action_app == 'export') {
														$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = 'true';
													}

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

				//save to the data
					if (is_array($array)) {
						$database = new database;
						$database->app_name = 'destinations';
						$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
						$database->save($array);
						$message = $database->message;
					}

			}

		//send the redirect header
			header("Location: destinations.php?type=".$destination_type);
			exit;

	}

//upload the destination csv
	if (file_exists($_SESSION['file']) && $action == 'delete') {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: destination_imports.php');
				exit;
			}

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
								foreach ($array['destinations'] as $row) {
									//get the values
										$domain_uuid = $row['domain_uuid'];
										$destination_number = $row['destination_number'];

									//get the dialplan uuid
										if (strlen($row['destination_number']) == 0 || !is_uuid($row['dialplan_uuid'])) {
											$sql = "select * from v_destinations ";
											$sql .= "where domain_uuid = :domain_uuid ";
											$sql .= "and destination_number = :destination_number; ";
											$parameters['domain_uuid'] = $domain_uuid;
											$parameters['destination_number'] = $destination_number;
											$database = new database;
											$destinations = $database->select($sql, $parameters, 'all');
											$row = $destinations[0];
											unset($sql, $parameters);

										//add to the array
											//$array['destinations'][$row_id] = $destinations[0];
											$array['destinations'][$row_id]['destination_uuid'] = $destinations[0]['destination_uuid'];
											if (strlen($row['dialplan_uuid']) > 0) {
												$array['destinations'][$row_id]['dialplan_uuid'] = $destinations[0]['dialplan_uuid'];
												//$array['dialplans'][$row_id]['dialplan_uuid'] = $destinations[0]['dialplan_uuid'];
											}
										}
								}

						}
						$row_number++;

					//process a chunk of the array
						if ($row_id === 1000) {
							//delete the destinations
							$row_number = 0;
							foreach ($array['destinations'] as $row) {
								//delete the dialplan
								if (is_uuid($row['dialplan_uuid'])) {
									$sql = "delete from v_dialplan_details ";
									$sql .= "where dialplan_uuid = :dialplan_uuid ";
									$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
									$database = new database;
									$database->execute($sql, $parameters);
									unset($sql, $parameters);

									$sql = "delete from v_dialplans ";
									$sql .= "where dialplan_uuid = :dialplan_uuid ";
									$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
									$database = new database;
									$database->execute($sql, $parameters);
									unset($sql, $parameters);
								}

								//delete the destinations
								if (is_uuid($row['destination_uuid'])) {
									$sql = "delete from v_destinations ";
									$sql .= "where destination_uuid = :destination_uuid ";
									$parameters['destination_uuid'] = $row['destination_uuid'];
									$database = new database;
									$database->execute($sql, $parameters);
									unset($sql, $parameters);
								}
							}

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
							if (is_uuid($row['dialplan_uuid'])) {
								$sql = "delete from v_dialplan_details ";
								$sql .= "where dialplan_uuid = :dialplan_uuid ";
								$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "delete from v_dialplans ";
								$sql .= "where dialplan_uuid = :dialplan_uuid ";
								$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

							//delete the destinations
							if (is_uuid($row['destination_uuid'])) {
								$sql = "delete from v_destinations ";
								$sql .= "where destination_uuid = :destination_uuid ";
								$parameters['destination_uuid'] = $row['destination_uuid'];
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}
						}
					}

				//set response
					message::add($text['message-delete'], 'positive');

				//send the redirect header
					header("Location: /app/destinations/destinations.php?type=".$destination_type);
					exit;
			}
	}

//match the column names to the field names
	if (strlen($delimiter) > 0 && file_exists($_SESSION['file']) && ($action !== 'add' or $action !== 'delete')) {

		//create token
			$object = new token;
			$token = $object->create($_SERVER['PHP_SELF']);

		//include the header
			$document['title'] = $text['title-destination_import'];
			require_once "resources/header.php";

		//form to match the fields to the column names
			echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

			echo "<div class='action_bar' id='action_bar'>\n";
			echo "	<div class='heading'><b>".$text['header-destination_import']."</b></div>\n";
			echo "	<div class='actions'>\n";
			echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'destination_imports.php']);
			echo button::create(['type'=>'submit','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'id'=>'btn_save']);
			echo "	</div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo $text['description-destination_import']."\n";
			echo "<br /><br />\n";

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

			if (isset($_SESSION['file_name']) && strlen($_SESSION['file_name']) > 0) {
				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				echo "		".$text['label-file_name']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				echo "		<b>".$_SESSION['file_name']."</b>\n";
				echo "<br />\n";
				//echo $text['description-file_name']."\n";
				echo "</td>\n";
				echo "</tr>\n";
			}

			//loop through user columns
			$x = 0;
			foreach ($line_fields as $line_field) {
				$line_field = trim(trim($line_field), $enclosure);
				echo "<tr>\n";
				echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
				echo $line_field;
				echo "	</td>\n";
				echo "	<td class='vtable' align='left'>\n";
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
							echo "				<option value='".escape($row['table']).".".$field."' ".$selected.">".escape($field)."</option>\n";
						}
					}
					echo "			</optgroup>\n";
				}
				echo "		</select>\n";
				//echo "<br />\n";
				//echo $text['description-zzz']."\n";
				echo "	</td>\n";
				echo "</tr>\n";
				$x++;
			}

			echo "<tr>\n";
			echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-destination_type']."\n";
			echo "</td>\n";
			echo "<td width='70%' class='vtable' align='left'>\n";
			echo "	<select class='formfld' name='destination_type' id='destination_type' onchange='type_control(this.options[this.selectedIndex].value);'>\n";
			switch ($destination_type) {
				case "inbound" : 	$selected[1] = "selected='selected'";	break;
				case "outbound" : 	$selected[2] = "selected='selected'";	break;
				//case "local" : 	$selected[2] = "selected='selected'";	break;
			}
			echo "	<option value='inbound' ".$selected[1].">".$text['option-inbound']."</option>\n";
			echo "	<option value='outbound' ".$selected[2].">".$text['option-outbound']."</option>\n";
			//echo "	<option value='local' ".$selected[3].">".$text['option-local']."</option>\n";
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

			echo "</table>\n";
			echo "<br /><br />\n";

			echo "<input name='from_row' type='hidden' value='".$from_row."'>\n";
			echo "<input name='data_delimiter' type='hidden' value='".$delimiter."'>\n";
			echo "<input name='data_enclosure' type='hidden' value='".$enclosure."'>\n";
			echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

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

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-destination_import'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-destination_import']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'destinations.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>$_SESSION['theme']['button_icon_upload'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-destination_import']."\n";
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
	echo "    <option value='	'>TAB</option>\n";
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

	echo "</table>\n";
	echo "<br><br>";

	echo "<input name='type' type='hidden' value='csv'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
