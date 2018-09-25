<?php

	//application details
		$apps[$x]['name'] = "Destination Imports";
		$apps[$x]['uuid'] = "ad643dc8-7129-4381-9a8c-50fc2a2a823a";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Used to define external destination numbers.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Wird verwendet um externe Ziele zu definieren.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Wird verwendet um externe Ziele zu definieren.";
		$apps[$x]['description']['es-cl'] = "Utilizado para definir números de destino externos.";
		$apps[$x]['description']['es-mx'] = "Utilizado para definir numeros destinos externos.";
		$apps[$x]['description']['fr-ca'] = "Usé pour définir cibler nombres externe.";
		$apps[$x]['description']['fr-fr'] = "Défini les numéros externes.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "Utilizado para definir os números de destino externos.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "destination_import";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

?>