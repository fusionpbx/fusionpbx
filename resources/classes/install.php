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

//define the install class
	class install {

		var $result;
		var $domain_uuid;
		var $domain;
		var $switch_conf_dir;
		var $switch_scripts_dir;
		var $switch_sounds_dir;

		//$option '-n' --no-clobber
		function recursive_copy($src, $dst, $option = '') {

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

		function recursive_delete($dir) {
			if (strlen($dir) > 0) {
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
			$this->copy_sounds();
			$this->copy_swf();
		}

		function copy_conf() {
			clearstatcache();
			if (file_exists($this->switch_conf_dir)) {
				//make a backup copy of the conf directory
					$src_dir = $this->switch_conf_dir;
					$dst_dir = $this->switch_conf_dir.'.orig';
					if (is_readable($src_dir)) {
						if (substr(strtoupper(PHP_OS), 0, 3) == "WIN") {
							$this->recursive_copy($src_dir, $dst_dir);
							$this->recursive_delete($this->switch_conf_dir);
						}
						else {
							exec ('mv '.$src_dir.' '.$dst_dir);
							//exec ('cp -RLp '.$src_dir.' '.$dst_dir);
						}
					} 
					else {
						if ($src_dir != "/conf") {
							mkdir($src_dir, 0755, true);
						}
					}
				//make sure the conf directory exists
					if (!mkdir($this->switch_conf_dir, 0755, true)) {
						//throw new Exception("Failed to create the switch conf directory '".$this->switch_conf_dir."'. ");
					}
				//copy resources/templates/conf to the freeswitch conf dir
					if (file_exists('/usr/share/fusionpbx/resources/templates/conf')){
						$src_dir = "/usr/share/fusionpbx/resources/templates/conf";
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

		function copy_scripts() {
			clearstatcache();
			if (file_exists($this->switch_scripts_dir)) {
				if (file_exists('/usr/share/fusionpbx/resources/install/scripts')){
					$src_dir = '/usr/share/fusionpbx/resources/install/scripts';
				}
				else {
					$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/scripts';
				}
				$dst_dir = $this->switch_scripts_dir;
				if (is_readable($this->switch_scripts_dir)) {
					$this->recursive_copy($src_dir, $dst_dir, "-n");
					unset($src_dir, $dst_dir);
				}
			}
		}

		function copy_sounds() {
			clearstatcache();
			if (file_exists($this->switch_sounds_dir)) {
				$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sounds/en/us/callie/custom/8000';
				$dst_dir = $this->switch_sounds_dir.'/en/us/callie/custom/8000';
				if (is_readable($this->switch_sounds_dir)) {
					if ($handle = opendir($src_dir)) {
						$i = 0;
						$files = array();
						while (false !== ($file = readdir($handle))) {
							if ($file != "." && $file != ".." && is_file($src_dir.'/'.$file)) {
								if (!file_exists($dst_dir.'/'.$file) ) {
									//copy the file if it does not exist in the destination directory
									if (copy($src_dir.'/'.$file, $dst_dir.'/'.$file)) {
										$this->result['copy']['sounds']['8000'][] = "copied from ".$src_dir."/".$file." to ".$dst_dir."/".$file."<br />\n";
									}
									else {
										$this->result['copy']['sounds']['8000'][] = "copy failed from ".$src_dir."/".$file." to ".$dst_dir."/".$file."<br />\n";
									}
								}
							}
						}
					}

					$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sounds/en/us/callie/custom/16000';
					$dst_dir = $this->switch_sounds_dir.'/en/us/callie/custom/16000';
					if ($handle = opendir($src_dir)) {
						$i = 0;
						$files = array();
						while (false !== ($file = readdir($handle))) {
							if ($file != "." && $file != ".." && is_file($src_dir.'/'.$file)) {
								if (!file_exists($dst_dir.'/'.$file) ) {
									//copy the file if it does not exist in the destination directory
									if (copy($src_dir.'/'.$file, $dst_dir.'/'.$file)) {
										$this->result['copy']['sounds']['16000'][] = "copied from ".$src_dir."/".$file." to ".$dst_dir."/".$file."<br />\n";
									}
									else {
										$this->result['copy']['sounds']['16000'][] = "copy failed from ".$src_dir."/".$file." to ".$dst_dir."/".$file."<br />\n";
									}
								}
							}
						}
					}
				}
			}
		}

		function copy_swf() {
			clearstatcache();
			if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/recordings')) {
				$file = "slim.swf";
				$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/htdocs';
				$dst_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/recordings';
				if (copy($src_dir.'/'.$file, $dst_dir.'/'.$file)) {
					$this->result['copy']['swf'][] = "copied from ".$src_dir."/".$file." to ".$dst_dir."/".$file."<br />\n";
				}
				else {
					$this->result['copy']['swf'][] = "copy failed from ".$src_dir."/".$file." to ".$dst_dir."/".$file."<br />\n";
				}
			}
		}

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