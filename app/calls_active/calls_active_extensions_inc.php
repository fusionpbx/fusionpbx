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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
include "app_languages.php";
if (permission_exists('extension_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set debug to true or false
	$debug = false;

//http get and set variables
	if (strlen($_GET['url']) > 0) {
		$url = $_GET['url'];
	}
	if (strlen($_GET['rows']) == 0) {
		$_GET['rows'] = 0;
	}

//define variables
	$c = 0;
	$row_style["0"] = "row_style1";
	$row_style["1"] = "row_style1";

//get the user status
	$sql = "select e.extension, u.username, u.user_status ";
	$sql .= "from v_users as u, v_extensions as e ";
	$sql .= "where e.domain_uuid = '$domain_uuid' ";
	$sql .= "and u.user_enabled = 'true' ";
	$sql .= "and u.domain_uuid = '$domain_uuid' ";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "e.extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$x = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		if (strlen($row["user_status"]) > 0) {
			$user_array[$row["extension"]]['username'] = $row["username"];
			$user_array[$row["extension"]]['user_status'] = $row["user_status"];
			$username_array[$row["username"]]['user_status'] = $row["user_status"];
			if ($row["username"] == $_SESSION["username"]) {
				$user_status = $row["user_status"];
			}
		}
		$x++;
	}
	unset ($prep_statement, $x);

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get information over event socket
	if (!$fp) {
		$msg = "<div align='center'>".$text['confirm-socket']."<br /></div>"; 
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>".$text['label-message']."</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	else {

		//get the agent list from event socket
			$switch_cmd = 'callcenter_config agent list';
			$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
			$agent_array = csv_to_named_array($event_socket_str, '|');
		//set the status on the user_array by using the extension as the key
			foreach ($agent_array as $row) {
				if (count($_SESSION['domains']) == 1) {
					//get the extension status from the call center agent list
					preg_match('/user\/(\d{2,7})/', $row['contact'], $matches);
					$extension = $matches[1];
					$user_array[$extension]['username'] = $tmp[0];
					if ($user_array[$extension]['user_status'] != "Do Not Disturb") {
						$user_array[$extension]['user_status'] = $row['status'];
					}
				} else {
					$tmp = explode('@',$row["name"]);
					if ($tmp[1] == $_SESSION['domain_name']) {
						//get the extension status from the call center agent list
						preg_match('/user\/(\d{2,7})/', $row['contact'], $matches);
						$extension = $matches[1];
						$user_array[$extension]['username'] = $tmp[0];
						if ($user_array[$extension]['user_status'] != "Do Not Disturb") {
							$user_array[$extension]['user_status'] = $row['status'];
						}
					}
				}
			}

		//send the api command over event socket
			//$switch_cmd = 'valet_info';
			//$valet_xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));

		//parse the xml
			//try {
			//	$valet_xml = new SimpleXMLElement($valet_xml_str);
			//}
			//catch(Exception $e) {
			//	//echo $e->getMessage();
			//}
			//$valet_xml = new SimpleXMLElement($valet_xml_str);
			//foreach ($valet_xml as $row) {
			//	$valet_name = (string) $row->attributes()->name;
			//	foreach ($row->extension as $row2) {
			//		$extension = (string) $row2;
			//		$uuid = (string) $row2->attributes()->uuid;
			//		$uuid = trim($uuid);
			//		$valet_array[$uuid]['name'] = $valet_name;
			//		$valet_array[$uuid]['extension'] = $extension;
			//	}
			//}

		//send the event socket command
			$switch_cmd = 'show channels as xml';
			$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));

		//parse the xml
			try {
				$xml = new SimpleXMLElement($xml_str);
			}
			catch(Exception $e) {
				//echo $e->getMessage();
			}

		//active channels array
				$channels_array = '';
				$x = 1;
				foreach ($xml as $row) {
					//set the original array id
						$channels_array[$x]['x'] = $x;

					//get the values from xml and set them to the channel array
						$channels_array[$x]['uuid'] = $row->uuid;
						$channels_array[$x]['direction'] = $row->direction;
						$channels_array[$x]['created'] = $row->created;
						$channels_array[$x]['created_epoch'] = $row->created_epoch;
						$channels_array[$x]['name'] = $row->name;
						$channels_array[$x]['state'] = $row->state;
						$channels_array[$x]['cid_name'] = $row->cid_name;
						$channels_array[$x]['cid_num'] = $row->cid_num;
						$channels_array[$x]['ip_addr'] = $row->ip_addr;
						$channels_array[$x]['dest'] = $row->dest;
						$channels_array[$x]['application'] = $row->application;
						$channels_array[$x]['application_data'] = $row->application_data;
						$channels_array[$x]['dialplan'] = $row->dialplan;
						$channels_array[$x]['context'] = $row->context;
						$channels_array[$x]['read_codec'] = $row->read_codec;
						$channels_array[$x]['read_rate'] = $row->read_rate;
						$channels_array[$x]['read_bit_rate'] = $row->read_bit_rate;
						$channels_array[$x]['write_codec'] = $row->write_codec;
						$channels_array[$x]['write_rate'] = $row->write_rate;
						$channels_array[$x]['write_bit_rate'] = $row->write_bit_rate;
						$channels_array[$x]['secure'] = $row->secure;
						$channels_array[$x]['hostname'] = $row->hostname;
						$channels_array[$x]['presence_id'] = $row->presence_id;
						$channels_array[$x]['presence_data'] = $row->presence_data;
						$channels_array[$x]['callstate'] = $row->callstate;
						$channels_array[$x]['callee_name'] = $row->callee_name;
						$channels_array[$x]['callee_num'] = $row->callee_num;
						$channels_array[$x]['callee_direction'] = $row->callee_direction;
						$channels_array[$x]['call_uuid'] = $row->call_uuid;

					//remove other domains
						if (count($_SESSION["domains"]) > 1) {
							//unset domains that are not related to this tenant
							$temp_array = explode("@", $channels_array[$x]['presence_id']);
							if ($temp_array[1] != $_SESSION['domain_name']) {
								unset($channels_array[$x]);
							}
						}

					//parse some of the php variables\
						$temp_array = explode("@", $channels_array[$x]['presence_id']);
						$channels_array[$x]['number'] = $temp_array[0];

					//remove the '+' because it breaks the call recording
						$channels_array[$x]['cid_num'] = $temp_array[0] = str_replace("+", "", $channels_array[$x]['cid_num']);

					//calculate and set the call length
						$call_length_seconds = time() - $channels_array[$x]['created_epoch'];
						$call_length_hour = floor($call_length_seconds/3600);
						$call_length_min = floor($call_length_seconds/60 - ($call_length_hour * 60));
						$call_length_sec = $call_length_seconds - (($call_length_hour * 3600) + ($call_length_min * 60));
						$call_length_min = sprintf("%02d", $call_length_min);
						$call_length_sec = sprintf("%02d", $call_length_sec);
						$call_length = $call_length_hour.':'.$call_length_min.':'.$call_length_sec;
						$channels_array[$x]['call_length'] = $call_length;

					//valet park
						//if (is_array($valet_array[$uuid])) {
						//	$valet_array[$uuid]['context'] = $channels_array[$x]['context'];
						//	$valet_array[$uuid]['cid_name'] = $channels_array[$x]['cid_name'];
						//	$valet_array[$uuid]['cid_num'] = $channels_array[$x]['cid_num'];
						//	$valet_array[$uuid]['call_length'] = $call_length;
						//}
					//increment the array index
						$x++;
				}

		//active extensions
			//get the extension information
				if ($debug) {
					unset($_SESSION['extension_array']);
				}
				if (count($_SESSION['extension_array']) == 0) {
					$sql = "";
					$sql .= "select * from v_extensions ";
					$x = 0;
					$range_array = $_GET['range'];
					foreach($range_array as $tmp_range) {
						$tmp_range = str_replace(":", "-", $tmp_range);
						$tmp_array = explode("-", $tmp_range);
						$tmp_min = $tmp_array[0];
						$tmp_max = $tmp_array[1];
						if ($x == 0) {
							$sql .= "where domain_uuid = '$domain_uuid' ";
							$sql .= "and extension >= $tmp_min ";
							$sql .= "and extension <= $tmp_max ";
							$sql .= "and enabled = 'true' ";
						}
						else {
							$sql .= "or domain_uuid = '$domain_uuid' ";
							$sql .= "and extension >= $tmp_min ";
							$sql .= "and extension <= $tmp_max ";
							$sql .= "and enabled = 'true' ";
						}
						$x++;
					}
					if (count($range_array) == 0) {
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and enabled = 'true' ";
					}
					$sql .= "order by extension asc ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						if ($row["enabled"] == "true") {
							$extension = $row["extension"];
							$extension_array[$extension]['domain_uuid'] = $row["domain_uuid"];
							$extension_array[$extension]['extension'] = $row["extension"];

							//$extension_array[$extension]['password'] = $row["password"];
							$extension_array[$extension]['mailbox'] = $row["mailbox"];
							//$vm_password = $row["vm_password"];
							//$vm_password = str_replace("#", "", $vm_password); //preserves leading zeros
							//$_SESSION['extension_array'][$extension]['vm_password'] = $vm_password;
							$extension_array[$extension]['accountcode'] = $row["accountcode"];
							$extension_array[$extension]['effective_caller_id_name'] = $row["effective_caller_id_name"];
							$extension_array[$extension]['effective_caller_id_number'] = $row["effective_caller_id_number"];
							$extension_array[$extension]['outbound_caller_id_name'] = $row["outbound_caller_id_name"];
							$extension_array[$extension]['outbound_caller_id_number'] = $row["outbound_caller_id_number"];
							$extension_array[$extension]['vm_enabled'] = $row["vm_enabled"];
							$extension_array[$extension]['vm_mailto'] = $row["vm_mailto"];
							$extension_array[$extension]['vm_attach_file'] = $row["vm_attach_file"];
							$extension_array[$extension]['vm_keep_local_after_email'] = $row["vm_keep_local_after_email"];
							$extension_array[$extension]['user_context'] = $row["user_context"];
							$extension_array[$extension]['call_group'] = $row["call_group"];
							$extension_array[$extension]['auth_acl'] = $row["auth_acl"];
							$extension_array[$extension]['cidr'] = $row["cidr"];
							$extension_array[$extension]['sip_force_contact'] = $row["sip_force_contact"];
							//$extension_array[$extension]['enabled'] = $row["enabled"];
							$extension_array[$extension]['effective_caller_id_name'] = $row["effective_caller_id_name"];
						}
					}
					$_SESSION['extension_array'] = $extension_array;
				}

			//get a list of assigned extensions for this user
				include "calls_active_assigned_extensions_inc.php";

			//list all extensions
				if (permission_exists('extension_active_list_view')) {
					echo "<table width='100%' border='0' cellpadding='5' cellspacing='0'>\n";
					echo "<tr>\n";
					echo "<td valign='top'>\n";

					echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
					echo "<tr>\n";
					echo "<th width='50px;'>".$text['label-ext']."</th>\n";
					if ($_SESSION['user_status_display'] == "false") {
						//hide the user_status when it is set to false
					}
					else {
						echo "<th>".$text['label-status']."</th>\n";
					}
					echo "<th>".$text['label-time']."</th>\n";
					if (if_group("admin") || if_group("superadmin")) {
						if (strlen(($_GET['rows'])) == 0) {
							echo "<th>".$text['label-cid-name']."</th>\n";
							echo "<th>".$text['label-cid-number']."</th>\n";
							echo "<th>".$text['label-destination']."</th>\n";
							echo "<th>".$text['label-app']."</th>\n";
							echo "<th>".$text['label-secure']."</th>\n";
						}
					}
					echo "<th>".$text['label-name']."</th>\n";
					if (if_group("admin") || if_group("superadmin")) {
						if (strlen(($_GET['rows'])) == 0) {
							echo "<th>".$text['label-opt']."</th>\n";
						}
					}
					echo "</tr>\n";
					$x = 1;
					
					foreach ($_SESSION['extension_array'] as $row) {
						$domain_uuid = $row['domain_uuid'];
						$extension = $row['extension'];
						$enabled = $row['enabled'];
						$effective_caller_id_name = $row['effective_caller_id_name'];

						$found_extension = false;
						foreach ($channels_array as $row) {
							//set the php variables
								foreach ($row as $key => $value) {
									$$key = $value;
								}
							//check to see if the extension is found in the channel array
								if ($number == $extension) {
									$found_extension = true;
									break;
								}
						}

						if ($found_extension) {
							if ($application == "conference") { 
								$alt_color = "background-image: url('".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/background_cell_active.gif";
							}
							switch ($application) {
							case "conference":
								$style_alternate = "style=\"color: #444444; background-image: url('".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/background_cell_conference.gif');\"";
								break;
							case "fifo":
								$style_alternate = "style=\"color: #444444; background-image: url('".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/background_cell_fifo.gif');\"";
								break;
							case "valet_park":
								$style_alternate = "style=\"color: #444444; background-image: url('".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/background_cell_fifo.gif');\"";
								break;
							default:
								$style_alternate = "style=\"color: #444444; background-image: url('".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/background_cell_active.gif');\"";
							}
							echo "<tr>\n";
							echo "<td class='".$row_style[$c]."' $style_alternate>$extension</td>\n";
							if ($_SESSION['user_status_display'] == "false") {
								//hide the user_status when it is set to false
							}
							else {
								echo "<td class='".$row_style[$c]."' $style_alternate>".$user_array[$extension]['user_status']."&nbsp;</td>\n";
							}
							echo "<td class='".$row_style[$c]."' $style_alternate width='20px;'>".$call_length."</td>\n";
							if (if_group("admin") || if_group("superadmin")) {
								if (strlen(($_GET['rows'])) == 0) {
									if (strlen($url) == 0) {
										$url = PROJECT_PATH."/app/contacts/contacts.php?search_all={cid_num}";
									}
									$url = str_replace ("{cid_num}", $cid_num, $url);
									$url = str_replace ("{cid_name}", $cid_name, $url);
									echo "<td class='".$row_style[$c]."' $style_alternate><a href='".$url."' style='color: #444444;' target='_blank'>".$cid_name."</a></td>\n";
									echo "<td class='".$row_style[$c]."' $style_alternate><a href='".$url."' style='color: #444444;' target='_blank'>".$cid_num."</a></td>\n";
								}
							}
							if (if_group("admin") || if_group("superadmin")) {
								if (strlen(($_GET['rows'])) == 0) {
									echo "<td class='".$row_style[$c]."' $style_alternate>\n";
									echo "".$dest."<br />\n";
									echo "</td>\n";
									echo "<td class='".$row_style[$c]."' $style_alternate>\n";
									if ($application == "fifo") {
										echo "queue &nbsp;\n";
									}
									else {
										echo $application." &nbsp;\n";
									}
									echo "</td>\n";
									echo "<td class='".$row_style[$c]."' $style_alternate>\n";
									echo "".$secure."<br />\n";
									echo "</td>\n";
								}
							}
						}
						else {
							$style_alternate = "style=\"color: #444444; background-image: url('".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/background_cell_light.gif');\"";
							echo "<tr>\n";
							echo "<td class='".$row_style[$c]."' $style_alternate>$extension</td>\n";
							if ($_SESSION['user_status_display'] == "false") {
								//hide the user_status when it is set to false
							}
							else {
								echo "<td class='".$row_style[$c]."' $style_alternate>".$user_array[$extension]['user_status']."&nbsp;</td>\n";
							}
							echo "<td class='".$row_style[$c]."' $style_alternate>&nbsp;</td>\n";
							if (if_group("admin") || if_group("superadmin")) {
								if (strlen(($_GET['rows'])) == 0) {
									echo "<td class='".$row_style[$c]."' $style_alternate>&nbsp;</td>\n";
									echo "<td class='".$row_style[$c]."' $style_alternate>&nbsp;</td>\n";
									echo "<td class='".$row_style[$c]."' $style_alternate>&nbsp;</td>\n";
									echo "<td class='".$row_style[$c]."' $style_alternate>&nbsp;</td>\n";
									echo "<td class='".$row_style[$c]."' $style_alternate>&nbsp;</td>\n";
								}
							}
						}

						echo "<td valign='top' class='".$row_style[$c]."' $style_alternate>\n";
						echo "	".$effective_caller_id_name."&nbsp;\n";
						echo "</td>\n";

						if (if_group("admin") || if_group("superadmin")) {
							if (strlen(($_GET['rows'])) == 0) {
								if ($found_extension) {
									echo "<td valign='top' class='".$row_style[$c]."' $style_alternate>\n";
										//transfer
											echo "	<a href='javascript:void(0);' style='color: #444444;' onclick=\"send_cmd('calls_exec.php?cmd='+get_transfer_cmd(escape('$uuid')));\">".$text['label-transfer']."</a>&nbsp;\n";
										//park
											echo "	<a href='javascript:void(0);' style='color: #444444;' onclick=\"send_cmd('calls_exec.php?cmd='+get_park_cmd(escape('$uuid')));\">".$text['label-park']."</a>&nbsp;\n";
										//hangup
											echo "	<a href='javascript:void(0);' style='color: #444444;' onclick=\"confirm_response = confirm('".$text['confirm-hangup']."');if (confirm_response){send_cmd('calls_exec.php?cmd=uuid_kill%20'+(escape('$uuid')));}\">".$text['label-hangup']."</a>&nbsp;\n";
										//record start/stop
											$tmp_file = $_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/".$uuid.".wav";
											if (file_exists($tmp_file)) {
												//stop
												echo "	<a href='javascript:void(0);' style='color: #444444;' onclick=\"send_cmd('calls_exec.php?cmd='+get_record_cmd(escape('$uuid'), 'active_extensions_', escape('$cid_num'))+'&uuid='+escape('$uuid')+'&action=record&action2=stop&prefix=active_extensions_&name='+escape('$cid_num'));\">".$text['label-stop']."</a>&nbsp;\n";
											}
											else {
												//start
												echo "	<a href='javascript:void(0);' style='color: #444444;' onclick=\"send_cmd('calls_exec.php?cmd='+get_record_cmd(escape('$uuid'), 'active_extensions_', escape('$cid_num'))+'&uuid='+escape('$uuid')+'&action=record&action2=start&prefix=active_extensions_');\">".$text['label-start']."</a>&nbsp;\n";
											}
										echo "	&nbsp;";
									echo "</td>\n";
								}
								else {
									echo "<td valign='top' class='".$row_style[$c]."' $style_alternate>\n";
									echo "	&nbsp;";
									echo "</td>\n";
								}
							}
						}
						echo "</tr>\n";

						if ($y == $_GET['rows'] && $_GET['rows'] > 0) {
							$y = 0;
							echo "</table>\n";

							echo "</td>\n";
							echo "<td valign='top'>\n";

							echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
							echo "<tr>\n";
							echo "<th>".$text['label-ext']."</th>\n";
							if ($_SESSION['user_status_display'] == "false") {
								//hide the user_status when it is set to false
							}
							else {
								echo "<th>".$text['label-status']."</th>\n";
							}
							echo "<th>".$text['label-time']."</th>\n";
							if (if_group("admin") || if_group("superadmin")) {
								if ($_GET['rows'] == 0) {
									echo "<th>".$text['label-cid-name']."</th>\n";
									echo "<th>".$text['label-cid-number']."</th>\n";
									echo "<th>".$text['label-destination']."</th>\n";
									echo "<th>".$text['label-app']."</th>\n";
									echo "<th>".$text['label-secure']."</th>\n";
								}
							}
							echo "<th>".$text['label-name']."</th>\n";
							if (if_group("admin") || if_group("superadmin")) {
								if ($_GET['rows'] == 0) {
									echo "<th>".$text['label-opt']."</th>\n";
								}
							}
							echo "</tr>\n";
						}
						$y++;
						if ($c==0) { $c=1; } else { $c=0; }
					}

				echo "</table>\n";
				echo "<br /><br />\n";
				//valet park
					//echo "<table width='100%' border='0' cellpadding='5' cellspacing='0'>\n";
					//echo "<tr>\n";
					//echo "<th valign='top'>".$text['label-park-ext']."</th>\n";
					//echo "<th valign='top'>".$text['label-time']."</th>\n";
					//echo "<th valign='top'>".$text['label-cid-name']."</th>\n";
					//echo "<th valign='top'>".$text['label-cid-number']."</th>\n";
					//echo "</tr>\n";
					//foreach ($valet_array as $row) {
					//	if (strlen($row['extension']) > 0) {
					//		if ($row['context'] == $_SESSION['domain_name'] || $row['context'] == "default") {
					//			echo "<tr>\n";
					//			echo "<td valign='top' class='".$row_style[$c]."' >*".$row['extension']."</td>\n";
					//			echo "<td valign='top' class='".$row_style[$c]."' >".$row['call_length']."</td>\n";
					//			echo "<td valign='top' class='".$row_style[$c]."' >".$row['cid_name']."</td>\n";
					//			echo "<td valign='top' class='".$row_style[$c]."' >".$row['cid_num']."</td>\n";
					//			echo "</tr>\n";
					//		}
					//	}
					//}
					//echo "<table>\n";

			} //end permission

		echo "<br /><br />\n";

		if ($user_status == "Available (On Demand)") {
			$user_status = "Available_On_Demand";
		}
		$user_status = str_replace(" ", "_", $user_status);
		echo "<span id='db_user_status' style='visibility:hidden;'>$user_status</span>\n";
		echo "<div id='cmd_reponse'>\n";
		echo "</div>\n";
	}
?>