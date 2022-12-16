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
	Portions created by the Initial Developer are Copyright (C) 2017 - 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * destinations
 *
 * @method get_array get the destinations
 * @method select build the html select
 */
if (!class_exists('destinations')) {
	class destinations {

		/**
		* destinations array
		*/
		public $destinations;
		public $domain_uuid;

		/**
		* declare private variables
		*/
		private $domain_name;
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;

		/**
		* Called when the object is created
		*/
		public function __construct() {
			//set the domain details
				if (is_null($this->domain_uuid)) {
					$this->domain_uuid = $_SESSION['domain_uuid'];
				}

			//assign private variables
				$this->app_name = 'destinations';
				$this->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
				$this->permission_prefix = 'destination_';
				$this->list_page = 'destinations.php';
				$this->table = 'destinations';
				$this->uuid_prefix = 'destination_';
		}

		/**
		* Called when there are no references to a particular object
		* unset the variables used in the class
		*/
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		* Convert destination number to a regular expression
		* @var string $array destination_prefix, destination_trunk_prefix, destination_area_code, destination_number
		*/
		public function to_regex($array) {

				if (isset($array['destination_prefix']) && isset($array['destination_trunk_prefix']) && isset($array['destination_area_code']) && isset($array['destination_number'])) {
					$destination_regex = "(\+?".$array['destination_prefix'].$array['destination_area_code'].$array['destination_number']."\$|";
					$destination_regex .= "^".$array['destination_trunk_prefix'].$array['destination_area_code'].$array['destination_number']."\$|";
					$destination_regex .= "^".$array['destination_area_code'].$array['destination_number']."\$|";
					$destination_regex .= "^".$array['destination_number']."\$)";
				}
				elseif (isset($array['destination_prefix']) && isset($array['destination_trunk_prefix']) && isset($array['destination_number'])) {
					$destination_regex = "(\+?".$array['destination_prefix'].$array['destination_number']."\$|";
					$destination_regex .= "^".$array['destination_trunk_prefix'].$array['destination_number']."\$|";
					$destination_regex .= "^".$array['destination_number']."\$)";
				}
				elseif (isset($array['destination_prefix']) && isset($array['destination_area_code']) && isset($array['destination_number'])) {
					$destination_regex = "(\+?".$array['destination_prefix'].$array['destination_area_code'].$array['destination_number']."\$|";
					$destination_regex .= "^".$array['destination_area_code'].$array['destination_number']."\$|";
					$destination_regex .= "^".$array['destination_number']."\$)";
				}
				elseif ((isset($array['destination_prefix']) && isset($array['destination_number'])) || isset($array['destination_number'])) {

					//set the variables
						$destination_prefix = $array['destination_prefix'];
						$destination_number = $array['destination_number'];
						$destination_regex = $array['destination_number'];

					//escape the plus
						if (substr($destination_number, 0, 1) == "+") {
							$destination_regex = "^\\+(".substr($destination_number, 1).")$";
						}

					//add prefix
						if (strlen($destination_prefix) > 0) {
							$destination_prefix = str_replace("+", "", $destination_prefix);
							$plus = '\+?';
							if (strlen($destination_prefix) == 1) {
								$destination_prefix = $plus.$destination_prefix.'?';
							}
							else {
								$destination_prefix = $plus.'(?:'.$destination_prefix.')?';
							}
						}

					//convert N,X,Z syntax to regex
						$destination_regex = str_ireplace("N", "[2-9]", $destination_regex);
						$destination_regex = str_ireplace("X", "[0-9]", $destination_regex);
						$destination_regex = str_ireplace("Z", "[1-9]", $destination_regex);

					//add ^ to the start of the string if missing
						if (substr($destination_regex, 0, 1) != "^") {
							$destination_regex = "^".$destination_regex;
						}

					//add $ to the end of the string if missing
						if (substr($destination_regex, -1) != "$") {
							$destination_regex = $destination_regex."$";
						}

					//add the round brackets
						if (!strstr($destination_regex, '(')) {
							if (strstr($destination_regex, '^')) {
								$destination_regex = str_replace("^", "^".$destination_prefix."(", $destination_regex);
							}
							else {
								$destination_regex = '^('.$destination_regex;
							}
							if (strstr($destination_regex, '$')) {
								$destination_regex = str_replace("$", ")$", $destination_regex);
							}
							else {
								$destination_regex = $destination_regex.')$';
							}
						}

				}

				return $destination_regex;

		}


		/**
		* Build the destination select list
		* @var string $destination_type can be ivr, dialplan, call_center_contact or bridge
		* @var string $destination_name - current name
		* @var string $destination_value - current value
		*/
		public function select($destination_type, $destination_name, $destination_value) {

			//set the global variables
			global $db_type;

			//get the domain_name
			$sql = "select domain_name from v_domains ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$database = new database;
			$this->domain_name = $database->select($sql, $parameters, 'column');

			//create a single destination select list
			if ($_SESSION['destinations']['select_mode']['text'] == 'default') {
				//get the destinations
				if (!is_array($this->destinations)) {

					//get the array from the app_config.php files
					$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
					$x = 0;
					foreach ($config_list as &$config_path) {
						try {
						    include($config_path);
						}
						catch (Exception $e) {
						    //echo 'Caught exception: ',  $e->getMessage(), "\n";
						}
						$x++;
					}
					$i = 0;
					foreach ($apps as $x => &$app) {
						if (isset($app['destinations'])) foreach ($app['destinations'] as &$row) {
							if (permission_exists($this->singular($row["name"])."_destinations")) {
								$this->destinations[] = $row;
							}
						}
					}
					//put the array in order
					if ($this->destinations !== null && is_array($this->destinations)) {
						foreach ($this->destinations as $row) {
							$option_groups[] = $row['label'];
						}
						array_multisort($option_groups, SORT_ASC, $this->destinations);
					}

					//add the sql and data to the array
					if ($this->destinations !== null && is_array($this->destinations)) {
						$x = 0;
						foreach ($this->destinations as $row) {
							if ($row['type'] === 'sql') {
								$table_name = preg_replace('#[^a-zA-Z0-9_]#', '', $row['name']);
								if (isset($row['sql'])) {
									if (is_array($row['sql'])) {
										$sql = trim($row['sql'][$db_type])." ";
									}
									else {
										$sql = trim($row['sql'])." ";
									}
								}
								else {
									$field_count = count($row['field']);
									$fields = '';
									$c = 1;
									foreach ($row['field'] as $key => $value) {
										$key = preg_replace('#[^a-zA-Z0-9_]#', '', $key);
										$value = preg_replace('#[^a-zA-Z0-9_]#', '', $value);
										if ($field_count != $c) { $delimiter = ','; } else { $delimiter = ''; }
										$fields .= $value." as ".$key.$delimiter." ";
										$c++;
									}
									$sql = "select ".$fields;
									$sql .= " from v_".$table_name." ";
								}
								if (isset($row['where'])) {
									$sql .= trim($row['where'])." ";
								}
								$sql .= "order by ".trim($row['order_by']);
								$sql = str_replace("\${domain_uuid}", $this->domain_uuid, $sql);
								$database = new database;
								$result = $database->select($sql, null, 'all');

								$this->destinations[$x]['result']['sql'] = $sql;
								$this->destinations[$x]['result']['data'] = $result;
							}
							if ($row['type'] === 'array') {
								$this->destinations[$x] = $row;
							}
							$x++;
						}
					}

					$this->destinations[$x]['type'] = 'array';
					$this->destinations[$x]['label'] = 'other';
					$this->destinations[$x]['name'] = 'dialplans';
					$this->destinations[$x]['field']['name'] = "name";
					$this->destinations[$x]['field']['destination'] = "destination";
					$this->destinations[$x]['select_value']['dialplan'] = "transfer:\${destination}";
					$this->destinations[$x]['select_value']['ivr'] = "menu-exec-app:transfer \${destination}";
					$this->destinations[$x]['select_label'] = "\${name}";
					$y = 0;
					$this->destinations[$x]['result']['data'][$y]['label'] = 'check_voicemail';
					$this->destinations[$x]['result']['data'][$y]['name'] = '*98';
					$this->destinations[$x]['result']['data'][$y]['destination'] = '*98 XML ${context}';
					$y++;
					$this->destinations[$x]['result']['data'][$y]['label'] = 'company_directory';
					$this->destinations[$x]['result']['data'][$y]['name'] = '*411';
					$this->destinations[$x]['result']['data'][$y]['destination'] = '*411 XML ${context}';
					$y++;
					$this->destinations[$x]['result']['data'][$y]['label'] = 'hangup';
					$this->destinations[$x]['result']['data'][$y]['name'] = 'hangup';
					$this->destinations[$x]['result']['data'][$y]['application'] = 'hangup';
					$this->destinations[$x]['result']['data'][$y]['destination'] = '';
					$y++;
					$this->destinations[$x]['result']['data'][$y]['label'] = 'record';
					$this->destinations[$x]['result']['data'][$y]['name'] = '*732';
					$this->destinations[$x]['result']['data'][$y]['destination'] = '*732 XML ${context}';
					$y++;
				}

				//remove special characters from the name
				$destination_id = str_replace("]", "", $destination_name);
				$destination_id = str_replace("[", "_", $destination_id);

				//set the css style
				$select_style = 'width: 200px;';

				//add additional
				if (if_group("superadmin")) {
					$response = "<script>\n";
					$response .= "var Objs;\n";
					$response .= "\n";
					$response .= "function changeToInput".$destination_id."(obj){\n";
					$response .= "	tb=document.createElement('INPUT');\n";
					$response .= "	tb.type='text';\n";
					$response .= "	tb.name=obj.name;\n";
					$response .= "	tb.className='formfld';\n";
					$response .= "	tb.setAttribute('id', '".$destination_id."');\n";
					$response .= "	tb.setAttribute('style', '".$select_style."');\n";
					if ($onchange != '') {
						$response .= "	tb.setAttribute('onchange', \"".$onchange."\");\n";
						$response .= "	tb.setAttribute('onkeyup', \"".$onchange."\");\n";
					}
					$response .= "	tb.value=obj.options[obj.selectedIndex].value;\n";
					$response .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.visibility = 'hidden';\n";
					$response .= "	tbb=document.createElement('INPUT');\n";
					$response .= "	tbb.setAttribute('class', 'btn');\n";
					$response .= "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
					$response .= "	tbb.type='button';\n";
					$response .= "	tbb.value=$('<div />').html('&#9665;').text();\n";
					$response .= "	tbb.objs=[obj,tb,tbb];\n";
					$response .= "	tbb.onclick=function(){ Replace".$destination_id."(this.objs); }\n";
					$response .= "	obj.parentNode.insertBefore(tb,obj);\n";
					$response .= "	obj.parentNode.insertBefore(tbb,obj);\n";
					$response .= "	obj.parentNode.removeChild(obj);\n";
					$response .= "	Replace".$destination_id."(this.objs);\n";
					$response .= "}\n";
					$response .= "\n";
					$response .= "function Replace".$destination_id."(obj){\n";
					$response .= "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
					$response .= "	obj[0].parentNode.removeChild(obj[1]);\n";
					$response .= "	obj[0].parentNode.removeChild(obj[2]);\n";
					$response .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.visibility = 'visible';\n";
					if ($onchange != '') {
						$response .= "	".$onchange.";\n";
					}
					$response .= "}\n";
					$response .= "</script>\n";
					$response .= "\n";
				}

				//set default to false
				$select_found = false;

				$response .= "	<select name='".$destination_name."' id='".$destination_id."' class='formfld' style='".$select_style."' onchange=\"".$onchange."\">\n";
				$response .= "			<option value=''></option>\n";
				foreach ($this->destinations as $row) {

					$name = $row['name'];
					$label = $row['label'];
					$destination = $row['field']['destination'];

					//add multi-lingual support
					if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$name."/app_languages.php")) {
						$language2 = new text;
						$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/'.$name);
					}

					if (is_array($row['result']['data']) && count($row['result']['data']) > 0 and strlen($row['select_value'][$destination_type]) > 0) {
						$response .= "		<optgroup label='".$text2['title-'.$label]."'>\n";
						$label2 = $label;
						foreach ($row['result']['data'] as $data) {
							$select_value = $row['select_value'][$destination_type];
							$select_label = $row['select_label'];
							foreach ($row['field'] as $key => $value) {
								if ($key == 'destination' and is_array($value)){
									if ($value['type'] === 'csv') {
										$array = explode($value['delimiter'], $data[$key]);
										$select_value = str_replace("\${destination}", $array[0], $select_value);
										$select_label = str_replace("\${destination}", $array[0], $select_label);
									}
								}
								else {
									if (strpos($value,',') !== false) {
										$keys = explode(",", $value);
										foreach ($keys as $k) {
											if (strlen($data[$k]) > 0) {
												$select_value = str_replace("\${".$key."}", $data[$k], $select_value);
												if (strlen($data['label']) == 0) {
													$select_label = str_replace("\${".$key."}", $data[$k], $select_label);
												}
												else {
													$label = $data['label'];
													$select_label = str_replace("\${".$key."}", $text2['option-'.$label], $select_label);
												}
											}
										}
									}
									else {
										$select_value = str_replace("\${".$key."}", $data[$key], $select_value);
										if (strlen($data['label']) == 0) {
											$select_label = str_replace("\${".$key."}", $data[$key], $select_label);
										}
										else {
											$label = $data['label'];
											$select_label = str_replace("\${".$key."}", $text2['option-'.$label], $select_label);
										}
									}
									//application: hangup
									if (strlen($data['application']) > 0) {
										$select_value = str_replace("transfer", $data['application'], $select_value);
									}
								}
							}

							$select_value = str_replace("\${domain_name}", $this->domain_name, $select_value);
							$select_value = str_replace("\${context}", $this->domain_name, $select_value);
							$select_label = str_replace("\${domain_name}", $this->domain_name, $select_label);
							$select_label = str_replace("\${context}", $this->domain_name, $select_label);
							$select_label = str_replace("&#9993", 'email-icon', $select_label);
							$select_label = escape(trim($select_label));
							$select_label = str_replace('email-icon', '&#9993', $select_label);
							if ($select_value == $destination_value) { $selected = "selected='selected' "; $select_found = true; } else { $selected = ''; }
							if ($label2 == 'destinations') { $select_label = format_phone($select_label); }
							$response .= "			<option value='".escape($select_value)."' ".$selected.">".$select_label."</option>\n";
						}
						$response .= "		</optgroup>\n";
						unset($text);
					}
				}
				if (!$select_found) {
					$destination_label = str_replace(":", " ", $destination_value);
					$destination_label = str_replace("menu-exec-app", "", $destination_label);
					$destination_label = str_replace("transfer", "", $destination_label);
					$destination_label = str_replace("XML ".$this->domain_name, "", $destination_label);
					if ($destination_value != '' || $destination_label != '') {
						$response .= "			<option value='".escape($destination_value)."' selected='selected'>".trim($destination_label)."</option>\n";
					}
				}
				$response .= "	</select>\n";
				if (if_group("superadmin")) {
					$response .= "<input type='button' id='btn_select_to_input_".$destination_id."' class='btn' name='' alt='back' onclick='changeToInput".$destination_id."(document.getElementById(\"".$destination_id."\"));this.style.visibility = \"hidden\";' value='&#9665;'>";
				}
			}

			//create a dynamic destination select list
			if ($_SESSION['destinations']['select_mode']['text'] == 'dynamic') {

				//remove special characters from the name
				$destination_id = str_replace("]", "", $destination_name);
				$destination_id = str_replace("[", "_", $destination_id);
				//$destination_id = preg_replace('/[^a-zA-Z_,.]/', '', $destination_name);
	
				?>
				<script type="text/javascript">
					function get_destinations(id, destination_type, action, search) {
						//alert(action);
						var xhttp = new XMLHttpRequest();
						xhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) {
								document.getElementById(id).innerHTML = this.responseText;
							}
						};
						if (action) {
							xhttp.open("GET", "/app/destinations/resources/destinations.php?destination_type="+destination_type+"&action="+action, true);
						}
						else {
							xhttp.open("GET", "/app/destinations/resources/destinations.php?destination_type="+destination_type, true);
						}
						xhttp.send();
					}
				</script>
				<?php

				//get the destinations
				$destination = new destinations;
				if (!isset($_SESSION['destinations']['array'][$destination_type])) {
					$_SESSION['destinations']['array'][$destination_type] = $destination->get($destination_type);
				}

				//get the destination label
				foreach($_SESSION['destinations']['array'][$destination_type] as $key => $value) {
					foreach($value as $k => $row) {
						if ($destination_value == $row['destination']) {
							$destination_key = $key;
							$destination_label = $row['label'];
							break;
						}
					}
				}

				//add the language object
				$language2 = new text;

				//build the destination select list in html
				$response .= "	<select id='{$destination_id}_type' class='formfld' style='".$select_style."' onchange=\"get_destinations('".$destination_id."', '".$destination_type."', this.value);\">\n";
				$response .= " 		<option value=''></option>\n";
				foreach($_SESSION['destinations']['array'][$destination_type] as $key => $value) {
					$singular = $this->singular($key);
					if (permission_exists("{$singular}_destinations")) {
						//determine if selected
						$selected = ($key == $destination_key) ? "selected='selected'" : ''; 

						//add multi-lingual support
						if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$key."/app_languages.php")) {
							$language2 = new text;
							$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/'.$key);
							$found = 'true';
						}
						if ($key == 'other') {
							$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/dialplans');
						}
						//add the application to the select list
						$response .= "		<option id='{$singular}' class='{$key}' value='".$key."' $selected>".$text2['title-'.$key]."</option>\n";
					}
				}
				$response .= "	</select>\n";
				$response .= "	<select id='".$destination_id."' name='".$destination_name."' class='formfld' style='".$select_style."'>\n";
				foreach($_SESSION['destinations']['array'][$destination_type] as $key => $value) {
					if ($key == $destination_key) {
						foreach($value as $k => $row) {
							$selected = ($row['destination'] == $destination_value) ? "selected='selected'" : '';
							$uuid = isset($row[$this->singular($key).'_uuid']) ? $row[$this->singular($key).'_uuid'] : $row['uuid'];
							$response .= "		<option id='{$uuid}' value='".$row['destination']."' $selected>".$row['label']."</option>\n";
						}
					}
				}
				$response .= "	</select>";
				$response .= button::create([
					'type'=>'button',
					'icon'=>'external-link-alt',
					'id'=>'btn_dest_go',
					'title'=>$text['label-edit'],
					'onclick'=>"let types = document.getElementById('{$destination_id}_type').options; let opts = document.getElementById('{$destination_id}').options; if(opts[opts.selectedIndex].id && opts[opts.selectedIndex].id.length > 0) {window.open('/app/'+types[types.selectedIndex].className+'/'+types[types.selectedIndex].id+'_edit.php?id='+opts[opts.selectedIndex].id, '_blank');}"
				])."\n";

				//debug information
				//echo $response;
				//echo "destination_key $destination_key\n";
				//echo "destination_id $destination_id\n";
				//echo "destination_type $destination_type\n";
				//echo "destination_name $destination_name\n";
				//echo "destination_value $destination_value\n";
				//exit;

			}

			//return the formatted destinations
			return $response;
		}

		/**
		* Get all the destinations
		* @var string $destination_type can be ivr, dialplan, call_center_contact or bridge
		*/
		public function all($destination_type) {

			//set the global variables
			global $db_type;

			//get the domain_name
			$sql = "select domain_name from v_domains ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$database = new database;
			$this->domain_name = $database->select($sql, $parameters, 'column');

			//get the destinations
			if (!is_array($this->destinations)) {

				//get the array from the app_config.php files
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				$x = 0;
				foreach ($config_list as &$config_path) {
					try {
					    include($config_path);
					}
					catch (Exception $e) {
					    //echo 'Caught exception: ',  $e->getMessage(), "\n";
					}
					$x++;
				}
				$i = 0;
				foreach ($apps as $x => &$app) {
					if (isset($app['destinations'])) {
						foreach ($app['destinations'] as &$row) {
							$this->destinations[] = $row;
						}
					}
				}

				//put the array in order
				foreach ($this->destinations as $row) {
					$option_groups[] = $row['label'];
				}
				array_multisort($option_groups, SORT_ASC, $this->destinations);

				//add the sql and data to the array
				$x = 0;
				foreach ($this->destinations as $row) {
					if ($row['type'] === 'sql') {
						$table_name = preg_replace('#[^a-zA-Z0-9_]#', '', $row['name']);
						if (isset($row['sql'])) {
							if (is_array($row['sql'])) {
								$sql = trim($row['sql'][$db_type])." ";
							}
							else {
								$sql = trim($row['sql'])." ";
							}
						}
						else {
							$field_count = count($row['field']);
							$fields = '';
							$c = 1;
							foreach ($row['field'] as $key => $value) {
								$key = preg_replace('#[^a-zA-Z0-9_]#', '', $key);
								$value = preg_replace('#[^a-zA-Z0-9_]#', '', $value);
								if ($field_count != $c) { $delimiter = ','; } else { $delimiter = ''; }
								$fields .= $value." as ".$key.$delimiter." ";
								$c++;
							}
							$sql = "select ".$fields;
							$sql .= " from v_".$table_name." ";
						}
						if (isset($row['where'])) {
							$sql .= trim($row['where'])." ";
						}
						$sql .= "order by ".trim($row['order_by']);
						$sql = str_replace("\${domain_uuid}", $this->domain_uuid, $sql);
						$database = new database;
						$result = $database->select($sql, null, 'all');

						$this->destinations[$x]['result']['sql'] = $sql;
						$this->destinations[$x]['result']['data'] = $result;
					}
					if ($row['type'] === 'array') {
						$this->destinations[$x] = $row;
					}
					$x++;
				}

				$this->destinations[$x]['type'] = 'array';
				$this->destinations[$x]['label'] = 'other';
				$this->destinations[$x]['name'] = 'dialplans';
				$this->destinations[$x]['field']['name'] = "name";
				$this->destinations[$x]['field']['destination'] = "destination";
				$this->destinations[$x]['select_value']['dialplan'] = "transfer:\${destination}";
				$this->destinations[$x]['select_value']['ivr'] = "menu-exec-app:transfer \${destination}";
				$this->destinations[$x]['select_label'] = "\${name}";
				$y=0;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'check_voicemail';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '*98 XML ${context}';
				$y++;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'company_directory';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '*411 XML ${context}';
				$y++;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'hangup';
				$this->destinations[$x]['result']['data'][$y]['application'] = 'hangup';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '';
				$y++;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'record';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '*732 XML ${context}';
				$y++;
			}

			//remove special characters from the name
			$destination_id = str_replace("]", "", $destination_name);
			$destination_id = str_replace("[", "_", $destination_id);

			//set default to false
			$select_found = false;

			foreach ($this->destinations as $row) {

				$name = $row['name'];
				$label = $row['label'];
				$destination = $row['field']['destination'];

				//add multi-lingual support
				if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$name."/app_languages.php")) {
					$language2 = new text;
					$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/'.$name);
				}

				if (is_array($row['result']['data']) && strlen($row['select_value'][$destination_type]) > 0) {
					$label2 = $label;
					foreach ($row['result']['data'] as $data) {
						$select_value = $row['select_value'][$destination_type];
						$select_label = $row['select_label'];
						foreach ($row['field'] as $key => $value) {
							if ($key == 'destination' and is_array($value)){
								if ($value['type'] == 'csv') {
									$array = explode($value['delimiter'], $data[$key]);
									$select_value = str_replace("\${destination}", $array[0], $select_value);
									$select_label = str_replace("\${destination}", $array[0], $select_label);
								}
							}
							else {
								if (strpos($value,',') !== false) {
									$keys = explode(",", $value);
									foreach ($keys as $k) {
										if (strlen($data[$k]) > 0) {
											$select_value = str_replace("\${".$key."}", $data[$k], $select_value);
											if (strlen($data['label']) == 0) {
												$select_label = str_replace("\${".$key."}", $data[$k], $select_label);
											}
											else {
												$label = $data['label'];
												$select_label = str_replace("\${".$key."}", $text2['option-'.$label], $select_label);
											}
										}
									}
								}
								else {
									$select_value = str_replace("\${".$key."}", $data[$key], $select_value);
									if (strlen($data['label']) == 0) {
										$select_label = str_replace("\${".$key."}", $data[$key], $select_label);
									}
									else {
										$label = $data['label'];
										$select_label = str_replace("\${".$key."}", $text2['option-'.$label], $select_label);
									}
								}
								//application: hangup
								if (strlen($data['application']) > 0) {
									$select_value = str_replace("transfer", $data['application'], $select_value);
								}
							}
						}

						$select_value = str_replace("\${domain_name}", $this->domain_name, $select_value);
						$select_value = str_replace("\${context}", $this->domain_name, $select_value);
						$select_label = str_replace("\${domain_name}", $this->domain_name, $select_label);
						$select_label = str_replace("\${context}", $this->domain_name, $select_label);
						$select_label = str_replace("&#9993", 'email-icon', $select_label);
						$select_label = escape(trim($select_label));
						$select_label = str_replace('email-icon', '&#9993', $select_label);
						if ($select_value == $destination_value) { $selected = "selected='selected' "; $select_found = true; } else { $selected = ''; }
						if ($label2 == 'destinations') { $select_label = format_phone($select_label); }
						$array[$label][$select_label] = $select_value;
					}
					unset($text);
				}
			}
			if (!$select_found) {
				$destination_label = str_replace(":", " ", $destination_value);
				$destination_label = str_replace("menu-exec-app", "", $destination_label);
				$destination_label = str_replace("transfer", "", $destination_label);
				$destination_label = str_replace("XML ".$this->domain_name, "", $destination_label);
				$array[$label][$destination_label] = $destination_value;
			}

			//return the formatted destinations
			return $array;
		}

		/**
		* Get all the destinations
		* @var string $destination_type can be ivr, dialplan, call_center_contact or bridge
		*/
		public function get($destination_type) {

			//set the global variables
			global $db_type;

			//get the domain_name
			$sql = "select domain_name from v_domains ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$database = new database;
			$this->domain_name = $database->select($sql, $parameters, 'column');

			//get the destinations
			if (!is_array($this->destinations)) {

				//get the array from the app_config.php files
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				$x = 0;
				foreach ($config_list as &$config_path) {
					try {
						include($config_path);
					}
					catch (Exception $e) {
						//echo 'Caught exception: ',  $e->getMessage(), "\n";
					}
					$x++;
				}
				$i = 0;
				foreach ($apps as $x => &$app) {
					if (isset($app['destinations'])) {
						foreach ($app['destinations'] as &$row) {
							$this->destinations[] = $row;
						}
					}
				}

				//put the array in order
				foreach ($this->destinations as $row) {
					$option_groups[] = $row['label'];
				}
				array_multisort($option_groups, SORT_ASC, $this->destinations);

				//add the sql and data to the array
				$x = 0;
				foreach ($this->destinations as $row) {
					if ($row['type'] === 'sql') {
						$table_name = preg_replace('#[^a-zA-Z0-9_]#', '', $row['name']);
						if (isset($row['sql'])) {
							if (is_array($row['sql'])) {
								$sql = trim($row['sql'][$db_type])." ";
							}
							else {
								$sql = trim($row['sql'])." ";
							}
						}
						else {
							$field_count = count($row['field']);
							$fields = '';
							$c = 1;
							foreach ($row['field'] as $key => $value) {
								$key = preg_replace('#[^a-zA-Z0-9_]#', '', $key);
								$value = preg_replace('#[^a-zA-Z0-9_]#', '', $value);
								if ($field_count != $c) { $delimiter = ','; } else { $delimiter = ''; }
								$fields .= $value." as ".$key.$delimiter." ";
								$c++;
							}
							//$sql = "select * ";
							$sql = "select ".$fields;
							$sql .= " from v_".$table_name." ";
						}
						if (isset($row['where'])) {
							$sql .= trim($row['where'])." ";
						}
						$sql .= "order by ".trim($row['order_by']);
						$sql = str_replace("\${domain_uuid}", $this->domain_uuid, $sql);
						$database = new database;
						$result = $database->select($sql, null, 'all');

						$this->destinations[$x]['result']['sql'] = $sql;
						$this->destinations[$x]['result']['data'] = $result;
					}
					if ($row['type'] === 'array') {
						$this->destinations[$x] = $row;
					}
					$x++;
				}

				$this->destinations[$x]['type'] = 'array';
				$this->destinations[$x]['label'] = 'other';
				$this->destinations[$x]['name'] = 'other';
				$this->destinations[$x]['field']['label'] = "label";
				$this->destinations[$x]['field']['name'] = "name";
				$this->destinations[$x]['field']['extension'] = "extension";
				$this->destinations[$x]['field']['destination'] = "destination";
				$this->destinations[$x]['select_value']['dialplan'] = "transfer:\${destination}";
				$this->destinations[$x]['select_value']['ivr'] = "menu-exec-app:transfer \${destination}";
				$this->destinations[$x]['select_label'] = "\${name}";
				$y = 0;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'check_voicemail';
				$this->destinations[$x]['result']['data'][$y]['extension'] = '*98';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '*98 XML ${context}';
				$y++;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'company_directory';
				$this->destinations[$x]['result']['data'][$y]['extension'] = '*411';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '*411 XML ${context}';
				$y++;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'hangup';
				$this->destinations[$x]['result']['data'][$y]['application'] = 'hangup';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '';
				$y++;
				$this->destinations[$x]['result']['data'][$y]['name'] = 'record';
				$this->destinations[$x]['result']['data'][$y]['extension'] = '*732';
				$this->destinations[$x]['result']['data'][$y]['destination'] = '*732 XML ${context}';
				$y++;

			}

			//remove special characters from the name
			$destination_id = str_replace("]", "", $destination_name);
			$destination_id = str_replace("[", "_", $destination_id);

			//set default to false
			$select_found = false;

			$i = 0;
			foreach ($this->destinations as $row) {

				$name = $row['name'];
				$label = $row['label'];
				$destination = $row['field']['destination'];

				//add multi-lingual support
				if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$name."/app_languages.php")) {
					$language2 = new text;
					$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/'.$name);
				}

				if (is_array($row['result']['data']) && strlen($row['select_value'][$destination_type]) > 0) {
					$label2 = $label;
					foreach ($row['result']['data'] as $data) {
						$select_value = $row['select_value'][$destination_type];
						$select_label = $row['select_label'];
						//echo $select_label." ".__line__." ".$name."<br />\n";
						foreach ($row['field'] as $key => $value) {
							if ($key == 'destination' and is_array($value)) {
								if ($value['type'] == 'csv') {
									$array = explode($value['delimiter'], $data[$key]);
									$select_value = str_replace("\${destination}", $array[0], $select_value);
									$select_label = str_replace("\${destination}", $array[0], $select_label);
								}
							}
							else {
								if (strpos($value,',') !== false) {
									$keys = explode(",", $value);
									foreach ($keys as $k) {
										if (strlen($data[$k]) > 0) {
											$select_value = str_replace("\${".$key."}", $data[$k], $select_value);
											if (strlen($data['label']) == 0) {
												$select_label = str_replace("\${".$key."}", $data[$k], $select_label);
											}
											else {
												$label = $data['label'];
												$select_label = str_replace("\${".$key."}", $text2['option-'.$label], $select_label);
											}
										}
									}
								}
								else {
									$select_value = str_replace("\${".$key."}", $data[$key], $select_value);
									if (strlen($data['label']) == 0) {
										$select_label = str_replace("\${".$key."}", $data[$key], $select_label);
									}
									else {
										$label = $data['label'];
										$select_label = str_replace("\${".$key."}", $text2['option-'.$label], $select_label);
									}
								}
								//application: hangup
								if (strlen($data['application']) > 0) {
									$select_value = str_replace("transfer", $data['application'], $select_value);
								}
							}
						}

						//view_array($data, false);
						//echo "name ".$name."\n";
						//echo "select_value ".$select_value."\n";
						//echo "select_label ".$select_label."\n";
						//echo "\n";

						$select_value = str_replace("\${domain_name}", $this->domain_name, $select_value);
						$select_value = str_replace("\${context}", $this->domain_name, $select_value);
						$select_label = str_replace("\${domain_name}", $this->domain_name, $select_label);
						$select_label = str_replace("\${context}", $this->domain_name, $select_label);
						$select_label = str_replace("&#9993", 'email-icon', $select_label);
						$select_label = escape(trim($select_label));
						$select_label = str_replace('email-icon', '&#9993', $select_label);
						if ($select_value == $destination_value) { $selected = "true' "; } else { $selected = 'false'; }
						if ($label2 == 'destinations') { $select_label = format_phone($select_label); }

						$array[$name][$i] = $data;
						$array[$name][$i]['label'] = $select_label;
						//$array[$name][$i]['destination'] = $select_value;
						//$array[$name][$i]['select_name'] = $select_name;
						//$array[$name][$i]['select_value'] = $select_value;
						//$array[$name][$i]['selected'] = $selected;
						$array[$name][$i]['destination'] = $select_value;
						$array[$name][$i]["extension"] = $data["extension"];
						
						$i++;
					}

					unset($text);
				}
			}

			if (!$selected) {
				$destination_label = str_replace(":", " ", $destination_value);
				$destination_label = str_replace("menu-exec-app", "", $destination_label);
				$destination_label = str_replace("transfer", "", $destination_label);
				$destination_label = str_replace("XML ".$this->domain_name, "", $destination_label);

				$array[$name][$i] = $row;
				$array[$name][$i]['label'] = $destination_label;
				//$array[$name][$i]['destination'] = $destination_value;
				//$array[$name][$i]['select_name'] = $select_name;
				//$array[$name][$i]['select_value'] = $select_value;
				$array[$name][$i]['destination'] = $destination_value;

				$i++;
			}

			//set the previous application name
			$previous_application = $name;

			//return the formatted destinations
			return $array;
		}

		/**
		* valid destination
		*/
		public function valid($destination, $type = 'dialplan') {
			//allow an empty destination
			if ($destination == ':') {
				return true;
			}

			//get all of the $destinations
			$destinations = $this->all($type);

			//loop through destinations to validate them
			foreach($destinations as $category => $array) {
				if (is_array($array)) {
					foreach ($array as $key => $value) {
						if ($destination == $value) {
							return true;
						}
					}
				}
			}
			return false;
		}

		/**
		* delete records
		*/
		public function delete($records) {
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
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {

									//build delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];

									//get the dialplan uuid and context
										$sql = "select dialplan_uuid, destination_context from v_destinations ";
										$sql .= "where destination_uuid = :destination_uuid ";
										$parameters['destination_uuid'] = $record['uuid'];
										$database = new database;
										$row = $database->select($sql, $parameters, 'row');
										unset($sql, $parameters);

									//include dialplan in array
										if (is_uuid($row['dialplan_uuid'])) {
											$array['dialplan_details'][$x]['dialplan_uuid'] = $row["dialplan_uuid"];
											$array['dialplans'][$x]['dialplan_uuid'] = $row["dialplan_uuid"];
											$destination_contexts[] = $row['destination_context'];
										}

								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_delete', 'temp');
									$p->add('dialplan_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_delete', 'temp');
									$p->delete('dialplan_detail_delete', 'temp');

								//clear the cache
									if (is_array($destination_contexts) && @sizeof($destination_contexts) != 0) {
										$destination_contexts = array_unique($destination_contexts);
										$cache = new cache;
										foreach ($destination_contexts as $destination_context) {
											$cache->delete("dialplan:".$destination_context);
										}
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-delete']);

							}
							unset($records);

					}
			}
		} //method

		/**
		* define singular function to convert a word in english to singular
		*/
		public function singular($word) {
			//"-es" is used for words that end in "-x", "-s", "-z", "-sh", "-ch" in which case you add
			if (substr($word, -2) == "es") {
				if (substr($word, -4) == "sses") { // eg. 'addresses' to 'address'
					return substr($word,0,-2);
				}
				elseif (substr($word, -3) == "ses") { // eg. 'databases' to 'database' (necessary!)
					return substr($word,0,-1);
				}
				elseif (substr($word, -3) == "ies") { // eg. 'countries' to 'country'
					return substr($word,0,-3)."y";
				}
				elseif (substr($word, -3, 1) == "x") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -3, 1) == "s") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -3, 1) == "z") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -4, 2) == "sh") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -4, 2) == "ch") {
					return substr($word,0,-2);
				}
				else {
					return rtrim($word, "s");
				}
			}
			else {
				return rtrim($word, "s");
			}
		} //method

	} //class
}
/*
$obj = new destinations;
//$destinations = $obj->destinations;
echo $obj->select('ivr', 'example1', 'menu-exec-app:transfer 32 XML voip.fusionpbx.com');
echo $obj->select('ivr', 'example2', '');
echo $obj->select('ivr', 'example3', '');
echo $obj->select('ivr', 'example4', '');
echo $obj->select('ivr', 'example5', '');
echo $obj->select('ivr', 'example6', '');
*/

?>
