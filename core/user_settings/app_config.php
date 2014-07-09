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

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Account Settings";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Config de Cuenta";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Confs du Compte";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Configurações da Conta";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "4d532f0b-c206-c39d-ff33-fc67d668fb69";
		$apps[$x]['menu'][0]['parent_uuid'] = "02194288-6d56-6d3e-0b1a-d53a2bc10788";
		$apps[$x]['menu'][0]['category'] = "internal";
		$apps[$x]['menu'][0]['path'] = "/core/user_settings/user_edit.php";
		$apps[$x]['menu'][0]['groups'][] = "user";
		$apps[$x]['menu'][0]['groups'][] = "admin";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";

		$apps[$x]['menu'][1]['title']['en-us'] = "User Dashboard";
		$apps[$x]['menu'][1]['title']['es-cl'] = "Dashboard Usuario";
		$apps[$x]['menu'][1]['title']['de-de'] = "";
		$apps[$x]['menu'][1]['title']['de-ch'] = "";
		$apps[$x]['menu'][1]['title']['de-at'] = "";
		$apps[$x]['menu'][1]['title']['fr-fr'] = "Tableau de bord de l'utilisateur";
		$apps[$x]['menu'][1]['title']['fr-ca'] = "";
		$apps[$x]['menu'][1]['title']['fr-ch'] = "";
		$apps[$x]['menu'][1]['title']['pt-pt'] = "Painel de Controle do Usuário";
		$apps[$x]['menu'][1]['title']['pt-br'] = "";
		$apps[$x]['menu'][1]['uuid'] = "92c8ffdb-3c82-4f08-aec0-82421ec41bb5";
		$apps[$x]['menu'][1]['parent_uuid'] = "02194288-6d56-6d3e-0b1a-d53a2bc10788";
		$apps[$x]['menu'][1]['category'] = "internal";
		$apps[$x]['menu'][1]['path'] = "/core/user_settings/user_dashboard.php";
		$apps[$x]['menu'][0]['groups'][] = "user";
		$apps[$x]['menu'][0]['groups'][] = "admin";
		$apps[$x]['menu'][1]['groups'][] = "superadmin";

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