<?php

if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_logo';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Path to image/logo file displayed in the header of the cover sheet.';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_disclaimer';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = "The information contained in this facsimile message is intended for the sole confidential use of the designated recipient(s) and may contain confidential information. If you have received this information in error, any review, dissemination, distribution or copying of this information is strictly prohibited. Please notify us immediately and return the original message to us by mail or, if electronic, reroute back to the sender.";
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Disclaimer displayed in the footer of the cover sheet.';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_contact_info';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Contact information displayed below the logo in the cover sheet header.';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'page_size';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'auto';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the default page size of new faxes.';
		$x++;

	//iterate and add each, if necessary
		foreach ($array as $index => $default_settings) {

		//add theme default settings
			$sql = "select count(*) as num_rows from v_default_settings ";
			$sql .= "where default_setting_category = 'fax' ";
			$sql .= "and default_setting_subcategory = '".$default_settings['default_setting_subcategory']."' ";
			$sql .= "and default_setting_name = '".$default_settings['default_setting_name']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				unset($prep_statement);
				if ($row['num_rows'] == 0) {
					$orm = new orm;
					$orm->name('default_settings');
					$orm->save($array[$index]);
					$message = $orm->message;
					//print_r($message);
				}
				unset($row);
			}

		}

}

?>