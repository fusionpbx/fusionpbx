<?php
	//application details
		$apps[$x]['name'] = "App Manager";
		$apps[$x]['uuid'] = "d8704214-75a0-e52f-1336-f0780e29fef8";
		$apps[$x]['category'] = "";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "";
		$apps[$x]['description']['es-cl'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "Apps";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Aplicaciones";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Apps";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Aplicações";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "fd29e39c-c936-f5fc-8e2b-611681b266b5";
		$apps[$x]['menu'][0]['parent_uuid'] = "";
		$apps[$x]['menu'][0]['category'] = "internal";
		if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/xml_cdr')) {
			$apps[$x]['menu'][0]['path'] = "/app/xml_cdr/xml_cdr.php";
		}
		else {
			$apps[$x]['menu'][0]['path'] = PROJECT_PATH;
		}
		$apps[$x]['menu'][0]['order'] = "20";
		$apps[$x]['menu'][0]['groups'][] = "user";
		$apps[$x]['menu'][0]['groups'][] = "admin";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";

		$apps[$x]['menu'][1]['title']['en-us'] = "App Manager";
		$apps[$x]['menu'][1]['title']['es-cl'] = "Administrador de Aplicaciones";
		$apps[$x]['menu'][1]['title']['de-de'] = "";
		$apps[$x]['menu'][1]['title']['de-ch'] = "";
		$apps[$x]['menu'][1]['title']['de-at'] = "";
		$apps[$x]['menu'][1]['title']['fr-fr'] = "Gestion App";
		$apps[$x]['menu'][1]['title']['fr-ca'] = "";
		$apps[$x]['menu'][1]['title']['fr-ch'] = "";
		$apps[$x]['menu'][1]['title']['pt-pt'] = "Gestor de Aplicações";
		$apps[$x]['menu'][1]['title']['pt-br'] = "";
		$apps[$x]['menu'][1]['uuid'] = "ef00f229-7890-00c2-bf23-fed5b8fa9fe7";
		$apps[$x]['menu'][1]['parent_uuid'] = "594d99c5-6128-9c88-ca35-4b33392cec0f";
		$apps[$x]['menu'][1]['category'] = "internal";
		$apps[$x]['menu'][1]['path'] = "/core/apps/apps.php";
		$apps[$x]['menu'][1]['groups'][] = "superadmin";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "app_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "ef00f229-7890-00c2-bf23-fed5b8fa9fe7";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "app_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "app_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "app_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = "v_apps";
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "app_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "app_category";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "app_version";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "app_enabled";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "";
		$z++;

?>