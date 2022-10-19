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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

class auto_loader {

	public function __construct() {
		spl_autoload_register(array($this, 'loader'));
	}

	private function loader($class_name) {
		//set the default value
			$class_found = false;

		//sanitize the class name
			$class_name = preg_replace('[^a-zA-Z0-9_]', '', $class_name);

		//save the log to the syslog server
			if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
				openlog("PHP", LOG_PID | LOG_PERROR, LOG_LOCAL0);
			}

		//find the most relevant class name
			if (!$class_found && file_exists($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/resources/classes/".$class_name.".php")) {
				//first priority
				$path = $_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/resources/classes/".$class_name.".php";
				$class_found = true;
				if ($_REQUEST['debug'] == 'true') {
					syslog(LOG_WARNING, "[php][autoloader] name: ".$class_name.", path: ".$path.", line: ".__line__);
				}
				include $path;
			}
			elseif (!$class_found && file_exists($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/core/".$class_name."/resources/classes/".$class_name.".php")) {
				//second priority
				$path = $_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/core/".$class_name."/resources/classes/".$class_name.".php";
				$class_found = true;
				if ($_REQUEST['debug'] == 'true') {
					syslog(LOG_WARNING, "[php][autoloader] name: ".$class_name.", path: ".$path.", line: ".__line__);
				}
				include $path;
			}
			elseif (!$class_found && file_exists($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/app/".$class_name."/resources/classes/".$class_name.".php")) {
				//third priority
				$path = $_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/app/".$class_name."/resources/classes/".$class_name.".php";
				$class_found = true;
				if ($_REQUEST['debug'] == 'true') {
					syslog(LOG_WARNING, "[php][autoloader] name: ".$class_name.", path: ".$path.", line: ".__line__);
				}
				include $path;
			}

		//use glob for a more exensive search for the classes (note: GLOB_BRACE doesn't work on some systems)
			if (!$class_found && !class_exists($class_name)) {
				//fourth priority
				$results_1 = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/resources/classes/".$class_name.".php");
				$results_2 = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/resources/classes/".$class_name.".php");
				$results = array_merge((array)$results_1,(array)$results_2);
				unset($results_1, $results_2);
				foreach ($results as &$class_file) {
					if (!$class_found) {
						$class_found = true;
						if ($_REQUEST['debug'] == 'true') {
							syslog(LOG_WARNING, "[php][autoloader] name: ".$class_name.", path: ".$class_file.", line: ".__line__);
						}
						include $class_file;
						break;
					}
				}
				unset($results);
			}

		//save the log to the syslog server
			if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
				closelog();
			}
	}
}

?>
