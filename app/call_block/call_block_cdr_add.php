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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>

	Call Block is written by Gerrit Visser <gerrit308@gmail.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('call_block_edit') && !permission_exists('call_block_add')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add from cdr
	if (is_uuid($_REQUEST["cdr_id"])) {

		$action = "cdr_add";
		$xml_cdr_uuid = $_REQUEST["cdr_id"];
		$call_block_name = $_REQUEST["name"];

		// get the caller id info from cdr the user chose
			$sql = "select caller_id_name, caller_id_number ";
			$sql .= "from v_xml_cdr ";
			$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
			$parameters['xml_cdr_uuid'] = $xml_cdr_uuid;
			$database = new database;
			$result = $database->select($sql, $parameters, 'row');
			unset ($sql, $parameters);

		//create data array
			$array['call_block'][0]['call_block_uuid'] = uuid();
			$array['call_block'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['call_block'][0]['call_block_name'] = $call_block_name == '' ? $result["caller_id_name"] : $call_block_name;
			$array['call_block'][0]['call_block_number'] = $result["caller_id_number"];
			$array['call_block'][0]['call_block_count'] = 0;
			$array['call_block'][0]['call_block_action'] = 'Reject';
			$array['call_block'][0]['call_block_enabled'] = 'true';
			$array['call_block'][0]['date_added'] = time();

		//ensure call block is enabled in the dialplan
			if ($action == "add" || $action == "update") {
				$sql = "select dialplan_uuid from v_dialplans where true ";
				$sql .= "and domain_uuid = :domain_uuid ";
				$sql .= "and app_uuid = 'b1b31930-d0ee-4395-a891-04df94599f1f' ";
				$sql .= "and dialplan_enabled <> 'true' ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$database = new database;
				$rows = $database->select($sql, $parameters);

				if (is_array($rows) && sizeof($rows) != 0) {
					foreach ($rows as $index => $row) {
						$array['dialplans'][$index]['dialplan_uuid'] = $row['dialplan_uuid'];
						$array['dialplans'][$index]['dialplan_enabled'] = 'true';
					}

					$p = new permissions;
					$p->add('dialplan_edit', 'temp');

					$database = new database;
					$database->save($array);
					unset($array);

					$p->delete('dialplan_edit', 'temp');
				}
			}

		//insert call block record
			$database = new database;
			$database->app_name = 'call_block';
			$database->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
			$database->save($array);
			$response = $database->message;
			unset($array);

		//add a message
			message::add($text['label-add-complete']);
	}

//redirect the browser
	header("Location: call_block.php");

?>
