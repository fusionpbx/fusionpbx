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
	Portions created by the Initial Developer are Copyright (C) 2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

// Includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

// Check permissions
	if (!permission_exists('operator_panel_view')) {
		exit;
	}

// Global variables
	global $database;

// Search term
	$term = $_GET['term'] ?? '';

// If term contains spaces, break into array
	if (substr_count($term, ' ') > 0) {
		$terms = explode(' ', $term);
	}
	else {
		$terms[] = $term;
	}

// Add multi-lingual support
	$language = new text;
	$text = $language->get();

// Retrieve current user's assigned groups (uuids)
	$user_group_uuids = [];
	foreach ($_SESSION['groups'] as $group_data) {
		$user_group_uuids[] = $group_data['group_uuid'];
	}
	// Add user's uuid to group uuid list to include private (non-shared) contacts
	$user_group_uuids[] = $_SESSION["user_uuid"];

// Get extensions list
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
	$parameters['term'] = '%'.$term.'%';
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$result = $database->select($sql, $parameters, 'all');
	unset ($parameters, $sql);

	$suggestions = [];
	if (is_array($result)) {
		foreach($result as $row) {
			$values = [];
			$dir_name = trim($row['directory_full_name'] ?? '');
			$cid_name = trim($row['effective_caller_id_name'] ?? '');
			if ($dir_name !== '') { $values[] = $dir_name; }
			if ($cid_name !== '') { $values[] = $cid_name; }

			$label = implode(', ', $values)." @ ".$row['extension'];
			$suggestions[] = [ "label" => $label, "value" => $row['extension'] ];
		}
	}

// Get contacts list
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
		$sql .= "and ( \n"; // Only contacts assigned to current user's group(s) and those not assigned to any group
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
	$parameters['term'] = '%'.$term.'%';
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$result = $database->select($sql, $parameters, 'all');
	unset ($parameters, $sql);

	if (is_array($result)) {
		foreach($result as $row) {
			$values = [];
			$org = trim($row['contact_organization'] ?? '');
			if ($org !== '') { $values[] = $org; }

			$names = '';
			if (trim($row['contact_name_given'] ?? '') !== '') { $names = trim($row['contact_name_given']); }
			if (trim($row['contact_name_middle'] ?? '') !== '') { $names .= ($names !== '' ? ' ' : '').trim($row['contact_name_middle']); }
			if (trim($row['contact_name_family'] ?? '') !== '') { $names .= ($names !== '' ? ' ' : '').trim($row['contact_name_family']); }
			if ($names !== '') { $values[] = $names; }

			$nickname = trim($row['contact_nickname'] ?? '');
			if ($nickname !== '') { $values[] = $nickname; }

			$phone_label = (trim($row['phone_label'] ?? '') !== '') ? " (".trim($row['phone_label']).")" : '';
			$prefix = !empty($values) ? implode(', ', $values).' ' : '';
			$label = $prefix."@ ".$row['phone_number'].$phone_label;
			$suggestions[] = [ "label" => $label, "value" => $row['phone_number'] ];
		}
	}

// Output suggestions as JSON
	header('Content-Type: application/json');
	echo json_encode($suggestions);
