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

//check for login return preference
	if ($_SESSION["user_uuid"] != '') {
		if (isset($_SESSION['login']['destination_last']) && ($_SESSION['login']['destination_last']['boolean'] == 'true')) {
			if ($_SERVER['HTTP_REFERER'] != '') {
				//convert to relative path
					$referrer = substr($_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], $_SERVER["HTTP_HOST"]) + strlen($_SERVER["HTTP_HOST"]));
				//check if destination url already exists
					$sql = "select count(*) as num_rows from v_user_settings ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and user_uuid = '".$_SESSION["user_uuid"]."' ";
					$sql .= "and user_setting_category = 'login' ";
					$sql .= "and user_setting_subcategory = 'destination' ";
					$sql .= "and user_setting_name = 'url' ";
					$prep_statement = $db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						$exists = ($row['num_rows'] > 0) ? true : false;
					}
					unset($sql, $prep_statement, $row);

				//if exists, update
					if ($exists) {
						$sql = "update v_user_settings set ";
						$sql .= "user_setting_value = '".$referrer."', ";
						$sql .= "user_setting_enabled = 'true' ";
						$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
						$sql .= "and user_uuid = '".$_SESSION["user_uuid"]."' ";
						$sql .= "and user_setting_category = 'login' ";
						$sql .= "and user_setting_subcategory = 'destination' ";
						$sql .= "and user_setting_name = 'url' ";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				//otherwise, insert
					else {
						$sql = "insert into v_user_settings ";
						$sql .= "( ";
						$sql .= "user_setting_uuid, ";
						$sql .= "domain_uuid, ";
						$sql .= "user_uuid, ";
						$sql .= "user_setting_category, ";
						$sql .= "user_setting_subcategory, ";
						$sql .= "user_setting_name, ";
						$sql .= "user_setting_value, ";
						$sql .= "user_setting_enabled ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "( ";
						$sql .= "'".uuid()."', ";
						$sql .= "'".$_SESSION['domain_uuid']."', ";
						$sql .= "'".$_SESSION["user_uuid"]."', ";
						$sql .= "'login', ";
						$sql .= "'destination', ";
						$sql .= "'url', ";
						$sql .= "'".$referrer."', ";
						$sql .= "'true' ";
						$sql .= ") ";
						$db->exec(check_sql($sql));
						unset($sql);
					}
			}
		}
	}

//redirect the user to the index page
	header("Location: ".PROJECT_PATH."/login.php");
	return;

?>