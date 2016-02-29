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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
	Riccardo Granchi <riccardo.granchi@nems.it>
	Gill Abada <ga@steadfasttelecom.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-dialplan-outbound-add'];
	require_once "resources/paging.php";

//get the http post values and set theme as php variables
	if (count($_POST) > 0) {
		//set the variables
			$dialplan_name = check_str($_POST["dialplan_name"]);
			$dialplan_order = check_str($_POST["dialplan_order"]);
			$dialplan_expression = check_str($_POST["dialplan_expression"]);
			$prefix_number = check_str($_POST["prefix_number"]);
			$condition_field_1 = check_str($_POST["condition_field_1"]);
			$condition_expression_1 = check_str($_POST["condition_expression_1"]);
			$condition_field_2 = check_str($_POST["condition_field_2"]);
			$condition_expression_2 = check_str($_POST["condition_expression_2"]);
			$gateway = check_str($_POST["gateway"]);
			$limit = check_str($_POST["limit"]);
			$accountcode = check_str($_POST["accountcode"]);
			$toll_allow_enable = check_str($_POST["toll_allow_enabled"]);

		//set default to enabled
			if (strlen($toll_allow_enable) == 0) { $toll_allow_enable = "false"; }

		//set the default type
			$gateway_type = 'gateway';
			$gateway_2_type = 'gateway';
			$gateway_3_type = 'gateway';

		//set the gateway type to enum
			if (strtolower(substr($gateway, 0, 7)) == "enum") {
				$gateway_type = 'enum';
			}
		//set the gateway type to freetdm
			if (strtolower(substr($gateway, 0, 7)) == "freetdm") {
				$gateway_type = 'freetdm';
			}
		//set the gateway type to transfer
			if (strtolower(substr($gateway, 0, 8)) == "transfer") {
				$gateway_type = 'transfer';
			}
		//set the gateway type to dingaling
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
			$gateway_2 = check_str($_POST["gateway_2"]);
		//set the gateway type to enum
			if (strtolower(substr($gateway_2, 0, 4)) == "enum") {
				$gateway_2_type = 'enum';
			}
		//set the gateway type to freetdm
			if (strtolower(substr($gateway_2, 0, 7)) == "freetdm") {
				$gateway_2_type = 'freetdm';
			}
		//set the gateway type to dingaling
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
			$gateway_3 = check_str($_POST["gateway_3"]);
		//set the gateway type to enum
			if (strtolower(substr($gateway_3, 0, 4)) == "enum") {
				$gateway_3_type = 'enum';
			}
		//set the gateway type to freetdm
			if (strtolower(substr($gateway_3, 0, 7)) == "freetdm") {
				$gateway_3_type = 'freetdm';
			}
		//set the gateway type to dingaling
			if (strtolower(substr($gateway_3, 0, 4)) == "xmpp") {
				$gateway_3_type = 'xmpp';
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
			$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
			$dialplan_description = check_str($_POST["dialplan_description"]);
		//set default to enabled
			if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; }
	}

//process the http form values
	if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
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

		if (strlen(trim($_POST['dialplan_expression']))> 0) {

			$tmp_array = explode("\n", $_POST['dialplan_expression']);

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
					case "^(?:\+?1)?(\d{10})$":
						$label = $text['label-north-america'];
						$abbrv = "10-11d";
						break;
					case "^(011\d{9,17})$":
						$label = $text['label-north-america-intl'];
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
					case "^(911)$":
						$label = $text['label-911'];
						$abbrv = "911";
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
					case "^1?(8(00|55|66|77|88)[2-9]\d{6})$":
						$label = $text['label-800'];
						$abbrv = "800";
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
						$action_data = "sofia/gateway/".$gateway_uuid."/".$prefix_number."\$1";
					}
					if (strlen($gateway_2_name) > 0 && $gateway_2_type == "gateway") {
						$extension_2_name = $gateway_2_id.".".$abbrv;
						$bridge_2_data .= "sofia/gateway/".$gateway_2_id."/".$prefix_number."\$1";
					}
					if (strlen($gateway_3_name) > 0 && $gateway_3_type == "gateway") {
						$extension_3_name = $gateway_3_id.".".$abbrv;
						$bridge_3_data .= "sofia/gateway/".$gateway_3_id."/".$prefix_number."\$1";
					}
					if ($gateway_type == "freetdm") {
						$dialplan_name = "freetdm.".$abbrv;
						$action_data = $gateway."/1/a/".$prefix_number."\$1";
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
						$action_data = "dingaling/gtalk/+".$prefix_number."\$1@voice.google.com";
					}
					if ($gateway_2_type == "xmpp") {
						$extension_2_name = "xmpp.".$abbrv;
						$bridge_2_data .= "dingaling/gtalk/+".$prefix_number."\$1@voice.google.com";
					}
					if ($gateway_3_type == "xmpp") {
						$extension_3_name = "xmpp.".$abbrv;
						$bridge_3_data .= "dingaling/gtalk/+".$prefix_number."\$1@voice.google.com";
					}
					if ($gateway_type == "enum") {
						if (strlen($bridge_2_data) == 0) {
							$dialplan_name = "enum.".$abbrv;
						}
						else {
							$dialplan_name = $extension_2_name;
						}
						$action_data = "\${enum_auto_route}";
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
						$action_data = $gateway_array[1];
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
					$dialplan_context = $_SESSION['context'];
					$dialplan_continue = 'false';
					$app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';

					//add the main dialplan include entry
						$dialplan_uuid = uuid();
						$sql = "insert into v_dialplans ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "dialplan_uuid, ";
						$sql .= "app_uuid, ";
						$sql .= "dialplan_name, ";
						$sql .= "dialplan_order, ";
						$sql .= "dialplan_continue, ";
						$sql .= "dialplan_context, ";
						$sql .= "dialplan_enabled, ";
						$sql .= "dialplan_description ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$_SESSION['domain_uuid']."', ";
						$sql .= "'$dialplan_uuid', ";
						$sql .= "'$app_uuid', ";
						$sql .= "'$dialplan_name', ";
						$sql .= "'$dialplan_order', ";
						$sql .= "'$dialplan_continue', ";
						$sql .= "'$dialplan_context', ";
						$sql .= "'$dialplan_enabled', ";
						$sql .= "'$dialplan_description' ";
						$sql .= ")";
						if ($v_debug) {
							echo $sql."<br />";
						}
						$db->exec(check_sql($sql));
						unset($sql);

					$dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan_detail_type = 'destination_number';
					$dialplan_detail_data = $dialplan_expression;
					$dialplan_detail_order = '005';
					$dialplan_detail_group = '0';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					if ($gateway_type != "transfer") {
						if (strlen($accountcode) > 0) {
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'set';
							$dialplan_detail_data = 'sip_h_X-accountcode='.$accountcode;
							$dialplan_detail_order = '010';
							$dialplan_detail_group = '0';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
						}
						else {
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'set';
							$dialplan_detail_data = 'sip_h_X-accountcode=${accountcode}';
							$dialplan_detail_order = '010';
							$dialplan_detail_group = '0';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
						}
					}

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'call_direction=outbound';
					$dialplan_detail_order = '020';
					$dialplan_detail_group = '0';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					if ($gateway_type != "transfer") {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'hangup_after_bridge=true';
						$dialplan_detail_order = '025';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'effective_caller_id_name=${outbound_caller_id_name}';
						$dialplan_detail_order = '030';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						if ($dialplan_expression == '^(911)$') {
							$dialplan_detail_data = 'effective_caller_id_number=${emergency_caller_id_number}';
						}
						else {
							$dialplan_detail_data = 'effective_caller_id_number=${outbound_caller_id_number}';
						}
						$dialplan_detail_order = '035';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'inherit_codec=true';
						$dialplan_detail_order = '040';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'ignore_display_updates=true';
						$dialplan_detail_order = '042';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'callee_id_number=$1';
						$dialplan_detail_order = '043';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'continue_on_fail=true';
						$dialplan_detail_order = '045';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					if ($gateway_type == "enum" || $gateway_2_type == "enum") {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'enum';
						$dialplan_detail_data = $prefix_number."$1 e164.org";
						$dialplan_detail_order = '050';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					if (strlen($limit) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'limit';
						$dialplan_detail_data = "hash \${domain_name} outbound ".$limit." !USER_BUSY";
						$dialplan_detail_order = '055';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					if (strlen($outbound_prefix) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'outbound_prefix='.$outbound_prefix;
						$dialplan_detail_order = '060';
						$dialplan_detail_group = '0';
						$dialplan_detail_break = '';
						$dialplan_detail_inline = 'true';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data, $dialplan_detail_break, $dialplan_detail_inline);
					}

					if ($toll_allow_enable == "true") {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'lua';
						$dialplan_detail_data = 'app.lua toll_allow ${uuid}';
						$dialplan_detail_order = '065';
						$dialplan_detail_group = '0';
						$dialplan_detail_break = '';
						$dialplan_detail_inline = 'true';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data, $dialplan_detail_break, $dialplan_detail_inline);
					}

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					if ($gateway_type == "transfer") {
						$dialplan_detail_type = 'transfer';
					}
					else {
						$dialplan_detail_type = 'bridge';
					}
					$dialplan_detail_data = $action_data;
					$dialplan_detail_order = '070';
					$dialplan_detail_group = '0';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					if (strlen($bridge_2_data) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'bridge';
						$dialplan_detail_data = $bridge_2_data;
						$dialplan_detail_order = '075';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					if (strlen($bridge_3_data) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'bridge';
						$dialplan_detail_data = $bridge_3_data;
						$dialplan_detail_order = '080';
						$dialplan_detail_group = '0';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					unset($bridge_2_data);
					unset($bridge_3_data);
					unset($label);
					unset($abbrv);
					unset($dialplan_expression);
					unset($action_data);
				} //if strlen
			} //end for each
		}

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$dialplan_context);

		//synchronize the xml config
			save_dialplan_xml();

		//redirect the browser
			$_SESSION["message"] = $text['message-update'];
			header("Location: ".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3");
			return;
	} //end if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)
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
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<span class=\"title\">".$text['label-outbound-routes']."</span>\n";
	echo "		</td>\n";
	echo "		<td align='right'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'\" value='".$text['button-back']."'>\n";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<br>";
	echo "			".$text['description-outbound-routes']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>";
	echo "<br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-gateway']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	if (if_group("superadmin")) {
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
		echo "    if ( document.getElementById('dialplan_expression_select').value == 'CUSTOM_PREFIX' ) {\n";
		echo "        document.getElementById('outbound_prefix').value = '';\n";
		echo "        $('#enter_custom_outbound_prefix_box').slideDown();\n";
		echo "    } else { \n";
		echo "        document.getElementById('dialplan_expression').value += document.getElementById('dialplan_expression_select').value + '\\n';\n";
		echo "        document.getElementById('outbound_prefix').value = '';\n";
		echo "        $('#enter_custom_outbound_prefix_box').slideUp();\n";
		echo "    }\n";
		echo "}\n";
		echo "function update_outbound_prefix() {\n";
		echo "    document.getElementById('dialplan_expression').value += '^' + document.getElementById('outbound_prefix').value + '(\\\d*)\$' + '\\n';\n";
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}

	//set the onchange
	if (if_group("superadmin")) { $onchange = "onchange='changeToInput(this);'"; } else { $onchange = ''; }

	$sql = "select * from v_gateways ";
	$sql .= "where enabled = 'true' ";
	if (permission_exists('outbound_route_any_gateway')) {
		$sql .= " order by domain_uuid = '$domain_uuid' ";
	}
	else {
		$sql .= " and domain_uuid = '$domain_uuid' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);
	echo "<select name=\"gateway\" id=\"gateway\" class=\"formfld\" $onchange>\n";
	echo "<option value=''></option>\n";
	echo "<optgroup label='".$text['label-gateway']."'>";
	$previous_domain_uuid = '';
	foreach($result as $row) {
		if (permission_exists('outbound_route_any_gateway')) {
			if ($previous_domain_uuid != $row['domain_uuid']) {
				echo "</optgroup>";
				echo "<optgroup label='&nbsp; &nbsp;".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."'>";
			}
			if ($row['gateway'] == $gateway_name) {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\" selected=\"selected\">&nbsp; &nbsp;".$row['gateway']."</option>\n";
			}
			else {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\">&nbsp; &nbsp;".$row['gateway']."</option>\n";
			}
		}
		else {
			if ($row['gateway'] == $gateway_name) {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\" $onchange selected=\"selected\">".$row['gateway']."</option>\n";
			}
			else {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\">".$row['gateway']."</option>\n";
			}
		}
		$previous_domain_uuid = $row['domain_uuid'];
	}
	unset($sql, $result, $row_count);
	echo "</optgroup>";
	echo "	<optgroup label='".$text['label-add-options']."'>";
	echo "	<option value=\"enum\">enum</option>\n";
	echo "	<option value=\"freetdm\">freetdm</option>\n";
	echo "	<option value=\"transfer:\$1 XML \${domain_name}\">transfer</option>\n";
	echo "	<option value=\"xmpp\">xmpp</option>\n";
	echo "</optgroup>";
	echo "</select>\n";
	echo "<br />\n";
	echo $text['message-add-options']."\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-alt1']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$sql = "select * from v_gateways ";
	$sql .= "where enabled = 'true' ";
	if (permission_exists('outbound_route_any_gateway')) {
		$sql .= "order by domain_uuid = '$domain_uuid' ";
	}
	else {
		$sql .= "and domain_uuid = '$domain_uuid' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);
	echo "<select name=\"gateway_2\" id=\"gateway\" class=\"formfld\" $onchange>\n";
	echo "<option value=''></option>\n";
	echo "<optgroup label='".$text['label-sip-gateway']."'>";
	$previous_domain_uuid = '';
	foreach($result as $row) {
		if (permission_exists('outbound_route_any_gateway')) {
			if ($previous_domain_uuid != $row['domain_uuid']) {
				echo "</optgroup>";
				echo "<optgroup label='&nbsp; &nbsp;".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."'>";
			}
			if ($row['gateway'] == $gateway_2_name) {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\" selected=\"selected\">&nbsp; &nbsp;".$row['gateway']."</option>\n";
			}
			else {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\">&nbsp; &nbsp;".$row['gateway']."</option>\n";
			}
		}
		else {
			if ($row['gateway'] == $gateway_2_name) {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\" selected=\"selected\">".$row['gateway']."</option>\n";
			}
			else {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\">".$row['gateway']."</option>\n";
			}
		}
		$previous_domain_uuid = $row['domain_uuid'];
	}
	unset($sql, $result, $row_count, $previous_domain_uuid);
	echo "</optgroup>";
	echo "<optgroup label='".$text['label-add-options']."'>";
	echo "	<option value=\"enum\">enum</option>\n";
	echo "	<option value=\"freetdm\">freetdm</option>\n";
	echo "	<option value=\"transfer:\$1 XML \${domain_name}\">transfer</option>\n";
	echo "	<option value=\"xmpp\">xmpp</option>\n";
	echo "</optgroup>";
	echo "</select>\n";
	echo "<br />\n";
	echo $text['message-add-options1']."\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-alt2']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$sql = "select * from v_gateways ";
	$sql .= "where enabled = 'true' ";
	if (permission_exists('outbound_route_any_gateway')) {
		$sql .= "order by domain_uuid = '$domain_uuid' ";
	}
	else {
		$sql .= "and domain_uuid = '$domain_uuid' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);
	echo "<select name=\"gateway_3\" id=\"gateway\" class=\"formfld\" $onchange>\n";
	echo "<option value=''></option>\n";
	echo "<optgroup label='".$text['label-sip-gateway']."'>";
	$previous_domain_uuid = '';
	foreach($result as $row) {
		if (permission_exists('outbound_route_any_gateway')) {
			if ($previous_domain_uuid != $row['domain_uuid']) {
				echo "</optgroup>";
				echo "<optgroup label='&nbsp; &nbsp;".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."'>";
			}
			if ($row['gateway'] == $gateway_3_name) {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\" selected=\"selected\">&nbsp; &nbsp;".$row['gateway']."</option>\n";
			}
			else {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\">&nbsp; &nbsp;".$row['gateway']."</option>\n";
			}
		}
		else {
			if ($row['gateway'] == $gateway_3_name) {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\" selected=\"selected\">".$row['gateway']."</option>\n";
			}
			else {
				echo "<option value=\"".$row['gateway_uuid'].":".$row['gateway']."\">".$row['gateway']."</option>\n";
			}
		}
		$previous_domain_uuid = $row['domain_uuid'];
	}
	unset($sql, $result, $row_count, $previous_domain_uuid);
	echo "</optgroup>";
	echo "<optgroup label='".$text['label-add-options']."'>";
	echo "	<option value=\"enum\">enum</option>\n";
	echo "	<option value=\"freetdm\">freetdm</option>\n";
	echo "	<option value=\"transfer:\$1 XML \${domain_name}\">transfer</option>\n";
	echo "	<option value=\"xmpp\">xmpp</option>\n";
	echo "</optgroup>";
	echo "</select>\n";
	echo "<br />\n";
	echo $text['message-add-options2']."\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "  <td valign=\"top\" class=\"vncellreq\">".$text['label-dialplan-expression']."</td>\n";
	echo "  <td align='left' class=\"vtable\">";

	echo "    <div id=\"dialplan_expression_box\" >\n";
	echo "        <textarea name=\"dialplan_expression\" id=\"dialplan_expression\" class=\"formfld\" cols=\"30\" rows=\"4\" style='width: 350px;' wrap=\"off\"></textarea>\n";
	echo "        <br>\n";
	echo "    </div>\n";

	echo "    <div id=\"enter_custom_outbound_prefix_box\" style=\"display:none\">\n";
	echo "        <input class='formfld' style='width: 10%;' type='text' name='custom-outbound-prefix' id=\"outbound_prefix\" maxlength='255'>\n";
	echo "        <input type='button' class='btn' name='' onclick=\"update_outbound_prefix()\" value='".$text['button-add']."'>\n";
	echo "        <br />".$text['description-enter-custom-outbound-prefix'].".\n";
	echo "    </div>\n";

	echo "    <select name='dialplan_expression_select' id='dialplan_expression_select' onchange=\"update_dialplan_expression()\" class='formfld'>\n";
	echo "    <option></option>\n";
	echo "    <option value='^(\\d{2})\$'>".$text['label-2d']."</option>\n";
	echo "    <option value='^(\\d{3})\$'>".$text['label-3d']."</option>\n";
	echo "    <option value='^(\\d{4})\$'>".$text['label-4d']."</option>\n";
	echo "    <option value='^(\\d{5})\$'>".$text['label-5d']."</option>\n";
	echo "    <option value='^(\\d{6})\$'>".$text['label-6d']."</option>\n";
	echo "    <option value='^(\\d{7})\$'>".$text['label-7d']."</option>\n";
	echo "    <option value='^(\\d{8})\$'>".$text['label-8d']."</option>\n";
	echo "    <option value='^(\\d{9})\$'>".$text['label-9d']."</option>\n";
	echo "    <option value='^(\\d{10})\$'>".$text['label-10d']."</option>\n";
	echo "    <option value='^\+?(\\d{11})\$'>".$text['label-11d']."</option>\n";
	echo "    <option value='^\+?1?(\\d{10})\$'>".$text['label-north-america']."</option>\n";
	echo "    <option value='^(011\\d{9,17})\$'>".$text['label-north-america-intl']."</option>\n";
	echo "    <option value='^(00\\d{9,17})\$'>".$text['label-europe-intl']."</option>\n";
	echo "    <option value='^(\\d{12,20})\$'>".$text['label-intl']."</option>\n";
	echo "    <option value='^(311)\$'>".$text['label-311']."</option>\n";
	echo "    <option value='^(411)\$'>".$text['label-411']."</option>\n";
	echo "    <option value='^(711)\$'>".$text['label-711']."</option>\n";
	echo "    <option value='^(911)\$'>".$text['label-911']."</option>\n";
	echo "    <option value='^1?(8(00|55|66|77|88)[2-9]\\d{6})\$'>".$text['label-800']."</option>\n";
	echo "    <option value='^9(\\d{2})\$'>".$text['label-9d2']."</option>\n";
	echo "    <option value='^9(\\d{3})\$'>".$text['label-9d3']."</option>\n";
	echo "    <option value='^9(\\d{4})\$'>".$text['label-9d4']."</option>\n";
	echo "    <option value='^9(\\d{5})\$'>".$text['label-9d5']."</option>\n";
	echo "    <option value='^9(\\d{6})\$'>".$text['label-9d6']."</option>\n";
	echo "    <option value='^9(\\d{7})\$'>".$text['label-9d7']."</option>\n";
	echo "    <option value='^9(\\d{8})\$'>".$text['label-9d8']."</option>\n";
	echo "    <option value='^9(\\d{9})\$'>".$text['label-9d9']."</option>\n";
	echo "    <option value='^9(\\d{10})\$'>".$text['label-9d10']."</option>\n";
	echo "    <option value='^9(\\d{11})\$'>".$text['label-9d11']."</option>\n";
	echo "    <option value='^9(\\d{12,20})\$'>".$text['label-9d.12-20']."</option>\n";
	echo "    <option value='CUSTOM_PREFIX'>".$text['label-custom-outbound-prefix']."</option>\n";
	echo "    </select>\n";
	echo "    <span class=\"vexpl\">\n";
	echo "    <br />\n";
	echo "    ".$text['description-shortcut']." \n";
	echo "    </span></td>\n";
	echo "</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-prefix']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='prefix_number' maxlength='255' value=\"$prefix_number\">\n";
	echo "<br />\n";
	echo $text['description-enter-prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-limit']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='limit' maxlength='255' value=\"$limit\">\n";
	echo "<br />\n";
	echo $text['description-limit']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-accountcode']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='accountcode' maxlength='255' value=\"$accountcode\">\n";
	echo "<br />\n";
	echo $text['description-accountcode']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('outbound_route_toll_allow_lua')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-toll_allow']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='toll_allow_enabled'>\n";
		echo "		<option value='true'>".$text['label-true']."</option>\n";
		echo "		<option value='false' selected='true'>".$text['label-false']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-enable-toll_allow']."\n";
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
		echo "		<option selected='yes' value='".htmlspecialchars($dialplan_order)."'>".htmlspecialchars($dialplan_order)."</option>\n";
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
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled'>\n";
	//echo "    <option value=''></option>\n";
	if ($dialplan_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td colspan='5' align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "		<br>";
	echo "		<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	</td>\n";
	echo "</tr>";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the footer
	require_once "resources/footer.php";
?>