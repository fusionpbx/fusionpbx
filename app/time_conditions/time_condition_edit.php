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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('time_condition_add') || permission_exists('time_condition_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "resources/header.php";


//add multi-lingual support
	$language = new text;
	$text = $language->get();

//initialize the destinations object
	$destination = new destinations;

//load available presets
	$preset_region = "preset_".$_SESSION['time_conditions']['region']['text'];
	foreach ($_SESSION['time_conditions'][$preset_region] as $json) {
		$available_presets[] = json_decode($json, true);
	}
	unset($preset_region);

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$dialplan_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the post variables
	if (count($_POST) > 0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_number = check_str($_POST["dialplan_number"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);

		$dialplan_anti_action = check_str($_POST["dialplan_anti_action"]);
		$dialplan_anti_action_array = explode(":", $dialplan_anti_action);
		$dialplan_anti_action_app = array_shift($dialplan_anti_action_array);
		$dialplan_anti_action_data = join(':', $dialplan_anti_action_array);

		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
	}

	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	//check for all required data
		if (strlen($domain_uuid) == 0) { $msg .= $text['label-required-domain_uuid']."<br>\n"; }
 		if (strlen($dialplan_name) == 0) { $msg .= $text['label-required-dialplan_name']."<br>\n"; }
 		if (strlen($dialplan_number) == 0) { $msg .= $text['label-required-dialplan_number']."<br>\n"; }
 		//if (strlen($dialplan_action) == 0) { $msg .= $text['label-required-action']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

	//remove the invalid characters from the dialplan name
		$dialplan_name = str_replace(' ', '_', $dialplan_name);
		$dialplan_name = str_replace('/', '', $dialplan_name);

	//start the atomic transaction
		$count = $db->exec("BEGIN;"); //returns affected rows

	//process main dialplan entry
		if ($action == "add") {

			//add main dialplan entry
				$dialplan_uuid = uuid();
				$sql = "insert into v_dialplans ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "app_uuid, ";
				$sql .= "dialplan_name, ";
				$sql .= "dialplan_number, ";
				$sql .= "dialplan_order, ";
				$sql .= "dialplan_continue, ";
				$sql .= "dialplan_context, ";
				$sql .= "dialplan_enabled, ";
				$sql .= "dialplan_description ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$domain_uuid."', ";
				$sql .= "'".$dialplan_uuid."', ";
				$sql .= "'4b821450-926b-175a-af93-a03c441818b1', ";
				$sql .= "'".$dialplan_name."', ";
				$sql .= "'".$dialplan_number."', ";
				$sql .= "'".$dialplan_order."', ";
				$sql .= "'true', ";
				$sql .= "'".$_SESSION['context']."', ";
				$sql .= "'".$dialplan_enabled."', ";
				$sql .= "'".$dialplan_description."' ";
				$sql .= ")";

				//execute query
				$db->exec(check_sql($sql));
				unset($sql);
		}
		else if ($action == "update") {
			//update main dialplan entry
				$sql = "update v_dialplans set ";
				$sql .= "dialplan_name = '".$dialplan_name."', ";
				$sql .= "dialplan_number = '".$dialplan_number."', ";
				$sql .= "dialplan_order = '".$dialplan_order."', ";
				$sql .= "dialplan_continue = 'true', ";
				$sql .= "dialplan_context = '".$_SESSION['context']."', ";
				$sql .= "dialplan_enabled = '".$dialplan_enabled."', ";
				$sql .= "dialplan_description = '".$dialplan_description."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);

			//delete existing dialplan details
				$sql = "delete from v_dialplan_details ";
				$sql .= "where dialplan_uuid = '".$dialplan_uuid."'; ";
				$db->query($sql);
				unset($sql);
		}

	//initialize dialplan detail group and order numbers
		$dialplan_detail_group = 0;
		$dialplan_detail_order = 0;

	//clean up array
		//remove presets not checked, restructure variable array
		foreach ($_REQUEST['variable']['preset'] as $group_id => $conditions) {
			if (!in_array($group_id, $_REQUEST['preset'])) {
				unset($_REQUEST['variable']['preset'][$group_id]);
				unset($_REQUEST['value'][$group_id]);
				unset($_REQUEST['dialplan_action'][$group_id]);
				continue;
			}
			$_REQUEST['variable'][$group_id] = $conditions;
		}
		foreach ($_REQUEST['variable']['custom'] as $group_id => $conditions) {
			$_REQUEST['variable'][$group_id] = $conditions;
		}
		unset($_REQUEST['variable']['custom'], $_REQUEST['variable']['preset']);

		//remove invalid conditions and values by checking conditions
		foreach ($_REQUEST['variable'] as $group_id => $conditions) {
			foreach ($conditions as $condition_id => $condition_variable) {
				if ($condition_variable == '') {
					unset($_REQUEST['variable'][$group_id][$condition_id]);
					unset($_REQUEST['value'][$group_id][$condition_id]);
				}
			}
		}

		//remove invalid conditions and values by checking start value
		foreach ($_REQUEST['value'] as $group_id => $values) {
			foreach ($values as $value_id => $value_range) {
				if ($value_range['start'] == '') {
					unset($_REQUEST['variable'][$group_id][$value_id]);
					unset($_REQUEST['value'][$group_id][$value_id]);
				}
			}
		}

		//remove any empty groups (where conditions no longer exist)
		foreach ($_REQUEST['variable'] as $group_id => $conditions) {
			if (sizeof($conditions) == 0) {
				unset($_REQUEST['variable'][$group_id]);
				unset($_REQUEST['value'][$group_id]);
				unset($_REQUEST['dialplan_action'][$group_id]);
			}
		}

		//remove groups where an action (or default_preset_action - if a preset group - or dialplan_anti_action) isn't defined
		foreach ($_REQUEST['variable'] as $group_id => $meh) {
			if (
				(in_array($group_id, $_REQUEST['preset']) && $_REQUEST['dialplan_action'][$group_id] == '' && $_REQUEST['default_preset_action'] == '' && $_REQUEST['dialplan_anti_action'] == '') ||
				(!in_array($group_id, $_REQUEST['preset']) && $_REQUEST['dialplan_action'][$group_id] == '')
				) {
				unset($_REQUEST['variable'][$group_id]);
				unset($_REQUEST['value'][$group_id]);
				unset($_REQUEST['dialplan_action'][$group_id]);
				foreach ($_REQUEST['preset'] as $preset_id => $preset_group_id) {
					if ($group_id == $preset_group_id) { unset($_REQUEST['preset'][$preset_id]); }
				}
			}
		}

	//process conditions
		$conditions_exist = false;

		//begin insert query for custom and preset conditions
		$sql = "insert into v_dialplan_details ";
		$sql .= "( ";
		$sql .= "domain_uuid, ";
		$sql .= "dialplan_uuid, ";
		$sql .= "dialplan_detail_uuid, ";
		$sql .= "dialplan_detail_tag, ";
		$sql .= "dialplan_detail_type, ";
		$sql .= "dialplan_detail_data, ";
		$sql .= "dialplan_detail_break, ";
		$sql .= "dialplan_detail_inline, ";
		$sql .= "dialplan_detail_group, ";
		$sql .= "dialplan_detail_order ";
		$sql .= ") ";
		$sql .= "values ";

		//add conditions
		foreach ($_REQUEST['variable'] as $group_id => $conditions) {

			$group_conditions_exist[$group_id] = false;

			//determine if preset
			$is_preset = (in_array($group_id, $_REQUEST['preset'])) ? true : false;

			//set group and order number

			$dialplan_detail_group_user = check_str($_POST["group_$group_id"]);
			if($dialplan_detail_group_user!='') {
				$dialplan_detail_group = $dialplan_detail_group_user;
			} else {
				$dialplan_detail_group = $group_id;
			}

			$dialplan_detail_order = 0;

			foreach ($conditions as $cond_num => $cond_var) {
				if ($cond_var != '') {
					$cond_start = $_REQUEST['value'][$group_id][$cond_num]['start'];
					$cond_stop = $_REQUEST['value'][$group_id][$cond_num]['stop'];

					//convert time-of-day to minute-of-day (due to inconsistencies with time-of-day on some systems)
					if ($cond_var == 'time-of-day') {
						$cond_var = 'minute-of-day';
						$array_cond_start = explode(':', $cond_start);
						$cond_start = ($array_cond_start[0] * 60) + $array_cond_start[1];
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
						//add destination number condition
						$dialplan_detail_order += 10;
						$sql .= ($conditions_exist) ? ", ( " : "( ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".$dialplan_uuid."', ";
						$sql .= "'".uuid()."', ";
						$sql .= "'condition', ";
						$sql .= "'destination_number', ";
						$sql .= "'^".$dialplan_number."$', ";
						$sql .= "null, ";
						$sql .= "null, ";
						$sql .= "'".$dialplan_detail_group."', ";
						$sql .= "'".$dialplan_detail_order."' ";
						$sql .= ") ";
					}

					//add condition to query string
					$dialplan_detail_order += 10;
					$sql .= ", ( ";
					$sql .= "'".$domain_uuid."', ";
					$sql .= "'".$dialplan_uuid."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'condition', ";
					$sql .= "'".$cond_var."', ";
					$sql .= "'".$cond_value."', ";
					$sql .= "'never', ";
					$sql .= "null, ";
					$sql .= "'".$dialplan_detail_group."', ";
					$sql .= "'".$dialplan_detail_order."' ";
					$sql .= ") ";

					$conditions_exist = true;
					$group_conditions_exist[$group_id] = true;
				} //if
			} //for each


			//continue adding to query only if conditions exist in current group
			if ($group_conditions_exist[$group_id]) {

				//determine group action app and data
				$dialplan_action = check_str($_REQUEST["dialplan_action"][$group_id]);
				if ($dialplan_action == '') {
					if ($is_preset) {
						if (check_str($_REQUEST['default_preset_action']) != '') {
							$dialplan_action = check_str($_REQUEST['default_preset_action']);
						}
						else if (check_str($_REQUEST['dialplan_anti_action']) != '') {
							$dialplan_action = check_str($_REQUEST['dialplan_anti_action']);
						}
					}
				}

				if ($dialplan_action != '') {
					//if preset, set log variable
					if ($is_preset) {
						foreach ($_REQUEST['preset'] as $preset_number => $preset_group_id) {
							if ($group_id == $preset_group_id) {
								foreach ($available_presets[$preset_number] as $available_preset_name => $meh) {
									$dialplan_detail_order += 10;
									$sql .= ", ( ";
									$sql .= "'".$domain_uuid."', ";
									$sql .= "'".$dialplan_uuid."', ";
									$sql .= "'".uuid()."', ";
									$sql .= "'action', ";
									$sql .= "'set', ";
									$sql .= "'preset=".$available_preset_name."', ";
									$sql .= "null, ";
									$sql .= "'true', ";
									$sql .= "'".$dialplan_detail_group."', ";
									$sql .= "'".$dialplan_detail_order."' ";
									$sql .= ") ";
								}
							}
						}
					}

					//parse group app and data
					if (substr_count($dialplan_action, ":") > 0) {
						$dialplan_action_array = explode(":", $dialplan_action);
						$dialplan_action_app = array_shift($dialplan_action_array);
						$dialplan_action_data = join(':', $dialplan_action_array);
					}
					else {
						$dialplan_action_app = $dialplan_action;
						$dialplan_action_data = '';
					}

					//add group action to query
					$dialplan_detail_order += 10;
					$sql .= ", ( ";
					$sql .= "'".$domain_uuid."', ";
					$sql .= "'".$dialplan_uuid."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'action', ";
					$sql .= "'".$dialplan_action_app."', ";
					$sql .= "'".$dialplan_action_data."', ";
					$sql .= "null, ";
					$sql .= "null, ";
					$sql .= "'".$dialplan_detail_group."', ";
					$sql .= "'".$dialplan_detail_order."' ";
					$sql .= ") ";
				}

			}

		} //for each


	//add to query for default anti-action (if defined)
		if (strlen($dialplan_anti_action_app) > 0) {

			//increment group number, reset order number
			$dialplan_detail_group = 999;
			$dialplan_detail_order = 0;

			//add destination number condition
			$dialplan_detail_order += 10;
			$sql .= ", ( ";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'".$dialplan_uuid."', ";
			$sql .= "'".uuid()."', ";
			$sql .= "'condition', ";
			$sql .= "'destination_number', ";
			$sql .= "'^".$dialplan_number."$', ";
			$sql .= "null, ";
			$sql .= "null, ";
			$sql .= "'".$dialplan_detail_group."', ";
			$sql .= "'".$dialplan_detail_order."' ";
			$sql .= ") ";

			//add anti-action
			$dialplan_detail_order += 10;
			$sql .= ", ( ";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'".$dialplan_uuid."', ";
			$sql .= "'".uuid()."', ";
			$sql .= "'action', ";
			$sql .= "'".$dialplan_anti_action_app."', ";
			$sql .= "'".$dialplan_anti_action_data."', ";
			$sql .= "null, ";
			$sql .= "null, ";
			$sql .= "'".$dialplan_detail_group."', ";
			$sql .= "'".$dialplan_detail_order."' ";
			$sql .= ") ";

		}

	//execute query
		if ($conditions_exist) {
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//commit the atomic transaction
		$count = $db->exec("COMMIT;"); //returns affected rows

	//clear the cache
		$cache = new cache;
		$cache->delete("dialplan:".$_SESSION["context"]);

	//synchronize the xml config
		save_dialplan_xml();

	//set the message
		if ($action == "add") {
			$_SESSION['message'] = $text['message-add'];
		}
		else if ($action == "update") {
			$_SESSION['message'] = $text['message-update'];
		}
		header("Location: time_condition_edit.php?id=".$dialplan_uuid.(($app_uuid != '') ? "&app_uuid=".$app_uuid : null));
		return;

	} //end if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)


//get existing data to pre-populate form
	if ($dialplan_uuid != '' && $_POST["persistformvar"] != "true") {

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", 'temp');
			$p->add("dialplan_detail_add", 'temp');
			$p->add("dialplan_edit", 'temp');
			$p->add("dialplan_detail_edit", 'temp');

		//get main dialplan entry
			$orm = new orm;
			$orm->name('dialplans');
			$orm->uuid($dialplan_uuid);
			$result = $orm->find()->get();
			//$message = $orm->message;
			foreach ($result as &$row) {
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
			unset ($prep_statement);

		//remove the temporary permission
			$p->delete("dialplan_add", 'temp');
			$p->delete("dialplan_detail_add", 'temp');
			$p->delete("dialplan_edit", 'temp');
			$p->delete("dialplan_detail_edit", 'temp');

		//get dialplan detail conditions
			$sql = "select dialplan_detail_group, dialplan_detail_tag, dialplan_detail_type, dialplan_detail_data from v_dialplan_details ";
			$sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
			$sql .= "and ";
			$sql .= "( ";
			$sql .= "	( ";
			$sql .= "		dialplan_detail_tag = 'condition' ";
			$sql .= "		and dialplan_detail_type in ('year','mon','mday','wday','yday','week','mweek','hour','minute','minute-of-day','time-of-day','date-time') ";
			$sql .= "	) ";
			$sql .= "	or ( ";
			$sql .= "		dialplan_detail_tag = 'action' ";
			$sql .= "		and dialplan_detail_data not like 'preset=%' ";
			$sql .= "	) ";
			$sql .= ") ";
			$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);

		//load current conditions into array (combined by group), and retrieve action and anti-action
			$c = 0;
			foreach ($result as $row) {
				if ($row['dialplan_detail_tag'] == 'action') {
					if ($row['dialplan_detail_group'] == '999') {
						$dialplan_anti_action = $row['dialplan_detail_type'].(($row['dialplan_detail_data'] != '') ? ':'.$row['dialplan_detail_data'] : null);
					}
					else {
						$dialplan_actions[$row['dialplan_detail_group']] = $row['dialplan_detail_type'].(($row['dialplan_detail_data'] != '') ? ':'.$row['dialplan_detail_data'] : null);
					}
				}
				else if ($row['dialplan_detail_tag'] == 'condition') {
					$current_conditions[$row['dialplan_detail_group']][$row['dialplan_detail_type']] = $row['dialplan_detail_data'];
				}
			}

		//loop through available presets (if any)
			if (isset($available_presets) && sizeof($available_presets) > 0) {
				foreach ($available_presets as $preset_number => $preset) {
					foreach ($preset as $preset_name => $preset_variables) {
						//loop through each condition group
						foreach ($current_conditions as $group_id => $condition_variables) {
							$matches = 0;
							foreach ($condition_variables as $condition_variable_name => $condition_variable_value) {
								//count matching variable values
								if ($preset_variables[$condition_variable_name] == $condition_variable_value) { $matches++; }
							}
							//if all preset variables found, then condition is a preset
							if ($matches == sizeof($preset_variables)) {
								$current_presets[$preset_number] = $group_id;
							}
						}
					}
				}
			}

	}

?>

<script type="text/javascript">

	function add_condition(group_id, type) {
		condition_id = Math.floor((Math.random() * 1000) + 1);
		html = "<table cellpadding='0' cellspacing='0' border='0' style='margin-top: 3px;' width='100%'>";
		html += "	<tr>";
		html += "		<td style='vertical-align: middle; min-width: 390px;' width='100%'>";
		html += "			<select class='formfld' style='width: 120px;' name='variable[" + type + "][" + group_id + "][" + condition_id + "]' id='variable_" + group_id + "_" + condition_id + "' onchange=\"load_value_fields(" + group_id + ", " + condition_id + ", this.options[this.selectedIndex].value);\">";
		html += "				<option value=''></option>";
		<?php
		$time_condition_vars["year"] = $text['label-year'];
		$time_condition_vars["mon"] = $text['label-month'];
		$time_condition_vars["mday"] = $text['label-day-of-month'];
		$time_condition_vars["wday"] = $text['label-day-of-week'];
		//$time_condition_vars["yday"] = $text['label-day-of-year'];
		$time_condition_vars["week"] = $text['label-week-of-year'];
		$time_condition_vars["mweek"] = $text['label-week-of-month'];
		$time_condition_vars["hour"] = $text['label-hour-of-day'];
		//$time_condition_vars["minute"] = $text['label-minute-of-hour'];
		//$time_condition_vars["minute-of-day"] = $text['label-minute-of-day'];
		$time_condition_vars["time-of-day"] = $text['label-time-of-day'];
		$time_condition_vars["date-time"] = $text['label-date-and-time'];
		foreach ($time_condition_vars as $var_name => $var_label) {
			echo "html += \"	<option value='".$var_name."' ".(($custom_conditions[$c]['var'] == $var_name) ? "selected='selected'" : null).">".$var_label."</option>\";";
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
		var c = document.getElementById('condition_' + group_id + '_' + condition_id);
		c.parentNode.removeChild(c);
	}

	function load_value_fields(group_id, condition_id, condition_var) {

		if (condition_var != '') {
			if (condition_var == 'date-time') {
				//change selects to text inputs
				clear_value_fields(group_id, condition_id);
				change_to_input(document.getElementById('value_' + group_id + '_' + condition_id + '_start'));
				change_to_input(document.getElementById('value_' + group_id + '_' + condition_id + '_stop'));
			}
			else {
				//get start and stop selects (necessary to do this before the select check below)
				sel_start = document.getElementById('value_' + group_id + '_' + condition_id + '_start');
				sel_stop = document.getElementById('value_' + group_id + '_' + condition_id + '_stop');

				//change inputs to selects (if necessary)
				if (!$(sel_start).is("select")) { change_to_select(sel_start); }
				if (!$(sel_stop).is("select")) { change_to_select(sel_stop); }

				//get start and stop selects (necessary to do this again)
				sel_start = document.getElementById('value_' + group_id + '_' + condition_id + '_start');
				sel_stop = document.getElementById('value_' + group_id + '_' + condition_id + '_stop');

				//clear options from start and stop selects
				clear_value_fields(group_id, condition_id);

				//add blank option to top of stop select
				sel_stop.options[sel_stop.options.length] = new Option('', '');

				//load options for condition variable selected
				switch (condition_var) {

					case 'year': //years
						for (y = <?php echo date('Y')?>; y <= <?php echo (date('Y') + 10)?>; y++) {
							sel_start.options[sel_start.options.length] = new Option(y, y);
							sel_stop.options[sel_stop.options.length] = new Option(y, y);
						}
						break;

					case 'mon': //month names
						<?php
						for ($m = 1; $m <= 12; $m++) {
							echo "sel_start.options[sel_start.options.length] = new Option('".date('F', strtotime('2015-'.number_pad($m,2).'-01'))."', ".$m.");\n";
							echo "sel_stop.options[sel_stop.options.length] = new Option('".date('F', strtotime('2015-'.number_pad($m,2).'-01'))."', ".$m.");\n";
						}
						?>
						break;

					case 'yday': //days of year
						for (d = 1; d <= 366; d++) {
							sel_start.options[sel_start.options.length] = new Option(d, d);
							sel_stop.options[sel_stop.options.length] = new Option(d, d);
						}
						break;

					case 'mday': //days of month
						for (d = 1; d <= 31; d++) {
							sel_start.options[sel_start.options.length] = new Option(d, d);
							sel_stop.options[sel_stop.options.length] = new Option(d, d);
						}
						break;

					case 'wday': //week days
						<?php
						for ($d = 1; $d <= 7; $d++) {
							echo "sel_start.options[sel_start.options.length] = new Option('".date('l', strtotime('Sunday +'.($d-1).' days'))."', ".$d.");\n";
							echo "sel_stop.options[sel_stop.options.length] = new Option('".date('l', strtotime('Sunday +'.($d-1).' days'))."', ".$d.");\n";
						}
						?>
						break;

					case 'week': //weeks of year
						for (w = 1; w <= 53; w++) {
							sel_start.options[sel_start.options.length] = new Option(w, w);
							sel_stop.options[sel_stop.options.length] = new Option(w, w);
						}
						break;

					case 'mweek': //weeks of month
						for (w = 1; w <= 5; w++) {
							sel_start.options[sel_start.options.length] = new Option(w, w);
							sel_stop.options[sel_stop.options.length] = new Option(w, w);
						}
						break;

					case 'hour': //hours of day
						for (h = 0; h <= 23; h++) {
							sel_start.options[sel_start.options.length] = new Option(((h != 0) ? ((h >= 12) ? ((h == 12) ? h : (h - 12)) + ' PM' : h + ' AM') : '12 AM'), h);
							sel_stop.options[sel_stop.options.length] = new Option(((h != 0) ? ((h >= 12) ? ((h == 12) ? h : (h - 12)) + ' PM' : h + ' AM') : '12 AM'), h);
						}
						break;

					case 'time-of-day': //time of day
						for (h = 0; h <= 23; h++) {
							for (m = 0; m <= 55; m += 5) {
								sel_start.options[sel_start.options.length] = new Option(((h != 0) ? ((h >= 12) ? ((h == 12) ? h : (h - 12)) + ':' + pad(m, 2) + ' PM' : h + ':' + pad(m, 2) + ' AM') : '12:' + pad(m, 2) + ' AM'), pad(h, 2) + ':' + pad(m, 2));
								sel_stop.options[sel_stop.options.length] = new Option(((h != 0) ? ((h >= 12) ? ((h == 12) ? h : (h - 12)) + ':' + pad(m, 2) + ' PM' : h + ':' + pad(m, 2) + ' AM') : '12:' + pad(m, 2) + ' AM'), pad(h, 2)  + ':' + pad(m, 2));
							}
						}
						break;

				}

			}
		}
		else {
			clear_value_fields(group_id, condition_id);
		}

	}

	function clear_value_fields(group_id, condition_id) {
		document.getElementById('value_' + group_id + '_' + condition_id + '_start').options.length = 0;
		document.getElementById('value_' + group_id + '_' + condition_id + '_stop').options.length = 0;
	}

	function pad(subject, max_width, pad_str) {
		pad_str = pad_str || '0';
		subject = subject + '';
		return subject.length >= max_width ? subject : new Array(max_width - subject.length + 1).join(pad_str) + subject;
	}

	function change_to_input(obj) {
		tb = document.createElement('input');
		tb.type = 'text';
		tb.name = obj.name;
		tb.id = obj.id;
		tb_id = obj.id;
		tb.className = 'formfld datetimepicker';
		tb.setAttribute('style', 'position: relative; width: 120px; min-width: 120px; max-width: 120px; text-align: center;');
		obj.parentNode.insertBefore(tb, obj);
		obj.parentNode.removeChild(obj);
		$('#'+tb_id).wrap("<div style='position: relative; display: inline;'></div>"); //add parent div
		$('#'+tb_id).datetimepicker({ format: 'YYYY-MM-DD HH:mm', showTodayButton: true, showClear: true, showClose: true, });
	}

	function change_to_select(obj) {
		sb = document.createElement('select');
		sb.name = obj.name;
		sb.id = obj.id;
		tb_id = obj.id;
		sb.className = 'formfld';
		sb.setAttribute('style', 'width: 120px; min-width: 120px; max-width: 120px;');
		$('#'+tb_id).unwrap(); //remove parent div
		obj.parentNode.insertBefore(sb, obj);
		obj.parentNode.removeChild(obj);
	}

	function alternate_destination_required() {
		require_default_or_alt_destination = false;
		<?php foreach ($available_presets as $preset_number => $meh) { ?>
			if (document.getElementById('preset_<?php echo $preset_number; ?>').checked) {
				preset_group_id = document.getElementById('preset_<?php echo $preset_number; ?>').value;
				preset_destination = $('#dialplan_action_' + preset_group_id).val();
				if (preset_destination == '') { require_default_or_alt_destination = true; }
			}
		<?php } ?>

		if (require_default_or_alt_destination && $('#default_preset_action').val() == '') {
			$('#td_alt_dest').attr('class', 'vncellreq');
			return true;
		}
		else {
			$('#td_alt_dest').attr('class', 'vncell');
			return false;
		}
	}

	function check_submit() {
		<?php
		// output pre-submit preset check, if they exist
		if (isset($available_presets) && sizeof($available_presets) > 0) {
			?>
			if (alternate_destination_required() && $('#dialplan_anti_action').val() == '') {
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
echo "<form method='post' name='frm' action='' onsubmit=\"return check_submit();\">\n";

echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
echo "	<tr>\n";
echo "		<td align='left' valign='top'>\n";
echo "			<span class='title'>".$text['title-time_conditions']."</span>";
echo "			<br /><br />\n";
echo "			".$text['description-time_conditions']."\n";
echo "		</td>\n";
echo "		<td align='right' valign='top'>\n";
echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='".PROJECT_PATH."/app/time_conditions/time_conditions.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1'\" value='".$text['button-back']."'>\n";
if (if_group("superadmin") && $action == 'update') {
	echo "		<input type='button' class='btn' name='' alt='".$text['button-dialplan']."' onclick=\"document.location.href='".PROJECT_PATH."/app/dialplan/dialplan_edit.php?id=".$dialplan_uuid."&app_uuid=4b821450-926b-175a-af93-a03c441818b1'\" value='".$text['button-dialplan']."'>\n";
}
echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>";
echo "<br />\n";

echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td width='20%' class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-name']."\n";
echo "</td>\n";
echo "<td width='80%' class='vtable' align='left'>\n";
echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"".$dialplan_name."\">\n";
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
echo "	<input class='formfld' type='text' name='dialplan_number' id='dialplan_number' maxlength='255' value=\"".$dialplan_number."\">\n";
echo "	<br />\n";
echo "	".$text['description-extension']."<br />\n";
echo "</td>\n";
echo "</tr>\n";

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
	//$destination = new destinations;
	echo $destination->select('dialplan', 'dialplan_action['.$group_id.']', $dialplan_action);
	echo "						</td>\n";
	echo "						<td width='100%'><input class='formfld' style='margin-left: 5px;' type='text' name='group_".$group_id."' id='group_".$group_id."' maxlength='255' value=\"".$group_id."\"></td>\n";
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
	foreach ($current_conditions as $group_id => $conditions) {
		if (!is_array($current_presets) || (is_array($current_presets) && !in_array($group_id, $current_presets))) {
			add_custom_condition($destination, $group_id, $dialplan_actions[$group_id]);
			foreach ($conditions as $cond_var => $cond_val) {
				$range_indicator = ($cond_var == 'date-time') ? '~' : '-';
				$tmp = explode($range_indicator, $cond_val);
				$cond_val_start = $tmp[0];
				$cond_val_stop = $tmp[1];
				unset($tmp);

				//convert minute-of-day to time-of-day values
				if ($cond_var == 'minute-of-day') {
					$cond_var = 'time-of-day';
					$cond_val_start = number_pad(floor($cond_val_start / 60),2).":".number_pad(fmod($cond_val_start, 60),2);
					if ($cond_val_stop != '') {
						$cond_val_stop = number_pad(floor($cond_val_stop / 60),2).":".number_pad(fmod($cond_val_stop, 60),2);
					}
				}

				echo "<script>";
				echo "	condition_id = add_condition(".$group_id.",'custom');\n";
				echo "	$('#variable_".$group_id."_' + condition_id + ' option[value=\"".$cond_var."\"]').prop('selected', true);\n";
				if ($cond_var == 'date-time') {
					echo "	change_to_input(document.getElementById('value_".$group_id."_' + condition_id + '_start'));\n";
					echo "	change_to_input(document.getElementById('value_".$group_id."_' + condition_id + '_stop'));\n";
					echo "	$('#value_".$group_id."_' + condition_id + '_start').val('".$cond_val_start."');\n";
					echo "	$('#value_".$group_id."_' + condition_id + '_stop').val('".$cond_val_stop."');\n";
				}
				else {
					echo "	load_value_fields(".$group_id.", condition_id, '".$cond_var."');\n";
					echo "	$('#value_".$group_id."_' + condition_id + '_start option[value=\"".$cond_val_start."\"]').prop('selected', true);\n";
					echo "	$('#value_".$group_id."_' + condition_id + '_stop option[value=\"".$cond_val_stop."\"]').prop('selected', true);\n";
				}
				echo "</script>";
			}
			//used to determine largest custom group id in use
			$largest_group_id = ($group_id > $largest_group_id) ? $group_id : $largest_group_id;
		}
	}
}

//add first/new set of custom condition fields
	if ($action != 'update' || ($action == 'update' && $largest_group_id == 0)) {
		$group_id = 500;
	}
	else {
		$group_id = $largest_group_id += 5;
	}
	add_custom_condition($destination, $group_id);
	echo "<script>";
	echo "	add_condition(".$group_id.",'custom');";
	if ($action == 'add' || ($action == 'update' && $largest_group_id == 0)) {
		echo "	add_condition(".$group_id.",'custom');";
	}
	echo "</script>";

//if presets exist, show the preset section
	if (isset($available_presets) && sizeof($available_presets) > 0) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-presets']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		foreach ($available_presets as $preset_number => $preset) {
			foreach ($preset as $preset_name => $preset_variables) {
				$checked = (is_array($current_presets) && $current_presets[$preset_number] != '') ? "checked='checked'" : null;
				$preset_group_id = ($checked) ? $current_presets[$preset_number] : $preset_group_id = $preset_number * 5 + 100;
				if (strlen($text['label-preset_'.$preset_name]) > 0) {
					$label_preset_name = $text['label-preset_'.$preset_name];
				}
				else {
					$label_preset_name = ucwords(str_replace(array("-", "_"), " ", $preset_name));
				}
				echo "<label><input type='checkbox' name='preset[".$preset_number."]' id='preset_".$preset_number."' value='".$preset_group_id."' onclick=\"alternate_destination_required();\" ".$checked."> <a href='javascript:void(0);' onclick=\"$('#preset_fields_".$preset_group_id."').slideToggle(400);\">".$label_preset_name."</a></label><br>\n";
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
				echo 				$destination->select('dialplan', 'dialplan_action['.$preset_group_id.']', $dialplan_action);
				echo "			</td>";
				echo "		</tr>";
				echo "	</table>";
				echo "	<br />";
				echo "</div>";
				if ($action == 'update' && is_array($current_presets) && $current_presets[$preset_number] != '') {
					//add (potentially customized) preset conditions and populate
					foreach ($current_conditions[$preset_group_id] as $cond_var => $cond_val) {
						$range_indicator = ($cond_var == 'date-time') ? '~' : '-';
						$tmp = explode($range_indicator, $cond_val);
						$cond_val_start = $tmp[0];
						$cond_val_stop = $tmp[1];
						unset($tmp);

						//convert minute-of-day to time-of-day values
						if ($cond_var == 'minute-of-day') {
							$cond_var = 'time-of-day';
							$cond_val_start = number_pad(floor($cond_val_start / 60),2).":".number_pad(fmod($cond_val_start, 60),2);
							if ($cond_val_stop != '') {
								$cond_val_stop = number_pad(floor($cond_val_stop / 60),2).":".number_pad(fmod($cond_val_stop, 60),2);
							}
						}

						echo "<script>\n";
						echo "	condition_id = add_condition(".$preset_group_id.",'preset');\n";
						echo "	$('#variable_".$preset_group_id."_' + condition_id + ' option[value=\"".$cond_var."\"]').prop('selected', true);\n";
						if ($cond_var == 'date-time') {
							echo "	change_to_input(document.getElementById('value_".$preset_group_id."_' + condition_id + '_start'));\n";
							echo "	change_to_input(document.getElementById('value_".$preset_group_id."_' + condition_id + '_stop'));\n";
							echo "	$('#value_".$preset_group_id."_' + condition_id + '_start').val('".$cond_val_start."');\n";
							echo "	$('#value_".$preset_group_id."_' + condition_id + '_stop').val('".$cond_val_stop."');\n";
						}
						else {
							echo "	load_value_fields(".$preset_group_id.", condition_id, '".$cond_var."');\n";
							echo "	$('#value_".$preset_group_id."_' + condition_id + '_start option[value=\"".$cond_val_start."\"]').prop('selected', true);\n";
							echo "	$('#value_".$preset_group_id."_' + condition_id + '_stop option[value=\"".$cond_val_stop."\"]').prop('selected', true);\n";
						}
						echo "	$('#dialplan_action_".$preset_group_id." option[value=\"".$dialplan_actions[$preset_group_id]."\"]').prop('selected', true);\n\n";
						echo "</script>";
					}
				}
				else {
					//add default preset conditions and populate
					foreach ($preset_variables as $preset_variable => $preset_value) {
						$range_indicator = ($preset_variable == 'date-time') ? '~' : '-';
						$tmp = explode($range_indicator, $preset_value);
						$preset_value_start = $tmp[0];
						$preset_value_stop = $tmp[1];
						unset($tmp);
						echo "<script>\n";
						echo "	condition_id = add_condition(".$preset_group_id.",'preset');\n";
						echo "	$('#variable_".$preset_group_id."_' + condition_id + ' option[value=\"".$preset_variable."\"]').prop('selected', true);\n";
						echo "	load_value_fields(".$preset_group_id.", condition_id, '".$preset_variable."');\n";
						echo "	$('#value_".$preset_group_id."_' + condition_id + '_start option[value=\"".$preset_value_start."\"]').prop('selected', true);\n";
						echo "	$('#value_".$preset_group_id."_' + condition_id + '_stop option[value=\"".$preset_value_stop."\"]').prop('selected', true);\n";
						echo "</script>\n\n";
					}
				}
			}
		}

		echo "	<br />\n";
		echo "	<table border='0' cellpadding='2' cellspacing='0' style='margin: -2px;'>\n";
		echo "		<tr>";
		echo "			<td>";
		echo "				<input type='button' class='btn' name='' alt='".$text['button-advanced']."' onclick=\"$(this).fadeOut(400, function() { $('#default_preset_destination').fadeIn(400); document.getElementById('default_preset_destination_description').innerHTML += '<br>".$text['description-presets_advanced']."'; });\" value='".$text['button-advanced']."'>\n";
		echo "				<span id='default_preset_destination' style=' display: none;'>";
		echo 				$destination->select('dialplan', 'default_preset_action', $dialplan_action);
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
echo 				$destination->select('dialplan', 'dialplan_anti_action', $dialplan_anti_action);
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-order']."\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<select name='dialplan_order' class='formfld'>\n";
for ($i = 300; $i <= 999; $i += 10) {
	$padded_i = str_pad($i, 3, '0', STR_PAD_LEFT);
	$selected = ($dialplan_order == $i) ? "selected='selected'" : null;
	echo "<option value='".$padded_i."' ".$selected.">".$padded_i."</option>\n";
}
echo "	</select>\n";
echo "	<br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
echo "    ".$text['label-enabled']."\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "<select class='formfld' name='dialplan_enabled'>\n";
echo "	<option value='true' ".(($dialplan_enabled == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
echo "	<option value='false' ".(($dialplan_enabled == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
echo "</select>\n";
echo "<br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td class='vncell' valign='top' align='left' nowrap>\n";
echo "	".$text['label-description']."\n";
echo "</td>\n";
echo "<td colspan='4' class='vtable' align='left'>\n";
echo "	<input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"".$dialplan_description."\">\n";
echo "<br />\n";
echo "</td>\n";
echo "</tr>\n";

echo "</table>\n";
echo "<br />";

echo "<div align='right'>\n";
if ($action == "update") {
	echo "	<input type='hidden' name='dialplan_uuid' value='".$dialplan_uuid."'>\n";
}
echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
echo "</div>";

echo "</form>";
echo "<br />";

//include the footer
	require_once "resources/footer.php";

?>