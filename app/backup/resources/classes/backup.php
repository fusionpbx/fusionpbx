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

//define the backup class
	if (!class_exists('backup')) {
		class backup {
			//variables
			public $result;
			public $domain_uuid;

			public function command($type, $format) {
				global $db;
				if ($type == "backup") {
					$backup_path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
					$backup_file = 'backup_'.date('Ymd_His').'.'.$file_format;
					if (count($_SESSION['backup']['path']) > 0) {
						//determine compression method
						switch ($format) {
							case "rar" : $cmd = 'rar a -ow -r '; break;
							case "zip" : $cmd = 'zip -r '; break;
							case "tbz" : $cmd = 'tar -jvcf '; break;
							default : $cmd = 'tar -zvcf ';
						}
						$cmd .= $backup_path.'/'.$backup_file.' ';
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
					$backup_path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
					$backup_file = 'backup_'.date('Ymd_His').'.'.$file_format;
					if (count($_SESSION['backup']['path']) > 0) {
						switch ($format) {
							case "rar" : $cmd = 'rar x -ow -o+ '.$backup_path.'/'.$backup_file.' /'; break;
							case "zip" : $cmd = 'umask 755; unzip -o -qq -X -K '.$backup_path.'/'.$backup_file.' -d /'; break;
							case "tbz" : $cmd = 'tar -xvpjf '.$backup_path.'/'.$backup_file.' -C /'; break;
							case "tgz" : $cmd = 'tar -xvpzf '.$backup_path.'/'.$backup_file.' -C /'; break;
							default: $valid_format = false;
						}
						return $cmd;
					}
					else {
						return false;
					}
				}
			}

		}
	}

?>