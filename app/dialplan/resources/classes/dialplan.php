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
	Copyright (C) 2010-2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the dialplan class
	if (!class_exists('dialplan')) {
		class dialplan {
			//variables
			public $db;
			public $result;
			public $domain_uuid;
			public $dialplan_uuid;
			public $xml;
			public $json;
			public $display_type;
			public $default_context;
			public $bridges;
			public $variables;

			//dialplans
			public $dialplan_name;
			public $dialplan_continue;
			public $dialplan_order;
			public $dialplan_context;
			public $dialplan_enabled;
			public $dialplan_description;

			//dialplan_details
			public $dialplan_detail_tag;
			public $dialplan_detail_order;
			public $dialplan_detail_type;
			public $dialplan_detail_data;
			public $dialplan_detail_break;
			public $dialplan_detail_inline;
			public $dialplan_detail_group;

			//class constructor
			public function __construct() {
				//connect to the database if not connected
				if (!$this->db) {
					require_once "resources/classes/database.php";
					$database = new database;
					$database->connect();
					$this->db = $database->db;
				}
			}

			public function dialplan_add() {

				$sql = "insert into v_dialplans ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "app_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_name, ";
				$sql .= "dialplan_number, ";
				$sql .= "dialplan_continue, ";
				$sql .= "dialplan_order, ";
				$sql .= "dialplan_context, ";
				$sql .= "dialplan_enabled, ";
				$sql .= "dialplan_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				if (strlen($this->domain_uuid) > 0) {
					$sql .= "'".check_str($this->domain_uuid)."', ";
				}
				else {
					$sql .= "null, ";
				}
				$sql .= "'".check_str($this->app_uuid)."', ";
				$sql .= "'".check_str($this->dialplan_uuid)."', ";
				$sql .= "'".check_str($this->dialplan_name)."', ";
				$sql .= "'".check_str($this->dialplan_number)."', ";
				$sql .= "'".check_str($this->dialplan_continue)."', ";
				$sql .= "'".check_str($this->dialplan_order)."', ";
				$sql .= "'".check_str($this->dialplan_context)."', ";
				$sql .= "'".check_str($this->dialplan_enabled)."', ";
				$sql .= "'".check_str($this->dialplan_description)."' ";
				$sql .= ")";
				$this->db->exec(check_sql($sql));
				unset($sql);
			} //end function

			public function dialplan_update() {

				$sql = "update v_dialplans set ";
				$sql .= "dialplan_name = '".check_str($this->dialplan_name)."', ";
				if (strlen($this->dialplan_continue) > 0) {
					$sql .= "dialplan_continue = '".check_str($this->dialplan_continue)."', ";
				}
				$sql .= "dialplan_order = '".check_str($this->dialplan_order)."', ";
				$sql .= "dialplan_context = '".check_str($this->dialplan_context)."', ";
				$sql .= "dialplan_enabled = '".check_str($this->dialplan_enabled)."', ";
				$sql .= "dialplan_description = '".check_str($this->dialplan_description)."' ";
				$sql .= "where (domain_uuid = '".check_str($this->domain_uuid)."' or domain_uuid is null) ";
				$sql .= "and dialplan_uuid = '".check_str($this->dialplan_uuid)."' ";
				//echo "sql: ".$sql."<br />";
				$this->db->query($sql);
				unset($sql);
			}

			public function dialplan_detail_add() {

				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_order, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "dialplan_detail_break, ";
				$sql .= "dialplan_detail_inline, ";
				$sql .= "dialplan_detail_group ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "( ";
				$sql .= "'".$dialplan_detail_uuid."', ";
				if (strlen($this->domain_uuid) == 0) {
					$sql .= "null, ";
				}
				else {
					$sql .= "'".check_str($this->domain_uuid)."', ";
				}
				$sql .= "'".check_str($this->dialplan_uuid)."', ";
				$sql .= "'".check_str($this->dialplan_detail_tag)."', ";
				$sql .= "'".check_str($this->dialplan_detail_order)."', ";
				$sql .= "'".check_str($this->dialplan_detail_type)."', ";
				$sql .= "'".check_str($this->dialplan_detail_data)."', ";
				if (strlen($this->dialplan_detail_break) == 0) {
					$sql .= "null, ";
				}
				else {
					$sql .= "'".check_str($this->dialplan_detail_break)."', ";
				}
				if (strlen($this->dialplan_detail_inline) == 0) {
					$sql .= "null, ";
				}
				else {
					$sql .= "'".check_str($this->dialplan_detail_inline)."', ";
				}
				if (strlen($this->dialplan_detail_group) == 0) {
					$sql .= "null ";
				}
				else {
					$sql .= "'".check_str($this->dialplan_detail_group)."' ";
				}
				$sql .= ")";
				//echo $sql."\n\n";
				$this->db->exec(check_sql($sql));
				unset($sql);
			} //end function

			public function dialplan_detail_update() {

				$sql = "update v_dialplans set ";
				$sql .= "dialplan_detail_order = '".check_str($this->dialplan_detail_order)."', ";
				$sql .= "dialplan_detail_type = '".check_str($this->dialplan_detail_type)."', ";
				$sql .= "dialplan_detail_data = '".check_str($this->dialplan_detail_data)."', ";
				if (strlen($this->dialplan_detail_break) > 0) {
					$sql .= "dialplan_detail_break = '".check_str($this->dialplan_detail_break)."', ";
				}
				if (strlen($this->dialplan_detail_inline) > 0) {
					$sql .= "dialplan_detail_inline = '".check_str($this->dialplan_detail_inline)."', ";
				}
				if (strlen($this->dialplan_detail_group) > 0) {
					$sql .= "dialplan_detail_group = '".check_str($this->dialplan_detail_group)."', ";
				}
				$sql .= "dialplan_detail_tag = '".check_str($this->dialplan_detail_tag)."' ";
				$sql .= "where (domain_uuid = '".check_str($this->domain_uuid)."' or domain_uuid is null) ";
				$sql .= "and dialplan_uuid = '".check_str($this->dialplan_uuid)."' ";
				//echo "sql: ".$sql."<br />";
				$this->db->query($sql);
				unset($sql);
			} //end function

			public function restore_advanced_xml() {
				$switch_dialplan_dir = $this->switch_dialplan_dir;
				if (is_dir($switch_dialplan_dir)) {
					//copy resources/templates/conf to the freeswitch conf dir
						if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')){
							$src_dir = "/usr/share/examples/fusionpbx/resources/templates/conf";
						}
						else {
							$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
						}
					//get the contents of the dialplan/default.xml
						$file_default_path = $src_dir.'/dialplan/default.xml';
						$file_default_contents = file_get_contents($file_default_path);
					//prepare the file contents and the path
						//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
							$file_default_contents = str_replace("{v_domain}", $_SESSION['domain_name'], $file_default_contents);
						//set the file path
							$file_path = $switch_dialplan_dir.'/'.$_SESSION['domain_name'].'.xml';
					//write the default dialplan
						$fh = fopen($file_path,'w') or die('Unable to write to '.$file_path.'. Make sure the path exists and permissons are set correctly.');
						fwrite($fh, $file_default_contents);
						fclose($fh);
					//set the message
						$this->result['dialplan']['restore']['msg'] = "Default Restored";
				}
			}

			private function app_uuid_exists() {
				$sql = "select count(*) as num_rows from v_dialplans ";
				$sql .= "where (domain_uuid = '".$this->domain_uuid."' or domain_uuid is null) ";
				$sql .= "and app_uuid = '".$this->app_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] > 0) {
						return true;
					}
					else {
						return false;
					}
				}
				unset($prep_statement, $result);
			}

			public function dialplan_exists() {
				$sql = "select count(*) as num_rows from v_dialplans ";
				$sql .= "where (domain_uuid = '".$this->domain_uuid."' or domain_uuid is null)";
				$sql .= "and dialplan_uuid = '".$this->dialplan_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] > 0) {
						return true;
					}
					else {
						return false;
					}
				}
				unset($prep_statement, $result);
			}

			public function import() {
				if (strlen($this->xml) > 0) {
					//replace the variables
						$this->xml = str_replace("{v_context}", $this->default_context, $this->xml);
						$this->xml = str_replace("{v_pin_number}", generate_password(8, 1), $this->xml);
						$this->xml = str_replace("{v_switch_recordings_dir}", $_SESSION['switch']['recordings']['dir'], $this->xml);
					//convert the xml string to an xml object
						$xml = simplexml_load_string($this->xml);
					//convert to json
						$json = json_encode($xml);
					//convert to an array
						$dialplan = json_decode($json, true);
				}
				if (strlen($this->json) > 0) {
					//convert to an array
						$dialplan = json_decode($json, true);
				}

				//ensure the condition array is uniform
					if (is_array($dialplan)) {
						if (!is_array($dialplan['extension']['condition'][0])) {
							$tmp = $dialplan['extension']['condition'];
							unset($dialplan['extension']['condition']);
							$dialplan['extension']['condition'][0] = $tmp;
						}
					}
				//check if the dialplan app uuid exists
					$this->app_uuid = $dialplan['extension']['@attributes']['app_uuid'];
					if ($this->app_uuid_exists()) {
						//dialplan entry already exists do nothing
					}
					else {
						//start the transaction
							$this->db->beginTransaction();
						//get the attributes
							$this->dialplan_uuid = uuid();
							$this->dialplan_name = $dialplan['extension']['@attributes']['name'];
							$this->dialplan_number = $dialplan['extension']['@attributes']['number'];
							$this->dialplan_context = $dialplan['@attributes']['name'];
							if (strlen($dialplan['extension']['@attributes']['global']) > 0) {
								if ($dialplan['extension']['@attributes']['global'] == "true") {
									$this->domain_uuid = null;
								}
							}
							if ($this->display_type == "text") {
								echo "	".$this->dialplan_name.":		added\n";
							}
							if (strlen($dialplan['extension']['@attributes']['continue']) > 0) {
								$this->dialplan_continue = $dialplan['extension']['@attributes']['continue'];
							}
							if (strlen($dialplan['extension']['@attributes']['enabled']) > 0) {
								$this->dialplan_enabled = $dialplan['extension']['@attributes']['enabled'];
							}
							else {
								$this->dialplan_enabled = "true";
							}
							$this->dialplan_description = '';
							$this->dialplan_add();
						//loop through the condition array
							$x = 0;
							$group = 0;
							$order = 5;
							if (isset($dialplan['extension']['condition'])) foreach ($dialplan['extension']['condition'] as &$row) {
								unset($this->dialplan_detail_break);
								unset($this->dialplan_detail_inline);
								$this->dialplan_detail_tag = 'condition';
								$this->dialplan_detail_type = $row['@attributes']['field'];
								$this->dialplan_detail_data = $row['@attributes']['expression'];
								$this->dialplan_detail_group = $group;
								$this->dialplan_detail_order = $order;
								if (strlen($row['@attributes']['break']) > 0) {
									$this->dialplan_detail_break = $row['@attributes']['break'];
								}
								$this->dialplan_detail_add();
								if (is_array($row['action']) || is_array($row['anti-action'])) {
									$condition_self_closing_tag = false;
									if (!is_array($row['action'][0])) {
										if ($row['action']['@attributes']['application']) {
											$tmp = $row['action'];
											unset($row['action']);
											$row['action'][0] = $tmp;
										}
									}
									if (!is_array($row['anti-action'][0])) {
										if ($row['anti-action']['@attributes']['application']) {
											$tmp = $row['anti-action'];
											unset($row['anti-action']);
											$row['anti-action'][0] = $tmp;
										}
									}
									$order = $order + 5;
									unset($this->dialplan_detail_break);
									unset($this->dialplan_detail_inline);
									if (isset($row['action'])) foreach ($row['action'] as &$row2) {
										$this->dialplan_detail_tag = 'action';
										$this->dialplan_detail_type = $row2['@attributes']['application'];
										$this->dialplan_detail_data = $row2['@attributes']['data'];
										if (strlen($row2['@attributes']['inline']) > 0) {
											$this->dialplan_detail_inline = $row2['@attributes']['inline'];
										}
										$this->dialplan_detail_group = $group;
										$this->dialplan_detail_order = $order;
										$this->dialplan_detail_add();
										$order = $order + 5;
									}
									if (isset($row['anti-action'])) foreach ($row['anti-action'] as &$row2) {
										$this->dialplan_detail_tag = 'anti-action';
										$this->dialplan_detail_type = $row2['@attributes']['application'];
										$this->dialplan_detail_data = $row2['@attributes']['data'];
										$this->dialplan_detail_group = $group;
										$this->dialplan_detail_order = $order;
										$this->dialplan_detail_add();
										$order = $order + 5;
									}
								}
								else {
									$condition_self_closing_tag = true;
								}
								//if not a self closing tag then increment the group
								if (!$condition_self_closing_tag) {
									$group++;
								}
								$row['group'] = $group;
								$order = $order + 5;
								$x++;
							}
						//end the transaction
							$this->db->commit();
					}
			}

			public function outbound_routes($destination_number) {

				//normalize the destination number
					$destination_number = trim($destination_number);

				//check the session array if it doesn't exist then build the array
					if (!is_array($_SESSION[$_SESSION['domain_uuid']]['outbound_routes'])) {
						//get the outbound routes from the database
							$sql = "select * from v_dialplans as d, v_dialplan_details as s ";
							$sql .= "where ";
							$sql .= "( ";
							$sql .= "d.domain_uuid = '".$this->domain_uuid."' ";
							$sql .= "or d.domain_uuid is null ";
							$sql .= ") ";
							$sql .= "and d.app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
							$sql .= "and d.dialplan_enabled = 'true' ";
							$sql .= "and d.dialplan_uuid = s.dialplan_uuid ";
							$sql .= "order by ";
							$sql .= "d.dialplan_order asc, ";
							$sql .= "d.dialplan_name asc, ";
							$sql .= "d.dialplan_uuid asc, ";
							$sql .= "s.dialplan_detail_group asc, ";
							$sql .= "CASE s.dialplan_detail_tag ";
							$sql .= "WHEN 'condition' THEN 1 ";
							$sql .= "WHEN 'action' THEN 2 ";
							$sql .= "WHEN 'anti-action' THEN 3 ";
							$sql .= "ELSE 100 END, ";
							$sql .= "s.dialplan_detail_order asc ";
							$prep_statement = $this->db->prepare(check_sql($sql));
							$prep_statement->execute();
							$dialplans = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
							$x = 0; $y = 0;
							if (isset($dialplans)) foreach ($dialplans as &$row) {
								//if the previous dialplan uuid has not been set then set it
									if (!isset($previous_dialplan_uuid)) { $previous_dialplan_uuid = $row['dialplan_uuid']; }

								//increment dialplan ordinal number
									if ($previous_dialplan_uuid != $row['dialplan_uuid']) {
										$x++; $y = 0;
									}

								//build the array
									$array[$x]['dialplan_uuid'] = $row['dialplan_uuid'];
									$array[$x]['dialplan_context'] = $row['dialplan_context'];
									$array[$x]['dialplan_name'] = $row['dialplan_name'];
									$array[$x]['dialplan_continue'] = $row['dialplan_continue'];
									$array[$x]['dialplan_order'] = $row['dialplan_order'];
									$array[$x]['dialplan_enabled'] = $row['dialplan_enabled'];
									$array[$x]['dialplan_description'] = $row['dialplan_description'];
									if (strlen($row['dialplan_detail_uuid']) > 0) {
										$array[$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
										$array[$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = $row['dialplan_detail_uuid'];
										$array[$x]['dialplan_details'][$y]['dialplan_detail_tag'] = $row['dialplan_detail_tag'];
										$array[$x]['dialplan_details'][$y]['dialplan_detail_type'] = $row['dialplan_detail_type'];
										$array[$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row['dialplan_detail_data'];
										$y++;
									}

								//set the previous dialplan_uuid
									$previous_dialplan_uuid = $row['dialplan_uuid'];
							}
							unset ($prep_statement);
						//set the session array
							$_SESSION[$_SESSION['domain_uuid']]['outbound_routes'] = $array;
					}
				//find the matching outbound routes
					if (isset($_SESSION[$_SESSION['domain_uuid']]['outbound_routes'])) foreach ($_SESSION[$_SESSION['domain_uuid']]['outbound_routes'] as $row) {
						if (isset($row['dialplan_details'])) foreach ($row['dialplan_details'] as $field) {
							if ($field['dialplan_detail_tag'] == "condition") {
								if ($field['dialplan_detail_type'] == "destination_number") {
									$dialplan_detail_data = $field['dialplan_detail_data'];
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
									}
								}
							}
							if ($regex_match) {
								//get the variables
									if ($field[dialplan_detail_type] == "set" && $field[dialplan_detail_tag] == "action") {
										//only set variables with values not variables
										if (strpos($field[dialplan_detail_data], '$') === false) {
											$this->variables .= $field[dialplan_detail_data].",";
										}
									}
								//process the $x detail data variables
									if ($field['dialplan_detail_tag'] == "action" && $field['dialplan_detail_type'] == "bridge" && $dialplan_detail_data != "\${enum_auto_route}") {
										$dialplan_detail_data = $field['dialplan_detail_data'];
										$dialplan_detail_data = str_replace("\$1", $regex_match_1, $dialplan_detail_data);
										$dialplan_detail_data = str_replace("\$2", $regex_match_2, $dialplan_detail_data);
										$dialplan_detail_data = str_replace("\$3", $regex_match_3, $dialplan_detail_data);
										$this->bridges = $dialplan_detail_data;
									}
							}
						}
					}
			}
		}
	}

?>