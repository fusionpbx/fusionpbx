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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//Change ringtones to tones
	$sql = "select * from v_vars ";
	$sql .= "where var_category = 'Tones' and var_name like '%-ring%'; ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$ringtones = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (is_array($ringtones)) {
		$sql = "update v_vars set var_category = 'Ringtones'  ";
		$sql .= "where  var_category = 'Tones' and var_name like '%-ring%'; ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

}

?>
