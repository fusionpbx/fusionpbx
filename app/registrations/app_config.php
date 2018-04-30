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
		$apps[$x]['description']['nl-nl'] = "";
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

?>
