<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('bridges')) {
	class bridges {

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
		 * delete bridges
		 */
		public function delete($bridges) {
			if (permission_exists('bridge_delete')) {

				//delete multiple bridges
					if (is_array($bridges)) {
						//get the action
							foreach($bridges as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								foreach($bridges as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										$sql = "delete from v_bridges ";
										$sql .= "where bridge_uuid = '".$row['bridge_uuid']."'; ";
										$this->db->query($sql);
										unset($sql);
									}
								}
								unset($bridges);
							}
					}
			}
		} //end the delete function

	}  //end the class
}

/*
$obj = new bridges;
$obj->delete();
*/

?>
