<?php

	//application details
		$apps[$x]['name'] = "BDR Management";
		$apps[$x]['uuid'] = "399cd584-e2eb-4993-afa9-4292593600a3";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "A tool to manage BDR.";
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
		$apps[$x]['permissions'][$y]['name'] = "bdr_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "7ed61deb-14dd-49f6-940d-6cf2023f8481";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "bdr_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

?>