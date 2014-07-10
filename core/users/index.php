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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('user_view') || if_group("superadmin")) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_manager'];

//show the user list
	echo "<div align='center'>";
	echo "	<table width='100%' border='0'>";
	echo "		<tr>";
	echo "		<td align='left' width='100%'>";
	require_once "users.php";
	echo "				<br />";
	echo "				<br />";
	echo "				<br />";
	echo "			</td>";
	echo "		</tr>";
	echo "	</table>";
	echo "</div>";

//include the footer
	include "resources/footer.php";

?>
