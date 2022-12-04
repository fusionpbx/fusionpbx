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

if ($domains_processed == 1) {

	//create the user view combines username, organization, contact first and last name
	$database = new database;
	$database->execute("DROP VIEW view_call_recordings;", null);
	$sql = "CREATE VIEW view_call_recordings AS ( \n";
	$sql .= "	select domain_uuid, xml_cdr_uuid as call_recording_uuid, \n";
	$sql .= "	caller_id_name, caller_id_number, caller_destination, \n";
	$sql .= "	record_name as call_recording_name, record_path as call_recording_path, \n";
	$sql .= "	duration as call_recording_length, start_stamp as call_recording_date, direction as call_direction \n";
	$sql .= "	from v_xml_cdr \n";
	$sql .= "	where record_name is not null \n";
	$sql .= "	and record_path is not null \n";
	$sql .= "	order by start_stamp desc \n";
	$sql .= "); \n";
	$database = new database;
	$database->execute($sql, null);
	unset($sql);

}

?>
