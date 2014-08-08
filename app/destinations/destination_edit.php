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
	Portions created by the Initial Developer are Copyright (C) 2013-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('destination_add') || permission_exists('destination_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billing/app_config.php")) {
	require_once "app/billing/resources/functions/currency.php";
	require_once "app/billing/resources/functions/rating.php";
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$destination_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
		$destination_type = check_str($_POST["destination_type"]);
		$destination_number = check_str($_POST["destination_number"]);
		$db_destination_number = check_str($_POST["db_destination_number"]);
		$regex_destination_number = str_replace("+", "\\+", $destination_number);
		$destination_caller_id_name = check_str($_POST["destination_caller_id_name"]);
		$destination_caller_id_number = check_str($_POST["destination_caller_id_number"]);
		$destination_cid_name_prefix = check_str($_POST["destination_cid_name_prefix"]);
		$destination_context = check_str($_POST["destination_context"]);
		$fax_uuid = check_str($_POST["fax_uuid"]);
		$destination_enabled = check_str($_POST["destination_enabled"]);
		$destination_description = check_str($_POST["destination_description"]);
		$destination_sell = check_float($_POST["destination_sell"]);
		$currency = check_str($_POST["currency"]);
		$destination_buy = check_float($_POST["destination_buy"]);
		$currency_buy = check_str($_POST["currency_buy"]);
		$destination_accountcode = check_str($_POST["destination_accountcode"]);
		$destination_carrier = check_str($_POST["destination_carrier"]);
	}

//unset the db_destination_number
	unset($_POST["db_destination_number"]);

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	//get the uuid
		if ($action == "update") {
			$destination_uuid = check_str($_POST["destination_uuid"]);
		}

	//check for all required data
		$msg = '';
		if (strlen($destination_type) == 0) { $msg .= $text['message-required']." ".$text['label-destination_type']."<br>\n"; }
		if (strlen($destination_number) == 0) { $msg .= $text['message-required']." ".$text['label-destination_number']."<br>\n"; }
		//if (strlen($destination_caller_id_name) == 0) { $msg .= $text['message-required']." ".$text['label-destination_caller_id_name']."<br>\n"; }
		//if (strlen($destination_caller_id_number) == 0) { $msg .= $text['message-required']." ".$text['label-destination_caller_id_number']."<br>\n"; }
		if (strlen($destination_context) == 0) { $msg .= $text['message-required']." ".$text['label-destination_context']."<br>\n"; }
		if (strlen($destination_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-destination_enabled']."<br>\n"; }

	//check for duplicates
		if ($action == "add" || $destination_number != $db_destination_number) {
			$sql = "select count(*) as num_rows from v_destinations ";
			$sql .= "where destination_number = '".$destination_number."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] > 0) {
					$msg .= $text['message-duplicate']."<br>\n";
				}
				unset($prep_statement);
			}
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

	//add or update the database
		if ($_POST["persistformvar"] != "true") {

			//add the domain_uuid
				$_POST["domain_uuid"] = $_SESSION['domain_uuid'];

			//add or update the dialplan if the destination number is set
				if (strlen($destination_number) > 0) {
					//get the array
						$dialplan_details = $_POST["dialplan_details"];

					//remove the array from the HTTP POST
						unset($_POST["dialplan_details"]);

					//array cleanup
						$x = 0;
						foreach ($dialplan_details as $row) {
							//unset the empty row
								if (strlen($row["dialplan_detail_data"]) == 0) {
									unset($dialplan_details[$x]);
								}
							//increment the row
								$x++;
						}

					//check to see if the dialplan exists
						if (strlen($dialplan_uuid) > 0) {
							$sql = "select dialplan_uuid from v_dialplans ";
							$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
							$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
							$prep_statement = $db->prepare($sql);
							if ($prep_statement) {
								$prep_statement->execute();
								$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
								if (strlen($row['dialplan_uuid']) > 0) {
									$dialplan_uuid = $row['dialplan_uuid'];
								}
								else {
									$dialplan_uuid = "";
								}
								unset($prep_statement);
							}
							else {
								$dialplan_uuid = "";
							}
						}

					//build the dialplan array
						$dialplan["app_uuid"] = "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4";
						if (strlen($dialplan_uuid) > 0) {
							$dialplan["dialplan_uuid"] = $dialplan_uuid;
						}
						$dialplan["domain_uuid"] = $_SESSION['domain_uuid'];
						$dialplan["dialplan_name"] = format_phone($destination_number);
						$dialplan["dialplan_number"] = $destination_number;
						$dialplan["dialplan_context"] = $destination_context;
						$dialplan["dialplan_continue"] = "false";
						$dialplan["dialplan_order"] = "100";
						$dialplan["dialplan_enabled"] = $destination_enabled;
						$dialplan["dialplan_description"] = $destination_description;
						$dialplan_detail_order = 10;

						//add the public condition
							$y = 0;
							$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
							$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
							$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "context";
							$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "public";
							$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
							$y++;

						//increment the dialplan detail order
							$dialplan_detail_order = $dialplan_detail_order + 10;

						//check the destination number
							$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
							$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
							$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
							$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $regex_destination_number;
							$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
							$y++;

						//increment the dialplan detail order
							$dialplan_detail_order = $dialplan_detail_order + 10;

						//set the caller id name prefix
							if (strlen($destination_cid_name_prefix) > 0) {
								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "effective_caller_id_name=".$destination_cid_name_prefix."#\${caller_id_name}";
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$y++;

								//increment the dialplan detail order
								$dialplan_detail_order = $dialplan_detail_order + 10;
							}

						//set the call accountcode
							if (strlen($destination_accountcode) > 0) {
								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=".$destination_accountcode;
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$y++;
	
								//increment the dialplan detail order
								$dialplan_detail_order = $dialplan_detail_order + 10;
							}

						//set the call carrier
							if (strlen($destination_carrier) > 0) {
								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "carrier=$destination_carrier";
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$y++;

						//increment the dialplan detail order
								$dialplan_detail_order = $dialplan_detail_order + 10;
							}

					//add fax detection
						if (strlen($fax_uuid) > 0) {
							//get the fax information
								$sql = "select * from v_fax ";
								$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
								$sql .= "and fax_uuid = '".$fax_uuid."' ";
								$prep_statement = $db->prepare(check_sql($sql));
								$prep_statement->execute();
								$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
								foreach ($result as &$row) {
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
								unset ($prep_statement);

							//add set tone detect_hits=1
								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "tone_detect_hits=1";
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$y++;
		
							//increment the dialplan detail order
								$dialplan_detail_order = $dialplan_detail_order + 10;
		
							// execute on tone detect
								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "execute_on_tone_detect=transfer ".$fax_extension." XML ".$_SESSION["context"];
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$y++;

							//increment the dialplan detail order
								$dialplan_detail_order = $dialplan_detail_order + 10;

							//add tone_detect fax 1100 r +5000
								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "tone_detect";
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "fax 1100 r +5000";
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$y++;

							//increment the dialplan detail order
								$dialplan_detail_order = $dialplan_detail_order + 10;

							// execute on tone detect
								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "sleep";
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "3000";
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$y++;

							//increment the dialplan detail order
								$dialplan_detail_order = $dialplan_detail_order + 10;
						}

					//add the actions
						foreach ($dialplan_details as $row) {
							if (strlen($row["dialplan_detail_data"]) > 1) {
								$actions = explode(":", $row["dialplan_detail_data"]);
								$dialplan_detail_type = array_shift($actions);
								$dialplan_detail_data = join(':', $actions);

								$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
								$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
								$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $dialplan_detail_type;
								$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $dialplan_detail_data;
								$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
								$dialplan_detail_order = $dialplan_detail_order + 10;
								$y++;
							}
						}

					//delete the previous details
						if(strlen($dialplan_uuid) > 0) {
							$sql = "delete from v_dialplan_details ";
							$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
							$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);
						}

					//save the dialplan
						$orm = new orm;
						$orm->name('dialplans');
						if (isset($dialplan["dialplan_uuid"])) {
							$orm->uuid($dialplan["dialplan_uuid"]);
						}
						$orm->save($dialplan);
						$dialplan_response = $orm->message;
						//print_r($dialplan_response);

					//synchronize the xml config
						save_dialplan_xml();

					//clear memcache
						$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
						if ($fp) {
							$switch_cmd = "memcache delete dialplan:".$destination_context;
							$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						}

				} //add or update the dialplan if the destination number is set

			//get the destination_uuid
				if (strlen($dialplan_response['uuid']) > 0) {
					$_POST["dialplan_uuid"] = $dialplan_response['uuid'];
				}

			//save the destination
				$orm = new orm;
				$orm->name('destinations');
				if (strlen($destination_uuid) > 0) {
					$orm->uuid($destination_uuid);
				}
				$orm->save($_POST);
				$message = $orm->message;
				$destination_response = $orm->message;

			//get the destination_uuid
				if (strlen($destination_response['uuid']) > 0) {
					$destination_uuid = $destination_response['uuid'];
				}

			//redirect the user
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
					// billing
					if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billing/app_config.php")){
						$db2 = new database;
						$db2->sql = "SELECT currency, billing_uuid, balance FROM v_billings WHERE type_value='$destination_accountcode'";
						$db2->result = $db2->execute();
						$default_currency = (strlen($_SESSION['billing']['currency']['text'])?$_SESSION['billing']['currency']['text']:'USD');
						$billing_currency = (strlen($db2->result[0]['currency'])?$db2->result[0]['currency']:$default_currency);
						$destination_sell_current_currency = currency_convert($destination_sell,$billing_currency,$currency);
						$billing_uuid = $db2->result[0]['billing_uuid'];
						$balance = $db2->result[0]['balance'];
						unset($db2->sql, $db2->result);

						$balance -= $destination_sell_current_currency;
						$db2->sql = "UPDATE v_billings SET balance = $balance, old_balance = $balance WHERE type_value='$destination_accountcode'";
						$db2->result = $db2->execute();
						unset($db2->sql, $db2->result);

						$billing_invoice_uuid = uuid();
						$user_uuid = check_str($_SESSION['user_uuid']);
						$settled=1;
						$mc_gross = -1 * $destination_sell_current_currency;
						$post_payload = serialize($_POST);
						$db2->sql = "INSERT INTO v_billing_invoices (billing_invoice_uuid, billing_uuid, payer_uuid, billing_payment_date, settled, amount, debt, post_payload,plugin_used, domain_uuid) VALUES ('$billing_invoice_uuid', '$billing_uuid', '$user_uuid', NOW(), $settled, $mc_gross, $balance, '$post_payload', 'DID $destination_number Assigment', '".$_SESSION['domain_uuid']."' )";
						$db2->result = $db2->execute();
						unset($db2->sql, $db2->result);

					}
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header("Location: destination_edit.php?id=".$destination_uuid);
				return;
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$destination_uuid = $_GET["id"];
		$orm = new orm;
		$orm->name('destinations');
		$orm->uuid($destination_uuid);
		$result = $orm->find()->get();
		foreach ($result as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$destination_type = $row["destination_type"];
			$destination_number = $row["destination_number"];
			$destination_caller_id_name = $row["destination_caller_id_name"];
			$destination_caller_id_number = $row["destination_caller_id_number"];
			$destination_cid_name_prefix = $row["destination_cid_name_prefix"];
			$destination_context = $row["destination_context"];
			$fax_uuid = $row["fax_uuid"];
			$destination_enabled = $row["destination_enabled"];
			$destination_description = $row["destination_description"];
			$currency = $row["currency"];
			$destination_sell = $row["destination_sell"];
			$destination_buy = $row["destination_buy"];
			$currency_buy = $row["currency_buy"];
			$destination_accountcode = $row["destination_accountcode"];
			$destination_carrier = $row["destination_carrier"];
			break; //limit to 1 row
		}
	}

//get the dialplan details in an array
	$sql = "select * from v_dialplan_details ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
	$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$dialplan_details = $prep_statement->fetchAll(PDO::FETCH_NAMED);;
	unset ($prep_statement, $sql);

//add an empty row to the array
	$x = count($dialplan_details);
	$limit = $x + 1;
	while($x < $limit) {
		$dialplan_details[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
		$dialplan_details[$x]['dialplan_uuid'] = $dialplan_uuid;
		//$dialplan_details[$x]['dialplan_detail_uuid'] = '';
		//$dialplan_details[$x]['dialplan_detail_tag'] = '';
		$dialplan_details[$x]['dialplan_detail_type'] = '';
		$dialplan_details[$x]['dialplan_detail_data'] = '';
		//$dialplan_details[$x]['dialplan_detail_break'] = '';
		//$dialplan_details[$x]['dialplan_detail_inline'] = '';
		//$dialplan_details[$x]['dialplan_detail_group'] = '';
		$dialplan_details[$x]['dialplan_detail_order'] = '';
		$x++;
	}
	unset($limit);

//set the defaults
	if (strlen($destination_type) == 0) { $destination_type = 'inbound'; }
	if (strlen($destination_context) == 0) { $destination_context = 'public'; }

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-destination-edit'];
	}
	else if ($action == "add") {
		$document['title'] = $text['title-destination-add'];
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-destination-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-destination-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='destinations.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description-destinations']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='destination_type'>\n";
	switch ($destination_type) {
		case "inbound" : 	$selected[1] = "selected='selected'";	break;
		case "outbound" : 	$selected[2] = "selected='selected'";	break;
	}
	echo "	<option value='inbound' ".$selected[1].">".$text['option-type_inbound']."</option>\n";
	echo "	<option value='outbound' ".$selected[2].">".$text['option-type_outbound']."</option>\n";
	unset($selected);
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-destination_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_number' maxlength='255' value=\"$destination_number\">\n";
	echo "<br />\n";
	echo $text['description-destination_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('outbound_caller_id_select')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_caller_id_name'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_caller_id_name' maxlength='255' value=\"$destination_caller_id_name\">\n";
		echo "<br />\n";
		echo $text['description-destination_caller_id_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_caller_id_number'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_caller_id_number' maxlength='255' value=\"$destination_caller_id_number\">\n";
		echo "<br />\n";
		echo $text['description-destination_caller_id_number']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_context'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_context' maxlength='255' value=\"$destination_context\">\n";
	echo "<br />\n";
	echo $text['description-destination_context']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-detail_action'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "			<table width='52%' border='0' cellpadding='2' cellspacing='0'>\n";
	$x = 0;
	$order = 10;
	foreach($dialplan_details as $row) {
		if ($row["dialplan_detail_type"] == "transfer" || $row["dialplan_detail_type"] == "bridge" || $row["dialplan_detail_type"] == "") {
			echo "				<tr>\n";
			echo "					<td>\n";
			if (strlen($row['dialplan_detail_uuid']) > 0) {
				echo "	<input name='dialplan_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$row['dialplan_detail_uuid']."\">\n";
			}
			echo "	<input name='dialplan_details[".$x."][dialplan_detail_type]' type='hidden' value=\"".$row['dialplan_detail_type']."\">\n";
			echo "	<input name='dialplan_details[".$x."][dialplan_detail_order]' type='hidden' value=\"".$order."\">\n";

			//echo $order."<br />\n";
			//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
			$data = $row['dialplan_detail_data'];
			$label = explode("XML", $data);
			$divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
			$detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
			switch_select_destination("dialplan", $label[0], "dialplan_details[".$x."][dialplan_detail_data]", $detail_action, "width: 60%;", $row['dialplan_detail_type']);

			echo "					</td>\n";
			//echo "					<td>\n";
			//echo "						<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
			//echo "					</td>\n";
			echo "					<td class='list_control_icons' style='width: 25px;'>";
			if (strlen($row['destination_uuid']) > 0) {
				//echo 					"<a href='estination_edit.php?id=".$row['destination_uuid']."&destination_uuid=".$row['destination_uuid']."' alt='edit'>$v_link_label_edit</a>";
				echo					"<a href='destination_delete.php?id=".$row['destination_uuid']."&destination_uuid=".$row['destination_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "					</td>\n";
			echo "				</tr>\n";
		}
		$order = $order + 10;
		$x++;
	}
	echo "			</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-fax_uuid'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$sql = "select * from v_fax ";
	$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
	$sql .= "order by fax_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	echo "	<select name='fax_uuid' id='fax_uuid' class='formfld' style='".$select_style."'>\n";
	echo "	<option value=''></option>\n";
	foreach ($result as &$row) {
		if ($row["fax_uuid"] == $fax_uuid) {
			echo "		<option value='".$row["fax_uuid"]."' selected='selected'>".$row["fax_extension"]." ".$row["fax_name"]."</option>\n";
		}
		else {
			echo "		<option value='".$row["fax_uuid"]."'>".$row["fax_extension"]." ".$row["fax_name"]."</option>\n";
		}
	}
	echo "	</select>\n";
	unset ($prep_statement, $extension);
	echo "	<br />\n";
	echo "	".$text['description-fax_uuid']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_cid_name_prefix'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_cid_name_prefix' maxlength='255' value=\"$destination_cid_name_prefix\">\n";
	echo "<br />\n";
	echo $text['description-destination_cid_name_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	// billing
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billing/app_config.php")){
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "  ".$text['label-monthly_price'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' min='0' step='0.01' name='destination_sell' maxlength='255' value=\"$destination_sell\">\n";
		currency_select($currency);
		echo "<br />\n";
		echo $text['description-monthly_price']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "  ".$text['label-monthly_price_buy'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' min='0' step='0.01' name='destination_buy' maxlength='255' value=\"$destination_buy\">\n";
		currency_select($currency_buy,0,'currency_buy');
		echo "<br />\n";
		echo $text['description-monthly_price_buy']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-carrier'].":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='destination_carrier' maxlength='255' value=\"$destination_carrier\">\n";
		echo "<br />\n";
		echo $text['description-carrier']."\n";
		echo "</td>\n";

		//set the default account code
		if ($action == "add") { $destination_accountcode=$_SESSION['domain_name']; }
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-accountcode'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_accountcode' maxlength='255' value=\"$destination_accountcode\">\n";
	echo "<br />\n";
	echo $text['description-accountcode']."\n";
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billing/app_config.php")){
		echo " ".$text['billing-warning'];
	}
	echo "</td>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_enabled'].":\n";
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
	echo "	".$text['label-destination_description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_description' maxlength='255' value=\"$destination_description\">\n";
	echo "<br />\n";
	echo $text['description-destination_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='db_destination_number' value='$destination_number'>\n";
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
		echo "				<input type='hidden' name='destination_uuid' value='$destination_uuid'>\n";
	}
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "resources/footer.php";

?>