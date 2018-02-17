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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the device class
	class device {
		public $db;
		public $domain_uuid;
		public $template_dir;

		public function __construct() {
			//require_once "resources/classes/database.php";
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function get_domain_uuid() {
			return $this->domain_uuid;
		}

		public static function get_vendor($mac){
			//use the mac address to find the vendor
				$mac = preg_replace('#[^a-fA-F0-9./]#', '', $mac);
				$mac = strtolower($mac);
				switch (substr($mac, 0, 6)) {
				case "00085d":
					$device_vendor = "aastra";
					break;
				case "001873":
					$device_vendor = "cisco";
					break;
				case "a44c11":
					$device_vendor = "cisco";
					break;
				case "0021A0":
					$device_vendor = "cisco";
					break;
				case "30e4db":
					$device_vendor = "cisco";
					break;
				case "002155":
					$device_vendor = "cisco";
					break;
				case "68efbd":
					$device_vendor = "cisco";
					break;
				case "000b82":
					$device_vendor = "grandstream";
					break;
				case "00177d":
					$device_vendor = "konftel";
					break;
				case "00045a":
					$device_vendor = "linksys";
					break;
				case "000625":
					$device_vendor = "linksys";
					break;
				case "000e08":
					$device_vendor = "linksys";
					break;
				case "08000f":
					$device_vendor = "mitel";
					break;
				case "0080f0":
					$device_vendor = "panasonic";
					break;
				case "0004f2":
					$device_vendor = "polycom";
					break;
				case "00907a":
					$device_vendor = "polycom";
					break;
				case "64167f":
					$device_vendor = "polycom";
					break;
				case "000413":
					$device_vendor = "snom";
					break;
				case "001565":
					$device_vendor = "yealink";
					break;
				case "805ec0":
					$device_vendor = "yealink";
					break;
				case "00268B":
					$device_vendor = "escene";
					break;
				case "001fc1":
					$device_vendor = "htek";
					break;
				case "0C383E":
					$device_vendor = "fanvil";
					break;
				default:
					$device_vendor = "";
				}
				return $device_vendor;
		}

		public static function get_vendor_by_agent($agent){
			if ($agent) {
					$agent = strtolower($agent);
				//get the vendor
					if (preg_replace('/^.*?(aastra).*$/i', '$1', $agent) == "aastra") {
						return "aastra";
					}
					if (preg_replace('/^.*?(cisco).*$/i', '$1', $agent) == "cisco") {
						return "cisco";
					}
					if (preg_replace('/^.*?(cisco\/spa).*$/i', '$1', $agent) == "cisco/spa") {
						return "cisco-spa";
					}
					if (preg_replace('/^.*?(grandstream).*$/i', '$1', $agent) == "grandstream") {
						return "grandstream";
					}
					if (preg_replace('/^.*?(linksys).*$/i', '$1', $agent) == "linksys") {
						return "linksys";
					}
					if (preg_replace('/^.*?(polycom).*$/i', '$1', $agent) == "polycom") {
						return "polycom";
					}
					if (preg_replace('/^.*?(yealink).*$/i', '$1', $agent) == "yealink") {
						return "yealink";
					}
					if (preg_replace('/^.*?(vp530p).*$/i', '$1', $agent) == "vp530p") {
						return "yealink";
					}
					if (preg_replace('/^.*?(snom).*$/i', '$1', $agent) == "snom") {
						return "snom";
					}
					if (preg_match('/^.*?addpac.*$/i', $agent)) {
						return "addpac";
					}
					/*Escene use User-Agent string like `ES320VN2 v4.0 ...  or `ES206 v1.0 ...` */
					if (preg_match('/^es\d\d\d.*$/i', $agent)) {
						return "escene";
					}
					if (preg_match('/^.*?panasonic.*$/i', $agent)) {
						return "panasonic";
					}
					if (preg_match('/^.*?htek.*$/i', $agent)) {
						return "htek";
					}
					if (preg_replace('/^.*?(fanvil).*$/i', '$1', $agent) == "fanvil") {
						return "fanvil";
					}
					// unknown vendor
					return "";
				}
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
