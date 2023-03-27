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
	Portions created by the Initial Developer are Copyright (C) 2016-2020
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

//check permissions
	if (permission_exists('sip_profile_add') || permission_exists('sip_profile_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$sip_profile_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($_POST['sip_profile_uuid'])) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $_POST['sip_profile_uuid'];

				switch ($_POST['action']) {
					case 'delete':
						if (permission_exists('sip_profile_delete')) {
							$obj = new sip_profiles;
							$obj->delete($array);
						}
						break;
				}

				header('Location: sip_profiles.php');
				exit;
			}

		$sip_profile_uuid = $_POST["sip_profile_uuid"];
		$sip_profile_name = $_POST["sip_profile_name"];
		$sip_profile_hostname = $_POST["sip_profile_hostname"];
		$sip_profile_enabled = $_POST["sip_profile_enabled"] ?: 'false';
		$sip_profile_description = $_POST["sip_profile_description"];
		$sip_profile_domains = $_POST["sip_profile_domains"];
		$sip_profile_settings = $_POST["sip_profile_settings"];
		$sip_profile_domains_delete = $_POST["sip_profile_domains_delete"];
		$sip_profile_settings_delete = $_POST["sip_profile_settings_delete"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$sip_profile_uuid = $_POST["sip_profile_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: sip_profiles.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (strlen($sip_profile_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_uuid']."<br>\n"; }
			if (strlen($sip_profile_name) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_name']."<br>\n"; }
			//if (strlen($sip_profile_hostname) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_hostname']."<br>\n"; }
			if (strlen($sip_profile_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_enabled']."<br>\n"; }
			if (strlen($sip_profile_description) == 0) { $msg .= $text['message-required']." ".$text['label-sip_profile_description']."<br>\n"; }
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

		//check for duplicate profile name
			$sql = "select sip_profile_name from v_sip_profiles".($action == 'update' ? "where sip_profile_name <> :sip_profile_name" : null);
			if ($action == 'update') {
				$parameters['sip_profile_name'] = $sip_profile_name;
			}
			$database = new database;
			$rows = $database->select($sql, $parameters, 'all');
			if (is_array($rows) && @sizeof($rows) != 0) {
				foreach ($rows as $array) {
					$sip_profile_names[] = $array['sip_profile_name'];
				}
			}
			unset($sql);
			if (is_array($sip_profile_names) && @sizeof($sip_profile_names) != 0 && in_array($sip_profile_name, $sip_profile_names)) {

				//set message
					message::add($text['message-sip_profile_unique'], 'negative', 5000);

				//redirect
					header("Location: sip_profiles.php");
					exit;
			}

		//add the sip_profile_uuid
			if (!is_uuid($_POST["sip_profile_uuid"])) {
				$sip_profile_uuid = uuid();
			}

		//prepare the array
			$array['sip_profiles'][0]['sip_profile_uuid'] = $sip_profile_uuid;
			$array['sip_profiles'][0]['sip_profile_name'] = $sip_profile_name;
			$array['sip_profiles'][0]['sip_profile_hostname'] = $sip_profile_hostname;
			$array['sip_profiles'][0]['sip_profile_enabled'] = $sip_profile_enabled;
			$array['sip_profiles'][0]['sip_profile_description'] = $sip_profile_description;
			$y = 0;
			foreach ($sip_profile_domains as $row) {
				if (strlen($row['sip_profile_domain_uuid']) > 0) {
					if (is_uuid($row['sip_profile_domain_uuid'])) {
						$sip_profile_domain_uuid = $row['sip_profile_domain_uuid'];
					}
					else {
						$sip_profile_domain_uuid = uuid();
					}
					if (strlen($row["sip_profile_domain_alias"]) > 0) {
						$array['sip_profiles'][0]['sip_profile_domains'][$y]["sip_profile_uuid"] = $sip_profile_uuid;
						$array['sip_profiles'][0]['sip_profile_domains'][$y]["sip_profile_domain_uuid"] = $sip_profile_domain_uuid;
						$array['sip_profiles'][0]['sip_profile_domains'][$y]["sip_profile_domain_name"] = $row["sip_profile_domain_name"];
						$array['sip_profiles'][0]['sip_profile_domains'][$y]["sip_profile_domain_alias"] = $row["sip_profile_domain_alias"];
						$array['sip_profiles'][0]['sip_profile_domains'][$y]["sip_profile_domain_parse"] = $row["sip_profile_domain_parse"];
					}
					$y++;
				}
			}
			$y = 0;
			foreach ($sip_profile_settings as $row) {
				if (strlen($row['sip_profile_setting_uuid']) > 0) {
					if (is_uuid($row['sip_profile_setting_uuid'])) {
						$sip_profile_setting_uuid = $row['sip_profile_setting_uuid'];
					}
					else {
						$sip_profile_setting_uuid = uuid();
					}
					if (strlen($row["sip_profile_setting_name"]) > 0) {
						$array['sip_profiles'][0]['sip_profile_settings'][$y]["sip_profile_uuid"] = $sip_profile_uuid;
						$array['sip_profiles'][0]['sip_profile_settings'][$y]["sip_profile_setting_uuid"] = $sip_profile_setting_uuid;
						$array['sip_profiles'][0]['sip_profile_settings'][$y]["sip_profile_setting_name"] = $row["sip_profile_setting_name"];
						$array['sip_profiles'][0]['sip_profile_settings'][$y]["sip_profile_setting_value"] = $row["sip_profile_setting_value"];
						$array['sip_profiles'][0]['sip_profile_settings'][$y]["sip_profile_setting_enabled"] = $row["sip_profile_setting_enabled"];
						$array['sip_profiles'][0]['sip_profile_settings'][$y]["sip_profile_setting_description"] = $row["sip_profile_setting_description"];
					}
					$y++;
				}
			}

		//grant temporary permissions
			$p = new permissions;
			$p->add('sip_profile_domain_add', 'temp');
			$p->add('sip_profile_setting_add', 'temp');

		//save to the data
			$database = new database;
			$database->app_name = 'sip_profiles';
			$database->app_uuid = '159a8da8-0e8c-a26b-6d5b-19c532b6d470';
			$database->save($array);
			$message = $database->message;

		//revoke temporary permissions
			$p->delete('sip_profile_domain_add', 'temp');
			$p->delete('sip_profile_setting_add', 'temp');

		//remove checked domains
			if (
				$action == 'update'
				&& permission_exists('sip_profile_domain_delete')
				&& is_array($sip_profile_domains_delete)
				&& @sizeof($sip_profile_domains_delete) != 0
				) {
				$obj = new sip_profiles;
				$obj->sip_profile_uuid = $sip_profile_uuid;
				$obj->delete_domains($sip_profile_domains_delete);
			}

		//remove checked settings
			if (
				$action == 'update'
				&& permission_exists('sip_profile_setting_delete')
				&& is_array($sip_profile_settings_delete)
				&& @sizeof($sip_profile_settings_delete) != 0
				) {
				$obj = new sip_profiles;
				$obj->sip_profile_uuid = $sip_profile_uuid;
				$obj->delete_settings($sip_profile_settings_delete);
			}

		//get the hostname
			if ($sip_profile_hostname == '') {
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$sip_profile_hostname = event_socket_request($fp, 'api switchname');
				}
			}

		//clear the cache
			$cache = new cache;
			$cache->delete("configuration:sofia.conf:".$sip_profile_hostname);

		//save the sip profile xml
			save_sip_profile_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//redirect the user
			if ($action == "add") {
				message::add($text['message-add']);
			}
			if ($action == "update") {
				message::add($text['message-update']);
			}
			header('Location: sip_profile_edit.php?id='.urlencode($sip_profile_uuid));
			exit;
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sip_profile_uuid = $_GET["id"];
		$sql = "select * from v_sip_profiles ";
		$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
		$parameters['sip_profile_uuid'] = $sip_profile_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$sip_profile_name = $row["sip_profile_name"];
			$sip_profile_hostname = $row["sip_profile_hostname"];
			$sip_profile_enabled = $row["sip_profile_enabled"];
			$sip_profile_description = $row["sip_profile_description"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (strlen($sip_profile_enabled) == 0) { $sip_profile_enabled = 'true'; }

//get the child data
	$sql = "select * from v_sip_profile_settings ";
	$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
	$sql .= "order by sip_profile_setting_name ";
	$parameters['sip_profile_uuid'] = $sip_profile_uuid;
	$database = new database;
	$sip_profile_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row
	if (permission_exists('sip_profile_setting_add')) {
		$x = count($sip_profile_settings);
		$sip_profile_settings[$x]['sip_profile_setting_uuid'] = '';
		$sip_profile_settings[$x]['sip_profile_uuid'] = $sip_profile_uuid;
		$sip_profile_settings[$x]['sip_profile_setting_name'] = '';
		$sip_profile_settings[$x]['sip_profile_setting_value'] = '';
		$sip_profile_settings[$x]['sip_profile_setting_enabled'] = '';
		$sip_profile_settings[$x]['sip_profile_setting_description'] = '';
	}

//get the child data
	$sql = "select * from v_sip_profile_domains ";
	$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
	$parameters['sip_profile_uuid'] = $sip_profile_uuid;
	$database = new database;
	$sip_profile_domains = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add an empty row
	if (permission_exists('sip_profile_domain_add')) {
		$x = count($sip_profile_domains);
		$sip_profile_domains[$x]['sip_profile_domain_uuid'] = '';
		$sip_profile_domains[$x]['sip_profile_uuid'] = $sip_profile_uuid;
		$sip_profile_domains[$x]['sip_profile_domain_name'] = '';
		$sip_profile_domains[$x]['sip_profile_domain_alias'] = '';
		$sip_profile_domains[$x]['sip_profile_domain_parse'] = '';
	}

//create js array of existing sip profile names to prevent duplicates
	$sql = "select sip_profile_name from v_sip_profiles";
	$database = new database;
	$rows = $database->select($sql, $parameters, 'all');
	if (is_array($rows) && @sizeof($rows) != 0) {
		foreach ($rows as $array) {
			$sip_profile_names[] = $array['sip_profile_name'];
		}
		if (is_array($sip_profile_names) && @sizeof($sip_profile_names) != 0) {
			//all profile names
			$js_sip_profile_names['all'] = "const sip_profile_names_all = ['".implode("','", $sip_profile_names)."'];";
			//other profile names
			foreach ($sip_profile_names as $n => $name) {
				if ($sip_profile_name == $name) { unset($sip_profile_names[$n]); }
			}
			if (is_array($sip_profile_names) && @sizeof($sip_profile_names) != 0) {
				$js_sip_profile_names['other'] = "const sip_profile_names_other = ['".implode("','", $sip_profile_names)."'];";
			}
		}
	}
	unset($sql);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-sip_profile'];
	require_once "resources/header.php";
	
//helper scripts
	echo "<script language='javascript'>\n";

	//label to form input
		echo "	function label_to_form(label_id, form_id) {\n";
		echo "		if (document.getElementById(label_id) != null) {\n";
		echo "			label = document.getElementById(label_id);\n";
		echo "			label.parentNode.removeChild(label);\n";
		echo "		}\n";
		echo "		document.getElementById(form_id).style.display='';\n";
		echo "	}\n";

	//output js arrays to prevent duplicate profile names
		echo $js_sip_profile_names['all']."\n";
		echo $js_sip_profile_names['other']."\n";
		unset($js_sip_profile_names);

	echo "</script>\n";
	
//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-sip_profile']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'sip_profiles.php']);
	$button_margin = 'margin-left: 15px;';
	if ($action == 'update') {
		if (
			permission_exists('dialplan_add')
			|| permission_exists('inbound_route_add')
			|| permission_exists('outbound_route_add')
			|| permission_exists('time_condition_add')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$button_margin,'onclick'=>"modal_open('modal-copy','new_profile_name');"]);
			unset($button_margin);
		}
		if (
			permission_exists('sip_profile_delete')
			|| permission_exists('sip_profile_domain_delete')
			|| permission_exists('sip_profile_setting_delete')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$button_margin,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
			unset($button_margin);
		}
	}
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>"if (document.getElementById('sip_profile_name').value != '' && !sip_profile_names_other.includes(document.getElementById('sip_profile_name').value)) { $('#frm').submit(); } else { display_message('".$text['message-sip_profile_unique']."', 'negative', 5000); }"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update') {
		if (
			permission_exists('dialplan_add')
			|| permission_exists('inbound_route_add')
			|| permission_exists('outbound_route_add')
			|| permission_exists('time_condition_add')
			) {
			echo modal::create([
				'id'=>'modal-copy',
				'type'=>'general',
				'message'=>
					$text['label-new_sip_profile_name']."...<br /><br />\n
					<input class='formfld modal-input' data-continue='btn_copy' type='text' id='new_profile_name' maxlength='255'>\n",
				'actions'=>button::create([
					'type'=>'button',
					'label'=>$text['button-continue'],
					'icon'=>'check',
					'id'=>'btn_copy',
					'style'=>'float: right; margin-left: 15px;',
					'collapse'=>'never',
					'onclick'=>"modal_close(); if (document.getElementById('new_profile_name').value != '' && !sip_profile_names_all.includes(document.getElementById('new_profile_name').value)) { window.location='sip_profile_copy.php?id=".urlencode($sip_profile_uuid)."&name=' + document.getElementById('new_profile_name').value; } else { display_message('".$text['message-sip_profile_unique']."', 'negative', 5000); }",
					]),
				'onclose'=>"document.getElementById('new_profile_name').value = '';",
				]);
		}
		if (
			permission_exists('sip_profile_delete')
			|| permission_exists('sip_profile_domain_delete')
			|| permission_exists('sip_profile_setting_delete')
			) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' id='sip_profile_name' name='sip_profile_name' maxlength='255' value=\"".escape($sip_profile_name)."\">\n";
	echo "<br />\n";
	echo $text['description-sip_profile_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td class='vncell' align='left'>\n";
	echo "			".$text['title-sip_profile_domains']."\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<th class='vtable'>".$text['label-sip_profile_domain_name']."</th>\n";
	echo "					<th class='vtable' style='text-align: center;'>".$text['label-sip_profile_domain_alias']."</th>\n";
	echo "					<th class='vtable' style='text-align: center;'>".$text['label-sip_profile_domain_parse']."</th>\n";
	if (
		permission_exists('sip_profile_domain_delete') && (
			(permission_exists('sip_profile_domain_add') && is_array($sip_profile_domains) && @sizeof($sip_profile_domains) > 1) ||
			(!permission_exists('sip_profile_domain_add') && is_array($sip_profile_domains) && @sizeof($sip_profile_domains) != 0)
		)) {
		echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_domains', 'delete_toggle_domains');\" onmouseout=\"swap_display('delete_label_domains', 'delete_toggle_domains');\">\n";
		echo "						<span id='delete_label_domains'>".$text['label-delete']."</span>\n";
		echo "						<span id='delete_toggle_domains'><input type='checkbox' id='checkbox_all_domains' name='checkbox_all' onclick=\"edit_all_toggle('domains');\"></span>\n";
		echo "					</td>\n";
	}
	echo "				</tr>\n";
	$x = 0;
	foreach ($sip_profile_domains as $row) {
		$bottom_border = !is_uuid($row['sip_profile_domain_uuid']) ? "border-bottom: none;" : null;
		echo "			<tr>\n";
		if (is_uuid($row["sip_profile_uuid"])) {
			$sip_profile_uuid = $row["sip_profile_uuid"];
		}
		echo "				<input type='hidden' name='sip_profile_domains[$x][sip_profile_domain_uuid]' value='".(is_uuid($row["sip_profile_domain_uuid"]) ? $sip_profile_domain_uuid : uuid())."'>\n";
		echo "				<input type='hidden' name='sip_profile_domains[$x][sip_profile_uuid]' value='".escape($sip_profile_uuid)."'>\n";
		echo "				<td class='vtablerow' style='".$bottom_border."' ".(permission_exists('sip_profile_domain_edit') ? "onclick=\"label_to_form('label_sip_profile_domain_name_$x','sip_profile_domain_name_$x');\"" : null)." nowrap='nowrap'>\n";
		echo "					&nbsp; <label id='label_sip_profile_domain_name_$x'>".escape($row["sip_profile_domain_name"])."</label>\n";
		echo "					<input id='sip_profile_domain_name_$x' class='formfld' style='display: none;' type='text' name='sip_profile_domains[$x][sip_profile_domain_name]' maxlength='255' value=\"".escape($row["sip_profile_domain_name"])."\">\n";
		echo "				</td>\n";
		echo "				<td class='vtablerow' style='".$bottom_border." text-align: center;' ".(permission_exists('sip_profile_domain_edit') ? "onclick=\"label_to_form('label_sip_profile_domain_alias_$x','sip_profile_domain_alias_$x');\"" : null)." nowrap='nowrap'>\n";
		echo "					<label id='label_sip_profile_domain_alias_$x'>".$text['label-'.$row["sip_profile_domain_alias"]]."</label>\n";
		echo "					<select id='sip_profile_domain_alias_$x' class='formfld' style='display: none;' name='sip_profile_domains[$x][sip_profile_domain_alias]'>\n";
		echo "						<option value=''></option>\n";
		echo "						<option value='true' ".($row["sip_profile_domain_alias"] == "true" ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "						<option value='false' ".($row["sip_profile_domain_alias"] == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "					</select>\n";
		echo "				</td>\n";
		echo "				<td class='vtablerow' style='".$bottom_border." text-align: center;' ".(permission_exists('sip_profile_domain_edit') ? "onclick=\"label_to_form('label_sip_profile_domain_parse_$x','sip_profile_domain_parse_$x');\"" : null)." nowrap='nowrap'>\n";
		echo "					<label id='label_sip_profile_domain_parse_$x'>".$text['label-'.$row["sip_profile_domain_parse"]]."</label>\n";
		echo "					<select id='sip_profile_domain_parse_$x' class='formfld' style='display: none;' name='sip_profile_domains[$x][sip_profile_domain_parse]'>\n";
		echo "						<option value=''></option>\n";
		echo "						<option value='true' ".($row["sip_profile_domain_parse"] == "true" ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
		echo "						<option value='false' ".($row["sip_profile_domain_parse"] == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "					</select>\n";
		echo "				</td>\n";
		if (permission_exists('sip_profile_domain_delete')) {
			if (is_uuid($row['sip_profile_domain_uuid'])) {
				echo "				<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
				echo "					<input type='checkbox' name='sip_profile_domains_delete[".$x."][checked]' value='true' class='chk_delete checkbox_domains' onclick=\"edit_delete_action('domains');\">\n";
				echo "					<input type='hidden' name='sip_profile_domains_delete[".$x."][uuid]' value='".escape($row['sip_profile_domain_uuid'])."' />\n";
			}
			else {
				echo "				<td>\n";
			}
			echo "				</td>\n";
		}
		echo "			</tr>\n";
		//convert last empty labels to form elements
		if (permission_exists('sip_profile_domain_add') && !is_uuid($row["sip_profile_domain_uuid"])) {
			echo "<script>\n";
			echo "	label_to_form('label_sip_profile_domain_name_$x','sip_profile_domain_name_$x');\n";
			echo "	label_to_form('label_sip_profile_domain_alias_$x','sip_profile_domain_alias_$x');\n";
			echo "	label_to_form('label_sip_profile_domain_parse_$x','sip_profile_domain_parse_$x');\n";
			echo "</script>\n";
		}
		$x++;
	}
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "		<td class='vncellreq' align='left'>\n";
	echo "			".$text['label-sip_profile_settings']."\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<th class='vtable'>&nbsp;".$text['label-sip_profile_setting_name']."</th>\n";
	echo "					<th class='vtable'>".$text['label-sip_profile_setting_value']."</th>\n";
	echo "					<th class='vtable' style='text-align: center;'>".$text['label-sip_profile_setting_enabled']."</th>\n";
	echo "					<th class='vtable'>".$text['label-sip_profile_setting_description']."</th>\n";
	if (
		permission_exists('sip_profile_setting_delete') && (
			(permission_exists('sip_profile_setting_add') && is_array($sip_profile_settings) && @sizeof($sip_profile_settings) > 1) ||
			(!permission_exists('sip_profile_setting_add') && is_array($sip_profile_settings) && @sizeof($sip_profile_settings) != 0)
		)) {
		echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_settings', 'delete_toggle_settings');\" onmouseout=\"swap_display('delete_label_settings', 'delete_toggle_settings');\">\n";
		echo "						<span id='delete_label_settings'>".$text['label-delete']."</span>\n";
		echo "						<span id='delete_toggle_settings'><input type='checkbox' id='checkbox_all_settings' name='checkbox_all' onclick=\"edit_all_toggle('settings');\"></span>\n";
		echo "					</td>\n";
	}
	echo "				</tr>\n";
	$x = 0;
	foreach ($sip_profile_settings as $row) {
		$bottom_border = !is_uuid($row['sip_profile_setting_uuid']) ? "border-bottom: none;" : null;
		echo "			<tr>\n";
		echo "				<input type='hidden' name='sip_profile_settings[$x][sip_profile_setting_uuid]' value='".(is_uuid($row["sip_profile_setting_uuid"]) ? $row["sip_profile_setting_uuid"] : uuid())."'>\n";
		echo "				<input type='hidden' name='sip_profile_settings[$x][sip_profile_uuid]' value='".escape($row["sip_profile_uuid"])."'>\n";
		echo "				<td class='vtablerow' style='".$bottom_border."' ".(permission_exists('sip_profile_setting_edit') ? "onclick=\"label_to_form('label_sip_profile_setting_name_$x','sip_profile_setting_name_$x');\"" : null)." nowrap='nowrap'>\n";
		echo "					&nbsp; <label id='label_sip_profile_setting_name_$x'>".escape($row["sip_profile_setting_name"])."</label>\n";
		echo "					<input id='sip_profile_setting_name_$x' class='formfld' style='display: none;' type='text' name='sip_profile_settings[$x][sip_profile_setting_name]' maxlength='255' value=\"".escape($row["sip_profile_setting_name"])."\">\n";
		echo "				</td>\n";
		echo "				<td class='vtablerow' style='".$bottom_border."' ".(permission_exists('sip_profile_setting_edit') ? "onclick=\"label_to_form('label_sip_profile_setting_value_$x','sip_profile_setting_value_$x');\"" : null)." nowrap='nowrap'>\n";
		echo "					<label id='label_sip_profile_setting_value_$x'>".escape(substr($row["sip_profile_setting_value"],0,22))." &nbsp;</label>\n";
		echo "					<input id='sip_profile_setting_value_$x' class='formfld' style='display: none;' type='text' name='sip_profile_settings[$x][sip_profile_setting_value]' maxlength='255' value=\"".escape($row["sip_profile_setting_value"])."\">\n";
		echo "				</td>\n";
		echo "				<td class='vtablerow' style='".$bottom_border." text-align: center;' ".(permission_exists('sip_profile_setting_edit') ? "onclick=\"label_to_form('label_sip_profile_setting_enabled_$x','sip_profile_setting_enabled_$x');\"" : null)." nowrap='nowrap'>\n";
		echo "					<label id='label_sip_profile_setting_enabled_$x'>".$text['label-'.$row["sip_profile_setting_enabled"]]."</label>\n";
		echo "					<select id='sip_profile_setting_enabled_$x' class='formfld' style='display: none;' name='sip_profile_settings[$x][sip_profile_setting_enabled].'>\n";
		echo "						<option value='true'>".$text['label-true']."</option>\n";
		echo "						<option value='false' ".($row['sip_profile_setting_enabled'] == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
		echo "					</select>\n";
		echo "				</td>\n";
		echo "				<td class='vtablerow' style='".$bottom_border."' ".(permission_exists('sip_profile_setting_edit') ? "onclick=\"label_to_form('label_sip_profile_setting_description_$x','sip_profile_setting_description_$x');\"" : null)." nowrap='nowrap'>\n";
		echo "					<label id='label_sip_profile_setting_description_$x'>".escape($row["sip_profile_setting_description"])."&nbsp;</label>\n";
		echo "					<input id='sip_profile_setting_description_$x' class='formfld' style='display: none;' type='text' name='sip_profile_settings[$x][sip_profile_setting_description]' maxlength='255' value=\"".escape($row["sip_profile_setting_description"])."\">\n";
		echo "				</td>\n";
		if (permission_exists('sip_profile_setting_delete')) {
			if (is_uuid($row['sip_profile_setting_uuid'])) {
				echo "				<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
				echo "					<input type='checkbox' name='sip_profile_settings_delete[".$x."][checked]' value='true' class='chk_delete checkbox_settings' onclick=\"edit_delete_action('settings');\">\n";
				echo "					<input type='hidden' name='sip_profile_settings_delete[".$x."][uuid]' value='".escape($row['sip_profile_setting_uuid'])."' />\n";
			}
			else {
				echo "				<td>\n";
			}
			echo "				</td>\n";
		}
		echo "			</tr>\n";
		//convert last empty labels to form elements
		if (permission_exists('sip_profile_setting_add') && !is_uuid($row["sip_profile_setting_uuid"])) {
			echo "<script>\n";
			echo "	label_to_form('label_sip_profile_setting_name_$x','sip_profile_setting_name_$x');\n";
			echo "	label_to_form('label_sip_profile_setting_value_$x','sip_profile_setting_value_$x');\n";
			echo "	label_to_form('label_sip_profile_setting_enabled_$x','sip_profile_setting_enabled_$x');\n";
			echo "	label_to_form('label_sip_profile_setting_description_$x','sip_profile_setting_description_$x');\n";
			echo "</script>\n";
		}
		$x++;
		$x++;
	}
	echo "			</table>\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_hostname']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sip_profile_hostname' maxlength='255' value=\"".escape($sip_profile_hostname)."\">\n";
	echo "<br />\n";
	echo $text['description-sip_profile_hostname']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='sip_profile_enabled' name='sip_profile_enabled' value='true' ".($sip_profile_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='sip_profile_enabled' name='sip_profile_enabled'>\n";
		echo "		<option value='true' ".($sip_profile_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($sip_profile_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-sip_profile_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sip_profile_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <textarea class='formfld' type='text' name='sip_profile_description'>".escape($sip_profile_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-sip_profile_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='sip_profile_uuid' value='".escape($sip_profile_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
