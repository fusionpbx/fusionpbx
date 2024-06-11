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

	public function __construct() {
		spl_autoload_register(array($this, 'loader'));
	}

	public static function autoload_search($array) : string {
		if (!is_array($array) && count($path) != 0) {
			return '';
		}
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

	private function loader($class_name) : bool {

		//sanitize the class name
		$class_name = preg_replace('[^a-zA-Z0-9_]', '', $class_name);

		//use glob for a more extensive search for the classes (note: GLOB_BRACE doesn't work on some systems)
		if (!class_exists($class_name)) {
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
				//send to syslog
				if (!empty($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
					openlog("PHP", LOG_PID | LOG_PERROR, LOG_LOCAL0);
					syslog(LOG_WARNING, "[php][autoloader] name: ".$class_name.", path: ".$path.", line: ".__line__);
					closelog();
				}

				//include the class or interface
				include $path;

				//return boolean
				return true;
			}
		}

		//return boolean
		return false;
	}
}

?>
