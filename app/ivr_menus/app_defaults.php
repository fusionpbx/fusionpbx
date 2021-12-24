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
	Portions created by the Initial Developer are Copyright (C) 2019 - 2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
if ($domains_processed == 1) {

	//select ivr menus with an empty context
	$sql = "select * from v_ivr_menus where ivr_menu_context is null ";
	$database = new database;
	$ivr_menus = $database->select($sql, null, 'all');
	unset($sql);

	if (is_array($ivr_menus)) {

		//get the domain list
		$sql = "select * from v_domains ";
		$domains = $database->select($sql, null, 'all');
		unset($sql);

		//update the ivr menu context
		$x = 0;
		foreach ($ivr_menus as $row) {
			foreach ($domains as $domain) {
				if ($row['domain_uuid'] == $domain['domain_uuid']) {
					$array['ivr_menus'][$x]['ivr_menu_uuid'] = $row['ivr_menu_uuid'];
					$array['ivr_menus'][$x]['ivr_menu_context'] = $domain['domain_name'];
					$x++;
				}
			}
		}
		if (is_array($array) && @sizeof($array) != 0) {

			$p = new permissions;
			$p->add('ivr_menu_edit', 'temp');

			$database = new database;
			$database->app_name = 'ivr_menus';
			$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
			$database->save($array, false);
			unset($array);
	
			$p->delete('ivr_menu_edit', 'temp');
		}
	}

	//use the ivr_menu_language to update the language dialect and voice
	$sql = "update v_ivr_menus set ";
	if ($db_type == 'pgsql') {
		$sql .= "ivr_menu_language = split_part(ivr_menu_language, '/', 1), ";
		$sql .= "ivr_menu_dialect = split_part(ivr_menu_language, '/', 2),  ";
		$sql .= "ivr_menu_voice = split_part(ivr_menu_language, '/', 3) ";
	}
	elseif ($db_type == 'mysql') {
		$sql .= "ivr_menu_language = SUBSTRING_INDEX(SUBSTRING_INDEX(ivr_menu_language, '/', 1), '/', -1), ";
		$sql .= "ivr_menu_dialect = SUBSTRING_INDEX(SUBSTRING_INDEX(ivr_menu_language, '/', 2), '/', -1),  ";
		$sql .= "ivr_menu_voice = SUBSTRING_INDEX(SUBSTRING_INDEX(ivr_menu_language, '/', 3), '/', -1) ";
	}
	$sql .= "where ivr_menu_language like '%/%/%'; ";
	$database = new database;
	$ivr_menus = $database->select($sql, null, 'all');
	unset($sql);

}

?>
