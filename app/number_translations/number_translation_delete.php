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

//delete the message
	messages::add($text['message-delete']);

//delete the data
	if (isset($_GET["id"]) && is_uuid($_GET["id"]) && permission_exists('number_translation_delete')) {

		//get the id
			$id = check_str($_GET["id"]);

		//delete the child data
			$sql = "delete from v_number_translation_details ";
			$sql .= "where number_translation_uuid = '".$id."' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();

		//delete number_translation
			$sql = "delete from v_number_translations ";
			$sql .= "where number_translation_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);

		//redirect the user
			header('Location: number_translations.php');
	}

//delete the child data
	if (isset($_REQUEST["number_translation_detail_uuid"]) && is_uuid($_REQUEST["number_translation_detail_uuid"]) && permission_exists('number_translation_detail_delete')) {
		//select from v_number_translation_details
			$sql = "select * from v_number_translation_details ";
			$sql .= "where number_translation_detail_uuid = '".$_REQUEST["number_translation_detail_uuid"]."' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$number_translation_uuid = $row["number_translation_uuid"];
			}
			unset ($prep_statement, $result);

		//delete the row
			$sql = "delete from v_number_translation_details ";
			$sql .= "where number_translation_detail_uuid = '".$_REQUEST["number_translation_detail_uuid"]."' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();

		//redirect the user
			header('Location: number_translation_edit.php?id='.$number_translation_uuid);
	}

?>
