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
require_once "resources/paging.php";


//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//get the post form variables and se them to php variables
	$action = 'add';
	if (count($_POST) > 0) {
		$dialplan_name = check_str($_POST["dialplan_name"]);
		$dialplan_number = check_str($_POST["dialplan_number"]);
		$dialplan_order = check_str($_POST["dialplan_order"]);

		$action_1 = check_str($_POST["action_1"]);
		$action_1_array = explode(":", $action_1);
		$action_application_1 = array_shift($action_1_array);
		$action_data_1 = join(':', $action_1_array);

		$anti_action_1 = check_str($_POST["anti_action_1"]);
		$anti_action_1_array = explode(":", $anti_action_1);
		$anti_action_application_1 = array_shift($anti_action_1_array);
		$anti_action_data_1 = join(':', $anti_action_1_array);

		$dialplan_enabled = check_str($_POST["dialplan_enabled"]);
		$dialplan_description = check_str($_POST["dialplan_description"]);
	}

//process submitted data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
		//check for all required data
			if (strlen($domain_uuid) == 0) { $msg .= $text['label-required-domain_uuid']."<br>\n"; }
			if (strlen($dialplan_name) == 0) { $msg .= $text['label-required-dialplan_name']."<br>\n"; }
			if (strlen($dialplan_number) == 0) { $msg .= $text['label-required-dialplan_number']."<br>\n"; }
			if (strlen($action_1) == 0) { $msg .= $text['label-required-action']."<br>\n"; }
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

		//add the time condition
			if (strlen($_GET['id']) == 0) {
				//start the atomic transaction
					$count = $db->exec("BEGIN;"); //returns affected rows

				//add the main dialplan include entry
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

				//initialize dialplan detail group and order numbers
					$dialplan_detail_group = 0;
					$dialplan_detail_order = 0;

				//check if custom conditions defined
					$custom_conditions_defined = false;
					foreach ($_REQUEST['variable'] as $cond_var) {
						if ($cond_var != '') { $custom_conditions_defined = true; }
					}

					if ($custom_conditions_defined) {

						//build insert query for custom conditions
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

						//add destination number condition
						$dialplan_detail_group++;
						$dialplan_detail_order += 10;
						$sql .= "( ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".$dialplan_uuid."', ";
						$sql .= "'".uuid()."', ";
						$sql .= "'condition', ";
						$sql .= "'destination_number', ";
						$sql .= "'^".$dialplan_number."$', ";
						$sql .= "'never', ";
						$sql .= "null, ";
						$sql .= "'".$dialplan_detail_group."', ";
						$sql .= "'".$dialplan_detail_order."' ";
						$sql .= ")";

						//add custom conditions
						foreach ($_REQUEST['variable'] as $cond_num => $cond_var) {
							if ($cond_var != '') {
								$scope = $_REQUEST['scope'][$cond_num];
								$cond_start = $_REQUEST[$cond_var][$cond_num]['start'];
								$cond_stop = $_REQUEST[$cond_var][$cond_num]['stop'];

								//handle time of day
									if ($cond_var == 'time-of-day' && $cond_start['hour'] != '') {
										//format condition start
											if ($cond_start['notation'] == 'PM') {
												$cond_start_hour = ($cond_start['hour'] != 12) ? $cond_start['hour'] += 12 : $cond_start['hour'];
											}
											else if ($cond_start['notation'] == 'AM') {
												$cond_start_hour = ($cond_start['hour'] == 12) ? $cond_start['hour'] -= 12 : $cond_start['hour'];
											}
											$cond_start_hour = number_pad($cond_start_hour,2);
											$cond_start_minute = $cond_start['minute'];
											$cond_start = $cond_start_hour.':'.$cond_start_minute;

										//format condition stop
											if ($cond_start != '' && $scope == 'range') {
												if ($cond_stop['notation'] == 'PM') {
													$cond_stop_hour = ($cond_stop['hour'] != 12) ? $cond_stop['hour'] += 12 : $cond_stop['hour'];
												}
												else if ($cond_stop['notation'] == 'AM') {
													$cond_stop_hour = ($cond_stop['hour'] == 12) ? $cond_stop['hour'] -= 12 : $cond_stop['hour'];
												}
												$cond_stop_hour = number_pad($cond_stop_hour,2);
												$cond_stop_minute = $cond_stop['minute'];
												$cond_stop = $cond_stop_hour.':'.$cond_stop_minute;
											}
											else {
												unset($cond_stop);
											}

										$cond_value = $cond_start.(($cond_stop != '') ? '-'.$cond_stop : null);
									}
								//handle all other variables
									else {
										if ($cond_start != '') {
											$cond_value = $cond_start;
											if ($scope == 'range' && $cond_stop != '') {
												$range_indicator = ($cond_var == 'date-time') ? '~' : '-';
												$cond_value .= $range_indicator.$cond_stop;
											}
										}
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
							}
						}

						//add condition action
						$dialplan_detail_order += 10;
						$sql .= ", ( ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".$dialplan_uuid."', ";
						$sql .= "'".uuid()."', ";
						$sql .= "'action', ";
						$sql .= "'set', ";
						$sql .= "'time_condition=true', ";
						$sql .= "null, ";
						$sql .= "'true', ";
						$sql .= "'".$dialplan_detail_group."', ";
						$sql .= "'".$dialplan_detail_order."' ";
						$sql .= ") ";

						//execute query
						$db->exec(check_sql($sql));
						unset($sql);
					}

				//add to query for preset conditions (if any)
					if (sizeof($_REQUEST['preset']) > 0) {

						//build insert query for preset conditions
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

						//get preset condition variables
						foreach ($_SESSION['time_conditions']['preset'] as $json) {
							$presets[] = json_decode($json, true);
						}

						foreach ($_REQUEST['preset'] as $index => $preset_number) {

							//increment group and order number
							$dialplan_detail_group++;
							$dialplan_detail_order = 0;

							//add destination number condition
							$dialplan_detail_order += 10;
							$sql .= ($index != 0) ? "," : null;
							$sql .= " ( ";
							$sql .= "'".$domain_uuid."', ";
							$sql .= "'".$dialplan_uuid."', ";
							$sql .= "'".uuid()."', ";
							$sql .= "'condition', ";
							$sql .= "'destination_number', ";
							$sql .= "'^".$dialplan_number."$', ";
							$sql .= "'never', ";
							$sql .= "null, ";
							$sql .= "'".$dialplan_detail_group."', ";
							$sql .= "'".$dialplan_detail_order."' ";
							$sql .= ") ";

							foreach ($presets[$preset_number] as $preset_name => $preset) {
								foreach ($preset['variables'] as $cond_var => $cond_value) {
									//add preset condition to query string
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
								}
							}

							//add condition action
							$dialplan_detail_order += 10;
							$sql .= ", ( ";
							$sql .= "'".$domain_uuid."', ";
							$sql .= "'".$dialplan_uuid."', ";
							$sql .= "'".uuid()."', ";
							$sql .= "'action', ";
							$sql .= "'set', ";
							$sql .= "'time_condition=true', ";
							$sql .= "null, ";
							$sql .= "'true', ";
							$sql .= "'".$dialplan_detail_group."', ";
							$sql .= "'".$dialplan_detail_order."' ";
							$sql .= ") ";

						}

						//execute query
						$db->exec(check_sql($sql));
						unset($sql);
					}


				//increment group number, reset order number
					$dialplan_detail_group = 100;
					$dialplan_detail_order = 0;

				//add to query for main action and anti-action condition

					//build insert query for custom conditions
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

					//add destination number condition
					$dialplan_detail_order += 10;
					$sql .= "( ";
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

					//add time condition met check
					$dialplan_detail_order += 10;
					$sql .= ", ( ";
					$sql .= "'".$domain_uuid."', ";
					$sql .= "'".$dialplan_uuid."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'condition', ";
					$sql .= "'".'${time_condition}'."', ";
					$sql .= "'^true$', ";
					$sql .= "null, ";
					$sql .= "null, ";
					$sql .= "'".$dialplan_detail_group."', ";
					$sql .= "'".$dialplan_detail_order."' ";
					$sql .= ") ";

					//add main action
					$dialplan_detail_order += 10;
					$sql .= ", ( ";
					$sql .= "'".$domain_uuid."', ";
					$sql .= "'".$dialplan_uuid."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'action', ";
					$sql .= "'".$action_application_1."', ";
					$sql .= "'".$action_data_1."', ";
					$sql .= "null, ";
					$sql .= "null, ";
					$sql .= "'".$dialplan_detail_group."', ";
					$sql .= "'".$dialplan_detail_order."' ";
					$sql .= ") ";

					//add anti-action (if defined)
					if (strlen($anti_action_application_1) > 0) {
						$dialplan_detail_order += 10;
						$sql .= ", ( ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".$dialplan_uuid."', ";
						$sql .= "'".uuid()."', ";
						$sql .= "'anti-action', ";
						$sql .= "'".$anti_action_application_1."', ";
						$sql .= "'".$anti_action_data_1."', ";
						$sql .= "null, ";
						$sql .= "null, ";
						$sql .= "'".$dialplan_detail_group."', ";
						$sql .= "'".$dialplan_detail_order."' ";
						$sql .= ") ";
					}
					//execute query
					$db->exec(check_sql($sql));
					unset($sql);

				//commit the atomic transaction
					$count = $db->exec("COMMIT;"); //returns affected rows

				//synchronize the xml config
					save_dialplan_xml();

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$_SESSION["context"]);

				//redirect the browser
					$_SESSION["message"] = $text['message-add'];
					header("Location: ".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1");
					return;
			} //end - add the time condition
	} //end if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//get the information to pre-populate the form
	if (strlen($_GET['id']) > 0) {
		//get the dialplan
			if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
				$dialplan_uuid = $_GET["id"];
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
			}

		//get the dialplan details in an array
			$sql = "select * from v_dialplan_details ";
			$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
			$sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);

		//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
			$x = 0;
			$details = '';
		//conditions
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "condition") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		//regex
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "regex") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		//actions
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "action") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
		//anti-actions
			foreach($result as $row) {
				if ($row['dialplan_detail_tag'] == "anti-action") {
					$group = $row['dialplan_detail_group'];
					foreach ($row as $key => $val) {
						$details[$group][$x][$key] = $val;
					}
				}
				$x++;
			}
			unset($result);

		//get the last action and anti-action
			//echo "<pre>\n";
			//print_r($details);
			//$detail_anti_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
			foreach($details as $group) {
				foreach ($group as $row) {
					if ($row['dialplan_detail_tag'] == 'action') {
						//echo $row['dialplan_detail_tag']." ".$row['dialplan_detail_type'].":".$row['dialplan_detail_data']."\n";
						$detail_action = $row['dialplan_detail_type'].':'.$row['dialplan_detail_data'];
					}
					if ($row['dialplan_detail_tag'] == 'anti-action') {
						//echo $row['dialplan_detail_tag']." ".$row['dialplan_detail_type'].":".$row['dialplan_detail_data']."\n";
						$detail_anti_action = $row['dialplan_detail_type'].':'.$row['dialplan_detail_data'];
					}
				}
			}
			//echo "</pre>\n";
			//exit;

		//blank row
			foreach($details as $group => $row) {
				//set the array key for the empty row
					$x = "999";
				//get the highest dialplan_detail_order
					foreach ($row as $key => $field) {
						$dialplan_detail_order = 0;
						if ($dialplan_detail_order < $field['dialplan_detail_order']) {
							$dialplan_detail_order = $field['dialplan_detail_order'];
						}
					}
				//increment the highest order by 5
					$dialplan_detail_order = $dialplan_detail_order + 10;
				//set the rest of the empty array
					//$details[$group][$x]['domain_uuid'] = '';
					//$details[$group][$x]['dialplan_uuid'] = '';
					$details[$group][$x]['dialplan_detail_tag'] = '';
					$details[$group][$x]['dialplan_detail_type'] = '';
					$details[$group][$x]['dialplan_detail_data'] = '';
					$details[$group][$x]['dialplan_detail_break'] = '';
					$details[$group][$x]['dialplan_detail_inline'] = '';
					$details[$group][$x]['dialplan_detail_group'] = $group;
					$details[$group][$x]['dialplan_detail_order'] = $dialplan_detail_order;
			}
	}

?>

<script type="text/javascript">
	<?php
	$time_condition_vars["year"] = $text['label-year'];
	$time_condition_vars["mon"] = $text['label-month'];
	$time_condition_vars["mday"] = $text['label-day-of-month'];
	$time_condition_vars["wday"] = $text['label-day-of-week'];
	//$time_condition_vars["yday"] = $text['label-day-of-year'];
	$time_condition_vars["week"] = $text['label-week-of-year'];
	$time_condition_vars["mweek"] = $text['label-week-of-month'];
	$time_condition_vars["hour"] = $text['label-hour-of-day'];
	$time_condition_vars["minute"] = $text['label-minute-of-hour'];
	//$time_condition_vars["minute-of-day"] = $text['label-minute-of-day'];
	$time_condition_vars["time-of-day"] = $text['label-time-of-day'];
	$time_condition_vars["date-time"] = $text['label-date-and-time'];
	?>
	function hide_var_options(row_num) {
		<?php
		foreach ($time_condition_vars as $var_name => $var_label) {
			echo "document.getElementById('var_".$var_name."_options_' + row_num).style.display = 'none';\n";
		}
		?>
	}

	function show_var_option(row_num, var_name) {
		if (var_name != '') { document.getElementById('var_' + var_name + '_options_' + row_num).style.display = ''; }
	}

	function toggle_var_stops(row_num, scope) {
		display = (scope == 'range') ? '' : 'none';
		<?php
		foreach ($time_condition_vars as $var_name => $var_label) {
			echo "document.getElementById('".$var_name."_' + row_num + '_stop').style.display = display;\n";
		}
		?>
	}
</script>

<?php

//show the content
	echo "<form method='post' name='frm' action=''>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top'>\n";
	echo "			<span class='title'>".$text['title-time-condition-add']."</span><br />\n";
	echo "		</td>\n";
	echo "		<td align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=4b821450-926b-175a-af93-a03c441818b1'\" value='".$text['button-back']."'>\n";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<span class='vexpl'>\n";
	echo "			".$text['description-time-condition-add']."\n";
	echo "			</span>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";

	echo "<br />\n";
	echo "<br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='20%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='80%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_name' maxlength='255' value=\"$dialplan_name\">\n";
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
	echo "	<input class='formfld' type='text' name='dialplan_number' id='dialplan_number' maxlength='255' value=\"$dialplan_number\">\n";
	echo "	<br />\n";
	echo "	".$text['description-extension']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-conditions']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//define select box options for each time condition variable (where appropriate)
	for ($y = date('Y'); $y <= (date('Y') + 10); $y++) { $var_option_select['year'][$y] = $y; } //years
	for ($m = 1; $m <= 12; $m++) { $var_option_select['mon'][$m] = date('F', strtotime('2015-'.number_pad($m,2).'-01')); } //month names
	for ($d = 1; $d <= 366; $d++) { $var_option_select['yday'][$d] = $d; } //days of year
	for ($d = 1; $d <= 31; $d++) { $var_option_select['mday'][$d] = $d; } //days of month
	for ($d = 1; $d <= 7; $d++) { $var_option_select['wday'][$d] = date('l', strtotime('Sunday +'.($d-1).' days')); } //week days
	for ($w = 1; $w <= 53; $w++) { $var_option_select['week'][$w] = $w; } //weeks of year
	for ($w = 1; $w <= 5; $w++) { $var_option_select['mweek'][$w] = $w; } //weeks of month
	for ($h = 0; $h <= 23; $h++) { $var_option_select['hour'][$h] = (($h) ? (($h >= 12) ? (($h == 12) ? $h : ($h-12)).' PM' : $h.' AM') : '12 AM'); } //hours of day
	for ($m = 0; $m <= 59; $m++) { $var_option_select['minute'][$m] = number_pad($m,2); } //minutes of hour
	//output condition fields
	echo "	<table border='0' cellpadding='2' cellspacing='0' style='margin: -2px;'>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-condition_parameter']."</td>\n";
	echo "			<td class='vtable'>".$text['label-condition_scope']."</td>\n";
	echo "			<td class='vtable'>".$text['label-condition_values']."</td>\n";
	echo "			<td></td>\n";
	echo "		</tr>\n";
	for ($c = 1; $c <= 3; $c++) {
		echo "	<tr>\n";
		echo "		<td>\n";
		echo "			<select class='formfld' name='variable[".$c."]' id='variable_".$c."' onchange=\"hide_var_options('".$c."'); show_var_option('".$c."', this.options[this.selectedIndex].value);\">\n";
		echo "				<option value=''></option>\n";
		foreach ($time_condition_vars as $var_name => $var_label) {
			echo "				<option value='".$var_name."'>".$var_label."</option>\n";
		}
		echo "			</select>\n";
		echo "		</td>\n";
		echo "		<td>\n";
		echo "			<select class='formfld' name='scope[".$c."]' id='scope_".$c."' onchange=\"toggle_var_stops('".$c."', this.options[this.selectedIndex].value);\">\n";
		echo "				<option value='single'>Single</option>\n";
		echo "				<option value='range'>Range</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
		echo "		<td>\n";

		foreach ($time_condition_vars as $var_name => $var_label) {
			switch ($var_name) {
				case "minute-of-day" :
					echo "<span id='var_minute-of-day_options_".$c."' style='display: none;'>\n";
					echo "	<input type='number' class='formfld' style='width: 50px; min-width: 50px; max-width: 50px;' name='minute-of-day[".$c."][start]' id='minute-of-day_".$c."_start'>\n";
					echo "	<span id='minute-of-day_".$c."_stop' style='display: none;'>\n";
					echo "		&nbsp;<strong>~</strong>&nbsp;\n";
					echo "		<input type='number' class='formfld' style='width: 50px; min-width: 50px; max-width: 50px;' name='minute-of-day[".$c."][stop]'>\n";
					echo "	</span>\n";
					echo "</span>\n";
					break;
				case "time-of-day" :
					echo "<span id='var_time-of-day_options_".$c."' style='display: none;'>\n";
					echo "	<select class='formfld' name='time-of-day[".$c."][start][hour]' id='time-of-day_".$c."_start_hour' onchange=\"if (document.getElementById('time-of-day_".$c."_start_minute').selectedIndex == 0) { document.getElementById('time-of-day_".$c."_start_minute').selectedIndex = 1; } if (document.getElementById('time-of-day_".$c."_stop_hour').selectedIndex == 0) { document.getElementById('time-of-day_".$c."_stop_hour').selectedIndex = this.selectedIndex; document.getElementById('time-of-day_".$c."_stop_minute').selectedIndex = 1; }\">\n";
					echo "		<option value=''>Hour</option>\n";
					for ($h = 1; $h <= 12; $h++) {
						echo "	<option value='".$h."'>".$h."</option>\n";
					}
					echo "	</select>\n";
					echo "	<select class='formfld' name='time-of-day[".$c."][start][minute]' id='time-of-day_".$c."_start_minute' onchange=\"if (document.getElementById('time-of-day_".$c."_stop_minute').selectedIndex == 0) { document.getElementById('time-of-day_".$c."_stop_minute').selectedIndex = this.selectedIndex; }\">\n";
					echo "		<option value='00'>Minute</option>\n";
					for ($m = 0; $m < 60; $m++) {
						echo "	<option value='".number_pad($m,2)."'>".number_pad($m,2)."</option>\n";
					}
					echo "	</select>\n";
					echo "	<select class='formfld' name='time-of-day[".$c."][start][notation]' id='time-of-day_".$c."_start_notation'>\n";
					echo "		<option value='AM'>AM</option>\n";
					echo "		<option value='PM'>PM</option>\n";
					echo "	</select>\n";
					echo "	<span id='time-of-day_".$c."_stop' style='display: none;'>\n";
					echo "		&nbsp;~&nbsp;";
					echo "		<select class='formfld' name='time-of-day[".$c."][stop][hour]' id='time-of-day_".$c."_stop_hour' onchange=\"if (document.getElementById('time-of-day_".$c."_stop_minute').selectedIndex == 0) { document.getElementById('time-of-day_".$c."_stop_minute').selectedIndex = 1; }\">\n";
					echo "			<option value=''>Hour</option>\n";
					for ($h = 1; $h <= 12; $h++) {
						echo "		<option value='".$h."'>".$h."</option>\n";
					}
					echo "		</select>\n";
					echo "		<select class='formfld' name='time-of-day[".$c."][stop][minute]' id='time-of-day_".$c."_stop_minute'>\n";
					echo "			<option value='00'>Minute</option>\n";
					for ($m = 0; $m < 60; $m++) {
						echo "		<option value='".number_pad($m,2)."'>".number_pad($m,2)."</option>\n";
					}
					echo "		</select>\n";
					echo "		<select class='formfld' name='time-of-day[".$c."][stop][notation]' id='time-of-day_".$c."_stop_notation'>\n";
					echo "			<option value='AM'>AM</option>\n";
					echo "			<option value='PM'>PM</option>\n";
					echo "		</select>\n";
					echo "	</span>\n";
					echo "</span>\n";
					break;
				case "date-time" :
					echo "<span id='var_date-time_options_".$c."' style='display: none;'>\n";
					echo "	<input type='text' class='formfld' style='min-width: 115px; max-width: 115px;' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: true, fxName: null, showButtons: true}\" name='date-time[".$c."][start]' id='date-time_".$c."_start'>\n";
					echo "	<span id='date-time_".$c."_stop' style='display: none;'>\n";
					echo "		&nbsp;<strong>~</strong>&nbsp;\n";
					echo "		<input type='text' class='formfld' style='min-width: 115px; max-width: 115px;' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: true, fxName: null, showButtons: true}\" name='date-time[".$c."][stop]'>\n";
					echo "	</span>\n";
					echo "</span>\n";
					break;
				default:
					echo "<span id='var_".$var_name."_options_".$c."' style='display: none;'>\n";
					echo "	<select class='formfld' name='".$var_name."[".$c."][start]' id='".$var_name."_".$c."_start' onchange=\"if (document.getElementById('".$var_name."_".$c."_stop').selectedIndex == 0) { document.getElementById('".$var_name."_".$c."_stop').selectedIndex = this.selectedIndex; }\">\n";
					foreach ($var_option_select[$var_name] as $var_option_select_value => $var_option_select_label) {
						echo "	<option value='".$var_option_select_value."'>".$var_option_select_label."</option>\n";
					}
					echo "	</select>\n";
					echo "	<span id='".$var_name."_".$c."_stop' style='display: none;'>\n";
					echo "		&nbsp;<strong>~</strong>&nbsp;\n";
					echo "		<select class='formfld' name='".$var_name."[".$c."][stop]' id='".$var_name."_".$c."_stop-real'>\n";
					echo "			<option value=''></option>\n";
					foreach ($var_option_select[$var_name] as $var_option_select_value => $var_option_select_label) {
						echo "		<option value='".$var_option_select_value."'>".$var_option_select_label."</option>\n";
					}
					echo "		</select>\n";
					echo "	</span>\n";
					echo "</span>\n";
			}
		}

		echo "		</td>\n";
		echo "	</tr>\n";
	}
	echo "	</table>\n";
	if ($action == 'add') {
		echo "<script>\n";
		//set field values
		echo "	document.getElementById('variable_1').selectedIndex = 4;\n"; //day of week
		echo "	document.getElementById('scope_1').selectedIndex = 1;\n"; //range
		echo "	document.getElementById('wday_1_start').selectedIndex = 1;\n"; //monday
		echo "	document.getElementById('wday_1_stop-real').selectedIndex = 6;\n"; //friday
		echo "	document.getElementById('variable_2').selectedIndex = 7;\n"; //hour of day
		echo "	document.getElementById('scope_2').selectedIndex = 1;\n"; //range
		echo "	document.getElementById('hour_2_start').selectedIndex = 8;\n"; //8am
		echo "	document.getElementById('hour_2_stop-real').selectedIndex = 18;\n"; //5pm
		//display fields
		echo "	document.getElementById('var_wday_options_1').style.display = '';\n";
		echo "	document.getElementById('wday_1_stop').style.display = '';\n";
		echo "	document.getElementById('var_hour_options_2').style.display = '';\n";
		echo "	document.getElementById('hour_2_stop').style.display = '';\n";
		echo "</script>\n";
	}
	echo "	".$text['description-conditions']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-presets']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	foreach ($_SESSION['time_conditions']['preset'] as $json) {
		$presets[] = json_decode($json, true);
	}
	//echo "<pre>"; print_r($presets); echo "<pre><br><br>";
	echo "	<table cellpadding='0' cellspacing='15' border='0' style='margin: -15px;'>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable' style='border: none; padding: 0px; vertical-align: top; white-space: nowrap;'>\n";
	$preset_count = sizeof($presets);
	$presets_per_column = ceil($preset_count / 3);
	$p = 0;
	foreach ($presets as $preset_number => $preset) {
		foreach ($preset as $preset_name => $preset_variables) {
			echo "<label for='preset_".$preset_number."'><input type='checkbox' name='preset[]' id='preset_".$preset_number."' value='".$preset_number."'> ".$text['label-preset_'.$preset_name]."</label><br>\n";
			$p++;
			if ($p == $presets_per_column) {
				echo "	</td>";
				echo "	<td class='vtable' style='border: none; padding: 0px; vertical-align: top; white-space: nowrap;'>\n";
				$p = 0;
			}
		}
	}
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "	<br />\n";
	echo "	".$text['description-presets']."<br />\n";
	echo "</td>\n";
	echo "</tr>\n";


	$x = 0;
	foreach($details as $group) {
		foreach ($group as $row) {
			if ($row['dialplan_detail_tag'] == 'action' && $row['dialplan_detail_type'] != 'set') {
				echo "<tr>\n";
				echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
				echo "    ".$text['label-action']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
				//switch_select_destination("dialplan", $action_1, "action_1", $action_1, "", "");

				//echo $row['dialplan_detail_tag']." ".$row['dialplan_detail_type'].":".$row['dialplan_detail_data']."\n";
				$data = $row['dialplan_detail_data'];
				$label = explode("XML", $data);
				$divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
				$detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
				switch_select_destination("dialplan", $label[0], "dialplan_details[".$x."][action]", $detail_action, "width: 60%;", 'action');
				echo "</td>\n";
				echo "</tr>\n";
			}
			if ($row['dialplan_detail_tag'] == 'anti-action' && $row['dialplan_detail_type'] != 'set') {
				echo "<tr>\n";
				echo "<td class='vncell' valign='top' align='left' nowrap>\n";
				echo "    ".$text['label-action-alternate']."\n";
				echo "</td>\n";
				echo "<td class='vtable' align='left'>\n";
				//switch_select_destination(select_type, select_label, select_name, select_value, select_style, $action);
				//switch_select_destination("dialplan", $anti_action_1, "anti_action_1", $anti_action_1, "", "");

				$label = explode("XML", $row['dialplan_detail_data']);
				$divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
				$detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
				switch_select_destination("dialplan", $label[0], "dialplan_details[".$x."][anti_action]", $detail_action, "width: 60%;", 'action');
				echo "</td>\n";
				echo "</tr>\n";
			}
		}
		$x++;
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='dialplan_order' class='formfld'>\n";
	$i = 300;
	while($i <= 999) {
		$selected = ($dialplan_order == $i) ? "selected" : null;
		if (strlen($i) == 1) { echo "<option value='00$i' ".$selected.">00$i</option>\n"; }
		if (strlen($i) == 2) { echo "<option value='0$i' ".$selected.">0$i</option>\n"; }
		if (strlen($i) == 3) { echo "<option value='$i' ".$selected.">$i</option>\n"; }
		$i = $i + 10;
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
	echo "    <select class='formfld' name='dialplan_enabled'>\n";
	if ($dialplan_enabled == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($dialplan_enabled == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td colspan='4' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='dialplan_description' maxlength='255' value=\"$dialplan_description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>";

	echo "<div align='right'>\n";
	if ($action == "update") {
		echo "	<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</div>";

	echo "</form>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>