<?php

	//application details
		$apps[$x]['name'] = "Demo Application";
		$apps[$x]['uuid'] = "de477837-1cga-4b3e-9b51-ad6c9e99215c";
		$apps[$x]['category'] = "";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "This application serve no real purpose";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "demo_a";
		//$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "demo_b";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "demo_c";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "demo_d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>
