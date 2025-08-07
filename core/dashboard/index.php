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
	Portions created by the Initial Developer are Copyright (C) 2022-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

//if config.conf file does not exist then redirect to the install page
	if (file_exists("/usr/local/etc/fusionpbx/config.conf")){
		//BSD
	}
	elseif (file_exists("/etc/fusionpbx/config.conf")){
		//Linux
	}
	elseif (file_exists(getenv('SystemDrive') . DIRECTORY_SEPARATOR . 'ProgramData' . DIRECTORY_SEPARATOR . 'fusionpbx' . DIRECTORY_SEPARATOR . 'config.conf')) {
		// Windows
	}
	else {
		header("Location: /core/install/install.php");
		exit;
	}

//additional includes
	require_once "resources/check_auth.php";

//initialize the database
	$database = new database;

//disable login message
	if (isset($_GET['msg']) && $_GET['msg'] == 'dismiss') {
		unset($_SESSION['login']['message']['text']);

		$sql = "update v_default_settings ";
		$sql .= "set default_setting_enabled = 'false' ";
		$sql .= "where ";
		$sql .= "default_setting_category = 'login' ";
		$sql .= "and default_setting_subcategory = 'message' ";
		$sql .= "and default_setting_name = 'text' ";
		$database->execute($sql);
		unset($sql);
	}

//build a list of groups the user is a member of to be used in a SQL in
	if (is_array($_SESSION['user']['groups'])) {
		foreach ($_SESSION['user']['groups'] as $group) {
			$group_uuids[] =  $group['group_uuid'];
		}
	}
	if (is_array($group_uuids)) {
		$group_uuids_in = "'".implode("','", $group_uuids)."'";
	}

//get the list
	$sql = "select ";
	$sql .= "dashboard_uuid, ";
	$sql .= "dashboard_name, ";
	$sql .= "dashboard_path, ";
	$sql .= "dashboard_icon, ";
	$sql .= "dashboard_icon_color, ";
	$sql .= "dashboard_url, ";
	$sql .= "dashboard_target, ";
	$sql .= "dashboard_width, ";
	$sql .= "dashboard_height, ";
	$sql .= "dashboard_content, ";
	$sql .= "dashboard_content_text_align, ";
	$sql .= "dashboard_content_details, ";
	$sql .= "dashboard_chart_type, ";
	$sql .= "cast(dashboard_label_enabled as text), ";
	$sql .= "dashboard_label_text_color, ";
	$sql .= "dashboard_label_text_color_hover, ";
	$sql .= "dashboard_label_background_color, ";
	$sql .= "dashboard_label_background_color_hover, ";
	$sql .= "dashboard_number_text_color, ";
	$sql .= "dashboard_number_text_color_hover, ";
	$sql .= "dashboard_number_background_color, ";
	$sql .= "dashboard_background_color, ";
	$sql .= "dashboard_background_color_hover, ";
	$sql .= "dashboard_detail_background_color, ";
	$sql .= "dashboard_background_gradient_style, ";
	$sql .= "dashboard_background_gradient_angle, ";
	$sql .= "dashboard_column_span, ";
	$sql .= "dashboard_row_span, ";
	$sql .= "dashboard_details_state, ";
	$sql .= "dashboard_order, ";
	$sql .= "cast(dashboard_enabled as text), ";
	$sql .= "dashboard_description, ";
	$sql .= "dashboard_parent_uuid ";
	$sql .= "from v_dashboard as d ";
	$sql .= "where dashboard_enabled = 'true' ";
	$sql .= "and dashboard_uuid in ( ";
	$sql .= "	select dashboard_uuid from v_dashboard_groups where group_uuid in ( ";
	$sql .= "		".$group_uuids_in." ";
	$sql .= "	) ";
	$sql .= ") ";
	$sql .= "order by dashboard_order, dashboard_name asc ";
	$dashboard = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//get http post variables and set them to php variables
	if (count($_POST) > 0 && permission_exists('dashboard_edit')) {
		//set the variables from the http values
		if (isset($_POST["widget_order"])) {
			$widgets = explode(",", $_POST["widget_order"]);
			$x = 0;

			foreach ($widgets as $widget) {
				list($widget_id, $parent_id, $order) = explode("|", $widget);
				$parent_uuid = null;

				foreach ($dashboard as $row) {
					$dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_uuid']));
					if ($widget_id == $dashboard_id) {
						if (!empty($parent_id)) {
							//find parent uuid
							foreach ($dashboard as $parent_row) {
								$parent_dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $parent_row['dashboard_uuid']));
								if ($parent_dashboard_id === $parent_id) {
									$parent_uuid = $parent_row['dashboard_uuid'];
									break;
								}
							}
						}
						$array['dashboard'][$x]['dashboard_uuid'] = $row['dashboard_uuid'];
						$array['dashboard'][$x]['dashboard_name'] = $row['dashboard_name'];
						$array['dashboard'][$x]['dashboard_icon'] = $row['dashboard_icon'];
						$array['dashboard'][$x]['dashboard_url'] = $row['dashboard_url'];
						$array['dashboard'][$x]['dashboard_content'] = $row['dashboard_content'];
						$array['dashboard'][$x]['dashboard_content_text_align'] = $row['dashboard_content_text_align'];
						$array['dashboard'][$x]['dashboard_content_details'] = $row['dashboard_content_details'];
						$array['dashboard'][$x]['dashboard_target'] = $row['dashboard_target'];
						$array['dashboard'][$x]['dashboard_width'] = $row['dashboard_width'];
						$array['dashboard'][$x]['dashboard_height'] = $row['dashboard_height'];
						$array['dashboard'][$x]['dashboard_order'] = $order;
						$array['dashboard'][$x]['dashboard_parent_uuid'] = $parent_uuid;
						$x++;
						break;
					}
				}
			}

			//save the data
			if (is_array($array)) {
				$database->app_name = 'dashboard';
				$database->app_uuid = '55533bef-4f04-434a-92af-999c1e9927f7';
				$database->save($array);
			}

			//redirect the browser
			message::add($text['message-update']);
			header("Location: /core/dashboard/index.php");
			return;
		}
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

//load the header
	$document['title'] = $text['title-dashboard'];
	require_once "resources/header.php";

//include websockets
	$version = md5(file_get_contents(__DIR__, '/resources/javascript/ws_client.js'));
	echo "<script src='/core/dashboard/resources/javascript/ws_client.js?v=$version'></script>\n";

//include sortablejs
	echo "<script src='/resources/sortablejs/sortable.min.js'></script>";

//include chart.js
	echo "<script src='/resources/chartjs/chart.min.js'></script>";

//chart variables
	echo "<script>\n";
	echo "	var chart_text_font = '".($settings->get('theme', 'dashboard_number_text_font') ?? 'arial')."';\n";
	echo "	var chart_text_size = '".($settings->get('theme', 'dashboard_chart_text_size') ?? '30px')."';\n";
	echo "	Chart.overrides.doughnut.cutout = '".($settings->get('theme', 'dashboard_chart_cutout') ?? '75%')."';\n";
	echo "	Chart.defaults.responsive = true;\n";
	echo "	Chart.defaults.maintainAspectRatio = false;\n";
	echo "	Chart.defaults.plugins.legend.display = false;\n";
	echo "</script>\n";

//determine initial state all button to display
	$expanded_all = true;
	if (!empty($dashboard)) {
		foreach ($dashboard as $row) {
			if ($row['dashboard_details_state'] == 'contracted' || $row['dashboard_details_state'] == 'hidden' || $row['dashboard_details_state'] == 'disabled') { $expanded_all = false; }
		}
	}

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<form id='dashboard' method='post' _onsubmit='setFormSubmitting()'>\n";
	if ($_SESSION['theme']['menu_style']['text'] != 'side') {
		echo "		".$text['label-welcome']." <a href='".PROJECT_PATH."/core/users/user_edit.php?id=user'>".$_SESSION["username"]."</a>&nbsp; &nbsp;";
	}
	if (permission_exists('dashboard_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','name'=>'btn_back','style'=>'display: none;','onclick'=>"edit_mode('off');"]);
		echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','name'=>'btn_save','style'=>'display: none; margin-left: 15px;']);
	}
	echo "<span id='expand_contract'>\n";
		echo button::create(['type'=>'button','label'=>$text['button-expand_all'],'icon'=>$settings->get('theme', 'button_icon_expand'),'id'=>'btn_expand','name'=>'btn_expand','style'=>($expanded_all ? 'display: none;' : null),'onclick'=>"$('.hud_details').slideDown('fast'); $(this).hide(); $('#btn_contract').show(); toggle_grid_row_span_all();"]);
		echo button::create(['type'=>'button','label'=>$text['button-collapse_all'],'icon'=>$settings->get('theme', 'button_icon_contract'),'id'=>'btn_contract','name'=>'btn_contract','style'=>(!$expanded_all ? 'display: none;' : null),'onclick'=>"$('.hud_details').slideUp('fast'); $(this).hide(); $('#btn_expand').show(); toggle_grid_row_span_all();"]);
	echo "</span>\n";
	if (permission_exists('dashboard_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'id'=>'btn_edit','name'=>'btn_edit','style'=>'margin-left: 15px;','onclick'=>"edit_mode('on');"]);
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','name'=>'btn_add','link'=>'dashboard.php']);
	}
	echo "		<input type='hidden' id='widget_order' name='widget_order' value='' />\n";
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

//display login message
	//if (if_group("superadmin") && isset($_SESSION['login']['message']['text']) && $_SESSION['login']['message']['text'] != '') {
	//	echo "<div class='login_message' width='100%'><b>".$text['login-message_attention']."</b>&nbsp;&nbsp;".$_SESSION['login']['message']['text']."&nbsp;&nbsp;(<a href='?msg=dismiss'>".$text['login-message_dismiss']."</a>)</div>\n";
	//}

?>

<style>

* {
	box-sizing: border-box;
	padding: 0;
	margin: 0;
}

.widget {
	/*background-color: #eee;*/
	cursor: pointer;
}

.widgets {
	max-width: 100%;
	margin: 0 auto;
	display: grid;
	grid-gap: 1rem;
}

div.hud_content {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	align-content: start;
	-webkit-transition: .4s;
	-moz-transition: .4s;
	transition: .4s;
}

div.hud_chart {
	height: 150px;
	padding-top: 7px;
}

/* dashboard settings */
<?php
foreach ($dashboard as $row) {
	$dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_uuid']));
	if (!empty($row['dashboard_icon_color'])) {
		echo "#".$dashboard_id." .hud_stat .fas {\n";
		echo "	color: ".$row['dashboard_icon_color'].";\n";
		echo "}\n";
	}
	if ($row['dashboard_label_enabled'] == 'false' && $row['dashboard_path'] != 'dashboard/parent') {
		echo "#".$dashboard_id." .hud_title:first-of-type {\n";
		echo "	display: none;\n";
		echo "}\n";
		echo "#".$dashboard_id." .hud_content {\n";
		echo "	align-content: center;\n";
		echo "}\n";
		echo "#".$dashboard_id." .hud_chart {\n";
		echo "	padding-top: 0;\n";
		echo "}\n";
	}
	if (!empty($row['dashboard_label_text_color']) || !empty($row['dashboard_label_background_color'])) {
		echo "#".$dashboard_id." > .hud_box > .hud_content > .hud_title:first-of-type {\n";
		if (!empty($row['dashboard_label_text_color'])) { echo "	color: ".$row['dashboard_label_text_color'].";\n"; }
		if (!empty($row['dashboard_label_background_color'])) { echo "	background-color: ".$row['dashboard_label_background_color'].";\n"; }
		echo "}\n";
	}
	if (!empty($row['dashboard_label_text_color_hover']) || !empty($row['dashboard_label_background_color_hover'])) {
		echo "#".$dashboard_id.":hover  > .hud_box > .hud_content > .hud_title:first-of-type {\n";
		if (!empty($row['dashboard_label_text_color_hover'])) { echo "	color: ".$row['dashboard_label_text_color_hover'].";\n"; }
		if (!empty($row['dashboard_label_background_color_hover'])) { echo "	background-color: ".$row['dashboard_label_background_color_hover'].";\n"; }
		echo "}\n";
	}
	if (!empty($row['dashboard_number_text_color'])) {
		echo "#".$dashboard_id." > .hud_box > .hud_content > .hud_stat {\n";
		echo "	color: ".$row['dashboard_number_text_color'].";\n";
		echo "}\n";
	}
	if (!empty($row['dashboard_number_text_color_hover'])) {
		echo "#".$dashboard_id.":hover > .hud_box > .hud_content > .hud_stat {\n";
		echo "	color: ".$row['dashboard_number_text_color_hover'].";\n";
		echo "}\n";
	}
	if (!empty($row['dashboard_background_color'])) {
		$background_color = json_decode($row['dashboard_background_color'], true);
		echo "#".$dashboard_id." > .hud_box:first-of-type {\n";
		echo "	background: ".$background_color[0].";\n";
		if (empty($row['dashboard_background_gradient_style']) || $row['dashboard_background_gradient_style'] == 'mirror') {
			echo "	background-image: linear-gradient(".(empty($row['dashboard_background_gradient_angle']) ? '0deg' : $row['dashboard_background_gradient_angle'].'deg').", ".$background_color[1]." 0%, ".$background_color[0]." 30%, ".$background_color[0]." 70%, ".$background_color[1]." 100%);\n";
		}
		else { //simple
			echo "	background-image: linear-gradient(".(empty($row['dashboard_background_gradient_angle']) ? '0deg' : $row['dashboard_background_gradient_angle'].'deg').", ".$background_color[0]." 0%, ".$background_color[1]." 100%);\n";
		}
		echo "}\n";
	}
	if (!empty($row['dashboard_background_color_hover'])) {
		$background_color_hover = json_decode($row['dashboard_background_color_hover'], true);
		echo "#".$dashboard_id.":hover > .hud_box:first-of-type {\n";
		echo "	background: ".$background_color_hover[0].";\n";
		if (empty($row['dashboard_background_gradient_style']) || $row['dashboard_background_gradient_style'] == 'mirror') {
			echo "	background-image: linear-gradient(".(empty($row['dashboard_background_gradient_angle']) ? '0deg' : $row['dashboard_background_gradient_angle'].'deg').", ".$background_color_hover[1]." 0%, ".$background_color_hover[0]." 30%, ".$background_color_hover[0]." 70%, ".$background_color_hover[1]." 100%);\n";
		}
		else { //simple
			echo "	background-image: linear-gradient(".(empty($row['dashboard_background_gradient_angle']) ? '0deg' : $row['dashboard_background_gradient_angle'].'deg').", ".$background_color_hover[0]." 0%, ".$background_color_hover[1]." 100%);\n";
		}
		echo "}\n";
	}
	if (!empty($row['dashboard_detail_background_color'])) {
		$detail_background_color = json_decode($row['dashboard_detail_background_color'], true);
		echo "#".$dashboard_id." > .hud_box > .hud_details {\n";
		echo "	background: ".$detail_background_color[0].";\n";
		if (empty($row['dashboard_background_gradient_style']) || $row['dashboard_background_gradient_style'] == 'mirror') {
			echo "	background-image: linear-gradient(".(empty($row['dashboard_background_gradient_angle']) ? '0deg' : $row['dashboard_background_gradient_angle'].'deg').", ".$detail_background_color[1]." 0%, ".$detail_background_color[0]." 30%, ".$detail_background_color[0]." 70%, ".$detail_background_color[1]." 100%);\n";
		}
		else { //simple
			echo "	background-image: linear-gradient(".(empty($row['dashboard_background_gradient_angle']) ? '0deg' : $row['dashboard_background_gradient_angle'].'deg').", ".$detail_background_color[0]." 0%, ".$detail_background_color[1]." 100%);\n";
		}
		echo "}\n";
	}
	if ($row['dashboard_path'] == "dashboard/icon") {
		echo "#".$dashboard_id." div.hud_content,\n";
		echo "#".$dashboard_id." span.hud_title,\n";
		echo "#".$dashboard_id." span.hud_stat {\n";
		echo "	transition: .4s;\n";
		echo "}\n";
	}
	switch ($row['dashboard_row_span']) {
		case 1:
			echo "#".$dashboard_id." > .hud_box > .hud_content {\n";
			echo "	height: 89.5px;\n";
			echo "}\n";
			echo "#".$dashboard_id." .hud_stat {\n";
			echo "	line-height: 0;\n";
			echo "	font-size: 30pt;\n";
			echo "}\n";
			echo "#".$dashboard_id." .hud_chart {\n";
			echo "	height: 54px;\n";
			echo "	width: 180px;\n";
			echo "	padding-top: 0;\n";
			echo "}\n";
			echo "#".$dashboard_id." .hud_stat .fas {\n";
			echo "	line-height: 0;\n";
			echo "	font-size: 24pt;\n";
			echo "}\n";
			break;
		case 2:
			echo "#".$dashboard_id." > .hud_box > .hud_content {\n";
			echo "	height: 195px;\n";
			echo "}\n";
			break;
		case 3:
			echo "#".$dashboard_id." > .hud_box > .hud_content {\n";
			echo "	height: 300.5px;\n";
			echo "}\n";
			break;
		case 4:
			echo "#".$dashboard_id." > .hud_box > .hud_content {\n";
			echo "	height: 406px;\n";
			echo "}\n";
			break;
		default: //if empty
			echo "#".$dashboard_id." > .hud_box > .hud_content {\n";
			echo "	height: 195px;\n";
			echo "}\n";
	}
	$row_span = $row['dashboard_row_span'] * 4;
	$expanded_row_span = $row_span + 13;
	if ($row['dashboard_details_state'] === "expanded" || $row['dashboard_details_state'] === "contracted") {
		$row_span += 1;
		$expanded_row_span += 1;
	}
	if (!empty($row['dashboard_row_span'])) {
		echo "#".$dashboard_id." {\n";
		echo "	grid-row: span ".$row_span.";\n";
		echo "}\n";
		echo "#".$dashboard_id.".expanded {\n";
		echo "	grid-row: span ".$expanded_row_span.";\n";
		echo "}\n";
	}
	if (!empty($row['dashboard_column_span'])) {
		echo "#".$dashboard_id." {\n";
		echo "	grid-column: span ".$row['dashboard_column_span'].";\n";
		echo "}\n";
	}
	if ($row['dashboard_path'] != "dashboard/icon" && $row['dashboard_chart_type'] != "icon" && $row['dashboard_column_span'] == 1) {
		echo "#".$dashboard_id.".child_widget {\n";
		echo "	grid-column: span 2;\n";
		echo "}\n";
	}
}
?>

/* Screen smaller than 575px? 1 columns */
@media (max-width: 575px) {
	.widgets { grid-template-columns: repeat(1, minmax(100px, 1fr)); }
	.col-num { grid-column: span 1; }
	<?php
		foreach ($dashboard as $row) {
			$dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_uuid']));
			if (!empty($row['dashboard_column_span'])) {
				echo "#".$dashboard_id." {\n";
				echo "	grid-column: span 1;\n";
				echo "}\n";
			}
			if ($row['dashboard_details_state'] == "hidden" || $row['dashboard_details_state'] == "disabled") {
				echo "#".$dashboard_id." .hud_box .hud_expander, \n";
				echo "#".$dashboard_id." .hud_box .hud_details {\n";
				echo "	display: none;\n";
				echo "}\n";
			}
		}
	?>
}

/* Screen larger than 575px? 2 columns */
@media (min-width: 575px) {
	.widgets { grid-template-columns: repeat(2, minmax(100px, 1fr)); }
	.col-num { grid-column: span 2; }
	<?php
		foreach ($dashboard as $row) {
			$dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_uuid']));
			if ($row['dashboard_column_span'] > 2) {
				echo "#".$dashboard_id." {\n";
				echo "	grid-column: span 2;\n";
				echo "}\n";
			}
			if ($row['dashboard_details_state'] == "expanded") {
				echo "#".$dashboard_id." .hud_box .hud_details {\n";
				echo "	display: block;\n";
				echo "}\n";
			}
			if ($row['dashboard_details_state'] == "contracted") {
				echo "#".$dashboard_id." .widget .hud_box .hud_details {\n";
				echo "	display: none;\n";
				echo "}\n";
			}
			if ($row['dashboard_details_state'] == "hidden" || $row['dashboard_details_state'] == "disabled") {
				echo "#".$dashboard_id." .hud_box .hud_expander, \n";
				echo "#".$dashboard_id." .hud_box .hud_details {\n";
				echo "	display: none;\n";
				echo "}\n";
			}
		}
	?>
}

/* Screen larger than 1300px? 3 columns */
@media (min-width: 1300px) {
	.widgets { grid-template-columns: repeat(3, minmax(100px, 1fr)); }
	.col-num { grid-column: span 2; }
	<?php
		foreach ($dashboard as $row) {
			$dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_uuid']));
			if ($row['dashboard_column_span'] > 3) {
				echo "#".$dashboard_id." {\n";
				echo "	grid-column: span 3;\n";
				echo "}\n";
			}
		}
	?>
}

/* Screen larger than 1500px? 4 columns */
@media (min-width: 1500px) {
	.widgets { grid-template-columns: repeat(4, minmax(100px, 1fr)); }
	.col-num { grid-column: span 2; }
	<?php
		foreach ($dashboard as $row) {
			$dashboard_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_uuid']));
			if (!empty($row['dashboard_column_span'])) {
				echo "#".$dashboard_id." {\n";
				echo "	grid-column: span ".$row['dashboard_column_span'].";\n";
				echo "}\n";
			}
		}
	?>
}

/* Screen larger than 2000px? 5 columns */
@media (min-width: 2000px) {
	.widgets { grid-template-columns: repeat(5, minmax(100px, 1fr)); }
	.col-num { grid-column: span 2; }
}

</style>

<script>

document.addEventListener('click', function(event) {
	let hud_content = event.target.closest('.hud_content');
	let hud_expander = event.target.closest('.hud_expander');

	if (hud_content || hud_expander) {
		let widget = event.target.closest('div.widget, div.child_widget');

		if (widget.classList.contains('disabled')) {
			return;
		}

		if (widget && widget.id) {
			toggle_grid_row_span(widget.id);
		}
	}
});

function toggle_grid_row_span(dashboard_id) {
	let widget = document.getElementById(dashboard_id);

	if (widget.classList.contains('expanded')) {
		widget.classList.remove('expanded');
	}
	else {
		widget.classList.add('expanded');
	}
}

let first_toggle = false;

function toggle_grid_row_span_all() {
	const widgets = document.querySelectorAll('div.widget, div.child_widget');

	widgets.forEach(widget => {
		if (widget.classList.contains('disabled')) {
			return;
		}

		if (!first_toggle && widget.classList.contains('expanded')) {
			return;
		}

		if (widget.classList.contains('expanded') || widget.getAttribute('data-expanded-all') === 'true') {
			widget.classList.remove('expanded');
			widget.setAttribute('data-expanded-all', 'false');
		}
		else {
			widget.classList.add('expanded');
			widget.setAttribute('data-expanded-all', 'true');
		}
	});

	first_toggle = true;
}

</script>

<?php

//include the dashboards
	echo "<div class='widgets' id='widgets' style='padding: 0 5px;'>\n";
	$x = 0;
	foreach ($dashboard as $row) {
		//skip child widgets
		if (!empty($row['dashboard_parent_uuid'])) {
			continue;
		}

		//set the variables
		$dashboard_uuid = $row['dashboard_uuid'] ?? '';
		$dashboard_name = $row['dashboard_name'] ?? '';
		$dashboard_icon = $row['dashboard_icon'] ?? '';
		$dashboard_url = $row['dashboard_url'] ?? '';
		$dashboard_target = $row['dashboard_target'] ?? '';
		$dashboard_width = $row['dashboard_width'] ?? '';
		$dashboard_height = $row['dashboard_height'] ?? '';
		$dashboard_content = $row['dashboard_content'] ?? '';
		$dashboard_content_text_align = $row['dashboard_content_text_align'] ?? '';
		$dashboard_content_details = $row['dashboard_content_details'] ?? '';
		$dashboard_chart_type = $row['dashboard_chart_type'] ?? '';
		$dashboard_label_text_color = $row['dashboard_label_text_color'] ?? $settings->get('theme', 'dashboard_label_text_color', '');
		$dashboard_number_text_color = $row['dashboard_number_text_color'] ?? $settings->get('theme', 'dashboard_number_text_color', '');
		$dashboard_number_background_color = $row['dashboard_number_background_color'] ?? $settings->get('theme', 'dashboard_number_background_color', '');
		$dashboard_details_state = $row['dashboard_details_state'] ?? 'hidden';
		$dashboard_row_span = $row['dashboard_row_span'] ?? '';

		//define the regex patterns
		$uuid_pattern = '/[^-A-Fa-f0-9]/';
		$number_pattern = '/[^-A-Za-z0-9()*#]/';
		$text_pattern = '/[^a-zA-Z0-9 _\-\/.\?:\=#\n]/';

		//sanitize the data
		$dashboard_uuid = preg_replace($uuid_pattern, '', $dashboard_uuid);
		$dashboard_id = 'id_'.md5($dashboard_uuid);
		$dashboard_name = trim(preg_replace($text_pattern, '', $dashboard_name));
		$dashboard_icon = preg_replace($text_pattern, '', $dashboard_icon);
		$dashboard_url = trim(preg_replace($text_pattern, '', $dashboard_url));
		$dashboard_target = trim(preg_replace($text_pattern, '', $dashboard_target));
		$dashboard_width = trim(preg_replace($text_pattern, '', $dashboard_width));
		$dashboard_height = trim(preg_replace($text_pattern, '', $dashboard_height));
		$dashboard_content = preg_replace($text_pattern, '', $dashboard_content);
		$dashboard_content = str_replace("\n", '<br />', $dashboard_content);
		$dashboard_content_text_align = trim(preg_replace($text_pattern, '', $dashboard_content_text_align));
		$dashboard_content_details = preg_replace($text_pattern, '', $dashboard_content_details);
		$dashboard_content_details = str_replace("\n", '<br />', $dashboard_content_details);
		$dashboard_chart_type = preg_replace($text_pattern, '', $dashboard_chart_type);
		$dashboard_label_text_color = preg_replace($text_pattern, '', $dashboard_label_text_color);
		$dashboard_number_text_color = preg_replace($text_pattern, '', $dashboard_number_text_color);
		$dashboard_number_background_color = preg_replace($text_pattern, '', $dashboard_number_background_color);
		$dashboard_details_state = preg_replace($text_pattern, '', $dashboard_details_state);
		$dashboard_row_span = preg_replace($number_pattern, '', $dashboard_row_span);
		$dashboard_path = preg_replace($text_pattern, '', strtolower($row['dashboard_path']));

		//find the application and widget
		$dashboard_path_array = explode('/', $dashboard_path);
		$application_name = $dashboard_path_array[0];
		$widget_name = $dashboard_path_array[1];
		$path_array = glob(dirname(__DIR__, 2).'/*/'.$application_name.'/resources/dashboard/'.$widget_name.'.php');

		echo "<div class='widget ".$dashboard_details_state."' id='".$dashboard_id."' ".($dashboard_path == 'dashboard/parent' ? "data-is-parent='true'" : null)." draggable='false'>\n";
		if (file_exists($path_array[0])) {
			include $path_array[0];
		}
		echo "</div>\n";


		$x++;
	}
	echo "</div>\n";

//begin edit
	if (permission_exists('dashboard_edit')) {
		?>

		<style>
		/*To prevent user selecting inside the drag source*/
		[draggable] {
			-moz-user-select: none;
			-khtml-user-select: none;
			-webkit-user-select: none;
			user-select: none;
		}

		div.widget.editable {
			cursor: move;
		}

		.hud_box.editable {
			transition: 0.2s;
			border: 1px dashed rgba(0,0,0,0.4);
		}

		.hud_box.editable:hover {
			box-shadow: 0 5px 10px rgba(0,0,0,0.2);
			border: 1px dashed rgba(0,0,0,0.4);
			transform: scale(1.03, 1.03);
			transition: 0.2s;
		}

		.hud_box .hud_box.editable:hover {
			box-shadow: none;
			transform: none;
		}

		.ghost {
			border: 2px dashed rgba(0,0,0,1);
			<?php $br = format_border_radius($_SESSION['theme']['dashboard_border_radius']['text'] ?? null, '5px'); ?>
			-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			<?php unset($br); ?>
			opacity: 0.2;
		}
		</style>

		<script>
		const widgets = document.getElementById('widgets');
		let sortable;

		//make widgets draggable
		function edit_mode(state) {

			if (state == 'on') {
				$('span#expand_contract, #btn_edit, #btn_add').hide();
				$('.hud_box').addClass('editable');
				$('#btn_back, #btn_save').show();
				$('div.widget').attr('draggable',true).addClass('editable');
				$('div.child_widget').attr('draggable',true).addClass('editable');

				function update_widget_order() {
					let widget_ids_list = [];
					let order = 10;

					widgets.querySelectorAll(':scope > div.widget[id]').forEach(widget => {
						const widget_id = widget.id;

						//add the widgets to the list
						widget_ids_list.push(`${widget_id}|null|${order}`);
						order += 10;

						//add the nested widgets to the list
						const nested_container = widget.querySelector('.parent_widget');
						if (nested_container) {
							nested_container.querySelectorAll(':scope > div.child_widget[id]').forEach(nested => {
								const child_id = nested.id;
								widget_ids_list.push(`${child_id}|${widget_id}|${order}`);
								order += 10;
							});
						}
					});

					document.getElementById('widget_order').value = widget_ids_list;
				}

				sortable = Sortable.create(widgets, {
					group: {
						name: 'shared',
						pull: function(to, from, dragEl) {
							return !dragEl.hasAttribute('data-is-parent');
						},
						put: true,
					},
					animation: 150,
					draggable: '.widget',
					preventOnFilter: true,
					ghostClass: 'ghost',
					onSort: update_widget_order,
					onAdd: function (event) {
						event.item.classList.add('widget');
						update_widget_order();
					},
					onRemove: function (event) {
						event.item.classList.remove('widget');
						update_widget_order();
					},
					onMove: function (event) {
						if (event.to !== event.from) {
							event.dragged.classList.remove('widget');
						} else {
							event.dragged.classList.add('widget');
						}
					},
				});

				document.querySelectorAll('.parent_widget').forEach(function(container) {
					Sortable.create(container, {
						group: {
							name: 'shared',
							pull: function(to, from, dragEl) {
								return true;
							},
							put: function(to, from, dragEl) {
								return !dragEl.hasAttribute('data-is-parent');
							},
						},
						animation: 150,
						draggable: '.child_widget',
						ghostClass: 'ghost',
						fallbackOnBody: true,
						swapThreshold: 0.65,
						onSort: update_widget_order,
						onAdd: function (event) {
							event.item.classList.add('child_widget');
							update_widget_order();
						},
						onRemove: function (event) {
							update_widget_order();
						},
						onMove: function (event) {
							if (event.to !== event.from) {
								event.dragged.classList.remove('child_widget');
							} else {
								event.dragged.classList.add('child_widget');
							}
						},
					});
				});

			}
			else { // off

				$('div.widget').attr('draggable',false).removeClass('editable');
				$('div.child_widget').attr('draggable',false).removeClass('editable');
				$('.hud_box').removeClass('editable');
				$('#btn_back, #btn_save').hide();
				$('span#expand_contract, #btn_edit, #btn_add').show();

				sortable.option('disabled', true);
				document.querySelectorAll('.parent_widget').forEach(el => {
				const nested_sortable = Sortable.get(el);
					if (nested_sortable) {
						nested_sortable.option('disabled', true);
					}
				});

			}
		}
		</script>
		<?php
	} //end edit

//show the footer
	require_once "resources/footer.php";

?>
