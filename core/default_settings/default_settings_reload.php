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
 Portions created by the Initial Developer are Copyright (C) 2008-2023
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
if (permission_exists('default_setting_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//set the variables
$search = $_REQUEST['search'] ?? '';
$domain_uuid = $_GET['id'] ?? null;

//reload default settings
require "resources/classes/domains.php";
$domain = new domains();
$domain->set();

//add a message
message::add($text['message-settings_reloaded']);

//redirect the browser
if (is_uuid($domain_uuid)) {
	$location = PROJECT_PATH.'/core/domains/domain_edit.php?id='.$domain_uuid;
}
else {
	$search = preg_replace('#[^a-zA-Z0-9_\-\.]# ', '', $search);
	$location = 'default_settings.php'.($search != '' ? "?search=".$search : null);
}
header("Location: ".$location);

?>
