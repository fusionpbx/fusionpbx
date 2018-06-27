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

		public $db;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
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
								foreach($streams as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										$sql = "delete from v_streams ";
										$sql .= "where stream_uuid = '".$row['stream_uuid']."'; ";
										$this->db->query($sql);
										unset($sql);
									}
								}
								unset($streams);
							}
					}
			}
		} //end the delete function

	}  //end the class
}

/*
$obj = new streams;
$obj->delete();
*/

?>
