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
	Portions created by the Initial Developer are Copyright (C) 2021-2025
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dashboard_add') || permission_exists('dashboard_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//initialize the database
	$database = new database;

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the defaults
	$dashboard_name = '';
	$dashboard_path = 'dashboard/icon';
	$dashboard_icon = '';
	$dashboard_icon_color = '';
	$dashboard_url = '';
	$dashboard_target = 'self';
	$dashboard_width = '';
	$dashboard_height = '';
	$dashboard_content = '';
	$dashboard_content_text_align = '';
	$dashboard_content_details = '';
	$dashboard_groups = [];
	$dashboard_label_enabled = 'true';
	$dashboard_label_text_color = '';
	$dashboard_label_background_color = '';
	$dashboard_number_text_color = '';
	$dashboard_number_background_color = '';
	$dashboard_column_span = '';
	$dashboard_row_span = '';
	$dashboard_details_state = '';
	$dashboard_parent_uuid = '';
	$dashboard_order = '';
	$dashboard_enabled = 'true';
	$dashboard_description = '';
	$dashboard_uuid = '';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$dashboard_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$dashboard_name = $_POST["dashboard_name"] ?? '';
		$dashboard_path = $_POST["dashboard_path"] ?? '';
		$dashboard_icon = $_POST["dashboard_icon"] ?? '';
		$dashboard_icon_color = $_POST["dashboard_icon_color"] ?? '';
		$dashboard_url = $_POST["dashboard_url"] ?? '';
		$dashboard_target = $_POST["dashboard_target"] ?? 'self';
		$dashboard_width = $_POST["dashboard_width"] ?? '';
		$dashboard_height = $_POST["dashboard_height"] ?? '';
		$dashboard_content = $_POST["dashboard_content"] ?? '';
		$dashboard_content_text_align = $_POST["dashboard_content_text_align"] ?? '';
		$dashboard_content_details = $_POST["dashboard_content_details"] ?? '';
		$dashboard_groups = $_POST["dashboard_groups"] ?? '';
		$dashboard_chart_type = $_POST["dashboard_chart_type"] ?? '';
		$dashboard_label_enabled = $_POST["dashboard_label_enabled"] ?? 'false';
		$dashboard_label_text_color = $_POST["dashboard_label_text_color"] ?? '';
		$dashboard_label_text_color_hover = $_POST["dashboard_label_text_color_hover"] ?? '';
		$dashboard_label_background_color = $_POST["dashboard_label_background_color"] ?? '';
		$dashboard_label_background_color_hover = $_POST["dashboard_label_background_color_hover"] ?? '';
		$dashboard_number_text_color = $_POST["dashboard_number_text_color"] ?? '';
		$dashboard_number_text_color_hover = $_POST["dashboard_number_text_color_hover"] ?? '';
		$dashboard_number_background_color = $_POST["dashboard_number_background_color"] ?? '';
		$dashboard_background_color = $_POST["dashboard_background_color"] ?? '';
		$dashboard_background_color_hover = $_POST["dashboard_background_color_hover"] ?? '';
		$dashboard_detail_background_color = $_POST["dashboard_detail_background_color"] ?? '';
		$dashboard_background_gradient_style = $_POST["dashboard_background_gradient_style"] ?? 'mirror';
		$dashboard_background_gradient_angle = $_POST["dashboard_background_gradient_angle"] ?? '90';
		$dashboard_column_span = $_POST["dashboard_column_span"] ?? '';
		$dashboard_row_span = $_POST["dashboard_row_span"] ?? '';
		$dashboard_details_state = $_POST["dashboard_details_state"] ?? '';
		$dashboard_parent_uuid = $_POST["dashboard_parent_uuid"] ?? '';
		$dashboard_order = $_POST["dashboard_order"] ?? '';
		$dashboard_enabled = $_POST["dashboard_enabled"] ?? 'false';
		$dashboard_description = $_POST["dashboard_description"] ?? '';

		//define the regex patterns
		$uuid_pattern = '/[^-A-Fa-f0-9]/';
		$number_pattern = '/[^-A-Za-z0-9()*#]/';
		$text_pattern = '/[^a-zA-Z0-9 _\-\/.\?:\=#\n]/';

		//sanitize the data
		$dashboard_name = trim($dashboard_name);
		$dashboard_path = preg_replace($text_pattern, '', strtolower($dashboard_path));
		$dashboard_icon = preg_replace($text_pattern, '', $dashboard_icon);
		$dashboard_icon_color = preg_replace($text_pattern, '', $dashboard_icon_color);
		$dashboard_url = trim(preg_replace($text_pattern, '', $dashboard_url));
		$dashboard_target = trim(preg_replace($text_pattern, '', $dashboard_target));
		$dashboard_width = trim(preg_replace($text_pattern, '', $dashboard_width));
		$dashboard_height = trim(preg_replace($text_pattern, '', $dashboard_height));
		$dashboard_content = trim($dashboard_content);
		$dashboard_content_text_align = trim(preg_replace($text_pattern, '', $dashboard_content_text_align));
		$dashboard_content_details = trim(preg_replace($text_pattern, '', $dashboard_content_details));
		$dashboard_chart_type = preg_replace($text_pattern, '', $dashboard_chart_type);
		$dashboard_label_enabled = preg_replace($text_pattern, '', $dashboard_label_enabled);
		$dashboard_label_text_color = preg_replace($text_pattern, '', $dashboard_label_text_color);
		$dashboard_label_text_color_hover = preg_replace($text_pattern, '', $dashboard_label_text_color_hover);
		$dashboard_label_background_color = preg_replace($text_pattern, '', $dashboard_label_background_color);
		$dashboard_label_background_color_hover = preg_replace($text_pattern, '', $dashboard_label_background_color_hover);
		$dashboard_number_text_color = preg_replace($text_pattern, '', $dashboard_number_text_color);
		$dashboard_number_text_color_hover = preg_replace($text_pattern, '', $dashboard_number_text_color_hover);
		$dashboard_number_background_color = preg_replace($text_pattern, '', $dashboard_number_background_color);
		$dashboard_background_color = preg_replace($text_pattern, '', $dashboard_background_color);
		$dashboard_background_color_hover = preg_replace($text_pattern, '', $dashboard_background_color_hover);
		$dashboard_detail_background_color = preg_replace($text_pattern, '', $dashboard_detail_background_color);
		$dashboard_background_gradient_style = preg_replace($text_pattern, '', $dashboard_background_gradient_style);
		$dashboard_background_gradient_angle = preg_replace($text_pattern, '', $dashboard_background_gradient_angle);
		$dashboard_column_span = preg_replace($number_pattern, '', $dashboard_column_span);
		$dashboard_row_span = preg_replace($number_pattern, '', $dashboard_row_span);
		$dashboard_details_state = preg_replace($text_pattern, '', $dashboard_details_state);
		$dashboard_parent_uuid = preg_replace($uuid_pattern, '', $dashboard_parent_uuid);
		$dashboard_order = preg_replace($number_pattern, '', $dashboard_order);
		$dashboard_enabled = preg_replace($text_pattern, '', $dashboard_enabled);
		$dashboard_description = preg_replace($text_pattern, '', $dashboard_description);
	}

//delete the group from the sub table
	if (isset($_REQUEST["a"]) && $_REQUEST["a"] == "delete" && permission_exists("dashboard_group_delete") && is_uuid($_GET["dashboard_group_uuid"]) && is_uuid($_GET["dashboard_uuid"])) {
		//get the uuid
			$dashboard_group_uuid = $_GET["dashboard_group_uuid"];
			$dashboard_uuid = $_GET["dashboard_uuid"];
		//delete the group from the users
			$array['dashboard_groups'][0]['dashboard_group_uuid'] = $dashboard_group_uuid;
			$database->app_name = 'dashboard';
			$database->app_uuid = '55533bef-4f04-434a-92af-999c1e9927f7';
			$database->delete($array);
			unset($array);
		//redirect the user
			message::add($text['message-delete']);
			header("Location: dashboard_edit.php?id=".urlencode($dashboard_uuid));
			return;
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && empty($_POST["persistformvar"])) {
		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dashboard.php');
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action'])) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('dashboard_add')) {
							$database->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('dashboard_delete')) {
							$database->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('dashboard_update')) {
							$database->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: dashboard_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (empty($dashboard_name)) { $msg .= $text['message-required']." ".$text['label-dashboard_name']."<br>\n"; }
			//if (empty($dashboard_path)) { $msg .= $text['message-required']." ".$text['label-dashboard_path']."<br>\n"; }
			//if (empty($dashboard_groups)) { $msg .= $text['message-required']." ".$text['label-dashboard_groups']."<br>\n"; }
			//if (empty($dashboard_order)) { $msg .= $text['message-required']." ".$text['label-dashboard_order']."<br>\n"; }
			//if (empty($dashboard_enabled)) { $msg .= $text['message-required']." ".$text['label-dashboard_enabled']."<br>\n"; }
			//if (empty($dashboard_description)) { $msg .= $text['message-required']." ".$text['label-dashboard_description']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />\n";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add the dashboard_uuid
			if (!is_uuid($_POST["dashboard_uuid"])) {
				$dashboard_uuid = uuid();
			}

		//remove empty values and convert to json
			if (!empty($dashboard_background_color)) {
				if (is_array($dashboard_background_color)) {
					$dashboard_background_color = array_filter($dashboard_background_color);
					if (count($dashboard_background_color) > 0) {
						$dashboard_background_color = json_encode($dashboard_background_color);
					}
					else {
						$dashboard_background_color = '';
					}
				}
			}
			if (!empty($dashboard_background_color_hover)) {
				if (is_array($dashboard_background_color_hover)) {
					$dashboard_background_color_hover = array_filter($dashboard_background_color_hover);
					if (count($dashboard_background_color_hover) > 0) {
						$dashboard_background_color_hover = json_encode($dashboard_background_color_hover);
					}
					else {
						$dashboard_background_color_hover = '';
					}
				}
			}
			if (!empty($dashboard_detail_background_color)) {
				if (is_array($dashboard_detail_background_color)) {
					$dashboard_detail_background_color = array_filter($dashboard_detail_background_color);
					if (count($dashboard_detail_background_color) > 0) {
						$dashboard_detail_background_color = json_encode($dashboard_detail_background_color);
					}
					else {
						$dashboard_detail_background_color = '';
					}
				}
			}

		//prepare the array
			$array['dashboard'][0]['dashboard_uuid'] = $dashboard_uuid;
			$array['dashboard'][0]['dashboard_name'] = $dashboard_name;
			$array['dashboard'][0]['dashboard_path'] = $dashboard_path;
			$array['dashboard'][0]['dashboard_icon'] = $dashboard_icon;
			$array['dashboard'][0]['dashboard_icon_color'] = $dashboard_icon_color;
			$array['dashboard'][0]['dashboard_url'] = $dashboard_url;
			$array['dashboard'][0]['dashboard_width'] = $dashboard_width;
			$array['dashboard'][0]['dashboard_height'] = $dashboard_height;
			$array['dashboard'][0]['dashboard_target'] = $dashboard_target;
			$array['dashboard'][0]['dashboard_content'] = $dashboard_content;
			$array['dashboard'][0]['dashboard_content_text_align'] = $dashboard_content_text_align;
			$array['dashboard'][0]['dashboard_content_details'] = $dashboard_content_details;
			$array['dashboard'][0]['dashboard_chart_type'] = $dashboard_chart_type;
			$array['dashboard'][0]['dashboard_label_enabled'] = $dashboard_label_enabled;
			$array['dashboard'][0]['dashboard_label_text_color'] = $dashboard_label_text_color;
			$array['dashboard'][0]['dashboard_label_text_color_hover'] = $dashboard_label_text_color_hover;
			$array['dashboard'][0]['dashboard_label_background_color'] = $dashboard_label_background_color;
			$array['dashboard'][0]['dashboard_label_background_color_hover'] = $dashboard_label_background_color_hover;
			$array['dashboard'][0]['dashboard_number_text_color'] = $dashboard_number_text_color;
			$array['dashboard'][0]['dashboard_number_text_color_hover'] = $dashboard_number_text_color_hover;
			$array['dashboard'][0]['dashboard_number_background_color'] = $dashboard_number_background_color;
			$array['dashboard'][0]['dashboard_background_color'] = $dashboard_background_color;
			$array['dashboard'][0]['dashboard_background_color_hover'] = $dashboard_background_color_hover;
			$array['dashboard'][0]['dashboard_detail_background_color'] = $dashboard_detail_background_color;
			$array['dashboard'][0]['dashboard_background_gradient_style'] = $dashboard_background_gradient_style;
			$array['dashboard'][0]['dashboard_background_gradient_angle'] = $dashboard_background_gradient_angle;
			$array['dashboard'][0]['dashboard_column_span'] = $dashboard_column_span;
			$array['dashboard'][0]['dashboard_row_span'] = $dashboard_row_span;
			$array['dashboard'][0]['dashboard_details_state'] = $dashboard_details_state;
			$array['dashboard'][0]['dashboard_parent_uuid'] = $dashboard_parent_uuid;
			$array['dashboard'][0]['dashboard_order'] = $dashboard_order;
			$array['dashboard'][0]['dashboard_enabled'] = $dashboard_enabled;
			$array['dashboard'][0]['dashboard_description'] = $dashboard_description;
			$y = 0;
			if (is_array($dashboard_groups)) {
				foreach ($dashboard_groups as $row) {
					if (isset($row['group_uuid']) && is_uuid($row['group_uuid'])) {
						$array['dashboard'][0]['dashboard_groups'][$y]['dashboard_group_uuid'] = uuid();
						$array['dashboard'][0]['dashboard_groups'][$y]['group_uuid'] = $row["group_uuid"];
						$y++;
					}
				}
			}

		//save the data
			$database->app_name = 'dashboard';
			$database->app_uuid = '55533bef-4f04-434a-92af-999c1e9927f7';
			$result = $database->save($array);
			//view_array($result);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: dashboard.php');
				header('Location: dashboard_edit.php?id='.urlencode($dashboard_uuid));
				return;
			}
	}

//pre-populate the form
	if (empty($_POST["persistformvar"])) {
		$sql = "select ";
		$sql .= " dashboard_uuid, ";
		$sql .= " dashboard_name, ";
		$sql .= " dashboard_path, ";
		$sql .= " dashboard_icon, ";
		$sql .= " dashboard_icon_color, ";
		$sql .= " dashboard_url, ";
		$sql .= " dashboard_width, ";
		$sql .= " dashboard_height, ";
		$sql .= " dashboard_target, ";
		$sql .= " dashboard_content, ";
		$sql .= " dashboard_content_text_align, ";
		$sql .= " dashboard_content_details, ";
		$sql .= " dashboard_chart_type, ";
		$sql .= " cast(dashboard_label_enabled as text), ";
		$sql .= " dashboard_label_text_color, ";
		$sql .= " dashboard_label_text_color_hover, ";
		$sql .= " dashboard_label_background_color, ";
		$sql .= " dashboard_label_background_color_hover, ";
		$sql .= " dashboard_number_text_color, ";
		$sql .= " dashboard_number_text_color_hover, ";
		$sql .= " dashboard_number_background_color, ";
		$sql .= " dashboard_background_color, ";
		$sql .= " dashboard_background_color_hover, ";
		$sql .= " dashboard_detail_background_color, ";
		$sql .= " dashboard_background_gradient_style, ";
		$sql .= " dashboard_background_gradient_angle, ";
		$sql .= " dashboard_column_span, ";
		$sql .= " dashboard_row_span, ";
		$sql .= " dashboard_details_state, ";
		$sql .= " dashboard_parent_uuid, ";
		$sql .= " dashboard_order, ";
		$sql .= " dashboard_enabled, ";
		$sql .= " dashboard_description ";
		$sql .= "from v_dashboard ";
		$sql .= "where dashboard_uuid = :dashboard_uuid ";
		$parameters['dashboard_uuid'] = $dashboard_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$dashboard_name = $row["dashboard_name"];
			$dashboard_path = $row["dashboard_path"];
			$dashboard_icon = $row["dashboard_icon"];
			$dashboard_icon_color = $row["dashboard_icon_color"];
			$dashboard_url = $row["dashboard_url"];
			$dashboard_width = $row["dashboard_width"];
			$dashboard_height = $row["dashboard_height"];
			$dashboard_target = $row["dashboard_target"];
			$dashboard_content = $row["dashboard_content"];
			$dashboard_content_text_align = $row["dashboard_content_text_align"];
			$dashboard_content_details = $row["dashboard_content_details"];
			$dashboard_chart_type = $row["dashboard_chart_type"];
			$dashboard_label_enabled = $row["dashboard_label_enabled"];
			$dashboard_label_text_color = $row["dashboard_label_text_color"];
			$dashboard_label_text_color_hover = $row["dashboard_label_text_color_hover"];
			$dashboard_label_background_color = $row["dashboard_label_background_color"];
			$dashboard_label_background_color_hover = $row["dashboard_label_background_color_hover"];
			$dashboard_number_text_color = $row["dashboard_number_text_color"];
			$dashboard_number_text_color_hover = $row["dashboard_number_text_color_hover"];
			$dashboard_number_background_color = $row["dashboard_number_background_color"];
			$dashboard_background_color = $row["dashboard_background_color"];
			$dashboard_background_color_hover = $row["dashboard_background_color_hover"];
			$dashboard_detail_background_color = $row["dashboard_detail_background_color"];
			$dashboard_background_gradient_style = $row["dashboard_background_gradient_style"];
			$dashboard_background_gradient_angle = $row["dashboard_background_gradient_angle"];
			$dashboard_column_span = $row["dashboard_column_span"];
			$dashboard_row_span = $row["dashboard_row_span"];
			$dashboard_details_state = $row["dashboard_details_state"];
			$dashboard_parent_uuid = $row["dashboard_parent_uuid"];
			$dashboard_order = $row["dashboard_order"];
			$dashboard_enabled = $row["dashboard_enabled"] ?? 'false';
			$dashboard_description = $row["dashboard_description"];
		}
		unset($sql, $parameters, $row);
	}

//find the application and widget
	$dashboard_path_array = explode('/', $dashboard_path);
	$application_name = $dashboard_path_array[0];
	$widget_name = $dashboard_path_array[1];
	$path_array = glob(dirname(__DIR__, 2).'/*/'.$application_name.'/resources/dashboard/config.php');
	if (file_exists($path_array[0])) {
		include($path_array[0]);
	}

//find the chart type options
	$dashboard_chart_type_options = [];
	foreach ($array['dashboard'] as $index => $row) {
		if ($row['dashboard_path'] === "$application_name/$widget_name") {
			$dashboard_chart_type_options = $row['dashboard_chart_type_options'];
			break;
		}
	}

//get the child data
	if (!empty($dashboard_uuid) && is_uuid($dashboard_uuid)) {
		$sql = "select ";
		$sql .= " dashboard_group_uuid, ";
		$sql .= " group_uuid ";
		$sql .= "from v_dashboard_groups ";
		$sql .= "where dashboard_uuid = :dashboard_uuid ";
		$parameters['dashboard_uuid'] = $dashboard_uuid;
		$dashboard_groups = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $dashboard_group_uuid
	if (empty($dashboard_group_uuid) || !empty($dashboard_group_uuid) && !is_uuid($dashboard_group_uuid)) {
		$dashboard_group_uuid = uuid();
	}

//convert the json to an array
	if (!empty($dashboard_background_color) && is_json($dashboard_background_color)) {
		$dashboard_background_color = json_decode($dashboard_background_color, true);
	}
	if (!empty($dashboard_background_color_hover) && is_json($dashboard_background_color_hover)) {
		$dashboard_background_color_hover = json_decode($dashboard_background_color_hover, true);
	}
	if (!empty($dashboard_detail_background_color) && is_json($dashboard_detail_background_color)) {
		$dashboard_detail_background_color = json_decode($dashboard_detail_background_color, true);
	}

//add an empty row
	$x = is_array($dashboard_groups) ? count($dashboard_groups) : 0;
	$dashboard_groups[$x]['dashboard_uuid'] = $dashboard_uuid;
	$dashboard_groups[$x]['dashboard_group_uuid'] = uuid();
	$dashboard_groups[$x]['group_uuid'] = '';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dashboard'];
	require_once "resources/header.php";

//get the dashboard groups
	$sql = "SELECT * FROM v_dashboard_groups as x, v_groups as g ";
	$sql .= "WHERE x.dashboard_uuid = :dashboard_uuid ";
	$sql .= "AND x.group_uuid = g.group_uuid ";
	$parameters['dashboard_uuid'] = $dashboard_uuid ?? '';
	$dashboard_groups = $database->select($sql, $parameters, 'all');
	unset ($sql, $parameters);

//get the groups
	$sql = "SELECT group_uuid, domain_uuid, group_name FROM v_groups ";
	$sql .= "WHERE (domain_uuid = :domain_uuid or domain_uuid is null)";
	$sql .= "ORDER BY domain_uuid desc, group_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$groups = $database->execute($sql, $parameters, 'all');
	unset ($sql, $parameters);

//get the dashboards
	$sql = "SELECT dashboard_uuid, dashboard_name FROM v_dashboard ";
	$sql .= "WHERE dashboard_parent_uuid is null ";
	$sql .= "ORDER by dashboard_order, dashboard_name asc ";
	$parameters = null;
	$dashboard_parents = $database->execute($sql, $parameters, 'all');
	unset ($sql, $parameters);

//set the assigned_groups array
	if (is_array($dashboard_groups) && sizeof($dashboard_groups) != 0) {
		$assigned_groups = array();
		foreach ($dashboard_groups as $field) {
			if (!empty($field['group_name'])) {
				if (is_uuid($field['group_uuid'])) {
					$assigned_groups[] = $field['group_uuid'];
				}
			}
		}
	}

//build the $dashboard_tools array
	$i = 0;
	foreach(glob($_SERVER["DOCUMENT_ROOT"].'/*/*/resources/dashboard/*.php') as $value) {

		//skip adding config.php to the array
		if (basename($value) === 'config.php') {
			continue;
		}

		//ensure the slashes are consistent
		$value = str_replace('\\', '/', $value);

		//prepare the key
		$key_replace[] = $_SERVER["DOCUMENT_ROOT"].'/core/';
		$key_replace[] = $_SERVER["DOCUMENT_ROOT"].'/app/';
		$key_replace[] = 'resources/dashboard/';
		$key_replace[] = '.php';
		$key = str_replace($key_replace, '', $value);

		//prepare the value
		$value_replace[] = $_SERVER["DOCUMENT_ROOT"].'/';
		$value = str_replace($value_replace, '', $value);

		//build the array
		$dashboard_tools[$key] = $value;

		$i++;
	}

//decide what settings to show
	$dashboard_settings_config = [
		'shared' => [
			'dashboard_name',
			'dashboard_path',
			'dashboard_groups',
			'dashboard_label_enabled',
			'dashboard_label_text_color',
			'dashboard_label_background_color',
			'dashboard_background_color',
			'dashboard_detail_background_color',
			'dashboard_background_gradient_style',
			'dashboard_background_gradient_angle',
			'dashboard_details_state',
			'dashboard_column_span',
			'dashboard_row_span',
			'dashboard_parent_uuid',
			'dashboard_order',
			'dashboard_enabled',
			'dashboard_description',
		],
		'icon' => [
			'dashboard_icon',
			'dashboard_icon_color',
			'dashboard_url',
			'dashboard_target',
			'dashboard_width',
			'dashboard_height',
			'dashboard_content_details',
			'dashboard_label_text_color_hover',
			'dashboard_label_background_color_hover',
			'dashboard_background_color_hover',
		],
		'content' => [
			'dashboard_content',
			'dashboard_content_text_align',
			'dashboard_content_details',
		],
		'parent' => [//doesn't use shared settings
			'dashboard_name',
			'dashboard_path',
			'dashboard_groups',
			'dashboard_background_color',
			'dashboard_background_gradient_style',
			'dashboard_background_gradient_angle',
			'dashboard_column_span',
			'dashboard_row_span',
			'dashboard_order',
			'dashboard_enabled',
			'dashboard_description',
		],
		'chart' => [
			'dashboard_chart_type',
			'dashboard_number_text_color',
			'icon' => [
				'dashboard_icon',
				'dashboard_icon_color',
				'dashboard_label_text_color_hover',
				'dashboard_label_background_color_hover',
				'dashboard_number_text_color_hover',
				'dashboard_number_background_color',
				'dashboard_background_color_hover',
			],
		],
	];

	//build the dashboard settings array
	$dashboard_settings = $dashboard_settings_config['shared'];
	$items_to_remove = [];

	if ($action == "add" || $dashboard_path == "dashboard/icon") {
		$dashboard_settings = array_merge($dashboard_settings, $dashboard_settings_config['icon']);

		if (empty($dashboard_url)) {
			$items_to_remove[] = 'dashboard_target';
		}
		if (empty($dashboard_url) || $dashboard_target != "new") {
			$items_to_remove[] = 'dashboard_width';
			$items_to_remove[] = 'dashboard_height';
		}
		if ($dashboard_label_enabled == "false") {
			$items_to_remove[] = 'dashboard_label_text_color';
			$items_to_remove[] = 'dashboard_label_text_color_hover';
			$items_to_remove[] = 'dashboard_label_background_color';
			$items_to_remove[] = 'dashboard_label_background_color_hover';
		}
	}
	else if ($dashboard_path == "dashboard/content") {
		$dashboard_settings = array_merge($dashboard_settings, $dashboard_settings_config['content']);
	}
	else if ($dashboard_path == "dashboard/parent") {
		$dashboard_settings = $dashboard_settings_config['parent'];
	}

	if (!empty($dashboard_chart_type)) {
		$dashboard_settings = array_merge($dashboard_settings, array_filter($dashboard_settings_config['chart'], 'is_scalar'));

		if ($dashboard_chart_type == "icon") {
			$dashboard_settings = array_merge($dashboard_settings, $dashboard_settings_config['chart']['icon']);
		}
		else if ($dashboard_chart_type == "line") {
			$items_to_remove[] = 'dashboard_number_text_color';
		}
	}

	$dashboard_settings = array_diff($dashboard_settings, $items_to_remove);

?>
<script>

//adjust form by type entered
document.addEventListener('DOMContentLoaded', function() {
	function adjust_form_path() {
		let selected_path = document.getElementById('dashboard_path').value;
		let settings_config = <?php echo json_encode($dashboard_settings_config); ?>;
		let settings = settings_config['shared'];

		//hide all settings initially
		const all_settings = Array.from(document.querySelectorAll("tr[id^='tr_dashboard_']"));
		all_settings.forEach(tr => tr.style.display = 'none');

		switch (selected_path) {
			case 'dashboard/icon':
				settings.push(...settings_config['icon']);
				break;
			case 'dashboard/content':
				settings.push(...settings_config['content']);
				break;
			case 'dashboard/parent':
				settings = settings_config['parent'];
				break;
		}

		//show settings after updating the settings array
		settings.forEach(setting => document.getElementById(`tr_${setting}`).style.display = '');

		//get the widget config
		fetch(`dashboard_config_json.php?dashboard_path=${encodeURIComponent(selected_path)}`)
			.then(response => response.json())
			.then(data => {
				if (data.error) {
					console.error('Error fetching config:', data.error);
				}
				else {
					let chart_type = document.querySelectorAll('.chart_type_button input:checked').value;
					let chart_type_options = data.chart_type_options;

					//update chart settings
					if (chart_type_options.length > 0) {
						const chart_settings = Object.values(settings_config['chart']).filter(value => !Array.isArray(value));
						const chart_type_buttons = Array.from(document.querySelectorAll('.chart_type_button'));

						//hide all chart settings initially
						chart_settings.forEach(setting => document.getElementById(`tr_${setting}`).style.display = 'none');
						chart_type_buttons.forEach(button => button.style.display = 'none');

						if (chart_type === "icon") {
							chart_settings.push(...settings_config['chart']['icon']);
						}
						else if (chart_type === "line" && chart_settings.includes('dashboard_number_color')) {
							chart_settings = chart_settings.indexOf('dashboard_number_color');
						}

						//show chart settings
						chart_settings.forEach(setting => document.getElementById(`tr_${setting}`).style.display = '');
						chart_type_options.forEach(option => {
							const button = document.querySelector(`.chart_type_button input[value='${option}']`).closest('.chart_type_button');
							if (button) {
								button.style.display = '';
							}
						});
					}
				}
			})
			.catch(error => {
				console.error('Error:', error);
			});

		if (selected_path == 'dashboard/icon') {
			adjust_form_url();
		}
	}

	function adjust_form_url() {
		let url_input = document.getElementById('dashboard_url');
		let target_select = document.getElementById('dashboard_target');

		if (url_input.value != '') {
			document.getElementById('tr_dashboard_target').style.display = '';
		}
		else {
			document.getElementById('tr_dashboard_target').style.display = 'none';
		}

		let selected_target = target_select.options[target_select.selectedIndex].value;

		if (selected_target == 'new' && url_input.value != '') {
			document.getElementById('tr_dashboard_width').style.display = '';
			document.getElementById('tr_dashboard_height').style.display = '';
		}
		else {
			document.getElementById('tr_dashboard_width').style.display = 'none';
			document.getElementById('tr_dashboard_height').style.display = 'none';
		}
	}

	function toggle_label_settings() {
		let label_settings = document.querySelectorAll("[id^='tr_dashboard_label_']:not([id='tr_dashboard_label_enabled'])");
		label_settings.forEach(function(setting) {
			setting.style.display = (setting.style.display == 'none' ? '' : 'none');
		});
	}

	document.getElementById('dashboard_path').addEventListener('change', adjust_form_path);
	document.getElementById('dashboard_url').addEventListener('change', adjust_form_url);
	document.getElementById('dashboard_target').addEventListener('change', adjust_form_url);
	document.getElementById('dashboard_label_enabled').addEventListener('change', toggle_label_settings);
});

</script>
<?php

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input class='formfld' type='hidden' name='dashboard_uuid' value='".escape($dashboard_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'dashboard.php']);
	if ($action == 'update') {
		if (permission_exists('dashboard_group_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('dashboard_group_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";
	//echo $text['title_description-dashboard']."\n";
	//echo "<br /><br />\n";

	if (!empty($action) && $action == 'update') {
		if (permission_exists('dashboard_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('dashboard_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr id='tr_dashboard_name' ".(!in_array('dashboard_name', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_name'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dashboard_name' maxlength='255' value='".escape($dashboard_name)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_path' ".(!in_array('dashboard_path', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_path']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' id='dashboard_path' name='dashboard_path'>\n";
	echo "		<option value=''></option>\n";
	foreach($dashboard_tools as $key => $value) {
		echo "		<option value='$key' ".($key == $dashboard_path ? "selected='selected'" : null).">".$key."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_path']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr id='tr_dashboard_icon' ".(!in_array('dashboard_icon', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "		<td class='vncell'>".$text['label-icon']."</td>\n";
	echo "		<td class='vtable' style='vertical-align: bottom;'>\n";
	if (file_exists($_SERVER["PROJECT_ROOT"].'/resources/fontawesome/fa_icons.php')) {
		include $_SERVER["PROJECT_ROOT"].'/resources/fontawesome/fa_icons.php';
	}
	if (!empty($font_awesome_icons) && is_array($font_awesome_icons)) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<select class='formfld' name='dashboard_icon' id='selected_icon' onchange=\"$('#icons').slideUp(200); $('#icon_search').fadeOut(200, function() { $('#grid_icon').fadeIn(); });\">\n";
		echo "				<option value=''></option>\n";
		foreach ($font_awesome_icons as $icon) {
			$selected = $dashboard_icon == implode(' ', $icon['classes']) ? "selected" : null;
			echo "			<option value='".escape(implode(' ', $icon['classes']))."' ".$selected.">".escape($icon['label'])."</option>\n";
		}
		echo "			</select>\n";
		echo "		</td>\n";
		echo "		<td style='padding: 0 0 0 5px;'>\n";
		echo "			<button id='grid_icon' type='button' class='btn btn-default list_control_icon' style='font-size: 15px; padding-top: 1px; padding-left: 3px;' onclick=\"load_icons(); $(this).fadeOut(200, function() { $('#icons').fadeIn(200); $('#icon_search').fadeIn(200).focus(); });\"><span class='fa-solid fa-th'></span></button>\n";
		echo "			<input id='icon_search' type='text' class='formfld' style='display: none;' onkeyup=\"if (this.value.length >= 3) { delay_submit(this.value); } else if (this.value == '') { load_icons(); } else { $('#icons').html(''); }\" placeholder=\"".$text['label-search']."\">\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "<div id='icons' style='clear: both; display: none; margin-top: 8px; padding-top: 10px; color: #000; max-height: 400px; overflow: auto;'></div>\n";

		echo "<script>\n";
		//load icons by search
		echo "function load_icons(search) {\n";
		echo "	xhttp = new XMLHttpRequest();\n";
		echo "	xhttp.open('GET', '".PROJECT_PATH."/resources/fontawesome/fa_icons.php?output=icons' + (search ? '&search=' + search : ''), false);\n";
		echo "	xhttp.send();\n";
		echo "	document.getElementById('icons').innerHTML = xhttp.responseText;\n";
		echo "}\n";
		//delay kepress action for 1/2 second
		echo "var keypress_timer;\n";
		echo "function delay_submit(search) {\n";
		echo "	clearTimeout(keypress_timer);\n";
		echo "	keypress_timer = setTimeout(function(){\n";
		echo "		load_icons(search);\n";
		echo "	}, 500);\n";
		echo "}\n";
		echo "</script>\n";
	}
	else {
		echo "		<input type='text' class='formfld' name='dashboard_icon' value='".escape($dashboard_icon)."'>\n";
	}
	echo			$text['description-dashboard_icon']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "<tr id='tr_dashboard_icon_color' ".(!in_array('dashboard_icon_color', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_icon_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_icon_color' value='".escape($dashboard_icon_color)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_icon_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_url' ".(!in_array('dashboard_url', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-link']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' id='dashboard_url' name='dashboard_url' maxlength='255' value='".escape($dashboard_url)."'\">\n";
	echo "<br />\n";
	echo $text['description-dashboard_url'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_target' ".(!in_array('dashboard_target', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-target']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select id='dashboard_target' name='dashboard_target' class='formfld'\">\n";
	echo "		<option value='self'>".$text['label-current_window']."</option>\n";
	echo "		<option value='new' ".(!empty($dashboard_target) && $dashboard_target == 'new' ? "selected='selected'" : null).">".$text['label-new_window']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_target']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_width' ".(!in_array('dashboard_width', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-width']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dashboard_width' maxlength='255' value='".escape($dashboard_width)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_width'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_height' ".(!in_array('dashboard_height', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-height']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dashboard_height' maxlength='255' value='".escape($dashboard_height)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_height'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_content' ".(!in_array('dashboard_content', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-content']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='height: 100px;' name='dashboard_content'>".$dashboard_content."</textarea>\n";
	echo "<br />\n";
	echo $text['description-dashboard_content']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_content_text_align' ".(!in_array('dashboard_content_text_align', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_content_text_align']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dashboard_content_text_align' class='formfld'>\n";
	echo "		<option value='left' ".(!empty($dashboard_content_text_align) && $dashboard_content_text_align == 'left' ? "selected='selected'" : null).">".$text['label-left']."</option>\n";
	echo "		<option value='right' ".(!empty($dashboard_content_text_align) && $dashboard_content_text_align == 'right' ? "selected='selected'" : null).">".$text['label-right']."</option>\n";
	echo "		<option value='center' ".(!empty($dashboard_content_text_align) && $dashboard_content_text_align == 'center' ? "selected='selected'" : null).">".$text['label-center']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_content_text_align']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_content_details' ".(!in_array('dashboard_content_details', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-details']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='height: 100px;' name='dashboard_content_details'>".$dashboard_content_details."</textarea>\n";
	echo "<br />\n";
	echo $text['description-dashboard_content_details']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_groups' ".(!in_array('dashboard_groups', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_groups']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (is_array($dashboard_groups) && sizeof($dashboard_groups) != 0) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		foreach($dashboard_groups as $field) {
			if (!empty($field['group_name'])) {
				echo "<tr>\n";
				echo "	<td class='vtable' style='white-space: nowrap; padding-right: 30px;' nowrap='nowrap'>\n";
				echo $field['group_name'].((!empty($field['domain_uuid'])) ? "@".$_SESSION['domains'][$field['domain_uuid']]['domain_name'] : null);
				echo "	</td>\n";
				if (permission_exists('dashboard_group_delete') || if_group("superadmin")) {
					echo "	<td class='list_control_icons' style='width: 25px;'>\n";
					echo 		"<a href='dashboard_edit.php?id=".escape($field['dashboard_group_uuid'])."&dashboard_group_uuid=".escape($field['dashboard_group_uuid'])."&dashboard_uuid=".escape($dashboard_uuid)."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
					echo "	</td>\n";
				}
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
	}
	if (!empty($groups) && is_array($groups)) {
		if (!empty($dashboard_groups)) { echo "<br />\n"; }
		echo "<select name='dashboard_groups[0][group_uuid]' class='formfld' style='width: auto; margin-right: 3px;'>\n";
		echo "	<option value=''></option>\n";
		foreach ($groups as $row) {
			if ((!empty($field['group_level']) && $field['group_level'] <= $_SESSION['user']['group_level']) || empty($field['group_level'])) {
				if (empty($assigned_groups) || !in_array($row["group_uuid"], $assigned_groups)) {
					echo "	<option value='".$row['group_uuid']."'>".$row['group_name'].(!empty($row['domain_uuid']) ? "@".$_SESSION['domains'][$row['domain_uuid']]['domain_name'] : null)."</option>\n";
				}
			}
		}
		echo "</select>\n";
		echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add')]);
	}
	echo "<br />\n";
	echo $text['description-dashboard_groups']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_chart_type' ".(!in_array('dashboard_chart_type', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_chart_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<div style='display: flex; gap: 8px;'>\n";
	echo "		<label class='chart_type_button' title='".$text['label-number']."' ".(!in_array('number', $dashboard_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='dashboard_chart_type' value='number' ".($dashboard_chart_type == 'number' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-hashtag'></i>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-doughnut']."' ".(!in_array('doughnut', $dashboard_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='dashboard_chart_type' value='doughnut' ".($dashboard_chart_type == 'doughnut' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-chart-pie'></i>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-icon']."' ".(!in_array('icon', $dashboard_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='dashboard_chart_type' value='icon' ".($dashboard_chart_type == 'icon' ? 'checked' : '').">\n";
	echo "			<div style='position: relative; display: inline-block;'>\n";
	echo "				<i class='fas fa-envelope'></i>\n";
	echo "				<span style=\"background: #4099FF; color: #ffffff; font-size: 9px; font-weight: bold; text-align: center; position: absolute; top: 11px; left: 14px; padding: 0px 4px; border-radius: 10px; white-space: nowrap;\">1</span>\n";
	echo "			</div>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-line']."' ".(!in_array('line', $dashboard_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='dashboard_chart_type' value='line' ".($dashboard_chart_type == 'line' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-chart-line'></i>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-progress_bar']."' ".(!in_array('progress_bar', $dashboard_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='dashboard_chart_type' value='progress_bar' ".($dashboard_chart_type == 'progress_bar' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-bars-progress'></i>\n";
	echo "		</label>\n";
	echo "	</div>\n";
	echo $text['description-dashboard_chart_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_label_enabled' ".(!in_array('dashboard_label_enabled', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_label_enabled'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='dashboard_label_enabled' name='dashboard_label_enabled' value='true' ".(empty($dashboard_label_enabled) || $dashboard_label_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='dashboard_label_enabled' name='dashboard_label_enabled'>\n";
		echo "		<option value='false'>".$text['option-false']."</option>\n";
		echo "		<option value='true' ".(empty($dashboard_label_enabled) || $dashboard_label_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-dashboard_label_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_label_text_color' ".(!in_array('dashboard_label_text_color', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_label_text_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_label_text_color' value='".escape($dashboard_label_text_color)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_label_text_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_label_text_color_hover' ".(!in_array('dashboard_label_text_color_hover', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_label_text_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_label_text_color_hover' value='".escape($dashboard_label_text_color_hover)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_label_text_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_label_background_color' ".(!in_array('dashboard_label_background_color', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_label_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_label_background_color' value='".escape($dashboard_label_background_color)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_label_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_label_background_color_hover' ".(!in_array('dashboard_label_background_color_hover', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_label_background_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_label_background_color_hover' value='".escape($dashboard_label_background_color_hover)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_label_background_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_number_text_color' ".(!in_array('dashboard_number_text_color', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_number_text_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_number_text_color' value='".escape($dashboard_number_text_color)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_number_text_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_number_text_color_hover' ".(!in_array('dashboard_number_text_color_hover', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_number_text_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_number_text_color_hover' value='".escape($dashboard_number_text_color_hover)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_number_text_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_number_background_color' ".(!in_array('dashboard_number_background_color', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_number_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='dashboard_number_background_color' value='".escape($dashboard_number_background_color)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_number_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_background_color' ".(!in_array('dashboard_background_color', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (!empty($dashboard_background_color) && is_array($dashboard_background_color)) {
		foreach ($dashboard_background_color as $c => $background_color) {
			echo "	<input type='text' class='formfld colorpicker' id='dashboard_background_color_".$c."' name='dashboard_background_color[]' value='".escape($background_color)."'>\n";
			if ($c < sizeof($dashboard_background_color) - 1) { echo "<br />\n"; }
		}
		//swap button
		if (!empty($dashboard_background_color) && is_array($dashboard_background_color) && sizeof($dashboard_background_color) > 1) {
			echo "	<input type='hidden' id='dashboard_background_color_temp'>\n";
			echo button::create(['type'=>'button','title'=>$text['button-swap'],'icon'=>'fa-solid fa-arrow-right-arrow-left fa-rotate-90','style'=>"z-index: 0; position: absolute; display: inline-block; margin: -14px 0 0 7px;",'onclick'=>"document.getElementById('dashboard_background_color_temp').value = document.getElementById('dashboard_background_color_0').value; document.getElementById('dashboard_background_color_0').value = document.getElementById('dashboard_background_color_1').value; document.getElementById('dashboard_background_color_1').value = document.getElementById('dashboard_background_color_temp').value; this.blur();"])."<br>\n";
		}
		else {
			echo "<br />\n";
		}
	}
	if (empty($dashboard_background_color) || (is_array($dashboard_background_color) && count($dashboard_background_color) < 2)) {
		echo "	<input type='text' class='formfld colorpicker' style='display: block;' name='dashboard_background_color[]' value='' onclick=\"document.getElementById('background_color_gradient').style.display = 'block';\">\n";
		if (empty($dashboard_background_color)) {
			echo "	<input id='background_color_gradient' style='display: none;' type='text' class='formfld colorpicker' name='dashboard_background_color[]'>\n";
		}
	}
	if (!empty($dashboard_background_color) && !is_array($dashboard_background_color)) {
		echo "	<input type='text' class='formfld colorpicker' name='dashboard_background_color[]' value='".escape([$dashboard_background_color])."'><br />\n";
		echo "	<input type='text' class='formfld colorpicker' name='dashboard_background_color[]' value=''><br />\n";
	}
	echo $text['description-dashboard_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_background_color_hover' ".(!in_array('dashboard_background_color_hover', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_background_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (!empty($dashboard_background_color_hover) && is_array($dashboard_background_color_hover)) {
		foreach ($dashboard_background_color_hover as $c => $background_color) {
			echo "	<input type='text' class='formfld colorpicker' id='dashboard_background_color_hover_".$c."' name='dashboard_background_color_hover[]' value='".escape($background_color)."'>\n";
			if ($c < sizeof($dashboard_background_color_hover) - 1) { echo "<br />\n"; }
		}
		//swap button
		if (!empty($dashboard_background_color_hover) && is_array($dashboard_background_color_hover) && sizeof($dashboard_background_color_hover) > 1) {
			echo "	<input type='hidden' id='dashboard_background_color_hover_temp'>\n";
			echo button::create(['type'=>'button','title'=>$text['button-swap'],'icon'=>'fa-solid fa-arrow-right-arrow-left fa-rotate-90','style'=>"z-index: 0; position: absolute; display: inline-block; margin: -14px 0 0 7px;",'onclick'=>"document.getElementById('dashboard_background_color_hover_temp').value = document.getElementById('dashboard_background_color_hover_0').value; document.getElementById('dashboard_background_color_hover_0').value = document.getElementById('dashboard_background_color_hover_1').value; document.getElementById('dashboard_background_color_hover_1').value = document.getElementById('dashboard_background_color_hover_temp').value; this.blur();"])."<br>\n";
		}
		else {
			echo "<br />\n";
		}
	}
	if (empty($dashboard_background_color_hover) || (is_array($dashboard_background_color_hover) && count($dashboard_background_color_hover) < 2)) {
		echo "	<input type='text' class='formfld colorpicker' style='display: block;' name='dashboard_background_color_hover[]' value='' onclick=\"document.getElementById('background_color_hover_gradient').style.display = 'block';\">\n";
		if (empty($dashboard_background_color_hover)) {
			echo "	<input id='background_color_hover_gradient' style='display: none;' type='text' class='formfld colorpicker' name='dashboard_background_color_hover[]'>\n";
		}
	}
	if (!empty($dashboard_background_color_hover) && !is_array($dashboard_background_color_hover)) {
		echo "	<input type='text' class='formfld colorpicker' name='dashboard_background_color_hover[]' value='".escape([$dashboard_background_color_hover])."'><br />\n";
		echo "	<input type='text' class='formfld colorpicker' name='dashboard_background_color_hover[]' value=''><br />\n";
	}
	echo $text['description-dashboard_background_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_detail_background_color' ".(!in_array('dashboard_detail_background_color', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_detail_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (!empty($dashboard_detail_background_color) && is_array($dashboard_detail_background_color)) {
		foreach ($dashboard_detail_background_color as $c => $detail_background_color) {
			echo "	<input type='text' class='formfld colorpicker' id='dashboard_detail_background_color_".$c."' name='dashboard_detail_background_color[]' value='".escape($detail_background_color)."'>\n";
			if ($c < sizeof($dashboard_detail_background_color) - 1) { echo "<br />\n"; }
		}
		//swap button
		if (!empty($dashboard_detail_background_color) && is_array($dashboard_detail_background_color) && sizeof($dashboard_detail_background_color) > 1) {
			echo "	<input type='hidden' id='dashboard_detail_background_color_temp'>\n";
			echo button::create(['type'=>'button','title'=>$text['button-swap'],'icon'=>'fa-solid fa-arrow-right-arrow-left fa-rotate-90','style'=>"z-index: 0; position: absolute; display: inline-block; margin: -14px 0 0 7px;",'onclick'=>"document.getElementById('dashboard_detail_background_color_temp').value = document.getElementById('dashboard_detail_background_color_0').value; document.getElementById('dashboard_detail_background_color_0').value = document.getElementById('dashboard_detail_background_color_1').value; document.getElementById('dashboard_detail_background_color_1').value = document.getElementById('dashboard_detail_background_color_temp').value; this.blur();"])."<br>\n";
		}
		else {
			echo "<br />\n";
		}
	}
	if (empty($dashboard_detail_background_color) || (is_array($dashboard_detail_background_color) && count($dashboard_detail_background_color) < 2)) {
		echo "	<input type='text' class='formfld colorpicker' style='display: block;' name='dashboard_detail_background_color[]' value='' onclick=\"document.getElementById('detail_background_color_gradient').style.display = 'block';\">\n";
		if (empty($dashboard_detail_background_color)) {
			echo "	<input id='detail_background_color_gradient' style='display: none;' type='text' class='formfld colorpicker' name='dashboard_detail_background_color[]'>\n";
		}
	}
	if (!empty($dashboard_detail_background_color) && !is_array($dashboard_detail_background_color)) {
		echo "	<input type='text' class='formfld colorpicker' name='dashboard_detail_background_color[]' value='".escape([$dashboard_detail_background_color])."'><br />\n";
		echo "	<input type='text' class='formfld colorpicker' name='dashboard_detail_background_color[]' value=''><br />\n";
	}
	echo $text['description-dashboard_detail_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_background_gradient_style' ".(!in_array('dashboard_background_gradient_style', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_background_gradient_style']."\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "	<select name='dashboard_background_gradient_style' class='formfld'>\n";
	echo "		<option value='mirror'>".$text['option-dashboard_background_gradient_style_option_mirror']."</option>\n";
	echo "		<option value='simple' ".($dashboard_background_gradient_style == 'simple' ? "selected='selected'" : null).">".$text['option-dashboard_background_gradient_style_option_simple']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_background_gradient_style']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_background_gradient_angle' ".(!in_array('dashboard_background_gradient_angle', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-dashboard_background_gradient_angle']."\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "	<div style='overflow: auto;'>\n";
	echo "		<select name='dashboard_background_gradient_angle' class='formfld' style='float: left;' onchange=\"document.getElementById('angle').style.transform = 'rotate(' + ($(this).val() - 90) + 'deg)';\">\n";
	for ($a = 0; $a <= 180; $a += 5) {
		echo "		<option value='".($a + 90)."' ".($dashboard_background_gradient_angle == ($a + 90) ? "selected='selected'" : null).">".$a."&deg;</option>\n";
	}
	echo "		</select>\n";
	echo "		<span id='angle' style='display: inline-block; font-size: 15px; margin-left: 15px; margin-top: 3px; transform: rotate(".(isset($dashboard_background_gradient_angle) ? ($dashboard_background_gradient_angle - 90) : 0)."deg);'>&horbar;</span>\n";
	echo "	</div>\n";
	echo $text['description-dashboard_background_gradient_angle']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_column_span' ".(!in_array('dashboard_column_span', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_column_span']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='dashboard_column_span' class='formfld'>\n";
	for ($i = 1; $i <= 4; $i++) {
		$selected = ($i == $dashboard_column_span) ? "selected" : null;
		echo "		<option value='$i' ".$selected.">$i</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_column_span']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_row_span' ".(!in_array('dashboard_row_span', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_row_span']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='dashboard_row_span' class='formfld'>\n";
	for ($i = 1; $i <= 4; $i++) {
		$selected = ($i == $dashboard_row_span) ? "selected" : null;
		echo "		<option value='$i' ".$selected.">$i</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_row_span']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_details_state' ".(!in_array('dashboard_details_state', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_details_state']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='dashboard_details_state' class='formfld'>\n";
	echo "		<option value='expanded'>".$text['option-expanded']."</option>\n";
	echo "		<option value='contracted' ".($dashboard_details_state == "contracted" ? "selected='selected'" : null).">".$text['option-contracted']."</option>\n";
	echo "		<option value='hidden' ".($dashboard_details_state == "hidden" ? "selected='selected'" : null).">".$text['option-hidden']."</option>\n";
	echo "		<option value='disabled' ".($dashboard_details_state == "disabled" || empty($dashboard_details_state) ? "selected='selected'" : null).">".$text['label-disabled']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_details_state']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('dashboard_parent_uuid')) {
		echo "	<tr id='tr_dashboard_parent_uuid' ".(!in_array('dashboard_parent_uuid', $dashboard_settings) ? "style='display: none;'" : null).">\n";
		echo "		<td class='vncell'>".$text['label-dashboard_parent_uuid']."</td>\n";
		echo "		<td class='vtable'>\n";
		echo "			<select name=\"dashboard_parent_uuid\" class='formfld'>\n";
		echo "			<option value=\"\"></option>\n";
		foreach ($dashboard_parents as $field) {
			if ($field['dashboard_uuid'] == $dashboard_parent_uuid) {
				echo "			<option value='".escape($field['dashboard_uuid'])."' selected>".escape($field['dashboard_name'])."</option>\n";
			}
			else {
				echo "			<option value='".escape($field['dashboard_uuid'])."'>".escape($field['dashboard_name'])."</option>\n";
			}
		}
		echo "			</select>\n";
		echo "<br />\n";
		echo $text['description-dashboard_parent_uuid']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";
	}

	echo "<tr id='tr_dashboard_order' ".(!in_array('dashboard_order', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='dashboard_order' class='formfld'>\n";
	$i=0;
	while ($i<=999) {
		$selected = ($i == $dashboard_order) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "		<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "		<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "		<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_enabled' ".(!in_array('dashboard_enabled', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='dashboard_enabled' name='dashboard_enabled' value='true' ".($dashboard_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='dashboard_enabled' name='dashboard_enabled'>\n";
		echo "		<option value='false'>".$text['option-false']."</option>\n";
		echo "		<option value='true' ".($dashboard_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-dashboard_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_dashboard_description' ".(!in_array('dashboard_description', $dashboard_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dashboard_description' maxlength='255' value='".escape($dashboard_description)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "</div>\n";
	echo "<br /><br />\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
