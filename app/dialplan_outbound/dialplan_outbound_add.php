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
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('outbound_route_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//show the header
	require_once "includes/header.php";
	require_once "includes/paging.php";

//get the http post values and set theme as php variables
	if (count($_POST)>0) {
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

		if (permission_exists('outbound_route_any_gateway')) {
			//get the domain_uuid for gateway
				$sql = "select * from v_gateways ";
				$sql .= "where gateway_uuid = '$gateway_uuid' ";
				$sql .= "and gateway = '$gateway_name' ";
				$sql .= "and enabled = 'true' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$gateway_domain_uuid = $row["domain_uuid"];
					break;
				}
				unset ($prep_statement);
			//get the domain_uuid for gateway_2
				$sql = "select * from v_gateways ";
				$sql .= "where gateway_uuid = '$gateway_2_id' ";
				$sql .= "and gateway = '$gateway_2_name' ";
				$sql .= "and enabled = 'true' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$gateway_2_domain_uuid = $row["domain_uuid"];
					break;
				}
				unset ($prep_statement);
			//get the domain_uuid for gateway_3
				$sql = "select * from v_gateways ";
				$sql .= "where gateway_uuid = '$gateway_3_id' ";
				$sql .= "and gateway = '$gateway_3_name' ";
				$sql .= "and enabled = 'true' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$gateway_3_domain_uuid = $row["domain_uuid"];
					break;
				}
				unset ($prep_statement);
		}

		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
		if (strlen($dialplan_enabled) == 0) { $dialplan_enabled = "true"; } //set default to enabled
	}

//process the http form values
	if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
		//check for all required data
			if (strlen($gateway) == 0) { $msg .= "Please provide: Gateway Name<br>\n"; }
			//if (strlen($gateway_2) == 0) { $msg .= "Please provide: Alternat 1<br>\n"; }
			//if (strlen($gateway_3) == 0) { $msg .= "Please provide: Alternat 2<br>\n"; }
			if (strlen($dialplan_expression) == 0) { $msg .= "Please provide: Dialplan Expression<br>\n"; }
			//if (strlen($dialplan_name) == 0) { $msg .= "Please provide: Extension Name<br>\n"; }
			//if (strlen($condition_field_1) == 0) { $msg .= "Please provide: Condition Field<br>\n"; }
			//if (strlen($condition_expression_1) == 0) { $msg .= "Please provide: Condition Expression<br>\n"; }
			//if (strlen($limit) == 0) { $msg .= "Please provide: Limit<br>\n"; }
			//if (strlen($dialplan_enabled) == 0) { $msg .= "Please provide: Enabled True or False<br>\n"; }
			//if (strlen($description) == 0) { $msg .= "Please provide: Description<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "includes/header.php";
				require_once "includes/persistformvar.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			}

		if (strlen(trim($_POST['dialplan_expression']))> 0) {

			$tmp_array = explode("\n", $_POST['dialplan_expression']);

			foreach($tmp_array as $dialplan_expression) {
				$dialplan_expression = trim($dialplan_expression);
				if (strlen($dialplan_expression)>0) {
					if (count($_SESSION["domains"]) > 1) {
						if (permission_exists('outbound_route_any_gateway')) {
							$tmp_gateway_name = $_SESSION['domains'][$gateway_domain_uuid]['domain_name'] .'-'.$gateway_name;
						}
						else {
							$tmp_gateway_name = $_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'] .'-'.$gateway_name;
						}
						if (strlen($gateway_2_name) > 0) {
							if (permission_exists('outbound_route_any_gateway')) {
								$tmp_gateway_2_name = $_SESSION['domains'][$gateway_2_domain_uuid]['domain_name'] .'-'.$gateway_2_name;
							}
							else {
								$tmp_gateway_2_name = $_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'] .'-'.$gateway_2_name;
							}
						}
						if (strlen($gateway_3_name) > 0) {
							if (permission_exists('outbound_route_any_gateway')) {
								$tmp_gateway_3_name = $_SESSION['domains'][$gateway_3_domain_uuid]['domain_name'] .'-'.$gateway_3_name;
							}
							else {
								$tmp_gateway_3_name = $_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'] .'-'.$gateway_3_name;
							}
						}
					}
					else {
						$tmp_gateway_name = $gateway_name;
						if (strlen($gateway_2_name) > 0) {
							$tmp_gateway_2_name = $gateway_2_name;
						}
						if (strlen($gateway_3_name) > 0) {
							$tmp_gateway_3_name = $gateway_3_name;
						}
					}
					switch ($dialplan_expression) {
					case "^(\d{7})$":
						$label = "7 digits";
						$abbrv = "7d";
						break;
					case "^(\d{8})$":
						$label = "8 digits";
						$abbrv = "8d";
						break;
					case "^(\d{9})$":
						$label = "9 digits";
						$abbrv = "9d";
						break;
					case "^(\d{10})$":
						$label = "10 digits";
						$abbrv = "10d";
						break;
					case "^\+?(\d{11})$":
						$label = "11 digits";
						$abbrv = "11d";
						break;
					case "^(\d{12})$":
						$label = "12 digits";
						$abbrv = "12d";
						break;
					case "^(\d{13})$":
						$label = "13 digits";
						$abbrv = "13d";
						break;
					case "^(\d{14})$":
						$label = "14 digits";
						$abbrv = "14d";
						break;
					case "^(\d{12,15})$":
						$label = "International";
						$abbrv = "Intl";
						break;
					case "^(311)$":
						$label = "311";
						$abbrv = "311";
						break;
					case "^(411)$":
						$label = "411";
						$abbrv = "411";
						break;
					case "^(911)$":
						$label = "911";
						$abbrv = "911";
						break;
					case "^9(\d{3})$":
						$label = "dial 9, 3 digits";
						$abbrv = "9.3d";
						break;
					case "^9(\d{4})$":
						$label = "dial 9, 4 digits";
						$abbrv = "9.4d";
						break;	
					case "^9(\d{7})$":
						$label = "dial 9, 7 digits";
						$abbrv = "9.7d";
						break;
					case "^9(\d{10})$":
						$label = "dial 9, 10 digits";
						$abbrv = "9.10d";
						break;
					case "^9(\d{11})$":
						$label = "dial 9, 11 digits";
						$abbrv = "9.11d";
						break;
					case "^9(\d{12})$":
						$label = "dial 9, 12 digits";
						$abbrv = "9.Intl";
						break;
					case "^9(\d{13})$":
						$label = "dial 9, 13 digits";
						$abbrv = "9.13d";
						break;
					case "^9(\d{14})$":
						$label = "dial 9, 14 digits";
						break;
					case "^9(\d{12,15})$":
						$label = "dial 9, International";
						$abbrv = "9.Intl";
						break;
					case "^1?(8(00|55|66|77|88)[2-9]\d{6})$":
						$label = "toll free";
						$abbrv = "tollfree";
						break;
					default:
						$label = $dialplan_expression;
						$abbrv = filename_safe($dialplan_expression);
					}

					if ($gateway_type == "gateway") {
						$dialplan_name = $gateway_name.".".$abbrv;
						$action_data = "sofia/gateway/".$tmp_gateway_name."/".$prefix_number."\$1";
					}
					if (strlen($gateway_2_name) > 0 && $gateway_2_type == "gateway") {
						$extension_2_name = $gateway_2_name.".".$abbrv;
						$bridge_2_data .= "sofia/gateway/".$tmp_gateway_2_name."/".$prefix_number."\$1";
					}
					if (strlen($gateway_3_name) > 0 && $gateway_3_type == "gateway") {
						$extension_3_name = $gateway_3_name.".".$abbrv;
						$bridge_3_data .= "sofia/gateway/".$tmp_gateway_3_name."/".$prefix_number."\$1";
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
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'sip_h_X-accountcode=${accountcode}';
					$dialplan_detail_order = '010';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'sip_h_X-Tag=';
					$dialplan_detail_order = '012';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'call_direction=outbound';
					$dialplan_detail_order = '015';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'hangup_after_bridge=true';
					$dialplan_detail_order = '020';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'effective_caller_id_name=${outbound_caller_id_name}';
					$dialplan_detail_order = '025';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					if ($dialplan_expression == '^(911)$') {
						$dialplan_detail_data = 'effective_caller_id_number=${emergency_caller_id_number}';
					}
					else {
						$dialplan_detail_data = 'effective_caller_id_number=${outbound_caller_id_number}';
					}
					$dialplan_detail_order = '030';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'set';
					$dialplan_detail_data = 'inherit_codec=true';
					$dialplan_detail_order = '035';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					if (strlen($bridge_2_data) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = 'continue_on_fail=true';
						$dialplan_detail_order = '040';
						$dialplan_detail_group = '';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					if ($gateway_type == "enum" || $gateway_2_type == "enum") {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'enum';
						$dialplan_detail_data = $prefix_number."$1 e164.org";
						$dialplan_detail_order = '045';
						$dialplan_detail_group = '';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					if (strlen($limit) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'limit';
						$dialplan_detail_data = "db \${domain} outbound ".$limit." !USER_BUSY";
						$dialplan_detail_order = '050';
						$dialplan_detail_group = '';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'bridge';
					$dialplan_detail_data = $action_data;
					$dialplan_detail_order = '055';
					$dialplan_detail_group = '';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					if (strlen($bridge_2_data) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'bridge';
						$dialplan_detail_data = $bridge_2_data;
						$dialplan_detail_order = '060';
						$dialplan_detail_group = '';
						dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
					}

					if (strlen($bridge_3_data) > 0) {
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'bridge';
						$dialplan_detail_data = $bridge_3_data;
						$dialplan_detail_order = '065';
						$dialplan_detail_group = '';
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

			//synchronize the xml config
				save_dialplan_xml();
			
			//changes in the dialplan may affect routes in the hunt groups
				save_hunt_group_xml();
		}

		//synchronize the xml config
			save_dialplan_xml();

		//redirect the user
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3\">\n";
			echo "<div align='center'>\n";
			echo "Update Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
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
-->
</script>

<?php
//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";

	echo " 	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left'><span class=\"vexpl\"><span class=\"red\"><strong>Outbound Routes\n";
	echo "			</strong></span></span>\n";
	echo "		</td>\n";
	echo "		<td align='right'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'\" value='Back'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<span class=\"vexpl\">\n";
	echo "				Outbound dialplans have one or more conditions that are matched to attributes of a call. \n";
	echo "				When a call matches the conditions the call is then routed to the gateway.\n";
	echo "			</span>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>";

	echo "<br />\n";
	echo "<br />\n";

	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Gateway:\n";
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
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value='<';\n";
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
	echo "<select name=\"gateway\" id=\"gateway\" class=\"formfld\" $onchange style='width: 60%;'>\n";
	echo "<option value=''></option>\n";
	echo "<optgroup label='SIP Gateways'>";
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
	echo "	<optgroup label='Additional Options'>";
	echo "	<option value=\"enum\">enum</option>\n";
	echo "	<option value=\"freetdm\">freetdm</option>\n";
	echo "	<option value=\"xmpp\">xmpp</option>\n";
	echo "</optgroup>";
	echo "</select>\n";
	echo "<br />\n";
	echo "Select the gateway to use with this outbound route.\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Alternate 1:\n";
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
	echo "<select name=\"gateway_2\" id=\"gateway\" class=\"formfld\" $onchange style='width: 60%;'>\n";
	echo "<option value=''></option>\n";
	echo "<optgroup label='SIP Gateways'>";
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
	echo "<optgroup label='Additional Options'>";
	echo "	<option value=\"enum\">enum</option>\n";
	echo "	<option value=\"freetdm\">freetdm</option>\n";
	echo "	<option value=\"xmpp\">xmpp</option>\n";
	echo "</optgroup>";
	echo "</select>\n";
	echo "<br />\n";
	echo "Select another gateway as an alternative to use if the first one fails.\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Alternate 2:\n";
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
	echo "<select name=\"gateway_3\" id=\"gateway\" class=\"formfld\" $onchange style='width: 60%;'>\n";
	echo "<option value=''></option>\n";
	echo "<optgroup label='SIP Gateways'>";
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
	echo "<optgroup label='Additional Options'>";
	echo "	<option value=\"enum\">enum</option>\n";
	echo "	<option value=\"freetdm\">freetdm</option>\n";
	echo "	<option value=\"xmpp\">xmpp</option>\n";
	echo "</optgroup>";
	echo "</select>\n";
	echo "<br />\n";
	echo "Select another gateway as an alternative to use if the second one fails.\n";
	echo "</td>\n";
	echo "</tr>\n";


	echo "<tr>\n";
	echo "  <td valign=\"top\" class=\"vncellreq\">Dialplan Expression:</td>\n";
	echo "  <td align='left' class=\"vtable\">";
	echo "    <textarea name=\"dialplan_expression\" id=\"dialplan_expression\" class=\"formfld\" style='width: 60%;' cols=\"30\" rows=\"4\" wrap=\"off\"></textarea>\n";
	echo "    <br>\n";
	echo "    <select name='dialplan_expression_select' id='dialplan_expression_select' onchange=\"document.getElementById('dialplan_expression').value += document.getElementById('dialplan_expression_select').value + '\\n';\" class='formfld' style='width: 60%;'>\n";
	echo "    <option></option>\n";
	echo "    <option value='^(\\d{2})\$'>2 digits</option>\n";
	echo "    <option value='^(\\d{3})\$'>3 digits</option>\n";
	echo "    <option value='^(\\d{4})\$'>4 digits</option>\n";
	echo "    <option value='^(\\d{5})\$'>5 digits</option>\n";
	echo "    <option value='^(\\d{6})\$'>6 digits</option>\n";
	echo "    <option value='^(\\d{7})\$'>7 digits local</option>\n";
	echo "    <option value='^(\\d{8})\$'>8 digits</option>\n";
	echo "    <option value='^(\\d{9})\$'>9 digits</option>\n";
	echo "    <option value='^(\\d{10})\$'>10 digits long distance</option>\n";
	echo "    <option value='^\+?(\\d{11})\$'>11 digits long distance</option>\n";
	echo "    <option value='^(\\d{12})\$'>12 digits</option>\n";
	echo "    <option value='^(\\d{13})\$'>13 digits</option>\n";
	echo "    <option value='^(\\d{14})\$'>14 digits</option>\n";
	echo "    <option value='^(\\d{15})\$'>15 digits International</option>\n";
	echo "    <option value='^(311)\$'>311 information</option>\n";
	echo "    <option value='^(411)\$'>411 information</option>\n";
	echo "    <option value='^(911)\$'>911 emergency</option>\n";
	echo "    <option value='^1?(8(00|55|66|77|88)[2-9]\\d{6})\$'>toll free</option>\n";
	echo "    <option value='^9(\\d{2})\$'>Dial 9 then 2 digits</option>\n";
	echo "    <option value='^9(\\d{3})\$'>Dial 9 then 3 digits</option>\n";
	echo "    <option value='^9(\\d{4})\$'>Dial 9 then 4 digits</option>\n";
	echo "    <option value='^9(\\d{5})\$'>Dial 9 then 5 digits</option>\n";
	echo "    <option value='^9(\\d{6})\$'>Dial 9 then 6 digits</option>\n";
	echo "    <option value='^9(\\d{7})\$'>Dial 9 then 7 digits</option>\n";
	echo "    <option value='^9(\\d{8})\$'>Dial 9 then 8 digits</option>\n";
	echo "    <option value='^9(\\d{9})\$'>Dial 9 then 9 digits</option>\n";
	echo "    <option value='^9(\\d{10})\$'>Dial 9 then 10 digits</option>\n";
	echo "    <option value='^9(\\d{11})\$'>Dial 9 then 11 digits</option>\n";
	echo "    <option value='^9(\\d{12})\$'>Dial 9 then 12 digits</option>\n";
	echo "    <option value='^9(\\d{13})\$'>Dial 9 then 13 digits</option>\n";
	echo "    <option value='^9(\\d{14})\$'>Dial 9 then 14 digits</option>\n";
	echo "    <option value='^9(\\d{15})\$'>Dial 9 then 15 digits</option>\n";
	echo "    </select>\n";
	echo "    <span class=\"vexpl\">\n";
	echo "    <br />\n";
	echo "    Shortcut to create the outbound dialplan entries for this Gateway. \n";
	echo "    </span></td>\n";
	echo "</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Prefix:\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' style='width: 60%;' type='text' name='prefix_number' maxlength='255' value=\"$prefix_number\">\n";
	echo "<br />\n";
	echo "Enter a prefix number to add to the beginning of the destination number.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Limit:\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' style='width: 60%;' type='text' name='limit' maxlength='255' value=\"$limit\">\n";
	echo "<br />\n";
	echo "Enter limit to restrict the number of outbound calls.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Order:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='dialplan_order' class='formfld' style='width: 60%;'>\n";
	//echo "              <option></option>\n";
	if (strlen(htmlspecialchars($dialplan_order))> 0) {
		echo "              <option selected='yes' value='".htmlspecialchars($dialplan_order)."'>".htmlspecialchars($dialplan_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		if (strlen($i) == 1) { echo "              <option value='00$i'>00$i</option>\n"; }
		if (strlen($i) == 2) { echo "              <option value='0$i'>0$i</option>\n"; }
		if (strlen($i) == 3) { echo "              <option value='$i'>$i</option>\n"; }
		$i++;
	}
	echo "              </select>\n";
	echo "<br />\n";
	echo "Select the order number. The order number determines the order of the outbound routes when there is more than one.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='dialplan_enabled' style='width: 60%;'>\n";
	//echo "    <option value=''></option>\n";
	if ($dialplan_enabled == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($dialplan_enabled == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Choose to enable or disable the outbound route.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Description:\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' style='width: 60%;' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
	echo "<br />\n";
	echo "Enter a description for the outbound route.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td colspan='5' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "		<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "	</td>\n";
	echo "</tr>";

	echo "</table>";
	echo "</div>";
	echo "</form>";

	echo "</td>\n";
	echo "</tr>";
	echo "</table>";
	echo "</div>";

	echo "<br><br>";

//show the footer
	require_once "includes/footer.php";
?>