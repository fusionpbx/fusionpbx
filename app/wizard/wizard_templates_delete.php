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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('wizard_template_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//check for the ids
	if (is_array($_REQUEST) && sizeof($_REQUEST) > 0) {

		$wizard_templates_uuids = $_REQUEST["id"];
		foreach($wizard_templates_uuids as $wizard_template_uuid) {
			$wizard_template_uuid = check_str($wizard_template_uuid);
			if ($wizard_template_uuid != '') {
				//get the extension templates array
					$sql = "select * from v_wizard_templates ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and wizard_template_uuid = '".$wizard_template_uuid."' ";
					$database = new database;
					$database->select($sql);
					$wizard_templates = $database->result;
					if (is_array($wizard_templates)) { 
						foreach ($wizard_templates as &$row) {
							$wizard_template_name = $row["wizard_template_name"];
							//$user_context = $row["user_context"];
						}
						unset ($prep_statement);
					}

				//delete the extension
					$sql = "delete from v_wizard_templates ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and wizard_template_uuid = '".$wizard_template_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);

			}
		}

	}

//redirect the browser
	$_SESSION["message"] = $text['message-delete'];
	header("Location: wizard_templates.php");
	return;

?>
