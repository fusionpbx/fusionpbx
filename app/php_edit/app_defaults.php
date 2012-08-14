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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the number of rows is 0 then add example clips
	if ($domains_processed == 1) {
		$sql = "select count(*) as num_rows from v_clips ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				$clip_name = "\$_POST";
				$clip_folder = "PHP";
				$clip_text_start = "\$zzz = \$_POST[\"";
				$clip_text_end = "\"];";
				$clip_desc = "Set HTTP POST value as a PHP variable.";
				$clip_order = 0;

				$sql = "insert into v_clips ";
				$sql .= "(";
				$sql .= "clip_uuid, ";
				$sql .= "clip_name, ";
				$sql .= "clip_folder, ";
				$sql .= "clip_text_start, ";
				$sql .= "clip_text_end, ";
				$sql .= "clip_desc, ";
				$sql .= "clip_order ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."', ";
				$sql .= "'$clip_name', ";
				$sql .= "'$clip_folder', ";
				$sql .= "'$clip_text_start', ";
				$sql .= "'$clip_text_end', ";
				$sql .= "'$clip_desc', ";
				$sql .= "'$clip_order' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);
			}
		}
	}

?>