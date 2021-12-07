<?php

	//application details
		$apps[$x]['name'] = "Call Forward";
		$apps[$x]['uuid'] = "19806921-e8ed-dcff-b325-dd3e5da4959d";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.2";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Call Forward, Follow Me and Do Not Disturb.";
		$apps[$x]['description']['en-gb'] = "Call Forward, Follow Me and Do Not Disturb.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Anrufweiterleitung, Follow Me und Nicht-Stören.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Anrufweiterleitung, Follow Me und Nicht-Stören.";
		$apps[$x]['description']['es-cl'] = "Reenvio de llamada, Sígueme y No Molestar.";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "Renvoi d'appel, Follow Me et ne pas deranger.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Oproep doorsturen, Volg mij en Niet storen";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "Desvio de chamadas, Siga-me e Não perturbe (DND).";
		$apps[$x]['description']['pt-pt'] = "Desvio de Chamadas, Seguir-me e Não Perturbar.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "follow_me";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "agent";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_forward";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "agent";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "do_not_disturb";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "agent";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "call_forward_all";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "655447f1-e969-4885-b3da-28889b531568";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "follow_me";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "follow_me_autocomplete";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "0";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "follow me destinations autocomplete list. true=autocomplete On, false=autocomplete Off";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "4fedc226-caca-4ebf-b4eb-31de93e5bed3";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "follow_me";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "max_destinations";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "5";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "0";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Set the maximum number of Follow Me Destinations.";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "ce105d64-2d1b-4d19-ae34-ccb818cc9c6e";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "follow_me";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "timeout";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "30";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_order'] = "0";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Set the default Follow Me Timeout value.";

?>
