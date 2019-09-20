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
*/

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('streams')) {
	class streams {

		/**
		 * Called when the object is created
		 */
		public function __construct() {

		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete streams
		 */
		public function delete($streams) {
			if (permission_exists('stream_delete')) {

				//delete multiple streams
					if (is_array($streams)) {
						//get the action
							foreach($streams as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								$x = 0;
								foreach($streams as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										//build delete array
											$array['streams'][$x]['stream_uuid'] = $row['stream_uuid'];
											$x++;
									}
								}
								if (is_array($array) && @sizeof($array) != 0) {
									//grant temporary permissions
										$p = new permissions;
										$p->add('stream_delete', 'temp');

									//execute delete
										$database = new database;
										$database->app_name = 'streams';
										$database->app_uuid = 'ffde6287-aa18-41fc-9a38-076d292e0a38';
										$database->delete($array);
										unset($array);

									//revoke temporary permissions
										$p->delete('stream_delete', 'temp');
								}
								unset($streams);
							}
					}
			}
		}

	}
}

/*
$obj = new streams;
$obj->delete();
*/

?>