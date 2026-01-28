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
	$widget_label = $text['title-'.$widget_key] ?? $widget_name;

//prepare variables
	$widget_target = ($widget_target == 'new') ? '_blank' : '_self';
	$window_parameters = '';
	if (!empty($widget_width) && !empty($widget_height)) {
		$window_parameters .= "width=".$widget_width.",height=".$widget_height;
	}

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' onclick=\"".(empty($widget_details_state) || $widget_details_state == "disabled" ? "window.open('".$widget_url."', '".$widget_target."', '".$window_parameters."'); return false;" : "$('#hud_icon_details').slideToggle('fast');")."\">\n";
	echo "		<span class='hud_title'><a style='padding: 10px 0;' onclick=\"window.open('".$widget_url."', '".$widget_target."', '".$window_parameters."'); return false;\">".escape($widget_label)."</a></span>\n";
	echo "		<span class='hud_stat'><a style='padding: 10px 20px;' onclick=\"window.open('".$widget_url."', '".$widget_target."', '".$window_parameters."'); return false;\"><i class=\"fas ".$widget_icon."\"></i></a></span>\n";
	echo "	</div>\n";

	if (!empty($widget_details_state) && $widget_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['widget_detail_background_color']) ? "background: ".$row['widget_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($widget_content_details))."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
