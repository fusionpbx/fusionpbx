<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('email_templates')) {
	class email_templates {

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
		 * delete email_templates
		 */
		public function delete($email_templates) {
			if (permission_exists('email_template_delete')) {

				//delete multiple email_templates
					if (is_array($email_templates)) {
						//get the action
							foreach($email_templates as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								foreach($email_templates as $row) {
									if ($row['checked'] == 'true') {
										$sql = "delete from v_email_templates ";
										$sql .= "where email_template_uuid = '".$row['email_template_uuid']."'; ";
										$this->db->query($sql);
										unset($sql);
									}
								}
								unset($email_templates);
							}
					}
			}
		} //end the delete function

	}  //end the class
}

/*
$obj = new email_templates;
$obj->delete();
*/

?>
