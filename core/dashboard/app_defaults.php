<?php

if ($domains_processed == 1) {

	//clear the array if it exists
		if (isset($array)) {
			unset($array);
		}

	//migrate widget data
		$sql = "select * from v_dashboard_widgets ";
		$new_table = $database->select($sql, null, 'all');
		unset($sql, $parameters);

		$sql = "select * from v_dashboard ";
		$old_table = $database->select($sql, null, 'all');
		unset($sql, $parameters);

		if (empty($new_table) && !empty($old_table)) {
			$sql = "INSERT INTO v_dashboard_widgets ( ";
			$sql .= "	dashboard_uuid, ";
			$sql .= "	dashboard_widget_uuid, ";
			$sql .= "	dashboard_widget_parent_uuid, ";
			$sql .= "	widget_name, ";
			$sql .= "	widget_path, ";
			$sql .= "	widget_icon, ";
			$sql .= "	widget_icon_color, ";
			$sql .= "	widget_url, ";
			$sql .= "	widget_target, ";
			$sql .= "	widget_width, ";
			$sql .= "	widget_height, ";
			$sql .= "	widget_content, ";
			$sql .= "	widget_content_text_align, ";
			$sql .= "	widget_content_details, ";
			$sql .= "	widget_chart_type, ";
			$sql .= "	widget_label_enabled, ";
			$sql .= "	widget_label_text_color, ";
			$sql .= "	widget_label_text_color_hover, ";
			$sql .= "	widget_label_background_color, ";
			$sql .= "	widget_label_background_color_hover, ";
			$sql .= "	widget_number_text_color, ";
			$sql .= "	widget_number_text_color_hover, ";
			$sql .= "	widget_number_background_color, ";
			$sql .= "	widget_background_color, ";
			$sql .= "	widget_background_color_hover, ";
			$sql .= "	widget_detail_background_color, ";
			$sql .= "	widget_background_gradient_style, ";
			$sql .= "	widget_background_gradient_angle, ";
			$sql .= "	widget_column_span, ";
			$sql .= "	widget_row_span, ";
			$sql .= "	widget_details_state, ";
			$sql .= "	widget_order, ";
			$sql .= "	widget_enabled, ";
			$sql .= "	widget_description, ";
			$sql .= "	insert_date, ";
			$sql .= "	insert_user, ";
			$sql .= "	update_date, ";
			$sql .= "	update_user ";
			$sql .= ") ";
			$sql .= "SELECT ";
			$sql .= "	'3e2cbaa4-2bec-41b2-a626-999a59b8b19c', ";
			$sql .= "	dashboard_uuid, ";
			$sql .= "	dashboard_parent_uuid, ";
			$sql .= "	dashboard_name, ";
			$sql .= "	dashboard_path, ";
			$sql .= "	dashboard_icon, ";
			$sql .= "	dashboard_icon_color, ";
			$sql .= "	dashboard_url, ";
			$sql .= "	dashboard_target, ";
			$sql .= "	dashboard_width, ";
			$sql .= "	dashboard_height, ";
			$sql .= "	dashboard_content, ";
			$sql .= "	dashboard_content_text_align, ";
			$sql .= "	dashboard_content_details, ";
			$sql .= "	dashboard_chart_type, ";
			$sql .= "	dashboard_label_enabled, ";
			$sql .= "	dashboard_label_text_color, ";
			$sql .= "	dashboard_label_text_color_hover, ";
			$sql .= "	dashboard_label_background_color, ";
			$sql .= "	dashboard_label_background_color_hover, ";
			$sql .= "	dashboard_number_text_color, ";
			$sql .= "	dashboard_number_text_color_hover, ";
			$sql .= "	dashboard_number_background_color, ";
			$sql .= "	dashboard_background_color, ";
			$sql .= "	dashboard_background_color_hover, ";
			$sql .= "	dashboard_detail_background_color, ";
			$sql .= "	dashboard_background_gradient_style, ";
			$sql .= "	dashboard_background_gradient_angle, ";
			$sql .= "	dashboard_column_span, ";
			$sql .= "	dashboard_row_span, ";
			$sql .= "	dashboard_details_state, ";
			$sql .= "	dashboard_order, ";
			$sql .= "	dashboard_enabled, ";
			$sql .= "	dashboard_description, ";
			$sql .= "	insert_date, ";
			$sql .= "	insert_user, ";
			$sql .= "	update_date, ";
			$sql .= "	update_user ";
			$sql .= "FROM v_dashboard; ";
			$database->execute($sql);
			unset($sql, $parameters);
		}

		$sql = "select * from v_dashboard_widget_groups ";
		$new_groups = $database->select($sql, null, 'all');
		unset($sql, $parameters);

		$sql = "select * from v_dashboard_groups ";
		$old_groups = $database->select($sql, null, 'all');
		unset($sql, $parameters);

		if (empty($new_groups) && !empty($old_groups)) {
			$sql = "INSERT INTO v_dashboard_widget_groups ( ";
			$sql .= "	dashboard_uuid, ";
			$sql .= "	dashboard_widget_group_uuid, ";
			$sql .= "	dashboard_widget_uuid, ";
			$sql .= "	group_uuid, ";
			$sql .= "	insert_date, ";
			$sql .= "	insert_user, ";
			$sql .= "	update_date, ";
			$sql .= "	update_user ";
			$sql .= ") ";
			$sql .= "SELECT ";
			$sql .= "	'3e2cbaa4-2bec-41b2-a626-999a59b8b19c', ";
			$sql .= "	dashboard_group_uuid, ";
			$sql .= "	dashboard_uuid, ";
			$sql .= "	group_uuid, ";
			$sql .= "	insert_date, ";
			$sql .= "	insert_user, ";
			$sql .= "	update_date, ";
			$sql .= "	update_user ";
			$sql .= "FROM v_dashboard_groups; ";
			$database->execute($sql);
			unset($sql, $parameters);
		}

	//make the default groups exist
		$group = new groups;
		$group->defaults();

	//get the groups
		$sql = "select * from v_groups ";
		$sql .= "where domain_uuid is null ";
		$groups = $database->select($sql, null, 'all');

	//get the dashboards
		$sql = "select ";
		$sql .= "domain_uuid, ";
		$sql .= "dashboard_uuid, ";
		$sql .= "dashboard_name, ";
		$sql .= "cast(dashboard_enabled as text), ";
		$sql .= "dashboard_description, ";
		$sql .= "domain_uuid ";
		$sql .= "from v_dashboards ";
		$dashboard_widgets = $database->select($sql, null, 'all');
		unset($sql, $parameters);

	//add the dashboards
		$dashboard_config_file = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/core/dashboard/resources/dashboard/config.php');
		$x = 0;
		foreach($dashboard_config_file as $file) {
			include ($file);
			$x++;
		}
		$dashboards = $array;
		unset($array);

	//build the array
		$x = 0;
		foreach($dashboards['dashboards'] as $row) {
			//check if the dashboard is already in the database
			$dashboard_found = false;
			foreach($dashboard_widgets as $dashboard_widget) {
				if ($dashboard_widget['dashboard_uuid'] == $row['dashboard_uuid']) {
					$dashboard_found = true;
				}
			}

			//add the dashboard to the array
			if (!$dashboard_found) {
				$array['dashboards'][$x]['dashboard_uuid'] = $row['dashboard_uuid'];
				$array['dashboards'][$x]['dashboard_name'] = $row['dashboard_name'];
				$array['dashboards'][$x]['dashboard_enabled'] = $row['dashboard_enabled'];
				$array['dashboards'][$x]['dashboard_description'] = $row['dashboard_description'];
				$x++;
			}
		}
//exit;
	//save the data
		if (!empty($array)) {
			$database->save($array, false);
		}
		unset($array);

	//get the dashboard
		$sql = "select ";
		$sql .= "dashboard_uuid, ";
		$sql .= "dashboard_widget_uuid, ";
		$sql .= "widget_name, ";
		$sql .= "widget_path, ";
		$sql .= "widget_order, ";
		$sql .= "cast(widget_enabled as text), ";
		$sql .= "widget_description ";
		$sql .= "from v_dashboard_widgets ";
		$dashboard_widgets = $database->select($sql, null, 'all');
		unset($sql, $parameters);

	//add the dashboard widgets
		$config_files = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/*/*/resources/dashboard/config.php');
		$x = 0;
		foreach($config_files as $file) {
			include ($file);
			$x++;
		}
		$widgets = $array;
		unset($array);

	//build the array
		$x = 0;
		foreach($widgets['dashboard_widgets'] as $row) {
			//check if the dashboard widget is already in the database
			$widget_found = false;
			foreach($dashboard_widgets as $dashboard_widget) {
				if ($dashboard_widget['dashboard_widget_uuid'] == $row['dashboard_widget_uuid']) {
					$widget_found = true;
				}
			}

			//add the dashboard widget to the array
			if (!$widget_found) {
				$array['dashboard_widgets'][$x]['dashboard_uuid'] = $row['dashboard_uuid'];
				$array['dashboard_widgets'][$x]['dashboard_widget_uuid'] = $row['dashboard_widget_uuid'];
				$array['dashboard_widgets'][$x]['widget_name'] = $row['widget_name'];
				$array['dashboard_widgets'][$x]['widget_path'] = $row['widget_path'];
				$array['dashboard_widgets'][$x]['widget_chart_type'] = $row['widget_chart_type'];
				$array['dashboard_widgets'][$x]['widget_column_span'] = $row['widget_column_span'] ?? 1;
				$array['dashboard_widgets'][$x]['widget_row_span'] = $row['widget_row_span'] ?? 1;
				$array['dashboard_widgets'][$x]['widget_details_state'] = $row['widget_details_state'];
				$array['dashboard_widgets'][$x]['widget_order'] = $row['widget_order'];
				$array['dashboard_widgets'][$x]['widget_enabled'] = $row['widget_enabled'];
				$array['dashboard_widgets'][$x]['widget_description'] = $row['widget_description'];
				$array['dashboard_widgets'][$x]['widget_label_enabled'] = $row['widget_label_enabled'] ?? 'true';
				if (!empty($row['widget_label_text_color'])) { $array['dashboard_widgets'][$x]['widget_label_text_color'] = $row['widget_label_text_color']; }
				if (!empty($row['widget_label_text_color_hover'])) { $array['dashboard_widgets'][$x]['widget_label_text_color_hover'] = $row['widget_label_text_color_hover']; }
				if (!empty($row['widget_number_text_color'])) { $array['dashboard_widgets'][$x]['widget_number_text_color'] = $row['widget_number_text_color']; }
				if (!empty($row['widget_number_text_color_hover'])) { $array['dashboard_widgets'][$x]['widget_number_text_color_hover'] = $row['widget_number_text_color_hover']; }
				if (!empty($row['widget_number_background_color'])) { $array['dashboard_widgets'][$x]['widget_number_background_color'] = $row['widget_number_background_color']; }
				if (!empty($row['widget_icon'])) { $array['dashboard_widgets'][$x]['widget_icon'] = $row['widget_icon']; }
				if (!empty($row['widget_icon_color'])) { $array['dashboard_widgets'][$x]['widget_icon_color'] = $row['widget_icon_color']; }
				if (!empty($row['widget_url'])) { $array['dashboard_widgets'][$x]['widget_url'] = $row['widget_url']; }
				if (!empty($row['widget_width'])) { $array['dashboard_widgets'][$x]['widget_width'] = $row['widget_width']; }
				if (!empty($row['widget_height'])) { $array['dashboard_widgets'][$x]['widget_height'] = $row['widget_height']; }
				if (!empty($row['widget_target'])) { $array['dashboard_widgets'][$x]['widget_target'] = $row['widget_target']; }
				if (!empty($row['widget_label_background_color'])) { $array['dashboard_widgets'][$x]['widget_label_background_color'] = $row['widget_label_background_color']; }
				if (!empty($row['widget_label_background_color_hover'])) { $array['dashboard_widgets'][$x]['widget_label_background_color_hover'] = $row['widget_label_background_color_hover']; }
				if (!empty($row['widget_background_color'])) { $array['dashboard_widgets'][$x]['widget_background_color'] = $row['widget_background_color']; }
				if (!empty($row['widget_background_color_hover'])) { $array['dashboard_widgets'][$x]['widget_background_color_hover'] = $row['widget_background_color_hover']; }
				if (!empty($row['widget_detail_background_color'])) { $array['dashboard_widgets'][$x]['widget_detail_background_color'] = $row['widget_detail_background_color']; }
				if (!empty($row['widget_content'])) { $array['dashboard_widgets'][$x]['widget_content'] = $row['widget_content']; }
				if (!empty($row['widget_content_details'])) { $array['dashboard_widgets'][$x]['widget_content_details'] = $row['widget_content_details']; }
				if (!empty($row['dashboard_widget_parent_uuid'])) { $array['dashboard_widgets'][$x]['dashboard_widget_parent_uuid'] = $row['dashboard_widget_parent_uuid']; }
				$y = 0;
				if (!empty($row['dashboard_widget_groups'])) {
					foreach ($row['dashboard_widget_groups'] as $row) {
						if (isset($row['group_name'])) {
							foreach($groups as $field) {
								if ($row['group_name'] == $field['group_name']) {
									$array['dashboard_widgets'][$x]['dashboard_widget_groups'][$y]['dashboard_uuid'] = $row['dashboard_uuid'];
									$array['dashboard_widgets'][$x]['dashboard_widget_groups'][$y]['dashboard_widget_group_uuid'] = $row['dashboard_widget_group_uuid'];
									$array['dashboard_widgets'][$x]['dashboard_widget_groups'][$y]['dashboard_widget_uuid'] = $row['dashboard_widget_uuid'];
									$array['dashboard_widgets'][$x]['dashboard_widget_groups'][$y]['group_uuid'] = $field['group_uuid'];
								}
							}
							$y++;
						}
					}
				}
			$x++;
			}
		}

	//add the temporary permissions
		$p = permissions::new();
		$p->add('dashboard_add', 'temp');
		$p->add('dashboard_group_add', 'temp');

	//save the data
		if (!empty($array)) {
			$database->save($array, false);
			//$result = $database->message;
		}

	//delete the temporary permissions
		$p->delete('dashboard_add', 'temp');
		$p->delete('dashboard_group_add', 'temp');

	//update dashboard icons to be prefixed with v6.x font awesome style class name (e.g. 'fa-solid ')
		$queries[] = "update v_dashboard_widgets set widget_icon = concat('fa-solid ', widget_icon) where widget_icon is not null and widget_icon not like 'fa-solid fa-%' and widget_icon not like 'fa-regular fa-%' and widget_icon not like 'fa-brands fa-%' ";

	//simplify the dashboard path
		$queries[] = "update v_dashboard_widgets set widget_path =  regexp_replace(widget_path, 'app/|core/|resources/dashboard/|\.php', '', 'g') where widget_path like '%.php';";

	//execute array of queries
		foreach ($queries as $sql) {
			$database->execute($sql);
		}
		unset($queries, $sql);

}

?>
