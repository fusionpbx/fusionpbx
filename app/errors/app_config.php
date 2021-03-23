<?php

	//application details
		$apps[$x]['name'] = "Server Errors";
		$apps[$x]['uuid'] = "0e08d30e-e4ec-4efd-9f4b-fbd541642c9c";
		$apps[$x]['category'] = "Core";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Display web server errors.";
		$apps[$x]['description']['en-gb'] = "Display web server errors.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['es-cl'] = "";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Toon web server fouten.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "errors_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "0b4702e7-a254-4fda-84ae-1f28350fc8f5";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "687893ef-f082-4a5d-8511-2b924658ddb2";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "server";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "error";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "/var/log/nginx/error.log";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Path to web server error log file.";
		$y++;

?>