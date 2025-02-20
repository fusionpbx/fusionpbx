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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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

//connect to the database
	$database = new database;

//set the defaults
	$agent_id = '';
	$agent_name = '';
	$agent_password = '';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$call_center_agent_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the users array
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$call_center_agent_uuid = $_POST["call_center_agent_uuid"] ?? null;
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
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: call_center_agents.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (empty($call_center_agent_uuid)) { $msg .= $text['message-required']." ".$text['label-call_center_agent_uuid']."<br>\n"; }
			//if (empty($domain_uuid)) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			//if (empty($user_uuid)) { $msg .= $text['message-required']." ".$text['label-user_uuid']."<br>\n"; }
			if (empty($agent_name)) { $msg .= $text['message-required']." ".$text['label-agent_name']."<br>\n"; }
			if (empty($agent_type)) { $msg .= $text['message-required']." ".$text['label-agent_type']."<br>\n"; }
			if (empty($agent_call_timeout)) { $msg .= $text['message-required']." ".$text['label-agent_call_timeout']."<br>\n"; }
			//if (empty($agent_id)) { $msg .= $text['message-required']." ".$text['label-agent_id']."<br>\n"; }
			//if (empty($agent_password)) { $msg .= $text['message-required']." ".$text['label-agent_password']."<br>\n"; }
			//if (empty($agent_status)) { $msg .= $text['message-required']." ".$text['label-agent_status']."<br>\n"; }
			if (empty($agent_contact)) { $msg .= $text['message-required']." ".$text['label-agent_contact']."<br>\n"; }
			//if (empty($agent_logout)) { $msg .= $text['message-required']." ".$text['label-agent_logout']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//set default values
			if (empty($agent_call_timeout)) { $agent_call_timeout = "20"; }
			if (empty($agent_max_no_answer)) { $agent_max_no_answer = "0"; }
			if (empty($agent_wrap_up_time)) { $agent_wrap_up_time = "10"; }
			if (empty($agent_no_answer_delay_time)) { $agent_no_answer_delay_time = "30"; }
			if (empty($agent_reject_delay_time)) { $agent_reject_delay_time = "90"; }
			if (empty($agent_busy_delay_time)) { $agent_busy_delay_time = "90"; }

		//add the call_center_agent_uuid
			if (empty($call_center_agent_uuid)) {
				$call_center_agent_uuid = uuid();
			}

		//change the contact string to loopback - Not recommended added for backwards comptability causes multiple problems
			if ($_SESSION['call_center']['agent_contact_method']['text'] == 'loopback') {
				$agent_contact = str_replace("user/", "loopback/", $agent_contact);
				$agent_contact = str_replace("@", "/", $agent_contact);
			}

		//freeswitch expands the contact string, so we need to sanitize it.
			$agent_contact = str_replace('$', '', $agent_contact);

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
			$database->app_name = 'call_center';
			$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
			$database->save($array);

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
			$esl = event_socket::create();
		//add the agent using event socket
			if ($esl->connected()) {
				//add the agent
					$cmd = "callcenter_config agent add ".$call_center_agent_uuid." '".$agent_type."'";
					$response = event_socket::api($cmd);
					usleep(200);
				//agent set contact
					$cmd = "callcenter_config agent set contact ".$call_center_agent_uuid." '".$agent_contact."'";
					$response = event_socket::api($cmd);
					usleep(200);
				//agent set status
					$cmd = "callcenter_config agent set status ".$call_center_agent_uuid." '".$agent_status."'";
					$response = event_socket::api($cmd);
					usleep(200);
				//agent set reject_delay_time
					$cmd = 'callcenter_config agent set reject_delay_time '.$call_center_agent_uuid.' '. $agent_reject_delay_time;
					$response = event_socket::api($cmd);
					usleep(200);
				//agent set busy_delay_time
					$cmd = 'callcenter_config agent set busy_delay_time '.$call_center_agent_uuid.' '.$agent_busy_delay_time;
					$response = event_socket::api($cmd);
				//agent set no_answer_delay_time
					$cmd = 'callcenter_config agent set no_answer_delay_time '.$call_center_agent_uuid.' '.$agent_no_answer_delay_time;
					$response = event_socket::api($cmd);
				//agent set max_no_answer
					$cmd = 'callcenter_config agent set max_no_answer '.$call_center_agent_uuid.' '.$agent_max_no_answer;
					$response = event_socket::api($cmd);
				//agent set wrap_up_time
					$cmd = 'callcenter_config agent set wrap_up_time '.$call_center_agent_uuid.' '.$agent_wrap_up_time;
					$response = event_socket::api($cmd);
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
	} //(is_array($_POST) && empty($_POST["persistformvar"]))

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (!empty($_GET["id"]) && is_uuid($_GET["id"]) && empty($_POST["persistformvar"])) {
		$call_center_agent_uuid = $_GET["id"];
		$sql = "select * from v_call_center_agents ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_center_agent_uuid = :call_center_agent_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['call_center_agent_uuid'] = $call_center_agent_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (!empty($row)) {
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
	if (empty($agent_type)) { $agent_type = "callback"; }
	if (empty($agent_call_timeout)) { $agent_call_timeout = "20"; }
	if (empty($agent_max_no_answer)) { $agent_max_no_answer = "0"; }
	if (empty($agent_wrap_up_time)) { $agent_wrap_up_time = "10"; }
	if (empty($agent_no_answer_delay_time)) { $agent_no_answer_delay_time = "30"; }
	if (empty($agent_reject_delay_time)) { $agent_reject_delay_time = "90"; }
	if (empty($agent_busy_delay_time)) { $agent_busy_delay_time = "90"; }

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

//include the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm' onsubmit=''>\n";

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

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-agent_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='agent_name' maxlength='255' value=\"".escape($agent_name)."\" />\n";
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
	foreach ($users as $field) {
		echo "			<option value='".escape($field['user_uuid'])."' ".(!empty($user_uuid) && $user_uuid == $field['user_uuid'] ? "selected='selected'" : null).">".escape($field['username'])."</option>\n";
	}
	echo "			</select>";
	unset($users);
	echo "			<br>\n";
	echo "			".!empty($text['description-users'])."\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-agent_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='agent_id' id='agent_id' maxlength='255' min='1' step='1' value='".escape($agent_id)."'>\n";
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
	echo $destination->select('user_contact', 'agent_contact', ($agent_contact ?? null));
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
	echo "		<option value=''></option>\n";
	echo "		<option value='Logged Out' ".(!empty($agent_status) && $agent_status == "Logged Out" ? "selected='selected'" : null).">".$text['option-logged_out']."</option>\n";
	echo "		<option value='Available' ".(!empty($agent_status) && $agent_status == "Available" ? "selected='selected'" : null).">".$text['option-available']."</option>\n";
	echo "		<option value='Available (On Demand)' ".(!empty($agent_status) && $agent_status == "Available (On Demand)" ? "selected='selected'" : null).">".$text['option-available_on_demand']."</option>\n";
	echo "		<option value='On Break' ".(!empty($agent_status) && $agent_status == "On Break" ? "selected='selected'" : null).">".$text['option-on_break']."</option>\n";
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
	echo "		<option value='true'>".$text['option-true']."</option>\n";
	echo "		<option value='false' ".(!empty($agent_record) && $agent_record != "true" ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
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
	echo "</div>\n";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='call_center_agent_uuid' value='".escape($call_center_agent_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
