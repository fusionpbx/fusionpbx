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
	public $domain_uuid;
	public $sound_types;
	public $full_path;

	/**
	* Class constructor
	*/
	public function __construct() {

	}

	/**
	 * Add a specific item in the cache
	 * @var array $array
	 * @var string $value	string to be cached
	 */
	public function get() {

		//miscellaneous
			if (empty($this->sound_types) || (is_array($this->sound_types) && in_array('miscellaneous', $this->sound_types))) {
				$x = 0;
				if (if_group("superadmin")) {
					$array['miscellaneous'][$x]['name'] = "say";
					$array['miscellaneous'][$x]['value'] = "say:";
					$x++;
					$array['miscellaneous'][$x]['name'] = "tone_stream";
					$array['miscellaneous'][$x]['value'] = "tone_stream:";
				}
			}
		//recordings
			if ((empty($this->sound_types) || (is_array($this->sound_types) && in_array('recordings', $this->sound_types))) && file_exists($_SERVER["PROJECT_ROOT"]."/app/recordings/app_config.php")) {
				$sql = "select recording_name, recording_filename from v_recordings ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "order by recording_name asc ";
				$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				$database = new database;
				$recordings = $database->select($sql, $parameters, 'all');
				if (is_array($recordings) && @sizeof($recordings) != 0) {
					foreach ($recordings as &$row) {
						$recording_name = $row["recording_name"];
						$recording_filename = $row["recording_filename"];
						$recording_path = !empty($this->full_path) && is_array($this->full_path) && in_array('recordings', $this->full_path) ? $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/' : null;
						$array['recordings'][$x]['name'] = $recording_name;
						$array['recordings'][$x]['value'] = $recording_path.$recording_filename;
						$x++;
					}
				}
				unset($sql, $parameters, $recordings, $row);
			}
		//phrases
			if ((empty($this->sound_types) || (is_array($this->sound_types) && in_array('phrases', $this->sound_types))) && file_exists($_SERVER["PROJECT_ROOT"]."/app/phrases/app_config.php")) {
				$sql = "select * from v_phrases ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				$database = new database;
				$phrases = $database->select($sql, $parameters, 'all');
				if (is_array($phrases) && @sizeof($phrases) != 0) {
					foreach ($phrases as &$row) {
						$array['phrases'][$x]['name'] = "phrase:".$row["phrase_name"];
						$array['phrases'][$x]['value'] = "phrase:".$row["phrase_uuid"];
						$x++;
					}
				}
				unset($sql, $parameters, $phrases, $row);
			}
		//sounds
			if ((empty($this->sound_types) || (is_array($this->sound_types) && in_array('sounds', $this->sound_types))) && file_exists($_SERVER["PROJECT_ROOT"]."/app/phrases/app_config.php")) {
				$file = new file;
				$sound_files = $file->sounds();
				if (is_array($sound_files) && @sizeof($sound_files) != 0) {
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

	}

}

?>