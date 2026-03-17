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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (!permission_exists('destination_map')) {
	echo "access denied";
	exit;
}

//get the search string
$search = $_REQUEST['search'] ?? '';

//get the destinations
$destinations = new destinations();
$array = $destinations->get('dialplan');

//get the language
$domain_language = $settings->get('domain', 'language');

//add multi-lingual support
$language = new text;
$text = $language->get();

//add multi-lingual support
$language2 = new text;

//count the rows
$num_rows = 0;
foreach($array as $key => $value) {
	foreach($value as $row) {
		//show only rows that match the search
		if (!empty($search) && !empty($row['uuid'])) {
			if (stripos($row['label'].$row['extension'], $search) !== false) {
				$destination_array[$key][] = $row;
				$num_rows++;
			}
		}

		//count the rows when the search is empty
		if (empty($search) && !empty($row['uuid'])) {
			$destination_array[$key][] = $row;
			$num_rows++;
		}
	}
}

//include the header
require_once "resources/header.php";

//show the content
echo "<div class='action_bar' id='action_bar'>\n";
echo "	<div class='heading'><b>".$text['title-destination_map']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
echo "	<div class='actions'>\n";
echo 		"<form id='form_search' class='inline' method='get'>\n";
echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
if ($paging_controls_mini != '') {
	echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
}
echo "		</form>\n";
echo "	</div>\n";
echo "	<div style='clear: both;'>".$text['description-destination_map']."</div>\n";
echo "</div>\n";

//loop through the destinations array
if (!empty($destination_array)) {
	foreach($destination_array as $key => $value) {
		//add multi-lingual support
		if (file_exists(dirname(__DIR__, 2)."/app/".$key."/app_languages.php")) {
			$text2 = $language2->get($domain_language, 'app/'.$key);
		}

		//set the applications php list filename
		$app_name_list = $key.'.php';
		if ($key == 'call_centers') {
			$app_name_list = 'call_center_queues.php';
		}

		//echo "<h3>".$text['title-'.$key]."</h3>\n";
		echo "<div class=\"category\" id=\"".escape($key)."\">\n";
		echo "<b><a href='/app/".$key."/".$app_name_list."'>".$text2['title-'.$key]."</a></b><br>\n";
		echo "<div class=\"card\">\n";

		echo "	<table class='list'>\n";
		echo "	<tr class='list-row' href='".$list_row_url."'>\n";
		echo "		<th>".$text['label-label']."</th>\n";
		echo "		<th>".$text['label-extension']."</th>\n";
		echo "		<th>&nbsp;</th>\n";
		//echo "		<th>description</th>\n";
		echo "	</tr>\n";
		foreach($value as $row) {
			if (!empty($row['uuid'])) {
				//set the applications php edit filename
				$app_name_edit = database::singular($key).'_edit.php';
				if ($key == 'call_centers') {
					$app_name_edit = 'call_center_queue_edit.php';
				}

				//set the row URL
				$list_row_url = '/app/'.$key.'/'.$app_name_edit.'?id='.$row['uuid'];

				//show the row
				echo "	<tr class='list-row' href='".$list_row_url."'>\n";
				echo "		<td class='overflow no-wrap'>\n";
				echo "			".$row['label']."\n";
				echo "		</td>\n";
				echo "		<td class='overflow no-wrap'>\n";
				echo "			".$row['extension']."\n";
				echo "		</td>\n";
				// echo "		<td class='overflow no-wrap'>\n";
				// echo "			".$row['description']."\n";
				// echo "		</td>\n";
				echo "		<td class=''>\n";
				echo "			&nbsp;\n";
				echo "		</td>\n";
				echo "	</tr>\n";
			}
		}
		echo "	</table>\n";
		echo "</div >\n";
		echo "<br />\n";
	}
}

//include the footer
require_once "resources/footer.php";
