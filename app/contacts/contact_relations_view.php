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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
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
	if (permission_exists('contact_relation_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the related contacts
	$sql = "select ";
	$sql .= "cr.contact_relation_uuid, ";
	$sql .= "cr.relation_label, ";
	$sql .= "c.contact_uuid, ";
	$sql .= "c.contact_organization, ";
	$sql .= "c.contact_name_given, ";
	$sql .= "c.contact_name_family ";
	$sql .= "from ";
	$sql .= "v_contact_relations as cr, ";
	$sql .= "v_contacts as c ";
	$sql .= "where ";
	$sql .= "cr.relation_contact_uuid = c.contact_uuid ";
	$sql .= "and cr.domain_uuid = :domain_uuid ";
	$sql .= "and cr.contact_uuid = :contact_uuid ";
	$sql .= "order by ";
	$sql .= "c.contact_organization desc, ";
	$sql .= "c.contact_name_given asc, ";
	$sql .= "c.contact_name_family asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_relations = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_relations) && @sizeof($contact_relations) != 0) {

		//show the content
			echo "<div class='grid' style='grid-template-columns: 70px auto auto;'>\n";
			$x = 0;
			foreach ($contact_relations as $row) {
				echo "<div class='box contact-details-label'>".escape($row['relation_label'])."</div>\n";
				echo "<div class='box'><a href='contact_view.php?id=".urlencode($row['contact_uuid'])."'>".escape($row['contact_organization'])."</a></div>\n";
				echo "<div class='box'><a href='contact_view.php?id=".urlencode($row['contact_uuid'])."'>".escape($row['contact_name_given']).(($row['contact_name_given'] && $row['contact_name_family']) ? ' ' : null).escape($row['contact_name_family'])."</a></div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_relations);

	}

?>