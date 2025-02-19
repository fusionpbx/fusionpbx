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

//prepare variables
	$dashboard_target = ($dashboard_target == 'new') ? '_blank' : '_self';
	$window_parameters = '';
	if (!empty($dashboard_width) && !empty($dashboard_height)) {
		$window_parameters .= "width=".$dashboard_width.",height=".$dashboard_height;
	}

//get the domain count for enabled domains
	$sql = "select count(*) as count from v_domains ";
	$sql .= "where domain_enabled = 'true'; ";
	$row = $database->select($sql, null, 'row');
	$domain_count = $row['count'];
	unset($sql, $row);

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(empty($dashboard_details_state) || $dashboard_details_state != "disabled" ? "onclick=\"$('#hud_icon_details').slideToggle('fast'); toggle_grid_row_end('".trim(preg_replace("/[^a-z]/", '_', strtolower($row['dashboard_name'])),'_')."');\"" : null).">\n";
	echo "		<span class='hud_title' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."')\">".escape($dashboard_label)."</span>";
	echo "		<div style='position: relative; display: inline-block;'>\n";
	echo "			<span class='hud_stat' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."')\"><i class=\"fas ".$dashboard_icon."\"></i></span>\n";
	echo "			<span style=\"background-color: ".(!empty($dashboard_number_background_color) ? $dashboard_number_background_color : '#0292FF')."; color: ".(!empty($dashboard_number_text_color) ? $dashboard_number_text_color : '#ffffff')."; font-size: 12px; font-weight: bold; text-align: center; position: absolute; top: 22px; left: 25px; padding: 2px 7px 1px 7px; border-radius: 10px; white-space: nowrap;\">".$domain_count."</span>\n";
	echo "		</div>\n";
	echo "	</div>\n";
	if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content_details))."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
