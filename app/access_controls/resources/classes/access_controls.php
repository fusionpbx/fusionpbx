<?php

/**
 * access controls class
 */
if (!class_exists('access_controls')) {

	class access_controls {

		const APP_NAME = 'access_controls';
		const APP_UUID = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
		const LIST_PAGE = 'access_controls.php';

		/**
		 * Database Connection Object
		 * @var database Object
		 */
		private $database;

		/**
		 * @param array $params Allows setting the database using an associative array
		 */
		public function __construct($params = []) {

			// check if passed in the constructor
			if (isset($params['database'])) {
				// use existing object
				$this->database = $params['database'];
			} else {
				// use an existing connection if possible
				$this->database = database::new();
			}

			// set the database name and uuid used for tracking database transactions
			$this->database->app_name = self::APP_NAME;
			$this->database->app_uuid = self::APP_UUID;

		}

		/**
		 * delete records 'checked' for deletion
		 * @param array $records associative array for records to delete
		 */
		public function delete($records) {

			//assign local variables
			$permission_prefix = 'access_control_';
			$table = 'access_controls';
			$uuid_prefix = 'access_control_';

			if (permission_exists($permission_prefix . 'delete')) {

				//add multi-lingual support
				$language = new text;
				$text = $language->get();

				//validate the token
				$token = new token;
				if (!$token->validate($_SERVER['PHP_SELF'])) {
					message::add($text['message-invalid_token'], 'negative');
					header('Location: ' . self::LIST_PAGE);
					exit;
				}

				//delete multiple records
				if (is_array($records) && @sizeof($records) != 0) {

					//build the delete array
					foreach ($records as $x => $record) {
						if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
							$array[$table][$x][$uuid_prefix . 'uuid'] = $record['uuid'];
							$array['access_control_nodes'][$x][$uuid_prefix . 'uuid'] = $record['uuid'];
						}
					}

					//delete the checked rows
					if (is_array($array) && @sizeof($array) != 0) {

						//grant temporary permissions
						$p = new permissions;
						$p->add('access_control_node_delete', 'temp');

						//execute delete
						$this->database->delete($array);
						unset($array);

						//revoke temporary permissions
						$p->delete('access_control_node_delete', 'temp');

						//clear the cache
						$cache = new cache;
						$cache->delete("configuration:acl.conf");

						//create the event socket connection
						event_socket::async("reloadacl");

						//set message
						message::add($text['message-delete']);
					}
					unset($records);
				}
			}
		}

		/**
		 * delete node records 'checked' for deletion and reloads the ACL using an event socket.
		 * @param array $records associative array for records to delete
		 */
		public function delete_nodes($records) {

			//assign local variables
			$permission_prefix = 'access_control_node_';
			$table = 'access_control_nodes';
			$uuid_prefix = 'access_control_node_';

			if (permission_exists($permission_prefix . 'delete')) {

				//add multi-lingual support
				$language = new text;
				$text = $language->get();

				//validate the token
				$token = new token;
				if (!$token->validate('/app/access_controls/access_control_nodes.php')) {
					message::add($text['message-invalid_token'], 'negative');
					header('Location: ' . self::LIST_PAGE);
					exit;
				}

				//delete multiple records
				if (is_array($records) && @sizeof($records) != 0) {

					//build the delete array
					foreach ($records as $x => $record) {
						if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
							$array[$table][$x][$uuid_prefix . 'uuid'] = $record['uuid'];
						}
					}

					//delete the checked rows
					if (is_array($array) && @sizeof($array) != 0) {

						//execute delete
						$this->database->delete($array);
						unset($array);

						//clear the cache
						$cache = new cache;
						$cache->delete("configuration:acl.conf");

						//create the event socket connection
						event_socket::async("reloadacl");

						//set message
						message::add($text['message-delete']);
					}
					unset($records);
				}
			}
		}

		/**
		 * copy records that are 'checked'. The word '(copy)' will be appended to the description using the currently selected language. Then reloads the ACL using an event socket.
		 * @param array $records associative array for records to delete
		 */
		public function copy($records) {

			//assign private variables
			$permission_prefix = 'access_control_';
			$table = 'access_controls';
			$uuid_prefix = 'access_control_';

			if (permission_exists($permission_prefix . 'add')) {

				//add multi-lingual support
				$language = new text;
				$text = $language->get();

				//validate the token
				$token = new token;
				if (!$token->validate($_SERVER['PHP_SELF'])) {
					message::add($text['message-invalid_token'], 'negative');
					header('Location: ' . self::LIST_PAGE);
					exit;
				}

				//copy the checked records
				if (is_array($records) && @sizeof($records) != 0) {

					//get checked records
					foreach ($records as $x => $record) {
						if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
							$uuids[] = "'" . $record['uuid'] . "'";
						}
					}

					//create insert array from existing data
					if (is_array($uuids) && @sizeof($uuids) != 0) {

						//primary table
						$sql = "select * from v_" . $table . " ";
						$sql .= "where " . $uuid_prefix . "uuid in (" . implode(', ', $uuids) . ") ";
						$rows = $this->database->select($sql, null, 'all');
						if (is_array($rows) && @sizeof($rows) != 0) {
							$y = 0;
							foreach ($rows as $x => $row) {
								$primary_uuid = uuid();

								//copy data
								$array[$table][$x] = $row;

								//overwrite
								$array[$table][$x][$uuid_prefix . 'uuid'] = $primary_uuid;
								$array[$table][$x]['access_control_description'] = trim($row['access_control_description'] . ' (' . $text['label-copy'] . ')');

								//nodes sub table
								$sql_2 = "select * from v_access_control_nodes where access_control_uuid = :access_control_uuid";
								$parameters_2['access_control_uuid'] = $row['access_control_uuid'];
								$rows_2 = $this->database->select($sql_2, $parameters_2, 'all');
								if (is_array($rows_2) && @sizeof($rows_2) != 0) {
									foreach ($rows_2 as $row_2) {

										//copy data
										$array['access_control_nodes'][$y] = $row_2;

										//overwrite
										$array['access_control_nodes'][$y]['access_control_node_uuid'] = uuid();
										$array['access_control_nodes'][$y]['access_control_uuid'] = $primary_uuid;

										//increment
										$y++;
									}
								}
								unset($sql_2, $parameters_2, $rows_2, $row_2);
							}
						}
						unset($sql, $rows, $row);
					}

					//save the changes and set the message
					if (is_array($array) && @sizeof($array) != 0) {

						//grant temporary permissions
						$p = new permissions;
						$p->add('access_control_node_add', 'temp');

						//save the array
						$this->database->save($array);
						unset($array);

						//revoke temporary permissions
						$p->delete('access_control_node_add', 'temp');

						//clear the cache
						$cache = new cache;
						$cache->delete("configuration:acl.conf");

						//create the event socket connection
						event_socket::async("reloadacl");

						//set message
						message::add($text['message-copy']);
					}
					unset($records);
				}
			}
		}
	}

}
?>
