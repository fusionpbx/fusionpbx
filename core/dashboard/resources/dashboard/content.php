<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/dashboard');

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(!empty($row['dashboard_background_color']) ? "style='background: ".$row['dashboard_background_color'].";'" : null)." ".(empty($dashboard_details_state) || $dashboard_details_state != "disabled" ? "onclick=\"$('#hud_content_details').slideToggle('fast'); toggle_grid_row_end('".trim(preg_replace("/[^a-z]/", '_', strtolower($row['dashboard_name'])),'_')."');\"" : null).">\n";
	echo "		<span class='hud_title' ".(!empty($row['dashboard_heading_background_color']) ? "style='background: ".$row['dashboard_heading_background_color'].";'" : null).">".escape($dashboard_name)."</span>";
	echo "		<span style='padding: 5%; height: 150px; max-height: 150px; text-align: ".$row['dashboard_content_text_align']."; vertical-align: middle; overflow: auto; ".(!empty($row['dashboard_number_text_color']) ? "color: ".$row['dashboard_number_text_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content))."</span>\n";
	echo "	</div>\n";
	if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_content_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content_details))."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_content_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>