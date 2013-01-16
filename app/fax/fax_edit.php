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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('fax_extension_add') || permission_exists('fax_extension_edit') || permission_exists('fax_extension_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the fax_extension and save it as a variable
	if (strlen($_REQUEST["fax_extension"]) > 0) {
		$fax_extension = check_str($_REQUEST["fax_extension"]);
	}

//set the fax directory
	if (count($_SESSION["domains"]) > 1) {
		$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'];
	}
	else {
		$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax';
	}

//get the fax extension
	if (strlen($fax_extension) > 0) {
		//set the fax directories. example /usr/local/freeswitch/storage/fax/329/inbox
			$dir_fax_inbox = $fax_dir.'/'.$fax_extension.'/inbox';
			$dir_fax_sent = $fax_dir.'/'.$fax_extension.'/sent';
			$dir_fax_temp = $fax_dir.'/'.$fax_extension.'/temp';

		//make sure the directories exist
			if (!is_dir($_SESSION['switch']['storage']['dir'])) {
				mkdir($_SESSION['switch']['storage']['dir']);
				chmod($dir_fax_sent,0774);
			}
			if (!is_dir($fax_dir.'/'.$fax_extension)) {
				mkdir($fax_dir.'/'.$fax_extension,0774,true);
				chmod($fax_dir.'/'.$fax_extension,0774);
			}
			if (!is_dir($dir_fax_inbox)) {
				mkdir($dir_fax_inbox,0774,true);
				chmod($dir_fax_inbox,0774);
			}
			if (!is_dir($dir_fax_sent)) {
				mkdir($dir_fax_sent,0774,true); 
				chmod($dir_fax_sent,0774);
			}
			if (!is_dir($dir_fax_temp)) {
				mkdir($dir_fax_temp,0774,true);
				chmod($dir_fax_temp,0774);
			}
	}

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$fax_uuid = check_str($_REQUEST["id"]);
		$dialplan_uuid = check_str($_REQUEST["dialplan_uuid"]);
	}
	else {
		$action = "add";
	}

//get the http post values and set them as php variables
	if (count($_POST)>0) {
		$fax_name = check_str($_POST["fax_name"]);
		$fax_extension = check_str($_POST["fax_extension"]);
		$fax_destination_number = check_str($_POST["fax_destination_number"]);
		$fax_email = check_str($_POST["fax_email"]);
		$fax_pin_number = check_str($_POST["fax_pin_number"]);
		$fax_caller_id_name = check_str($_POST["fax_caller_id_name"]);
		$fax_caller_id_number = check_str($_POST["fax_caller_id_number"]);
		$fax_forward_number = check_str($_POST["fax_forward_number"]);
		if (strlen($fax_destination_number) == 0) {
			$fax_destination_number = $fax_extension;
		}
		if (strlen($fax_forward_number) > 3) {
			//$fax_forward_number = preg_replace("~[^0-9]~", "",$fax_forward_number);
			$fax_forward_number = str_replace(" ", "", $fax_forward_number);
			$fax_forward_number = str_replace("-", "", $fax_forward_number);
		}
		if (strripos($fax_forward_number, '$1') === false) {
			$fax_prefix = ''; //not found
		} else {
			$fax_prefix = $fax_forward_number.'#'; //found
		}
		$fax_description = check_str($_POST["fax_description"]);
	}

//delete the user from the fax users
	if ($_GET["a"] == "delete" && permission_exists("fax_extension_delete")) {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$fax_uuid = check_str($_REQUEST["id"]);

		//delete the group from the users
			$sql = "delete from v_fax_users ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and fax_uuid = '".$fax_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));

		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=fax_edit.php?id=$fax_uuid\">\n";
			echo "<div align='center'>".$text['confirm-delete']."</div>";
			require_once "includes/footer.php";
			return;
	}

//add the user to the fax users
	if (strlen($_REQUEST["user_uuid"]) > 0 && strlen($_REQUEST["id"]) > 0 && $_GET["a"] != "delete") {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$fax_uuid = check_str($_REQUEST["id"]);
		//assign the user to the fax extension
			$sql_insert = "insert into v_fax_users ";
			$sql_insert .= "(";
			$sql_insert .= "fax_user_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "fax_uuid, ";
			$sql_insert .= "user_uuid ";
			$sql_insert .= ")";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= "'".uuid()."', ";
			$sql_insert .= "'".$_SESSION['domain_uuid']."', ";
			$sql_insert .= "'".$fax_uuid."', ";
			$sql_insert .= "'".$user_uuid."' ";
			$sql_insert .= ")";
			$db->exec($sql_insert);

		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=fax_edit.php?id=$fax_uuid\">\n";
			echo "<div align='center'>".$text['confirm-add']."</div>";
			require_once "includes/footer.php";
			return;
	}

//clear file status cache
	clearstatcache(); 

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update" && permission_exists('fax_extension_edit')) {
		$fax_uuid = check_str($_POST["fax_uuid"]);
	}

	//check for all required data
		if (strlen($fax_extension) == 0) { $msg .= "".$text['confirm-ext']."<br>\n"; }
		if (strlen($fax_name) == 0) { $msg .= "".$text['confirm-fax']."<br>\n"; }
		//if (strlen($fax_email) == 0) { $msg .= "Please provide: Email<br>\n"; }
		//if (strlen($fax_pin_number) == 0) { $msg .= "Please provide: Pin Number<br>\n"; }
		//if (strlen($fax_caller_id_name) == 0) { $msg .= "Please provide: Caller ID Name<br>\n"; }
		//if (strlen($fax_caller_id_number) == 0) { $msg .= "Please provide: Caller ID Number<br>\n"; }
		//if (strlen($fax_forward_number) == 0) { $msg .= "Please provide: Forward Number<br>\n"; }
		//if (strlen($fax_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//set the PHP_BIN 
		if (file_exists(PHP_BINDIR."/php")) { define(PHP_BIN, 'php'); }
		if (file_exists(PHP_BINDIR."/php.exe")) {  define(PHP_BIN, 'php.exe'); }

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('fax_extension_add')) {
				//prepare the unique identifiers
					$fax_uuid = uuid();
					$dialplan_uuid = uuid();

				//add the fax extension to the database
					$sql = "insert into v_fax ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "fax_uuid, ";
					$sql .= "dialplan_uuid, ";
					$sql .= "fax_extension, ";
					$sql .= "fax_destination_number, ";
					$sql .= "fax_name, ";
					$sql .= "fax_email, ";
					$sql .= "fax_pin_number, ";
					$sql .= "fax_caller_id_name, ";
					$sql .= "fax_caller_id_number, ";
					if (strlen($fax_forward_number) > 0) {
						$sql .= "fax_forward_number, ";
					}
					$sql .= "fax_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$_SESSION['domain_uuid']."', ";
					$sql .= "'$fax_uuid', ";
					$sql .= "'$dialplan_uuid', ";
					$sql .= "'$fax_extension', ";
					$sql .= "'$fax_destination_number', ";
					$sql .= "'$fax_name', ";
					$sql .= "'$fax_email', ";
					$sql .= "'$fax_pin_number', ";
					$sql .= "'$fax_caller_id_name', ";
					$sql .= "'$fax_caller_id_number', ";
					if (strlen($fax_forward_number) > 0) {
						$sql .= "'$fax_forward_number', ";
					}
					$sql .= "'$fax_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//set the dialplan action
					$dialplan_type = "add";
			}

			if ($action == "update" && permission_exists('fax_extension_edit')) {
				//update the fax extension in the database
					$dialplan_type = "";
					$sql = "update v_fax set ";
					if (strlen($dialplan_uuid) > 0) {
						$sql .= "dialplan_uuid = '".$dialplan_uuid."', ";
					}
					$sql .= "fax_extension = '$fax_extension', ";
					$sql .= "fax_destination_number = '$fax_destination_number', ";
					$sql .= "fax_name = '$fax_name', ";
					$sql .= "fax_email = '$fax_email', ";
					$sql .= "fax_pin_number = '$fax_pin_number', ";
					$sql .= "fax_caller_id_name = '$fax_caller_id_name', ";
					$sql .= "fax_caller_id_number = '$fax_caller_id_number', ";
					if (strlen($fax_forward_number) > 0) {
						$sql .= "fax_forward_number = '$fax_forward_number', ";
					}
					else {
						$sql .= "fax_forward_number = null, ";
					}
					$sql .= "fax_description = '$fax_description' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and fax_uuid = '$fax_uuid' ";
					$db->exec(check_sql($sql));
					unset($sql);
			}

			//if there are no variables in the vars table then add them
				if ($dialplan_type != "add") {
					$sql = "select count(*) as num_rows from v_dialplans ";
					$sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] == 0) {
							$dialplan_type = "add";
						}
						else {
							$dialplan_type = "update";
						}
					}
				}

				if ($dialplan_type == "add") {
					//add the dialplan entry for fax
						$dialplan_name = $fax_name;
						$dialplan_order ='310';
						$dialplan_context = $_SESSION['context'];
						$dialplan_enabled = 'true';
						$dialplan_description = $fax_description;
						$app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
						dialplan_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

						//<!-- default ${domain_name} -->
						//<condition field="destination_number" expression="^\*9978$">
							$dialplan_detail_tag = 'condition'; //condition, action, antiaction
							$dialplan_detail_type = 'destination_number';
							$dialplan_detail_data = '^'.$fax_destination_number.'$';
							$dialplan_detail_order = '000';
							$dialplan_detail_group = '';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="system" data="$switch_scripts_dir/emailfax.sh USER DOMAIN {$_SESSION['switch']['scripts']['dir']}/fax/inbox/9872/${last_fax}.tif"/>
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'set';
							$dialplan_detail_data = "api_hangup_hook=system ".PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/fax_to_email.php ";
							$dialplan_detail_data .= "email=".$fax_email." ";
							$dialplan_detail_data .= "extension=".$fax_extension." ";
							$dialplan_detail_data .= "name=".$fax_prefix."\\\\\\\${last_fax} ";
							$dialplan_detail_data .= "messages='result: \\\\\\\${fax_result_text} sender:\\\\\\\${fax_remote_station_id} pages:\\\\\\\${fax_document_total_pages}' ";
							$dialplan_detail_data .= "domain=".$_SESSION['domain_name']." ";
							$dialplan_detail_data .= "caller_id_name='\\\\\\\${caller_id_name}' ";
							$dialplan_detail_data .= "caller_id_number=\\\\\\\${caller_id_number} ";
							$dialplan_detail_data .= "fax_relay=true ";

							$dialplan_detail_order = '010';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="answer" />
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'answer';
							$dialplan_detail_data = '';
							$dialplan_detail_order = '010';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="set" data="fax_enable_t38=true"/>
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'set';
							$dialplan_detail_data = 'fax_enable_t38=true';
							$dialplan_detail_order = '015';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="set" data="fax_enable_t38_request=true"/>
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'set';
							$dialplan_detail_data = 'fax_enable_t38_request=true';
							$dialplan_detail_order = '020';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="set" data="last_fax=${caller_id_number}-${strftime(%Y-%m-%d-%H-%M-%S)}"/>
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'set';
							$dialplan_detail_data = 'last_fax=${caller_id_number}-${strftime(%Y-%m-%d-%H-%M-%S)}';
							$dialplan_detail_order = '025';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="playback" data="silence_stream://2000"/>
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'playback';
							$dialplan_detail_data = 'silence_stream://2000';
							$dialplan_detail_order = '030';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="rxfax" data="$switch_storage_dir/fax/inbox/${last_fax}.tif"/>
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'rxfax';
							if (count($_SESSION["domains"]) > 1) {
								$dialplan_detail_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'].'/'.$fax_extension.'/inbox/'.$fax_prefix.'${last_fax}.tif';
							}
							else {
								$dialplan_detail_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$fax_extension.'/inbox/'.$fax_prefix.'${last_fax}.tif';
							}
							$dialplan_detail_order = '035';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

						//<action application="hangup"/>
							$dialplan_detail_tag = 'action'; //condition, action, antiaction
							$dialplan_detail_type = 'hangup';
							$dialplan_detail_data = '';
							$dialplan_detail_order = '040';
							dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);
				}
				if ($dialplan_type == "update") {
					//udpate the fax dialplan entry
						$sql = "update v_dialplans set ";
						$sql .= "dialplan_name = '$fax_name', ";
						if (strlen($dialplan_order) > 0) {
							$sql .= "dialplan_order = '333', ";
						}
						$sql .= "dialplan_context = '".$_SESSION['context']."', ";
						$sql .= "dialplan_enabled = 'true', ";
						$sql .= "dialplan_description = '$fax_description' ";
						$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
						$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
						$db->query($sql);
						unset($sql);

					//update dialplan detail condition
						$sql = "update v_dialplan_details set ";
						$sql .= "dialplan_detail_data = '^".$fax_destination_number."$' ";
						$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
						$sql .= "and dialplan_detail_tag = 'condition' ";
						$sql .= "and dialplan_detail_type = 'destination_number' ";
						$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
						$db->query($sql);
						unset($sql);

					//update dialplan detail action
						if (count($_SESSION["domains"]) > 1) {
							$dialplan_detail_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'].'/'.$fax_extension.'/inbox/'.$fax_prefix.'${last_fax}.tif';
						}
						else {
							$dialplan_detail_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$fax_extension.'/inbox/'.$fax_prefix.'${last_fax}.tif';
						}
						$sql = "update v_dialplan_details set ";
						$sql .= "dialplan_detail_data = '".$dialplan_detail_data."' ";
						$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
						$sql .= "and dialplan_detail_tag = 'action' ";
						$sql .= "and dialplan_detail_type = 'rxfax' ";
						$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
						$db->query($sql);

					//update dialplan detail action
						$dialplan_detail_tag = 'action'; //condition, action, antiaction
						$dialplan_detail_type = 'set';
						$dialplan_detail_data = "api_hangup_hook=system ".PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/fax_to_email.php ";
						$dialplan_detail_data .= "email=".$fax_email." ";
						$dialplan_detail_data .= "extension=".$fax_extension." ";
						$dialplan_detail_data .= "name=".$fax_prefix."\\\\\\\${last_fax} ";
						$dialplan_detail_data .= "messages='result: \\\\\\\${fax_result_text} sender:\\\\\\\${fax_remote_station_id} pages:\\\\\\\${fax_document_total_pages}' ";
						$dialplan_detail_data .= "domain=".$_SESSION['domain_name']." ";
						$dialplan_detail_data .= "caller_id_name='\\\\\\\${caller_id_name}' ";
						$dialplan_detail_data .= "caller_id_number=\\\\\\\${caller_id_number} ";
						$dialplan_detail_data .= "fax_relay=true ";
						$sql = "update v_dialplan_details set ";
						$sql .= "dialplan_detail_data = '".check_str($dialplan_detail_data)."' ";
						$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
						$sql .= "and dialplan_detail_tag = 'action' ";
						$sql .= "and dialplan_detail_type = 'set' ";
						$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
						$sql .= "and dialplan_detail_data like 'api_hangup_hook=%' ";
						$db->query(check_sql($sql));
				}

			//save the xml
				save_dialplan_xml();

			//apply settings reminder
				$_SESSION["reload_xml"] = true;

			//delete the dialplan context from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd .= "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

			//redirect the browser
				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=fax.php\">\n";
				echo "<div align='center'>\n";
				if ($action == "update" && permission_exists('fax_extension_edit')) {
					echo "".$text['confirm-update']."\n";
				}
				if ($action == "add" && permission_exists('fax_extension_add')) {
					echo "".$text['confirm-add']."\n";
				}
				echo "</div>\n";
				require_once "includes/footer.php";
				return;

		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (strlen($_GET['id']) > 0 && $_POST["persistformvar"] != "true") {
		$fax_uuid = check_str($_GET["id"]);
		$sql = "select * from v_fax ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and fax_uuid = '$fax_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (count($result) == 0) {
			echo "access denied";
			exit;
		}
		foreach ($result as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$fax_extension = $row["fax_extension"];
			$fax_destination_number = $row["fax_destination_number"];
			$fax_name = $row["fax_name"];
			$fax_email = $row["fax_email"];
			$fax_pin_number = $row["fax_pin_number"];
			$fax_caller_id_name = $row["fax_caller_id_name"];
			$fax_caller_id_number = $row["fax_caller_id_number"];
			$fax_forward_number = $row["fax_forward_number"];
			$fax_description = $row["fax_description"];
		}
		unset ($prep_statement);
	}

//set the dialplan_uuid
	if (strlen($dialplan_uuid) == 0) {
		$dialplan_uuid = uuid();
	}

//show the header
	require_once "includes/header.php";

//fax extension form
	echo "<div align='center'>";
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap><b>".$text['confirm-fax-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap><b>".$text['confirm-fax-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='copy' onclick=\"if (confirm('".$text['confirm-copy-info']."')){window.location='fax_copy.php?id=".$fax_uuid."';}\" value='".$text['button-copy']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='fax.php'\" value='".$text['button-back']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_name' maxlength='255' value=\"$fax_name\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-extension'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_extension' maxlength='255' value=\"$fax_extension\">\n";
	echo "<br />\n";
	echo "".$text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-destination-number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_destination_number' maxlength='255' value=\"$fax_destination_number\">\n";
	echo "<br />\n";
	echo " ".$text['description-destination-number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-email'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_email' maxlength='255' value=\"$fax_email\">\n";
	echo "<br />\n";
	echo "	".$text['description-email']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-pin'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_pin_number' maxlength='255' value=\"$fax_pin_number\">\n";
	echo "<br />\n";
	echo "".$text['description-pin']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-caller-id-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_caller_id_name' maxlength='255' value=\"$fax_caller_id_name\">\n";
	echo "<br />\n";
	echo "".$text['description-caller-id-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-caller-id-number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_caller_id_number' maxlength='255' value=\"$fax_caller_id_number\">\n";
	echo "<br />\n";
	echo "".$text['description-caller-id-number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-forward'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (is_numeric($fax_forward_number)) {
		echo "	<input class='formfld' type='text' name='fax_forward_number' maxlength='255' value=\"".format_phone($fax_forward_number)."\">\n";
	}
	else {
		echo "	<input class='formfld' type='text' name='fax_forward_number' maxlength='255' value=\"".$fax_forward_number."\">\n";
	}
	echo "<br />\n";
	echo "".$text['description-forward-number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (if_group("admin") || if_group("superadmin")) {
		if ($action == "update") {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-user-list'].":</td>";
			echo "		<td class='vtable'>";

			echo "			<table width='52%'>\n";
			$sql = "SELECT * FROM v_fax_users as e, v_users as u ";
			$sql .= "where e.user_uuid = u.user_uuid  ";
			$sql .= "and e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and e.fax_uuid = '".$fax_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($result);
			foreach($result as $field) {
				echo "			<tr>\n";
				echo "				<td class='vtable'>".$field['username']."</td>\n";
				echo "				<td>\n";
				echo "					<a href='fax_edit.php?id=".$fax_uuid."&domain_uuid=".$_SESSION['domain_uuid']."&user_uuid=".$field['user_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
				echo "				</td>\n";
				echo "			</tr>\n";
			}
			echo "			</table>\n";

			echo "			<br />\n";
			$sql = "SELECT * FROM v_users ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			echo "			<select name=\"user_uuid\" class='frm'>\n";
			echo "			<option value=\"\"></option>\n";
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $field) {
				echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
			}
			echo "			</select>";
			echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
			unset($sql, $result);
			echo "			<br>\n";
			echo "			".$text['description-user-add']."\n";
			echo "			<br />\n";
			echo "		</td>";
			echo "	</tr>";
		}
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_description' maxlength='255' value=\"$fax_description\">\n";
	echo "<br />\n";
	echo "".$text['description-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "			<input type='hidden' name='fax_uuid' value='$fax_uuid'>\n";
		echo "			<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
	}
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "</div>";

	echo "<br />\n";
	echo "<br />\n";

//show the footer
	require_once "includes/footer.php";
?>
