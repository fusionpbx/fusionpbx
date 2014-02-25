<?php
	//application details
		$apps[$x]['name'] = "Login";
		$apps[$x]['uuid'] = "c9715076-dfdb-4f41-ab9f-a93f01bd6403";
		$apps[$x]['category'] = "Core";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Used to login to a user account";
		$apps[$x]['description']['es-cl'] = "Utilizado para iniciar sesión";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Pour connecter un compte usager";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Utilizado para iniciar a sessão de um utilizador";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Login";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Ingresar";
		$apps[$x]['menu'][0]['title']['es-mx'] = "";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Connexion";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Entrar";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "c85bf816-b88d-40fa-8634-11b456928afa";
		$apps[$x]['menu'][0]['parent_uuid'] = "";
		$apps[$x]['menu'][0]['category'] = "internal";
		$apps[$x]['menu'][0]['path'] = "/login.php";
		$apps[$x]['menu'][0]['groups'][] = "public";
		$apps[$x]['menu'][0]['order'] = "99";

		$apps[$x]['menu'][1]['title']['en-us'] = "Logout";
		$apps[$x]['menu'][1]['title']['es-cl'] = "Salir";
		$apps[$x]['menu'][1]['title']['es-mx'] = "";
		$apps[$x]['menu'][1]['title']['de-de'] = "";
		$apps[$x]['menu'][1]['title']['de-ch'] = "";
		$apps[$x]['menu'][1]['title']['de-at'] = "";
		$apps[$x]['menu'][1]['title']['fr-fr'] = "Déconnexion";
		$apps[$x]['menu'][1]['title']['fr-ca'] = "";
		$apps[$x]['menu'][1]['title']['fr-ch'] = "";
		$apps[$x]['menu'][1]['title']['pt-pt'] = "Sair";
		$apps[$x]['menu'][1]['title']['pt-br'] = "";
		$apps[$x]['menu'][1]['uuid'] = "0d29e9f4-0c9b-9d8d-cd2d-454899dc9bc4";
		$apps[$x]['menu'][1]['parent_uuid'] = "02194288-6d56-6d3e-0b1a-d53a2bc10788";
		$apps[$x]['menu'][1]['category'] = "internal";
		$apps[$x]['menu'][1]['path'] = "/logout.php";
		$apps[$x]['menu'][1]['groups'][] = "user";
		$apps[$x]['menu'][1]['groups'][] = "admin";
		$apps[$x]['menu'][1]['groups'][] = "superadmin";

?>
