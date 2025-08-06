<?php

//includes files
	require_once dirname(__DIR__, 4) . "/resources/require.php";

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

//system status
	echo "<div class='hud_box'>\n";

	$c = 0;
	$row_style['0'] = "row_style0";
	$row_style['1'] = "row_style1";

	//disk usage
	if (PHP_OS == 'FreeBSD' || PHP_OS == 'Linux') {
		$tmp = shell_exec("df / 2>&1");
		$tmp = explode("\n", $tmp);
		$tmp = preg_replace('!\s+!', ' ', $tmp[1]); // multiple > single space
		$tmp = explode(' ', $tmp);
		foreach ($tmp as $stat) {
			if (substr_count($stat, '%') > 0) { $percent_disk_usage = rtrim($stat,'%'); break; }
		}

		if (!empty($percent_disk_usage)) {

			//add half doughnut chart
			echo "	<div class='hud_content' ".($dashboard_details_state == "disabled" ?: "onclick=\"$('#hud_system_disk_usage_details').slideToggle('fast');\"").">\n";
			echo "		<span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/system/system.php'\">".$text['label-disk_usage']."</a></span>\n";

			if (!isset($dashboard_chart_type) || $dashboard_chart_type == "doughnut") {
				?>
				<div class='hud_chart' style='width: 175px;'><canvas id='system_disk_usage_chart'></canvas></div>

				<script>
					const system_disk_usage_chart = new Chart(
						document.getElementById('system_disk_usage_chart').getContext('2d'),
						{
							type: 'doughnut',
							data: {
								datasets: [{
									data: ['<?php echo $percent_disk_usage; ?>', 100 - '<?php echo $percent_disk_usage; ?>'],
									backgroundColor: [
										<?php
										if ($percent_disk_usage <= 80) {
											echo "'".($settings->get('theme', 'dashboard_disk_usage_chart_main_color')[0] ?? '#03c04a')."',\n";
										} else if ($percent_disk_usage <= 90) {
											echo "'".($settings->get('theme', 'dashboard_disk_usage_chart_main_color')[1] ?? '#ff9933')."',\n";
										} else if ($percent_disk_usage > 90) {
											echo "'".($settings->get('theme', 'dashboard_disk_usage_chart_main_color')[2] ?? '#ea4c46')."',\n";
										}
										?>
										'<?php echo ($settings->get('theme', 'dashboard_disk_usage_chart_sub_color') ?? '#d4d4d4'); ?>'
									],
									borderColor: '<?php echo $settings->get('theme', 'dashboard_chart_border_color'); ?>',
									borderWidth: '<?php echo $settings->get('theme', 'dashboard_chart_border_width'); ?>',
								}]
							},
							options: {
								circumference: 180,
								rotation: 270,
								plugins: {
									chart_number_2: {
										text: '<?php echo round($percent_disk_usage); ?>'
									}
								}
							},
							plugins: [{
								id: 'chart_number_2',
								beforeDraw(chart, args, options){
									const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
									ctx.font = chart_text_size + ' ' + chart_text_font;
									ctx.textBaseline = 'middle';
									ctx.textAlign = 'center';
									ctx.fillStyle = '<?php echo $dashboard_number_text_color; ?>';
									ctx.fillText(options.text + '%', width / 2, top + (height / 2) + 35);
									ctx.save();
								}
							}]
						}
					);
				</script>
				<?php
			}
			if ($dashboard_chart_type == "number") {
				echo "	<span class='hud_stat'>".round($percent_disk_usage)."%</span>";
			}
			echo "	</div>\n";
		}
	}

	if ($dashboard_details_state != 'disabled') {
		echo "<div class='hud_details hud_box' id='hud_system_disk_usage_details'>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "  <th class='hud_heading' width='50%'>".($text['label-mount_point'] ?? 'Mount Point')."</th>\n";
		echo "  <th class='hud_heading' style='text-align: center;'>".($text['label-size'] ?? 'Size')."</th>\n";
		echo "  <th class='hud_heading' style='text-align: center;'>".($text['label-used'] ?? 'Used')."</th>\n";
		echo "  <th class='hud_heading' style='text-align: right;'>".($text['label-available'] ?? 'Available')."</th>\n";
		echo "</tr>\n";

		//disk usage
			if (permission_exists('system_view_hdd')) {
				$system_information = [];
				if (stristr(PHP_OS, 'Linux') || stristr(PHP_OS, 'FreeBSD')) {
					$shell_result = shell_exec('df -hP');
					if (!empty($shell_result)) {
						$lines = explode("\n",$shell_result);
						//name the columns
						$column_names = preg_split("/[\s,]+/", $lines[0]);
						$col_file_system = array_search('Filesystem', $column_names, true); //usually 0
						$col_size = array_search('Size', $column_names, true);              //usually 1
						$col_used = array_search('Used', $column_names, true);              //usually 2
						$col_available = array_search('Avail', $column_names, true);        //usually 3
						$col_mount_point = array_search('Mounted', $column_names, true);    //usually 5 but can be 4
						//skip heading line by starting at 1
						for ($i = 1; $i < count($lines); $i++) {
							$line = $lines[$i];
							$columns = preg_split("/[\s,]+/", $line);
							$system_information['os']['disk'][$i-1]['file_system'] = $columns[$col_file_system];
							$system_information['os']['disk'][$i-1][   'size'    ] = $columns[   $col_size    ];
							$system_information['os']['disk'][$i-1][   'used'    ] = $columns[   $col_used    ];
							$system_information['os']['disk'][$i-1][ 'available' ] = $columns[ $col_available ];
							$system_information['os']['disk'][$i-1]['mount_point'] = $columns[$col_mount_point];
						}
					}
					foreach ($system_information['os']['disk'] as $disk) {
						echo "<tr class='tr_link_void'>\n";
						echo "  <td valign='top' class='".$row_style[$c]." hud_text' style='text-align: left;'>".$disk['mount_point']."</td>\n";
						echo "  <td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$disk[   'size'    ]."</td>\n";
						echo "  <td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$disk[   'used'    ]."</td>\n";
						echo "  <td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$disk[ 'available' ]."</td>\n";
						echo "</tr>\n";
						$c = ($c) ? 0 : 1;
					}
				}
				else if (stristr(PHP_OS, 'WIN')) {

				}
			}

		echo "</table>\n";
		echo "</div>";
		//$n++;

		echo "<span class='hud_expander' onclick=\"$('#hud_system_disk_usage_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	}
	echo "</div>\n";

?>
