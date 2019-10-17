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
if (permission_exists('module_delete')) {
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
	$module_uuid = $_GET["id"];

if (is_uuid($module_uuid)) {

	//delete module
		$array['modules'][0]['module_uuid'] = $module_uuid;

		$database = new database;
		$database->app_name = 'modules';
		$database->app_uuid = '5eb9cba1-8cb6-5d21-e36a-775475f16b5e';
		$database->delete($array);
		unset($array);

	//set message
		message::add($text['message-delete']);

}

//redirect
	header("Location: modules.php");
	exit;

?>