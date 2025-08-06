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
	$dashboard_label = $text['title-'.$dashboard_key] ?? $dashboard_name;

//prepare variables
	$dashboard_target = ($dashboard_target == 'new') ? '_blank' : '_self';
	$window_parameters = '';
	if (!empty($dashboard_width) && !empty($dashboard_height)) {
		$window_parameters .= "width=".$dashboard_width.",height=".$dashboard_height;
	}

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(empty($dashboard_details_state) || $dashboard_details_state != "disabled" ? "onclick=\"$('#hud_icon_details').slideToggle('fast');\"" : null).">\n";
	echo "		<span class='hud_title'><a style='padding: 10px 0;' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."'); return false;\">".escape($dashboard_label)."</a></span>\n";
	echo "		<span class='hud_stat'><a style='padding: 10px 20px;' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."'); return false;\"><i class=\"fas ".$dashboard_icon."\"></i></a></span>\n";
	echo "	</div>\n";

	if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content_details))."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
