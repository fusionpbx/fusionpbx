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
$tftpservice_fileslocation=(strpos(PHP_OS,"WIN") !== false) ? $_SERVER["TMP"] : "/tmp";

function Service_Install()
{
	// install for specific os
	if (strpos(PHP_OS,"WIN") !== false)
	{	
		// check if we found the executable binary
		if (file_exists(PHP_BINARY))
		{
			global $appname;
			global $appdesc;
			exec('sc create '.$appname.' type=own binPath="'.PHP_BINARY.' '.$_SERVER["SCRIPT_FILENAME"].'" DisplayName="'.$appdesc.'" start=auto');
			die($appdesc.' was successfully installed.\n');
		}
		else 
		{
			die($appdesc.' could not be installed because the php executable was not found.\n');
		}
	}
	else
	{
		require_once __DIR__.'/../../resources/config.php';

		// read template file
		$template=file_get_contents('resources/systemd.service.template');

		// service name
		$template=str_replace('\{\$name\}',$appdesc,$template);
		// service dependencies
		switch ($dbtype) {
		case 'pgsql':
			$template=str_replace('\{\$database\}','postgresql.service',$template);
			break;
		case 'mysql':
			$template=str_replace('\{\$database\}','mariadb.service',$template);
			break;
        default:
			$template=str_replace('\{\$database\}','',$template);
            break;
        }
		// script folder
		$template=str_replace('\{\$scriptfolder\}',dirname(__FILE__),$template);
		// script name
		$template=str_replace('\{\$scriptname\}',basename(__FILE__),$template);

		// write service file
		file_put_contents('/lib/systemd/system/'.$appname.'.service');

		die($appdesc.' was successfully installed.\n');
	}
}

function Service_Uninstall()
{
	// uninstall for specific os
	if (strpos(PHP_OS,"WIN") !== false)
	{
		global $appname;
		global $appdesc;
		exec('sc delete "'.$appname.'"');
		die($appdesc.' was successfully uninstalled.\n');
	}
	else
	{
		unlink('/lib/systemd/system/'.$appname.'.service');
		die($appdesc.' was successfully uninstalled.\n');
	}
}

function Run()
{
	global $appname;
	global $appdesc;
	global $pid;
	global $pidfile;
	global $tftpservice_address;
	global $tftpservice_port;
	global $tftpservice_fileslocation;

	// required for php 4.3.0
	/*
	declare(ticks = 1);

	function _process_term() { exit(0);}
	function _process_output($buffer) {  }
	*/

	// check for existing process
	if (file_exists($pidfile)) {
		$pid = file_get_contents($pidfile);
		if (is_numeric($pid)) {
			if (strpos(PHP_OS,"WIN") !== false)
			{
				exec('tasklist -NH -FO TABLE -FI "PID eq '.$pid.'" 2>NUL', $data);
				foreach($data as $line)
				{
					if (strpos($line,$pid) !== false) die($appdesc.' already running with process id '.$pid);
				}
			}
			else 
			{
				if (file_exists('/proc/'.$pid)) die($appdesc.' already running with process id'.$pid);
			}
		}
	}

	/*
	// fork process
	$pid = pcntl_fork();
	if ($pid < 0)
	die("fusionpbx-tftpservice process fork failed\n");
	else if ($pid) // parent
	die("fusionpbx-tftpservice process fork failed\n");

	posix_setsid();
	pcntl_signal(SIGTERM, "_process_term");
	pcntl_signal(SIGHUP, SIG_IGN);
	// redirect normal output to null function
	ob_start("_process_output");
	*/

	// write pid file
	file_put_contents($pidfile, getmypid());

	// load required files
	require_once __DIR__.'/../../resources/config.php';
	require_once 'resources/tftpservice.class.php';
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
			case 'tftp_service_fileslocation':
				$tftpservice_fileslocation=$i[1];
				break;
		}
	}
	// disconnect from database
	unset($db);
	// destroy data
	unset($s);

	// start service
	$server = new tftpservice("udp://$tftpservice_address:$tftpservice_port", array('db_type'=>$db_type,'db_host'=>$db_host, "db_port"=>$db_port, "db_name"=>$db_name, "db_username"=>$db_username, "db_password"=>$db_password, "files_location"=>$tftpservice_fileslocation));
	echo $appdesc.' has started.';
	if(!$server->loop($error, $user)) die("$error\n");
	echo $appdesc.' has stopped.';
}

// Install System Service
if(isset($_SERVER["argv"][1])&&$_SERVER["argv"][1]=="--InstallService") 
	Service_Install();
// Uninstall System Service
elseif(isset($_SERVER["argv"][1])&&$_SERVER["argv"][1]=="--UninstallService")
	Service_Uninstall();
// Run Service
else Run();


?>
