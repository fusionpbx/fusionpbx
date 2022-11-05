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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('conference_room_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['conference_rooms'])) {
		$action = $_POST['action'];
		$toggle_field = $_POST['toggle_field'];
		$search = $_POST['search'];
		$conference_rooms = $_POST['conference_rooms'];
	}

//process the http post data by action
	if ($action != '' && is_array($conference_rooms) && @sizeof($conference_rooms) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('conference_room_edit')) {
					$obj = new conference_centers;
					$obj->toggle_field = $toggle_field;
					$obj->toggle_conference_rooms($conference_rooms);
				}
				break;
			case 'delete':
				if (permission_exists('conference_room_delete')) {
					$obj = new conference_centers;
					$obj->delete_conference_rooms($conference_rooms);
				}
				break;
		}

		header('Location: conference_rooms.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

/*
//if the $_GET array exists then process it
	if (count($_GET) > 0 && strlen($_GET["search"]) == 0) {
		//get http GET variables and set them as php variables
			$conference_room_uuid = $_GET["conference_room_uuid"];
			$record = $_GET["record"];
			$wait_mod = $_GET["wait_mod"];
			$announce = $_GET["announce"];
			$mute = $_GET["mute"];
			$sounds = $_GET["sounds"];
			$enabled = $_GET["enabled"];

		//record announcement
			if ($record == "true" && is_uuid($meeting_uuid)) {
				//prepare the values
					$default_language = 'en';
					$default_dialect = 'us';
					$default_voice = 'callie';
					$switch_cmd = "conference ".$meeting_uuid."@".$_SESSION['domain_name']." play ".$_SESSION['switch']['sounds']['dir']."/".$default_language."/".$default_dialect."/".$default_voice."/ivr/ivr-recording_started.wav";
				//connect to event socket
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}
			}

		//build the array
			$array['conference_rooms'][0]['conference_room_uuid'] = $conference_room_uuid;
			if (strlen($record) > 0) {
				$array['conference_rooms'][0]['record'] = $record;
			}
			if (strlen($wait_mod) > 0) {
				$array['conference_rooms'][0]['wait_mod'] = $wait_mod;
			}
			if (strlen($announce) > 0) {
				$array['conference_rooms'][0]['announce'] = $announce;
			}
			if (strlen($mute) > 0) {
				$array['conference_rooms'][0]['mute'] = $mute;
			}
			if (strlen($sounds) > 0) {
				$array['conference_rooms'][0]['sounds'] = $sounds;
			}
			if (strlen($enabled) > 0) {
				$array['conference_rooms'][0]['enabled'] = $enabled;
			}

		//save to the data
			$database = new database;
			$database->app_name = 'conference_rooms';
			$database->app_uuid = '8d083f5a-f726-42a8-9ffa-8d28f848f10e';
			$database->save($array);
			$message = $database->message;
			unset($array);
	}
*/

//get conference array
	$switch_cmd = "conference xml_list";
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		//connection to even socket failed
	}
	else {
		$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
		try {
			$xml = new SimpleXMLElement($xml_str, true);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}
		foreach ($xml->conference as $row) {
			//convert the xml object to an array
				$json = json_encode($row);
				$row = json_decode($json, true);
			//set the variables
				$conference_name = $row['@attributes']['name'];
				$session_uuid = $row['@attributes']['uuid'];
				$member_count = $row['@attributes']['member-count'];
			//show the conferences that have a matching domain
				$tmp_domain = substr($conference_name, -strlen($_SESSION['domain_name']));
				if ($tmp_domain == $_SESSION['domain_name']) {
					$meeting_uuid = substr($conference_name, 0, strlen($conference_name) - strlen('@'.$_SESSION['domain_name']));
					$conference[$meeting_uuid]["conference_name"] = $conference_name;
					$conference[$meeting_uuid]["session_uuid"] = $session_uuid;
					$conference[$meeting_uuid]["member_count"] = $member_count;
				}
		}
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the conference room count
	$conference_center = new conference_centers;
	$conference_center->db = $db;
	$conference_center->domain_uuid = $_SESSION['domain_uuid'];
	if (strlen($search) > 0) {
		$conference_center->search = $search;
	}
	$num_rows = $conference_center->room_count();

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	if (isset($_GET['page'])) {
		$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $page;
	}

//get the conference rooms
	$conference_center->rows_per_page = $rows_per_page;
	$conference_center->offset = $offset;
	$conference_center->order_by = $order_by;
	$conference_center->order = $order;
	if (strlen($search) > 0) {
		$conference_center->search = $search;
	}
	$result = $conference_center->rooms();

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include header
	$document['title'] = $text['title-conference_rooms'];
	require_once "resources/header.php";

//javascript for toggle select box
	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	function toggle_select() {\n";
	echo "		$('#conference_room_feature').fadeToggle(400, function() {\n";
	echo "			document.getElementById('conference_room_feature').selectedIndex = 0;\n";
	echo "			document.getElementById('conference_room_feature').focus();\n";
	echo "		});\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_rooms']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'conference_centers.php']);
	if (permission_exists('conference_room_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'conference_room_edit.php']);
	}
	if (permission_exists('conference_room_edit') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"toggle_select(); this.blur();"]);
		echo 		"<select class='formfld' style='display: none; width: auto;' id='conference_room_feature' onchange=\"if (this.selectedIndex != 0) { modal_open('modal-toggle','btn_toggle'); }\">";
		echo "			<option value='' selected='selected'>".$text['label-select']."</option>";
		echo "			<option value='record'>".$text['label-record']."</option>";
		echo "			<option value='wait_mod'>".$text['label-wait_moderator']."</option>";
		echo "			<option value='announce_name'>".$text['label-announce_name']."</option>";
		echo "			<option value='announce_count'>".$text['label-announce_count']."</option>";
		echo "			<option value='announce_recording'>".$text['label-announce_recording']."</option>";
		echo "			<option value='mute'>".$text['label-mute']."</option>";
		echo "			<option value='sounds'>".$text['label-sounds']."</option>";
		echo "			<option value='enabled'>".$text['label-enabled']."</option>";
		echo "		</select>";
	}
	if (permission_exists('conference_room_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'bridges.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('conference_room_edit') && $result) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); document.getElementById('toggle_field').value = document.getElementById('conference_room_feature').options[document.getElementById('conference_room_feature').selectedIndex].value; list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('conference_room_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-conference_rooms']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' id='toggle_field' name='toggle_field' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('conference_room_add') || permission_exists('conference_room_edit') || permission_exists('conference_room_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	//echo th_order_by('conference_center_uuid', 'Conference UUID', $order_by, $order);
	echo "<th>".$text['label-name']."</th>\n";
	echo "<th>".$text['label-moderator-pin']."</th>\n";
	echo "<th>".$text['label-participant-pin']."</th>\n";
	//echo th_order_by('profile', $text['label-profile'], $order_by, $order);
	echo th_order_by('record', $text['label-record'], $order_by, $order, null, "class='center'");
	//echo th_order_by('max_members', 'Max', $order_by, $order);
	echo th_order_by('wait_mod', $text['label-wait_moderator'], $order_by, $order, null, "class='center'");
	echo th_order_by('announce', $text['label-announce_name'], $order_by, $order, null, "class='center'");
	echo th_order_by('announce', $text['label-announce_count'], $order_by, $order, null, "class='center'");
	echo th_order_by('announce', $text['label-announce_recording'], $order_by, $order, null, "class='center'");
	//echo th_order_by('enter_sound', 'Enter Sound', $order_by, $order);
	echo th_order_by('mute', $text['label-mute'], $order_by, $order, null, "class='center'");
	echo th_order_by('sounds', $text['label-sounds'], $order_by, $order, null, "class='center'");
	echo "<th class='center'>".$text['label-members']."</th>\n";
	echo "<th>".$text['label-tools']."</th>\n";
	if (permission_exists('conference_room_enabled')) {
		echo th_order_by('enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	}
	echo th_order_by('description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('conference_room_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

//show the data
	if (is_array($result) > 0) {
		$x = 0;
		foreach ($result as $row) {
			$conference_room_name = $row['conference_room_name'];
			$moderator_pin = $row['moderator_pin'];
			$participant_pin = $row['participant_pin'];
			if (strlen($moderator_pin) == 9)  {
				$moderator_pin = substr($moderator_pin, 0, 3) ."-".  substr($moderator_pin, 3, 3) ."-". substr($moderator_pin, -3)."\n";
			}
			if (strlen($participant_pin) == 9)  {
				$participant_pin = substr($participant_pin, 0, 3) ."-".  substr($participant_pin, 3, 3) ."-". substr($participant_pin, -3)."\n";
			}

			if (permission_exists('conference_room_edit')) {
				$list_row_url = "conference_room_edit.php?id=".urlencode($row['conference_room_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('conference_room_add') || permission_exists('conference_room_edit') || permission_exists('conference_room_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='conference_rooms[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='conference_rooms[$x][uuid]' value='".escape($row['conference_room_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td><a href='".$list_row_url."'>".escape($conference_room_name)."</a>&nbsp;</td>\n";
			echo "	<td>".$moderator_pin."</td>\n";
			echo "	<td>".$participant_pin."</td>\n";
			//echo "	<td>".escape($row['conference_center_uuid'])."&nbsp;</td>\n";
			//echo "	<td>".escape($row['profile'])."&nbsp;</td>\n";

			if (permission_exists('conference_room_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['record'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'record'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['record'] == "true" ? 'true' : 'false')];
			}
			echo "	</td>\n";
// 			echo "	<td>";
// 			if ($row['record'] == "true") {
// 				echo "<a href='?conference_room_uuid=".urlencode($row['conference_room_uuid'])."&record=false&meeting_uuid=".urlencode($meeting_uuid)."'>".$text['label-true']."</a>";
// 			}
// 			else {
// 				echo "<a href='?conference_room_uuid=".urlencode($row['conference_room_uuid'])."&record=true&meeting_uuid=".urlencode($meeting_uuid)."'>".$text['label-false']."</a>";
// 			}
// 			echo "	</td>\n";
			//echo "	<td>".$row['max_members']."&nbsp;</td>\n";

			if (permission_exists('conference_room_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['wait_mod'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'wait_mod'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['wait_mod'] == "true" ? 'true' : 'false')];
			}
			echo "	</td>\n";
// 			echo "	<td>";
// 			if ($row['wait_mod'] == "true") {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&wait_mod=false'>".$text['label-true']."</a>";
// 			}
// 			else {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&wait_mod=true'>".$text['label-false']."</a>";
// 			}
// 			echo "	</td>\n";

			if (permission_exists('conference_room_edit') && permission_exists('conference_room_announce_name')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['announce_name'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'announce_name'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['announce_name'] == "true" ? 'true' : 'false')];
			}
			echo "	</td>\n";
			if (permission_exists('conference_room_edit') && permission_exists('conference_room_announce_count')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['announce_count'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'announce_count'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['announce_count'] == "true" ? 'true' : 'false')];
			}
			echo "	</td>\n";
			if (permission_exists('conference_room_edit') && permission_exists('conference_room_announce_recording')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['announce_recording'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'announce_recording'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['announce_recording'] == "true" ? 'true' : 'false')];
			}
			echo "	</td>\n";
// 			echo "	<td>";
// 			if ($row['announce'] == "true") {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&announce=false'>".$text['label-true']."</a>";
// 			}
// 			else {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&announce=true'>".$text['label-false']."</a>";
// 			}
// 			echo "	</td>\n";

			if (permission_exists('conference_room_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['mute'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'mute'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['mute'] == "true" ? 'true' : 'false')];
			}
			echo "	</td>\n";
// 			echo "	<td>";
// 			if ($row['mute'] == "true") {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&mute=false'>".$text['label-true']."</a>&nbsp;";
// 			}
// 			else {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&mute=true'>".$text['label-false']."</a>&nbsp;";
// 			}
// 			echo "	</td>\n";

			if (permission_exists('conference_room_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['sounds'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'sounds'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['sounds'] == "true" ? 'true' : 'false')];
			}
			echo "	</td>\n";
// 			echo "	<td>";
// 			if ($row['sounds'] == "true") {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&sounds=false'>".$text['label-true']."</a>";
// 			}
// 			else {
// 				echo "<a href='?conference_room_uuid=".escape($row['conference_room_uuid'])."&sounds=true'>".$text['label-false']."</a>";
// 			}
// 			echo "	</td>\n";

			if (strlen($conference[$meeting_uuid]["session_uuid"])) {
				echo "	<td class='center'>".escape($conference[$meeting_uuid]["member_count"])."&nbsp;</td>\n";
			}
			else {
				echo "	<td class='center'>0</td>\n";
			}
			echo "	<td class='no-link no-wrap'>\n";
			if (permission_exists('conference_interactive_view')) {
				echo "		<a href='".PROJECT_PATH."/app/conferences_active/conference_interactive.php?c=".urlencode($row['conference_room_uuid'])."'>".$text['label-view']."</a>&nbsp;\n";
			}
			if (permission_exists('conference_cdr_view')) {	
				echo "		<a href='/app/conference_cdr/conference_cdr.php?id=".urlencode($row['conference_room_uuid'])."'>".$text['button-cdr']."</a>\n";
			}
			if (permission_exists('conference_session_view')) {	
				echo "		<a href='conference_sessions.php?id=".urlencode($row['conference_room_uuid'])."'>".$text['label-sessions']."</a>\n";
			}
			echo "	</td>\n";

			if (permission_exists('conference_room_enabled')) {
				if (permission_exists('conference_room_edit')) {
					echo "	<td class='no-link center'>\n";
					echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['enabled'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'enabled'; list_form_submit('form_list')"]);
				}
				else {
					echo "	<td class='center'>\n";
					echo $text['label-'.($row['enabled'] == "true" ? 'true' : 'false')];
				}
				echo "	</td>\n";
// 				echo "	<td>";
// 				if ($row['enabled'] == "true") {
// 					echo "<a href='?conference_room_uuid=".urlencode($row['conference_room_uuid'])."&enabled=false'>".$text['label-true']."</a>";
// 				}
// 				else {
// 					echo "<a href='?conference_room_uuid=".urlencode($row['conference_room_uuid'])."&enabled=true'>".$text['label-false']."</a>";
// 				}
// 				echo "	</td>\n";
			}

			echo "	<td class='description overflow hide-sm-dn'>".escape($row['description'])."</td>\n";
			if (permission_exists('conference_room_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($result);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
