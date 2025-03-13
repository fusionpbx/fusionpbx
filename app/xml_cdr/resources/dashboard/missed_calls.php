<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permisions
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

//create assigned extensions array
	if (is_array($_SESSION['user']['extension'])) {
		foreach ($_SESSION['user']['extension'] as $assigned_extension) {
			$assigned_extensions[$assigned_extension['extension_uuid']] = $assigned_extension['user'];
		}
	}
	unset($assigned_extension);

//if also viewing system status, show more recent calls (more room avaialble)
	$missed_limit = !empty($selected_blocks) && (is_array($selected_blocks) && in_array('counts', $selected_blocks)) ? 10 : 5;

//set the sql time format
	$sql_time_format = 'DD Mon HH12:MI am';
	if (!empty($_SESSION['domain']['time_format']['text'])) {
		$sql_time_format = $_SESSION['domain']['time_format']['text'] == '12h' ? "DD Mon HH12:MI am" : "DD Mon HH24:MI";
	}

//get the missed calls from call detail records
	$sql =	"select \n";
	$sql .= "	status, \n";
	$sql .=	"	direction, \n";
	$sql .= "	to_char(timezone(:time_zone, start_stamp), '".$sql_time_format."') as start_date_time, \n";
	$sql .=	"	caller_id_name, \n";
	$sql .=	"	caller_id_number, \n";
	$sql .=	"	answer_stamp \n";
	$sql .=	"from \n";
	$sql .=	"	v_xml_cdr \n";
	$sql .=	"where \n";
	$sql .=	"	domain_uuid = :domain_uuid \n";
	$sql .=	"	and ( \n";
	$sql .=	"		direction = 'inbound' \n";
	$sql .=	"		or direction = 'local' \n";
	$sql .=	"	) \n";
	$sql .=	"	and (status = 'missed') ";
	$sql .=	"	and hangup_cause <> 'LOSE_RACE' ";
	if (!permission_exists('xml_cdr_domain')) {
		if (!empty($assigned_extensions)) {
			$x = 0;
			foreach ($assigned_extensions as $assigned_extension_uuid => $assigned_extension) {
				$sql_where_array[] = "extension_uuid = :assigned_extension_uuid_".$x;
				$sql_where_array[] = "destination_number = :destination_number_".$x;
				$parameters['assigned_extension_uuid_'.$x] = $assigned_extension_uuid;
				$parameters['destination_number_'.$x] = $assigned_extension;
				$x++;
			}
			if (!empty($sql_where_array)) {
				$sql .= "and (".implode(' or ', $sql_where_array).") \n";
			}
			unset($sql_where_array);
		}
		else {
			$sql .= "and false \n";
		}
	}
	$sql .= "and start_epoch > ".(time() - 86400)." \n";
	$sql .=	"order by \n";
	$sql .=	"start_epoch desc \n";
	$sql .=	"limit :missed_limit \n";
	$parameters['missed_limit'] = $missed_limit;
	$parameters['time_zone'] = isset($_SESSION['domain']['time_zone']['name']) ? $_SESSION['domain']['time_zone']['name'] : date_default_timezone_get();
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	if (!isset($database)) { $database = new database; }
	$result = $database->select($sql, $parameters, 'all');
	$num_rows = !empty($result) ? sizeof($result) : 0;

//define row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//missed calls
	echo "<div class='hud_box'>\n";

	echo "<div class='hud_content' ".($dashboard_details_state == "disabled" ?: "onclick=\"$('#hud_missed_calls_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"").">\n";
	echo "	<span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?call_result=missed'\">".$text['label-missed_calls']."</a></span>";

	if ($dashboard_chart_type == "doughnut") {
		//add doughnut chart
		?>
		<div class='hud_chart'><canvas id='missed_calls_chart'></canvas></div>

		<script>
			const missed_calls_chart = new Chart(
				document.getElementById('missed_calls_chart').getContext('2d'),
				{
					type: 'doughnut',
					data: {
						datasets: [{
							data: ['<?php echo $num_rows; ?>', 0.00001],
							backgroundColor: [
								'<?php echo ($settings->get('theme', 'dashboard_missed_calls_chart_main_color') ?? '#ea4c46'); ?>',
								'<?php echo ($settings->get('theme', 'dashboard_missed_calls_chart_sub_color') ?? '#d4d4d4'); ?>'
							],
							borderColor: '<?php echo $settings->get('theme', 'dashboard_chart_border_color'); ?>',
							borderWidth: '<?php echo $settings->get('theme', 'dashboard_chart_border_width'); ?>',
						}]
					},
					options: {
						plugins: {
							chart_number: {
								text: '<?php echo $num_rows; ?>'
							}
						}
					},
					plugins: [{
						id: 'chart_number',
						beforeDraw(chart, args, options){
							const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
							ctx.font = chart_text_size + ' ' + chart_text_font;
							ctx.textBaseline = 'middle';
							ctx.textAlign = 'center';
							ctx.fillStyle = '<?php echo $dashboard_number_text_color; ?>';
							ctx.fillText(options.text, width / 2, top + (height / 2));
							ctx.save();
						}
					}]
				}
			);
		</script>
		<?php
	}

	//dashboard number
	if (!isset($dashboard_chart_type) || $dashboard_chart_type == "number") {
		echo "<span class='hud_stat'>".$num_rows."</span>";
	}

	//dashboard icon
	if (!isset($dashboard_chart_type) || $dashboard_chart_type == "icon") {
		echo "<span class='hud_content'>\n";
		echo "<div style='position: relative; display: inline-block;'>\n";
		echo "		<span class='hud_stat'><i class=\"fas ".$dashboard_icon." \"></i></span>\n";
		echo "		<span style=\"background-color: ".(!empty($dashboard_number_background_color) ? $dashboard_number_background_color : '#EA4C46')."; color: ".(!empty($dashboard_number_text_color) ? $dashboard_number_text_color : '#ffffff')."; font-size: 12px; font-weight: bold; text-align: center; position: absolute; top: 23px; left: 24.5px; padding: 2px 7px 1px 7px; border-radius: 10px; white-space: nowrap;\">".$num_rows."</span>\n";
		echo "	</div>\n";
		echo "</span>";
	}
	echo "</div>\n";

	if ($dashboard_details_state != 'disabled') {
		echo "<div class='hud_details hud_box' id='hud_missed_calls_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		if ($num_rows > 0) {
			echo "<th class='hud_heading'>&nbsp;</th>\n";
		}
		echo "<th class='hud_heading' width='100%'>".$text['label-cid_number']."</th>\n";
		echo "<th class='hud_heading'>".$text['label-missed']."</th>\n";
		echo "</tr>\n";

		if ($num_rows > 0) {
			$theme_cdr_images_exist = (
				file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
				file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
				file_exists($theme_image_path."icon_cdr_local_cancelled.png")
				) ? true : false;

			foreach ($result as $index => $row) {
				$start_date_time = str_replace('/0','/', ltrim($row['start_date_time'], '0'));
				if (!empty($_SESSION['domain']['time_format']) && $_SESSION['domain']['time_format']['text'] == '12h') {
					$start_date_time = str_replace(' 0',' ', $start_date_time);
				}
				//set click-to-call variables
				if (permission_exists('click_to_call_call')) {
					$tr_link = "onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php".
						"?src_cid_name=".urlencode($row['caller_id_name'] ?? '').
						"&src_cid_number=".urlencode($row['caller_id_number'] ?? '').
						"&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'] ?? '').
						"&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'] ?? '').
						"&src=".urlencode($_SESSION['user']['extension'][0]['user'] ?? '').
						"&dest=".urlencode($row['caller_id_number'] ?? '').
						"&rec=".(filter_var($_SESSION['click_to_call']['record']['boolean'] ?? false, FILTER_VALIDATE_BOOL) ? "true" : "false").
						"&ringback=".(isset($_SESSION['click_to_call']['ringback']['text']) ? $_SESSION['click_to_call']['ringback']['text'] : "us-ring").
						"&auto_answer=".(filter_var($_SESSION['click_to_call']['auto_answer']['boolean'] ?? false, FILTER_VALIDATE_BOOL) ? "true" : "false").
						"');\" ".
						"style='cursor: pointer;'";
				}
				echo "<tr ".$tr_link.">\n";
				echo "<td valign='middle' class='".$row_style[$c]."' style='cursor: help; padding: 0 0 0 6px;'>\n";
				if ($theme_cdr_images_exist) {
					$call_result = $row['status'];
					if (isset($row['direction'])) {
						echo "	<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_".$row['direction']."_".$call_result.".png' width='16' style='border: none;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$call_result]."'>\n";
					}
				}
				echo "</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'><a href='javascript:void(0);' ".(($row['caller_id_name'] != '') ? "title=\"".$row['caller_id_name']."\"" : null).">".((is_numeric($row['caller_id_number'])) ? format_phone($row['caller_id_number']) : $row['caller_id_number'])."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$start_date_time."</td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}
		}
		unset($sql, $parameters, $result, $num_rows, $index, $row);

		echo "</table>\n";
		echo "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?status=missed'>".$text['label-view_all']."</a></span>\n";
		echo "</div>";
		//$n++;

		echo "<span class='hud_expander' onclick=\"$('#hud_missed_calls_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"><span class='fas fa-ellipsis-h'></span></span>";
	}
	echo "</div>\n";

?>
