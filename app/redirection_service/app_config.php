<?php

	//application details
		$apps[$x]['name'] = "CNAM Caching";
		$apps[$x]['uuid'] = "d5fbc90b-dc1f-459b-aeb0-dc5427e78427";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "";
		$apps[$x]['description']['en-us'] = "CNAM caching app";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['es-cl'] = "";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//schema details
		$y=0;
		$apps[$x]['db'][$y]['table']['name'] = "v_cnam";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "cnam_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "phone_number";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name'] = "cnam";
                $apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "date";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "timestamptz";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "date";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "timestamp";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";


?>
