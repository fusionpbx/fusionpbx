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
	James Rose <james.o.rose@gmail.com>
*/
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
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

//delete the user from the v_extension_users
	if ($_GET["a"] == "delete" && permission_exists("user_delete")) {
		//set the variables
			$ring_group_extension_uuid = check_str($_REQUEST["id"]);
			$ring_group_uuid = check_str($_REQUEST["ring_group_uuid"]);
		//delete the extension from the ring_group
			$sql = "delete from v_ring_group_extensions ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and ring_group_extension_uuid = '$ring_group_extension_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);
		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=ring_groups_edit.php?id=$ring_group_uuid\">\n";
			echo "<div align='center'>Delete Complete</div>";
			require_once "includes/footer.php";
			return;
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$ring_group_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//define the destination_select function
	function destination_select($select_name, $select_value, $select_default) {
		if (strlen($select_value) == 0) { $select_value = $select_default; }
		echo "	<select class='formfld' style='width: 45px;' name='$select_name'>\n";
		echo "	<option value=''></option>\n";

		$i = 0;
		while($i <= 100) {
			if ($select_value == $i) {
				echo "	<option value='$i' selected='selected'>$i</option>\n";
			}
			else {
				echo "	<option value='$i'>$i</option>\n";
			}
			$i = $i + 5;
		}
		echo "</select>\n";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		//set variables from http values
			$ring_group_name = check_str($_POST["ring_group_name"]);
			$ring_group_extension = check_str($_POST["ring_group_extension"]);
			$ring_group_context = check_str($_POST["ring_group_context"]);
			$ring_group_strategy = check_str($_POST["ring_group_strategy"]);
			$ring_group_timeout_sec = check_str($_POST["ring_group_timeout_sec"]);
			$ring_group_timeout_action = check_str($_POST["ring_group_timeout_action"]);
			$ring_group_cid_name_prefix = check_str($_POST["ring_group_cid_name_prefix"]);
			$ring_group_ringback = check_str($_POST["ring_group_ringback"]);
			$ring_group_enabled = check_str($_POST["ring_group_enabled"]);
			$ring_group_description = check_str($_POST["ring_group_description"]);
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
			//$ring_group_timeout_action = "transfer:1001 XML default";
			$ring_group_timeout_array = explode(":", $ring_group_timeout_action);
			$ring_group_timeout_app = array_shift($ring_group_timeout_array);
			$ring_group_timeout_data = join(':', $ring_group_timeout_array);
			$extension_uuid = check_str($_POST["extension_uuid"]);
			$extension_delay = check_str($_POST["extension_delay"]);
			$extension_timeout = check_str($_POST["extension_timeout"]);

		//set the context for users that are not in the superadmin group
			if (!if_group("superadmin")) {
				if (count($_SESSION["domains"]) > 1) {
					$ring_group_context = $_SESSION['domain_name'];
				}
				else {
					$ring_group_context = "default";
				}
			}

	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$ring_group_uuid = check_str($_POST["ring_group_uuid"]);
	}

	//check for all required data
		if (strlen($ring_group_name) == 0) { $msg .= $text['message-name']."<br>\n"; }
		if (strlen($ring_group_extension) == 0) { $msg .= $text['message-extension']."<br>\n"; }
		if (strlen($ring_group_strategy) == 0) { $msg .= $text['message-strategy']."<br>\n"; }
		if (strlen($ring_group_timeout_sec) == 0) { $msg .= $text['message-strategy']."<br>\n"; }
		if (strlen($ring_group_timeout_app) == 0) { $msg .= $text['message-timeout-action']."<br>\n"; }
		//if (strlen($ring_group_cid_name_prefix) == 0) { $msg .= "Please provide: Caller ID Prefix<br>\n"; }
		//if (strlen($ring_group_ringback) == 0) { $msg .= "Please provide: Ringback<br>\n"; }
		if (strlen($ring_group_enabled) == 0) { $msg .= $text['message-enabled']."<br>\n"; }
		//if (strlen($ring_group_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add") {
				//prepare the uuids
					$ring_group_uuid = uuid();
					$dialplan_uuid = uuid();
				//add the ring group
					$sql = "insert into v_ring_groups ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "ring_group_uuid, ";
					$sql .= "ring_group_name, ";
					$sql .= "ring_group_extension, ";
					$sql .= "ring_group_context, ";
					$sql .= "ring_group_strategy, ";
					$sql .= "ring_group_timeout_sec, ";
					$sql .= "ring_group_timeout_app, ";
					$sql .= "ring_group_timeout_data, ";
					$sql .= "ring_group_cid_name_prefix, ";
					$sql .= "ring_group_ringback, ";
					$sql .= "ring_group_enabled, ";
					$sql .= "ring_group_description, ";
					$sql .= "dialplan_uuid ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$_SESSION['domain_uuid']."', ";
					$sql .= "'".$ring_group_uuid."', ";
					$sql .= "'$ring_group_name', ";
					$sql .= "'$ring_group_extension', ";
					$sql .= "'$ring_group_context', ";
					$sql .= "'$ring_group_strategy', ";
					$sql .= "'$ring_group_timeout_sec', ";
					$sql .= "'$ring_group_timeout_app', ";
					$sql .= "'$ring_group_timeout_data', ";
					$sql .= "'$ring_group_cid_name_prefix', ";
					$sql .= "'$ring_group_ringback', ";
					$sql .= "'$ring_group_enabled', ";
					$sql .= "'$ring_group_description', ";
					$sql .= "'$dialplan_uuid' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_ring_groups set ";
				$sql .= "ring_group_name = '$ring_group_name', ";
				$sql .= "ring_group_extension = '$ring_group_extension', ";
				if (if_group("superadmin")) {
					$sql .= "ring_group_context = '$ring_group_context', ";
				}
				$sql .= "ring_group_strategy = '$ring_group_strategy', ";
				$sql .= "ring_group_timeout_sec = '$ring_group_timeout_sec', ";
				$sql .= "ring_group_timeout_app = '$ring_group_timeout_app', ";
				$sql .= "ring_group_timeout_data = '$ring_group_timeout_data', ";
				$sql .= "ring_group_cid_name_prefix = '$ring_group_cid_name_prefix', ";
				$sql .= "ring_group_ringback = '$ring_group_ringback', ";
				$sql .= "ring_group_enabled = '$ring_group_enabled', ";
				$sql .= "ring_group_description = '$ring_group_description' ";
				//$sql .= "dialplan_uuid = '$dialplan_uuid' ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and ring_group_uuid = '$ring_group_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);
			}

			if ($action == "update" || $action == "add") {
				//if extension_uuid then add it to ring group extensions
					if (strlen($extension_uuid) > 0) {
						$ring_group_extension_uuid = uuid();
						$sql = "insert into v_ring_group_extensions ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "ring_group_uuid, ";
						$sql .= "ring_group_extension_uuid, ";
						$sql .= "extension_delay, ";
						if (strlen($extension_timeout) > 0) {
							$sql .= "extension_timeout, ";
						}
						$sql .= "extension_uuid ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$_SESSION['domain_uuid']."', ";
						$sql .= "'$ring_group_uuid', ";
						$sql .= "'$ring_group_extension_uuid', ";
						$sql .= "'$extension_delay', ";
						if (strlen($extension_timeout) > 0) {
							$sql .= "'$extension_timeout', ";
						}
						$sql .= "'$extension_uuid' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}

				//if it does not exist in the dialplan then add it
					$sql = "select count(*) as num_rows from v_dialplans ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] == 0) {
							//add the dialplan
								require_once "includes/classes/database.php";
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
								$database->fields['dialplan_detail_data'] = 'ring_group.lua';
								$database->fields['dialplan_detail_order'] = '030';
								$database->add();

							//save the xml
								save_dialplan_xml();

							//apply settings reminder
								$_SESSION["reload_xml"] = true;
						}
					}

				//delete the dialplan context from memcache
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}

				//redirect the browser
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=ring_groups_edit.php?id=$ring_group_uuid\">\n";
					echo "<div align='center'>\n";
					if ($action == "add") {
						echo $text['message-enabled']."\n";
					}
					if ($action == "update") {
						echo $text['message-update-complete']."\n";
					}
					echo "</div>\n";
					require_once "includes/footer.php";
					exit;
			}
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$ring_group_uuid = $_GET["id"];
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
			$ring_group_timeout_sec = $row["ring_group_timeout_sec"];
			$ring_group_timeout_app = $row["ring_group_timeout_app"];
			$ring_group_timeout_data = $row["ring_group_timeout_data"];
			$ring_group_cid_name_prefix = $row["ring_group_cid_name_prefix"];
			$ring_group_ringback = $row["ring_group_ringback"];
			$ring_group_enabled = $row["ring_group_enabled"];
			$ring_group_description = $row["ring_group_description"];
			$dialplan_uuid = $row["dialplan_uuid"];
		}
		unset ($prep_statement);
		if (strlen($ring_group_timeout_app) > 0) {
			$ring_group_timeout_action = $ring_group_timeout_app.":".$ring_group_timeout_data;
		}
	}

//set defaults
	if (strlen($ring_group_timeout_sec) == 0) { $ring_group_timeout_sec = '30'; }
	if (strlen($ring_group_enabled) == 0) { $ring_group_enabled = 'true'; }

//set the context for users that are not in the superadmin group
	if (strlen($ring_group_context) == 0) {
		if (count($_SESSION["domains"]) > 1) {
			$ring_group_context = $_SESSION['domain_name'];
		}
		else {
			$ring_group_context = "default";
		}
	}

//show the header
	require_once "includes/header.php";

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
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['label-ring-group']."</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='ring_groups.php'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_name' maxlength='255' value=\"$ring_group_name\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_extension' maxlength='255' value=\"$ring_group_extension\">\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (if_group("superadmin")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	",$text['label-context'],":\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ring_group_context' maxlength='255' value=\"$ring_group_context\">\n";
		echo "<br />\n";
		echo $text['description-enter-context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-strategy'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_strategy'>\n";
	echo "	<option value=''></option>\n";
	if ($ring_group_strategy == "sequence") {
		echo "	<option value='sequence' selected='selected'>".$text['select-sequence']."</option>\n";
	}
	else {
		echo "	<option value='sequence'>".$text['select-sequence']."</option>\n";
	}
	if ($ring_group_strategy == "simultaneous") {
		echo "	<option value='simultaneous' selected='selected'>".$text['select-simultaneous']."</option>\n";
	}
	else {
		echo "	<option value='simultaneous'>".$text['select-simultaneous']."</option>\n";
	}
	if ($ring_group_strategy == "enterprise") {
		echo "	<option value='enterprise' selected='selected'>".$text['select-enterprise']."</option>\n";
	}
	else {
		echo "	<option value='enterprise'>".$text['select-enterprise']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['label-sequence']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-extensions'].":</td>";
	echo "		<td class='vtable' align='left'>";
	if ($action == "update") {
		echo "			<table width='52%' border='0' cellpadding='0' cellspacing='0'>\n";
		$sql = "SELECT g.ring_group_extension_uuid, e.extension_uuid, g.extension_delay, g.extension_timeout, e.extension ";
		$sql .= "FROM v_ring_groups as r, v_ring_group_extensions as g, v_extensions as e ";
		$sql .= "where g.ring_group_uuid = r.ring_group_uuid ";
		$sql .= "and g.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and g.ring_group_uuid = '".$ring_group_uuid."' ";
		$sql .= "and e.extension_uuid = g.extension_uuid ";
		$sql .= "order by g.extension_delay asc, e.extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		echo "<tr>\n";
		echo "	<td class='vtable'>".$text['label-extension']."</td>\n";
		echo "	<td class='vtable'>".$text['label-delay']."</td>\n";
		echo "	<td class='vtable'>".$text['label-timeout']."</td>\n";
		echo "	<td></td>\n";
		echo "</tr>\n";
		foreach($result as $field) {
			if (strlen($field['extension_delay']) == 0) { $field['extension_delay'] = "0"; }
			if (strlen($field['extension_timeout']) == 0) { $field['extension_timeout'] = "30"; } 
			echo "			<tr>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$field['extension'];
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$field['extension_delay']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td class='vtable'>\n";
			echo "					".$field['extension_timeout']."&nbsp;\n";
			echo "				</td>\n";
			echo "				<td>\n";
			echo "					<a href='ring_groups_edit.php?id=".$field['ring_group_extension_uuid']."&ring_group_uuid=".$ring_group_uuid."&a=delete' alt='delete' onclick=\"return confirm('".$text['message-delete']."')\">$v_link_label_delete</a>\n";
			echo "				</td>\n";
			echo "			</tr>\n";
		}
		echo "			</table>\n";
	}
	echo "			<br />\n";
	$sql = "SELECT * FROM v_extensions ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by extension asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "			<select name=\"extension_uuid\" class='frm'>\n";
	echo "			<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		echo "			<option value='".$field['extension_uuid']."'>".$field['extension']."</option>\n";
	}
	echo "			</select>";
	echo "			&nbsp;\n";

	echo "	".$text['label-delay']."&nbsp;";
	destination_select('extension_delay', $extension_delay, '0');
	echo "	&nbsp;".$text['label-timeout']."&nbsp;\n";
	destination_select('extension_timeout', $extension_timeout, '30');
	if ($action == "update") {
		echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
	}
	unset($sql, $result);
	echo "			<br>\n";
	echo "			".$text['description-extension']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-timeout'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_timeout_sec' maxlength='255' value='$ring_group_timeout_sec'>\n";
	echo "<br />\n";
	echo $text['description-timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
	switch_select_destination("dialplan", "", "ring_group_timeout_action", $ring_group_timeout_action, "", "");
	echo "	<br />\n";
	echo "	".$text['description-destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-cid-prefix'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_cid_name_prefix' maxlength='255' value='$ring_group_cid_name_prefix'>\n";
	echo "<br />\n";
	echo $text['description-cid-prefix']." \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	 ".$text['label-ringback'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$select_options = "";
	if ($ring_group_ringback == "\${us-ring}" || $ring_group_ringback == "us-ring") { 
		$select_options .= "		<option value='\${us-ring}' selected='selected'>".$text['dropdown-usring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${us-ring}'>".$text['dropdown-usring']."</option>\n";
	}
	if ($ring_group_ringback == "\${fr-ring}" || $ring_group_ringback == "fr-ring") {
		$select_options .= "		<option value='\${fr-ring}' selected='selected'>".$text['dropdown-frring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${fr-ring}'>".$text['dropdown-frring']."</option>\n";
	}
	if ($ring_group_ringback == "\${uk-ring}" || $ring_group_ringback == "uk-ring") { 
		$select_options .= "		<option value='\${uk-ring}' selected='selected'>".$text['dropdown-ukring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${uk-ring}'>".$text['dropdown-ukring']."</option>\n";
	}
	if ($ring_group_ringback == "\${rs-ring}" || $ring_group_ringback == "rs-ring") { 
		$select_options .= "		<option value='\${rs-ring}' selected='selected'>".$text['dropdown-rsring']."</option>\n";
	}
	else {
		$select_options .= "		<option value='\${rs-ring}'>".$text['dropdown-rsring']."</option>\n";
	}
	require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
	$moh = new switch_music_on_hold;
	$moh->select_name = "ring_group_ringback";
	$moh->select_value = $ring_group_ringback;
	$moh->select_options = $select_options;
	echo $moh->select();

	echo "<br />\n";
	echo $text['description-ringback']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($ring_group_enabled == "true") { 
		echo "	<option value='true' selected='selected'>".$text['dropdown-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['dropdown-true']."</option>\n";
	}
	if ($ring_group_enabled == "false") { 
		echo "	<option value='false' selected='selected'>".$text['dropdown-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['dropdown-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_description' maxlength='255' value=\"$ring_group_description\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
		echo "				<input type='hidden' name='ring_group_uuid' value='$ring_group_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>