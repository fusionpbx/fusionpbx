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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('fax_extension_copy')) {
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
	$fax_uuid = $_REQUEST["id"];

	if (is_uuid($fax_uuid)) {

		//get the data
			$sql = "select * from v_fax ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and fax_uuid = :fax_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$fax_extension = $row["fax_extension"];
				$fax_name = $row["fax_name"];
				$fax_email = $row["fax_email"];
				$fax_email_connection_type = $row["fax_email_connection_type"];
				$fax_email_connection_host = $row["fax_email_connection_host"];
				$fax_email_connection_port = $row["fax_email_connection_port"];
				$fax_email_connection_security = $row["fax_email_connection_security"];
				$fax_email_connection_validate = $row["fax_email_connection_validate"];
				$fax_email_connection_username = $row["fax_email_connection_username"];
				$fax_email_connection_password = $row["fax_email_connection_password"];
				$fax_email_connection_mailbox = $row["fax_email_connection_mailbox"];
				$fax_email_inbound_subject_tag = $row["fax_email_inbound_subject_tag"];
				$fax_email_outbound_subject_tag = $row["fax_email_outbound_subject_tag"];
				$fax_email_outbound_authorized_senders = $row["fax_email_outbound_authorized_senders"];
				$fax_pin_number = $row["fax_pin_number"];
				$fax_caller_id_name = $row["fax_caller_id_name"];
				$fax_caller_id_number = $row["fax_caller_id_number"];
				$fax_forward_number = $row["fax_forward_number"];
				$fax_description = $row["fax_description"].' ('.$text['label-copy'].')';
			}
			unset($sql, $parameters, $row);

		//build array
			$fax_uuid = uuid();
			$dialplan_uuid = uuid();
			$array['fax'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['fax'][0]['fax_uuid'] = $fax_uuid;
			$array['fax'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['fax'][0]['fax_extension'] = $fax_extension;
			$array['fax'][0]['fax_name'] = $fax_name;
			$array['fax'][0]['fax_email'] = $fax_email;
			$array['fax'][0]['fax_email_connection_type'] = $fax_email_connection_type;
			$array['fax'][0]['fax_email_connection_host'] = $fax_email_connection_host;
			$array['fax'][0]['fax_email_connection_port'] = $fax_email_connection_port;
			$array['fax'][0]['fax_email_connection_security'] = $fax_email_connection_security;
			$array['fax'][0]['fax_email_connection_validate'] = $fax_email_connection_validate;
			$array['fax'][0]['fax_email_connection_username'] = $fax_email_connection_username;
			$array['fax'][0]['fax_email_connection_password'] = $fax_email_connection_password;
			$array['fax'][0]['fax_email_connection_mailbox'] = $fax_email_connection_mailbox;
			$array['fax'][0]['fax_email_inbound_subject_tag'] = $fax_email_inbound_subject_tag;
			$array['fax'][0]['fax_email_outbound_subject_tag'] = $fax_email_outbound_subject_tag;
			$array['fax'][0]['fax_email_outbound_authorized_senders'] = $fax_email_outbound_authorized_senders;
			$array['fax'][0]['fax_pin_number'] = $fax_pin_number;
			$array['fax'][0]['fax_caller_id_name'] = $fax_caller_id_name;
			$array['fax'][0]['fax_caller_id_number'] = $fax_caller_id_number;
			if (strlen($fax_forward_number) > 0) {
				$array['fax'][0]['fax_forward_number'] = $fax_forward_number;
			}
			$array['fax'][0]['fax_description'] = $fax_description;

		//execute insert
			$p = new permissions;
			$p->add('fax_add', 'temp');

			$database = new database;
			$database->app_name = 'fax';
			$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
			$database->save($array);
			unset($array);

			$p->delete('fax_add', 'temp');

		//set message
			message::add($text['message-copy']);
	}

//redirect
	header("Location: fax.php");
	exit;

?>
