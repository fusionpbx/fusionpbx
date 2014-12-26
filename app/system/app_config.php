<?php

	//application details
		$apps[$x]['name'] = "System";
		$apps[$x]['uuid'] = "b7ef56fd-57c5-d4e8-bb4b-7887eede2e78";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Displays information for CPU, HDD, RAM and more.";
		$apps[$x]['description']['es-cl'] = "Muestra información del sistema como RAM, CPU y Disco Duro";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Affiche les information sur le sytème comme les informations sur la RAM, la CPU et le Disque Dur.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Exibe informações do CPU, disco rígido, memória RAM e muito mais.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "system_view_info";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "5243e0d2-0e8b-277a-912e-9d8b5fcdb41d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "system_view_cpu";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "5243e0d2-0e8b-277a-912e-9d8b5fcdb41d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "system_view_hdd";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "5243e0d2-0e8b-277a-912e-9d8b5fcdb41d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "system_view_ram";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "5243e0d2-0e8b-277a-912e-9d8b5fcdb41d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "system_view_memcache";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "5243e0d2-0e8b-277a-912e-9d8b5fcdb41d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "system_view_backup";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "5243e0d2-0e8b-277a-912e-9d8b5fcdb41d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "software_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "5243e0d2-0e8b-277a-912e-9d8b5fcdb41d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "software_add";
		//$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "software_edit";
		//$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "software_delete";
		//$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>