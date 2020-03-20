<?php

	//application details
		$apps[$x]['name'] = "System";
		$apps[$x]['uuid'] = "b7ef56fd-57c5-d4e8-bb4b-7887eede2e78";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Displays information for CPU, HDD, RAM and more.";
		$apps[$x]['description']['en-gb'] = "Displays information for CPU, HDD, RAM and more.";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Zeigt Informationen über die CPU, Festplatten, Speicher und anderes an.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Zeigt Informationen über die CPU, Festplatten, Speicher und anderes an.";
		$apps[$x]['description']['es-cl'] = "Muestra información del sistema como RAM, CPU y Disco Duro";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "Affiche les information sur le sytème comme les informations sur la RAM, la CPU et le Disque Dur.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['nl-nl'] = "Laat informatie zien over CPU, HardDisk, RAM en meer.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "Exibe informações do CPU, disco rígido, memória RAM e muito mais.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Отображает на дисплее информацию о Процессоре, Пямяти, Дисковых накопителях и другоую.";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
		$y=0;
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
