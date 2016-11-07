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
 Portions created by the Initial Developer are Copyright (C) 2008-2012
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Konrad <konrd@yahoo.com>
 */
//functions for the MAC Address
include "root.php";

//define the wizard class
	class wizard{
		public $domain_uuid;
		public $template_dir;
		
		//determine if the mac is valid
		public static function is_valid_mac($mac) {
		  // 01:23:45:67:89:ab
		  if (preg_match('/^([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}$/', $mac))
		    return true;
		  // 01-23-45-67-89-ab
		  if (preg_match('/^([a-fA-F0-9]{2}\-){5}[a-fA-F0-9]{2}$/', $mac))
		    return true;
		  // 0123456789ab
		  else if (preg_match('/^[a-fA-F0-9]{12}$/', $mac))
		    return true;
		  // 0123.4567.89ab
		  else if (preg_match('/^([a-fA-F0-9]{4}\.){2}[a-fA-F0-9]{4}$/', $mac))
		    return true;
		  else
		    return false;
		}
		
		public function get_domain_uuid() {
			return $this->domain_uuid;
		}
		
		//normalize the mac address
		public static function normalize_mac($mac)	{
		  // remove any dots
		  $mac =  str_replace(".", "", $mac);
		
		  // remove any dashes
		  $mac =  str_replace("-", "", $mac);
		
		  // remove any colons
		  $mac =  str_replace(":", "", $mac);
		  
		  // lowercase
		  $mac = strtolower($mac);
		
		  return $mac;
		}
		
		public function get_template_dir() {
			//set the default template directory
				if (PHP_OS == "Linux") {
					//set the default template dir
						if (strlen($this->template_dir) == 0) {
							if (file_exists('/etc/fusionpbx/resources/templates/provision')) {
								$this->template_dir = '/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				} elseif (PHP_OS == "FreeBSD") {
					//if the FreeBSD port is installed use the following paths by default.
						if (file_exists('/usr/local/etc/fusionpbx/resources/templates/provision')) {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = '/usr/local/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
						else {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				} elseif (PHP_OS == "NetBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				} elseif (PHP_OS == "OpenBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				} else {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}

			//check to see if the domain name sub directory exists
				if (is_dir($this->template_dir."/".$_SESSION["domain_name"])) {
					$this->template_dir = $this->template_dir."/".$_SESSION["domain_name"];
				}

			//return the template directory
				return $this->template_dir;
		}
	}
?>
