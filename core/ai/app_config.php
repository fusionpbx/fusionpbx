<?php

	//application details
		$apps[$x]['name'] = 'AI';
		$apps[$x]['uuid'] = 'be9e4d36-bea6-4dd2-b08e-b5f10a0ab8df';
		$apps[$x]['category'] = 'AI';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '1.0';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = 'Artificial Intelligence';

	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "c4751875-011d-4181-ba9a-8978ffd7497d";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "ai";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "speech_enabled";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Text to Speech";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "e7a77c36-92d1-4fb6-9db3-4f62866dbaf2";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "ai";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "speech_engine";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "openai";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Text to Speech";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "eced068b-db30-4257-aa7c-6e2659271e4b";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "ai";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "speech_key";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Text to Speech";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "bc054920-5877-4695-9885-9c9009a7713c";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "ai";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "transcribe_enabled";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Speech to Text";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "72b9feeb-b21c-4dad-ad26-dde86955d87b";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "ai";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "transcribe_engine";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "openai";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Speech to Text";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "7883f9fc-9259-4f9b-a73d-532f44db2a28";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "ai";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "transcribe_key";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Speech to Text";
		$y++;

?>