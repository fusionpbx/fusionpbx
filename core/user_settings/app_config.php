<?php

	//application details
		$apps[$x]['name'] = "Account Settings";
		$apps[$x]['uuid'] = "3a3337f7-78d1-23e3-0cfd-f14499b8ed97";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "User account settings can be changed by the user.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Einstellungen des Benutzerkontos können durch den Benutzer geändert werden.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Einstellungen des Benutzerkontos können durch den Benutzer geändert werden.";
		$apps[$x]['description']['es-cl'] = "La configuración de la cuenta puede ser modificada por el usuario.";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "L'usager peut modifier la configuration de son compte";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "Configurações das contas podem ser alteradas pelo usuário.";
		$apps[$x]['description']['pt-pt'] = "Configurações de conta de utilizador pode ser alterado pelo utilizador.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Настройки учетной записи могут быть изменены пользователем";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "user_account_setting_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "4d532f0b-c206-c39d-ff33-fc67d668fb69";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "user_account_setting_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
