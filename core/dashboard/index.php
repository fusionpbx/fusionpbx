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
	Portions created by the Initial Developer are Copyright (C) 2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//include the root directory
	include "root.php";

//if config.php file does not exist then redirect to the install page
	if (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
		//do nothing
	} elseif (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
		//original directory
	} elseif (file_exists("/etc/fusionpbx/config.php")){
		//linux
	} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")){
		//bsd
	} else {
		header("Location: ".PROJECT_PATH."/core/install/install.php");
		exit;
	}

//additional includes
	require_once "resources/check_auth.php";

//disable login message
	if (isset($_GET['msg']) && $_GET['msg'] == 'dismiss') {
		unset($_SESSION['login']['message']['text']);

		$sql = "update v_default_settings ";
		$sql .= "set default_setting_enabled = 'false' ";
		$sql .= "where ";
		$sql .= "default_setting_category = 'login' ";
		$sql .= "and default_setting_subcategory = 'message' ";
		$sql .= "and default_setting_name = 'text' ";
		$database = new database;
		$database->execute($sql);
		unset($sql);
	}

//build a list of groups the user is a member of to be used in a SQL in
	foreach($_SESSION['user']['groups'] as $group) {
		$group_uuids[] =  $group['group_uuid'];
	}
	$group_uuids_in = "'".implode("','", $group_uuids)."'";

//get the list
	$sql = "select \n";
	$sql .= "dashboard_uuid, \n";
	$sql .= "dashboard_name, \n";
	$sql .= "dashboard_path, \n";
	$sql .= "dashboard_order, \n";
	$sql .= "cast(dashboard_enabled as text), \n";
	$sql .= "dashboard_description \n";
	$sql .= "from v_dashboard as d \n";
	$sql .= "where dashboard_enabled = 'true' \n";
	$sql .= "and dashboard_uuid in (\n";
	$sql .= "	select dashboard_uuid from v_dashboard_groups where group_uuid in (\n";
	$sql .= "		".$group_uuids_in." \n";
	$sql .= "	)\n";
	$sql .= ")\n";
	$sql .= "order by dashboard_order asc \n";
	$database = new database;
	$dashboard = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//load the header
	$document['title'] = $text['title-dashboard'];
	require_once "resources/header.php";

//include chart.js
	echo "<script src='/resources/chartjs/chart.min.js'></script>";

//chart variables
	?>
	<script>
		var chart_font_family = 'Arial';
		var chart_font_size = '30';
		var chart_font_color = '#444';
		var chart_cutout = '75%';

		const chart_counter = {
			id: 'chart_counter',
			beforeDraw(chart, args, options){
				const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
				ctx.font = chart_font_size + 'px ' + chart_font_family;
				ctx.textBaseline = 'middle';
				ctx.textAlign = 'center';
				ctx.fillStyle = chart_font_color;
				ctx.fillText(options.chart_text, width / 2, top + (height / 2));
				ctx.save();
			}
		};

		const chart_counter_2 = {
			id: 'chart_counter_2',
			beforeDraw(chart, args, options){
				const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
				ctx.font = (chart_font_size - 7) + 'px ' + chart_font_family;
				ctx.textBaseline = 'middle';
				ctx.textAlign = 'center';
				ctx.fillStyle = chart_font_color;
				ctx.fillText(options.chart_text + '%', width / 2, top + (height / 2) + 35);
				ctx.save();
			}
		};
	</script>
	<?php

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if ($_SESSION['theme']['menu_style']['text'] != 'side') {
		echo "		".$text['label-welcome']." <a href='".PROJECT_PATH."/core/users/user_edit.php?id=user'>".$_SESSION["username"]."</a>&nbsp; &nbsp;";
	}
	if (permission_exists('dashboard_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'dashboard.php']);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both; text-align: left;'>".$text['description-dashboard']."</div>\n";
	echo "</div>\n";

//display login message
	if (if_group("superadmin") && isset($_SESSION['login']['message']['text']) && $_SESSION['login']['message']['text'] != '') {
		echo "<div class='login_message' width='100%'><b>".$text['login-message_attention']."</b>&nbsp;&nbsp;".$_SESSION['login']['message']['text']."&nbsp;&nbsp;(<a href='?msg=dismiss'>".$text['login-message_dismiss']."</a>)</div>";
	}

?>

<style>

* {
  box-sizing: border-box;
  padding: 0;
  margin: 0;
}

.widget {
  /*background-color: #eee*/
}

.widgets {
  max-width: 100%;
  margin: 0 auto;
  display: grid;
  grid-gap: 1rem;
  grid-column: auto;
}

/* Screen smaller than 550px? 1 columns */
@media (max-width: 575px) {
  .widgets { grid-template-columns: repeat(1, 1fr); }
  .col-num { grid-column: span 1; }
}

/* Screen larger than 550px? 2 columns */
@media (min-width: 575px) {
  .widgets { grid-template-columns: repeat(2, 1fr); }
  .col-num { grid-column: span 2; }
}

/* Screen larger than 1300px? 3 columns */
@media (min-width: 1300px) {
  .widgets { grid-template-columns: repeat(3, 1fr); }
  .col-num { grid-column: span 2; }
}

/* Screen larger than 1500px? 4 columns */
@media (min-width: 1500px) {
  .widgets { grid-template-columns: repeat(4, 1fr); }
  .col-num { grid-column: span 2; }
}

/* Screen larger than 2000px? 5 columns */
@media (min-width: 2000px) {
  .widgets { grid-template-columns: repeat(5, 1fr); }
  .col-num { grid-column: span 2; }
}

</style>

<?php

//include the dashboards
	echo "<div class='widgets' style='padding: 0 5px;'>\n";
	$x = 0;
	foreach($dashboard as $row) {
		//if ($x > 3) { $class = 'col-num'; }
		//echo "<div class='widget $class'>";
		echo "<div class='widget'>";
			include($row['dashboard_path']);
		echo "</div>\n";
		$x++;
	}
	echo "</div>\n";

//show the footer
	require_once "resources/footer.php";

?>
