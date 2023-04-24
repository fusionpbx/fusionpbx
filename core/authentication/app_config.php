<?php

	//application details
		$apps[$x]['name'] = "Authentication";
		$apps[$x]['uuid'] = "a8a12918-69a4-4ece-a1ae-3932be0e41f1";
		$apps[$x]['category'] = "Core";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.1";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Provides an authentication framework with plugins to check if a user is authorized to login.";
		$apps[$x]['description']['en-gb'] = "Provides an authentication framework with plugins to check if a user is authorized to login.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "Stellt ein Authentifizierungs-Framework mit Plugins bereit, um zu prüfen, obsich ein Benutzer anmelden darf.";
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
		$apps[$x]['description']['ru-ru'] = "Предоставляет платформу проверки подлинности с плагинами для проверки авторизации пользователя.";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "309c4b74-711a-4a73-9408-412e5d089b59";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "authentication";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "methods";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "array";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "database";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "10";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "bc31a3f4-671b-44ca-8724-64ec077eed0b";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "authentication";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "methods";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "array";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "email";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "20";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "ab6ecf21-28e8-4caf-a04e-8667ec702f37";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "authentication";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "methods";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "array";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "totp";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "30";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";

?>
