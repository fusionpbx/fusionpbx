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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//add theme settings default settings
		$sql = "select count(*) as num_rows from v_default_settings ";
		$sql .= "where default_setting_category = 'theme' ";
		$sql .= "and default_setting_subcategory = 'background_images' ";
		$sql .= "and default_setting_name = 'var' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			unset($prep_statement);
			if ($row['num_rows'] == 0) {
				$x = 0;
				$array[$x]['default_setting_category'] = 'theme';
				$array[$x]['default_setting_subcategory'] = 'background_images';
				$array[$x]['default_setting_name'] = 'var';
				$array[$x]['default_setting_value'] = 'true';
				$array[$x]['default_setting_enabled'] = 'false';
				$array[$x]['default_setting_description'] = 'Enable background images in the selected template (where available).';
				$x++;
				$orm = new orm;
				$orm->name('default_settings');
				$orm->save($array[0]);
				$message = $orm->message;
				//print_r($message);
			}
			unset($row);
		}
}

?>
