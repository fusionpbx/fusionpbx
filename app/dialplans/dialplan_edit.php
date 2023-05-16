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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('dialplan_add')
		|| permission_exists('dialplan_edit')
		|| permission_exists('inbound_route_add')
		|| permission_exists('inbound_route_edit')
		|| permission_exists('outbound_route_add')
		|| permission_exists('outbound_route_edit')
		|| permission_exists('fifo_add')
		|| permission_exists('fifo_edit')
		|| permission_exists('time_condition_add')
		|| permission_exists('time_condition_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (is_uuid($_GET["id"])) {
		$action = "update";
		$dialplan_uuid = $_GET["id"];
	}
	else {
		$action = "add";
	}

//set the app_uuid
	if (is_uuid($_REQUEST["app_uuid"])) {
		$app_uuid = $_REQUEST["app_uuid"];
	}

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		$hostname = $_POST["hostname"];
		$dialplan_name = $_POST["dialplan_name"];
		$dialplan_number = $_POST["dialplan_number"];
		$dialplan_order = $_POST["dialplan_order"];
		$dialplan_continue = $_POST["dialplan_continue"] != '' ? $_POST["dialplan_continue"] : 'false';
		$dialplan_details = $_POST["dialplan_details"];
		$dialplan_context = $_POST["dialplan_context"];
		$dialplan_enabled = $_POST["dialplan_enabled"];
		$dialplan_description = $_POST["dialplan_description"];
		$dialplan_details_delete = $_POST["dialplan_details_delete"];
	}

//get the list of applications
	if (!is_array($_SESSION['switch']['applications'])) {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$result = event_socket_request($fp, 'api show application');
			
			$show_applications = explode("\n\n", $result);
			$raw_applications = explode("\n", $show_applications[0]);
			unset($result);
			unset($fp);

			$previous_application = null;
			foreach($raw_applications as $row) {
				if (strlen($row) > 0) {
					$application_array = explode(",", $row);
					$application = $application_array[0];

					if (
						$application != "name" 
						&& $application != "system" 
						&& $application != "bgsystem" 
						&& $application != "spawn" 
						&& $application != "bg_spawn" 
						&& $application != "spawn_stream" 
						&& stristr($application, "[") != true
					) {
						if ($application != $previous_application) {
							$applications[] = $application;
						}
					}
					$previous_application = $application;
				}
			}
			$_SESSION['switch']['applications'] = $applications;
		} else {
			$_SESSION['switch']['applications'] = Array();
		}
	}

//process and save the data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the dialplan uuid
			if ($action == "update") {
				$dialplan_uuid = $_POST["dialplan_uuid"];
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($_POST['dialplan_uuid'])) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $_POST['dialplan_uuid'];

				$list_page = 'dialplans.php'.(is_uuid($app_uuid) ? '?app_uuid='.urlencode($app_uuid) : null);

				switch ($_POST['action']) {
					case 'copy':
						if (
							permission_exists('dialplan_add') ||
							permission_exists('inbound_route_add') ||
							permission_exists('outbound_route_add') ||
							permission_exists('fifo_add') ||
							permission_exists('time_condition_add')
							) {
							$obj = new dialplan;
							$obj->app_uuid = $app_uuid;
							$obj->list_page = $list_page;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (
							permission_exists('dialplan_delete') ||
							permission_exists('inbound_route_delete') ||
							permission_exists('outbound_route_delete') ||
							permission_exists('fifo_delete') ||
							permission_exists('time_condition_delete')
							) {
							$obj = new dialplan;
							$obj->app_uuid = $app_uuid;
							$obj->list_page = $list_page;
							$obj->delete($array);
						}
						break;
				}

				header('Location: '.$list_page);
				exit;
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dialplans.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($dialplan_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			if (strlen($dialplan_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
			if (strlen($dialplan_continue) == 0) { $msg .= $text['message-required'].$text['label-continue']."<br>\n"; }
			if (strlen($dialplan_context) == 0) { $msg .= $text['message-required'].$text['label-context']."<br>\n"; }
			if (strlen($dialplan_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			//if (strlen($dialplan_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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

		//remove the invalid characters from the dialplan name
			$dialplan_name = $_POST["dialplan_name"];
			$dialplan_name = str_replace(" ", "_", $dialplan_name);
			$dialplan_name = str_replace("/", "", $dialplan_name);

		//build the array
			$x = 0;
			if (is_uuid($_POST["dialplan_uuid"])) {
				$array['dialplans'][$x]['dialplan_uuid'] = $_POST["dialplan_uuid"];
			}
			else {
				$dialplan_uuid = uuid();
				$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
			}
			if (permission_exists('dialplan_domain')) {
				if (is_uuid($_POST["domain_uuid"])) {
					$array['dialplans'][$x]['domain_uuid'] = $_POST['domain_uuid'];
				}
				else {
					$array['dialplans'][$x]['domain_uuid'] = ''; //global
				}
			}
			else {
				$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
			}
			if ($action == 'add') {
				$array['dialplans'][$x]['app_uuid'] = uuid();
			}
			$array['dialplans'][$x]['hostname'] = $hostname;
			$array['dialplans'][$x]['dialplan_name'] = $dialplan_name;
			$array['dialplans'][$x]['dialplan_number'] = $_POST["dialplan_number"];
			$array['dialplans'][$x]['dialplan_destination'] = $_POST["dialplan_destination"];
			$array['dialplans'][$x]['dialplan_context'] = $_POST["dialplan_context"];
			$array['dialplans'][$x]['dialplan_continue'] = $_POST["dialplan_continue"];
			$array['dialplans'][$x]['dialplan_order'] = $_POST["dialplan_order"];
			$array['dialplans'][$x]['dialplan_enabled'] = $_POST["dialplan_enabled"];
			$array['dialplans'][$x]['dialplan_description'] = $_POST["dialplan_description"];
			$y = 0;
			if (is_array($_POST["dialplan_details"])) {
				foreach ($_POST["dialplan_details"] as $row) {
					if (strlen($row["dialplan_detail_tag"]) > 0) {
						if (strlen($row["dialplan_detail_uuid"]) > 0) {
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = $row["dialplan_detail_uuid"];
						}
						if (!preg_match("/system/i", $row["dialplan_detail_type"])) {
							$dialplan_detail_type = $row["dialplan_detail_type"];
						}
						if (!preg_match("/spawn/i", $row["dialplan_detail_type"])) {
							$dialplan_detail_type = $row["dialplan_detail_type"];
						}
						if (!preg_match("/system/i", $row["dialplan_detail_data"])) {
							$dialplan_detail_data = $row["dialplan_detail_data"];
						}
						if (!preg_match("/spawn/i", $row["dialplan_detail_data"])) {
							$dialplan_detail_data = $row["dialplan_detail_data"];
						}
						$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = is_uuid($_POST["domain_uuid"]) ? $_POST["domain_uuid"] : null;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = $row["dialplan_detail_tag"];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $dialplan_detail_type;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $dialplan_detail_data;
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_break'] = $row["dialplan_detail_break"];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = $row["dialplan_detail_inline"];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = ($row["dialplan_detail_group"] != '') ? $row["dialplan_detail_group"] : '0';
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $row["dialplan_detail_order"];
						$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = $row["dialplan_detail_enabled"];
					}
					$y++;
				}
			}

		//add or update the database
			$database = new database;
			$database->app_name = 'dialplans';
			$database->app_uuid = $app_uuid;
			if ( strlen($dialplan_uuid)>0 )
				$database->uuid($dialplan_uuid);
			$database->save($array);
			unset($array);

		//remove checked dialplan details
			if (
				$action == 'update'
				&& permission_exists('dialplan_detail_delete')
				&& is_array($dialplan_details_delete)
				&& @sizeof($dialplan_details_delete) != 0
				) {
				$obj = new dialplan;
				$obj->dialplan_uuid = $dialplan_uuid;
				$obj->app_uuid = $app_uuid;
				$obj->delete_details($dialplan_details_delete);
			}

		//update the dialplan xml
			$dialplans = new dialplan;
			$dialplans->source = "details";
			$dialplans->destination = "database";
			$dialplans->uuid = $dialplan_uuid;
			$dialplans->xml();

		//clear the cache
			$cache = new cache;
			if ($dialplan_context == "\${domain_name}" or $dialplan_context == "global") {
				$dialplan_context = "*";
			}
			$cache->delete("dialplan:".$dialplan_context);

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//set the message
			if ($action == "add") {
				message::add($text['message-add']);
			}
			else if ($action == "update") {
				message::add($text['message-update']);
			}
			header("Location: ?id=".escape($dialplan_uuid).(is_uuid($app_uuid) ? "&app_uuid=".$app_uuid : null));
			exit;

	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_dialplans ";
		$sql .= "where dialplan_uuid = :dialplan_uuid ";
		$parameters['dialplan_uuid'] = $dialplan_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$hostname = $row["hostname"];
			$dialplan_name = $row["dialplan_name"];
			$dialplan_number = $row["dialplan_number"];
			$dialplan_destination = $row["dialplan_destination"];
			$dialplan_order = $row["dialplan_order"];
			$dialplan_continue = $row["dialplan_continue"];
			$dialplan_context = $row["dialplan_context"];
			$dialplan_enabled = $row["dialplan_enabled"];
			$dialplan_description = $row["dialplan_description"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (strlen($dialplan_context) == 0) {
		$dialplan_context = $_SESSION['domain_name'];
	}
	if (strlen($dialplan_order) == 0) {
		$dialplan_order = '200';
	}
	if (strlen($dialplan_destination) == 0) {
		$dialplan_destination = 'false';
	}

//get the dialplan details in an array
	$sql = "select ";
	$sql .= "domain_uuid, dialplan_uuid, dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data, ";
	$sql .= "dialplan_detail_break, dialplan_detail_inline, dialplan_detail_group, dialplan_detail_order, cast(dialplan_detail_enabled as text) ";
	$sql .= "from v_dialplan_details ";
	$sql .= "where dialplan_uuid = :dialplan_uuid ";
	$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
	$parameters['dialplan_uuid'] = $dialplan_uuid;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
	//set the array index
		$x = 0;
	//define the array
		$details = array();
	//conditions
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				if ($row['dialplan_detail_tag'] == "condition") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		}
	//regex
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				if ($row['dialplan_detail_tag'] == "regex") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		}
	//actions
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				if ($row['dialplan_detail_tag'] == "action") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		}
	//anti-actions
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				if ($row['dialplan_detail_tag'] == "anti-action") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		}
		unset($result);
	//blank row
		if (is_array($details) && @sizeof($details) != 0) {
			foreach ($details as $group => $row) {
				//set the array key for the empty row
					$x = "999";
				//get the highest dialplan_detail_order
					if (is_array($row) && @sizeof($details) != 0) {
						foreach ($row as $key => $field) {
							$dialplan_detail_order = 0;
							if ($dialplan_detail_order < $field['dialplan_detail_order']) {
								$dialplan_detail_order = $field['dialplan_detail_order'];
							}
						}
					}
				//increment the highest order by 5
					$dialplan_detail_order = $dialplan_detail_order + 10;
				//set the rest of the empty array
					//$details[$group][$x]['domain_uuid'] = '';
					//$details[$group][$x]['dialplan_uuid'] = '';
					$details[$group][$x]['dialplan_detail_tag'] = '';
					$details[$group][$x]['dialplan_detail_type'] = '';
					$details[$group][$x]['dialplan_detail_data'] = '';
					$details[$group][$x]['dialplan_detail_break'] = '';
					$details[$group][$x]['dialplan_detail_inline'] = '';
					$details[$group][$x]['dialplan_detail_group'] = $group;
					$details[$group][$x]['dialplan_detail_order'] = $dialplan_detail_order;
					$details[$group][$x]['dialplan_detail_enabled'] = 'true';
					
			}
		}
	//sort the details array by group number
		if (is_array($details)) {
			ksort($details);
		}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dialplan_edit'];
	require_once "resources/header.php";

//javascript to change select to input and back again
	?><script language="javascript">
		var objs;

		function change_to_input(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			//tb.setAttribute('id', 'ivr_menu_option_param');
			tb.setAttribute('style', 'width:175px;');
			tb.value=obj.options[obj.selectedIndex].value;
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.setAttribute('style', 'margin-left: 4px;');
			tbb.type='button';
			tbb.value=$("<div />").html('&#9665;').text();
			tbb.objs=[obj,tb,tbb];
			tbb.onclick=function(){ replace_param(this.objs); }
			obj.parentNode.insertBefore(tb,obj);
			obj.parentNode.insertBefore(tbb,obj);
			obj.parentNode.removeChild(obj);
			replace_param(this.objs);
		}

		function replace_param(obj){
			obj[2].parentNode.insertBefore(obj[0],obj[2]);
			obj[0].parentNode.removeChild(obj[1]);
			obj[0].parentNode.removeChild(obj[2]);
		}
	</script>
	<?php

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dialplan_edit']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'dialplans.php'.(is_uuid($app_uuid) ? "?app_uuid=".urlencode($app_uuid) : null)]);
	if ($action == 'update') {
		if (permission_exists('dialplan_xml')) {
			echo button::create(['type'=>'button','label'=>$text['button-xml'],'icon'=>'code','style'=>'margin-left: 15px;','link'=>'dialplan_xml.php?id='.urlencode($dialplan_uuid).(is_uuid($app_uuid) ? "&app_uuid=".urlencode($app_uuid) : null)]);
		}
		$button_margin = 'margin-left: 15px;';
		if (
			permission_exists('dialplan_add') ||
			permission_exists('inbound_route_add') ||
			permission_exists('outbound_route_add') ||
			permission_exists('fifo_add') ||
			permission_exists('time_condition_add')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$button_margin,'onclick'=>"modal_open('modal-copy','btn_copy');"]);
			unset($button_margin);
		}
		if (
			permission_exists('dialplan_delete') ||
			permission_exists('dialplan_detail_delete') ||
			permission_exists('inbound_route_delete') ||
			permission_exists('outbound_route_delete') ||
			permission_exists('fifo_delete') ||
			permission_exists('time_condition_delete')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$button_margin,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
			unset($button_margin);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update') {
		if (
			permission_exists('dialplan_add') ||
			permission_exists('inbound_route_add') ||
			permission_exists('outbound_route_add') ||
			permission_exists('fifo_add') ||
			permission_exists('time_condition_add')
			) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (
			permission_exists('dialplan_delete') ||
			permission_exists('dialplan_detail_delete') ||
			permission_exists('inbound_route_delete') ||
			permission_exists('outbound_route_delete') ||
			permission_exists('fifo_delete') ||
			permission_exists('time_condition_delete')
			) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo $text['description-dialplan-edit']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' style='vertical-align: top;'>\n";

	echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "	<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "		".$text['label-name']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' width='70%' align='left'>\n";
	echo "		<input class='formfld' type='text' name='dialplan_name' maxlength='255' placeholder='' value=\"".escape($dialplan_name)."\" required='required'>\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "		".$text['label-number']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input class='formfld' type='text' name='dialplan_number' maxlength='255' placeholder='' value=\"".escape($dialplan_number)."\">\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "		".$text['label-hostname']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input class='formfld' type='text' name='hostname' maxlength='255' value=\"".escape($hostname)."\">\n";
	echo "		<br />\n";
	//echo "		".$text['description-hostname']."\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "		".$text['label-context']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left' width='70%'>\n";
	echo "		<input class='formfld' type='text' name='dialplan_context' maxlength='255' placeholder='' value=\"".escape($dialplan_context)."\">\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "		".$text['label-continue']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' name='dialplan_continue'>\n";
	if ($dialplan_continue == "true") {
		echo "			<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "			<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($dialplan_continue == "false") {
		echo "			<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "			<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "</td>";
	echo "<td width='50%' style='vertical-align: top;'>\n";

	echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "	<tr>\n";
	echo "	<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "		".$text['label-order']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left' width='70%'>\n";
	echo "		<select name='dialplan_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($i == $dialplan_order) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "			<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "			<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "			<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "		".$text['label-destination']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' name='dialplan_destination'>\n";
	echo "			<option value=''></option>\n";
	if ($dialplan_destination == "true") {
		echo "			<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "			<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($dialplan_destination == "false") {
		echo "			<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "			<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	if (permission_exists('dialplan_domain')) {
		echo "	<tr>\n";
		echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "		".$text['label-domain']."\n";
		echo "	</td>\n";
		echo "	<td class='vtable' align='left'>\n";
		echo "		<select class='formfld' name='domain_uuid'>\n";
		if (!is_uuid($domain_uuid)) {
			echo "		<option value='' selected='selected'>".$text['select-global']."</option>\n";
		}
		else {
			echo "		<option value=''>".$text['select-global']."</option>\n";
		}
		if (is_array($_SESSION['domains']) && @sizeof($_SESSION['domains']) != 0) {
			foreach ($_SESSION['domains'] as $row) {
				if ($row['domain_uuid'] == $domain_uuid) {
					echo "		<option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
				}
				else {
					echo "		<option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
				}
			}
		}
		echo "		</select>\n";
		echo "		<br />\n";
		//echo "		".$text['description-domain_name']."\n";
		echo "	</td>\n";
		echo "	</tr>\n";
	}

	echo "	<tr>\n";
	echo "	<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='dialplan_enabled'>\n";
	if ($dialplan_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "		".$text['label-description']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left' width='70%'>\n";
	//echo "		<textarea class='formfld' style='width: 250px;' name='dialplan_description'>".escape($dialplan_description)."</textarea>\n";
	echo "		<input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"".escape($dialplan_description)."\">\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	</table>\n";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br><br>";

	//dialplan details
	if ($action == "update") {
		?>
		<!--javascript to change select to input and back again-->
			<script language="javascript">

				function label_to_form(label_id, form_id) {
					if (document.getElementById(label_id) != null) {
						label = document.getElementById(label_id);
						label.parentNode.removeChild(label);
					}
					document.getElementById(form_id).style.display='';
				}

			</script>
		<?php

		//display the results
			if (is_array($details) && @sizeof($details) != 0) {

				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' style='margin: -2px; border-spacing: 2px;'>\n";

				$x = 0;
				foreach($details as $g => $group) {

					if ($x != 0) {
						echo "<tr><td colspan='7'><br><br></td></tr>";
					}

					echo "<tr>\n";
					echo "<td class='vncellcolreq'>".$text['label-tag']."</td>\n";
					echo "<td class='vncellcolreq'>".$text['label-type']."</td>\n";
					echo "<td class='vncellcol' width='70%'>".$text['label-data']."</td>\n";
					echo "<td class='vncellcol'>".$text['label-break']."</td>\n";
					echo "<td class='vncellcol' style='text-align: center;'>".$text['label-inline']."</td>\n";
					echo "<td class='vncellcolreq' style='text-align: center;'>".$text['label-group']."</td>\n";
					echo "<td class='vncellcolreq' style='text-align: center;'>".$text['label-order']."</td>\n";
					echo "<td class='vncellcolreq' style='text-align: center;'>".$text['label-enabled']."</td>\n";
					if (permission_exists('dialplan_detail_delete')) {
						echo "<td class='vncellcol edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_group_".$g."', 'delete_toggle_group_".$g."');\" onmouseout=\"swap_display('delete_label_group_".$g."', 'delete_toggle_group_".$g."');\">\n";
						echo "	<span id='delete_label_group_".$g."'>".$text['label-delete']."</span>\n";
						echo "	<span id='delete_toggle_group_".$g."'><input type='checkbox' id='checkbox_all_group_".$g."' name='checkbox_all' onclick=\"edit_all_toggle('group_".$g."');\"></span>\n";
						echo "</td>\n";
					}
					echo "</tr>\n";

					if (is_array($group) && @sizeof($group) != 0) {
						foreach($group as $index => $row) {

							//get the values from the database and set as variables
								$dialplan_detail_uuid = $row['dialplan_detail_uuid'];
								$dialplan_detail_tag = $row['dialplan_detail_tag'];
								$dialplan_detail_type = $row['dialplan_detail_type'];
								$dialplan_detail_data = $row['dialplan_detail_data'];
								$dialplan_detail_break = $row['dialplan_detail_break'];
								$dialplan_detail_inline = $row['dialplan_detail_inline'];
								$dialplan_detail_group = $row['dialplan_detail_group'];
								$dialplan_detail_order = $row['dialplan_detail_order'];
								$dialplan_detail_enabled = $row['dialplan_detail_enabled'];

							//default to enabled true
								if (strlen($dialplan_detail_enabled) == 0) {
									$dialplan_detail_enabled = 'true';
								}

							//no border on last row
								$no_border = ($index == 999) ? "border: none;" : null;

							//begin the row
								echo "<tr>\n";
							//determine whether to hide the element
								if (strlen($dialplan_detail_tag) == 0) {
									$element['hidden'] = false;
									$element['visibility'] = "";
								}
								else {
									$element['hidden'] = true;
									$element['visibility'] = "display: none;";
								}
							//add the primary key uuid
								if (is_uuid($dialplan_detail_uuid)) {
									echo "	<input name='dialplan_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".escape($dialplan_detail_uuid)."\">\n";
								}
							//tag
								$selected = "selected=\"selected\" ";
								echo "<td class='vtablerow' style='".$no_border."' onclick=\"label_to_form('label_dialplan_detail_tag_".$x."','dialplan_detail_tag_".$x."');\" nowrap='nowrap'>\n";
								if ($element['hidden']) {
									echo "	<label id=\"label_dialplan_detail_tag_".$x."\">".escape($dialplan_detail_tag)."</label>\n";
								}
								echo "	<select id='dialplan_detail_tag_".$x."' name='dialplan_details[".$x."][dialplan_detail_tag]' class='formfld' style='width: 97px; ".$element['visibility']."'>\n";
								echo "	<option></option>\n";
								echo "	<option value='condition' ".($dialplan_detail_tag == "condition" ? $selected : null).">".$text['option-condition']."</option>\n";
								echo "	<option value='regex' ".($dialplan_detail_tag == "regex" ? $selected : null).">".$text['option-regex']."</option>\n";
								echo "	<option value='action' ".($dialplan_detail_tag == "action" ? $selected : null).">".$text['option-action']."</option>\n";
								echo "	<option value='anti-action' ".($dialplan_detail_tag == "anti-action" ? $selected : null).">".$text['option-anti-action']."</option>\n";
								echo "	</select>\n";
								echo "</td>\n";
							//type
								echo "<td class='vtablerow' style='".$no_border."' onclick=\"label_to_form('label_dialplan_detail_type_".$x."','dialplan_detail_type_".$x."');\" nowrap='nowrap'>\n";
								if ($element['hidden']) {
									echo "	<label id=\"label_dialplan_detail_type_".$x."\">".escape($dialplan_detail_type)."</label>\n";
								}
								echo "	<select id='dialplan_detail_type_".$x."' name='dialplan_details[".$x."][dialplan_detail_type]' class='formfld' style='width: auto; ".$element['visibility']."' onchange='change_to_input(this);'>\n";
								if (strlen($dialplan_detail_type) > 0) {
									echo "	<optgroup label='selected'>\n";
									echo "		<option value=\"".escape($dialplan_detail_type)."\">".escape($dialplan_detail_type)."</option>\n";
									echo "	</optgroup>\n";
								}
								else {
									echo "	<option value=''></option>\n";
								}
								//if (strlen($dialplan_detail_tag) == 0 || $dialplan_detail_tag == "condition" || $dialplan_detail_tag == "regex") {
									echo "	<optgroup label='".$text['optgroup-condition_or_regex']."'>\n";
									echo "		<option value='ani'>".$text['option-ani']."</option>\n";
									echo "		<option value='ani2'>".$text['option-ani2']."</option>\n";
									echo "		<option value='caller_id_name'>".$text['option-caller_id_name']."</option>\n";
									echo "		<option value='caller_id_number'>".$text['option-caller_id_number']."</option>\n";
									echo "		<option value='chan_name'>".$text['option-chan_name']."</option>\n";
									echo "		<option value='context'>".$text['option-context']."</option>\n";
									echo "		<option value='destination_number'>".$text['option-destination_number']."</option>\n";
									echo "		<option value='dialplan'>".$text['option-dialplan']."</option>\n";
									echo "		<option value='network_addr'>".$text['option-network_addr']."</option>\n";
									echo "		<option value='rdnis'>".$text['option-rdnis']."</option>\n";
									echo "		<option value='source'>".$text['option-source']."</option>\n";
									echo "		<option value='username'>".$text['option-username']."</option>\n";
									echo "		<option value='uuid'>".$text['option-uuid']."</option>\n";
									echo "		<option value='\${call_direction}'>\${call_direction}</option>\n";
									echo "		<option value='\${number_alias}'>\${number_alias}</option>\n";
									echo "		<option value='\${sip_contact_host}'>\${sip_contact_host}</option>\n";
									echo "		<option value='\${sip_contact_uri}'>\${sip_contact_uri}</option>\n";
									echo "		<option value='\${sip_contact_user}'>\${sip_contact_user}</option>\n";
									echo "		<option value='\${sip_h_Diversion}'>\${sip_h_Diversion}</option>\n";
									echo "		<option value='\${sip_from_host}'>\${sip_from_host}</option>\n";
									echo "		<option value='\${sip_from_uri}'>\${sip_from_uri}</option>\n";
									echo "		<option value='\${sip_from_user}'>\${sip_from_user}</option>\n";
									echo "		<option value='\${sip_to_uri}'>\${sip_to_uri}</option>\n";
									echo "		<option value='\${sip_to_user}'>\${sip_to_user}</option>\n";
									echo "		<option value='\${toll_allow}'>\${toll_allow}</option>\n";
									echo "	</optgroup>\n";
								//}
								//if (strlen($dialplan_detail_tag) == 0 || $dialplan_detail_tag == "action" || $dialplan_detail_tag == "anti-action") {
									echo "	<optgroup label='".$text['optgroup-applications']."'>\n";
									if (is_array($_SESSION['switch']['applications'])) {
										foreach ($_SESSION['switch']['applications'] as $application) {
											echo "	<option value='".escape($application)."'>".escape($application)."</option>\n";
										}
									}
									echo "	</optgroup>\n";
								//}
								echo "	</select>\n";
								//echo "	<input type='button' id='btn_select_to_input_dialplan_detail_type' class='btn' style='visibility:hidden;' name='' alt='".$text['button-back']."' onclick='change_to_input(document.getElementById(\"dialplan_detail_type\"));this.style.visibility = \"hidden\";' value='&#9665;'>\n";
								echo "</td>\n";
							//data
								echo "<td class='vtablerow' onclick=\"label_to_form('label_dialplan_detail_data_".$x."','dialplan_detail_data_".$x."');\" style='".$no_border." width: 100%; max-width: 150px; overflow: hidden; _text-overflow: ellipsis; white-space: nowrap;' nowrap='nowrap'>\n";
								if ($element['hidden']) {
									$dialplan_detail_data_mod = $dialplan_detail_data;
									if ($dialplan_detail_type == 'bridge') {
										// split up failover bridges and get variables in statement
										$failover_bridges = explode('|', $dialplan_detail_data);
										preg_match('/^\{.*\}/', $failover_bridges[0], $bridge_vars);
										$bridge_vars = $bridge_vars[0];

										// rename parse and rename each gateway
										foreach ($failover_bridges as $bridge_statement_exploded) {
											// parse out gateway uuid
											$bridge_statement = str_replace($bridge_vars, '', explode('/', $bridge_statement_exploded));
											array_unshift($bridge_statement, $bridge_vars);

											if ($bridge_statement[1] == 'sofia' && $bridge_statement[2] == 'gateway' && is_uuid($bridge_statement[3])) {
												// retrieve gateway name from db
												$sql = "select gateway from v_gateways where gateway_uuid = :gateway_uuid ";
												$parameters['gateway_uuid'] = $bridge_statement[3];
												$database = new database;
												$gateways = $database->select($sql, $parameters, 'all');
												if (is_array($gateways) && @sizeof($gateways) != 0) {
													$gateway_name = $gateways[0]['gateway'];
													$bridge_statement_exploded_mod = str_replace($bridge_statement[3], $gateway_name, $bridge_statement_exploded);
												}
												$dialplan_detail_data_mod = str_replace($bridge_statement_exploded, $bridge_statement_exploded_mod, $dialplan_detail_data_mod);
												unset($sql, $parameters, $bridge_statement, $gateways, $bridge_statement_exploded, $bridge_statement_exploded_mod);
											}
										}
									}
									echo "	<label id=\"label_dialplan_detail_data_".$x."\">".escape($dialplan_detail_data_mod)."</label>\n";
								}
								echo "	<input id='dialplan_detail_data_".$x."' name='dialplan_details[".$x."][dialplan_detail_data]' class='formfld' type='text' style='width: calc(100% - 2px); min-width: calc(100% - 2px); max-width: calc(100% - 2px); ".$element['visibility']."' placeholder='' value=\"".escape($dialplan_detail_data)."\">\n";
								echo "</td>\n";
							//break
								echo "<td class='vtablerow' style='".$no_border."' onclick=\"label_to_form('label_dialplan_detail_break_".$x."','dialplan_detail_break_".$x."');\" nowrap='nowrap'>\n";
								if ($element['hidden']) {
									echo "	<label id=\"label_dialplan_detail_break_".$x."\">".escape($dialplan_detail_break)."</label>\n";
								}
								echo "	<select id='dialplan_detail_break_".$x."' name='dialplan_details[".$x."][dialplan_detail_break]' class='formfld' style='width: auto; ".$element['visibility']."'>\n";
								echo "	<option></option>\n";
								echo "	<option value='on-true' ".($dialplan_detail_break == "on-true" ? $selected : null).">".$text['option-on_true']."</option>\n";
								echo "	<option value='on-false' ".($dialplan_detail_break == "on-false" ? $selected : null).">".$text['option-on_false']."</option>\n";
								echo "	<option value='always' ".($dialplan_detail_break == "always" ? $selected : null).">".$text['option-always']."</option>\n";
								echo "	<option value='never' ".($dialplan_detail_break == "never" ? $selected : null).">".$text['option-never']."</option>\n";
								echo "	</select>\n";
								echo "</td>\n";
							//inline
								echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_dialplan_detail_inline_".$x."','dialplan_detail_inline_".$x."');\" nowrap='nowrap'>\n";
								if ($element['hidden']) {
									echo "	<label id=\"label_dialplan_detail_inline_".$x."\">".escape($dialplan_detail_inline)."</label>\n";
								}
								echo "	<select id='dialplan_detail_inline_".$x."' name='dialplan_details[".$x."][dialplan_detail_inline]' class='formfld' style='width: auto; ".$element['visibility']."'>\n";
								echo "	<option></option>\n";
								echo "	<option value='true' ".($dialplan_detail_inline == "true" ? $selected : null).">".$text['option-true']."</option>\n";
								echo "	<option value='false' ".($dialplan_detail_inline == "false" ? $selected : null).">".$text['option-false']."</option>\n";
								echo "	</select>\n";
								echo "</td>\n";
							//group
								echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_dialplan_detail_group_".$x."','dialplan_detail_group_".$x."');\" nowrap='nowrap'>\n";
								if ($element['hidden']) {
									echo "	<label id=\"label_dialplan_detail_group_".$x."\">".escape($dialplan_detail_group)."</label>\n";
								}
								echo "	<input id='dialplan_detail_group_".$x."' name='dialplan_details[".$x."][dialplan_detail_group]' class='formfld' type='number' min='0' step='1' style='width: 45px; text-align: center; ".$element['visibility']."' placeholder='' value=\"".escape($dialplan_detail_group)."\" onclick='this.select();'>\n";
								/*
								echo "	<select id='dialplan_detail_group_".$x."' name='dialplan_details[".$x."][dialplan_detail_group]' class='formfld' style='".$element['width']." ".$element['visibility']."'>\n";
								echo "	<option value=''></option>\n";
								if (strlen($dialplan_detail_group)> 0) {
									echo "	<option $selected value='".escape($dialplan_detail_group)."'>".escape($dialplan_detail_group)."</option>\n";
								}
								$i=0;
								while($i<=999) {
									echo "	<option value='$i'>$i</option>\n";
									$i++;
								}
								echo "	</select>\n";
								*/
								echo "</td>\n";
							//order
								echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_dialplan_detail_order_".$x."','dialplan_detail_order_".$x."');\" nowrap='nowrap'>\n";
								if ($element['hidden']) {
									echo "	<label id=\"label_dialplan_detail_order_".$x."\">".escape($dialplan_detail_order)."</label>\n";
								}
								echo "	<input id='dialplan_detail_order_".$x."' name='dialplan_details[".$x."][dialplan_detail_order]' class='formfld' type='number' min='0' step='1' style='width: 45px; text-align: center; ".$element['visibility']."' placeholder='' value=\"".escape($dialplan_detail_order)."\" onclick='this.select();'>\n";
								/*
								echo "	<select id='dialplan_detail_order_".$x."' name='dialplan_details[".$x."][dialplan_detail_order]' class='formfld' style='".$element['width']." ".$element['visibility']."'>\n";
								if (strlen($dialplan_detail_order)> 0) {
									echo "	<option $selected value='".escape($dialplan_detail_order)."'>".escape($dialplan_detail_order)."</option>\n";
								}
								$i=0;
								while($i<=999) {
									if (strlen($i) == 1) {
										echo "	<option value='00$i'>00$i</option>\n";
									}
									if (strlen($i) == 2) {
										echo "	<option value='0$i'>0$i</option>\n";
									}
									if (strlen($i) == 3) {
										echo "	<option value='$i'>$i</option>\n";
									}
									$i++;
								}
								echo "	</select>\n";
								*/
								echo "</td>\n";
							//enabled
								echo "<td class='vtablerow' style='".$no_border." text-align: center;' onclick=\"label_to_form('label_dialplan_detail_enabled_".$x."','dialplan_detail_enabled_".$x."');\" nowrap='nowrap'>\n";
								if ($element['hidden']) {
									echo "	<label id=\"label_dialplan_detail_enabled_".$x."\">".escape($dialplan_detail_enabled)."</label>\n";
								}
								echo "	<select id='dialplan_detail_enabled_".$x."' name='dialplan_details[".$x."][dialplan_detail_enabled]' class='formfld' style='width: auto; ".$element['visibility']."'>\n";
								echo "	<option></option>\n";
								echo "	<option value='true' ".($dialplan_detail_enabled == "true" ? $selected : null).">".$text['option-true']."</option>\n";
								echo "	<option value='false' ".($dialplan_detail_enabled == "false" ? $selected : null).">".$text['option-false']."</option>\n";
								echo "	</select>\n";
								echo "</td>\n";
							//tools
								if (permission_exists('dialplan_detail_delete')) {
									if (is_uuid($dialplan_detail_uuid)) {
										echo "<td class='vtable' style='text-align: center;'>";
										echo "	<input type='checkbox' name='dialplan_details_delete[".$x."][checked]' value='true' class='chk_delete checkbox_group_".$g."' onclick=\"edit_delete_action('group_".$g."');\">\n";
										echo "	<input type='hidden' name='dialplan_details_delete[".$x."][uuid]' value='".escape($dialplan_detail_uuid)."' />\n";
									}
									else {
										echo "<td>\n";
									}
									echo "	</td>\n";
								}
							//end the row
								echo "</tr>\n";
							//increment the value
								$x++;
						}
					}
					$x++;
				} //end foreach
				unset($details);

				echo "</table>";

			} //end if results

	} //end if update

	echo "<br /><br />\n";

	echo "<input type='hidden' name='app_uuid' value='".escape($app_uuid)."'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "	<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//show the footer
	require_once "resources/footer.php";

?>
