<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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

//missed calls
	echo "<div class='hud_box'>\n";
	if (is_array($_SESSION['user']['extension'])) {
		foreach ($_SESSION['user']['extension'] as $assigned_extension) {
			$assigned_extensions[$assigned_extension['extension_uuid']] = $assigned_extension['user'];
		}
	}
	unset($assigned_extension);

//if also viewing system status, show more recent calls (more room avaialble)
	$missed_limit = (is_array($selected_blocks) && in_array('counts', $selected_blocks)) ? 10 : 5;

	$sql =	"select \n";
	$sql .=	"	direction, \n";
	$sql .=	"	start_stamp, \n";
	$sql .=	"	start_epoch, \n";
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
	$sql .=	"	and (missed_call = true or bridge_uuid is null) ";
	$sql .=	"	and hangup_cause <> 'LOSE_RACE' ";
	if (is_array($assigned_extensions) && sizeof($assigned_extensions) != 0) {
		$x = 0;
		foreach ($assigned_extensions as $assigned_extension_uuid => $assigned_extension) {
			$sql_where_array[] = "extension_uuid = :assigned_extension_uuid_".$x;
			$sql_where_array[] = "destination_number = :destination_number_".$x;
			$parameters['assigned_extension_uuid_'.$x] = $assigned_extension_uuid;
			$parameters['destination_number_'.$x] = $assigned_extension;
			$x++;
		}
		if (is_array($sql_where_array) && sizeof($sql_where_array) != 0) {
			$sql .= "and (".implode(' or ', $sql_where_array).") \n";
		}
		unset($sql_where_array);
	}
	$sql .= "and start_epoch > ".(time() - 86400)." \n";
	$sql .=	"order by \n";
	$sql .=	"start_epoch desc \n";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	//echo $sql;
	//view_array($parameters);
	if (!isset($database)) { $database = new database; }
	$result = $database->select($sql, $parameters, 'all');

	$num_rows = is_array($result) ? sizeof($result) : 0;

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";


//add doughnut chart
	?>
	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;'>
		<canvas id='missed_calls_chart' width='175px' height='175px'></canvas>
	</div>

	<script>
		const missed_calls_chart = new Chart(
			document.getElementById('missed_calls_chart').getContext('2d'),
			{
				type: 'doughnut',
				data: {
					datasets: [{
						data: ['<?php echo $num_rows; ?>', 0.00001],
						backgroundColor: [
							'<?php echo $_SESSION['dashboard']['missed_calls_chart_main_background_color']['text']; ?>',
							'<?php echo $_SESSION['dashboard']['missed_calls_chart_sub_background_color']['text']; ?>'
						],
						borderColor: '<?php echo $_SESSION['dashboard']['missed_calls_chart_border_color']['text']; ?>',
						borderWidth: '<?php echo $_SESSION['dashboard']['missed_calls_chart_border_Width']['text']; ?>',
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
							text: '<?php echo $text['label-missed_calls']; ?>'
						}
					}
				},
				plugins: [chart_counter],
			}
		);
	</script>
	<?php

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

		foreach($result as $index => $row) {
			if ($index + 1 > $missed_limit) { break; } //only show limit
			$tmp_year = date("Y", strtotime($row['start_stamp']));
			$tmp_month = date("M", strtotime($row['start_stamp']));
			$tmp_day = date("d", strtotime($row['start_stamp']));
			$tmp_start_epoch = ($_SESSION['domain']['time_format']['text'] == '12h') ? date("n/j g:ia", $row['start_epoch']) : date("n/j H:i", $row['start_epoch']);
			//set click-to-call variables
			if (permission_exists('click_to_call_call')) {
				$tr_link = "onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php".
					"?src_cid_name=".urlencode($row['caller_id_name']).
					"&src_cid_number=".urlencode($row['caller_id_number']).
					"&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name']).
					"&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number']).
					"&src=".urlencode($_SESSION['user']['extension'][0]['user']).
					"&dest=".urlencode($row['caller_id_number']).
					"&rec=".(isset($_SESSION['click_to_call']['record']['boolean'])?$_SESSION['click_to_call']['record']['boolean']:"false").
					"&ringback=".(isset($_SESSION['click_to_call']['ringback']['text'])?$_SESSION['click_to_call']['ringback']['text']:"us-ring").
					"&auto_answer=".(isset($_SESSION['click_to_call']['auto_answer']['boolean'])?$_SESSION['click_to_call']['auto_answer']['boolean']:"true").
					"');\" ".
					"style='cursor: pointer;'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "<td valign='middle' class='".$row_style[$c]."' style='cursor: help; padding: 0 0 0 6px;'>\n";
			if ($theme_cdr_images_exist) {
				$call_result = ($row['answer_stamp'] != '') ? 'voicemail' : 'cancelled';
				if (isset($row['direction'])) {
					echo "	<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_".$row['direction']."_".$call_result.".png' width='16' style='border: none;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$call_result]."'>\n";
				}
			}
			echo "</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'><a href='javascript:void(0);' ".(($row['caller_id_name'] != '') ? "title=\"".$row['caller_id_name']."\"" : null).">".((is_numeric($row['caller_id_number'])) ? format_phone($row['caller_id_number']) : $row['caller_id_number'])."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$tmp_start_epoch."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
	}
	unset($sql, $parameters, $result, $num_rows, $index, $row);

	echo "</table>\n";
	echo "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?call_result=missed'>".$text['label-view_all']."</a></span>\n";
	echo "</div>";
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#hud_missed_calls_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
