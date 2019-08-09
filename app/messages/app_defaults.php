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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this code online once
if ($domains_processed == 1) {

	//update default settings
	$sql = "update v_default_settings set ";
	$sql .= "default_setting_name = 'text' ";
	$sql .= "where default_setting_category = 'message' ";
	$sql .= "and default_setting_subcategory = 'http_auth_password' ";
	$sql .= "and default_setting_name = 'array' ";
	$database = new database;
	$database->execute($sql);
	unset($sql);

	//update domain settings
	$sql = "update v_domain_settings set ";
	$sql .= "domain_setting_name = 'text' ";
	$sql .= "where domain_setting_category = 'message' ";
	$sql .= "and domain_setting_subcategory = 'http_auth_password' ";
	$sql .= "and domain_setting_name = 'array' ";
	$database = new database;
	$database->execute($sql);
	unset($sql);

}

?>
