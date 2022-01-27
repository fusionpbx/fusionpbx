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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the extensions dir doesn't exist then create it
	if ($domains_processed == 1) {

		//create the directory
			if (strlen($_SESSION['switch']['extensions']['dir']) > 0) {
				if (!is_dir($_SESSION['switch']['extensions']['dir'])) {
					mkdir($_SESSION['switch']['extensions']['dir'], 0770, false);
				}
			}

		//update the directory first and last names
			$sql = "select * from v_extensions ";
			$sql .= "where directory_first_name <> '' ";
			$sql .= "and directory_last_name is null ";
			$database = new database;
			$extensions = $database->select($sql, null, 'all');
			unset($sql);
			if (is_array($extensions) && @sizeof($extensions) != 0) {
				foreach($extensions as $index => $row) {
					$name = explode(' ', $row['directory_first_name']);
					if (strlen($name[1]) > 0) {
						$array['extensions'][$index]['extension_uuid'] = $row['extension_uuid'];
						$array['extensions'][$index]['directory_first_name'] = $name[0];
						$array['extensions'][$index]['directory_last_name'] = $name[1];
					}
				}
				if (is_array($array) && @sizeof($array) != 0) {
					$p = new permissions;
					$p->add('extension_edit', 'temp');

					$database = new database;
					$database->app_name = 'extensions';
					$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
					$database->save($array, false);
					unset($array);

					$p->delete('extension_edit', 'temp');
				}
			}
			unset($extensions, $row);

		//change category security to extension
			$sql = "update v_default_settings ";
			$sql .= "set default_setting_category = 'extension' ";
			$sql .= "where default_setting_category = 'security' ";
			$sql .= "and default_setting_subcategory like 'password_%' ";

			$p = new permissions;
			$p->add('default_setting_edit', 'temp');

			$database = new database;
			$database->execute($sql);
			unset($sql);

			$p->delete('default_setting_edit', 'temp');

		//create natural sort function (source: http://www.rhodiumtoad.org.uk/junk/naturalsort.sql)
			if ($db_type == 'pgsql') {
				$sql = "create or replace function natural_sort(text)\n";
				$sql .= "	returns bytea language sql immutable strict as \$f\$\n";
				$sql .= "	select string_agg(convert_to(coalesce(r[2], length(length(r[1])::text) || length(r[1])::text || r[1]), 'UTF8'),'\\x00')\n";
				$sql .= "	from regexp_matches(\$1, '0*([0-9]+)|([^0-9]+)', 'g') r;\n";
				$sql .= "\$f\$;";
				$database = new database;
				$database->execute($sql);
				unset($sql);
			}
		
		//do not disturb no longer uses the extension dial_string set the value to null
			$sql = "update v_extensions set dial_string = null where (dial_string = '!USER_BUSY' or dial_string = 'error/user_busy');\n";
			$database->execute($sql);
			unset($sql);

	}

?>
