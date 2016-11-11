<?php

if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_uuid'] = '6840bdb0-eb9d-45bd-a79a-ccb64d08fd97';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'allowed_extension';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '.pdf';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_uuid'] = '7031d8ec-4610-4696-b5b4-9b5f89f2bff1';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'allowed_extension';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '.tif';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_uuid'] = 'c3c4487d-62f1-48d1-9fda-048fca830fb9';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'allowed_extension';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '.tiff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_uuid'] = 'cec88775-8aaf-4667-a04d-414b3274e545';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_logo';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Path to image/logo file displayed in the header of the cover sheet.';
		$x++;
		$array[$x]['default_setting_uuid'] = '6aba55ae-ced9-4853-81d0-33667be763e5';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_font';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'times';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Font used to generate cover page. Can be full path to .ttf file or font name alredy installed.';
		$x++;
		$array[$x]['default_setting_uuid'] = '549f8854-2377-448f-892c-58a85ac83a56';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_footer';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = "The information contained in this facsimile is intended for the sole confidential use of the recipient(s) designated above, and may contain confidential and legally privileged information. If you are not the intended recipient, you are hereby notified that the review, disclosure, dissemination, distribution, copying, duplication in any form, and taking of any action in regards to the contents of this document - except with respect to its direct delivery to the intended recipient - is strictly prohibited.  Please notify the sender immediately and destroy this cover sheet and all attachments.  If stored or viewed electronically, please permanently delete it from your system.";
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Notice displayed in the footer of the cover sheet.';
		$x++;
		$array[$x]['default_setting_uuid'] = 'e907df99-6b3a-4864-bd11-681888f20289';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'cover_header';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Default information displayed beneath the logo in the header of the cover sheet.';
		$x++;
		$array[$x]['default_setting_uuid'] = '8338a404-3966-416e-b4f9-a1ac36c37bd1';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'page_size';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'letter';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the default page size of new faxes.';
		$x++;
		$array[$x]['default_setting_uuid'] = '9fd6cb5f-5824-4cbb-89b2-53066649272e';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'resolution';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'normal';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the default transmission quality of new faxes.';
		$x++;
		$array[$x]['default_setting_uuid'] = '63e55b19-8bf2-4aaa-b7df-a514c62ecfcc';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'variable';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'fax_enable_t38=true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable T.38';
		$x++;
		$array[$x]['default_setting_uuid'] = '0f07d24a-296a-4798-8478-e6ef1a59f54f';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'variable';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'fax_enable_t38_request=false';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Send a T38 reinvite when a fax tone is detected.';
		$x++;
		$array[$x]['default_setting_uuid'] = '7681e5d8-1462-420b-9276-acf4b2156982';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'variable';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'ignore_early_media=true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Ignore ringing to improve fax success rate.';
		$x++;
		$array[$x]['default_setting_uuid'] = '80eee263-a22c-4ec9-9df1-397908a274f6';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'keep_local';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Keep the file after sending or receiving the fax.';
		$x++;
		$array[$x]['default_setting_uuid'] = '63d2cb3c-708a-43c2-b233-a3f4e3367224';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'send_mode';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'queue';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = '';
		$x++;
		$array[$x]['default_setting_uuid'] = 'd2977fe1-ee7e-4403-b44f-8d86aeb5f310';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'send_retry_limit';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '5';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Number of attempts to send fax (count only calls with answer)';
		$x++;
		$array[$x]['default_setting_uuid'] = '6ca1712b-5a53-4c54-ba53-b2046c89280d';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'send_retry_interval';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '15';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Delay before we make next call after answered call';
		$x++;
		$array[$x]['default_setting_uuid'] = '2c5640d0-493f-4426-b76d-04e378c86d76';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'send_no_answer_retry_limit';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '3';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Number of unanswered attempts in sequence';
		$x++;
		$array[$x]['default_setting_uuid'] = '03820df1-f242-4358-950f-ede87f502e9a';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'send_no_answer_retry_interval';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '30';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Delay before we make next call after no answered call';
		$x++;
		$array[$x]['default_setting_uuid'] = '9ed2e73c-6b82-4a79-83db-b7588f78c675';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'send_no_answer_limit';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '3';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Giveup reach the destination after this number of sequences';
		$x++;
		$array[$x]['default_setting_uuid'] = 'c97457b7-da9a-44a6-a4a4-442dbf5fa4fa';
		$array[$x]['default_setting_category'] = 'fax';
		$array[$x]['default_setting_subcategory'] = 'send_no_answer_interval';
		$array[$x]['default_setting_name'] = 'numeric';
		$array[$x]['default_setting_value'] = '300';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Delay before next call sequence';
		$x++;

	//get an array of the default settings
		$sql = "select * from v_default_settings where default_setting_category = 'fax'";
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
					unset($array[$x]);
				}
			}
			$x++;
		}
		unset($array);

	//update the array structure
		if (is_array($missing)) {
			$array['default_settings'] = $missing;
			unset($missing);
		}

	//add the default settings
		if (is_array($array)) {
			$database = new database;
			$database->app_name = 'default_settings';
			$database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
			$database->save($array);
			$message = $database->message;
			unset($database);
		}

}

?>
