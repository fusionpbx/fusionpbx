<?php

	//application details
		$apps[$x]['name'] = "Registrations";
		$apps[$x]['uuid'] = "5d9e7cd7-629e-3553-4cf5-f26e39fefa39";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Displays registrations from endpoints.";
		$apps[$x]['description']['en-gb'] = "Displays registrations from endpoints.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Zeigt registrierte Endgeräte an.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Zeigt registrierte Endgeräte an.";
		$apps[$x]['description']['es-cl'] = "Muestra los registros desde los extremos";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "Afficher les enregistrements des équipements.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Toont registraties van endpunten.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "Exibe registos de terminais SIP.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "registration_domain";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "17dbfd56-291d-8c1c-bc43-713283a9dd5a";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "registration_all";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "17dbfd56-291d-8c1c-bc43-713283a9dd5a";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "registration_reload";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "e3bd174e-ef22-46e0-b65f-3598531d29b6";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "aa2fa675-ccc0-4343-92fb-76c37d67a409";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "registrations";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "list_row_button_unregister";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Set whether to display the Unregister button on individual list rows.";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "ded35115-d6f2-4724-ba57-3c46bdd89a58";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "registrations";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "list_row_button_provision";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Set whether to display the Provision button on individual list rows.";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "cd8c393a-42c1-4de6-8a27-a857c8ae5e58";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "registrations";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "list_row_button_reboot";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Set whether to display the Reboot button on individual list rows.";

?>
