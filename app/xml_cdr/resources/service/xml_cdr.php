<?php

//add the document root to the include path
	if (defined('STDIN')) {
		//includes files
		require_once dirname(__DIR__, 4) . "/resources/require.php";
	}
	else {
		exit;
	}

//increase limits
	set_time_limit(0);
	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '512M');

//save the arguments to variables
	$script_name = $argv[0];
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}

//set the variables
	if (isset($_GET['hostname'])) {
		$hostname = urldecode($_GET['hostname']);
	}
	if (isset($_GET['debug'])) {
		$debug = $_GET['debug'];
	}

//get the hostname
	if (!isset($hostname)) {
		$hostname = gethostname();
	}

//define the process id file
	$pid_file = "/var/run/fusionpbx/".basename( $argv[0], ".php") .".pid";

//function to check if the process exists
	function process_exists($file = false) {

		//set the default exists to false
		$exists = false;

		//check to see if the process is running
		if (file_exists($file)) {
			$pid = file_get_contents($file);
			if (function_exists('posix_getsid')) {
				if (posix_getsid($pid) === false) {
					//process is not running
					$exists = false;
				}
				else {
					//process is running
					$exists = true;
				}
			}
			else {
				if (file_exists('/proc/'.$pid)) {
					//process is running
					$exists = true;
				}
				else {
					//process is not running
					$exists = false;
				}
			}
		}

		//return the result
		return $exists;
	}

//check to see if the process exists
	$pid_exists = process_exists($pid_file);

//prevent the process running more than once
	if ($pid_exists) {
		echo "Cannot lock pid file {$pid_file}\n";
		exit;
	}

//get cdr settings
	//$interval = $settings->get('xml_cdr', '$interval');

//make sure the /var/run/fusionpbx directory exists
	if (!file_exists('/var/run/fusionpbx')) {
		$result = mkdir('/var/run/fusionpbx', 0777, true);
		if (!$result) {
			die('Failed to create /var/run/fusionpbx');
		}
	}

//create the process id file if the process doesn't exist
	if (!$pid_exists) {
		//remove the old pid file
		if (file_exists($pid_file)) {
			unlink($pid_file);
		}

		//show the details to the user
		if (isset($debug) && $debug == true) {
			echo "\n";
			echo "Service: ".basename( $argv[0], ".php")."\n";
			echo "Process ID: ".getmypid()."\n";
			echo "PID File: ".$pid_file."\n";
		}

		//save the pid file
		file_put_contents($pid_file, getmypid());
	}

//import the call detail records from HTTP POST or file system
	$cdr = new xml_cdr;

//get the cdr record
	$xml_cdr_dir = $settings->get('switch', 'log').'/xml_cdr';

//service loop
	while (true) {

		//make sure the database connection is available
		while (!$database->is_connected()) {
			//connect to the database
			$database->connect();

			//sleep for a moment
			sleep(3);
		}

		//find and process cdr records
		$xml_cdr_array = glob($xml_cdr_dir.'/*.cdr.xml');
		if (!empty($xml_cdr_array)) {
			$i = 0;
			foreach ($xml_cdr_array as $xml_cdr_file) {
				//add debug information
				if (isset($debug) && $debug == true) {
					echo $xml_cdr_file."\n";
				}

				//get the content from the file
				$call_details = file_get_contents($xml_cdr_file);

				//process the call detail record
				if (isset($xml_cdr_file) && isset($call_details)) {
					//set the file
					$cdr->file = basename($xml_cdr_file);

					//get the leg of the call and the file prefix
					if (substr(basename($xml_cdr_file), 0, 2) == "a_") {
						$leg = "a";
					}
					else {
						$leg = "b";
					}

					//decode the xml string
					if (substr($call_details, 0, 1) == '%') {
						$call_details = urldecode($call_details);
					}

					//parse the xml and insert the data into the db
					$cdr->xml_array($i, $leg, $call_details);
				}

				//limit the number of records process at one time
				if ($i == 100) {
					break;
				}

				//increment the value
				$i++;
			}
		}

		//sleep for a moment
		usleep(100000);

		//debug info
		if (!empty($debug) && $debug_level == '2') {
			//current memory
			$memory_usage = memory_get_usage();

			//peak memory
			$memory_peak = memory_get_peak_usage();
			echo "\n";
			echo 'Current memory: ' . round($memory_usage / 1024) . " KB\n";
			echo 'Peak memory: ' . round($memory_peak / 1024) . " KB\n\n";
			echo "\n";
		}

	}

?>
