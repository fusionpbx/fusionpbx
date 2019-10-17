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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_extension_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get value and set it as a php variable
	$fax_uuid = $_GET["id"];

//delete the fax extension
	if (is_uuid($fax_uuid)) {

		//get the dialplan uuid
			$sql = "select dialplan_uuid from v_fax ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and fax_uuid = :fax_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
			$database = new database;
			$dialplan_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//delete the fax entry
			$array['fax'][0]['fax_uuid'] = $fax_uuid;
			$array['fax'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

		if (is_uuid($dialplan_uuid)) {
			//delete the dialplan entry
				$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplans'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

			//delete the dialplan details
				$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplan_details'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		}

		//grant temp permissions
			$p = new permissions;
			$p->add('fax_delete', 'temp');
			$p->add('dialplan_delete', 'temp');
			$p->add('dialplan_detail_delete', 'temp');

		//execute delete
			$database = new database;
			$database->app_name = 'fax';
			$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
			$database->delete($array);
			unset($array);

		//revoke temp permissions
			$p->delete('fax_delete', 'temp');
			$p->delete('dialplan_delete', 'temp');
			$p->delete('dialplan_detail_delete', 'temp');

		//syncrhonize configuration
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header("Location: fax.php");
	return;

?>