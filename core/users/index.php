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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('user_view') || if_group("superadmin")) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//include the header
	require_once "includes/header.php";

//show the user list
	echo "<div align='center'>";
	echo "	<table width='100%' border='0'>";
	echo "		<tr>";
	echo "		<td align='left' width='100%'>";
	require_once "userlist.php";
	echo "				<br />";
	echo "				<br />";
	echo "				<br />";
	echo "			</td>";
	echo "		</tr>";
	echo "	</table>";
	echo "</div>";

//include the footer
	include "includes/footer.php";

?>
