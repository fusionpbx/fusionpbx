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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
	Riccardo Granchi <riccardo.granchi@nems.it>
	Gill Abada <ga@steadfasttelecom.com>
	Andrew Colin <andrewd.colin@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('outbound_route_add')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post values and set theme as php variables
	if (is_array($_POST) > 0) {
		//set the variables
			$dialplan_name = $_POST["dialplan_name"];
			$dialplan_order = $_POST["dialplan_order"];
			$dialplan_expression = $_POST["dialplan_expression"];
			$prefix_number = $_POST["prefix_number"];
			$condition_field_1 = $_POST["condition_field_1"];
			$condition_expression_1 = $_POST["condition_expression_1"];
			$condition_field_2 = $_POST["condition_field_2"];
			$condition_expression_2 = $_POST["condition_expression_2"];
			$gateway = $_POST["gateway"];
			$limit = $_POST["limit"];
			$accountcode = $_POST["accountcode"];
			$toll_allow = $_POST["toll_allow"];
			$pin_numbers_enable = $_POST["pin_numbers_enabled"];
			if (strlen($pin_numbers_enable) == 0) { $pin_numbers_enable = "false"; }
		//set the default type
			$gateway_type = 'gateway';
			$gateway_2_type = 'gateway';
			$gateway_3_type = 'gateway';
		//set the gateway type to bridge
			if (strtolower(substr($gateway, 0, 6)) == "bridge") {
				$gateway_type = 'bridge';
			}
		//set the type to enum
			if (strtolower(substr($gateway, 0, 4)) == "enum") {
				$gateway_type = 'enum';
			}
		//set the type to freetdm
			if (strtolower(substr($gateway, 0, 7)) == "freetdm") {
				$gateway_type = 'freetdm';
			}
		//set the type to transfer
			if (strtolower(substr($gateway, 0, 8)) == "transfer") {
				$gateway_type = 'transfer';
			}
		//set the type to dingaling
			if (strtolower(substr($gateway, 0, 4)) == "xmpp") {
				$gateway_type = 'xmpp';
			}
		//set the gateway_uuid and gateway_name
			if ($gateway_type == "gateway") {
				$gateway_array = explode(":",$gateway);
				$gateway_uuid = $gateway_array[0];
				$gateway_name = $gateway_array[1];
			}
			else {
				$gateway_name = '';
				$gateway_uuid = '';
			}

		//set the gateway_2 variable
			$gateway_2 = $_POST["gateway_2"];
		//set the type to bridge
			if (strtolower(substr($gateway_2, 0, 6)) == "bridge") {
				$gateway_2_type = 'bridge';
			}
		//set type to enum
			if (strtolower(substr($gateway_2, 0, 4)) == "enum") {
				$gateway_2_type = 'enum';
			}
		//set the type to freetdm
			if (strtolower(substr($gateway_2, 0, 7)) == "freetdm") {
				$gateway_2_type = 'freetdm';
			}
		//set the type to transfer
			if (strtolower(substr($gateway_2, 0, 8)) == "transfer") {
				$gateway_type = 'transfer';
			}
		//set the type to dingaling
			if (strtolower(substr($gateway_2, 0, 4)) == "xmpp") {
				$gateway_2_type = 'xmpp';
			}
		//set the gateway_2_id and gateway_2_name
			if ($gateway_2_type == "gateway" && strlen($_POST["gateway_2"]) > 0) {
				$gateway_2_array = explode(":",$gateway_2);
				$gateway_2_id = $gateway_2_array[0];
				$gateway_2_name = $gateway_2_array[1];
			}
			else {
				$gateway_2_id = '';
				$gateway_2_name = '';
			}

		//set the gateway_3 variable
			$gateway_3 = $_POST["gateway_3"];
		//set the type to bridge
			if (strtolower(substr($gateway_3, 0, 6)) == "bridge") {
				$gateway_3_type = 'bridge';
			}
		//set the type to enum
			if (strtolower(substr($gateway_3, 0, 4)) == "enum") {
				$gateway_3_type = 'enum';
			}
		//set the type to freetdm
			if (strtolower(substr($gateway_3, 0, 7)) == "freetdm") {
				$gateway_3_type = 'freetdm';
			}
		//set the type to dingaling
			if (strtolower(substr($gateway_3, 0, 4)) == "xmpp") {
				$gateway_3_type = 'xmpp';
			}
		//set the type to transfer
			if (strtolower(substr($gateway_3, 0, 8)) == "transfer") {
				$gateway_type = 'transfer';
			}
		//set the gateway_3_id and gateway_3_name
			if ($gateway_3_type == "gateway" && strlen($_POST["gateway_3"]) > 0) {
				$gateway_3_array = explode(":",$gateway_3);
				$gateway_3_id = $gateway_3_array[0];
				$gateway_3_name = $gateway_3_array[1];
			}
			else {
				$gateway_3_id = '';
				$gateway_3_name = '';
			}
		//set additional variables
			$dialplan_enabled = $_POST["dialplan_enabled"];
			$dialplan_description = $_POST["dialplan_description"];
		//set default to enabled
			if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; }
	}

//process the http form values
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: '.PROJECT_PATH.'/app/dialplans/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3');
				exit;
			}

		//check for all required data
			if (strlen($gateway) == 0) { $msg .= $text['message-provide'].": ".$text['label-gateway-name']."<br>\n"; }
			//if (strlen($gateway_2) == 0) { $msg .= "Please provide: Alternat 1<br>\n"; }
			//if (strlen($gateway_3) == 0) { $msg .= "Please provide: Alternat 2<br>\n"; }
			if (strlen($dialplan_expression) == 0) { $msg .= $text['message-provide'].": ".$text['label-dialplan-expression']."<br>\n"; }
			//if (strlen($dialplan_name) == 0) { $msg .= "Please provide: Extension Name<br>\n"; }
			//if (strlen($condition_field_1) == 0) { $msg .= "Please provide: Condition Field<br>\n"; }
			//if (strlen($condition_expression_1) == 0) { $msg .= "Please provide: Condition Expression<br>\n"; }
			//if (strlen($limit) == 0) { $msg .= "Please provide: Limit<br>\n"; }
			//if (strlen($dialplan_enabled) == 0) { $msg .= "Please provide: Enabled True or False<br>\n"; }
			//if (strlen($description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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

		//prepare to build the array
			if (strlen(trim($_POST['dialplan_expression'])) > 0) {

				$tmp_array = explode("\n", $_POST['dialplan_expression']);
				$x = 0;
				foreach($tmp_array as $dialplan_expression) {
					$dialplan_expression = trim($dialplan_expression);
					if (strlen($dialplan_expression) > 0) {
						switch ($dialplan_expression) {
						case "^(\d{7})$":
							$label = $text['label-7d'];
							$abbrv = "7d";
							break;
						case "^(\d{8})$":
							$label = $text['label-8d'];
							$abbrv = "8d";
							break;
						case "^(\d{9})$":
							$label = $text['label-9d'];
							$abbrv = "9d";
							break;
						case "^(\d{10})$":
							$label = $text['label-10d'];
							$abbrv = "10d";
							break;
						case "^\+?(\d{11})$":
							$label = $text['label-11d'];
							$abbrv = "11d";
							break;
						case "^(?:\+1|1)?([2-9]\d{2}[2-9]\d{2}\d{4})$":
							$label = $text['label-north_america'];
							$abbrv = "10-11-NANP";
							break;
						case "^(011\d{9,17})$":
							$label = $text['label-north_america_intl'];
							$abbrv = "011.9-17d";
							break;
						case "^\+?1?((?:264|268|242|246|441|284|345|767|809|829|849|473|658|876|664|787|939|869|758|784|721|868|649|340|684|671|670|808)\d{7})$":
							$label = $text['label-north_america_islands'];
							$abbrv = "011.9-17d";
							break;
						case "^(\d{12,20})$":
							$label = $text['label-intl'];
							$abbrv = $text['label-intl'];
							break;
						case "^(311)$":
							$label = $text['label-311'];
							$abbrv = "311";
							break;
						case "^(411)$":
							$label = $text['label-411'];
							$abbrv = "411";
							break;
						case "^(711)$":
							$label = $text['label-711'];
							$abbrv = "711";
							break;
						case "(^911$|^933$)":
							$label = $text['label-911'];
							$abbrv = "911";
							break;
						case "(^988$)":
							$label = $text['label-988'];
							$abbrv = "988";
							break;
						case "^9(\d{3})$":
							$label = $text['label-9d3'];
							$abbrv = "9.3d";
							break;
						case "^9(\d{4})$":
							$label = $text['label-9d4'];
							$abbrv = "9.4d";
							break;
						case "^9(\d{7})$":
							$label = $text['label-9d7'];
							$abbrv = "9.7d";
							break;
						case "^9(\d{10})$":
							$label = $text['label-9d10'];
							$abbrv = "9.10d";
							break;
						case "^9(\d{11})$":
							$label = $text['label-9d11'];
							$abbrv = "9.11d";
							break;
						case "^9(\d{12,20})$":
							$label = $text['label-9d.12-20'];
							$abbrv = "9.12-20";
							break;
						case "^1?(8(00|33|44|55|66|77|88)[2-9]\d{6})$":
							$label = $text['label-800'];
							$abbrv = "800";
							break;
						case "^0118835100\d{8}$":
							$label = $text['label-inum'];
							$abbrv = "inum";
							break;
						default:
							$label = $dialplan_expression;
							$abbrv = filename_safe($dialplan_expression);
						}

						// Use as outbound prefix all digits beetwen ^ and first (
						$tmp_prefix = preg_replace("/^\^(\d{1,})\(.*/", "$1", $dialplan_expression);
						$tmp_prefix == $dialplan_expression
								? $outbound_prefix = ""
								: $outbound_prefix = $tmp_prefix;
	
						if ($gateway_type == "gateway") {
							$dialplan_name = $gateway_name.".".$abbrv;
							if ($abbrv == "988") {
								$bridge_data = "sofia/gateway/".$gateway_uuid."/".$prefix_number."18002738255";
							} else {
								$bridge_data = "sofia/gateway/".$gateway_uuid."/".$prefix_number."\$1";
							}
						}
						if (strlen($gateway_2_name) > 0 && $gateway_2_type == "gateway") {
							$extension_2_name = $gateway_2_id.".".$abbrv;
							if ($abbrv == "988") {
								$bridge_2_data = "sofia/gateway/".$gateway_2_id."/".$prefix_number."18002738255";
							} else {
								$bridge_2_data = "sofia/gateway/".$gateway_2_id."/".$prefix_number."\$1";
							}
						}
						if (strlen($gateway_3_name) > 0 && $gateway_3_type == "gateway") {
							$extension_3_name = $gateway_3_id.".".$abbrv;
							if ($abbrv == "988") {
								$bridge_3_data = "sofia/gateway/".$gateway_3_id."/".$prefix_number."18002738255";
							} else {
								$bridge_3_data = "sofia/gateway/".$gateway_3_id."/".$prefix_number."\$1";
							}
						}
						if ($gateway_type == "freetdm") {
							$dialplan_name = "freetdm.".$abbrv;
							$bridge_data = $gateway."/1/a/".$prefix_number."\$1";
						}
						if ($gateway_2_type == "freetdm") {
							$extension_2_name = "freetdm.".$abbrv;
							$bridge_2_data .= $gateway_2."/1/a/".$prefix_number."\$1";
						}
						if ($gateway_3_type == "freetdm") {
							$extension_3_name = "freetdm.".$abbrv;
							$bridge_3_data .= $gateway_3."/1/a/".$prefix_number."\$1";
						}
						if ($gateway_type == "xmpp") {
							$dialplan_name = "xmpp.".$abbrv;
							$bridge_data = "dingaling/gtalk/+".$prefix_number."\$1@voice.google.com";
						}
						if ($gateway_2_type == "xmpp") {
							$extension_2_name = "xmpp.".$abbrv;
							$bridge_2_data .= "dingaling/gtalk/+".$prefix_number."\$1@voice.google.com";
						}
						if ($gateway_3_type == "xmpp") {
							$extension_3_name = "xmpp.".$abbrv;
							$bridge_3_data .= "dingaling/gtalk/+".$prefix_number."\$1@voice.google.com";
						}

						if ($gateway_type == "bridge") {
							$dialplan_name = "bridge.".$abbrv;
							$gateway_array = explode(":",$gateway);
							$bridge_data = $gateway_array[1];
						}
						if ($gateway_2_type == "bridge") {
							$dialplan_name = "bridge.".$abbrv;
							$gateway_array = explode(":",$gateway_2);
							$bridge_2_data = $gateway_array[1];
						}
						if ($gateway_3_type == "bridge") {
							$dialplan_name = "bridge.".$abbrv;
							$gateway_array = explode(":",$gateway_3);
							$bridge_3_data = $gateway_array[1];
						}

						if ($gateway_type == "enum") {
							if (strlen($bridge_2_data) == 0) {
								$dialplan_name = "enum.".$abbrv;
							}
							else {
								$dialplan_name = $extension_2_name;
							}
							$bridge_data = "\${enum_auto_route}";
						}
						if ($gateway_2_type == "enum") {
							$bridge_2_data .= "\${enum_auto_route}";
						}
						if ($gateway_3_type == "enum") {
							$bridge_3_data .= "\${enum_auto_route}";
						}

						if ($gateway_type == "transfer") {
							$dialplan_name = "transfer.".$abbrv;
							$gateway_array = explode(":",$gateway);
							$bridge_data = $gateway_array[1];
						}
						if ($gateway_2_type == "transfer") {
							$gateway_array = explode(":",$gateway_2);
							$bridge_2_data = $gateway_array[1];
						}
						if ($gateway_3_type == "transfer") {
							$gateway_array = explode(":",$gateway_3);
							$bridge_3_data = $gateway_array[1];
						}

						if (strlen($dialplan_order) == 0) {
							$dialplan_order ='333';
						}
						$dialplan_context = $_SESSION['domain_name'];
						$dialplan_continue = 'false';
						$app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';

					//set the uuid
						$dialplan_uuid = uuid();

					//build the array - set call_direction
						$x = 0;
						$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['app_uuid'] = $app_uuid;
						$array['dialplans'][$x]['dialplan_name'] = 'call_direction-outbound';
						$array['dialplans'][$x]['dialplan_order'] = '22';
						$array['dialplans'][$x]['dialplan_continue'] = 'true';
						$array['dialplans'][$x]['dialplan_context'] = $dialplan_context;
						$array['dialplans'][$x]['dialplan_enabled'] = $dialplan_enabled;
						$array['dialplans'][$x]['dialplan_description'] = $dialplan_description;
						$y = 0;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${user_exists}';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'false';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						$y++;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'destination_number';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $dialplan_expression;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						$y++;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'export';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_direction=outbound';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = 'true';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						$y++;
						$x++;

					//set the uuid
						$dialplan_uuid = uuid();

					//build the array - outbound route
						$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['app_uuid'] = $app_uuid;
						$array['dialplans'][$x]['dialplan_name'] = $dialplan_name;
						$array['dialplans'][$x]['dialplan_order'] = $dialplan_order;
						$array['dialplans'][$x]['dialplan_continue'] = $dialplan_continue;
						$array['dialplans'][$x]['dialplan_context'] = $dialplan_context;
						$array['dialplans'][$x]['dialplan_enabled'] = $dialplan_enabled;
						$array['dialplans'][$x]['dialplan_description'] = $dialplan_description;
						$y = 0;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${user_exists}';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'false';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						$y++;

						if (strlen($toll_allow) > 0) {
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${toll_allow}';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $toll_allow;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
							$y++;
						}

						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'destination_number';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $dialplan_expression;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

						if ($gateway_type != "transfer") {
							if (strlen($accountcode) > 0) {
								$y++;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
								$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'sip_h_accountcode='.$accountcode;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'false';
							}
							else {
								$y++;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
								$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'sip_h_accountcode=${accountcode}';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'false';
							}
						}

						$y++;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'export';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_direction=outbound';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = 'true';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

						$y++;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'unset';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_timeout';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

						if ($gateway_type != "transfer") {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'hangup_after_bridge=true';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							if ($dialplan_expression == '(^911$|^933$)') {
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'effective_caller_id_name=${emergency_caller_id_name}';
							}
							else {
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'effective_caller_id_name=${outbound_caller_id_name}';
							}
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							if ($dialplan_expression == '(^911$|^933$)') {
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'effective_caller_id_number=${emergency_caller_id_number}';
							}
							else {
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'effective_caller_id_number=${outbound_caller_id_number}';
							}
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

							if ($dialplan_expression == '(^911$|^933$)') {
								$y++;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
								$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'lua';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = "email.lua \${email_to} \${email_from} '' 'Emergency Call' '\${sip_from_user}@\${domain_name} has called 911 emergency'";
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
								$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'false';
							}

							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'inherit_codec=true';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'ignore_display_updates=true';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'callee_id_number=$1';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'continue_on_fail=1,2,3,6,18,21,27,28,31,34,38,41,42,44,58,88,111,403,501,602,607';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						if ($gateway_type == "enum" || $gateway_2_type == "enum") {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'enum';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $prefix_number."$1 e164.org";
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						if (strlen($limit) > 0) {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'limit';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = "hash \${domain_name} outbound ".$limit." !USER_BUSY";
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						if (strlen($outbound_prefix) > 0) {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'outbound_prefix='.$outbound_prefix;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						if ($pin_numbers_enable == "true") {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'pin_number=database';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'lua';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'pin_number.lua';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						if (strlen($prefix_number) > 2) {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'provider_prefix='.$prefix_number;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						if ($gateway_type == "transfer") { $dialplan_detail_type = 'transfer'; } else { $dialplan_detail_type = 'bridge'; }
						$y++;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $dialplan_detail_type;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $bridge_data;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

						if (strlen($bridge_2_data) > 0) {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'bridge';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $bridge_2_data;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						if (strlen($bridge_3_data) > 0) {
							$y++;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'bridge';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $bridge_3_data;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
						}

						unset($bridge_data);
						unset($bridge_2_data);
						unset($bridge_3_data);
						unset($label);
						unset($abbrv);
						unset($dialplan_expression);
					} //if strlen
					$x++;
				} //end foreach
			}

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_detail_add", "temp");

		//save to the data
			$database = new database;
			$database->app_name = 'outbound_routes';
			$database->app_uuid = $app_uuid;
			$database->save($array);
			$message = $database->message;
			unset($array);

		//update the dialplan xml
			$dialplans = new dialplan;
			$dialplans->source = "details";
			$dialplans->destination = "database";
			$dialplans->uuid = $dialplan_uuid;
			$dialplans->xml();

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_detail_add", "temp");

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$dialplan_context);

		//redirect the browser
			message::add($text['message-update']);
			header("Location: ".PROJECT_PATH."/app/dialplans/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3");
			return;
	}

//get the domains
	$sql = "select * from v_domains ";
	$sql .= "where domain_enabled = 'true' ";
	$database = new database;
	$domains = $database->select($sql, null, 'all');
	unset($sql);

//get the gateways
	$sql = "select * from v_gateways ";
	$sql .= "where enabled = 'true' ";
	if (permission_exists('outbound_route_any_gateway')) {
		$sql .= "order by domain_uuid = :domain_uuid DESC, gateway ";
	}
	else {
		$sql .= "and domain_uuid = :domain_uuid ";
		
	}
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$gateways = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the bridges
	if (permission_exists('bridge_view')) {
		$sql = "select * from v_bridges ";
		$sql .= "where bridge_enabled = 'true' ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$bridges = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dialplan-outbound-add'];
	require_once "resources/header.php";

?>

<script type="text/javascript">
<!--
function type_onchange(dialplan_detail_type) {
	var field_value = document.getElementById(dialplan_detail_type).value;

	if (dialplan_detail_type == "condition_field_1") {
		if (field_value == "destination_number") {
			document.getElementById("desc_condition_expression_1").innerHTML = "expression: ^12081231234$";
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
			document.getElementById("desc_condition_expression_2").innerHTML = "expression: ^12081231234$";
		}
		else if (field_value == "zzz") {
			document.getElementById("desc_condition_expression_2").innerHTML = "";
		}
		else {
			document.getElementById("desc_condition_expression_2").innerHTML = "";
		}
	}
}
-->
</script>

<?php

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['label-outbound-routes']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>PROJECT_PATH.'/app/dialplans/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-outbound-routes']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-gateway']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";

	echo "<script>\n";
	echo "var Objs;\n";
	echo "\n";
	echo "function changeToInput(obj){\n";
	echo "	tb=document.createElement('INPUT');\n";
	echo "	tb.type='text';\n";
	echo "	tb.name=obj.name;\n";
	echo "	tb.setAttribute('class', 'formfld');\n";
	echo "	tb.setAttribute('style', 'width: 400px;');\n";
	echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
	echo "	tbb=document.createElement('INPUT');\n";
	echo "	tbb.setAttribute('class', 'btn');\n";
	echo "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
	echo "	tbb.type='button';\n";
	echo "	tbb.value=$('<div />').html('&#9665;').text();\n";
	echo "	tbb.objs=[obj,tb,tbb];\n";
	echo "	tbb.onclick=function(){ Replace(this.objs); }\n";
	echo "	obj.parentNode.insertBefore(tb,obj);\n";
	echo "	obj.parentNode.insertBefore(tbb,obj);\n";
	echo "	obj.parentNode.removeChild(obj);\n";
	echo "}\n";
	echo "\n";
	echo "function Replace(obj){\n";
	echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
	echo "	obj[0].parentNode.removeChild(obj[1]);\n";
	echo "	obj[0].parentNode.removeChild(obj[2]);\n";
	echo "}\n";
	echo "function update_dialplan_expression() {\n";
	echo "	if ( document.getElementById('dialplan_expression_select').value == 'CUSTOM_PREFIX' ) {\n";
	echo "		document.getElementById('outbound_prefix').value = '';\n";
	echo "		$('#enter_custom_outbound_prefix_box').slideDown();\n";
	echo "	} else { \n";
	echo "		document.getElementById('dialplan_expression').value += document.getElementById('dialplan_expression_select').value + '\\n';\n";
	echo "		document.getElementById('outbound_prefix').value = '';\n";
	echo "		$('#enter_custom_outbound_prefix_box').slideUp();\n";
	echo "	}\n";
	echo "}\n";
	echo "function update_outbound_prefix() {\n";
	echo "	document.getElementById('dialplan_expression').value += '^' + document.getElementById('outbound_prefix').value + '(\\\d*)\$' + '\\n';\n";
	echo "}\n";
	echo "</script>\n";
	echo "\n";


	//set the onchange
	$onchange = '';
	//if (if_group("superadmin")) { $onchange = "onchange='changeToInput(this);'"; } else { $onchange = ''; }

	echo "<select name=\"gateway\" id=\"gateway\" class=\"formfld\" $onchange>\n";
	echo "<option value=''></option>\n";
	echo "<optgroup label='".$text['label-gateway']."'>\n";
	$previous_domain_uuid = '';
	foreach($gateways as $row) {
		if (permission_exists('outbound_route_any_gateway')) {
			if ($previous_domain_uuid != $row['domain_uuid']) {
				$domain_name = '';
				foreach($domains as $field) {
					if ($row['domain_uuid'] == $field['domain_uuid']) {
						$domain_name = $field['domain_name'];
						break;
					}
				}
				if (strlen($domain_name) == 0) { $domain_name = $text['label-global']; }
				echo "</optgroup>";
				echo "<optgroup label='&nbsp; &nbsp;".$domain_name."'>\n";
			}
			if ($row['gateway'] == $gateway_name) {
				echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" selected=\"selected\">".escape($row['gateway'])."</option>\n";
			}
			else {
				echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
			}
		}
		else {
			if ($row['gateway'] == $gateway_name) {
				echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" $onchange selected=\"selected\">".escape($row['gateway'])."</option>\n";
			}
			else {
				echo "<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
			}
		}
		$previous_domain_uuid = $row['domain_uuid'];
	}
	echo "	</optgroup>\n";
	if (permission_exists('bridge_view')) {
		echo "	<optgroup label='".$text['label-bridges']."'>\n";
		foreach($bridges as $row) {
			echo "		<option value=\"bridge:".$row['bridge_destination']."\">".$row['bridge_name']."</option>\n";
		}
		echo "	</optgroup>\n";
	}
	echo "	<optgroup label='".$text['label-add-options']."'>\n";
	echo "		<option value=\"enum\">enum</option>\n";
	echo "		<option value=\"freetdm\">freetdm</option>\n";
	echo "		<option value=\"transfer:\$1 XML \${domain_name}\">transfer</option>\n";
	echo "		<option value=\"xmpp\">xmpp</option>\n";
	echo "	</optgroup>\n";
	echo "</select>\n";
	echo "<br />\n";
	echo $text['message-add-options']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-alt1']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name=\"gateway_2\" id=\"gateway\" class=\"formfld\" $onchange>\n";
	echo "	<option value=''></option>\n";
	echo "	<optgroup label='".$text['label-sip-gateway']."'>\n";
	$previous_domain_uuid = '';
	foreach($gateways as $row) {
		if (permission_exists('outbound_route_any_gateway')) {
			if ($previous_domain_uuid != $row['domain_uuid']) {
				$domain_name = '';
				foreach($domains as $field) {
					if ($row['domain_uuid'] == $field['domain_uuid']) {
						$domain_name = $field['domain_name'];
						break;
					}
				}
				if (strlen($domain_name) == 0) { $domain_name = $text['label-global']; }
				echo "	</optgroup>\n";
				echo "	<optgroup label='&nbsp; &nbsp;".$domain_name."'>\n";
			}
			if ($row['gateway'] == $gateway_2_name) {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" selected=\"selected\">".escape($row['gateway'])."</option>\n";
			}
			else {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
			}
		}
		else {
			if ($row['gateway'] == $gateway_2_name) {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" selected=\"selected\">".escape($row['gateway'])."</option>\n";
			}
			else {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
			}
		}
		$previous_domain_uuid = $row['domain_uuid'];
	}
	echo "	</optgroup>\n";
	if (permission_exists('bridge_view')) {
		echo "	<optgroup label='".$text['label-bridges']."'>\n";
		foreach($bridges as $row) {
			echo "		<option value=\"bridge:".$row['bridge_destination']."\">".$row['bridge_name']."</option>\n";
		}
		echo "	</optgroup>\n";
	}
	echo "	<optgroup label='".$text['label-add-options']."'>\n";
	echo "		<option value=\"enum\">enum</option>\n";
	echo "		<option value=\"freetdm\">freetdm</option>\n";
	echo "		<option value=\"transfer:\$1 XML \${domain_name}\">transfer</option>\n";
	echo "		<option value=\"xmpp\">xmpp</option>\n";
	echo "	</optgroup>\n";
	echo "</select>\n";
	echo "<br />\n";
	echo $text['message-add-options1']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-alt2']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name=\"gateway_3\" id=\"gateway\" class=\"formfld\" $onchange>\n";
	echo "	<option value=''></option>\n";
	echo "	<optgroup label='".$text['label-sip-gateway']."'>\n";
	$previous_domain_uuid = '';
	foreach($gateways as $row) {
		if (permission_exists('outbound_route_any_gateway')) {
			if ($previous_domain_uuid != $row['domain_uuid']) {
				$domain_name = '';
				foreach($domains as $field) {
					if ($row['domain_uuid'] == $field['domain_uuid']) {
						$domain_name = $field['domain_name'];
						break;
					}
				}
				if (strlen($domain_name) == 0) { $domain_name = $text['label-global']; }
				echo "	</optgroup>\n";
				echo "	<optgroup label='&nbsp; &nbsp;".$domain_name."'>\n";
			}
			if ($row['gateway'] == $gateway_3_name) {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" selected=\"selected\">".escape($row['gateway'])."</option>\n";
			}
			else {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
			}
		}
		else {
			if ($row['gateway'] == $gateway_3_name) {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\" selected=\"selected\">".escape($row['gateway'])."</option>\n";
			}
			else {
				echo "		<option value=\"".escape($row['gateway_uuid']).":".escape($row['gateway'])."\">".escape($row['gateway'])."</option>\n";
			}
		}
		$previous_domain_uuid = $row['domain_uuid'];
	}
	echo "	</optgroup>\n";
	if (permission_exists('bridge_view')) {
		echo "	<optgroup label='".$text['label-bridges']."'>\n";
		foreach($bridges as $row) {
			echo "		<option value=\"bridge:".$row['bridge_destination']."\">".$row['bridge_name']."</option>\n";
		}
		echo "	</optgroup>\n";
	}
	echo "	<optgroup label='".$text['label-add-options']."'>\n";
	echo "		<option value=\"enum\">enum</option>\n";
	echo "		<option value=\"freetdm\">freetdm</option>\n";
	echo "		<option value=\"transfer:\$1 XML \${domain_name}\">transfer</option>\n";
	echo "		<option value=\"xmpp\">xmpp</option>\n";
	echo "	</optgroup>\n";
	echo "</select>\n";
	echo "<br />\n";
	echo $text['message-add-options2']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "  <td valign=\"top\" class=\"vncellreq\">".$text['label-dialplan-expression']."</td>\n";
	echo "  <td align='left' class=\"vtable\">";

	echo "	<div id=\"dialplan_expression_box\" >\n";
	echo "		<textarea name=\"dialplan_expression\" id=\"dialplan_expression\" class=\"formfld\" cols=\"30\" rows=\"4\" style='width: 350px;' wrap=\"off\"></textarea>\n";
	echo "		<br>\n";
	echo "	</div>\n";

	echo "	<div id=\"enter_custom_outbound_prefix_box\" style=\"display:none\">\n";
	echo "		<input class='formfld' style='width: 10%;' type='text' name='custom-outbound-prefix' id=\"outbound_prefix\" maxlength='255'>\n";
	echo "		<input type='button' class='btn' name='' onclick=\"update_outbound_prefix()\" value='".$text['button-add']."'>\n";
	echo "		<br />".$text['description-enter-custom-outbound-prefix'].".\n";
	echo "	</div>\n";

	echo "	<select name='dialplan_expression_select' id='dialplan_expression_select' onchange=\"update_dialplan_expression()\" class='formfld'>\n";
	echo "	<option></option>\n";
	echo "	<option value='^(\\d{2})\$'>".$text['label-2d']."</option>\n";
	echo "	<option value='^(\\d{3})\$'>".$text['label-3d']."</option>\n";
	echo "	<option value='^(\\d{4})\$'>".$text['label-4d']."</option>\n";
	echo "	<option value='^(\\d{5})\$'>".$text['label-5d']."</option>\n";
	echo "	<option value='^(\\d{6})\$'>".$text['label-6d']."</option>\n";
	echo "	<option value='^(\\d{7})\$'>".$text['label-7d']."</option>\n";
	echo "	<option value='^(\\d{8})\$'>".$text['label-8d']."</option>\n";
	echo "	<option value='^(\\d{9})\$'>".$text['label-9d']."</option>\n";
	echo "	<option value='^(\\d{10})\$'>".$text['label-10d']."</option>\n";
	echo "	<option value='^\+?(\\d{11})\$'>".$text['label-11d']."</option>\n";
	echo "	<option value='^\+?1?([2-9]\\d{2}[2-9]\\d{2}\\d{4})\$'>".$text['label-north_america']."</option>\n";
	echo "	<option value='^(011\\d{9,17})\$'>".$text['label-north_america_intl']."</option>\n";
	echo "	<option value='^\+?1?((?:264|268|242|246|441|284|345|767|809|829|849|473|658|876|664|787|939|869|758|784|721|868|649|340|684|671|670|808)\d{7})\$'>".$text['label-north_america_islands']."</option>\n";
	echo "	<option value='^(00\\d{9,17})\$'>".$text['label-europe_intl']."</option>\n";
	echo "	<option value='^(\\d{12,20})\$'>".$text['label-intl']."</option>\n";
	echo "	<option value='^(311)\$'>".$text['label-311']."</option>\n";
	echo "	<option value='^(411)\$'>".$text['label-411']."</option>\n";
	echo "	<option value='^(711)\$'>".$text['label-711']."</option>\n";
	echo "	<option value='(^911\$|^933\$)'>".$text['label-911']."</option>\n";
	echo "  <option value='(^988\$)'>".$text['label-988']."</option>\n";
	echo "	<option value='^1?(8(00|33|44|55|66|77|88)[2-9]\\d{6})\$'>".$text['label-800']."</option>\n";
	echo "	<option value='^0118835100\d{8}\$'>".$text['label-inum']."</option>\n";
	echo "	<option value='^9(\\d{2})\$'>".$text['label-9d2']."</option>\n";
	echo "	<option value='^9(\\d{3})\$'>".$text['label-9d3']."</option>\n";
	echo "	<option value='^9(\\d{4})\$'>".$text['label-9d4']."</option>\n";
	echo "	<option value='^9(\\d{5})\$'>".$text['label-9d5']."</option>\n";
	echo "	<option value='^9(\\d{6})\$'>".$text['label-9d6']."</option>\n";
	echo "	<option value='^9(\\d{7})\$'>".$text['label-9d7']."</option>\n";
	echo "	<option value='^9(\\d{8})\$'>".$text['label-9d8']."</option>\n";
	echo "	<option value='^9(\\d{9})\$'>".$text['label-9d9']."</option>\n";
	echo "	<option value='^9(\\d{10})\$'>".$text['label-9d10']."</option>\n";
	echo "	<option value='^9(\\d{11})\$'>".$text['label-9d11']."</option>\n";
	echo "	<option value='^9(\\d{12,20})\$'>".$text['label-9d.12-20']."</option>\n";
	echo "	<option value='CUSTOM_PREFIX'>".$text['label-custom_outbound_prefix']."</option>\n";
	echo "	</select>\n";
	echo "	<span class=\"vexpl\">\n";
	echo "	<br />\n";
	echo "	".$text['description-shortcut']." \n";
	echo "	</span></td>\n";
	echo "</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-prefix']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='prefix_number' maxlength='255' value=\"".escape($prefix_number)."\">\n";
	echo "<br />\n";
	echo $text['description-enter-prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-limit']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='limit' maxlength='255' value=\"".escape($limit)."\">\n";
	echo "<br />\n";
	echo $text['description-limit']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-accountcode']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='accountcode' maxlength='255' value=\"".escape($accountcode)."\">\n";
	echo "<br />\n";
	echo $text['description-accountcode']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-toll_allow']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='toll_allow' maxlength='255' value=\"".escape($toll_allow)."\">\n";
	echo "<br />\n";
	echo $text['description-enable-toll_allow']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('outbound_route_pin_numbers')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-pin_numbers']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='pin_numbers_enabled'>\n";
		echo "		<option value='true'>".$text['label-true']."</option>\n";
		echo "		<option value='false' selected='true'>".$text['label-false']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-enable-pin_numbers']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_order' class='formfld'>\n";
	//echo "		<option></option>\n";
	if (strlen(htmlspecialchars($dialplan_order))> 0) {
		echo "		<option selected='yes' value='".escape($dialplan_order)."'>".escape($dialplan_order)."</option>\n";
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
	echo "	".$text['description-order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='dialplan_enabled'>\n";
	//echo "	<option value=''></option>\n";
	if ($dialplan_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"".escape($dialplan_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//show the footer
	require_once "resources/footer.php";

?>
