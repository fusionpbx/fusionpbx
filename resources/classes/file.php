<?php

/**
 * cache class provides an abstracted cache
 */
class file {

	/**
	 *  variables
	*/
	public $recursive;
	public $files;

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * Called when the object is created
	 */
		public function __construct(array $setting_array = []) {
			//set domain and user UUIDs
			$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
			$this->user_uuid = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

			//set objects
			$this->database = $setting_array['database'] ?? database::new();
			$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);
	}

	/**
	 * Glob search for a list of files
	 * @var string $dir			this is the directory to scan
	 * @var boolean $recursive	get the sub directories
	 * @return array list of files or an empty array if not found
	 */
	public function glob($dir, $recursive): array {
		$files = [];
		if ($dir != '' || $dir != '/') {
			$tree = glob(rtrim($dir, '/') . '/*');
			if ($recursive) {
				if (is_array($tree)) {
					foreach($tree as $file) {
						if (is_dir($file)) {
							if ($recursive == true) {
								$files[] = $this->glob($file, $recursive);
							}
						} elseif (is_file($file)) {
							$files[] = $file;
						}
					}
				}
			}
			return $files;
		}
	}


	/**
	 * Get the sounds list of search as a relative path without the rate
	 */
	public function sounds($language = 'en', $dialect = 'us', $voice = 'callie') {
		//define an empty array
		$array = [];

		//set default values
		if (!isset($language)) { $language = 'en'; }
		if (!isset($dialect)) { $dialect = 'us'; }
		if (!isset($voice)) { $voice = 'callie'; }

		//set the variables
		if (!empty($this->settings->get('switch', 'sounds')) && file_exists($this->settings->get('switch', 'sounds'))) {
			$dir = $this->settings->get('switch', 'sounds').'/'.$language.'/'.$dialect.'/'.$voice;
			$rate = '8000';
			$files = $this->glob($dir.'/*/'.$rate, true);
		}

		//loop through the languages
		if (!empty($files)) {
			foreach($files as $file) {
				$file = substr($file, strlen($dir)+1);
				$file = str_replace("/".$rate, "", $file);
				$array[] = $file;
			}
		}

		//return the list of sounds
		return $array;
	}

}

/*
//add multi-lingual support
	$file = new file;
	$files = $file->sounds();
	print_r($files);
*/

?>
