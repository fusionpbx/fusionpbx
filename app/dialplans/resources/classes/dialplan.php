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
	Copyright (C) 2010-2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the dialplan class
	if (!class_exists('dialplan')) {
		class dialplan {
			//variables
			public $result;
			public $domain_uuid;
			public $dialplan_uuid;
			public $dialplan_detail_uuid;
			public $xml;
			public $json;
			public $display_type;
			public $default_context;
			public $bridges;
			public $variables;

			//dialplans
			public $dialplan_name;
			public $dialplan_number;
			public $dialplan_destination;
			public $dialplan_continue;
			public $dialplan_order;
			public $dialplan_context;
			public $dialplan_global;
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
			
			//xml
			public $uuid;
			public $context;
			public $source;
			public $destination;
			public $is_empty;
			public $array;

			//class constructor
			public function __construct() {
				//set the default value
				$this->dialplan_global = false;
			}

			public function dialplan_add() {
				//build insert array
					$array['dialplans'][0]['dialplan_uuid'] = $this->dialplan_uuid;
					$array['dialplans'][0]['domain_uuid'] = !$this->dialplan_global ? $this->domain_uuid : null;
					$array['dialplans'][0]['app_uuid'] = $this->app_uuid;
					$array['dialplans'][0]['dialplan_name'] = $this->dialplan_name;
					$array['dialplans'][0]['dialplan_number'] = $this->dialplan_number;
					$array['dialplans'][0]['dialplan_destination'] = $this->dialplan_destination;
					$array['dialplans'][0]['dialplan_continue'] = $this->dialplan_continue;
					$array['dialplans'][0]['dialplan_order'] = $this->dialplan_order;
					$array['dialplans'][0]['dialplan_context'] = $this->dialplan_context;
					$array['dialplans'][0]['dialplan_enabled'] = $this->dialplan_enabled;
					$array['dialplans'][0]['dialplan_description'] = $this->dialplan_description;
				//grant temporary permissions
					$p = new permissions;
					$p->add('dialplan_add', 'temp');
				//execute insert
					$database = new database;
					$database->app_name = 'dialplans';
					$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('dialplan_add', 'temp');
			}

			public function dialplan_update() {
				//build update array
					$array['dialplans'][0]['dialplan_uuid'] = $this->dialplan_uuid;
					$array['dialplans'][0]['dialplan_name'] = $this->dialplan_name;
					if (strlen($this->dialplan_continue) > 0) {
						$array['dialplans'][0]['dialplan_continue'] = $this->dialplan_continue;
					}
					$array['dialplans'][0]['dialplan_order'] = $this->dialplan_order;
					$array['dialplans'][0]['dialplan_context'] = $this->dialplan_context;
					$array['dialplans'][0]['dialplan_enabled'] = $this->dialplan_enabled;
					$array['dialplans'][0]['dialplan_description'] = $this->dialplan_description;
				//grant temporary permissions
					$p = new permissions;
					$p->add('dialplan_edit', 'temp');
				//execute update
					$database = new database;
					$database->app_name = 'dialplans';
					$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('dialplan_edit', 'temp');
			}

			public function dialplan_detail_add() {
				//build insert array
					$dialplan_detail_uuid = uuid();
					$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
					$array['dialplan_details'][0]['domain_uuid'] = !$this->dialplan_global ? $this->domain_uuid : null;
					$array['dialplan_details'][0]['dialplan_uuid'] = $this->dialplan_uuid;
					$array['dialplan_details'][0]['dialplan_detail_tag'] = $this->dialplan_detail_tag;
					$array['dialplan_details'][0]['dialplan_detail_order'] = $this->dialplan_detail_order;
					$array['dialplan_details'][0]['dialplan_detail_type'] = $this->dialplan_detail_type;
					$array['dialplan_details'][0]['dialplan_detail_data'] = $this->dialplan_detail_data;
					$array['dialplan_details'][0]['dialplan_detail_break'] = strlen($this->dialplan_detail_break) != 0 ? $this->dialplan_detail_break : null;
					$array['dialplan_details'][0]['dialplan_detail_inline'] = strlen($this->dialplan_detail_inline) != 0 ? $this->dialplan_detail_inline : null;
					$array['dialplan_details'][0]['dialplan_detail_group'] = strlen($this->dialplan_detail_group) != 0 ? $this->dialplan_detail_group : null;
				//grant temporary permissions
					$p = new permissions;
					$p->add('dialplan_detail_add', 'temp');
				//execute insert
					$database = new database;
					$database->app_name = 'dialplans';
					$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('dialplan_detail_add', 'temp');
			}

			public function dialplan_detail_update() {
				//build update array
					$array['dialplan_details'][0]['dialplan_detail_uuid'] = $this->dialplan_detail_uuid;
					$array['dialplan_details'][0]['dialplan_detail_tag'] = $this->dialplan_detail_tag;
					$array['dialplan_details'][0]['dialplan_detail_order'] = $this->dialplan_detail_order;
					$array['dialplan_details'][0]['dialplan_detail_type'] = $this->dialplan_detail_type;
					$array['dialplan_details'][0]['dialplan_detail_data'] = $this->dialplan_detail_data;
					if (strlen($this->dialplan_detail_break) != 0) {
						$array['dialplan_details'][0]['dialplan_detail_break'] = $this->dialplan_detail_break;
					}
					if (strlen($this->dialplan_detail_inline) != 0) {
						$array['dialplan_details'][0]['dialplan_detail_inline'] = $this->dialplan_detail_inline;
					}
					if (strlen($this->dialplan_detail_group) != 0) {
						$array['dialplan_details'][0]['dialplan_detail_group'] = $this->dialplan_detail_group;
					}
				//grant temporary permissions
					$p = new permissions;
					$p->add('dialplan_detail_edit', 'temp');
				//execute update
					$database = new database;
					$database->app_name = 'dialplans';
					$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('dialplan_detail_edit', 'temp');
			}

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
				$sql = "select count(*) from v_dialplans ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$sql .= "and app_uuid = :app_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['app_uuid'] = $this->app_uuid;
				$database = new database;
				return $database->select($sql, $parameters, 'column') != 0 ? true : false;
				unset($sql, $parameters);
			}

			public function dialplan_exists() {
				$sql = "select count(*) from v_dialplans ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null)";
				$sql .= "and dialplan_uuid = :dialplan_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['dialplan_uuid'] = $this->dialplan_uuid;
				$database = new database;
				return $database->select($sql, $parameters, 'column') != 0 ? true : false;
				unset($sql, $parameters);
			}

			public function import() {
				if (strlen($this->xml) > 0) {
					//replace the variables
						$length = (is_numeric($_SESSION["security"]["pin_length"]["var"])) ? $_SESSION["security"]["pin_length"]["var"] : 8;
						//$this->xml = str_replace("{v_context}", $this->default_context, $this->xml);
						$this->xml = str_replace("{v_pin_number}", generate_password($length, 1), $this->xml);
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

				//get the app_uuid
					$this->app_uuid = $dialplan['extension']['@attributes']['app_uuid'];

				//get the list of domains
					if (!isset($_SESSION['domains'])) {
						$sql = "select * from v_domains ";
						$database = new database;
						$_SESSION['domains'] = $database->select($sql, null, 'all');
						unset($sql);
					}

				//check if the dialplan app uuid exists
					foreach ($_SESSION['domains'] as $domain) {
						//get the domain_uuid
						$this->domain_uuid = $domain['domain_uuid'];
						//set the dialplan_context
						$this->dialplan_context = $dialplan['@attributes']['name'];
						if ($this->dialplan_context == "{v_context}") {
							$this->dialplan_context = $domain['domain_name'];
						}
						//check if the dialplan exists
						if (!$this->app_uuid_exists()) {
							//get the attributes
								$this->dialplan_uuid = uuid();
								$this->dialplan_name = $dialplan['extension']['@attributes']['name'];
								$this->dialplan_number = $dialplan['extension']['@attributes']['number'];
								if (strlen($dialplan['extension']['@attributes']['destination']) > 0) {
									$this->dialplan_destination = $dialplan['extension']['@attributes']['destination'];
								}
								$this->dialplan_global = false;
								if (strlen($dialplan['extension']['@attributes']['global']) > 0) {
									if ($dialplan['extension']['@attributes']['global'] == "true") {
										$this->dialplan_global = true;
									}
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
								$this->dialplan_description = $dialplan['extension']['@attributes']['description'];
								$this->dialplan_add();
							//loop through the condition array
								$x = 0;
								$group = 0;
								$order = 5;
								if (isset($dialplan['extension']['condition'])) {
									foreach ($dialplan['extension']['condition'] as &$row) {
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
											if (isset($row['action'])) {
												foreach ($row['action'] as &$row2) {
													$this->dialplan_detail_tag = 'action';
													$this->dialplan_detail_type = $row2['@attributes']['application'];
													$this->dialplan_detail_data = $row2['@attributes']['data'];
													if (strlen($row2['@attributes']['inline']) > 0) {
														$this->dialplan_detail_inline = $row2['@attributes']['inline'];
													}
													else {
														$this->dialplan_detail_inline = null;
													}
													$this->dialplan_detail_group = $group;
													$this->dialplan_detail_order = $order;
													$this->dialplan_detail_add();
													$order = $order + 5;
												}
											}
											if (isset($row['anti-action'])) {
												foreach ($row['anti-action'] as &$row2) {
													$this->dialplan_detail_tag = 'anti-action';
													$this->dialplan_detail_type = $row2['@attributes']['application'];
													$this->dialplan_detail_data = $row2['@attributes']['data'];
													if (strlen($row2['@attributes']['inline']) > 0) {
														$this->dialplan_detail_inline = $row2['@attributes']['inline'];
													}
													else {
														$this->dialplan_detail_inline = null;
													}
													$this->dialplan_detail_group = $group;
													$this->dialplan_detail_order = $order;
													$this->dialplan_detail_add();
													$order = $order + 5;
												}
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
								}
							//update the session array
								$_SESSION['upgrade']['app_defaults']['dialplans'][$domain['domain_name']][]['dialplan_name'] = $this->dialplan_name;
						}
					}
			}

			public function outbound_routes($destination_number) {

				//normalize the destination number
					$destination_number = trim($destination_number);

				//check the session array if it doesn't exist then build the array
					if (!is_array($_SESSION[$_SESSION['domain_uuid']]['outbound_routes'])) {
						//get the outbound routes from the database
							$sql = "select * ";
							$sql .= "from v_dialplans as d, ";
							$sql .= "v_dialplan_details as s ";
							$sql .= "where ";
							$sql .= "( ";
							$sql .= "d.domain_uuid = :domain_uuid ";
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
							$sql .= "case s.dialplan_detail_tag ";
							$sql .= "when 'condition' then 1 ";
							$sql .= "when 'action' then 2 ";
							$sql .= "when 'anti-action' then 3 ";
							$sql .= "else 100 end, ";
							$sql .= "s.dialplan_detail_order asc ";
							$parameters['domain_uuid'] = $this->domain_uuid;
							$database = new database;
							$dialplans = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);
							$x = 0; $y = 0;
							if (isset($dialplans) && @sizeof($dialplans) != 0) {
								foreach ($dialplans as &$row) {
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
							}
						//set the session array
							$_SESSION[$_SESSION['domain_uuid']]['outbound_routes'] = $array;
					}

				//find the matching outbound routes
					if (isset($_SESSION[$_SESSION['domain_uuid']]['outbound_routes'])) {
						foreach ($_SESSION[$_SESSION['domain_uuid']]['outbound_routes'] as $row) {
							if (isset($row['dialplan_details'])) {
								foreach ($row['dialplan_details'] as $field) {
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
									} //if
								} //foreach
							} //if
						} //foreach
					} //if
			} //function

			//reads dialplan details from the database to build the xml
			public function xml() {

				//set the xml array and then concatenate the array to a string
					/* $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n"; */
					//$xml .= "<document type=\"freeswitch/xml\">\n";
					//$xml .= "	<section name=\"dialplan\" description=\"\">\n";
					//$xml .= "		<context name=\"" . $this->context . "\">\n"; 

				//set defaults
					$previous_dialplan_uuid = "";
					$previous_dialplan_detail_group = "";
					$dialplan_tag_status = "closed";
					$condition_tag_status = "closed";

				//get the dialplans from the dialplan_xml field in the dialplans table
					if ($this->source == "dialplans") {
						//get the data using a join between the dialplans and dialplan details tables
							$sql = "select dialplan_uuid, dialplan_xml ";
							$sql .= "from v_dialplans ";
							if (is_uuid($this->uuid)) {
								$sql .= "where dialplan_uuid = :dialplan_uuid ";
								$parameters['dialplan_uuid'] = $this->uuid;
							}
							else {
								if (isset($this->context)) {
									if ($this->context == "public" || substr($this->context, 0, 7) == "public@" || substr($this->context, -7) == ".public") {
										$sql .= "where dialplan_context = :dialplan_context ";
									}
									else {
										$sql .= "where (dialplan_context = :dialplan_context or dialplan_context = '\${domain_name}') ";
									}
									$sql .= "and dialplan_enabled = 'true' ";
									$parameters['dialplan_context'] = $this->context;
								}
							}
							if ($this->is_empty == "dialplan_xml") {
								$sql .= "and p.dialplan_xml is null ";
							}
							$sql .= "order by ";
							$sql .= "dialplan_context asc, ";
							$sql .= "dialplan_order asc ";
							$database = new database;
							$results = $database->select($sql, $parameters, 'all');
							if (is_array($result) && @sizeof($result) != 0) {
								foreach ($results as $row) {
									$dialplans[$row["dialplan_uuid"]] = $row["dialplan_xml"];
								}
							}
							unset($sql, $parameters, $results, $row);
					}

				//get the dialplans from the dialplan details
					if ($this->source == "details") {

						//get the data using a join between the dialplans and dialplan details tables
							$sql = "select ";
							$sql .= "p.domain_uuid, p.dialplan_uuid, p.app_uuid, p.dialplan_context, p.dialplan_name, p.dialplan_number, ";
							$sql .= "p.dialplan_continue, p.dialplan_order, p.dialplan_enabled, p.dialplan_description, ";
							$sql .= "s.dialplan_detail_uuid, s.dialplan_detail_tag, s.dialplan_detail_type, s.dialplan_detail_data, ";
							$sql .= "s.dialplan_detail_break, s.dialplan_detail_inline, s.dialplan_detail_group, s.dialplan_detail_order ";
							$sql .= "from v_dialplans as p, v_dialplan_details as s ";
							$sql .= "where p.dialplan_uuid = s.dialplan_uuid ";
							if ($this->is_empty == "dialplan_xml") {
								$sql .= "and p.dialplan_xml is null ";
							}
							if (isset($this->context)) {
								if ($this->context == "public" || substr($this->context, 0, 7) == "public@" || substr($this->context, -7) == ".public") {
									$sql .= "and p.dialplan_context = :dialplan_context ";
								}
								else {
									$sql .= "and (p.dialplan_context = :dialplan_context or p.dialplan_context = '\${domain_name}') ";
								}
								$sql .= "and p.dialplan_enabled = 'true' ";
								$parameters['dialplan_context'] = $this->context;
							}
							if (is_uuid($this->uuid)) {
								$sql .= "and p.dialplan_uuid = :dialplan_uuid ";
								$sql .= "and s.dialplan_uuid = :dialplan_uuid ";
								$parameters['dialplan_uuid'] = $this->uuid;
							}
							$sql .= "order by ";
							$sql .= "p.dialplan_order asc, ";
							$sql .= "p.dialplan_name asc, ";
							$sql .= "p.dialplan_uuid asc, ";
							$sql .= "s.dialplan_detail_group asc, ";
							$sql .= "case s.dialplan_detail_tag ";
							$sql .= "when 'condition' then 1 ";
							$sql .= "when 'action' then 2 ";
							$sql .= "when 'anti-action' then 3 ";
							$sql .= "else 100 end, ";
							$sql .= "s.dialplan_detail_order asc ";
							$database = new database;
							$results = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

						//loop through the results to get the xml from the dialplan_xml field or from dialplan details table
							$x = 0;
							if (is_array($results) && @sizeof($results) != 0) {
								foreach ($results as $row) {
									//clear flag pass
										$pass = false;

									//get the dialplan
										$domain_uuid = $row["domain_uuid"];
										$dialplan_uuid = $row["dialplan_uuid"];
										//$app_uuid = $row["app_uuid"];
										$this->context = $row["dialplan_context"];
										$dialplan_name = $row["dialplan_name"];
										//$dialplan_number = $row["dialplan_number"];
										$dialplan_continue = $row["dialplan_continue"];
										//$dialplan_order = $row["dialplan_order"];
										//$dialplan_enabled = $row["dialplan_enabled"];
										//$dialplan_description = $row["dialplan_description"];

									//$get the dialplan details
										//$dialplan_detail_uuid = $row["dialplan_detail_uuid"];
										$dialplan_detail_tag = $row["dialplan_detail_tag"];
										$dialplan_detail_type = $row["dialplan_detail_type"];
										$dialplan_detail_data = $row["dialplan_detail_data"];
										$dialplan_detail_break = $row["dialplan_detail_break"];
										$dialplan_detail_inline = $row["dialplan_detail_inline"];
										$dialplan_detail_group = $row["dialplan_detail_group"];
										//$dialplan_detail_order = $row["dialplan_detail_order;

									//remove $$ and replace with $
										$dialplan_detail_data = str_replace("$$", "$", $dialplan_detail_data);

									//get the dialplan detail inline
										$detail_inline = "";
										if ($dialplan_detail_inline) {
											if (strlen($dialplan_detail_inline) > 0) {
												$detail_inline = " inline=\"" . $dialplan_detail_inline . "\"";
											}
										}

									//close the tags
										if ($dialplan_tag_status != "closed") {
											if (($previous_dialplan_uuid != $dialplan_uuid) || ($previous_dialplan_detail_group != $dialplan_detail_group)) {
												if ($condition_tag_status != "closed") {
													if ($condition_attribute && (strlen($condition_attribute) > 0)) {
														$xml .= "	<condition " . $condition_attribute . $condition_break . "/>\n";
														$condition_attribute = "";
														$condition_tag_status = "closed";
													}
													else if ($condition && (strlen($condition) > 0)) {
														$xml .= " ".$condition . "/>";
														$condition = "";
														$condition_tag_status = "closed";
													}
													else if ($condition_tag_status != "closed") {
														$xml .= "	</condition>\n";
														$condition_tag_status = "closed";
													}
													$condition_tag_status = "closed";
												}
											}
											if ($previous_dialplan_uuid != $dialplan_uuid) {
												$xml .= "</extension>\n";

												//add to the dialplanss
												$dialplans[$previous_dialplan_uuid] = $xml;
												$xml = '';

												$dialplan_tag_status = "closed";
											}
										}

									//open the tags
										if ($dialplan_tag_status == "closed") {
											$xml = '';
											$xml .= "<extension name=\"" . $dialplan_name . "\" continue=\"" . $dialplan_continue . "\" uuid=\"" . $dialplan_uuid . "\">\n";
											$dialplan_tag_status = "open";
											$first_action = true;
											$condition = "";
											$condition_attribute = "";
										}
										if ($dialplan_detail_tag == "condition") {
											//determine the type of condition
												if ($dialplan_detail_type == "hour") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "minute") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "minute-of-day") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "mday") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "mweek") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "mon") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "time-of-day") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "yday") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "year") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "wday") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "week") {
													$condition_type = 'time';
												}
												else if ($dialplan_detail_type == "date-time") {
													$condition_type = 'time';
												}
												else {
													$condition_type = 'default';
												}

											// finalize any previous pending condition statements
												if ($condition_tag_status == "open") {
													if (strlen($condition) > 0) {
														$xml .= $condition . "/>\n";
														$condition = '';
														$condition_tag_status = "closed";
													}
													else if (strlen($condition_attribute) > 0 && $condition_tag_status == "open") {
														// previous condition(s) must have been of type time
														// do not finalize if new condition is also of type time
														if ($condition_type != 'time') {
															// note: condition_break here is value from the previous loop
															$xml .= "	<condition " . $condition_attribute . $condition_break . "/>\n";
															$condition_attribute = '';
															$condition_tag_status = "closed";
														}
														//else {
														//	$xml .= "	</condition>\n";
														//	$condition_tag_status = "closed";
														//}
													}
												}

											//get the condition break attribute
												$condition_break = "";
												if ($dialplan_detail_break) {
													if (strlen($dialplan_detail_break) > 0) {
														$condition_break = " break=\"" . $dialplan_detail_break . "\"";
													}
												}

											//condition tag but leave off the ending
												if ($condition_type == "default") {
													$condition = "	<condition field=\"" . $dialplan_detail_type . "\" expression=\"" . $dialplan_detail_data . "\"" . $condition_break;
												}
												else if ($condition_type == "time") {
													if ($condition_attribute) {
														$condition_attribute = $condition_attribute . $dialplan_detail_type . "=\"" . $dialplan_detail_data . "\" ";
													}
													else {
														$condition_attribute = $dialplan_detail_type . "=\"" . $dialplan_detail_data . "\" ";
													}
													$condition = ""; //prevents a duplicate time condition
												}
												else {
													$condition = "	<condition field=\"" . $dialplan_detail_type . "\" expression=\"" . $dialplan_detail_data . "\"" .  $condition_break;
												}
												$condition_tag_status = "open";
										}

										if ($dialplan_detail_tag == "action" || $dialplan_detail_tag == "anti-action") {
											if ($condition_tag_status == "open") {
												if ($condition_attribute && (strlen($condition_attribute) > 0)) {
													$xml .= "	<condition " . $condition_attribute . $condition_break . ">\n";
													$condition_attribute = "";
												}
												else if ($condition && (strlen($condition) > 0)) {
													$xml .= $condition . ">\n";
													$condition = "";
												}
											}
										}

										if ($this->context == "public" || substr($this->context, 0, 7) == "public@" || substr($this->context, -7) == ".public") {
											if ($dialplan_detail_tag == "action") {
												if ($first_action) {
													//get the domains
														if (!isset($domains)) {
															$sql = "select * from v_domains ";
															$database = new database;
															$result = $database->select($sql, null, 'all');
															if (is_array($result) && @sizeof($result) != 0) {
																foreach($result as $row) {
																	$domains[$row['domain_uuid']] = $row['domain_name'];
																}
															}
															unset($sql, $result, $row);
														}
													//add the call direction and domain name and uuid
														$xml .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
														if ($domain_uuid != null and $domain_uuid != '') {
															$domain_name = $domains[$domain_uuid];
															$xml .= "		<action application=\"set\" data=\"domain_uuid=" . $domain_uuid . "\" inline=\"true\"/>\n";
														}
														if ($domain_name != null and $domain_name != '') {
															$xml .= "		<action application=\"set\" data=\"domain_name=" . $domain_name . "\" inline=\"true\"/>\n";
														}
														$first_action = false;
												}
											}
										}
										if ($dialplan_detail_tag == "action") {
											$xml .= "		<action application=\"" . $dialplan_detail_type . "\" data=\"" . $dialplan_detail_data . "\"" . $detail_inline . "/>\n";
										}
										if ($dialplan_detail_tag == "anti-action") {
											$xml .= "		<anti-action application=\"" . $dialplan_detail_type . "\" data=\"" . $dialplan_detail_data . "\"" . $detail_inline . "/>\n";
										}

									//save the previous values
										$previous_dialplan_uuid = $dialplan_uuid;
										$previous_dialplan_detail_group = $dialplan_detail_group;

									//increment the x
										$x++;

									//set flag pass
										$pass = true;
								}
							}
							unset($row);

						// prevent partial dialplan (pass=nil may be error in sql or empty resultset)
							if ($pass == false) {
								if (count($results)) {
									echo 'error while build context: ' . $this->context;
								}
							}

						//close the extension tag if it was left open
							if ($dialplan_tag_status == "open") {
								if ($condition_tag_status == "open") {
									if ($condition_attribute and (strlen($condition_attribute) > 0)) {
										$xml .= "	<condition " . $condition_attribute . $condition_break . "/>\n";
									}
									else if ($condition && (strlen($condition) > 0)) {
										$xml .= $condition . "/>\n";
									}
									else {
										$xml .= "	</condition>\n";
									}
								}
								$xml .= "</extension>\n";

								//add to the dialplans array
								$dialplans[$dialplan_uuid] = $xml;
							}

						//set the xml array and then concatenate the array to a string
							//$xml .= "		</context>\n";
							///$xml .= "	</section>\n";
							//$xml .= "</document>\n";

					} //end if source = details

				//return the array
					if ($this->destination == "array") {
						return $dialplans;
					}

				//save the dialplan xml
					if ($this->destination == "database") {
						if (is_array($dialplans)) {
							$x = 0;
							foreach ($dialplans as $key => $value) {
								//build update array
									$array['dialplans'][$x]['dialplan_uuid'] = $key;
									$array['dialplans'][$x]['dialplan_xml'] = $value;
								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_edit', 'temp');
								//execute update
									$database = new database;
									$database->app_name = 'dialplans';
									$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
									$database->save($array);
									unset($array);
								//revoke temporary permissions
									$p->delete('dialplan_edit', 'temp');
							}
						}
					}

			}

			public function defaults() {

				//get the array of xml files and then process thm
					$xml_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/resources/switch/conf/dialplan/*.xml");
					foreach ($xml_list as &$xml_file) {
						//get and parse the xml
							$xml_string = file_get_contents($xml_file);
						//get the order number prefix from the file name
							$name_array = explode('_', basename($xml_file));
							if (is_numeric($name_array[0])) {
								$dialplan_order = $name_array[0];
							}
							else {
								$dialplan_order = 0;
							}
							$dialplan->dialplan_order = $dialplan_order;

							$this->xml = $xml_string;
							$this->import();
					}

				//update the dialplan order
					$sql[] = "update v_dialplans set dialplan_order = '870' where dialplan_order = '980' and dialplan_name = 'cidlookup' ";
					$sql[] = "update v_dialplans set dialplan_order = '880' where dialplan_order = '990' and dialplan_name = 'call_screen' ";
					$sql[] = "update v_dialplans set dialplan_order = '890' where dialplan_order = '999' and dialplan_name = 'local_extension' ";
					$database = new database;
					foreach ($sql as $query) {
						$database->execute($query);
					}
					unset($sql, $query);

				//add xml for each dialplan where the dialplan xml is empty
					$this->source = "details";
					$this->destination = "database";
					$this->is_empty = "dialplan_xml";
					$array = $this->xml();
					//print_r($array);
					unset($this->source, $this->destination, $this->is_empty, $array);

			}

		}
	}

?>
