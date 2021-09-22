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
	Portions created by the Initial Developer are Copyright (C) 2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//determine if we need to migrate the pin numbers from meetings to conference rooms table

	//get the conference room count
	$sql = "select count(*) from v_conference_rooms; ";
	$database = new database;
	$conference_room_count = $database->select($sql, null, 'column');

	//get the count of moderator and participant pins that are null
	$sql = "select count(*) from v_conference_rooms where moderator_pin is null and participant_pin is null; ";
	$database = new database;
	$pin_null_count = $database->select($sql, null, 'column');

	//if missing move pin numbers from meetings table to the conference rooms table
	if ($database->table_exists($db_type, $db_name, 'v_meetings') && $conference_room_count > 0 && $pin_null_count > 0) {
		$sql = "UPDATE v_conference_rooms ";
		$sql .= "SET participant_pin = subquery.participant_pin, moderator_pin = subquery.moderator_pin ";
		$sql .= "FROM ( ";
		$sql .= "	SELECT ";
		$sql .= "	r.conference_room_uuid, r.conference_room_name, ";
		$sql .= "	m.moderator_pin, m.participant_pin ";
		$sql .= "	FROM v_conference_rooms as r, v_meetings as m ";
		$sql .= 	"WHERE r.meeting_uuid = m.meeting_uuid  ";
		$sql .= "	) AS subquery ";
		$sql .= "WHERE v_conference_rooms.conference_room_uuid = subquery.conference_room_uuid; ";
		$database = new database;
		$database->execute($sql);
		unset($sql);
	}

	//get the count of moderator and participant pins that are null
	$sql = "select count(*) from v_conference_room_users; ";
	$database = new database;
	$conference_room_user_count = $database->select($sql, null, 'column');

	//check if meeting_users table exists
	$table_exists = $database->table_exists($db_type, $db_name, 'v_meeting_users');

	//count the meeting users table
	if ($table_exists) {
		$sql = "select count(*) from v_meeting_users; ";
		$database = new database;
		$meeting_user_count = $database->select($sql, null, 'column');
	}

	//if missing mv users from the meeting_users table to the conference room users table
	if ($table_exists && $conference_room_user_count == 0 && $meeting_user_count > 0) {
		$sql = "INSERT INTO v_conference_room_users ( ";
		$sql .= "	domain_uuid, conference_room_user_uuid, conference_room_uuid, user_uuid ";
		$sql .= ") ";
		$sql .= "SELECT r.domain_uuid, m.meeting_user_uuid as conference_room_user_uuid, r.conference_room_uuid, m.user_uuid ";
		$sql .= "FROM v_conference_rooms as r, v_meeting_users as m ";
		$sql .= "WHERE r.meeting_uuid = m.meeting_uuid; ";
		$database = new database;
		$database->execute($sql);
		unset($sql);
	}
	
	//unset
	unset($table_exists);

}

?>
