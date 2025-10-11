<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($settings->get('domain', 'language', 'en-us'), 'core/dashboard');

//prepare the settings
	$widget_content_length = strlen($widget_content);
	$widget_content_text_vertical_align = 'top';
	if ($widget_content_length < 30) { $widget_content_text_vertical_align = 'middle'; }
	$widget_content_height = $widget_row_span * 120 . 'px';

//escape the content and details
	$widget_content = escape($widget_content);
	$widget_content_details = escape($widget_content_details);

//allow line breaks
	$widget_content = str_replace('&lt;br &sol;&gt;', '<br />', $widget_content);
	$widget_content_details = str_replace('&lt;br &sol;&gt;', '<br />', $widget_content_details);

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(!empty($row['widget_background_color']) ? "style='background: ".$row['widget_background_color'].";'" : null)." ".(empty($widget_details_state) || $widget_details_state != "disabled" ? "onclick=\"$('#hud_content_details').slideToggle('fast');\"" : null).">\n";
	echo "		<span class='hud_title' ".(!empty($row['widget_label_background_color']) ? "style='background: ".$row['widget_label_background_color'].";'" : null).">".escape($widget_name)."</span>";
	echo "		<span style='padding: 12px; height: ".$widget_content_height."; max-height: ".$widget_content_height.";  text-align: ".$row['widget_content_text_align']."; vertical-align: ".$widget_content_text_vertical_align."; overflow: auto; ".(!empty($row['widget_number_text_color']) ? "color: ".$row['widget_number_text_color'].";" : null)."'>".$widget_content."</span>\n";
	echo "	</div>\n";
	if (empty($widget_details_state) || $widget_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_content_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['widget_detail_background_color']) ? "background: ".$row['widget_detail_background_color'].";" : null)."'>".$widget_content_details."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_content_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
