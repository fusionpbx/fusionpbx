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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";

//load icons
$font_awesome_icons = [];
if (file_exists($_SERVER["PROJECT_ROOT"].'/resources/fontawesome/metadata/icons.json')) {
	$icons_json = file_get_contents($_SERVER["PROJECT_ROOT"].'/resources/fontawesome/metadata/icons.json');
	if (!empty($icons_json)) {
		$icons_array = json_decode($icons_json, true);
		if (!empty($icons_array) && is_array($icons_array)) {
			foreach ($icons_array as $icon_name => $properties) {
				if (!empty($properties['free']) && is_array($properties['free'])) {
					foreach ($properties['free'] as $icon_style) {
						$font_awesome_icons[] = 'fa-'.$icon_style.' fa-'.$icon_name;
					}
				}
			}
		}
	}
}

//view_array($font_awesome_icons, 0);