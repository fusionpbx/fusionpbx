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

spl_autoload_register('autoloader');

//load the composer autoloader if it exists
if (file_exists(__DIR__ . '/libs/autoload.php')) {
	require_once __DIR__ . '/libs/autoload.php';
}

function autoloader($class_name) {
	//save the log to the syslog server
	if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
		openlog("PHP", LOG_PID | LOG_PERROR, LOG_DAEMON);
	}

	//set array to search
	$search_paths = [
		__DIR__,
		__DIR__ . "/classes",
		glob(dirname(__DIR__) . "/*/*/resources/classes/$class_name.php"),
		glob(__DIR__ . "/*/*/resources/classes/$class_name.php"),
	];
	$class_file = autoloader_search_paths($class_name, $search_paths);

	if (!empty($class_file)) {
		require_once $class_file;
	} else {
		trigger_error("Unable to find $class_name", E_USER_ERROR);
	}

	//save the log to the syslog server
	if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
		syslog(LOG_WARNING, "[php][autoloader] name: ".$class_name.", path: ".$class_file.", line: ".__line__);
		closelog();
	}
}

function autoloader_search_paths($class_name, $search_paths): string {
	foreach($search_paths as $path) {
		if (is_array($path)) {
			return autoloader_search_paths($class_name, $path);
		}
		if (file_exists($path . '/' . $class_name . '.php')) {
			return $path . '/' . $class_name . '.php';
		} elseif (substr($path, -4) === '.php' && file_exists($path)) {
			return $path;
		}
	}
	return "";
}

/* Example Usage:
	require_once __DIR__ . '/auto_loader.php';
	$database = new database();
	$config = new config();
	$xml_cdr = new xml_cdr();
 //*/