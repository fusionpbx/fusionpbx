<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//convert to a key
	$widget_key = str_replace(' ', '_', strtolower($widget_name));

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], dirname($widget_url));

//get the dashboard label
	$widget_label = $text['title-'.$widget_key];
	if (empty($widget_label)) {
		$widget_label = $widget_name;
	}

//prepare variables
	$widget_target = ($widget_target == 'new') ? '_blank' : '_self';
	$window_parameters = '';
	if (!empty($widget_width) && !empty($widget_height)) {
		$window_parameters .= "width=".$widget_width.",height=".$widget_height;
	}

//get the domain count for enabled domains
	$sql = "select count(*) as count from v_domains ";
	$sql .= "where domain_enabled = true; ";
	$row = $database->select($sql, null, 'row');
	$domain_count = $row['count'];
	unset($sql, $row);

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(empty($widget_details_state) || $widget_details_state != "disabled" ? "onclick=\"$('#hud_icon_details').slideToggle('fast');\"" : null).">\n";
	echo "		<span class='hud_title'><a onclick=\"window.open('".$widget_url."', '".$widget_target."', '".$window_parameters."')\">".escape($widget_label)."</a></span>";
	echo "		<div style='position: relative; display: inline-block;'>\n";
	echo "			<span class='hud_stat' onclick=\"window.open('".$widget_url."', '".$widget_target."', '".$window_parameters."')\"><i class=\"fas ".$widget_icon."\"></i></span>\n";
	echo "			<span style=\"background-color: ".(!empty($widget_number_background_color) ? $widget_number_background_color : '#0292FF')."; color: ".(!empty($widget_number_text_color) ? $widget_number_text_color : '#ffffff')."; font-size: 12px; font-weight: bold; text-align: center; position: absolute; top: 22px; left: 25px; padding: 2px 7px 1px 7px; border-radius: 10px; white-space: nowrap;\">".$domain_count."</span>\n";
	echo "		</div>\n";
	echo "	</div>\n";
	if (empty($widget_details_state) || $widget_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['widget_detail_background_color']) ? "background: ".$row['widget_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($widget_content_details))."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
