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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Riccardo Granchi <riccardo.granchi@nems.it>
*/
require_once "root.php";
require_once "resources/require.php";

//preferences
	$v_menu_tab_show = false;
	$v_path_show = true;

//get user defined variables
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/vars/app_config.php")) {
		if (strlen($_SESSION['user_defined_variables']) == 0) {
			$sql = "select * from v_vars ";
			$sql .= "where var_cat = 'Defaults' and var_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			foreach ($result as &$row) {
				switch ($row["var_name"]) {
					case "domain":
						//not allowed to override this value
						break;
					case "domain_name":
						//not allowed to override this value
						break;
					case "domain_uuid":
						//not allowed to override this value
						break;
					case "username":
						//not allowed to override this value
						break;
					case "groups":
						//not allowed to override this value
						break;
					case "menu":
						//not allowed to override this value
						break;
					case "template_name":
						//not allowed to override this value
						break;
					case "template_content":
						//not allowed to override this value
						break;
					case "extension_array":
						//not allowed to override this value
						break;
					case "user_extension_array":
						//not allowed to override this value
						break;
					case "user_array":
						//not allowed to override this value
						break;
					default:
						$_SESSION[$row["var_name"]] = $row["var_value"];
				}
			}
			//when this value is cleared it will re-read the user defined variables
			$_SESSION["user_defined_variables"] = "set";
		}
	}

/*
function v_settings() {
	global $db, $domain_uuid;

	//get the program directory
		$program_dir = '';
		$doc_root = $_SERVER["DOCUMENT_ROOT"];
		$doc_root = str_replace ("\\", "/", $doc_root);
		$doc_root_array = explode("/", $doc_root);
		$doc_root_array_count = count($doc_root_array);
		$x = 0;
		foreach ($doc_root_array as $value) {
			$program_dir = $program_dir.$value."/";
			if (($doc_root_array_count-3) == $x) {
				break;
			}
			$x++;
		}
		$program_dir = rtrim($program_dir, "/");

	//get the domains variables
		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$name = $row['domain_setting_name'];
				$settings_array[$name] = $row['domain_setting_value'];
			}
		}

	//get the server variables
		$sql = "select * from v_server_settings ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$name = $row['server_setting_name'];
				$settings_array[$name] = $row['server_setting_value'];
			}
		}

	//return the results
		return $settings_array;
}
//update the settings
//$settings_array = v_settings();
foreach($settings_array as $name => $value) {
	$$name = $value;
}
*/

//get the event socket information
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/settings/app_config.php")) {
		if (strlen($_SESSION['event_socket_ip_address']) == 0) {
			$sql = "select * from v_settings ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				foreach ($result as &$row) {
					$_SESSION['event_socket_ip_address'] = $row["event_socket_ip_address"];
					$_SESSION['event_socket_port'] = $row["event_socket_port"];
					$_SESSION['event_socket_password'] = $row["event_socket_password"];
					break; //limit to 1 row
				}
			}
		}
	}

//get the extensions that are assigned to this user
function load_extensions() {
	global $db;
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/extensions/app_config.php")) {
		if (strlen($_SESSION["domain_uuid"]) > 0 && strlen($_SESSION["user_uuid"]) > 0 && count($_SESSION['user']['extension']) == 0) {
			//get the user extension list
				unset($_SESSION['user']['extension']);
				$sql = "select ";
				$sql .= "	e.extension, ";
				$sql .= "	e.number_alias, ";
				$sql .= "	e.user_context, ";
				$sql .= "	e.extension_uuid, ";
				$sql .= "	e.outbound_caller_id_name, ";
				$sql .= "	e.outbound_caller_id_number, ";
				$sql .= "	v.voicemail_uuid ";
				$sql .= "from ";
				$sql .= "	v_extension_users as u, ";
				$sql .= "	v_extensions as e ";
				$sql .= "		left outer join v_voicemails as v on ( ";
				$sql .= "			e.domain_uuid = v.domain_uuid ";
				$sql .= "			and v.voicemail_enabled = 'true' ";
				$sql .= "			and ( ";
				$sql .= "				e.extension = v.voicemail_id ";
				$sql .= "				or e.number_alias = v.voicemail_id ";
				$sql .= "			) ";
				$sql .= "		) ";
				$sql .= "where ";
				$sql .= "	e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "	and e.extension_uuid = u.extension_uuid ";
				$sql .= "	and u.user_uuid = '".$_SESSION['user_uuid']."' ";
				$sql .= "	and e.enabled = 'true' ";
				$sql .= "order by ";
				$sql .= "	e.extension asc ";
				$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$x = 0;
					foreach($result as $row) {
						$_SESSION['user']['extension'][$x]['user'] = $row['extension'];
						$_SESSION['user']['extension'][$x]['number_alias'] = $row['number_alias'];
						$_SESSION['user']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
						$_SESSION['user']['extension'][$x]['outbound_caller_id_name'] = $row['outbound_caller_id_name'];
						$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
						if ($row['voicemail_uuid'] != '') {
							$_SESSION['user']['voicemail'][]['voicemail_uuid'] = $row['voicemail_uuid'];
						}
						$_SESSION['user_context'] = $row["user_context"];
						$x++;
					}
				}
			//if no extension has been assigned then setting user_context will still need to be set
				if (strlen($_SESSION['user_context']) == 0) {
					if (count($_SESSION['domains']) == 1) {
						$_SESSION['user_context'] = "default";
					}
					else {
						$_SESSION['user_context'] = $_SESSION['domain_name'];
					}
				}
		}
	}
}
load_extensions();

function build_menu() {
	global $v_menu_tab_show;

	if ($v_menu_tab_show) {
		global $config;
		if (is_dir($_SERVER["DOCUMENT_ROOT"].'/fusionpbx')){ $relative_url = $_SERVER["DOCUMENT_ROOT"].'/fusionpbx'; } else { $relative_url = '/'; }

		$tab_array = array();
		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/setting_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Settings"), $menu_selected, $relative_url."/setting_edit.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplans.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplans.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplan_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplan_details_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Dialplan"), $menu_selected, $relative_url."/dialplans.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/extensions.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/extension_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Extensions"), $menu_selected, $relative_url."/extensions.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/fax.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/fax_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/hunt_group.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/hunt_group_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/hunt_group_destinations.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/hunt_group_destinations_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/auto_attendant.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/auto_attendant_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/auto_attendant_options_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/modules.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/recordings.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/recording_edit.php") { $menu_selected = true; }
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/gateways.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/gateway_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Gateways"), $menu_selected, $relative_url."/gateways.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/sip_profiles.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/sip_profile_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Profiles"), $menu_selected, $relative_url."/sip_profiles.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/sip_status.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Status"), $menu_selected, $relative_url."/sip_status.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/vars.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Vars"), $menu_selected, $relative_url."/vars.php");
		unset($menu_selected);
	}
}


function event_socket_create($host, $port, $password) {
	$fp = fsockopen($host, $port, $errno, $errdesc, 3);
	socket_set_blocking($fp,false);

	if (!$fp) {
		//error "invalid handle<br />\n";
		//echo "error number: ".$errno."<br />\n";
		//echo "error description: ".$errdesc."<br />\n";
	}
	else {
		//connected to the socket return the handle
		while (!feof($fp)) {
			$buffer = fgets($fp, 1024);
			usleep(100); //allow time for reponse
			if (trim($buffer) == "Content-Type: auth/request") {
				 fputs($fp, "auth $password\n\n");
				 break;
			}
		}
		return $fp;
	}
} //end function


function event_socket_request($fp, $cmd) {
	if ($fp) {
		$cmd_array = explode("\n",$cmd);
		foreach ($cmd_array as &$value) {
		    fputs($fp, $value."\n");
		}
		fputs($fp, "\n"); //second line feed to end the headers

		usleep(100); //allow time for reponse

		$response = "";
		$i = 0;
		$content_length = 0;
		while (!feof($fp)) {
			$buffer = fgets($fp, 4096);
			if ($content_length > 0) {
				$response .= $buffer;
			}

			if ($content_length == 0) { //if the content has length don't process again
				if (strlen(trim($buffer)) > 0) { //run only if buffer has content
					$array = explode(":", trim($buffer));
					if ($array[0] == "Content-Length") {
						$content_length = trim($array[1]);
					}
				}
			}

			usleep(50); //allow time for reponse

			//prevent an endless loop //optional because of script timeout
			if ($i > 30000) { break; }

			if ($content_length > 0) { //is content_length set
				//stop reading if all content has been read.
				if (strlen($response) >= $content_length) {
					break;
				}
			}
			$i++;
		}

		return $response;
	}
	else {
		echo "no handle";
	}
}


function event_socket_request_cmd($cmd) {
	global $db, $domain_uuid, $host;

	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/settings/app_config.php")) {
		$sql = "select * from v_settings ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			$event_socket_ip_address = $row["event_socket_ip_address"];
			$event_socket_port = $row["event_socket_port"];
			$event_socket_password = $row["event_socket_password"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

	$fp = event_socket_create($event_socket_ip_address, $event_socket_port, $event_socket_password);
	$response = event_socket_request($fp, $cmd);
	fclose($fp);
}

function byte_convert($bytes, $decimals = 2) {
	if ($bytes <= 0) { return '0 Bytes'; }
	$convention = 1024;
	$formattedbytes = array_reduce( array(' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', 'ZB'), create_function( '$a,$b', 'return is_numeric($a)?($a>='.$convention.'?$a/'.$convention.':number_format($a,'.$decimals.').$b):$a;' ), $bytes );
	return $formattedbytes;
}

function ListFiles($dir) {
	if($dh = opendir($dir)) {
		$files = Array();
		$inner_files = Array();

		while($file = readdir($dh)) {
			if($file != "." && $file != ".." && $file[0] != '.') {
				if(is_dir($dir . "/" . $file)) {
					//$inner_files = ListFiles($dir . "/" . $file); //recursive
					if(is_array($inner_files)) $files = array_merge($files, $inner_files);
			} else {
					array_push($files, $file);
					//array_push($files, $dir . "/" . $file);
				}
			}
		}
		closedir($dh);
		return $files;
	}
}

function switch_select_destination($select_type, $select_label, $select_name, $select_value, $select_style, $action='', $onchange='', $text_append=' ...') {
	//select_type can be ivr, dialplan, call_center_contact or bridge
	global $text, $config, $db, $domain_uuid;

	//remove special characters from the name
		$select_id = str_replace("]", "", $select_name);
		$select_id = str_replace("[", "_", $select_id);

	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput".$select_id."(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.className='formfld';\n";
		echo "	tb.setAttribute('id', '".$select_id."');\n";
		echo "	tb.setAttribute('style', '".$select_style."');\n";
		if ($onchange != '') {
			echo "	tb.setAttribute('onchange', \"".$onchange."\");\n";
			echo "	tb.setAttribute('onkeyup', \"".$onchange."\");\n";
		}
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	document.getElementById('btn_select_to_input_".$select_id."').style.visibility = 'hidden';\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "	tbb.objs=[obj,tb,tbb];\n";
		echo "	tbb.onclick=function(){ Replace".$select_id."(this.objs); }\n";
		echo "	obj.parentNode.insertBefore(tb,obj);\n";
		echo "	obj.parentNode.insertBefore(tbb,obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "	Replace".$select_id."(this.objs);\n";
		echo "}\n";
		echo "\n";
		echo "function Replace".$select_id."(obj){\n";
		echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "	obj[0].parentNode.removeChild(obj[1]);\n";
		echo "	obj[0].parentNode.removeChild(obj[2]);\n";
		echo "	document.getElementById('btn_select_to_input_".$select_id."').style.visibility = 'visible';\n";
		if ($onchange != '') {
			echo "	".$onchange.";\n";
		}
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}

	//default selection found to false
		$selection_found = false;

	echo "	<select name='".$select_name."' id='".$select_id."' class='formfld' style='".$select_style."' onchange=\"".$onchange."\">\n";

	//display a default select label (specified explicitly or from the language file) if no selected value is passed in
		if ($select_value == '' && $select_label != '' && $select_label != ' ') {
			$options[] = "<option value=''>".$select_label."</option>";
			$options[] = "<option value='' disabled='disabled'></option>";
		}
		else if ($select_value == '' && $select_label == '' && $text['label-select_destination'] != '') {
			$options[] = "<option value=''>".$text['label-select_destination']."</option>";
			$options[] = "<option value='' disabled='disabled'></option>";
		}
		else {
			$options[] = "<option value=''></option>";
		}

	//call center queues
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/call_center/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_call_center_queues ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "order by queue_name asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Call Center'>";
					$previous_call_center_name = "";
					foreach ($result as &$row) {
						$queue_name = $row["queue_name"];
						$queue_name = str_replace('_${domain_name}@default', '', $queue_name);
						$queue_extension = $row["queue_extension"];
						if ($previous_call_center_name != $queue_name) {
							$selected = (
								$select_value == "transfer:".$queue_extension." XML ".$_SESSION["context"] ||
								$select_value == "menu-exec-app:transfer ".$queue_extension." XML ".$_SESSION["context"]
								) ? true : false;
							if ($select_type == "ivr") {
								$options[] = "<option value='menu-exec-app:transfer ".$queue_extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$queue_extension." ".$queue_name."</option>";
							}
							if ($select_type == "dialplan") {
								$options[] = "<option value='transfer:".$queue_extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$queue_extension." ".$queue_name."</option>";
							}
							if ($selected) { $selection_found = true; }
							$previous_call_center_name = $queue_name;
						}
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement);
			}
		}

	//call flows
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/call_flows/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_call_flows ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "order by call_flow_extension asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Call Flows'>";
					foreach ($result as &$row) {
						$call_flow_name = $row["call_flow_name"];
						$call_flow_extension = $row["call_flow_extension"];
						$call_flow_context = $row["call_flow_context"];
						$selected = (
							$select_value == "transfer ".$call_flow_extension." XML ".$call_flow_context ||
							$select_value == "transfer:".$call_flow_extension." XML ".$call_flow_context ||
							$select_value == "menu-exec-app:transfer ".$call_flow_extension." XML ".$call_flow_context
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$call_flow_extension." XML ".$call_flow_context."' ".(($selected) ? "selected='selected'" : null).">".$call_flow_extension." ".$call_flow_name."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$call_flow_extension." XML ".$call_flow_context."' ".(($selected) ? "selected='selected'" : null).">".$call_flow_extension." ".$call_flow_name."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement, $call_flow_extension);
			}
		}

	//call groups
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/extensions/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select distinct(call_group) from v_extensions ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and call_group <> '' ";
				$sql .= "order by call_group asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$x = 0;
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$previous_call_group_name = "";
					$options[] = "<optgroup label='Call Group'>";
					foreach ($result as &$row) {
						$call_groups = $row["call_group"];
						$call_group_array = explode(",", $call_groups);
						foreach ($call_group_array as $call_group) {
							$call_group = trim($call_group);
							if ($previous_call_group_name != $call_group) {
								$selected = (
									$select_value == "bridge:group/".$call_group."@".$_SESSION['domain_name'] ||
									$select_value == "menu-exec-app:bridge group/".$call_group."@".$_SESSION['domain_name']
									) ? true : false;
								if ($select_type == "ivr") {
									$options[] = "<option value='menu-exec-app:bridge group/".$call_group."@".$_SESSION['domain_name']."' ".(($selected) ? "selected='selected'" : null).">".$call_group."</option>";
								}
								if ($select_type == "dialplan") {
									$options[] = "<option value='bridge:group/".$call_group."@".$_SESSION['domain_name']."' ".(($selected) ? "selected='selected'" : null).">".$call_group."</option>";
								}
								if ($selected) { $selection_found = true; }
								$previous_call_group_name = $call_group;
							}
						}
						$x++;
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement);
			}
		}

	//conference centers
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/conference_centers/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_conference_centers ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "order by conference_center_name asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Conference Centers'>";
					foreach ($result as &$row) {
						$name = $row["conference_center_name"];
						$extension = $row["conference_center_extension"];
						$description = $row["conference_center_description"];
						$selected = (
							$select_value == "transfer ".$extension." XML ".$_SESSION['context'] ||
							$select_value == "transfer:".$extension." XML ".$_SESSION['context'] ||
							$select_value == "menu-exec-app:transfer ".$extension." XML ".$_SESSION['context']
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$extension." XML ".$_SESSION['context']."' ".(($selected) ? "selected='selected'" : null).">".$name." ".$description."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$extension." XML ".$_SESSION['context']."' ".(($selected) ? "selected='selected'" : null).">".$name." ".$description."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
					unset ($prep_statement);
				}
			}
		}

	//conferences
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/conferences/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_conferences ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "order by conference_name asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Conferences'>";
					foreach ($result as &$row) {
						$name = $row["conference_name"];
						$extension = $row["conference_extension"];
						$description = $row["conference_description"];
						$selected = (
							$select_value == "transfer ".$extension." XML ".$_SESSION['context'] ||
							$select_value == "transfer:".$extension." XML ".$_SESSION['context'] ||
							$select_value == "menu-exec-app:transfer ".$extension." XML ".$_SESSION['context']
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$extension." XML ".$_SESSION['context']."' ".(($selected) ? "selected='selected'" : null).">".$name." ".$description."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$extension." XML ".$_SESSION['context']."' ".(($selected) ? "selected='selected'" : null).">".$name." ".$description."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement);
			}
		}

	//extensions
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/extensions/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
				$sql = "select * from v_extensions ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and enabled = 'true' ";
				$sql .= "order by extension asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Extensions'>";
					foreach ($result as &$row) {
						$extension = $row["extension"];
						$context = $row["user_context"];
						$description = $row["description"];
						$selected = (
							$select_value == "user/".$extension."@".$_SESSION['domains'][$domain_uuid]['domain_name'] ||
							$select_value == "transfer:".$extension." XML ".$context ||
							$select_value == "menu-exec-app:transfer ".$extension." XML ".$context
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$extension." XML ".$context."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$description."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$extension." XML ".$context."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$description."</option>";
						}
						if ($select_type == "call_center_contact") {
							$options[] = "<option value='user/".$extension."@".$_SESSION['domains'][$domain_uuid]['domain_name']."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$description."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement, $extension);
			}
		}

	//fax extensions
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/fax/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_fax ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "order by fax_extension asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='FAX'>";
					foreach ($result as &$row) {
						$fax_name = $row["fax_name"];
						$extension = $row["fax_extension"];
						$selected = (
							$select_value == "transfer ".$extension." XML ".$_SESSION["context"] ||
							$select_value == "transfer:".$extension." XML ".$_SESSION["context"] ||
							$select_value == "menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$fax_name."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$fax_name."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement, $extension);
			}
		}

	//fifo queues
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/fifo/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_dialplan_details ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and dialplan_detail_type = 'fifo' ";
				$sql .= "and dialplan_detail_data not like '%@${domain_name} in%' ";
				$sql .= "order by dialplan_detail_data asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Queues (FIFO)'>";
					foreach ($result as &$row) {
						$dialplan_uuid = $row["dialplan_uuid"];
						//get the extension number using the dialplan_uuid
							$sql = "select dialplan_detail_data as extension_number ";
							$sql .= "from v_dialplan_details ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
							$sql .= "and dialplan_detail_type = 'destination_number' ";
							$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
							$extension_number = $tmp['extension_number'];
							$extension_number = ltrim($extension_number, "^");
							$extension_number = ltrim($extension_number, "\\");
							$extension_number = rtrim($extension_number, "$");
							unset($tmp);

						//get the extension number using the dialplan_uuid
							$sql = "select * ";
							$sql .= "from v_dialplans ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
							$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
							$dialplan_name = $tmp['dialplan_name'];
							$dialplan_name = str_replace("_", " ", $dialplan_name);
							unset($tmp);

						$fifo_name = $row["dialplan_detail_data"];
						$fifo_name = str_replace('@${domain_name} in', '', $fifo_name);
						$option_label = $extension_number.' '.$dialplan_name;
						if ($select_type == "ivr") {
							$selected = ("menu-exec-app:transfer ".$row["dialplan_detail_data"] == $select_value) ? true : false;
							$options[] = "<option value='menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$option_label."</option>";
							if ($selected) { $selection_found = true; }
						}
						if ($select_type == "dialplan") {
							$selected = ("transfer:".$extension_number." XML ".$_SESSION["context"] == $select_value) ? true : false;
							$options[] = "<option value='transfer:".$extension_number." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$option_label."</option>";
							if ($selected) { $selection_found = true; }
						}
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement);
			}
		}

	//gateways
		if (if_group("superadmin")) {
			$sql = "select v_gateways.gateway_uuid, v_gateways.gateway, v_domains.domain_name from v_gateways ";
			$sql .= "inner join v_domains on v_gateways.domain_uuid=v_domains.domain_uuid ";
			$sql .= "where enabled = 'true' ";
			$sql .= "order by gateway asc ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$tmp_selected = '';
			if (count($result) > 0) {
				if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact" || $select_type == "bridge") {
					$options[] = "<optgroup label='Gateways'>";
				}
				foreach($result as $row) {
					switch ($select_type) {
						case "dialplan":
							$default_value = 'bridge:sofia/gateway/'.$row['gateway_uuid'].'/';
							$selected = (strpos($select_value, $default_value) === 0) ? true : false;
							$value = ($selected) ? $select_value : $default_value.$text_append;
							$flag = ($selected && $select_value != $default_value.$text_append) ? "*" : null;
							$options[] = "<option value='".$value."' ".(($selected) ? "selected='selected'" : null).">".$row['gateway']."@".$row['domain_name']." ".$flag."</option>";
							break;
						case "bridge":
							$default_value = 'sofia/gateway/'.$row['gateway_uuid'].'/';
							$selected = (strpos($select_value, $default_value) === 0) ? true : false;
							$value = ($selected) ? $select_value : $default_value.$text_append;
							$flag = ($selected && $select_value != $default_value.$text_append) ? "*" : null;
							$options[] = "<option value='".$value."' ".(($selected) ? "selected='selected'" : null).">".$row['gateway']."@".$row['domain_name']." ".$flag."</option>";
							break;
						case "ivr":
							$default_value = 'menu-exec-app:bridge sofia/gateway/'.$row['gateway_uuid'].'/';
							$selected = (strpos($select_value, $default_value) === 0) ? true : false;
							$value = ($selected) ? $select_value : $default_value.$text_append;
							$flag = ($selected && $select_value != $default_value.$text_append) ? "*" : null;
							$options[] = "<option value='".$value."' ".(($selected) ? "selected='selected'" : null).">".$row['gateway']."@".$row['domain_name']." ".$flag."</option>";
							break;
						case "call_center_contact":
							if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/conference_centers/app_config.php")) {
								$default_value = 'sofia/gateway/'.$row['gateway_uuid'].'/';
								$selected = (strpos($select_value, $default_value) === 0) ? true : false;
								$value = ($selected) ? $select_value : $default_value.$text_append;
								$flag = ($selected && $select_value != $default_value.$text_append) ? "*" : null;
								$options[] = "<option value='".$value."' ".(($selected) ? "selected='selected'" : null).">".$row['gateway']."@".$row['domain_name']." ".$flag."</option>";
							}
							break;
					}
					if ($selected) { $selection_found = true; }
				}
				if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
					$options[] = "</optgroup>";
				}
			}
			unset($prep_statement, $sql, $result, $value, $default_value, $flag);
		}


	//xmpp
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/xmpp/app_config.php")) {
			if (if_group("superadmin") && $select_type == "bridge") {
				$sql = "select v_xmpp.xmpp_profile_uuid, v_xmpp.profile_name, v_domains.domain_name from v_xmpp ";
				$sql .= "inner join v_domains on v_xmpp.domain_uuid=v_domains.domain_uuid ";
				$sql .= "where enabled = 'true' ";
				$sql .= "order by profile_name asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				unset ($prep_statement, $sql);
				$tmp_selected = '';
				if (count($result) > 0) {
					$options[] = "<optgroup label='XMPP Gateways'>";
					foreach($result as $row) {
						$selected = ('dingaling/'.$row['profile_name'].'/' == $select_value) ? true : false;
						$options[] = "<option value='dingaling/".$row['profile_name']."/' ".(($selected) ? "selected='selected'" : null).">".$row['profile_name']."@".$row['domain_name']."</option>";
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset($sql, $result);
			}
		}

	//hunt groups
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/hunt_groups/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_hunt_groups ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and hunt_group_enabled = 'true' ";
				$sql .= "and ( ";
				$sql .= "hunt_group_type = 'simultaneous' ";
				$sql .= "or hunt_group_type = 'sequence' ";
				$sql .= "or hunt_group_type = 'sequentially' ";
				$sql .= ") ";
				$sql .= "order by hunt_group_extension asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Hunt Groups'>";
					foreach ($result as &$row) {
						$extension = $row["hunt_group_extension"];
						$hunt_group_name = $row["hunt_group_name"];
						$selected = (
							$select_value == "transfer ".$extension." XML ".$_SESSION["context"] ||
							$select_value == "transfer:".$extension." XML ".$_SESSION["context"] ||
							$select_value == "menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$hunt_group_name."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$hunt_group_name."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement, $extension);
			}
		}

	//ivr menus
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/ivr_menu/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_ivr_menus ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and ivr_menu_enabled = 'true' ";
				$sql .= "order by ivr_menu_extension asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='IVR Menu'>";
					foreach ($result as &$row) {
						$extension = $row["ivr_menu_extension"];
						$extension_name = $row["ivr_menu_name"];
						$extension_label = $row["ivr_menu_name"];
						$extension_name = str_replace(" ", "_", $extension_name);
						if (count($_SESSION["domains"]) > 1) {
							$extension_name =  $_SESSION['domains'][$row['domain_uuid']]['domain_name'].'-'.$extension_name;
						}
						$selected = (
							$select_value == "ivr ".$extension_name ||
							$select_value == "ivr:".$extension_name ||
							$select_value == "menu-exec-app:ivr ".$extension ||
							$select_value == "transfer ".$extension." XML ".$_SESSION["context"] ||
							$select_value == "transfer:".$extension." XML ".$_SESSION["context"] ||
							$select_value == "menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$extension_label."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$extension." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$extension_label."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement, $extension);
			}
		}

	//languages
		if ($select_type == "dialplan" || $select_type == "ivr") {
			$options[] = "<optgroup label='Language'>";
		}
		$tmp_lang_options = array(
			'nl' => 'Dutch',
			'en' => 'English',
			'fr' => 'French',
			'it' => 'Italian',
			'de' => 'German',
			'pt-pt' => 'Portuguese (Portugal)',
			'pt-br' => 'Portuguese (Brazil)',
			'es' => 'Spanish');
		foreach ($tmp_lang_options as $tmp_lang_option_abbr => $tmp_lang_option_name) {
			$selected = (
				$select_value == "menu-exec-app:set default_language=".$tmp_lang_option_abbr ||
				$select_value == "set:default_language=".$tmp_lang_option_abbr
				) ? true : false;
			if ($select_type == "ivr") {
				$options[] = "	<option value='menu-exec-app:set default_language=".$tmp_lang_option_abbr."' ".(($selected) ? "selected='selected'" : null).">".$tmp_lang_option_name."</option>";
			}
			if ($select_type == "dialplan") {
				$options[] = "	<option value='set:default_language=".$tmp_lang_option_abbr."' ".(($selected) ? "selected='selected'" : null).">".$tmp_lang_option_name."</option>";
			}
			if ($selected) { $selection_found = true; }
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			$options[] = "</optgroup>";
		}
		unset($tmp_lang_options);

	//recordings
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/recordings/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_recordings ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "order by recording_name asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Recordings'>";
					foreach ($result as &$row) {
						$recording_name = $row["recording_name"];
						$recording_filename = $row["recording_filename"];
						$path_mod = ($_SESSION['recordings']['storage_type']['text'] != 'base64') ? $_SESSION['switch']['recordings']['dir']."/" : null;
						if ($select_type == "dialplan") {
							$execute_method = 'lua:';
						}
						else if ($select_type == "ivr") {
							$execute_method = 'menu-exec-app:lua ';
						}
						$selected = (
							$select_value == $execute_method."streamfile.lua ".$_SESSION['switch']['recordings']['dir']."/".$recording_filename ||
							$select_value == $execute_method."streamfile.lua ".$recording_filename
							) ? true : false;
						$options[] = "<option value='".$execute_method."streamfile.lua ".$path_mod.$recording_filename."' ".(($selected) ? "selected='selected'" : null).">".$recording_name."</option>";
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
			}
		}

	//phrases
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/phrases/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_phrases where domain_uuid = '".$domain_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Phrases'>";
					foreach ($result as &$row) {
						$selected = ($select_value == "phrase:".$row["phrase_name"].".".$domain_uuid) ? true : false;
						$options[] = "<option value='phrase:".$row["phrase_name"].".".$domain_uuid."' ".(($selected) ? "selected='selected'" : null).">".$row["phrase_name"]."</option>";
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement);
			}
		}

	//ring groups
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/ring_groups/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_ring_groups ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and ring_group_enabled = 'true' ";
				$sql .= "order by ring_group_extension asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Ring Groups'>";
					foreach ($result as &$row) {
						$extension = $row["ring_group_extension"];
						$context = $row["ring_group_context"];
						$description = $row["ring_group_description"];
						$selected = (
							$select_value == "transfer ".$extension." XML ".$context ||
							$select_value == "transfer:".$extension." XML ".$context ||
							$select_value == "menu-exec-app:transfer ".$extension." XML ".$context
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ".$extension." XML ".$context."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$description."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:".$extension." XML ".$context."' ".(($selected) ? "selected='selected'" : null).">".$extension." ".$description."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
			}
		}

	//time conditions
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/time_conditions/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_dialplan_details ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$x = 0;
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						switch ($row['dialplan_detail_type']) {
							case "hour":
							case "minute":
							case "minute-of-day":
							case "time-of-day":
							case "mday":
							case "mweek":
							case "mon":
							case "yday":
							case "year":
							case "wday":
							case "week":
								$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
						}
					}
					$options[] = "<optgroup label='Time Conditions'>";
					foreach($time_array as $key=>$val) {
						$dialplan_uuid = $key;
						//get the extension number using the dialplan_uuid
							$sql = "select dialplan_detail_data as extension_number ";
							$sql .= "from v_dialplan_details ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
							$sql .= "and dialplan_detail_type = 'destination_number' ";
							$sql .= "order by extension_number asc ";
							$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
							$extension_number = $tmp['extension_number'];
							$extension_number = ltrim($extension_number, "^");
							$extension_number = ltrim($extension_number, "\\");
							$extension_number = rtrim($extension_number, "$");
							unset($tmp);

						//get the extension number using the dialplan_uuid
							$sql = "select * ";
							$sql .= "from v_dialplans ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
							$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
							$dialplan_name = $tmp['dialplan_name'];
							$dialplan_name = str_replace("_", " ", $dialplan_name);
							unset($tmp);

							$option_label = $extension_number.' '.$dialplan_name;
							if ($select_type == "ivr") {
								$selected = ("menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"] == $select_value) ? true : false;
								$options[] = "<option value='menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$option_label."</option>";
								if ($selected) { $selection_found = true; }
							}
							if ($select_type == "dialplan") {
								$selected = ("transfer:".$extension_number." XML ".$_SESSION["context"] == $select_value) ? true : false;
								$options[] = "<option value='transfer:".$extension_number." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$option_label."</option>";
								if ($selected) { $selection_found = true; }
							}
					}
					$options[] = "</optgroup>";
				}
				unset ($prep_statement);
			}
		}

	//voicemail
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/voicemails/app_config.php")) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				$sql = "select * from v_voicemails ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and voicemail_enabled = 'true' ";
				$sql .= "order by voicemail_id asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$options[] = "<optgroup label='Voicemail'>";
					foreach ($result as &$row) {
						$voicemail_id = $row["voicemail_id"];
						$description = $row["voicemail_description"];
						$selected = (
							$select_value == "voicemail default \${domain_name} ".$voicemail_id ||
							$select_value == "voicemail:default \${domain_name} ".$voicemail_id ||
							$select_value == "transfer:*99".$voicemail_id." XML ".$_SESSION["context"] ||
							$select_value == "menu-exec-app:transfer *99".$voicemail_id." XML ".$_SESSION["context"]
							) ? true : false;
						if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer *99".$voicemail_id." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$voicemail_id." ".$description."</option>";
						}
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:*99".$voicemail_id." XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">".$voicemail_id." ".$description."</option>";
						}
						if ($selected) { $selection_found = true; }
					}
					$options[] = "</optgroup>";
				}
			}
		}

	//other
		if ($select_type == "dialplan" || $select_type == "ivr") {
			$options[] = "<optgroup label='Other'>";
			//set the default value
				$selected = '';
			//check voicemail
				$selected = ($select_value == "transfer:*98 XML ".$_SESSION["context"] || $select_value == "menu-exec-app:transfer *98 XML ".$_SESSION["context"]) ? true : false;
				if ($select_type == "dialplan") {
					$options[] = "<option value='transfer:*98 XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">check voicemail</option>";
				}
				else if ($select_type == "ivr") {
					$options[] = "<option value='menu-exec-app:transfer *98 XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">check voicemail</option>";
				}
				if ($selected) { $selection_found = true; }
			//company directory
				$selected = ($select_value == "transfer:*411 XML ".$_SESSION["context"] || $select_value == "menu-exec-app:transfer *411 XML ".$_SESSION["context"]) ? true : false;
				if ($select_type == "dialplan") {
					$options[] = "<option value='transfer:*411 XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">company directory</option>";
				}
				else if ($select_type == "ivr") {
					$options[] = "<option value='menu-exec-app:transfer *411 XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">company directory</option>";
				}
				if ($selected) { $selection_found = true; }
			//record
				$selected = ($select_value == "transfer:*732 XML ".$_SESSION["context"] || $select_value == "menu-exec-app:transfer *732 XML ".$_SESSION["context"]) ? true : false;
				if ($select_type == "dialplan") {
					$options[] = "<option value='transfer:*732 XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">record</option>";
				}
				else if ($select_type == "ivr") {
					$options[] = "<option value='menu-exec-app:transfer *732 XML ".$_SESSION["context"]."' ".(($selected) ? "selected='selected'" : null).">record</option>";
				}
				if ($selected) { $selection_found = true; }
			//advanced
				if (if_group("superadmin")) {
					//answer
						$selected = ($select_value == "answer" || $select_value == "menu-exec-app:answer") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='answer' ".(($selected) ? "selected='selected'" : null).">answer</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:answer' ".(($selected) ? "selected='selected'" : null).">answer</option>";
						}
						if ($selected) { $selection_found = true; }
					//hangup
						$selected = ($select_value == "hangup" || $select_value == "menu-exec-app:hangup") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='hangup' ".(($selected) ? "selected='selected'" : null).">hangup</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:hangup' ".(($selected) ? "selected='selected'" : null).">hangup</option>";
						}
						if ($selected) { $selection_found = true; }
					//info
						$selected = ($select_value == "info" || $select_value == "menu-exec-app:info") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='info' ".(($selected) ? "selected='selected'" : null).">info</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:info' ".(($selected) ? "selected='selected'" : null).">info</option>";
						}
						if ($selected) { $selection_found = true; }
					//bridge
						$selected = ($select_value == "bridge" || $select_value == "bridge:" || $select_value == "menu-exec-app:bridge") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='bridge:' ".(($selected) ? "selected='selected'" : null).">bridge</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:bridge ' ".(($selected) ? "selected='selected'" : null).">bridge</option>";
						}
						if ($selected) { $selection_found = true; }
					//db
						$selected = ($select_value == "db" || $select_value == "db:" || $select_value == "menu-exec-app:db") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='db:' ".(($selected) ? "selected='selected'" : null).">db</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:db ' ".(($selected) ? "selected='selected'" : null).">db</option>";
						}
						if ($selected) { $selection_found = true; }
					//export
						$selected = ($select_value == "export" || $select_value == "export:" || $select_value == "menu-exec-app:export") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='export:' ".(($selected) ? "selected='selected'" : null).">export</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:export ' ".(($selected) ? "selected='selected'" : null).">export</option>";
						}
						if ($selected) { $selection_found = true; }
					//global_set
						$selected = ($select_value == "global_set" || $select_value == "global_set:" || $select_value == "menu-exec-app:global_set") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='global_set:' ".(($selected) ? "selected='selected'" : null).">global_set</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:global_set ' ".(($selected) ? "selected='selected'" : null).">global_set</option>";
						}
						if ($selected) { $selection_found = true; }
					//group
						$selected = ($select_value == "group" || $select_value == "group:" || $select_value == "menu-exec-app:group") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='group:' ".(($selected) ? "selected='selected'" : null).">group</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:group ' ".(($selected) ? "selected='selected'" : null).">group</option>";
						}
						if ($selected) { $selection_found = true; }
					//javascript
						$selected = ($select_value == "javascript" || $select_value == "javascript:" || $select_value == "menu-exec-app:javascript") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='javascript:' ".(($selected) ? "selected='selected'" : null).">javascript</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:javascript ' ".(($selected) ? "selected='selected'" : null).">javascript</option>";
						}
						if ($selected) { $selection_found = true; }
					//lua
						$selected = ($select_value == "lua" || $select_value == "lua:" || $select_value == "menu-exec-app:lua") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='lua:' ".(($selected) ? "selected='selected'" : null).">lua</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:lua ' ".(($selected) ? "selected='selected'" : null).">lua</option>";
						}
						if ($selected) { $selection_found = true; }
					//perl
						$selected = ($select_value == "perl" || $select_value == "perl:" || $select_value == "menu-exec-app:perl") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='perl:' ".(($selected) ? "selected='selected'" : null).">perl</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:perl ' ".(($selected) ? "selected='selected'" : null).">perl</option>";
						}
						if ($selected) { $selection_found = true; }
					//reject
						$selected = ($select_value == "reject" || $select_value == "menu-exec-app:reject") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='reject' ".(($selected) ? "selected='selected'" : null).">reject</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:reject' ".(($selected) ? "selected='selected'" : null).">reject</option>";
						}
						if ($selected) { $selection_found = true; }
					//set
						$selected = ($select_value == "set" || $select_value == "set:" || $select_value == "menu-exec-app:set") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='set:' ".(($selected) ? "selected='selected'" : null).">set</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:set ' ".(($selected) ? "selected='selected'" : null).">set</option>";
						}
						if ($selected) { $selection_found = true; }
					//sleep
						$selected = ($select_value == "sleep" || $select_value == "sleep:" || $select_value == "menu-exec-app:sleep") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='sleep:' ".(($selected) ? "selected='selected'" : null).">sleep</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:sleep ' ".(($selected) ? "selected='selected'" : null).">sleep</option>";
						}
						if ($selected) { $selection_found = true; }
					//transfer
						$selected = ($select_value == "transfer" || $select_value == "transfer:" || $select_value == "menu-exec-app:transfer") ? true : false;
						if ($select_type == "dialplan") {
							$options[] = "<option value='transfer:' ".(($selected) ? "selected='selected'" : null).">transfer</option>";
						}
						else if ($select_type == "ivr") {
							$options[] = "<option value='menu-exec-app:transfer ' ".(($selected) ? "selected='selected'" : null).">transfer</option>";
						}
						if ($selected) { $selection_found = true; }
				}
			$options[] = "</optgroup>";
		}

	//custom
		if (!$selection_found && $select_value != '') {
			array_unshift($options,
				"<option value='".$select_value."' selected='selected'>".$select_value."</option>",
				"<option value='' disabled='disabled'></option>"
			);
		}

	//output options
		if (sizeof($options)>0) {
			echo implode("\n",$options);
		}

	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".$select_id."' class='btn' name='' alt='back' onclick='changeToInput".$select_id."(document.getElementById(\"".$select_id."\"));this.style.visibility = \"hidden\";' value='&#9665;'>";
	}
}

function save_setting_xml() {
	global $db, $domain_uuid, $host, $config;

	$sql = "select * from v_settings ";
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			$fout = fopen($_SESSION['switch']['conf']['dir']."/directory/default/default.xml","w");
			$xml = "<include>\n";
			$xml .= "  <user id=\"default\"> <!--if id is numeric mailbox param is not necessary-->\n";
			$xml .= "    <variables>\n";
			$xml .= "      <!--all variables here will be set on all inbound calls that originate from this user -->\n";
			$xml .= "      <!-- set these to take advantage of a dialplan localized to this user -->\n";
			$xml .= "      <variable name=\"numbering_plan\" value=\"" . $row['numbering_plan'] . "\"/>\n";
			$xml .= "      <variable name=\"default_gateway\" value=\"" . $row['default_gateway'] . "\"/>\n";
			$xml .= "      <variable name=\"default_area_code\" value=\"" . $row['default_area_code'] . "\"/>\n";
			$xml .= "    </variables>\n";
			$xml .= "  </user>\n";
			$xml .= "</include>\n";
			fwrite($fout, $xml);
			unset($xml);
			fclose($fout);

			$event_socket_ip_address = $row['event_socket_ip_address'];
			if (strlen($event_socket_ip_address) == 0) { $event_socket_ip_address = '127.0.0.1'; }

			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/event_socket.conf.xml","w");
			$xml = "<configuration name=\"event_socket.conf\" description=\"Socket Client\">\n";
			$xml .= "  <settings>\n";
			$xml .= "    <param name=\"listen-ip\" value=\"" . $event_socket_ip_address . "\"/>\n";
			$xml .= "    <param name=\"listen-port\" value=\"" . $row['event_socket_port'] . "\"/>\n";
			$xml .= "    <param name=\"password\" value=\"" . $row['event_socket_password'] . "\"/>\n";
			$xml .= "    <!--<param name=\"apply-inbound-acl\" value=\"lan\"/>-->\n";
			$xml .= "  </settings>\n";
			$xml .= "</configuration>";
			fwrite($fout, $xml);
			unset($xml, $event_socket_password);
			fclose($fout);

			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_rpc.conf.xml","w");
			$xml = "<configuration name=\"xml_rpc.conf\" description=\"XML RPC\">\n";
			$xml .= "  <settings>\n";
			$xml .= "    <!-- The port where you want to run the http service (default 8080) -->\n";
			$xml .= "    <param name=\"http-port\" value=\"" . $row['xml_rpc_http_port'] . "\"/>\n";
			$xml .= "    <!-- if all 3 of the following params exist all http traffic will require auth -->\n";
			$xml .= "    <param name=\"auth-realm\" value=\"" . $row['xml_rpc_auth_realm'] . "\"/>\n";
			$xml .= "    <param name=\"auth-user\" value=\"" . $row['xml_rpc_auth_user'] . "\"/>\n";
			$xml .= "    <param name=\"auth-pass\" value=\"" . $row['xml_rpc_auth_pass'] . "\"/>\n";
			$xml .= "  </settings>\n";
			$xml .= "</configuration>\n";
			fwrite($fout, $xml);
			unset($xml);
			fclose($fout);

			//shout.conf.xml
				$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/shout.conf.xml","w");
				$xml = "<configuration name=\"shout.conf\" description=\"mod shout config\">\n";
				$xml .= "  <settings>\n";
				$xml .= "    <!-- Don't change these unless you are insane -->\n";
				$xml .= "    <param name=\"decoder\" value=\"" . $row['mod_shout_decoder'] . "\"/>\n";
				$xml .= "    <param name=\"volume\" value=\"" . $row['mod_shout_volume'] . "\"/>\n";
				$xml .= "    <!--<param name=\"outscale\" value=\"8192\"/>-->\n";
				$xml .= "  </settings>\n";
				$xml .= "</configuration>";
				fwrite($fout, $xml);
				unset($xml);
				fclose($fout);

			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function filename_safe($filename) {
	// lower case
		$filename = strtolower($filename);

	// replace spaces with a '_'
		$filename = str_replace(" ", "_", $filename);

	// loop through string
		$result = '';
		for ($i=0; $i<strlen($filename); $i++) {
			if (preg_match('([0-9]|[a-z]|_)', $filename[$i])) {
				$result .= $filename[$i];
			}
		}

	// return filename
		return $result;
}

function save_gateway_xml() {

	//skip saving the gateway xml if the directory is not set
		if (strlen($_SESSION['switch']['sip_profiles']['dir']) == 0) {
			return;
		}

	//declare the global variables
		global $db, $domain_uuid, $config;

	//delete all old gateways to prepare for new ones
		if (count($_SESSION["domains"]) > 1) {
			$v_needle = 'v_'.$_SESSION['domain_name'].'-';
		}
		else {
			$v_needle = 'v_';
		}
		$gateway_list = glob($_SESSION['switch']['sip_profiles']['dir'] . "/*/".$v_needle."*.xml");
		foreach ($gateway_list as $gateway_file) {
			unlink($gateway_file);
		}

	//get the list of gateways and write the xml
		$sql = "select * from v_gateways ";
		$sql .= "where (domain_uuid = '$domain_uuid' or domain_uuid is null) ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			if ($row['enabled'] != "false") {
					//set the default profile as external
						$profile = $row['profile'];
						if (strlen($profile) == 0) {
							$profile = "external";
						}
					//open the xml file
						$fout = fopen($_SESSION['switch']['sip_profiles']['dir']."/".$profile."/v_".strtolower($row['gateway_uuid']).".xml","w");
					//build the xml
						$xml .= "<include>\n";
						$xml .= "    <gateway name=\"" . strtolower($row['gateway_uuid']) . "\">\n";
						if (strlen($row['username']) > 0) {
							$xml .= "      <param name=\"username\" value=\"" . $row['username'] . "\"/>\n";
						}
						if (strlen($row['distinct_to']) > 0) {
							$xml .= "      <param name=\"distinct-to\" value=\"" . $row['distinct_to'] . "\"/>\n";
						}
						if (strlen($row['auth_username']) > 0) {
							$xml .= "      <param name=\"auth-username\" value=\"" . $row['auth_username'] . "\"/>\n";
						}
						if (strlen($row['password']) > 0) {
							$xml .= "      <param name=\"password\" value=\"" . $row['password'] . "\"/>\n";
						}
						if (strlen($row['realm']) > 0) {
							$xml .= "      <param name=\"realm\" value=\"" . $row['realm'] . "\"/>\n";
						}
						if (strlen($row['from_user']) > 0) {
							$xml .= "      <param name=\"from-user\" value=\"" . $row['from_user'] . "\"/>\n";
						}
						if (strlen($row['from_domain']) > 0) {
							$xml .= "      <param name=\"from-domain\" value=\"" . $row['from_domain'] . "\"/>\n";
						}
						if (strlen($row['proxy']) > 0) {
							$xml .= "      <param name=\"proxy\" value=\"" . $row['proxy'] . "\"/>\n";
						}
						if (strlen($row['register_proxy']) > 0) {
							$xml .= "      <param name=\"register-proxy\" value=\"" . $row['register_proxy'] . "\"/>\n";
						}
						if (strlen($row['outbound_proxy']) > 0) {
							$xml .= "      <param name=\"outbound-proxy\" value=\"" . $row['outbound_proxy'] . "\"/>\n";
						}
						if (strlen($row['expire_seconds']) > 0) {
							$xml .= "      <param name=\"expire-seconds\" value=\"" . $row['expire_seconds'] . "\"/>\n";
						}
						if (strlen($row['register']) > 0) {
							$xml .= "      <param name=\"register\" value=\"" . $row['register'] . "\"/>\n";
						}

						if (strlen($row['register_transport']) > 0) {
							switch ($row['register_transport']) {
							case "udp":
								$xml .= "      <param name=\"register-transport\" value=\"udp\"/>\n";
								break;
							case "tcp":
								$xml .= "      <param name=\"register-transport\" value=\"tcp\"/>\n";
								break;
							case "tls":
								$xml .= "      <param name=\"register-transport\" value=\"tls\"/>\n";
								$xml .= "      <param name=\"contact-params\" value=\"transport=tls\"/>\n";
								break;
							default:
								$xml .= "      <param name=\"register-transport\" value=\"" . $row['register_transport'] . "\"/>\n";
							}
						}

						if (strlen($row['retry_seconds']) > 0) {
							$xml .= "      <param name=\"retry-seconds\" value=\"" . $row['retry_seconds'] . "\"/>\n";
						}
						if (strlen($row['extension']) > 0) {
							$xml .= "      <param name=\"extension\" value=\"" . $row['extension'] . "\"/>\n";
						}
						if (strlen($row['ping']) > 0) {
							$xml .= "      <param name=\"ping\" value=\"" . $row['ping'] . "\"/>\n";
						}
						if (strlen($row['context']) > 0) {
							$xml .= "      <param name=\"context\" value=\"" . $row['context'] . "\"/>\n";
						}
						if (strlen($row['caller_id_in_from']) > 0) {
							$xml .= "      <param name=\"caller-id-in-from\" value=\"" . $row['caller_id_in_from'] . "\"/>\n";
						}
						if (strlen($row['supress_cng']) > 0) {
							$xml .= "      <param name=\"supress-cng\" value=\"" . $row['supress_cng'] . "\"/>\n";
						}
						if (strlen($row['sip_cid_type']) > 0) {
							$xml .= "      <param name=\"sip_cid_type\" value=\"" . $row['sip_cid_type'] . "\"/>\n";
						}
						if (strlen($row['extension_in_contact']) > 0) {
							$xml .= "      <param name=\"extension-in-contact\" value=\"" . $row['extension_in_contact'] . "\"/>\n";
						}

						$xml .= "    </gateway>\n";
						$xml .= "</include>";

					//write the xml
						fwrite($fout, $xml);
						unset($xml);
						fclose($fout);
			}

		} //end foreach
		unset($prep_statement);

	//apply settings
		$_SESSION["reload_xml"] = true;

}


function save_module_xml() {
	global $config, $db, $domain_uuid;

	$xml = "";
	$xml .= "<configuration name=\"modules.conf\" description=\"Modules\">\n";
	$xml .= "	<modules>\n";

	$sql = "select * from v_modules order by module_category = 'Languages' OR  module_category = 'Loggers' DESC, module_category ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$prev_module_cat = '';
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $row) {
		if ($prev_module_cat != $row['module_category']) {
			$xml .= "\n		<!-- ".$row['module_category']." -->\n";
		}
		if ($row['module_enabled'] == "true"){
			$xml .= "		<load module=\"".$row['module_name']."\"/>\n";
		}
		$prev_module_cat = $row['module_category'];
	}
	$xml .= "\n";
	$xml .= "	</modules>\n";
	$xml .= "</configuration>";

	$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/modules.conf.xml","w");
	fwrite($fout, $xml);
	unset($xml);
	fclose($fout);

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function save_var_xml() {
	global $config, $db, $domain_uuid;

	$fout = fopen($_SESSION['switch']['conf']['dir']."/vars.xml","w");
	$xml = '';

	$sql = "select * from v_vars ";
	$sql .= "where var_enabled = 'true' ";
	$sql .= "order by var_cat, var_order asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$prev_var_cat = '';
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		if ($row['var_cat'] != 'Provision') {
			if ($prev_var_cat != $row['var_cat']) {
				$xml .= "\n<!-- ".$row['var_cat']." -->\n";
				if (strlen($row["var_description"]) > 0) {
					$xml .= "<!-- ".base64_decode($row['var_description'])." -->\n";
				}
			}
			if (strlen($row['var_hostname']) == 0) {
				$xml .= "<X-PRE-PROCESS cmd=\"set\" data=\"".$row['var_name']."=".$row['var_value']."\"/>\n";
			} elseif ($row['var_hostname'] == system('hostname')) {
				$xml .= "<X-PRE-PROCESS cmd=\"set\" data=\"".$row['var_name']."=".$row['var_value']."\"/>\n";
			}
		}
		$prev_var_cat = $row['var_cat'];
	}
	$xml .= "\n";
	fwrite($fout, $xml);
	unset($xml);
	fclose($fout);

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function outbound_route_to_bridge ($domain_uuid, $destination_number) {
	global $db;

	$destination_number = trim($destination_number);
	if (is_numeric($destination_number)) {
		//not found, continue to process the function
	}
	else {
		//not a number, brige_array and exit the function
		$bridge_array[0] = $destination_number;
		return $bridge_array;
	}

	$sql = "select * from v_dialplans ";
	$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
	$sql .= "and app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	$sql .= "and dialplan_enabled = 'true' ";
	$sql .= "order by dialplan_order asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	$x = 0;
	foreach ($result as &$row) {
		//set as variables
			$dialplan_uuid = $row['dialplan_uuid'];
			$dialplan_detail_tag = $row["dialplan_detail_tag"];
			$dialplan_detail_type = $row['dialplan_detail_type'];
			$dialplan_continue = $row['dialplan_continue'];

		//get the extension number using the dialplan_uuid
			$sql = "select * ";
			$sql .= "from v_dialplan_details ";
			$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
			$sql .= "order by dialplan_detail_order asc ";
			$sub_result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
			$regex_match = false;
			foreach ($sub_result as &$sub_row) {
				if ($sub_row['dialplan_detail_tag'] == "condition") {
					if ($sub_row['dialplan_detail_type'] == "destination_number") {
							$dialplan_detail_data = $sub_row['dialplan_detail_data'];
							$pattern = '/'.$dialplan_detail_data.'/';
							preg_match($pattern, $destination_number, $matches, PREG_OFFSET_CAPTURE);
							if (count($matches) == 0) {
								$regex_match = false;
							}
							else {
								$regex_match = true;
								$regex_match_1 = $matches[1][0];
								$regex_match_2 = $matches[2][0];
								$regex_match_3 = $matches[3][0];
								$regex_match_4 = $matches[4][0];
								$regex_match_5 = $matches[5][0];
							}
					}
				}
			}
			if ($regex_match) {
				foreach ($sub_result as &$sub_row) {
					$dialplan_detail_data = $sub_row['dialplan_detail_data'];
					if ($sub_row['dialplan_detail_tag'] == "action" && $sub_row['dialplan_detail_type'] == "bridge" && $dialplan_detail_data != "\${enum_auto_route}") {
					$dialplan_detail_data = str_replace("\$1", $regex_match_1, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$2", $regex_match_2, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$3", $regex_match_3, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$4", $regex_match_4, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$5", $regex_match_5, $dialplan_detail_data);
						//echo "dialplan_detail_data: $dialplan_detail_data";
						$bridge_array[$x] = $dialplan_detail_data;
						$x++;
						if ($dialplan_continue == "false") {
							break 2;
						}
					}
				}
			}
	}
	return $bridge_array;
	unset ($prep_statement);
}

function email_validate($strEmail){
   $validRegExp =  '/^[a-zA-Z0-9\._-]+@[a-zA-Z0-9\._-]+\.[a-zA-Z]{2,3}$/';
   // search email text for regular exp matches
   preg_match($validRegExp, $strEmail, $matches, PREG_OFFSET_CAPTURE);

   if (count($matches) == 0) {
	return 0;
   }
   else {
	return 1;
   }
}
//$destination_number = '1231234';
//$bridge_array = outbound_route_to_bridge ($domain_uuid, $destination_number);
//foreach ($bridge_array as &$bridge) {
//	echo "bridge: ".$bridge."<br />";
//}

function extension_exists($extension) {
	global $db, $domain_uuid;
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and extension = '$extension' ";
	$sql .= "and enabled = 'true' ";
	$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	if (count($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function get_recording_filename($id) {
	global $domain_uuid, $db;
	$sql = "select * from v_recordings ";
	$sql .= "where recording_uuid = '$id' ";
	$sql .= "and domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		//$filename = $row["filename"];
		//$recording_name = $row["recording_name"];
		//$recording_uuid = $row["recording_uuid"];
		return $row["filename"];
		break; //limit to 1 row
	}
	unset ($prep_statement);
}

function dialplan_add($domain_uuid, $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid) {
	global $db, $db_type;
	$sql = "insert into v_dialplans ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	if (strlen($app_uuid) > 0) {
		$sql .= "app_uuid, ";
	}
	$sql .= "dialplan_name, ";
	$sql .= "dialplan_order, ";
	$sql .= "dialplan_context, ";
	$sql .= "dialplan_enabled, ";
	$sql .= "dialplan_description ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$dialplan_uuid', ";
	if (strlen($app_uuid) > 0) {
		$sql .= "'$app_uuid', ";
	}
	$sql .= "'$dialplan_name', ";
	$sql .= "'$dialplan_order', ";
	$sql .= "'$dialplan_context', ";
	$sql .= "'$dialplan_enabled', ";
	$sql .= "'$dialplan_description' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);
}

function dialplan_detail_add($domain_uuid, $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data, $dialplan_detail_break, $dialplan_detail_inline) {
	global $db;
	$dialplan_detail_uuid = uuid();
	$sql = "insert into v_dialplan_details ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	$sql .= "dialplan_detail_uuid, ";
	$sql .= "dialplan_detail_tag, ";
	$sql .= "dialplan_detail_group, ";
	$sql .= "dialplan_detail_order, ";
	$sql .= "dialplan_detail_type, ";
	$sql .= "dialplan_detail_data, ";
	$sql .= "dialplan_detail_break, ";
	$sql .= "dialplan_detail_inline ";
	$sql .= ") ";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'".check_str($dialplan_uuid)."', ";
	$sql .= "'".check_str($dialplan_detail_uuid)."', ";
	$sql .= "'".check_str($dialplan_detail_tag)."', ";
	if (strlen($dialplan_detail_group) == 0) {
		$sql .= "null, ";
	}
	else {
		$sql .= "'".check_str($dialplan_detail_group)."', ";
	}
	$sql .= "'".check_str($dialplan_detail_order)."', ";
	$sql .= "'".check_str($dialplan_detail_type)."', ";
	$sql .= "'".check_str($dialplan_detail_data)."', ";
	if (strlen($dialplan_detail_break) == 0) {
		$sql .= "null, ";
	}
	else {
		$sql .= "'".check_str($dialplan_detail_break)."', ";
	}
	if (strlen($dialplan_detail_inline) == 0) {
		$sql .= "null ";
	}
	else {
		$sql .= "'".check_str($dialplan_detail_inline)."' ";
	}
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);
}

function save_dialplan_xml() {
	global $db, $domain_uuid;

	//get the context based from the domain_uuid
		if (count($_SESSION['domains']) == 1) {
			$user_context = "default";
		}
		else {
			$user_context = $_SESSION['domains'][$domain_uuid]['domain_name'];
		}

	//prepare for dialplan .xml files to be written. delete all dialplan files that are prefixed with dialplan_ and have a file extension of .xml
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*v_dialplan*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*_v_*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*/*_v_*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}

	//if dialplan dir exists then build and save the dialplan xml
		if (is_dir($_SESSION['switch']['dialplan']['dir'])) {
			$sql = "select * from v_dialplans ";
			$sql .= "where dialplan_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				foreach ($result as &$row) {
					$tmp = "";
					$tmp .= "\n";
					$first_action = true;

					$dialplan_continue = '';
					if ($row['dialplan_continue'] == "true") {
						$dialplan_continue = "continue=\"true\"";
					}

					$tmp = "<extension name=\"".$row['dialplan_name']."\" $dialplan_continue>\n";

					$sql = " select * from v_dialplan_details ";
					$sql .= " where dialplan_uuid = '".$row['dialplan_uuid']."' ";
					$sql .= " order by dialplan_detail_group asc, dialplan_detail_order asc ";
					$prep_statement_2 = $db->prepare($sql);
					if ($prep_statement_2) {
						$prep_statement_2->execute();
						$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
						$result_count2 = count($result2);
						unset ($prep_statement_2, $sql);

						//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
							$details = '';
							$previous_tag = '';
							$details[$group]['condition_count'] = '';
							//conditions
								$x = 0;
								$y = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "condition") {
										//get the group
											$group = $row2['dialplan_detail_group'];
										//get the generic type
											switch ($row2['dialplan_detail_type']) {
												case "hour":
												case "minute":
												case "minute-of-day":
												case "time-of-day":
												case "mday":
												case "mweek":
												case "mon":
												case "yday":
												case "year":
												case "wday":
												case "week":
													$type = 'time';
													break;
												default:
													$type = 'default';
											}

										//add the conditions to the details array
											$details[$group]['condition-'.$x]['dialplan_detail_tag'] = $row2['dialplan_detail_tag'];
											$details[$group]['condition-'.$x]['dialplan_detail_type'] = $row2['dialplan_detail_type'];
											$details[$group]['condition-'.$x]['dialplan_uuid'] = $row2['dialplan_uuid'];
											$details[$group]['condition-'.$x]['dialplan_detail_order'] = $row2['dialplan_detail_order'];
											$details[$group]['condition-'.$x]['field'][$y]['type'] = $row2['dialplan_detail_type'];
											$details[$group]['condition-'.$x]['field'][$y]['data'] = $row2['dialplan_detail_data'];
											$details[$group]['condition-'.$x]['dialplan_detail_break'] = $row2['dialplan_detail_break'];
											$details[$group]['condition-'.$x]['dialplan_detail_group'] = $row2['dialplan_detail_group'];
											$details[$group]['condition-'.$x]['dialplan_detail_inline'] = $row2['dialplan_detail_inline'];
											if ($type == "time") {
												$y++;
											}
									}
									if ($type == "default") {
										$x++;
										$y = 0;
									}
								}

							//actions
								$x = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "action") {
										$group = $row2['dialplan_detail_group'];
										foreach ($row2 as $key => $val) {
											$details[$group]['action-'.$x][$key] = $val;
										}
									}
									$x++;
								}
							//anti-actions
								$x = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "anti-action") {
										$group = $row2['dialplan_detail_group'];
										foreach ($row2 as $key => $val) {
											$details[$group]['anti-action-'.$x][$key] = $val;
										}
									}
									$x++;
								}
							unset($result2);
					}

					$i=1;
					if ($result_count2 > 0) {
						foreach($details as $group) {
							$current_count = 0;
							$x = 0;
							foreach($group as $ent) {
								$close_condition_tag = true;
								if (empty($ent)) {
									$close_condition_tag = false;
								}
								$current_tag = $ent['dialplan_detail_tag'];
								$c = 0;
								if ($ent['dialplan_detail_tag'] == "condition") {
									//get the generic type
										switch ($ent['dialplan_detail_type']) {
											case "hour":
											case "minute":
											case "minute-of-day":
											case "time-of-day":
											case "mday":
											case "mweek":
											case "mon":
											case "yday":
											case "year":
											case "wday":
											case "week":
												$type = 'time';
												break;
											default:
												$type = 'default';
										}

									//set the attribute and expression
										$condition_attribute = '';
										foreach($ent['field'] as $field) {
											if ($type == "time") {
												if (strlen($field['type']) > 0) {
													$condition_attribute .= $field['type'].'="'.$field['data'].'" ';
												}
												$condition_expression = '';
											}
											if ($type == "default") {
												$condition_attribute = '';
												if (strlen($field['type']) > 0) {
													$condition_attribute = 'field="'.$field['type'].'" ';
												}
												$condition_expression = '';
												if (strlen($field['data']) > 0) {
													$condition_expression = 'expression="'.$field['data'].'" ';
												}
											}
										}

									//get the condition break attribute
										$condition_break = '';
										if (strlen($ent['dialplan_detail_break']) > 0) {
											$condition_break = "break=\"".$ent['dialplan_detail_break']."\" ";
										}

									//get the count
										$count = 0;
										foreach($details as $group2) {
											foreach($group2 as $ent2) {
												if ($ent2['dialplan_detail_group'] == $ent['dialplan_detail_group'] && $ent2['dialplan_detail_tag'] == "condition") {
													$count++;
												}
											}
										}

									//use the correct type of dialplan_detail_tag open or self closed
										if ($count == 1) { //single condition
											//start dialplan_detail_tag
											$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break.">\n";
										}
										else { //more than one condition
											$current_count++;
											if ($current_count < $count) {
												//all tags should be self-closing except the last one
												$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break."/>\n";
											}
											else {
												//for the last dialplan_detail_tag use the start dialplan_detail_tag
												$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break.">\n";
											}
										}
								}
								//actions
									if ($ent['dialplan_detail_tag'] == "action") {
										//set the domain info for the public context
										if ($row['dialplan_context'] == "public") {
											if ($first_action) {
												$tmp .= "       <action application=\"set\" data=\"call_direction=inbound\"/>\n";
												$tmp .= "       <action application=\"set\" data=\"domain_uuid=".$row['domain_uuid']."\"/>\n";
												$tmp .= "       <action application=\"set\" data=\"domain_name=".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."\"/>\n";
												$tmp .= "       <action application=\"set\" data=\"domain=".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."\"/>\n";
												$first_action = false;
											}
										}
										//get the action inline attribute
										$action_inline = '';
										if (strlen($ent['dialplan_detail_inline']) > 0) {
											$action_inline = "inline=\"".$ent['dialplan_detail_inline']."\"";
										}
										if (strlen($ent['dialplan_detail_data']) > 0) {
											$tmp .= "       <action application=\"".$ent['dialplan_detail_type']."\" data=\"".$ent['dialplan_detail_data']."\" $action_inline/>\n";
										}
										else {
											$tmp .= "       <action application=\"".$ent['dialplan_detail_type']."\" $action_inline/>\n";
										}
									}
								//anti-actions
									if ($ent['dialplan_detail_tag'] == "anti-action") {
										//get the action inline attribute
										$anti_action_inline = '';
										if (strlen($ent['dialplan_detail_inline']) > 0) {
											$anti_action_inline = "inline=\"".$ent['dialplan_detail_inline']."\"";
										}
										if (strlen($ent['dialplan_detail_data']) > 0) {
											$tmp .= "       <anti-action application=\"".$ent['dialplan_detail_type']."\" data=\"".$ent['dialplan_detail_data']."\" $anti_action_inline/>\n";
										}
										else {
											$tmp .= "       <anti-action application=\"".$ent['dialplan_detail_type']."\" $anti_action_inline/>\n";
										}
									}
								//set the previous dialplan_detail_tag
									$previous_tag = $ent['dialplan_detail_tag'];
								$i++;
							} //end foreach
							if ($close_condition_tag == true) {
								$tmp .= "   </condition>\n";
							}
							$x++;
						}
						if ($condition_count > 0) {
							$condition_count = $result_count2;
						}
						unset($sql, $result_count2, $result2, $row_count2);
					} //end if results
					$tmp .= "</extension>\n";

					$dialplan_order = $row['dialplan_order'];
					if (strlen($dialplan_order) == 0) { $dialplan_order = "000".$dialplan_order; }
					if (strlen($dialplan_order) == 1) { $dialplan_order = "00".$dialplan_order; }
					if (strlen($dialplan_order) == 2) { $dialplan_order = "0".$dialplan_order; }
					if (strlen($dialplan_order) == 4) { $dialplan_order = "999"; }
					if (strlen($dialplan_order) == 5) { $dialplan_order = "999"; }

					//remove invalid characters from the file names
					$dialplan_name = $row['dialplan_name'];
					$dialplan_name = str_replace(" ", "_", $dialplan_name);
					$dialplan_name = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $dialplan_name);

					$dialplan_filename = $dialplan_order."_v_".$dialplan_name.".xml";
					if (strlen($row['dialplan_context']) > 0) {
						if (!is_dir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'])) {
							mkdir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'],0755,true);
						}
						if ($row['dialplan_context'] == "public") {
							if (count($_SESSION['domains']) > 1 && strlen($row['domain_uuid']) > 0) {
								if (!is_dir($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name'])) {
									mkdir($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name'],0755,true);
								}
								file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."/".$dialplan_filename, $tmp);
							}
							else {
								file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$dialplan_filename, $tmp);
							}
						}
						else {
							if (!is_dir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'])) {
								mkdir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'],0755,true);
							}
							file_put_contents($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context']."/".$dialplan_filename, $tmp);
						}
					}
					unset($dialplan_filename);
					unset($tmp);
				} //end while

				//apply settings
					$_SESSION["reload_xml"] = true;
			}
		} //end if (is_dir($_SESSION['switch']['dialplan']['dir']))
}


if (!function_exists('phone_letter_to_number')) {
	function phone_letter_to_number($tmp) {
		$tmp = strtolower($tmp);
		if ($tmp == "a" | $tmp == "b" | $tmp == "c") { return 2; }
		if ($tmp == "d" | $tmp == "e" | $tmp == "f") { return 3; }
		if ($tmp == "g" | $tmp == "h" | $tmp == "i") { return 4; }
		if ($tmp == "j" | $tmp == "k" | $tmp == "l") { return 5; }
		if ($tmp == "m" | $tmp == "n" | $tmp == "o") { return 6; }
		if ($tmp == "p" | $tmp == "q" | $tmp == "r" | $tmp == "s") { return 7; }
		if ($tmp == "t" | $tmp == "u" | $tmp == "v") { return 8; }
		if ($tmp == "w" | $tmp == "x" | $tmp == "y" | $tmp == "z") { return 9; }
	}
}


if (!function_exists('save_call_center_xml')) {
	function save_call_center_xml() {
		global $db, $domain_uuid;

		if (strlen($_SESSION['switch']['call_center']['dir']) > 0) {
			//include the classes
			include "app/dialplan/resources/classes/dialplan.php";

			$sql = "select * from v_call_center_queues ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as $row) {
					//set the variables
						$call_center_queue_uuid = $row["call_center_queue_uuid"];
						$domain_uuid = $row["domain_uuid"];
						$dialplan_uuid = $row["dialplan_uuid"];
						$queue_name = check_str($row["queue_name"]);
						$queue_extension = $row["queue_extension"];
						$queue_strategy = $row["queue_strategy"];
						$queue_moh_sound = $row["queue_moh_sound"];
						$queue_record_template = $row["queue_record_template"];
						$queue_time_base_score = $row["queue_time_base_score"];
						$queue_max_wait_time = $row["queue_max_wait_time"];
						$queue_max_wait_time_with_no_agent = $row["queue_max_wait_time_with_no_agent"];
						$queue_tier_rules_apply = $row["queue_tier_rules_apply"];
						$queue_tier_rule_wait_second = $row["queue_tier_rule_wait_second"];
						$queue_tier_rule_wait_multiply_level = $row["queue_tier_rule_wait_multiply_level"];
						$queue_tier_rule_no_agent_no_wait = $row["queue_tier_rule_no_agent_no_wait"];
						$queue_timeout_action = $row["queue_timeout_action"];
						$queue_discard_abandoned_after = $row["queue_discard_abandoned_after"];
						$queue_abandoned_resume_allowed = $row["queue_abandoned_resume_allowed"];
						$queue_cid_prefix = $row["queue_cid_prefix"];
						$queue_announce_sound = $row["queue_announce_sound"];
						$queue_announce_frequency = $row["queue_announce_frequency"];
						$queue_description = check_str($row["queue_description"]);

					//replace space with an underscore
						$queue_name = str_replace(" ", "_", $queue_name);

					//add each Queue to the dialplan
						if (strlen($row['call_center_queue_uuid']) > 0) {
							$action = 'add'; //set default action to add
							$i = 0;

							//determine the action add or update
							if (strlen($dialplan_uuid) > 0) {
								$sql = "select * from v_dialplans ";
								$sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
								$prep_statement_2 = $db->prepare($sql);
								$prep_statement_2->execute();
								while($row2 = $prep_statement_2->fetch(PDO::FETCH_ASSOC)) {
									$action = 'update';
								}
								unset ($sql, $prep_statement_2);
							}

							if ($action == 'add') {
								//create queue entry in the dialplan
									$dialplan_name = $queue_name;
									$dialplan_order ='210';
									$dialplan_context = $_SESSION['context'];
									$dialplan_enabled = 'true';
									$dialplan_description = $queue_description;
									$app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
									$dialplan_uuid = uuid();
									dialplan_add($domain_uuid, $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

								//add the dialplan_uuid to the call center table
									$sql = "update v_call_center_queues set ";
									$sql .= "dialplan_uuid = '$dialplan_uuid' ";
									$sql .= "where domain_uuid = '$domain_uuid' ";
									$sql .= "and call_center_queue_uuid = '".$row['call_center_queue_uuid']."' ";
									$db->exec(check_sql($sql));
									unset($sql);
							}
							if ($action == 'update') {
								//add the dialplan_uuid to the call center table
									$sql = "update v_dialplans set ";
									$sql .= "dialplan_name = '".$queue_name."', ";
									$sql .= "dialplan_description = '".$queue_description."' ";
									$sql .= "where domain_uuid = '".$domain_uuid."' ";
									$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
									$db->exec(check_sql($sql));
									unset($sql);

								//add the dialplan_uuid to the call center table
									$sql = "delete from v_dialplan_details ";
									$sql .= "where domain_uuid = '$domain_uuid' ";
									$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
									$db->exec(check_sql($sql));
									unset($sql);
							}

							//group 1
								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = '${caller_id_name}';
								$dialplan->dialplan_detail_data = '^([^#]+#)(.*)$';
								$dialplan->dialplan_detail_break = 'never';
								$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '1';
								$dialplan->dialplan_detail_order = '010';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'set';
								$dialplan->dialplan_detail_data = 'caller_id_name=$2';
								$dialplan->dialplan_detail_break = '';
								$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '1';
								$dialplan->dialplan_detail_order = '020';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

							//group 2
								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'destination_number';
								$dialplan->dialplan_detail_data = '^'.$row['queue_extension'].'$';
								$dialplan->dialplan_detail_break = '';
								$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '010';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'answer';
								$dialplan->dialplan_detail_data = '';
								$dialplan->dialplan_detail_break = '';
								$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '020';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'set';
								$dialplan->dialplan_detail_data = 'hangup_after_bridge=true';
								$dialplan->dialplan_detail_break = '';
								$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '030';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								if (strlen($queue_cid_prefix) > 0) {
									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'set';
									$dialplan->dialplan_detail_data = "effective_caller_id_name=".$queue_cid_prefix."-\${caller_id_name}";
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '040';
									$dialplan->dialplan_detail_add();
									unset($dialplan);
								}

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'callcenter';
								$dialplan->dialplan_detail_data = $queue_name."@".$_SESSION['domains'][$domain_uuid]['domain_name'];
								$dialplan->dialplan_detail_break = '';
								$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '050';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								if (strlen($queue_timeout_action) > 0) {
									$action_array = explode(":",$queue_timeout_action);
									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = $action_array[0];
									$dialplan->dialplan_detail_data = substr($queue_timeout_action, strlen($action_array[0])+1, strlen($queue_timeout_action));
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '060';
									$dialplan->dialplan_detail_add();
									unset($dialplan);
								}

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'hangup';
								$dialplan->dialplan_detail_data = '';
								$dialplan->dialplan_detail_break = '';
								$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '070';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

							//synchronize the xml config
								save_dialplan_xml();

							//unset variables
								unset($action);

						} //end if strlen call_center_queue_uuid; add the call center queue to the dialplan
				}

				//prepare Queue XML string
					$v_queues = '';
					$sql = "select * from v_call_center_queues ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					$x=0;
					foreach ($result as &$row) {
						$queue_name = $row["queue_name"];
						$queue_extension = $row["queue_extension"];
						$queue_strategy = $row["queue_strategy"];
						$queue_moh_sound = $row["queue_moh_sound"];
						$queue_record_template = $row["queue_record_template"];
						$queue_time_base_score = $row["queue_time_base_score"];
						$queue_max_wait_time = $row["queue_max_wait_time"];
						$queue_max_wait_time_with_no_agent = $row["queue_max_wait_time_with_no_agent"];
						$queue_tier_rules_apply = $row["queue_tier_rules_apply"];
						$queue_tier_rule_wait_second = $row["queue_tier_rule_wait_second"];
						$queue_tier_rule_wait_multiply_level = $row["queue_tier_rule_wait_multiply_level"];
						$queue_tier_rule_no_agent_no_wait = $row["queue_tier_rule_no_agent_no_wait"];
						$queue_discard_abandoned_after = $row["queue_discard_abandoned_after"];
						$queue_abandoned_resume_allowed = $row["queue_abandoned_resume_allowed"];
						$queue_announce_sound = $row["queue_announce_sound"];
						$queue_announce_frequency = $row ["queue_announce_frequency"];
						$queue_description = $row["queue_description"];
						if ($x > 0) {
							$v_queues .= "\n";
							$v_queues .= "		";
						}
						$v_queues .= "<queue name=\"$queue_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\">\n";
						$v_queues .= "			<param name=\"strategy\" value=\"$queue_strategy\"/>\n";
						if (strlen($queue_moh_sound) == 0) {
							$v_queues .= "			<param name=\"moh-sound\" value=\"local_stream://default\"/>\n";
						}
						else {
							if (substr($queue_moh_sound, 0, 15) == 'local_stream://') {
								$v_queues .= "			<param name=\"moh-sound\" value=\"".$queue_moh_sound."\"/>\n";
							}
							elseif (substr($queue_moh_sound, 0, 2) == '${' && substr($queue_moh_sound, -5) == 'ring}') {
								$v_queues .= "			<param name=\"moh-sound\" value=\"tone_stream://".$queue_moh_sound.";loops=-1\"/>\n";
							}
							else {
								$v_queues .= "			<param name=\"moh-sound\" value=\"".$queue_moh_sound."\"/>\n";
							}
						}
						if (strlen($queue_record_template) > 0) {
							$v_queues .= "			<param name=\"record-template\" value=\"$queue_record_template\"/>\n";
						}
						$v_queues .= "			<param name=\"time-base-score\" value=\"$queue_time_base_score\"/>\n";
						$v_queues .= "			<param name=\"max-wait-time\" value=\"$queue_max_wait_time\"/>\n";
						$v_queues .= "			<param name=\"max-wait-time-with-no-agent\" value=\"$queue_max_wait_time_with_no_agent\"/>\n";
						$v_queues .= "			<param name=\"max-wait-time-with-no-agent-time-reached\" value=\"$queue_max_wait_time_with_no_agent_time_reached\"/>\n";
						$v_queues .= "			<param name=\"tier-rules-apply\" value=\"$queue_tier_rules_apply\"/>\n";
						$v_queues .= "			<param name=\"tier-rule-wait-second\" value=\"$queue_tier_rule_wait_second\"/>\n";
						$v_queues .= "			<param name=\"tier-rule-wait-multiply-level\" value=\"$queue_tier_rule_wait_multiply_level\"/>\n";
						$v_queues .= "			<param name=\"tier-rule-no-agent-no-wait\" value=\"$queue_tier_rule_no_agent_no_wait\"/>\n";
						$v_queues .= "			<param name=\"discard-abandoned-after\" value=\"$queue_discard_abandoned_after\"/>\n";
						$v_queues .= "			<param name=\"abandoned-resume-allowed\" value=\"$queue_abandoned_resume_allowed\"/>\n";
						$v_queues .= "			<param name=\"announce-sound\" value=\"$queue_announce_sound\"/>\n";
						$v_queues .= "			<param name=\"announce-frequency\" value=\"$queue_announce_frequency\"/>\n";
						$v_queues .= "		</queue>";
						$x++;
					}
					unset ($prep_statement);

				//prepare Agent XML string
					$v_agents = '';
					$sql = "select * from v_call_center_agents ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					$x=0;
					foreach ($result as &$row) {
						//get the values from the db and set as php variables
							$agent_name = $row["agent_name"];
							$agent_type = $row["agent_type"];
							$agent_call_timeout = $row["agent_call_timeout"];
							$agent_contact = $row["agent_contact"];
							$agent_status = $row["agent_status"];
							$agent_no_answer_delay_time = $row["agent_no_answer_delay_time"];
							$agent_max_no_answer = $row["agent_max_no_answer"];
							$agent_wrap_up_time = $row["agent_wrap_up_time"];
							$agent_reject_delay_time = $row["agent_reject_delay_time"];
							$agent_busy_delay_time = $row["agent_busy_delay_time"];
							if ($x > 0) {
								$v_agents .= "\n";
								$v_agents .= "		";
							}

						//get and then set the complete agent_contact with the call_timeout and when necessary confirm
							//$tmp_confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1";
							//if you change this variable also change app/call_center/call_center_agent_edit.php
							$tmp_confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1,group_confirm_read_timeout=2000,leg_timeout=".$agent_call_timeout;
							if(strstr($agent_contact, '}') === FALSE) {
								//not found
								if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
									//add the call_timeout
									$tmp_agent_contact = "{call_timeout=".$agent_call_timeout."}".$agent_contact;
								}
								else {
									//add the call_timeout and confirm
									$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
									$tmp_agent_contact = "{".$tmp_confirm.",call_timeout=".$agent_call_timeout."}".$agent_contact;
								}
							}
							else {
								//found
								if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
									//not found
									if(stristr($agent_contact, 'call_timeout') === FALSE) {
										//add the call_timeout
										$tmp_pos = strrpos($agent_contact, "}");
										$tmp_first = substr($agent_contact, 0, $tmp_pos);
										$tmp_last = substr($agent_contact, $tmp_pos);
										$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
									}
									else {
										//the string has the call timeout
										$tmp_agent_contact = $agent_contact;
									}
								}
								else {
									//found
									$tmp_pos = strrpos($agent_contact, "}");
									$tmp_first = substr($agent_contact, 0, $tmp_pos);
									$tmp_last = substr($agent_contact, $tmp_pos);
									if(stristr($agent_contact, 'call_timeout') === FALSE) {
										//add the call_timeout and confirm
										$tmp_agent_contact = $tmp_first.','.$tmp_confirm.',call_timeout='.$agent_call_timeout.$tmp_last;
									}
									else {
										//add confirm
										$tmp_agent_contact = $tmp_first.','.$tmp_confirm.$tmp_last;
									}
								}
							}

						$v_agents .= "<agent ";
						$v_agents .= "name=\"$agent_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" ";
						$v_agents .= "type=\"$agent_type\" ";
						$v_agents .= "contact=\"$tmp_agent_contact\" ";
						$v_agents .= "status=\"$agent_status\" ";
						$v_agents .= "no-answer-delay-time=\"$agent_no_answer_delay_time\" ";
						$v_agents .= "max-no-answer=\"$agent_max_no_answer\" ";
						$v_agents .= "wrap-up-time=\"$agent_wrap_up_time\" ";
						$v_agents .= "reject-delay-time=\"$agent_reject_delay_time\" ";
						$v_agents .= "busy-delay-time=\"$agent_busy_delay_time\" ";
						$v_agents .= "/>";
						$x++;
					}
					unset ($prep_statement);

				//prepare Tier XML string
					$v_tiers = '';
					$sql = "select * from v_call_center_tiers ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					$x=0;
					foreach ($result as &$row) {
						$agent_name = $row["agent_name"];
						$queue_name = $row["queue_name"];
						$tier_level = $row["tier_level"];
						$tier_position = $row["tier_position"];
						if ($x > 0) {
							$v_tiers .= "\n";
							$v_tiers .= "		";
						}
						$v_tiers .= "<tier agent=\"$agent_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" queue=\"$queue_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" level=\"$tier_level\" position=\"$tier_position\"/>";
						$x++;
					}

				//set the path
					if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
						$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
					}
					else {
						$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
					}

				//get the contents of the template
					$file_contents = file_get_contents($path."/autoload_configs/callcenter.conf.xml");

				//add the Call Center Queues, Agents and Tiers to the XML config
					$file_contents = str_replace("{v_queues}", $v_queues, $file_contents);
					unset ($v_queues);

					$file_contents = str_replace("{v_agents}", $v_agents, $file_contents);
					unset ($v_agents);

					$file_contents = str_replace("{v_tiers}", $v_tiers, $file_contents);
					unset ($v_tiers);

				//write the XML config file
					$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/callcenter.conf.xml","w");
					fwrite($fout, $file_contents);
					fclose($fout);

				//save the dialplan xml files
					save_dialplan_xml();

				//apply settings
					$_SESSION["reload_xml"] = true;
			}
		}
	}
}

if (!function_exists('switch_conf_xml')) {
	function switch_conf_xml() {
		//get the global variables
			global $db, $domain_uuid;

		//get the contents of the template
			if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
				$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
			}
			else {
				$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
			}
			$file_contents = file_get_contents($path."/autoload_configs/switch.conf.xml");

		//prepare the php variables
			if (stristr(PHP_OS, 'WIN')) {
				$bindir = getenv(PHPRC);
				$v_mailer_app ='""'. $bindir."/php". '" -f  '.$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure/v_mailto.php -- "';
				$v_mailer_app = sprintf("'%s'", $v_mailer_app);
				$v_mailer_app_args = "";
			}
			else {
				if (file_exists(PHP_BINDIR.'/php')) { define("PHP_BIN", "php"); }
				$v_mailer_app = PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/v_mailto.php";
				$v_mailer_app = sprintf('"%s"', $v_mailer_app);
				$v_mailer_app_args = "-t";
			}

		//replace the values in the template
			$file_contents = str_replace("{v_mailer_app}", $v_mailer_app, $file_contents);
			unset ($v_mailer_app);

		//replace the values in the template
			$file_contents = str_replace("{v_mailer_app_args}", $v_mailer_app_args, $file_contents);
			unset ($v_mailer_app_args);

		//write the XML config file
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/switch.conf.xml","w");
			fwrite($fout, $file_contents);
			fclose($fout);

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('xml_cdr_conf_xml')) {
	function xml_cdr_conf_xml() {

		//get the global variables
			global $db, $domain_uuid;

		//get the contents of the template
		 	if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
				$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
			}
			else {
				$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
			}
			$file_contents = file_get_contents($path."/autoload_configs/xml_cdr.conf.xml");

		//replace the values in the template
			$file_contents = str_replace("{v_http_protocol}", "http", $file_contents);
			$file_contents = str_replace("{domain_name}", "127.0.0.1", $file_contents);
			$file_contents = str_replace("{v_project_path}", PROJECT_PATH, $file_contents);

			$v_user = generate_password();
			$file_contents = str_replace("{v_user}", $v_user, $file_contents);
			unset ($v_user);

			$v_pass = generate_password();
			$file_contents = str_replace("{v_pass}", $v_pass, $file_contents);
			unset ($v_pass);

		//write the XML config file
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_cdr.conf.xml","w");
			fwrite($fout, $file_contents);
			fclose($fout);

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_sip_profile_xml')) {
	function save_sip_profile_xml() {

		//skip saving the sip profile xml if the directory is not set
			if (strlen($_SESSION['switch']['sip_profiles']['dir']) == 0) {
				return;
			}

		//get the global variables
			global $db, $domain_uuid;

		//get the sip profiles from the database
			$sql = "select * from v_sip_profiles ";
			$sql .= "where sip_profile_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as $row) {
					$sip_profile_uuid = $row['sip_profile_uuid'];
					$sip_profile_name = $row['sip_profile_name'];

					//get the xml sip profile template
						if ($sip_profile_name == "internal" || $sip_profile_name == "external" || $sip_profile_name == "internal-ipv6") {
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/sip_profiles/resources/xml/sip_profiles/".$sip_profile_name.".xml");
						}
						else {
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/sip_profiles/resources/xml/sip_profiles/default.xml");
						}

					//get the sip profile settings
						$sql = "select * from v_sip_profile_settings ";
						$sql .= "where sip_profile_uuid = '$sip_profile_uuid' ";
						$sql .= "and sip_profile_setting_enabled = 'true' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll();
						$sip_profile_settings = '';
						foreach ($result as &$row) {
							$sip_profile_settings .= "		<param name=\"".$row["sip_profile_setting_name"]."\" value=\"".$row["sip_profile_setting_value"]."\"/>\n";
						}
						unset ($prep_statement);

					//replace the values in the template
						$file_contents = str_replace("{v_sip_profile_name}", $sip_profile_name, $file_contents);
						$file_contents = str_replace("{v_sip_profile_settings}", $sip_profile_settings, $file_contents);

					//write the XML config file
						if (is_readable($_SESSION['switch']['conf']['dir']."/sip_profiles/")) {
							$fout = fopen($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name.".xml","w");
							fwrite($fout, $file_contents);
							fclose($fout);
						}

					//if the directory does not exist then create it
						if (!is_readable($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name)) { mkdir($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name,0775,true); }

				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_switch_xml')) {
	function save_switch_xml() {
		if (is_readable($_SESSION['switch']['dialplan']['dir'])) {
			save_dialplan_xml();
		}
		if (is_readable($_SESSION['switch']['extensions']['dir'])) {
			if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/resources/classes/extension.php")) {
				require_once $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."app/extensions/resources/classes/extension.php";
				$extension = new extension;
				$extension->xml();
			}
		}
		if (is_readable($_SESSION['switch']['conf']['dir'])) {
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/settings/app_config.php")) {
				save_setting_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/modules/app_config.php")) {
				save_module_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/vars/app_config.php")) {
				save_var_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/call_center/app_config.php")) {
				save_call_center_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/gateways/app_config.php")) {
				save_gateway_xml();
			}
			//if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/ivr_menu/app_config.php")) {
			//	save_ivr_menu_xml();
			//}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/sip_profiles/app_config.php")) {
				save_sip_profile_xml();
			}
		}
	}
}

?>
