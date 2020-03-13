<?php

	//application details
		$apps[$x]['name'] = "Upgrade";
		$apps[$x]['uuid'] = "8b1d7eb5-1009-052c-e1a8-d1f4887a3f5c";
		$apps[$x]['category'] = "Core";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Update or restore various system settings.";
		$apps[$x]['description']['en-gb'] = "Update or restore various system settings.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Verschiedene Systemeinstellungen aktualisieren oder zurücksetzen.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Verschiedene Systemeinstellungen aktualisieren oder zurücksetzen.";
		$apps[$x]['description']['es-cl'] = "Actualiza el esquema de la base de datos";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "Mise à jour du Schàma de la base de donnàes";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "Atualizar";
		$apps[$x]['description']['pt-pt'] = "Atualizar o esquema da base de dados.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Обновление или восстановление настроек системы";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "upgrade_source";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "71051909-81ff-4301-9997-52b11206b3a6";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "upgrade_schema";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "8c826e92-be3c-0944-669a-24e5b915d562";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "upgrade_apps";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "e7bb1296-3141-48c9-a95a-82d2768d0ae4";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "upgrade_switch";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
