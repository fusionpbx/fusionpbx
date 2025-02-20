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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

class auto_loader {

	const FILE = 'autoloader_cache.php';

	private $classes;

	public function __construct($project_path = '') {
		//classes must be loaded before this object is registered
		if (!$this->load_cache()) {
			//cache miss so load them
			$this->reload_classes($project_path);
			//update the cache after loading classes array
			$this->update_cache();
		}
		//register this object to load any unknown classes
		spl_autoload_register(array($this, 'loader'));
	}

	public function update_cache(string $file = ''): bool {
		//ensure we have somewhere to put the file
		if (empty($file)) {
			$file = sys_get_temp_dir() . '/' . self::FILE;
		}

		//guard against writing an empty file
		if (!empty($this->classes)) {
			//export the classes array using PHP engine
			$data = var_export($this->classes, true);

			//put the array in a form that it can be loaded directly to an array
			$result = file_put_contents($file, "<?php\n return " . $data . ";\n");
			if ($result !== false) {
				return true;
			}
			$error_array = error_get_last();
			//send to syslog when debugging
			if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
				openlog("PHP", LOG_PID | LOG_PERROR, LOG_LOCAL0);
				syslog(LOG_WARNING, "[php][auto_loader] " . $error_array['message']);
				closelog();
			}
		}
		return false;
	}

	public function load_cache(string $file = ''): bool {
		$this->classes = [];
		//use a standard file
		if (empty($file)) {
			$file = sys_get_temp_dir() . '/'. self::FILE;
		}
		//use PHP engine to parse it
		if (file_exists($file)) {
			$this->classes = include $file;
		}
		//assign to an array
		if (!empty($this->classes)) {
			return true;
		}
		return false;
	}

	public function reload_classes($project_path = '') {
		//set project path using magic dir constant
		if (empty($project_path)) {
			$project_path = dirname(__DIR__, 2);
		}

		//build the array of all classes
		$search_path = [];
		$search_path = array_merge($search_path, glob($project_path . '/resources/classes/*.php'));
		$search_path = array_merge($search_path, glob($project_path . '/resources/interfaces/*.php'));
		$search_path = array_merge($search_path, glob($project_path . '/resources/traits/*.php'));
		$search_path = array_merge($search_path, glob($project_path . '/*/*/resources/classes/*.php'));
		$search_path = array_merge($search_path, glob($project_path . '/*/*/resources/interfaces/*.php'));
		$search_path = array_merge($search_path, glob($project_path . '/*/*/resources/traits/*.php'));

		//reset the current array
		$this->classes = [];

		//store the class name (key) and the path (value)
		foreach ($search_path as $path) {
			$this->classes[basename($path, '.php')] = $path;
		}

	}

	private function loader($class_name) : bool {

		//sanitize the class name
		$class_name = preg_replace('[^a-zA-Z0-9_]', '', $class_name);

		//find the path using the class_name as the key in the classes array
		if (isset($this->classes[$class_name])) {
			//include the class or interface
			include_once $this->classes[$class_name];

			//return boolean
			return true;
		}

		//cache miss
		if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
			openlog("PHP", LOG_PID | LOG_PERROR, LOG_LOCAL0);
			syslog(LOG_WARNING, "[php][auto_loader] class not found in cache: ".$class_name);
			closelog();
		}

		//set project path using magic dir constant
		$project_path = dirname(__DIR__, 2);

		//build the search path array
		$search_path[] = glob($project_path . "/resources/classes/".$class_name.".php");
		$search_path[] = glob($project_path . "/resources/interfaces/".$class_name.".php");
		$search_path[] = glob($project_path . "/resources/traits/".$class_name.".php");
		$search_path[] = glob($project_path . "/*/*/resources/classes/".$class_name.".php");
		$search_path[] = glob($project_path . "/*/*/resources/interfaces/".$class_name.".php");
		$search_path[] = glob($project_path . "/*/*/resources/traits/".$class_name.".php");

		//find the path
		$path = self::autoload_search($search_path);
		if (!empty($path)) {

			//include the class or interface
			include $path;

			//make sure to reload the cache after we found a new class
			$this->reload_classes();

			//update the cache with new classes
			$this->update_cache();

			//return boolean
			return true;
		}

		//send to syslog when debugging
		if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
			openlog("PHP", LOG_PID | LOG_PERROR, LOG_LOCAL0);
			syslog(LOG_WARNING, "[php][auto_loader] class not found name: ".$class_name);
			closelog();
		}

		//return boolean
		return false;
	}

	public static function autoload_search($array) : string {
		foreach($array as $path) {
			if (is_array($path) && count($path) != 0) {
				foreach($path as $sub_path) {
					if (!empty($sub_path) && file_exists($sub_path)) {
						return $sub_path;
					}
				}
			}
			elseif (!empty($path) && file_exists($path)) {
				return $path;
			}
		}
		return '';
	}
}
