<?php

/**
 * call block class
 *
 * @method null download
 */
if (!class_exists('call_block')) {
	class call_block {

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
		 * delete call block
		 */
		public function delete($call_blocks) {
			if (permission_exists('call_block_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: call_block.php');
						exit;
					}

				//delete multiple call blocks
					if (is_array($call_blocks) && @sizeof($call_blocks) != 0) {
						//build the delete array
							foreach($call_blocks as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['call_block_uuid'])) {
									$array['call_block'][$x]['call_block_uuid'] = $row['call_block_uuid'];
									$array['call_block'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}
						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = 'call_block';
									$database->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
									$database->delete($array);
									unset($array);
								//set message
									message::add($text['message-delete']);
							}
							unset($call_blocks);
					}
			}
		}

		/**
		 * toggle call block
		 */
		public function toggle($call_blocks) {
			if (permission_exists('call_block_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: call_block.php');
						exit;
					}

				//toggle the checked call blocks
					if (is_array($call_blocks) && @sizeof($call_blocks) != 0) {
						//get current enabled state of checked call block
							foreach($call_blocks as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['call_block_uuid'])) {
									$call_block_uuids[] = "call_block_uuid = '".$row['call_block_uuid']."'";
								}
							}
							if (is_array($call_block_uuids) && @sizeof($call_block_uuids) != 0) {
								$sql = "select call_block_uuid, call_block_enabled from v_call_block ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ( ".implode(' or ', $call_block_uuids)." ) ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$call_block_states[$row['call_block_uuid']] = $row['call_block_enabled'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($call_block_states as $call_block_uuid => $call_block_state) {
								$array['call_block'][$x]['call_block_uuid'] = $call_block_uuid;
								$array['call_block'][$x]['call_block_enabled'] = $call_block_state == 'true' ? 'false' : 'true';
								$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = 'call_block';
									$database->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
									$database->save($array);
									unset($array);
								//set message
									message::add($text['message-toggle']);
							}
							unset($call_blocks, $call_block_states);
					}

			}
		}

		/**
		 * copy call blocks
		 */
		public function copy($call_blocks) {
			if (permission_exists('call_block_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: call_block.php');
						exit;
					}

				//copy the checked call blocks
					if (is_array($call_blocks) && @sizeof($call_blocks) != 0) {

						//get checked call blocks
							foreach($call_blocks as $x => $row) {
								if ($row['checked'] == 'true' && is_uuid($row['call_block_uuid'])) {
									$call_block_uuids[] = "call_block_uuid = '".$row['call_block_uuid']."'";
								}
							}
						//create insert array from existing data
							if (is_array($call_block_uuids) && @sizeof($call_block_uuids) != 0) {
								$sql = "select * from v_call_block ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ( ".implode(' or ', $call_block_uuids)." ) ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $x => $row) {
										$array['call_block'][$x]['call_block_uuid'] = uuid();
										$array['call_block'][$x]['domain_uuid'] = $row['domain_uuid'];
										$array['call_block'][$x]['call_block_name'] = $row['call_block_name'];
										$array['call_block'][$x]['call_block_number'] = $row['call_block_number'];
										$array['call_block'][$x]['call_block_count'] = 0;
										$array['call_block'][$x]['call_block_action'] = $row['call_block_action'];
										$array['call_block'][$x]['date_added'] = $row['date_added'];
										$array['call_block'][$x]['call_block_enabled'] = $row['call_block_enabled'];
										$array['call_block'][$x]['call_block_description'] = trim($row['call_block_description'].' ('.$text['label-copy'].')');
									}
								}
								unset($sql, $parameters, $rows, $row);
							}
						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = 'call_block';
									$database->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);
							}
							unset($call_blocks);
					}

			}
		}

	}
}

/*
$obj = new call_block;
$obj->delete();
*/

?>