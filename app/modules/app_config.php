<?php
	//application details
		$apps[$x]['name'] = "Modules";
		$apps[$x]['uuid'] = "5eb9cba1-8cb6-5d21-e36a-775475f16b5e";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Modules extend the features of the system. Use this page to enable or disable modules.";
		$apps[$x]['description']['es-cl'] = "Los módulos extienden las funcionalidades del sistema. Utilice esta página para activar o desactivar los diferentes módulos.";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Les Modules augmentent les fonctionnalitées du système. Ici il est possible de les activer et de les désactiver.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Módulos estendem as funcionalidades do sistema. Utilize esta página para habilitar ou desabilitar módulos.";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Modules";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Módulos";
		$apps[$x]['menu'][0]['title']['es-mx'] = "";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Modules";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Modulos";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "49fdb4e1-5417-0e7a-84b3-eb77f5263ea7";
		$apps[$x]['menu'][0]['parent_uuid'] = "02194288-6d56-6d3e-0b1a-d53a2bc10788";
		$apps[$x]['menu'][0]['category'] = "internal";
		$apps[$x]['menu'][0]['path'] = "/app/modules/modules.php";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "module_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "49fdb4e1-5417-0e7a-84b3-eb77f5263ea7";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "module_add";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "module_edit";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "module_delete";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = "v_modules";
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "id";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "module_id";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "serial";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "integer";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "INT NOT NULL AUTO_INCREMENT";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = "true";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "module_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "v_id";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = "true";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "module_label";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "modulelabel";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "module_name";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "modulename";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "module_category";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "modulecat";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "module_enabled";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "moduleenabled";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "module_default_enabled";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "moduledefaultenabled";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "module_description";
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = "moduledesc";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";

?>
