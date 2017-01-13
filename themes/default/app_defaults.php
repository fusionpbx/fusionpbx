<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//get the background images
		$relative_path = PROJECT_PATH.'/themes/default/images/backgrounds';
		$backgrounds = opendir($_SERVER["DOCUMENT_ROOT"].'/'.$relative_path);
		unset($array);
		$x = 0;
		while (false !== ($file = readdir($backgrounds))) {
			if ($file != "." AND $file != "..") {
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if ($ext == "png" || $ext == "jpg" || $ext == "jpeg" || $ext == "gif") {
					$array[$x]['default_setting_category'] = 'theme';
					$array[$x]['default_setting_subcategory'] = 'background_image';
					$array[$x]['default_setting_name'] = 'array';
					$array[$x]['default_setting_value'] = $relative_path.'/'.$file;
					$array[$x]['default_setting_enabled'] = 'false';
					$array[$x]['default_setting_description'] = 'Set a relative path or URL within a selected compatible template.';
					$x++;
					$array[$x]['default_setting_category'] = 'theme';
					$array[$x]['default_setting_subcategory'] = 'login_background_image';
					$array[$x]['default_setting_name'] = 'array';
					$array[$x]['default_setting_value'] = $relative_path.'/'.$file;
					$array[$x]['default_setting_enabled'] = 'false';
					$array[$x]['default_setting_description'] = 'Set a relative path or URL within a selected compatible template.';
					$x++;
				}
				if ($x > 300) { break; };
			}
		}

}

?>
