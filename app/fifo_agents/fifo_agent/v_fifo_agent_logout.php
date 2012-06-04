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

//agent logout
	$sql = "";
	$sql .= "delete from v_fifo_agents ";
	$sql .= "where agent_username = '".$_SESSION["username"]."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	unset($sql);

//agent status log login 
	$agent_status = '0'; //login
	$sql = "insert into v_fifo_agent_status_logs ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "username, ";
	$sql .= "agent_status, ";
	$sql .= "add_date ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'".$_SESSION["username"]."', ";
	$sql .= "'$agent_status', ";
	$sql .= "now() ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);

//redirect
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=v_fifo_agent_edit.php\">\n";
	echo "<div align='center'>\n";
	echo "Logout Complete\n";
	echo "</div>\n";

require_once "includes/footer.php";
return;

?>