<?php

/**
 * sounds class
 *
 * @method string get
 */
class sounds {

	/**
	* Called when the object is created
	*/
	public $db;
	public $domain_uuid;

	/**
	* Class constructor
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
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}

	/**
	 * Add a specific item in the cache
	 * @var array $array
	 * @var string $value	string to be cached
	 */
	public function get() {

		//miscellaneous
			$x=0;
			if (if_group("superadmin")) {
				$array['miscellaneous'][$x]['name'] = "say";
				$array['miscellaneous'][$x]['value'] = "say:";
				$x++;
				$array['miscellaneous'][$x]['name'] = "tone_stream";
				$array['miscellaneous'][$x]['value'] = "tone_stream:";
			}
		//recordings
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/phrases/app_config.php")) {
				$sql = "select recording_name, recording_filename from v_recordings ";
				$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
				$sql .= "order by recording_name asc ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$recordings = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if (is_array($recordings) > 0) {
					foreach ($recordings as &$row) {
						$recording_name = $row["recording_name"];
						$recording_filename = $row["recording_filename"];
						$array['recordings'][$x]['name'] = $recording_name;
						$array['recordings'][$x]['value'] = $recording_filename;
						$x++;
					}
				}
			}
		//phrases
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/phrases/app_config.php")) {
				$sql = "select * from v_phrases where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						$array['phrases'][$x]['name'] = "phrase:".$row["phrase_name"];
						$array['phrases'][$x]['value'] = "phrase:".$row["phrase_uuid"];
						$x++;
					}
					unset ($prep_statement);
				}
			}
		//sounds
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/phrases/app_config.php")) {
				$file = new file;
				$sound_files = $file->sounds();
				if (is_array($sound_files)) {
					foreach ($sound_files as $value) {
						if (substr($value, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
							$value = substr($var, 71);
						}
						$array['sounds'][$x]['name'] = $value;
						$array['sounds'][$x]['value'] = $value;
						$x++;
					}
				}
			}
		//send the results
			return $array;
			//print_r($array);
	} // get method
} //end class

?>
