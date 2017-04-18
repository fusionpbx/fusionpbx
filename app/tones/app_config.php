<?php

	//application details
		$apps[$x]['name'] = "Tones";
		$apps[$x]['uuid'] = "38ab9f01-bcd2-4726-a9ff-9af8ed9e396a";
		$apps[$x]['category'] = "Switch";;
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Manage Tones";

	//destination details
		$y=0;
		$apps[$x]['destinations'][$y]['type'] = "sql";
		$apps[$x]['destinations'][$y]['label'] = "tones";
		$apps[$x]['destinations'][$y]['name'] = "tones";
		$apps[$x]['destinations'][$y]['sql'] = "select var_uuid as uuid, var_name as name, var_value as destination, var_description as description from v_vars";
		$apps[$x]['destinations'][$y]['where'] = "where var_cat = 'Tones' ";
		$apps[$x]['destinations'][$y]['order_by'] = "var_name asc";
		$apps[$x]['destinations'][$y]['field']['uuid'] = "var_uuid";
		$apps[$x]['destinations'][$y]['field']['name'] = "var_name";
		$apps[$x]['destinations'][$y]['field']['destination'] = "var_filename";
		$apps[$x]['destinations'][$y]['field']['description'] = "var_description";
		$apps[$x]['destinations'][$y]['select_value']['dialplan'] = "play tone_stream://\${destination}";
		$apps[$x]['destinations'][$y]['select_value']['ivr'] = "play tone_stream://\${destination}";
		$apps[$x]['destinations'][$y]['select_label'] = "\${name}";

?>
