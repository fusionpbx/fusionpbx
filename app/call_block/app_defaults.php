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

if ($domains_processed == 1) {

	//create a view for call block
		$database = new database;
		$database->execute("DROP VIEW view_call_block;", null);
		$sql = "CREATE VIEW view_call_block AS ( \n";
		$sql .= "	select c.domain_uuid, call_block_uuid, c.call_block_direction, c.extension_uuid, c.call_block_name, c.call_block_country_code, \n";
		$sql .= "	c.call_block_number, e.extension, e.number_alias, c.call_block_count, c.call_block_app, c.call_block_data, c.date_added, c.call_block_enabled, c.call_block_description \n";
		$sql .= "	from v_call_block as c \n";
		$sql .= " left join v_extensions as e \n";
		$sql .= "	on c.extension_uuid = e.extension_uuid \n";
		$sql .= "); \n";
		$database = new database;
		$database->execute($sql, null);
		unset($sql);

	//set call blocks to inbound if no direction defined
		$sql = "update v_call_block set call_block_direction = 'inbound' where call_block_direction is null ";
		$database = new database;
		$database->execute($sql, null);
		unset($sql);

}

?>
