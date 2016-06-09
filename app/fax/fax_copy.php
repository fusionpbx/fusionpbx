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
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
if (permission_exists('fax_extension_add')) {
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
	if (isset($_REQUEST["id"])) {
		$fax_uuid = check_str($_REQUEST["id"]);
	}

//get the data
	$sql = "select * from v_fax ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and fax_uuid = '$fax_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (count($result) == 0) {
		echo "access denied";
		exit;
	}
	foreach ($result as &$row) {
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
		$fax_description = 'copy: '.$row["fax_description"];
	}
	unset ($prep_statement);

//copy the fax extension
	$fax_uuid = uuid();
	$dialplan_uuid = uuid();
	$sql = "insert into v_fax ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "fax_uuid, ";
	$sql .= "dialplan_uuid, ";
	$sql .= "fax_extension, ";
	$sql .= "fax_name, ";
	$sql .= "fax_email, ";
	$sql .= "fax_email_connection_type, ";
	$sql .= "fax_email_connection_host, ";
	$sql .= "fax_email_connection_port, ";
	$sql .= "fax_email_connection_security, ";
	$sql .= "fax_email_connection_validate, ";
	$sql .= "fax_email_connection_username, ";
	$sql .= "fax_email_connection_password, ";
	$sql .= "fax_email_connection_mailbox, ";
	$sql .= "fax_email_inbound_subject_tag, ";
	$sql .= "fax_email_outbound_subject_tag, ";
	$sql .= "fax_email_outbound_authorized_senders, ";
	$sql .= "fax_pin_number, ";
	$sql .= "fax_caller_id_name, ";
	$sql .= "fax_caller_id_number, ";
	if (strlen($fax_forward_number) > 0) {
		$sql .= "fax_forward_number, ";
	}
	$sql .= "fax_description ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'".$_SESSION['domain_uuid']."', ";
	$sql .= "'$fax_uuid', ";
	$sql .= "'$dialplan_uuid', ";
	$sql .= "'$fax_extension', ";
	$sql .= "'$fax_name', ";
	$sql .= "'$fax_email', ";
	$sql .= "'$fax_email_connection_type', ";
	$sql .= "'$fax_email_connection_host', ";
	$sql .= "'$fax_email_connection_port', ";
	$sql .= "'$fax_email_connection_security', ";
	$sql .= "'$fax_email_connection_validate', ";
	$sql .= "'$fax_email_connection_username', ";
	$sql .= "'$fax_email_connection_password', ";
	$sql .= "'$fax_email_connection_mailbox', ";
	$sql .= "'$fax_email_inbound_subject_tag', ";
	$sql .= "'$fax_email_outbound_subject_tag', ";
	$sql .= "'$fax_email_outbound_authorized_senders', ";
	$sql .= "'$fax_pin_number', ";
	$sql .= "'$fax_caller_id_name', ";
	$sql .= "'$fax_caller_id_number', ";
	if (strlen($fax_forward_number) > 0) {
		$sql .= "'$fax_forward_number', ";
	}
	$sql .= "'$fax_description' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);

//redirect the user
	$_SESSION["message"] = $text['confirm-copy'];
	header("Location: fax.php");
	return;

?>