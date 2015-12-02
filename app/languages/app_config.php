<?php

	//application details
		$apps[$x]['name'] = "Languages";
		$apps[$x]['uuid'] = "23ecb350-b423-428d-9a8d-d617d27b30fe";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "A tool to analyze languages used in the GUI following https://msdn.microsoft.com/en-gb/library/ee825488%28v=cs.20%29.aspx";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "languages_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "29295c90-b1b9-440b-9c7E-c8363c6e8975";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>