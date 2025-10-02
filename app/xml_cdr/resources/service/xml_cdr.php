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

//make sure the database connection is available
	while (!$database->is_connected()) {
		//connect to the database
		$database->connect();

		//reload settings after connection to the database
		$settings = new settings(['database' => $database]);

		//sleep for a moment
		sleep(3);
	}

//get the xml_cdr directory
	$xml_cdr_dir = $settings->get('switch', 'log', '/var/log/freeswitch').'/xml_cdr';

//rename the directory
	if (file_exists($xml_cdr_dir.'/failed/invalid_xml')) {
		rename($xml_cdr_dir.'/failed/invalid_xml', $xml_cdr_dir.'/failed/xml');
	}

//create the invalid xml directory
	if (!file_exists($xml_cdr_dir.'/failed/xml')) {
		mkdir($xml_cdr_dir.'/failed/xml', 0770, true);
	}

//create the invalid size directory
	if (!file_exists($xml_cdr_dir.'/failed/size')) {
		mkdir($xml_cdr_dir.'/failed/size', 0770, true);
	}

//create the invalid sql directory
	if (!file_exists($xml_cdr_dir.'/failed/sql')) {
		mkdir($xml_cdr_dir.'/failed/sql', 0770, true);
	}

//update permissions to correct systems with the wrong permissions
	if (file_exists($xml_cdr_dir.'/failed')) {
		exec('chmod 770 -R '.$xml_cdr_dir.'/failed');
	}

//import the call detail records from HTTP POST or file system
	$cdr = new xml_cdr;

//service loop
	while (true) {

		//get the list of call detail records, and limit the number of records
		$xml_cdr_array = array_slice(glob($xml_cdr_dir . '/*.cdr.xml'), 0, 100);

		//process the call detail records
		if (!empty($xml_cdr_array)) {
			//make sure the database connection is available
			while (!$database->is_connected()) {
				//connect to the database
				$database->connect();

				//reload settings after connection to the database
				$settings = new settings(['database' => $database]);

				//sleep for a moment
				sleep(3);
			}

			foreach ($xml_cdr_array as $xml_cdr_file) {
				//move the files that are too large or zero file size to the failed size directory
				if (filesize($xml_cdr_file) >= (3 * 1024 * 1024) || filesize($xml_cdr_file) == 0) {
					//echo "WARNING: File too large or zero file size. Moving $file to failed\n";
					if (!empty($xml_cdr_dir)) {
						if (isset($debug) && $debug == true) {
							echo "Move the file ".$xml_cdr_file." to ".$xml_cdr_dir."/failed/size\n";
						}
						rename($xml_cdr_file, $xml_cdr_dir.'/failed/size/'.basename($xml_cdr_file));
					}
					continue;
				}

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
			}
		}

		//sleep for 100 ms
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
