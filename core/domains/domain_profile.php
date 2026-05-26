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
	if (!permission_exists('domain_profile')) {
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

//get domain settings

	$sql = "select * from v_domain_settings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and domain_setting_enabled = true ";
	$parameters['domain_uuid'] = $domain_uuid;
	$result = $database->select($sql, $parameters, 'all');
	$domain_settings = [];
	if (is_array($result)) {
		foreach($result as $row) {
			$category = $row['domain_setting_category'];
			$subcategory = $row['domain_setting_subcategory'];
			if (empty($subcategory)) {
				//$$category[$name] = $row['domain_setting_value'];
				$domain_settings[$category] = $row;
			}
			else {
				$domain_settings[$category][$subcategory] = $row;
			}
		}
	}
	unset($sql, $parameters, $result, $row);

//process the http post
	if (!empty($_POST)) {
		//get the HTTP values and set as variables
			$domain_language = $_POST["domain_language"];
			$domain_time_zone = $_POST["domain_time_zone"];
			$domain_time_format = $_POST["domain_time_format"];

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: users.php');
				exit;
			}

		//check to see if domain language is set
			$row = $domain_settings['domain']['language'] ?? [];
			if (!empty($domain_language) && (empty($row) || (!empty($row['domain_setting_uuid']) && !is_uuid($row['domain_setting_uuid'])))) {
				//add user setting to array for insert
				$array['domain_settings'][$i]['domain_setting_uuid'] = uuid();
				$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
				$array['domain_settings'][$i]['domain_setting_category'] = 'domain';
				$array['domain_settings'][$i]['domain_setting_subcategory'] = 'language';
				$array['domain_settings'][$i]['domain_setting_name'] = 'code';
				$array['domain_settings'][$i]['domain_setting_value'] = $domain_language;
				$array['domain_settings'][$i]['domain_setting_enabled'] = 'true';
				$i++;
			}
			else {
				if (empty($row['domain_setting_value']) || empty($domain_language)) {
					$array_delete['domain_settings'][0]['domain_setting_category'] = 'domain';
					$array_delete['domain_settings'][0]['domain_setting_subcategory'] = 'language';
					$array_delete['domain_settings'][0]['domain_uuid'] = $domain_uuid;

					$p = permissions::new();
					$p->add('domain_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('domain_setting_delete', 'temp');
				}
				if (!empty($domain_language)) {
					//add user setting to array for update
					$array['domain_settings'][$i]['domain_setting_uuid'] = $row['domain_setting_uuid'];
					$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['domain_settings'][$i]['domain_setting_category'] = 'domain';
					$array['domain_settings'][$i]['domain_setting_subcategory'] = 'language';
					$array['domain_settings'][$i]['domain_setting_name'] = 'code';
					$array['domain_settings'][$i]['domain_setting_value'] = $domain_language;
					$array['domain_settings'][$i]['domain_setting_enabled'] = 'true';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//update switch timezone variables
			if (permission_exists('dialplan_view')) {
				//get the dialplan_uuid
					$sql = "select dialplan_uuid from v_dialplans ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and app_uuid = '9f356fe7-8cf8-4c14-8fe2-6daf89304458' ";
					$parameters['domain_uuid'] = $domain_uuid;
					$dialplan_uuid = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

				//get the action
					$sql = "select dialplan_detail_uuid from v_dialplan_details ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and dialplan_uuid = :dialplan_uuid ";
					$sql .= "and dialplan_detail_tag = 'action' ";
					$sql .= "and dialplan_detail_type = 'set' ";
					$sql .= "and dialplan_detail_data like 'timezone=%' ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['dialplan_uuid'] = $dialplan_uuid;
					$dialplan_detail_uuid = $database->select($sql, $parameters, 'column');
					$detail_action = is_uuid($dialplan_detail_uuid) ? 'update' : 'add';
					unset($sql, $parameters);

				//update the timezone
					$p = permissions::new();
					if ($detail_action == "update") {
						$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
						$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$domain_time_zone;
						$array['dialplan_details'][0]['dialplan_detail_enabled'] = 'true';
						$p->add('dialplan_detail_edit', 'temp');
					}
					else {
						$array['dialplan_details'][0]['dialplan_detail_uuid'] = uuid();
						$array['dialplan_details'][0]['domain_uuid'] = $domain_uuid;
						$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplan_details'][0]['dialplan_detail_tag'] = 'action';
						$array['dialplan_details'][0]['dialplan_detail_type'] = 'set';
						$array['dialplan_details'][0]['dialplan_detail_data'] = 'timezone='.$domain_time_zone;
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
			$row = $domain_settings['domain']['time_zone'];
			if (!empty($domain_time_zone) && (empty($row) || (!empty($row['domain_setting_uuid']) && !is_uuid($row['domain_setting_uuid'])))) {
				//add user setting to array for insert
				$array['domain_settings'][$i]['domain_setting_uuid'] = uuid();
				$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
				$array['domain_settings'][$i]['domain_setting_category'] = 'domain';
				$array['domain_settings'][$i]['domain_setting_subcategory'] = 'time_zone';
				$array['domain_settings'][$i]['domain_setting_name'] = 'name';
				$array['domain_settings'][$i]['domain_setting_value'] = $domain_time_zone;
				$array['domain_settings'][$i]['domain_setting_enabled'] = 'true';
				$i++;
			}
			else {
				if (empty($row['domain_setting_value']) || empty($domain_time_zone)) {
					$array_delete['domain_settings'][0]['domain_setting_category'] = 'domain';
					$array_delete['domain_settings'][0]['domain_setting_subcategory'] = 'time_zone';
					$array_delete['domain_settings'][0]['domain_uuid'] = $domain_uuid;

					$p = permissions::new();
					$p->add('domain_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('domain_setting_delete', 'temp');
				}
				if (!empty($domain_time_zone)) {
					//add user setting to array for update
					$array['domain_settings'][$i]['domain_setting_uuid'] = $row['domain_setting_uuid'];
					$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['domain_settings'][$i]['domain_setting_category'] = 'domain';
					$array['domain_settings'][$i]['domain_setting_subcategory'] = 'time_zone';
					$array['domain_settings'][$i]['domain_setting_name'] = 'name';
					$array['domain_settings'][$i]['domain_setting_value'] = $domain_time_zone;
					$array['domain_settings'][$i]['domain_setting_enabled'] = 'true';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//check to see if domain time_format is set
			$row = $domain_settings['domain']['time_format'] ?? [];
			if (!empty($domain_language) && (empty($row) || (!empty($row['domain_setting_uuid']) && !is_uuid($row['domain_setting_uuid'])))) {
				//add user setting to array for insert
				$array['domain_settings'][$i]['domain_setting_uuid'] = uuid();
				$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
				$array['domain_settings'][$i]['domain_setting_category'] = 'domain';
				$array['domain_settings'][$i]['domain_setting_subcategory'] = 'time_format';
				$array['domain_settings'][$i]['domain_setting_name'] = 'text';
				$array['domain_settings'][$i]['domain_setting_value'] = $domain_time_format;
				$array['domain_settings'][$i]['domain_setting_enabled'] = 'true';
				$i++;
			}
			else {
				if (empty($row['domain_setting_value']) || empty($domain_time_format)) {
					$array_delete['domain_settings'][0]['domain_setting_category'] = 'domain';
					$array_delete['domain_settings'][0]['domain_setting_subcategory'] = 'time_format';
					$array_delete['domain_settings'][0]['domain_uuid'] = $domain_uuid;

					$p = permissions::new();
					$p->add('domain_setting_delete', 'temp');

					$database->delete($array_delete);
					unset($array_delete);

					$p->delete('domain_setting_delete', 'temp');
				}
				if (!empty($domain_time_format)) {
					//add user setting to array for update
					$array['domain_settings'][$i]['domain_setting_uuid'] = $row['domain_setting_uuid'];
					$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['domain_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['domain_settings'][$i]['domain_setting_category'] = 'domain';
					$array['domain_settings'][$i]['domain_setting_subcategory'] = 'time_format';
					$array['domain_settings'][$i]['domain_setting_name'] = 'text';
					$array['domain_settings'][$i]['domain_setting_value'] = $domain_time_format;
					$array['domain_settings'][$i]['domain_setting_enabled'] = 'true';
					$i++;
				}
			}
			unset($sql, $parameters, $row);

		//initialize the permissing object
			$p = permissions::new();

		//add temporary permissions
			$p->add("domain_setting_add", "temp");
			$p->add("domain_setting_edit", "temp");

		//save the data
			if (!empty($array)) {
				$database->save($array);
				//$message = $database->message;
			}

		//remove the temporary permissions
			$p->delete("domain_setting_add", "temp");
			$p->delete("domain_setting_edit", "temp");

		//clear the menu
			unset($_SESSION["menu"]);

		//get settings based on the user
			$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid']]);
			settings::clear_cache();

		//response message
			message::add($text['message-update'],'positive');

		//redirect
			header('Location: domain_profile.php');
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
	$document['title'] = $text['title-domain_profile'];

//show the content
	echo "<form name='frm' id='frm' method='post' enctype='multipart/form-data'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-domain_profile']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if (!empty($unsaved)) {
		echo "<div class='unsaved'>".$text['message-unsaved_changes']." <i class='fas fa-exclamation-triangle'></i></div>";
	}

	$button_margin = 'margin-left: 15px;';
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','style'=>'margin-left: 15px;','onclick'=>'submit_form();']);

	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-domain_profile']."\n";
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
	echo "		<select id='domain_language' name='domain_language' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	if (!empty($languages) && is_array($languages) && sizeof($languages) != 0) {
		foreach ($languages as $row) {
			$language_codes[$row["code"]] = $row["language"];
		}
	}
	unset($sql, $languages, $row);
	if (is_array($_SESSION['app']['languages']) && sizeof($_SESSION['app']['languages']) != 0) {
		foreach ($_SESSION['app']['languages'] as $code) {
			$selected = (isset($domain_language) && $code == $domain_language) || (isset($domain_settings['domain']['language']['domain_setting_value']) && $code == $domain_settings['domain']['language']['domain_setting_value']) ? "selected='selected'" : null;
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
	echo "		<select id='domain_time_zone' name='domain_time_zone' class='formfld' style=''>\n";
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
		$selected = (isset($domain_time_zone) && $row == $domain_time_zone) || (!empty($domain_settings['domain']['time_zone']['domain_setting_value']) && $row == $domain_settings['domain']['time_zone']['domain_setting_value']) ? "selected='selected'" : null;
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
	echo "	<select class='formfld' id='domain_time_format' name='domain_time_format'>\n";
	echo "	 	<option value=''></option>\n";
	echo "	 	<option value='12h' ".(($domain_settings['domain']['time_format']['domain_setting_value'] == "12h") ? "selected='selected'" : null).">".$text['label-12-hour']."</option>\n";
	echo "		<option value='24h' ".(($domain_settings['domain']['time_format']['domain_setting_value'] == "24h") ? "selected='selected'" : null).">".$text['label-24-hour']."</option>\n";
	echo "	</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-time_format']."<br />\n";
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
