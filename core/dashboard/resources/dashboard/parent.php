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
	$sql .= "dashboard_icon_color, ";
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
	$sql .= "dashboard_number_background_color, ";
	$sql .= "dashboard_background_color, ";
	$sql .= "dashboard_background_color_hover, ";
	$sql .= "dashboard_detail_background_color, ";
	$sql .= "dashboard_background_gradient_style, ";
	$sql .= "dashboard_background_gradient_angle, ";
	$sql .= "dashboard_column_span, ";
	$sql .= "dashboard_row_span, ";
	$sql .= "dashboard_details_state, ";
	$sql .= "dashboard_order, ";
	$sql .= "cast(dashboard_enabled as text), ";
	$sql .= "dashboard_description ";
	$sql .= "from v_dashboard as d ";
	$sql .= "where dashboard_enabled = 'true' ";
	$sql .= "and dashboard_uuid in (";
	$sql .= "	select dashboard_uuid from v_dashboard_groups where group_uuid in (";
	$sql .= "		".$group_uuids_in." ";
	$sql .= "	)";
	$sql .= ")";
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

?>

<style>

div.parent_widgets {
	max-width: 100%;
	margin: 0 auto;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
	row-gap: 1rem;
}

div.parent_widget div.hud_box:first-of-type {
	/*min-width: 120px;*/
	background: rgba(0,0,0,0);
	border: 0px dashed rgba(0,0,0,0);
	box-shadow: none;
	overflow: visible;
	-webkit-transition: .1s;
	-moz-transition: .1s;
	transition: .1s;
}

div.parent_widget.editable div.hud_box:first-of-type {
	border: 1px dashed rgba(0,0,0,0.4);
}

div.parent_widget:not(:has(.parent_widgets)) .hud_content {
	align-content: center;
}

div.parent_widget div.hud_chart  {
	padding: 7px;
}

div.parent_widget:hover:has(i) div.hud_box,
div.parent_widget.editable:hover:has(i) div.hud_box {
	transform: scale(1.05, 1.05);
	-webkit-transition: .1s;
	-moz-transition: .1s;
	transition: .1s;
}

/* dashboard settings */
<?php
foreach ($parent_widgets as $row) {
	$dashboard_name = trim(preg_replace("/[^a-z0-9_]/", '_', strtolower($row['dashboard_name'])),'_');
	if (!empty($row['dashboard_icon_color'])) {
		echo "#".$dashboard_name." .hud_stat:has(i) {\n";
		echo "	color: ".$row['dashboard_icon_color'].";\n";
		echo "}\n";
	}
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
		echo "#".$dashboard_name." {\n";
		echo "	background: ".$background_color[0].";\n";
		echo "	background-image: linear-gradient(to right, ".$background_color[1]." 0%, ".$background_color[0]." 30%, ".$background_color[0]." 70%, ".$background_color[1]." 100%);\n";
		echo "}\n";
	}
	if (!empty($row['dashboard_background_color_hover'])) {
		$background_color_hover = json_decode($row['dashboard_background_color_hover'], true);
		echo "#".$dashboard_name.":hover {\n";
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
	if ($row['dashboard_path'] == "dashboard/icon") {
		echo "#".$dashboard_name.",\n";
		echo "#".$dashboard_name." span.hud_title,\n";
		echo "#".$dashboard_name." span.hud_stat {\n";
		echo "	transition: .4s;\n";
		echo "	border-radius: 5px;\n";
		echo "}\n";
	}
	if ($row['dashboard_column_span'] > 1) {
		echo "#".$dashboard_name.".parent_widget {\n";
		echo "	grid-column: span ".preg_replace($number_pattern, '', $row['dashboard_column_span']).";\n";
		echo "}\n";
	}
	else if ($row['dashboard_row_span'] > 1) {
		echo "#".$dashboard_name.".parent_widget {\n";
		echo "	grid-column: span 2;\n";
		echo "}\n";
	}
}
?>

</style>

<?php

//include the dashboards
	echo "<div class='hud_box' style='overflow-y: auto;'>\n";
	echo "	<div class='hud_content parent_widgets'>\n";

	$x = 0;
	foreach ($parent_widgets as $row) {
		//set the variables
		$dashboard_uuid = $row['dashboard_uuid'] ?? '';
		$dashboard_name = $row['dashboard_name'] ?? '';
		$dashboard_label = $row['dashboard_name'] ?? '';
		$dashboard_icon = $row['dashboard_icon'] ?? '';
		$dashboard_url = $row['dashboard_url'] ?? '';
		$dashboard_target = $row['dashboard_target'] ?? '';
		$dashboard_width = $row['dashboard_width'] ?? '';
		$dashboard_height = $row['dashboard_height'] ?? '';
		$dashboard_content = $row['dashboard_content'] ?? '';
		$dashboard_content_text_align = $row['dashboard_content_text_align'] ?? '';
		$dashboard_content_details = $row['dashboard_content_details'] ?? '';
		$dashboard_chart_type = $row['dashboard_chart_type'] ?? 'doughnut';
		$dashboard_label_text_color = $row['dashboard_label_text_color'] ?? $settings->get('theme', 'dashboard_label_text_color', '');
		$dashboard_number_text_color = $row['dashboard_number_text_color'] ?? $settings->get('theme', 'dashboard_number_text_color', '');
		$dashboard_number_background_color = $row['dashboard_number_background_color'] ?? $settings->get('theme', 'dashboard_number_background_color', '');
		$dashboard_details_state = $row['dashboard_details_state'] ?? 'disabled';
		$dashboard_row_span = $row['dashboard_row_span'] ?? 1;

		//define the regex patterns
		$uuid_pattern = '/[^-A-Fa-f0-9]/';
		$number_pattern = '/[^-A-Za-z0-9()*#]/';
		$text_pattern = '/[^a-zA-Z0-9 _\-\/.\?:\=#\n]/';

		//sanitize the data
		$dashboard_uuid = preg_replace($uuid_pattern, '', $dashboard_uuid);
		$dashboard_name = trim(preg_replace($text_pattern, '', $dashboard_name));
		$dashboard_name_id = trim(preg_replace("/[^a-z0-9_]/", '_', strtolower($dashboard_name)),'_');
		$dashboard_icon = preg_replace($text_pattern, '', $dashboard_icon);
		$dashboard_url = trim(preg_replace($text_pattern, '', $dashboard_url));
		$dashboard_target = trim(preg_replace($text_pattern, '', $dashboard_target));
		$dashboard_width = trim(preg_replace($text_pattern, '', $dashboard_width));
		$dashboard_height = trim(preg_replace($text_pattern, '', $dashboard_height));
		$dashboard_content = preg_replace($text_pattern, '', $dashboard_content);
		$dashboard_content = str_replace("\n", '<br />', $dashboard_content);
		$dashboard_content_text_align = trim(preg_replace($text_pattern, '', $dashboard_content_text_align));
		$dashboard_content_details = preg_replace($text_pattern, '', $dashboard_content_details);
		$dashboard_content_details = str_replace("\n", '<br />', $dashboard_content_details);
		$dashboard_chart_type = preg_replace($text_pattern, '', $dashboard_chart_type);
		$dashboard_label_text_color = preg_replace($text_pattern, '', $dashboard_label_text_color);
		$dashboard_number_text_color = preg_replace($text_pattern, '', $dashboard_number_text_color);
		$dashboard_number_background_color = preg_replace($text_pattern, '', $dashboard_number_background_color);
		$dashboard_details_state = preg_replace($text_pattern, '', $dashboard_details_state);
		$dashboard_row_span = preg_replace($number_pattern, '', $dashboard_row_span);
		$dashboard_path = preg_replace($text_pattern, '', strtolower($row['dashboard_path']));

		//find the application and widget
		$dashboard_path_array = explode('/', $dashboard_path);
		$application_name = $dashboard_path_array[0];
		$parent_widget_name = $dashboard_path_array[1];
		$path_array = glob(dirname(__DIR__, 4).'/*/'.$application_name.'/resources/dashboard/'.$parent_widget_name.'.php');

		echo "<div class='parent_widget' style='grid-row-end: span ".$dashboard_row_span.";' data-state='".$dashboard_details_state."'  onclick=\"".(!empty($dashboard_url && $dashboard_path == "dashboard/icon") ? "window.open('". $dashboard_url ."', '". $dashboard_target ."', '". $window_parameters ."')" : "")."\" id='".$dashboard_name_id."' draggable='false'>\n";
		if (file_exists($path_array[0])) {
			include $path_array[0];
		}
		echo "</div>\n";
	}

	echo "	</div>\n";
	//if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
	//	echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content_details))."</div>\n";
	//}
	//echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
