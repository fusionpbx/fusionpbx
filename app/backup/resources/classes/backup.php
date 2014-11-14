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
	Copyright (C) 2014
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the backup class
	if (!class_exists('backup')) {
		class backup {
			public $result;
			public $domain_uuid;

			public function command($type, $file) {
				if ($type == "backup") {
					$path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
					$file = str_replace(array("/", "\\"),'',$file); //remove slashes to prevent changing the directory with the file name
					$format = substr($file,-3);
					if (strlen($file) == 3) {
						$file = 'backup_'.date('Ymd_His').'.'.$format;
					}
					if (count($_SESSION['backup']['path']) > 0) {
						//determine compression method
						switch ($format) {
							case "rar" : $cmd = 'rar a -ow -r '; break;
							case "zip" : $cmd = 'zip -r '; break;
							case "tbz" : $cmd = 'tar -jvcf '; break;
							default : $cmd = 'tar -zvcf ';
						}
						$cmd .= $path.'/'.$file.' ';
						foreach ($_SESSION['backup']['path'] as $value) {
							$cmd .= $value.' ';
						}
						return $cmd;
					}
					else {
						return false;
					}
				}
				if ($type == "restore") {
					$path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
					$file = str_replace(array("/", "\\"),'',$file); //remove slashes to prevent changing the directory with the file name
					$format = substr($file,-3);
					if (count($_SESSION['backup']['path']) > 0) {
						switch ($format) {
							case "rar" : $cmd = 'rar x -ow -o+ '.$path.'/'.$file.' /'; break;
							case "zip" : $cmd = 'umask 755; unzip -o -qq -X -K '.$path.'/'.$file.' -d /'; break;
							case "tbz" : $cmd = 'tar -xvpjf '.$path.'/'.$file.' -C /'; break;
							case "tgz" : $cmd = 'tar -xvpzf '.$path.'/'.$file.' -C /'; break;
							default: $valid_format = false;
						}
						return $cmd;
					}
					else {
						return false;
					}
				}
			}

			public function backup($type, $format) {
				$cmd = $this->command("backup", $format);
				exec($cmd);
				return $cmd;
			}

			public function restore($file) {
				$path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
				$format = substr($file,-3);
				switch ($format) {
					case "rar" : break;
					case "zip" : break;
					case "tbz" : break;
					case "tgz" : break;
					default: return false;
				}
				$cmd = $this->command("restore", $file);
				exec($cmd);
				return $cmd;
			}

			public function download($file) {
				$path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
				session_cache_limiter('public');
				$file = str_replace(array("/", "\\"),'',$file); //remove slashes to prevent changing the directory with the file name
				if (file_exists($path."/".$file)) {
					$fd = fopen($path."/".$file, 'rb');
					header("Content-Type: application/octet-stream");
					header("Content-Transfer-Encoding: binary");
					header("Content-Description: File Transfer");
					header('Content-Disposition: attachment; filename='.$file);
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
					header("Content-Length: ".filesize($path."/".$file));
					header("Pragma: no-cache");
					header("Expires: 0");
					ob_clean();
					fpassthru($fd);
					exit;
				}
			}
		}
	}

?>