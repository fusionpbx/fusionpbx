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
	Copyright (C) 2010-2023
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the dialplan class
	if (!class_exists('dialplan')) {
		class dialplan {

			//variables
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
			public $dialplan_details;
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

			/**
			* declare public/private properties
			*/
			private $app_name;
			public $app_uuid;
			private $permission_prefix;
			public $list_page;
			private $table;
			private $uuid_prefix;
			private $toggle_field;
			private $toggle_values;

			/**
			* Set in the constructor. Must be a database object and cannot be null.
			* @var database Database Object
			*/
			private $database;

			//class constructor
			public function __construct() {
				//set the default value
				$this->dialplan_global = false;

				//assign property defaults
				$this->app_name = 'dialplans';
				$this->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db'; //dialplans
				$this->permission_prefix = 'dialplan_';
				$this->list_page = 'dialplans.php';
				$this->table = 'dialplans';
				$this->uuid_prefix = 'dialplan_';
				$this->toggle_field = 'dialplan_enabled';
				$this->toggle_values = ['true','false'];

				//open a database connection
				if (empty($setting_array['database'])) {
					$this->database = database::new();
				} else {
					$this->database = $setting_array['database'];
				}
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
					$p = permissions::new();
					$p->add('dialplan_add', 'temp');

				//execute insert
					$this->database->app_name = 'dialplans';
					$this->database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$this->database->save($array);
					unset($array);

				//clear the destinations session array
					if (isset($_SESSION['destinations']['array'])) {
						unset($_SESSION['destinations']['array']);
					}

				//revoke temporary permissions
					$p->delete('dialplan_add', 'temp');
			}

			public function dialplan_update() {
				//build update array
					$array['dialplans'][0]['dialplan_uuid'] = $this->dialplan_uuid;
					$array['dialplans'][0]['dialplan_name'] = $this->dialplan_name;
					if (!empty($this->dialplan_continue)) {
						$array['dialplans'][0]['dialplan_continue'] = $this->dialplan_continue;
					}

					$array['dialplans'][0]['dialplan_order'] = $this->dialplan_order;
					$array['dialplans'][0]['dialplan_context'] = $this->dialplan_context;
					$array['dialplans'][0]['dialplan_enabled'] = $this->dialplan_enabled;
					$array['dialplans'][0]['dialplan_description'] = $this->dialplan_description;

				//grant temporary permissions
					$p = permissions::new();
					$p->add('dialplan_edit', 'temp');

				//execute update
					$this->database->app_name = 'dialplans';
					$this->database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
					$this->database->save($array);
					unset($array);

				//revoke temporary permissions
					$p->delete('dialplan_edit', 'temp');
			}

			private function app_uuid_exists() {
				$sql = "select count(*) from v_dialplans ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$sql .= "and app_uuid = :app_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['app_uuid'] = $this->app_uuid;
				return $this->database->select($sql, $parameters ?? null, 'column') != 0 ? true : false;
				unset($sql, $parameters);
			}

			public function dialplan_exists() {
				$sql = "select count(*) from v_dialplans ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null)";
				$sql .= "and dialplan_uuid = :dialplan_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['dialplan_uuid'] = $this->dialplan_uuid;
				return $this->database->select($sql, $parameters ?? null, 'column') != 0 ? true : false;
				unset($sql, $parameters);
			}

			public function import($domains) {
				//set the row id
					$x = 0;

				//get the array of xml files
					$xml_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/resources/switch/conf/dialplan/*.xml");

				//add a band-aid for CLI editors with faulty syntax highlighting
					/* **/

				//build the dialplan xml array
					/*
					foreach ($xml_list as $xml_file) {
						$xml_string = file_get_contents($xml_file);

						//prepare the xml
						if (!empty($xml_string)) {
							//replace the variables
								$length = (is_numeric($_SESSION["security"]["pin_length"]["var"])) ? $_SESSION["security"]["pin_length"]["var"] : 8;
								$xml_string = str_replace("{v_context}", $domain['domain_name'], $xml_string);
								$xml_string = str_replace("{v_pin_number}", generate_password($length, 1), $xml_string);
							//convert the xml string to an xml object
								$xml = simplexml_load_string($xml_string);
							//convert to json
								$json = json_encode($xml);
							//convert to an array
								$dialplan = json_decode($json, true);
						}
						if (!empty($this->json)) {
							//convert to an array
								$dialplan = json_decode($json, true);
						}
						$_SESSION['dialplans']['default'][] = $dialplan;
					}
					*/

				//loop through each domain
					if (!empty($domains) && is_array($domains) && @sizeof($domains) != 0) {
						foreach ($domains as $domain) {
							//debug info
								//echo "domain name ".$domain['domain_name']."\n";

							//determine if the dialplan already exists
								$sql = "select app_uuid from v_dialplans ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and app_uuid is not null ";
								$parameters['domain_uuid'] = $domain['domain_uuid'];
								$app_uuids = $this->database->select($sql, $parameters, 'all');
								unset($parameters);

							//process the dialplan xml files
								//foreach ($_SESSION['dialplans']['default'] as $dialplan) {
								foreach ($xml_list as $xml_file) {
									//get the xml string
										$xml_string = file_get_contents($xml_file);

									//prepare the xml
										if (!empty($xml_string)) {
											//replace the variables
												$length = (!empty($_SESSION["security"]["pin_length"]["var"])) ? $_SESSION["security"]["pin_length"]["var"] : 8;
												$xml_string = str_replace("{v_context}", $domain['domain_name'], $xml_string);
												$xml_string = str_replace("{v_pin_number}", generate_password($length, 1), $xml_string);

											//convert the xml string to an xml object
												$xml = simplexml_load_string($xml_string);

											//convert to json
												$json = json_encode($xml);

											//convert to an array
												$dialplan = json_decode($json, true);

										}
										if (!empty($this->json)) {
											//convert to an array
												$dialplan = json_decode($json, true);
										}

									//ensure the condition array is uniform
										if (!empty($dialplan)) {
											if (empty($dialplan['condition'][0])) {
												$tmp = $dialplan['condition'];
												unset($dialplan['condition']);
												$dialplan['condition'][0] = $tmp;
											}
										}

									//determine if the dialplan already exists
										$app_uuid_exists = false;
										foreach($app_uuids as $row) {
											if ($dialplan['@attributes']['app_uuid'] == $row['app_uuid']) {
												$app_uuid_exists = true;
											}
										}

									//check if the dialplan exists
										if (!$app_uuid_exists) {

											//dialplan global
												if (isset($dialplan['@attributes']['global']) && $dialplan['@attributes']['global'] == "true") {
													$dialplan_global = true;
												}
												else {
													$dialplan_global = false;
												}

											//get the dialplan context
												$dialplan_context = $dialplan['@attributes']['context'];
												$dialplan_context = str_replace("\${domain_name}", $domain['domain_name'], $dialplan_context);

											//set the domain_uuid
												if ($dialplan_global) {
													$domain_uuid = null;
												}
												else {
													$domain_uuid = $domain['domain_uuid'];
												}

											//get the attributes
												$dialplan_uuid = uuid();

												$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
												$array['dialplans'][$x]['domain_uuid'] = $domain_uuid;
												$array['dialplans'][$x]['app_uuid'] = $dialplan['@attributes']['app_uuid'];
												$array['dialplans'][$x]['dialplan_name'] = $dialplan['@attributes']['name'];
												$array['dialplans'][$x]['dialplan_number'] = $dialplan['@attributes']['number'] ?? null;
												$array['dialplans'][$x]['dialplan_context'] = $dialplan_context;
												if (!empty($dialplan['@attributes']['destination'])) {
													$array['dialplans'][$x]['dialplan_destination'] = $dialplan['@attributes']['destination'];
												}
												if (!empty($dialplan['@attributes']['continue'])) {
													$array['dialplans'][$x]['dialplan_continue'] = $dialplan['@attributes']['continue'];
												}
												$array['dialplans'][$x]['dialplan_order'] = $dialplan['@attributes']['order'];
												if (!empty($dialplan['@attributes']['enabled'])) {
													$array['dialplans'][$x]['dialplan_enabled'] = $dialplan['@attributes']['enabled'];
												}
												else {
													$array['dialplans'][$x]['dialplan_enabled'] = "true";
												}
												$array['dialplans'][$x]['dialplan_description'] = $dialplan['@attributes']['description'] ?? null;

											//loop through the condition array
												$y = 0;
												$group = 0;
												$order = 5;
												if (isset($dialplan['condition'])) {
													foreach ($dialplan['condition'] as $row) {

														$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
														$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
														$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
														$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $order;
														$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $row['@attributes']['field'] ?? null;
														$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row['@attributes']['expression'] ?? null;
														if (!empty($row['@attributes']['break'])) {
															$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_break'] = $row['@attributes']['break'];
														}
														$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = $group;
														if (isset($row['@attributes']['enabled'])) {
															$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = $row['@attributes']['enabled'];
														}
														else {
															$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
														}
														$y++;

														if (!empty($row['action']) || !empty($row['anti-action'])) {
															$condition_self_closing_tag = false;
															if (empty($row['action'][0])) {
																if ($row['action']['@attributes']['application']) {
																	$tmp = $row['action'];
																	unset($row['action']);
																	$row['action'][0] = $tmp;
																}
															}
															if (empty($row['anti-action'][0])) {
																if (!empty($row['anti-action']['@attributes']['application'])) {
																	$tmp = $row['anti-action'];
																	unset($row['anti-action']);
																	$row['anti-action'][0] = $tmp;
																}
															}
															$order = $order + 5;
															if (isset($row['action'])) {
																foreach ($row['action'] as $row2) {
																	$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $order;
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $row2['@attributes']['application'];
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row2['@attributes']['data'] ?? null;
																	if (!empty($row2['@attributes']['inline'])) {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = $row2['@attributes']['inline'];
																	}
																	else {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = null;
																	}
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = $group;
																	if (isset($row2['@attributes']['enabled'])) {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = $row2['@attributes']['enabled'];
																	}
																	else {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
																	}
																	$y++;

																	//increase the order number
																	$order = $order + 5;
																}
															}
															if (isset($row['anti-action'])) {
																foreach ($row['anti-action'] as $row2) {
																	$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'anti-action';
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $order;
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = $row2['@attributes']['application'];
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row2['@attributes']['data'];
																	if (!empty($row2['@attributes']['inline'])) {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = $row2['@attributes']['inline'];
																	}
																	else {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = null;
																	}
																	$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = $group;
																	if (isset($row2['@attributes']['enabled'])) {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = $row2['@attributes']['enabled'];
																	}
																	else {
																		$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
																	}
																	$y++;

																	//increase the order number
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

														//increment the values
														$order = $order + 5;

														//increase the row number
														$x++;
													}
												}

											//update the session array
												$_SESSION['upgrade']['app_defaults']['dialplans'][$domain['domain_name']][]['dialplan_name'] = $dialplan_name ?? null;

										} //app_uuid exists
								} //end foreach $xml_list

							//grant temporary permissions
								$p = permissions::new();
								$p->add('dialplan_add', 'temp');
								$p->add('dialplan_edit', 'temp');
								$p->add('dialplan_detail_add', 'temp');
								$p->add('dialplan_detail_edit', 'temp');

							//save the data
								if (!empty($array)) {
									$this->database->app_name = 'dialplans';
									$this->database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
									$this->database->save($array);
									unset($array);
								}

							//revoke temporary permissions
								$p->delete('dialplan_add', 'temp');
								$p->delete('dialplan_edit', 'temp');
								$p->delete('dialplan_detail_add', 'temp');
								$p->delete('dialplan_detail_edit', 'temp');

							//add dialplan xml when the dialplan_xml is null
								$this->source = 'details';
								$this->destination = 'database';
								$this->context = $domain['domain_name'];
								$this->is_empty = 'dialplan_xml';
								$this->xml();

						} //foreach domains
					}
			}

			public function outbound_routes($destination_number) {

				//normalize the destination number
					$destination_number = trim($destination_number);

				//check the session array if it doesn't exist then build the array
					if (empty($_SESSION[$_SESSION['domain_uuid']]['outbound_routes'])) {
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
							$dialplans = $this->database->select($sql, $parameters ?? null, 'all');
							unset($sql, $parameters);
							$x = 0; $y = 0;
							if (!empty($dialplans)) {
								foreach ($dialplans as $row) {
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
										if (!empty($row['dialplan_detail_uuid'])) {
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

			//combines array dialplans and dialplan details arrays to match results from the database
			public function prepare_details($database_array) {
				$array = [];
				$id = 0;
				foreach($database_array['dialplans'] as $row) {
					if (!empty($row['dialplan_details'])) {
						foreach($row['dialplan_details'] as $detail) {
							if ($detail['dialplan_detail_enabled'] == 'true') {
								$array[$id]['domain_uuid'] = $row['domain_uuid'];
								$array[$id]['dialplan_uuid'] = $row['dialplan_uuid'];
								$array[$id]['app_uuid'] = $row['app_uuid'];
								$array[$id]['dialplan_context'] = $row['dialplan_context'];
								$array[$id]['dialplan_name'] = $row['dialplan_name'];
								$array[$id]['dialplan_number'] = $row['dialplan_number'];
								$array[$id]['dialplan_continue'] = $row['dialplan_continue'];
								$array[$id]['dialplan_order'] = $row['dialplan_order'];
								$array[$id]['dialplan_enabled'] = $row['dialplan_enabled'];
								$array[$id]['dialplan_description'] = $row['dialplan_description'];
								$array[$id]['dialplan_detail_uuid'] = $detail['dialplan_detail_uuid'];
								$array[$id]['dialplan_detail_tag'] = $detail['dialplan_detail_tag'];
								$array[$id]['dialplan_detail_type'] = $detail['dialplan_detail_type'];
								$array[$id]['dialplan_detail_data'] = $detail['dialplan_detail_data'];
								$array[$id]['dialplan_detail_break'] = $detail['dialplan_detail_break'];
								$array[$id]['dialplan_detail_inline'] = $detail['dialplan_detail_inline'];
								$array[$id]['dialplan_detail_group'] = $detail['dialplan_detail_group'];
								$array[$id]['dialplan_detail_order'] = $detail['dialplan_detail_order'];
								$array[$id]['dialplan_detail_enabled'] = $detail['dialplan_detail_enabled'];
								$id++;
							}
						}
					}
				}
				//sort the dataset by group and then by order before returning the values
				$dialplan_detail_group = array_column($array, 'dialplan_detail_group');
				$dialplan_detail_order = array_column($array, 'dialplan_detail_order');
				array_multisort($dialplan_detail_group, SORT_ASC, $dialplan_detail_order, SORT_ASC, $array);
				$this->dialplan_details = $array;
			}

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
								if (!empty($this->context)) {
									if ($this->context == "public" || substr($this->context, 0, 7) == "public@" || substr($this->context, -7) == ".public") {
										$sql .= "where dialplan_context = :dialplan_context ";
									}
									else {
										$sql .= "where (dialplan_context = :dialplan_context or dialplan_context = '\${domain_name}' or dialplan_context = 'global') ";
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
							$results = $this->database->select($sql, $parameters ?? null, 'all');
							if (!empty($results)) {
								foreach ($results as $row) {
									$dialplans[$row["dialplan_uuid"]] = $row["dialplan_xml"];
								}
							}
							unset($sql, $parameters, $results, $row);

					}

				//get the dialplans from the dialplan details
					if ($this->source == "details") {

						//get the domains
							if (!isset($domains)) {
								$sql = "select * from v_domains ";
								$result = $this->database->select($sql, null, 'all');
								if (!empty($result)) {
									foreach($result as $row) {
										$domains[$row['domain_uuid']] = $row['domain_name'];
									}
								}
								unset($sql, $result, $row);
							}

						//get the data using a join between the dialplans and dialplan details tables
							if (empty($this->dialplan_details)) {
								$sql = "select \n";
								$sql .= "p.domain_uuid, p.dialplan_uuid, p.app_uuid, p.dialplan_context, p.dialplan_name, p.dialplan_number, \n";
								$sql .= "p.dialplan_continue, p.dialplan_order, p.dialplan_enabled, p.dialplan_description, \n";
								$sql .= "s.dialplan_detail_uuid, s.dialplan_detail_tag, s.dialplan_detail_type, s.dialplan_detail_data, \n";
								$sql .= "s.dialplan_detail_break, s.dialplan_detail_inline, s.dialplan_detail_group, s.dialplan_detail_order, s.dialplan_detail_enabled \n";
								$sql .= "from v_dialplans as p, v_dialplan_details as s \n";
								$sql .= "where p.dialplan_uuid = s.dialplan_uuid \n";
								if ($this->is_empty == "dialplan_xml") {
									$sql .= "and p.dialplan_xml is null \n";
								}
								if (isset($this->context)) {
									if ($this->context == "public" || substr($this->context, 0, 7) == "public@" || substr($this->context, -7) == ".public") {
										$sql .= "and p.dialplan_context = :dialplan_context \n";
									}
									else {
										$sql .= "and (p.dialplan_context = :dialplan_context or p.dialplan_context = '\${domain_name}' or dialplan_context = 'global') \n";
									}
									$parameters['dialplan_context'] = $this->context;
								}
								if (is_uuid($this->uuid)) {
									$sql .= "and p.dialplan_uuid = :dialplan_uuid \n";
									$parameters['dialplan_uuid'] = $this->uuid;
								}
								$sql .= "and (s.dialplan_detail_enabled = 'true' or s.dialplan_detail_enabled is null) \n";
								$sql .= "order by \n";
								$sql .= "p.dialplan_order asc, \n";
								$sql .= "p.dialplan_name asc, \n";
								$sql .= "p.dialplan_uuid asc, \n";
								$sql .= "s.dialplan_detail_group asc, \n";
								$sql .= "case s.dialplan_detail_tag \n";
								$sql .= "when 'condition' then 0 \n";
								$sql .= "when 'regex' then 1 \n";
								$sql .= "when 'action' then 2 \n";
								$sql .= "when 'anti-action' then 3 \n";
								$sql .= "else 100 end, \n";
								$sql .= "s.dialplan_detail_order asc \n";
								$this->dialplan_details = $this->database->select($sql, $parameters ?? null, 'all');
								unset($sql, $parameters);

								//return if no records were found to update
								if (empty($this->dialplan_details)) {
									return;
								}
							}

						//define the values before they are used
							$previous_dialplan_uuid = null;
							$previous_dialplan_detail_group = null;
							$dialplan_tag_status = null;

						//loop through the results to get the xml from the dialplan_xml field or from dialplan details table
							$x = 0;
							if (!empty($this->dialplan_details)) {
								foreach ($this->dialplan_details as $row) {

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
										$dialplan_detail_data = str_replace("$$", "$", $dialplan_detail_data ?? '');

									//get the dialplan detail inline
										$detail_inline = "";
										if ($dialplan_detail_inline) {
											if (!empty($dialplan_detail_inline)) {
												$detail_inline = " inline=\"" . $dialplan_detail_inline . "\"";
											}
										}

									//close the tags
										if ($dialplan_tag_status != "closed") {
											if (($previous_dialplan_uuid != $dialplan_uuid) || ($previous_dialplan_detail_group != $dialplan_detail_group)) {

												if ($condition_tag_status != "closed") {

													if ($condition_attribute && (!empty($condition_attribute))) {
														$xml .= "	<condition " . $condition_attribute . $condition_break . "/>\n";
														$condition_attribute = "";
														$condition_tag_status = "closed";
													}
													else if (!empty($condition) && substr($condition, -1) == ">") {
														$xml .= " ".$condition;
														$condition = "";
														$condition_tag_status = "closed";
													}
													else if (!empty($condition)) {
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
												//close the extension tag
												if (!empty($previous_dialplan_uuid)) {
													$xml .= "</extension>\n";
												}

												//add to the dialplans
												$dialplans[$previous_dialplan_uuid] = $xml ?? '';
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
										if (isset($dialplan_detail_tag) && $dialplan_detail_tag == "condition" || $dialplan_detail_tag == "regex") {
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

											//finalize any previous pending condition statements
												if ($condition_tag_status == "open") {
													if (!empty($condition) && substr($condition, -1) == ">") {
														$xml .= $condition . "\n";
														$condition = '';
														$condition_tag_status = "closed";
													}
													else if (!empty($condition)) {
														$xml .= $condition . "/>\n";
														$condition = '';
														$condition_tag_status = "closed";
													}
													else if (!empty($condition_attribute) && $condition_tag_status == "open") {
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
													if (!empty($dialplan_detail_break)) {
														$condition_break = " break=\"" . $dialplan_detail_break . "\"";
													}
												}

											//condition tag but leave off the ending
												if ($condition_type == "default") {
													if (isset($dialplan_detail_type) && $dialplan_detail_tag == 'condition' && $dialplan_detail_type == 'regex') {
														$condition = "	<condition regex=\"" . $dialplan_detail_data . "\"" . $condition_break.">";
													}
													elseif (isset($dialplan_detail_type) && $dialplan_detail_tag == 'regex') {
														$condition = "		<regex field=\"" . $dialplan_detail_type . "\" expression=\"" . $dialplan_detail_data . "\"" . $condition_break . "/>";
													}
													else {
														$condition = "	<condition field=\"" . $dialplan_detail_type . "\" expression=\"" . $dialplan_detail_data . "\"" . $condition_break;
													}
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
												if ($condition_attribute && (!empty($condition_attribute))) {
													$xml .= "	<condition " . $condition_attribute . $condition_break . ">\n";
													$condition_attribute = "";
												}
												else if (!empty($condition) && !empty($condition_tag_status) && substr($condition, -1) == ">") {
													$xml .= $condition . "\n";
													$condition = "";
												}
												else if (!empty($condition) && !empty($condition_tag_status)) {
													$xml .= $condition . ">\n";
													$condition = "";
												}
											}
										}

										if ($this->context == "public" || substr($this->context, 0, 7) == "public@" || substr($this->context, -7) == ".public") {
											if ($dialplan_detail_tag == "action") {
												if ($first_action) {
													//add the call direction and domain name and uuid
														$xml .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
														if (!empty($domain_uuid)) {
															$domain_name = $domains[$domain_uuid];
															$xml .= "		<action application=\"set\" data=\"domain_uuid=" . $domain_uuid . "\" inline=\"true\"/>\n";
														}
														if (!empty($domain_name)) {
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

									//reset back to first action if the group has changed
										if ($previous_dialplan_detail_group != $dialplan_detail_group) {
											$first_action = true;
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

						//prevent partial dialplan (pass=nil may be error in sql or empty resultset)
							if (isset($pass) && $pass == false) {
								if (!empty($this->details)) {
									echo 'error while build context: ' . $this->context;
								}
							}

						//close the extension tag if it was left open
							if ($dialplan_tag_status == "open") {
								if ($condition_tag_status == "open") {
									if ($condition_attribute && (!empty($condition_attribute))) {
										$xml .= "	<condition " . $condition_attribute . $condition_break . "/>\n";
									}
									else if (!empty($condition) && substr($string, -1) == ">") {
										$xml .= $condition . "\n";
									}
									else if (!empty($condition)) {
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
							//$xml .= "	</section>\n";
							//$xml .= "</document>\n";

					} //end if source = details

				//return the array
					if ($this->destination == "array") {
						return $dialplans;
					}

				//save the dialplan xml
					if ($this->destination == "database") {
						if (!empty($dialplans)) {
							$x = 0;
							foreach ($dialplans as $key => $value) {
								if (is_uuid($key) && !empty($value)) {
									//build update array
									$array['dialplans'][$x]['dialplan_uuid'] = $key;
									$array['dialplans'][$x]['dialplan_xml'] = $value;

									//grant temporary permissions
									$p = permissions::new();
									$p->add('dialplan_edit', 'temp');

									//execute update
									$this->database->app_name = 'dialplans';
									$this->database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
									$this->database->save($array);
									unset($array);

									//revoke temporary permissions
									$p->delete('dialplan_edit', 'temp');
								}
							}
						}
					}

			}

			public function defaults() {

				//get the array of xml files and then process thm
					$xml_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/resources/switch/conf/dialplan/*.xml");
					foreach ($xml_list as $xml_file) {
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
					foreach ($sql as $query) {
						$this->database->execute($query);
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

			/**
			* delete records
			*/
			public function delete($records) {

				//determine app and permission prefix
					if ($this->app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
						$this->app_name = 'dialplan_inbound';
						$this->permission_prefix = 'inbound_route_';
					}
					else if ($this->app_uuid == '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3') {
						$this->app_name = 'dialplan_outbound';
						$this->permission_prefix = 'outbound_route_';
					}
					else if ($this->app_uuid == '16589224-c876-aeb3-f59f-523a1c0801f7') {
						$this->app_name = 'fifo';
						$this->permission_prefix = 'fifo_';
					}
					else if ($this->app_uuid == '4b821450-926b-175a-af93-a03c441818b1') {
						$this->app_name = 'time_conditions';
						$this->permission_prefix = 'time_condition_';
					}
					else {
						//use default in constructor
					}

				if (permission_exists($this->permission_prefix.'delete')) {

					//add multi-lingual support
						$language = new text;
						$text = $language->get();

					//validate the token
						$token = new token;
						if (!$token->validate($_SERVER['PHP_SELF'])) {
							message::add($text['message-invalid_token'],'negative');
							header('Location: '.$this->list_page);
							exit;
						}

					//delete multiple records
						if (!empty($records)) {

							//build the delete array
								foreach ($records as $x => $record) {
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {

										//build delete array
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
											$array['dialplan_details'][$x]['dialplan_uuid'] = $record['uuid'];

										//get the dialplan context
											$sql = "select dialplan_context from v_dialplans ";
											$sql .= "where dialplan_uuid = :dialplan_uuid ";
											$parameters['dialplan_uuid'] = $record['uuid'];
											$dialplan_contexts[] = $this->database->select($sql, $parameters ?? null, 'column');
											unset($sql, $parameters);

									}
								}

							//delete the checked rows
								if (!empty($array)) {

									//grant temporary permissions
										$p = permissions::new();
										$p->add('dialplan_delete', 'temp');
										$p->add('dialplan_detail_delete', 'temp');

									//execute delete
										$this->database->app_name = $this->app_name;
										$this->database->app_uuid = $this->app_uuid;
										$this->database->delete($array);

									//revoke temporary permissions
										$p->delete('dialplan_delete', 'temp');
										$p->delete('dialplan_detail_delete', 'temp');

									//clear the cache
										if (!empty($dialplan_contexts)) {
											$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);
											$cache = new cache;
											foreach ($dialplan_contexts as $dialplan_context) {
												$cache->delete("dialplan:".$dialplan_context);
											}
										}

									//clear the destinations session array
										if (isset($_SESSION['destinations']['array'])) {
											unset($_SESSION['destinations']['array']);
										}

									//set message
										message::add($text['message-delete'].': '.@sizeof($array[$this->table]));

								}
								unset($records, $array);

						}
				}
			}

			public function delete_details($records) {
				//set private variables
					$this->table = 'dialplan_details';
					$this->uuid_prefix = 'dialplan_detail_';

				//determine app and permission prefix
					if ($this->app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
						$this->app_name = 'dialplan_inbound';
						$this->permission_prefix = 'inbound_route_';
					}
					else if ($this->app_uuid == '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3') {
						$this->app_name = 'dialplan_outbound';
						$this->permission_prefix = 'outbound_route_';
					}
					else if ($this->app_uuid == '16589224-c876-aeb3-f59f-523a1c0801f7') {
						$this->app_name = 'fifo';
						$this->permission_prefix = 'fifo_';
					}
					else if ($this->app_uuid == '4b821450-926b-175a-af93-a03c441818b1') {
						$this->app_name = 'time_conditions';
						$this->permission_prefix = 'time_condition_';
					}
					else {
						$this->permission_prefix = 'dialplan_detail_';
					}

				if (permission_exists($this->permission_prefix.'delete')) {

					//add multi-lingual support
						$language = new text;
						$text = $language->get();

					//validate the token
						$token = new token;
						if (!$token->validate($_SERVER['PHP_SELF'])) {
							message::add($text['message-invalid_token'],'negative');
							header('Location: '.$this->list_page);
							exit;
						}

					//delete multiple records
						if (!empty($records)) {

							//build the delete array
								foreach ($records as $x => $record) {
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {

										//build delete array
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
											$array[$this->table][$x]['dialplan_uuid'] = $this->dialplan_uuid;

										//get the dialplan context
											$sql = "select dialplan_context from v_dialplans ";
											$sql .= "where dialplan_uuid = :dialplan_uuid ";
											$parameters['dialplan_uuid'] = $this->dialplan_uuid;
											$dialplan_contexts[] = $this->database->select($sql, $parameters ?? null, 'column');
											unset($sql, $parameters);

									}
								}

							//delete the checked rows
								if (!empty($array)) {

									//grant temporary permissions
										$p = permissions::new();
										$p->add('dialplan_detail_delete', 'temp');

									//execute delete
										$this->database->app_name = $this->app_name;
										$this->database->app_uuid = $this->app_uuid;
										$this->database->delete($array);

									//revoke temporary permissions
										$p->delete('dialplan_detail_delete', 'temp');

									//clear the cache
										if (!empty($dialplan_contexts)) {
											$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);
											$cache = new cache;
											foreach ($dialplan_contexts as $dialplan_context) {
												$cache->delete("dialplan:".$dialplan_context);
											}
										}

								}
								unset($records, $array);

						}
				}
			}

			/**
			* toggle records
			*/
			public function toggle($records) {

				//determine app and permission prefix
					if ($this->app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
						$this->app_name = 'dialplan_inbound';
						$this->permission_prefix = 'inbound_route_';
					}
					else if ($this->app_uuid == '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3') {
						$this->app_name = 'dialplan_outbound';
						$this->permission_prefix = 'outbound_route_';
					}
					else if ($this->app_uuid == '16589224-c876-aeb3-f59f-523a1c0801f7') {
						$this->app_name = 'fifo';
						$this->permission_prefix = 'fifo_';
					}
					else if ($this->app_uuid == '4b821450-926b-175a-af93-a03c441818b1') {
						$this->app_name = 'time_conditions';
						$this->permission_prefix = 'time_condition_';
					}
					else {
						//use default in constructor
					}

				if (permission_exists($this->permission_prefix.'edit')) {

					//add multi-lingual support
						$language = new text;
						$text = $language->get();

					//validate the token
						$token = new token;
						if (!$token->validate($_SERVER['PHP_SELF'])) {
							message::add($text['message-invalid_token'],'negative');
							header('Location: '.$this->list_page);
							exit;
						}

					//toggle the checked records
						if (!empty($records)) {

							//get current toggle state
								foreach($records as $x => $record) {
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$uuids[] = "'".$record['uuid']."'";
									}
								}
								if (!empty($uuids)) {
									$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, dialplan_context from v_".$this->table." ";
									$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									if (!permission_exists('dialplan_all')) {
										$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
										$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									}
									$rows = $this->database->select($sql, $parameters ?? null, 'all');
									if (!empty($rows)) {
										foreach ($rows as $row) {
											$states[$row['uuid']] = $row['toggle'];
											$dialplan_contexts[] = $row['dialplan_context'];
										}
									}
									unset($sql, $parameters, $rows, $row);
								}

							//build update array
								$x = 0;
								foreach($states as $uuid => $state) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
									$x++;
								}

							//save the changes
								if (!empty($array)) {

									//grant temporary permissions
										$p = permissions::new();
										$p->add('dialplan_edit', 'temp');

									//save the array
										$this->database->app_name = $this->app_name;
										$this->database->app_uuid = $this->app_uuid;
										$this->database->save($array);
										unset($array);

									//revoke temporary permissions
										$p->delete('dialplan_edit', 'temp');

									//clear the cache
										if (!empty($dialplan_contexts)) {
											$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);
											$cache = new cache;
											foreach ($dialplan_contexts as $dialplan_context) {
												$cache->delete("dialplan:".$dialplan_context);
											}
										}

									//clear the destinations session array
										if (isset($_SESSION['destinations']['array'])) {
											unset($_SESSION['destinations']['array']);
										}

									//set message
										message::add($text['message-toggle']);
								}
								unset($records, $states);
						}

				}
			}

			/**
			* copy records
			*/
			public function copy($records) {

				//determine app and permission prefix
					if ($this->app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
						$this->app_name = 'dialplan_inbound';
						$this->permission_prefix = 'inbound_route_';
					}
					else if ($this->app_uuid == '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3') {
						$this->app_name = 'dialplan_outbound';
						$this->permission_prefix = 'outbound_route_';
					}
					else if ($this->app_uuid == '16589224-c876-aeb3-f59f-523a1c0801f7') {
						$this->app_name = 'fifo';
						$this->permission_prefix = 'fifo_';
					}
					else if ($this->app_uuid == '4b821450-926b-175a-af93-a03c441818b1') {
						$this->app_name = 'time_conditions';
						$this->permission_prefix = 'time_condition_';
					}
					else {
						//use default in constructor
					}

				if (permission_exists($this->permission_prefix.'add')) {

					//add multi-lingual support
						$language = new text;
						$text = $language->get();

					//validate the token
						$token = new token;
						if (!$token->validate($_SERVER['PHP_SELF'])) {
							message::add($text['message-invalid_token'],'negative');
							header('Location: '.$this->list_page);
							exit;
						}

					//copy the checked records
						if (!empty($records)) {

							//get checked records
								foreach($records as $x => $record) {
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$uuids[] = "'".$record['uuid']."'";
									}
								}

							//create insert array from existing data
								if (!empty($uuids)) {

									//primary table
										$sql = "select * from v_".$this->table." ";
										$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
										$rows = $this->database->select($sql, $parameters ?? null, 'all');
										if (!empty($rows)) {
											$y = 0;
											foreach ($rows as $x => $row) {
												//set a unique uuid
													$primary_uuid = uuid();

												//copy data
													$array[$this->table][$x] = $row;

												//app_uuid needs to be unique for copied dialplans
													//except for inbound and outbound routes, fifo, time conditions
													$app_uuid = $row['app_uuid'];
													switch ($app_uuid) {
														case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": break;
														case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": break;
														case "16589224-c876-aeb3-f59f-523a1c0801f7": break;
														case "4b821450-926b-175a-af93-a03c441818b1": break;
														default: $app_uuid = uuid();
													}

												//dialplan copy should have a unique app_uuid
													$array[$this->table][$x]['app_uuid'] = $app_uuid;

												//overwrite
													$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $primary_uuid;
													$array[$this->table][$x]['dialplan_description'] = trim($row['dialplan_description'].' ('.$text['label-copy'].')');

												//details sub table
													$sql_2 = "select * from v_dialplan_details where dialplan_uuid = :dialplan_uuid";
													$parameters_2['dialplan_uuid'] = $row['dialplan_uuid'];
													$rows_2 = $this->database->select($sql_2, $parameters_2 ?? null, 'all');
													if (!empty($rows_2)) {
														foreach ($rows_2 as $row_2) {

															//copy data
																$array['dialplan_details'][$y] = $row_2;

															//overwrite
																$array['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
																$array['dialplan_details'][$y]['dialplan_uuid'] = $primary_uuid;

															//increment
																$y++;

														}
													}
													unset($sql_2, $parameters_2, $rows_2, $row_2);

												//get dialplan contexts
													$dialplan_contexts[] = $row['dialplan_context'];
											}
										}
										unset($sql, $parameters, $rows, $row);
								}

							//save the changes and set the message
								if (!empty($array)) {

									//grant temporary permissions
										$p = permissions::new();
										$p->add('dialplan_detail_add', 'temp');

									//save the array
										$this->database->app_name = $this->app_name;
										$this->database->app_uuid = $this->app_uuid;
										$this->database->save($array);
										//view_array($this->database->message);
										unset($array);

									//revoke temporary permissions
										$p->delete('dialplan_detail_add', 'temp');

									//clear the cache
										if (!empty($dialplan_contexts)) {
											$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);
											$cache = new cache;
											foreach ($dialplan_contexts as $dialplan_context) {
												$cache->delete("dialplan:".$dialplan_context);
											}
										}

									//set message
										message::add($text['message-copy']);

								}
								unset($records);
						}

				}
			} //method


		} //class
	}

?>
