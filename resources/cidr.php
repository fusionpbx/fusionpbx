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
	Portions created by the Initial Developer are Copyright (C) 20018-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//check the domain cidr range
	global $settings;
	if (!defined('STDIN') && !empty($settings->get('domain', 'cidr'))) {
		$found = false;
		$cidr_array = $settings->get('domain', 'cidr');
		if (!empty($cidr_array)) {
			foreach($cidr_array as $cidr) {
				if (check_cidr($cidr, $_SERVER['REMOTE_ADDR'])) {
					$found = true;
					break;
				}
			}
		}
		if (!$found) {
			echo "access denied";
			exit;
		}
	}
 
 ?>
