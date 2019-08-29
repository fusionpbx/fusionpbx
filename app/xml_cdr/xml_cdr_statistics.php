<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "xml_cdr_statistics_inc.php";
	require_once "resources/header.php";

//search url
	$search_url = '';
	if (permission_exists('xml_cdr_search_advanced')) {
		$search_url .= '&redirect=xml_cdr_statistics';
	}
	if(permission_exists('xml_cdr_all') && ($_GET['showall'] === 'true')){
		$search_url .= '&showall=true';
	}
	if (strlen($_GET['direction']) > 0) {
		$search_url .= '&direction='.escape($_GET['direction']);
	}
	if (strlen($_GET['leg']) > 0) {
		$search_url .= '&leg='.escape($_GET['leg']);
	}
	if (strlen($_GET['caller_id_name']) > 0) {
		$search_url .= '&caller_id_name='.escape($_GET['caller_id_name']);
	}
	if (strlen($_GET['caller_extension_uuid']) > 0) {
		$search_url .= '&caller_extension_uuid='.escape($_GET['caller_extension_uuid']);
	}
	if (strlen($_GET['caller_id_number']) > 0) {
		$search_url .= '&caller_id_number='.escape($_GET['caller_id_number']);
	}
	if (strlen($_GET['destination_number']) > 0) {
		$search_url .= '&destination_number='.escape($_GET['destination_number']);
	}
	if (strlen($_GET['context']) > 0) {
		$search_url .= '&context='.escape($_GET['context']);
	}
	if (strlen($_GET['start_stamp_begin']) > 0) {
		$search_url .= '&start_stamp_begin='.escape($_GET['start_stamp_begin']);
	}
	if (strlen($_GET['start_stamp_end']) > 0) {
		$search_url .= '&start_stamp_end='.escape($_GET['start_stamp_end']);
	}
	if (strlen($_GET['answer_stamp_begin']) > 0) {
		$search_url .= '&answer_stamp_begin='.escape($_GET['answer_stamp_begin']);
	}
	if (strlen($_GET['answer_stamp_end']) > 0) {
		$search_url .= '&answer_stamp_end='.escape($_GET['answer_stamp_end']);
	}
	if (strlen($_GET['end_stamp_begin']) > 0) {
		$search_url .= '&end_stamp_begin='.escape($_GET['end_stamp_begin']);
	}
	if (strlen($_GET['end_stamp_end']) > 0) {
		$search_url .= '&end_stamp_end='.escape($_GET['end_stamp_end']);
	}
	if (strlen($_GET['duration']) > 0) {
		$search_url .= '&duration='.escape($_GET['duration']);
	}
	if (strlen($_GET['billsec']) > 0) {
		$search_url .= '&billsec='.escape($_GET['billsec']);
	}
	if (strlen($_GET['hangup_cause']) > 0) {
		$search_url .= '&hangup_cause='.escape($_GET['hangup_cause']);
	}
	if (strlen($_GET['uuid']) > 0) {
		$search_url .= '&uuid='.escape($_GET['uuid']);
	}
	if (strlen($_GET['bleg_uuid']) > 0) {
		$search_url .= '&bleg_uuid='.escape($_GET['bleg_uuid']);
	}
	if (strlen($_GET['accountcode']) > 0) {
		$search_url .= '&accountcode='.escape($_GET['accountcode']);
	}
	if (strlen($_GET['read_codec']) > 0) {
		$search_url .= '&read_codec='.escape($_GET['read_codec']);
	}
	if (strlen($_GET['write_codec']) > 0) {
		$search_url .= '&write_codec='.escape($_GET['write_codec']);
	}
	if (strlen($_GET['remote_media_ip']) > 0) {
		$search_url .= '&remote_media_ip='.escape($_GET['remote_media_ip']);
	}
	if (strlen($_GET['network_addr']) > 0) {
		$search_url .= '&network_addr='.escape($_GET['network_addr']);
	}
	if (strlen($_GET['mos_comparison']) > 0) {
		$search_url .= '&mos_comparison='.escape($_GET['mos_comparison']);
	}
	if (strlen($_GET['mos_score']) > 0) {
		$search_url .= '&mos_score='.escape($_GET['mos_score']);
	}

//page title and description
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' align='left' valign='top' nowrap='nowrap'><b>".$text['label-call-statistics']."</b></td>\n";
	echo "	<td width='70%' align='right' valign='top'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='xml_cdr.php'\" value='".$text['button-back']."'>\n";
	if (permission_exists('xml_cdr_search_advanced')) {
		echo "			<input type='button' class='btn' value='".$text['button-advanced_search']."' onclick=\"window.location='xml_cdr_search.php?type=advanced".$search_url."';\">\n";
	}
	if (permission_exists('xml_cdr_all')) {
		if ($_GET['showall'] != 'true') {
			echo "<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='xml_cdr_statistics.php?showall=true".$search_url."';\">\n";
		}
	}
	echo "		<input type='button' class='btn' value='".$text['button-extension_summary']."' onclick=\"document.location.href='xml_cdr_extension_summary.php';\">\n";
	echo "		<input type='button' class='btn' value='".$text['button-download_csv']."' onclick=\"document.location.href='xml_cdr_statistics_csv.php?type=csv".$search_url."';\">\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['label-call-statistics-description']." \n";
	echo "			<br />\n";
	echo "			<br />\n";
	echo "		</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

//set the style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

?>
	<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/flot/excanvas.min.js"></script><![endif]-->
	<script language="javascript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/flot/jquery.flot.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/flot/jquery.flot.time.js"></script>
	<div align='center'>
	<table>
		<tr>
			<td align='left'>
				<div id="placeholder-legend" style="padding:2px;margin-bottom: 8px;border-radius: 3px 3px 3px 3px;border: 1px solid #E6E6E6;display: inline-block;margin: 0 auto;"></div>
			</td>
		</tr>
		<tr>
			<td align='left'>
				<div id="placeholder" style="width:700px;height:180px;"></div>
			</td>
			<td align='left' valign='top'>
				<p id="choices"></p>
			</td>
		</tr>
	</table>
	</div>
	<script type="text/javascript">
	$(function () {
		var datasets = {
			"volume": {
				label: "Volume",
				data: <?php echo json_encode($graph['volume']); ?>
			},
			"minutes": {
				label: "Minutes",
				data: <?php echo json_encode($graph['minutes']); ?>
			},
			"call_per_min": {
				label: "Calls Per Min",
				data: <?php echo json_encode($graph['call_per_min']); ?>
			},
			"missed": {
				label: "Missed",
				data: <?php echo json_encode($graph['missed']); ?>
			},
			"asr": {
				label: "ASR",
				data: <?php echo json_encode($graph['asr']); ?>
			},
			"aloc": {
				label: "ALOC",
				data: <?php echo json_encode($graph['aloc']); ?>
			},
		};

		// hard-code color indices to prevent them from shifting as
		// countries are turned on/off
		var i = 0;
		$.each(datasets, function(key, val) {
			val.color = i;
			++i;
		});

		// insert checkboxes
		var choiceContainer = $("#choices");
		$.each(datasets, function(key, val) {
			choiceContainer.append('<br /><input type="checkbox" name="' + key +
								   '" checked="checked" id="id' + key + '">' +
								   '<label for="id' + key + '">'
									+ val.label + '</label>');
		});
		choiceContainer.find("input").on('click', plotAccordingToChoices);

		function plotAccordingToChoices() {
			var data = [];
			choiceContainer.find("input:checked").each(function () {
				var key = $(this).attr("name");
				if (key && datasets[key])
					data.push(datasets[key]);
			});

			if (data.length > 0)
				$.plot($("#placeholder"), data, {
					legend:{
						show: true,
						noColumns: 10,
						container: $("#placeholder-legend"),
						placement: 'outsideGrid',
					},
					yaxis: { min: 0 },
					<?php
						if ($hours <= 48) {
							echo "xaxis: {mode: \"time\",timeformat: \"%d:%H\",minTickSize: [1, \"hour\"]}";
						} else if ($hours > 48 && $hours < 168) {
							echo "xaxis: {mode: \"time\",timeformat: \"%m:%d\",minTickSize: [1, \"day\"]}";
						} else {
							echo "xaxis: {mode: \"time\",timeformat: \"%m:%d\",minTickSize: [1, \"month\"]}";
						}
					?>
				});
		}

		plotAccordingToChoices();
	});
	</script>
	<?php

//show the results
	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th>".$text['table-hours']."</th>\n";
	echo "	<th>".$text['table-date']."</th>\n";
	echo "	<th nowrap='nowrap'>".$text['table-time']."</th>\n";
	echo "	<th>Volume</th>\n";
	echo "	<th>".$text['table-minutes']."</th>\n";
	echo "	<th>".$text['table-calls-per-minute']."</th>\n";
	echo "	<th>".$text['table-missed']."</th>\n";
	echo "	<th>ASR</th>\n";
	echo "	<th>ALOC</th>\n";
	echo "</tr>\n";

	$i = 0;
	foreach ($stats as $row) {
		echo "<tr >\n";
		if ($i <= $hours) {
			echo "	<td valign='top' class='".$row_style[$c]."'>".($i+1)."</td>\n";
		}
		elseif ($i == $hours+1) {
			echo "	<br /><br />\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<td>\n";
			echo "		<br /><br />\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<th nowrap='nowrap'>".$text['table-days']."</th>\n";
			echo "	<th nowrap='nowrap'>".$text['table-date']."</th>\n";
			echo "	<th nowrap='nowrap'>".$text['table-time']."</th>\n";
			echo "	<th>Volume</th>\n";
			echo "	<th>".$text['table-minutes']."</th>\n";
			echo "	<th nowrap='nowrap'>".$text['table-calls-per-minute']."</th>\n";
			echo "	<th>".$text['table-missed']."</th>\n";
			echo "	<th>ASR</th>\n";
			echo "	<th>ALOC</th>\n";
			echo "</tr>\n";
			echo "<tr>\n";
		}
		if ($i > $hours) {
			echo "	<td valign='top' class='".$row_style[$c]."'>" . floor(escape($row['hours'])/24) . "</td>\n";
		}
		if ($i <= $hours) {
			echo "	<td valign='top' class='".$row_style[$c]."'>".date('j M', $row['start_epoch'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".date('H:i', $row['start_epoch'])." - ".date('H:i', $row['stop_epoch'])."&nbsp;</td>\n";
		}
		else {
			echo "	<td valign='top' class='".$row_style[$c]."'>".date('j M', $row['start_epoch'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".date('H:i', $row['start_epoch'])." - ".date('j M H:i', $row['stop_epoch'])."&nbsp;</td>\n";
		}
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['volume'])."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".(round(escape($row['minutes']),2))."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".(round(escape($row['avg_min']),2))."&nbsp;/&nbsp;".(round(escape($row['cpm_ans']),2))."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'><a href=\"xml_cdr.php?missed=true&direction=$direction&start_epoch=".escape($row['start_epoch'])."&stop_epoch=".escape($row['stop_epoch'])."\">".escape($row['missed'])."</a>&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".(round(escape($row['asr']),2))."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".(round(escape($row['aloc']),2))."&nbsp;</td>\n";
		echo "</tr >\n";
		if ($c==0) { $c=1; } else { $c=0; }
		$i++;
	}
	echo "</table>\n";
	echo "<br><br>";

//show the footer
	require_once "resources/footer.php";

?>
