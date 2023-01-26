<?php

	//application details
		$apps[$x]['name'] = "Switch";
		$apps[$x]['uuid'] = "9cc48cb9-22d3-42eb-8bf8-3ca970e364d7";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Switch details such as version, uptime, channels and registrations.";
		$apps[$x]['description']['en-gb'] = "Switch details such as version, uptime, channels and registrations.";
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
		$apps[$x]['description']['nl-nl'] = "";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//destination details
		$languages = glob($_SESSION["switch"]['sounds']['dir']."/*/*/*");
		foreach ($languages as $key => $path) {
			$path = str_replace($_SESSION["switch"]['sounds']['dir'].'/', "", $path);
			$path_array = explode('/', $path);
			if (count($path_array) <> 3 || strlen($path_array[0]) <> 2 || strlen($path_array[1]) <> 2) {
				unset($languages[$key]);
			}
			$languages[$key] = str_replace($_SESSION["switch"]['sounds']['dir']."/","",$languages[$key]);
			if (strlen($languages[$key]) == 0) {
				unset($languages[$key]);
			}
		}
		$y=0;
		$apps[$x]['destinations'][$y]['type'] = 'array';
		$apps[$x]['destinations'][$y]['label'] = 'languages';
		$apps[$x]['destinations'][$y]['name'] = 'languages';
		$apps[$x]['destinations'][$y]['field']['name']  = 'name';
		$apps[$x]['destinations'][$y]['field']['language'] = 'language';
		$apps[$x]['destinations'][$y]['field']['dialect'] = 'dialect';
		$apps[$x]['destinations'][$y]['field']['voice'] = 'voice';
		$apps[$x]['destinations'][$y]['select_value']['dialplan'] = "multiset:^^,default_language=\${language},default_dialect=\${dialect},default_voice=\${voice}";
		$apps[$x]['destinations'][$y]['select_value']['ivr'] = "menu-exec-app:multiset ^^,default_language=\${language},default_dialect=\${dialect},default_voice=\${voice}";
		$apps[$x]['destinations'][$y]['select_label'] = "\${name}";
		$z=0;
		foreach ($languages as $language) {
			$apps[$x]['destinations'][$y]['result']['data'][$z]['name'] = str_replace('/', '-', $language);
			$apps[$x]['destinations'][$y]['result']['data'][$z]['language'] = explode('/', $language)[0];
			$apps[$x]['destinations'][$y]['result']['data'][$z]['dialect'] = explode('/', $language)[1];
			$apps[$x]['destinations'][$y]['result']['data'][$z]['voice'] = explode('/', $language)[2];
			$z++;
		}

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "switch_version";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "switch_uptime";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "switch_channels";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "switch_registrations";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "language_destinations";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";

	//default settings
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "9ff1ed88-76ba-4648-a082-d53f64947d08";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "dashboard";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "switch_status_chart_main_background_color";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "#2a9df4";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "7d7ba6e6-1616-4082-9f20-774e8ef673c9";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "dashboard";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "switch_status_chart_sub_background_color";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "#d4d4d4";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "cad4fdf1-2eb5-4669-8215-2b3dbee5d124";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "dashboard";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "switch_status_chart_border_color";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "rgba(0,0,0,0)";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "206d91d0-1ee2-46f3-a30b-977ad49b84cb";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "dashboard";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "switch_status_chart_border_width";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "0";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";

?>
