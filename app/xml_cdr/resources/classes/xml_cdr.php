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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/


/**
 * xml_cdr class provides methods for adding cdr records to the database
 *
 * @method boolean add
 */
if (!class_exists('xml_cdr')) {
	class xml_cdr {

		//define variables
		public $db;
		public $array;
		public $debug;
		public $fields;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			if (isset($this)) foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}


		/**
		 * cdr process logging
		 */
		public function log($message) {
			//save to file system (alternative to a syslog server)
				$fp = fopen($_SESSION['server']['temp']['dir'].'/xml_cdr.log', 'a+');
				if (!$fp) {
					return;
				}
				fwrite($fp, $message);
				fclose($fp);
		}

		/**
		 * cdr fields in the database schema
		 */
		public function fields() {

			$this->fields[] = "uuid";
			$this->fields[] = "domain_uuid";
			$this->fields[] = "extension_uuid";
			$this->fields[] = "domain_name";
			$this->fields[] = "accountcode";
			$this->fields[] = "direction";
			$this->fields[] = "default_language";
			$this->fields[] = "context";
			$this->fields[] = "xml";
			$this->fields[] = "json";
			$this->fields[] = "caller_id_name";
			$this->fields[] = "caller_id_number";
			$this->fields[] = "destination_number";
			$this->fields[] = "start_epoch";
			$this->fields[] = "start_stamp";
			$this->fields[] = "answer_stamp";
			$this->fields[] = "answer_epoch";
			$this->fields[] = "end_epoch";
			$this->fields[] = "end_stamp";
			$this->fields[] = "duration";
			$this->fields[] = "mduration";
			$this->fields[] = "billsec";
			$this->fields[] = "billmsec";
			$this->fields[] = "bridge_uuid";
			$this->fields[] = "read_codec";
			$this->fields[] = "read_rate";
			$this->fields[] = "write_codec";
			$this->fields[] = "write_rate";
			$this->fields[] = "remote_media_ip";
			$this->fields[] = "network_addr";
			$this->fields[] = "recording_file";
			$this->fields[] = "leg";
			$this->fields[] = "pdd_ms";
			$this->fields[] = "rtp_audio_in_mos";
			$this->fields[] = "last_app";
			$this->fields[] = "last_arg";
			$this->fields[] = "cc_side";
			$this->fields[] = "cc_member_uuid";
			$this->fields[] = "cc_queue_joined_epoch";
			$this->fields[] = "cc_queue";
			$this->fields[] = "cc_member_session_uuid";
			$this->fields[] = "cc_agent";
			$this->fields[] = "cc_agent_type";
			$this->fields[] = "waitsec";
			$this->fields[] = "conference_name";
			$this->fields[] = "conference_uuid";
			$this->fields[] = "conference_member_id";
			$this->fields[] = "digits_dialed";
			$this->fields[] = "pin_number";
			$this->fields[] = "hangup_cause";
			$this->fields[] = "hangup_cause_q850";
			$this->fields[] = "sip_hangup_disposition";
		}

		/**
		 * save to the database
		 */
		public function save() {

			$this->fields();
			$field_count = sizeof($this->fields);

			$sql = "insert into v_xml_cdr (";
			$f = 1;
			if (isset($this->fields)) foreach ($this->fields as $field) {
				if ($field_count == $f) {
					$sql .= "$field ";
				}
				else {
					$sql .= "$field, ";
				}
				$f++;
			}
			$sql .= ")\n";
			$sql .= "values \n";
			$row_count = sizeof($this->array);
			//$field_count = sizeof($this->fields);
			$i = 0;
			if (isset($this->array)) foreach ($this->array as $row) {
				$sql .= "(";
				$f = 1;
				if (isset($this->fields)) foreach ($this->fields as $field) {
					if (isset($row[$field]) && strlen($row[$field]) > 0) {
						$sql .= "'".$row[$field]."'";
					}
					else {
						$sql .= "null";
					}
					if ($field_count != $f) {
						$sql .= ",";
					}
					$f++;
				}
				$sql .= ")";
				if ($row_count != $i) {
					$sql .= ",\n";
				}
				$i++;
			}
			if (substr($sql,-2) == ",\n") {
				$sql = substr($sql,0,-2);
			}
			$this->db->exec(check_sql($sql));
			unset($sql);
		}

		/**
		 * process method converts the xml cdr and adds it to the database
		 */
		public function xml_array($row, $leg, $xml_string) {

			//fix the xml by escaping the contents of <sip_full_XXX>
				if(defined('STDIN')) {
					$xml_string = preg_replace_callback("/<([^><]+)>(.*?[><].*?)<\/\g1>/",
						function ($matches) {
							return '<' . $matches[1] . '>' .
								str_replace(">", "&gt;",
									str_replace("<", "&lt;", $matches[2])
								) .
							'</' . $matches[1] . '>';
						},
						$xml_string
					);
				}

			//parse the xml to get the call detail record info
				try {
					//$this->log($xml_string);
					$xml = simplexml_load_string($xml_string);
					//$this->log("\nxml load done\n");
				}
				catch(Exception $e) {
					echo $e->getMessage();
					//$this->log("\nfail loadxml: " . $e->getMessage() . "\n");
				}

			//misc
				$uuid = check_str(urldecode($xml->variables->uuid));
				$this->array[$row]['uuid'] = $uuid;
				$this->array[$row]['accountcode'] = check_str(urldecode($xml->variables->accountcode));
				$this->array[$row]['default_language'] = check_str(urldecode($xml->variables->default_language));
				$this->array[$row]['bridge_uuid'] = check_str(urldecode($xml->variables->bridge_uuid));
				//$this->array[$row]['digits_dialed'] = check_str(urldecode($xml->variables->digits_dialed));
				$this->array[$row]['sip_hangup_disposition'] = check_str(urldecode($xml->variables->sip_hangup_disposition));
				$this->array[$row]['pin_number'] = check_str(urldecode($xml->variables->pin_number));
			//time
				$this->array[$row]['start_epoch'] = check_str(urldecode($xml->variables->start_epoch));
				$start_stamp = check_str(urldecode($xml->variables->start_stamp));
				$this->array[$row]['start_stamp'] = $start_stamp;
				$this->array[$row]['answer_stamp'] = check_str(urldecode($xml->variables->answer_stamp));
				$this->array[$row]['answer_epoch'] = check_str(urldecode($xml->variables->answer_epoch));
				$this->array[$row]['end_epoch'] = check_str(urldecode($xml->variables->end_epoch));
				$this->array[$row]['end_stamp'] = check_str(urldecode($xml->variables->end_stamp));
				$this->array[$row]['duration'] = check_str(urldecode($xml->variables->duration));
				$this->array[$row]['mduration'] = check_str(urldecode($xml->variables->mduration));
				$this->array[$row]['billsec'] = check_str(urldecode($xml->variables->billsec));
				$this->array[$row]['billmsec'] = check_str(urldecode($xml->variables->billmsec));
			//codecs
				$this->array[$row]['read_codec'] = check_str(urldecode($xml->variables->read_codec));
				$this->array[$row]['read_rate'] = check_str(urldecode($xml->variables->read_rate));
				$this->array[$row]['write_codec'] = check_str(urldecode($xml->variables->write_codec));
				$this->array[$row]['write_rate'] = check_str(urldecode($xml->variables->write_rate));
				$this->array[$row]['remote_media_ip'] = check_str(urldecode($xml->variables->remote_media_ip));
				$this->array[$row]['hangup_cause'] = check_str(urldecode($xml->variables->hangup_cause));
				$this->array[$row]['hangup_cause_q850'] = check_str(urldecode($xml->variables->hangup_cause_q850));
			//call center
				$this->array[$row]['cc_side'] = check_str(urldecode($xml->variables->cc_side));
				$this->array[$row]['cc_member_uuid'] = check_str(urldecode($xml->variables->cc_member_uuid));
				$this->array[$row]['cc_queue_joined_epoch'] = check_str(urldecode($xml->variables->cc_queue_joined_epoch));
				$this->array[$row]['cc_queue'] = check_str(urldecode($xml->variables->cc_queue));
				$this->array[$row]['cc_member_session_uuid'] = check_str(urldecode($xml->variables->cc_member_session_uuid));
				$this->array[$row]['cc_agent'] = check_str(urldecode($xml->variables->cc_agent));
				$this->array[$row]['cc_agent_type'] = check_str(urldecode($xml->variables->cc_agent_type));
				$this->array[$row]['waitsec'] = check_str(urldecode($xml->variables->waitsec));
			//app info
				$this->array[$row]['last_app'] = check_str(urldecode($xml->variables->last_app));
				$this->array[$row]['last_arg'] = check_str(urldecode($xml->variables->last_arg));
			//conference
				$this->array[$row]['conference_name'] = check_str(urldecode($xml->variables->conference_name));
				$this->array[$row]['conference_uuid'] = check_str(urldecode($xml->variables->conference_uuid));
				$this->array[$row]['conference_member_id'] = check_str(urldecode($xml->variables->conference_member_id));
			//call quality
				$rtp_audio_in_mos = check_str(urldecode($xml->variables->rtp_audio_in_mos));
				if (strlen($rtp_audio_in_mos) > 0) {
					$this->array[$row]['rtp_audio_in_mos'] = $rtp_audio_in_mos;
				}

			//get the values from the callflow.
				$x = 0;
				if (isset($xml->callflow)) foreach ($xml->callflow as $callflow) {
					if ($x == 0) {
						$context = check_str(urldecode($callflow->caller_profile->context));
						$this->array[$row]['destination_number'] = check_str(urldecode($callflow->caller_profile->destination_number));
						$this->array[$row]['context'] = $context;
						$this->array[$row]['network_addr'] = check_str(urldecode($callflow->caller_profile->network_addr));
					}
					$this->array[$row]['caller_id_name'] = check_str(urldecode($callflow->caller_profile->caller_id_name));
					$this->array[$row]['caller_id_number'] = check_str(urldecode($callflow->caller_profile->caller_id_number));
					$x++;
				}
				unset($x);

			//store the call leg
				$this->array[$row]['leg'] = $leg;

			//store the call direction
				$this->array[$row]['direction'] = check_str(urldecode($xml->variables->call_direction));

			//store post dial delay, in milliseconds
				$this->array[$row]['pdd_ms'] = check_str(urldecode($xml->variables->progress_mediamsec) + urldecode($xml->variables->progressmsec));

			//get break down the date to year, month and day
				$tmp_time = strtotime($start_stamp);
				$tmp_year = date("Y", $tmp_time);
				$tmp_month = date("M", $tmp_time);
				$tmp_day = date("d", $tmp_time);

			//get the domain values from the xml
				$domain_name = check_str(urldecode($xml->variables->domain_name));
				$domain_uuid = check_str(urldecode($xml->variables->domain_uuid));

			//get the domain name from sip_req_host
				if (strlen($domain_name) == 0) {
					$domain_name = check_str(urldecode($xml->variables->sip_req_host));
				}

			//send the domain name to the cdr log
				//$this->log("\ndomain_name is `$domain_name`; domain_uuid is '$domain_uuid'\n");

			//get the domain_uuid with the domain_name
				if (strlen($domain_uuid) == 0) {
					$sql = "select domain_uuid from v_domains ";
					if (strlen($domain_name) == 0 && $context != 'public' && $context != 'default') {
						$sql .= "where domain_name = '".$context."' ";
					}
					else {
						$sql .= "where domain_name = '".$domain_name."' ";
					}
					$row = $this->db->query($sql)->fetch();
					$domain_uuid = $row['domain_uuid'];
				}

			//set values in the database
				if (strlen($domain_uuid) > 0) {
					$this->array[$row]['domain_uuid'] = $domain_uuid;
				}
				if (strlen($domain_name) > 0) {
					$this->array[$row]['domain_name'] = $domain_name;
				}

			//check whether a recording exists
				$recording_relative_path = '/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
				if (file_exists($_SESSION['switch']['recordings']['dir'].$recording_relative_path.'/'.$uuid.'.wav')) {
					$recording_file = $recording_relative_path.'/'.$uuid.'.wav';
				}
				elseif (file_exists($_SESSION['switch']['recordings']['dir'].$recording_relative_path.'/'.$uuid.'.mp3')) {
					$recording_file = $recording_relative_path.'/'.$uuid.'.mp3';
				}
				if(isset($recording_file) && !empty($recording_file)) {
					$this->array[$row]['recording_file'] = $recording_file;
				}

			//save to the database in xml format
				if ($_SESSION['cdr']['format']['text'] == "xml" && $_SESSION['cdr']['storage']['text'] == "db") {
					$this->array[$row]['xml'] = check_str($xml_string);
				}

			//save to the database in json format
				if ($_SESSION['cdr']['format']['text'] == "json" && $_SESSION['cdr']['storage']['text'] == "db") {
					$this->array[$row]['json'] = check_str(json_encode($xml));
				}

			//insert the check_str($extension_uuid)
				if (strlen($xml->variables->extension_uuid) > 0) {
					$this->array[$row]['extension_uuid'] = check_str(urldecode($xml->variables->extension_uuid));
				}

			//insert the values
				if (strlen($uuid) > 0) {
					if ($this->debug) {
						//$time5_insert = microtime(true);
						//echo $sql."<br />\n";
					}
					try {
						$error = "false";
						//$this->db->exec(check_sql($sql));
					}
					catch(PDOException $e) {
						$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/failed/';
						if(!file_exists($tmp_dir)) {
							mkdir($tmp_dir, 0777, true);
						}
						if ($_SESSION['cdr']['format']['text'] == "xml") {
							$tmp_file = $uuid.'.xml';
							$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
							fwrite($fh, $xml_string);
						}
						else {
							$tmp_file = $uuid.'.json';
							$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
							fwrite($fh, json_encode($xml));
						}
						fclose($fh);
						if ($this->debug) {
							echo $e->getMessage();
						}
						$error = "true";
					}

					if ($_SESSION['cdr']['storage']['text'] == "dir" && $error != "true") {
						if (strlen($uuid) > 0) {
							$tmp_time = strtotime($start_stamp);
							$tmp_year = date("Y", $tmp_time);
							$tmp_month = date("M", $tmp_time);
							$tmp_day = date("d", $tmp_time);
							$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
							if(!file_exists($tmp_dir)) {
								mkdir($tmp_dir, 0777, true);
							}
							if ($_SESSION['cdr']['format']['text'] == "xml") {
								$tmp_file = $uuid.'.xml';
								$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
								fwrite($fh, $xml_string);
							}
							else {
								$tmp_file = $uuid.'.json';
								$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
								fwrite($fh, json_encode($xml));
							}
							fclose($fh);
						}
					}
					unset($error);

					//if ($this->debug) {
						//GLOBAL $insert_time,$insert_count;
						//$insert_time+=microtime(true)-$time5_insert; //add this current query.
						//$insert_count++;
					//}
				}
				unset($sql);
			}

		/**
		 * get xml from the filesystem and save it to the database
		 */
		public function read_files() {
			$xml_cdr_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr';
			$dir_handle = opendir($xml_cdr_dir);
			$x = 0;
			while($file = readdir($dir_handle)) {
				if ($file != '.' && $file != '..') {
					if ( !is_dir($xml_cdr_dir . '/' . $file) ) {
						//get the leg of the call and the file prefix
							if (substr($file, 0, 2) == "a_") {
								$leg = "a";
								$file_prefix = substr($file, 2, 1);
							}
							else {
								$leg = "b";
								$file_prefix = substr($file, 0, 1);
							}

						//set the limit
							if (isset($_SERVER["argv"][1]) && is_numeric($_SERVER["argv"][1])) {
								$limit = $_SERVER["argv"][1];
							}
							else {
								$limit = 1;
							}

						//filter for specific files based on the file prefix
							if (isset($_SERVER["argv"][2])) {
								if (strpos($_SERVER["argv"][2], $file_prefix) !== FALSE) {
									$import = true;
								}
								else {
									$import = false;
								}
							}
							else {
								$import = true;
							}

						//import the call detail record
							if ($import) {
								//get the xml cdr string
									$xml_string = file_get_contents($xml_cdr_dir.'/'.$file);

								//parse the xml and insert the data into the db
									$this->xml_array($x, $leg, $xml_string);

								//delete the file after it has been imported
									unlink($xml_cdr_dir.'/'.$file);
							}

						//increment the value
							if ($import) {
								$x++;
							}

						//if limit exceeded exit the loop
							if ($limit == $x) {
								//echo "limit: $limit count: $x if\n";
								break;
							}
					}
				}
			}
			$this->save();
			closedir($dir_handle);
		}
		//$this->read_files();

		/**
		 * read the call detail records from the http post
		 */
		public function post() {
			if (isset($_POST["cdr"])) {
				//debug method
					if ($this->debug){
						print_r($_POST["cdr"]);
					}

				//authentication for xml cdr http post
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "true" && strlen($_SESSION["xml_cdr"]["username"]) == 0) {
							//get the contents of xml_cdr.conf.xml
								$conf_xml_string = file_get_contents($_SESSION['switch']['conf']['dir'].'/autoload_configs/xml_cdr.conf.xml');

							//parse the xml to get the call detail record info
								try {
									$conf_xml = simplexml_load_string($conf_xml_string);
								}
								catch(Exception $e) {
									echo $e->getMessage();
								}
								if (isset($conf_xml->settings->param)) foreach ($conf_xml->settings->param as $row) {
									if ($row->attributes()->name == "cred") {
										$auth_array = explode(":", $row->attributes()->value);
										//echo "username: ".$auth_array[0]."<br />\n";
										//echo "password: ".$auth_array[1]."<br />\n";
									}
									if ($row->attributes()->name == "url") {
										//check name is equal to url
									}
								}
						}
					}

				//if http enabled is set to false then deny access
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "false") {
							echo "access denied<br />\n";
							return;
						}
					}

				//check for the correct username and password
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "true") {
							if ($auth_array[0] == $_SERVER["PHP_AUTH_USER"] && $auth_array[1] == $_SERVER["PHP_AUTH_PW"]) {
								//echo "access granted<br />\n";
								$_SESSION["xml_cdr"]["username"] = $auth_array[0];
								$_SESSION["xml_cdr"]["password"] = $auth_array[1];
							}
							else {
								echo "access denied<br />\n";
								return;
							}
						}
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

				//log the xml cdr
					//xml_cdr_log("process cdr via post\n");

				//parse the xml and insert the data into the database
					$this->xml_array(0, $leg, $xml_string);
					$this->save();
			}
		}
		//$this->post();

	} //end scripts class
}
/*
//example use
	$cdr = new xml_cdr;
	$cdr->read_files();
*/
?>