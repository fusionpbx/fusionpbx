<?php

	//application details
		$apps[$x]['name'] = "Click to Call";
		$apps[$x]['uuid'] = "eb221c9b-cb13-5542-9140-dff924816dc4";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Originate calls with a URL.";
		$apps[$x]['description']['en-gb'] = "Originate calls with a URL.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Anrufe über eine URL erzeugen.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Anrufe über eine URL erzeugen.";
		$apps[$x]['description']['es-cl'] = "Genera llamadas con un URL.";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "Appeller avec d'URL";
		$apps[$x]['description']['fr-fr'] = "Appeler à partir d'une URL";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Start oproepen met een URL";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "Gera chamadas a partir de um URL";
		$apps[$x]['description']['pt-pt'] = "Originar chamadas com um URL.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Создание исходящих вызовов с помощью вызова URL";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "click_to_call_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "f862556f-9ddd-2697-fdf4-bed08ec63aa5";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "click_to_call_call";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
