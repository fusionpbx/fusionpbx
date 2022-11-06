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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get posted data
	if (is_array($_POST['contacts'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$name = $_POST['name'];
	}

//retrieve current user's assigned groups (uuids)
	foreach ($_SESSION['groups'] as $group_data) {
		$user_group_uuids[] = $group_data['group_uuid'];
	}

//add user's uuid to group uuid list to include private (non-shared) contacts
	$user_group_uuids[] = $_SESSION["user_uuid"];

//add the search term
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the list of contacts
	$sql = "select *, ";
	$sql .= "( ";
	$sql .= "	select a.contact_attachment_uuid from v_contact_attachments as a ";
	$sql .= "	where a.contact_uuid = c.contact_uuid and a.attachment_primary = 1 ";
	$sql .= ") as contact_attachment_uuid ";
	$sql .= "from v_contacts as c ";
	$sql .= "where true ";
	if ($_GET['show'] != "all" || !permission_exists('contact_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!permission_exists('contact_domain_view')) {
		$sql .= "and ( "; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	contact_uuid in ( ";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where ";
		if (is_array($user_group_uuids) && @sizeof($user_group_uuids) != 0) {
			foreach ($user_group_uuids as $index => $user_group_uuid) {
				if (is_uuid($user_group_uuid)) {
					$sql_where_or[] = "group_uuid = :group_uuid_".$index;
					$parameters['group_uuid_'.$index] = $user_group_uuid;
				}
			}
			if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
				$sql .= " ( ".implode(' or ', $sql_where_or)." ) ";
			}
			unset($sql_where_or, $index, $user_group_uuid);
		}
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "	) ";
		$sql .= "	or contact_uuid in ( ";
		$sql .= "		select contact_uuid from v_contact_users ";
		$sql .= "		where user_uuid = :user_uuid ";
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "";
		$sql .= "	) ";
		$sql .= ") ";
		$parameters['user_uuid'] = $_SESSION['user_uuid'];
	}
	if (isset($search)) {
		if (is_numeric($search)) {
			$sql .= "and contact_uuid in ( ";
			$sql .= "	select contact_uuid from v_contact_phones ";
			$sql .= "	where phone_number like :search ";
			$sql .= ") ";
		}
		else {
			//open container
				$sql .= "and ( ";
			//search contact
				$sql .= "contact_uuid in ( ";
				$sql .= "	select contact_uuid from v_contacts ";
				$sql .= "	where domain_uuid = :domain_uuid ";
				$sql .= "	and ( ";
				$sql .= "		lower(contact_organization) like :search or ";
				$sql .= "		lower(contact_name_given) like :search or ";
				$sql .= "		lower(contact_name_family) like :search or ";
				$sql .= "		lower(contact_nickname) like :search or ";
				$sql .= "		lower(contact_title) like :search or ";
				$sql .= "		lower(contact_category) like :search or ";
				$sql .= "		lower(contact_role) like :search or ";
				$sql .= "		lower(contact_url) like :search or ";
				$sql .= "		lower(contact_time_zone) like :search or ";
				$sql .= "		lower(contact_note) like :search or ";
				$sql .= "		lower(contact_type) like :search ";
				$sql .= "	) ";
				$sql .= ") ";
			//search contact emails
				if (permission_exists('contact_email_view')) {
					$sql .= "or contact_uuid in ( ";
					$sql .= "	select contact_uuid from v_contact_emails ";
					$sql .= "	where domain_uuid = :domain_uuid ";
					$sql .= "	and ( ";
					$sql .= "		lower(email_address) like :search or ";
					$sql .= "		lower(email_description) like :search ";
					$sql .= "	) ";
					$sql .= ") ";
				}
			//search contact notes
				if (permission_exists('contact_note_view')) {
					$sql .= "or contact_uuid in ( ";
					$sql .= "	select contact_uuid from v_contact_notes ";
					$sql .= "	where domain_uuid = :domain_uuid ";
					$sql .= "	and lower(contact_note) like :search ";
					$sql .= ") ";
				}
			//close container
				$sql .= ") ";
		}
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= "order by contact_organization asc ";
	$sql .= "limit 300 ";
	$database = new database;
	$contact_array = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//return the contacts as json
	$i = 0;
	if (is_array($contact_array)) {
		foreach($contact_array as $row) {
			$contact_name = array();
			if ($row['contact_organization'] != '') { $contact_name[] = $row['contact_organization']; }
			if ($row['contact_name_family'] != '') { $contact_name[] = $row['contact_name_family']; }
			if ($row['contact_name_given'] != '') { $contact_name[] = $row['contact_name_given']; }
			if ($row['contact_name_family'] == '' && $row['contact_name_given'] == '' && $row['contact_nickname'] != '') { $contact_name[] = $row['contact_nickname']; }
			$contacts[$i]['id'] = $row['contact_uuid'];
			$contacts[$i]['name'] = implode(', ', $contact_name);
			$i++;
		}
		echo json_encode($contacts, true);
	}

?>
