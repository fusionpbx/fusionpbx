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

//disable login message
	if (isset($_GET['msg']) && $_GET['msg'] == 'dismiss') {
		unset($_SESSION['login']['message']);

		$sql = "update v_default_settings ";
		$sql .= "set default_setting_enabled = false ";
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

//get the dashboard uuid
	$sql = "select dashboard_uuid ";
	$sql .= "from v_dashboards ";
	$sql .= "where dashboard_enabled = true ";
	$sql .= "and (";
	$sql .= "	domain_uuid = :domain_uuid ";
	$sql .= "	or domain_uuid is null ";
	$sql .= ") ";
	if (!empty($_GET['name'])) {
		$sql .= "and dashboard_name = :dashboard_name ";
		$parameters['dashboard_name'] = $_GET['name'];
	}
	$sql .= "order by case when domain_uuid = :domain_uuid then 0 else 1 end ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$result = $database->select($sql, $parameters ?? null, 'all');
	$dashboard_uuid = $result[0]['dashboard_uuid'] ?? null;
	unset($sql, $parameters);

//get the list
	$sql = "select ";
	$sql .= "dashboard_uuid, ";
	$sql .= "dashboard_widget_uuid, ";
	$sql .= "widget_name, ";
	$sql .= "widget_path, ";
	$sql .= "widget_icon, ";
	$sql .= "widget_icon_color, ";
	$sql .= "widget_url, ";
	$sql .= "widget_target, ";
	$sql .= "widget_width, ";
	$sql .= "widget_height, ";
	$sql .= "widget_content, ";
	$sql .= "widget_content_text_align, ";
	$sql .= "widget_content_details, ";
	$sql .= "widget_chart_type, ";
	$sql .= "widget_label_enabled, ";
	$sql .= "widget_label_text_color, ";
	$sql .= "widget_label_text_color_hover, ";
	$sql .= "widget_label_background_color, ";
	$sql .= "widget_label_background_color_hover, ";
	$sql .= "widget_number_text_color, ";
	$sql .= "widget_number_text_color_hover, ";
	$sql .= "widget_number_background_color, ";
	$sql .= "widget_background_color, ";
	$sql .= "widget_background_color_hover, ";
	$sql .= "widget_detail_background_color, ";
	$sql .= "widget_background_gradient_style, ";
	$sql .= "widget_background_gradient_angle, ";
	$sql .= "widget_column_span, ";
	$sql .= "widget_row_span, ";
	$sql .= "widget_details_state, ";
	$sql .= "dashboard_widget_parent_uuid, ";
	$sql .= "widget_order, ";
	$sql .= "cast(widget_enabled as text), ";
	$sql .= "widget_description ";
	$sql .= "from v_dashboard_widgets as d ";
	$sql .= "where widget_enabled = true ";
	$sql .= "and dashboard_widget_uuid in ( ";
	$sql .= "	select dashboard_widget_uuid from v_dashboard_widget_groups where group_uuid in ( ";
	$sql .= "		".$group_uuids_in." ";
	$sql .= "	) ";
	$sql .= ") ";
	$sql .= "and dashboard_uuid = :dashboard_uuid ";
	$sql .= "order by widget_order, widget_name asc ";
	$parameters['dashboard_uuid'] = $dashboard_uuid;
	$widgets = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//get the list of widget uuids
	$widget_uuid_list = [];
	foreach ($widgets as $row) {
		$widget_uuid_list[] = $row['dashboard_widget_uuid'];
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0 && permission_exists('dashboard_edit')) {
		//set the variables from the http values
		if (isset($_POST["widget_order"])) {
			$widget_order = explode(",", $_POST["widget_order"]);
			$x = 0;

			foreach ($widget_order as $widget) {
				list($widget_id, $parent_id, $order) = explode("|", $widget);
				$parent_uuid = null;

				foreach ($widgets as $row) {
					$dashboard_widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_widget_uuid']));
					if ($widget_id == $dashboard_widget_id) {
						if (!empty($parent_id)) {
							//find parent uuid
							foreach ($widgets as $parent_row) {
								$parent_widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $parent_row['dashboard_widget_uuid']));
								if ($parent_widget_id === $parent_id) {
									$parent_uuid = $parent_row['dashboard_widget_uuid'];
									break;
								}
							}
						}
						$array['dashboard_widgets'][$x]['dashboard_widget_uuid'] = $row['dashboard_widget_uuid'];
						$array['dashboard_widgets'][$x]['widget_name'] = $row['widget_name'];
						$array['dashboard_widgets'][$x]['widget_icon'] = $row['widget_icon'];
						$array['dashboard_widgets'][$x]['widget_url'] = $row['widget_url'];
						$array['dashboard_widgets'][$x]['widget_content'] = $row['widget_content'];
						$array['dashboard_widgets'][$x]['widget_content_text_align'] = $row['widget_content_text_align'];
						$array['dashboard_widgets'][$x]['widget_content_details'] = $row['widget_content_details'];
						$array['dashboard_widgets'][$x]['widget_target'] = $row['widget_target'];
						$array['dashboard_widgets'][$x]['widget_width'] = $row['widget_width'];
						$array['dashboard_widgets'][$x]['widget_height'] = $row['widget_height'];
						$array['dashboard_widgets'][$x]['widget_order'] = $order;
						$array['dashboard_widgets'][$x]['dashboard_widget_parent_uuid'] = $parent_uuid;
						$x++;
						break;
					}
				}
			}

			//save the data
			if (is_array($array)) {
				$database->save($array);
			}

			//redirect the browser
			message::add($text['message-update']);
			header("Location: /core/dashboard/".(!empty($_GET['name']) ? "?name=".urlencode($_GET['name']) : null));
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
	$version = md5(file_get_contents(__DIR__ . '/resources/javascript/ws_client.js'));
	echo "<script src='/core/dashboard/resources/javascript/ws_client.js?v=$version'></script>\n";

//include sortablejs
	echo "<script src='/resources/sortablejs/sortable.min.js'></script>";

//include chart.js
	echo "<script src='/resources/chartjs/chart.min.js'></script>";

//chart variables
	echo "<script>\n";
	echo "	var chart_text_font = '".$settings->get('theme', 'dashboard_number_text_font', 'arial')."';\n";
	echo "	var chart_text_size = '".$settings->get('theme', 'dashboard_chart_text_size', '30px')."';\n";
	echo "	Chart.overrides.doughnut.cutout = '".$settings->get('theme', 'dashboard_chart_cutout', '75%')."';\n";
	echo "	Chart.defaults.responsive = true;\n";
	echo "	Chart.defaults.maintainAspectRatio = false;\n";
	echo "	Chart.defaults.plugins.legend.display = false;\n";
	echo "</script>\n";

//determine initial state all button to display
	$expanded_all = true;
	if (!empty($widgets)) {
		foreach ($widgets as $row) {
			if ($row['widget_details_state'] == 'contracted' || $row['widget_details_state'] == 'hidden' || $row['widget_details_state'] == 'disabled') { $expanded_all = false; }
		}
	}

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<form id='dashboard' method='post' _onsubmit='setFormSubmitting()'>\n";
	if ($settings->get('theme', 'menu_style', '') != 'side') {
		echo "		".$text['label-welcome']." <a href='".PROJECT_PATH."/core/users/user_profile.php'>".$_SESSION["username"]."</a>&nbsp; &nbsp;";
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
	//if (if_group("superadmin") && !empty($settings->get('login', 'message')) && $settings->get('login', 'message') != '') {
	//	echo "<div class='login_message' width='100%'><b>".$text['login-message_attention']."</b>&nbsp;&nbsp;".$settings->get('login', 'message')."&nbsp;&nbsp;(<a href='?msg=dismiss'>".$text['login-message_dismiss']."</a>)</div>\n";
	//}

?>

<style>

:root {
	--row-height: 89.5px;
	--grid-gap: 16px;
}

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
	grid-gap: var(--grid-gap);
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
foreach ($widgets as $row) {
	$widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_widget_uuid']));
	if (!empty($row['widget_icon_color'])) {
		echo "#".$widget_id." .hud_stat .fas {\n";
		echo "	color: ".$row['widget_icon_color'].";\n";
		echo "}\n";
	}
	if ($row['widget_label_enabled'] === false && $row['widget_path'] != 'dashboard/parent') {
		echo "#".$widget_id." .hud_title:first-of-type {\n";
		echo "	display: none;\n";
		echo "}\n";
		echo "#".$widget_id." .hud_content {\n";
		echo "	align-content: center;\n";
		echo "}\n";
		echo "#".$widget_id." .hud_chart {\n";
		echo "	padding-top: 0;\n";
		echo "}\n";
	}
	if (!empty($row['widget_label_text_color']) || !empty($row['widget_label_background_color'])) {
		echo "#".$widget_id." > .hud_box > .hud_content > .hud_title:first-of-type {\n";
		if (!empty($row['widget_label_text_color'])) { echo "	color: ".$row['widget_label_text_color'].";\n"; }
		if (!empty($row['widget_label_background_color'])) { echo "	background-color: ".$row['widget_label_background_color'].";\n"; }
		echo "}\n";
	}
	if (!empty($row['widget_label_text_color_hover']) || !empty($row['widget_label_background_color_hover'])) {
		echo "#".$widget_id.":hover  > .hud_box > .hud_content > .hud_title:first-of-type {\n";
		if (!empty($row['widget_label_text_color_hover'])) { echo "	color: ".$row['widget_label_text_color_hover'].";\n"; }
		if (!empty($row['widget_label_background_color_hover'])) { echo "	background-color: ".$row['widget_label_background_color_hover'].";\n"; }
		echo "}\n";
	}
	if (!empty($row['widget_number_text_color'])) {
		echo "#".$widget_id." > .hud_box > .hud_content > .hud_stat {\n";
		echo "	color: ".$row['widget_number_text_color'].";\n";
		echo "}\n";
	}
	if (!empty($row['widget_number_text_color_hover'])) {
		echo "#".$widget_id.":hover > .hud_box > .hud_content > .hud_stat {\n";
		echo "	color: ".$row['widget_number_text_color_hover'].";\n";
		echo "}\n";
	}
	if (!empty($row['widget_background_color'])) {
		$background_color = json_validate($row['widget_background_color']) ? json_decode($row['widget_background_color'], true) : $row['widget_background_color'];
		echo "#".$widget_id." > .hud_box:first-of-type {\n";
		echo "	background: ".$background_color[0].";\n";
		if (empty($row['widget_background_gradient_style']) || $row['widget_background_gradient_style'] == 'mirror') {
			echo "	background-image: linear-gradient(".(empty($row['widget_background_gradient_angle']) ? '0deg' : $row['widget_background_gradient_angle'].'deg').", ".$background_color[1]." 0%, ".$background_color[0]." 30%, ".$background_color[0]." 70%, ".$background_color[1]." 100%);\n";
		}
		else { //simple
			echo "	background-image: linear-gradient(".(empty($row['widget_background_gradient_angle']) ? '0deg' : $row['widget_background_gradient_angle'].'deg').", ".$background_color[0]." 0%, ".$background_color[1]." 100%);\n";
		}
		echo "}\n";
	}
	if (!empty($row['widget_background_color_hover'])) {
		$background_color_hover = json_validate($row['widget_background_color_hover']) ? json_decode($row['widget_background_color_hover'], true) : $row['widget_background_color_hover'];
		echo "#".$widget_id.":hover > .hud_box:first-of-type {\n";
		echo "	background: ".$background_color_hover[0].";\n";
		if (empty($row['widget_background_gradient_style']) || $row['widget_background_gradient_style'] == 'mirror') {
			echo "	background-image: linear-gradient(".(empty($row['widget_background_gradient_angle']) ? '0deg' : $row['widget_background_gradient_angle'].'deg').", ".$background_color_hover[1]." 0%, ".$background_color_hover[0]." 30%, ".$background_color_hover[0]." 70%, ".$background_color_hover[1]." 100%);\n";
		}
		else { //simple
			echo "	background-image: linear-gradient(".(empty($row['widget_background_gradient_angle']) ? '0deg' : $row['widget_background_gradient_angle'].'deg').", ".$background_color_hover[0]." 0%, ".$background_color_hover[1]." 100%);\n";
		}
		echo "}\n";
	}
	if (!empty($row['widget_detail_background_color'])) {
		$detail_background_color = json_validate($row['widget_detail_background_color']) ? json_decode($row['widget_detail_background_color'], true) : $row['widget_detail_background_color'];
		echo "#".$widget_id." > .hud_box > .hud_details {\n";
		echo "	background: ".$detail_background_color[0].";\n";
		if (empty($row['widget_background_gradient_style']) || $row['widget_background_gradient_style'] == 'mirror') {
			echo "	background-image: linear-gradient(".(empty($row['widget_background_gradient_angle']) ? '0deg' : $row['widget_background_gradient_angle'].'deg').", ".$detail_background_color[1]." 0%, ".$detail_background_color[0]." 30%, ".$detail_background_color[0]." 70%, ".$detail_background_color[1]." 100%);\n";
		}
		else { //simple
			echo "	background-image: linear-gradient(".(empty($row['widget_background_gradient_angle']) ? '0deg' : $row['widget_background_gradient_angle'].'deg').", ".$detail_background_color[0]." 0%, ".$detail_background_color[1]." 100%);\n";
		}
		echo "}\n";
	}
	if ($row['widget_path'] == "dashboard/icon") {
		echo "#".$widget_id." div.hud_content,\n";
		echo "#".$widget_id." span.hud_title,\n";
		echo "#".$widget_id." span.hud_stat {\n";
		echo "	transition: .4s;\n";
		echo "}\n";
	}
	switch ($row['widget_row_span']) {
		case 1:
			echo "#".$widget_id." > .hud_box > .hud_content {\n";
			echo "	height: var(--row-height);\n";
			echo "}\n";
			echo "#".$widget_id." .hud_stat {\n";
			echo "	line-height: 0;\n";
			echo "	font-size: 30pt;\n";
			echo "}\n";
			echo "#".$widget_id." .hud_stat .fas {\n";
			echo "	line-height: 0;\n";
			echo "	font-size: 24pt;\n";
			echo "}\n";
			echo "#".$widget_id." > .hud_box > .hud_content > .hud_chart {\n";
			echo "	height: 54px;\n";
			echo "	width: 180px;\n";
			echo "	padding-top: 0;\n";
			echo "}\n";
			break;
		case 2:
			echo "#".$widget_id." > .hud_box > .hud_content {\n";
			echo "	height: calc((var(--row-height) * 2) + var(--grid-gap));\n";
			echo "}\n";
			break;
		case 3:
			echo "#".$widget_id." > .hud_box > .hud_content {\n";
			echo "	height: calc((var(--row-height) * 3) + (var(--grid-gap) * 2));\n";
			echo "}\n";
			break;
		case 4:
			echo "#".$widget_id." > .hud_box > .hud_content {\n";
			echo "	height: calc((var(--row-height) * 4) + (var(--grid-gap) * 3));\n";
			echo "}\n";
			break;
		default: //if empty
			echo "#".$widget_id." > .hud_box > .hud_content {\n";
			echo "	height: calc((var(--row-height) * 2) + var(--grid-gap));\n";
			echo "}\n";
	}
	$row_span = $row['widget_row_span'] * 4;
	$expanded_row_span = $row_span + 13;
	if ($row['widget_details_state'] === "expanded" || $row['widget_details_state'] === "contracted") {
		$row_span += 1;
		$expanded_row_span += 1;
	}
	if (!empty($row['widget_row_span'])) {
		echo "#".$widget_id." {\n";
		echo "	--row-span: ".$row['widget_row_span'].";\n";
		echo "}\n";
		echo "#".$widget_id." {\n";
		echo "	grid-row: span ".$row_span.";\n";
		echo "}\n";
		echo "#".$widget_id.".expanded {\n";
		echo "	grid-row: span ".$expanded_row_span.";\n";
		echo "}\n";
	}
	if (!empty($row['widget_column_span'])) {
		echo "#".$widget_id." {\n";
		echo "	grid-column: span ".$row['widget_column_span'].";\n";
		echo "}\n";
	}
	if ($row['widget_path'] != "dashboard/icon" && $row['widget_chart_type'] != "icon" && $row['widget_column_span'] == 1) {
		echo "#".$widget_id.".child_widget {\n";
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
		foreach ($widgets as $row) {
			$widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_widget_uuid']));
			if (!empty($row['widget_column_span'])) {
				echo "#".$widget_id." {\n";
				echo "	grid-column: span 1;\n";
				echo "}\n";
			}
			if ($row['widget_details_state'] == "hidden" || $row['widget_details_state'] == "disabled") {
				echo "#".$widget_id." .hud_box .hud_expander, \n";
				echo "#".$widget_id." .hud_box .hud_details {\n";
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
		foreach ($widgets as $row) {
			$widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_widget_uuid']));
			if ($row['widget_column_span'] > 2) {
				echo "#".$widget_id." {\n";
				echo "	grid-column: span 2;\n";
				echo "}\n";
			}
			if ($row['widget_details_state'] == "expanded") {
				echo "#".$widget_id." .hud_box .hud_details {\n";
				echo "	display: block;\n";
				echo "}\n";
			}
			if ($row['widget_details_state'] == "contracted") {
				echo "#".$widget_id." .widget .hud_box .hud_details {\n";
				echo "	display: none;\n";
				echo "}\n";
			}
			if ($row['widget_details_state'] == "hidden" || $row['widget_details_state'] == "disabled") {
				echo "#".$widget_id." .hud_box .hud_expander, \n";
				echo "#".$widget_id." .hud_box .hud_details {\n";
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
		foreach ($widgets as $row) {
			$widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_widget_uuid']));
			if ($row['widget_column_span'] > 3) {
				echo "#".$widget_id." {\n";
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
		foreach ($widgets as $row) {
			$widget_id = 'id_'.md5(preg_replace('/[^-A-Fa-f0-9]/', '', $row['dashboard_widget_uuid']));
			if (!empty($row['widget_column_span'])) {
				echo "#".$widget_id." {\n";
				echo "	grid-column: span ".$row['widget_column_span'].";\n";
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

function toggle_grid_row_span(widget_id) {
	let widget = document.getElementById(widget_id);

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

function update_parent_height() {
	const parent_widgets = document.querySelectorAll('.parent_widget');

	parent_widgets.forEach(parent_widget => {
		if (!parent_widget.dataset.originalHeight) {
			parent_widget.dataset.originalHeight = parseFloat(window.getComputedStyle(parent_widget).height.replace('px', ''));
		}
		const widget = parent_widget.closest('.widget');
		const row_gap = parseInt(window.getComputedStyle(document.documentElement).getPropertyValue('--grid-gap').replace('px', ''));
		const row_height = parseInt(window.getComputedStyle(document.documentElement).getPropertyValue('--row-height').replace('px', ''));
		const original_row_span = parseInt(window.getComputedStyle(widget).getPropertyValue('--row-span').replace('span ', ''));
		const original_height = parseFloat(parent_widget.dataset.originalHeight);
		const content_height = parent_widget.scrollHeight;
		const new_row_span = Math.ceil(content_height / (row_height + row_gap));

		if (content_height !== original_height) {
			widget.style.gridRow = `span ${new_row_span * 4}`;
		}
		else {
			widget.style.gridRow = `span ${original_row_span * 4}`;
		}

		parent_widget.style.minHeight = `${original_height}px`;
		parent_widget.style.height = `auto`;
	});
}

document.addEventListener('DOMContentLoaded', update_parent_height);
window.addEventListener('resize', update_parent_height);

</script>

<?php

//include the dashboards
	echo "<div class='widgets' id='widgets' style='padding: 0 5px;'>\n";
	$x = 0;
	foreach ($widgets as $row) {
		//skip child widgets unless the parent doesn't exist
		if (!empty($row['dashboard_widget_parent_uuid']) && in_array($row['dashboard_widget_parent_uuid'], $widget_uuid_list)) {
			continue;
		}

		//set the variables
		$widget_uuid = $row['dashboard_widget_uuid'] ?? '';
		$widget_name = $row['widget_name'] ?? '';
		$widget_icon = $row['widget_icon'] ?? '';
		$widget_url = $row['widget_url'] ?? '';
		$widget_target = $row['widget_target'] ?? '';
		$widget_width = $row['widget_width'] ?? '';
		$widget_height = $row['widget_height'] ?? '';
		$widget_content = $row['widget_content'] ?? '';
		$widget_content_text_align = $row['widget_content_text_align'] ?? '';
		$widget_content_details = $row['widget_content_details'] ?? '';
		$widget_chart_type = $row['widget_chart_type'] ?? '';
		$widget_label_text_color = $row['widget_label_text_color'] ?? $settings->get('theme', 'dashboard_label_text_color', '');
		$widget_number_text_color = $row['widget_number_text_color'] ?? $settings->get('theme', 'dashboard_number_text_color', '');
		$widget_number_background_color = $row['widget_number_background_color'] ?? $settings->get('theme', 'dashboard_number_background_color', '');
		$widget_details_state = $row['widget_details_state'] ?? 'hidden';
		$widget_row_span = $row['widget_row_span'] ?? '';

		//define the regex patterns
		$uuid_pattern = '/[^-A-Fa-f0-9]/';
		$number_pattern = '/[^-A-Za-z0-9()*#]/';
		$text_pattern = '/[^a-zA-Z0-9 _\-\/.\?:\=#\n]/';

		//sanitize the data
		$widget_uuid = preg_replace($uuid_pattern, '', $widget_uuid);
		$widget_id = 'id_'.md5($widget_uuid);
		$widget_name = trim(preg_replace($text_pattern, '', $widget_name));
		$widget_icon = preg_replace($text_pattern, '', $widget_icon);
		$widget_url = trim(preg_replace($text_pattern, '', $widget_url));
		$widget_target = trim(preg_replace($text_pattern, '', $widget_target));
		$widget_width = trim(preg_replace($text_pattern, '', $widget_width));
		$widget_height = trim(preg_replace($text_pattern, '', $widget_height));
		$widget_content = preg_replace($text_pattern, '', $widget_content);
		$widget_content = str_replace("\n", '<br />', $widget_content);
		$widget_content_text_align = trim(preg_replace($text_pattern, '', $widget_content_text_align));
		$widget_content_details = preg_replace($text_pattern, '', $widget_content_details);
		$widget_content_details = str_replace("\n", '<br />', $widget_content_details);
		$widget_chart_type = preg_replace($text_pattern, '', $widget_chart_type);
		$widget_label_text_color = preg_replace($text_pattern, '', $widget_label_text_color);
		$widget_number_text_color = preg_replace($text_pattern, '', $widget_number_text_color);
		$widget_number_background_color = preg_replace($text_pattern, '', $widget_number_background_color);
		$widget_details_state = preg_replace($text_pattern, '', $widget_details_state);
		$widget_row_span = preg_replace($number_pattern, '', $widget_row_span);
		$widget_path = preg_replace($text_pattern, '', strtolower($row['widget_path']));

		//find the application and widget
		$widget_path_array = explode('/', $widget_path);
		$application_name = $widget_path_array[0];
		$widget_path_name = $widget_path_array[1];
		$path_array = glob(dirname(__DIR__, 2).'/*/'.$application_name.'/resources/dashboard/'.$widget_path_name.'.php');

		echo "<div class='widget ".$widget_details_state."' id='".$widget_id."' ".($widget_path == 'dashboard/parent' ? "data-is-parent='true'" : null)." draggable='false'>\n";
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
			<?php $br = format_border_radius($settings->get('theme', 'dashboard_border_radius') ?? null, '5px'); ?>
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
						onSort: function (event) {
							update_widget_order();
							update_parent_height();
						},
						onAdd: function (event) {
							event.item.classList.add('child_widget');
							update_widget_order();
							update_parent_height();
						},
						onRemove: function (event) {
							update_widget_order();
							update_parent_height();
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
