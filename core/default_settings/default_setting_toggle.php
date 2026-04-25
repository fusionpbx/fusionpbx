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
 Portions created by the Initial Developer are Copyright (C) 2008-2026
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('default_setting_edit')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get submitted variables
	$default_setting_uuids = $_REQUEST["id"];

// Set variables from http GET parameters
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'default_setting_category'));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';
	$default_setting_category = $_GET['default_setting_category'] ?? '';

// Build the query string
	$param = [];
	if (!empty($_GET['order_by'])) {
		$param['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$param['order'] = $order;
	}
	if (!empty($search)) {
		$param['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('stream_all')) {
		$param['show'] = $show;
	}
	if (!empty($default_setting_category)) {
		$param['default_setting_category'] = $default_setting_category;
	}
	$query_string = http_build_query($param);

//toggle the setting
	$toggled = 0;
	if (is_array($default_setting_uuids) && sizeof($default_setting_uuids) > 0) {
		foreach ($default_setting_uuids as $default_setting_uuid) {
			if (is_uuid($default_setting_uuid)) {
				//get current status
					$sql = "select default_setting_enabled from v_default_settings where default_setting_uuid = :default_setting_uuid ";
					$parameters['default_setting_uuid'] = $default_setting_uuid;
					$default_setting_enabled = $database->select($sql, $parameters, 'column');
					$new_status = ($default_setting_enabled == 'true') ? 'false' : 'true';
					unset($sql, $parameters);

				//set new status
					$array['default_settings'][0]['default_setting_uuid'] = $default_setting_uuid;
					$array['default_settings'][0]['default_setting_enabled'] = $new_status;

					$database->save($array);
					//$message = $database->message;
					unset($array);

				//increment toggle total
					$toggled++;
			}
		}
		if ($toggled > 0) {
			$_SESSION["message"] = $text['message-toggled'].': '.$toggled;
		}
	}

//redirect the user
	header("Location: default_settings.php".($query_string ? '?'.$query_string : ''));

?>
