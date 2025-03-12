<?php

//includes files
	require_once  dirname(__DIR__, 4) . "/resources/require.php";

//check permisions
	require_once "resources/check_auth.php";
	if (permission_exists('voicemail_view') || permission_exists('voicemail_message_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

//used for missed and recent calls
	$theme_image_path = $_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/";

//voicemail
	echo "<div class='hud_box'>\n";

//get the voicemail
	$vm = new voicemail;
	$vm->domain_uuid = $_SESSION['domain_uuid'];
	$vm->order_by = $order_by ?? null;
	$vm->order = $order ?? null;
	$voicemails = $vm->messages();

//sum total and new
	$messages['total'] = 0;
	$messages['new'] = 0;
	if (!empty($voicemails) && sizeof($voicemails) > 0) {
		foreach($voicemails as $field) {
			$messages[$field['voicemail_uuid']]['ext'] = $field['voicemail_id'];
			$messages[$field['voicemail_uuid']]['total'] = 0;
			$messages[$field['voicemail_uuid']]['new'] = 0;
			foreach ($field['messages'] as $row) {
				if ($row['message_status'] == '') {
					$messages[$field['voicemail_uuid']]['new']++;
					$messages['new']++;
				}
				$messages[$field['voicemail_uuid']]['total']++;
				$messages['total']++;
			}
		}
	}

	echo "<div class='hud_content' ".($dashboard_details_state == "disabled" ?: "onclick=\"$('#hud_voicemail_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"").">\n";
	echo "	<span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php'\">".$text['label-new_messages']."</a></span>";

	if (isset($dashboard_chart_type) && $dashboard_chart_type == "doughnut") {
		//add doughnut chart
		?>
		<div class='hud_chart'><canvas id='new_messages_chart'></canvas></div>

		<script>
			const new_messages_chart = new Chart(
				document.getElementById('new_messages_chart').getContext('2d'),
				{
					type: 'doughnut',
					data: {
						datasets: [{
							data: ['<?php echo $messages['new']; ?>', 0.00001],
							backgroundColor: [
								'<?php echo ($settings->get('theme', 'dashboard_missed_calls_chart_main_color') ?? '#ff9933'); ?>',
								'<?php echo ($settings->get('theme', 'dashboard_missed_calls_chart_sub_color') ?? '#d4d4d4'); ?>'
							],
							borderColor: '<?php echo $settings->get('theme', 'dashboard_chart_border_color'); ?>',
							borderWidth: '<?php echo $settings->get('theme', 'dashboard_chart_border_width'); ?>',
						}]
					},
					options: {
						plugins: {
							chart_number: {
								text: '<?php echo $messages['new']; ?>'
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
		echo "	<span class='hud_stat'>".$messages['new']."</span>";
	}

	//dashboard icon
	if (!isset($dashboard_chart_type) || $dashboard_chart_type == "icon") {
		echo "<span class='hud_content'>\n";
		echo "<div style='position: relative; display: inline-block;'>\n";
		echo "		<span class='hud_stat'><i class=\"fas ".$dashboard_icon." \"></i></span>\n";
		echo "		<span style=\"background-color: ".(!empty($dashboard_number_background_color) ? $dashboard_number_background_color : '#03c04a')."; color: ".(!empty($dashboard_number_text_color) ? $dashboard_number_text_color : '#ffffff')."; font-size: 12px; font-weight: bold; text-align: center; position: absolute; top: 23px; left: 24.5px; padding: 2px 7px 1px 7px; border-radius: 10px; white-space: nowrap;\">".$messages['new']."</span>\n";
		echo "	</div>\n";
		echo "</span>";
	}

	echo "</div>\n";

	if ($dashboard_details_state != 'disabled') {
		echo "<div class='hud_details hud_box' id='hud_voicemail_details'>";
		if (!empty($voicemails) && sizeof($voicemails) > 0) {
			echo "<table class='tr_hover' cellpadding='2' cellspacing='0' border='0' width='100%'>";
			echo "<tr>";
			echo "	<th class='hud_heading' width='50%'>".$text['label-voicemail']."</th>";
			echo "	<th class='hud_heading' style='text-align: center;' width='50%'>".$text['label-new']."</th>";
			echo "	<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>";
			echo "</tr>";

			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			foreach ($messages as $voicemail_uuid => $row) {
				if (is_uuid($voicemail_uuid)) {
					$tr_link = "href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php?id=".(permission_exists('voicemail_view') ? $voicemail_uuid : $row['ext'])."'";
					echo "<tr ".$tr_link." style='cursor: pointer;'>";
					echo "	<td class='".$row_style[$c]." hud_text'><a href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php?id=".(permission_exists('voicemail_view') ? $voicemail_uuid : $row['ext'])."&back=".urlencode($_SERVER["REQUEST_URI"])."'>".$row['ext']."</a></td>";
					echo "	<td class='".$row_style[$c]." hud_text' style='text-align: center;'>".$row['new']."</td>";
					echo "	<td class='".$row_style[$c]." hud_text' style='text-align: center;'>".$row['total']."</td>";
					echo "</tr>";
					$c = ($c) ? 0 : 1;
				}
			}

			echo "</table>";
		}
		else {
			echo "<br />".$text['label-no_voicemail_assigned'];
		}
		echo "</div>";
		//$n++;

		echo "<span class='hud_expander' onclick=\"$('#hud_voicemail_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"><span class='fas fa-ellipsis-h'></span></span>";
	}

	echo "</div>\n";

?>
