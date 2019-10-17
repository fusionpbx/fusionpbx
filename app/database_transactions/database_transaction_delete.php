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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('database_transaction_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$database_transaction_uuid = $_GET["id"];

//delete transaction
	if (is_uuid($database_transaction_uuid)) {
		$array['database_transactions'][0]['database_transaction_uuid'] = $database_transaction_uuid;
		$array['database_transactions'][0]['domain_uuid'] = $domain_uuid;

		$database = new database;
		$database->app_name = 'database_transactions';
		$database->app_uuid = 'de47783c-1caa-4b3e-9b51-ad6c9e69215c';
		$database->delete($array);
		unset($array);

		message::add($text['message-delete']);
	}

//redirect
	header('Location: database_transactions.php');

?>