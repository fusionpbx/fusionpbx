<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/dashboard');

//prepare the settings
	$dashboard_content_length = strlen($dashboard_content);
	$dashboard_content_text_vertical_align = 'top';
	if ($dashboard_content_length < 30) { $dashboard_content_text_vertical_align = 'middle'; }
	$dashboard_content_height = $dashboard_row_span * 120 . 'px';

//escape the content and details
	$dashboard_content = escape($dashboard_content);
	$dashboard_content_details = escape($dashboard_content_details);

//allow line breaks
	$dashboard_content = str_replace('&lt;br &sol;&gt;', '<br />', $dashboard_content);
	$dashboard_content_details = str_replace('&lt;br &sol;&gt;', '<br />', $dashboard_content_details);

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(!empty($row['dashboard_background_color']) ? "style='background: ".$row['dashboard_background_color'].";'" : null)." ".(empty($dashboard_details_state) || $dashboard_details_state != "disabled" ? "onclick=\"$('#hud_content_details').slideToggle('fast');\"" : null).">\n";
	echo "		<span class='hud_title' ".(!empty($row['dashboard_label_background_color']) ? "style='background: ".$row['dashboard_label_background_color'].";'" : null).">".escape($dashboard_name)."</span>";
	echo "		<span style='padding: 12px; height: ".$dashboard_content_height."; max-height: ".$dashboard_content_height.";  text-align: ".$row['dashboard_content_text_align']."; vertical-align: ".$dashboard_content_text_vertical_align."; overflow: auto; ".(!empty($row['dashboard_number_text_color']) ? "color: ".$row['dashboard_number_text_color'].";" : null)."'>".$dashboard_content."</span>\n";
	echo "	</div>\n";
	if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_content_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".$dashboard_content_details."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_content_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
