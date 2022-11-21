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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('menu_add') || permission_exists('menu_edit') || permission_exists('menu_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the http post data
	if (is_array($_POST['menu_items'])) {
		$action = $_POST['action'];
		$menu_uuid = $_POST['menu_uuid'];
		$menu_items = $_POST['menu_items'];
	}

//process the http post data by action
	if ($action != '' && is_array($menu_items) && @sizeof($menu_items) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('menu_item_edit')) {
					$obj = new menu;
					$obj->toggle_items($menu_items);
				}
				break;
			case 'delete':
				if (permission_exists('menu_item_delete')) {
					$obj = new menu;
					$obj->delete_items($menu_items);
				}
				break;
		}

		header('Location: menu_edit.php?id='.urlencode($menu_uuid));
		exit;
	}

$tmp_menu_item_order = 0;

function build_db_child_menu_list ($db, $menu_item_level, $menu_item_uuid) {
	global $menu_uuid, $tmp_menu_item_order, $v_link_label_edit, $v_link_label_delete, $page, $text, $x;

	//check for sub menus
		$menu_item_level = $menu_item_level+1;
		$sql = "select * from v_menu_items ";
		$sql .= "where menu_uuid = :menu_uuid ";
		$sql .= "and menu_item_parent_uuid = :menu_item_parent_uuid ";
		$sql .= "order by menu_item_title, menu_item_order asc ";
		$parameters['menu_uuid'] = $menu_uuid;
		$parameters['menu_item_parent_uuid'] = $menu_item_uuid;
		$database = new database;
		$result2 = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		if (is_array($result2) && sizeof($result2) != 0) {
			foreach ($result2 as $row2) {
				//set the db values as php variables
					$menu_item_uuid = $row2['menu_item_uuid'];
					$menu_item_category = $row2['menu_item_category'];
					$menu_item_protected = $row2['menu_item_protected'];
					$menu_item_parent_uuid = $row2['menu_item_parent_uuid'];
					$menu_item_order = $row2['menu_item_order'];
					$menu_item_language = $row2['menu_item_language'];
					$menu_item_title = $row2['menu_item_title'];
					$menu_item_link = $row2['menu_item_link'];
				//get the groups that have been assigned to the menu
					$sql = "select ";
					$sql .= "	g.group_name, g.domain_uuid as group_domain_uuid ";
					$sql .= "from ";
					$sql .= "	v_menu_item_groups as mig, ";
					$sql .= "	v_groups as g ";
					$sql .= "where ";
					$sql .= "	mig.group_uuid = g.group_uuid ";
					$sql .= "	and mig.menu_uuid = :menu_uuid ";
					$sql .= "	and mig.menu_item_uuid = :menu_item_uuid ";
					$sql .= "order by ";
					$sql .= "	g.domain_uuid desc, ";
					$sql .= "	g.group_name asc ";
					$parameters['menu_uuid'] = $menu_uuid;
					$parameters['menu_item_uuid'] = $menu_item_uuid;
					$database = new database;
					$sub_result = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters, $group_list);

					if (is_array($sub_result) && sizeof($sub_result) != 0) {
						foreach ($sub_result as &$sub_row) {
							$group_list[] = $sub_row["group_name"].(($sub_row['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$sub_row['group_domain_uuid']]['domain_name'] : null);
						}
						$group_list = isset($group_list) ? implode(', ', $group_list) : '';
					}
					unset($sql, $sub_result, $sub_row);
				//display the main body of the list
					switch ($menu_item_category) {
						case "internal":
							$menu_item_link = "<a href='".PROJECT_PATH.$menu_item_link."'>$menu_item_link</a>";
							break;
						case "external":
							if (substr($menu_item_link,0,1) == "/") {
								$menu_item_link = PROJECT_PATH.$menu_item_link;
							}
							$menu_item_link = "<a href='".$menu_item_link."' target='_blank'>".$menu_item_link."</a>";
							break;
						case "email":
							$menu_item_link = "<a href='mailto:".$menu_item_link."'>".$menu_item_link."</a>";
							break;
					}

				//display the content of the list
					if (permission_exists('menu_item_edit')) {
						$list_row_url = 'menu_item_edit.php?id='.urlencode($menu_uuid)."&menu_item_uuid=".urlencode($menu_item_uuid)."&menu_item_parent_uuid=".urlencode($row2['menu_item_parent_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('menu_item_edit') || permission_exists('menu_item_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='menu_items[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
						echo "		<input type='hidden' name='menu_items[$x][uuid]' value='".escape($menu_item_uuid)."' />\n";
						echo "	</td>\n";
					}
					echo "<td class='no-wrap".($menu_item_category != 'internal' ? "no-link" : null)."' style='padding-left: ".($menu_item_level * 25)."px;'>\n";
					if (permission_exists('menu_item_edit')) {
						echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($menu_item_title)."</a>\n";
					}
					else {
						echo "	".escape($menu_item_title);
					}
					echo "</td>\n";
					echo "<td class='no-wrap overflow no-link hide-sm-dn'>".$menu_item_link."&nbsp;</td>\n";
					echo "<td class='no-wrap overflow hide-xs'>".$group_list."&nbsp;</td>";
					echo "<td class='center'>".$menu_item_category."&nbsp;</td>";
					if (permission_exists('menu_item_edit')) {
						echo "	<td class='no-link center'>\n";
						echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($menu_item_protected == 'true' ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
					}
					else {
						echo "	<td class='center'>\n";
						echo $text['label-'.($menu_item_protected == 'true' ? 'true' : 'false')];
					}
					echo "	</td>\n";
					echo "<td class='center no-wrap'>&nbsp;</td>";

					//echo "<td align='center'>";
					//if (permission_exists('menu_edit')) {
					//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_up.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row2['menu_item_parent_uuid']."&menu_item_uuid=".$row2[menu_item_uuid]."&menu_item_order=".$row2[menu_item_order]."'\" value='<' title='".$row2[menu_item_order].". ".$text['button-move_up']."'>";
					//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_down.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row2['menu_item_parent_uuid']."&menu_item_uuid=".$row2[menu_item_uuid]."&menu_item_order=".$row2[menu_item_order]."'\" value='>' title='".$row2[menu_item_order].". ".$text['button-move_down']."'>";
					//}
					//echo "</td>";

					if (permission_exists('menu_item_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;

				//update the menu order
					if ($row2['menu_item_order'] != $tmp_menu_item_order) {
						$array['menu_items'][0]['menu_item_uuid'] = $row2['menu_item_uuid'];
						$array['menu_items'][0]['menu_uuid'] = $menu_uuid;
						$array['menu_items'][0]['menu_item_title'] = $row2['menu_item_title'];
						$array['menu_items'][0]['menu_item_order'] = $tmp_menu_item_order;
						$database = new database;
						$database->app_name = 'menu';
						$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
						$database->save($array);
						unset($array);
					}
					$tmp_menu_item_order++;

				//check for additional sub menus
					if (strlen($menu_item_uuid)> 0) {
						build_db_child_menu_list($db, $menu_item_level, $menu_item_uuid);
					}

			}
			unset($result2, $row2);
		}
}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the list
	$sql = "select * from v_menu_items ";
	$sql .= "where menu_uuid = :menu_uuid ";
	$sql .= "and menu_item_parent_uuid is null ";
	$sql .= order_by($order_by, $order, 'menu_item_order', 'asc');
	$parameters['menu_uuid'] = $menu_uuid;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/core/menu/menu_item_list.php');

//show the content
	echo "<form id='form_list' method='post' action='menu_item_list.php'>\n";
	echo "<input type='hidden' name='action' id='action' value=''>\n";
	echo "<input type='hidden' name='menu_uuid' value='".escape($menu_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar_sub'>\n";
	echo "	<div class='heading'><b id='heading_sub'>".$text['header-menu_items']." (<span id='num_rows'></span>)</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','id'=>'action_bar_sub_button_back','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'collapse'=>'hide-xs','style'=>'margin-right: 15px; display: none;','link'=>'menu.php']);
	if (permission_exists('menu_item_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','collapse'=>'hide-xs','link'=>'menu_item_edit.php?id='.urlencode($menu_uuid)]);
	}
	if (permission_exists('menu_item_edit') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('menu_item_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('menu_item_edit') && $result) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('menu_item_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<table class='list'>\n";
	echo "	<tr class='list-header'>";
	if (permission_exists('menu_item_edit') || permission_exists('menu_item_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo "		<th class='no-wrap pct-30'>".$text['label-title']."</th>";
	echo "		<th class='no-wrap pct-35 hide-sm-dn'>".$text['label-link']."</th>";
	echo "		<th class='no-wrap pct-35 hide-xs'>".$text['label-groups']."</th>";
	echo "		<th class='no-wrap center shrink'>".$text['label-category']."</th>";
	echo "		<th class='no-wrap center shrink'>".$text['label-protected']."</th>";
	echo "		<th class='no-wrap center shrink'>".$text['label-menu_order']."</th>";
	if (permission_exists('menu_item_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach ($result as $row) {
			//set the db values as php variables
				$menu_item_uuid = $row['menu_item_uuid'];
				$menu_item_category = $row['menu_item_category'];
				$menu_item_title = $row['menu_item_title'];
				$menu_item_link = $row['menu_item_link'];
				$menu_item_protected = $row['menu_item_protected'];

			//get the groups that have been assigned to the menu
				$sql = "select ";
				$sql .= "	g.group_name, g.domain_uuid as group_domain_uuid ";
				$sql .= "from ";
				$sql .= "	v_menu_item_groups as mig, ";
				$sql .= "	v_groups as g ";
				$sql .= "where ";
				$sql .= "	mig.group_uuid = g.group_uuid ";
				$sql .= "	and mig.menu_uuid = :menu_uuid ";
				$sql .= "	and mig.menu_item_uuid = :menu_item_uuid ";
				$sql .= "order by ";
				$sql .= "	g.domain_uuid desc, ";
				$sql .= "	g.group_name asc ";
				$parameters['menu_uuid'] = $menu_uuid;
				$parameters['menu_item_uuid'] = $menu_item_uuid;
				$database = new database;
				$sub_result = $database->select($sql, $parameters, 'all');
				unset($sql, $group_list);

				if (is_array($sub_result) && sizeof($sub_result) != 0) {
					foreach ($sub_result as &$sub_row) {
						$group_list[] = $sub_row["group_name"].(($sub_row['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$sub_row['group_domain_uuid']]['domain_name'] : null);
					}
					$group_list = implode(', ', $group_list);
				}
				unset($sub_result, $sub_row);

			//add the type link based on the type of the menu
				switch ($menu_item_category) {
					case "internal":
						$menu_item_link = "<a href='".PROJECT_PATH.$menu_item_link."'>".$menu_item_link."</a>";
						break;
					case "external":
						if (substr($menu_item_link, 0,1) == "/") {
							$menu_item_link = PROJECT_PATH.$menu_item_link;
						}
						$menu_item_link = "<a href='".$menu_item_link."' target='_blank'>".$menu_item_link."</a>";
						break;
					case "email":
						$menu_item_link = "<a href='mailto:".$menu_item_link."'>".$menu_item_link."</a>";
						break;
				}

			//display the content of the list
				if (permission_exists('menu_item_edit')) {
					$list_row_url = 'menu_item_edit.php?id='.urlencode($menu_uuid)."&menu_item_uuid=".urlencode($menu_item_uuid)."&menu_uuid=".urlencode($menu_uuid);
				}
				echo "<tr class='list-row' href='".$list_row_url."'>\n";
				if (permission_exists('menu_item_edit') || permission_exists('menu_item_delete')) {
					echo "<td class='checkbox'>\n";
					echo "	<input type='checkbox' name='menu_items[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
					echo "	<input type='hidden' name='menu_items[$x][uuid]' value='".escape($menu_item_uuid)."' />\n";
					echo "</td>\n";
				}
				echo "<td>\n";
				if (permission_exists('menu_item_edit')) {
					echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($menu_item_title)."</a>\n";
				}
				else {
					echo "	".escape($menu_item_title);
				}
				echo "</td>\n";
				echo "<td class='no-wrap overflow no-link hide-sm-dn'>".$menu_item_link."&nbsp;</td>\n";
				echo "<td class='no-wrap overflow hide-xs'>".$group_list."&nbsp;</td>\n";
				echo "<td class='center'>".$menu_item_category."&nbsp;</td>\n";
				if (permission_exists('menu_item_edit')) {
					echo "<td class='no-link center'>\n";
					echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($menu_item_protected == 'true' ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
				}
				else {
					echo "<td class='center'>\n";
					echo $text['label-'.($menu_item_protected == 'true' ? 'true' : 'false')];
				}
				echo "</td>\n";
				echo "<td class='center'>".$row['menu_item_order']."&nbsp;</td>\n";

				//echo "<td align='center' nowrap>";
				//if (permission_exists('menu_edit')) {
				//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_up.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row['menu_item_parent_uuid']."&menu_item_uuid=".$menu_item_uuid."&menu_item_order=".$row['menu_item_order']."'\" value='<' title='".$row['menu_item_order'].". ".$text['button-move_up']."'>";
				//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_down.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row['menu_item_parent_uuid']."&menu_item_uuid=".$menu_item_uuid."&menu_item_order=".$row['menu_item_order']."'\" value='>' title='".$row['menu_item_order'].". ".$text['button-move_down']."'>";
				//}
				//echo "</td>";

				if (permission_exists('menu_item_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
					echo "<td class='action-button'>\n";
					echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
					echo "</td>\n";
				}
				echo "</tr>\n";
				$x++;

			//update the menu order
				if ($row['menu_item_order'] != $tmp_menu_item_order) {
					$array['menu_items'][0]['menu_item_uuid'] = $menu_item_uuid;
					$array['menu_items'][0]['menu_uuid'] = $menu_uuid;
					$array['menu_items'][0]['menu_item_title'] = $row['menu_item_title'];
					$array['menu_items'][0]['menu_item_order'] = $tmp_menu_item_order;
					//$database = new database;
					//$database->app_name = 'menu';
					//$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
					//$database->save($array);
					unset($array);
				}
				$tmp_menu_item_order++;

			//check for sub menus
				$menu_item_level = 0;
				if (is_uuid($menu_item_uuid)) {
					build_db_child_menu_list($db, $menu_item_level, $menu_item_uuid);
				}

		}
		unset($result);

	}

	echo "</table>\n";
	echo "<br><br>";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//make sub action bar sticky
	echo "<script>\n";

	echo "	window.addEventListener('scroll', function(){\n";
	echo "		action_bar_scroll('action_bar_sub', 280, heading_modify, heading_restore);\n";
	echo "	}, false);\n";

	echo "	function heading_modify() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'inline-block';\n";
	echo "	}\n";

	echo "	function heading_restore() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'none';\n";
	echo "	}\n";

//update number of menu items
	echo "	document.getElementById('num_rows').innerHTML = '".($x ?: 0)."';\n";

	echo "</script>\n";

?>