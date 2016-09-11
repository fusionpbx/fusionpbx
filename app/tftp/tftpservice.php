#!/usr/bin/env php
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
	Sebastian Krupinski <sebastian@ksacorp.com>
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Sebastian Krupinski <sebastian@ksacorp.com>
*/

// define variables and constants
$appname = "fusionpbx-tftp";
$appdesc = "FusionPBX TFTP Service";
$pid=null;
$pidfile = (strpos(PHP_OS,"WIN") !== false) ? $_SERVER["TMP"]."\\$appname.pid" : "/var/run/$appname.pid";
$tftpservice_address="0.0.0.0";
$tftpservice_port=69;
$tftpservice_file_path=(strpos(PHP_OS,"WIN") !== false) ? $_SERVER["TMP"] : "/tmp";

function Service_Install()
{
	global $appname;
	global $appdesc;
	
	// install for specific os
	if (strpos(PHP_OS,"WIN") !== false)
	{	
		// check if we found the executable binary
		if (file_exists(PHP_BINARY))
		{
			win32_create_service( Array( 
				'service' => $appname, 
				'display' => $appdesc,
				'params' => __FILE__ . " --Service", 
				'path' => PHP_BINARY
			));

			//exec('sc create '.$appname.' type=own binPath="'.PHP_BINARY.' '.$_SERVER["SCRIPT_FILENAME"].'" DisplayName="'.$appdesc.'" start=auto');
			die($appdesc." was successfully installed.\n");
		}
		else 
		{
			die($appdesc." could not be installed because the php executable was not found.\n");
		}
	}
	else
	{
		// load required files
		require_once __DIR__.'/../../resources/config.php'; //required for database type

		// read template file
		$template=file_get_contents(dirname(__FILE__).'/resources/systemd.service.template');

		// service short name
		$template=str_replace('{$shortname}',$appname,$template);
		// service full name
		$template=str_replace('{$fullname}',$appdesc,$template);
		// service dependencies
		switch ($db_type) {
		case 'pgsql':
			$template=str_replace('{$database}','postgresql.service',$template);
			break;
		case 'mysql':
			$template=str_replace('{$database}','mariadb.service',$template);
			break;
        default:
			$template=str_replace('{$database}','',$template);
            break;
        }
		// script path
		$template=str_replace('{$scriptpath}',__FILE__,$template);
		// script folder
		$template=str_replace('{$scriptfolder}',dirname(__FILE__),$template);
		// script filename
		$template=str_replace('{$scriptfilename}',basename(__FILE__),$template);

		// write service file
		file_put_contents('/lib/systemd/system/'.$appname.'.service', $template);
		// reload systemd and enable service
		exec('systemctl daemon-reload');
		exec('systemctl enable '.$appname);

		die($appdesc." was successfully installed.\n");
	}
}

function Service_Uninstall()
{
	global $appname;
	global $appdesc;
	
	// uninstall for specific os
	if (strpos(PHP_OS,"WIN") !== false)
	{
		win32_delete_service($appname);
		//exec('sc delete "'.$appname.'"');
		die($appdesc." was successfully uninstalled.\n");
	}
	else
	{
		// stop service and disable in systemd
		exec('systemctl stop '.$appname);
		exec('systemctl disable '.$appname);
		// delete systemd service file
		unlink('/lib/systemd/system/'.$appname.'.service');
		die($appdesc." was successfully uninstalled.\n");
	}
}

function Service_Run()
{
	global $appname;
	global $appdesc;
	global $pid;
	global $pidfile;
	
	// check for existing process
	if (file_exists($pidfile)) {
		$pid = file_get_contents($pidfile);
		if (is_numeric($pid)) {
			if (strpos(PHP_OS,"WIN") !== false)
			{
				exec('tasklist -NH -FO TABLE -FI "PID eq '.$pid.'" 2>NUL', $data);
				foreach($data as $line)
				{
					if (strpos($line,$pid) !== false) die($appdesc." already running with process id ".$pid);
				}

				Service_Windows_Run();
			}
			else 
			{
				if (file_exists('/proc/'.$pid)) die($appdesc." already running with process id".$pid);

				Service_Linux_Run();
			}
		}
	}

}

function Service_Linux_Run()
{
	global $appname;
	global $appdesc;
	global $pid;
	global $pidfile;
	global $tftpservice_address;
	global $tftpservice_port;
	global $tftpservice_file_path;

	// write pid file
	file_put_contents($pidfile, getmypid());

	// load required files
	require_once __DIR__.'/../../resources/config.php';
	require_once 'resources/dbhelper.php';	

	// get service settings from database
	// connect to database
	$db = database::connect($db_type,$db_host,$db_port,$db_name,$db_username,$db_password);
	// get settings
	$s = database::get_table($db,'v_default_settings',array('default_setting_subcategory','default_setting_value'),array('default_setting_subcategory','LIKE','tftp_service_%'));
	// set local variables
	foreach ($s as $i) {
		switch ($i[0]) {
			case 'tftp_service_address':
				$tftpservice_address=$i[1];
				break;
			case 'tftp_service_port':
				$tftpservice_port=$i[1];
				break;
			case 'tftp_service_file_path':
				$tftpservice_file_path=$i[1];
				break;
		}
	}
	// disconnect from database
	unset($db);
	// destroy data
	unset($s);

	// load required files
	require_once 'resources/tftpservice.class.php';

	// start service
	$server = new tftpservice("udp://$tftpservice_address:$tftpservice_port", array("headless"=>true, "db_type"=>$db_type, "db_host"=>$db_host, "db_port"=>$db_port, "db_name"=>$db_name, "db_username"=>$db_username, "db_password"=>$db_password, "files_location"=>$tftpservice_file_path));
	if(!$server->loop($error, $user)) die("$error\n");
}

function Service_Windows_Run()
{
	global $appname;
	global $appdesc;
	global $pid;
	global $pidfile;
	global $tftpservice_address;
	global $tftpservice_port;
	global $tftpservice_file_path;

	// write pid file
	file_put_contents($pidfile, getmypid());

	// load required files
	require_once __DIR__.'/../../resources/config.php';
	require_once 'resources/dbhelper.php';	

	// get service settings from database
	// connect to database
	$db = database::connect($db_type,$db_host,$db_port,$db_name,$db_username,$db_password);
	// get settings
	$s = database::get_table($db,'v_default_settings',array('default_setting_subcategory','default_setting_value'),array('default_setting_subcategory','LIKE','tftp_service_%'));
	// set local variables
	foreach ($s as $i) {
		switch ($i[0]) {
			case 'tftp_service_address':
				$tftpservice_address=$i[1];
				break;
			case 'tftp_service_port':
				$tftpservice_port=$i[1];
				break;
			case 'tftp_service_file_path':
				$tftpservice_file_path=$i[1];
				break;
		}
	}
	// disconnect from database
	unset($db);
	// destroy data
	unset($s);

	// load required files
	require_once 'resources/tftpservice.class.php';

	// start service
	$server = new tftpservice("udp://$tftpservice_address:$tftpservice_port", array("headless"=>true, "db_type"=>$db_type, "db_host"=>$db_host, "db_port"=>$db_port, "db_name"=>$db_name, "db_username"=>$db_username, "db_password"=>$db_password, "files_location"=>$tftpservice_file_path));
	// signal running to service controller
	win32_start_service_ctrl_dispatcher($appname); 
    win32_set_service_status(WIN32_SERVICE_RUNNING);
	// execute run loop
	if(!$server->loop($error, $user)) die("$error\n");
	// signal stopped to service controller
	win32_set_service_status(WIN32_SERVICE_STOPPED); 
}

function Run()
{
	global $appname;
	global $appdesc;
	global $pid;
	global $pidfile;
	global $tftpservice_address;
	global $tftpservice_port;
	global $tftpservice_file_path;

	// check for existing process
	if (file_exists($pidfile)) {
		$pid = file_get_contents($pidfile);
		if (is_numeric($pid)) {
			if (strpos(PHP_OS,"WIN") !== false)
			{
				exec('tasklist -NH -FO TABLE -FI "PID eq '.$pid.'" 2>NUL', $data);
				foreach($data as $line)
				{
					if (strpos($line,$pid) !== false) die($appdesc." already running with process id ".$pid);
				}
			}
			else 
			{
				if (file_exists('/proc/'.$pid)) die($appdesc." already running with process id".$pid);
			}
		}
	}

	// write pid file
	file_put_contents($pidfile, getmypid());

	// load required files
	require_once __DIR__.'/../../resources/config.php';
	require_once 'resources/dbhelper.php';	

	// get service settings from database
	// connect to database
	$db = database::connect($db_type,$db_host,$db_port,$db_name,$db_username,$db_password);
	// get settings
	$s = database::get_table($db,'v_default_settings',array('default_setting_subcategory','default_setting_value'),array('default_setting_subcategory','LIKE','tftp_service_%'));
	// set local variables
	foreach ($s as $i) {
		switch ($i[0]) {
			case 'tftp_service_address':
				$tftpservice_address=$i[1];
				break;
			case 'tftp_service_port':
				$tftpservice_port=$i[1];
				break;
			case 'tftp_service_file_path':
				$tftpservice_file_path=$i[1];
				break;
		}
	}
	// disconnect from database
	unset($db);
	// destroy data
	unset($s);

	// load required files
	require_once 'resources/tftpservice.class.php';

	// start service
	$server = new tftpservice("udp://$tftpservice_address:$tftpservice_port", array('db_type'=>$db_type,'db_host'=>$db_host, "db_port"=>$db_port, "db_name"=>$db_name, "db_username"=>$db_username, "db_password"=>$db_password, "files_location"=>$tftpservice_file_path));
	echo $appdesc." has started.\n";
	if(!$server->loop($error, $user)) die("$error\n");
	echo $appdesc." has stopped.\n";
}

if (php_sapi_name() === 'cli') {
	if(isset($_SERVER["argv"][1])&&$_SERVER["argv"][1]=="--InstallService") 
		Service_Install(); // Install System Service
	elseif(isset($_SERVER["argv"][1])&&$_SERVER["argv"][1]=="--UninstallService")
		Service_Uninstall(); // Uninstall System Service
	elseif(isset($_SERVER["argv"][1])&&$_SERVER["argv"][1]=="--Service")
		Service_Run(); // Run as a Service
	else 
		Run(); // Run
}
?>
