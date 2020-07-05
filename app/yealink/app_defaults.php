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
	Portions created by the Initial Developer are Copyright (C) 2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
	if ($domains_processed == 1) {
		
		//correct yealink_codec_opus_priority values
		if ($db_type == 'pgsql') {
			$sql = "select pg_typeof(default_setting_enabled) from v_default_settings limit 1;";
			$database = new database;
			$enabled_type = $database->select($sql, null, 'column');
			if ($enabled_type == 'text') {
				$sql = "update v_default_settings set default_setting_enabled = 'true', default_setting_value = '13'  ";
				$sql .= "where default_setting_uuid = 'a018c028-0f99-4ef8-869d-f5322636ae36' and default_setting_enabled = '13'; ";
				$database = new database;
				$database->execute($sql, null);
				unset($sql);
			}
		}

	}
     
