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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (!permission_exists('number_translation_delete')) {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the number_translation uuid
	$number_translation_uuids = $_REQUEST["id"];
	$app_uuid = check_str($_REQUEST['app_uuid']);

//delete the number_translations
	if (sizeof($number_translation_uuids) > 0) {

		//get number_translation contexts
			foreach ($number_translation_uuids as $number_translation_uuid) {
				//check each
					$number_translation_uuid = check_str($number_translation_uuid);

				//get the number_translation data
					$sql = "select * from v_number_translations ";
					$sql .= "where number_translation_uuid = '".$number_translation_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						$database_number_translation_uuid = $row["number_translation_uuid"];
						$number_translation_contexts[] = $row["number_translation_context"];
					}
					unset($prep_statement);
			}

		//start the atomic transaction
			$db->beginTransaction();

		//delete number_translation and details
			$number_translations_deleted = 0;
			foreach ($number_translation_uuids as $number_translation_uuid) {

				//delete child data
					$sql = "delete from v_number_translation_details ";
					$sql .= "where number_translation_uuid = '".$number_translation_uuid."'; ";
					$db->query($sql);
					unset($sql);

				//delete parent data
					$sql = "delete from v_number_translations ";
					$sql .= "where number_translation_uuid = '".$number_translation_uuid."'; ";
					$db->query($sql);
					unset($sql);

				$number_translations_deleted++;
			}

		//commit the atomic transaction
			$db->commit();

		//update the number_translation xml
			$number_translations = new number_translation;
			$number_translations->xml();

	}

//redirect the browser
	messages::add($text['message-delete'].(($number_translations_deleted > 1) ? ": ".$number_translations_deleted : null));
	header("Location: ".PROJECT_PATH."/app/number_translation/number_translations.php".(($app_uuid != '') ? "?app_uuid=".$app_uuid : null));

?>