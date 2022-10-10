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
	if (permission_exists('extension_copy')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the http get/post variable(s) to a php variable
	if (is_uuid($_REQUEST["id"]) && $_REQUEST["ext"] != '') {
		$extension_uuid = $_REQUEST["id"];
		$extension_new = $_REQUEST["ext"];
		if (!is_numeric($extension_new)) {
			$number_alias_new = $_REQUEST["alias"];
		}
		$page = $_REQUEST['page'];
	}

// skip the copy if the domain extension already exists
	$extension = new extension;
	if ($extension->exists($_SESSION['domain_uuid'], $extension_new)) {
		message::add($text['message-duplicate'], 'negative');
		header("Location: extensions.php".(is_numeric($page) ? '?page='.$page : null));
		exit;
	}

//get the extension data
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and extension_uuid = :extension_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['extension_uuid'] = $extension_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		$extension = $row["extension"];
		$number_alias = $row["number_alias"];
		$accountcode = $row["accountcode"];
		$effective_caller_id_name = $row["effective_caller_id_name"];
		$effective_caller_id_number = $row["effective_caller_id_number"];
		$outbound_caller_id_name = $row["outbound_caller_id_name"];
		$outbound_caller_id_number = $row["outbound_caller_id_number"];
		$emergency_caller_id_name = $row["emergency_caller_id_name"];
		$emergency_caller_id_number = $row["emergency_caller_id_number"];
		$directory_visible = $row["directory_visible"];
		$directory_exten_visible = $row["directory_exten_visible"];
		$limit_max = $row["limit_max"];
		$limit_destination = $row["limit_destination"];
		$user_context = $row["user_context"];
		$missed_call_app = $row["missed_call_app"];
		$missed_call_data = $row["missed_call_data"];
		$toll_allow = $row["toll_allow"];
		$call_timeout = $row["call_timeout"];
		$call_group = $row["call_group"];
		$user_record = $row["user_record"];
		$hold_music = $row["hold_music"];
		$auth_acl = $row["auth_acl"];
		$cidr = $row["cidr"];
		$sip_force_contact = $row["sip_force_contact"];
		$nibble_account = $row["nibble_account"];
		$sip_force_expires = $row["sip_force_expires"];
		$mwi_account = $row["mwi_account"];
		$sip_bypass_media = $row["sip_bypass_media"];
		$dial_string = $row["dial_string"];
		$enabled = $row["enabled"];
		$description = $row["description"].' ('.$text['button-copy'].')';
	}
	unset($sql, $parameters, $row);

//copy the extension
	$array['extensions'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
	$array['extensions'][0]['extension_uuid'] = uuid();
	$array['extensions'][0]['extension'] = $extension_new;
	$array['extensions'][0]['number_alias'] = $number_alias_new;
	$array['extensions'][0]['password'] = generate_password();
	$array['extensions'][0]['accountcode'] = $password;
	$array['extensions'][0]['effective_caller_id_name'] = $effective_caller_id_name;
	$array['extensions'][0]['effective_caller_id_number'] = $effective_caller_id_number;
	$array['extensions'][0]['outbound_caller_id_name'] = $outbound_caller_id_name;
	$array['extensions'][0]['outbound_caller_id_number'] = $outbound_caller_id_number;
	$array['extensions'][0]['emergency_caller_id_name'] = $emergency_caller_id_name;
	$array['extensions'][0]['emergency_caller_id_number'] = $emergency_caller_id_number;
	$array['extensions'][0]['directory_visible'] = $directory_visible;
	$array['extensions'][0]['directory_exten_visible'] = $directory_exten_visible;
	$array['extensions'][0]['limit_max'] = $limit_max;
	$array['extensions'][0]['limit_destination'] = $limit_destination;
	$array['extensions'][0]['user_context'] = $user_context;
	$array['extensions'][0]['missed_call_app'] = $missed_call_app;
	$array['extensions'][0]['missed_call_data'] = $missed_call_data;
	$array['extensions'][0]['toll_allow'] = $toll_allow;
	$array['extensions'][0]['call_timeout'] = $call_timeout;
	$array['extensions'][0]['call_group'] = $call_group;
	$array['extensions'][0]['user_record'] = $user_record;
	$array['extensions'][0]['hold_music'] = $hold_music;
	$array['extensions'][0]['auth_acl'] = $auth_acl;
	$array['extensions'][0]['cidr'] = $cidr;
	$array['extensions'][0]['sip_force_contact'] = $sip_force_contact;
	$array['extensions'][0]['nibble_account'] = $nibble_account;
	$array['extensions'][0]['sip_force_expires'] = $sip_force_expires;
	$array['extensions'][0]['mwi_account'] = $mwi_account;
	$array['extensions'][0]['sip_bypass_media'] = $sip_bypass_media;
	$array['extensions'][0]['dial_string'] = $dial_string;
	$array['extensions'][0]['enabled'] = $enabled;
	$array['extensions'][0]['description'] = $description;
	$database = new database;
	$database->save($array);
	$message = $database->message;
	unset($array);

//get the source extension voicemail data
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/voicemails')) {

		//get the voicemails
			$sql = "select * from v_voicemails ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and voicemail_id = :voicemail_id ";
			$parameters['voicemail_id'] = is_numeric($number_alias) ? $number_alias : $extension;
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$voicemail_mailto = $row["voicemail_mail_to"];
				$voicemail_file = $row["voicemail_file"];
				$voicemail_local_after_email = $row["voicemail_local_after_email"];
				$voicemail_enabled = $row["voicemail_enabled"];
			}
			unset($sql, $parameters, $row);

		//set the new voicemail password
			if (strlen($voicemail_password) == 0) {
				$voicemail_password = generate_password(9, 1);
			}

		//add voicemail via class
			$ext = new extension;
			$ext->db = $db;
			$ext->domain_uuid = $domain_uuid;
			$ext->extension = $extension_new;
			$ext->number_alias = $number_alias_new;
			$ext->voicemail_password = $voicemail_password;
			$ext->voicemail_mail_to = $voicemail_mailto;
			$ext->voicemail_file = $voicemail_file;
			$ext->voicemail_local_after_email = $voicemail_local_after_email;
			$ext->voicemail_enabled = $voicemail_enabled;
			$ext->description = $description;
			$ext->voicemail();
			unset($ext);

	}

//synchronize configuration
	if (is_writable($_SESSION['switch']['extensions']['dir'])) {
		require_once "app/extensions/resources/classes/extension.php";
		$ext = new extension;
		$ext->xml();
		unset($ext);
	}

//redirect the user
	message::add($text['message-copy']);
	header("Location: extensions.php".(is_numeric($page) ? '?page='.$page : null));
	exit;

?>