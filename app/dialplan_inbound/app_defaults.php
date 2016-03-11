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
	Portions created by the Initial Developer are Copyright (C) 2008-2010
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if there are multiple domains then update the public dir path to include the domain
	if ($domains_processed == 1) {
		if (count($_SESSION["domains"]) > 1) {
			if (is_dir($_SESSION['switch']['dialplan']['dir'].'/public')) {
				//clear out the old xml files
					$v_needle = '_v_';
					if($dh = opendir($_SESSION['switch']['dialplan']['dir'].'/public')) {
						$files = Array();
						while($file = readdir($dh)) {
							if($file != "." && $file != ".." && $file[0] != '.') {
								if(is_dir($dir . "/" . $file)) {
									//this is a directory
								} else {
									if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
										unlink($_SESSION['switch']['dialplan']['dir'].'/public/'.$file);
									}
								}
							}
						}
						closedir($dh);
					}
			}
		}
	}

//if the public directory doesn't exist then create it
	if ($domains_processed == 1) {
		if (strlen($_SESSION['switch']['dialplan']['dir']) > 0) {
			if (!is_dir($_SESSION['switch']['dialplan']['dir'].'/public')) { mkdir($_SESSION['switch']['dialplan']['dir'].'/public',0777,true); }
		}
	}

//if multiple domains then make sure that the dialplan/public/domain_name.xml file exists
	if (count($_SESSION["domains"]) > 1) {
		//make sure the public directory and xml file exist
		if (strlen($_SESSION['switch']['dialplan']['dir']) > 0) {
			if (!is_dir($_SESSION['switch']['dialplan']['dir'].'/public'.$_SESSION['domains'][$domain_uuid]['domain_name'])) {
				mkdir($_SESSION['switch']['dialplan']['dir'].'/public/'.$_SESSION['domains'][$domain_uuid]['domain_name'],0777,true);
			}
			$file = $_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$domain_uuid]['domain_name'].".xml";
			if (!file_exists($file)) {
				$fout = fopen($file,"w");
				$xml = "<include>\n";
				$xml .= "  <X-PRE-PROCESS cmd=\"include\" data=\"".$_SESSION['domains'][$domain_uuid]['domain_name']."/*.xml\"/>\n";
				$xml .= "</include>\n";
				fwrite($fout, $xml);
				fclose($fout);
				unset($xml,$file);
			}
		}
		}

?>