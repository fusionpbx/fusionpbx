<?php

/**
 * cache class provides an abstracted cache
 *
 * @method string glob
 */
class file {

	/**
	 *  variables
	*/
	public $recursive;
	public $files;

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
	 * Glob search for a list of files
	 * @var string $dir			this is the directory to scan
	 * @var boolean $recursive	get the sub directories
	 */
	public function glob($dir, $recursive) {
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
				else {
					$files[] = $file;
				}
			}
			else {
				$files[] = $file;
			}
			return $files;
		}
	}


	/**
	 * Get the sounds list of search as a relative path without the rate
	 */
	public function sounds($language = 'en', $dialect = 'us', $voice = 'callie') {
		//set default values
		if (!isset($language)) { $language = 'en'; }
		if (!isset($dialect)) { $dialect = 'us'; }
		if (!isset($voice)) { $voice = 'callie'; }

		//set the variables
		$dir = $_SESSION['switch']['sounds']['dir'].'/'.$language.'/'.$dialect.'/'.$voice;
		$rate = '8000';
		$files = $this->glob($dir.'/*/'.$rate, true);
		
		//loop through the languages
		foreach($files as $file) {
			$file = substr($file, strlen($dir)+1);
			$file = str_replace("/".$rate, "", $file);
			$array[] = $file;
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
