<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//convert to a key
	$dashboard_key = str_replace(' ', '_', strtolower($dashboard_name));

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], dirname($dashboard_url));

//get the dashboard label
	$dashboard_label = $text['title-'.$dashboard_key];
	if (empty($dashboard_label)) {
		$dashboard_label = $dashboard_name;
	}

//get the list
	$sql = "select ";
	$sql .= "dashboard_uuid, ";
	$sql .= "dashboard_name, ";
	$sql .= "dashboard_path, ";
	$sql .= "dashboard_icon, ";
	$sql .= "dashboard_url, ";
	$sql .= "dashboard_target, ";
	$sql .= "dashboard_width, ";
	$sql .= "dashboard_height, ";
	$sql .= "dashboard_content, ";
	$sql .= "dashboard_content_text_align, ";
	$sql .= "dashboard_content_details, ";
	$sql .= "dashboard_chart_type, ";
	$sql .= "cast(dashboard_label_enabled as text), ";
	$sql .= "dashboard_label_text_color, ";
	$sql .= "dashboard_label_text_color_hover, ";
	$sql .= "dashboard_label_background_color, ";
	$sql .= "dashboard_label_background_color_hover, ";
	$sql .= "dashboard_number_text_color, ";
	$sql .= "dashboard_number_text_color_hover, ";
	$sql .= "dashboard_background_color, ";
	$sql .= "dashboard_background_color_hover, ";
	$sql .= "dashboard_detail_background_color, ";
	$sql .= "dashboard_column_span, ";
	$sql .= "dashboard_row_span, ";
	$sql .= "dashboard_details_state, ";
	$sql .= "dashboard_order, ";
	$sql .= "cast(dashboard_enabled as text), ";
	$sql .= "dashboard_description ";
	$sql .= "from v_dashboard as d ";
	$sql .= "where dashboard_enabled = 'true' ";
	$sql .= "and dashboard_parent_uuid = :dashboard_uuid ";
	$sql .= "order by dashboard_order, dashboard_name asc ";
	$parameters['dashboard_uuid'] = $dashboard_uuid;
	$parent_widgets = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//prepare variables
	$dashboard_target = ($dashboard_target == 'new') ? '_blank' : '_self';
	$window_parameters = '';
	if (!empty($dashboard_width) && !empty($dashboard_height)) {
		$window_parameters .= "width=".$dashboard_width.",height=".$dashboard_height;
	}

//dashboard settings
	echo "<style>\n";
	foreach ($parent_widgets as $row) {
		$dashboard_name = trim(preg_replace("/[^a-z]/", '_', strtolower($row['dashboard_name'])),'_');
		if (!empty($row['dashboard_label_text_color']) || !empty($row['dashboard_label_background_color'])) {
			echo "#".$dashboard_name." .hud_title {\n";
			if (!empty($row['dashboard_label_text_color'])) { echo "	color: ".$row['dashboard_label_text_color'].";\n"; }
			if (!empty($row['dashboard_label_background_color'])) { echo "	background-color: ".$row['dashboard_label_background_color'].";\n"; }
			echo "}\n";
		}
		if (!empty($row['dashboard_label_text_color_hover']) || !empty($row['dashboard_label_background_color_hover'])) {
			echo "#".$dashboard_name.":hover .hud_title {\n";
			if (!empty($row['dashboard_label_text_color_hover'])) { echo "	color: ".$row['dashboard_label_text_color_hover'].";\n"; }
			if (!empty($row['dashboard_label_background_color_hover'])) { echo "	background-color: ".$row['dashboard_label_background_color_hover'].";\n"; }
			echo "}\n";
		}
		if (!empty($row['dashboard_number_text_color'])) {
			echo "#".$dashboard_name." .hud_stat {\n";
			echo "	color: ".$row['dashboard_number_text_color'].";\n";
			echo "}\n";
		}
		if (!empty($row['dashboard_number_text_color_hover'])) {
			echo "#".$dashboard_name.":hover .hud_stat {\n";
			echo "	color: ".$row['dashboard_number_text_color_hover'].";\n";
			echo "}\n";
		}
		if (!empty($row['dashboard_background_color'])) {
			$background_color = json_decode($row['dashboard_background_color'], true);
			echo "#".$dashboard_name." .hud_content {\n";
			echo "	background: ".$background_color[0].";\n";
			echo "	background-image: linear-gradient(to right, ".$background_color[1]." 0%, ".$background_color[0]." 30%, ".$background_color[0]." 70%, ".$background_color[1]." 100%);\n";
			echo "}\n";
		}
		if (!empty($row['dashboard_background_color_hover'])) {
			$background_color_hover = json_decode($row['dashboard_background_color_hover'], true);
			echo "#".$dashboard_name.":hover .hud_content {\n";
			echo "	background: ".$background_color_hover[0].";\n";
			echo "	background-image: linear-gradient(to right, ".$background_color_hover[1]." 0%, ".$background_color_hover[0]." 30%, ".$background_color_hover[0]." 70%, ".$background_color_hover[1]." 100%);\n";
			echo "}\n";
		}
		if (!empty($row['dashboard_detail_background_color'])) {
			$detail_background_color = json_decode($row['dashboard_detail_background_color'], true);
			echo "#".$dashboard_name." .hud_details {\n";
			echo "	background: ".$detail_background_color[0].";\n";
			echo "	background-image: linear-gradient(to right, ".$detail_background_color[1]." 0%, ".$detail_background_color[0]." 30%, ".$detail_background_color[0]." 70%, ".$detail_background_color[1]." 100%);\n";
			echo "}\n";
		}
		if ($row['dashboard_label_enabled'] == 'false') {
			echo "#".$dashboard_name." .hud_title {\n";
			echo "	display: none;\n";
			echo "}\n";
			echo "#".$dashboard_name." .hud_content {\n";
			echo "	align-content: center;\n";
			echo "}\n";
		}
		if ($row['dashboard_path'] == "core/dashboard/resources/dashboard/icon.php") {
			echo "#".$dashboard_name." div.hud_content,\n";
			echo "#".$dashboard_name." span.hud_title,\n";
			echo "#".$dashboard_name." span.hud_stat {\n";
			echo "	transition: .4s;\n";
			echo "}\n";
		}
		switch ($row['dashboard_row_span']) {
			case 1:
				echo "#".$dashboard_name." .hud_content {\n";
				echo "	height: 89.5px;\n";
				echo "}\n";
				echo "#".$dashboard_name." .hud_stat {\n";
				echo "	line-height: 0;\n";
				echo "	font-size: 30pt;\n";
				echo "}\n";
				echo "#".$dashboard_name." .hud_chart {\n";
				echo "	height: 54px;\n";
				echo "	width: 180px;\n";
				echo "	padding-top: 0;\n";
				echo "}\n";
				echo "#".$dashboard_name." div.hud_content .fas {\n";
				echo "	line-height: 0;\n";
				echo "	font-size: 24pt;\n";
				echo "}\n";
				break;
			case 2:
				echo "#".$dashboard_name." .hud_content {\n";
				echo "	height: 195px;\n";
				echo "}\n";
				break;
			case 3:
				echo "#".$dashboard_name." .hud_content {\n";
				echo "	height: 300.5px;\n";
				echo "}\n";
				break;
		}
	}
	echo "</style>\n";

//include the dashboards
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' style='flex-direction: row; justify-content: center; align-content: start; width: 100%;' ".(empty($dashboard_details_state) || $dashboard_details_state != "disabled" ? "onclick=\"$('#hud_icon_details').slideToggle('fast'); toggle_grid_row_end('".trim(preg_replace("/[^a-z]/", '_', strtolower($row['dashboard_name'])),'_')."');\"" : null).">\n";

	$x = 0;
	foreach ($parent_widgets as $row) {
		$dashboard_name = $row['dashboard_name'];
		$dashboard_label = $row['dashboard_name'];
		$dashboard_icon = $row['dashboard_icon'] ?? '';
		$dashboard_url = $row['dashboard_url'] ?? '';
		$dashboard_target = $row['dashboard_target'] ?? '';
		$dashboard_width = $row['dashboard_width'] ?? '';
		$dashboard_height = $row['dashboard_height'] ?? '';
		$dashboard_content = $row['dashboard_content'] ?? '';
		$dashboard_content_text_align = $row['dashboard_content_text_align'] ?? '';
		$dashboard_content_details = $row['dashboard_content_details'] ?? '';
		//$dashboard_chart_type = $row['dashboard_chart_type'] ?? "doughnut";
		$dashboard_label_text_color = $row['dashboard_label_text_color'] ?? $settings->get('theme', 'dashboard_label_text_color');
		$dashboard_number_text_color = $row['dashboard_number_text_color'] ?? $settings->get('theme', 'dashboard_number_text_color');
		//$dashboard_details_state = $row['dashboard_details_state'] ?? "expanded";
		//$dashboard_row_span = $row['dashboard_row_span'] ?? 2;
		//if ($dashboard_details_state == "expanded") {
		//	$dashboard_row_span += 3;
		//}

		echo "<div class='widget' style='width: 120px; margin: 13px;' id='".trim(preg_replace("/[^a-z]/", '_', strtolower($dashboard_name)),'_')."' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."')\" draggable='false'>\n";
		echo "	<span class='hud_title'>".escape($dashboard_label)."</span>";
		echo "	<span class='hud_stat' style='padding: 0;'><i class=\"fas ".$dashboard_icon."\" style='font-size: 24pt;'></i></span>\n";
		echo "</div>\n";
	}

	echo "	</div>\n";
	//if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
	//	echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content_details))."</div>\n";
	//}
	//echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
