<?php

//check the permission
	if (defined('STDIN')) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);
	}
	else {
		exit;
	}

//includes files
	require_once "resources/require.php";
	require_once "resources/pdo.php";
	include "resources/classes/permissions.php";
	require $_SERVER['DOCUMENT_ROOT']."/app/email_queue/resources/functions/transcribe.php";

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

//define the process id file
	$pid_file = "/var/run/fusionpbx/".basename( $argv[0], ".php") .".pid";
	//echo "pid_file: ".$pid_file."\n";

//function to check if the process exists
	function process_exists($file = false) {

		//set the default exists to false
		$exists = false;

		//check to see if the process is running
		if (file_exists($file)) {
			$pid = file_get_contents($file);
			if (posix_getsid($pid) === false) { 
				//process is not running
				$exists = false;
			}
			else {
				//process is running
				$exists = true;
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

//email queue enabled
	if ($_SESSION['email_queue']['enabled']['boolean'] != 'true') {
		echo "Email Queue is disabled in Default Settings\n";
		exit;
	}

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
		if (file_exists($file)) {
			unlink($pid_file);
		}

		//show the details to the user
		//echo "The process id is ".getmypid()."\n";
		//echo "pid_file: ".$pid_file."\n";

		//save the pid file
		file_put_contents($pid_file, getmypid());
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
    $sql = "select * from v_email_queue ";
    $sql .= "where (email_status = 'waiting' or email_status = 'trying') ";
    $sql .= "and hostname = :hostname ";
    $sql .= "order by domain_uuid asc ";
    $sql .= "limit :limit ";
    if (isset($hostname)) {
        $parameters['hostname'] = $hostname;
    }
    else {
        $parameters['hostname'] = gethostname();
    }
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
                //run process inline to see debug info
                echo $command."\n";
                $result = system($command);
                echo $result."\n";
            }
            else {
                //starts process rapidly doesn't wait for previous process to finish (used for production)
                $handle = popen($command." > /dev/null &", 'r'); 
                echo "'$handle'; " . gettype($handle) . "\n";
                $read = fread($handle, 2096);
                echo $read;
                pclose($handle);
            }
        }
    }

//remove the old pid file
	if (file_exists($pid_file)) {
		unlink($pid_file);
	}

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
