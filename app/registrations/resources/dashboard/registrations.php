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

//channel count
	if ($esl == null) {
		$esl = event_socket::create();
	}

//registration count
	if ($esl->is_connected() && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/registrations/")) {
		$registration = new registrations;
		$active_registrations = $registration->count();
	}

//get the total enabled extensions
	$sql = "select count(*) as count from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true'; ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$row = $database->select($sql, $parameters, 'row');
	$enabled_extensions = $row['count'];
	unset($sql, $row);

//calculate the inactive extensions
	$inactive_registrations = $enabled_extensions - $active_registrations;

//dashboard icon
	echo "<div class='hud_box'>\n";
	echo "	<div class='hud_content' ".(empty($dashboard_details_state) || $dashboard_details_state != "disabled" ? "onclick=\"$('#hud_icon_details').slideToggle('fast'); toggle_grid_row_end('".trim(preg_replace("/[^a-z]/", '_', strtolower($row['dashboard_name'])),'_')."');\"" : null).">\n";
	echo "		<span class='hud_title' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."')\">".escape($dashboard_label)."</span>";
	echo "		<span class='hud_stat' onclick=\"window.open('".$dashboard_url."', '".$dashboard_target."', '".$window_parameters."')\"><i class=\"fas ".$dashboard_icon."\"></i></span>\n";
	echo "		<div style='color: #000000; font-size: 12px; font-weight: unset; text-align: center; position: relative; bottom: 50px; right: -90px; padding: 10px 10px;'>\n";
	echo "			<span class='' style=\"position: relative; \">Inactive: ".$inactive_registrations."</span>\n";
	echo "			<span class='' style=\"position: relative; \">Active: ".$active_registrations."</span>\n";
	echo "		</div>\n";
	echo "	</div>\n";
	if (empty($dashboard_details_state) || $dashboard_details_state != "disabled") {
		echo "	<div class='hud_details hud_box' id='hud_icon_details' style='padding: 20px; 10%; overflow: auto; ".(!empty($row['dashboard_detail_background_color']) ? "background: ".$row['dashboard_detail_background_color'].";" : null)."'>".str_replace("\r", '<br>', escape($dashboard_content_details))."</div>\n";
	}
	echo "	<span class='hud_expander' onclick=\"$('#hud_icon_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
