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
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//if config.conf file does not exist then redirect to the install page
	if (file_exists("/usr/local/etc/fusionpbx/config.conf")){
		//BSD
	} elseif (file_exists("/etc/fusionpbx/config.conf")){
		//Linux
	} else {
		header("Location: /core/install/install.php");
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
	if (is_array($_SESSION['user']['groups'])) {
		foreach($_SESSION['user']['groups'] as $group) {
			$group_uuids[] =  $group['group_uuid'];
		}
	}
	if (is_array($group_uuids)) {
		$group_uuids_in = "'".implode("','", $group_uuids)."'";
	}

//get the list
	$sql = "select \n";
	$sql .= "dashboard_uuid, \n";
	$sql .= "dashboard_name, \n";
	$sql .= "dashboard_path, \n";
	$sql .= "dashboard_column_span, \n";
	$sql .= "dashboard_details_state, \n";
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

//get http post variables and set them to php variables
	if (count($_POST) > 0 && permission_exists('dashboard_edit')) {
		//set the variables from the http values
		if (isset($_POST["widget_order"])) {
			$widgets = explode(",", $_POST["widget_order"]);
			$dashboard_order = '0';
			$x = 0;
			foreach($widgets as $widget) {
				foreach($dashboard as $row) {
					$dashboard_name = strtolower($row['dashboard_name']);
					$dashboard_name = str_replace(" ", "_", $dashboard_name);
					if ($widget == $dashboard_name) {
						$dashboard_order = $dashboard_order + 10;
						$array['dashboard'][$x]['dashboard_name'] = $row['dashboard_name'];
						$array['dashboard'][$x]['dashboard_uuid'] = $row['dashboard_uuid'];
						$array['dashboard'][$x]['dashboard_order'] = $dashboard_order;
						$x++;
					}
				}
			}

			//save the data
			$database = new database;
			$database->app_name = 'dashboard';
			$database->app_uuid = '55533bef-4f04-434a-92af-999c1e9927f7';
			$database->save($array);

			//redirect the browser
			message::add($text['message-update']);
			header("Location: /core/dashboard/index.php");
			return;
		}
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//load the header
	$document['title'] = $text['title-dashboard'];
	require_once "resources/header.php";

//include sortablejs
	echo "<script src='/resources/sortablejs/sortable.min.js'></script>";

//include chart.js
	echo "<script src='/resources/chartjs/chart.min.js'></script>";

//chart variables
	?>
	<script>
		var chart_text_font = 'arial';
		var chart_text_size = '<?php echo $_SESSION['dashboard']['chart_text_size']['text']; ?>';
		var chart_text_color = '<?php echo $_SESSION['dashboard']['chart_text_color']['text']; ?>';
		var chart_cutout = '75%';

		const chart_counter = {
			id: 'chart_counter',
			beforeDraw(chart, args, options){
				const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
				ctx.font = chart_text_size + 'px ' + chart_text_font;
				ctx.textBaseline = 'middle';
				ctx.textAlign = 'center';
				ctx.fillStyle = chart_text_color;
				ctx.fillText(options.chart_text, width / 2, top + (height / 2));
				ctx.save();
			}
		};

		const chart_counter_2 = {
			id: 'chart_counter_2',
			beforeDraw(chart, args, options){
				const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
				ctx.font = (chart_text_size - 7) + 'px ' + chart_text_font;
				ctx.textBaseline = 'middle';
				ctx.textAlign = 'center';
				ctx.fillStyle = chart_text_color;
				ctx.fillText(options.chart_text + '%', width / 2, top + (height / 2) + 35);
				ctx.save();
			}
		};
	</script>
	<?php

//show the content
	echo "<form id='dashboard' method='POST' onsubmit='setFormSubmitting()'>\n";
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if ($_SESSION['theme']['menu_style']['text'] != 'side') {
		echo "		".$text['label-welcome']." <a href='".PROJECT_PATH."/core/users/user_edit.php?id=user'>".$_SESSION["username"]."</a>&nbsp; &nbsp;";
	}
	if (permission_exists('dashboard_edit')) {
		if ($_GET['edit'] == 'true') {
			echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','name'=>'btn_back','link'=>'index.php']);
			echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','name'=>'btn_save']);
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'id'=>'btn_edit','name'=>'btn_edit','link'=>'index.php?edit=true']);
		}
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'dashboard.php']);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both; text-align: left;'>".$text['description-dashboard']."</div>\n";
	echo "</div>\n";
	echo "<input type='hidden' id='widget_order' name='widget_order' value='' />\n";
	echo "</form>\n";

//display login message
	if (if_group("superadmin") && isset($_SESSION['login']['message']['text']) && $_SESSION['login']['message']['text'] != '') {
		echo "<div class='login_message' width='100%'><b>".$text['login-message_attention']."</b>&nbsp;&nbsp;".$_SESSION['login']['message']['text']."&nbsp;&nbsp;(<a href='?msg=dismiss'>".$text['login-message_dismiss']."</a>)</div>\n";
	}

?>

<style>

* {
  box-sizing: border-box;
  padding: 0;
  margin: 0;
}

.widget {
  /*background-color: #eee;*/
}

.widgets {
  max-width: 100%;
  margin: 0 auto;
  display: grid;
  grid-gap: 1rem;
  grid-column: auto;
}

/* Screen smaller than 575px? 1 columns */
@media (max-width: 575px) {
  .widgets { grid-template-columns: repeat(1, minmax(100px, 1fr)); }
  .col-num { grid-column: span 1; }
	<?php
		foreach($dashboard as $row) {
			$dashboard_name = strtolower($row['dashboard_name']);
			$dashboard_name = str_replace(" ", "_", $dashboard_name);
			if (is_numeric($dashboard_column_span)) {
				echo "#".$dashboard_name." {\n";
				echo "	grid-column: span 1;\n";
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
		foreach($dashboard as $row) {
			$dashboard_name = strtolower($row['dashboard_name']);
			$dashboard_name = str_replace(" ", "_", $dashboard_name);
			$dashboard_column_span = 1;
			if (is_numeric($dashboard_column_span)) {
				if ($row['dashboard_column_span'] > 2) {
					$dashboard_column_span = 2;
				}
				echo "#".$dashboard_name." {\n";
				echo "	grid-column: span ".$dashboard_column_span.";\n";
				echo "}\n";
			}
			if ($row['dashboard_details_state'] == "contracted") {
				echo "#".$dashboard_name." .hud_box .hud_details {\n";
				echo "	display: none;\n";
				echo "}\n";
			}
			if ($row['dashboard_details_state'] == "hidden") {
				echo "#".$dashboard_name." .hud_box .hud_expander, \n";
				echo "#".$dashboard_name." .hud_box .hud_details {\n";
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
		foreach($dashboard as $row) {
			$dashboard_name = strtolower($row['dashboard_name']);
			$dashboard_name = str_replace(" ", "_", $dashboard_name);
			$dashboard_column_span = $row['dashboard_column_span'];
			if (is_numeric($dashboard_column_span)) {
				echo "#".$dashboard_name." {\n";
				echo "	grid-column: span ".$dashboard_column_span.";\n";
				echo "}\n";
			}
		}
	?>
}

/* Screen larger than 1500px? 4 columns */
@media (min-width: 1500px) {
  .widgets { grid-template-columns: repeat(4, minmax(100px, 1fr)); }
  .col-num { grid-column: span 2; }
}

/* Screen larger than 2000px? 5 columns */
@media (min-width: 2000px) {
  .widgets { grid-template-columns: repeat(5, minmax(100px, 1fr)); }
  .col-num { grid-column: span 2; }
}

</style>

<?php

//include the dashboards
	echo "<div class='widgets' id='widgets' style='padding: 0 5px;'>\n";
	$x = 0;
	foreach($dashboard as $row) {
		$dashboard_name = strtolower($row['dashboard_name']);
		$dashboard_name = str_replace(" ", "_", $dashboard_name);
		$draggable = '';
		if ($_GET['edit'] == 'true') {
			$draggable = "draggable='true'";
		}
		echo "<div class='widget' id='".$dashboard_name."' ".$draggable.">\n";
			include($row['dashboard_path']);
		echo "</div>\n";
		$x++;
	}
	echo "</div>\n";

//begin edit
	if ($_GET['edit'] == 'true') {
		?>

		<style>
		/*To prevent user selecting inside the drag source*/
		[draggable] {
			-moz-user-select: none;
			-khtml-user-select: none;
			-webkit-user-select: none;
			user-select: none;
		}

		.widget {
			cursor: move;
		}

		.hud_box {
			transition: 0.2s;
		}

		.hud_box:hover {
			box-shadow: 0 5px 10px rgba(0,0,0,0.2);
			transform: scale(1.03, 1.03);
			transition: 0.2s;
		}

		.hud_box .hud_box:hover {
			box-shadow: none;
			transform: none;
		}

		.ghost {
			border: 3px dashed rgba(0,0,0,1);
			opacity: 0.2;
		}
		</style>

		<script>
		//make widgets draggable
		var widgets = document.getElementById('widgets');
		var sortable = Sortable.create(widgets, {
			animation: 150,
			draggable: ".widget",
			preventOnFilter: true,
			ghostClass: 'ghost',
			onChange: function (evt) {
				//check if chart exists
				let chart_status = Chart.getChart(evt.item.id + "_chart");
				if(chart_status !== undefined && chart_status !== null) {
					let context = eval(evt.item.id + "_chart_context");
					let chart = Chart.getChart(context);
					let config = eval(evt.item.id + "_chart_config");

					//re render the chart
					chart.destroy();
					chart.options.animation = { duration: 0 };
					chart = new Chart(context, config);
				}
			},
			onSort: function (evt) {
				let widget_ids = document.querySelectorAll("#widgets > div[id]");
				let widget_ids_list = [];
				for (let i = 0; i < widget_ids.length; i++) {
					widget_ids_list.push(widget_ids[i].id);
				}
				document.getElementById('widget_order').value = widget_ids_list;
			},
		});

		/*
		//warn the user before leaving the page
		var formSubmitting = false;
		var setFormSubmitting = function() { formSubmitting = true; };

		window.onload = function() {
			window.addEventListener("beforeunload", function (e) {
				var confirmationMessage = 'You have unsaved changes which will not be saved.';

				if (formSubmitting) {
					return undefined;
				}

				(e || window.event).returnValue = confirmationMessage;
				return confirmationMessage;
			});
		};
		*/
		</script>
		<?php
	} //end edit

//show the footer
	require_once "resources/footer.php";

?>
