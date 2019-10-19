<?php

/**
 * access controls class
 *
 * @method null download
 */
if (!class_exists('access_controls')) {
	class access_controls {

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
		 * delete access controls
		 */
		public function delete($access_controls) {
			if (permission_exists('access_control_delete') && permission_exists('access_control_node_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: access_controls.php');
						exit;
					}

				//delete multiple access controls
					if (is_array($access_controls) && @sizeof($access_controls) != 0) {
						//build the delete array
							foreach($access_controls as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['access_control_uuid'])) {
									$array['access_controls'][$x]['access_control_uuid'] = $row['access_control_uuid'];
									$array['access_control_nodes'][$x]['access_control_uuid'] = $row['access_control_uuid'];
								}
							}
						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = 'access_controls';
									$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
									$database->delete($array);
									unset($array);
								//set message
									message::add($text['message-delete']);
							}
							unset($access_controls);
					}
			}
		}

		/**
		 * copy access controls
		 */
		public function copy($access_controls) {
			if (permission_exists('access_control_add') && permission_exists('access_control_node_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: access_controls.php');
						exit;
					}

				//copy the checked access controls
					if (is_array($access_controls) && @sizeof($access_controls) != 0) {

						//get checked access controls
							foreach($access_controls as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['access_control_uuid'])) {
									$access_control_uuids[] = "access_control_uuid = '".$row['access_control_uuid']."'";
								}
							}
						//create insert array from existing data
							if (is_array($access_control_uuids) && @sizeof($access_control_uuids) != 0) {
								$sql = "select * from v_access_controls ";
								$sql .= "where ".implode(' or ', $access_control_uuids)." ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$y = 0;
									foreach ($rows as $x => $row) {
										//access control
											$access_control_uuid = uuid();
											$array['access_controls'][$x]['access_control_uuid'] = $access_control_uuid;
											$array['access_controls'][$x]['access_control_name'] = $row['access_control_name'];
											$array['access_controls'][$x]['access_control_default'] = $row['access_control_default'];
											$array['access_controls'][$x]['access_control_description'] = trim($row['access_control_description'].' ('.$text['label-copy'].')');
										//access control nodes
											$sql_2 = "select * from v_access_control_nodes where access_control_uuid = :access_control_uuid";
											$parameters_2['access_control_uuid'] = $row['access_control_uuid'];
											$database = new database;
											$rows_2 = $database->select($sql_2, $parameters_2, 'all');
											if (is_array($rows_2) && @sizeof($rows_2) != 0) {
												foreach ($rows_2 as $row_2) {
													$access_control_node_uuid = uuid();
													$array['access_control_nodes'][$y]['access_control_node_uuid'] = $access_control_node_uuid;
													$array['access_control_nodes'][$y]['access_control_uuid'] = $access_control_uuid;
													$array['access_control_nodes'][$y]['node_type'] = $row_2['node_type'];
													$array['access_control_nodes'][$y]['node_cidr'] = $row_2['node_cidr'];
													$array['access_control_nodes'][$y]['node_domain'] = $row_2['node_domain'];
													$array['access_control_nodes'][$y]['node_description'] = $row_2['node_description'];
													$y++;
												}
											}
											unset($sql_2, $parameters_2, $rows_2, $row_2);
									}
								}
								unset($sql, $parameters, $rows, $row);
							}
						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = 'access_controls';
									$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);
							}
							unset($access_controls);
					}

			}
		}

	}
}

/*
$obj = new access_controls;
$obj->delete();
*/

?>