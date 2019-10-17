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
	require_once "resources/functions/save_phrases_xml.php";

//check permissions
	if (permission_exists('phrase_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get values
	$phrase_detail_uuid = $_GET["pdid"];
	$phrase_uuid = $_GET["pid"];
	$phrase_language = $_GET["lang"];

//delete the detail entry
	if (is_uuid($phrase_detail_uuid) && is_uuid($phrase_uuid)) {
		//build array
			$array['phrase_details'][0]['phrase_detail_uuid'] = $phrase_detail_uuid;
			$array['phrase_details'][0]['phrase_uuid'] = $phrase_uuid;
			$array['phrase_details'][0]['domain_uuid'] = $domain_uuid;

		//grant temporary permissions
			$p = new permissions;
			$p->add('phrase_detail_delete', 'temp');

		//execute delete
			$database = new database;
			$database->app_name = 'phrases';
			$database->app_uuid = '5c6f597c-9b78-11e4-89d3-123b93f75cba';
			$database->delete($array);
			unset($array);

		//revoke temporary permissions
			$p->delete('phrase_detail_delete', 'temp');

		//save the xml to the file system if the phrase directory is set
			save_phrases_xml();

		//clear the cache
			$cache = new cache;
			$cache->delete("languages:".$phrase_language);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header('Location: phrase_edit.php?id='.$phrase_uuid);
	exit;

?>
