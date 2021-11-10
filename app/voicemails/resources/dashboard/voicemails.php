<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

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
	//if (is_array($selected_blocks) && in_array('voicemail', $selected_blocks) && permission_exists('voicemail_message_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/")) {
		//required class
			require_once "app/voicemails/resources/classes/voicemail.php";
		//get the voicemail
			$vm = new voicemail;
			$vm->db = $db;
			$vm->domain_uuid = $_SESSION['domain_uuid'];
			$vm->order_by = $order_by;
			$vm->order = $order;
			$voicemails = $vm->messages();
		//sum total and new
			$messages['total'] = 0;
			$messages['new'] = 0;
			if (sizeof($voicemails) > 0) {
				foreach($voicemails as $field) {
					$messages[$field['voicemail_uuid']]['ext'] = $field['voicemail_id'];
					$messages[$field['voicemail_uuid']]['total'] = 0;
					$messages[$field['voicemail_uuid']]['new'] = 0;
					foreach($field['messages'] as &$row) {
						if ($row['message_status'] == '') {
							$messages[$field['voicemail_uuid']]['new']++;
							$messages['new']++;
						}
						$messages[$field['voicemail_uuid']]['total']++;
						$messages['total']++;
					}
				}
			}

		//add doughnut chart
			?>
				<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom:10px;'>
					<div style='width: 175px; height: 175px;'><canvas id='new_messages_chart'></canvas></div>
				</div>

				<script>
					var new_messages_bgc = ['#ff9933', '#d4d4d4'];

					const new_messages_data = {
						datasets: [{
							data:[".$messages['new'].", 0.00001],
							borderColor: 'rgba(0,0,0,0)',
							backgroundColor: [new_messages_bgc[0], new_messages_bgc[1]],
							cutout: chart_cutout
						}]
					};

					const new_messages_config = {
						type: 'doughnut',
						data: new_messages_data,
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: {
								chart_counter: {
									chart_text: ".$messages['new'].",
								},
								legend: {
									display: false
								},
								title: {
									display: true,
									text: '".$text['label-new_messages']."',
									fontFamily: chart_font_family
								}
							}
						},
						plugins: [chart_counter],
					};

					const new_messages_chart = new Chart(
						document.getElementById('new_messages_chart'),
						new_messages_config
					);
				</script>
			<?php

			echo "<div class='hud_details hud_box' id='hud_voicemail_details'>";
			if (sizeof($voicemails) > 0) {
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
						echo "	<td class='".$row_style[$c]." hud_text'><a href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php?id=".(permission_exists('voicemail_view') ? $voicemail_uuid : $row['ext'])."'>".$row['ext']."</a></td>";
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
			$n++;
	//}
	echo "			<span class='hud_expander' onclick=\"$('#hud_voicemail_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";

?>
