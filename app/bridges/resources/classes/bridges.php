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

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: bridges.php');
						exit;
					}

				//delete multiple bridges
					if (is_array($bridges) && @sizeof($bridges) != 0) {
						//delete the checked rows
							foreach($bridges as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['bridge_uuid'])) {
									$array['bridges'][$x]['bridge_uuid'] = $row['bridge_uuid'];
									$array['bridges'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = 'bridges';
									$database->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
									$database->delete($array);
									unset($array);
								//set message
									message::add($text['message-delete']);
							}
							unset($bridges);
					}
			}
		}

		/**
		 * toggle bridges
		 */
		public function toggle($bridges) {
			if (permission_exists('bridge_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: bridges.php');
						exit;
					}

				//toggle the checked bridges
					if (is_array($bridges) && @sizeof($bridges) != 0) {
						//get current enabled state of checked bridges
							foreach($bridges as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['bridge_uuid'])) {
									$bridge_uuids[] = "bridge_uuid = '".$row['bridge_uuid']."'";
								}
							}
							if (is_array($bridge_uuids) && @sizeof($bridge_uuids) != 0) {
								$sql = "select bridge_uuid, bridge_enabled from v_bridges ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ( ".implode(' or ', $bridge_uuids)." ) ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$bridge_states[$row['bridge_uuid']] = $row['bridge_enabled'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($bridge_states as $bridge_uuid => $bridge_state) {
								$array['bridges'][$x]['bridge_uuid'] = $bridge_uuid;
								$array['bridges'][$x]['bridge_enabled'] = $bridge_state == 'true' ? 'false' : 'true';
								$x++;
							}

						if (is_array($array) && @sizeof($array) != 0) {
							//execute update
								$database = new database;
								$database->app_name = 'bridges';
								$database->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
								$database->save($array);
								unset($array);
							//set message
								message::add($text['message-toggle']);
						}
						unset($bridges, $bridge_states);
					}

			}
		}

		/**
		 * copy bridges
		 */
		public function copy($bridges) {
			if (permission_exists('bridge_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: bridges.php');
						exit;
					}

				//copy the checked bridges
					if (is_array($bridges) && @sizeof($bridges) != 0) {

						//get checked bridges
							foreach($bridges as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['bridge_uuid'])) {
									$bridge_uuids[] = "bridge_uuid = '".$row['bridge_uuid']."'";
								}
							}
						//create insert array from existing data
							if (is_array($bridge_uuids) && @sizeof($bridge_uuids) != 0) {
								$sql = "select * from v_bridges ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ( ".implode(' or ', $bridge_uuids)." ) ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $x => $row) {
										$array['bridges'][$x]['bridge_uuid'] = uuid();
										$array['bridges'][$x]['domain_uuid'] = $row['domain_uuid'];
										$array['bridges'][$x]['bridge_name'] = $row['bridge_name'];
										$array['bridges'][$x]['bridge_destination'] = $row['bridge_destination'];
										$array['bridges'][$x]['bridge_enabled'] = $row['bridge_enabled'];
										$array['bridges'][$x]['bridge_description'] = trim($row['bridge_description'].' ('.$text['label-copy'].')');
									}
								}
								unset($sql, $parameters, $rows, $row);
							}
						//execute insert
							if (is_array($array) && @sizeof($array) != 0) {
								$database = new database;
								$database->app_name = 'bridges';
								$database->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
								$database->save($array);
								unset($array);
							//set message
								message::add($text['message-copy']);
							}
							unset($bridges);
					}

			}
		}

	}
}

/*
$obj = new bridges;
$obj->delete();
*/

?>