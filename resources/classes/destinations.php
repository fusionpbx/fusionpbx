<?php

/**
 * destinations
 *
 * @method array get the destinations
 */
class destinations {

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//place holder
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
	 * Get the destination array
	 * @var null
	 */
	public function get_array() {

		//set the global variables
			global $db;

		//get the array from the app_config.php files
			$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
			$x = 0;
			foreach ($config_list as &$config_path) {
				include($config_path);
				$x++;
			}
			$i = 0;
			foreach ($apps as $x => &$app) {
				foreach ($app['destinations'] as &$row) {
					$switch[destinations][] = $row;
				}
			}

		//put the array in order
			foreach ($switch[destinations] as $row) {
				$option_groups[] = $row['label'];
			}
			array_multisort($option_groups, SORT_ASC, $switch[destinations]);

		//add the sql and data to the array
			$x = 0;
			foreach ($switch[destinations] as $row) {
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
					if (isset($row['sql'])) {
						$sql .= "order by ".trim($row['order_by']);
					}
					$sql = str_replace("\${domain_name}", $_SESSION['domain_uuid'], $sql);
					$sql = trim($sql);
					$statement = $db->prepare($sql);
					$statement->execute();
					$result = $statement->fetchAll(PDO::FETCH_NAMED);
					unset($statement);

					$switch['destinations'][$x]['result']['sql'] = $sql;
					$switch['destinations'][$x]['result']['data'] = $result;
				}
				$x++;
			}

		//return the destination array
			return $switch['destinations'];

	}

	/**
	 * Get a specific item from the cache
	 * @var string $destination_type can be ivr, dialplan, call_center_contact or bridge
	 * @var string $destination_name - current name
	 * @var string $destination_value - current value
	 */
	public function select($destination_type, $destination_name, $destination_value) {

		//get the array
			$destinations = $this->get_array();

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

			//default selection found to false
			$selection_found = false;

			//print_r($switch);
			$response .= "	<select name='".$destination_name."' id='".$destination_id."' class='formfld' style='".$select_style."' onchange=\"".$onchange."\">\n";
			foreach ($switch[destinations] as $row) {

				$name = $row['name'];
				$label = $row['label'];
				$destination = $row['field']['destination'];

				//add multi-lingual support
				if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/".$name."/app_languages.php")) {
					$language2 = new text;
					$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/'.$name);
				}

				$response .= "		<optgroup label='".$text2['title-'.$label]."'>\n";
				foreach ($row['result']['data'] as $data) {
					$select_value = $row['select_value']['dialplan'];
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
							$select_value = str_replace("\${".$key."}", $data[$key], $select_value);
							$select_label = str_replace("\${".$key."}", $data[$key], $select_label);
						}
					}
					$select_value = str_replace("\${domain_name}", $_SESSION['domain_name'], $select_value);
					$select_value = str_replace("\${context}", $_SESSION['context'], $select_value); //to do: context can come from the array
					$select_label = str_replace("\${domain_name}", $_SESSION['domain_name'], $select_label);
					$select_label = str_replace("\${context}", $_SESSION['context'], $select_label);
					$response .= "			<option value='".$select_value."'>".trim($select_label)."</option>\n";
				}
				$response .= "		</optgroup>\n";
				unset($text);
			}
			$response .= "	</select>\n";
			if (if_group("superadmin")) {
				$response .= "<input type='button' id='btn_select_to_input_".$destination_id."' class='btn' name='' alt='back' onclick='changeToInput".$destination_id."(document.getElementById(\"".$destination_id."\"));this.style.visibility = \"hidden\";' value='&#9665;'>";
			}

		//return the formatted destinations
			return $response;
	}
}
//$obj = new destinations;
//echo $obj->select('dialplan', 'example', 'value');

?>