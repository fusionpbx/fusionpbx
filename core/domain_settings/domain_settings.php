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
 Portions created by the Initial Developer are Copyright (C) 2008-2018
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

//toggle enabled
	if (sizeof($_REQUEST) > 1) {
		//get the variables
			$action = $_REQUEST["action"];
			$domain_uuid = $_REQUEST["domain_id"];
			$domain_setting_uuids = $_REQUEST["id"];
			$enabled = $_REQUEST['enabled'];

		//change enabled value
			if (
				permission_exists('domain_setting_edit') &&
				is_uuid($domain_uuid) &&
				is_array($domain_setting_uuids) &&
				sizeof($domain_setting_uuids) == 1 &&
				($enabled == 'true' || $enabled == 'false')
				) {
				$array['domain_settings'][0]['domain_setting_uuid'] = $domain_setting_uuids[0];
				$array['domain_settings'][0]['domain_setting_enabled'] = $enabled;
				$database = new database;
				$database->app_name = 'domains';
				$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
				$database->save($array);
				unset($array);

				message::add($text['message-update']);
				header("Location: ".PROJECT_PATH."/core/domains/domain_edit.php?id=".$domain_uuid);
				exit;
			}

		//copy domain settings
			if ($action == 'copy' && permission_exists('domain_setting_add')) {
				$target_domain_uuid = $_POST["target_domain_uuid"];

				if (is_uuid($target_domain_uuid) && is_array($domain_setting_uuids) && sizeof($domain_setting_uuids) != 0) {
					foreach ($domain_setting_uuids as $index => $domain_setting_uuid) {

						if (is_uuid($domain_setting_uuid)) {

							// get default setting from db
							$sql = "select * from v_domain_settings ";
							$sql .= "where domain_setting_uuid = :domain_setting_uuid ";
							$parameters['domain_setting_uuid'] = $domain_setting_uuid;
							$database = new database;
							$row = $database->select($sql, $parameters, 'row');
							if (is_array($row) && sizeof($row) != 0) {
								$domain_setting_uuid = $row["default_setting_uuid"];
								$domain_setting_category = $row["default_setting_category"];
								$domain_setting_subcategory = $row["default_setting_subcategory"];
								$domain_setting_name = $row["default_setting_name"];
								$domain_setting_value = $row["default_setting_value"];
								$domain_setting_order = $row["default_setting_order"];
								$domain_setting_enabled = $row["default_setting_enabled"];
								$domain_setting_description = $row["default_setting_description"];
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
							if (is_uuid($target_domain_setting_uuid)) {
								$action = "update";
							}
							else {
								$action = "add";
								$target_domain_setting_uuid = uuid();
							}
							unset($sql, $parameters);

							// fix null
							$domain_setting_order = $domain_setting_order != '' ? $domain_setting_order : null;

							//prepare the array
							$array['domain_settings'][$index]['domain_uuid'] = $target_domain_uuid;
							$array['domain_settings'][$index]['domain_setting_uuid'] = $target_domain_setting_uuid;
							$array['domain_settings'][$index]['default_setting_category'] = $default_setting_category;
							$array['domain_settings'][$index]['domain_setting_uuid'] = $default_setting_subcategory;
							$array['domain_settings'][$index]['domain_setting_uuid'] = $default_setting_name;
							$array['domain_settings'][$index]['domain_setting_uuid'] = $default_setting_value;
							$array['domain_settings'][$index]['domain_setting_uuid'] = $default_setting_order;
							$array['domain_settings'][$index]['domain_setting_uuid'] = $default_setting_enabled;
							$array['domain_settings'][$index]['domain_setting_uuid'] = $default_setting_description;

						}

					} // foreach

					//save the data
					if (is_array($array) && sizeof($array) != 0) {
						$database = new database;
						$database->app_name = 'domain_settings';
						$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
						$database->save($array);
						$message = $database->message;

						// set message
						message::add($text['message-copy'].": ".sizeof($array));

						unset($array);
					}
				}
				else {
					// set message
					message::add($text['message-copy_failed']);
				}
	
				header("Location: ".PROJECT_PATH."/core/domains/domains.php".($search != '' ? "?search=".escape($search) : null));
				exit;
			}

		//delete domain settings
			if ($action == 'delete' && permission_exists('domain_setting_delete')) {
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
				else {
					// set message
					message::add($text['message-delete_failed'], 'negative');
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

//show the content
	echo "<form name='domain_frm' id='domain_frm' method='GET' action='".PROJECT_PATH."/core/domain_settings/domain_settings.php'>";
	echo "<input type='hidden' name='action' id='action' value=''>";
	echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>";

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
	$result_count = sizeof($result);
	unset($sql, $parameters);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	if (is_array($result) && sizeof($result) != 0) {
		$previous_category = '';
		foreach($result as $row) {
			if ($previous_category != $row['domain_setting_category']) {
				$c = 0;
				echo "<tr>\n";
				echo "	<td colspan='7' align='left'>\n";
				if ($previous_category != '') {
					echo "	<br /><br />\n";
				}
				echo "		<b>\n";
				if (strtolower($row['domain_setting_category']) == "cdr") {
					echo "		CDR";
				}
				elseif (strtolower($row['domain_setting_category']) == "ldap") {
					echo "		LDAP";
				}
				else {
					echo "		".ucfirst($row['domain_setting_category']);
				}
				echo "		</b>\n";
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
				echo "<td class='list_control_icons'>";
				if (permission_exists('domain_setting_add')) {
					echo "<a href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_setting_category=".escape($row['domain_setting_category'])."&domain_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
				}
				if (permission_exists('domain_setting_delete')) {
					echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('action').value = 'delete'; document.forms.domain_frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
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
			else {
				if ($category == "theme" && substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
					echo "		".(img_spacer('15px', '15px', 'background: '.escape($row['domain_setting_value']).'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['domain_setting_value'], -0.18)).'; padding: -1px;'));
					echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".escape($row['domain_setting_value'])."</span>\n";
				}
				else {
					echo "		".escape($row['domain_setting_value'])."\n";
				}
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center;'>\n";
			echo "		<a href='".PROJECT_PATH."/core/domain_settings/domain_settings.php?domain_id=".escape($row['domain_uuid'])."&id[]=".escape($row['domain_setting_uuid'])."&enabled=".(($row['domain_setting_enabled'] == 'true') ? 'false' : 'true')."'>".$text['label-'.escape($row['domain_setting_enabled'])]."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['domain_setting_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('domain_setting_edit')) {
				echo "<a href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_uuid=".escape($row['domain_uuid'])."&id=".escape($row['domain_setting_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('domain_setting_delete')) {
				echo "<a href='".PROJECT_PATH."/core/domain_settings/domain_settings.php?domain_uuid=".escape($row['domain_uuid'])."&id[]=".escape($row['domain_setting_uuid'])."&action=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$previous_category = $row['domain_setting_category'];
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='20' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('domain_setting_add')) {
		echo 		"<a href='".PROJECT_PATH."/core/domain_settings/domain_setting_edit.php?domain_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	if (permission_exists('domain_setting_delete') && $result_count > 0) {
		echo 		"<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.getElementById('action').value = 'delete'; document.getElementById('domain_frm').submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</form>";

	echo "<br /><br />";

	// check or uncheck all category checkboxes
	if (sizeof($subcat_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what, category) {\n";
		foreach ($subcat_ids as $domain_setting_category => $checkbox_ids) {
			echo "if (category == '".$domain_setting_category."') {\n";
			foreach ($checkbox_ids as $index => $checkbox_id) {
				echo "document.getElementById('".escape($checkbox_id)."').checked = (what == 'all') ? true : false;\n";
			}
			echo "}\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

//include the footer
	//require_once "resources/footer.php";

?>
