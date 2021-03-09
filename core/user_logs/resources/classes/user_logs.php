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
	Portions created by the Initial Developer are Copyright (C) 2019-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * user_logs class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('user_logs')) {
	class user_logs {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;
		private $table;
		private $toggle_field;
		private $toggle_values;
		private $location;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'user_logs';
				$this->app_uuid = '582a13cf-7d75-4ea3-b2d9-60914352d76e';
				$this->name = 'user_log';
				$this->table = 'user_logs';
				$this->toggle_field = '';
				$this->toggle_values = ['true','false'];
				$this->location = 'user_logs.php';
		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * add user_logs
		 */
		public static function add($result) {
			//prepare the array
				$array['user_logs'][0]["timestamp"] = 'now()';
				$array['user_logs'][0]["domain_uuid"] = $result['domain_uuid'];
				$array['user_logs'][0]["user_uuid"] = $result['user_uuid'];
				$array['user_logs'][0]["username"] = $result['username'];
				$array['user_logs'][0]["type"] = 'login';
				$array['user_logs'][0]["remote_address"] = $_SERVER['REMOTE_ADDR'];
				$array['user_logs'][0]["user_agent"] = $_SERVER['HTTP_USER_AGENT'];
				$array['user_logs'][0]["type"] = 'login';
				if ($result["authorized"] == "true") {
					$array['user_logs'][0]["result"] = 'success';
				}
				else {
					$array['user_logs'][0]["result"] = 'failure';
				}

			//add the dialplan permission
				$p = new permissions;
				$p->add("user_log_add", 'temp');

			//save to the data
				$database = new database;
				$database->app_name = 'authentication';
				$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932be0e41f1';
				$database->uuid($user_log_uuid);
				$database->save($array);
				$message = $database->message;

			//remove the temporary permission
				$p->delete("user_log_add", 'temp');
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$array[$this->table][$x][$this->name.'_uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									}

								//increment the id
									$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

	}
}

?>
