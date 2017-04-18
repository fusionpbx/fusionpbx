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

// load required files
require_once 'tftpserver.class.php';

class tftpservice extends TFTPServer
{
	private $_headless=true;
	private $_debug=false;
	private $_dbtype;
	private $_dbhost;
	private $_dbport;
	private $_dbname;
	private $_dbusername;
	private $_dbpassword;
	private $_fileslocation;

	function __construct($server_url, $config)
	{
		parent::__construct($server_url);
		if (isset($config['headless'])) $this->_headless=$config['headless'];
		if (isset($config['debug'])) $this->_debug=$config['debug'];
		if (isset($config['db_type'])) $this->_dbtype=$config['db_type'];
		if (isset($config['db_host'])) $this->_dbhost=$config['db_host'];
		if (isset($config['db_port'])) $this->_dbport=$config['db_port'];
		if (isset($config['db_name'])) $this->_dbname=$config['db_name'];
		if (isset($config['db_username'])) $this->_dbusername=$config['db_username'];
		if (isset($config['db_password'])) $this->_dbpassword=$config['db_password'];
		if (isset($config['files_location'])) $this->_fileslocation=$config['files_location'];

		if (!file_exists($_fileslocation)) {
			$_fileslocation = (strpos(PHP_OS,"WIN") !== false) ? $_SERVER["TMP"] : "/tmp";
		}
	}
  
	private function log($client, $level, $message) {   
		if(!$this->_headless && ($level!='D' || $this->_debug)) {
		echo 
			date("H:i:s") . " " .
			$level . " " .
			$client . " " .
			$message . "\n";
		}
		
	}
	
	public function get($client, $filepath, $mode)
	{
		$this->log($client,"N", "Requested File ".$filepath);

		try {
			$regex_filter='/provision\/(?<domain>\b(?:(?-)[A-Za-z0-9-\_]{1,63}(?-)\.)+[A-Za-z]{1,63}\b|\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b)\/(?<mac>\b(?:[0-9a-fA-F]{2}(?:\-|\:)?){6}\b)/';
			
			preg_match($regex_filter,$filepath,$regex_matches);

			// check if filepath is in a specific format and respond acordingly
			if ($regex_matches['domain']&&$regex_matches['mac'])
			{
				// generate file from db
				$filedata = $this->generate_file($client,$regex_matches['domain'],$regex_matches['mac']);
			}
			else 
			{
				// retrieve file from disk
				$filedata = $this->retrieve_file($client,$filepath);
			}

			if($filedata !== false)
			{
				$this->log($client,"N", "Transmitting File ".$filepath);

				return $filedata;
			}
			else
			{
				return false;
			}
		}
		catch (Exception $exception)
		{
			$this->log($client,"E", "Exception: ".$exception->getMessage());
			return false;
		}
	}

	public function generate_file($client, $domain, $mac)
	{	
		// load required files 
		require_once __DIR__.'/dbhelper.php';
		require_once __DIR__.'/../../../resources/functions.php';
		require_once __DIR__.'/../../../resources/classes/template.php';
		require_once __DIR__.'/../../provision/resources/classes/provision.php';
		
		$this->log($client,"D", "Generating File ".$domain." ".$mac);
		
		// connect to database
		$db = database::connect($this->_dbtype,$this->_dbhost,$this->_dbport,$this->_dbname,$this->_dbusername,$this->_dbpassword);
		
		// get domain uuid
		$domain_uuid = database::get_value($db,'v_domains','domain_uuid','domain_name',$domain);
		
		// set temporary folder for template engine
		$_SESSION['server']['temp']['dir'] = (strpos(PHP_OS,"WIN") !== false) ? $_SERVER["TMP"] : "/tmp";
		
		// update device provisioned status
		$data=array('device_provisioned_date'=>date("Y-m-d H:i:s"),'device_provisioned_method'=>'tftp','device_provisioned_ip'=>$client);
		database::set_row($db,'v_devices',$data,'device_mac_address',$mac);
		
		// generate file
		$prov = new provision;
		$prov->db = $db;
		$prov->domain_uuid = $domain_uuid;
		$prov->mac = $mac;
		$data = $prov->render();

		// return data or false
		if($data === false) 
		{
			$this->log($client,"W", "Generating File Failed ".$domain." ".$mac);
			return false;
		}
		else
		{
			return $data;
		}
	}

	public function retrieve_file($client, $path){
	
		$this->log($client,"D", "Retrieve File ".$path);	
		// check for reletive path directive
		if(strstr($path, "../") != false || strstr($path, "/..") != false) return false;
		// combine base and path
		$path = rtrim($this->_fileslocation,'/').'/'.ltrim($path,'/');
		if(substr($path, 0, strlen($this->_fileslocation)) != $this->_fileslocation) return false;
		// read contents
		if($this->_debug) $this->log($client,"D", "Reading File ".$path);
		$data = @file_get_contents($path);
		// return data or false
		if($data === false)
		{
			$this->log($client,"W", "Retrieving File Failed ".$path);
			return false;
		}
		else
		{
			return $data;
		}

	}
}

?>