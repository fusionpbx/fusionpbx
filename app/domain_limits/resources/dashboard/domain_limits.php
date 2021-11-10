<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permisions
	require_once "resources/check_auth.php";
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

//domain limits
	if (is_array($_SESSION['limit']) && sizeof($_SESSION['limit']) > 0) {
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		$show_stat = true;
		if (permission_exists('extension_view')) {
			$sql = "select count(extension_uuid) from v_extensions ";
			$database = new database;
			$extension_total = $database->select($sql, null, 'column');

			$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/extensions/extensions.php'\"";
			$hud_stat = $extension_total;
			$hud_stat_title = $text['label-total_extensions'];
		}
		else if (permission_exists('destination_view')) {
			$sql = "select count(destination_uuid) from v_destinations ";
			$database = new database;
			$destination_total = $database->select($sql, null, 'column');

			$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/destinations/destinations.php'\"";
			$hud_stat = $destination_total;
			$hud_stat_title = $text['label-total_destinations'];
		}
		else {
			$show_stat = false;
		}

		echo "<span class='hud_title' ".$onclick.">".$text['label-domain_limits']."</span>";

		if ($show_stat) {
			echo "<span class='hud_stat' onclick=\"$('#hud_domain_limits_details').slideToggle('fast');\">".$hud_stat."</span>";
			echo "<span class='hud_stat_title' onclick=\"$('#hud_domain_limits_details').slideToggle('fast');\">".$hud_stat_title."</span>\n";
		}

		echo "<div class='hud_details hud_box' id='hud_domain_limits_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th class='hud_heading' width='50%'>".$text['label-feature']."</th>\n";
		echo "<th class='hud_heading' width='50%' style='text-align: center;'>".$text['label-used']."</th>\n";
		echo "<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>\n";
		echo "</tr>\n";

		foreach ($_SESSION['limit'] as $category => $value) {
			$limit = $value['numeric'];
			switch ($category) {
				case 'users':
					if (!permission_exists('user_view')) { continue 2; }
					$url = '/core/users/users.php';
					break;
				case 'call_center_queues':
					if (!permission_exists('call_center_active_view')) { continue 2; }
					$url = '/app/call_centers/call_center_queues.php';
					break;
				case 'destinations':
					if (!permission_exists('destination_view')) { continue 2; }
					$url = '/app/destinations/destinations.php';
					break;
				case 'devices':
					if (!permission_exists('device_view')) { continue 2; }
					$url = '/app/devices/devices.php';
					break;
				case 'extensions':
					if (!permission_exists('extension_view')) { continue 2; }
					$url = '/app/extensions/extensions.php';
					break;
				case 'gateways':
					if (!permission_exists('gateway_view')) { continue 2; }
					$url = '/app/gateways/gateways.php';
					break;
				case 'ivr_menus':
					if (!permission_exists('ivr_menu_view')) { continue 2; }
					$url = '/app/ivr_menus/ivr_menus.php';
					break;
				case 'ring_groups':
					if (!permission_exists('ring_group_view')) { continue 2; }
					$url = '/app/ring_groups/ring_groups.php';
					break;
			}
			$tr_link = "href='".PROJECT_PATH.$url."'";
			echo "<tr ".$tr_link." style='cursor: pointer;'>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-'.$category]."</a></td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats['domain'][$category]['total']."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$limit."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		echo "</table>\n";
		echo "</div>";
		$n++;

		echo "			<span class='hud_expander' onclick=\"$('#hud_domain_limits_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	}

?>