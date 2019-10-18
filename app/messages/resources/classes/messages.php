<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('messages')) {
	class messages {

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
								$x = 0;
								foreach($messages as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										//build delete array
											$array['messages'][$x]['message_uuid'] = $row['message_uuid'];
											$x++;
									}
								}
								if (is_array($array) && @sizeof($array) != 0) {
									//grant temporary permissions
										$p = new permissions;
										$p->add('message_delete', 'temp');

									//execute delete
										$database = new database;
										$database->app_name = 'messages';
										$database->app_uuid = '4a20815d-042c-47c8-85df-085333e79b87';
										$database->delete($array);
										unset($array);

									//revoke temporary permissions
										$p->delete('message_delete', 'temp');
								}
								unset($messages);
							}
					}
			}
		} //end the delete function

		
		/**
		 * add messages
		 */
		public function add() {

		} //end the add function
	}  //end the class
}

/*
$obj = new messages;
$obj->delete();
*/

?>