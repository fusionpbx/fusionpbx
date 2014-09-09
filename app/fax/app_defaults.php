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
		$array[$x]['default_setting_subcategory'] = 'cover_footer';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = "The information contained in this facsimile is intended for the sole confidential use of the recipient(s) designated above, and may contain confidential and legally privileged information. If you are not the intended recipient, you are hereby notified that the review, disclosure, dissemination, distribution, copying, duplication in any form, and taking of any action in regards to the contents of this document - except with respect to its direct delivery to the intended recipient - is strictly prohibited.  Please notify the sender immediately and destroy this cover sheet and all attachments.  If stored or viewed electronically, please permanently delete it from your system.";
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Notice displayed in the footer of the cover sheet.';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_header';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Default information displayed beneath the logo in the header of the cover sheet.';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'page_size';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'letter';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the default page size of new faxes.';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'resolution';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'normal';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the default transmission quality of new faxes.';
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