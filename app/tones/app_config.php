<?php

	//application details
		$apps[$x]['name'] = "Tones";
		$apps[$x]['uuid'] = "38ab9f01-bcd2-4726-a9ff-9af8ed9e396a";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Manage Tones";
		$apps[$x]['description']['en-gb'] = "Manage Tones";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Töne verwalten";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Töne verwalten";
		$apps[$x]['description']['es-cl'] = "";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "Gestione Toni";
		$apps[$x]['description']['nl-nl'] = "Beheer geluiden";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Менеджер Тонов";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//destination details
		$y=0;
		$apps[$x]['destinations'][$y]['type'] = "sql";
		$apps[$x]['destinations'][$y]['label'] = "tones";
		$apps[$x]['destinations'][$y]['name'] = "tones";
		$apps[$x]['destinations'][$y]['sql'] = "select var_uuid as uuid, var_name as name, var_value as destination, var_description as description from v_vars";
		$apps[$x]['destinations'][$y]['where'] = "where var_category = 'Tones' ";
		$apps[$x]['destinations'][$y]['order_by'] = "var_name asc";
		$apps[$x]['destinations'][$y]['field']['uuid'] = "var_uuid";
		$apps[$x]['destinations'][$y]['field']['name'] = "var_name";
		$apps[$x]['destinations'][$y]['field']['destination'] = "var_filename";
		$apps[$x]['destinations'][$y]['field']['description'] = "var_description";
		$apps[$x]['destinations'][$y]['select_value']['dialplan'] = "playback:tone_stream://\${destination}";
		$apps[$x]['destinations'][$y]['select_value']['ivr'] = "menu-exec-app:playback tone_stream://\${destination}";
		$apps[$x]['destinations'][$y]['select_label'] = "\${name}";

		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "tone_destinations";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
