<?php

	//application details
		$apps[$x]['name'] = "Hot Desking";
		$apps[$x]['uuid'] = "f4ae30f0-68ff-46d2-afd3-34caff2887c9";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Login into hot desking with an ID and your voicemail password to direct your calls to a remote extension. Then make and receive calls as if you were at your extension.";
		$apps[$x]['description']['es-cl'] = "Ingrese en un escritorio con un ID y la contaseña para direccionar las llamadas a una extensión remota. Hace y recibe llamadas";
		$apps[$x]['description']['es-mx'] = "Firmarse en un escritorio con un ID y la contaseña para direccionar las llamadas a una extensión remota. Hace y recibe llamadas";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "S'identifier au bureau avec un ID et un mot de passe pour diriger touts les appels vers autre poste. Passer et recevoir appels."; 
		$apps[$x]['description']['fr-ca'] = "S'identifier au bureau avec un ID et la mot de passe pour diriger touts les appels vers autre bureau lointain.  Faites et recevoyez appels.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Habilitar o escritório remoto recorrendo a um ID e à password do correio de voz para encaminhar as chamadas para uma extensão remota. Em seguida, fazer e receber ligações como se estivesse a utilizar a sua extensão.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "hot_desk_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "baa57691-37d4-4c7d-b227-f2929202b480";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "hot_desk_add";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "hot_desk_edit";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "hot_desk_delete";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

?>