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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_view')) {
		//access granted
	}
	else {
		exit;
	}

//search term
	$term = check_str($_GET['term']);
	if (isset($_GET['debug'])) {
		echo "Search Term: ".escape($term)."<br><br>";
	}

//if term contains spaces, break into array
	if (substr_count($term, ' ') > 0) {
		$terms = explode(' ', $term);
	}
	else {
		$terms[] = $term;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//retrieve current user's assigned groups (uuids)
	foreach ($_SESSION['groups'] as $group_data) {
		$user_group_uuids[] = $group_data['group_uuid'];
	}
	//add user's uuid to group uuid list to include private (non-shared) contacts
	$user_group_uuids[] = $_SESSION["user_uuid"];

//create the database object
	$database = new database;

//get extensions list
	$sql = "select \n";
	$sql .= "e.extension, \n";
	$sql .= "e.effective_caller_id_name, \n";
	$sql .= "concat(e.directory_first_name, ' ', e.directory_last_name) as directory_full_name \n";
	$sql .= "from \n";
	$sql .= "v_extensions e \n";
	$sql .= "where \n";
	foreach ($terms as $index => $term) {
		$sql .= "( \n";
		$sql .= "	lower(e.effective_caller_id_name) like lower(:term) or \n";
		$sql .= "	lower(e.outbound_caller_id_name) like lower(:term) or \n";
		$sql .= "	lower(concat(e.directory_first_name, ' ', e.directory_last_name)) like lower(:term) or \n";
		$sql .= "	lower(e.description) like lower(:term) or \n";
		$sql .= "	lower(e.call_group) like lower(:term) or \n";
		$sql .= "	e.extension like :term \n";
		$sql .= ") \n";
		if ($index + 1 < sizeof($terms)) {
			$sql .= " and \n";
		}
	}
	$sql .= "and e.domain_uuid = :domain_uuid \n";
	$sql .= "and e.enabled = 'true' \n";
	$sql .= "order by \n";
	$sql .= "directory_full_name asc, \n";
	$sql .= "e.effective_caller_id_name asc \n";
	if (isset($_GET['debug'])) { echo $sql."<br><br>"; }
	$parameters['term'] = '%'.$term.'%';
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset ($parameters, $sql);

	if (is_array($result)) {
		if (isset($_GET['debug'])) { echo $result."<br><br>"; }
		foreach($result as $row) {
			if ($row['directory_full_name'] != '') { $values[] = $row['directory_full_name']; }
			if ($row['effective_caller_id_name'] != '') { $values[] = $row['effective_caller_id_name']; }

			$suggestions[] = "{ \"label\": \"".(implode(', ', $values)." @ ".$row['extension'])."\", \"value\": \"".$row['extension']."\" }";
			unset($values);
		}
		unset($sql, $result, $row_count);
	}

//get contacts list
	$sql = "select \n";
	$sql .= "c.contact_organization, \n";
	$sql .= "c.contact_name_given, \n";
	$sql .= "c.contact_name_middle, \n";
	$sql .= "c.contact_name_family, \n";
	$sql .= "c.contact_nickname, \n";
	$sql .= "p.phone_number, \n";
	$sql .= "p.phone_label \n";
	$sql .= "from \n";
	$sql .= "v_contacts as c, \n";
	$sql .= "v_contact_phones as p \n";
	$sql .= "where \n";
	foreach ($terms as $index => $term) {
		$sql .= "( \n";
		$sql .= "	lower(c.contact_organization) like lower(:term) or \n";
		$sql .= "	lower(c.contact_name_given) like lower(:term) or \n";
		$sql .= "	lower(c.contact_name_middle) like lower(:term) or \n";
		$sql .= "	lower(c.contact_name_family) like lower(:term) or \n";
		$sql .= "	lower(c.contact_nickname) like lower(:term) or \n";
		$sql .= "	p.phone_number like :term \n";
		$sql .= ") \n";
		if ($index + 1 < sizeof($terms)) {
			$sql .= " and \n";
		}
	}
	$sql .= "and c.contact_uuid = p.contact_uuid \n";
	$sql .= "and c.domain_uuid = :domain_uuid \n";
	if (sizeof($user_group_uuids) > 0) {
		$sql .= "and ( \n"; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	c.contact_uuid in ( \n";
		$sql .= "		select contact_uuid from v_contact_groups \n";
		$sql .= "		where group_uuid in ('".implode("','", $user_group_uuids)."') \n";
		$sql .= "		and domain_uuid = :domain_uuid \n";
		$sql .= "	) \n";
		$sql .= "	or \n";
		$sql .= "	c.contact_uuid not in ( \n";
		$sql .= "		select contact_uuid from v_contact_groups \n";
		$sql .= "		where domain_uuid = :domain_uuid \n";
		$sql .= "	) \n";
		$sql .= ") \n";
	}
	$sql .= "and p.phone_type_voice = 1 \n";
	$sql .= "order by \n";
	$sql .= "contact_organization desc, \n";
	$sql .= "contact_name_given asc, \n";
	$sql .= "contact_name_family asc \n";
	if (isset($_GET['debug'])) { echo $sql."<br><br>"; }
	$parameters['term'] = '%'.$term.'%';
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset ($parameters, $sql);

	if (is_array($result)) {
		foreach($result as $row) {
			if ($row['contact_organization'] != '') { $values[] = $row['contact_organization']; }

			if ($row['contact_name_given'] != '') { $names = $row['contact_name_given']; }
			if ($row['contact_name_middle'] != '') { $names .= " ".$row['contact_name_middle']; }
			if ($row['contact_name_family'] != '') { $names .= " ".$row['contact_name_family']; }
			if ($names != '') { $values[] = $names; }

			if ($row['contact_nickname'] != '') { $values[] = $row['contact_nickname']; }

			$suggestions[] = "{ \"label\": \"".(implode(', ', $values)." - ".format_phone($row['phone_number']).(($row['phone_label'] != '') ? " (".$row['phone_label'].")" : null))."\", \"value\": \"".$row['phone_number']."\" }";
			unset($values, $names);
		}
		unset($sql, $result, $row_count);
	}

//output suggestions, if any
	if (sizeof($suggestions) > 0) {
		$resp .= "[\n";
		$resp .= implode(",\n", $suggestions)."\n";
		$resp .= "]";

		if (isset($_GET['debug'])) { echo "<pre>"; }
		echo $resp;
		if (isset($_GET['debug'])) { echo "</pre>"; }
	}

?>
