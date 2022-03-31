<?php

// make sure the PATH_SEPARATOR is defined
	umask(2);
	if (!defined("PATH_SEPARATOR")) {
		if (strpos($_ENV["OS"], "Win") !== false) {
			define("PATH_SEPARATOR", ";");
		} else {
			define("PATH_SEPARATOR", ":");
		}
	}

	if (!isset($output_format)) $output_format = (PHP_SAPI == 'cli') ? 'text' : 'html';

	// make sure the document_root is set
	$_SERVER["SCRIPT_FILENAME"] = str_replace("\\", '/', $_SERVER["SCRIPT_FILENAME"]);
	if(PHP_SAPI == 'cli'){
		chdir(pathinfo(realpath($_SERVER["PHP_SELF"]), PATHINFO_DIRNAME));
		$script_full_path = str_replace("\\", '/', getcwd() . '/' . $_SERVER["SCRIPT_FILENAME"]);
		$dirs = explode('/', pathinfo($script_full_path, PATHINFO_DIRNAME));
		if (file_exists('/project_root.php')) {
			$path = '/';
		} else {
			$i    = 1;
			$path = '';
			while ($i < count($dirs)) {
				$path .= '/' . $dirs[$i];
				if (file_exists($path. '/project_root.php')) {
					break;
				}
				$i++;
			}
		}
		$_SERVER["DOCUMENT_ROOT"] = $path;
	}else{
		$_SERVER["DOCUMENT_ROOT"]   = str_replace($_SERVER["PHP_SELF"], "", $_SERVER["SCRIPT_FILENAME"]);
	}
	$_SERVER["DOCUMENT_ROOT"]   = realpath($_SERVER["DOCUMENT_ROOT"]);
// try to detect if a project path is being used
	if (!defined('PROJECT_PATH')) {
		if (is_dir($_SERVER["DOCUMENT_ROOT"]. '/fusionpbx')) {
			define('PROJECT_PATH', '/fusionpbx');
		} elseif (file_exists($_SERVER["DOCUMENT_ROOT"]. '/project_root.php')) {
			define('PROJECT_PATH', '');
		} else {
			$dirs = explode('/', str_replace('\\', '/', pathinfo($_SERVER["PHP_SELF"], PATHINFO_DIRNAME)));
			$i    = 1;
			$path = $_SERVER["DOCUMENT_ROOT"];
			while ($i < count($dirs)) {
				$path .= '/' . $dirs[$i];
				if (file_exists($path. '/project_root.php')) {
					break;
				}
				$i++;
			}
			if(!file_exists($path. '/project_root.php')){
				die("Failed to locate the Project Root by searching for project_root.php please contact support for assistance");
			}
			$project_path = str_replace($_SERVER["DOCUMENT_ROOT"], "", $path);
			define('PROJECT_PATH', $project_path);
		}
		$_SERVER["PROJECT_ROOT"] = realpath($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH);
		set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER["PROJECT_ROOT"]);
	}

?>
