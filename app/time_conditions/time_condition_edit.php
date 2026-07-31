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
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

// Check permissions
	if (!(permission_exists('time_condition_add') || permission_exists('time_condition_edit'))) {
		echo "access denied";
		exit;
	}

// Add multi-lingual support
	$language = new text;
	$text = $language->get();

// Initialize the destinations object
	$destination = new destinations;

// Get the server time zone
	$server_time_zone = trim(shell_exec('date +%Z'));

// Load available presets
	$preset_region = "preset_".$settings->get('time_conditions', 'region');
	if (is_array($_SESSION['time_conditions'][$preset_region])) {
		foreach ($_SESSION['time_conditions'][$preset_region] as $json) {
			$json_array = json_decode($json, true);
			if (is_array($json_array)) {
				$available_presets[] = $json_array;
				$valid_presets[] = array_key_first(end($available_presets));
			}
		}
	}
	unset($preset_region);

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'dialplan_name'));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';

// Build the query string
	$param = [];
	if (!empty($page)) {
		$param['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$param['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$param['order'] = $order;
	}
	if (!empty($search)) {
		$param['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('time_condition_all')) {
		$param['show'] = $show;
	}
	$query_string = http_build_query($param);

// Set the action as an add or an update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$dialplan_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

// Get the post variables
	if (count($_POST) > 0) {
		$domain_uuid = $_POST["domain_uuid"];
		$dialplan_name = $_POST["dialplan_name"];
		$dialplan_number = $_POST["dialplan_number"];
		$dialplan_time_zone = $_POST["dialplan_time_zone"];
		$dialplan_order = $_POST["dialplan_order"];

		$dialplan_anti_action = $_POST["dialplan_anti_action"] ?? '';
		$dialplan_anti_action_array = explode(":", $dialplan_anti_action);
		$dialplan_anti_action_app = array_shift($dialplan_anti_action_array);
		$dialplan_anti_action_data = join(':', $dialplan_anti_action_array);
		if (permission_exists('time_condition_context')) {
			$dialplan_context = $_POST["dialplan_context"];
		}
		$dialplan_enabled = $_POST["dialplan_enabled"];
		$dialplan_description = $_POST["dialplan_description"];

		if (!permission_exists('time_condition_domain')) {
			$domain_uuid = $_SESSION['domain_uuid'];
		}
	}

	if (count($_POST) > 0 && empty($_POST["persistformvar"])) {

		// Validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: time_conditions.php'.($query_string ? '?'.$query_string : ''));
				exit;
			}

		// Check for all required data
			$msg = null;
			// if (empty($domain_uuid)) { $msg .= $text['label-required-domain_uuid']."<br>\n"; }
	 		if (empty($dialplan_name)) { $msg .= $text['label-required-dialplan_name']."<br>\n"; }
	 		if (empty($dialplan_number)) { $msg .= $text['label-required-dialplan_number']."<br>\n"; }
	 		// if (empty($dialplan_action)) { $msg .= $text['label-required-action']."<br>\n"; }
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

		// Remove the invalid characters from the dialplan name
			$dialplan_name = str_replace('/', '', $dialplan_name);

		// Set the context for users that do not have the permission
			if (permission_exists('time_condition_context')) {
				$dialplan_context = $_POST["dialplan_context"];
			}
			else {
				if ($action == 'add') {
					$dialplan_context = $_SESSION['domain_name'];
				}
				if ($action == 'update') {
					$sql = "select * from v_dialplans ";
					$sql .= "where dialplan_uuid = :dialplan_uuid ";
					$parameters['dialplan_uuid'] = $dialplan_uuid;
					$row = $database->select($sql, $parameters, 'row');
					if (is_array($row) && @sizeof($row) != 0) {
						$domain_uuid = $row["domain_uuid"];
						$dialplan_context = $row["dialplan_context"];
					}
					unset($sql, $parameters, $row);

				}
			}

		// Process main dialplan entry
			if ($action == "add") {
				// Build insert array
					$dialplan_uuid = uuid();
					$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][0]['app_uuid'] = '4b821450-926b-175a-af93-a03c441818b1';
					$array['dialplans'][0]['dialplan_continue'] = 'false';
					$array['dialplans'][0]['dialplan_context'] = $dialplan_context;

				// Grant temporary permissions
					$p = permissions::new();
					$p->add('dialplan_add', 'temp');
			}
			else if ($action == "update") {
				// Build delete array
					$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;

				// Grant temporary permissions
					$p = permissions::new();
					$p->add('dialplan_detail_delete', 'temp');

				// Execute delete
					$database->delete($array);
					unset($array);

				// Revoke temporary permissions
					$p->delete('dialplan_detail_delete', 'temp');

				// Build update array
					$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][0]['dialplan_continue'] = 'false';
					if (!empty($dialplan_context)) {
						$array['dialplans'][0]['dialplan_context'] = $dialplan_context;
					}

				// Grant temporary permissions
					$p = permissions::new();
					$p->add('dialplan_edit', 'temp');
			}

			if (is_array($array) && @sizeof($array) != 0) {
				// Add common fields to insert/update array
					$array['dialplans'][0]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
					$array['dialplans'][0]['dialplan_name'] = $dialplan_name;
					$array['dialplans'][0]['dialplan_number'] = $dialplan_number;
					$array['dialplans'][0]['dialplan_order'] = $dialplan_order;
					$array['dialplans'][0]['dialplan_enabled'] = $dialplan_enabled;
					$array['dialplans'][0]['dialplan_description'] = $dialplan_description;

				// Execute insert/update
					$database->save($array);
					unset($array);

				// Revoke temporary permissions
					$p->delete('dialplan_add', 'temp');
					$p->delete('dialplan_edit', 'temp');
			}

		// Initialize dialplan detail group and order numbers
			$dialplan_detail_group = 0;
			$dialplan_detail_order = 0;
			$x = 0;

		// Add the timezone
			if (!empty($dialplan_time_zone)) {
				$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
				$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
				$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
				$array['dialplan_details'][$x]['dialplan_detail_type'] = 'destination_number';
				$array['dialplan_details'][$x]['dialplan_detail_data'] = '^'.$dialplan_number.'$';
				$array['dialplan_details'][$x]['dialplan_detail_break'] = null;
				$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
				$array['dialplan_details'][$x]['dialplan_detail_group'] = 0;
				$array['dialplan_details'][$x]['dialplan_detail_order'] = 10;
				$x++;
				$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
				$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
				$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'action';
				$array['dialplan_details'][$x]['dialplan_detail_type'] = 'set';
				$array['dialplan_details'][$x]['dialplan_detail_data'] = 'timezone='.$dialplan_time_zone;
				$array['dialplan_details'][$x]['dialplan_detail_break'] = null;
				$array['dialplan_details'][$x]['dialplan_detail_inline'] = 'true';
				$array['dialplan_details'][$x]['dialplan_detail_group'] = 0;
				$array['dialplan_details'][$x]['dialplan_detail_order'] = 20;
				$x++;
			}

		// Clean up array
			// Remove presets not checked, restructure variable array
			if (is_array($_REQUEST['variable']['preset'])) {
				foreach ($_REQUEST['variable']['preset'] as $group_id => $conditions) {
					if (empty($_REQUEST['preset']) || !is_array($_REQUEST['preset']) || !in_array($group_id, $_REQUEST['preset'])) {
						unset($_REQUEST['variable']['preset'][$group_id]);
						unset($_REQUEST['value'][$group_id]);
						unset($_REQUEST['dialplan_action'][$group_id]);
						continue;
					}
					$_REQUEST['variable'][$group_id] = $conditions;
				}
			}
			if (is_array($_REQUEST['variable']['custom'])) {
				foreach ($_REQUEST['variable']['custom'] as $group_id => $conditions) {
					$_REQUEST['variable'][$group_id] = $conditions;
				}
			}
			unset($_REQUEST['variable']['custom'], $_REQUEST['variable']['preset']);

		// Remove invalid conditions and values by checking conditions
			if (is_array($_REQUEST['variable'])) {
				foreach ($_REQUEST['variable'] as $group_id => $conditions) {
					if (is_array($conditions)) {
						foreach ($conditions as $condition_id => $condition_variable) {
							if ($condition_variable == '') {
								unset($_REQUEST['variable'][$group_id][$condition_id]);
								unset($_REQUEST['value'][$group_id][$condition_id]);
							}
						}
					}
				}
			}

		// Remove invalid conditions and values by checking start value
			if (is_array($_REQUEST['value'])) {
				foreach ($_REQUEST['value'] as $group_id => $values) {
					foreach ($values as $value_id => $value_range) {
						if ($value_range['start'] == '') {
							unset($_REQUEST['variable'][$group_id][$value_id]);
							unset($_REQUEST['value'][$group_id][$value_id]);
						}
					}
				}
			}

		// Remove any empty groups (where conditions no longer exist)
			if (is_array($_REQUEST['variable'])) {
				foreach ($_REQUEST['variable'] as $group_id => $conditions) {
					if (sizeof($conditions) == 0) {
						unset($_REQUEST['variable'][$group_id]);
						unset($_REQUEST['value'][$group_id]);
						unset($_REQUEST['dialplan_action'][$group_id]);
					}
				}
			}

		// Remove groups where an action (or default_preset_action - if a preset group - or dialplan_anti_action) isn't defined
			if (is_array($_REQUEST['variable'])) {
				foreach ($_REQUEST['variable'] as $group_id => $meh) {
					if (
						(!empty($_REQUEST['preset']) && is_array($_REQUEST['preset']) && in_array($group_id, $_REQUEST['preset']) && empty($_REQUEST['dialplan_action'][$group_id]) && empty($_REQUEST['default_preset_action']) && empty($_REQUEST['dialplan_anti_action'])) ||
						((empty($_REQUEST['preset']) || !is_array($_REQUEST['preset']) || !in_array($group_id, $_REQUEST['preset'])) && $_REQUEST['dialplan_action'][$group_id] == '')
						) {
						unset($_REQUEST['variable'][$group_id]);
						unset($_REQUEST['value'][$group_id]);
						unset($_REQUEST['dialplan_action'][$group_id]);
						if (is_array($_REQUEST['preset'])) {
							foreach ($_REQUEST['preset'] as $preset_id => $preset_group_id) {
								if ($group_id == $preset_group_id) { unset($_REQUEST['preset'][$preset_id]); }
							}
						}
					}
				}
			}

		// Add conditions to insert array for custom and preset conditions
			if (is_array($_REQUEST['variable'])) {
				foreach ($_REQUEST['variable'] as $group_id => $conditions) {

					$group_conditions_exist[$group_id] = false;

					// Determine if preset
					$is_preset = !empty($_REQUEST['preset']) && is_array($_REQUEST['preset']) && in_array($group_id, $_REQUEST['preset']) ? true : false;

					// Set group and order number
					$dialplan_detail_group_user = $_POST['group_'.$group_id] ?? null;
					if ($dialplan_detail_group_user != '') {
						$dialplan_detail_group = $dialplan_detail_group_user;
					}
					else {
						$dialplan_detail_group = $group_id;
					}

					$dialplan_detail_order = 0;

					if (is_array($conditions)) {
						foreach ($conditions as $cond_num => $cond_var) {
							if ($cond_var != '') {
								$cond_start = $_REQUEST['value'][$group_id][$cond_num]['start'];
								$cond_stop = $_REQUEST['value'][$group_id][$cond_num]['stop'];

								// Convert to 24-hour time and UTC. use the user and the servers time zone, use a time offset
								if ($server_time_zone === 'UTC') {
									if (!empty($cond_start) && $cond_var == 'date-time') {
										$format = $settings->get('domain', 'time_format') == '24h' ? 'Y-m-d H:i' : 'Y-m-d h:i a';
										$user_timezone = $settings->get('domain', 'time_zone', date_default_timezone_get());

										$dt = DateTime::createFromFormat($format, $cond_start, new DateTimeZone($user_timezone));
										if ($dt !== false) {
											$dt->setTimezone(new DateTimeZone('UTC'));
											$cond_start = $dt->format('Y-m-d H:i');
										}
									}
									if (!empty($cond_stop) && $cond_var == 'date-time') {
										$format = $settings->get('domain', 'time_format') == '24h' ? 'Y-m-d H:i' : 'Y-m-d h:i a';
										$user_timezone = $settings->get('domain', 'time_zone', date_default_timezone_get());

										$dt = DateTime::createFromFormat($format, $cond_stop, new DateTimeZone($user_timezone));
										if ($dt !== false) {
											$dt->setTimezone(new DateTimeZone('UTC'));
											$cond_stop = $dt->format('Y-m-d H:i');
										}
									}
								}

								// Convert to 24 hour time - use the server's local time zone to set the time, no time offset
								if ($server_time_zone !== 'UTC') {
									$format = $settings->get('domain', 'time_format') == '24h' ? 'Y-m-d H:i' : 'Y-m-d h:i a';
									if (!empty($cond_start) && $cond_var == 'date-time' && $settings->get('domain', 'time_format') != '24h') {
										$cond_start = DateTime::createFromFormat($format, $cond_start)->format('Y-m-d H:i');
									}
									if (!empty($cond_stop) && $cond_var == 'date-time' && $settings->get('domain', 'time_format') != '24h') {
										$cond_stop = DateTime::createFromFormat($format, $cond_stop)->format('Y-m-d H:i');
									}
								}

								// Use date-time to set the year, month, day, and time
								/*
								if (!empty($cond_start) && !empty($cond_stop) && $cond_var == 'date-time') {
									// Extract components
									$cond_start_datetime = DateTime::createFromFormat('Y-m-d H:i', $cond_start);
									$cond_start_year   = $cond_start_datetime->format('Y');
									$cond_start_month  = $cond_start_datetime->format('n');
									$cond_start_day	= $cond_start_datetime->format('j');
									$cond_start_hour   = $cond_start_datetime->format('G');
									$cond_start_minute = $cond_start_datetime->format('i');
									$cond_start_minutes = ($cond_start_hour * 60) + $cond_start_minute + 1;

									$cond_stop_datetime = DateTime::createFromFormat('Y-m-d H:i', $cond_stop);
									$cond_stop_year   = $cond_stop_datetime->format('Y');
									$cond_stop_month  = $cond_stop_datetime->format('n');
									$cond_stop_day	= $cond_stop_datetime->format('j');
									$cond_stop_hour   = $cond_stop_datetime->format('G');
									$cond_stop_minute = $cond_stop_datetime->format('i');
									$cond_stop_minutes = ($cond_stop_hour * 60) + $cond_stop_minute;

									$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
									$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
									$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
									$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
									$array['dialplan_details'][$x]['dialplan_detail_type'] = 'year';
									$array['dialplan_details'][$x]['dialplan_detail_data'] = ($cond_start_year == $cond_stop_year) ? $cond_start_year : $cond_start_year.'-'. $cond_stop_year;
									$array['dialplan_details'][$x]['dialplan_detail_break'] = 'never';
									$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
									$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
									$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
									$x++;
									$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
									$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
									$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
									$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
									$array['dialplan_details'][$x]['dialplan_detail_type'] = 'mon';
									$array['dialplan_details'][$x]['dialplan_detail_data'] = ($cond_start_month == $cond_stop_month) ? $cond_start_month : $cond_start_month.'-'. $cond_stop_month;
									$array['dialplan_details'][$x]['dialplan_detail_break'] = 'never';
									$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
									$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
									$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
									$x++;
									$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
									$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
									$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
									$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
									$array['dialplan_details'][$x]['dialplan_detail_type'] = 'mday';
									$array['dialplan_details'][$x]['dialplan_detail_data'] = ($cond_start_day == $cond_stop_day) ? $cond_start_day : $cond_start_day.'-'. $cond_stop_day;
									$array['dialplan_details'][$x]['dialplan_detail_break'] = 'never';
									$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
									$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
									$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
									$x++;
									if ($cond_start_minutes != $cond_stop_minutes) {
										$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
										$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
										$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
										$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
										$array['dialplan_details'][$x]['dialplan_detail_type'] = 'minute-of-day';
										$array['dialplan_details'][$x]['dialplan_detail_data'] = $cond_start_minutes.'-'. $cond_stop_minutes;
										$array['dialplan_details'][$x]['dialplan_detail_break'] = 'never';
										$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
										$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
										$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
										$x++;
									}
								}
								*/

								// Convert time-of-day to minute-of-day (due to inconsistencies with time-of-day on some systems)
								if ($cond_var == 'time-of-day') {
									$cond_var = 'minute-of-day';
									$array_cond_start = explode(':', $cond_start);
									// Adjust the time by one minute to account for freeswitch starting one minute early under the start condition behavior.
									$cond_start = ($array_cond_start[0] * 60) + $array_cond_start[1] + 1;
									if ($cond_stop != '') {
										$array_cond_stop = explode(':', $cond_stop);
										$cond_stop = ($array_cond_stop[0] * 60) + $array_cond_stop[1];
									}
								}
								$cond_value = $cond_start;
								if ($cond_stop != '') {
									$range_indicator = ($cond_var == 'date-time') ? '~' : '-';
									$cond_value .= $range_indicator.$cond_stop;
								}

								if (!$group_conditions_exist[$group_id]) {
									// Add destination number condition
									$dialplan_detail_order += 10;
									$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
									$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
									$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
									$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
									$array['dialplan_details'][$x]['dialplan_detail_type'] = 'destination_number';
									$array['dialplan_details'][$x]['dialplan_detail_data'] = '^'.$dialplan_number.'$';
									$array['dialplan_details'][$x]['dialplan_detail_break'] = null;
									$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
									$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
									$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
									$x++;
								}

								// Add condition to query string
								if (!empty($cond_var)) {
									$dialplan_detail_order += 10;
									$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
									$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
									$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
									$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
									$array['dialplan_details'][$x]['dialplan_detail_type'] = $cond_var;
									$array['dialplan_details'][$x]['dialplan_detail_data'] = $cond_value;
									$array['dialplan_details'][$x]['dialplan_detail_break'] = 'never';
									$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
									$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
									$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
									$x++;
								}

								$group_conditions_exist[$group_id] = true;
							} // if
						} // foreach
					} // if

					// Continue adding to query only if conditions exist in current group
					if ($group_conditions_exist[$group_id]) {

						// Determine group action app and data
						$dialplan_action = $_REQUEST["dialplan_action"][$group_id];
						if ($dialplan_action == '') {
							if ($is_preset) {
								if ($_REQUEST['default_preset_action'] != '') {
									$dialplan_action = $_REQUEST['default_preset_action'];
								}
								else if ($_REQUEST['dialplan_anti_action'] != '') {
									$dialplan_action = $_REQUEST['dialplan_anti_action'];
								}
							}
						}

						if ($dialplan_action != '') {
							// If preset, set log variable
							if ($is_preset && is_array($_REQUEST['preset'])) {
								foreach ($_REQUEST['preset'] as $preset_number => $preset_group_id) {
									if ($group_id == $preset_group_id) {
										if (is_array($available_presets[$preset_number])) {
											foreach ($available_presets[$preset_number] as $available_preset_name => $meh) {
												$dialplan_detail_order += 10;
												$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
												$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
												$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
												$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'action';
												$array['dialplan_details'][$x]['dialplan_detail_type'] = 'set';
												$array['dialplan_details'][$x]['dialplan_detail_data'] = 'preset='.$available_preset_name;
												$array['dialplan_details'][$x]['dialplan_detail_break'] = null;
												$array['dialplan_details'][$x]['dialplan_detail_inline'] = 'true';
												$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
												$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
												$x++;
											}
										}
									}
								}
							}

							// Parse group app and data
							if (substr_count($dialplan_action, ":") > 0) {
								$dialplan_action_array = explode(":", $dialplan_action);
								$dialplan_action_app = array_shift($dialplan_action_array);
								$dialplan_action_data = join(':', $dialplan_action_array);
							}
							else {
								$dialplan_action_app = $dialplan_action;
								$dialplan_action_data = '';
							}

							// Add group action to query
							$dialplan_detail_order += 10;
							$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
							$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
							$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
							$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'action';
							if ($destination->valid($dialplan_action_app.':'.$dialplan_action_data)) {
								$array['dialplan_details'][$x]['dialplan_detail_type'] = $dialplan_action_app;
								$array['dialplan_details'][$x]['dialplan_detail_data'] = $dialplan_action_data;
							}
							$array['dialplan_details'][$x]['dialplan_detail_break'] = null;
							$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
							$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
							$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
							$x++;
						}
					}

				} // foreach
			} //if

		// Add to query for default anti-action (if defined)
			if (!empty($dialplan_anti_action_app)) {

				// Increment group number, reset order number
				$dialplan_detail_group = 999;
				$dialplan_detail_order = 0;

				// Add destination number condition
				$dialplan_detail_order += 10;
				$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
				$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
				$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'condition';
				$array['dialplan_details'][$x]['dialplan_detail_type'] = 'destination_number';
				$array['dialplan_details'][$x]['dialplan_detail_data'] = '^'.$dialplan_number.'$';
				$array['dialplan_details'][$x]['dialplan_detail_break'] = null;
				$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
				$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
				$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
				$x++;

				// Add anti-action
				$dialplan_detail_order += 10;
				$array['dialplan_details'][$x]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
				$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
				$array['dialplan_details'][$x]['dialplan_detail_uuid'] = uuid();
				$array['dialplan_details'][$x]['dialplan_detail_tag'] = 'action';
				if ($destination->valid($dialplan_anti_action_app.':'.$dialplan_anti_action_data)) {
					$array['dialplan_details'][$x]['dialplan_detail_type'] = $dialplan_anti_action_app;
					$array['dialplan_details'][$x]['dialplan_detail_data'] = $dialplan_anti_action_data;
				}
				$array['dialplan_details'][$x]['dialplan_detail_break'] = null;
				$array['dialplan_details'][$x]['dialplan_detail_inline'] = null;
				$array['dialplan_details'][$x]['dialplan_detail_group'] = $dialplan_detail_group;
				$array['dialplan_details'][$x]['dialplan_detail_order'] = $dialplan_detail_order;
				$x++;
			}

		// Execute query
			if (!empty($array) && is_array($array) && @sizeof($array) != 0) {
				// Grant temporary permissions
					$p = permissions::new();
					$p->add('dialplan_detail_add', 'temp');
					$p->add('dialplan_detail_edit', 'temp');

				// Execute insert
					$database->save($array);
					unset($array);

				// Revoke temporary permissions
					$p->delete('dialplan_detail_add', 'temp');
					$p->delete('dialplan_detail_edit', 'temp');
			}

		// Update the dialplan xml
			$dialplans = new dialplan;
			$dialplans->source = "details";
			$dialplans->destination = "database";
			$dialplans->uuid = $dialplan_uuid;
			$dialplans->xml();

		// Clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["domain_name"]);

		// Clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		// Set the message
			if ($action == "add") {
				message::add($text['message-add']);
			}
			else if ($action == "update") {
				message::add($text['message-update']);
			}

		// Redirect the browser
			header("Location: time_condition_edit.php?id=".$dialplan_uuid.(!empty($app_uuid) && is_uuid($app_uuid) ? "&app_uuid=".$app_uuid : null).($query_string ? '&'.$query_string : ''));
			exit;

	}

// Get existing data to pre-populate form
	if (!empty($dialplan_uuid) && is_uuid($dialplan_uuid) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {

		// Get main dialplan entry
			$sql = "select * from v_dialplans ";
			$sql .= "where dialplan_uuid = :dialplan_uuid ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$parameters['dialplan_uuid'] = $dialplan_uuid;
			$parameters['domain_uuid'] = $domain_uuid;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$domain_uuid = $row["domain_uuid"];
				//$app_uuid = $row["app_uuid"];
				$dialplan_name = $row["dialplan_name"];
				$dialplan_number = $row["dialplan_number"];
				$dialplan_order = $row["dialplan_order"];
				$dialplan_continue = $row["dialplan_continue"];
				$dialplan_context = $row["dialplan_context"];
				$dialplan_enabled = $row["dialplan_enabled"];
				$dialplan_description = $row["dialplan_description"];
			}
			unset($sql, $parameters, $row);

		// Remove the underscore in the time condition name
			$dialplan_name = str_replace('_', ' ', $dialplan_name);

		// Get dialplan detail conditions
			$sql = "select dialplan_detail_group, dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data ";
			$sql .= "from v_dialplan_details ";
			$sql .= "where dialplan_uuid = :dialplan_uuid ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$sql .= "and ";
			$sql .= "( ";
			$sql .= "	( ";
			$sql .= "		dialplan_detail_tag = 'condition' ";
			$sql .= "		and dialplan_detail_type in ('year','mon','mday','wday','yday','week','mweek','hour','minute','minute-of-day','time-of-day','date-time') ";
			$sql .= "	) ";
			$sql .= "	or dialplan_detail_tag = 'action' ";
			$sql .= ") ";
			$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
			$parameters['dialplan_uuid'] = $dialplan_uuid;
			$parameters['domain_uuid'] = $domain_uuid;
			$dialplan_details = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

		// Load current conditions into array (combined by group), and retrieve action and anti-action
			$c = 0;
			if (is_array($dialplan_details) && @sizeof($dialplan_details) != 0) {
				// Get time zone
				foreach ($dialplan_details as $i => $row) {
					if ($row['dialplan_detail_tag'] == 'action' && $row['dialplan_detail_type'] == 'set' && strpos($row['dialplan_detail_data'], 'timezone=') === 0) {
						$dialplan_time_zone = explode('=',$row['dialplan_detail_data'])[1];
					}
				}
				// Detect dialplan detail group has valid preset
				$dialplan_detail_group_max = 0;
				foreach ($dialplan_details as $i => $row) {
					if ($row['dialplan_detail_tag'] == 'action' && $row['dialplan_detail_type'] == 'set' && strpos($row['dialplan_detail_data'], 'preset=') === 0) {
						$preset_name = explode('=',$row['dialplan_detail_data'])[1];
						if (!empty($valid_presets) && in_array($preset_name, $valid_presets)) {
							$dialplan_detail_group_preset[$row['dialplan_detail_group']] = $preset_name;
						}
						else {
							$invalid_presets_dialplan_detail_groups[] = $row['dialplan_detail_group'];
							unset($dialplan_details[$i]);
						}
					}
					if ($row['dialplan_detail_group'] > $dialplan_detail_group_max) { $dialplan_detail_group_max = $row['dialplan_detail_group']; }
				}
				// Reorder any invalid preset dialplan detail groups
				if (isset($invalid_presets_dialplan_detail_groups) && is_array($invalid_presets_dialplan_detail_groups) && @sizeof($invalid_presets_dialplan_detail_groups) != 0) {
					foreach ($dialplan_details as $i => $row) {
						if (in_array($row['dialplan_detail_group'], $invalid_presets_dialplan_detail_groups)) {
							$dialplan_details[$i]['dialplan_detail_group'] = $dialplan_detail_group_max + 5;
						}
					}
				}
				// Parse out dialplan actions, anti-actions and conditions
				foreach ($dialplan_details as $i => $row) {
					if ($row['dialplan_detail_tag'] == 'action') {
						if ($row['dialplan_detail_group'] == '999') {
							$dialplan_anti_action = $row['dialplan_detail_type'].($row['dialplan_detail_data'] != '' || $row['dialplan_detail_type'] == 'hangup' ? ':'.$row['dialplan_detail_data'] : null);
						}
						else {
							$dialplan_detail_group = $dialplan_detail_group_preset[$row['dialplan_detail_group']] ?? $row['dialplan_detail_group'];
							$dialplan_actions[$dialplan_detail_group] = $row['dialplan_detail_type'].($row['dialplan_detail_data'] != '' || $row['dialplan_detail_type'] == 'hangup' ? ':'.$row['dialplan_detail_data'] : null);
						}
					}
					else if ($row['dialplan_detail_tag'] == 'condition') {
						$dialplan_detail_group = $dialplan_detail_group_preset[$row['dialplan_detail_group']] ?? $row['dialplan_detail_group'];
						$current_conditions[$dialplan_detail_group][$row['dialplan_detail_type']] = $row['dialplan_detail_data'];
					}
				}
			}

		// Loop through available presets (if any)
			if (is_array($available_presets) && @sizeof($available_presets) != 0) {
				foreach ($available_presets as $preset_number => $preset) {
					if (is_array($preset) && @sizeof($preset) != 0) {
						foreach ($preset as $preset_name => $preset_variables) {
							// Loop through each condition group
							if (!empty($current_conditions) && is_array($current_conditions)) {
								foreach ($current_conditions as $group_id => $condition_variables) {
									$matches = 0;
									if (is_array($condition_variables)) {
										foreach ($condition_variables as $condition_variable_name => $condition_variable_value) {
											//count matching variable values
											if (isset($preset_variables[$condition_variable_name]) && $preset_variables[$condition_variable_name] == $condition_variable_value) { $matches++; }
										}
									}
									// If all preset variables found, then condition is a preset
									if ($matches == sizeof($preset_variables)) {
										// Preset found
										if (!is_numeric($group_id)) {
											$current_presets[] = $group_id;
										}
										// Preset *conditions* found, but wasn't marked as a preset in the dialplan, so promote and update current conditions and dialplan actions
										else {
											$current_presets[] = $preset_name;
											$current_conditions[$preset_name] = $current_conditions[$group_id];
											$dialplan_actions[$preset_name] = $dialplan_actions[$group_id];
											unset($current_conditions[$group_id], $dialplan_actions[$group_id]);
										}
									}
								}
							}
						}
					}
				}
			}

		// Sort arrays by keys
			if (!empty($dialplan_actions) && is_array($dialplan_actions)) { ksort($dialplan_actions); }
			if (!empty($current_conditions) && is_array($current_conditions)) { ksort($current_conditions); }

	}

// Set the defaults
	$dialplan_context = $dialplan_context ?? $_SESSION['domain_name'];
	$dialplan_enabled = $dialplan_enabled ?? true;

// Create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

// Include the header
	$document['title'] = $text['title-time_condition'];
	require_once "resources/header.php";

// Set the time format options: 12h, 24h
	if ($settings->get('domain', 'time_format') == '24h') {
		$time_format = 'HH:mm';
	}
	else {
		$time_format = 'hh:mm a';
	}

// Debug
// 	echo "<div style='overflow: auto; font-family: courier; width: 100%; height: 200px; border: 1px solid #ccc; padding: 20px;'>\n";
// 	echo "<b>".'$dialplan_details'."</b>\n"; view_array($dialplan_details, false);
// 	echo "<b>".'$dialplan_anti_action'."</b>\n"; view_array($dialplan_anti_action, false);
// 	echo "<b>".'$dialplan_actions'."</b>\n"; view_array($dialplan_actions, false);
// 	echo "<b>".'$current_conditions'."</b>\n"; view_array($current_conditions, false);
// 	echo "<b>".'$available_presets'."</b>\n"; view_array($available_presets, false);
// 	echo "<b>".'$current_presets'."</b>\n"; view_array($current_presets, false);
// 	echo "</div><br><br>\n";

?>

<script type="text/javascript">

	function add_condition(group_id, type) {
		var condition_id = Math.floor((Math.random() * 1000) + 1);
		var html = "<table cellpadding='0' cellspacing='0' border='0' style='margin-top: 3px;' width='100%'>";
		html += "	<tr>";
		html += "		<td style='vertical-align: middle; min-width: 390px;' width='100%' nowrap='nowrap'>";
		html += "			<select class='formfld' style='width: 120px;' name='variable[" + type + "][" + group_id + "][" + condition_id + "]' id='variable_" + group_id + "_" + condition_id + "' onchange=\"load_value_fields(" + group_id + ", " + condition_id + ", this.options[this.selectedIndex].value);\">";
		html += "				<option value=''></option>";
		<?php
		$time_condition_vars["year"] = $text['label-year'];
		$time_condition_vars["mon"] = $text['label-month'];
		$time_condition_vars["mday"] = $text['label-day-of-month'];
		$time_condition_vars["wday"] = $text['label-day-of-week'];
		// $time_condition_vars["yday"] = $text['label-day-of-year'];
		$time_condition_vars["week"] = $text['label-week-of-year'];
		$time_condition_vars["mweek"] = $text['label-week-of-month'];
		$time_condition_vars["hour"] = $text['label-hour-of-day'];
		// $time_condition_vars["minute"] = $text['label-minute-of-hour'];
		// $time_condition_vars["minute-of-day"] = $text['label-minute-of-day'];
		$time_condition_vars["time-of-day"] = $text['label-time-of-day'];
		$time_condition_vars["date-time"] = $text['label-date-and-time'];
		if (is_array($time_condition_vars)) {
			foreach ($time_condition_vars as $var_name => $var_label) {
				echo "html += \"	<option value='".$var_name."'>".$var_label."</option>\";";
			}
		}
		?>
		html += "			</select>";
		html += "			<select class='formfld' style='width: 120px;' name='value[" + group_id + "][" + condition_id + "][start]' id='value_" + group_id + "_" + condition_id + "_start'></select>";
		html += "			&nbsp;~&nbsp;";
		html += "			<select class='formfld' style='width: 120px; margin-right: 2px;' name='value[" + group_id + "][" + condition_id + "][stop]' id='value_" + group_id + "_" + condition_id + "_stop'></select>";
		html += "		</td>";
		html += "		<td style='vertical-align: middle; text-align: right;'>";
		html += "			<a href='javascript:void(0);' onclick='delete_condition(" + group_id + ", " + condition_id + ");'><?php echo $v_link_label_delete?></a>";
		html += "		</td>";
		html += "	</tr>";
		html += "</table>";

		var temp_div = document.createElement('div');
		temp_div.id = "condition_" + group_id + "_" + condition_id;
		temp_div.innerHTML = html;
		document.getElementById('group_'+group_id).appendChild(temp_div);

		return condition_id;
	}

	function delete_condition(group_id, condition_id) {
		var cond_element = document.getElementById('condition_' + group_id + '_' + condition_id);
		if (cond_element && cond_element.parentNode) {
			cond_element.parentNode.removeChild(cond_element);
		}
	}

	function load_value_fields(group_id, condition_id, condition_var) {

		if (condition_var != '') {
			if (condition_var == 'date-time') {
				// Change selects to text inputs
				clear_value_fields(group_id, condition_id);
				change_to_input(document.getElementById('value_' + group_id + '_' + condition_id + '_start'));
				change_to_input(document.getElementById('value_' + group_id + '_' + condition_id + '_stop'));
			}
			else {
				// Get start and stop selects (necessary to do this before the select check below)
				var select_start = document.getElementById('value_' + group_id + '_' + condition_id + '_start');
				var select_stop = document.getElementById('value_' + group_id + '_' + condition_id + '_stop');

				// Change inputs to selects (if necessary)
				if (select_start.tagName.toLowerCase() !== 'select') { change_to_select(select_start); }
				if (select_stop.tagName.toLowerCase() !== 'select') { change_to_select(select_stop); }

				// Get start and stop selects (necessary to do this again)
				select_start = document.getElementById('value_' + group_id + '_' + condition_id + '_start');
				select_stop = document.getElementById('value_' + group_id + '_' + condition_id + '_stop');

				// Clear options from start and stop selects
				clear_value_fields(group_id, condition_id);

				// Add blank option to top of stop select
				var blank_option = new Option('', '');
				select_stop.options[select_stop.options.length] = blank_option;

				// Load options for condition variable selected
				switch (condition_var) {

					case 'year': // Years
						for (var y = <?php echo (date('Y') - 5) ?>; y <= <?php echo (date('Y') + 10)?>; y++) {
							select_start.options[select_start.options.length] = new Option(y, y);
							select_stop.options[select_stop.options.length] = new Option(y, y);
						}
						break;

					case 'mon': // Month Names
						<?php
						for ($m = 1; $m <= 12; $m++) {
							echo "select_start.options[select_start.options.length] = new Option('".$text[strtolower(date('F', strtotime('2015-'.$m.'-01')))]."', ".$m.");\n";
							echo "select_stop.options[select_stop.options.length] = new Option('".$text[strtolower(date('F', strtotime('2015-'.$m.'-01')))]."', ".$m.");\n";
						}
						?>
						break;

					case 'yday': // Days of Year
						for (var d = 1; d <= 366; d++) {
							select_start.options[select_start.options.length] = new Option(d, d);
							select_stop.options[select_stop.options.length] = new Option(d, d);
						}
						break;

					case 'mday': // Days of Month
						for (var d = 1; d <= 31; d++) {
							select_start.options[select_start.options.length] = new Option(d, d);
							select_stop.options[select_stop.options.length] = new Option(d, d);
						}
						break;

					case 'wday': // Week Days
						<?php
						for ($d = 1; $d <= 7; $d++) {
							echo "select_start.options[select_start.options.length] = new Option('".$text[strtolower(date('l', strtotime('Sunday +'.($d-1).' days')))]."', ".$d.");\n";
							echo "select_stop.options[select_stop.options.length] = new Option('".$text[strtolower(date('l', strtotime('Sunday +'.($d-1).' days')))]."', ".$d.");\n";
						}
						?>
						break;

					case 'week': // Weeks of Year
						for (var w = 1; w <= 53; w++) {
							select_start.options[select_start.options.length] = new Option(w, w);
							select_stop.options[select_stop.options.length] = new Option(w, w);
						}
						break;

					case 'mweek': // Weeks of Month
						for (var w = 1; w <= 5; w++) {
							select_start.options[select_start.options.length] = new Option(w, w);
							select_stop.options[select_stop.options.length] = new Option(w, w);
						}
						break;

					case 'hour': // Hours of Day
						<?php
						if ($settings->get('domain', 'time_format') == '24h') {
							for ($h = 0; $h <= 23; $h++) {
								echo "select_start.options[select_start.options.length] = new Option(".$h.", ".$h.");\n";
								echo "select_stop.options[select_stop.options.length] = new Option(".$h.", ".$h.");\n";
							}
						} else {
							for ($h = 0; $h <= 23; $h++) {
								echo "select_start.options[select_start.options.length] = new Option(((".$h." != 0) ? ((".$h." >= 12) ? ((".$h." == 12) ? ".$h." : (".$h." - 12)) + ' PM' : ".$h." + ' AM') : '12 AM'), ".$h.");\n";
								echo "select_stop.options[select_stop.options.length] = new Option(((".$h." != 0) ? ((".$h." >= 12) ? ((".$h." == 12) ? ".$h." : (".$h." - 12)) + ' PM' : ".$h." + ' AM') : '12 AM'), ".$h.");\n";
							}
						}
						?>
						break;

					case 'time-of-day': // Time of Day
						<?php
						if ($settings->get('domain', 'time_format') == '24h') {
							for ($h = 0; $h <= 23; $h++) {
								for ($m = 0; $m <= 59; $m++) {
									echo "select_start.options[select_start.options.length] = new Option(('0'+'".$h."').slice(-2)+':'+('0'+'".$m."').slice(-2),pad('".$h."', 2)  + ':' + pad(".$m.", 2));\n";
									echo "select_stop.options[select_stop.options.length] = new Option(('0'+'".$h."').slice(-2)+':'+('0'+'".$m."').slice(-2),pad('".$h."', 2)  + ':' + pad(".$m.", 2));\n";
								}
							}

						} else {
							for ($h = 0; $h <= 23; $h++) {
								for ($m = 0; $m <= 59; $m++) {
									echo "select_start.options[select_start.options.length] = new Option(((".$h." != 0) ? ((".$h." >= 12) ? ((".$h." == 12) ? ".$h." : (".$h." - 12)) + ':' + pad(".$m.", 2) + ' PM' : ".$h." + ':' + pad(".$m.", 2) + ' AM') : '12:' + pad(".$m.", 2) + ' AM'), pad(".$h.", 2) + ':' + pad(".$m.", 2));\n";
									echo "select_stop.options[select_stop.options.length] = new Option(((".$h." != 0) ? ((".$h." >= 12) ? ((".$h." == 12) ? ".$h." : (".$h." - 12)) + ':' + pad(".$m.", 2) + ' PM' : ".$h." + ':' + pad(".$m.", 2) + ' AM') : '12:' + pad(".$m.", 2) + ' AM'), pad(".$h.", 2) + ':' + pad(".$m.", 2));\n";
								}
							}
						}
						// h = 23;
						// m = 59;
						// select_stop.options[select_stop.options.length] = new Option(((h != 0) ? ((h >= 12) ? ((h == 12) ? h : (h - 12)) + ':' + pad(m, 2) + ' PM' : h + ':' + pad(m, 2) + ' AM') : '12:' + pad(m, 2) + ' AM'), pad(h, 2)  + ':' + pad(m, 2));
						?>
						break;
				}

			}
		}
		else {
			clear_value_fields(group_id, condition_id);
		}

	}

	function clear_value_fields(group_id, condition_id) {
		var start_select = document.getElementById('value_' + group_id + '_' + condition_id + '_start');
		var stop_select = document.getElementById('value_' + group_id + '_' + condition_id + '_stop');
		if (start_select) start_select.options.length = 0;
		if (stop_select) stop_select.options.length = 0;
	}

	function pad(subject, max_width, pad_str) {
		pad_str = pad_str || '0';
		subject = subject + '';
		return subject.length >= max_width ? subject : new Array(max_width - subject.length + 1).join(pad_str) + subject;
	}

	function wrap_element(element, wrapper_html) {
		var temp_div = document.createElement('div');
		temp_div.innerHTML = wrapper_html;
		var wrapper = temp_div.firstElementChild;
		element.parentNode.insertBefore(wrapper, element);
		wrapper.appendChild(element);
	}

	function unwrap_element(element) {
		if (element.parentNode && element.parentNode.nodeType === 1) {
			while (element.firstChild) {
				element.parentNode.insertBefore(element.firstChild, element);
			}
			element.parentNode.removeChild(element);
		}
	}

	function change_to_input(obj) {
		var input_element = document.createElement('input');
		input_element.type = 'text';
		input_element.name = obj.name;
		input_element.id = obj.id;
		var input_id = obj.id;
		input_element.className = 'formfld datetimepicker';
		input_element.setAttribute('style', 'position: relative; width: 130px; min-width: 130px; max-width: 130px; text-align: center;');
		input_element.setAttribute('data-toggle', 'datetimepicker');
		input_element.setAttribute('data-target', '#' + input_id);
		input_element.setAttribute('onblur', "document.getElementById('" + input_id + "').dataset.hide = 'true';");
		obj.parentNode.insertBefore(input_element, obj);
		obj.parentNode.removeChild(obj);

		wrap_element(input_element, "<div style='position: relative; display: inline;'></div>"); //add parent div
		// Keep the Jquery datetimepicker as requested exception
		$('#'+input_id).datetimepicker({ format: 'YYYY-MM-DD <?php echo $time_format; ?>', });
	}

	function change_to_select(obj) {
		var select_element = document.createElement('select');
		select_element.name = obj.name;
		select_element.id = obj.id;
		select_element.className = 'formfld';
		select_element.setAttribute('style', 'width: 120px; min-width: 120px; max-width: 120px;');

		unwrap_element(obj); // Remove parent div
		obj.parentNode.insertBefore(select_element, obj);
		obj.parentNode.removeChild(obj);
	}

	function alternate_destination_required() {
		var require_default_or_alt_dest = false;
		<?php
		if (is_array($available_presets)) {
			foreach ($available_presets as $preset_number => $meh) { ?>
				var preset_chk = document.getElementById('preset_<?php echo $preset_number; ?>');
				if (preset_chk && preset_chk.checked) {
					preset_group_id_val = preset_chk.value;
					preset_destination_el = document.getElementById('dialplan_action_' + preset_group_id_val);
					if (!preset_destination_el || preset_destination_el.value == '') { require_default_or_alt_dest = true; }
				}
				<?php
			}
		}
		?>

		var default_preset_el = document.getElementById('default_preset_action');
		if (require_default_or_alt_dest && (!default_preset_el || default_preset_el.value == '')) {
			var td_alt = document.getElementById('td_alt_dest');
			if (td_alt) td_alt.className = 'vncellreq';
			return true;
		}
		else {
			var td_alt = document.getElementById('td_alt_dest');
			if (td_alt) td_alt.className = 'vncell';
			return false;
		}
	}

	function check_submit() {
		<?php
		// Output pre-submit preset check, if they exist
		if (isset($available_presets) && sizeof($available_presets) > 0) {
			?>
			if (alternate_destination_required() && document.getElementById('dialplan_anti_action').value == '') {
				display_message("<?php echo $text['message-alternate_destination_required']; ?>", 'negative', 3000);
				return false;
			}
			else {
				return true;
			}
			<?php
		}
		else {
			echo "return true;";
		}
		?>
	}

</script>

<?php
echo "<form method='post' name='frm' id='frm' onsubmit=\"return check_submit();\">\n";

echo "<div class='action_bar' id='action_bar'>\n";
echo "	<div class='heading'><b>".$text['title-time_condition']."</b></div>\n";
echo "	<div class='actions'>\n";
echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>PROJECT_PATH.'/app/time_conditions/time_conditions.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1'.($query_string ? '&'.$query_string : '')]);
if ($action == 'update' && permission_exists('dialplan_edit')) {
	echo button::create(['type'=>'button','label'=>$text['button-dialplan'],'icon'=>'list','style'=>'margin-right: 15px;','link'=>PROJECT_PATH.'/app/dialplans/dialplan_edit.php?id='.urlencode($dialplan_uuid).'&app_uuid=4b821450-926b-175a-af93-a03c441818b1']);
}
echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save']);
echo "	</div>\n";
echo "	<div style='clear: both;'></div>\n";
echo "</div>\n";

echo $text['description-time_conditions']."\n";
echo "<br /><br />\n";

echo "<div class='card'>\n";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
echo "	".$text['label-name']."\n";
echo "</td>\n";
echo "<td width='70%' class='vtable' align='left'>\n";
echo "	<input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"".escape($dialplan_name ?? null)."\">\n";
echo "	<br />\n";
echo "	".$text['description-name']."\n";
echo "<br />\n";
echo "\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "	".$text['label-extension']."\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' type='text' name='dialplan_number' id='dialplan_number' maxlength='255' value=\"".escape($dialplan_number ?? null)."\" required='required' placeholder=\"".($settings->get('time_conditions', 'extension_range') ?? '')."\">\n";
echo "	<br />\n";
echo "	".$text['description-extension']."<br />\n";
echo "</td>\n";
echo "</tr>\n";

/**
 * Adds a custom condition to the given group.
 *
 * @param object $destination     The destination object being processed.
 * @param int    $group_id        The ID of the group to which the condition is being added.
 * @param string $dialplan_action The dialplan action for the group (optional).
 */
function add_custom_condition($destination, $group_id, $dialplan_action = '') {
	global $text, $v_link_label_add;
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-settings'];
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<table border='0' cellpadding='0' cellspacing='0' style='margin: -2px;'>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable' style='width: 120px;'>".$text['label-condition']."</td>\n";
	echo "			<td class='vtable' style='width: 135px;'>".$text['label-condition_value']."</td>\n";
	echo "			<td class='vtable' style='width: 120px;'>".$text['label-condition_range']."</td>\n";
	echo "			<td style='width: 1px; text-align: right;'><a href='javascript:void(0);' onclick=\"add_condition(".$group_id.",'custom');\">".$v_link_label_add."</a></td>\n";
	echo "		</tr>\n";
	echo "		<tr>";
	echo "			<td colspan='4' style='min-width: 390px;' id='group_".$group_id."'></td>";
	echo "		</tr>";
	echo "		<tr>";
	echo "			<td colspan='4' style='padding-top: 10px; white-space: nowrap;'>";
	echo "				<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
	echo "					<tr>\n";
	echo "						<td>\n";
	// $destination = new destinations;
	echo $destination->select('dialplan', 'dialplan_action['.$group_id.']', $dialplan_action);
	echo "						</td>\n";
	echo "						<td><input class='formfld' style='margin-left: 5px; max-width: 50px; text-align: center;' type='text' name='group_".$group_id."' id='group_".$group_id."' maxlength='6' value=\"".$group_id."\"></td>\n";
	echo "					</tr>";
	echo "				</table>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>";
	echo "	<br />";
	echo "	".$text['description-settings'];
	echo "</td>\n";
	echo "</tr>\n";

}

if ($action == 'update') {
	$largest_group_id = 0;
	if (!empty($current_conditions) && is_array($current_conditions)) {
		foreach ($current_conditions as $group_id => $conditions) {
			if (empty($current_presets) || (is_array($current_presets) && !in_array($group_id, $current_presets))) {
				add_custom_condition($destination, $group_id, $dialplan_actions[$group_id]);
				if (is_array($conditions)) {
					foreach ($conditions as $cond_var => $cond_val) {
						$range_indicator = ($cond_var == 'date-time') ? '~' : '-';
						$tmp = explode($range_indicator, $cond_val);
						$cond_val_start = $tmp[0];
						$cond_val_stop = $tmp[1] ?? null;
						unset($tmp);

						// Convert minute-of-day to time-of-day values
						if ($cond_var == 'minute-of-day') {
							$cond_var = 'time-of-day';

							// Adjust time one minute earlier to account for FreeSWITCH one minute early on start condition behavior.
							$cond_val_start = $cond_val_start - 1;

							$cond_val_start = number_pad(floor($cond_val_start / 60),2).":".number_pad(fmod($cond_val_start, 60),2);
							if ($cond_val_stop != '') {
								$cond_val_stop = number_pad(floor($cond_val_stop / 60),2).":".number_pad(fmod($cond_val_stop, 60),2);
							}
						}

						echo "<script>\n";
						echo "	var condition_id = add_condition(".$group_id.",'custom');\n";
						echo "	var sel_cond = document.getElementById('variable_".$group_id."_'+condition_id);\n";
						echo "	if (sel_cond) { sel_cond.value = \"".$cond_var."\"; }\n";
						if ($cond_var == 'date-time') {
							echo "	change_to_input(document.getElementById('value_".$group_id."_'+condition_id+'_start'));\n";
							echo "	change_to_input(document.getElementById('value_".$group_id."_'+condition_id+'_stop'));\n";

							// Convert from UTC to user timezone and format appropriately
							$user_timezone = $settings->get('domain', 'time_zone', date_default_timezone_get());
							if ($server_time_zone === 'UTC') {
								$dt_start = DateTime::createFromFormat('Y-m-d H:i', $cond_val_start, new DateTimeZone('UTC'));
								if ($dt_start !== false) {
									$dt_start->setTimezone(new DateTimeZone($user_timezone));
									if ($settings->get('domain', 'time_format') != '24h') {
										$cond_val_start = $dt_start->format('Y-m-d h:i a');
									} else {
										$cond_val_start = $dt_start->format('Y-m-d H:i');
									}
								}
								if (!empty($cond_val_stop)) {
									$dt_stop = DateTime::createFromFormat('Y-m-d H:i', $cond_val_stop, new DateTimeZone('UTC'));
									if ($dt_stop !== false) {
										$dt_stop->setTimezone(new DateTimeZone($user_timezone));
										if ($settings->get('domain', 'time_format') != '24h') {
											$cond_val_stop = $dt_stop->format('Y-m-d h:i a');
										} else {
											$cond_val_stop = $dt_stop->format('Y-m-d H:i');
										}
									}
								}
							}

							// Convert to 12-hour time if needed - use the server's local time zone
							if ($server_time_zone !== 'UTC') {
								if ($settings->get('domain', 'time_format') != '24h') {
									$cond_val_start = DateTime::createFromFormat('Y-m-d H:i', $cond_val_start)->format('Y-m-d h:i a');
									$cond_val_stop = DateTime::createFromFormat('Y-m-d H:i', $cond_val_stop)->format('Y-m-d h:i a');
								}
							}

							echo "	var start_input = document.getElementById('value_".$group_id."_'+condition_id+'_start');\n";
							echo "	if(start_input) start_input.value = \"".$cond_val_start."\";\n";
							echo "	var start_output = document.getElementById('value_".$group_id."_'+condition_id+'_stop');\n";
							echo "	if(start_output) start_output.value = \"".$cond_val_stop."\";\n";
						}
						else {
							echo "	load_value_fields(".$group_id.", condition_id, '".$cond_var."');\n";
							// select the correct dropdown options
							echo "	var start_sel = document.getElementById('value_".$group_id."_'+condition_id+'_start');\n";
							echo "	for(var i=0; i<start_sel.options.length; i++){ if(start_sel.options[i].value == \"".$cond_val_start."\"){ start_sel.selectedIndex = i; break; } }\n";
							echo "	var stop_sel = document.getElementById('value_".$group_id."_'+condition_id+'_stop');\n";
							echo "	for(var i=0; i<stop_sel.options.length; i++){ if(stop_sel.options[i].value == \"".$cond_val_stop."\"){ stop_sel.selectedIndex = i; break; } }\n";
						}
						echo "</script>\n";
					}
				}
				// Used to determine largest custom group id in use
				$largest_group_id = (is_numeric($group_id) && $group_id > $largest_group_id) ? $group_id : $largest_group_id;
			}
		}
	}
}

// Add first/new set of custom condition fields
	if ($action != 'update' || ($action == 'update' && $largest_group_id == 0)) {
		$group_id = 500;
	}
	else {
		$group_id = $largest_group_id += 5;
	}
	add_custom_condition($destination, $group_id);
	echo "<script>\n";
	echo "	add_condition(".$group_id.",'custom');\n";
	if ($action == 'add' || ($action == 'update' && $largest_group_id == 0)) {
		echo "	add_condition(".$group_id.",'custom');\n";
	}
	echo "</script>\n";

// If presets exist, show the preset section
	if (isset($available_presets) && sizeof($available_presets) > 0) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-presets']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if (is_array($available_presets)) {
			foreach ($available_presets as $preset_number => $preset) {
				if (is_array($preset)) {
					foreach ($preset as $preset_name => $preset_variables) {
						$checked = !empty($current_presets) && is_array($current_presets) && in_array($preset_name, $current_presets) ? "checked='checked'" : null;
						$preset_group_id = $preset_number * 5 + 100;
						if (!empty($text['label-preset_'.$preset_name])) {
							$label_preset_name = $text['label-preset_'.$preset_name];
						}
						else {
							$label_preset_name = ucwords(str_replace(array("-", "_"), " ", $preset_name));
						}
						echo "<label><input type='checkbox' name='preset[".$preset_number."]' id='preset_".$preset_number."' value='".$preset_group_id."' onclick=\"alternate_destination_required();\" ".$checked."> <a href='javascript:void(0);' onclick=\"var pf=document.getElementById('preset_fields_".$preset_group_id."'); pf.style.display = pf.style.display==='none' ? 'block' : 'none';\">".$label_preset_name."</a></label><br>\n";
						echo "<div id='preset_fields_".$preset_group_id."' style='display: none; margin: 4px 0px 0px 20px;'>";
						echo "	<table border='0' cellpadding='2' cellspacing='0' style='margin: -2px; margin-bottom: 10px;'>\n";
						echo "		<tr>\n";
						echo "			<td class='vtable' style='width: 120px;'>".$text['label-condition']."</td>\n";
						echo "			<td class='vtable' style='width: 135px;'>".$text['label-condition_value']."</td>\n";
						echo "			<td class='vtable' style='width: 120px;'>".$text['label-condition_range']."</td>\n";
						echo "			<td style='width: 1px; text-align: right;'><a href='javascript:void(0);' onclick=\"add_condition(".$preset_group_id.",'preset');\">".$v_link_label_add."</a></td>\n";
						echo "		</tr>\n";
						echo "		<tr>";
						echo "			<td colspan='4' style='min-width: 390px;' id='group_".$preset_group_id."'></td>";
						echo "		</tr>";
						echo "		<tr>";
						echo "			<td colspan='4' style='padding-top: 10px;'>";
						echo 				$destination->select('dialplan', 'dialplan_action['.$preset_group_id.']', $dialplan_actions[$preset_name] ?? null);
						echo "			</td>";
						echo "		</tr>";
						echo "	</table>";
						echo "	<br />";
						echo "</div>";

						if (!empty($action) && $action == 'update' && !empty($current_presets) && is_array($current_presets) && in_array($preset_name, $current_presets)) {
							// Add (potentially customized) preset conditions and populate
							if (is_array($current_conditions[$preset_name])) {
								foreach ($current_conditions[$preset_name] as $cond_var => $cond_val) {
									$range_indicator = ($cond_var == 'date-time') ? '~' : '-';
									$tmp = explode($range_indicator, $cond_val);
									$cond_val_start = $tmp[0];
									$cond_val_stop = $tmp[1] ?? null;
									unset($tmp);

									// Convert minute-of-day to time-of-day values
									if ($cond_var == 'minute-of-day') {
										$cond_var = 'time-of-day';
										$cond_val_start = number_pad(floor($cond_val_start / 60),2).":".number_pad(fmod($cond_val_start, 60),2);
										if ($cond_val_stop != '') {
											$cond_val_stop = number_pad(floor($cond_val_stop / 60),2).":".number_pad(fmod($cond_val_stop, 60),2);
										}
									}

									echo "<script>\n";
									echo "	var condition_id = add_condition(".$preset_group_id.",'preset');\n";
									echo "	var sel_preset = document.getElementById('variable_".$preset_group_id."_'+condition_id);\n";
									echo "	if (sel_preset) { sel_preset.value = \"".$cond_var."\"; }\n";

									if ($cond_var == 'date-time') {
										echo "	change_to_input(document.getElementById('value_".$preset_group_id."_'+condition_id+'_start'));\n";
										echo "	change_to_input(document.getElementById('value_".$preset_group_id."_'+condition_id+'_stop'));\n";
										echo "	var start_input = document.getElementById('value_".$preset_group_id."_'+condition_id+'_start');\n";
										echo "	if(start_input) start_input.value = \"".$cond_val_start."\";\n";
										echo "	var start_output = document.getElementById('value_".$preset_group_id."_'+condition_id+'_stop');\n";
										echo "	if (start_output) start_output.value = \"".$cond_val_stop."\";\n";
									}
									else {
										echo "	load_value_fields(".$preset_group_id.", condition_id, '".$cond_var."');\n";
										echo "	var start_sel = document.getElementById('value_".$preset_group_id."_'+condition_id+'_start');\n";
										echo "	for(var i=0; i<start_sel.options.length; i++){ if(start_sel.options[i].value == \"".$cond_val_start."\"){ start_sel.selectedIndex = i; break; } }\n";
										echo "	var stop_sel = document.getElementById('value_".$preset_group_id."_'+condition_id+'_stop');\n";
										echo "	for(var i=0; i<stop_sel.options.length; i++){ if(stop_sel.options[i].value == \"".$cond_val_stop."\"){ stop_sel.selectedIndex = i; break; } }\n";
									}
									echo "</script>\n";
								}
							}
						}
						else {
							// Add default preset conditions and populate
							if (is_array($preset_variables)) {
								foreach ($preset_variables as $preset_variable => $preset_value) {
									$range_indicator = ($preset_variable == 'date-time') ? '~' : '-';
									$tmp = explode($range_indicator, $preset_value);
									$preset_value_start = $tmp[0];
									$preset_value_stop = $tmp[1] ?? null;
									unset($tmp);
									echo "<script>\n";
									echo "	var condition_id = add_condition(".$preset_group_id.",'preset');\n";
									echo "	var sel_def = document.getElementById('variable_".$preset_group_id."_'+condition_id);\n";
									echo "	if (sel_def) { sel_def.value = \"".$preset_variable."\"; }\n";
									
									if ($preset_variable == 'date-time') {
										echo "	change_to_input(document.getElementById('value_".$preset_group_id."_'+condition_id+'_start'));\n";
										echo "	change_to_input(document.getElementById('value_".$preset_group_id."_'+condition_id+'_stop'));\n";
										echo "	var start_input = document.getElementById('value_".$preset_group_id."_'+condition_id+'_start');\n";
										echo "	if(start_input) start_input.value = \"".$preset_value_start."\";\n";
										echo "	var start_output = document.getElementById('value_".$preset_group_id."_'+condition_id+'_stop');\n";
										echo "	if(start_output) start_output.value = \"".$preset_value_stop."\";\n";
									}
									else {
										echo "	load_value_fields(".$preset_group_id.", condition_id, '".$preset_variable."');\n";
										echo "	var start_sel = document.getElementById('value_".$preset_group_id."_'+condition_id+'_start');\n";
										echo "	for(var i=0; i<start_sel.options.length; i++){ if(start_sel.options[i].value == \"".$preset_value_start."\"){ start_sel.selectedIndex = i; break; } }\n";
										echo "	var stop_sel = document.getElementById('value_".$preset_group_id."_'+condition_id+'_stop');\n";
										echo "	for(var i=0; i<stop_sel.options.length; i++){ if(stop_sel.options[i].value == \"".$preset_value_stop."\"){ stop_sel.selectedIndex = i; break; } }\n";
									}
									echo "</script>\n\n";
								}
							}
						}

					}
				}
			}
		}

		echo "	<br />\n";
		echo "	<table border='0' cellpadding='2' cellspacing='0' style='margin: -2px;'>\n";
		echo "		<tr>";
		echo "			<td>";
		echo button::create(['type'=>'button','label'=>$text['button-advanced'],'icon'=>'tools','onclick'=>"$(this).fadeOut(400, function() { $('#default_preset_destination').fadeIn(400); document.getElementById('default_preset_destination_description').innerHTML += '<br>".$text['description-presets_advanced']."'; });"]);
		echo "				<span id='default_preset_destination' style='display: none;'>";
		echo 				$destination->select('dialplan', 'default_preset_action', $dialplan_action ?? null);
		echo "				</span>";
		echo "			</td>";
		echo "		</tr>";
		echo "	</table>";
		echo "	<br />";
		echo "	<span id='default_preset_destination_description'>".$text['description-presets']."</span><br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td id='td_alt_dest' class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-alternate-destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	".$destination->select('dialplan', 'dialplan_anti_action', $dialplan_anti_action ?? null);
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-time_zone']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='dialplan_time_zone' name='dialplan_time_zone' class='formfld searchable_select' style=''>\n";
	echo "			<option value=''></option>\n";
	//$list = DateTimeZone::listAbbreviations();
	$time_zone_identifiers = DateTimeZone::listIdentifiers();
	$previous_category = '';
	$x = 0;
	foreach ($time_zone_identifiers as $key => $row) {
		$time_zone = explode("/", $row);
		$category = $time_zone[0];
		if ($category != $previous_category) {
			if ($x > 0) {
				echo "		</optgroup>\n";
			}
			echo "		<optgroup label='$category'>\n";
		}
		$selected = (!empty($dialplan_time_zone) && $row == $dialplan_time_zone) ? "selected" : null;
		echo "			<option value='".escape($row)."' $selected>".escape($row)."</option>\n";
		$previous_category = $category;
		$x++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-time_zone']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_order' class='formfld'>\n";
	for ($i = 300; $i <= 999; $i += 10) {
		$padded_i = str_pad($i, 3, '0', STR_PAD_LEFT);
		$selected = !empty($dialplan_order) && $dialplan_order == $i ? "selected='selected'" : null;
		echo "<option value='".$padded_i."' ".$selected.">".$padded_i."</option>\n";
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('time_condition_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='domain_uuid'>\n";
		if (empty($domain_uuid)) {
			echo "	<option value='' selected='selected'>".$text['label-global']."</option>\n";
		}
		else {
			echo "	<option value=''>".$text['label-global']."</option>\n";
		}
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "	<option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
			}
			else {
				echo "	<option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
			}
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('time_condition_context')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='dialplan_context' maxlength='255' value=\"".escape($dialplan_context)."\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-enter-context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='dialplan_enabled' name='dialplan_enabled'>\n";
	echo "		<option value='true' ".($dialplan_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($dialplan_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"".escape($dialplan_description ?? null)."\">\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "</div>\n";
	echo "<br /><br />\n";

	if ($action == "update") {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

// Include the footer
	require_once "resources/footer.php";

?>
