<?php

	/**
	 * access controls class
	 *
	 * @method null download
	 */
	if (!class_exists('access_controls')) {

		class access_controls implements app_defaults {

			/**
			 * declare private variables
			 */
			private $app_name;
			private $app_uuid;
			private $permission_prefix;
			private $list_page;
			private $table;
			private $uuid_prefix;

			/**
			 * called when the object is created
			 */
			public function __construct() {

				//assign private variables
				$this->app_name = 'access_controls';
				$this->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
				$this->list_page = 'access_controls.php';
			}

			/**
			 * delete records
			 */
			public function delete($records) {

				//assign private variables
				$this->permission_prefix = 'access_control_';
				$this->table = 'access_controls';
				$this->uuid_prefix = 'access_control_';

				if (permission_exists($this->permission_prefix . 'delete')) {

					//add multi-lingual support
					$language = new text;
					$text = $language->get();

					//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'], 'negative');
						header('Location: ' . $this->list_page);
						exit;
					}

					//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
						foreach ($records as $x => $record) {
							if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
								$array[$this->table][$x][$this->uuid_prefix . 'uuid'] = $record['uuid'];
								$array['access_control_nodes'][$x][$this->uuid_prefix . 'uuid'] = $record['uuid'];
							}
						}

						//delete the checked rows
						if (is_array($array) && @sizeof($array) != 0) {

							//grant temporary permissions
							$p = new permissions;
							$p->add('access_control_node_delete', 'temp');

							//execute delete
							$database = new database;
							$database->app_name = $this->app_name;
							$database->app_uuid = $this->app_uuid;
							$database->delete($array);
							unset($array);

							//revoke temporary permissions
							$p->delete('access_control_node_delete', 'temp');

							//clear the cache
							$cache = new cache;
							$cache->delete("configuration:acl.conf");

							//create the event socket connection
							$fp = event_socket_create();
							if ($fp) {
								event_socket_request($fp, "api reloadacl");
							}

							//set message
							message::add($text['message-delete']);
						}
						unset($records);
					}
				}
			}

			public function delete_nodes($records) {

				//assign private variables
				$this->permission_prefix = 'access_control_node_';
				$this->table = 'access_control_nodes';
				$this->uuid_prefix = 'access_control_node_';

				if (permission_exists($this->permission_prefix . 'delete')) {

					//add multi-lingual support
					$language = new text;
					$text = $language->get();

					//validate the token
					$token = new token;
					if (!$token->validate('/app/access_controls/access_control_nodes.php')) {
						message::add($text['message-invalid_token'], 'negative');
						header('Location: ' . $this->list_page);
						exit;
					}

					//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
						foreach ($records as $x => $record) {
							if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
								$array[$this->table][$x][$this->uuid_prefix . 'uuid'] = $record['uuid'];
							}
						}

						//delete the checked rows
						if (is_array($array) && @sizeof($array) != 0) {

							//execute delete
							$database = new database;
							$database->app_name = $this->app_name;
							$database->app_uuid = $this->app_uuid;
							$database->delete($array);
							unset($array);

							//clear the cache
							$cache = new cache;
							$cache->delete("configuration:acl.conf");

							//create the event socket connection
							$fp = event_socket_create();
							if ($fp) {
								event_socket_request($fp, "api reloadacl");
							}

							//set message
							message::add($text['message-delete']);
						}
						unset($records);
					}
				}
			}

			/**
			 * copy records
			 */
			public function copy($records) {

				//assign private variables
				$this->permission_prefix = 'access_control_';
				$this->table = 'access_controls';
				$this->uuid_prefix = 'access_control_';

				if (permission_exists($this->permission_prefix . 'add')) {

					//add multi-lingual support
					$language = new text;
					$text = $language->get();

					//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'], 'negative');
						header('Location: ' . $this->list_page);
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
							$sql = "select * from v_" . $this->table . " ";
							$sql .= "where " . $this->uuid_prefix . "uuid in (" . implode(', ', $uuids) . ") ";
							$database = new database;
							$rows = $database->select($sql, $parameters, 'all');
							if (is_array($rows) && @sizeof($rows) != 0) {
								$y = 0;
								foreach ($rows as $x => $row) {
									$primary_uuid = uuid();

									//copy data
									$array[$this->table][$x] = $row;

									//overwrite
									$array[$this->table][$x][$this->uuid_prefix . 'uuid'] = $primary_uuid;
									$array[$this->table][$x]['access_control_description'] = trim($row['access_control_description'] . ' (' . $text['label-copy'] . ')');

									//nodes sub table
									$sql_2 = "select * from v_access_control_nodes where access_control_uuid = :access_control_uuid";
									$parameters_2['access_control_uuid'] = $row['access_control_uuid'];
									$database = new database;
									$rows_2 = $database->select($sql_2, $parameters_2, 'all');
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
							unset($sql, $parameters, $rows, $row);
						}

						//save the changes and set the message
						if (is_array($array) && @sizeof($array) != 0) {

							//grant temporary permissions
							$p = new permissions;
							$p->add('access_control_node_add', 'temp');

							//save the array
							$database = new database;
							$database->app_name = $this->app_name;
							$database->app_uuid = $this->app_uuid;
							$database->save($array);
							unset($array);

							//revoke temporary permissions
							$p->delete('access_control_node_add', 'temp');

							//clear the cache
							$cache = new cache;
							$cache->delete("configuration:acl.conf");

							//create the event socket connection
							$fp = event_socket_create();
							if ($fp) {
								event_socket_request($fp, "api reloadacl");
							}

							//set message
							message::add($text['message-copy']);
						}
						unset($records);
					}
				}
			}

			public static function defaults(config $config,
				settings $settings,
				database $database,
				permissions $permissions,
				cache $cache): void {
				//add the access control list to the database
				$sql = "select count(*) from v_access_controls ";
				$num_rows = $database->select($sql, null, 'column');
				if ($num_rows == 0) {

					//set the directory based on the config file
					$xml_dir = $config->value('switch.conf.dir', '/etc/freeswitch') . '/autoload_configs';
					$xml_file = $xml_dir . "/acl.conf.xml";
					$root = $config->value('document.root', '/var/www/fusionpbx');
					$project_path = rtrim($config->value('project.path', ''), '/') . '/' ;
					$xml_file_alt = $root . $project_path . '/app/switch/resources/conf/autoload_configs/acl.conf';

					//load the xml and save it into an array
					if (file_exists($xml_file)) {
						$xml_string = file_get_contents($xml_file);
					} elseif (file_exists($xml_file_alt)) {
						$xml_string = file_get_contents(xml_file_alt);
					} else {
						$xml_string = "<configuration name=\"acl.conf\" description=\"Network Lists\">\n";
						$xml_string .= "	<network-lists>\n";
						$xml_string .= "		<list name=\"rfc1918\" default=\"deny\">\n";
						$xml_string .= "			<node type=\"allow\" cidr=\"10.0.0.0/8\"/>\n";
						$xml_string .= "			<node type=\"allow\" cidr=\"172.16.0.0/12\"/>\n";
						$xml_string .= "			<node type=\"allow\" cidr=\"192.168.0.0/16\"/>\n";
						$xml_string .= "		</list>\n";
						$xml_string .= "		<list name=\"providers\" default=\"deny\">\n";
						$xml_string .= "		</list>\n";
						$xml_string .= "	</network-lists>\n";
						$xml_string .= "</configuration>\n";
					}
					$xml_object = simplexml_load_string($xml_string);
					$json = json_encode($xml_object);
					$conf_array = json_decode($json, true);

					//process the array
					if (is_array($conf_array['network-lists']['list'])) {
						foreach ($conf_array['network-lists']['list'] as $list) {
							//get the attributes
							$access_control_name = $list['@attributes']['name'];
							$access_control_default = $list['@attributes']['default'];

							//insert the name, description
							$array = [];
							$access_control_uuid = uuid();
							$array['access_controls'][0]['access_control_uuid'] = $access_control_uuid;
							$array['access_controls'][0]['access_control_name'] = $access_control_name;
							$array['access_controls'][0]['access_control_default'] = $access_control_default;

							$permissions->add('access_control_add', 'temp');

							$database->app_name = 'access_controls';
							$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
							$database->save($array, false);
							unset($array);

							$permissions->delete('access_control_add', 'temp');

							//normalize the array - needed because the array is inconsistent when there is only one row vs multiple
							if (!empty($list['node']['@attributes']['type'])) {
								$list['node'][]['@attributes'] = $list['node']['@attributes'];
								unset($list['node']['@attributes']);
							}

							//add the nodes
							if (is_array($list['node'])) {
								foreach ($list['node'] as $row) {
									//get the name and value pair
									$node_type = $row['@attributes']['type'];
									$node_cidr = $row['@attributes']['cidr'];
									$node_description = $row['@attributes']['description'];

									//add the profile settings into the database
									$access_control_node_uuid = uuid();
									$array['access_control_nodes'][0]['access_control_node_uuid'] = $access_control_node_uuid;
									$array['access_control_nodes'][0]['access_control_uuid'] = $access_control_uuid;
									$array['access_control_nodes'][0]['node_type'] = $node_type;
									$array['access_control_nodes'][0]['node_cidr'] = $node_cidr;
									$array['access_control_nodes'][0]['node_description'] = $node_description;

									$permissions->add('access_control_node_add', 'temp');

									$database->app_name = 'access_controls';
									$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
									$database->save($array, false);
									unset($array);

									$permissions->delete('access_control_node_add', 'temp');
								}
							}
						}
					}

					//rename the file
					if (file_exists($xml_dir . '/acl.conf.xml')) {
						rename($xml_dir . '/acl.conf.xml', $xml_dir . '/acl.conf');
					}
				}
				unset($sql, $num_rows);

				//rename domains access control to providers
				$sql = "select count(*) from v_access_controls ";
				$sql .= "where access_control_name = 'domains' ";
				$num_rows = $database->select($sql, null, 'column');
				if ($num_rows > 0) {
					//update the access control name
					$sql = "update v_access_controls set access_control_name = 'providers' ";
					$sql .= "where access_control_name = 'domains' ";
					$database->execute($sql, null);
					unset($sql);

					//update the sip profile settings
					$sql = "update v_sip_profile_settings set sip_profile_setting_value = 'providers' ";
					$sql .= "where (sip_profile_setting_name = 'apply-inbound-acl' or sip_profile_setting_name = 'apply-register-acl') ";
					$sql .= "and sip_profile_setting_value = 'domains'; ";
					$database->execute($sql, null);
					unset($sql);

					//clear the cache
					$cache->delete("configuration:acl.conf");
					$cache->delete("configuration:sofia.conf:" . gethostname());

					//connect to the freeswitch socket if available
					$socket = new event_socket();
					//reload the acl
					if ($socket->is_connected()) {
						$socket->api("reloadacl");
						//rescan each sip profile
						$sql = "select sip_profile_name from v_sip_profiles ";
						$sql .= "where sip_profile_enabled = 'true'; ";
						$sip_profiles = $database->select($sql, null, 'all');
						if (is_array($sip_profiles)) {
							foreach ($sip_profiles as $row) {
								$socket->api("sofia profile '{$row['sip_profile_name']}' rescan");
							}
						}
					}
				}

				//remove orphaned access control nodes
				$sql = "delete from v_access_control_nodes ";
				$sql .= "where access_control_uuid not in ( ";
				$sql .= "	select access_control_uuid from v_access_controls ";
				$sql .= ")";
				$database->execute($sql);
			}
		}

	}
?>