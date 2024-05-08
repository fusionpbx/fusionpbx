<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/dashboard');

//dashboard icon
	echo "<div class='hud_box'>\n";

	echo "<div class='hud_container' ".($row['dashboard_details_state'] == "disabled" ?: "onclick=\"$('#hud_icon_details').slideToggle('fast');\"").">\n";
	echo "	<span class='hud_title' onclick=\"document.location.href='".$dashboard_url."'\">".$dashboard_name."</span>"; // (isset($text['label-'.$dashboard_name])) ? $text['label-'.$dashboard_name] : $dashboard_name
	echo "	<a href='".$dashboard_url."'><span class='hud_stat'><i class=\"fas ".$dashboard_icon."\" style=\"font-size: 0.8em;\"></i></span></a>\n";
	echo "</div>\n";

	if ($dashboard_detail_state != "disabled") {
		echo "<div class='hud_details hud_box' id='hud_icon_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<td>\n";
		echo "	&nbsp;\n";
		echo "</td>\n";
		echo "</table>\n";
		//echo "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?status=missed'>".$text['label-view_all']."</a></span>\n";
		echo "</div>";
		//$n++;
	}

	echo "<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
