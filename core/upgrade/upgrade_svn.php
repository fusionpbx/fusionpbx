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
	Mark J Crane <markjcrane@fusionpbx.com>
*/
/*
   $mtime = microtime();
   $mtime = explode(" ",$mtime);
   $mtime = $mtime[1] + $mtime[0];
   $start_time = $mtime;
*/

include "root.php";
require_once "includes/require.php";
require_once "includes/phpsvnclient/phpsvnclient.php";

if (!isset($display_results)) {
	$display_results = true;
}

if (strlen($_SERVER['HTTP_USER_AGENT']) > 0) {
	require_once "includes/checkauth.php";
	if (permission_exists('upgrade_svn') || if_group("superadmin")) {
		//echo "access granted";
	}
	else {
		echo "access denied";
		exit;
	}
}
else {
	$display_results = false; //true false
	//$display_type = 'csv'; //html, csv
}

ini_set('display_errors', '0');
ini_set(max_execution_time,3600);
clearstatcache();

if ($display_results) {
	require_once "includes/header.php";
}

$svn_url = 'http://fusionpbx.googlecode.com/svn/';
$svn_path = '/trunk/fusionpbx/';

//set path_array
	$sql = "";
	$sql .= "select * from v_src ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$path = trim($row["path"]);
		$path_array[$path][type] = $row["type"];
		$path_array[$path][last_mod] = $row["last_mod"];
	}
	unset ($prep_statement);

$svn  = new phpsvnclient($svn_url);
//$svn_version = $svn->getVersion();
$svn_directory_tree = $svn->getDirectoryTree($svn_path);

if ($display_results) {
	echo "<table width='100%' border='0' cellpadding='20' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>Type</th>\n";
	echo "<th>Last Modified</th>\n";
	echo "<th>Path</th>\n";
	echo "<th>Status/Size</th>\n";
	echo "<th>MD5 file</th>\n";
	echo "<th>MD5 xml</th>\n";
	echo "<th>Action</th>\n";
	echo "<tr>\n";
}

//$db->beginTransaction();
foreach ($svn_directory_tree as &$row) {
	$md5_match = false;
	$xml_type = $row[type];
	$xml_relative_path = trim(str_replace(trim($svn_path,'/'),"",$row[path]));
	$xml_last_mod = $row[last_mod];
	$new_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH . $xml_relative_path;

	if (file_exists($new_path)) {
		$exists = true;
	}
	else {
		$exists = false;
	}	
	
	if ( $xml_type == 'file' ) {
		$xml_file_path = trim($row[path]); //we need this to download the file from svn
		$md5_xml = $row[md5];
		if ($exists) {
			$md5_file = md5_file($new_path);
			if ($md5_xml == $md5_file){ 
				$md5_match = true; 
			}
		}
		else { 
			$md5_match = false;//???
			$md5_file = '';
		}
	}
	else {
		$md5_xml = '';//directory has no md5
	}

	if (strlen($xml_relative_path) > 0) {
		if ($display_results) {
			if ($xml_type == 'file' && !$md5_match) {
				echo "<tr>\n";
				echo "<td class='row_style1'>$xml_type</td>\n";
				echo "<td class='row_style1'>$xml_last_mod</td>\n";
				echo "<td class='row_style1'>$xml_relative_path</td>\n";
				echo "<td class='row_style1'>$exists</td>\n";
				//echo "<td class='row_style1'>$xml_size</td>\n";
				echo "<td class='row_style1'>$md5_file</td>\n";
				echo "<td class='row_style1'>$md5_xml</td>\n";
				echo "<td class='row_style1'>$md5_match </td>\n";
				//file_get_contents($svn_url.$svn_path.$xml_relative_path);</td>\n";
				echo "<td class='row_style1'>\n";
			}
		}

		//update the v_scr data
		if ($xml_type=='file' && strlen($path_array[$xml_relative_path]['type']) == 0) { 
			//insert a new record into the src table
			$sql ="";
			$sql .= "insert into v_src ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "type, ";
			$sql .= "last_mod, ";
			$sql .= "path ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$xml_type', ";
			$sql .= "'$xml_last_mod', ";
			$sql .= "'$xml_relative_path' ";
			$sql .= ")";
			//echo "$sql<br />\n";
		} 
		else {
			if ($xml_type=='file' && !$md5_match) {//update changed files
				//update the src table
				$sql =""; 
				$sql .= "update v_src set ";
				$sql .= "type = '$xml_type', ";
				$sql .= "last_mod = '$xml_last_mod' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and path = '$xml_relative_path' ";
			}
		}
		//if the path exists and is a file
		if ($exists && $xml_type == 'file') {
			//the md5 of the xml file and the local file do not match
			if ($md5_match) {
				if ($display_results) {
					//echo "current "; //the file is up to date
				}
			}
			else {
/*				if ($xml_file_path == '/core/upgrade/upgrade_svn.php' ) {
					if ($display_results) {
						echo "white list"; //the file is up to date
					}
					continue;
				}
*/				//get the remote file contents
				$file_content = $svn->getFile($xml_file_path);
				
				//the md5 of the local file and the remote content match
				if (md5_file($new_path) == md5($file_content)) {
					if ($display_results) {
						//echo "current 2 "; //the file is up to date
					}
				}
				else {
					//make sure the string matches the file md5 that was recorded.
					if (strlen($file_content) > 0) {
						$tmp_fh = fopen($new_path, 'w');
						fwrite($tmp_fh, $file_content);
						fclose($tmp_fh);
					}

					//display the results
					if ($display_results) {
						echo "<strong style='color: #FF0000;'> ";
						if (is_writable($new_path)) {
							echo "updated ";
						}
						else {
							echo "not writable ";
						}
						echo "</strong>";
					}
				}
			}
			//unset the variable
			unset($file_content);
		}
		else {
			
			//if the path does not exist create it and then add it to the database
			//echo "file is missing |";
			if ($xml_type == 'directory' && !$exists) {
				//make sure the directory exists
					mkdir (dirname($new_path), 0755, true);
			}
			if ($xml_type == 'file') {
				//make sure the directory exists
					if (!is_dir(dirname($new_path))){
						mkdir (dirname($new_path), 0755, true);
					}

				//get the remote file contents
					$file_content = $svn->getFile($xml_file_path);

				//make sure we got some data.
					if (strlen($file_content) > 0) {
						$tmp_fh = fopen($new_path, 'w');
						fwrite($tmp_fh, $file_content);
						fclose($tmp_fh);
					}

					if ($display_results) {
						echo "<strong style='color: #FF0000;'> ";
						if (is_writable($new_path)) {
							echo "added/restored";
						}
						else {
							echo "not writable ";
						}
						echo "</strong>";
						//echo "<br />\n";
					}
				//unset the variable
					unset($file_content);
			}
		}

		if ($display_results) {
			if ($xml_type == 'file' && !$md5_match) {
				echo "&nbsp;";
				echo "</td>\n";
				echo "<tr>\n";
			}
		}
		//update the database
		if (strlen($sql) > 0) {
			$db->exec(check_sql($sql));
			//echo "$sql<br />\n";
		}
		unset($sql);
	}
}
//$db->commit();
//clearstatcache();
if ($display_results) {
	echo "</table>\n";
	require_once "includes/footer.php";
}
/*
   $mtime = microtime();
   $mtime = explode(" ",$mtime);
   $mtime = $mtime[1] + $mtime[0];
   $end_time = $mtime;
   $total_time = ($end_time - $start_time);
   echo "This page was created in ".$total_time." seconds";
*/
?>