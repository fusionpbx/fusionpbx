<?php

	//application details
		$apps[$x]['name'] = "Tenant Settings";
		$apps[$x]['guid'] = "dddaf83c-f20c-489a-b6f7-9c75dadaa710";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "Alpha";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Allow tenants to manage specific domain settings";
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
		$apps[$x]['permissions'][$y]['name'] = "tenant_settings_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "a684573f-3b61-463c-aab7-606a5e59e09e";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "tenant_settings_admin";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;


?>