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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
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
	if ($_GET['msg'] == 'dismiss') {
		unset($_SESSION['login']['message']['text']);

		$sql = "update v_default_settings ";
		$sql .= "set default_setting_enabled = 'false' ";
		$sql .= "where ";
		$sql .= "default_setting_category = 'login' ";
		$sql .= "and default_setting_subcategory = 'message' ";
		$sql .= "and default_setting_name = 'text' ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//load header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_dashboard'];

	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
	echo "	<tr>\n";
	echo "		<td valign='top'>";
	echo "			<b>".$text['header-user_dashboard']."</b><br />";
	echo "			".$text['description-user_dashboard'];
	echo "		</td>\n";
	echo "		<td valign='top' style='text-align: right; white-space: nowrap;'>\n";
	echo "			".$text['label-welcome']." <a href='".PROJECT_PATH."/core/user_settings/user_edit.php'>".$_SESSION["username"]."</a>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

//display login message
	if (if_group("superadmin") && $_SESSION['login']['message']['text'] != '') {
		echo "<div class='login_message' width='100%'><b>".$text['login-message_attention']."</b>&nbsp;&nbsp;".$_SESSION['login']['message']['text']."&nbsp;&nbsp;(<a href='?msg=dismiss'>".$text['login-message_dismiss']."</a>)</div>";
	}

//determine hud blocks
	if (is_array($_SESSION['dashboard']) && sizeof($_SESSION['dashboard']) > 0) {
		foreach ($_SESSION['groups'] as $index => $group) {
			$group_name = $group['group_name'];
			if (is_array($_SESSION['dashboard'][$group_name]) && sizeof($_SESSION['dashboard'][$group_name]) > 0) {
				foreach ($_SESSION['dashboard'][$group_name] as $hud_block) {
					$hud_blocks[] = strtolower($hud_block);
				}
			}
		}
	}
	if (is_array($hud_blocks) && sizeof($hud_blocks) > 0) {
		$selected_blocks = array_unique($hud_blocks);
		sort($selected_blocks, SORT_NATURAL);
	}
	unset($group, $group_name, $index, $hud_block, $hud_blocks);


//collect stats for counts and limits
	if (in_array('counts', $selected_blocks) || in_array('limits', $selected_blocks)) {

		//domains
			if (permission_exists('domain_view')) {
				$stats['system']['domains']['total'] = sizeof($_SESSION['domains']);
				$stats['system']['domains']['disabled'] = 0;
				foreach ($_SESSION['domains'] as $domain) {
					$stats['system']['domains']['disabled'] += ($domain['domain_enabled'] != 'true') ? 1 : 0;
				}
			}

		//devices
			if (permission_exists('device_view')) {
				$stats['system']['devices']['total'] = 0;
				$stats['system']['devices']['disabled'] = 0;
				$stats['domain']['devices']['total'] = 0;
				$stats['domain']['devices']['disabled'] = 0;
				$sql = "select domain_uuid, device_enabled from v_devices";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['devices']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['devices']['disabled'] += ($row['device_enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['devices']['total']++;
						$stats['domain']['devices']['disabled'] += ($row['device_enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//extensions
			if (permission_exists('extension_view')) {
				$stats['system']['extensions']['total'] = 0;
				$stats['system']['extensions']['disabled'] = 0;
				$stats['domain']['extensions']['total'] = 0;
				$stats['domain']['extensions']['disabled'] = 0;
				$sql = "select domain_uuid, enabled from v_extensions";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['extensions']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['extensions']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['extensions']['total']++;
						$stats['domain']['extensions']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//gateways
			if (permission_exists('gateway_view')) {
				$stats['system']['gateways']['total'] = 0;
				$stats['system']['gateways']['disabled'] = 0;
				$stats['domain']['gateways']['total'] = 0;
				$stats['domain']['gateways']['disabled'] = 0;
				$sql = "select domain_uuid, enabled from v_gateways";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['gateways']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['gateways']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['gateways']['total']++;
						$stats['domain']['gateways']['disabled'] += ($row['enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//users
			if (permission_exists('user_view') || if_group("superadmin")) {
				$stats['system']['users']['total'] = 0;
				$stats['system']['users']['disabled'] = 0;
				$stats['domain']['users']['total'] = 0;
				$stats['domain']['users']['disabled'] = 0;
				$sql = "select domain_uuid, user_enabled from v_users";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['users']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['users']['disabled'] += ($row['user_enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['users']['total']++;
						$stats['domain']['users']['disabled'] += ($row['user_enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//destinations
			if (permission_exists('destination_view')) {
				$stats['system']['destinations']['total'] = 0;
				$stats['system']['destinations']['disabled'] = 0;
				$stats['domain']['destinations']['total'] = 0;
				$stats['domain']['destinations']['disabled'] = 0;
				$sql = "select domain_uuid, destination_enabled from v_destinations";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['destinations']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['destinations']['disabled'] += ($row['destination_enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['destinations']['total']++;
						$stats['domain']['destinations']['disabled'] += ($row['destination_enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//call center queues
			if (permission_exists('call_center_active_view')) {
				$stats['system']['call_center_queues']['total'] = 0;
				$stats['system']['call_center_queues']['disabled'] = 0;
				$stats['domain']['call_center_queues']['total'] = 0;
				$stats['domain']['call_center_queues']['disabled'] = 0;
				$sql = "select domain_uuid from v_call_center_queues";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['call_center_queues']['total'] = count($result);
				foreach ($result as $row) {
					//$stats['system']['call_center_queues']['disabled'] += ($row['queue_enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['call_center_queues']['total']++;
						//$stats['domain']['call_center_queues']['disabled'] += ($row['queue_enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//ivr menus
			if (permission_exists('ivr_menu_view')) {
				$stats['system']['ivr_menus']['total'] = 0;
				$stats['system']['ivr_menus']['disabled'] = 0;
				$stats['domain']['ivr_menus']['total'] = 0;
				$stats['domain']['ivr_menus']['disabled'] = 0;
				$sql = "select domain_uuid, ivr_menu_enabled from v_ivr_menus";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['ivr_menus']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['ivr_menus']['disabled'] += ($row['ivr_menu_enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['ivr_menus']['total']++;
						$stats['domain']['ivr_menus']['disabled'] += ($row['ivr_menu_enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//ring groups
			if (permission_exists('ring_group_view')) {
				$stats['system']['ring_groups']['total'] = 0;
				$stats['system']['ring_groups']['disabled'] = 0;
				$stats['domain']['ring_groups']['total'] = 0;
				$stats['domain']['ring_groups']['disabled'] = 0;
				$sql = "select domain_uuid, ring_group_enabled from v_ring_groups";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['ring_groups']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['ring_groups']['disabled'] += ($row['ring_group_enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['ring_groups']['total']++;
						$stats['domain']['ring_groups']['disabled'] += ($row['ring_group_enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//voicemails
			if (permission_exists('voicemail_view')) {
				$stats['system']['voicemails']['total'] = 0;
				$stats['system']['voicemails']['disabled'] = 0;
				$stats['domain']['voicemails']['total'] = 0;
				$stats['domain']['voicemails']['disabled'] = 0;
				$sql = "select domain_uuid, voicemail_enabled from v_voicemails";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['voicemails']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['voicemails']['disabled'] += ($row['voicemail_enabled'] != 'true') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['voicemails']['total']++;
						$stats['domain']['voicemails']['disabled'] += ($row['voicemail_enabled'] != 'true') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

		//voicemail messages
			if (permission_exists('voicemail_message_view')) {
				$stats['system']['messages']['total'] = 0;
				$stats['system']['messages']['new'] = 0;
				$stats['domain']['messages']['total'] = 0;
				$stats['domain']['messages']['new'] = 0;
				$sql = "select domain_uuid, message_status from v_voicemail_messages";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$stats['system']['messages']['total'] = count($result);
				foreach ($result as $row) {
					$stats['system']['messages']['new'] += ($row['message_status'] != 'saved') ? 1 : 0;
					if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
						$stats['domain']['messages']['total']++;
						$stats['domain']['messages']['new'] += ($row['message_status'] != 'saved') ? 1 : 0;
					}
				}
				unset ($sql, $prep_statement, $result);
			}

	}

//build hud block html
	$n = 0;
	$theme_image_path = $_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/"; // used for missed and recent calls

	//voicemail
		if (in_array('voicemail', $selected_blocks) && permission_exists('voicemail_message_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/")) {
			//required class
				require_once "app/voicemails/resources/classes/voicemail.php";
			//get the voicemail
				$vm = new voicemail;
				$vm->db = $db;
				$vm->domain_uuid = $_SESSION['domain_uuid'];
				$vm->order_by = $order_by;
				$vm->order = $order;
				$voicemails = $vm->messages();
			//sum total and new
				$messages['total'] = 0;
				$messages['new'] = 0;
				if (sizeof($voicemails) > 0) {
					foreach($voicemails as $field) {
						$messages[$field['voicemail_uuid']]['ext'] = $field['voicemail_id'];
						$messages[$field['voicemail_uuid']]['total'] = 0;
						$messages[$field['voicemail_uuid']]['new'] = 0;
						foreach($field['messages'] as &$row) {
							if ($row['message_status'] == '') {
								$messages[$field['voicemail_uuid']]['new']++;
								$messages['new']++;
							}
							$messages[$field['voicemail_uuid']]['total']++;
							$messages['total']++;
						}
					}
				}

				$hud[$n]['html'] .= "<span class='hud_title' onclick=\"document.location.href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php';\">".$text['label-voicemail']."</span>";

				$hud[$n]['html'] .= "<span class='hud_stat' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$messages['new']."</span>";
				$hud[$n]['html'] .= "<span class='hud_stat_title' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$text['label-new_messages']."</span>\n";

				$hud[$n]['html'] .= "<div class='hud_details' id='hud_".$n."_details'>";
				if (sizeof($voicemails) > 0) {
					$hud[$n]['html'] .= "<table class='tr_hover' cellpadding='2' cellspacing='0' border='0' width='100%'>";
					$hud[$n]['html'] .= "<tr>";
					$hud[$n]['html'] .= "	<th class='hud_heading' width='50%'>".$text['label-voicemail']."</th>";
					$hud[$n]['html'] .= "	<th class='hud_heading' style='text-align: center;' width='50%'>".$text['label-new']."</th>";
					$hud[$n]['html'] .= "	<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>";
					$hud[$n]['html'] .= "</tr>";

					$c = 0;
					$row_style["0"] = "row_style0";
					$row_style["1"] = "row_style1";

					foreach ($messages as $voicemail_uuid => $row) {
						if (is_uuid($voicemail_uuid)) {
							$tr_link = "href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php?id=".$voicemail_uuid."'";
							$hud[$n]['html'] .= "<tr ".$tr_link." style='cursor: pointer;'>";
							$hud[$n]['html'] .= "	<td class='".$row_style[$c]." hud_text'><a href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php?id=".$voicemail_uuid."'>".$row['ext']."</a></td>";
							$hud[$n]['html'] .= "	<td class='".$row_style[$c]." hud_text' style='text-align: center;'>".$row['new']."</td>";
							$hud[$n]['html'] .= "	<td class='".$row_style[$c]." hud_text' style='text-align: center;'>".$row['total']."</td>";
							$hud[$n]['html'] .= "</tr>";
							$c = ($c) ? 0 : 1;
						}
					}

					$hud[$n]['html'] .= "</table>";
				}
				else {
					$hud[$n]['html'] .= "<br />".$text['label-no_voicemail_assigned'];
				}
				$hud[$n]['html'] .= "</div>";
				$n++;
		}


	//missed calls
		if (in_array('missed', $selected_blocks) && permission_exists('xml_cdr_view') && is_array($_SESSION['user']['extension']) && sizeof($_SESSION['user']['extension']) > 0) {
			foreach ($_SESSION['user']['extension'] as $assigned_extension) {
				$assigned_extensions[$assigned_extension['extension_uuid']] = $assigned_extension['user'];
			}

			//if also viewing system status, show more recent calls (more room avaialble)
			$missed_limit = (in_array('counts', $selected_blocks)) ? 10 : 5;

			$sql = "
				select
					direction,
					start_stamp,
					start_epoch,
					caller_id_name,
					caller_id_number,
					answer_stamp
				from
					v_xml_cdr
				where
					domain_uuid = '".$_SESSION['domain_uuid']."'
					and (
						direction = 'inbound'
						or direction = 'local'
					)
					and bridge_uuid is null
					and destination_number in ('".implode("','",$assigned_extensions)."')
					and (";
					$x = 0;
					foreach ($assigned_extensions as $assigned_extension_uuid => $assigned_extension) {
						$sql .= "extension_uuid = '".$assigned_extension_uuid."' ";
						$sql .= "or destination_number = '".$assigned_extension."' ";
						if (++$x < sizeof($assigned_extensions)) { $sql .= "or "; }
					}
					$sql .= "
					)
					and start_epoch > ".(time() - 86400)."
				order by
					start_epoch desc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($result);
			unset ($prep_statement, $sql);

			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			$hud[$n]['html'] .= "<span class='hud_title' onclick=\"document.location.href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?call_result=missed'\">".$text['label-missed_calls']."</span>";

			$hud[$n]['html'] .= "<span class='hud_stat' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$result_count."</span>";
			$hud[$n]['html'] .= "<span class='hud_stat_title' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$text['label-last_24_hours']."</span>\n";

			$hud[$n]['html'] .= "<div class='hud_details' id='hud_".$n."_details'>";
			$hud[$n]['html'] .= "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
			$hud[$n]['html'] .= "<tr>\n";
			if ($result_count > 0) {
				$hud[$n]['html'] .= "<th class='hud_heading'>&nbsp;</th>\n";
			}
			$hud[$n]['html'] .= "<th class='hud_heading' width='100%'>".$text['label-cid_number']."</th>\n";
			$hud[$n]['html'] .= "<th class='hud_heading'>".$text['label-missed']."</th>\n";
			$hud[$n]['html'] .= "</tr>\n";

			if ($result_count > 0) {
				$theme_cdr_images_exist = (
					file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
					file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
					file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
					file_exists($theme_image_path."icon_cdr_local_cancelled.png")
					) ? true : false;

				foreach($result as $index => $row) {
					if ($index + 1 > $missed_limit) { break; } //only show limit
					$tmp_year = date("Y", strtotime($row['start_stamp']));
					$tmp_month = date("M", strtotime($row['start_stamp']));
					$tmp_day = date("d", strtotime($row['start_stamp']));
					$tmp_start_epoch = ($_SESSION['domain']['time_format']['text'] == '12h') ? date("n/j g:ia", $row['start_epoch']) : date("n/j H:i", $row['start_epoch']);
					//set click-to-call variables
						if (permission_exists('click_to_call_call')) {
							$tr_link = "onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php".
								"?src_cid_name=".urlencode($row['caller_id_name']).
								"&src_cid_number=".urlencode($row['caller_id_number']).
								"&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name']).
								"&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number']).
								"&src=".urlencode($_SESSION['user']['extension'][0]['user']).
								"&dest=".urlencode($row['caller_id_number']).
								"&rec=false".
								"&ringback=us-ring".
								"&auto_answer=true".
								"');\" ".
								"style='cursor: pointer;'";
						}
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='middle' class='".$row_style[$c]."' style='cursor: help; padding: 0 0 0 6px;'>\n";
					if ($theme_cdr_images_exist) {
						$call_result = ($row['answer_stamp'] != '') ? 'voicemail' : 'cancelled';
						$hud[$n]['html'] .= "<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_".$row['direction']."_".$call_result.".png' width='16' style='border: none;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$call_result]."'>\n";
					}
					$hud[$n]['html'] .= "</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'><a href='javascript:void(0);' ".(($row['caller_id_name'] != '') ? "title=\"".$row['caller_id_name']."\"" : null).">".((is_numeric($row['caller_id_number'])) ? format_phone($row['caller_id_number']) : $row['caller_id_number'])."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$tmp_start_epoch."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}
				unset($sql, $result, $row_count);
			}

			$hud[$n]['html'] .= "</table>\n";
			$hud[$n]['html'] .= "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php?call_result=missed'>".$text['label-view_all']."</a></span>\n";
			$hud[$n]['html'] .= "</div>";
			$n++;
		}


	//recent calls
		if (in_array('recent', $selected_blocks) && permission_exists('xml_cdr_view') && is_array($_SESSION['user']['extension']) && sizeof($_SESSION['user']['extension']) > 0) {
			foreach ($_SESSION['user']['extension'] as $assigned_extension) {
				$assigned_extensions[$assigned_extension['extension_uuid']] = $assigned_extension['user'];
			}

			//if also viewing system status, show more recent calls (more room avaialble)
			$recent_limit = (in_array('counts', $selected_blocks)) ? 10 : 5;

			$sql = "
				select
					direction,
					start_stamp,
					start_epoch,
					caller_id_name,
					caller_id_number,
					destination_number,
					answer_stamp,
					bridge_uuid,
					sip_hangup_disposition
				from
					v_xml_cdr
				where
					domain_uuid = '".$_SESSION['domain_uuid']."'
					and (";
					$x = 0;
					foreach ($assigned_extensions as $assigned_extension_uuid => $assigned_extension) {
						$sql .= "extension_uuid = '".$assigned_extension_uuid."' ";
						$sql .= "or caller_id_number = '".$assigned_extension."' ";
						$sql .= "or destination_number = '".$assigned_extension."' ";
						$sql .= "or destination_number = '*99".$assigned_extension."' ";
						if (++$x < sizeof($assigned_extensions)) { $sql .= "or "; }
					}
					$sql .= "
					)
					and start_epoch > ".(time() - 86400)."
				order by
					start_epoch desc";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($result);
			unset ($prep_statement, $sql);

			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			$hud[$n]['html'] .= "<span class='hud_title' onclick=\"document.location.href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php';\">".$text['label-recent_calls']."</span>";

			$hud[$n]['html'] .= "<span class='hud_stat' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$result_count."</span>";
			$hud[$n]['html'] .= "<span class='hud_stat_title' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$text['label-last_24_hours']."</span>\n";

			$hud[$n]['html'] .= "<div class='hud_details' id='hud_".$n."_details'>";
			$hud[$n]['html'] .= "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
			$hud[$n]['html'] .= "<tr>\n";
			if ($result_count > 0) {
				$hud[$n]['html'] .= "<th class='hud_heading'>&nbsp;</th>\n";
			}
			$hud[$n]['html'] .= "<th class='hud_heading' width='100%'>".$text['label-cid_number']."</th>\n";
			$hud[$n]['html'] .= "<th class='hud_heading'>".$text['label-date_time']."</th>\n";
			$hud[$n]['html'] .= "</tr>\n";

			if ($result_count > 0) {
				$theme_cdr_images_exist = (
					file_exists($theme_image_path."icon_cdr_inbound_answered.png") &&
					file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
					file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
					file_exists($theme_image_path."icon_cdr_inbound_failed.png") &&
					file_exists($theme_image_path."icon_cdr_outbound_answered.png") &&
					file_exists($theme_image_path."icon_cdr_outbound_cancelled.png") &&
					file_exists($theme_image_path."icon_cdr_outbound_failed.png") &&
					file_exists($theme_image_path."icon_cdr_local_answered.png") &&
					file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
					file_exists($theme_image_path."icon_cdr_local_cancelled.png") &&
					file_exists($theme_image_path."icon_cdr_local_failed.png")
					) ? true : false;

				foreach($result as $index => $row) {
					if ($index + 1 > $recent_limit) { break; } //only show limit
					$tmp_year = date("Y", strtotime($row['start_stamp']));
					$tmp_month = date("M", strtotime($row['start_stamp']));
					$tmp_day = date("d", strtotime($row['start_stamp']));
					$tmp_start_epoch = ($_SESSION['domain']['time_format']['text'] == '12h') ? date("n/j g:ia", $row['start_epoch']) : date("n/j H:i", $row['start_epoch']);

					//determine name
						$cdr_name = ($row['direction'] == 'inbound' || ($row['direction'] == 'local' && in_array($row['destination_number'], $assigned_extensions))) ? $row['caller_id_name'] : $row['destination_number'];
					//determine number to display
						if ($row['direction'] == 'inbound' || ($row['direction'] == 'local' && in_array($row['destination_number'], $assigned_extensions))) {
							$cdr_number = (is_numeric($row['caller_id_number'])) ? format_phone($row['caller_id_number']) : $row['caller_id_number'];
							$dest = $row['caller_id_number'];
						}
						else if ($row['direction'] == 'outbound' || ($row['direction'] == 'local' && in_array($row['caller_id_number'], $assigned_extensions))) {
							$cdr_number = (is_numeric($row['destination_number'])) ? format_phone($row['destination_number']) : $row['destination_number'];
							$dest = $row['destination_number'];
						}
					//set click-to-call variables
						if (permission_exists('click_to_call_call')) {
							$tr_link = "onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php".
								"?src_cid_name=".urlencode($cdr_name).
								"&src_cid_number=".urlencode($cdr_number).
								"&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name']).
								"&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number']).
								"&src=".urlencode($_SESSION['user']['extension'][0]['user']).
								"&dest=".urlencode($dest).
								"&rec=false".
								"&ringback=us-ring".
								"&auto_answer=true".
								"');\" ".
								"style='cursor: pointer;'";
						}
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					//determine call result and appropriate icon
						$hud[$n]['html'] .= "<td valign='middle' class='".$row_style[$c]."' style='cursor: help; padding: 0 0 0 6px;'>\n";
						if ($theme_cdr_images_exist) {
							if ($row['direction'] == 'inbound' || $row['direction'] == 'local') {
								if ($row['answer_stamp'] != '' && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
								else if ($row['answer_stamp'] != '' && $row['bridge_uuid'] == '') { $call_result = 'voicemail'; }
								else if ($row['answer_stamp'] == '' && $row['bridge_uuid'] == '' && $row['sip_hangup_disposition'] != 'send_refuse') { $call_result = 'cancelled'; }
								else { $call_result = 'failed'; }
							}
							else if ($row['direction'] == 'outbound') {
								if ($row['answer_stamp'] != '' && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
								else if ($row['answer_stamp'] == '' && $row['bridge_uuid'] != '') { $call_result = 'cancelled'; }
								else { $call_result = 'failed'; }
							}
							$hud[$n]['html'] .= "<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_".$row['direction']."_".$call_result.".png' width='16' style='border: none;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$call_result]."'>\n";
						}
						$hud[$n]['html'] .= "</td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'><a href='javascript:void(0);' ".(($cdr_name != '') ? "title=\"".$cdr_name."\"" : null).">".$cdr_number."</a></td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$tmp_start_epoch."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";

					unset($cdr_name, $cdr_number);
					$c = ($c) ? 0 : 1;
				}
				unset($sql, $result, $row_count);
			}

			$hud[$n]['html'] .= "</table>\n";
			$hud[$n]['html'] .= "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php'>".$text['label-view_all']."</a></span>\n";
			$hud[$n]['html'] .= "</div>";
			$n++;
		}


	//domain limits
		if (in_array('limits', $selected_blocks) && is_array($_SESSION['limit']) && sizeof($_SESSION['limit']) > 0) {
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			$show_stat = true;
			if (permission_exists('extension_view')) {
				$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/extensions/extensions.php'\"";
				$hud_stat = $stats['domain']['extensions']['total'];
				$hud_stat_title = $text['label-total_extensions'];
			}
			else if (permission_exists('destination_view')) {
				$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/destinations/destinations.php'\"";
				$hud_stat = $stats['domain']['destinations']['total'];
				$hud_stat_title = $text['label-total_destinations'];
			}
			else {
				$show_stat = false;
			}

			$hud[$n]['html'] .= "<span class='hud_title' ".$onclick.">".$text['label-domain_limits']."</span>";

			if ($show_stat) {
				$hud[$n]['html'] .= "<span class='hud_stat' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$hud_stat."</span>";
				$hud[$n]['html'] .= "<span class='hud_stat_title' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$hud_stat_title."</span>\n";
			}

			$hud[$n]['html'] .= "<div class='hud_details' id='hud_".$n."_details'>";
			$hud[$n]['html'] .= "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
			$hud[$n]['html'] .= "<tr>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' width='50%'>".$text['label-feature']."</th>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' width='50%' style='text-align: center;'>".$text['label-used']."</th>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>\n";
			$hud[$n]['html'] .= "</tr>\n";

			foreach ($_SESSION['limit'] as $category => $value) {
				$limit = $value['numeric'];
				switch ($category) {
					case 'users':
						if (!permission_exists('user_view')) { continue 2; }
						$url = '/core/users/index.php';
						break;
					case 'call_center_queues':
						if (!permission_exists('call_center_active_view')) { continue 2; }
						$url = '/app/call_centers/call_center_queues.php';
						break;
					case 'destinations':
						if (!permission_exists('destination_view')) { continue 2; }
						$url = '/app/destinations/destinations.php';
						break;
					case 'devices':
						if (!permission_exists('device_view')) { continue 2; }
						$url = '/app/devices/devices.php';
						break;
					case 'extensions':
						if (!permission_exists('extension_view')) { continue 2; }
						$url = '/app/extensions/extensions.php';
						break;
					case 'gateways':
						if (!permission_exists('gateway_view')) { continue 2; }
						$url = '/app/gateways/gateways.php';
						break;
					case 'ivr_menus':
						if (!permission_exists('ivr_menu_view')) { continue 2; }
						$url = '/app/ivr_menus/ivr_menus.php';
						break;
					case 'ring_groups':
						if (!permission_exists('ring_group_view')) { continue 2; }
						$url = '/app/ring_groups/ring_groups.php';
						break;
				}
				$tr_link = "href='".PROJECT_PATH.$url."'";
				$hud[$n]['html'] .= "<tr ".$tr_link." style='cursor: pointer;'>\n";
				$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-'.$category]."</a></td>\n";
				$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats['domain'][$category]['total']."</td>\n";
				$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$limit."</td>\n";
				$hud[$n]['html'] .= "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

			$hud[$n]['html'] .= "</table>\n";
			$hud[$n]['html'] .= "</div>";
			$n++;
		}


	//system/domain counts
		if (in_array('counts', $selected_blocks)) {
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			$scope = (permission_exists('dialplan_add')) ? 'system' : 'domain';

			$show_stat = true;
			if (permission_exists('domain_view')) {
				$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/core/domain_settings/domains.php'\"";
				$hud_stat = $stats[$scope]['domains']['total'] - $stats[$scope]['domains']['disabled'];
				$hud_stat_title = $text['label-active_domains'];
			}
			else if (permission_exists('extension_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/")) {
				$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/app/extensions/extensions.php'\"";
				$hud_stat = $stats[$scope]['extensions']['total'] - $stats[$scope]['extensions']['disabled'];
				$hud_stat_title = $text['label-active_extensions'];
			}
			else if ((permission_exists('user_view') || if_group("superadmin")) && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/core/users/")) {
				$onclick = "onclick=\"document.location.href='".PROJECT_PATH."/core/users/index.php'\"";
				$hud_stat = $stats[$scope]['users']['total'] - $stats[$scope]['users']['disabled'];
				$hud_stat_title = $text['label-active_users'];
			}
			else {
				$show_stat = false;
			}

			$hud[$n]['html'] .= "<span class='hud_title' ".$onclick.">".$text['label-system_counts']."</span>";

			if ($show_stat) {
				$hud[$n]['html'] .= "<span class='hud_stat' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$hud_stat."</span>";
				$hud[$n]['html'] .= "<span class='hud_stat_title' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$hud_stat_title."</span>\n";
			}

			$hud[$n]['html'] .= "<div class='hud_details' id='hud_".$n."_details'>";
			$hud[$n]['html'] .= "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
			$hud[$n]['html'] .= "<tr>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' width='50%'>".$text['label-item']."</th>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' width='50%' style='text-align: center; padding-left: 0; padding-right: 0;'>".$text['label-disabled']."</th>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>\n";
			$hud[$n]['html'] .= "</tr>\n";

			//domains
				if (permission_exists('domain_view')) {
					$tr_link = "href='".PROJECT_PATH."/core/domain_settings/domains.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-domains']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['domains']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['domains']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//devices
				if (permission_exists('device_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/devices/")) {
					$tr_link = "href='".PROJECT_PATH."/app/devices/devices.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-devices']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['devices']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['devices']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//extensions
				if (permission_exists('extension_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/")) {
					$tr_link = "href='".PROJECT_PATH."/app/extensions/extensions.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-extensions']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['extensions']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['extensions']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//gateways
				if (permission_exists('gateway_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/gateways/")) {
					$tr_link = "href='".PROJECT_PATH."/app/gateways/gateways.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-gateways']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['gateways']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['gateways']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//users
				if ((permission_exists('user_view') || if_group("superadmin")) && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/core/users/")) {
					$tr_link = "href='".PROJECT_PATH."/core/users/index.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-users']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['users']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['users']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//destinations
				if (permission_exists('destination_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/destinations/")) {
					$tr_link = "href='".PROJECT_PATH."/app/destinations/destinations.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-destinations']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['destinations']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['destinations']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//call center queues
				if (permission_exists('call_center_active_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/call_centers/")) {
					$tr_link = "href='".PROJECT_PATH."/app/call_centers/call_center_queues.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-call_center_queues']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['call_center_queues']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['call_center_queues']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//ivr menus
				if (permission_exists('ivr_menu_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/ivr_menus/")) {
					$tr_link = "href='".PROJECT_PATH."/app/ivr_menus/ivr_menus.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-ivr_menus']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ivr_menus']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ivr_menus']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//ring groups
				if (permission_exists('ring_group_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/ring_groups/")) {
					$tr_link = "href='".PROJECT_PATH."/app/ring_groups/ring_groups.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-ring_groups']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ring_groups']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['ring_groups']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//voicemails
				if (permission_exists('voicemail_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/")) {
					$tr_link = "href='".PROJECT_PATH."/app/voicemails/voicemails.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-voicemail']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['voicemails']['disabled']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['voicemails']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//messages
				if (permission_exists('voicemail_message_view') && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/")) {
					$hud[$n]['html'] .= "<tr>\n";
					$hud[$n]['html'] .= "<th class='hud_heading' width='50%'>".$text['label-item']."</th>\n";
					$hud[$n]['html'] .= "<th class='hud_heading' width='50%' style='text-align: center; padding-left: 0; padding-right: 0;'>".$text['label-new']."</th>\n";
					$hud[$n]['html'] .= "<th class='hud_heading' style='text-align: center;'>".$text['label-total']."</th>\n";
					$hud[$n]['html'] .= "</tr>\n";

					$tr_link = "href='".PROJECT_PATH."/app/voicemails/voicemails.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-messages']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['messages']['new']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: center;'>".$stats[$scope]['messages']['total']."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			$hud[$n]['html'] .= "</table>\n";
			$hud[$n]['html'] .= "</div>";
			$n++;
		}


	//system status
		if (in_array('system', $selected_blocks)) {
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			$hud[$n]['html'] .= "<span class='hud_title' style='cursor: default;'>".$text['label-system_status']."</span>";

			//disk usage
			if (stristr(PHP_OS, 'Linux')) {
				$df = shell_exec("/usr/bin/which df");
				$tmp = shell_exec($df." /home");
				$tmp = explode("\n", $tmp);
				$tmp = preg_replace('!\s+!', ' ', $tmp[1]); // multiple > single space
				$tmp = explode(' ', $tmp);
				foreach ($tmp as $stat) {
					if (substr_count($stat, '%') > 0) { $percent_disk_usage = rtrim($stat,'%'); break; }
				}

				if ($percent_disk_usage != '') {
					$hud[$n]['html'] .= "<span class='hud_stat' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\">".$percent_disk_usage."</span>";
					$hud[$n]['html'] .= "<span class='hud_stat_title' onclick=\"$('#hud_'+".$n."+'_details').slideToggle('fast');\" style='cursor: default;'>".$text['label-disk_usage']." (%)</span>\n";
				}
			}

			$hud[$n]['html'] .= "<div class='hud_details' id='hud_".$n."_details'>";
			$hud[$n]['html'] .= "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
			$hud[$n]['html'] .= "<tr>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' width='50%'>".$text['label-item']."</th>\n";
			$hud[$n]['html'] .= "<th class='hud_heading' style='text-align: right;'>".$text['label-value']."</th>\n";
			$hud[$n]['html'] .= "</tr>\n";

			//pbx version
				$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
				$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".(isset($_SESSION['theme']['title']['text'])?$_SESSION['theme']['title']['text']:'FusionPBX')."</td>\n";
				$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".software_version()."</td>\n";
				$hud[$n]['html'] .= "</tr>\n";
				$c = ($c) ? 0 : 1;

			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				//switch version
					$switch_version = event_socket_request($fp, 'api version');
					preg_match("/FreeSWITCH Version (\d+\.\d+\.\d+(?:\.\d+)?).*\(.*?(\d+\w+)\s*\)/", $switch_version, $matches);
					$switch_version = $matches[1];
					$switch_bits = $matches[2];
					if ($switch_version != '' && $switch_bits != '') {
						$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-switch']."</td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$switch_version." (".$switch_bits.")</td>\n";
						$hud[$n]['html'] .= "</tr>\n";
						$c = ($c) ? 0 : 1;
					}

				//switch uptime
					$tmp = event_socket_request($fp, 'api status');
					$tmp = explode("\n", $tmp);
					$tmp = $tmp[0];
					$tmp = explode(' ', $tmp);
					$uptime = (($tmp[1]) ? $tmp[1].'y ' : null);
					$uptime .= (($tmp[3]) ? $tmp[3].'d ' : null);
					$uptime .= (($tmp[5]) ? $tmp[5].'h ' : null);
					$uptime .= (($tmp[7]) ? $tmp[7].'m ' : null);
					$uptime .= (($tmp[9]) ? $tmp[9].'s' : null);
					if ($uptime != '') {
						$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-switch_uptime']."</td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$uptime."</td>\n";
						$hud[$n]['html'] .= "</tr>\n";
						$c = ($c) ? 0 : 1;
					}
			}

			//os uptime
				if (stristr(PHP_OS, 'Linux')) {
					unset($tmp);
					$cut = shell_exec("/usr/bin/which cut");
					$uptime = shell_exec($cut." -d. -f1 /proc/uptime");
					$tmp['y'] = floor($uptime/60/60/24/365);
					$tmp['d'] = $uptime/60/60/24%365;
					$tmp['h'] = $uptime/60/60%24;
					$tmp['m'] = $uptime/60%60;
					$tmp['s'] = $uptime%60;
					$uptime = (($tmp['y'] != 0 && $tmp['y'] != '') ? $tmp['y'].'y ' : null);
					$uptime .= (($tmp['d'] != 0 && $tmp['d'] != '') ? $tmp['d'].'d ' : null);
					$uptime .= (($tmp['h'] != 0 && $tmp['h'] != '') ? $tmp['h'].'h ' : null);
					$uptime .= (($tmp['m'] != 0 && $tmp['m'] != '') ? $tmp['m'].'m ' : null);
					$uptime .= (($tmp['s'] != 0 && $tmp['s'] != '') ? $tmp['s'].'s' : null);
					if ($uptime != '') {
						$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-system_uptime']."</td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$uptime."</td>\n";
						$hud[$n]['html'] .= "</tr>\n";
						$c = ($c) ? 0 : 1;
					}
				}

			//memory usage (for available memory, use "free | awk 'FNR == 3 {print $4/($3+$4)*100}'" instead)
				if (stristr(PHP_OS, 'Linux')) {
					$free = shell_exec("/usr/bin/which free");
					$awk = shell_exec("/usr/bin/which awk");
					$percent_memory = round(shell_exec($free." | ".$awk." 'FNR == 3 {print $3/($3+$4)*100}'"), 1);
					if ($percent_memory != '') {
						$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-memory_usage']."</td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_memory."%</td>\n";
						$hud[$n]['html'] .= "</tr>\n";
						$c = ($c) ? 0 : 1;
					}
				}

			//disk usage
				if (stristr(PHP_OS, 'Linux')) {
					//calculated above
					if ($percent_disk_usage != '') {
						$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-disk_usage']."</td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_disk_usage."%</td>\n";
						$hud[$n]['html'] .= "</tr>\n";
						$c = ($c) ? 0 : 1;
					}
				}

			//cpu usage
				if (stristr(PHP_OS, 'Linux')) {
					$ps = shell_exec("/usr/bin/which ps");
					$sed = shell_exec("/usr/bin/which sed");
					$tmp = shell_exec($ps." -e -o pcpu,cpu,nice,state,cputime,args --sort pcpu | ".$sed." '/^ 0.0 /d'");
					$tmp = explode("\n", $tmp);
					$tmp = preg_replace('!\s+!', ' ', $tmp[1]); // multiple > single space
					$tmp = explode(' ', trim($tmp));
					$percent_cpu = $tmp[0];
					if ($percent_cpu != '') {
						$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-processor_usage']."</td>\n";
						$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$percent_cpu."%</td>\n";
						$hud[$n]['html'] .= "</tr>\n";
						$c = ($c) ? 0 : 1;
					}
				}

			//db connections
				switch ($db_type) {
					case 'pgsql':
						$sql = "select count(*) as connections from pg_stat_activity";
						break;
					case 'mysql':
						$sql = "show status where `variable_name` = 'Threads_connected'";
						break;
					default:
						unset($sql);
						if ($db_path != '' && $dbfilename != '') {
							$tmp =  shell_exec("lsof ".realpath($db_path).'/'.$dbfilename);
							$tmp = explode("\n", $tmp);
							$connections = sizeof($tmp) - 1;
						}
				}
				if ($sql != '') {
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetch(PDO::FETCH_NAMED);
					$connections = $result['connections'];
					unset ($sql, $prep_statement, $result);
				}
				if ($connections != '') {
					$hud[$n]['html'] .= "<tr class='tr_link_void'>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'>".$text['label-database_connections']."</td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$connections."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//channel count
				if ($fp) {
					$tmp = event_socket_request($fp, 'api status');
					$matches = Array();
					preg_match("/(\d+)\s+session\(s\)\s+\-\speak/", $tmp, $matches);
					$channels = $matches[1] ? $matches[1] : 0;
					$tr_link = "href='".PROJECT_PATH."/app/calls_active/calls_active.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-channels']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$channels."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			//registration count
				if ($fp && file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/registrations/")) {
					$tmp = event_socket_request($fp, 'api sofia xmlstatus profile internal reg');
					$registrations = substr_count($tmp, '<registration>');
					$registrations = (is_numeric($registrations)) ? $registrations : 0;
					$tr_link = "href='".PROJECT_PATH."/app/registrations/status_registrations.php'";
					$hud[$n]['html'] .= "<tr ".$tr_link.">\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text'><a ".$tr_link.">".$text['label-registrations']."</a></td>\n";
					$hud[$n]['html'] .= "<td valign='top' class='".$row_style[$c]." hud_text' style='text-align: right;'>".$registrations."</td>\n";
					$hud[$n]['html'] .= "</tr>\n";
					$c = ($c) ? 0 : 1;
				}

			$hud[$n]['html'] .= "</table>\n";
			$hud[$n]['html'] .= "</div>";
			$n++;
		}

//output hud blocks
	if (is_array($hud) && sizeof($hud) > 0) {

		//javascript function: send_cmd
		if ((in_array('missed', $selected_blocks) || in_array('recent', $selected_blocks)) && permission_exists('xml_cdr_view')) {
			echo "<script type=\"text/javascript\">\n";
			echo "	function send_cmd(url) {\n";
			//echo "		alert(url);\n";
			echo "		if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari\n";
			echo "			xmlhttp=new XMLHttpRequest();\n";
			echo "		}\n";
			echo "		else {// code for IE6, IE5\n";
			echo "			xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
			echo "		}\n";
			echo "		xmlhttp.open(\"GET\",url,true);\n";
			echo "		xmlhttp.send(null);\n";
			echo "		document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
			echo "	}\n";
			echo "</script>\n";
		}

		//define grid columns widths and when to use a clear fix
		//-- $col_str[box_total][/usr/bin/which_box]
		//-- $clear_fix[box_total][after_box]
		$col_str[1][1] = "col-xs-12 col-sm-12 col-md-12 col-lg-12";
		for ($n = 1; $n <= 2; $n++) { $col_str[2][$n] = "col-xs-12 col-sm-6 col-md-6 col-lg-6"; }
		for ($n = 1; $n <= 3; $n++) { $col_str[3][$n] = "col-xs-12 col-sm-4 col-md-4 col-lg-4"; }
		for ($n = 1; $n <= 4; $n++) { $col_str[4][$n] = "col-xs-12 col-sm-6 col-md-3 col-lg-3"; }
		for ($n = 1; $n <= 3; $n++) { $col_str[5][$n] = "col-xs-12 col-sm-4 col-md-4 col-lg-2"; }
		$col_str[5][4] = "col-xs-12 col-sm-6 col-md-6 col-lg-3";
		$col_str[5][5] = "col-xs-12 col-sm-6 col-md-6 col-lg-3";
		for ($n = 1; $n <= 6; $n++) { $col_str[6][$n] = "col-xs-12 col-sm-6 col-md-4 col-lg-2"; }

		$clear_fix[4][2] = "visible-sm";
		$clear_fix[5][3] = "visible-sm visible-md";
		$clear_fix[6][2] = "visible-sm";
		$clear_fix[6][3] = "visible-md";
		$clear_fix[6][4] = "visible-sm";

		echo "<div class='row' style='padding: 0 10px;'>";
		foreach ($hud as $index => $block) {
			echo "<div class='".$col_str[sizeof($hud)][$index+1]."'>";
			echo "	<div class='row' style='padding: 6px;'>";
			echo "		<div class='col-md-12 hud_box' style='padding: 0;'>";
			echo 			$block['html'];
			echo "			<span class='hud_expander' onclick=\"$('#hud_'+".$index."+'_details').slideToggle('fast');\"><span class='glyphicon glyphicon-option-horizontal'></span></span>";
			echo "		</div>";
			echo "	</div>";
			echo "</div>";
			if (isset($clear_fix[sizeof($hud)][$index+1]) && $clear_fix[sizeof($hud)][$index+1] != '') {
				echo "<div class='clearfix ".$clear_fix[sizeof($hud)][$index+1]."'></div>";
			}
		}
		echo "</div>";

	}

if (!is_array($selected_blocks) || in_array('call_routing', $selected_blocks) || in_array('ring_groups', $selected_blocks)) {
	echo "<div class='row' style='margin-top: 30px;'>\n";

	if (!is_array($selected_blocks) || in_array('call_routing', $selected_blocks)) {
		//call routing
			if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/calls/calls.php")) {
				if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
					$is_included = true;
					echo "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-7' style='margin: 0 0 50px 0;'>\n";
					require_once "app/calls/calls.php";
					echo "</div>\n";
				}
			}
	}

	if (!is_array($selected_blocks) || in_array('ring_groups', $selected_blocks)) {
		//reload language values
			$language = new text;
			$text = $language->get();

		//ring group forward
			if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/ring_groups/ring_group_forward.php")) {
				if (permission_exists('ring_group_forward')) {
					$is_included = true;
					echo "<div class='col-xs-12 col-sm-12 col-md-6 col-lg-5' style='margin: 0 0 50px 0;'>\n";
					require_once "app/ring_groups/ring_group_forward.php";
					echo "</div>";
				}
			}
	}

	echo "</div>\n";
}


//show the footer
	require_once "resources/footer.php";
?>
