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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('destination_add') || permission_exists('destination_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$destination_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the type
	switch ($_GET['type']) {
		case 'inbound': $destination_type = 'inbound'; break;
		case 'outbound': $destination_type = 'outbound'; break;
		case 'local': $destination_type = 'local'; break;
		default: $destination_type = 'inbound';
	}

//get total destination count from the database, check limit, if defined
	if (!permission_exists('destination_domain')) {
		if ($action == 'add') {
			if ($_SESSION['limit']['destinations']['numeric'] != '') {
				$sql = "select count(*) from v_destinations where domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$database = new database;
				$total_destinations = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

				if ($total_destinations >= $_SESSION['limit']['destinations']['numeric']) {
					message::add($text['message-maximum_destinations'].' '.$_SESSION['limit']['destinations']['numeric'], 'negative');
					header('Location: destinations.php');
					exit;
				}
			}
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//get the uuid
			if ($action == "update" && is_uuid($_POST["destination_uuid"])) {
				$destination_uuid = $_POST["destination_uuid"];
			}

		//set the variables
			$dialplan_uuid = $_POST["dialplan_uuid"];
			$domain_uuid = $_POST["domain_uuid"];
			$destination_type = $_POST["destination_type"];
			$destination_condition_field = $_POST["destination_condition_field"];
			$destination_number = $_POST["destination_number"];
			$destination_prefix = $_POST["destination_prefix"];
			$destination_trunk_prefix = $_POST["destination_trunk_prefix"];
			$destination_area_code = $_POST["destination_area_code"];
			$db_destination_number = $_POST["db_destination_number"];
			$destination_caller_id_name = $_POST["destination_caller_id_name"];
			$destination_caller_id_number = $_POST["destination_caller_id_number"];
			$destination_cid_name_prefix = $_POST["destination_cid_name_prefix"];
			$destination_context = $_POST["destination_context"];
			$fax_uuid = $_POST["fax_uuid"];
			$provider_uuid = $_POST["provider_uuid"];
			$user_uuid = $_POST["user_uuid"];
			$destination_order= $_POST["destination_order"];
			$destination_enabled = $_POST["destination_enabled"];
			$destination_description = $_POST["destination_description"];
			$destination_sell = check_float($_POST["destination_sell"]);
			$currency = $_POST["currency"];
			$destination_buy = check_float($_POST["destination_buy"]);
			$currency_buy = $_POST["currency_buy"];
			$destination_hold_music = $_POST["destination_hold_music"];
			$destination_record = $_POST["destination_record"];
			$destination_accountcode = $_POST["destination_accountcode"];
			$destination_type_voice = $_POST["destination_type_voice"];
			$destination_type_fax = $_POST["destination_type_fax"];
			$destination_type_text = $_POST["destination_type_text"];
			$destination_type_emergency = $_POST["destination_type_emergency"];
			$destination_carrier = $_POST["destination_carrier"];

		//get the destination app and data
			$destination_action_array = explode(":", $_POST["destination_action"], 2);
			$destination_app = $destination_action_array[0];
			$destination_data = $destination_action_array[1];

		//get the alternate destination app and data
			$destination_alternate_action_array = explode(":", $_POST["destination_alternate_action"], 2);
			$destination_alternate_app = $destination_alternate_action_array[0];
			$destination_alternate_data = $destination_alternate_action_array[1];
	}

//process the http post
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//initialize the destinations object
			$destination = new destinations;
			if (permission_exists('destination_domain') && is_uuid($domain_uuid)) {
				$destination->domain_uuid = $domain_uuid;
			}

		//set the default context
			if ($destination_type =="inbound" && strlen($destination_context) == 0) {
				$destination_context = 'public';
			}
			if ($destination_type =="outbound" && strlen($destination_context) == 0) {
				$destination_context = $_SESSION['domain_name'];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: destinations.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($destination_type) == 0) { $msg .= $text['message-required']." ".$text['label-destination_type']."<br>\n"; }
			//if (strlen($destination_number) == 0) { $msg .= $text['message-required']." ".$text['label-destination_number']."<br>\n"; }
			if (strlen($destination_context) == 0) { $msg .= $text['message-required']." ".$text['label-destination_context']."<br>\n"; }
			if (strlen($destination_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-destination_enabled']."<br>\n"; }

		//check for duplicates
			if ($destination_type == 'inbound' && $destination_number != $db_destination_number && $_SESSION['destinations']['unique']['boolean'] == 'true') {
				$sql = "select count(*) from v_destinations ";
				$sql .= "where (destination_number = :destination_number or destination_prefix || destination_number = :destination_number) ";
				$sql .= "and destination_type = 'inbound' ";
				$parameters['destination_number'] = $destination_number;
				$database = new database;
				$num_rows = $database->select($sql, $parameters, 'column');
				if ($num_rows > 0) {
					$msg .= $text['message-duplicate']."<br>\n";
				}
				unset($sql, $parameters, $num_rows);
			}

		//show the message
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//get the uuid
			if ($action == "update" && is_uuid($_POST["destination_uuid"])) {
				$destination_uuid = $_POST["destination_uuid"];
			}

		//get the destination row values
			if ($action == 'update' && is_uuid($destination_uuid)) {
				$sql = "select * from v_destinations ";
				$sql .= "where destination_uuid = :destination_uuid ";
				$parameters['destination_uuid'] = $destination_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				unset($sql, $parameters);
			}

		//get the destination settings from the database
			if (is_array($row) && @sizeof($row) != 0) {
				//get the dialplan_uuid from the database
				$dialplan_uuid = $row["dialplan_uuid"];

				//if the destination_number is not set then get it from the database
				if (!isset($destination_number)) {
					$destination_prefix = $row["destination_prefix"];
					$destination_number = $row["destination_number"];
				}
			}

		//if the user doesn't have the correct permission then 
		//override destination_number and destination_context values
			if (is_array($row) && @sizeof($row) != 0) {
				if (!permission_exists('destination_trunk_prefix')) {
					$destination_trunk_prefix = $row["destination_trunk_prefix"];
				}
				if (!permission_exists('destination_area_code')) {
					$destination_area_code = $row["destination_area_code"];
				}
				if (!permission_exists('destination_number')) {
					$destination_prefix = $row["destination_prefix"];
					$destination_number = $row["destination_number"];
				}
				if (!permission_exists('destination_condition_field')) {
					$destination_condition_field = $row["destination_condition_field"];
				}
				if (!permission_exists('destination_context')) {
					$destination_context = $row["destination_context"];
				}
			}
			unset($row);

		//build the destination_numbers array
			$array = explode('-', $destination_number);
			$array = array_map('trim', $array);
			if (count($array) == 2 && is_numeric($array[0]) && is_numeric($array[1])) {
				$destination_numbers = range($array[0], $array[1]);
				$destination_number_range = true;
			}
			elseif (stristr($destination_number, 'n') || stristr($destination_number, 'x') || stristr($destination_number, 'z')) {
				//n = 2-9, x = 0-9, z = 1-9
				$destination_start = $destination_number;
				$destination_end = $destination_number;
				$destination_start = str_ireplace("n", "2", $destination_start);
				$destination_end = str_ireplace("n", "9", $destination_end);
				$destination_start = str_ireplace("x", "0", $destination_start);
				$destination_end = str_ireplace("x", "9", $destination_end);
				$destination_start = str_ireplace("z", "1", $destination_start);
				$destination_end = str_ireplace("z", "9", $destination_end);
				$destination_numbers = range($destination_start, $destination_end);
				$destination_number_range = true;
			}
			else {
				//$destination_numbers[] = $destination_number;
				$destination_numbers = $array;
				$destination_number_range = false;
			}
			unset($array);

		//save the inbound destination and add the dialplan for the inbound route
			if ($destination_type == 'inbound' || $destination_type == 'local') {

				//get the array
					$dialplan_details = $_POST["dialplan_details"];

				//array cleanup
					if (is_array($dialplan_details)) {
						foreach ($dialplan_details as $index => $row) {
							//unset the empty row
							if (strlen($row["dialplan_detail_data"]) == 0) {
								unset($dialplan_details[$index]);
							}
						}
					}

				//get the fax information
					if (is_uuid($fax_uuid)) {
						$sql = "select * from v_fax ";
						$sql .= "where fax_uuid = :fax_uuid ";
						//if (!permission_exists('destination_domain')) {
						//	$sql .= "and domain_uuid = :domain_uuid ";
						//}
						$parameters['fax_uuid'] = $fax_uuid;
						//$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$row = $database->select($sql, $parameters, 'row');
						if (is_array($row) && @sizeof($row) != 0) {
							$fax_extension = $row["fax_extension"];
							$fax_destination_number = $row["fax_destination_number"];
							$fax_name = $row["fax_name"];
							$fax_email = $row["fax_email"];
							$fax_pin_number = $row["fax_pin_number"];
							$fax_caller_id_name = $row["fax_caller_id_name"];
							$fax_caller_id_number = $row["fax_caller_id_number"];
							$fax_forward_number = $row["fax_forward_number"];
							$fax_description = $row["fax_description"];
						}
						unset($sql, $parameters, $row);
					}

				//add the destinations and asscociated dialplans
					$x = 0;
					foreach($destination_numbers as $destination_number) {

						//convert the number to a regular expression
							if (isset($destination_prefix) && strlen($destination_prefix) > 0) {
								$destination_numbers['destination_prefix'] = $destination_prefix;
							}
							if (isset($destination_trunk_prefix) && strlen($destination_trunk_prefix) > 0) {
								$destination_numbers['destination_trunk_prefix'] = $destination_trunk_prefix;
							}
							if (isset($destination_area_code) && strlen($destination_area_code) > 0) {
								$destination_numbers['destination_area_code'] = $destination_area_code;
							}
							if (isset($destination_number) && strlen($destination_number) > 0) {
								$destination_numbers['destination_number'] = $destination_number;
							}
							$destination = new destinations;
							$destination_number_regex = $destination->to_regex($destination_numbers);
							unset($destination_numbers);

						//if empty then get new uuid
							if (!is_uuid($destination_uuid)) {
								$destination_uuid = uuid();
							}
							if (!is_uuid($dialplan_uuid)) {
								$dialplan_uuid = uuid();
							}

						//if the destination range is true then set a new uuid for each iteration of the loop
							if ($destination_number_range) {
								$destination_uuid = uuid();
								$dialplan_uuid = uuid();
							}

						//set the dialplan_uuid
							$array['destinations'][$x]["dialplan_uuid"] = $dialplan_uuid;

						//build the dialplan array
							if ($destination_type == "inbound") {
								$dialplan["app_uuid"] = "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4";
							}
							if ($destination_type == "local") {
								$dialplan["app_uuid"] = "b5242951-686f-448f-8b4e-5031ba0601a4";
							}
							$dialplan["dialplan_uuid"] = $dialplan_uuid;
							$dialplan["domain_uuid"] = $domain_uuid;
							$dialplan["dialplan_name"] = ($dialplan_name != '') ? $dialplan_name : format_phone($destination_area_code.$destination_number);
							$dialplan["dialplan_number"] = $destination_area_code.$destination_number;
							$dialplan["dialplan_context"] = $destination_context;
							$dialplan["dialplan_continue"] = "false";
							$dialplan["dialplan_order"] = $destination_order;
							$dialplan["dialplan_enabled"] = $destination_enabled;
							$dialplan["dialplan_description"] = ($dialplan_description != '') ? $dialplan_description : $destination_description;
							$dialplan_detail_order = 10;

						//set the dialplan detail type
							if (strlen($destination_condition_field) > 0) {
								$dialplan_detail_type = $destination_condition_field;
							}
							elseif (strlen($_SESSION['dialplan']['destination']['text']) > 0) {
								$dialplan_detail_type = $_SESSION['dialplan']['destination']['text'];
							}
							else {
								$dialplan_detail_type = "destination_number";
							}

						//build the xml dialplan
							$dialplan["dialplan_xml"] = "<extension name=\"".$dialplan_name."\" continue=\"false\" uuid=\"".$dialplan_uuid."\">\n";
							$dialplan["dialplan_xml"] .= "	<condition field=\"".$dialplan_detail_type."\" expression=\"".$destination_number_regex."\">\n";
							$dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
							$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_uuid=".$_SESSION['domain_uuid']."\" inline=\"true\"/>\n";
							$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_name=".$_SESSION['domain_name']."\" inline=\"true\"/>\n";

							//add this only if using application bridge
							if ($destination_app == 'bridge') {
									$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"hangup_after_bridge=true\" inline=\"true\"/>\n";
									$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"continue_on_fail=true\" inline=\"true\"/>\n";
							}

							if (strlen($destination_cid_name_prefix) > 0) {
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"effective_caller_id_name=".$destination_cid_name_prefix."#\${caller_id_name}\" inline=\"false\"/>\n";
							}
							if (strlen($destination_record) > 0 && $destination_record == 'true') {
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_path=\${recordings_dir}/\${domain_name}/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}\" inline=\"true\"/>\n";
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_name=\${uuid}.\${record_ext}\" inline=\"true\"/>\n";
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_append=true\" inline=\"true\"/>\n";
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_in_progress=true\" inline=\"true\"/>\n";
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"recording_follow_transfer=true\" inline=\"true\"/>\n";
								$dialplan["dialplan_xml"] .= "		<action application=\"record_session\" data=\"\${record_path}/\${record_name}\" inline=\"false\"/>\n";
							}
							if (strlen($destination_hold_music) > 0) {
								$dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"hold_music=".$destination_hold_music."\" inline=\"true\"/>\n";
							}
							if (strlen($destination_accountcode) > 0) {
								$dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"accountcode=".$destination_accountcode."\" inline=\"true\"/>\n";
							}
							if (strlen($destination_carrier) > 0) {
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"carrier=".$destination_carrier."\" inline=\"true\"/>\n";
							}
							if (strlen($fax_uuid) > 0) {
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"tone_detect_hits=1\" inline=\"true\"/>\n";
								$dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"execute_on_tone_detect=transfer ".$fax_extension." XML \${domain_name}\" inline=\"true\"/>\n";
								$dialplan["dialplan_xml"] .= "		<action application=\"tone_detect\" data=\"fax 1100 r +3000\"/>\n";
							}
							if ($destination->valid($destination_app.':'.$destination_data)) {
								$dialplan["dialplan_xml"] .= "		<action application=\"".$destination_app."\" data=\"".$destination_data."\"/>\n";
							}
							if (strlen($destination_alternate_app) > 0 && $destination->valid($destination_alternate_app.':'.$destination_alternate_data)) {
								$dialplan["dialplan_xml"] .= "		<action application=\"".$destination_alternate_app."\" data=\"".$destination_alternate_data."\"/>\n";
							}
							$dialplan["dialplan_xml"] .= "	</condition>\n";
							$dialplan["dialplan_xml"] .= "</extension>\n";

						//dialplan details
							if ($_SESSION['destinations']['dialplan_details']['boolean'] == "true") {

								//increment the dialplan detail order
									$dialplan_detail_order = $dialplan_detail_order + 10;

								//check the destination number
									$y=0;
									$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
									$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
									if (strlen($destination_condition_field) > 0) {
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $destination_condition_field;
									}
									elseif (strlen($_SESSION['dialplan']['destination']['text']) > 0) {
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $_SESSION['dialplan']['destination']['text'];
									}
									else {
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
									}
									$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_number_regex;
									$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
									$y++;

								//increment the dialplan detail order
									$dialplan_detail_order = $dialplan_detail_order + 10;

								//add this only if using application bridge
									if ($destination_app == 'bridge') {
										//add hangup_after_bridge
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "hangup_after_bridge=true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;

										//add continue_on_fail
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "continue_on_fail=true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;
									}

								//increment the dialplan detail order
									$dialplan_detail_order = $dialplan_detail_order + 10;

								//set the caller id name prefix
									if (strlen($destination_cid_name_prefix) > 0) {
										$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
										$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
										$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "effective_caller_id_name=".$destination_cid_name_prefix."#\${caller_id_name}";
										$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "false";
										$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
										$y++;

										//increment the dialplan detail order
										$dialplan_detail_order = $dialplan_detail_order + 10;
									}

								//set the call accountcode
									if (strlen($destination_accountcode) > 0) {
										$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
										$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "export";
										$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=".$destination_accountcode;
										$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
										$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
										$y++;

										//increment the dialplan detail order
										$dialplan_detail_order = $dialplan_detail_order + 10;
									}

								//set the call carrier
									if (strlen($destination_carrier) > 0) {
										$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
										$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
										$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "carrier=$destination_carrier";
										$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
										$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
										$y++;

										//increment the dialplan detail order
										$dialplan_detail_order = $dialplan_detail_order + 10;
									}

								//set the hold music
									if (strlen($destination_hold_music) > 0) {
										$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
										$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "export";
										$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "hold_music=".$destination_hold_music;
										$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
										$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
										$y++;
									}

								//add fax detection
									if (is_uuid($fax_uuid)) {

										//add set tone detect_hits=1
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "tone_detect_hits=1";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;

										//execute on tone detect
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "execute_on_tone_detect=transfer ".$fax_extension." XML \${domain_name}";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;

										//add tone_detect fax 1100 r +5000
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "tone_detect";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "fax 1100 r +5000";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;
									}

								//add option record to the dialplan
									if ($destination_record == "true") {

										//add a variable
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_path=\${recordings_dir}/\${domain_name}/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;

										//add a variable
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_name=\${uuid}.\${record_ext}";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;

										//add a variable
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_append=true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;

										//add a variable
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_in_progress=true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
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

										//add a variable
											$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
											$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
											$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "record_session";
											$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "\${record_path}/\${record_name}";
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "false";
											$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
											$y++;

										//increment the dialplan detail order
											$dialplan_detail_order = $dialplan_detail_order + 10;
									}

								//add the actions
									if ($destination->valid($destination_app.':'.$destination_data)) {
										//add to the dialplan_details array
										$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
										$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $destination_app;
										$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_data;
										$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;

										//set inline to true
										if ($destination_app == 'set' || $destination_data == 'export') {
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = 'true';
										}
										$y++;

										//increment the dialplan detail order
										$dialplan_detail_order = $dialplan_detail_order + 10;
									}
									if ($destination->valid($destination_alternate_app.':'.$destination_alternate_data)) {
										//add to the dialplan_details array
										$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
										$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
										$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $destination_alternate_app;
										$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_alternate_data;
										$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;

										//set inline to true
										if ($destination_alternate_app == 'set' || $destination_alternate_app == 'export') {
											$dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = 'true';
										}
										$y++;

										//increment the dialplan detail order
										$dialplan_detail_order = $dialplan_detail_order + 10;
									}

								//delete the previous details
									if ($action == "update") {
										$sql = "delete from v_dialplan_details ";
										$sql .= "where dialplan_uuid = :dialplan_uuid ";
										if (!permission_exists('destination_domain')) {
											$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
											$parameters['domain_uuid'] = $domain_uuid;
										}
										$parameters['dialplan_uuid'] = $dialplan_uuid;
										$database = new database;
										$database->execute($sql, $parameters);
										unset($sql, $parameters);
									}
							}

						//build the destination array
							$array['destinations'][$x]["domain_uuid"] = $domain_uuid;
							$array['destinations'][$x]["destination_uuid"] = $destination_uuid;
							$array['destinations'][$x]["dialplan_uuid"] = $dialplan_uuid;
							$array['destinations'][$x]["fax_uuid"] = $fax_uuid;
							if (permission_exists('provider_edit')) {
								$array['destinations'][$x]["provider_uuid"] = $provider_uuid;
							}
							if (permission_exists('user_edit')) {
								$array['destinations'][$x]["user_uuid"] = $user_uuid;
							}
							$array['destinations'][$x]["destination_type"] = $destination_type;
							if (permission_exists('destination_condition_field')) {
								$array['destinations'][$x]["destination_condition_field"] = $destination_condition_field;
							}
							if (permission_exists('destination_number')) {
								$array['destinations'][$x]["destination_number"] = $destination_number;
								$array['destinations'][$x]["destination_number_regex"] = $destination_number_regex;
								$array['destinations'][$x]["destination_prefix"] = $destination_prefix;
							}
							if (permission_exists('destination_trunk_prefix')) {
								$array['destinations'][$x]["destination_trunk_prefix"] = $destination_trunk_prefix;
							}
							if (permission_exists('destination_area_code')) {
								$array['destinations'][$x]["destination_area_code"] = $destination_area_code;
							}
							$array['destinations'][$x]["destination_caller_id_name"] = $destination_caller_id_name;
							$array['destinations'][$x]["destination_caller_id_number"] = $destination_caller_id_number;
							$array['destinations'][$x]["destination_cid_name_prefix"] = $destination_cid_name_prefix;
							$array['destinations'][$x]["destination_context"] = $destination_context;
							$array['destinations'][$x]["destination_hold_music"] = $destination_hold_music;
							$array['destinations'][$x]["destination_record"] = $destination_record;
							$array['destinations'][$x]["destination_accountcode"] = $destination_accountcode;
							$array['destinations'][$x]["destination_type_voice"] = $destination_type_voice ? 1 : null;
							$array['destinations'][$x]["destination_type_fax"] = $destination_type_fax ? 1 : null;
							$array['destinations'][$x]["destination_type_text"] = $destination_type_text ? 1 : null;
							if (permission_exists('destination_emergency')){
								$array['destinations'][$x]["destination_type_emergency"] = $destination_type_emergency ? 1 : null;
							}
							if (strlen($destination_app) == 0) {
								$array['destinations'][$x]["destination_app"] = null;
								$array['destinations'][$x]["destination_data"] = null;
							}
							elseif ($destination->valid($destination_app.':'.$destination_data)) {
								$array['destinations'][$x]["destination_app"] = $destination_app;
								$array['destinations'][$x]["destination_data"] = $destination_data;
							}
							if (strlen($destination_alternate_app) == 0) {
								$array['destinations'][$x]["destination_alternate_app"] = null;
								$array['destinations'][$x]["destination_alternate_data"] = null;
							}
							elseif ($destination->valid($destination_alternate_app.':'.$destination_alternate_data)) {
								$array['destinations'][$x]["destination_alternate_app"] = $destination_alternate_app;
								$array['destinations'][$x]["destination_alternate_data"] = $destination_alternate_data;
							}
							$array['destinations'][$x]["destination_order"] = $destination_order;
							$array['destinations'][$x]["destination_enabled"] = $destination_enabled;
							$array['destinations'][$x]["destination_description"] = $destination_description;
							$x++;

						//prepare the array
							$array['dialplans'][] = $dialplan;
							unset($dialplan);
					} //foreach($destination_numbers as $destination_number)

				//add the dialplan permission
					$p = new permissions;
					$p->add("dialplan_add", 'temp');
					$p->add("dialplan_detail_add", 'temp');
					$p->add("dialplan_edit", 'temp');
					$p->add("dialplan_detail_edit", 'temp');

				//save the dialplan
					$database = new database;
					$database->app_name = 'destinations';
					$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
					$database->save($array);
					$dialplan_response = $database->message;

				//remove the temporary permission
					$p->delete("dialplan_add", 'temp');
					$p->delete("dialplan_detail_add", 'temp');
					$p->delete("dialplan_edit", 'temp');
					$p->delete("dialplan_detail_edit", 'temp');

				//clear the cache
					$cache = new cache;
					if ($_SESSION['destinations']['dialplan_mode']['text'] == 'multiple') {
						$cache->delete("dialplan:".$destination_context);
					}
					if ($_SESSION['destinations']['dialplan_mode']['text'] == 'single') {
						if (isset($destination_number) && is_numeric($destination_number)) {
							$cache->delete("dialplan:".$destination_context.":".$destination_number);
						}
						if (isset($destination_prefix) && is_numeric($destination_prefix) && isset($destination_number) && is_numeric($destination_number)) {
							$cache->delete("dialplan:".$destination_context.":".$destination_prefix.$destination_number);
						}
					}

			} //if $destination_type == inbound

		//save the outbound destination
			if ($destination_type == 'outbound') {

				//add the destinations and asscociated dialplans
					$x = 0;
					foreach($destination_numbers as $destination_number) {

						//if empty then get new uuid
							if (!is_uuid($destination_uuid)) {
								$destination_uuid = uuid();
							}


						//if the destination range is true then set a new uuid for each iteration of the loop
							if ($destination_number_range) {
								$destination_uuid = uuid();
							}

						//prepare the array
							$x = 0;
							$array['destinations'][$x]["destination_uuid"] = $destination_uuid;
							$array['destinations'][$x]["domain_uuid"] = $domain_uuid;
							$array['destinations'][$x]["destination_type"] = $destination_type;
							$array['destinations'][$x]["destination_number"] = $destination_number;
							$array['destinations'][$x]["destination_prefix"] = $destination_prefix;
							$array['destinations'][$x]["destination_context"] = $destination_context;
							$array['destinations'][$x]["destination_enabled"] = $destination_enabled;
							$array['destinations'][$x]["destination_description"] = $destination_description;
							$x++;
					}

				//save the destination
					$database = new database;
					$database->app_name = 'destinations';
					$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
					$database->save($array);
					$dialplan_response = $database->message;
					unset($array);

				//clear the destinations session array
					if (isset($_SESSION['destinations']['array'])) {
						unset($_SESSION['destinations']['array']);
					}

			} //if destination_type == outbound

		//redirect the user
			if ($action == "add") {
				message::add($text['message-add']);
			}
			if ($action == "update") {
				message::add($text['message-update']);
			}
			header("Location: destination_edit.php?id=".urlencode($destination_uuid)."&type=".urlencode($destination_type));
			return;

	}

//pre-populate the form
	if (is_array($_GET) > 0 && $_POST["persistformvar"] != "true") {
	 	if (is_uuid($_GET["id"])) {
	 		$destination_uuid = $_GET["id"];
			$sql = "select * from v_destinations ";
			$sql .= "where destination_uuid = :destination_uuid ";
			$parameters['destination_uuid'] = $destination_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$domain_uuid = $row["domain_uuid"];
				$dialplan_uuid = $row["dialplan_uuid"];
				$destination_type = $row["destination_type"];
				$destination_number = $row["destination_number"];
				$destination_condition_field = $row["destination_condition_field"];
				$destination_prefix = $row["destination_prefix"];
				$destination_trunk_prefix = $row["destination_trunk_prefix"];
				$destination_area_code = $row["destination_area_code"];
				$destination_caller_id_name = $row["destination_caller_id_name"];
				$destination_caller_id_number = $row["destination_caller_id_number"];
				$destination_cid_name_prefix = $row["destination_cid_name_prefix"];
				$destination_hold_music = $row["destination_hold_music"];
				$destination_record = $row["destination_record"];
				$destination_accountcode = $row["destination_accountcode"];
				$destination_type_voice = $row["destination_type_voice"];
				$destination_type_fax = $row["destination_type_fax"];
				$destination_type_text = $row["destination_type_text"];
				$destination_type_emergency = $row["destination_type_emergency"];
				$destination_context = $row["destination_context"];
				$destination_app = $row["destination_app"];
				$destination_data = $row["destination_data"];
				$destination_alternate_app = $row["destination_alternate_app"];
				$destination_alternate_data = $row["destination_alternate_data"];
				$fax_uuid = $row["fax_uuid"];
				$provider_uuid = $row["provider_uuid"];
				$user_uuid = $row["user_uuid"];
				$currency = $row["currency"];
				$destination_sell = $row["destination_sell"];
				$destination_buy = $row["destination_buy"];
				$currency_buy = $row["currency_buy"];
				$destination_carrier = $row["destination_carrier"];
				$destination_order = $row["destination_order"];
				$destination_enabled = $row["destination_enabled"];
				$destination_description = $row["destination_description"];
			}
			unset($sql, $parameters, $row);
		}
	}

//get the dialplan details in an array
	$sql = "select * from v_dialplan_details ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= "and dialplan_uuid = :dialplan_uuid ";
	$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['dialplan_uuid'] = $dialplan_uuid;
	$database = new database;
	$dialplan_details = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row to the array
	$x = (is_array($dialplan_details)) ? count($dialplan_details) : $x = 0;
	$limit = $x + 1;
	while($x < $limit) {
		$dialplan_details[$x]['domain_uuid'] = $domain_uuid;
		$dialplan_details[$x]['dialplan_uuid'] = $dialplan_uuid;
		$dialplan_details[$x]['dialplan_detail_type'] = '';
		$dialplan_details[$x]['dialplan_detail_data'] = '';
		$dialplan_details[$x]['dialplan_detail_order'] = '';
		$x++;
	}
	unset($limit);

//remove previous fax details
	$x = 0;
	foreach($dialplan_details as $row) {
		if ($row['dialplan_detail_data'] == "tone_detect_hits=1") {
			unset($dialplan_details[$x]);
		}
 		if ($row['dialplan_detail_type'] == "tone_detect") {
			unset($dialplan_details[$x]);
		}
		if (substr($dialplan_detail_data,0,22) == "execute_on_tone_detect") {
			unset($dialplan_details[$x]);
		}
 		if ($row['dialplan_detail_type'] == "answer") {
			unset($dialplan_details[$x]);
		}
 		if ($row['dialplan_detail_type'] == "sleep") {
			unset($dialplan_details[$x]);
		}
 		if ($row['dialplan_detail_type'] == "record_session") {
			unset($dialplan_details[$x]);
		}
		//increment the row id
		$x++;
	}

//set the defaults
	if (strlen($destination_order) == 0) { $destination_order = '100'; }
	if (strlen($destination_type) == 0) { $destination_type = 'inbound'; }
	if (strlen($destination_context) == 0) { $destination_context = 'public'; }
	if ($destination_type =="outbound") { $destination_context = $_SESSION['domain_name']; }
	if ($destination_type =="local") { $destination_context = $_SESSION['domain_name']; }

//initialize the destinations object
	$destination = new destinations;
	if (permission_exists('destination_domain') && is_uuid($domain_uuid)) {
		$destination->domain_uuid = $domain_uuid;
	}

//get the providers list
	if (permission_exists('provider_edit')) {
		$sql = "select ";
		$sql .= "provider_uuid, ";
		$sql .= "provider_name, ";
		$sql .= "domain_uuid ";
		$sql .= "from v_providers ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "and provider_enabled = true ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$providers = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the users list
	if (permission_exists('user_edit')) {
		$sql = "select * from v_users ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and user_enabled = 'true' ";
		$sql .= "order by username asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$users = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	if ($action == "update") {
		$document['title'] = $text['title-destination-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-destination-add'];
	}
	require_once "resources/header.php";

//js controls
	echo "<script type='text/javascript'>\n";
	echo "	function type_control(dir) {\n";
	echo "		if (dir == 'outbound') {\n";
	echo "			if (document.getElementById('tr_caller_id_name')) { document.getElementById('tr_caller_id_name').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_caller_id_number')) { document.getElementById('tr_caller_id_number').style.display = 'none'; }\n";
	echo "			document.getElementById('tr_actions').style.display = 'none';\n";
	echo "			if (document.getElementById('tr_fax_detection')) { document.getElementById('tr_fax_detection').style.display = 'none'; }\n";
	echo "			document.getElementById('tr_cid_name_prefix').style.display = 'none';\n";
	echo "			if (document.getElementById('tr_sell')) { document.getElementById('tr_sell').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_buy')) { document.getElementById('tr_buy').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_carrier')) { document.getElementById('tr_carrier').style.display = 'none'; }\n";
	echo "			document.getElementById('tr_account_code').style.display = 'none';\n";
	echo "			if (document.getElementById('tr_user')) { document.getElementById('tr_user').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_hold_music')) { document.getElementById('tr_hold_music').style.display = 'none'; }\n";
	//echo "			document.getElementById('destination_context').value = '".$destination_context."'";
	echo "		}\n";
	echo "		else if (dir == 'inbound') {\n";
	echo "			if (document.getElementById('tr_caller_id_name')) { document.getElementById('tr_caller_id_name').style.display = ''; }\n";
	echo "			if (document.getElementById('tr_caller_id_number')) { document.getElementById('tr_caller_id_number').style.display = ''; }\n";
	echo "			document.getElementById('tr_actions').style.display = '';\n";
	echo "			if (document.getElementById('tr_fax_detection')) { document.getElementById('tr_fax_detection').style.display = ''; }\n";
	echo "			document.getElementById('tr_cid_name_prefix').style.display = '';\n";
	echo "			if (document.getElementById('tr_sell')) { document.getElementById('tr_sell').style.display = ''; }\n";
	echo "			if (document.getElementById('tr_buy')) { document.getElementById('tr_buy').style.display = ''; }\n";
	echo "			if (document.getElementById('tr_carrier')) { document.getElementById('tr_carrier').style.display = ''; }\n";
	echo "			if (document.getElementById('tr_user')) { document.getElementById('tr_user').style.display = ''; }\n";
	echo "			if (document.getElementById('tr_hold_music')) { document.getElementById('tr_hold_music').style.display = ''; }\n";
	echo "			document.getElementById('tr_account_code').style.display = '';\n";
	echo "			document.getElementById('destination_context').value = 'public'";
	echo "		}\n";
	echo "		else if (dir == 'local') {\n";
	echo "			if (document.getElementById('tr_caller_id_name')) { document.getElementById('tr_caller_id_name').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_caller_id_number')) { document.getElementById('tr_caller_id_number').style.display = 'none'; }\n";
	echo "			document.getElementById('tr_actions').style.display = '';\n";
	echo "			if (document.getElementById('tr_fax_detection')) { document.getElementById('tr_fax_detection').style.display = 'none'; }\n";
	echo "			document.getElementById('tr_cid_name_prefix').style.display = 'none';\n";
	echo "			if (document.getElementById('tr_sell')) { document.getElementById('tr_sell').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_buy')) { document.getElementById('tr_buy').style.display = 'none'; }\n";
	echo "			if (document.getElementById('tr_carrier')) { document.getElementById('tr_carrier').style.display = 'none'; }\n";
	echo "			document.getElementById('tr_account_code').style.display = '';\n";
	//echo "			document.getElementById('destination_context').value = '".$destination_context."'";
	echo "		}\n";
	echo "		";
	echo "	}\n";
	echo "	\n";
	echo "	function context_control() {\n";
	echo "		destination_type = document.getElementById('destination_type');\n";
	echo " 		destination_domain = document.getElementById('destination_domain');\n";
	echo "		if (destination_type.options[destination_type.selectedIndex].value == 'outbound') {\n";
	echo "			if (destination_domain.options[destination_domain.selectedIndex].value != '') {\n";
	echo "				document.getElementById('destination_context').value = destination_domain.options[destination_domain.selectedIndex].innerHTML;\n";
	echo "			}\n";
	echo "			else {\n";
	echo "				document.getElementById('destination_context').value = '\${domain_name}';\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['header-destination-add']."</b>";
	}
	if ($action == "update") {
		echo "<b>".$text['header-destination-edit']."</b>";
	}
	echo 	"</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'destinations.php?type='.urlencode($destination_type)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-destinations']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_type']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='destination_type' id='destination_type' onchange='type_control(this.options[this.selectedIndex].value);context_control();'>\n";
	switch ($destination_type) {
		case "inbound" :	$selected[0] = "selected='selected'";	break;
		case "outbound" :	$selected[1] = "selected='selected'";	break;
		case "local" :	$selected[2] = "selected='selected'";	break;
	}
	echo "	<option value='inbound' ".$selected[0].">".$text['option-inbound']."</option>\n";
	echo "	<option value='outbound' ".$selected[1].">".$text['option-outbound']."</option>\n";
	echo "	<option value='local' ".$selected[2].">".$text['option-local']."</option>\n";
	unset($selected);
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-destination_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('destination_number')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_country_code']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_prefix' maxlength='32' value=\"".escape($destination_prefix)."\">\n";
		echo "<br />\n";
		echo $text['description-destination_country_code']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('destination_trunk_prefix')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_trunk_prefix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_trunk_prefix' maxlength='32' value=\"".escape($destination_trunk_prefix)."\">\n";
		echo "<br />\n";
		echo $text['description-destination_trunk_prefix']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('destination_area_code')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_area_code']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_area_code' maxlength='32' value=\"".escape($destination_area_code)."\">\n";
		echo "<br />\n";
		echo $text['description-destination_area_code']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (permission_exists('destination_number')) {
		echo "	<input class='formfld' type='text' name='destination_number' maxlength='255' value=\"".escape($destination_number)."\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-destination_number']."\n";
	}
	else {
		echo escape($destination_number)."\n";
	}
	echo "</td>\n";
	echo "</tr>\n";
	
	if (permission_exists('destination_condition_field')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_condition_field']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_condition_field' maxlength='32' value=\"".escape($destination_condition_field)."\">\n";
		echo "<br />\n";
		echo $text['description-destination_condition_field']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('destination_caller_id_name')) {
		echo "<tr id='tr_caller_id_name'>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_caller_id_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_caller_id_name' maxlength='255' value=\"".escape($destination_caller_id_name)."\">\n";
		echo "<br />\n";
		echo $text['description-destination_caller_id_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('destination_caller_id_number')) {
		echo "<tr id='tr_caller_id_number'>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='number' name='destination_caller_id_number' maxlength='255' min='0' step='1' value=\"".escape($destination_caller_id_number)."\">\n";
		echo "<br />\n";
		echo $text['description-destination_caller_id_number']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('destination_context')) {
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
	}

	echo "<tr id='tr_actions'>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-detail_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$destination_action = $destination_app.":".$destination_data;
	echo $destination->select('dialplan', 'destination_action', $destination_action);
	echo "<br />\n";
	$destination_alternate_action = $destination_alternate_app.":".$destination_alternate_data;
	echo $destination->select('dialplan', 'destination_alternate_action', $destination_alternate_action);
	echo "</td>\n";

	if (permission_exists('destination_fax')) {
		$sql = "select * from v_fax ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "order by fax_name asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			echo "<tr id='tr_fax_detection'>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap>\n";
			echo "	".$text['label-fax_uuid']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "	<select name='fax_uuid' id='fax_uuid' class='formfld' style='".$select_style."'>\n";
			echo "	<option value=''></option>\n";
			foreach ($result as &$row) {
				if ($row["fax_uuid"] == $fax_uuid) {
					echo "		<option value='".escape($row["fax_uuid"])."' selected='selected'>".escape($row["fax_extension"])." ".escape($row["fax_name"])."</option>\n";
				}
				else {
					echo "		<option value='".escape($row["fax_uuid"])."'>".escape($row["fax_extension"])." ".escape($row["fax_name"])."</option>\n";
				}
			}
			echo "	</select>\n";
			echo "	<br />\n";
			echo "	".$text['description-fax_uuid']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		unset($sql, $parameters, $result, $row);
	}

	if (permission_exists('provider_edit') && is_array($providers) && @sizeof($providers) != 0) {
		echo "<tr id='tr_fax_detection'>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-provider']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select name='provider_uuid' id='provider_uuid' class='formfld' style='".$select_style."'>\n";
		echo "	<option value=''></option>\n";
		foreach ($providers as &$row) {
			if ($row["provider_uuid"] == $provider_uuid) {
				echo "		<option value='".escape($row["provider_uuid"])."' selected='selected'>".escape($row["provider_name"])."</option>\n";
			}
			else {
				echo "		<option value='".escape($row["provider_uuid"])."'>".escape($row["provider_name"])."</option>\n";
			}
		}
		echo "	</select>\n";
		echo "	<br />\n";
		echo "	".$text['description-providers']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('user_edit')) {
		echo "<tr id='tr_user'>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-user']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "			<select name=\"user_uuid\" class='formfld' style='width: auto;'>\n";
		echo "			<option value=\"\"></option>\n";
		foreach($users as $field) {
			if ($field['user_uuid'] == $user_uuid) { $selected = "selected='selected'"; } else { $selected = ''; }
			echo "			<option value='".escape($field['user_uuid'])."' $selected>".escape($field['username'])."</option>\n";
		}
		echo "			</select>";
		unset($users);
		echo "			<br>\n";
		echo "			".$text['description-user']."\n";
	}

	echo "<tr id='tr_cid_name_prefix'>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_cid_name_prefix']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_cid_name_prefix' maxlength='255' value=\"".escape($destination_cid_name_prefix)."\">\n";
	echo "<br />\n";
	echo $text['description-destination_cid_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($destination_type == 'inbound' && permission_exists('destination_record')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-destination_record']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='destination_record'>\n";
		echo "	<option value=''></option>\n";
		if ($destination_record == "true") {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($destination_record == "false") {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/music_on_hold')) {
		echo "<tr id='tr_hold_music'>\n";
		echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_hold_music']."\n";
		echo "</td>\n";
		echo "<td width=\"70%\" class='vtable' align='left'>\n";
		require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
		$music_on_hold = new switch_music_on_hold;
		echo $music_on_hold->select('destination_hold_music', $destination_hold_music, null);
		echo "	<br />\n";
		echo $text['description-destination_hold_music']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("destination_accountcode")) {
		echo "<tr id='tr_account_code'>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-account_code']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_accountcode' maxlength='255' value=\"".escape($destination_accountcode)."\">\n";
		echo "<br />\n";
		echo $text['description-account_code']."\n";
		echo "</td>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-usage']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<label><input type='checkbox' name='destination_type_voice' id='destination_type_voice' value='1' ".($destination_type_voice ? "checked='checked'" : null)."> ".$text['label-voice']."</label>&nbsp;\n";
	echo "	<label><input type='checkbox' name='destination_type_fax' id='destination_type_fax' value='1' ".($destination_type_fax ? "checked='checked'" : null)."> ".$text['label-fax']."</label>&nbsp;\n";
	echo "	<label><input type='checkbox' name='destination_type_text' id='destination_type_text' value='1' ".($destination_type_text ? "checked='checked'" : null)."> ".$text['label-text']."</label>&nbsp;\n";
	if (permission_exists('destination_emergency')){
		echo "	<label><input type='checkbox' name='destination_type_emergency' id='destination_type_emergency' value='1' ".($destination_type_emergency ? "checked='checked'" : null)."> ".$text['label-emergency']."</label>\n";
	}
	echo "<br />\n";
	echo $text['description-usage']."\n";
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

	echo "	<tr>\n";
	echo "	<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "		".$text['label-order']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left' width='70%'>\n";
	echo "		<select name='destination_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($i == $destination_order) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "			<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "			<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "			<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

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

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_description' maxlength='255' value=\"".escape($destination_description)."\">\n";
	echo "<br />\n";
	echo $text['description-destination_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='db_destination_number' value='".escape($destination_number)."'>\n";
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
		echo "<input type='hidden' name='destination_uuid' value='".escape($destination_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//adjust form if outbound destination
	if ($destination_type == 'outbound') {
		echo "<script type='text/javascript'>type_control('outbound');</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>
