<?php

//add the document root to the include path
	if (defined('STDIN')) {
		$config_glob = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		$conf = parse_ini_file($config_glob[0]);
		set_include_path($conf['document.root']);
	}
	else {
		exit;
	}

//include files
	require_once "resources/require.php";
	include "resources/classes/permissions.php";
	require "app/email_queue/resources/functions/transcribe.php";

//increase limits
	set_time_limit(0);
	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '512M');

//save the arguments to variables
	$script_name = $argv[0];
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}
	//print_r($_GET);

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

//email queue enabled
	if ($_SESSION['email_queue']['enabled']['boolean'] != 'true') {
		echo "Email Queue is disabled in Default Settings\n";
		exit;
	}
	$mypid = getmypid();
	define('LOCK_FILE', "/var/run/" . basename($argv[0], ".php") . ".lock");
	$pid = file_get_contents(LOCK_FILE);

	if (!tryLock())
    	die("Already running.\n");

# remove the lock on exit (Control+C doesn't count as 'exit'?)
	register_shutdown_function('unlink', LOCK_FILE);


function tryLock() {
    # If lock file exists, check if stale.  If exists and is not stale, return TRUE
    # Else, create lock file and return FALSE.

    if (@symlink("/proc/" . getmypid(), LOCK_FILE) !== FALSE) # the @ in front of 'symlink' is to suppress the NOTICE you get if the LOCK_FILE exists
        return true;

    # link already exists
    # check if it's stale
    if (is_link(LOCK_FILE) && !is_dir(LOCK_FILE))
    {
        unlink(LOCK_FILE);
        # try to lock again
        return tryLock();
    }

    return false;
}

//get the call center settings
	$interval = $_SESSION['email_queue']['interval']['numeric'];

//set the defaults
	if (!is_numeric($interval)) { $interval = 30; }

//set the email queue limit
	if (isset($_SESSION['email_queue']['limit']['numeric'])) {
		$email_queue_limit = $_SESSION['email_queue']['limit']['numeric'];
	}
	else {
		$email_queue_limit = '30';
	}
	if (isset($_SESSION['email_queue']['debug']['boolean'])) {
		$debug = $_SESSION['email_queue']['debug']['boolean'];
	}

//get the messages waiting in the email queue
	while (true) {

		//get the messages that are waiting to send
		$sql = "select * from v_email_queue ";
		$sql .= "where (email_status = 'waiting' or email_status = 'trying') ";
		$sql .= "and hostname = :hostname ";
		$sql .= "order by domain_uuid asc ";
		$sql .= "limit :limit ";
		$parameters['hostname'] = $hostname;
		$parameters['limit'] = $email_queue_limit;
		$database = new database;
		$email_queue = $database->select($sql, $parameters, 'all');
		unset($parameters);

		//process the messages
		if (is_array($email_queue) && @sizeof($email_queue) != 0) {
			foreach($email_queue as $row) {
				$command = exec('which php')." ".$_SERVER['DOCUMENT_ROOT']."/app/email_queue/resources/jobs/email_send.php ";
				$command .= "'action=send&email_queue_uuid=".$row["email_queue_uuid"]."&hostname=".$hostname."'";
				if (isset($debug)) {
					exec($command, $output);
					$tz = ini_get('date.timezone');
					date_default_timezone_set($tz);
					$date = date('D M j G:i:s T Y');
					$prefix = '['. $date. '] ';
					$line = "\n";
					$log = preg_filter('/^/', $prefix, $output);
					$line = preg_filter('/$/', $line, $log);
					$prf = 'email_queue-'. $mypid;
					$temp_file = sys_get_temp_dir(). '/'. $prf.'-'.date('m-d-Y').'.log';
					if (file_exists($temp_file)) {
						file_put_contents($temp_file, $line, FILE_APPEND);
					} else {
						file_put_contents($temp_file, $line);
					}
				} else {
					//starts process rapidly doesn't wait for previous process to finish (used for production)
					$handle = popen($command." > /dev/null &", 'r');
					echo "'$handle'; " . gettype($handle) . "\n";
					$read = fread($handle, 2096);
					echo $read;
					pclose($handle);
				}
			}
		}

		//pause to prevent excessive database queries
		sleep($interval);
	}

//remove the old pid file
//	if (file_exists($pid_file)) {
//		unlink($pid_file);
//	}

//save output to
	//$fp = fopen(sys_get_temp_dir()."/mailer-app.log", "a");

//prepare the output buffers
	//ob_end_clean();
	//ob_start();

//message divider for log file
	//echo "\n\n=============================================================================================================================================\n\n";

//get and save the output from the buffer
	//$content = ob_get_contents(); //get the output from the buffer
	//$content = str_replace("<br />", "", $content);

	//ob_end_clean(); //clean the buffer

	//fwrite($fp, $content);
	//fclose($fp);

//notes
	//echo __line__."\n";
	// if not keeping the email then need to delete it after the voicemail is emailed

//how to use this feature
	// cd /var/www/fusionpbx; /usr/bin/php /var/www/fusionpbx/app/email_queue/resources/send.php

?>