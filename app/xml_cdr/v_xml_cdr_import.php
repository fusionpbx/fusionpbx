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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//check the permission
	if(defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/app\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		require_once "includes/require.php";
		$display_type = 'text'; //html, text
	}
	else {
		include "root.php";
		require_once "includes/require.php";
	}

//set debug
	$debug = false; //true //false
	if($debug){
		$time5 = microtime(true);
		$insert_time=$insert_count=0;
	}

//increase limits
	set_time_limit(3600);
	ini_set('memory_limit', '256M');

//set pdo attribute that enables exception handling
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//define the process_xml_cdr function
	function process_xml_cdr($db, $leg, $xml_string) {
		//set global variable
			global $debug;

		//parse the xml to get the call detail record info
			try {
				$xml = simplexml_load_string($xml_string);
			}
			catch(Exception $e) {
				echo $e->getMessage();
			}

		//prepare the database object
			require_once "includes/classes/database.php";
			$database = new database;
			$database->table = "v_xml_cdr";

			//misc
				$uuid = check_str(urldecode($xml->variables->uuid));
				$database->fields['uuid'] = $uuid;
				$database->fields['accountcode'] = check_str(urldecode($xml->variables->accountcode));
				$database->fields['default_language'] = check_str(urldecode($xml->variables->default_language));
				$database->fields['bridge_uuid'] = check_str(urldecode($xml->variables->bridge_uuid));
				//$database->fields['digits_dialed'] = check_str(urldecode($xml->variables->digits_dialed));
				$database->fields['sip_hangup_disposition'] = check_str(urldecode($xml->variables->sip_hangup_disposition));
			//time
				$database->fields['start_epoch'] = check_str(urldecode($xml->variables->start_epoch));
				$start_stamp = check_str(urldecode($xml->variables->start_stamp));
				$database->fields['start_stamp'] = $start_stamp;
				$database->fields['answer_stamp'] = check_str(urldecode($xml->variables->answer_stamp));
				$database->fields['answer_epoch'] = check_str(urldecode($xml->variables->answer_epoch));
				$database->fields['end_epoch'] = check_str(urldecode($xml->variables->end_epoch));
				$database->fields['end_stamp'] = check_str(urldecode($xml->variables->end_stamp));
				$database->fields['duration'] = check_str(urldecode($xml->variables->duration));
				$database->fields['mduration'] = check_str(urldecode($xml->variables->mduration));
				$database->fields['billsec'] = check_str(urldecode($xml->variables->billsec));
				$database->fields['billmsec'] = check_str(urldecode($xml->variables->billmsec));
			//codecs
				$database->fields['read_codec'] = check_str(urldecode($xml->variables->read_codec));
				$database->fields['read_rate'] = check_str(urldecode($xml->variables->read_rate));
				$database->fields['write_codec'] = check_str(urldecode($xml->variables->write_codec));
				$database->fields['write_rate'] = check_str(urldecode($xml->variables->write_rate));
				$database->fields['remote_media_ip'] = check_str(urldecode($xml->variables->remote_media_ip));
				$database->fields['hangup_cause'] = check_str(urldecode($xml->variables->hangup_cause));
				$database->fields['hangup_cause_q850'] = check_str(urldecode($xml->variables->hangup_cause_q850));
			//call center
				$database->fields['cc_side'] = check_str(urldecode($xml->variables->cc_side));
				$database->fields['cc_member_uuid'] = check_str(urldecode($xml->variables->cc_member_uuid));
				$database->fields['cc_queue_joined_epoch'] = check_str(urldecode($xml->variables->cc_queue_joined_epoch));
				$database->fields['cc_queue'] = check_str(urldecode($xml->variables->cc_queue));
				$database->fields['cc_member_session_uuid'] = check_str(urldecode($xml->variables->cc_member_session_uuid));
				$database->fields['cc_agent'] = check_str(urldecode($xml->variables->cc_agent));
				$database->fields['cc_agent_type'] = check_str(urldecode($xml->variables->cc_agent_type));
				$database->fields['waitsec'] = check_str(urldecode($xml->variables->waitsec));
			//app info
				$database->fields['last_app'] = check_str(urldecode($xml->variables->last_app));
				$database->fields['last_arg'] = check_str(urldecode($xml->variables->last_arg));
			//conference
				$database->fields['conference_name'] = check_str(urldecode($xml->variables->conference_name));
				$database->fields['conference_uuid'] = check_str(urldecode($xml->variables->conference_uuid));
				$database->fields['conference_member_id'] = check_str(urldecode($xml->variables->conference_member_id));

		//get the values from the callflow.
			$x = 0;
			foreach ($xml->callflow as $row) {
				if ($x == 0) {
					$context = check_str(urldecode($row->caller_profile->context));
					$database->fields['destination_number'] = check_str(urldecode($row->caller_profile->destination_number));
					$database->fields['context'] = $context;
					$database->fields['network_addr'] = check_str(urldecode($row->caller_profile->network_addr));
				}
				$database->fields['caller_id_name'] = check_str(urldecode($row->caller_profile->caller_id_name));
				$database->fields['caller_id_number'] = check_str(urldecode($row->caller_profile->caller_id_number));
				$x++;
			}
			unset($x);

		//store the call leg
			$database->fields['leg'] = $leg;

		//store the call direction
			$database->fields['direction'] = check_str(urldecode($xml->variables->call_direction));

		//store post dial delay, in milliseconds
			$database->fields['pdd_ms'] = check_str(urldecode($xml->variables->progress_mediamsec) + urldecode($xml->variables->progressmsec));

		//get break down the date to year, month and day
			$tmp_time = strtotime($start_stamp);
			$tmp_year = date("Y", $tmp_time);
			$tmp_month = date("M", $tmp_time);
			$tmp_day = date("d", $tmp_time);

		//get the domain values from the xml
			$domain_name = check_str(urldecode($xml->variables->domain_name));
			$domain_uuid = check_str(urldecode($xml->variables->domain_uuid));

		//get the domain_uuid with the domain_name
			if (strlen($domain_uuid) == 0) {
				$sql = "select domain_uuid from v_domains ";
				if (strlen($domain_name) == 0 && $context != 'public' && $context != 'default') {
					$sql .= "where domain_name = '".$context."' ";
				}
				else {
					$sql .= "where domain_name = '".$domain_name."' ";
				}
				$row = $db->query($sql)->fetch();
				$domain_uuid = $row['domain_uuid'];
				if (strlen($domain_uuid) == 0) {
					$sql = "select domain_name, domain_uuid from v_domains ";
					$row = $db->query($sql)->fetch();
					$domain_uuid = $row['domain_uuid'];
					if (strlen($domain_name) == 0) { $domain_name = $row['domain_name']; }
				}
			}

		//set values in the database
			$database->domain_uuid = $domain_uuid;
			$database->fields['domain_uuid'] = $domain_uuid;
			$database->fields['domain_name'] = $domain_name;

		//check whether a recording exists
			$recording_relative_path = '/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
			if (file_exists($_SESSION['switch']['recordings']['dir'].$recording_relative_path.'/'.$uuid.'.wav')) {
				$recording_file = $recording_relative_path.'/'.$uuid.'.wav';
			}
			elseif (file_exists($_SESSION['switch']['recordings']['dir'].$recording_relative_path.'/'.$uuid.'.mp3')) {
				$recording_file = $recording_relative_path.'/'.$uuid.'.mp3';
			}
			if(isset($recording_file) && !empty($recording_file)) { 
				$database->fields['recording_file'] = $recording_file;
			}

		//determine where the xml cdr will be archived
			$sql = "select * from v_vars ";
			$sql .= "where var_name = 'xml_cdr_archive' ";
			$row = $db->query($sql)->fetch();
			$var_value = trim($row["var_value"]);
			switch ($var_value) {
			case "dir":
				$xml_cdr_archive = 'dir';
				break;
			case "db":
				$xml_cdr_archive = 'db';
				break;
			case "none":
				$xml_cdr_archive = 'none';
				break;
			default:
				$xml_cdr_archive = 'dir';
				break;
			}

		//if xml_cdr_archive is set to db then insert it.
			if ($xml_cdr_archive == "db") {
				$database->fields['xml_cdr'] = check_str($xml_string);
			}

		//insert the check_str($extension_uuid)
			if (strlen($xml->variables->extension_uuid) > 0) {
				$database->fields['extension_uuid'] = check_str(urldecode($xml->variables->extension_uuid));
			}

		//insert xml_cdr into the db
			if (strlen($start_stamp) > 0) {
				$database->add();
				if ($debug) {
					echo $database->sql."\n";
				}
			}

		//insert the values
			if (strlen($uuid) > 0) {
				if ($debug) {
					$time5_insert = microtime(true);
					//echo $sql."<br />\n";
				}
				try {
					$error = "false";
					//$db->exec(check_sql($sql));
				}
				catch(PDOException $e) {
					$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/failed/';
					if(!file_exists($tmp_dir)) {
						mkdir($tmp_dir, 0777, true);
					}
					$tmp_file = $uuid.'.xml';
					$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
					fwrite($fh, $xml_string);
					fclose($fh);
					if ($debug) {
						echo $e->getMessage();
					}
					$error = "true";
				}
				//if xml_cdr_archive is set to dir, then store it.
				if ($xml_cdr_archive == "dir" && $error != "true") {
					if (strlen($uuid) > 0) {
						$tmp_time = strtotime($start_stamp);
						$tmp_year = date("Y", $tmp_time);
						$tmp_month = date("M", $tmp_time);
						$tmp_day = date("d", $tmp_time);
						$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
						if(!file_exists($tmp_dir)) {
							mkdir($tmp_dir, 0777, true);
						}
						$tmp_file = $uuid.'.xml';
						$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
						fwrite($fh, $xml_string);
						fclose($fh);
					}
				}
				unset($error);

				if ($debug) {
					GLOBAL $insert_time,$insert_count;
					$insert_time+=microtime(true)-$time5_insert;//add this current query.
					$insert_count++;
				}
			}
			unset($sql);
	}

//get cdr details from the http post
	if (strlen($_POST["cdr"]) > 0) {

		//authentication for xml cdr http post
			if (strlen($_SESSION["xml_cdr"]["http_enabled"]) == 0) {
				//get the contents of xml_cdr.conf.xml
					$conf_xml_string = file_get_contents($_SESSION['switch']['conf']['dir'].'/autoload_configs/xml_cdr.conf.xml');

				//parse the xml to get the call detail record info
					try {
						$conf_xml = simplexml_load_string($conf_xml_string);
					}
					catch(Exception $e) {
						echo $e->getMessage();
					}
					$_SESSION["xml_cdr"]["http_enabled"] = false;
					foreach ($conf_xml->settings->param as $row) {
						if ($row->attributes()->name == "cred") {
							$auth_array = explode(":", $row->attributes()->value);
							$_SESSION["xml_cdr"]["username"] = $auth_array[0];
							$_SESSION["xml_cdr"]["password"] = $auth_array[1];
							//echo "username: ".$_SESSION["xml_cdr"]["username"]."<br />\n";
							//echo "password: ".$_SESSION["xml_cdr"]["password"]."<br />\n";
						}
						if ($row->attributes()->name == "url") {
							$_SESSION["xml_cdr"]["http_enabled"] = true;
						}
					}
			}

		//if http enabled is set to false then deny access
			if (!$_SESSION["xml_cdr"]["http_enabled"]) {
				echo "access denied<br />\n";
				return;
			}

		//check for the correct username and password
			if ($_SESSION["xml_cdr"]["username"] == $_SERVER["PHP_AUTH_USER"] && $_SESSION["xml_cdr"]["password"] == $_SERVER["PHP_AUTH_PW"]) {
				//echo "access granted<br />\n";
			}
			else {
				echo "access denied<br />\n";
				return;
			}
		//loop through all attribues
			//foreach($xml->settings->param[1]->attributes() as $a => $b) {
			//		echo $a,'="',$b,"\"<br />\n";
			//}

		//get the http post variable
			$xml_string = trim($_POST["cdr"]);

		//get the leg of the call
			if (substr($_REQUEST['uuid'], 0, 2) == "a_") {
				$leg = "a";
			}
			else {
				$leg = "b";
			}

		//parse the xml and insert the data into the db
			process_xml_cdr($db, $leg, $xml_string);
	}

//check the filesystem for xml cdr records that were missed
	$xml_cdr_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr';
	$dir_handle = opendir($xml_cdr_dir);
	$x = 0;
	while($file=readdir($dir_handle)) {
		if ($file != '.' && $file != '..') {
			if ( !is_dir($xml_cdr_dir . '/' . $file) ) {
				//get the leg of the call
					if (substr($file, 0, 2) == "a_") {
						$leg = "a";
					}
					else {
						$leg = "b";
					}

				//get the xml cdr string
					$xml_string = file_get_contents($xml_cdr_dir.'/'.$file);

				//parse the xml and insert the data into the db
					process_xml_cdr($db, $leg, $xml_string);

				//delete the file after it has been imported
					unlink($xml_cdr_dir.'/'.$file);

				$x++;
			}
		}
	}
	closedir($dir_handle);

//debug true
	if ($debug) {
		$content = ob_get_contents(); //get the output from the buffer
		ob_end_clean(); //clean the buffer
		$time = "\n\n$insert_count inserts in: ".number_format($insert_time,5). " seconds.\n";
		$time .= "Other processing time: ".number_format((microtime(true)-$time5-$insert_time),5). " seconds.\n";
		$fp = fopen($_SESSION['server']['temp']['dir'].'/xml_cdr.log', 'w');
		fwrite($fp, $content.$time);
		fclose($fp);
	}

?>