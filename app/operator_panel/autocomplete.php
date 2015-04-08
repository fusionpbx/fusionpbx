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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_view')) {
	//access granted
}
else {
	exit;
}

//search term
	$term = check_str($_GET['term']);
	if (isset($_GET['debug'])) {
		echo "Search Term: ".$term."<br><br>";
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

//get extensions list
	$sql = "select ";
	$sql .= "e.extension, ";
	$sql .= "e.effective_caller_id_name, ";
	$sql .= "e.directory_full_name ";
	$sql .= "from ";
	$sql .= "v_extensions e ";
	$sql .= "where ";
	foreach ($terms as $index => $term) {
		$sql .= "( ";
		$sql .= "	lower(e.effective_caller_id_name) like lower('%".$term."%') or ";
		$sql .= "	lower(e.outbound_caller_id_name) like lower('%".$term."%') or ";
		$sql .= "	lower(e.directory_full_name) like lower('%".$term."%') or ";
		$sql .= "	lower(e.description) like lower('%".$term."%') or ";
		$sql .= "	lower(e.call_group) like lower('%".$term."%') or ";
		$sql .= "	e.extension like '%".$term."%' ";
		$sql .= ") ";
		if ($index + 1 < sizeof($terms)) {
			$sql .= " and ";
		}
	}
	$sql .= "and e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and e.enabled = 'true' ";
	$sql .= "order by ";
	$sql .= "e.directory_full_name asc, ";
	$sql .= "e.effective_caller_id_name asc ";
	if (isset($_GET['debug'])) { echo $sql."<br><br>"; }
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	if ($result_count > 0) {
		foreach($result as $row) {
			if ($row['directory_full_name'] != '') { $values[] = $row['directory_full_name']; }
			if ($row['effective_caller_id_name'] != '') { $values[] = $row['effective_caller_id_name']; }

			$suggestions[] = "{ \"label\": \"".(implode(', ', $values)." @ ".$row['extension'])."\", \"value\": \"".$row['extension']."\" }";
			unset($values);
		}
		unset($sql, $result, $row_count);
	}

//get contacts list
	$sql = "select ";
	$sql .= "c.contact_organization, ";
	$sql .= "c.contact_name_given, ";
	$sql .= "c.contact_name_middle, ";
	$sql .= "c.contact_name_family, ";
	$sql .= "c.contact_nickname, ";
	$sql .= "p.phone_number, ";
	$sql .= "p.phone_label ";
	$sql .= "from ";
	$sql .= "v_contacts as c, ";
	$sql .= "v_contact_phones as p ";
	$sql .= "where ";
	foreach ($terms as $index => $term) {
		$sql .= "( ";
		$sql .= "	lower(c.contact_organization) like lower('%".$term."%') or ";
		$sql .= "	lower(c.contact_name_given) like lower('%".$term."%') or ";
		$sql .= "	lower(c.contact_name_middle) like lower('%".$term."%') or ";
		$sql .= "	lower(c.contact_name_family) like lower('%".$term."%') or ";
		$sql .= "	lower(c.contact_nickname) like lower('%".$term."%') or ";
		$sql .= "	p.phone_number like '%".$term."%' ";
		$sql .= ") ";
		if ($index + 1 < sizeof($terms)) {
			$sql .= " and ";
		}
	}
	$sql .= "and c.contact_uuid = p.contact_uuid ";
	$sql .= "and c.domain_uuid = '".$_SESSION['domain_uuid']."' ";
	if (sizeof($user_group_uuids) > 0) {
		$sql .= "and ( \n"; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	c.contact_uuid in ( \n";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where group_uuid in ('".implode("','", $user_group_uuids)."') ";
		$sql .= "		and domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "	) \n";
		$sql .= "	or \n";
		$sql .= "	c.contact_uuid not in ( \n";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "	) \n";
		$sql .= ") \n";
	}
	$sql .= "and p.phone_type_voice = 1 ";
	$sql .= "order by ";
	$sql .= "contact_organization desc, ";
	$sql .= "contact_name_given asc, ";
	$sql .= "contact_name_family asc ";
	if (isset($_GET['debug'])) { echo $sql."<br><br>"; }
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset($prep_statement, $sql);

	if ($result_count > 0) {
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