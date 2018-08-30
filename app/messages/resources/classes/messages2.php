<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('messages')) {
	class messages {

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
		 * delete messages
		 */
		public function delete($messages) {
			if (permission_exists('message_delete')) {

				//delete multiple messages
					if (is_array($messages)) {
						//get the action
							foreach($messages as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								foreach($messages as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										$sql = "delete from v_messages ";
										$sql .= "where message_uuid = '".$row['message_uuid']."'; ";
										$this->db->query($sql);
										unset($sql);
									}
								}
								unset($messages);
							}
					}
			}
		} //end the delete function

	}  //end the class
}

/*
$obj = new messages;
$obj->delete();
*/

?>