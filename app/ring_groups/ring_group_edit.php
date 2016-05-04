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
	Portions created by the Initial Developer are Copyright (C) 2010-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the user from v_ring_group_users
	if ($_GET["a"] == "delete" && strlen($_REQUEST["user_uuid"]) > 0 && permission_exists("ring_group_edit")) {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$ring_group_uuid = check_str($_REQUEST["id"]);
		//delete the group from the users
			$sql = "delete from v_ring_group_users ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and ring_group_uuid = '".$ring_group_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
		//save the message to a session variable
			$_SESSION['message'] = $text['message-delete'];
		//redirect the browser
			header("Location: ring_group_edit.php?id=$ring_group_uuid");
			exit;
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$ring_group_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get total ring group count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['ring_groups']['numeric'] != '') {
			$sql = "select count(*) as num_rows from v_ring_groups where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				$total_ring_groups = $row['num_rows'];
			}
			unset($prep_statement, $row);
			if ($total_ring_groups >= $_SESSION['limit']['ring_groups']['numeric']) {
				$_SESSION['message_mood'] = 'negative';
				$_SESSION['message'] = $text['message-maximum_ring_groups'].' '.$_SESSION['limit']['ring_groups']['numeric'];
				header('Location: ring_groups.php');
				return;
			}
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//set variables from http values
			$ring_group_name = check_str($_POST["ring_group_name"]);
			$ring_group_extension = check_str($_POST["ring_group_extension"]);
			$ring_group_context = check_str($_POST["ring_group_context"]);
			$ring_group_strategy = check_str($_POST["ring_group_strategy"]);
			$ring_group_timeout_action = check_str($_POST["ring_group_timeout_action"]);
			$ring_group_cid_name_prefix = check_str($_POST["ring_group_cid_name_prefix"]);
			$ring_group_cid_number_prefix = check_str($_POST["ring_group_cid_number_prefix"]);
			$ring_group_distinctive_ring = check_str($_POST["ring_group_distinctive_ring"]);
			$ring_group_ringback = check_str($_POST["ring_group_ringback"]);
			$ring_group_skip_active = check_str($_POST["ring_group_skip_active"]);
			$ring_group_missed_call_app = check_str($_POST["ring_group_missed_call_app"]);
			$ring_group_missed_call_data = check_str($_POST["ring_group_missed_call_data"]);
			$ring_group_forward_enabled = check_str($_POST["ring_group_forward_enabled"]);
			$ring_group_forward_destination = check_str($_POST["ring_group_forward_destination"]);
			$ring_group_enabled = check_str($_POST["ring_group_enabled"]);
			$ring_group_description = check_str($_POST["ring_group_description"]);
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
			//$ring_group_timeout_action = "transfer:1001 XML default";
			$ring_group_timeout_array = explode(":", $ring_group_timeout_action);
			$ring_group_timeout_app = array_shift($ring_group_timeout_array);
			$ring_group_timeout_data = join(':', $ring_group_timeout_array);
			$destination_number = check_str($_POST["destination_number"]);
			$destination_delay = check_str($_POST["destination_delay"]);
			$destination_timeout = check_str($_POST["destination_timeout"]);
			$destination_prompt = check_str($_POST["destination_prompt"]);

		//set the context for users that are not in the superadmin group
			if (!if_group("superadmin")) {
				$ring_group_context = $_SESSION['domain_name'];
			}
	}

//assign the user
	if (strlen($_REQUEST["user_uuid"]) > 0 && strlen($_REQUEST["id"]) > 0 && $_GET["a"] != "delete") {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$extension_uuid = check_str($_REQUEST["id"]);
		//assign the user to the ring group
			$sql_insert = "insert into v_ring_group_users ";
			$sql_insert .= "(";
			$sql_insert .= "ring_group_user_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "ring_group_uuid, ";
			$sql_insert .= "user_uuid ";
			$sql_insert .= ")";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= "'".uuid()."', ";
			$sql_insert .= "'$domain_uuid', ";
			$sql_insert .= "'".$ring_group_uuid."', ";
			$sql_insert .= "'".$user_uuid."' ";
			$sql_insert .= ")";
			$db->exec($sql_insert);
		//save the message to a session variable
			$_SESSION['message'] = $text['message-add'];
		//redirect the browser
			header("Location: ring_group_edit.php?id=$ring_group_uuid");
			exit;
	}

//process the HTTP POST
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the ring group uuid
			if (strlen($ring_group_uuid) == 0) {
				$ring_group_uuid = check_str($_REQUEST["ring_group_uuid"]);
			}

		//check for all required data
			$msg = '';
			if (strlen($ring_group_name) == 0) { $msg .= $text['message-name']."<br>\n"; }
			if (strlen($ring_group_extension) == 0) { $msg .= $text['message-extension']."<br>\n"; }
			if (strlen($ring_group_strategy) == 0) { $msg .= $text['message-strategy']."<br>\n"; }
			//if (strlen($ring_group_timeout_app) == 0) { $msg .= $text['message-timeout-action']."<br>\n"; }
			//if (strlen($ring_group_cid_name_prefix) == 0) { $msg .= "Please provide: Caller ID Name Prefix<br>\n"; }
			//if (strlen($ring_group_cid_number_prefix) == 0) { $msg .= "Please provide: Caller ID Number Prefix<br>\n"; }
			//if (strlen($ring_group_ringback) == 0) { $msg .= "Please provide: Ringback<br>\n"; }
			if (strlen($ring_group_enabled) == 0) { $msg .= $text['message-enabled']."<br>\n"; }
			//if (strlen($ring_group_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
				//prep missed call values for db insert/update
					switch ($ring_group_missed_call_app) {
						case 'email':
							$ring_group_missed_call_data = str_replace(';',',',$ring_group_missed_call_data);
							$ring_group_missed_call_data = str_replace(' ','',$ring_group_missed_call_data);
							if (substr_count($ring_group_missed_call_data, ',') > 0) {
								$ring_group_missed_call_data_array = explode(',', $ring_group_missed_call_data);
								foreach ($ring_group_missed_call_data_array as $array_index => $email_address) {
									if (!valid_email($email_address)) { unset($ring_group_missed_call_data_array[$array_index]); }
								}
								//echo "<pre>".print_r($ring_group_missed_call_data_array, true)."</pre><br><br>";
								if (sizeof($ring_group_missed_call_data_array) > 0) {
									$ring_group_missed_call_data = implode(',', $ring_group_missed_call_data_array);
								}
								else {
									unset($ring_group_missed_call_app, $ring_group_missed_call_data);
								}
								//echo "Multiple Emails = ".$ring_group_missed_call_data;
							}
							else {
								//echo "Single Email = ".$ring_group_missed_call_data."<br>";
								if (!valid_email($ring_group_missed_call_data)) {
									//echo "Invalid Email<br><br>";
									unset($ring_group_missed_call_app, $ring_group_missed_call_data);
								}
							}
							break;
						case 'text':
							$ring_group_missed_call_data = str_replace('-','',$ring_group_missed_call_data);
							$ring_group_missed_call_data = str_replace('.','',$ring_group_missed_call_data);
							$ring_group_missed_call_data = str_replace('(','',$ring_group_missed_call_data);
							$ring_group_missed_call_data = str_replace(')','',$ring_group_missed_call_data);
							$ring_group_missed_call_data = str_replace(' ','',$ring_group_missed_call_data);
							if (!is_numeric($ring_group_missed_call_data)) { unset($ring_group_missed_call_app, $ring_group_missed_call_data); }
							break;
					}
					if (permission_exists('ring_group_missed_call') && $ring_group_missed_call_app != '' && $ring_group_missed_call_data != '') {
						$_POST["ring_group_missed_call_app"] = $ring_group_missed_call_app;
						$_POST["ring_group_missed_call_data"] = $ring_group_missed_call_data;
					}

				//set the app and data
					$ring_group_timeout_array = explode(":", $ring_group_timeout_action);
					$ring_group_timeout_app = array_shift($ring_group_timeout_array);
					$ring_group_timeout_data = join(':', $ring_group_timeout_array);
					$_POST["ring_group_timeout_app"] = $ring_group_timeout_app;
					$_POST["ring_group_timeout_data"] = $ring_group_timeout_data;

				//remove the action
					unset($_POST["ring_group_timeout_action"]);

				//remove the user_uuid
					unset($_POST["user_uuid"]);

				//add the domain_uuid
					if (strlen($_POST["domain_uuid"]) == 0) {
						$_POST["domain_uuid"] = $_SESSION['domain_uuid'];
					}

				//add the dialplan_uuid
					if (strlen($_POST["dialplan_uuid"]) == 0) {
						$dialplan_uuid = uuid();
						$_POST["dialplan_uuid"] = $dialplan_uuid;
					}

				//update the ring group destinations array
					$x = 0;
					foreach ($_POST["ring_group_destinations"] as $row) {
						//add the domain_uuid
							if (strlen($_POST["ring_group_destinations"][$x]["domain_uuid"]) == 0) {
								$_POST["ring_group_destinations"][$x]["domain_uuid"] = $_SESSION['domain_uuid'];
							}
						//unset the empty row
							if (strlen($_POST["ring_group_destinations"][$x]["destination_number"]) == 0) {
								unset($_POST["ring_group_destinations"][$x]);
							}
						//unset ring_group_destination_uuid if the field has no value
							if (strlen($row["ring_group_destination_uuid"]) == 0) {
								unset($_POST["ring_group_destinations"][$x]["ring_group_destination_uuid"]);
							}
						//increment the row
							$x++;
					}

				//save to the data
					$orm = new orm;
					$orm->name('ring_groups');
					if (strlen($ring_group_uuid) > 0) {
						$orm->uuid($ring_group_uuid);
					}
					$orm->save($_POST);
					$message = $orm->message;
					if (strlen($ring_group_uuid) == 0) {
						$ring_group_uuid = $message['uuid'];
						$_GET["id"] = $ring_group_uuid;
					}
			}

		//delete the dialplan details
			$sql = "delete from v_dialplan_details ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//delete from the dialplan
			$sql = "delete from v_dialplans ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//add the dialplan
			$database = new database;
			$database->db = $db;
			$database->table = "v_dialplans";
			$database->fields['domain_uuid'] = $_SESSION['domain_uuid'];
			$database->fields['dialplan_uuid'] = $dialplan_uuid;
			$database->fields['dialplan_name'] = $ring_group_name;
			$database->fields['dialplan_order'] = '333';
			$database->fields['dialplan_context'] = $ring_group_context;
			$database->fields['dialplan_enabled'] = 'true';
			$database->fields['dialplan_description'] = $ring_group_description;
			$database->fields['app_uuid'] = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
			$database->add();

		//add the dialplan details
			$database->table = "v_dialplan_details";
			$database->fields['domain_uuid'] = $_SESSION['domain_uuid'];
			$database->fields['dialplan_uuid'] = $dialplan_uuid;
			$database->fields['dialplan_detail_uuid'] = uuid();
			$database->fields['dialplan_detail_tag'] = 'condition'; //condition, action, antiaction
			$database->fields['dialplan_detail_type'] = 'destination_number';
			$database->fields['dialplan_detail_data'] = '^'.$ring_group_extension.'$';
			$database->fields['dialplan_detail_order'] = '000';
			$database->add();

		//add the dialplan details
			$database->table = "v_dialplan_details";
			$database->fields['domain_uuid'] = $_SESSION['domain_uuid'];
			$database->fields['dialplan_uuid'] = $dialplan_uuid;
			$database->fields['dialplan_detail_uuid'] = uuid();
			$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
			$database->fields['dialplan_detail_type'] = 'set';
			$database->fields['dialplan_detail_data'] = 'ring_group_uuid='.$ring_group_uuid;
			$database->fields['dialplan_detail_order'] = '025';
			$database->add();

		//add the dialplan details
			$database->table = "v_dialplan_details";
			$database->fields['domain_uuid'] = $_SESSION['domain_uuid'];
			$database->fields['dialplan_uuid'] = $dialplan_uuid;
			$database->fields['dialplan_detail_uuid'] = uuid();
			$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
			$database->fields['dialplan_detail_type'] = 'lua';
			$database->fields['dialplan_detail_data'] = 'app.lua ring_groups';
			$database->fields['dialplan_detail_order'] = '030';
			$database->add();

		//save the xml
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$ring_group_context);

		//set the message
			if ($action == "add") {
				//save the message to a session variable
					$_SESSION['message'] = $text['message-add'];
				//redirect the browser
					header("Location: ring_group_edit.php?id=$ring_group_uuid");
					exit;
			}
			if ($action == "update") {
				//save the message to a session variable
					$_SESSION['message'] = $text['message-update'];
			}

	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (strlen($ring_group_uuid) == 0) { $ring_group_uuid = check_str($_GET["id"]); }
	if (strlen($ring_group_uuid) > 0) {
		$sql = "select * from v_ring_groups ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and ring_group_uuid = '$ring_group_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$ring_group_name = $row["ring_group_name"];
			$ring_group_extension = $row["ring_group_extension"];
			$ring_group_context = $row["ring_group_context"];
			$ring_group_strategy = $row["ring_group_strategy"];
			$ring_group_timeout_app = $row["ring_group_timeout_app"];
			$ring_group_timeout_data = $row["ring_group_timeout_data"];
			$ring_group_cid_name_prefix = $row["ring_group_cid_name_prefix"];
			$ring_group_cid_number_prefix = $row["ring_group_cid_number_prefix"];
			$ring_group_distinctive_ring = $row["ring_group_distinctive_ring"];
			$ring_group_ringback = $row["ring_group_ringback"];
			$ring_group_skip_active = $row["ring_group_skip_active"];
			$ring_group_missed_call_app = $row["ring_group_missed_call_app"];
			$ring_group_missed_call_data = $row["ring_group_missed_call_data"];
			$ring_group_forward_enabled = $row["ring_group_forward_enabled"];
			$ring_group_forward_destination = $row["ring_group_forward_destination"];
			$ring_group_enabled = $row["ring_group_enabled"];
			$ring_group_description = $row["ring_group_description"];
			$dialplan_uuid = $row["dialplan_uuid"];
		}
		unset ($prep_statement);
		if (strlen($ring_group_timeout_app) > 0) {
			$ring_group_timeout_action = $ring_group_timeout_app.":".$ring_group_timeout_data;
		}
	}

//get the ring group destination array
	if ($action == "add") { $x = 0; $limit = 5; }
	if (strlen($ring_group_uuid) > 0) {
		$sql = "select * from v_ring_group_destinations ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and ring_group_uuid = '".$ring_group_uuid."' ";
		$sql .= "order by destination_delay, destination_number asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$ring_group_destinations = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$x = count($ring_group_destinations);
		$limit = $x + 1;
	}

	while($x < $limit) {
		$ring_group_destinations[$x]['destination_number'] = '';
		$ring_group_destinations[$x]['destination_delay'] = '';
		$ring_group_destinations[$x]['destination_timeout'] = '';
		$ring_group_destinations[$x]['destination_prompt'] = '';
		$x++;
	}
	unset($limit);

//get the ring group users
	if (strlen($ring_group_uuid) > 0) {
		$sql = "select u.username, r.user_uuid, r.ring_group_uuid from v_ring_group_users as r, v_users as u ";
		$sql .= "where r.user_uuid = u.user_uuid  ";
		$sql .= "and u.user_enabled = 'true' ";
		$sql .= "and r.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and r.ring_group_uuid = '".$ring_group_uuid."' ";
		$sql .= "order by u.username asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$ring_group_users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		//$ring_group_users[$x]['username'] = '';
	}

//set defaults
	if (strlen($ring_group_skip_active) == 0) { $ring_group_skip_active = 'false'; }
	if (strlen($ring_group_enabled) == 0) { $ring_group_enabled = 'true'; }

//set the context for users that are not in the superadmin group
	if (strlen($ring_group_context) == 0) {
		$ring_group_context = $_SESSION['domain_name'];
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['label-ring-group']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='ring_groups.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2' valign='top'>\n";
	echo $text['description']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_name' maxlength='255' value=\"$ring_group_name\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_extension' maxlength='255' value=\"$ring_group_extension\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-strategy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_strategy' onchange=\"getElementById('destination_delayorder').innerHTML = (this.selectedIndex == 1 || this.selectedIndex == 3) ? '".$text['label-destination_order']."' : '".$text['label-destination_delay']."';\">\n";
	echo "	<option value='simultaneous' ".(($ring_group_strategy == "simultaneous") ? "selected='selected'" : null).">".$text['option-simultaneous']."</option>\n";
	echo "	<option value='sequence' ".(($ring_group_strategy == "sequence") ? "selected='selected'" : null).">".$text['option-sequence']."</option>\n";
	echo "	<option value='enterprise' ".(($ring_group_strategy == "enterprise") ? "selected='selected'" : null).">".$text['option-enterprise']."</option>\n";
	echo "	<option value='rollover' ".(($ring_group_strategy == "rollover") ? "selected='selected'" : null).">".$text['option-rollover']."</option>\n";
	echo "	<option value='random' ".(($ring_group_strategy == "random") ? "selected='selected'" : null).">".$text['option-random']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-strategy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncellreq' valign='top'>".$text['label-destinations']."</td>";
	echo "		<td class='vtable' align='left'>";

	echo "			<table border='0' cellpadding='2' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<td class='vtable'>".$text['label-destination_number']."</td>\n";
	echo "					<td class='vtable' id='destination_delayorder'>";
	echo 						($ring_group_strategy == 'sequence' || $ring_group_strategy == 'rollover') ? $text['label-destination_order'] : $text['label-destination_delay'];
	echo "					</td>\n";
	echo "					<td class='vtable'>".$text['label-destination_timeout']."</td>\n";
	if (permission_exists('ring_group_prompt')) {
		echo "				<td class='vtable'>".$text['label-destination_prompt']."</td>\n";
	}
	echo "					<td></td>\n";
	echo "				</tr>\n";
	$x = 0;
	foreach($ring_group_destinations as $row) {
		if (strlen($row['destination_delay']) == 0) { $row['destination_delay'] = "0"; }
		if (strlen($row['destination_timeout']) == 0) { $row['destination_timeout'] = "30"; }

		if (strlen($row['ring_group_destination_uuid']) > 0) {
			echo "		<input name='ring_group_destinations[".$x."][ring_group_destination_uuid]' type='hidden' value=\"".$row['ring_group_destination_uuid']."\">\n";
		}

		echo "			<tr>\n";
		echo "				<td>\n";
		echo "					<input type=\"text\" name=\"ring_group_destinations[".$x."][destination_number]\" class=\"formfld\" style=\"width: 90%;\"value=\"".$row['destination_number']."\">\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<select name='ring_group_destinations[".$x."][destination_delay]' class='formfld' style='width:55px'>\n";
		$i=0;
		while($i<=300) {
			if ($i == $row['destination_delay']) {
				echo "				<option value='$i' selected='selected'>$i</option>\n";
			}
			else {
				echo "				<option value='$i'>$i</option>\n";
			}
			$i = $i + 5;
		}
		echo "					</select>\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<select name='ring_group_destinations[".$x."][destination_timeout]' class='formfld' style='width:55px'>\n";
		$i=5;
		while($i<=300) {
			if ($i == $row['destination_timeout']) {
				echo "				<option value='$i' selected='selected'>$i</option>\n";
			}
			else {
				echo "				<option value='$i'>$i</option>\n";
			}
			$i = $i + 5;
		}
		echo "					</select>\n";
		echo "				</td>\n";

		if (permission_exists('ring_group_prompt')) {
			echo "			<td>\n";
			echo "				<select class='formfld' style='width: 90px;' name='ring_group_destinations[".$x."][destination_prompt]'>\n";
			echo "					<option value=''></option>\n";
			echo "					<option value='1' ".(($row['destination_prompt'])?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
			//echo "				<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
			echo "				</select>\n";
			echo "			</td>\n";
		}
		echo "				<td>&nbsp;</td>\n";
		echo "				<td class='list_control_icons' style='width: 25px;'>";
		if (strlen($row['ring_group_destination_uuid']) > 0) {
			echo				"<a href='ring_group_destination_delete.php?id=".$row['ring_group_destination_uuid']."&ring_group_uuid=".$row['ring_group_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
		}
		echo "				</td>\n";
		echo "			</tr>\n";
		$x++;
	}
	echo "			</table>\n";
	echo "			".$text['description-destinations']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-timeout_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'ring_group_timeout_action', $ring_group_timeout_action);
	echo "	<br />\n";
	echo "	".$text['description-timeout_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-cid-name-prefix']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_cid_name_prefix' maxlength='255' value='$ring_group_cid_name_prefix'>\n";
	echo "<br />\n";
	echo $text['description-cid-name-prefix']." \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-cid-number-prefix']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='ring_group_cid_number_prefix' maxlength='255' min='0' step='1' value='$ring_group_cid_number_prefix'>\n";
	echo "<br />\n";
	echo $text['description-cid-number-prefix']." \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-distinctive_ring']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_distinctive_ring' maxlength='255' value='$ring_group_distinctive_ring'>\n";
	echo "<br />\n";
	echo $text['description-distinctive_ring']." \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	 ".$text['label-ringback']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$select_options = "";
	if ($ring_group_ringback == "\${us-ring}" || $ring_group_ringback == "us-ring") {
		$select_options .= "		<option value='\${us-ring}' selected='selected'>".$text['option-usring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${us-ring}'>".$text['option-usring']."</option>\n";
	}
	if ($ring_group_ringback == "\${pt-ring}" || $ring_group_ringback == "pt-ring") {
		$select_options .= "		<option value='\${pt-ring}' selected='selected'>".$text['option-ptring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${pt-ring}'>".$text['option-ptring']."</option>\n";
	}
	if ($ring_group_ringback == "\${fr-ring}" || $ring_group_ringback == "fr-ring") {
		$select_options .= "		<option value='\${fr-ring}' selected='selected'>".$text['option-frring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${fr-ring}'>".$text['option-frring']."</option>\n";
	}
	if ($ring_group_ringback == "\${uk-ring}" || $ring_group_ringback == "uk-ring") {
		$select_options .= "		<option value='\${uk-ring}' selected='selected'>".$text['option-ukring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${uk-ring}'>".$text['option-ukring']."</option>\n";
	}
	if ($ring_group_ringback == "\${rs-ring}" || $ring_group_ringback == "rs-ring") {
		$select_options .= "		<option value='\${rs-ring}' selected='selected'>".$text['option-rsring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${rs-ring}'>".$text['option-rsring']."</option>\n";
	}
	if ($ring_group_ringback == "\${it-ring}" || $ring_group_ringback == "it-ring") {
		$select_options .= "		<option value='\${it-ring}' selected='selected'>".$text['option-itring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${it-ring}'>".$text['option-itring']."</option>\n";
	}
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/music_on_hold')) {
		require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
		$moh = new switch_music_on_hold;
		$moh->select_name = "ring_group_ringback";
		$moh->select_value = $ring_group_ringback;
		$moh->select_options = $select_options;
		echo $moh->select();
	}
	else {
		echo "	<select class='formfld' name='ring_group_ringback'>\n";
		//echo "	<option value=''></option>\n";
		echo $select_options;
		echo "	</select>\n";
	}

	echo "<br />\n";
	echo $text['description-ringback']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-user_list']."</td>";
	echo "		<td class='vtable'>";
	echo "			<table width='52%'>\n";
	if (isset($ring_group_users)) foreach($ring_group_users as $field) {
		echo "			<tr>\n";
		echo "				<td class='vtable'>".$field['username']."</td>\n";
		echo "				<td>\n";
		echo "					<a href='ring_group_edit.php?id=".$ring_group_uuid."&user_uuid=".$field['user_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
		echo "				</td>\n";
		echo "			</tr>\n";
	}
	echo "			</table>\n";
	echo "			<br />\n";
	$sql = "SELECT * FROM v_users ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "			<select name=\"user_uuid\" class='formfld' style='width: auto;'>\n";
	echo "			<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
	}
	echo "			</select>";
	echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
	unset($sql, $result);
	echo "			<br>\n";
	echo "			".$text['description-user_list']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-skip_active']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_skip_active'>\n";
	if ($ring_group_skip_active == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ring_group_skip_active == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-skip_active']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('ring_group_missed_call')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-missed_call']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='ring_group_missed_call_app' id='ring_group_missed_call_app' onchange=\"if (this.selectedIndex != 0) { document.getElementById('ring_group_missed_call_data').style.display = ''; document.getElementById('ring_group_missed_call_data').focus(); } else { document.getElementById('ring_group_missed_call_data').style.display='none'; }\">\n";
		echo "		<option value=''></option>\n";
		echo "    	<option value='email' ".(($ring_group_missed_call_app == "email" && $ring_group_missed_call_data != '') ? "selected='selected'" : null).">".$text['label-email']."</option>\n";
		//echo "    	<option value='text' ".(($ring_group_missed_call_app == "text" && $ring_group_missed_call_data != '') ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		//echo "    	<option value='url' ".(($ring_group_missed_call_app == "url" && $ring_group_missed_call_data != '') ? "selected='selected'" : null).">".$text['label-url']."</option>\n";
		echo "    </select>\n";
		$ring_group_missed_call_data = ($ring_group_missed_call_app == 'text') ? format_phone($ring_group_missed_call_data) : $ring_group_missed_call_data;
		echo "    <input class='formfld' type='text' name='ring_group_missed_call_data' id='ring_group_missed_call_data' maxlength='255' value=\"$ring_group_missed_call_data\" style='min-width: 200px; width: 200px; ".(($ring_group_missed_call_app == '' || $ring_group_missed_call_data == '') ? "display: none;" : null)."'>\n";
		echo "<br />\n";
		echo $text['description-missed_call']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-forwarding']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_forward_enabled' id='ring_group_forward_enabled' onchange=\"(this.selectedIndex == 1) ? document.getElementById('ring_group_forward_destination').focus() : null;\">";
	echo "		<option value='false'>".$text['option-disabled']."</option>";
	echo "		<option value='true' ".(($ring_group_forward_enabled == 'true') ? "selected='selected'" : null).">".$text['option-enabled']."</option>";
	echo "	</select>";
	echo 	"<input class='formfld' style='min-width: 95px;' type='text' name='ring_group_forward_destination' id='ring_group_forward_destination' placeholder=\"".$text['label-forward_destination']."\" maxlength='255' value=\"".$ring_group_forward_destination."\">";
	echo "<br />\n";
	echo $text['description-ring-group-forward']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (if_group("superadmin")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ring_group_context' maxlength='255' value=\"$ring_group_context\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-enter-context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_enabled'>\n";
	if ($ring_group_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ring_group_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_description' maxlength='255' value=\"$ring_group_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if (strlen($dialplan_uuid) > 0) {
		echo "		<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	if (strlen($ring_group_uuid) > 0) {
		echo "		<input type='hidden' name='ring_group_uuid' value='$ring_group_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
