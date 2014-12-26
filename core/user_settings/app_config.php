<?php
	//application details
		$apps[$x]['name'] = "Account Settings";
		$apps[$x]['uuid'] = "3a3337f7-78d1-23e3-0cfd-f14499b8ed97";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "User account settings can be changed by the user.";
		$apps[$x]['description']['es-cl'] = "La configuración de la cuenta puede ser modificada por el usuario.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "L'usager peut modifier la configuration de son compte";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Configurações de conta de utilizador pode ser alterado pelo utilizador.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "user_account_setting_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "4d532f0b-c206-c39d-ff33-fc67d668fb69";
		$apps[$x]['permissions'][0]['groups'][] = "user";
		$apps[$x]['permissions'][0]['groups'][] = "admin";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "user_account_setting_edit";
		$apps[$x]['permissions'][1]['groups'][] = "user";
		$apps[$x]['permissions'][1]['groups'][] = "admin";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

?>