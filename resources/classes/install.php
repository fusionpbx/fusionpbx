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
	Copyright (C) 2010-2014
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the install class
	class install {

		var $result;
		var $domain_uuid;
		var $domain;
		var $switch_conf_dir;
		var $switch_scripts_dir;
		var $switch_sounds_dir;

		//$option '-n' --no-clobber
		public function recursive_copy($src, $dst, $option = '') {
			if (file_exists('/bin/cp')) {
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') {
					//copy -R recursive, preserve attributes for SUN
					exec ('cp -Rp '.$src.'/* '.$dst);
				} else {
					//copy -R recursive, -L follow symbolic links, -p preserve attributes for other Posix systemss
					exec ('cp -RLp '.$option.' '.$src.'/* '.$dst);
				}
			}
			else {
				$dir = opendir($src);
				if (!$dir) {
					if (!mkdir($src, 0755, true)) {
						throw new Exception("recursive_copy() source directory '".$src."' does not exist.");
					}
				}
				if (!is_dir($dst)) {
					if (!mkdir($dst, 0755, true)) {
						throw new Exception("recursive_copy() failed to create destination directory '".$dst."'");
					}
				}
				while(false !== ($file = readdir($dir))) {
					if (($file != '.') && ($file != '..')) {
						if (is_dir($src.'/'.$file)) {
							$this->recursive_copy($src.'/'.$file, $dst.'/'.$file);
						}
						else {
							//copy only missing files -n --no-clobber
								if ($option == '-n') {
									if (!file_exists($dst.'/'.$file)) {
										copy($src.'/'.$file, $dst.'/'.$file);
										//echo "copy(".$src."/".$file.", ".$dst."/".$file.");<br />\n";
									}
								}
								else {
									copy($src.'/'.$file, $dst.'/'.$file);
								}
						}
					}
				}
				closedir($dir);
			}
		}

		function recursive_delete($dir) {
			if (file_exists('/bin/rm')) {
				 exec ('rm -Rf '.$dir.'/*');
			}
			else {
				foreach (glob($dir) as $file) {
					if (is_dir($file)) {
						$this->recursive_delete("$file/*");
						rmdir($file);
						//echo "rm dir: ".$file."\n";
					} else {
						//echo "delete file: ".$file."\n";
						unlink($file);
					}
				}
			}
			clearstatcache();
		}

		function copy() {
			$this->copy_scripts();
			//$this->copy_sounds();
		}

		function copy_conf() {
			if (file_exists($this->switch_conf_dir)) {
				//make a backup copy of the conf directory
					$src_dir = $this->switch_conf_dir;
					$dst_dir = $this->switch_conf_dir.'.orig';
					if (is_readable($src_dir)) {
						$this->recursive_copy($src_dir, $dst_dir);
						$this->recursive_delete($src_dir);
					}
					else {
						if ($src_dir != "/conf") {
							mkdir($src_dir, 0774, true);
						}
					}
				//make sure the conf directory exists
					if (!is_dir($this->switch_conf_dir)) {
						if (!mkdir($this->switch_conf_dir, 0774, true)) {
							throw new Exception("Failed to create the switch conf directory '".$this->switch_conf_dir."'. ");
						}
					}
				//copy resources/templates/conf to the freeswitch conf dir
				// added /examples/ into the string
					if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')){
						$src_dir = "/usr/share/examples/fusionpbx/resources/templates/conf";
					}
					else {
						
						$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
					}
					$dst_dir = $this->switch_conf_dir;
					if (is_readable($dst_dir)) {
						$this->recursive_copy($src_dir, $dst_dir);
					}
					//print_r($install->result);
			}
		}
		// added /examples/ into the string
		function copy_scripts() {
			if (file_exists($this->switch_scripts_dir)) {
				if (file_exists('/usr/share/examples/fusionpbx/resources/install/scripts')){
					$src_dir = '/usr/share/examples/fusionpbx/resources/install/scripts';
				}
				else {
					$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/scripts';
				}
				$dst_dir = $this->switch_scripts_dir;
				if (is_readable($this->switch_scripts_dir)) {
					$this->recursive_copy($src_dir, $dst_dir, "-n");
					unset($src_dir, $dst_dir);
				}
				chmod($dst_dir, 0774);
			}
		}
		
		//function copy_sounds() {
		//	if (file_exists($this->switch_sounds_dir)) {
		//			if (file_exists('/usr/share/examples/fusionpbx/resources/install/sounds/en/us/callie/custom/')){
		//			$src_dir = '/usr/share/examples/fusionpbx/resources/install/sounds/en/us/callie/custom/';
					// changes the output dir for testing
		//			$dst_dir = $this->switch_sounds_dir.'/en/us/fusionpbx/custom/';
		//		}
		//		else {
		//			$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sounds/en/us/callie/custom/';
		//			$dst_dir = $this->switch_sounds_dir.'/en/us/callie/custom/';
		//		}
		//		$this->recursive_copy($src_dir, $dst_dir, "-n");
		//		if (is_readable($this->switch_sounds_dir)) {
		//			$this->recursive_copy($src_dir, $dst_dir);
		//			chmod($dst_dir, 0664);
		//		}
		//	}
		//}
	}

//how to use the class
	//$install = new install;
	//$install->domain_uuid = $domain_uuid;
	//$install->switch_conf_dir = $switch_conf_dir;
	//$install->switch_scripts_dir = $switch_scripts_dir;
	//$install->switch_sounds_dir = $switch_sounds_dir;
	//$install->copy();
	//print_r($install->result);
?>