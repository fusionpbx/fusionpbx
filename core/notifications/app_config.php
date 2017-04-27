<?php

	//application details
		$apps[$x]['name'] = "Notifications";
		$apps[$x]['uuid'] = "e746fbcb-f67f-4e0e-ab64-c414c01fac11";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Configure notification preferences.";
		$apps[$x]['description']['es-cl'] = "Configure las preferencias de notificaciones.";
		$apps[$x]['description']['de-de'] = "Verwalte Benachrichtigunggseinstellungen.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "Verwalte Benachrichtigunggseinstellungen.";
		$apps[$x]['description']['fr-fr'] = "Configurez les notifications.";
		$apps[$x]['description']['fr-ca'] = "Configurez les notifications.";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['ru-ru'] = "Настройка параметров уведомлений";

	//schema details
		$y=0;
		$apps[$x]['db'][$y]['table']['name'] = "v_notifications";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "notification_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "project_notifications";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;

?>
