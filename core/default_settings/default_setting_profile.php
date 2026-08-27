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
	if (!permission_exists('default_setting_profile')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the domain details
	$domain_name = $_SESSION['domain_name'];
	$domain_uuid = $_SESSION['domain_uuid'];

//get all language codes from database
	$sql = "select * from v_languages order by language asc ";
	$languages = $database->select($sql, null, 'all');

//get default settings
	$sql = "select * from v_default_settings ";
	$result = $database->select($sql, null, 'all');
	$default_settings = [];
	if (is_array($result)) {
		foreach($result as $row) {
			$category = $row['default_setting_category'];
			$subcategory = $row['default_setting_subcategory'];
			if (empty($subcategory)) {
				//$$category[$name] = $row['default_setting_value'];
				$default_settings[$category] = $row;
			}
			else {
				$default_settings[$category][$subcategory] = $row;
			}
		}
	}
	unset($sql, $parameters, $result, $row);

//process the http post
	if (!empty($_POST)) {
		//get the HTTP values and set as variables
			$global_language = $_POST["global_language"];
			$global_time_zone = $_POST["global_time_zone"];
			$global_time_format = $_POST["global_time_format"];
			$global_menu_style = $_POST["global_menu_style"];

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: users.php');
				exit;
			}

		//check to see if domain language is set
			$row = $default_settings['domain']['language'] ?? [];
			$i = 0;
			if (!empty($global_language) && (empty($row) || (!empty($row['default_setting_uuid']) && !is_uuid($row['default_setting_uuid'])))) {
				//add user setting to array for insert
				$array['default_settings'][$i]['default_setting_uuid'] = uuid();
				$array['default_settings'][$i]['default_setting_category'] = 'domain';
				$array['default_settings'][$i]['default_setting_subcategory'] = 'language';
				$array['default_settings'][$i]['default_setting_name'] = 'code';
				$array['default_settings'][$i]['default_setting_value'] = $global_language;
				$array['default_settings'][$i]['default_setting_enabled'] = 'true';
				$array['default_settings'][$i]['default_setting_description'] = '';
				$i++;
			}
			else {
				if (!empty($row['default_setting_uuid']) && (empty($row['default_setting_value']) || empty($global_language))) {
					$array_delete['default_settings'][0]['default_setting_category'] = 'domain';
					$array_delete['default_settings'][0]['default_setting_subcategory'] = 'language';
					$array_delete['default_settings'][0]['default_setting_uuid'] = $row['default_setting_uuid'];

					$p = permissions::new();
					$p->add('default_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('default_setting_delete', 'temp');
				}
				if (!empty($global_language)) {
					//add user setting to array for update
					$array['default_settings'][$i]['default_setting_uuid'] = $row['default_setting_uuid'];
					$array['default_settings'][$i]['default_setting_category'] = 'domain';
					$array['default_settings'][$i]['default_setting_subcategory'] = 'language';
					$array['default_settings'][$i]['default_setting_name'] = 'code';
					$array['default_settings'][$i]['default_setting_value'] = $global_language;
					$array['default_settings'][$i]['default_setting_enabled'] = 'true';
					$array['default_settings'][$i]['default_setting_description'] = $row['default_setting_description'] ?? '';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//update switch timezone variables
			if (permission_exists('dialplan_view')) {
				//get the dialplan_uuid using the global-variables app_uuid
					$sql = "select dialplan_uuid from v_dialplans ";
					$sql .= "where app_uuid = 'd49ee3bd-5085-4619-a2f9-2b62c8c461c5' ";
					$dialplan_uuid = $database->select($sql, null, 'column');
					unset($sql, $parameters);

				//get the action
					$sql = "select dialplan_detail_uuid from v_dialplan_details ";
					$sql .= "where dialplan_uuid = :dialplan_uuid ";
					$sql .= "and dialplan_detail_tag = 'action' ";
					$sql .= "and dialplan_detail_type = 'set' ";
					$sql .= "and dialplan_detail_data like 'timezone=%' ";
					$parameters['dialplan_uuid'] = $dialplan_uuid;
					$dialplan_detail_uuid = $database->select($sql, $parameters, 'column');
					$detail_action = is_uuid($dialplan_detail_uuid) ? 'update' : 'add';
					unset($sql, $parameters);

				//update the timezone
					$p = permissions::new();
					if ($detail_action == "update") {
						$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
						$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$global_time_zone;
						$array['dialplan_details'][0]['dialplan_detail_enabled'] = 'true';
						$p->add('dialplan_detail_edit', 'temp');
					}
					else {
						$array['dialplan_details'][0]['dialplan_detail_uuid'] = uuid();
						$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplan_details'][0]['dialplan_detail_tag'] = 'action';
						$array['dialplan_details'][0]['dialplan_detail_type'] = 'set';
						$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$global_time_zone;
						$array['dialplan_details'][0]['dialplan_detail_inline'] = 'true';
						$array['dialplan_details'][0]['dialplan_detail_group'] = '0';
						$array['dialplan_details'][0]['dialplan_detail_order'] = '20';
						$array['dialplan_details'][0]['dialplan_detail_enabled'] = 'true';
						$p->add('dialplan_detail_add', 'temp');
					}

				//get the dialplan uuid
					$sql = "select domain_name from v_domains ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$domain_name = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

				//update the dialplan xml
					$dialplans = new dialplan;
					$dialplans->source = "details";
					$dialplans->destination = "database";
					$dialplans->uuid = $dialplan_uuid;
					$dialplans->xml();

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$domain_name);
			}

		//check to see if domain time zone is set
			$row = $default_settings['domain']['time_zone'] ?? [];
			if (!empty($global_time_zone) && (empty($row) || (!empty($row['default_setting_uuid']) && !is_uuid($row['default_setting_uuid'])))) {
				//add user setting to array for insert
				$array['default_settings'][$i]['default_setting_uuid'] = uuid();
				$array['default_settings'][$i]['default_setting_category'] = 'domain';
				$array['default_settings'][$i]['default_setting_subcategory'] = 'time_zone';
				$array['default_settings'][$i]['default_setting_name'] = 'text';
				$array['default_settings'][$i]['default_setting_value'] = $global_time_zone;
				$array['default_settings'][$i]['default_setting_enabled'] = 'true';
				$array['default_settings'][$i]['default_setting_description'] = '';
				$i++;
			}
			else {
				if (!empty($row['default_setting_uuid']) && (empty($row['default_setting_value']) || empty($global_time_zone))) {
					$array_delete['default_settings'][0]['default_setting_category'] = 'domain';
					$array_delete['default_settings'][0]['default_setting_subcategory'] = 'time_zone';
					$array_delete['default_settings'][0]['default_setting_uuid'] = $row['default_setting_uuid'];

					$p = permissions::new();
					$p->add('default_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('default_setting_delete', 'temp');
				}
				if (!empty($global_time_zone)) {
					//add user setting to array for update
					$array['default_settings'][$i]['default_setting_uuid'] = $row['default_setting_uuid'];
					$array['default_settings'][$i]['default_setting_category'] = 'domain';
					$array['default_settings'][$i]['default_setting_subcategory'] = 'time_zone';
					$array['default_settings'][$i]['default_setting_name'] = 'text';
					$array['default_settings'][$i]['default_setting_value'] = $global_time_zone;
					$array['default_settings'][$i]['default_setting_enabled'] = 'true';
					$array['default_settings'][$i]['default_setting_description'] = $row['default_setting_description'] ?? '';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//check to see if global time_format is set
			$row = $default_settings['domain']['time_format'] ?? [];
			if (!empty($global_time_format) && (empty($row) || (!empty($row['default_setting_uuid']) && !is_uuid($row['default_setting_uuid'])))) {
				//add user setting to array for insert
				$array['default_settings'][$i]['default_setting_uuid'] = uuid();
				$array['default_settings'][$i]['default_setting_category'] = 'domain';
				$array['default_settings'][$i]['default_setting_subcategory'] = 'time_format';
				$array['default_settings'][$i]['default_setting_name'] = 'text';
				$array['default_settings'][$i]['default_setting_value'] = $global_time_format;
				$array['default_settings'][$i]['default_setting_enabled'] = 'true';
				$array['default_settings'][$i]['default_setting_description'] = 'Toggle between 24 hour and 12 hour time formats. Default is 12 hour when disabled.';
				$i++;
			}
			else {
				if (!empty($row['default_setting_uuid']) && (empty($row['default_setting_value']) || empty($global_time_format))) {
					$array_delete['default_settings'][0]['default_setting_category'] = 'domain';
					$array_delete['default_settings'][0]['default_setting_subcategory'] = 'time_format';
					$array_delete['default_settings'][0]['default_setting_uuid'] = $row['default_setting_uuid'];

					$p = permissions::new();
					$p->add('default_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('default_setting_delete', 'temp');
				}
				if (!empty($global_time_format)) {
					//add user setting to array for update
					$array['default_settings'][$i]['default_setting_uuid'] = $row['default_setting_uuid'];
					$array['default_settings'][$i]['default_setting_category'] = 'domain';
					$array['default_settings'][$i]['default_setting_subcategory'] = 'time_format';
					$array['default_settings'][$i]['default_setting_name'] = 'text';
					$array['default_settings'][$i]['default_setting_value'] = $global_time_format;
					$array['default_settings'][$i]['default_setting_enabled'] = 'true';
					$array['default_settings'][$i]['default_setting_description'] = $row['default_setting_description'] ?? '';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//check to see if global menu_style is set
			$row = $default_settings['theme']['menu_style'] ?? [];
			if (!empty($global_menu_style) && (empty($row) || (!empty($row['default_setting_uuid']) && !is_uuid($row['default_setting_uuid'])))) {
				//add user setting to array for insert
				$array['default_settings'][$i]['default_setting_uuid'] = uuid();
				$array['default_settings'][$i]['default_setting_category'] = 'theme';
				$array['default_settings'][$i]['default_setting_subcategory'] = 'menu_style';
				$array['default_settings'][$i]['default_setting_name'] = 'text';
				$array['default_settings'][$i]['default_setting_value'] = $global_menu_style;
				$array['default_settings'][$i]['default_setting_enabled'] = 'true';
				$array['default_settings'][$i]['default_setting_description'] = 'Set the style of the main menu.';
				$i++;
			}
			else {
				if (!empty($row['default_setting_uuid']) && (empty($row['default_setting_value']) || empty($global_menu_style))) {
					$array_delete['default_settings'][0]['default_setting_category'] = 'theme';
					$array_delete['default_settings'][0]['default_setting_subcategory'] = 'menu_style';
					$array_delete['default_settings'][0]['default_setting_uuid'] = $row['default_setting_uuid'];

					$p = permissions::new();
					$p->add('default_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('default_setting_delete', 'temp');
				}
				if (!empty($global_menu_style)) {
					//add user setting to array for update
					$array['default_settings'][$i]['default_setting_uuid'] = $row['default_setting_uuid'];
					$array['default_settings'][$i]['default_setting_category'] = 'theme';
					$array['default_settings'][$i]['default_setting_subcategory'] = 'menu_style';
					$array['default_settings'][$i]['default_setting_name'] = 'text';
					$array['default_settings'][$i]['default_setting_value'] = $global_menu_style;
					$array['default_settings'][$i]['default_setting_enabled'] = 'true';
					$array['default_settings'][$i]['default_setting_description'] = $row['default_setting_description'] ?? '';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//initialize the permissing object
			$p = permissions::new();

		//add temporary permissions
			$p->add("default_setting_add", "temp");
			$p->add("default_setting_edit", "temp");

		//save the data
			if (!empty($array)) {
				$database->save($array);
				//$message = $database->message;
			}

		//remove the temporary permissions
			$p->delete("default_setting_add", "temp");
			$p->delete("default_setting_edit", "temp");

		//clear the menu
			unset($_SESSION["menu"]);

		//get settings based on the user
			$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid']]);
			settings::clear_cache();

		//response message
			message::add($text['message-update'],'positive');

		//redirect
			header('Location: default_setting_profile.php');
			exit;
	}

//populate form
	if (persistent_form_values('exists')) {
		//populate the form with values from session variable
			persistent_form_values('load');
		//clear, set $unsaved flag
			persistent_form_values('clear');
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-global_profile'];

//show the content
	echo "<form name='frm' id='frm' method='post' enctype='multipart/form-data'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-global_profile']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if (!empty($unsaved)) {
		echo "<div class='unsaved'>".$text['message-unsaved_changes']." <i class='fas fa-exclamation-triangle'></i></div>";
	}
	if (permission_exists('default_setting_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$settings->get('theme', 'button_icon_settings'),'id'=>'btn_back','style'=>'margin-right: 2px;','link'=>PROJECT_PATH.'/core/default_settings/default_settings.php']);
	}
	$button_margin = 'margin-left: 15px;';
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>'submit_form();']);

	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-default_setting_profile']."\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%' class='mb-4'>";

	// echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	// echo "	<tr>";
	// echo "		<td class='vncellreq'>".$text['label-email']."</td>";
	// echo "		<td class='vtable'><input type='text' class='formfld' name='user_email' value='".escape($user_email ?? '')."' required='required'></td>";
	// echo "	</tr>";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-language']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='global_language' name='global_language' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	if (!empty($languages) && is_array($languages) && sizeof($languages) != 0) {
		foreach ($languages as $row) {
			$language_codes[$row["code"]] = $row["language"];
		}
	}
	unset($sql, $languages, $row);
	if (is_array($_SESSION['app']['languages']) && sizeof($_SESSION['app']['languages']) != 0) {
		foreach ($_SESSION['app']['languages'] as $code) {
			$selected = (isset($global_language) && $code == $global_language) || (!empty($default_settings['domain']['language']['default_setting_enabled']) && $code == $default_settings['domain']['language']['default_setting_value']) ? "selected" : null;
			echo "	<option value='".$code."' ".$selected.">".escape($language_codes[$code] ?? $language_codes[explode('-', $code)[0]] ?? null)." [".escape($code ?? null)."]</option>\n";
		}
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-domain_language']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-time_zone']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='global_time_zone' name='global_time_zone' class='formfld searchable_select' style=''>\n";
	echo "		<option value=''></option>\n";
	//$list = DateTimeZone::listAbbreviations();
	$time_zone_identifiers = DateTimeZone::listIdentifiers();
	$previous_category = '';
	$x = 0;
	foreach ($time_zone_identifiers as $key => $row) {
		$time_zone = explode("/", $row);
		$category = $time_zone[0];
		if ($category != $previous_category) {
			if ($x > 0) {
				echo "		</optgroup>\n";
			}
			echo "		<optgroup label='".$category."'>\n";
		}
		$selected = (isset($global_time_zone) && $row == $global_time_zone) || (!empty($default_settings['domain']['time_zone']['default_setting_enabled']) && $row == $default_settings['domain']['time_zone']['default_setting_value']) ? "selected" : null;
		echo "			<option value='".escape($row)."' ".$selected.">".escape($row)."</option>\n";
		$previous_category = $category;
		$x++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-time_zone']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-time_format']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "	<select class='formfld' id='global_time_format' name='global_time_format'>\n";
	echo "	 	<option value=''></option>\n";
	echo "	 	<option value='12h' ".((!empty($default_settings['domain']['time_format']['default_setting_enabled']) && $default_settings['domain']['time_format']['default_setting_value'] == "12h") ? "selected" : null).">".$text['label-12-hour']."</option>\n";
	echo "		<option value='24h' ".((!empty($default_settings['domain']['time_format']['default_setting_enabled']) && $default_settings['domain']['time_format']['default_setting_value'] == "24h") ? "selected" : null).">".$text['label-24-hour']."</option>\n";
	echo "	</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-time_format']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-menu_style']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select class='formfld' id='global_menu_style' name='global_menu_style'>\n";
	echo "			<option value='fixed' ".((!empty($default_settings['theme']['menu_style']['default_setting_value']) && $default_settings['theme']['menu_style']['default_setting_value'] == "fixed") ? "selected='selected'" : null).">".$text['label-fixed']."</option>\n";
	echo "			<option value='static' ".((!empty($default_settings['theme']['menu_style']['default_setting_value']) && $default_settings['theme']['menu_style']['default_setting_value'] == "static") ? "selected='selected'" : null).">".$text['label-static']."</option>\n";
	echo "			<option value='inline' ".((!empty($default_settings['theme']['menu_style']['default_setting_value']) && $default_settings['theme']['menu_style']['default_setting_value'] == "inline") ? "selected='selected'" : null).">".$text['label-inline']."</option>\n";
	echo "			<option value='side' ".((!empty($default_settings['theme']['menu_style']['default_setting_value']) && $default_settings['theme']['menu_style']['default_setting_value'] == "side") ? "selected='selected'" : null).">".$text['label-side']."</option>\n";
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-menu_style']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";


	echo "</table>";
	echo "</div>\n";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//hide password fields before submit
	echo "<script>\n";
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
