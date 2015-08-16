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
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'variable';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'fax_enable_t38=true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable T.38';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'variable';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'fax_enable_t38_request=false';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Send a T38 reinvite when a fax tone is detected.';
		$x++;
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'keep_local';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Keep the file after sending or receiving the fax.';
		$x++;
	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);

	//find the missing default settings
		$x = 0;
		foreach ($array as $setting) {
			$found = false;
			$missing[$x] = $setting;
			foreach ($default_settings as $row) {
				if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory'])) {
					$found = true;
					//remove items from the array that were found
					unset($missing[$x]);
				}
			}
			$x++;
		}

	//add the missing default settings
		foreach ($missing as $row) {
			//add the default settings
			$orm = new orm;
			$orm->name('default_settings');
			$orm->save($row);
			$message = $orm->message;
			unset($orm);
		}
		unset($missing);

}

?>