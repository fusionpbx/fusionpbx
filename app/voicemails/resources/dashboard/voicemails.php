<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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
	echo "<div class='hud_box'>\n";

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
	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;' onclick="$('#hud_voicemail_details').slideToggle('fast');">
		<canvas id='new_messages_chart' width='175px' height='175px'></canvas>
	</div>

	<script>
		const new_messages_chart = new Chart(
			document.getElementById('new_messages_chart').getContext('2d'),
			{
				type: 'doughnut',
				data: {
					datasets: [{
						data: ['<?php echo $messages['new']; ?>', 0.00001],
						backgroundColor: [
							'<?php echo $_SESSION['dashboard']['new_messages_chart_main_background_color']['text']; ?>', 
							'<?php echo $_SESSION['dashboard']['new_messages_chart_sub_background_color']['text']; ?>'
						],
						borderColor: '<?php echo $_SESSION['dashboard']['new_messages_chart_border_color']['text']; ?>',
						borderWidth: '<?php echo $_SESSION['dashboard']['new_messages_chart_border_width']['text']; ?>',
						cutout: chart_cutout
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						chart_counter: {
							chart_text: '<?php echo $messages['new']; ?>',
						},
						legend: {
							display: false
						},
						title: {
							display: true,
							text: '<?php echo $text['label-new_messages']; ?>',
							fontFamily: chart_text_font
						}
					}
				},
				plugins: [chart_counter],
			}
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
	
	echo "<span class='hud_expander' onclick=\"$('#hud_voicemail_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
