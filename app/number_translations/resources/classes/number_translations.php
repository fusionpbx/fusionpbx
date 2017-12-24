<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('number_translations')) {
	class number_translations {

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
		 * delete number_translations
		 */
		public function delete($number_translations) {
			if (permission_exists('number_translation_delete')) {

				//delete multiple number_translations
					if (is_array($number_translations)) {
						//get the action
							foreach($number_translations as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								foreach($number_translations as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										$sql = "delete from v_number_translations ";
										$sql .= "where number_translation_uuid = '".$row['number_translation_uuid']."'; ";
										$this->db->query($sql);
										unset($sql);
									}
								}
								unset($number_translations);
							}
					}
			}
		} //end the delete function

	}  //end the class
}

/*
$obj = new number_translations;
$obj->delete();
*/

?>
