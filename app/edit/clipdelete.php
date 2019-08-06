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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('script_editor_save')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the uuid from http values
	$clip_uuid = $_GET["id"];

//delete the clip
	if (is_uuid($clip_uuid)) {
		$array['clips'][0]['clip_uuid'] = $clip_uuid;

		$p = new permissions;
		$p->add('clip_delete', 'temp');

		$database = new database;
		$database->app_name = 'edit';
		$database->app_uuid = '17e628ee-ccfa-49c0-29ca-9894a0384b9b';
		$database->delete($array);
		unset($array);

		$p->delete('clip_delete', 'temp');
	}

//redirect the browser
	header("Location: clipoptions.php");

?>