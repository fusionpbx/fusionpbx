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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Riccardo Granchi <riccardo.granchi@nems.it>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('inbound_route_add')) {
		//access granted
	}
	else {
		echo $text['label-access-denied'];
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get values and set them as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];
	$action = $_GET["action"];

//initialize the destinations object
	$destination = new destinations;

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		$dialplan_name = $_POST["dialplan_name"];
		$caller_id_outbound_prefix = $_POST["caller_id_outbound_prefix"];
		$limit = $_POST["limit"];
		$public_order = $_POST["public_order"];
		$condition_field_1 = $_POST["condition_field_1"];
		$condition_expression_1 = $_POST["condition_expression_1"];
		$condition_field_2 = $_POST["condition_field_2"];
		$condition_expression_2 = $_POST["condition_expression_2"];
		$destination_uuid = $_POST["destination_uuid"];
	
	 	$action_1 = $_POST["action_1"];
		//$action_1 = "transfer:1001 XML default";
		$action_1_array = explode(":", $action_1);
		$action_application_1 = array_shift($action_1_array);
		$action_data_1 = join(':', $action_1_array);
	
	 	$action_2 = $_POST["action_2"];
		//$action_2 = "transfer:1001 XML default";
		$action_2_array = explode(":", $action_2);
		$action_application_2 = array_shift($action_2_array);
		$action_data_2 = join(':', $action_2_array);
	
		//$action_application_1 = $_POST["action_application_1"];
		//$action_data_1 = $_POST["action_data_1"];
		//$action_application_2 = $_POST["action_application_2"];
		//$action_data_2 = $_POST["action_data_2"];
	
		$destination_carrier = '';
		$destination_accountcode = '';
	
		//use the destination_uuid to set the condition_expression_1
		if (is_uuid($destination_uuid)) {
			$sql = "select * from v_destinations ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and destination_uuid = :destination_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['destination_uuid'] = $destination_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$destination_number = $row["destination_number"];
				$condition_expression_1 = $row["destination_number"];
				$fax_uuid = $row["fax_uuid"];
				$destination_carrier = $row["destination_carrier"];
				$destination_accountcode = $row["destination_accountcode"];
			}
			unset($sql, $parameters, $row);
		}
	
		if (permission_exists("inbound_route_advanced") && $action == "advanced") {
			//allow users with group advanced control, not always superadmin. You may change this in group permissions
		}
		else {
			if (strlen($condition_field_1) == 0) { $condition_field_1 = "destination_number"; }
			if (is_numeric($condition_expression_1)) {
				//the number is numeric
				$condition_expression_1 = str_replace("+", "\+", $condition_expression_1);
				$condition_expression_1 = '^('.$condition_expression_1.')$';
			}
		}
		$dialplan_enabled = $_POST["dialplan_enabled"];
		$dialplan_description = $_POST["dialplan_description"];
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

//process the http post data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: '.PROJECT_PATH.'/app/dialplans/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4');
				exit;
			}

		//check for all required data
			if (strlen($domain_uuid) == 0) { $msg .= "".$text['label-required-domain_uuid']."<br>\n"; }
			if (strlen($dialplan_name) == 0) { $msg .= "".$text['label-required-dialplan_name']."<br>\n"; }
			if (strlen($condition_field_1) == 0) { $msg .= "".$text['label-required-condition_field_1']."<br>\n"; }
			if (strlen($condition_expression_1) == 0) { $msg .= "".$text['label-required-condition_expression_1']."<br>\n"; }
			if (strlen($action_application_1) == 0) { $msg .= "".$text['label-required-action_application_1']."<br>\n"; }
			//if (strlen($limit) == 0) { $msg .= "Please provide: Limit<br>\n"; }
			//if (strlen($dialplan_enabled) == 0) { $msg .= "Please provide: Enabled True or False<br>\n"; }
			//if (strlen($dialplan_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				require_once "resources/footer.php";
				return;
			}

		//remove the invalid characters from the extension name
			$dialplan_name = str_replace(" ", "_", $dialplan_name);
			$dialplan_name = str_replace("/", "", $dialplan_name);

		//set the context
			$context = '$${domain_name}';

		//set the uuids
			$dialplan_uuid = uuid();
			$app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
			$domain_uuid = $_SESSION['domain_uuid'];

		//build the array
			$x = 0;
			$array['dialplans'][$x]['domain_uuid'] = $domain_uuid;
			$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][$x]['app_uuid'] = $app_uuid;
			$array['dialplans'][$x]['dialplan_name'] = $dialplan_name;
			$array['dialplans'][$x]['dialplan_number'] = $destination_number;
			$array['dialplans'][$x]['dialplan_order'] = $public_order;
			$array['dialplans'][$x]['dialplan_continue'] = 'false';
			$array['dialplans'][$x]['dialplan_context'] = 'public';
			$array['dialplans'][$x]['dialplan_enabled'] = $dialplan_enabled;
			$array['dialplans'][$x]['dialplan_description'] = $dialplan_description;

		//add condition 1
			$y = 0;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
			$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $condition_field_1;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $condition_expression_1;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';

		//add condition 2
			if (strlen($condition_field_2) > 0) {
				$y++;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
				$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $condition_field_2;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $condition_expression_2;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}

		//set accountcode
			if (strlen($destination_accountcode) > 0) {
				$y++;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
				$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'accountcode='.$destination_accountcode;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}

		//set carrier
			if (strlen($destination_carrier) > 0) {
				$y++;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
				$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'carrier='.$destination_carrier;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}

		//set limit
			if (strlen($limit) > 0) {
				$y++;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
				$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'limit';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = "hash \${domain_name} inbound ".$limit." !USER_BUSY";
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}

		//set redial outbound prefix
			if (strlen($caller_id_outbound_prefix) > 0) {
				$y++;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
				$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = "effective_caller_id_number=".$caller_id_outbound_prefix."\${caller_id_number}";
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}

		//set fax_uuid
			if (is_uuid($fax_uuid)) {

				//get the fax information
					$sql = "select * from v_fax ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and fax_uuid = :fax_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['fax_uuid'] = $fax_uuid;
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

				//add set codec_string=PCMU,PCMA
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'codec_string=PCMU,PCMA';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';

				//add set tone_detect_hits=1
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'tone_detect_hits=1';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';

				//add execute_on_tone_detect
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = "execute_on_tone_detect=transfer ".$fax_extension." XML ".$_SESSION["context"];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';

				//add tone_detect fax 1100 r +5000
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'tone_detect';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'fax 1100 r +5000';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';

				//add sleep to provide time for fax detection
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'sleep';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = '3000';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';

				//set codec_string=${ep_codec_string}
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'export';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'codec_string=\${ep_codec_string}';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}

		//set answer
			$tmp_app = false;
			if ($action_application_1 == "ivr") { $tmp_app = true; }
			if ($action_application_2 == "ivr") { $tmp_app = true; }
			if ($action_application_1 == "conference") { $tmp_app = true; }
			if ($action_application_2 == "conference") { $tmp_app = true; }
			if ($tmp_app) {
				$y++;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
				$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'answer';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = '';
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}
			unset($tmp_app);

		//add action 1
			$y++;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
			$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
			if ($destination->valid($action_application_1.':'.$action_data_1)) {
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $action_application_1;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $action_data_1;
			}
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
			$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';

		//add action 2
			if (strlen($action_application_2) > 0) {
				$y++;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
				$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
				if ($destination->valid($action_application_2.':'.$action_data_2)) {
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $action_application_2;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $action_data_2;
				}
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
				$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
			}

		//update the destination dialplan_uuid
			if (is_uuid($destination_uuid)) {

				$p = new permissions;
				$p->add('destination_edit', 'temp');

				$array['destinations'][0]['destination_uuid'] = $destination_uuid;
				$array['destinations'][0]['domain_uuid'] = $domain_uuid;
				$array['destinations'][0]['dialplan_uuid'] = $dialplan_uuid;
			}

		//save the data
			$database = new database;
			$database->app_name = 'inbound_routes';
			$database->app_uuid = $app_uuid;
			$database->save($array);
			$message = $database->message;
			unset($array);

		//remove temp permission, if exists
			if (is_uuid($destination_uuid)) {
				$p->delete('destination_edit', 'temp');
			}

		//update the dialplan xml
			$dialplans = new dialplan;
			$dialplans->source = "details";
			$dialplans->destination = "database";
			$dialplans->uuid = $dialplan_uuid;
			$dialplans->xml();

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:public");

		//redirect message
			message::add($text['confirm-update-complete']);
			header("Location: ".PROJECT_PATH."/app/dialplans/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4");
			exit;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-dialplan-inbound-add'];
	require_once "resources/header.php";

?>

<script type="text/javascript">
	function type_onchange(dialplan_detail_type) {
		var field_value = document.getElementById(dialplan_detail_type).value;
		if (dialplan_detail_type == "condition_field_1") {
			if (field_value == "destination_number") {
				document.getElementById("desc_condition_expression_1").innerHTML = "expression: 5551231234";
			}
			else if (field_value == "zzz") {
				document.getElementById("desc_condition_expression_1").innerHTML = "";
			}
			else {
				document.getElementById("desc_condition_expression_1").innerHTML = "";
			}
		}
		if (dialplan_detail_type == "condition_field_2") {
			if (field_value == "destination_number") {
				document.getElementById("desc_condition_expression_2").innerHTML = "expression: 5551231234";
			}
			else if (field_value == "zzz") {
				document.getElementById("desc_condition_expression_2").innerHTML = "";
			}
			else {
				document.getElementById("desc_condition_expression_2").innerHTML = "";
			}
		}
	}
</script>

<?php

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dialplan-inbound-add']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>PROJECT_PATH.'/app/dialplans/dialplans.php?app_uuid=c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4']);
	if (permission_exists("inbound_route_advanced")) {
		if (permission_exists("inbound_route_edit") && $action == "advanced") {
			echo button::create(['type'=>'button','label'=>$text['button-basic'],'icon'=>'hammer','style'=>'margin-left: 15px;','link'=>'dialplan_inbound_add.php?action=basic']);
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-advanced'],'icon'=>'tools','style'=>'margin-left: 15px;','link'=>'dialplan_inbound_add.php?action=advanced']);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-dialplan-inbound-add']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"".escape($dialplan_name)."\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists("inbound_route_edit") && $action == "advanced" && permission_exists("inbound_route_advanced")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-condition_1']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		?>
		<script>
		var Objs;
		function changeToInput_condition_field_1(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			tb.setAttribute('id', 'condition_field_1');
			tb.setAttribute('style', 'width: 85%;');
			tb.value=obj.options[obj.selectedIndex].value;
			document.getElementById('btn_select_to_input_condition_field_1').style.visibility = 'hidden';
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.setAttribute('style', 'margin-left: 4px;');
			tbb.type='button';
			tbb.value=$("<div />").html('&#9665;').text();
			tbb.objs=[obj,tb,tbb];
			tbb.onclick=function(){ Replace_condition_field_1(this.objs); }
			obj.parentNode.insertBefore(tb,obj);
			obj.parentNode.insertBefore(tbb,obj);
			obj.parentNode.removeChild(obj);
			Replace_condition_field_1(this.objs);
		}

		function Replace_condition_field_1(obj){
			obj[2].parentNode.insertBefore(obj[0],obj[2]);
			obj[0].parentNode.removeChild(obj[1]);
			obj[0].parentNode.removeChild(obj[2]);
			document.getElementById('btn_select_to_input_condition_field_1').style.visibility = 'visible';
		}
		</script>
		<?php
		echo "	<table border='0'>\n";
		echo "	<tr>\n";
		//echo "	<td>".$text['label-field']."</td>\n";
		echo "	<td nowrap='nowrap'>\n";

		echo "    <select class='formfld' name='condition_field_1' id='condition_field_1' onchange='changeToInput_condition_field_1(this);this.style.visibility = \"hidden\";'>\n";
		echo "    <option value=''></option>\n";
		if (strlen($condition_field_1) > 0) {
			echo "    <option value='".escape($condition_field_1)."' selected>".escape($condition_field_1)."</option>\n";
		}
		echo "    <option value='context'>".$text['option-context']."</option>\n";
		echo "    <option value='username'>".$text['option-username']."</option>\n";
		echo "    <option value='rdnis'>".$text['option-rdnis']."</option>\n";
		echo "    <option value='destination_number'>".$text['option-destination_number']."</option>\n";
		echo "    <option value='public'>".$text['option-public']."</option>\n";
		echo "    <option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
		echo "    <option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
		echo "    <option value='ani'>".$text['option-ani']."</option>\n";
		echo "    <option value='ani2'>".$text['option-ani2']."</option>\n";
		echo "    <option value='uuid'>".$text['option-uuid']."</option>\n";
		echo "    <option value='source'>".$text['option-source']."</option>\n";
		echo "    <option value='chan_name'>".$text['option-chan_name']."</option>\n";
		echo "    <option value='network_addr'>".$text['option-network_addr']."</option>\n";
		echo "    </select>\n";
		echo "    <input type='button' id='btn_select_to_input_condition_field_1' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_condition_field_1(document.getElementById(\"condition_field_1\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
		echo "    <br />\n";
		echo "	</td>\n";

		echo "	<td>\n";
		echo "		&nbsp;<input class='formfld' type='text' name='condition_expression_1' maxlength='255' value=\"".escape($condition_expression_1)."\">\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "	<div id='desc_condition_expression_1'></div>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-condition_2']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		echo "	<table border='0'>\n";
		echo "	<tr>\n";
		//echo "	<td align='left'>".$text['label-field']."</td>\n";
		echo "	<td align='left' nowrap='nowrap'>\n";
		?>
		<script>
		var Objs;
		function changeToInput_condition_field_2(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			tb.setAttribute('id', 'condition_field_2');
			tb.setAttribute('style', 'width: 85%;');
			tb.value=obj.options[obj.selectedIndex].value;
			document.getElementById('btn_select_to_input_condition_field_2').style.visibility = 'hidden';
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.setAttribute('style', 'margin-left: 4px;');
			tbb.type='button';
			tbb.value=$("<div />").html('&#9665;').text();
			tbb.objs=[obj,tb,tbb];
			tbb.onclick=function(){ Replace_condition_field_2(this.objs); }
			obj.parentNode.insertBefore(tb,obj);
			obj.parentNode.insertBefore(tbb,obj);
			obj.parentNode.removeChild(obj);
			Replace_condition_field_2(this.objs);
		}

		function Replace_condition_field_2(obj){
			obj[2].parentNode.insertBefore(obj[0],obj[2]);
			obj[0].parentNode.removeChild(obj[1]);
			obj[0].parentNode.removeChild(obj[2]);
			document.getElementById('btn_select_to_input_condition_field_2').style.visibility = 'visible';
		}
		</script>
		<?php
		echo "    <select class='formfld' name='condition_field_2' id='condition_field_2' onchange='changeToInput_condition_field_2(this);this.style.visibility = \"hidden\";'>\n";
		echo "    <option value=''></option>\n";
		if (strlen($condition_field_2) > 0) {
			echo "    <option value='".escape($condition_field_2)."' selected>".escape($condition_field_2)."</option>\n";
		}
		echo "    <option value='context'>".$text['option-context']."</option>\n";
		echo "    <option value='username'>".$text['option-username']."</option>\n";
		echo "    <option value='rdnis'>".$text['option-rdnis']."</option>\n";
		echo "    <option value='destination_number'>".$text['option-destination_number']."</option>\n";
		echo "    <option value='public'>".$text['option-public']."</option>\n";
		echo "    <option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
		echo "    <option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
		echo "    <option value='ani'>".$text['option-ani']."</option>\n";
		echo "    <option value='ani2'>".$text['option-ani2']."</option>\n";
		echo "    <option value='uuid'>".$text['option-uuid']."</option>\n";
		echo "    <option value='source'>".$text['option-source']."</option>\n";
		echo "    <option value='chan_name'>".$text['option-chan_name']."</option>\n";
		echo "    <option value='network_addr'>".$text['option-network_addr']."</option>\n";
		echo "	</select>\n";
		echo "  <input type='button' id='btn_select_to_input_condition_field_2' class='btn' name='' alt='".$text['button-back']."' onclick='changeToInput_condition_field_2(document.getElementById(\"condition_field_2\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
		echo "	<br />\n";
		//echo "	</td>\n";
		//echo "	<td align='left'>&nbsp;&nbsp;&nbsp;".$text['label-expression']."\n";
		//echo "	</td>\n";
		echo "	<td>\n";
		echo "		&nbsp;<input class='formfld' type='text' name='condition_expression_2' maxlength='255' value=\"".escape($condition_expression_2)."\">\n";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "	<div id='desc_condition_expression_2'></div>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-destination-number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		$sql = "select * from v_destinations ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and destination_type = 'inbound' ";
		$sql .= "order by destination_number asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			echo "	<select name='destination_uuid' id='destination_uuid' class='formfld' >\n";
			echo "	<option></option>\n";
			foreach ($result as &$row) {
				if (strlen($row["dialplan_uuid"]) == 0) {
					echo "		<option value='".escape($row["destination_uuid"])."' style=\"font-weight:bold;\">".escape($row["destination_number"])." ".escape($row["destination_description"])."</option>\n";
				}
				else {
					echo "		<option value='".escape($row["destination_uuid"])."'>".escape($row["destination_number"])." ".escape($row["destination_description"])."</option>\n";
				}
			}
			echo "		</select>\n";
			echo "<br />\n";
			echo "".$text['label-select-inbound-destination-number']."\n";
		}
		else {
			echo "	<input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
		}
		unset($sql, $parameters, $result, $row);

		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	if (permission_exists("inbound_route_edit") && $action=="advanced") {
		echo "    ".$text['label-action_1']."\n";
	}
	else {
		echo "    ".$text['label-action']."\n";
	}
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'action_1', $action_1);
	echo "</td>\n";
	echo "</tr>\n";

	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists("inbound_route_edit") && $action=="advanced") {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-action_2']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo $destination->select('dialplan', 'action_2', $action_2);
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-limit']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit' maxlength='255' value=\"".escape($limit)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-caller-id-number-prefix']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='caller_id_outbound_prefix' maxlength='255' value=\"".escape($limit)."\">\n";
	echo "<br />\n";
	echo "".$text['description-caller-id-number-prefix']."<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='public_order' class='formfld'>\n";
	if (strlen(htmlspecialchars($public_order))> 0) {
		echo "		<option selected='yes' value='".htmlspecialchars($public_order)."'>".htmlspecialchars($public_order)."</option>\n";
	}
	$i = 100;
	while($i <= 999) {
		if (strlen($i) == 1) { echo "		<option value='00$i'>00$i</option>\n"; }
		if (strlen($i) == 2) { echo "		<option value='0$i'>0$i</option>\n"; }
		if (strlen($i) == 3) { echo "		<option value='$i'>$i</option>\n"; }
		$i = $i + 10;
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "	\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled'>\n";
	if ($dialplan_enabled == "true") {
		echo "    <option value='true' SELECTED >".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "    <option value='false' SELECTED >".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"".escape($dialplan_description)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update" && permission_exists("inbound_route_edit")) {
		echo "	<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
