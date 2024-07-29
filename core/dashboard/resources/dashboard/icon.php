<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/dashboard');

//prepare variables
	$dashboard_target = ($dashboard_target == 'new') ? '_blank' : '_self';
	$window_parameters = 'aaa=bbb,';
	if (!empty($dashboard_width) && !empty($dashboard_height)) {
		$window_parameters .= "width=".$dashboard_width.",height=".$dashboard_height;
	}

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(empty($dashboard_details_state) || $dashboard_details_state != "disabled" ? "onclick=\"$('#hud_icon_details').slideToggle('fast'); toggle_grid_row_end('".trim(preg_replace("/[^a-z]/", '_', strtolower($row['dashboard_name'])),'_')."');\"" : null).">\n";
	echo "		<span class='hud_title' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."')\">".escape($dashboard_name)."</span>";
	echo "		<span class='hud_stat' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."')\"><i class=\"fas ".$dashboard_icon."\" style=\"font-size: 0.8em;\"></i></span>\n";
	echo "	</div>\n";
	if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content_details))."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
