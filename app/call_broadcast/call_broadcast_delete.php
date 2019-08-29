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
require "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_broadcast_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the call broadcast entry
	if (is_uuid($_GET["id"])) {
		$call_broadcast_uuid = $_GET['id'];
		$array['call_broadcasts'][0]['call_broadcast_uuid'] = $call_broadcast_uuid;
		$array['call_broadcasts'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

		$database = new database;
		$database->app_name = 'call_broadcasts';
		$database->app_uuid = 'efc11f6b-ed73-9955-4d4d-3a1bed75a056';
		$database->delete($array);
		unset($array);

		message::add($text['message-delete']);
	}

header("Location: call_broadcast.php");
return;

?>