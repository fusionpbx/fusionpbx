<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//convert to a key
	$widget_key = str_replace(' ', '_', strtolower($widget_name));

//add multi-lingual support
	$language = new text;
	$text = $language->get($settings->get('domain', 'language', 'en-us'), dirname($widget_url));

//get the dashboard label
	$widget_label = $text['title-'.$widget_key] ?? '';
	if (empty($widget_label)) {
		$widget_label = $widget_name;
	}

//get the list
	$sql = "select ";
	$sql .= "dashboard_uuid, ";
	$sql .= "dashboard_widget_uuid, ";
	$sql .= "widget_name, ";
	$sql .= "widget_path, ";
	$sql .= "widget_icon, ";
	$sql .= "widget_icon_color, ";
	$sql .= "widget_url, ";
	$sql .= "widget_target, ";
	$sql .= "widget_width, ";
	$sql .= "widget_height, ";
	$sql .= "widget_content, ";
	$sql .= "widget_content_text_align, ";
	$sql .= "widget_content_details, ";
	$sql .= "widget_chart_type, ";
	$sql .= "cast(widget_label_enabled as text), ";
	$sql .= "widget_label_text_color, ";
	$sql .= "widget_label_text_color_hover, ";
	$sql .= "widget_label_background_color, ";
	$sql .= "widget_label_background_color_hover, ";
	$sql .= "widget_number_text_color, ";
	$sql .= "widget_number_text_color_hover, ";
	$sql .= "widget_number_background_color, ";
	$sql .= "widget_background_color, ";
	$sql .= "widget_background_color_hover, ";
	$sql .= "widget_detail_background_color, ";
	$sql .= "widget_background_gradient_style, ";
	$sql .= "widget_background_gradient_angle, ";
	$sql .= "widget_column_span, ";
	$sql .= "widget_row_span, ";
	$sql .= "widget_details_state, ";
	$sql .= "widget_order, ";
	$sql .= "cast(widget_enabled as text), ";
	$sql .= "widget_description ";
	$sql .= "from v_dashboard_widgets as d ";
	$sql .= "where widget_enabled = 'true' ";
	$sql .= "and dashboard_widget_uuid in (";
	$sql .= "	select dashboard_widget_uuid from v_dashboard_widget_groups where group_uuid in (";
	$sql .= "		".$group_uuids_in." ";
	$sql .= "	)";
	$sql .= ")";
	$sql .= "and dashboard_widget_parent_uuid = :dashboard_widget_uuid ";
	$sql .= "order by widget_order, widget_name asc ";
	$parameters['dashboard_widget_uuid'] = $widget_uuid;
	$child_widgets = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

?>

<style>

div.parent_widget {
	max-width: 100%;
	margin: 0 auto;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
	row-gap: 1rem;
}

div.child_widget div.hud_box:first-of-type {
	background: rgba(0,0,0,0);
	border: 1px dashed rgba(0,0,0,0);
	box-shadow: none;
	overflow: visible;
	-webkit-transition: .1s;
	-moz-transition: .1s;
	transition: .1s;
}

div.child_widget.editable div.hud_box:first-of-type {
	border: 1px dashed rgba(0,0,0,0.4);
}

div.child_widget .hud_content {
	align-content: center;
	border-radius: 5px;
}

div.child_widget div.hud_chart  {
	padding: 7px;
}

/* dashboard settings */
<?php
foreach ($child_widgets as $row) {
	$widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_widget_uuid']));
	if ($row['widget_path'] === "dashboard/icon" || $row['widget_chart_type'] === "icon") {
		echo "#".$widget_id.":hover div.hud_box:first-of-type,\n";
		echo "#".$widget_id.".editable:hover div.hud_box:first-of-type {\n";
		echo "	transform: scale(1.05, 1.05);\n";
		echo "	-webkit-transition: .1s;\n";
		echo "	-moz-transition: .1s;\n";
		echo "	transition: .1s;\n";
		echo "}\n";
	}
}
?>

</style>

<?php

//include the dashboards
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content parent_widget'>\n";

	$x = 0;
	foreach ($child_widgets as $row) {
		//set the variables
		$widget_uuid = $row['dashboard_widget_uuid'] ?? '';
		$widget_name = $row['widget_name'] ?? '';
		$widget_label = $row['widget_name'] ?? '';
		$widget_icon = $row['widget_icon'] ?? '';
		$widget_url = $row['widget_url'] ?? '';
		$widget_target = $row['widget_target'] ?? '';
		$widget_width = $row['widget_width'] ?? '';
		$widget_height = $row['widget_height'] ?? '';
		$widget_content = $row['widget_content'] ?? '';
		$widget_content_text_align = $row['widget_content_text_align'] ?? '';
		$widget_content_details = $row['widget_content_details'] ?? '';
		$widget_chart_type = $row['widget_chart_type'] ?? '';
		$widget_label_text_color = $row['widget_label_text_color'] ?? $settings->get('theme', 'dashboard_label_text_color', '');
		$widget_number_text_color = $row['widget_number_text_color'] ?? $settings->get('theme', 'dashboard_number_text_color', '');
		$widget_number_background_color = $row['widget_number_background_color'] ?? $settings->get('theme', 'dashboard_number_background_color', '');
		$widget_details_state = $row['widget_details_state'] ?? 'disabled';
		$widget_row_span = $row['widget_row_span'] ?? '';

		//define the regex patterns
		$uuid_pattern = '/[^-A-Fa-f0-9]/';
		$number_pattern = '/[^-A-Za-z0-9()*#]/';
		$text_pattern = '/[^a-zA-Z0-9 _\-\/.\?:\=#\n]/';

		//sanitize the data
		$widget_uuid = preg_replace($uuid_pattern, '', $widget_uuid);
		$widget_id = 'id_'.md5($widget_uuid);
		$widget_name = trim(preg_replace($text_pattern, '', $widget_name));
		$widget_icon = preg_replace($text_pattern, '', $widget_icon);
		$widget_url = trim(preg_replace($text_pattern, '', $widget_url));
		$widget_target = trim(preg_replace($text_pattern, '', $widget_target));
		$widget_width = trim(preg_replace($text_pattern, '', $widget_width));
		$widget_height = trim(preg_replace($text_pattern, '', $widget_height));
		$widget_content = preg_replace($text_pattern, '', $widget_content);
		$widget_content = str_replace("\n", '<br />', $widget_content);
		$widget_content_text_align = trim(preg_replace($text_pattern, '', $widget_content_text_align));
		$widget_content_details = preg_replace($text_pattern, '', $widget_content_details);
		$widget_content_details = str_replace("\n", '<br />', $widget_content_details);
		$widget_chart_type = preg_replace($text_pattern, '', $widget_chart_type);
		$widget_label_text_color = preg_replace($text_pattern, '', $widget_label_text_color);
		$widget_number_text_color = preg_replace($text_pattern, '', $widget_number_text_color);
		$widget_number_background_color = preg_replace($text_pattern, '', $widget_number_background_color);
		$widget_details_state = preg_replace($text_pattern, '', $widget_details_state);
		$widget_row_span = preg_replace($number_pattern, '', $widget_row_span);
		$widget_path = preg_replace($text_pattern, '', strtolower($row['widget_path']));

		//find the application and widget
		$widget_path_array = explode('/', $widget_path);
		$application_name = $widget_path_array[0];
		$child_widget_name = $widget_path_array[1];
		$path_array = glob(dirname(__DIR__, 4).'/*/'.$application_name.'/resources/dashboard/'.$child_widget_name.'.php');

		echo "<div class='child_widget ".$widget_details_state."' id='".$widget_id."' draggable='false'>\n";
		if (file_exists($path_array[0])) {
			include $path_array[0];
		}
		echo "</div>\n";
	}

	echo "	</div>\n";
	//if (empty($widget_details_state) || $widget_details_state != "disabled") {
	//	echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['widget_detail_background_color']) ? "background: ".$row['widget_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($widget_content_details))."</div>\n";
	//}
	//echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
