<?php
	
	//application details
		$apps[$x]['name'] = "Bulk Account Settings";
		$apps[$x]['uuid'] = "6b4e03c9-c302-4eaa-b16d-e1c5c08a2eb7";
		$apps[$x]['category'] = "Advanced";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Bulk Account Settings";

	//permission details

		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "bulk_account_settings";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "74341982-313c-4c42-bf4a-533be4f50a4a";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "bulk_account_settings_extensions";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "bulk_account_settings_devices";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;		
		$apps[$x]['permissions'][$y]['name'] = "bulk_account_settings_users";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;		
		$apps[$x]['permissions'][$y]['name'] = "bulk_account_settings_voicemails";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;		
		$apps[$x]['permissions'][$y]['name'] = "bulk_account_settings_view";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
?>
