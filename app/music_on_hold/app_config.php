<?php
	//application details
		$apps[$x]['name'] = "Music on Hold";
		$apps[$x]['uuid'] = "1dafe0f8-c08a-289b-0312-15baf4f20f81";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Add, Delete, or Play Music on hold files.";
		$apps[$x]['description']['es-cl'] = "Agregar, Eliminar o Reproducir archivos de música en espera";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "Ajouter, Supprimer ou Lire les fichiers de Musique de Garde.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "Adicionar, excluir ou reproduzir música em arquivos de espera.";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$apps[$x]['permissions'][0]['name'] = "music_on_hold_default_view";
		$apps[$x]['permissions'][0]['menu']['uuid'] = "1cd1d6cb-912d-db32-56c3-e0d5699feb9d";
		$apps[$x]['permissions'][0]['groups'][] = "superadmin";

		$apps[$x]['permissions'][1]['name'] = "music_on_hold_default_add";
		$apps[$x]['permissions'][1]['groups'][] = "superadmin";

		$apps[$x]['permissions'][2]['name'] = "music_on_hold_default_delete";
		$apps[$x]['permissions'][2]['groups'][] = "superadmin";

		$apps[$x]['permissions'][3]['name'] = "music_on_hold_view";
		$apps[$x]['permissions'][3]['menu']['uuid'] = "1cd1d6cb-912d-db32-56c3-e0d5699feb9d";
		$apps[$x]['permissions'][3]['groups'][] = "superadmin";
		$apps[$x]['permissions'][3]['groups'][] = "admin";

		$apps[$x]['permissions'][4]['name'] = "music_on_hold_add";
		$apps[$x]['permissions'][4]['groups'][] = "superadmin";
		$apps[$x]['permissions'][4]['groups'][] = "admin";

		$apps[$x]['permissions'][5]['name'] = "music_on_hold_delete";
		$apps[$x]['permissions'][5]['groups'][] = "superadmin";
		$apps[$x]['permissions'][5]['groups'][] = "admin";

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = "v_music_on_hold";
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_domains";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_name";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_rate";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "numeric";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "8000,16000,32000,48000";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_path";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_shuffle";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "true/false";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_timer";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "soft";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_chime_list";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_chime_freq";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "numeric";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "music_on_hold_chime_max";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "numeric";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";

?>