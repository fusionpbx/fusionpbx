<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('bridges')) {
	class bridges {

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
								$database = new database;
								foreach($bridges as $x => $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										$array['bridges'][$x]['bridge_uuid'] = $row['bridge_uuid'];
										$array['bridges'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									}
								}
								if (is_array($array) && @sizeof($array) != 0) {
									$database->app_name = 'bridges';
									$database->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
									$database->delete($array);
									unset($array);
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
