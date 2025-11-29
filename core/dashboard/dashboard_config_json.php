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
	Alex Crane <alex@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2025
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('dashboard_edit')) {
		echo "access denied";
		exit;
	}

//find the widget config
	if (!empty($_GET['widget_path'])) {
		$widget_path = $_GET['widget_path'];

		if (!preg_match('/^[a-zA-Z0-9\/_]+$/', $widget_path)) {
			echo json_encode(['error' => 'Invalid widget path']);
			exit;
		}

		//find the application and widget
		$dashboard_path_array = explode('/', $widget_path);
		$application_name = $dashboard_path_array[0];
		$widget_path_name = $dashboard_path_array[1];
		$path_array = glob(dirname(__DIR__, 2) . '/*/' . $application_name . '/resources/dashboard/config.php');

		if (file_exists($path_array[0])) {
			include($path_array[0]);

			foreach ($array['dashboard_widgets'] as $index => $row) {
				if ($row['widget_path'] === "$application_name/$widget_path_name") {
					echo json_encode([
						'chart_type_options' => $row['widget_chart_type_options'],
					]);
					exit;
				}
			}
		}
	}
	echo json_encode(['error' => 'Configuration not found']);

?>
