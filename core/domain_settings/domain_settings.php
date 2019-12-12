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
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check prmissions
	if (permission_exists('domain_setting_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get posted values, if any
	if (is_array($_REQUEST) && @sizeof($_REQUEST) > 1) {
		//get the variables
			$action = $_REQUEST["action"];
			$domain_uuid = $_REQUEST["domain_uuid"];
			$domain_setting_uuids = $_REQUEST["id"];
			$enabled = $_REQUEST['enabled'];

		//validate the token
			$token = new token;
			if (!$token->validate(PROJECT_PATH."/core/domains/domain_edit.php")) {
				message::add($text['message-invalid_token'],'negative');
				header("Location: ".PROJECT_PATH."/core/domains/domains.php");
				exit;
			}

		//copy settings
			if ($action == 'copy') {
				if (permission_exists('domain_select') && count($_SESSION['domains']) > 1) {
					$target_domain_uuid = $_POST["target_domain_uuid"];

					//to different domain
						if (is_uuid($target_domain_uuid)) {

							if (is_array($domain_setting_uuids) && @sizeof($domain_setting_uuids) > 0) {
								$settings_copied = 0;
								foreach ($domain_setting_uuids as $domain_setting_uuid) {

									if (is_uuid($domain_setting_uuid)) {

										//get domain setting from db
										$sql = "select * from v_domain_settings ";
										$sql .= "where domain_setting_uuid = :domain_setting_uuid ";
										$parameters['domain_setting_uuid'] = $domain_setting_uuid;
										$database = new database;
										$row = $database->select($sql, $parameters, 'row');
										if (is_array($row) && sizeof($row) != 0) {
											$domain_setting_category = $row["domain_setting_category"];
											$domain_setting_subcategory = $row["domain_setting_subcategory"];
											$domain_setting_name = $row["domain_setting_name"];
											$domain_setting_value = $row["domain_setting_value"];
											$domain_setting_order = $row["domain_setting_order"];
											$domain_setting_enabled = $row["domain_setting_enabled"];
											$domain_setting_description = $row["domain_setting_description"];
										}
										unset($sql, $parameters, $row);

										//set a random password for http_auth_password
										if ($domain_setting_subcategory == "http_auth_password") {
											$domain_setting_value = generate_password();
										}

										// check if exists
										$sql = "select domain_setting_uuid from v_domain_settings ";
										$sql .= "where domain_uuid = :domain_uuid ";
										$sql .= "and domain_setting_category = :domain_setting_category ";
										$sql .= "and domain_setting_subcategory = :domain_setting_subcategory ";
										$sql .= "and domain_setting_name = :domain_setting_name ";
										$sql .= "and domain_setting_name <> 'array' ";
										$parameters['domain_uuid'] = $target_domain_uuid;
										$parameters['domain_setting_category'] = $domain_setting_category;
										$parameters['domain_setting_subcategory'] = $domain_setting_subcategory;
										$parameters['domain_setting_name'] = $domain_setting_name;
										$database = new database;
										$target_domain_setting_uuid = $database->select($sql, $parameters, 'column');

										$action = is_uuid($target_domain_setting_uuid) ? 'update' : 'add';
										unset($sql, $parameters);

										// fix null
										$domain_setting_order = $domain_setting_order != '' ? $domain_setting_order : null;

										//begin array
										$array['domain_settings'][0]['domain_uuid'] = $target_domain_uuid;
										$array['domain_settings'][0]['domain_setting_category'] = $domain_setting_category;
										$array['domain_settings'][0]['domain_setting_subcategory'] = $domain_setting_subcategory;
										$array['domain_settings'][0]['domain_setting_name'] = $domain_setting_name;
										$array['domain_settings'][0]['domain_setting_value'] = $domain_setting_value;
										$array['domain_settings'][0]['domain_setting_order'] = $domain_setting_order;
										$array['domain_settings'][0]['domain_setting_enabled'] = $domain_setting_enabled;
										$array['domain_settings'][0]['domain_setting_description'] = $domain_setting_description;

										//insert
										if ($action == "add" && permission_exists("domain_setting_add")) {
											$array['domain_settings'][0]['domain_setting_uuid'] = uuid();
										}
										//update
										if ($action == "update" && permission_exists('domain_setting_edit')) {
											$array['domain_settings'][0]['domain_setting_uuid'] = $target_domain_setting_uuid;
										}

										//execute
										if (is_uuid($array['domain_settings'][0]['domain_setting_uuid'])) {
											$database = new database;
											$database->app_name = 'domain_settings';
											$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
											$database->save($array);
											unset($array);

											$settings_copied++;
										}

									}

								}

								// set message
								message::add($text['message-copy'].": ".escape($settings_copied));
							}

						}

					//to default settings
						else if ($target_domain_uuid == 'default') {

							if (is_array($domain_setting_uuids) && @sizeof($domain_setting_uuids) > 0) {
								$settings_copied = 0;
								foreach ($domain_setting_uuids as $domain_setting_uuid) {

									if (is_uuid($domain_setting_uuid)) {

										//get domain setting from db
										$sql = "select * from v_domain_settings ";
										$sql .= "where domain_setting_uuid = :domain_setting_uuid ";
										$parameters['domain_setting_uuid'] = $domain_setting_uuid;
										$database = new database;
										$row = $database->select($sql, $parameters, 'row');
										if (is_array($row) && sizeof($row) != 0) {
											$domain_setting_category = $row["domain_setting_category"];
											$domain_setting_subcategory = $row["domain_setting_subcategory"];
											$domain_setting_name = $row["domain_setting_name"];
											$domain_setting_value = $row["domain_setting_value"];
											$domain_setting_order = $row["domain_setting_order"];
											$domain_setting_enabled = $row["domain_setting_enabled"];
											$domain_setting_description = $row["domain_setting_description"];
										}
										unset($sql, $parameters, $row);

										//set a random password for http_auth_password
										if ($domain_setting_subcategory == "http_auth_password") {
											$domain_setting_value = generate_password();
										}

										// check if exists
										$sql = "select default_setting_uuid from v_default_settings ";
										$sql .= "where default_setting_category = :default_setting_category ";
										$sql .= "and default_setting_subcategory = :default_setting_subcategory ";
										$sql .= "and default_setting_name = :default_setting_name ";
										$sql .= "and default_setting_name <> 'array' ";
										$parameters['default_setting_category'] = $domain_setting_category;
										$parameters['default_setting_subcategory'] = $domain_setting_subcategory;
										$parameters['default_setting_name'] = $domain_setting_name;
										$database = new database;
										$target_default_setting_uuid = $database->select($sql, $parameters, 'column');

										$action = is_uuid($target_default_setting_uuid) ? 'update' : 'add';
										unset($sql, $parameters);

										// fix null
										$domain_setting_order = $domain_setting_order != '' ? $domain_setting_order : null;

										//begin array
										$array['default_settings'][0]['default_setting_category'] = $domain_setting_category;
										$array['default_settings'][0]['default_setting_subcategory'] = $domain_setting_subcategory;
										$array['default_settings'][0]['default_setting_name'] = $domain_setting_name;
										$array['default_settings'][0]['default_setting_value'] = $domain_setting_value;
										$array['default_settings'][0]['default_setting_order'] = $domain_setting_order;
										$array['default_settings'][0]['default_setting_enabled'] = $domain_setting_enabled;
										$array['default_settings'][0]['default_setting_description'] = $domain_setting_description;

										//insert
										if ($action == "add" && permission_exists("default_setting_add")) {
											$array['default_settings'][0]['default_setting_uuid'] = uuid();
										}
										//update
										if ($action == "update" && permission_exists('default_setting_edit')) {
											$array['default_settings'][0]['default_setting_uuid'] = $target_default_setting_uuid;
										}

										//execute
										if (is_uuid($array['default_settings'][0]['default_setting_uuid'])) {
											$database = new database;
											$database->app_name = 'domain_settings';
											$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
											$database->save($array);
											unset($array);

											$settings_copied++;
										}

									}

								}

								// set message
								message::add($text['message-copy'].": ".escape($settings_copied));
							}

						}
				}

				header("Location: ".PROJECT_PATH."/core/domains/domain_edit.php?id=".escape($_REQUEST["domain_uuid"]));
				exit;
			}

		//toggle
			$toggled = 0;
			if ($action == 'toggle') {
				if (is_array($domain_setting_uuids) && sizeof($domain_setting_uuids) > 0) {
					foreach ($domain_setting_uuids as $domain_setting_uuid) {
						if (is_uuid($domain_setting_uuid)) {
							//get current status
								$sql = "select domain_setting_enabled from v_domain_settings where domain_setting_uuid = :domain_setting_uuid ";
								$parameters['domain_setting_uuid'] = $domain_setting_uuid;
								$database = new database;
								$domain_setting_enabled = $database->select($sql, $parameters, 'column');
								$new_status = $domain_setting_enabled == 'true' ? 'false' : 'true';
								unset($sql, $parameters);
							//set new status
								$array['domain_settings'][0]['domain_setting_uuid'] = $domain_setting_uuid;
								$array['domain_settings'][0]['domain_setting_enabled'] = $new_status;
								$database = new database;
								$database->app_name = 'domain_settings';
								$database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
								$database->save($array);
								$message = $database->message;
								unset($array);
							//increment toggle total
								$toggled++;
						}
					}
					if ($toggled > 0) {
						message::add($text['message-toggle'].': '.$toggled);
					}
				}

				header("Location: ".PROJECT_PATH."/core/domains/domain_edit.php?id=".escape($_REQUEST["domain_uuid"]));
				exit;
			}

		//delete
			if ($action == 'delete') {
				if (permission_exists('domain_setting_delete')) {
					//add multi-lingual support
						$language = new text;
						$text = $language->get();

					if (is_array($domain_setting_uuids) && sizeof($domain_setting_uuids) != 0) {
						foreach ($domain_setting_uuids as $index => $domain_setting_uuid) {
							if (is_uuid($domain_setting_uuid)) {
								$array['domain_settings'][$index]['domain_setting_uuid'] = $domain_setting_uuid;
							}
						}
						if (is_array($array) && sizeof($array) != 0) {
							$database = new database;
							$database->app_name = 'domain_settings';
							$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
							$database->delete($array);
							$message = $database->message;

							// set message
							$_SESSION["message"] = $text['message-delete'].": ".sizeof($array);

							unset($array);
						}
					}
				}

				header("Location: ".PROJECT_PATH."/core/domains/domain_edit.php?id=".escape($_REQUEST["domain_uuid"]));
				exit;
			}
	}

//include the paging
	require_once "resources/paging.php";

//get the variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//prepare to page the results
	$sql = "select count(*) from v_domain_settings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 1000;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_domain_settings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if ($order_by == '') {
		$sql .= "order by domain_setting_category, domain_setting_subcategory, domain_setting_order asc, domain_setting_name, domain_setting_value ";
	}
	else {
		$sql .= order_by($order_by, $order);
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//copy settings javascript
	if (
		permission_exists("domain_select") &&
		permission_exists("domain_setting_add") &&
		is_array($_SESSION['domains']) &&
		@sizeof($_SESSION['domains']) > 1
		) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	var fade_speed = 400;\n";
		echo "	function show_domains() {\n";
		echo "		$('#button_copy').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_back').fadeIn(fade_speed);\n";
		echo "			$('#target_domain_uuid').fadeIn(fade_speed);\n";
		echo "			$('#button_paste').fadeIn(fade_speed);\n";
		echo "		});";
		echo "	}";
		echo "	function hide_domains() {\n";
		echo "		$('#button_back').fadeOut(fade_speed);\n";
		echo "		$('#target_domain_uuid').fadeOut(fade_speed);\n";
		echo "		$('#button_paste').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_copy').fadeIn(fade_speed);\n";
		echo "			document.getElementById('target_domain_uuid').selectedIndex = 0;\n";
		echo "		});\n";
		echo "	}\n";
		echo "</script>";
	}

//show the content
	echo "<form name='domain_frm' id='domain_frm' method='post' action='".PROJECT_PATH."/core/domain_settings/domain_settings.php'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";

	echo "<div style='float: right;'>\n";
	if (permission_exists('domain_setting_add')) {
		echo "<a href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_uuid=".urlencode($domain_uuid)."' alt=\"".$text['button-add']."\"><button type='button' class='btn btn-default'><span class='fas fa-plus'></span> ".$text['button-add']."</button></a>";
	}
	if (is_array($result) && @sizeof($result) != 0) {
		if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
			echo "		<button type='button' id='button_copy' class='btn btn-default' alt='".$text['button-copy']."' onclick='show_domains();'><span class='fas fa-copy'></span> ".$text['button-copy']."</button>";
			echo "		<button type='button' id='button_back' class='btn btn-default' style='display: none;' alt='".$text['button-back']."' onclick='hide_domains();'><span class='fas fa-undo-alt'></span> ".$text['button-back']."</button>";
			echo "		<select class='formfld' style='display: none; width: auto;' name='target_domain_uuid' id='target_domain_uuid'>\n";
			echo "			<option value=''>".$text['label-domain']."...</option>\n";
			foreach ($_SESSION['domains'] as $domain) {
				if ($domain['domain_uuid'] == $domain_uuid) { continue; }
				echo "		<option value='".escape($domain["domain_uuid"])."'>".escape($domain["domain_name"])."</option>\n";
			}
			if (permission_exists('default_setting_add') && permission_exists('default_setting_edit')) {
				echo "		<option value='' disabled='disabled'></option>\n";
				echo "		<option value='default'>".$text['label-default_settings']."</option>\n";
			}
			echo "		</select>\n";
			echo "		<button type='submit' id='button_paste' name='action' class='btn btn-default' style='display: none;' alt='".$text['button-paste']."' value='copy'><span class='fas fa-paste'></span> ".$text['button-paste']."</button>";
		}
		if (permission_exists('default_setting_edit')) {
			echo "	<button type='submit' name='action' class='btn btn-default' alt=\"".$text['button-toggle']."\" value='toggle' onclick=\"if (!confirm('".$text['confirm-toggle']."')) { this.blur(); return false; }\"><span class='fas fa-toggle-on'></span> ".$text['button-toggle']."</button>";
		}
		if (permission_exists('default_setting_delete')) {
			echo "	<button type='submit' name='action' class='btn btn-default' alt=\"".$text['button-delete']."\" value='delete' onclick=\"if (!confirm('".$text['confirm-delete']."')) { this.blur(); return false; }\"><span class='fas fa-trash'></span> ".$text['button-delete']."</button>";
		}
	}
	if (permission_exists('default_setting_view') && is_array($result) && @sizeof($result) != 0) {
		echo button::create(['type'=>'button','label'=>$text['button-reload'],'icon'=>$_SESSION['theme']['button_icon_reload'],'style'=>'margin-left: 15px;','link'=>PROJECT_PATH.'/core/default_settings/default_settings_reload.php?id='.$domain_uuid]);
	}
	echo "</div>\n";
	echo "<b>".$text['header-domain_settings']."</b>";
	echo "<br><br>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$previous_category = '';
		foreach($result as $row) {
			if ($previous_category != $row['domain_setting_category']) {
				$c = 0;
				echo "<tr>\n";
				echo "	<td colspan='7' align='left' class='tr_link_void'>\n";
				if ($previous_category != '') {
					echo "	<br /><br />\n";
				}
				echo "<b>";
				switch (strtolower($row['domain_setting_category'])) {
					case "api" : echo "API"; break;
					case "cdr" : echo "CDR"; break;
					case "ldap" : echo "LDAP"; break;
					case "ivr_menu" : echo "IVR Menu"; break;
					default: echo escape(ucwords(str_replace("_", " ", $row['domain_setting_category'])));
				}
				echo "</b>\n";
				echo "	</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				if ((permission_exists("domain_select")
					&& permission_exists("domain_setting_add")
					&& count($_SESSION['domains']) > 1) ||
					permission_exists('domain_setting_delete')) {
						echo "<th style='width: 30px; vertical-align: bottom; text-align: center; padding: 0px 3px 2px 8px;'><input type='checkbox' id='chk_all_".escape($row['domain_setting_category'])."' class='chk_all' onchange=\"(this.checked) ? check('all','".strtolower(escape($row['domain_setting_category']))."') : check('none','".strtolower(escape($row['domain_setting_category']))."');\"></th>";
				}
				echo "<th>".$text['label-subcategory']."</th>";
				echo "<th>".$text['label-type']."</th>";
				echo "<th>".$text['label-value']."</th>";
				echo "<th style='text-align: center;'>".$text['label-enabled']."</th>";
				echo "<th>".$text['label-description']."</th>";
				echo "	<td class='tr_link_void' style='width: 1px;'>\n";
				if (permission_exists('domain_setting_add')) {
					echo "<a href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_setting_category=".escape($row['domain_setting_category'])."&domain_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>".$v_button_icon_add."</a>";
				}
				echo "</td>\n";
				echo "</tr>\n";
			}
			$tr_link = (permission_exists('domain_setting_edit')) ? " href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_uuid=".escape($row['domain_uuid'])."&id=".escape($row['domain_setting_uuid'])."'" : null;
			echo "<tr ".$tr_link.">\n";
			if ((permission_exists("domain_select") && permission_exists("domain_setting_add") 
				&& count($_SESSION['domains']) > 1) ||
				permission_exists("domain_setting_delete")) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; padding: 3px 3px 0px 8px;'><input type='checkbox' name='id[]' id='checkbox_".escape($row['domain_setting_uuid'])."' value='".escape($row['domain_setting_uuid'])."' onclick=\"if (!this.checked) { document.getElementById('chk_all_".escape($row['domain_setting_category'])."').checked = false; }\"></td>\n";
				$subcat_ids[strtolower($row['domain_setting_category'])][] = 'checkbox_'.escape($row['domain_setting_uuid']);
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('domain_setting_edit')) {
				echo 	"<a href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_uuid=".escape($row['domain_uuid'])."&id=".escape($row['domain_setting_uuid'])."'>".escape($row['domain_setting_subcategory'])."</a>";
			}
			else {
				echo $row['domain_setting_subcategory'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['domain_setting_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='width: 30%; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>\n";

			$category = $row['domain_setting_category'];
			$subcategory = $row['domain_setting_subcategory'];
			$name = $row['domain_setting_name'];
			if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
				$sql = "select * from v_menus ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$parameters['menu_uuid'] = $row['domain_setting_value'];
				$database = new database;
				$sub_result = $database->select($sql, $parameters, 'all');
				if (is_array($sub_result) && sizeof($sub_result) != 0) {
					foreach ($sub_result as &$sub_row) {
						echo escape($sub_row["menu_language"])." - ".escape($sub_row["menu_name"])."\n";
					}
				}
				unset($sql, $parameters, $sub_result, $sub_row);
			}
			else if ($category == "domain" && $subcategory == "template" && $name == "name" ) {
				echo "		".ucwords($row['domain_setting_value']);
			}
			else if ($category == "domain" && $subcategory == "time_format" && $name == "text" ) {
				switch ($row['domain_setting_value']) {
					case '12h': echo $text['label-12-hour']; break;
					case '24h': echo $text['label-24-hour']; break;
				}
			}
			else if (
				( $category == "theme" && $subcategory == "menu_main_icons" && $name == "boolean" ) ||
				( $category == "theme" && $subcategory == "menu_sub_icons" && $name == "boolean" ) ||
				( $category == "theme" && $subcategory == "menu_brand_type" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "menu_style" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "menu_position" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "logo_align" && $name == "text" )
				) {
				echo "		".$text['label-'.escape($row['domain_setting_value'])];
			}
			else if ($subcategory == 'password' || substr_count($subcategory, '_password') > 0 || $category == "login" && $subcategory == "password_reset_key" && $name == "text") {
				echo "		".str_repeat('*', strlen(escape($row['domain_setting_value'])));
			}
			else if ($category == 'theme' && $subcategory == 'button_icons' && $name == 'text') {
				echo "		".$text['option-button_icons_'.$row['domain_setting_value']]."\n";
			}
			else if ($category == "theme" && substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
				echo "		".(img_spacer('15px', '15px', 'background: '.escape($row['domain_setting_value']).'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['domain_setting_value'], -0.18)).'; padding: -1px;'));
				echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".escape($row['domain_setting_value'])."</span>\n";
			}
			else if ($category == 'recordings' && $subcategory == 'storage_type' && $name == 'text') {
				echo "		".$text['label-'.$row['domain_setting_value']]."\n";
			}
			else {
				echo "		".escape($row['domain_setting_value'])."\n";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>\n";
			echo "		<button type='submit' class='btn btn-link' name='action' value='toggle' onclick=\"document.getElementById('checkbox_".escape($row['domain_setting_uuid'])."').checked=true;\">".$text['label-'.escape($row['domain_setting_enabled'])]."</button>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['domain_setting_description'])."&nbsp;</td>\n";
			echo "	<td class='tr_link_void'>";
			if (permission_exists('domain_setting_edit')) {
				echo "<a href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_uuid=".escape($row['domain_uuid'])."&id=".escape($row['domain_setting_uuid'])."' alt='".$text['button-edit']."'>".$v_button_icon_edit."</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$previous_category = $row['domain_setting_category'];
			$c = $c ? 0 : 1;
		}
		unset($sql, $result, $row_count);
	}

	echo "<tr>\n";
	echo "</table>";

	echo "<div style='text-align: center; white-space: nowrap'>".$paging_controls."</div>";

	echo "</form>";

	echo "<br /><br />";

	// check or uncheck all category checkboxes
		if (isset($subcat_ids) && sizeof($subcat_ids) > 0) {
			echo "<script>\n";
			echo "	function check(what, category) {\n";
			foreach ($subcat_ids as $domain_setting_category => $checkbox_ids) {
				echo "if (category == '".$domain_setting_category."') {\n";
				foreach ($checkbox_ids as $index => $checkbox_id) {
					echo "document.getElementById('".$checkbox_id."').checked = (what == 'all') ? true : false;\n";
				}
				echo "}\n";
			}
			echo "	}\n";
			echo "</script>\n";
		}

	//handle form actions
		echo "<script type='text/javascript'>\n";
		echo "	function set_action(action) {\n";
		echo "		document.getElementById('action').value = action;\n";
		echo "	}\n";

		echo "	function submit_form(form_id) {\n";
		echo "		document.getElementById(form_id).submit();\n";
		echo "	}\n";
		echo "</script>\n";

?>