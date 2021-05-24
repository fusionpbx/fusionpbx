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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_center_agent_add') || permission_exists('call_center_agent_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//check for duplicates
	if ($_GET["check"] == 'duplicate') {
		//agent id
			if ($_GET["agent_id"] != '') {
				$sql = "select agent_name ";
				$sql .= "from v_call_center_agents ";
				$sql .= "where agent_id = :agent_id ";
				$sql .= "and domain_uuid = :domain_uuid ";
				if (is_uuid($_GET["agent_uuid"])) {
					$sql .= " and call_center_agent_uuid <> :call_center_agent_uuid ";
					$parameters['call_center_agent_uuid'] = $_GET["agent_uuid"];
				}
				$parameters['agent_id'] = $_GET["agent_id"];
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && sizeof($row) != 0 && $row['agent_name'] != '') {
					echo $text['message-duplicate_agent_id'].(if_group("superadmin") ? ": ".$row["agent_name"] : null);
				}
				unset($sql, $parameters);
			}

		exit;
	}

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$call_center_agent_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$call_center_agent_uuid = $_POST["call_center_agent_uuid"];
		$user_uuid = $_POST["user_uuid"];
		$agent_name = $_POST["agent_name"];
		$agent_type = $_POST["agent_type"];
		$agent_call_timeout = $_POST["agent_call_timeout"];
		$agent_id = $_POST["agent_id"];
		$agent_password = $_POST["agent_password"];
		$agent_status = $_POST["agent_status"];
		$agent_contact = $_POST["agent_contact"];
		$agent_no_answer_delay_time = $_POST["agent_no_answer_delay_time"];
		$agent_max_no_answer = $_POST["agent_max_no_answer"];
		$agent_wrap_up_time = $_POST["agent_wrap_up_time"];
		$agent_reject_delay_time = $_POST["agent_reject_delay_time"];
		$agent_busy_delay_time = $_POST["agent_busy_delay_time"];
		$agent_record = $_POST["agent_record"];
		//$agent_logout = $_POST["agent_logout"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: call_center_agents.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (strlen($call_center_agent_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-call_center_agent_uuid']."<br>\n"; }
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			//if (strlen($user_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-user_uuid']."<br>\n"; }
			if (strlen($agent_name) == 0) { $msg .= $text['message-required']." ".$text['label-agent_name']."<br>\n"; }
			if (strlen($agent_type) == 0) { $msg .= $text['message-required']." ".$text['label-agent_type']."<br>\n"; }
			if (strlen($agent_call_timeout) == 0) { $msg .= $text['message-required']." ".$text['label-agent_call_timeout']."<br>\n"; }
			//if (strlen($agent_id) == 0) { $msg .= $text['message-required']." ".$text['label-agent_id']."<br>\n"; }
			//if (strlen($agent_password) == 0) { $msg .= $text['message-required']." ".$text['label-agent_password']."<br>\n"; }
			//if (strlen($agent_status) == 0) { $msg .= $text['message-required']." ".$text['label-agent_status']."<br>\n"; }
			if (strlen($agent_contact) == 0) { $msg .= $text['message-required']." ".$text['label-agent_contact']."<br>\n"; }
			if (strlen($agent_no_answer_delay_time) == 0) { $msg .= $text['message-required']." ".$text['label-agent_no_answer_delay_time']."<br>\n"; }
			if (strlen($agent_max_no_answer) == 0) { $msg .= $text['message-required']." ".$text['label-agent_max_no_answer']."<br>\n"; }
			if (strlen($agent_wrap_up_time) == 0) { $msg .= $text['message-required']." ".$text['label-agent_wrap_up_time']."<br>\n"; }
			if (strlen($agent_reject_delay_time) == 0) { $msg .= $text['message-required']." ".$text['label-agent_reject_delay_time']."<br>\n"; }
			if (strlen($agent_busy_delay_time) == 0) { $msg .= $text['message-required']." ".$text['label-agent_busy_delay_time']."<br>\n"; }
			//if (strlen($agent_logout) == 0) { $msg .= $text['message-required']." ".$text['label-agent_logout']."<br>\n"; }
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

		//add the call_center_agent_uuid
			if (strlen($call_center_agent_uuid) == 0) {
				$call_center_agent_uuid = uuid();
			}

		//get the users array
			$sql = "select * from v_users ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "order by username asc ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$users = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

		//change the contact string to loopback - Not recommended added for backwards comptability causes multiple problems
			if ($_SESSION['call_center']['agent_contact_method']['text'] == 'loopback') {
				$agent_contact = str_replace("user/", "loopback/", $agent_contact);
				$agent_contact = str_replace("@", "/", $agent_contact);
			}

		//prepare the array
			$array['call_center_agents'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['call_center_agents'][0]['call_center_agent_uuid'] = $call_center_agent_uuid;
			$array['call_center_agents'][0]['agent_name'] = $agent_name;
			$array['call_center_agents'][0]['agent_type'] = $agent_type;
			$array['call_center_agents'][0]['agent_call_timeout'] = $agent_call_timeout;
			$array['call_center_agents'][0]['user_uuid'] = $user_uuid;
			$array['call_center_agents'][0]['agent_id'] = $agent_id;
			$array['call_center_agents'][0]['agent_password'] = $agent_password;
			$array['call_center_agents'][0]['agent_contact'] = $agent_contact;
			$array['call_center_agents'][0]['agent_status'] = $agent_status;
			$array['call_center_agents'][0]['agent_no_answer_delay_time'] = $agent_no_answer_delay_time;
			$array['call_center_agents'][0]['agent_max_no_answer'] = $agent_max_no_answer;
			$array['call_center_agents'][0]['agent_wrap_up_time'] = $agent_wrap_up_time;
			$array['call_center_agents'][0]['agent_reject_delay_time'] = $agent_reject_delay_time;
			$array['call_center_agents'][0]['agent_busy_delay_time'] = $agent_busy_delay_time;
			$array['call_center_agents'][0]['agent_record'] = $agent_record;
			if (is_uuid($user_uuid)) {
				$array['users'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
				$array['users'][0]['user_uuid'] = $user_uuid;
				$array['users'][0]['user_status'] = $agent_status;
			}

		//save to the data
			$database = new database;
			$database->app_name = 'call_center';
			$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
			$database->save($array);
			//$message = $database->message;

		//syncrhonize configuration
			save_call_center_xml();

		//clear the cache
			$cache = new cache;
			$cache->delete('configuration:callcenter.conf');

	//get and then set the complete agent_contact with the call_timeout and when necessary confirm
		//if you change this variable, also change resources/switch.php
		$confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1,group_confirm_read_timeout=2000,leg_timeout=".$agent_call_timeout;
		if(strstr($agent_contact, '}') === FALSE) {
			//not found
			if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
				//add the call_timeout
				$agent_contact = "{call_timeout=".$agent_call_timeout.",sip_invite_domain=".$_SESSION['domain_name']."}".$agent_contact;
			}
			else {
				//add the call_timeout and confirm
				$agent_contact = "{".$confirm.",call_timeout=".$agent_call_timeout.",sip_invite_domain=".$_SESSION['domain_name']."}".$agent_contact;
			}
		}
		else {
			$position = strrpos($agent_contact, "}");
			$first = substr($agent_contact, 0, $position);
			$last = substr($agent_contact, $position);
			//add call_timeout and sip_invite_domain, only if missing
			$call_timeout = (stristr($agent_contact, 'call_timeout') === FALSE) ? ',call_timeout='.$agent_call_timeout : null;
			$sip_invite_domain = (stristr($agent_contact, 'sip_invite_domain') === FALSE) ? ',sip_invite_domain='.$_SESSION['domain_name'] : null;
			//compose
			if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
				$agent_contact = $first.$sip_invite_domain.$call_timeout.$last;
			}
			else {
				$agent_contact = $first.','.$confirm.$sip_invite_domain.$call_timeout.$last;
			}
		}

	//add the agent
		//setup the event socket connection
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		//add the agent using event socket
			if ($fp) {
				//add the agent
					$cmd = "api callcenter_config agent add ".$call_center_agent_uuid." ".$agent_type;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set contact
					$cmd = "api callcenter_config agent set contact ".$call_center_agent_uuid." ".$agent_contact;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set status
					$cmd = "api callcenter_config agent set status ".$call_center_agent_uuid." '".$agent_status."'";
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set reject_delay_time
					$cmd = "api callcenter_config agent set reject_delay_time ".$call_center_agent_uuid." ".$agent_reject_delay_time;
					$response = event_socket_request($fp, $cmd);
					usleep(200);
				//agent set busy_delay_time
					$cmd = "api callcenter_config agent set busy_delay_time ".$call_center_agent_uuid." ".$agent_busy_delay_time;
					$response = event_socket_request($fp, $cmd);
				//agent set no_answer_delay_time
					$cmd = "api callcenter_config agent set no_answer_delay_time ".$call_center_agent_uuid." ".$agent_no_answer_delay_time;
					$response = event_socket_request($fp, $cmd);
				//agent set max_no_answer
					$cmd = "api callcenter_config agent set max_no_answer ".$call_center_agent_uuid." ".$agent_max_no_answer;
					$response = event_socket_request($fp, $cmd);
				//agent set wrap_up_time
					$cmd = "api callcenter_config agent set wrap_up_time ".$call_center_agent_uuid." ".$agent_wrap_up_time;
					$response = event_socket_request($fp, $cmd);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
				header("Location: call_center_agents.php");
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (is_uuid($_GET["id"]) && $_POST["persistformvar"] != "true") {
		$call_center_agent_uuid = $_GET["id"];
		$sql = "select * from v_call_center_agents ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_center_agent_uuid = :call_center_agent_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['call_center_agent_uuid'] = $call_center_agent_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$call_center_agent_uuid = $row["call_center_agent_uuid"];
			$user_uuid = $row["user_uuid"];
			$agent_name = $row["agent_name"];
			$agent_type = $row["agent_type"];
			$agent_call_timeout = $row["agent_call_timeout"];
			$agent_id = $row["agent_id"];
			$agent_password = $row["agent_password"];
			$agent_status = $row["agent_status"];
			$agent_contact = $row["agent_contact"];
			$agent_no_answer_delay_time = $row["agent_no_answer_delay_time"];
			$agent_max_no_answer = $row["agent_max_no_answer"];
			$agent_wrap_up_time = $row["agent_wrap_up_time"];
			$agent_reject_delay_time = $row["agent_reject_delay_time"];
			$agent_busy_delay_time = $row["agent_busy_delay_time"];
			$agent_record = $row["agent_record"];
			//$agent_logout = $row["agent_logout"];
		}
		unset($sql, $parameters, $row);
	}

//set default values
	if (strlen($agent_type) == 0) { $agent_type = "callback"; }
	if (strlen($agent_call_timeout) == 0) { $agent_call_timeout = "20"; }
	if (strlen($agent_max_no_answer) == 0) { $agent_max_no_answer = "0"; }
	if (strlen($agent_wrap_up_time) == 0) { $agent_wrap_up_time = "10"; }
	if (strlen($agent_no_answer_delay_time) == 0) { $agent_no_answer_delay_time = "30"; }
	if (strlen($agent_reject_delay_time) == 0) { $agent_reject_delay_time = "90"; }
	if (strlen($agent_busy_delay_time) == 0) { $agent_busy_delay_time = "90"; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	if ($action == "add") {
		$document['title'] = $text['title-call_center_agent_add'];
	}
	if ($action == "update") {
		$document['title'] = $text['title-call_center_agent_edit'];
	}
	require_once "resources/header.php";

//get the list of users for this domain
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//javascript to check for duplicates
	?>
	<script language="javascript">
		function check_duplicates() {
			//check agent id
				var agent_id = document.getElementById('agent_id').value;
				$("#duplicate_agent_id_response").load("call_center_agent_edit.php?check=duplicate&agent_id="+agent_id+"&agent_uuid=<?php echo escape($call_center_agent_uuid); ?>", function() {
					var duplicate_agent_id = false;
					if ($("#duplicate_agent_id_response").html() != '') {
						$('#agent_id').addClass('formfld_highlight_bad');
						display_message($("#duplicate_agent_id_response").html(), 'negative'<?php if (if_group("superadmin")) { echo ', 3000'; } ?>);
						duplicate_agent_id = true;
					}
					else {
						$("#duplicate_agent_id_response").html('');
						$('#agent_id').removeClass('formfld_highlight_bad');
						duplicate_agent_id = false;
					}

					if (duplicate_agent_id == false) {
						document.getElementById('frm').submit();
					}
				});
		}
	</script>

<?php
//show the content
	echo "<form method='post' name='frm' id='frm' onsubmit='check_duplicates(); return false;'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['header-call_center_agent_add']."</b>";
	}
	if ($action == "update") {
		echo "<b>".$text['header-call_center_agent_edit']."</b>";
	}
	echo 	"</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'call_center_agents.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-agent_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='agent_name' maxlength='255' value=\"".escape($agent_name)."\" />\n";
	/*
	echo "<select id=\"agent_name\" name=\"agent_name\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	if (is_array($users)) {
		foreach($users as $field) {
			if ($field[username] == $agent_name) {
				echo "<option value='".escape($field[username])."' selected='selected'>".escape($field[username])."</option>\n";
			}
			else {
				echo "<option value='".escape($field[username])."'>".escape($field[username])."</option>\n";
			}
		}
	}
	echo "</select>";
	*/
	echo "<br />\n";
	echo $text['description-agent_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='agent_type' maxlength='255' value=\"".escape($agent_type)."\" pattern='^(callback|uuid-standby)$'>\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_call_timeout' maxlength='255' min='1' step='1' value='".escape($agent_call_timeout)."'>\n";
	echo "<br />\n";
	echo $text['description-call_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-username']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "			<select name=\"user_uuid\" class='formfld' style='width: auto;'>\n";
	echo "			<option value=\"\"></option>\n";
	foreach($users as $field) {
		if ($user_uuid == $field['user_uuid']) {
			echo "			<option value='".escape($field['user_uuid'])."' selected='selected'>".escape($field['username'])."</option>\n";
		}
		else {
			echo "			<option value='".escape($field['user_uuid'])."' $selected>".escape($field['username'])."</option>\n";
		}
	}
	echo "			</select>";
	unset($users);
	echo "			<br>\n";
	echo "			".$text['description-users']."\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-agent_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_id' id='agent_id' maxlength='255' min='1' step='1' value='".escape($agent_id)."'>\n";
	echo "	<div style='display: none;' id='duplicate_agent_id_response'></div>\n";
	echo "<br />\n";
	echo $text['description-agent_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-agent_password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='password' name='agent_password' autocomplete='off' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!\$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='255' min='1' step='1' value='".escape($agent_password)."'>\n";
	echo "<br />\n";
	echo $text['description-agent_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('user_contact', 'agent_contact', $agent_contact);
	echo "<br />\n";
	echo $text['description-contact']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-status']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='agent_status'>\n";
	echo "	<option value=''></option>\n";
	if ($agent_status == "Logged Out") {
		echo "	<option value='Logged Out' SELECTED >".$text['option-logged_out']."</option>\n";
	}
	else {
		echo "	<option value='Logged Out'>".$text['option-logged_out']."</option>\n";
	}
	if ($agent_status == "Available") {
		echo "	<option value='Available' SELECTED >".$text['option-available']."</option>\n";
	}
	else {
		echo "	<option value='Available'>".$text['option-available']."</option>\n";
	}
	if ($agent_status == "Available (On Demand)") {
		echo "	<option value='Available (On Demand)' SELECTED >".$text['option-available_on_demand']."</option>\n";
	}
	else {
		echo "	<option value='Available (On Demand)'>".$text['option-available_on_demand']."</option>\n";
	}
	if ($agent_status == "On Break") {
		echo "	<option value='On Break' SELECTED >".$text['option-on_break']."</option>\n";
	}
	else {
		echo "	<option value='On Break'>".$text['option-on_break']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-status']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-no_answer_delay_time']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_no_answer_delay_time' maxlength='255' min='0' step='1' value='".escape($agent_no_answer_delay_time)."'>\n";
	echo "<br />\n";
	echo $text['description-no_answer_delay_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-max_no_answer']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_max_no_answer' maxlength='255' min='0' step='1' value='".escape($agent_max_no_answer)."'>\n";
	echo "<br />\n";
	echo $text['description-max_no_answer']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-wrap_up_time']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_wrap_up_time' maxlength='255' min='0' step='1' value='".escape($agent_wrap_up_time)."'>\n";
	echo "<br />\n";
	echo $text['description-wrap_up_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-reject_delay_time']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_reject_delay_time' maxlength='255' min='0' step='1' value='".escape($agent_reject_delay_time)."'>\n";
	echo "<br />\n";
	echo $text['description-reject_delay_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-busy_delay_time']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_busy_delay_time' maxlength='255' min='1' step='1' value='".escape($agent_busy_delay_time)."'>\n";
	echo "<br />\n";
	echo $text['description-busy_delay_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-record_template']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='agent_record'>\n";
	echo "	<option value='true' ".($agent_record == "true" ?  "selected='selected'" : '')." >".$text['option-true']."</option>\n";
	echo "	<option value='false' ".($agent_record != "true" ?  "selected='selected'" : '').">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-record_template']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-agent_logout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='agent_logout' maxlength='255' value='".escape($agent_logout)."'>\n";
	echo "<br />\n";
	echo $text['description-agent_logout']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	*/

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='call_center_agent_uuid' value='".escape($call_center_agent_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
