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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('xml_cdr_statistics')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//include the xml cdr statistics backend
	require_once "xml_cdr_statistics_inc.php";

//set the http header
	header('Content-type: application/octet-binary');
	header('Content-Disposition: attachment; filename=cdr-statistics.csv');

//show the column names on the first line
	$z = 0;
	foreach ($stats[1] as $key => $val) {
		if ($z == 0) {
			echo '"'.$key.'"';
		}
		else {
			echo ',"'.$key.'"';
		}
		$z++;
	}
	echo "\n";

//add the values to the csv
	$x = 0;
	foreach ($stats as $row) {
		$z = 0;
		foreach ($row as $key => $val) {
			if ($z == 0) {
				echo '"'.$stats[$x][$key].'"';
			}
			else {
				echo ',"'.$stats[$x][$key].'"';
			}
			$z++;
		}
		echo "\n";
		$x++;
	}

?>
