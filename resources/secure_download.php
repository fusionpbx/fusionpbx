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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//clears if file exists cache
clearstatcache();


function getDownloadFilename($strfile) {
	// Get download file name and path
	//$basedir = "c:\\products\\";
    //$basedir = "/home/wwwbeta/secure/files/";
		$basedir = "c:/www/demo.netprofx.com/secure/files/";
	// Build and return download file name
	return $basedir . $strfile;
}

function DownloadFile($filename) {
	// Check filename
	if (empty($filename) || !file_exists($filename)) {
        echo "Error: file doesn't exist or is empty. <br>\n $filename";
		return FALSE;
	}

    $file_extension = strtolower(substr(strrchr($filename,"."),1));
     switch ($file_extension) {
         case "pdf": $ctype="application/pdf"; break;
         case "exe": $ctype="application/octet-stream"; break;
         case "zip": $ctype="application/zip"; break;
         case "doc": $ctype="application/msword"; break;
         case "xls": $ctype="application/vnd.ms-excel"; break;
         case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
         case "gif": $ctype="image/gif"; break;
         case "png": $ctype="image/png"; break;
         case "jpe": case "jpeg":
         case "jpg": $ctype="image/jpg"; break;
         default: $ctype="application/force-download";
     }

     //if (!file_exists($filename)) {
     //    die("NO FILE HERE<br>$filename");
     //}

	// Create download file name to be displayed to user
	$saveasname = basename($filename);

    header("Expires: 0");
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false);
    header("Content-Type: $ctype");
    header("Content-Disposition: attachment; filename=\"".basename($filename)."\";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".@filesize($filename));

    set_time_limit(0);
    @readfile($filename) or die("File not found.");

    // Done
	return TRUE;
}



?>
