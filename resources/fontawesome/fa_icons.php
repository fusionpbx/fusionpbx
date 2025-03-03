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
			$i = 0;
			foreach ($icons_array as $icon_name => $properties) {
				//loop through free icons
				if (!empty($properties['free']) && is_array($properties['free'])) {
					foreach ($properties['free'] as $icon_style) {
						//if search terms exist, add them to array
						$terms = [];
						if (!empty($properties['search']['terms']) && is_array($properties['search']['terms'])) {
							foreach ($properties['search']['terms'] as $term) {
								$words = explode(' ', $term);
								foreach ($words as $word) {
									if (strlen($word) >= 3) {
										$terms[] = strtolower($word);
									}
								}
								unset($words);
							}
						}
						//add icon name *words* themselves as search terms
						if (strlen(str_replace('fa-', '', $icon_name)) >= 3) {
							$words = explode(' ', str_replace(['fa-','-'], ['',' '], $icon_name));
							foreach ($words as $word) {
								if (strlen($word) >= 3) {
									$terms[] = strtolower($word);
								}
							}
							unset($words);
						}
						//remove duplicate terms
						if (!empty($terms) && is_array($terms)) {
							$terms = array_unique($terms);
						}
						//filter by search, if submitted
						if (
							!empty($_GET['search']) &&
							strlen(trim($_GET['search'])) >= 3 &&
							(
								empty($terms) ||
								(
									!empty($terms) &&
									is_array($terms) &&
									!in_array(trim(strtolower($_GET['search'])), $terms)
								)
							)
							) {
							continue;
						}
						$font_awesome_icons[$i]['terms'] = $terms;
						//add classes (icon style and name)
						$font_awesome_icons[$i]['classes']['style'] = 'fa-'.$icon_style;
						$font_awesome_icons[$i]['classes']['name'] = 'fa-'.$icon_name;
						//detmine whether to append style to previous (and current) label
						$append_style = false;
						if (
							$i != 0 &&
							$font_awesome_icons[$i - 1]['classes']['name'] == $font_awesome_icons[$i]['classes']['name'] &&
							$font_awesome_icons[$i - 1]['classes']['style'] != $font_awesome_icons[$i]['classes']['style']
							) {
							$font_awesome_icons[$i - 1]['label'] .= ' - '.ucwords(str_replace('fa-', '', $font_awesome_icons[$i - 1]['classes']['style']));
							$append_style = true;
						}
						//determine label
						$font_awesome_icons[$i]['label'] = ucwords(str_replace(['fa-','-'], ['',' '], $icon_name)).($append_style ? ' - '.ucwords(str_replace('fa-', '', $font_awesome_icons[$i]['classes']['style'])) : null);
						//clear vars
						$i++;
					}
				}
			}
		}
	}
}
//view_array($font_awesome_icons);

//output icons
if (
	!empty($_GET['output']) && $_GET['output'] == 'icons' &&
	!empty($font_awesome_icons) && is_array($font_awesome_icons)
	) {
	foreach ($font_awesome_icons as $icon) {
		echo "<span class='".escape(implode(' ', $icon['classes']))." fa-fw' style='font-size: 24px; float: left; margin: 0 8px 8px 0; cursor: pointer; opacity: 0.3;' title='".escape($icon['label'])."' onclick=\"$('#selected_icon').val('".escape(implode(' ', $icon['classes']))."'); $('#icon_color').show(); $('#icons').slideUp(); $('#icon_search').fadeOut(200, function() { $('#icon_search').val(''); $('#grid_icon').fadeIn(); });\" onmouseover=\"this.style.opacity='1';\" onmouseout=\"this.style.opacity='0.3';\"></span>\n";
	}
}