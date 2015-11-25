<?php

	//application details
		$apps[$x]['name'] = "Install";
		$apps[$x]['uuid'] = "75507e6e-891e-11e5-af63-feff819cdc9f";
		$apps[$x]['category'] = "Core";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Install the fuisionPBX system or add new switches";
		$apps[$x]['description']['es-cl'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['pt-br'] = "";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "install";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "75507e6e-891e-11e5-af63-feff819cdc9f";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

?>