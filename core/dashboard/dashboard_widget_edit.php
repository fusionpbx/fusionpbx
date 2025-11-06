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
	if (!(permission_exists('dashboard_widget_add') || permission_exists('dashboard_widget_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the defaults
	$widget_uuid = '';
	$widget_name = '';
	$widget_path = 'dashboard/icon';
	$widget_icon = '';
	$widget_icon_color = '';
	$widget_url = '';
	$widget_target = 'self';
	$widget_width = '';
	$widget_height = '';
	$widget_content = '';
	$widget_content_text_align = '';
	$widget_content_details = '';
	$widget_groups = [];
	$widget_label_enabled = '';
	$widget_label_text_color = '';
	$widget_label_text_color_hover = '';
	$widget_label_background_color = '';
	$widget_label_background_color_hover = '';
	$widget_number_text_color = '';
	$widget_number_background_color = '';
	$widget_column_span = '';
	$widget_row_span = '';
	$widget_details_state = '';
	$widget_parent_uuid = '';
	$widget_order = '';
	$widget_enabled = '';
	$widget_description = '';

//action add or update
	if (!empty($_REQUEST["widget_uuid"]) && is_uuid($_REQUEST["widget_uuid"])) {
		$action = "update";
		$dashboard_uuid = $_REQUEST["id"];
		$widget_uuid = $_REQUEST["widget_uuid"];
	}
	else {
		$action = "add";
		$dashboard_uuid = $_REQUEST["id"];
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$widget_name = $_POST["widget_name"] ?? '';
		$widget_path = $_POST["widget_path"] ?? '';
		$widget_icon = $_POST["widget_icon"] ?? '';
		$widget_icon_color = $_POST["widget_icon_color"] ?? '';
		$widget_url = $_POST["widget_url"] ?? '';
		$widget_target = $_POST["widget_target"] ?? 'self';
		$widget_width = $_POST["widget_width"] ?? '';
		$widget_height = $_POST["widget_height"] ?? '';
		$widget_content = $_POST["widget_content"] ?? '';
		$widget_content_text_align = $_POST["widget_content_text_align"] ?? '';
		$widget_content_details = $_POST["widget_content_details"] ?? '';
		$widget_groups = $_POST["dashboard_widget_groups"] ?? '';
		$widget_chart_type = $_POST["widget_chart_type"] ?? '';
		$widget_label_enabled = $_POST["widget_label_enabled"];
		$widget_label_text_color = $_POST["widget_label_text_color"] ?? '';
		$widget_label_text_color_hover = $_POST["widget_label_text_color_hover"] ?? '';
		$widget_label_background_color = $_POST["widget_label_background_color"] ?? '';
		$widget_label_background_color_hover = $_POST["widget_label_background_color_hover"] ?? '';
		$widget_number_text_color = $_POST["widget_number_text_color"] ?? '';
		$widget_number_text_color_hover = $_POST["widget_number_text_color_hover"] ?? '';
		$widget_number_background_color = $_POST["widget_number_background_color"] ?? '';
		$widget_background_color = $_POST["widget_background_color"] ?? '';
		$widget_background_color_hover = $_POST["widget_background_color_hover"] ?? '';
		$widget_detail_background_color = $_POST["widget_detail_background_color"] ?? '';
		$widget_background_gradient_style = $_POST["widget_background_gradient_style"] ?? 'mirror';
		$widget_background_gradient_angle = $_POST["widget_background_gradient_angle"] ?? '90';
		$widget_column_span = $_POST["widget_column_span"] ?? '';
		$widget_row_span = $_POST["widget_row_span"] ?? '';
		$widget_details_state = $_POST["widget_details_state"] ?? '';
		$widget_parent_uuid = $_POST["dashboard_widget_parent_uuid"] ?? '';
		$widget_order = $_POST["widget_order"] ?? '';
		$widget_enabled = $_POST["widget_enabled"];
		$widget_description = $_POST["widget_description"] ?? '';

		//define the regex patterns
		$uuid_pattern = '/[^-A-Fa-f0-9]/';
		$number_pattern = '/[^-A-Za-z0-9()*#]/';
		$text_pattern = '/[^a-zA-Z0-9 _\-\/.\?:\=#\n,()]/';

		//sanitize the data
		$widget_name = trim($widget_name);
		$widget_path = preg_replace($text_pattern, '', strtolower($widget_path));
		$widget_icon = preg_replace($text_pattern, '', $widget_icon);
		$widget_icon_color = preg_replace($text_pattern, '', $widget_icon_color);
		$widget_url = trim(preg_replace($text_pattern, '', $widget_url));
		$widget_target = trim(preg_replace($text_pattern, '', $widget_target));
		$widget_width = trim(preg_replace($text_pattern, '', $widget_width));
		$widget_height = trim(preg_replace($text_pattern, '', $widget_height));
		$widget_content = trim($widget_content);
		$widget_content_text_align = trim(preg_replace($text_pattern, '', $widget_content_text_align));
		$widget_content_details = trim(preg_replace($text_pattern, '', $widget_content_details));
		$widget_chart_type = preg_replace($text_pattern, '', $widget_chart_type);
		$widget_label_enabled = preg_replace($text_pattern, '', $widget_label_enabled);
		$widget_label_text_color = preg_replace($text_pattern, '', $widget_label_text_color);
		$widget_label_text_color_hover = preg_replace($text_pattern, '', $widget_label_text_color_hover);
		$widget_label_background_color = preg_replace($text_pattern, '', $widget_label_background_color);
		$widget_label_background_color_hover = preg_replace($text_pattern, '', $widget_label_background_color_hover);
		$widget_number_text_color = preg_replace($text_pattern, '', $widget_number_text_color);
		$widget_number_text_color_hover = preg_replace($text_pattern, '', $widget_number_text_color_hover);
		$widget_number_background_color = preg_replace($text_pattern, '', $widget_number_background_color);
		$widget_background_color = preg_replace($text_pattern, '', $widget_background_color);
		$widget_background_color_hover = preg_replace($text_pattern, '', $widget_background_color_hover);
		$widget_detail_background_color = preg_replace($text_pattern, '', $widget_detail_background_color);
		$widget_background_gradient_style = preg_replace($text_pattern, '', $widget_background_gradient_style);
		$widget_background_gradient_angle = preg_replace($text_pattern, '', $widget_background_gradient_angle);
		$widget_column_span = preg_replace($number_pattern, '', $widget_column_span);
		$widget_row_span = preg_replace($number_pattern, '', $widget_row_span);
		$widget_details_state = preg_replace($text_pattern, '', $widget_details_state);
		$widget_parent_uuid = preg_replace($uuid_pattern, '', $widget_parent_uuid);
		$widget_order = preg_replace($number_pattern, '', $widget_order);
		$widget_enabled = preg_replace($text_pattern, '', $widget_enabled);
		$widget_description = preg_replace($text_pattern, '', $widget_description);
	}

//delete the group from the sub table
	if (!empty($_POST["action"]) && $_POST["action"] === "delete" && permission_exists("dashboard_widget_group_delete") && is_uuid($_POST["dashboard_widget_group_uuid"]) && is_uuid($_POST["dashboard_widget_uuid"])) {
		//get the uuid
			$widget_group_uuid = $_POST['dashboard_widget_group_uuid'];

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dashboard_edit.php?id='.urlencode($dashboard_uuid));
				exit;
			}

		//delete the group from the widget
			$array['dashboard_widget_groups'][0]['dashboard_widget_group_uuid'] = $widget_group_uuid;
			$database->delete($array);
			unset($array);

		//redirect the user
			message::add($text['message-delete']);
			header("Location: dashboard_widget_edit.php?id=".urlencode($dashboard_uuid)."&widget_uuid=".urlencode($widget_uuid));
			return;
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && empty($_POST["persistformvar"])) {
		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dashboard_edit.php?id='.urlencode($dashboard_uuid));
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action'])) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('dashboard_widget_add')) {
							$database->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('dashboard_widget_delete')) {
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
					header('Location: dashboard_edit.php?id='.urlencode($dashboard_uuid));
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (empty($widget_name)) { $msg .= $text['message-required']." ".$text['label-widget_name']."<br>\n"; }
			//if (empty($widget_path)) { $msg .= $text['message-required']." ".$text['label-widget_path']."<br>\n"; }
			//if (empty($widget_groups)) { $msg .= $text['message-required']." ".$text['label-widget_groups']."<br>\n"; }
			//if (empty($widget_order)) { $msg .= $text['message-required']." ".$text['label-widget_order']."<br>\n"; }
			//if (empty($widget_enabled)) { $msg .= $text['message-required']." ".$text['label-widget_enabled']."<br>\n"; }
			//if (empty($widget_description)) { $msg .= $text['message-required']." ".$text['label-widget_description']."<br>\n"; }
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

		//add the dashboard_widget_uuid
			if (!is_uuid($_POST["dashboard_widget_uuid"])) {
				$widget_uuid = uuid();
			}

		//remove empty values and convert to json
			if (!empty($widget_background_color)) {
				if (is_array($widget_background_color)) {
					$widget_background_color = array_filter($widget_background_color);
					if (count($widget_background_color) > 0) {
						$widget_background_color = json_encode($widget_background_color);
					}
					else {
						$widget_background_color = '';
					}
				}
			}
			if (!empty($widget_background_color_hover)) {
				if (is_array($widget_background_color_hover)) {
					$widget_background_color_hover = array_filter($widget_background_color_hover);
					if (count($widget_background_color_hover) > 0) {
						$widget_background_color_hover = json_encode($widget_background_color_hover);
					}
					else {
						$widget_background_color_hover = '';
					}
				}
			}
			if (!empty($widget_detail_background_color)) {
				if (is_array($widget_detail_background_color)) {
					$widget_detail_background_color = array_filter($widget_detail_background_color);
					if (count($widget_detail_background_color) > 0) {
						$widget_detail_background_color = json_encode($widget_detail_background_color);
					}
					else {
						$widget_detail_background_color = '';
					}
				}
			}

		//prepare the array
			$array['dashboard_widgets'][0]['dashboard_uuid'] = $dashboard_uuid;
			$array['dashboard_widgets'][0]['dashboard_widget_uuid'] = $widget_uuid;
			$array['dashboard_widgets'][0]['widget_name'] = $widget_name;
			$array['dashboard_widgets'][0]['widget_path'] = $widget_path;
			$array['dashboard_widgets'][0]['widget_icon'] = $widget_icon;
			$array['dashboard_widgets'][0]['widget_icon_color'] = $widget_icon_color;
			$array['dashboard_widgets'][0]['widget_url'] = $widget_url;
			$array['dashboard_widgets'][0]['widget_width'] = $widget_width;
			$array['dashboard_widgets'][0]['widget_height'] = $widget_height;
			$array['dashboard_widgets'][0]['widget_target'] = $widget_target;
			$array['dashboard_widgets'][0]['widget_content'] = $widget_content;
			$array['dashboard_widgets'][0]['widget_content_text_align'] = $widget_content_text_align;
			$array['dashboard_widgets'][0]['widget_content_details'] = $widget_content_details;
			$array['dashboard_widgets'][0]['widget_chart_type'] = $widget_chart_type;
			$array['dashboard_widgets'][0]['widget_label_enabled'] = $widget_label_enabled;
			$array['dashboard_widgets'][0]['widget_label_text_color'] = $widget_label_text_color;
			$array['dashboard_widgets'][0]['widget_label_text_color_hover'] = $widget_label_text_color_hover;
			$array['dashboard_widgets'][0]['widget_label_background_color'] = $widget_label_background_color;
			$array['dashboard_widgets'][0]['widget_label_background_color_hover'] = $widget_label_background_color_hover;
			$array['dashboard_widgets'][0]['widget_number_text_color'] = $widget_number_text_color;
			$array['dashboard_widgets'][0]['widget_number_text_color_hover'] = $widget_number_text_color_hover;
			$array['dashboard_widgets'][0]['widget_number_background_color'] = $widget_number_background_color;
			$array['dashboard_widgets'][0]['widget_background_color'] = $widget_background_color;
			$array['dashboard_widgets'][0]['widget_background_color_hover'] = $widget_background_color_hover;
			$array['dashboard_widgets'][0]['widget_detail_background_color'] = $widget_detail_background_color;
			$array['dashboard_widgets'][0]['widget_background_gradient_style'] = $widget_background_gradient_style;
			$array['dashboard_widgets'][0]['widget_background_gradient_angle'] = $widget_background_gradient_angle;
			$array['dashboard_widgets'][0]['widget_column_span'] = $widget_column_span;
			$array['dashboard_widgets'][0]['widget_row_span'] = $widget_row_span;
			$array['dashboard_widgets'][0]['widget_details_state'] = $widget_details_state;
			$array['dashboard_widgets'][0]['dashboard_widget_parent_uuid'] = $widget_parent_uuid;
			$array['dashboard_widgets'][0]['widget_order'] = $widget_order;
			$array['dashboard_widgets'][0]['widget_enabled'] = $widget_enabled;
			$array['dashboard_widgets'][0]['widget_description'] = $widget_description;
			$y = 0;
			if (is_array($widget_groups)) {
				foreach ($widget_groups as $row) {
					if (isset($row['group_uuid']) && is_uuid($row['group_uuid'])) {
						$array['dashboard_widgets'][0]['dashboard_widget_groups'][$y]['dashboard_uuid'] = $dashboard_uuid;
						$array['dashboard_widgets'][0]['dashboard_widget_groups'][$y]['dashboard_widget_group_uuid'] = uuid();
						$array['dashboard_widgets'][0]['dashboard_widget_groups'][$y]['group_uuid'] = $row["group_uuid"];
						$y++;
					}
				}
			}

		//save the data
			$result = $database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
					header('Location: dashboard_edit.php?id='.urlencode($dashboard_uuid));
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
					header('Location: dashboard_widget_edit.php?id='.urlencode($dashboard_uuid).'&widget_uuid='.urlencode($widget_uuid));
				}
				return;
			}
	}

//pre-populate the form
	if (empty($_POST["persistformvar"])) {
		$sql = "select ";
		$sql .= " dashboard_widget_uuid, ";
		$sql .= " widget_name, ";
		$sql .= " widget_path, ";
		$sql .= " widget_icon, ";
		$sql .= " widget_icon_color, ";
		$sql .= " widget_url, ";
		$sql .= " widget_width, ";
		$sql .= " widget_height, ";
		$sql .= " widget_target, ";
		$sql .= " widget_content, ";
		$sql .= " widget_content_text_align, ";
		$sql .= " widget_content_details, ";
		$sql .= " widget_chart_type, ";
		$sql .= " widget_label_enabled, ";
		$sql .= " widget_label_text_color, ";
		$sql .= " widget_label_text_color_hover, ";
		$sql .= " widget_label_background_color, ";
		$sql .= " widget_label_background_color_hover, ";
		$sql .= " widget_number_text_color, ";
		$sql .= " widget_number_text_color_hover, ";
		$sql .= " widget_number_background_color, ";
		$sql .= " widget_background_color, ";
		$sql .= " widget_background_color_hover, ";
		$sql .= " widget_detail_background_color, ";
		$sql .= " widget_background_gradient_style, ";
		$sql .= " widget_background_gradient_angle, ";
		$sql .= " widget_column_span, ";
		$sql .= " widget_row_span, ";
		$sql .= " widget_details_state, ";
		$sql .= " dashboard_widget_parent_uuid, ";
		$sql .= " widget_order, ";
		$sql .= " widget_enabled, ";
		$sql .= " widget_description ";
		$sql .= "from v_dashboard_widgets ";
		$sql .= "where dashboard_widget_uuid = :dashboard_widget_uuid ";
		$parameters['dashboard_widget_uuid'] = $widget_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$widget_name = $row["widget_name"];
			$widget_path = $row["widget_path"];
			$widget_icon = $row["widget_icon"];
			$widget_icon_color = $row["widget_icon_color"];
			$widget_url = $row["widget_url"];
			$widget_width = $row["widget_width"];
			$widget_height = $row["widget_height"];
			$widget_target = $row["widget_target"];
			$widget_content = $row["widget_content"];
			$widget_content_text_align = $row["widget_content_text_align"];
			$widget_content_details = $row["widget_content_details"];
			$widget_chart_type = $row["widget_chart_type"];
			$widget_label_enabled = $row["widget_label_enabled"];
			$widget_label_text_color = $row["widget_label_text_color"];
			$widget_label_text_color_hover = $row["widget_label_text_color_hover"];
			$widget_label_background_color = $row["widget_label_background_color"];
			$widget_label_background_color_hover = $row["widget_label_background_color_hover"];
			$widget_number_text_color = $row["widget_number_text_color"];
			$widget_number_text_color_hover = $row["widget_number_text_color_hover"];
			$widget_number_background_color = $row["widget_number_background_color"];
			$widget_background_color = $row["widget_background_color"];
			$widget_background_color_hover = $row["widget_background_color_hover"];
			$widget_detail_background_color = $row["widget_detail_background_color"];
			$widget_background_gradient_style = $row["widget_background_gradient_style"];
			$widget_background_gradient_angle = $row["widget_background_gradient_angle"];
			$widget_column_span = $row["widget_column_span"];
			$widget_row_span = $row["widget_row_span"];
			$widget_details_state = $row["widget_details_state"];
			$widget_parent_uuid = $row["dashboard_widget_parent_uuid"];
			$widget_order = $row["widget_order"];
			$widget_enabled = $row["widget_enabled"];
			$widget_description = $row["widget_description"];
		}
		unset($sql, $parameters, $row);
	}

//find the application and widget
	$widget_path_array = explode('/', $widget_path);
	$application_name = $widget_path_array[0];
	$widget_path_name = $widget_path_array[1];
	$path_array = glob(dirname(__DIR__, 2).'/*/'.$application_name.'/resources/dashboard/config.php');
	if (file_exists($path_array[0])) {
		$x = 0;
		include($path_array[0]);
	}

//find the chart type options
	$widget_chart_type_options = [];
	if (!empty($array['dashboard_widgets'])) {
		foreach ($array['dashboard_widgets'] as $index => $row) {
			if ($row['widget_path'] === "$application_name/$widget_path_name") {
				$widget_chart_type_options = $row['widget_chart_type_options'];
				break;
			}
		}
	}

//get the child data
	if (!empty($widget_uuid) && is_uuid($widget_uuid)) {
		$sql = "select ";
		$sql .= " dashboard_widget_group_uuid, ";
		$sql .= " group_uuid ";
		$sql .= "from v_dashboard_widget_groups ";
		$sql .= "where dashboard_widget_uuid = :dashboard_widget_uuid ";
		$parameters['dashboard_widget_uuid'] = $widget_uuid;
		$widget_groups = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $widget_group_uuid
	if (empty($widget_group_uuid) || !empty($widget_group_uuid) && !is_uuid($widget_group_uuid)) {
		$widget_group_uuid = uuid();
	}

//convert the json to an array
	if (!empty($widget_background_color) && is_json($widget_background_color)) {
		$widget_background_color = json_decode($widget_background_color, true);
	}
	if (!empty($widget_background_color_hover) && is_json($widget_background_color_hover)) {
		$widget_background_color_hover = json_decode($widget_background_color_hover, true);
	}
	if (!empty($widget_detail_background_color) && is_json($widget_detail_background_color)) {
		$widget_detail_background_color = json_decode($widget_detail_background_color, true);
	}

//add an empty row
	$x = is_array($widget_groups) ? count($widget_groups) : 0;
	$widget_groups[$x]['dashboard_widget_uuid'] = $widget_uuid;
	$widget_groups[$x]['dashboard_widget_group_uuid'] = uuid();
	$widget_groups[$x]['group_uuid'] = '';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dashboard_widget'];
	require_once "resources/header.php";

//get the dashboard groups
	$sql = "SELECT * FROM v_dashboard_widget_groups as x, v_groups as g ";
	$sql .= "WHERE x.dashboard_widget_uuid = :dashboard_widget_uuid ";
	$sql .= "AND x.group_uuid = g.group_uuid ";
	$parameters['dashboard_widget_uuid'] = $widget_uuid ?? '';
	$widget_groups = $database->select($sql, $parameters, 'all');
	unset ($sql, $parameters);

//get the groups
	$sql = "SELECT group_uuid, domain_uuid, group_name FROM v_groups ";
	$sql .= "WHERE (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= "ORDER BY domain_uuid desc, group_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$groups = $database->execute($sql, $parameters, 'all');
	unset ($sql, $parameters);

//get the dashboards
	$sql = "SELECT dashboard_widget_uuid, widget_name FROM v_dashboard_widgets ";
	$sql .= "WHERE widget_path = 'dashboard/parent' ";
	$sql .= "AND dashboard_uuid = :dashboard_uuid ";
	$sql .= "ORDER by widget_order, widget_name asc ";
	$parameters['dashboard_uuid'] = $dashboard_uuid;
	$widget_parents = $database->execute($sql, $parameters, 'all');
	unset ($sql, $parameters);

//set the assigned_groups array
	if (is_array($widget_groups) && sizeof($widget_groups) != 0) {
		$assigned_groups = array();
		foreach ($widget_groups as $field) {
			if (!empty($field['group_name'])) {
				if (is_uuid($field['group_uuid'])) {
					$assigned_groups[] = $field['group_uuid'];
				}
			}
		}
	}

//build the $widget_tools array
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
		$widget_tools[$key] = $value;

		$i++;
	}

//decide what settings to show
	$widget_settings_config = [
		'shared' => [
			'widget_name',
			'widget_path',
			'widget_groups',
			'widget_label_enabled',
			'widget_label_text_color',
			'widget_label_background_color',
			'widget_background_color',
			'widget_detail_background_color',
			'widget_background_gradient_style',
			'widget_background_gradient_angle',
			'widget_details_state',
			'widget_column_span',
			'widget_row_span',
			'widget_parent_uuid',
			'widget_order',
			'widget_enabled',
			'widget_description',
		],
		'icon' => [
			'widget_icon',
			'widget_icon_color',
			'widget_url',
			'widget_target',
			'widget_width',
			'widget_height',
			'widget_content_details',
			'widget_label_text_color_hover',
			'widget_label_background_color_hover',
			'widget_background_color_hover',
		],
		'content' => [
			'widget_content',
			'widget_content_text_align',
			'widget_content_details',
		],
		'parent' => [//doesn't use shared settings
			'widget_name',
			'widget_path',
			'widget_groups',
			'widget_background_color',
			'widget_background_gradient_style',
			'widget_background_gradient_angle',
			'widget_column_span',
			'widget_row_span',
			'widget_order',
			'widget_enabled',
			'widget_description',
		],
		'chart' => [
			'widget_chart_type',
			'widget_number_text_color',
			'icon' => [
				'widget_icon',
				'widget_icon_color',
				'widget_label_text_color_hover',
				'widget_label_background_color_hover',
				'widget_number_text_color_hover',
				'widget_number_background_color',
				'widget_background_color_hover',
			],
		],
	];

	//build the dashboard settings array
	$widget_settings = $widget_settings_config['shared'];
	$items_to_remove = [];

	if ($action == "add" || $widget_path == "dashboard/icon") {
		$widget_settings = array_merge($widget_settings, $widget_settings_config['icon']);

		if (empty($widget_url)) {
			$items_to_remove[] = 'widget_target';
		}
		if (empty($widget_url) || $widget_target != "new") {
			$items_to_remove[] = 'widget_width';
			$items_to_remove[] = 'widget_height';
		}
		if ($widget_label_enabled === false) {
			$items_to_remove[] = 'widget_label_text_color';
			$items_to_remove[] = 'widget_label_text_color_hover';
			$items_to_remove[] = 'widget_label_background_color';
			$items_to_remove[] = 'widget_label_background_color_hover';
		}
	}
	else if ($widget_path == "dashboard/content") {
		$widget_settings = array_merge($widget_settings, $widget_settings_config['content']);
	}
	else if ($widget_path == "dashboard/parent") {
		$widget_settings = $widget_settings_config['parent'];
	}

	if (!empty($widget_chart_type) && !empty($widget_chart_type_options)) {
		$widget_settings = array_merge($widget_settings, array_filter($widget_settings_config['chart'], 'is_scalar'));

		if ($widget_chart_type == "icon") {
			$widget_settings = array_merge($widget_settings, $widget_settings_config['chart']['icon']);
		}
		else if ($widget_chart_type == "line") {
			$items_to_remove[] = 'widget_number_text_color';
		}
	}

	if (empty($widget_details_state) || $widget_details_state == 'none') {
		$items_to_remove[] = 'widget_details_state';
	}

	$widget_settings = array_diff($widget_settings, $items_to_remove);

?>
<script>

//adjust form by type entered
document.addEventListener('DOMContentLoaded', function() {
	function adjust_form_path() {
		let selected_path = document.getElementById('widget_path').value;
		let settings_config = <?php echo json_encode($widget_settings_config); ?>;
		let settings = settings_config['shared'];

		//hide all settings initially
		const all_settings = Array.from(document.querySelectorAll("tr[id^='tr_widget_']"));
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
		fetch(`dashboard_config_json.php?widget_path=${encodeURIComponent(selected_path)}`)
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
						else if (chart_type === "line" && chart_settings.includes('widget_number_color')) {
							chart_settings = chart_settings.indexOf('widget_number_color');
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
		let url_input = document.getElementById('widget_url');
		let target_select = document.getElementById('widget_target');

		if (url_input.value != '') {
			document.getElementById('tr_widget_target').style.display = '';
		}
		else {
			document.getElementById('tr_widget_target').style.display = 'none';
		}

		let selected_target = target_select.options[target_select.selectedIndex].value;

		if (selected_target == 'new' && url_input.value != '') {
			document.getElementById('tr_widget_width').style.display = '';
			document.getElementById('tr_widget_height').style.display = '';
		}
		else {
			document.getElementById('tr_widget_width').style.display = 'none';
			document.getElementById('tr_widget_height').style.display = 'none';
		}
	}

	function toggle_label_settings() {
		let widget_settings = Object.values(<?php echo json_encode($widget_settings); ?>);
		let label_settings = document.querySelectorAll("[id^='tr_widget_label_']:not([id='tr_widget_label_enabled'])");

		label_settings.forEach(function(setting) {
			let setting_name = setting.id.replace("tr_", "");
			if (widget_settings.includes(setting_name)) {
				setting.style.display = (setting.style.display == 'none' ? '' : 'none');
			}
		});
	}

	document.getElementById('widget_path').addEventListener('change', adjust_form_path);
	document.getElementById('widget_url').addEventListener('change', adjust_form_url);
	document.getElementById('widget_target').addEventListener('change', adjust_form_url);
	document.getElementById('widget_label_enabled').addEventListener('change', toggle_label_settings);
});

</script>
<?php

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input class='formfld' type='hidden' name='dashboard_widget_uuid' value='".escape($widget_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard_widget']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'dashboard_edit.php?id='.urlencode($dashboard_uuid)]);
	if ($action == 'update') {
		if (permission_exists('dashboard_widget_group_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('dashboard_widget_group_delete')) {
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
		if (permission_exists('dashboard_widget_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('dashboard_widget_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr id='tr_widget_name' ".(!in_array('widget_name', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_name'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='widget_name' maxlength='255' value='".escape($widget_name)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_path' ".(!in_array('widget_path', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_path']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' id='widget_path' name='widget_path'>\n";
	echo "		<option value=''></option>\n";
	foreach($widget_tools as $key => $value) {
		echo "		<option value='$key' ".($key == $widget_path ? "selected='selected'" : null).">".$key."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-widget_path']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr id='tr_widget_icon' ".(!in_array('widget_icon', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "		<td class='vncell'>".$text['label-icon']."</td>\n";
	echo "		<td class='vtable' style='vertical-align: bottom;'>\n";
	if (file_exists($_SERVER["PROJECT_ROOT"].'/resources/fontawesome/fa_icons.php')) {
		include $_SERVER["PROJECT_ROOT"].'/resources/fontawesome/fa_icons.php';
	}
	if (!empty($font_awesome_icons) && is_array($font_awesome_icons)) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<select class='formfld' name='widget_icon' id='selected_icon' onchange=\"$('#icons').slideUp(200); $('#icon_search').fadeOut(200, function() { $('#grid_icon').fadeIn(); });\">\n";
		echo "				<option value=''></option>\n";
		foreach ($font_awesome_icons as $icon) {
			$selected = $widget_icon == implode(' ', $icon['classes']) ? "selected" : null;
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
		echo "		<input type='text' class='formfld' name='widget_icon' value='".escape($widget_icon)."'>\n";
	}
	echo			$text['description-widget_icon']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "<tr id='tr_widget_icon_color' ".(!in_array('widget_icon_color', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_icon_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_icon_color' value='".escape($widget_icon_color)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_icon_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_url' ".(!in_array('widget_url', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-link']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' id='widget_url' name='widget_url' maxlength='255' value='".escape($widget_url)."'\">\n";
	echo "<br />\n";
	echo $text['description-widget_url'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_target' ".(!in_array('widget_target', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-target']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select id='widget_target' name='widget_target' class='formfld'\">\n";
	echo "		<option value='self'>".$text['label-current_window']."</option>\n";
	echo "		<option value='new' ".(!empty($widget_target) && $widget_target == 'new' ? "selected='selected'" : null).">".$text['label-new_window']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-widget_target']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_width' ".(!in_array('widget_width', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-width']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='widget_width' maxlength='255' value='".escape($widget_width)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_width'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_height' ".(!in_array('widget_height', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-height']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='widget_height' maxlength='255' value='".escape($widget_height)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_height'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_content' ".(!in_array('widget_content', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-content']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='height: 100px;' name='widget_content'>".$widget_content."</textarea>\n";
	echo "<br />\n";
	echo $text['description-widget_content']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_content_text_align' ".(!in_array('widget_content_text_align', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_content_text_align']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='widget_content_text_align' class='formfld'>\n";
	echo "		<option value='left' ".(!empty($widget_content_text_align) && $widget_content_text_align == 'left' ? "selected='selected'" : null).">".$text['label-left']."</option>\n";
	echo "		<option value='right' ".(!empty($widget_content_text_align) && $widget_content_text_align == 'right' ? "selected='selected'" : null).">".$text['label-right']."</option>\n";
	echo "		<option value='center' ".(!empty($widget_content_text_align) && $widget_content_text_align == 'center' ? "selected='selected'" : null).">".$text['label-center']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-widget_content_text_align']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_content_details' ".(!in_array('widget_content_details', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-details']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='height: 100px;' name='widget_content_details'>".$widget_content_details."</textarea>\n";
	echo "<br />\n";
	echo $text['description-widget_content_details']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_groups' ".(!in_array('widget_groups', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_groups']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (is_array($widget_groups) && sizeof($widget_groups) != 0) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		if (permission_exists('dashboard_widget_group_delete')) {
			echo "	<input type='hidden' id='action' name='action' value=''>\n";
			echo "	<input type='hidden' id='dashboard_widget_group_uuid' name='dashboard_widget_group_uuid' value=''>\n";
		}
		$x = 0;
		foreach($widget_groups as $field) {
			if (!empty($field['group_name'])) {
				echo "<tr>\n";
				echo "	<td class='vtable' style='white-space: nowrap; padding-right: 30px;' nowrap='nowrap'>\n";
				echo $field['group_name'].((!empty($field['domain_uuid'])) ? "@".$_SESSION['domains'][$field['domain_uuid']]['domain_name'] : null);
				echo "	</td>\n";
				if (permission_exists('dashboard_widget_group_delete')) {
					echo "	<td class='list_control_icons' style='width: 25px;'>\n";
					echo button::create(['type'=>'button','icon'=>'fas fa-minus','id'=>'btn_delete','class'=>'default list_control_icon','name'=>'btn_delete','onclick'=>"modal_open('modal-delete-group-$x','btn_delete');"]);
					echo modal::create(['id'=>'modal-delete-group-'.$x,'type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); document.getElementById('dashboard_widget_group_uuid').value = '".escape($field['dashboard_widget_group_uuid'])."'; list_form_submit('frm');"])]);
					echo "	</td>\n";
				}
				echo "</tr>\n";
				$x++;
			}
		}
		echo "</table>\n";
	}
	if (!empty($groups) && is_array($groups)) {
		if (!empty($widget_groups)) { echo "<br />\n"; }
		echo "<select name='dashboard_widget_groups[0][group_uuid]' class='formfld' style='width: auto; margin-right: 3px;'>\n";
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
	echo $text['description-widget_groups']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_chart_type' ".(!in_array('widget_chart_type', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_chart_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<div style='display: flex; gap: 8px;'>\n";
	echo "		<label class='chart_type_button' title='".$text['label-number']."' ".(!in_array('number', $widget_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='widget_chart_type' value='number' ".($widget_chart_type == 'number' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-hashtag'></i>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-doughnut']."' ".(!in_array('doughnut', $widget_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='widget_chart_type' value='doughnut' ".($widget_chart_type == 'doughnut' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-chart-pie'></i>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-icon']."' ".(!in_array('icon', $widget_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='widget_chart_type' value='icon' ".($widget_chart_type == 'icon' ? 'checked' : '').">\n";
	echo "			<div style='position: relative; display: inline-block;'>\n";
	echo "				<i class='fas fa-envelope'></i>\n";
	echo "				<span style=\"background: #4099FF; color: #ffffff; font-size: 9px; font-weight: bold; text-align: center; position: absolute; top: 11px; left: 14px; padding: 0px 4px; border-radius: 10px; white-space: nowrap;\">1</span>\n";
	echo "			</div>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-line']."' ".(!in_array('line', $widget_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='widget_chart_type' value='line' ".($widget_chart_type == 'line' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-chart-line'></i>\n";
	echo "		</label>\n";
	echo "		<label class='chart_type_button' title='".$text['label-progress_bar']."' ".(!in_array('progress_bar', $widget_chart_type_options) ? "style='display: none;'" : null).">\n";
	echo "			<input type='radio' style='display: none;' name='widget_chart_type' value='progress_bar' ".($widget_chart_type == 'progress_bar' ? 'checked' : '').">\n";
	echo "			<i class='fas fa-bars-progress'></i>\n";
	echo "		</label>\n";
	echo "	</div>\n";
	echo $text['description-widget_chart_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_label_enabled' ".(!in_array('widget_label_enabled', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_label_enabled'] ?? '';
	echo "\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='widget_label_enabled' name='widget_label_enabled'>\n";
	echo "			<option value='true' ".($widget_label_enabled === true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($widget_label_enabled === false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-widget_label_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_label_text_color' ".(!in_array('widget_label_text_color', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_label_text_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_label_text_color' value='".escape($widget_label_text_color)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_label_text_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_label_text_color_hover' ".(!in_array('widget_label_text_color_hover', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_label_text_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_label_text_color_hover' value='".escape($widget_label_text_color_hover)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_label_text_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_label_background_color' ".(!in_array('widget_label_background_color', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_label_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_label_background_color' value='".escape($widget_label_background_color)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_label_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_label_background_color_hover' ".(!in_array('widget_label_background_color_hover', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_label_background_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_label_background_color_hover' value='".escape($widget_label_background_color_hover)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_label_background_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_number_text_color' ".(!in_array('widget_number_text_color', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_number_text_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_number_text_color' value='".escape($widget_number_text_color)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_number_text_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_number_text_color_hover' ".(!in_array('widget_number_text_color_hover', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_number_text_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_number_text_color_hover' value='".escape($widget_number_text_color_hover)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_number_text_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_number_background_color' ".(!in_array('widget_number_background_color', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_number_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input type='text' class='formfld colorpicker' name='widget_number_background_color' value='".escape($widget_number_background_color)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_number_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_background_color' ".(!in_array('widget_background_color', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (!empty($widget_background_color) && is_array($widget_background_color)) {
		foreach ($widget_background_color as $c => $background_color) {
			echo "	<input type='text' class='formfld colorpicker' id='widget_background_color_".$c."' name='widget_background_color[]' value='".escape($background_color)."'>\n";
			if ($c < sizeof($widget_background_color) - 1) { echo "<br />\n"; }
		}
		//swap button
		if (!empty($widget_background_color) && is_array($widget_background_color) && sizeof($widget_background_color) > 1) {
			echo "	<input type='hidden' id='widget_background_color_temp'>\n";
			echo button::create(['type'=>'button','title'=>$text['button-swap'],'icon'=>'fa-solid fa-arrow-right-arrow-left fa-rotate-90','style'=>"z-index: 0; position: absolute; display: inline-block; margin: -14px 0 0 7px;",'onclick'=>"document.getElementById('widget_background_color_temp').value = document.getElementById('widget_background_color_0').value; document.getElementById('widget_background_color_0').value = document.getElementById('widget_background_color_1').value; document.getElementById('widget_background_color_1').value = document.getElementById('widget_background_color_temp').value; this.blur();"])."<br>\n";
		}
		else {
			echo "<br />\n";
		}
	}
	if (empty($widget_background_color) || (is_array($widget_background_color) && count($widget_background_color) < 2)) {
		echo "	<input type='text' class='formfld colorpicker' style='display: block;' name='widget_background_color[]' value='' onclick=\"document.getElementById('background_color_gradient').style.display = 'block';\">\n";
		if (empty($widget_background_color)) {
			echo "	<input id='background_color_gradient' style='display: none;' type='text' class='formfld colorpicker' name='widget_background_color[]'>\n";
		}
	}
	if (!empty($widget_background_color) && !is_array($widget_background_color)) {
		echo "	<input type='text' class='formfld colorpicker' name='widget_background_color[]' value='".escape([$widget_background_color])."'><br />\n";
		echo "	<input type='text' class='formfld colorpicker' name='widget_background_color[]' value=''><br />\n";
	}
	echo $text['description-widget_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_background_color_hover' ".(!in_array('widget_background_color_hover', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_background_color_hover']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (!empty($widget_background_color_hover) && is_array($widget_background_color_hover)) {
		foreach ($widget_background_color_hover as $c => $background_color) {
			echo "	<input type='text' class='formfld colorpicker' id='widget_background_color_hover_".$c."' name='widget_background_color_hover[]' value='".escape($background_color)."'>\n";
			if ($c < sizeof($widget_background_color_hover) - 1) { echo "<br />\n"; }
		}
		//swap button
		if (!empty($widget_background_color_hover) && is_array($widget_background_color_hover) && sizeof($widget_background_color_hover) > 1) {
			echo "	<input type='hidden' id='widget_background_color_hover_temp'>\n";
			echo button::create(['type'=>'button','title'=>$text['button-swap'],'icon'=>'fa-solid fa-arrow-right-arrow-left fa-rotate-90','style'=>"z-index: 0; position: absolute; display: inline-block; margin: -14px 0 0 7px;",'onclick'=>"document.getElementById('widget_background_color_hover_temp').value = document.getElementById('widget_background_color_hover_0').value; document.getElementById('widget_background_color_hover_0').value = document.getElementById('widget_background_color_hover_1').value; document.getElementById('widget_background_color_hover_1').value = document.getElementById('widget_background_color_hover_temp').value; this.blur();"])."<br>\n";
		}
		else {
			echo "<br />\n";
		}
	}
	if (empty($widget_background_color_hover) || (is_array($widget_background_color_hover) && count($widget_background_color_hover) < 2)) {
		echo "	<input type='text' class='formfld colorpicker' style='display: block;' name='widget_background_color_hover[]' value='' onclick=\"document.getElementById('background_color_hover_gradient').style.display = 'block';\">\n";
		if (empty($widget_background_color_hover)) {
			echo "	<input id='background_color_hover_gradient' style='display: none;' type='text' class='formfld colorpicker' name='widget_background_color_hover[]'>\n";
		}
	}
	if (!empty($widget_background_color_hover) && !is_array($widget_background_color_hover)) {
		echo "	<input type='text' class='formfld colorpicker' name='widget_background_color_hover[]' value='".escape([$widget_background_color_hover])."'><br />\n";
		echo "	<input type='text' class='formfld colorpicker' name='widget_background_color_hover[]' value=''><br />\n";
	}
	echo $text['description-widget_background_color_hover']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_detail_background_color' ".(!in_array('widget_detail_background_color', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_detail_background_color']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (!empty($widget_detail_background_color) && is_array($widget_detail_background_color)) {
		foreach ($widget_detail_background_color as $c => $detail_background_color) {
			echo "	<input type='text' class='formfld colorpicker' id='widget_detail_background_color_".$c."' name='widget_detail_background_color[]' value='".escape($detail_background_color)."'>\n";
			if ($c < sizeof($widget_detail_background_color) - 1) { echo "<br />\n"; }
		}
		//swap button
		if (!empty($widget_detail_background_color) && is_array($widget_detail_background_color) && sizeof($widget_detail_background_color) > 1) {
			echo "	<input type='hidden' id='widget_detail_background_color_temp'>\n";
			echo button::create(['type'=>'button','title'=>$text['button-swap'],'icon'=>'fa-solid fa-arrow-right-arrow-left fa-rotate-90','style'=>"z-index: 0; position: absolute; display: inline-block; margin: -14px 0 0 7px;",'onclick'=>"document.getElementById('widget_detail_background_color_temp').value = document.getElementById('widget_detail_background_color_0').value; document.getElementById('widget_detail_background_color_0').value = document.getElementById('widget_detail_background_color_1').value; document.getElementById('widget_detail_background_color_1').value = document.getElementById('widget_detail_background_color_temp').value; this.blur();"])."<br>\n";
		}
		else {
			echo "<br />\n";
		}
	}
	if (empty($widget_detail_background_color) || (is_array($widget_detail_background_color) && count($widget_detail_background_color) < 2)) {
		echo "	<input type='text' class='formfld colorpicker' style='display: block;' name='widget_detail_background_color[]' value='' onclick=\"document.getElementById('detail_background_color_gradient').style.display = 'block';\">\n";
		if (empty($widget_detail_background_color)) {
			echo "	<input id='detail_background_color_gradient' style='display: none;' type='text' class='formfld colorpicker' name='widget_detail_background_color[]'>\n";
		}
	}
	if (!empty($widget_detail_background_color) && !is_array($widget_detail_background_color)) {
		echo "	<input type='text' class='formfld colorpicker' name='widget_detail_background_color[]' value='".escape([$widget_detail_background_color])."'><br />\n";
		echo "	<input type='text' class='formfld colorpicker' name='widget_detail_background_color[]' value=''><br />\n";
	}
	echo $text['description-widget_detail_background_color']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_background_gradient_style' ".(!in_array('widget_background_gradient_style', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_background_gradient_style']."\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "	<select name='widget_background_gradient_style' class='formfld'>\n";
	echo "		<option value='mirror'>".$text['option-widget_background_gradient_style_option_mirror']."</option>\n";
	echo "		<option value='simple' ".($widget_background_gradient_style == 'simple' ? "selected='selected'" : null).">".$text['option-widget_background_gradient_style_option_simple']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-widget_background_gradient_style']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_background_gradient_angle' ".(!in_array('widget_background_gradient_angle', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo $text['label-widget_background_gradient_angle']."\n";
	echo "</td>\n";
	echo "<td class='vtable'>\n";
	echo "	<div style='overflow: auto;'>\n";
	echo "		<select name='widget_background_gradient_angle' class='formfld' style='float: left;' onchange=\"document.getElementById('angle').style.transform = 'rotate(' + ($(this).val() - 90) + 'deg)';\">\n";
	for ($a = 0; $a <= 180; $a += 5) {
		echo "		<option value='".($a + 90)."' ".($widget_background_gradient_angle == ($a + 90) ? "selected='selected'" : null).">".$a."&deg;</option>\n";
	}
	echo "		</select>\n";
	echo "		<span id='angle' style='display: inline-block; font-size: 15px; margin-left: 15px; margin-top: 3px; transform: rotate(".(isset($widget_background_gradient_angle) ? ($widget_background_gradient_angle - 90) : 0)."deg);'>&horbar;</span>\n";
	echo "	</div>\n";
	echo $text['description-widget_background_gradient_angle']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_column_span' ".(!in_array('widget_column_span', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_column_span']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='widget_column_span' class='formfld'>\n";
	for ($i = 1; $i <= 4; $i++) {
		$selected = ($i == $widget_column_span) ? "selected" : null;
		echo "		<option value='$i' ".$selected.">$i</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-widget_column_span']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_row_span' ".(!in_array('widget_row_span', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_row_span']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='widget_row_span' class='formfld'>\n";
	for ($i = 1; $i <= 4; $i++) {
		$selected = ($i == $widget_row_span) ? "selected" : null;
		echo "		<option value='$i' ".$selected.">$i</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-widget_row_span']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_details_state' ".(!in_array('widget_details_state', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_details_state']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='widget_details_state' class='formfld' ".(!in_array('widget_details_state', $widget_settings) ? "disabled" : null).">\n";
	echo "		<option value='expanded'>".$text['option-expanded']."</option>\n";
	echo "		<option value='contracted' ".($widget_details_state == "contracted" ? "selected='selected'" : null).">".$text['option-contracted']."</option>\n";
	echo "		<option value='hidden' ".($widget_details_state == "hidden" ? "selected='selected'" : null).">".$text['option-hidden']."</option>\n";
	echo "		<option value='disabled' ".($widget_details_state == "disabled" || empty($widget_details_state) ? "selected='selected'" : null).">".$text['label-disabled']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-widget_details_state']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('dashboard_widget_parent_uuid')) {
		echo "	<tr id='tr_widget_parent_uuid' ".(!in_array('widget_parent_uuid', $widget_settings) ? "style='display: none;'" : null).">\n";
		echo "		<td class='vncell'>".$text['label-dashboard_widget_parent_uuid']."</td>\n";
		echo "		<td class='vtable'>\n";
		echo "			<select name=\"dashboard_widget_parent_uuid\" class='formfld'>\n";
		echo "			<option value=\"\"></option>\n";
		foreach ($widget_parents as $field) {
			if ($field['dashboard_widget_uuid'] == $widget_parent_uuid) {
				echo "			<option value='".escape($field['dashboard_widget_uuid'])."' selected>".escape($field['widget_name'])."</option>\n";
			}
			else {
				echo "			<option value='".escape($field['dashboard_widget_uuid'])."'>".escape($field['widget_name'])."</option>\n";
			}
		}
		echo "			</select>\n";
		echo "<br />\n";
		echo $text['description-dashboard_widget_parent_uuid']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";
	}

	echo "<tr id='tr_widget_order' ".(!in_array('widget_order', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='widget_order' class='formfld'>\n";
	$i=0;
	while ($i<=999) {
		$selected = ($i == $widget_order) ? "selected" : null;
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
	echo $text['description-widget_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_enabled' ".(!in_array('widget_enabled', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='widget_enabled' name='widget_enabled'>\n";
	echo "			<option value='true' ".($widget_enabled === true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($widget_enabled === false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-widget_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_widget_description' ".(!in_array('widget_description', $widget_settings) ? "style='display: none;'" : null).">\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-widget_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='widget_description' maxlength='255' value='".escape($widget_description)."'>\n";
	echo "<br />\n";
	echo $text['description-widget_description']."\n";
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
