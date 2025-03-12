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
	Copyright (C) 2022	All Rights Reserved.

*/

/**
 * presence class
 */
	class presence {

		/**
		 * active presence
		 * @var string $presence_id
		 */
		public function active($presence_id) {
			$json = event_socket::api('show calls as json');
			$call_array = json_decode($json, true);
			if (isset($call_array['rows'])) {
				$x = 0;
				foreach ($call_array['rows'] as $row) {
					if ($row['presence_id'] == $presence_id) {
						return true;
					}

					if (isset($row['b_presence_id']) && $row['b_presence_id'] != 0) {
						if ($row['b_presence_id'] == $presence_id) {
							return true;
						}
						$x++;
					}

					$x++;
				}
			}
			return false;
		}

		/**
		 * show presence
		 */
		public function show() {
			$json = event_socket::api('show calls as json');
			$call_array = json_decode($json, true);
			if (isset($call_array['rows'])) {
				$x = 0;
				foreach ($call_array['rows'] as $row) {
					$array[$x]['presence_id'] = $row['presence_id'];
					$array[$x]['presence_user'] = explode('@', $row['presence_id'])[0];
					$array[$x]['domain_name'] = explode('@', $row['presence_id'])[1];

					if (isset($row['b_presence_id']) && $row['b_presence_id'] != 0) {
						$x++;
						$array[$x]['presence_id'] = $row['b_presence_id'];
						$array[$x]['presence_user'] = explode('@', $row['b_presence_id'])[0];
						$array[$x]['domain_name'] = explode('@', $row['b_presence_id'])[1];
					}

					$x++;
				}
			}
			return $array;
		}
	}

//examples
	/*
	//check if presence is active
		$presence_id = '103@'.$_SESSION['domain_name'];
		$presence = new presence;
		$result = $presence->active($presence_id);
		echo "presence_id $presence_id<br />\n";
		if ($result) {
			echo "active: true\n";
		}
		else {
			echo "active: false\n";
		}
	//show active the presence
		$presence = new presence;
		$array = $presence->show();
	*/
