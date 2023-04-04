<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
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
	$recent_limit = (is_array($selected_blocks) && in_array('counts', $selected_blocks)) ? 10 : 5;

//get the recent calls from call detail records
	$sql = "
		select
			direction,
			start_stamp,
			start_epoch,
			caller_id_name,
			caller_id_number,
			destination_number,
			answer_stamp,
			bridge_uuid,
			sip_hangup_disposition
		from
			v_xml_cdr
		where
			domain_uuid = :domain_uuid ";
			if (is_array($assigned_extensions) && sizeof($assigned_extensions) != 0) {
				$x = 0;
				foreach ($assigned_extensions as $assigned_extension_uuid => $assigned_extension) {
					$sql_where_array[] = "extension_uuid = :extension_uuid_".$x;
					$sql_where_array[] = "caller_id_number = :caller_id_number_".$x;
					$sql_where_array[] = "destination_number = :destination_number_1_".$x;
					$sql_where_array[] = "destination_number = :destination_number_2_".$x;
					$parameters['extension_uuid_'.$x] = $assigned_extension_uuid;
					$parameters['caller_id_number_'.$x] = $assigned_extension;
					$parameters['destination_number_1_'.$x] = $assigned_extension;
					$parameters['destination_number_2_'.$x] = '*99'.$assigned_extension;
					$x++;
				}
				if (is_array($sql_where_array) && sizeof($sql_where_array) != 0) {
					$sql .= "and (".implode(' or ', $sql_where_array).") ";
				}
				unset($sql_where_array);
			}
			$sql .= "
			and start_epoch > ".(time() - 86400)."
		order by
			start_epoch desc";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	if (!isset($database)) { $database = new database; }
	$result = $database->select($sql, $parameters, 'all');
	$num_rows = is_array($result) ? sizeof($result) : 0;

//define row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//recent calls
	echo "<div class='hud_box'>\n";

//add doughnut chart
	?>
	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick="$('#hud_recent_calls_details').slideToggle('fast');">
		<canvas id='recent_calls_chart' width='175px' height='175px'></canvas>
	</div>

	<script>
		const recent_calls_chart = new Chart(
			document.getElementById('recent_calls_chart').getContext('2d'),
			{
				type: 'doughnut',
				data: {
					datasets: [{
						data: ['<?php echo $num_rows; ?>', 0.00001],
						backgroundColor: [
							'<?php echo $_SESSION['dashboard']['recent_calls_chart_main_background_color']['text']; ?>',
							'<?php echo $_SESSION['dashboard']['recent_calls_chart_sub_background_color']['text']; ?>'
						],
						borderColor: '<?php echo $_SESSION['dashboard']['recent_calls_chart_border_color']['text']; ?>',
						borderWidth: '<?php echo $_SESSION['dashboard']['recent_calls_chart_border_width']['text']; ?>',
						cutout: chart_cutout
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						chart_counter: {
							chart_text: '<?php echo $num_rows; ?>'
						},
						legend: {
							display: false
						},
						title: {
							display: true,
							text: '<?php echo $text['label-recent_calls']; ?>'
						}
					}
				},
				plugins: [chart_counter],
			}
		);
	</script>
	<?php

	echo "<div class='hud_details hud_box' id='hud_recent_calls_details'>";
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	if ($num_rows > 0) {
		echo "<th class='hud_heading'>&nbsp;</th>\n";
	}
	echo "<th class='hud_heading' width='100%'>".$text['label-cid_number']."</th>\n";
	echo "<th class='hud_heading'>".$text['label-date_time']."</th>\n";
	echo "</tr>\n";

	if ($num_rows > 0) {
		$theme_cdr_images_exist = (
			file_exists($theme_image_path."icon_cdr_inbound_answered.png") &&
			file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
			file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
			file_exists($theme_image_path."icon_cdr_inbound_failed.png") &&
			file_exists($theme_image_path."icon_cdr_outbound_answered.png") &&
			file_exists($theme_image_path."icon_cdr_outbound_cancelled.png") &&
			file_exists($theme_image_path."icon_cdr_outbound_failed.png") &&
			file_exists($theme_image_path."icon_cdr_local_answered.png") &&
			file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
			file_exists($theme_image_path."icon_cdr_local_cancelled.png") &&
			file_exists($theme_image_path."icon_cdr_local_failed.png")
			) ? true : false;

		foreach ($result as $index => $row) {
			if ($index + 1 > $recent_limit) { break; } //only show limit
			$tmp_year = date("Y", strtotime($row['start_stamp']));
			$tmp_month = date("M", strtotime($row['start_stamp']));
			$tmp_day = date("d", strtotime($row['start_stamp']));
			$tmp_start_epoch = ($_SESSION['domain']['time_format']['text'] == '12h') ? date("n/j g:ia", $row['start_epoch']) : date("n/j H:i", $row['start_epoch']);

			//determine name
				$cdr_name = ($row['direction'] == 'inbound' || ($row['direction'] == 'local' && is_array($assigned_extensions) && in_array($row['destination_number'], $assigned_extensions))) ? $row['caller_id_name'] : $row['destination_number'];
			//determine number to display
				if ($row['direction'] == 'inbound' || ($row['direction'] == 'local' && is_array($assigned_extensions) && in_array($row['destination_number'], $assigned_extensions))) {
					$cdr_number = (is_numeric($row['caller_id_number'])) ? format_phone($row['caller_id_number']) : $row['caller_id_number'];
					$dest = $row['caller_id_number'];
				}
				else if ($row['direction'] == 'outbound' || ($row['direction'] == 'local' && is_array($assigned_extensions) && in_array($row['caller_id_number'], $assigned_extensions))) {
					$cdr_number = (is_numeric($row['destination_number'])) ? format_phone($row['destination_number']) : $row['destination_number'];
					$dest = $row['destination_number'];
				}
			//set click-to-call variables
				if (permission_exists('click_to_call_call')) {
					$tr_link = "onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php".
						"?src_cid_name=".urlencode($cdr_name).
						"&src_cid_number=".urlencode($cdr_number).
						"&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name']).
						"&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number']).
						"&src=".urlencode($_SESSION['user']['extension'][0]['user']).
						"&dest=".urlencode($dest).
						"&rec=".(isset($_SESSION['click_to_call']['record']['boolean'])?$_SESSION['click_to_call']['record']['boolean']:"false").
						"&ringback=".(isset($_SESSION['click_to_call']['ringback']['text'])?$_SESSION['click_to_call']['ringback']['text']:"us-ring").
						"&auto_answer=".(isset($_SESSION['click_to_call']['auto_answer']['boolean'])?$_SESSION['click_to_call']['auto_answer']['boolean']:"true").
						"');\" ".
						"style='cursor: pointer;'";
				}
			echo "<tr ".$tr_link.">\n";
			//determine call result and appropriate icon
				echo "<td valign='middle' class='".$row_style[$c]."' style='cursor: help; padding: 0 0 0 6px;'>\n";
				if ($theme_cdr_images_exist) {
					if ($row['direction'] == 'inbound' || $row['direction'] == 'local') {
						if ($row['answer_stamp'] != '' && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
						else if ($row['answer_stamp'] != '' && $row['bridge_uuid'] == '') { $call_result = 'voicemail'; }
						else if ($row['answer_stamp'] == '' && $row['bridge_uuid'] == '' && $row['sip_hangup_disposition'] != 'send_refuse') { $call_result = 'cancelled'; }
						else { $call_result = 'failed'; }
					}
					else if ($row['direction'] == 'outbound') {
						if ($row['answer_stamp'] != '' && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
						else if ($row['answer_stamp'] == '' && $row['bridge_uuid'] != '') { $call_result = 'cancelled'; }
						else { $call_result = 'failed'; }
					}
					if (isset($row['direction'])) {
						echo "<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_".$row['direction']."_".$call_result.".png' width='16' style='border: none;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$call_result]."'>\n";
					}
				}
				echo "</td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'><a href='javascript:void(0);' ".(($cdr_name != '') ? "title=\"".$cdr_name."\"" : null).">".$cdr_number."</a></td>\n";
				echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$tmp_start_epoch."</td>\n";
			echo "</tr>\n";

			unset($cdr_name, $cdr_number);
			$c = ($c) ? 0 : 1;
		}
	}
	unset($sql, $parameters, $result, $num_rows, $index, $row);

	echo "</table>\n";
	echo "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php'>".$text['label-view_all']."</a></span>\n";
	echo "</div>";
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_recent_calls_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>