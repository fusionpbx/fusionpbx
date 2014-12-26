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

?>