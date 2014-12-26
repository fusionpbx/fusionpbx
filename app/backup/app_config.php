<?php
	//application details
		$apps[$x]['name'] = "Backup";
		$apps[$x]['uuid'] = "1df70270-8274-44e9-a43e-5074684649ad";
		$apps[$x]['category'] = "App";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "";
		$apps[$x]['description']['es-cl'] = "";
		$apps[$x]['description']['es-mx'] = "";
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
		$apps[$x]['permissions'][$y]['name'] = "backup_download";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "7e174c3c-e494-4bb0-a52a-4ea55209ffeb";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "backup_upload";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

?>