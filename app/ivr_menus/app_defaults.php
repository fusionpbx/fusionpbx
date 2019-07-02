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
	Portions created by the Initial Developer are Copyright (C) 2019
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
	if (is_array($ivr_menus)) {

		//get the domain list
		$sql = "select * from v_domains ";
		$domains = $database->select($sql, null, 'all');

		//update the ivr menu context
		foreach ($ivr_menus as $row) {
			foreach ($domains as $domain) {
				if ($row['domain_uuid'] == $domain['domain_uuid']) {
					$sql = "update v_ivr_menus set ivr_menu_context = :domain_name \n";
					$sql .= "where ivr_menu_uuid = :ivr_menu_uuid \n";
					$parameters['domain_name'] = $domain['domain_name'];
					$parameters['ivr_menu_uuid'] = $row['ivr_menu_uuid'];
					$database->execute($sql, $parameters);
					unset($parameters);
				}
			 }
		}
	}

}

?>
