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
	Ken Rice <krice@tollfreegateway.com>
	Mark J Crane <markjcrane@fusionpbx.com>
*/
function sql_tables($db_type) {
	$x = 0;

	include "app_config.php";
	$sql = "";

	foreach ($apps[$x]['db'] as $new_db) {
		$sql .= "CREATE TABLE " . $new_db['table'] . " (\n";
		$fcount = 0;
		foreach ($new_db['fields'] as $field) {
			if ($fcount > 0 ) { $sql .= ",\n"; }
			$sql .= $field['name'] . " ";
			if (is_array($field['type'])) {
				$sql .=	$field['type'][$db_type];
			} else {
				$sql .=	$field['type'];
			}
			$fcount++;
		}
		$sql .= ");\n\n";
	}

	return $sql;
}
?>
