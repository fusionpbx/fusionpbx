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

//proccess this only one time
if ($domains_processed == 1) {

	//migrate existing attachment preferences to new column, where appropriate
		$sql = "update v_voicemails set voicemail_file = 'attach' where voicemail_attach_file = 'true'";
		$db->exec(check_sql($sql));
		unset($sql);

	//add that the directory structure for voicemail each domain and voicemail id is
		$sql = "select d.domain_name, v.voicemail_id ";
		$sql .= "from v_domains as d, v_voicemails as v ";
		$sql .= "where v.domain_uuid = d.domain_uuid ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$voicemails = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($voicemails as $row) {
			$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$row['domain_name'].'/'.$row['voicemail_id'];
			if (!file_exists($path)) {
				mkdir($path, 02770, true);
			}
		}
		unset ($prep_statement, $sql);

}

?>