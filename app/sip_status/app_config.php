<?php

	//application details
		$apps[$x]['name'] = "SIP Status";
		$apps[$x]['uuid'] = "caca8695-9ca7-b058-56e7-4ea94ea1c0e8";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Displays system information such as RAM, CPU and Hard Drive information.";
		$apps[$x]['description']['en-gb'] = "Displays system information such as RAM, CPU and Hard Drive information.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Zeigt den SIP-Status an.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Zeigt den SIP-Status an.";
		$apps[$x]['description']['es-cl'] = "Muestra información del sistema como RAM, CPU y Disco Duro";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "Affiche les informations système telles que les informations sur la RAM, le processeur et le disque dur.";
		$apps[$x]['description']['fr-fr'] = "Affiche les informations système telles que les informations sur la RAM, le processeur et le disque dur.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Toont de systeem informatie zoals RAM, CPU en Harddisk informatie.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "Exibe informações do sistema, como memória RAM, CPU e informações do disco rígido.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Отображает системную информацию о состоянии Памяти, Процессора и Дисковых накопителей.";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "system_status_sofia_status";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "b7aea9f7-d3cf-711f-828e-46e56e2e5328";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "system_status_sofia_status_profile";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "b7aea9f7-d3cf-711f-828e-46e56e2e5328";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_status_switch_status";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
