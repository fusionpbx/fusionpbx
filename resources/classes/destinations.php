<?php

/**
 * destinations
 *
 * @method get_array get the destinations
 * @method select build the html select
 */
class destinations {

	/**
	 * destinations array
	 */
	public $destinations;

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//set the global variables
			global $db, $db_type;

		//get the array from the app_config.php files
			$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
			$x = 0;
			foreach ($config_list as &$config_path) {
				include($config_path);
				$x++;
			}
			$i = 0;
			foreach ($apps as $x => &$app) {
				if (isset($app['destinations'])) foreach ($app['destinations'] as &$row) {
					$this->destinations[] = $row;
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
				if ($row['type'] = 'sql') {
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
							if ($field_count != $c) { $delimiter = ','; } else { $delimiter = ''; }
							$fields .= $value." as ".$key.$delimiter." ";
							$c++;
						}
						$sql = "select ".$fields;
						$sql .= " from v_".$row['name']." ";
					}
					if (isset($row['where'])) {
						$sql .= trim($row['where'])." ";
					}
					$sql .= "order by ".trim($row['order_by']);
					$sql = str_replace("\${domain_uuid}", $_SESSION['domain_uuid'], $sql);
					$sql = trim($sql);
					$statement = $db->prepare($sql);
					$statement->execute();
					$result = $statement->fetchAll(PDO::FETCH_NAMED);
					unset($statement);

					$this->destinations[$x]['result']['sql'] = $sql;
					$this->destinations[$x]['result']['data'] = $result;
				}
				$x++;
			}
			$this->destinations[$x]['type'] = 'array';
			$this->destinations[$x]['label'] = 'other';
			$this->destinations[$x]['name'] = 'dialplan';
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
	 * Get the destination menu
	 * @var string $destination_type can be ivr, dialplan, call_center_contact or bridge
	 * @var string $destination_name - current name
	 * @var string $destination_value - current value
	 */
	public function select($destination_type, $destination_name, $destination_value) {

		//remove special characters from the name
			$destination_id = str_replace("]", "", $destination_name);
			$destination_id = str_replace("[", "_", $destination_id);

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

				if (count($row['result']['data']) > 0 and strlen($row['select_value'][$destination_type]) > 0) {
					$response .= "		<optgroup label='".$text2['title-'.$label]."'>\n";
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

						$select_value = str_replace("\${domain_name}", $_SESSION['domain_name'], $select_value);
						$select_value = str_replace("\${context}", $_SESSION['context'], $select_value); //to do: context can come from the array
						$select_label = str_replace("\${domain_name}", $_SESSION['domain_name'], $select_label);
						$select_label = str_replace("\${context}", $_SESSION['context'], $select_label);
						$select_label = trim($select_label);
						if ($select_value == $destination_value) { $selected = "selected='selected' "; $select_found = true; } else { $selected = ''; }
						if ($label2 == 'destinations') { $select_label = format_phone($select_label); }
						$response .= "			<option value='".$select_value."' ".$selected.">".$select_label."</option>\n";
					}
					$response .= "		</optgroup>\n";
					unset($text);
				}
			}
			if (!$select_found) {
				$destination_label = str_replace(":", " ", $destination_value);
				$destination_label = str_replace("menu-exec-app:", " ", $destination_label);
				$response .= "			<option value='".$destination_value."' selected='selected'>".trim($destination_label)."</option>\n";
			}
			$response .= "	</select>\n";
			if (if_group("superadmin")) {
				$response .= "<input type='button' id='btn_select_to_input_".$destination_id."' class='btn' name='' alt='back' onclick='changeToInput".$destination_id."(document.getElementById(\"".$destination_id."\"));this.style.visibility = \"hidden\";' value='&#9665;'>";
			}

		//return the formatted destinations
			return $response;
	}
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
