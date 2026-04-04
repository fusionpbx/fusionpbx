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
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('music_on_hold_map')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//connect to the database
	$database = database::new();

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', 'false');

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//define the variables
	$search = '';
	$show = '';
	$list_row_url = '';

//add the search variable
	if (!empty($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//add the show variable
	if (!empty($_GET["show"])) {
		$show = $_GET["show"];
	}

//prepare the excluded applications array based on permission exists
	$excluded_app_array = [];
	if (!permission_exists('extension_view')) {
	    $excluded_app_array[] = 'extensions';
	}
	if (!permission_exists('ring_group_view')) {
	    $excluded_app_array[] = 'ring_groups';
	}
	if (!permission_exists('ivr_menu_view')) {
	    $excluded_app_array[] = 'ivr_menus';
	}
	if (!permission_exists('call_center_queue_view')) {
	    $excluded_app_array[] = 'call_center_queues';
	}
	if (!permission_exists('fifo_view')) {
	    $excluded_app_array[] = 'fifo';
	}
	if (!permission_exists('destination_view')) {
	    $excluded_app_array[] = 'destinations';
	}
	if (!permission_exists('dialplan_view')) {
	    $excluded_app_array[] = 'dialplans';
	}
	$excluded_applications = implode(',', $excluded_app_array);

//get the music on hold map
	$sql = "SELECT ";
	$sql .= " application, ";
	$sql .= " type, ";
	$sql .= " uuid, ";
	$sql .= " domain_uuid, ";
	$sql .= " domain_name, ";
	$sql .= " name, ";
	$sql .= " number, ";
	$sql .= " music, ";
	$sql .= " description \n";
	$sql .= "FROM view_music_on_hold_map \n";
	$sql .= "WHERE true \n";
	if (!empty($show) && $show === "all" && permission_exists('dialplan_all')) {
		//show all
	}
	else {
		$sql .= "AND domain_uuid = :domain_uuid \n";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!empty($search)) {
		$sql .= "AND ( \n";
		$sql .= "	application like :search \n";
		$sql .= "	or type like :search \n";
		$sql .= "	or name like :search \n";
		$sql .= "	or number like :search \n";
		$sql .= "	or music like :search \n";
		$sql .= "	or description like :search \n";
		$sql .= ") \n";
		$parameters['search'] = '%'.$search.'%';
	}
	if (!empty($excluded_applications)) {
	    $sql .= "AND application NOT IN ('" . implode("','", $excluded_app_array) . "') \n";
	}
	$sql .= "ORDER BY application, domain_name ASC \n";

//get the list
	$results = $database->select($sql, $parameters ?? null, 'all');
	$num_rows = count($results);
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//create the text object
	$text_language = new text;

//get the language
	$language = $settings->get('domain', 'language', 'en-us');

//additional includes
	$document['title'] = $text['title-music_on_hold_map'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-music_on_hold_map']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('dialplan_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all&search='.urlencode($search)]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	// if ($paging_controls_mini != '') {
	// 	echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	// }
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-music_on_hold_map']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	if (!empty($results) && is_array($results) && @sizeof($results) != 0) {
		$previous_application = '';
		$x = 0;
		foreach ($results as $row) {
			if ($row['application'] == 'extensions' && permission_exists('extension_edit')) {
				$list_row_url = "/app/extensions/extension_edit.php?id=".urlencode($row['uuid']);
			}
			if ($row['application'] == 'ivr_menus' && permission_exists('ivr_menu_edit')) {
				$list_row_url = "/app/ivr_menus/ivr_menu_edit.php?id=".urlencode($row['uuid']);
			}
			if ($row['application'] == 'dialplans' && permission_exists('dialplan_edit')) {
				$list_row_url = "/app/dialplans/dialplan_edit.php?id=".urlencode($row['uuid']);
			}
			if ($row['application'] == 'fifo' && permission_exists('fifo_edit')) {
				$list_row_url = "/app/fifo/fifo_edit.php?id=".urlencode($row['uuid']);
			}
			if ($row['application'] == 'call_center_queues' && permission_exists('call_center_queue_edit')) {
				$list_row_url = "/app/call_centers/call_center_queue_edit.php?id=".urlencode($row['uuid']);
			}
			if ($row['application'] == 'ring_groups' && permission_exists('call_center_queue_edit')) {
				$list_row_url = "/app/ring_groups/ring_group_edit.php?id=".urlencode($row['uuid']);
			}
			if ($row['application'] == 'destinations' && permission_exists('destination_edit')) {
				$list_row_url = "/app/destinations/destination_edit.php?id=".urlencode($row['uuid']);
			}
			if ($row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
				$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
			}

			//add the table header
			if ($previous_application != $row['application']) {
				$previous_application = $row['application'];
				if ($x > 0) {
					echo "</table>\n";
					echo "</div>\n";
				}

				$application = $row['application'];
				if ($application == 'call_center_queues') {
					$application = 'call_centers';
				}

				//add multi-lingual support
				if (file_exists(dirname(__DIR__, 2)."/app/".$application."/app_languages.php")) {
					$text2 = $text_language->get($settings->get('domain', 'language', 'en-us'), 'app/'.$application);
				}

				echo "<strong>".escape($text2['title-'.$application])."</strong>\n";
				echo "<div class='card'>\n";
				echo "<table class='list'>\n";
				echo "<tr class='list-header'>\n";
				if (permission_exists('extension_view') && $list_row_edit_button == 'true') {
					echo "	<td class='action-button'>&nbsp;</td>\n";
				}
				echo "</tr>\n";

				echo "<tr class='list-header' href='".$list_row_url."'>\n";
				if (!empty($show) && $show == 'all' && permission_exists('dialplan_all')) {
					echo "	<th class='' style='width: 150px;'>".escape($text['label-domain_name'])."</th>\n";
				}
				echo "	<th class='' style='width: 150px;'>".escape($text['label-name'])."</th>\n";
				echo "	<th class='' style='width: 150px;'>".escape($text['label-type'])."</th>\n";
				echo "	<th class='' style='width: 150px;'>".escape($text['label-extension'])."</th>\n";
				echo "	<th class='hide-sm-dn' style='width: 400px;'>".escape($text['label-music'])."</th>\n";
				echo "	<th class='hide-sm-dn' style='width:'>".escape($text['label-description'])."</th>\n";
				if (permission_exists('extension_view') && $list_row_edit_button == 'true') {
					echo "	<th class='action-button'>\n";
					echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
					echo "	</th>\n";
				}
				echo "</tr>\n";
			}

			//ad the table row
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (!empty($show) && $show == 'all' && permission_exists('dialplan_all')) {
				echo "	<td class=''>".escape($row['domain_name'])."</td>\n";
			}
			echo "	<td class=''>".escape($row['name'])."</td>\n";
			echo "	<td class=''>".escape($row['type'])."</td>\n";
			echo "	<td class=''>".escape($row['number'])."</td>\n";
			echo "	<td class='hide-sm-dn'>".escape($row['music'])."</td>\n";
			echo "	<td class='hide-sm-dn'>".escape($row['description'])."</td>\n";
			if (permission_exists('extension_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			//increment the value
			$x++;
		}
		unset($extensions);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";

	//echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
