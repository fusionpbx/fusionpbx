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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
	if ($domains_processed == 1) {

		//domain settings - change the type from var to text
			$sql = "update v_domain_settings ";
			$sql .= "set domain_setting_name = 'text' ";
			$sql .= "where domain_setting_name = 'var' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//update any domains set to legacy languages
			$language = new text;
			foreach ($language->legacy_map as $language_code => $legacy_code) {
				if(strlen($legacy_code) == 5)
					continue;
				$sql = "update v_domain_settings set domain_setting_value = '$language_code' where domain_setting_value = '$legacy_code' and domain_setting_name = 'code' and domain_setting_subcategory = 'language' and domain_setting_category = 'domain'";
				$db->exec(check_sql($sql));
				unset($sql);
			}

		//migrate old domain_settings
			$sql = "update v_domain_settings ";
			$sql .= "set domain_setting_value = '#fafafa' ";
			$sql .= "where domain_setting_subcategory = 'message_default_color' ";
			$sql .= "and domain_setting_value = '#ccffcc' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
			}
			$sql = "update v_domain_settings ";
			$sql .= "set domain_setting_value = '#666' ";
			$sql .= "where domain_setting_subcategory = 'message_default_background_color' ";
			$sql .= "and domain_setting_value = '#004200' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
			}
			unset($prep_statement, $sql);
	}

?>
