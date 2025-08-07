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
	border: 0px dashed rgba(0,0,0,0);
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
foreach ($parent_widgets as $row) {
	$dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_uuid']));
	if ($row['dashboard_path'] === "dashboard/icon" || $row['dashboard_chart_type'] === "icon") {
		echo "#".$dashboard_id.":hover div.hud_box:first-of-type,\n";
		echo "#".$dashboard_id.".editable:hover div.hud_box:first-of-type {\n";
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
	echo "<div class='hud_box' style='overflow-y: auto;'>\n";
	echo "	<div class='hud_content parent_widget'>\n";

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
		$dashboard_chart_type = $row['dashboard_chart_type'] ?? '';
		$dashboard_label_text_color = $row['dashboard_label_text_color'] ?? $settings->get('theme', 'dashboard_label_text_color', '');
		$dashboard_number_text_color = $row['dashboard_number_text_color'] ?? $settings->get('theme', 'dashboard_number_text_color', '');
		$dashboard_number_background_color = $row['dashboard_number_background_color'] ?? $settings->get('theme', 'dashboard_number_background_color', '');
		$dashboard_details_state = $row['dashboard_details_state'] ?? 'disabled';
		$dashboard_row_span = $row['dashboard_row_span'] ?? '';

		//define the regex patterns
		$uuid_pattern = '/[^-A-Fa-f0-9]/';
		$number_pattern = '/[^-A-Za-z0-9()*#]/';
		$text_pattern = '/[^a-zA-Z0-9 _\-\/.\?:\=#\n]/';

		//sanitize the data
		$dashboard_uuid = preg_replace($uuid_pattern, '', $dashboard_uuid);
		$dashboard_id = 'id_'.md5($dashboard_uuid);
		$dashboard_name = trim(preg_replace($text_pattern, '', $dashboard_name));
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
		$child_widget_name = $dashboard_path_array[1];
		$path_array = glob(dirname(__DIR__, 4).'/*/'.$application_name.'/resources/dashboard/'.$child_widget_name.'.php');

		echo "<div class='child_widget ".$dashboard_details_state."' id='".$dashboard_id."' draggable='false'>\n";
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
