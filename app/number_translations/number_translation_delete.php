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
	Portions created by the Initial Developer are Copyright (C) 2017
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

	//delete the data
	if (is_array($_REQUEST['number_translations']) && @sizeof($_REQUEST['number_translations']) != 0 && permission_exists('number_translation_delete')) {
		//get the ids, build array
			foreach ($_REQUEST['number_translations'] as $index => $number_translation_uuid) {
				if (is_uuid($number_translation_uuid)) {
					//delete the child data
						$array['number_translation_details'][$index]['number_translation_uuid'] = $number_translation_uuid;
					//delete number_translation
						$array['number_translations'][$index]['number_translation_uuid'] = $number_translation_uuid;
				}
			}

		if (is_array($array) && @sizeof($array) != 0) {
			//execute
				$database = new database;
				$database->app_name = 'number_translations';
				$database->app_uuid = '6ad54de6-4909-11e7-a919-92ebcb67fe33';
				$database->delete($array);
				unset($array);

			//delete the message
				message::add($text['message-delete']);
		}

		//redirect the user
			header('Location: number_translations.php');
			exit;
	}

//delete the child data
	if (is_uuid($_REQUEST["number_translation_detail_uuid"]) && permission_exists('number_translation_detail_delete')) {
		//select from v_number_translation_details
			$sql = "select number_translation_uuid from v_number_translation_details ";
			$sql .= "where number_translation_detail_uuid = :number_translation_detail_uuid ";
			$parameters['number_translation_detail_uuid'] = $_REQUEST["number_translation_detail_uuid"];
			$database = new database;
			$number_translation_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//delete the row
			$array['number_translation_details'][0]['number_translation_detail_uuid'] = $_REQUEST["number_translation_detail_uuid"];

		//execute
			$database = new database;
			$database->app_name = 'number_translations';
			$database->app_uuid = '6ad54de6-4909-11e7-a919-92ebcb67fe33';
			$database->delete($array);
			unset($array);

		//delete the message
			message::add($text['message-delete']);

		//redirect the user
			header('Location: number_translation_edit.php?id='.$number_translation_uuid);
			exit;
	}

//default redirect
	header('Location: number_translations.php');
	exit;

?>
