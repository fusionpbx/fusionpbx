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

// Includes files
	global $settings, $domain_uuid, $database;
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

// Check the permissions
	if (!(permission_exists('dialplan_view') || permission_exists('inbound_route_view') || permission_exists('outbound_route_view'))) {
		echo "access denied";
		exit;
	}

// Set the domain name from the session, defaulting to an empty string if not set
	$domain_name = $domain_name ?? $_SESSION['domain_name'] ?? '';

// Load the dialplan files
	$dialplan_templates = [];

	// Get the files
	$dialplan_files = glob(__DIR__ . '/resources/switch/conf/dialplan/*.xml');
	if (!empty($dialplan_files)) {
		// Parse each dialplan file and store its enabled status
		foreach ($dialplan_files as $file) {
			// Parse the XML file and extract the dialplan name
			$xml = simplexml_load_file($file);

			// Get the dialplan name to use as the array key
			$dialplan_name = (string)$xml->attributes()->name ?? '';

			// Skip this file if the dialplan name is empty
			if (empty($dialplan_name)) {
				continue;
			}

			// If the dialplan name already exists, skip this file to avoid overwriting
			if (isset($dialplan_templates[$dialplan_name])) {
				continue;
			}

			// Get the values
			$app_uuid = (string)$xml->attributes()->app_uuid ?? '';
			$dialplan_context = (string)$xml->attributes()->context ?? 'public';
			$dialplan_continue = (string)trim($xml->attributes()->continue ?? '');
			$dialplan_global = (string)$xml->attributes()->global ?? 'false';
			$dialplan_order = (string)$xml->attributes()->order ?? 0;
			$dialplan_enabled = (string)$xml->attributes()->enabled ?? '';

			// Replace the domain_name variable with the domain name
			$dialplan_context = str_replace('${domain_name}', $domain_name, $dialplan_context);

			// If the enabled attribute is not set, default to 'true'
			if ($dialplan_enabled === '') {
				$dialplan_enabled = 'true';
			}

			// If the enabled attribute is not set, default to 'false'
			if ($dialplan_continue === '') {
				$dialplan_continue = 'false';
			}

			// Create the array of the dialplan attributes
			$dialplan_templates[$dialplan_name] = [];
			$dialplan_templates[$dialplan_name]['dialplan_name'] = $dialplan_name ?? '';
			$dialplan_templates[$dialplan_name]['app_uuid'] = $app_uuid;
			$dialplan_templates[$dialplan_name]['dialplan_context'] = $dialplan_context;
			$dialplan_templates[$dialplan_name]['dialplan_continue'] = $dialplan_continue;
			// $dialplan_templates[$dialplan_name]['dialplan_global'] = $dialplan_global;
			$dialplan_templates[$dialplan_name]['dialplan_order'] = (int)$dialplan_order;
			$dialplan_templates[$dialplan_name]['dialplan_enabled'] = $dialplan_enabled;

			// Add the dialplan details
			if (isset($xml->condition)) {
				$y = 0;
				foreach ($xml->condition as $cond) {
					// Extract Condition Attributes
					$cond_attrs = $cond->attributes();
					$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
					$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_type'] = (string)$cond_attrs->field ?? '';
					$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_data'] = (string)$cond_attrs->expression ?? '';
					// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_break'] = (string)$cond_attrs->{'break'} ?? 'continue';
					// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_inline'] = '';
					// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_enabled'] = (string)$cond_attrs->enabled ?? 'true';
					$y++;

					// Extract Actions for this condition
					if (isset($cond->action)) {
						foreach ($cond->action as $act) {
							$act_attrs = $act->attributes();
							$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_type'] = (string)$act_attrs->application ?? '';
							$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_data'] = (string)$act_attrs->data ?? '';
							// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_break'] = '';
							// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_inline'] = (string)$act_attrs->inline ?? '';
							// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_enabled'] = (string)$act_attrs->attributes()->enabled ?? 'true';
							$y++;
						}
					}

					// Extract Anti-Actions for this condition
					if (isset($cond->{'anti-action'})) { // Use curly braces for tags with hyphens
						foreach ($cond->{'anti-action'} as $ant) {
							$ant_attrs = $ant->attributes();
							$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_tag'] = 'anti-action';
							$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_type'] = (string)$ant_attrs->application ?? '';
							$dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_data'] = (string)$ant_attrs->data ?? '';
							// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_break'] = '';
							// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_inline'] = (string)$ant_attrs->inline ?? '';
							// $dialplan_templates[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_enabled'] = (string)$ant_attrs->attributes()->enabled ?? 'true';
							$y++;
						}
					}
				}
			}
		}
		unset($dialplan_files, $file, $xml, $dialplan_name);
	}

// Add multi-lingual support
	$language = new text;
	$text = $language->get();

// Drop app uuid from the query if not from specific apps
	$allowed_app_uuids = [
		'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4', //inbound routes
		'8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3', //outbound routes
		'16589224-c876-aeb3-f59f-523a1c0801f7', //fifo queues
		'4b821450-926b-175a-af93-a03c441818b1', //time conditions
		];
	if (!empty($_GET['app_uuid']) && is_uuid($_GET['app_uuid']) && !in_array($_GET['app_uuid'], $allowed_app_uuids)) {
		unset($_GET['app_uuid']);
		header('Location: dialplans.php'.(!empty($_GET) ? '?'.http_build_query($_GET) : null));
		exit;
	}

// Get posted data
	if (!empty($_POST['dialplans'])) {
		$action = $_POST['action'];
		$dialplans = $_POST['dialplans'];
	}

// Set variables from http GET parameters
	$app_uuid = is_uuid($_GET['app_uuid'] ?? '') ? $_GET['app_uuid'] : '';
	$context = $_GET['context'] ?? '';
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? ''));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';

// Build the query string
	$url_params = [];
	if (!empty($app_uuid)) {
		$url_params['app_uuid'] = $app_uuid;
	}
	if (!empty($context)) {
		$url_params['context'] = $context;
	}
	if (!empty($page)) {
		$url_params['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$url_params['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$url_params['order'] = $order;
	}
	if (!empty($search)) {
		$url_params['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('dialplan_all')) {
		$url_params['show'] = $show;
	}
	$query_string = http_build_query($url_params);

// Process the http post data by action
	if (!empty($action) && is_array($dialplans) && @sizeof($dialplans) != 0) {

		// Process action
			switch ($action) {
				case 'copy':
					if (permission_exists('dialplan_add')) {
						$obj = new dialplan;
						$obj->app_uuid = $app_uuid;
						$obj->list_page = $list_page;
						$obj->copy($dialplans);
					}
					break;
				case 'toggle':
					if (permission_exists('dialplan_edit')) {
						$obj = new dialplan;
						$obj->app_uuid = $app_uuid;
						$obj->list_page = $list_page;
						$obj->toggle($dialplans);
					}
					break;
				case 'delete':
					if (permission_exists('dialplan_delete')) {
						$obj = new dialplan;
						$obj->app_uuid = $app_uuid;
						$obj->list_page = $list_page;
						$obj->delete($dialplans);
					}
					break;
			}

		// Redirect
			header('Location: dialplans.php'.($query_string ? '?'.$query_string : ''));
			exit;
	}

// Make sure all dialplans with context of public have the inbound route app_uuid
	if (!empty($app_uuid) && $app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
		$sql = "update v_dialplans set ";
		$sql .= "app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
		$sql .= "where dialplan_context = 'public' ";
		$sql .= "and app_uuid is null; ";
		$database->execute($sql);
		unset($sql);
	}

// Set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', false);
	$button_icon_add = $settings->get('theme', 'button_icon_add') ?? '';
	$button_icon_copy = $settings->get('theme', 'button_icon_copy') ?? '';
	$button_icon_toggle = $settings->get('theme', 'button_icon_toggle') ?? '';
	$button_icon_all = $settings->get('theme', 'button_icon_all') ?? '';
	$button_icon_delete = $settings->get('theme', 'button_icon_delete') ?? '';
	$button_icon_search = $settings->get('theme', 'button_icon_search') ?? '';
	$button_icon_edit = $settings->get('theme', 'button_icon_edit') ?? '';
	$button_icon_reset = $settings->get('theme', 'button_icon_reset') ?? '';

// Get the number of rows in the dialplan
	$sql = "select count(dialplan_uuid) from v_dialplans ";
	if ($show == "all" && permission_exists('dialplan_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid ";
		if (permission_exists('dialplan_global')) {
			$sql .= "or domain_uuid is null ";
		}
		$sql .= ") ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (empty($app_uuid)) {
		// Hide inbound routes
			$sql .= "and app_uuid <> 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
			$sql .= "and dialplan_context <> 'public' ";
		// Hide outbound routes
			//$sql .= "and app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	}
	else {
		if ($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
			$sql .= "and (app_uuid = :app_uuid or dialplan_context = 'public') ";
		}
		else {
			$sql .= "and app_uuid = :app_uuid ";
		}
		$parameters['app_uuid'] = $app_uuid;
	}
	if (!empty($context)) {
		$sql .= "and dialplan_context = :dialplan_context ";
		$parameters['dialplan_context'] = $context;
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= " 	lower(dialplan_context) like :search ";
		$sql .= " 	or lower(dialplan_name) like :search ";
		$sql .= " 	or lower(dialplan_number) like :search ";
		$sql .= " 	or lower(dialplan_description) like :search ";
		if (is_numeric($search)) {
			$sql .= " 	or dialplan_order = :search_numeric ";
			$parameters['search_numeric'] = $search;
		}
		$sql .= ") ";
		$parameters['search'] = '%'.lower_case($search).'%';
	}
	$num_rows = $database->select($sql, $parameters  ?? null, 'column');

// Prepare the paging
	$rows_per_page = $settings->get('domain', 'paging', 50);
	list($paging_controls, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page, true);
	$offset = $rows_per_page * $page;

// Get the list of dialplans
	$sql = "select ";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	$sql .= "app_uuid, ";
	$sql .= "hostname, ";
	$sql .= "dialplan_context, ";
	$sql .= "dialplan_name, ";
	$sql .= "dialplan_number, ";
	$sql .= "dialplan_destination, ";
	$sql .= "cast(dialplan_continue as text), ";
	$sql .= "dialplan_xml, ";
	$sql .= "dialplan_order, ";
	$sql .= "cast(dialplan_enabled as text), ";
	$sql .= "dialplan_description ";
	$sql .= "from v_dialplans ";
	if ($show == "all" && permission_exists('dialplan_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (";
		$sql .= "	domain_uuid = :domain_uuid ";
		if (permission_exists('dialplan_global')) {
			$sql .= "	or domain_uuid is null ";
		}
		$sql .= ") ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!is_uuid($app_uuid)) {
		// Hide inbound routes
			$sql .= "and app_uuid <> 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
			$sql .= "and dialplan_context <> 'public' ";
		// Hide outbound routes
			//$sql .= "and app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	}
	else {
		if ($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
			$sql .= "and (app_uuid = :app_uuid or dialplan_context = 'public') ";
		}
		else {
			$sql .= "and app_uuid = :app_uuid ";
		}
		$parameters['app_uuid'] = $app_uuid;
	}
	if (!empty($context)) {
		$sql .= "and dialplan_context = :dialplan_context ";
		$parameters['dialplan_context'] = $context;
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "	lower(dialplan_context) like :search ";
		$sql .= "	or lower(dialplan_name) like :search ";
		$sql .= "	or lower(dialplan_number) like :search ";
		$sql .= "	or lower(dialplan_description) like :search ";
		if (is_numeric($search)) {
			$sql .= " 	or dialplan_order = :search_numeric ";
			$parameters['search_numeric'] = $search;
		}
		$sql .= ") ";
		$parameters['search'] = '%'.lower_case($search).'%';
	}
	if (!empty($order_by)) {
		if ($order_by == 'dialplan_name' || $order_by == 'dialplan_description') {
			$sql .= 'order by lower('.$order_by.') '.$order.' ';
		}
		else {
			$sql .= order_by($order_by, $order);
		}
	}
	else {
		$sql .= "order by dialplan_order asc, lower(dialplan_name) asc ";
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$dialplans = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

// Build an array of the dialplan primary keys
	foreach($dialplans as $row) {
		$dialplan_uuids[] = $row['dialplan_uuid'];
	}

// Get the dialplan details
	if (!empty($dialplan_uuids)) {
		// Create a string of placeholders (?, ?, ?) based on the number of UUIDs
		$placeholders = implode(',', array_fill(0, count($dialplan_uuids), '?'));

		// Get the dialplan details
		$sql = "select * from v_dialplan_details ";
		$sql .= "where dialplan_uuid in (".$placeholders.")";
		$sql .= "order by dialplan_detail_order asc ";
		$dialplan_details = $database->select($sql, $dialplan_uuids, 'all');
		// echo "<pre>\n";
		// print_r($dialplan_details);
		// echo "</pre>\n";
	}

// Build the production dialplan array
	foreach($dialplans as $row) {
		$dialplan_name = $row['dialplan_name'];
		$dialplan_context = $row['dialplan_context'];

		// Replace the domain_name variable with the domain name
		$dialplan_context = str_replace('${domain_name}', $domain_name, $dialplan_context);

		$dialplan_production[$dialplan_name] = [];
		$dialplan_production[$dialplan_name]['dialplan_name'] = $dialplan_name ?? 'true';
		$dialplan_production[$dialplan_name]['app_uuid'] = $row['app_uuid'] ?? '';
		$dialplan_production[$dialplan_name]['dialplan_context'] = $dialplan_context;
		$dialplan_production[$dialplan_name]['dialplan_continue'] = $row['dialplan_continue'] ?? 'false';
		// $dialplan_production[$dialplan_name]['global_global'] = $row['domain_uuid'] ?? 'false';
		$dialplan_production[$dialplan_name]['dialplan_order'] = (int)$row['dialplan_order'] ?? 0;
		$dialplan_production[$dialplan_name]['dialplan_enabled'] = $row['dialplan_enabled'] ?? 'true';

		$y = 0;
		foreach($dialplan_details as $sub_row) {
			if ($row['dialplan_uuid'] == $sub_row['dialplan_uuid']) {
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['domain_uuid'] = $sub_row['domain_uuid'];
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_uuid'] = $sub_row['dialplan_uuid'];
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_uuid'] = $sub_row['dialplan_detail_uuid'];
				$dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_tag'] = $sub_row['dialplan_detail_tag'];
				$dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_type'] = $sub_row['dialplan_detail_type'] ?? '';
				$dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_data'] = $sub_row['dialplan_detail_data'] ?? '';
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_break'] = $sub_row['dialplan_detail_break'];
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_inline'] = $sub_row['dialplan_detail_inline'];
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_group'] = $sub_row['dialplan_detail_group'];
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_order'] = $sub_row['dialplan_detail_order'];
				// $dialplan_production[$dialplan_name]['dialplan_details'][$y]['dialplan_detail_enabled'] = $sub_row['dialplan_detail_enabled'];
				$y++;
			}
		}
	}

// Get the list of all dialplan contexts
	$sql = "select dc.* from ( ";
	$sql .= "select distinct dialplan_context from v_dialplans ";
	if ($show == "all" && permission_exists('dialplan_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!is_uuid($app_uuid)) {
		// Hide inbound routes
		$sql .= "and app_uuid <> 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
		$sql .= "and dialplan_context <> 'public' ";
	}
	else {
		$sql .= "and (app_uuid = :app_uuid ".($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ? "or dialplan_context = 'public'" : null).") ";
		$parameters['app_uuid'] = $app_uuid;
	}
	$sql .= ") as dc ";
	$rows = $database->select($sql, $parameters ?? null, 'all');
	if (is_array($rows) && @sizeof($rows) != 0) {
		foreach ($rows as $row) {
			// Reverse the array's (string) values in preparation to sort
			$dialplan_contexts[] = strrev($row['dialplan_context']);
		}
		// Sort the reversed context values, now grouping them by the domain
		sort($dialplan_contexts, SORT_NATURAL);
		// Create new array
		foreach ($dialplan_contexts as $dialplan_context) {
			// If no subcontext (doesn't contain '@'), create new key in array with a null value
			if (!substr_count($dialplan_context, '@') || strrev($dialplan_context) == 'global' || strrev($dialplan_context) == 'public') {
				$array[strrev($dialplan_context)] = null;
			}
			// Subcontext (contains '@'), create new key in array, and place subcontext in subarray
			else {
				$dialplan_context_parts = explode('@', $dialplan_context);
				$array[strrev($dialplan_context_parts[0])][] = strrev($dialplan_context_parts[1]);
			}
		}

		// Sort the array by key (domain)
		ksort($array, SORT_NATURAL);

		// Move global and public to beginning of array
		if (array_key_exists('global', $array)) {
			unset($array['global']);
			$array = array_merge(['global'=>null], $array);
		}
		if (array_key_exists('public', $array)) {
			unset($array['public']);
			$array = array_merge(['public'=>null], $array);
		}
		$dialplan_contexts = $array;
		unset($dialplan_context, $array, $dialplan_context_parts);
	}
	unset($sql, $parameters, $rows, $row);

// Create the token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

// Include the header
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": $document['title'] = $text['title-inbound_routes']; break;
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": $document['title'] = $text['title-outbound_routes']; break;
		case "16589224-c876-aeb3-f59f-523a1c0801f7": $document['title'] = $text['title-queues']; break;
		case "4b821450-926b-175a-af93-a03c441818b1": $document['title'] = $text['title-time_conditions']; break;
		default: $document['title'] = $text['title-dialplan_manager'];
	}
	require_once "resources/header.php";

// Show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>";
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": echo $text['header-inbound_routes']; break;
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": echo $text['header-outbound_routes']; break;
		case "16589224-c876-aeb3-f59f-523a1c0801f7": echo $text['header-queues']; break;
		case "4b821450-926b-175a-af93-a03c441818b1": echo $text['header-time_conditions']; break;
		default: echo $text['header-dialplan_manager'];
	}
	echo "	</b><div class='count'>".number_format($num_rows)."</div>";
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	$button_add_url = '';
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_add')) { $button_add_url = PROJECT_PATH."/app/dialplan_inbound/dialplan_inbound_add.php"; }
	else if ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_add')) { $button_add_url = PROJECT_PATH."/app/dialplan_outbound/dialplan_outbound_add.php"; }
	else if ($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) { $button_add_url = PROJECT_PATH."/app/fifo/fifo_add.php"; }
	else if ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) { $button_add_url = PROJECT_PATH."/app/time_conditions/time_condition_edit.php"; }
	else if (permission_exists('dialplan_add')) { $button_add_url = PROJECT_PATH."/app/dialplans/dialplan_add.php"; }
	if (!empty($button_add_url)) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$button_icon_add,'id'=>'btn_add','link'=>$button_add_url]);
	}
	if (!empty($dialplans)) {
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_copy')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_copy')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) ||
			permission_exists('dialplan_add')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$button_icon_copy,'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) ||
			permission_exists('dialplan_edit')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$button_icon_toggle,'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_delete')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_delete')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_delete')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_delete')) ||
			permission_exists('dialplan_delete')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$button_icon_delete,'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
		if (permission_exists('dialplan_xml')) {
			echo button::create(['type'=>'button','label'=>$text['button-xml'],'icon'=>'code','style'=>'margin-left: 3px;','link'=>'dialplan_xml.php'.(!empty($app_uuid) ? '?app_uuid='.urlencode($app_uuid) : '')]);
		}
	}
	echo "		<form id='form_search' class='inline' method='get'>\n";
	foreach ($url_params as $key => $value) {
		if (in_array($key, ['order_by', 'order', 'show'])) {
			echo "		<input type='hidden' name='".escape($key)."' value='".escape($value)."'>\n";
		}
	}
	if ($show !== 'all' && permission_exists('dialplan_all')) {
		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$button_icon_all,'link'=>'?show=all'.(!empty($app_uuid) ? '&app_uuid='.urlencode($app_uuid) : '')]);
	}
	if (permission_exists('dialplan_context')) {
		echo "	<select name='context' id='context' class='formfld' style='max-width: ".(empty($context) || $context == 'global' ? '80px' : '140px')."; margin-left: 18px;' onchange=\"$('#form_search').submit();\">\n";
		echo "		<option value='' ".(!$context ? "selected='selected'" : null)." disabled='disabled'>".$text['label-context']."...</option>\n";
		echo "		<option value=''></option>\n";
		if (!empty($dialplan_contexts) && is_array($dialplan_contexts) && @sizeof($dialplan_contexts) != 0) {
			foreach ($dialplan_contexts as $dialplan_context => $dialplan_subcontexts) {
				if (is_array($dialplan_subcontexts) && @sizeof($dialplan_subcontexts) != 0) {
					echo "<option value='".$dialplan_context."' ".($context == $dialplan_context ? "selected='selected'" : null).">".escape($dialplan_context)."</option>\n";
					foreach ($dialplan_subcontexts as $dialplan_subcontext) {
						echo "<option value='".$dialplan_subcontext."@".$dialplan_context."' ".($context == $dialplan_subcontext."@".$dialplan_context ? "selected='selected'" : null).">&nbsp;&nbsp;&nbsp;".escape($dialplan_subcontext)."@</option>\n";
					}
				}
				else {
					$dialplan_context_label = in_array($dialplan_context, ['global','public']) ? ucwords($dialplan_context) : $dialplan_context;
					echo "<option value='".$dialplan_context."' ".($context == $dialplan_context ? "selected='selected'" : null).">".escape($dialplan_context_label)."</option>\n";
				}
			}
		}
		echo "	</select>\n";
	}
	echo "		<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$button_icon_search,'type'=>'submit','id'=>'btn_search']);
	// echo button::create(['label'=>$text['button-reset'],'icon'=>$button_icon_reset,'type'=>'button','id'=>'btn_reset','link'=>'dialplans.php?app_uuid='.urlencode($app_uuid),'style'=>($search == '' ? 'display: none;' : null)]);
	if (!empty($paging_controls_mini)) {
		echo "	<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (!empty($dialplans)) {
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_copy')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_copy')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) ||
			permission_exists('dialplan_add')
			) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) ||
			permission_exists('dialplan_edit')
			) {
			echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_delete')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_delete')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_delete')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_delete')) ||
			permission_exists('dialplan_delete')
			) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
		}
	}

	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": echo $text['description-inbound_routes']; break;
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": echo $text['description-outbound_routes']; break;
		case "16589224-c876-aeb3-f59f-523a1c0801f7": echo $text['description-queues']; break;
		case "4b821450-926b-175a-af93-a03c441818b1": echo $text['description-time_conditions']; break;
		default: echo $text['description-dialplan_manager'.(permission_exists('dialplan_edit') ? '-superadmin' : '')];
	}
	echo "\n<br /><br />\n";


	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='app_uuid' name='app_uuid' value='".escape($app_uuid)."'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (
		($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && (permission_exists('inbound_route_copy') || permission_exists('inbound_route_edit') || permission_exists('inbound_route_delete'))) ||
		($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && (permission_exists('outbound_route_copy') || permission_exists('outbound_route_edit') || permission_exists('outbound_route_delete'))) ||
		($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && (permission_exists('fifo_add') || permission_exists('fifo_edit') || permission_exists('fifo_delete'))) ||
		($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && (permission_exists('time_condition_add') || permission_exists('time_condition_edit') || permission_exists('time_condition_delete'))) ||
		permission_exists('dialplan_add') || permission_exists('dialplan_edit') || permission_exists('dialplan_delete')
		) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($dialplans) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == "all" && permission_exists('dialplan_all')) {
		echo "<th>".$text['label-domain']."</th>\n";
	}
	echo th_order_by('dialplan_name', $text['label-name'], $order_by, $order, null, null, $query_string);
	echo th_order_by('dialplan_number', $text['label-number'], $order_by, $order, null, null, $query_string);
	if (permission_exists('dialplan_context')) {
		echo th_order_by('dialplan_context', $text['label-context'], $order_by, $order, null, null, $query_string);
	}
	echo th_order_by('dialplan_order', $text['label-order'], $order_by, $order, null, "class='center shrink'", $query_string);
	// echo "<th>" . $text['label-status'] . " &nbsp;</th>\n";
	echo th_order_by('dialplan_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'", $query_string);
	echo th_order_by('dialplan_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn' style='min-width: 100px;'", $query_string);
	if ((
		($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
		($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
		($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
		($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) ||
		permission_exists('dialplan_edit')) && $list_row_edit_button
		) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($dialplans)) {
		$x = 0;
		foreach ($dialplans as $row) {
			// Set the dialplan description
			$dialplan_description = $row['dialplan_description'] ?? $text['description-dialplan_'.$row['dialplan_name']] ?? '';
			$dialplan_description = str_replace('${number}', $row['dialplan_number'] ?? '', $dialplan_description);

			// Compare the database dialplans with the default dialplan templates. Set the dialplan name to bold when the dialplan has been modified.
			$dialplan_status = '';
			$dialplan_diff = '';
			$dialplan_tooltip = '';
			if (isset($dialplan_templates[$row['dialplan_name']]) && isset($dialplan_production[$row['dialplan_name']])) {
				$template_hash = serialize($dialplan_templates[$row['dialplan_name']]);
				$production_hash = serialize($dialplan_production[$row['dialplan_name']]);
				if ($template_hash == $production_hash) {
					$dialplan_status = $text['label-default'];
					$dialplan_tooltip = 'Default';
				}
				else {
					$dialplan_status = 'custom';
					$dialplan_tooltip = $text['label-modified'];
				}
				// if (!empty($dialplan_production[$row['dialplan_name']])) {
				// 	$dialplan_diff = array_diff_deep($dialplan_templates[$row['dialplan_name']], $dialplan_production[$row['dialplan_name']]);
				// }
			}
			$dialplan_bold = ($dialplan_status == 'custom') ? true : false;
			$hover_tooltip = (!empty($dialplan_status)) ? " title='".escape($dialplan_tooltip)."'" : '';

			// Prepare the List URL
			$list_row_url = '';
			if ($row['app_uuid'] == "4b821450-926b-175a-af93-a03c441818b1") {
				if (permission_exists('time_condition_edit') || permission_exists('dialplan_edit')) {
					$list_row_url = PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".urlencode($row['dialplan_uuid']).($query_string ? '&'.$query_string : '');
					if (!empty($row['domain_uuid']) && $row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
						$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
					}
				}
			}
			else if (
				($row['app_uuid'] == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($row['app_uuid'] == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($row['app_uuid'] == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				permission_exists('dialplan_edit')
				) {
				$list_row_url = "dialplan_edit.php?id=".urlencode($row['dialplan_uuid']).($query_string ? '&'.$query_string : '');
				if (!empty($row['domain_uuid']) && $row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid'] ?? '').'&domain_change=true';
				}
			}

			// Show the dialplan rows
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (
				(!is_uuid($app_uuid) && (permission_exists('dialplan_add') || permission_exists('dialplan_edit') || permission_exists('dialplan_delete'))) ||
				($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && (permission_exists('inbound_route_copy') || permission_exists('inbound_route_edit') || permission_exists('inbound_route_delete'))) ||
				($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && (permission_exists('outbound_route_copy') || permission_exists('outbound_route_edit') || permission_exists('outbound_route_delete'))) ||
				($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && (permission_exists('fifo_add') || permission_exists('fifo_edit') || permission_exists('fifo_delete'))) ||
				($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && (permission_exists('time_condition_add') || permission_exists('time_condition_edit') || permission_exists('time_condition_delete')))
				) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='dialplans[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='dialplans[$x][uuid]' value='".escape($row['dialplan_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == "all" && permission_exists('dialplan_all')) {
				if (!empty($_SESSION['domains'][$row['domain_uuid']]['domain_name'])) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>";
			if ($list_row_url) {
				echo "<a href='".$list_row_url."' ".($dialplan_bold ? " style='font-weight: bold;'" : "").$hover_tooltip.">".escape($row['dialplan_name'])."</a>";
			}
			else {
				echo escape($row['dialplan_name']);
			}
			echo "	</td>\n";
			echo "	<td>".((!empty($row['dialplan_number'])) ? escape(format_phone($row['dialplan_number'])) : "&nbsp;")."</td>\n";
			if (permission_exists('dialplan_context')) {
				echo "	<td>".escape($row['dialplan_context'])."</td>\n";
			}
			echo "	<td class='center'>".escape($row['dialplan_order'])."</td>\n";

			// echo "	<td class='left'>".$dialplan_status."</td>\n";
			// if (!empty($dialplan_diff)) {
			//	echo "	<td class='left'><pre>".print_r($dialplan_diff, true)."</pre></td>\n";
			// }

			$original_enabled = $dialplan_templates[$row['dialplan_name']]['dialplan_enabled'] ?? $row['dialplan_enabled'];
			$bold_enabled = $row['dialplan_enabled'] != $original_enabled;
			if (
				(!is_uuid($app_uuid) && permission_exists('dialplan_edit')) ||
				($row['app_uuid'] == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($row['app_uuid'] == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($row['app_uuid'] == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				($row['app_uuid'] == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit'))
				) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['dialplan_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')", 'style'=>($bold_enabled ? 'font-weight: bold;' : '')]);
			}
			else {
				echo "	<td class='center'>";
				echo $bold_enabled ? "	<strong>" . $text['label-'.$row['dialplan_enabled']] . "</strong>" : $text['label-'.$row['dialplan_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($dialplan_description)."&nbsp;</td>\n";
			if ($list_row_edit_button && (
				(!is_uuid($app_uuid) && permission_exists('dialplan_edit')) ||
				($row['app_uuid'] == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($row['app_uuid'] == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($row['app_uuid'] == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				($row['app_uuid'] == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit'))
				)) {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$button_icon_edit,'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($dialplans);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

// Include the footer
	require_once "resources/footer.php";

?>
