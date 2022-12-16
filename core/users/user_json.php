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
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('user_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	if (isset($_GET["search"])) {
		$search =  strtolower($_GET["search"]);
	}

//check to see if contact details are in the view
	$sql = "select * from view_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters = null;
	$database = new database;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$row = $database->select($sql, $parameters, 'row');
	if (isset($row['contact_organization'])) {
		$show_contact_fields = true;
	}
	else {
		$show_contact_fields = false;
	}
	unset($parameters);

//get the list
	$sql = "select domain_name, domain_uuid, user_uuid, username, group_names, ";
	if ($show_contact_fields) {
		$sql .= "contact_organization,contact_name, ";
	}
	$sql .= "cast(user_enabled as text) ";
	$sql .= "from view_users ";
	$sql .= "where true ";
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(username) like :search ";
		$sql .= "	or lower(group_names) like :search ";
		$sql .= "	or lower(contact_organization) like :search ";
		$sql .= "	or lower(contact_name) like :search ";
		//$sql .= "	or lower(user_status) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	if ($_GET['show'] == "all" && permission_exists('user_all')) {

	}
	else {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$sql .= "and ( ";
	$sql .= "	group_level <= :group_level ";
	$sql .= "	or group_level is null ";
	$sql .= ") ";
	$parameters['group_level'] = $_SESSION['user']['group_level'];
	$sql .= order_by($order_by, $order, 'username', 'asc');
	$sql .= "limit 300\n";
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//return the contacts as json
	if (is_array($users)) {
		echo json_encode($users, true);
	}

?>
