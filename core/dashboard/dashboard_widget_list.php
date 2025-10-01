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
	Portions created by the Initial Developer are Copyright (C) 2021-2025
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dashboard_widget_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (!empty($_POST['dashboard_widgets'])) {
		$action = $_POST['action'];
		$dashboard_uuid = $_POST['dashboard_uuid'];
		$dashboard_widgets = $_POST['dashboard_widgets'];
		$group_uuid = $_POST['group_uuid'];
	}

//process the http post data by action
	if (!empty($action) && !empty($dashboard_widgets)) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('dashboard_widget_edit')) {
					$obj = new dashboard;
					$obj->toggle_widgets($dashboard_widgets);
				}
				break;
			case 'delete':
				if (permission_exists('dashboard_widget_delete')) {
					$obj = new dashboard;
					$obj->delete_widgets($dashboard_widgets);
				}
				break;
			case 'group_widgets_add':
				if (permission_exists('dashboard_widget_edit')) {
					$obj = new dashboard;
					$obj->assign_widgets($dashboard_widgets, $dashboard_uuid, $group_uuid);
				}
				break;
			case 'group_widgets_delete':
				if (permission_exists('dashboard_widget_delete')) {
					$obj = new dashboard;
					$obj->unassign_widgets($dashboard_widgets, $dashboard_uuid, $group_uuid);
				}
				break;
		}

		//redirect the user
		header('Location: dashboard_edit.php?id='.urlencode($dashboard_uuid));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//get the count
	$sql = "select count(dashboard_widget_uuid) ";
	$sql .= "from v_dashboard_widgets ";
	$sql .= "where dashboard_uuid = :dashboard_uuid ";
	$parameters['dashboard_uuid'] = $dashboard_uuid;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//get the list
	$sql = "select \n";
	$sql .= "dashboard_widget_uuid, \n";
	$sql .= "widget_name, \n";
	$sql .= "widget_path, \n";
	$sql .= "widget_icon, \n";
	$sql .= "( \n";
	$sql .= "	select \n";
	$sql .= "	string_agg(g.group_name, ', ') \n";
	$sql .= "	from \n";
	$sql .= "	v_dashboard_widget_groups as dg, \n";
	$sql .= "	v_groups as g \n";
	$sql .= "	where \n";
	$sql .= "	dg.group_uuid = g.group_uuid \n";
	$sql .= "	and d.dashboard_widget_uuid = dg.dashboard_widget_uuid \n";
	$sql .= ") AS dashboard_widget_groups, \n";
	$sql .= "dashboard_widget_parent_uuid, \n";
	$sql .= "widget_order, \n";
	$sql .= "cast(widget_enabled as text), \n";
	$sql .= "widget_description \n";
	$sql .= "from v_dashboard_widgets as d \n";
	$sql .= "where dashboard_uuid = :dashboard_uuid ";
	$sql .= order_by($order_by, $order, 'widget_order, widget_name', 'asc');
	$sql .= limit_offset($rows_per_page ?? null, $offset ?? null);
	$parameters['dashboard_uuid'] = $dashboard_uuid;
	$result = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

	//get the list of widget uuids
	$widget_uuid_list = [];
	foreach ($result as $row) {
		$widget_uuid_list[] = $row['dashboard_widget_uuid'];
	}

	$widgets = [];
	foreach ($result as $row) {
		//skip child widgets unless the parent doesn't exist
		if (!empty($row['dashboard_widget_parent_uuid']) && in_array($row['dashboard_widget_parent_uuid'], $widget_uuid_list)) {
			continue;
		}

		//add the widget to the array
		$widgets[] = $row;

		//add child widgets under parent widgets
		if ($row['widget_path'] == 'dashboard/parent') {
			foreach ($result as $child) {
				if ($child['dashboard_widget_parent_uuid'] == $row['dashboard_widget_uuid']) {
					$widgets[] = $child;
				}
			}
		}
	}

	//get the group list
	$sql = "select group_uuid, group_name from v_groups ";
	$groups = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/core/dashboard/dashboard_widget_list.php');

//show the content
	echo "<form id='form_list' method='post' action='dashboard_widget_list.php'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='dashboard_uuid' value='".escape($dashboard_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar_sub'>\n";
	echo "	<div class='heading'><b>".$text['title-widgets']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo "	<select class='formfld revealed' id='group_uuid' name='group_uuid' style='display: none;'>\n";
	echo "		<option value=''>Select Group</option>\n";
	if (!empty($groups)) {
		foreach ($groups as $row) {
			echo "	<option value='".urlencode($row["group_uuid"])."'>".escape($row['group_name'])." ".escape($row['group_description'])."</option>\n";
		}
	}
	echo "	</select>\n";

	if (permission_exists('dashboard_widget_add') && !empty($widgets)) {
		echo button::create(['type'=>'button','label'=>$text['button-assign'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_group_widgets_add','class' => 'btn btn-default revealed','collapse'=>'hide-xs','style'=>'display: none;','onclick'=>"list_action_set('group_widgets_add'); list_form_submit('form_list');"]);
	}
	if (permission_exists('dashboard_widget_delete') && !empty($widgets)) {
		echo button::create(['type'=>'button','label'=>$text['button-unassign'],'icon'=>$_SESSION['theme']['button_icon_cancel'],'name'=>'btn_group_widgets_delete','class' => 'btn btn-default revealed','style'=>'display: none; margin-right: 35px;','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete-groups','btn_group_widgets_delete');"]);
	}
	if (permission_exists('dashboard_widget_delete') && !empty($widgets)) {
		echo modal::create(['id'=>'modal-delete-groups','type'=>'unassign', 'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_group_widgets_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('group_widgets_delete'); list_form_submit('form_list');"])]);
	}
	echo button::create(['type'=>'button','id'=>'action_bar_sub_button_back','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'collapse'=>'hide-xs','style'=>'margin-right: 15px; display: none;','link'=>'dashboard.php']);
	if (permission_exists('dashboard_widget_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','name'=>'btn_add','link'=>'dashboard_widget_edit.php?id='.escape($dashboard_uuid).'&widget_uuid='.escape($widget_uuid ?? null)]);
	}
	if (permission_exists('dashboard_widget_edit') && !empty($widgets)) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('dashboard_widget_delete') && !empty($widgets)) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if (!empty($paging_controls_mini)) {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('dashboard_widget_edit') && !empty($widgets)) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('dashboard_widget_delete') && !empty($widgets)) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('dashboard_widget_add') || permission_exists('dashboard_widget_edit') || permission_exists('dashboard_widget_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($widgets) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('widget_name', $text['label-widget_name'], $order_by, $order);
	echo th_order_by('dashboard_widget_groups', $text['label-widget_groups'], $order_by, $order);
	//echo th_order_by('widget_icon', $text['label-icons'], $order_by, $order);
	echo th_order_by('widget_order', $text['label-widget_order'], $order_by, $order);
	echo th_order_by('widget_enabled', $text['label-widget_enabled'], $order_by, $order, null, "class='center'");
	echo "	<th class='hide-sm-dn'>".$text['label-widget_description']."</th>\n";
	if (permission_exists('dashboard_widget_edit') && $settings->get('theme', 'list_row_edit_button', false)) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($widgets)) {
		$x = 0;
		foreach ($widgets as $row) {
			$list_row_url = '';
			if (permission_exists('dashboard_widget_edit')) {
				$list_row_url = "dashboard_widget_edit.php?id=".urlencode($dashboard_uuid)."&widget_uuid=".urlencode($row['dashboard_widget_uuid']);
				if (!empty($row['domain_uuid']) && $row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
				}
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('dashboard_widget_add') || permission_exists('dashboard_widget_edit') || permission_exists('dashboard_widget_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='dashboard_widgets[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='dashboard_widgets[$x][dashboard_widget_uuid]' value='".escape($row['dashboard_widget_uuid'])."' />\n";
				echo "	</td>\n";
			}
			$widget_icon = (!empty($row['widget_icon']) ? "<i class='fas ".$row['widget_icon']."' style='margin-left: 7px; margin-top: 2px; text-indent: initial; ".(!empty($row['widget_icon_color']) ? "color: ".$row['widget_icon_color'].";" : "opacity: 0.4;")."'></i>\n" : null);
			echo "	<td ".(!empty($row['dashboard_widget_parent_uuid']) && in_array($row['dashboard_widget_parent_uuid'], $widget_uuid_list) ? "style='text-indent: 1rem;'" : null).">\n";//indent child widgets
			if (permission_exists('dashboard_widget_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['widget_name'])."</a>\n";
				echo $widget_icon;
			}
			else {
				echo "	".escape($row['widget_name']);
				echo $widget_icon;
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['dashboard_widget_groups'])."</td>\n";
			//echo "	<td>".escape($row['widget_icon'])."</td>\n";
			echo "	<td>".escape($row['widget_order'])."</td>\n";
			if (permission_exists('dashboard_widget_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='dashboard_widgets[$x][widget_enabled]' value='".escape($row['widget_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['widget_enabled']?:'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['widget_enabled']?:'false')];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['widget_description'])."</td>\n";
			if (permission_exists('dashboard_widget_edit') && $settings->get('theme', 'list_row_edit_button', false)) {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($widgets);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".($paging_controls ?? '')."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//make sub action bar sticky
	echo "<script>\n";

	echo "	window.addEventListener('scroll', function(){\n";
	echo "		action_bar_scroll('action_bar_sub', 350, heading_modify, heading_restore);\n";
	echo "	}, false);\n";

	echo "	function heading_modify() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'inline-block';\n";
	echo "	}\n";

	echo "	function heading_restore() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'none';\n";
	echo "	}\n";

	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
