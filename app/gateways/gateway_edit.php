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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
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
	if (permission_exists('gateway_add') || permission_exists('gateway_edit')) {
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
		if (is_uuid($_POST["id"])) {
			$gateway_uuid = $_REQUEST["id"];
		}
		if (is_uuid($_POST["gateway_uuid"])) {
			$gateway_uuid = $_POST["gateway_uuid"];
		}
	}
	else {
		$action = "add";
		$gateway_uuid = uuid();
	}

//get total gateway count from the database, check limit, if defined
	if ($action == 'add') {
		if (is_numeric($_SESSION['limit']['gateways']['numeric'])) {
			$sql = "select count(gateway_uuid) from v_gateways ";
			$sql .= "where (domain_uuid = :domain_uuid ".(permission_exists('gateway_domain') ? " or domain_uuid is null " : null).") ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$total_gateways = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);
			if ($total_gateways >= $_SESSION['limit']['gateways']['numeric']) {
				message::add($text['message-maximum_gateways'].' '.$_SESSION['limit']['gateways']['numeric'], 'negative');
				header('Location: gateways.php');
				exit;
			}
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$domain_uuid = $_POST["domain_uuid"];
		$gateway = $_POST["gateway"];
		$username = $_POST["username"];
		$password = $_POST["password"];
		$distinct_to = $_POST["distinct_to"];
		$auth_username = $_POST["auth_username"];
		$realm = $_POST["realm"];
		$from_user = $_POST["from_user"];
		$from_domain = $_POST["from_domain"];
		$proxy = $_POST["proxy"];
		$register_proxy = $_POST["register_proxy"];
		$outbound_proxy = $_POST["outbound_proxy"];
		$expire_seconds = $_POST["expire_seconds"];
		$register = $_POST["register"];
		$register_transport = $_POST["register_transport"];
		$retry_seconds = $_POST["retry_seconds"];
		$extension = $_POST["extension"];
		$ping = $_POST["ping"];
		$ping_min = $_POST["ping_min"];
		$ping_max = $_POST["ping_max"];
		$contact_in_ping = $_POST["contact_in_ping"];
		$channels = $_POST["channels"];
		$caller_id_in_from = $_POST["caller_id_in_from"];
		$supress_cng = $_POST["supress_cng"];
		$sip_cid_type = $_POST["sip_cid_type"];
		$codec_prefs = $_POST["codec_prefs"];
		$extension_in_contact = $_POST["extension_in_contact"];
		$context = $_POST["context"];
		$profile = $_POST["profile"];
		$hostname = $_POST["hostname"];
		$enabled = $_POST["enabled"];
		$description = $_POST["description"];
	}

//prevent the domain_uuid from not being set by someone without this permission
	if (!permission_exists('gateway_domain')) {
		$domain_uuid = $_SESSION['domain_uuid'];
	}

//process the HTTP POST
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: gateways.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($gateway) == 0) { $msg .= $text['message-required']." ".$text['label-gateway']."<br>\n"; }
			if ($register == "true") {
				if (strlen($username) == 0) { $msg .= $text['message-required']." ".$text['label-username']."<br>\n"; }
				if (strlen($password) == 0) { $msg .= $text['message-required']." ".$text['label-password']."<br>\n"; }
			}
			if (strlen($proxy) == 0) { $msg .= $text['message-required']." ".$text['label-proxy']."<br>\n"; }
			if (strlen($expire_seconds) == 0) { $msg .= $text['message-required']." ".$text['label-expire_seconds']."<br>\n"; }
			if (strlen($register) == 0) { $msg .= $text['message-required']." ".$text['label-register']."<br>\n"; }
			if (strlen($retry_seconds) == 0) { $msg .= $text['message-required']." ".$text['label-retry_seconds']."<br>\n"; }
			if (strlen($channels) == 0) {
				//$msg .= $text['message-required']." ".$text['label-channels']."<br>\n";
				$channels = 0;
			}
			if (strlen($context) == 0) { $msg .= $text['message-required']." ".$text['label-context']."<br>\n"; }
			if (strlen($profile) == 0) { $msg .= $text['message-required']." ".$text['label-profile']."<br>\n"; }
			if (strlen($enabled) == 0) { $msg .= $text['message-required']." ".$text['label-enabled']."<br>\n"; }
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

				//build the gateway array
					$x = 0;
					$array['gateways'][$x]["domain_uuid"] = is_uuid($domain_uuid) ? $domain_uuid : null;
					$array['gateways'][$x]["gateway_uuid"] = $gateway_uuid;
					$array['gateways'][$x]["gateway"] = $gateway;
					$array['gateways'][$x]["username"] = $username;
					$array['gateways'][$x]["password"] = $password;
					$array['gateways'][$x]["distinct_to"] = $distinct_to;
					$array['gateways'][$x]["auth_username"] = $auth_username;
					$array['gateways'][$x]["realm"] = $realm;
					$array['gateways'][$x]["from_user"] = $from_user;
					$array['gateways'][$x]["from_domain"] = $from_domain;
					$array['gateways'][$x]["proxy"] = $proxy;
					$array['gateways'][$x]["register_proxy"] = $register_proxy;
					$array['gateways'][$x]["outbound_proxy"] = $outbound_proxy;
					$array['gateways'][$x]["expire_seconds"] = $expire_seconds;
					$array['gateways'][$x]["register"] = $register;
					$array['gateways'][$x]["register_transport"] = $register_transport;
					$array['gateways'][$x]["retry_seconds"] = $retry_seconds;
					$array['gateways'][$x]["extension"] = $extension;
					$array['gateways'][$x]["ping"] = $ping;
					$array['gateways'][$x]["ping_min"] = $ping_min;
					$array['gateways'][$x]["ping_max"] = $ping_max;
					$array['gateways'][$x]["contact_in_ping"] = $contact_in_ping;
					$array['gateways'][$x]["channels"] = $channels;
					$array['gateways'][$x]["caller_id_in_from"] = $caller_id_in_from;
					$array['gateways'][$x]["supress_cng"] = $supress_cng;
					$array['gateways'][$x]["sip_cid_type"] = $sip_cid_type;
					$array['gateways'][$x]["codec_prefs"] = $codec_prefs;
					$array['gateways'][$x]["extension_in_contact"] = $extension_in_contact;
					$array['gateways'][$x]["context"] = $context;
					$array['gateways'][$x]["profile"] = $profile;
					$array['gateways'][$x]["hostname"] = strlen($hostname) != 0 ? $hostname : null;
					$array['gateways'][$x]["enabled"] = $enabled;
					$array['gateways'][$x]["description"] = $description;

				//update gateway session variable
					if ($enabled == 'true') {
						$_SESSION['gateways'][$gateway_uuid] = $gateway;
					}
					else {
						unset($_SESSION['gateways'][$gateway_uuid]);
					}

				//save to the data
					$database = new database;
					$database->app_name = 'gateways';
					$database->app_uuid = '297ab33e-2c2f-8196-552c-f3567d2caaf8';
					if (is_uuid($gateway_uuid)) {
						$database->uuid($gateway_uuid);
					}
					$database->save($array);
					$message = $database->message;

				//remove xml file (if any) if not enabled
					if ($enabled != 'true' && $_SESSION['switch']['sip_profiles']['dir'] != '') {
						$gateway_xml_file = $_SESSION['switch']['sip_profiles']['dir']."/".$profile."/v_".$gateway_uuid.".xml";
						if (file_exists($gateway_xml_file)) {
							unlink($gateway_xml_file);
						}
					}

				//syncrhonize configuration
					save_gateway_xml();

				//clear the cache
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					$hostname = trim(event_socket_request($fp, 'api switchname'));
					$cache = new cache;
					$cache->delete("configuration:sofia.conf:".$hostname);

				//rescan the external profile to look for new or stopped gateways
					//create the event socket connection
						$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
						$tmp_cmd = 'api sofia profile external rescan';
						$response = event_socket_request($fp, $tmp_cmd);
						unset($tmp_cmd);
						usleep(1000);
					//close the connection
						fclose($fp);
					//clear the apply settings reminder
						$_SESSION["reload_xml"] = false;

			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
				header("Location: gateways.php");
				exit;
			}
	}

//pre-populate the form
	if (count($_GET) > 0 && is_uuid($_GET["id"]) && $_POST["persistformvar"] != "true") {
		$gateway_uuid = $_GET["id"];
		$sql = "select * from v_gateways ";
		$sql .= "where gateway_uuid = :gateway_uuid ";
		$parameters['gateway_uuid'] = $gateway_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$gateway = $row["gateway"];
			$username = $row["username"];
			$password = $row["password"];
			$distinct_to = $row["distinct_to"];
			$auth_username = $row["auth_username"];
			$realm = $row["realm"];
			$from_user = $row["from_user"];
			$from_domain = $row["from_domain"];
			$proxy = $row["proxy"];
			$register_proxy = $row["register_proxy"];
			$outbound_proxy = $row["outbound_proxy"];
			$expire_seconds = $row["expire_seconds"];
			$register = $row["register"];
			$register_transport = $row["register_transport"];
			$retry_seconds = $row["retry_seconds"];
			$extension = $row["extension"];
			$ping = $row["ping"];
			$ping_min = $row["ping_min"];
			$ping_max = $row["ping_max"];
			$contact_in_ping = $row["contact_in_ping"];
			$channels = $row["channels"];
			$caller_id_in_from = $row["caller_id_in_from"];
			$supress_cng = $row["supress_cng"];
			$sip_cid_type = $row["sip_cid_type"];
			$codec_prefs = $row["codec_prefs"];
			$extension_in_contact = $row["extension_in_contact"];
			$context = $row["context"];
			$profile = $row["profile"];
			$hostname = $row["hostname"];
			$enabled = $row["enabled"];
			$description = $row["description"];
		}
		unset($sql, $parameters, $row);
	}

//get the sip profiles
	$sql = "select sip_profile_name from v_sip_profiles ";
	$sql .= "where sip_profile_enabled = 'true' ";
	$sql .= "order by sip_profile_name asc ";
	$database = new database;
	$sip_profiles = $database->select($sql, null, 'all');
	unset($sql);

//set defaults
	if (strlen($enabled) == 0) { $enabled = "true"; }
	if (strlen($register) == 0) { $register = "true"; }
	if (strlen($retry_seconds) == 0) { $retry_seconds = "30"; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-gateway'];
	require_once "resources/header.php";

//show the content
	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	$('#show_advanced_box').slideToggle();\n";
	echo "	$('#show_advanced').slideToggle();\n";
	echo "}\n";
	echo "</script>";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-gateway']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'gateways.php']);
	if ($action == "update" && permission_exists('gateway_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>'submit_form();']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update" && permission_exists('gateway_add')) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','link'=>'gateway_copy.php?id='.urlencode($gateway_uuid),'onclick'=>"modal_close();"])]);
	}

	echo $text['description-gateway-edit']."\n";
	echo "<br /><br />\n";

	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-gateway']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='gateway' maxlength='255' value=\"".escape($gateway)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-gateway-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-username']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='username' maxlength='255' autocomplete='off' value=\"".escape($username)."\">\n";
	echo "    <input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "<br />\n";
	echo $text['description-username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "    <input class='formfld' type='password' name='password' id='password' autocomplete='new-password' maxlength='255' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" value=\"".escape($password)."\">\n";
	echo "    <br />\n";
	echo "    ".$text['description-password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-from_user']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='from_user' maxlength='255' value=\"".escape($from_user)."\">\n";
	echo "<br />\n";
	echo $text['description-from_user']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-from_domain']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='from_domain' maxlength='255' value=\"".escape($from_domain)."\">\n";
	echo "<br />\n";
	echo $text['description-from_domain']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-proxy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='proxy' maxlength='255' value=\"".escape($proxy)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-proxy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-realm']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='realm' maxlength='255' value=\"".escape($realm)."\">\n";
	echo "<br />\n";
	echo $text['description-realm']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-expire_seconds']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (strlen($expire_seconds) == 0) { $expire_seconds = "800"; }
	echo "  <input class='formfld' type='number' name='expire_seconds' maxlength='255' value='".escape($expire_seconds)."' min='1' max='65535' step='1' required='required'>\n";
	echo "<br />\n";
	echo $text['description-expire_seconds']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-register']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='register'>\n";
	if ($register == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($register == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-register']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-retry_seconds']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='number' name='retry_seconds' maxlength='255' value='".escape($retry_seconds)."' min='1' max='65535' step='1' required='required'>\n";
	echo "<br />\n";
	echo $text['description-retry_seconds']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	//--- begin: show_advanced -----------------------
	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

	echo "	<div id=\"show_advanced_box\">\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
	echo "		<td width=\"70%\" class=\"vtable\">\n";
	echo button::create(['type'=>'button','label'=>$text['button-advanced'],'icon'=>'tools','onclick'=>'show_advanced_config();']);
	echo "		</td>\n";
	echo "		</tr>\n";
	echo "		</table>\n";
	echo "	</div>\n";

	echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-distinct_to']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='distinct_to'>\n";
	echo "    <option value=''></option>\n";
	if ($distinct_to == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($distinct_to == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-distinct_to']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-auth_username']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='auth_username' maxlength='255' value=\"".escape($auth_username)."\">\n";
	echo "<br />\n";
	echo $text['description-auth_username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='extension' maxlength='255' value=\"".escape($extension)."\">\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-register_transport']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='register_transport'>\n";
	echo "    <option value=''></option>\n";
	if ($register_transport == "udp") {
		echo "    <option value='udp' selected='selected'>udp</option>\n";
	}
	else {
		echo "    <option value='udp'>udp</option>\n";
	}
	if ($register_transport == "tcp") {
		echo "    <option value='tcp' selected='selected'>tcp</option>\n";
	}
	else {
		echo "    <option value='tcp'>tcp</option>\n";
	}
	if ($register_transport == "tls") {
		echo "    <option value='tls' selected='selected'>tls</option>\n";
	}
	else {
		echo "    <option value='tls'>tls</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-register_transport']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-register_proxy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='register_proxy' maxlength='255' value=\"".escape($register_proxy)."\">\n";
	echo "<br />\n";
	echo $text['description-register_proxy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-outbound_proxy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='outbound_proxy' maxlength='255' value=\"".escape($outbound_proxy)."\">\n";
	echo "<br />\n";
	echo $text['description-outbound_proxy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "		".$text['label-caller_id_in_from']."\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' name='caller_id_in_from'>\n";
	echo "		<option value=''></option>\n";
	if ($caller_id_in_from == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($caller_id_in_from == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-caller_id_in_from']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-supress_cng']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='supress_cng'>\n";
	echo "    <option value=''></option>\n";
	if ($supress_cng == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($supress_cng == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-supress_cng']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-sip_cid_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='sip_cid_type' maxlength='255' value=\"".escape($sip_cid_type)."\" pattern='^(none|pid|rpid)$'>\n";
	echo "<br />\n";
	echo $text['description-sip_cid_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-codec_prefs']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='codec_prefs' maxlength='255' value=\"".escape($codec_prefs)."\">\n";
	echo "<br />\n";
	echo $text['description-codec_prefs']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-extension_in_contact']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='extension_in_contact'>\n";
	echo "    <option value=''></option>\n";
	if ($extension_in_contact == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($extension_in_contact == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-extension_in_contact']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-ping']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='number' name='ping' maxlength='255' min='1' max='65535' step='1' value=\"".escape($ping)."\">\n";
	echo "<br />\n";
	echo $text['description-ping']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-ping_min']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='number' name='ping_min' maxlength='255' min='1' max='65535' step='1' value=\"".escape($ping_min)."\">\n";
	echo "<br />\n";
	echo $text['description-ping_min']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-ping_max']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='number' name='ping_max' maxlength='255' min='1' max='65535' step='1' value=\"".escape($ping_max)."\">\n";
	echo "<br />\n";
	echo $text['description-ping_max']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-contact_in_ping']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='contact_in_ping'>\n";
	echo "    <option value=''></option>\n";
	if ($contact_in_ping == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($contact_in_ping == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-contact_in_ping']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('gateway_channels')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "    ".$text['label-channels']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <input class='formfld' type='number' name='channels' maxlength='255' value=\"".escape($channels)."\" min='0' max='65535' step='1'>\n";
		echo "<br />\n";
		echo $text['description-channels']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-hostname']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='hostname' maxlength='255' value=\"".escape($hostname)."\">\n";
	echo "<br />\n";
	echo $text['description-hostname']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('gateway_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='domain_uuid'>\n";
		if (strlen($domain_uuid) == 0) {
			echo "    <option value='' selected='selected'>".$text['select-global']."</option>\n";
		}
		else {
			echo "    <option value=''>".$text['select-global']."</option>\n";
		}
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "    <option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
			}
			else {
				echo "    <option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
			}
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "	</table>\n";
	echo "	</div>";

	echo "</td>\n";
	echo "</tr>\n";
	//--- end: show_advanced -----------------------

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-context']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (strlen($context) == 0) { $context = "public"; }
	echo "	<input class='formfld' type='text' name='context' maxlength='255' value=\"".escape($context)."\">\n";
	echo "<br />\n";
	echo $text['description-context']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-profile']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='profile' required='required'>\n";
	foreach ($sip_profiles as $row) {
		$sip_profile_name = $row["sip_profile_name"];
		if ($profile == $sip_profile_name) {
			echo "	<option value='$sip_profile_name' selected='selected'>".escape($sip_profile_name)."</option>\n";
		}
		else {
			echo "	<option value='".escape($sip_profile_name)."'>".escape($sip_profile_name)."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-profile']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='enabled'>\n";
	if ($enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
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
	echo "	<input class='formfld' type='text' name='description' maxlength='255' value=\"".escape($description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='gateway_uuid' value='".escape($gateway_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//hide password fields before submit
	echo "<script>\n";
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
