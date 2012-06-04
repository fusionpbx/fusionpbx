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
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (if_group("agent") || if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//show the header
	require_once "includes/header.php";

echo "<div align='right'>\n";
echo "	<input type='button' class='btn' name='' alt='reload' onclick=\"var f = document.getElementById('iframe1');f.src = f.src;\" value='Reload'>\n";
echo "	&nbsp;&nbsp;\n";
echo "</div>\n";

//show the iframe
	echo "<iframe src ='http://phone-1.viagogo.corp/mod/fifo_agents/fifo_agent/v_fifo_agent_edit.php' width='100%' id='iframe1' height='750px' frameborder=0>\n";
	echo "	<p>Your browser does not support iframes.</p>\n";
	echo "</iframe>\n";

//show the footer
	require_once "includes/footer.php";
?>
