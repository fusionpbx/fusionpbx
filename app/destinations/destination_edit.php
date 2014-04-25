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
		$destination_caller_id_name = check_str($_POST["destination_caller_id_name"]);
		$destination_caller_id_number = check_str($_POST["destination_caller_id_number"]);
		$destination_context = check_str($_POST["destination_context"]);
		$fax_uuid = check_str($_POST["fax_uuid"]);
		$destination_enabled = check_str($_POST["destination_enabled"]);
		$destination_description = check_str($_POST["destination_description"]);
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$destination_uuid = check_str($_POST["destination_uuid"]);
	}

	//check for all required data
		//if (strlen($dialplan_uuid) == 0) { $msg .= "Please provide: Dialplan UUID<br>\n"; }
		//if (strlen($destination_type) == 0) { $msg .= "Please provide: Name<br>\n"; }
		//if (strlen($destination_number) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		//if (strlen($destination_caller_id_name) == 0) { $msg .= "Please provide: Caller ID Name<br>\n"; }
		//if (strlen($destination_caller_id_number) == 0) { $msg .= "Please provide: Caller ID Number<br>\n"; }
		//if (strlen($destination_context) == 0) { $msg .= "Please provide: Context<br>\n"; }
		//if (strlen($destination_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($destination_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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

			//get the array
				$dialplan_details = $_POST["dialplan_details"];

			//remove the array from the HTTP POST
				unset($_POST["dialplan_details"]);

			//add the domain_uuid
				$_POST["domain_uuid"] = $_SESSION['domain_uuid'];

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
				$dialplan["dialplan_continue"] = "true";
				$dialplan["dialplan_order"] = "100";
				$dialplan["dialplan_enabled"] = $destination_enabled;
				$dialplan["dialplan_description"] = $destination_description;
				if (strlen($dialplan_uuid) == 0) {
					$y = 0;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "context";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "public";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = "10";
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_number;
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = "10";
					$y++;
					//$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
					//$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					//$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					//$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "call_direction=inbound";
					//$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = "20";
					//$y++;
				}
				$dialplan_detail_order = 20;
				foreach ($dialplan_details as $row) {
					$actions = explode(":", $row["dialplan_detail_data"]);
					$dialplan_detail_type = array_shift($actions);
					$dialplan_detail_data = join(':', $actions);
					if (strlen($dialplan_detail_type) > 1) {	
						if (isset($row["dialplan_detail_uuid"])) {
							$dialplan["dialplan_details"][$y]["dialplan_detail_uuid"] = $row["dialplan_detail_uuid"];
						}
						$dialplan["dialplan_details"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
						$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
						$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $dialplan_detail_type;
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $dialplan_detail_data;
						if (isset($row["dialplan_detail_order"])) {
							$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $row["dialplan_detail_order"];
						}
						else {
							$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
						}
						$dialplan_detail_order = $dialplan_detail_order + 10;
						$y++;
					}
				}
	
			//save the dialplan
				$orm = new orm;
				$orm->name('dialplans');
				if (isset($dialplan["dialplan_uuid"])) {
					$orm->uuid($dialplan["dialplan_uuid"]);
				}
				$orm->save($dialplan);
				$dialplan_response = $orm->message;
	
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
				$destination_response = $orm->message;
	
			//get the destination_uuid
				if (strlen($destination_response['uuid']) > 0) {
					$destination_uuid = $destination_response['uuid'];
				}

			//synchronize the xml config
				save_dialplan_xml();

			//clear memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "memcache delete dialplan:".$destination_context;
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

			//redirect the user
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
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
			$destination_context = $row["destination_context"];
			$fax_uuid = $row["fax_uuid"];
			$destination_enabled = $row["destination_enabled"];
			$destination_description = $row["destination_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//get the dialplan details in an array
	$sql = "select * from v_dialplan_details ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
	$sql .= "and dialplan_detail_tag = 'action' ";
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
		$page["title"] = $text['title-destination-edit'];
	}
	else if ($action == "add") {
		$page["title"] = $text['title-destination-add'];
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
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='destinations.php'\" value='".$text['button-back']."'></td>\n";
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
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
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
	//echo "				<tr>\n";
	//echo "					<td class='vtable'>".$text['label-dialplan_detail_type']."</td>\n";
	//echo "					<td class='vtable'>".$text['label-dialplan_detail_data']."</td>\n";
	//echo "					<td class='vtable'>".$text['label-dialplan_detail_order']."</td>\n";
	//echo "					<td></td>\n";
	//echo "				</tr>\n";
	$x = 0;
	foreach($dialplan_details as $row) {

		if (strlen($row['dialplan_detail_uuid']) > 0) {
			echo "	<input name='dialplan_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$row['dialplan_detail_uuid']."\">\n";
		}
		//$order = $row['dialplan_detail_order'] + 10;
		//echo "	<input name='dialplan_details[".$x."][dialplan_detail_order]' type='hidden' value=\"".$order."\">\n";

		echo "				<tr>\n";
		echo "					<td>\n";
		//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
		$data = $row['dialplan_detail_data'];
		$label = explode("XML", $data);
		$detail_action = $row['dialplan_detail_type'].":".$row['dialplan_detail_data'];
		switch_select_destination("dialplan", $label[0], "dialplan_details[".$x."][dialplan_detail_data]", $detail_action, "width: 60%;", $row['dialplan_detail_type']);

		echo "					</td>\n";
		//echo "					<td>\n";
		//echo "						<input type=\"text\" name=\"dialplan_details[".$x."][dialplan_detail_order]\" class=\"formfld\" style=\"width: 90%;\"value=\"".$row['dialplan_detail_order']."\">\n";
		//echo "					</td>\n";
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

		echo "		</td>";
		echo "	</tr>";
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