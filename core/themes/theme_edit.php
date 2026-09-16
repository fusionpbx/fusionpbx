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

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!(permission_exists('theme_add') || permission_exists('theme_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the defaults
	$theme_category = '';
	$theme_name = '';
	$theme_description = '';

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'theme_setting_name'));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';

// Build the query string
	$url_params = [];
	if (!empty($theme_uuid)) {
		$url_params['id'] = $theme_uuid;
	}
	if (!empty($page)) {
		$url_params['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$url_params['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$url_params['order'] = $order;
	}
	if (!empty($search)) {
		$url_params['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('theme_all')) {
		$url_params['show'] = $show;
	}
	$query_string = http_build_query($url_params);

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$theme_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
		$theme_uuid = '';
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$theme_category = $_POST["theme_category"] ?? null;
		$theme_name = $_POST["theme_name"] ?? null;
		$theme_enabled = $_POST["theme_enabled"] ?? null;
		$theme_description = $_POST["theme_description"] ?? null;
		$theme_settings = $_POST["theme_settings"] ?? null;
	}

//process the data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: theme_edit.php?id='.urlencode($theme_uuid));
				exit;
			}

		//check for all required data
			$msg = '';
			// if (empty($theme_name)) { $msg .= $text['message-required']." ".$text['label-theme_name']."<br>\n"; }
			// if (empty($theme_enabled)) { $msg .= $text['message-required']." ".$text['label-theme_enabled']."<br>\n"; }
			// if (empty($theme_description)) { $msg .= $text['message-required']." ".$text['label-theme_description']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add the theme_uuid
			if (!is_uuid($theme_uuid)) {
				$theme_uuid = uuid();
			}

		//prepare the array
			$array['themes'][0]['theme_uuid'] = $theme_uuid;
			$array['themes'][0]['theme_category'] = $theme_category;
			$array['themes'][0]['theme_name'] = $theme_name;
			$array['themes'][0]['theme_enabled'] = $theme_enabled;
			$array['themes'][0]['theme_description'] = $theme_description;
			$array['theme_settings'] = $theme_settings;

		//save the data
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: theme_edit.php?id='.urlencode($theme_uuid));
				return;
			}

	}

//pre-populate the form
	if (is_array($_GET) && empty($_POST["persistformvar"])) {
		$sql = "select ";
		$sql .= " theme_uuid, ";
		$sql .= " theme_category, ";
		$sql .= " theme_name, ";
		$sql .= " theme_enabled , ";
		$sql .= " theme_description ";
		$sql .= "from v_themes ";
		$sql .= "where theme_uuid = :theme_uuid ";
		$parameters['theme_uuid'] = $theme_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$theme_category = $row["theme_category"];
			$theme_name = $row["theme_name"];
			$theme_enabled = $row["theme_enabled"];
			$theme_description = $row["theme_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the list
	$sql = "select ";
	$sql .= "theme_uuid, ";
	$sql .= "theme_setting_uuid, ";
	$sql .= "theme_setting_name, ";
	$sql .= "theme_setting_type, ";
	$sql .= "theme_setting_value, ";
	$sql .= "cast(theme_setting_enabled as text), ";
	$sql .= "theme_setting_description ";
	$sql .= "from v_theme_settings ";
	$sql .= "where theme_uuid = :theme_uuid ";
	$parameters['theme_uuid'] = $theme_uuid;
	$sql .= order_by($order_by, $order, 'theme_setting_name', 'asc');
	$theme_settings = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//set the defaults
	$theme_enabled = $theme_enabled ?? true;

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-theme'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input class='formfld' type='hidden' name='theme_uuid' value='".escape($theme_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-theme']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('theme_setting_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$settings->get('theme', 'button_icon_settings'),'id'=>'btn_back','style'=>'margin-right: 2px;','link'=>PROJECT_PATH.'/core/themes/theme_settings.php?id='.$theme_uuid]);
	}
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'themes.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-themes']."\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_category' maxlength='255' value='".escape($theme_category)."'>\n";
	echo "<br />\n";
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_name' maxlength='255' value='".escape($theme_name)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='theme_enabled' name='theme_enabled'>\n";
	echo "		<option value='true' ".($theme_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($theme_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-theme_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-theme_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='theme_description' maxlength='255' value='".escape($theme_description)."'>\n";
	echo "<br />\n";
	echo $text['description-theme_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";

	if (!empty($theme_settings) && is_array($theme_settings) && @sizeof($theme_settings) != 0) {
		echo "<div class='action_bar' id='action_bar'>\n";
		echo "	<div class='actions'>\n";
		if (permission_exists('theme_setting_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','name'=>'btn_add','link'=>'theme_setting_edit.php?theme_uuid='.$theme_uuid]);
		}
		echo "	</div>\n";
		echo "	<div style='clear: both;'></div>\n";
		echo "</div>\n";

		echo "<div class='card'>\n";
		echo "<table class='list'>\n";
		$x = 0;
		$previous_category = '';
		foreach ($theme_settings as $row) {
			if (empty($row['theme_setting_name'])) {
				continue;
			}
			$label = ucwords(str_replace('_', ' ', $row['theme_setting_name'] ?? ''));
			$category = explode(' ', $label)[0];
			if ($previous_category != $category) {
				echo "<tr>\n";
				echo "<td colspan='7' class='no-link'>\n";
				echo ($previous_category != '' ? '<br />' : null)."<b>".escape($category)."</b>";
				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "<tr>\n";
			echo "<td class='vncell no-link' width='32%' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".escape(str_replace($category, '', $label))."\n";
			echo "</td>\n";
			echo "<td class='vtable no-link' style='position: relative;' align='left'>\n";
			echo "	<input type='hidden' name='theme_settings[$x][theme_uuid]' value='".escape($row['theme_uuid'])."'>\n";
			echo "	<input type='hidden' name='theme_settings[$x][theme_setting_uuid]' value='".escape($row['theme_setting_uuid'])."'>\n";
			echo "	<input type='hidden' name='theme_settings[$x][theme_setting_name]' value='".escape($row['theme_setting_name'])."'>\n";
			if (str_starts_with($row['theme_setting_value'], "rgb") || str_starts_with($row['theme_setting_value'], "#")) {
				echo "	<input type='text' class='formfld colorpicker' id='colorpicker_$x' name='theme_settings[$x][theme_setting_value]' value=\"".escape($row['theme_setting_value'])."\">\n";
				echo "	<div id='color_$x' style='display: inline-block; width: 15px; height: 15px; background: ".escape($row['theme_setting_value'])."; margin-right: 4px; vertical-align: middle; border: 1px solid ".(color_adjust($row['theme_setting_value'], -0.18))."; padding: -1px;'></div>\n";
				echo "	<script>\n";
				echo "	$('#colorpicker_$x').on('changeColor', function (event) {\n";
				echo "		document.getElementById('color_$x').style.background = event.color.toHex();\n";
				echo "	});\n";
				echo "	</script>\n";
			} else {
				echo "	<input type='text' class='formfld' name='theme_settings[$x][theme_setting_value]' value=\"".escape($row['theme_setting_value'])."\">\n";
			}
			echo "<br />\n";
			// echo ($row['theme_setting_description'] ?? '')."\n";
			echo "</td>\n";
			echo "</tr>\n";
			$previous_category = $category;
			$x++;
		}
		unset($theme_settings);
		echo "</table>\n";
		echo "</div>\n";
	}

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
