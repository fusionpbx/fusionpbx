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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('default_setting_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted values, if any
	if (sizeof($_REQUEST) > 0) {
		$action = check_str($_REQUEST["action"]);
		$default_setting_uuids = $_REQUEST["id"];
		$enabled = check_str($_REQUEST['enabled']);
		$category = check_str($_REQUEST['category']);
		$search = check_str($_REQUEST['search']);

		if (sizeof($default_setting_uuids) == 1 && $enabled != '') {
			$sql = "update v_default_settings set ";
			$sql .= "default_setting_enabled = '".$enabled."' ";
			$sql .= "where default_setting_uuid = '".$default_setting_uuids[0]."'";
			$db->exec(check_sql($sql));
			unset($sql);

			$_SESSION["message"] = $text['message-update'];
			header("Location: default_settings.php".(($search != '') ? "?search=".$search : null)."#".$category);
			exit;
		}

		if ($action == 'copy' && permission_exists('domain_setting_add')) {
			$target_domain_uuid = check_str($_POST["target_domain_uuid"]);

			if ($target_domain_uuid != '' && sizeof($default_setting_uuids) > 0) {
				$settings_copied = 0;
				foreach ($default_setting_uuids as $default_setting_uuid) {

					// get default setting from db
					$sql = "select * from v_default_settings ";
					$sql .= "where default_setting_uuid = '".$default_setting_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						$default_setting_category = $row["default_setting_category"];
						$default_setting_subcategory = $row["default_setting_subcategory"];
						$default_setting_name = $row["default_setting_name"];
						$default_setting_value = $row["default_setting_value"];
						$default_setting_order = $row["default_setting_order"];
						$default_setting_enabled = $row["default_setting_enabled"];
						$default_setting_description = $row["default_setting_description"];
					}
					unset ($prep_statement);

					// check if exists
					$sql = "select domain_setting_uuid from v_domain_settings ";
					$sql .= "where domain_uuid = '".$target_domain_uuid."' ";
					$sql .= "and domain_setting_category = '".$default_setting_category."' ";
					$sql .= "and domain_setting_subcategory = '".$default_setting_subcategory."' ";
					$sql .= "and domain_setting_name = '".$default_setting_name."' ";
					$sql .= "and domain_setting_name <> 'array' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					if (sizeof($result) > 0) {
						foreach ($result as &$row) {
							$target_domain_setting_uuid = $row["domain_setting_uuid"];
							break;
						}
						$action = "update";
					}
					else {
						$action = "add";
					}
					unset ($prep_statement);

					// fix null
					$default_setting_order = ($default_setting_order != '') ? $default_setting_order : 'null';

					// insert for target domain
					if ($action == "add" && permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
						$sql = "insert into v_domain_settings ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "domain_setting_uuid, ";
						$sql .= "domain_setting_category, ";
						$sql .= "domain_setting_subcategory, ";
						$sql .= "domain_setting_name, ";
						$sql .= "domain_setting_value, ";
						$sql .= "domain_setting_order, ";
						$sql .= "domain_setting_enabled, ";
						$sql .= "domain_setting_description ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$target_domain_uuid."', ";
						$sql .= "'".uuid()."', ";
						$sql .= "'".$default_setting_category."', ";
						$sql .= "'".$default_setting_subcategory."', ";
						$sql .= "'".$default_setting_name."', ";
						$sql .= "'".$default_setting_value."', ";
						$sql .= " ".$default_setting_order." , ";
						$sql .= "'".$default_setting_enabled."', ";
						$sql .= "'".$default_setting_description."' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);

						$settings_copied++;
					} // add

					if ($action == "update" && permission_exists('domain_setting_edit')) {
						$sql = "update v_domain_settings set ";
						$sql .= "domain_setting_category = '".$default_setting_category."', ";
						$sql .= "domain_setting_subcategory = '".$default_setting_subcategory."', ";
						$sql .= "domain_setting_name = '".$default_setting_name."', ";
						$sql .= "domain_setting_value = '".$default_setting_value."', ";
						$sql .= "domain_setting_order = ".$default_setting_order.", ";
						$sql .= "domain_setting_enabled = '".$default_setting_enabled."', ";
						$sql .= "domain_setting_description = '".$default_setting_description."' ";
						$sql .= "where domain_uuid = '".$target_domain_uuid."' ";
						$sql .= "and domain_setting_uuid = '".$target_domain_setting_uuid."' ";
						$db->exec(check_sql($sql));
						unset($sql);

						$settings_copied++;
					} // update
				} // foreach

				// set message
				$_SESSION["message"] = $text['message-copy'].": ".$settings_copied;
			}
			else {
				// set message
				$_SESSION["message"] = $text['message-copy_failed'];
			}

			header("Location: default_settings.php".(($search != '') ? "?search=".$search : null));
			exit;
		}

		if ($action == 'delete' && permission_exists('default_setting_delete')) {
			if (sizeof($default_setting_uuids) > 0) {
				foreach ($default_setting_uuids as $default_setting_uuid) {
					//delete default_setting(s)
					$sql = "delete from v_default_settings ";
					$sql .= "where default_setting_uuid = '".$default_setting_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($sql);
				}

				// set message
				$_SESSION["message"] = $text['message-delete'].": ".sizeof($default_setting_uuids);
			}
			else {
				// set message
				$_SESSION["message"] = $text['message-delete_failed'];
				$_SESSION["message_mood"] = "negative";
			}

			header("Location: default_settings.php".(($search != '') ? "?search=".$search : null));
			exit;
		}
	} // post

//header and paging
	require_once "resources/header.php";
	$document['title'] = $text['title-default_settings'];
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//copy settings javascript
	if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	var fade_speed = 400;\n";
		echo "	function show_domains() {\n";
		echo "		document.getElementById('action').value = 'copy';\n";
		echo "		$('#button_copy').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_back').fadeIn(fade_speed);\n";
		echo "			$('#target_domain_uuid').fadeIn(fade_speed);\n";
		echo "			$('#button_paste').fadeIn(fade_speed);\n";
		echo "		});";
		echo "	}";
		echo "	function hide_domains() {\n";
		echo "		document.getElementById('action').value = '';\n";
		echo "		$('#button_back').fadeOut(fade_speed);\n";
		echo "		$('#target_domain_uuid').fadeOut(fade_speed);\n";
		echo "		$('#button_paste').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_copy').fadeIn(fade_speed);\n";
		echo "			document.getElementById('target_domain_uuid').selectedIndex = 0;\n";
		echo "		});\n";
		echo "	}\n";
		echo "\n";
		echo "	$( document ).ready(function() {\n";
		echo "		$('#default_setting_search').focus();\n";
		if ($search == '') {
			echo "		// scroll to previous category\n";
			echo "		var category_span_id;\n";
			echo "		var url = document.location.href;\n";
			echo "		var hashindex = url.indexOf('#');\n";
			echo "		if (hashindex == -1) { }\n";
			echo "		else {\n";
			echo "			category_span_id = url.substr(hashindex + 1);\n";
			echo "		}\n";
			echo "		if (category_span_id) {\n";
			echo "			$('#page').animate({scrollTop: $('#anchor_'+category_span_id).offset().top - 200}, 'slow');\n";
			echo "		}\n";
		}
		echo "	});\n";
		echo "</script>";
	}

//prevent enter key submit on search field
	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	$(document).ready(function() {\n";
	echo "		$('#default_setting_search').keydown(function(event){\n";
	echo "			if (event.keyCode == 13) {\n";
	echo "				event.preventDefault();\n";
	echo "				return false;\n";
	echo "			}\n";
	echo "		});\n";
	echo "	});\n";
	echo "</script>\n";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>";
	echo "<input type='hidden' name='action' id='action' value=''>";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top' nowrap='nowrap'>";
	echo "			<b>".$text['header-default_settings']."</b>";
	echo "			<br><br>";
	echo "			".$text['description-default_settings'];
	echo "		</td>\n";
	echo "		<td align='right' valign='top' nowrap='nowrap'>";
	echo "			<input type='text' name='search' id='default_setting_search' class='formfld' style='min-width: 150px; width:150px; max-width: 150px;' placeholder=\"".$text['label-search']."\" value=\"".$search."\" onkeyup='setting_search();'>\n";
	if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
		echo "		<input type='button' class='btn' id='button_copy' alt='".$text['button-copy']."' onclick='show_domains();' value='".$text['button-copy']."'>";
		echo "		<input type='button' class='btn' style='display: none;' id='button_back' alt='".$text['button-back']."' onclick='hide_domains();' value='".$text['button-back']."'> ";
		echo "		<select class='formfld' style='display: none; width: auto;' name='target_domain_uuid' id='target_domain_uuid'>\n";
		echo "			<option value=''>Select Domain...</option>\n";
		foreach ($_SESSION['domains'] as $domain) {
			echo "		<option value='".$domain["domain_uuid"]."'>".$domain["domain_name"]."</option>\n";
		}
		echo "		</select>\n";
		echo "		<input type='button' class='btn' id='button_paste' style='display: none;' alt='".$text['button-paste']."' value='".$text['button-paste']."' onclick=\"$('#frm').attr('action', 'default_settings.php?search='+$('#default_setting_search').val()).submit();\">";
	}
	if (permission_exists('default_setting_edit')) {
		echo "		<input type='button' class='btn' alt='".$text['button-toggle']."' onclick=\"$('#frm').attr('action', 'default_setting_toggle.php').submit();\" value='".$text['button-toggle']."'>\n";
	}
	echo "			<input type='button' class='btn' id='button_reload' alt='".$text['button-reload']."' value='".$text['button-reload']."' onclick=\"document.location.href='default_settings_reload.php?search='+$('#default_setting_search').val();\">";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";

//prepare to page the results
	$sql = "select count(*) as num_rows from v_default_settings ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
	$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}

//prepare to page the results
	$rows_per_page = 1000;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_default_settings ";
	if (strlen($order_by) == 0) {
		$sql .= "order by default_setting_category, default_setting_subcategory, default_setting_order asc ";
	}
	else {
		$sql .= "order by $order_by $order ";
	}
	$sql .= "limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	if ($result_count > 0) {
		$previous_category = '';
		foreach($result as $row) {

			if ($previous_category != $row['default_setting_category']) {
				$c = 0;
				if ($previous_category != '') {
					echo "</table>";
					echo "</div>";
				}
				echo "<div id='category_".$row['default_setting_category']."' style='padding-top: 20px;'>";
				echo "<span id='anchor_".$row['default_setting_category']."'></span>";
				echo "<b>";
				switch (strtolower($row['default_setting_category'])) {
					case "api" : echo "API"; break;
					case "cdr" : echo "CDR"; break;
					case "ldap" : echo "LDAP"; break;
					case "ivr menu" : echo "IVR Menu"; break;
					default: echo ucwords(str_replace("_", " ", $row['default_setting_category']));
				}
				echo "</b>\n";

				echo "<table class='tr_hover' style='margin-top: 5px;' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "<tr>\n";
				if ( (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) || permission_exists('default_setting_delete') ) {
					echo "<th style='width: 30px; vertical-align: bottom; text-align: center; padding: 0px 3px 2px 8px;'><input type='checkbox' id='chk_all_".$row['default_setting_category']."' class='chk_all' onchange=\"(this.checked) ? check('all','".strtolower($row['default_setting_category'])."') : check('none','".strtolower($row['default_setting_category'])."');\"></th>";
				}
				echo "<th width='23%'>".$text['label-subcategory']."</th>";
				echo "<th width='7%'>".$text['label-type']."</th>";
				echo "<th width='30%'>".$text['label-value']."</th>";
				echo "<th style='text-align: center;'>".$text['label-enabled']."</th>";
				echo "<th width='40%'>".$text['label-description']."</th>";
				echo "<td class='list_control_icons'>";
				if (permission_exists('default_setting_add')) {
					echo "<a href='javascript:void(0)' onclick=\"document.location.href='default_setting_edit.php?default_setting_category=".urlencode($row['default_setting_category'])."&search='+$('#default_setting_search').val();\" alt='".$text['button-add']."'>".$v_link_label_add."</a>";
				}
				if (permission_exists('default_setting_delete')) {
					echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('action').value = 'delete'; $('#frm').attr('action', 'default_settings.php?search='+$('#default_setting_search').val()).submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
				}
				echo "</td>\n";
				echo "</tr>\n";
			}

			$tr_link = (permission_exists('default_setting_edit')) ? "href=\"javascript:document.location.href='default_setting_edit.php?id=".$row['default_setting_uuid']."&search='+$('#default_setting_search').val();\"" : null;
			echo "<tr id='setting_".$row['default_setting_uuid']."' ".$tr_link.">\n";
			if ( (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) || permission_exists("default_setting_delete") ) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; padding: 3px 3px 0px 8px;'><input type='checkbox' name='id[]' id='checkbox_".$row['default_setting_uuid']."' value='".$row['default_setting_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$row['default_setting_category']."').checked = false; }\"></td>\n";
				$subcat_ids[strtolower($row['default_setting_category'])][] = 'checkbox_'.$row['default_setting_uuid'];
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('default_setting_edit')) {
				echo "<a href=\"javascript:document.location.href='default_setting_edit.php?id=".$row['default_setting_uuid']."&search='+$('#default_setting_search').val(); return false;\">".$row['default_setting_subcategory']."</a>";
			}
			else {
				echo $row['default_setting_subcategory'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['default_setting_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='width: 30%; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>\n";

			$category = $row['default_setting_category'];
			$subcategory = $row['default_setting_subcategory'];
			$name = $row['default_setting_name'];
			if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
				$sql = "select * from v_menus ";
				$sql .= "where menu_uuid = '".$row['default_setting_value']."' ";
				$sub_prep_statement = $db->prepare(check_sql($sql));
				$sub_prep_statement->execute();
				$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset ($prep_statement, $sql);
				foreach ($sub_result as &$sub_row) {
					echo $sub_row["menu_language"]." - ".$sub_row["menu_name"]."\n";
				}
			}
			else if ($category == "domain" && $subcategory == "template" && $name == "name" ) {
				echo "		".ucwords($row['default_setting_value']);
			}
			else if ($category == "domain" && $subcategory == "time_format" && $name == "text" ) {
				switch ($row['default_setting_value']) {
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
				echo "		".$text['label-'.$row['default_setting_value']];
			}
			else if ($subcategory == 'password' || substr_count($subcategory, '_password') > 0 || $category == "login" && $subcategory == "password_reset_key" && $name == "text") {
				echo "		".str_repeat('*', strlen($row['default_setting_value']));
			}
			else {
				if ($category == "theme" && substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
					echo "		".(img_spacer('15px', '15px', 'background: '.$row['default_setting_value'].'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['default_setting_value'], -0.18)).'; padding: -1px;'));
					echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".htmlspecialchars($row['default_setting_value'])."</span>\n";
				}
				else {
					echo "		".htmlspecialchars($row['default_setting_value'])."\n";
				}
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>\n";
			if (permission_exists('default_setting_edit')) {
				echo "	<a href=\"javascript:document.location.href='?id[]=".$row['default_setting_uuid']."&enabled=".(($row['default_setting_enabled'] == 'true') ? 'false' : 'true')."&category=".$category."&search='+$('#default_setting_search').val();\">".$text['label-'.$row['default_setting_enabled']]."</a>\n";
			}
			else {
				echo "	".$text['label-'.$row['default_setting_enabled']]."\n";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg' style='width: 40%; max-width: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>".$row['default_setting_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons' nowrap='nowrap'>";
			if (permission_exists('default_setting_edit')) {
				echo "<a href=\"javascript:document.location.href='default_setting_edit.php?id=".$row['default_setting_uuid']."&search='+$('#default_setting_search').val();\" alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('default_setting_delete')) {
				echo "<a href=\"javascript:document.location.href='default_settings.php?id[]=".$row['default_setting_uuid']."&action=delete&search='+$('#default_setting_search').val();\" alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			//populate search/filter arrays
			$array_categories[] = $row['default_setting_category'];
			$array_categories_displayed[] = str_replace("_", " ", $row['default_setting_category']);
			$array_setting_uuids[] = $row['default_setting_uuid'];
			$array_setting_subcategories[] = $row['default_setting_subcategory'];
			$array_setting_types[] = $row['default_setting_name'];
			$array_setting_values[] = str_replace('"','\"',$row['default_setting_value']);
			$array_setting_descriptions[] = str_replace('"','\"',$row['default_setting_description']);

			$previous_category = $row['default_setting_category'];
			$c = ($c == 0) ? 1 : 0;

		} //end foreach

		echo "</table>";
		echo "</div>";

		unset($sql, $result, $row_count);
	} //end if results

	echo "<br />";
	echo $paging_controls;
	echo "<br /><br /><br />";

	echo "</form>";

	//check or uncheck all category checkboxes
		if (sizeof($subcat_ids) > 0) {
			echo "<script>\n";
			echo "	function check(what, category) {\n";
			foreach ($subcat_ids as $default_setting_category => $checkbox_ids) {
				echo "if (category == '".$default_setting_category."') {\n";
				foreach ($checkbox_ids as $index => $checkbox_id) {
					echo "document.getElementById('".$checkbox_id."').checked = (what == 'all') ? true : false;\n";
				}
				echo "}\n";
			}
			echo "	}\n";
			echo "</script>\n";
		}

	//setting search script
		echo "<script>\n";
		echo "	var categories = new Array(\"".implode('","', $array_categories)."\");\n";
		echo "	var categories_displayed = new Array(\"".implode('","', $array_categories_displayed)."\");\n";
		echo "	var setting_uuids = new Array(\"".implode('","', $array_setting_uuids)."\");\n";
		echo "	var setting_subcategories = new Array(\"".implode('","', $array_setting_subcategories)."\");\n";
		echo "	var setting_types = new Array(\"".implode('","', $array_setting_types)."\");\n";
		echo "	var setting_values = new Array(\"".implode('","', $array_setting_values)."\");\n";
		echo "	var setting_descriptions = new Array(\"".implode('","', $array_setting_descriptions)."\");\n";
		echo "\n";
		echo "	function setting_search() {\n";
		echo "		var criteria = $('#default_setting_search').val();\n";
		echo "		if (criteria.length >= 2) {\n";
		echo "			$('.chk_all').hide();\n";
		echo "			for (var x = 0; x < categories.length; x++) {\n";
		echo "				document.getElementById('category_'+categories[x]).style.display = 'none';\n";
		echo "			}\n";
		echo "			for (var x = 0; x < setting_uuids.length; x++) {\n";
		echo "				if (\n";
		echo "					categories_displayed[x].toLowerCase().match(criteria.toLowerCase()) ||\n";
		echo "					setting_subcategories[x].toLowerCase().match(criteria.toLowerCase()) ||\n";
		echo "					setting_types[x].toLowerCase().match(criteria.toLowerCase()) ||\n";
		echo "					setting_values[x].toLowerCase().match(criteria.toLowerCase()) ||\n";
		echo "					setting_descriptions[x].toLowerCase().match(criteria.toLowerCase())\n";
		echo "					) {\n";
		echo "					document.getElementById('category_'+categories[x]).style.display = '';\n";
		echo "					document.getElementById('setting_'+setting_uuids[x]).style.display = '';\n";
		echo "				}\n";
		echo "				else {\n";
		echo "					document.getElementById('setting_'+setting_uuids[x]).style.display = 'none';\n";
		echo "				}\n";
		echo "			}\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			$('.chk_all').show();\n";
		echo "			for (var x = 0; x < setting_uuids.length; x++) {\n";
		echo "				document.getElementById('category_'+categories[x]).style.display = '';\n";
		echo "				document.getElementById('setting_'+setting_uuids[x]).style.display = '';\n";
		echo "			}\n";
		echo "		}\n";
		echo "	}\n";
		echo "\n";
	//auto run, if search term passed back
		if ($search != '') {
			echo "	setting_search();";
			echo "	$('#default_setting_search').select();\n";
		}
		echo "</script>\n";

//include the footer
	require_once "resources/footer.php";
?>