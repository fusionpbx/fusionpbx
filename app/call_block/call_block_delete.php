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

	Call Block is written by Gerrit Visser <gerrit308@gmail.com>
*/
//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('call_block_delete')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the extension
	if (is_uuid($_GET["id"])) {
		$call_block_uuid = $_GET["id"];

		//read the call_block_number
			$sql = "select c.call_block_number, d.domain_name ";
			$sql .= "from v_call_block as c ";
			$sql .= "join v_domains as d on c.domain_uuid = d.domain_uuid ";
			$sql .= "where c.domain_uuid = :domain_uuid ";
			$sql .= "and c.call_block_uuid = :call_block_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['call_block_uuid'] = $call_block_uuid;
			$database = new database;
			$result = $database->select($sql, $parameters, 'row');

			if (is_array($result) && sizeof($result) != 0) {
				$call_block_number = $result["call_block_number"];
				$domain_name = $result["domain_name"];

				//clear the cache
				$cache = new cache;
				$cache->delete("app:call_block:".$domain_name.":".$call_block_number);
			}

			unset($sql, $parameters, $result);

		//delete the call block
			$array['call_block'][0]['call_block_uuid'] = $call_block_uuid;
			$array['call_block'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

			$database = new database;
			$database->app_name = 'call_block';
			$database->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
			$database->delete($array);
			$response = $database->message;
			unset($array);

		//message
			message::add($text['label-delete-complete']);
	}

	//redirect the browser
		header("Location: call_block.php");
		return;

?>
