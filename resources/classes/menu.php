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
	Copyright (C) 2010 - 2023
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * menu class
 */
	class menu {

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
		public $menu_uuid;
		public $menu_language;
		public $text;

		/**
		 * Set in the constructor. Must be a database object and cannot be null.
		 * @var database Database Object
		 */
		private $database;

		/**
		 * Settings object set in the constructor. Must be a settings object and cannot be null.
		 * @var settings Settings Object
		 */
		private $settings;

		/**
		 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		private $user_uuid;

		/**
		 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		private $domain_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct($setting_array = []) {
			//assign the variables
			$this->app_name = 'menus';
			$this->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
			$this->location = 'menus.php';

			$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
			$this->user_uuid = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

			//open a database connection
			if (empty($setting_array['database'])) {
				$this->database = database::new();
			} else {
				$this->database = $setting_array['database'];
			}

			//load the settings
			if (empty($setting_array['settings'])) {
				$this->settings = new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);
			} else {
				$this->settings = $setting_array['settings'];
			}

			//add multi-lingual support
			$this->text = (new text)->get();
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			//assign the variables
				$this->name = 'menu';
				$this->table = 'menus';

			if (permission_exists($this->name.'_delete')) {

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($this->text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//remove menu languages
										$array['menu_languages'][$x][$this->name.'_uuid'] = $record['uuid'];

									//remove menu item groups
										$array['menu_item_groups'][$x][$this->name.'_uuid'] = $record['uuid'];

									//remove menu items
										$array['menu_items'][$x][$this->name.'_uuid'] = $record['uuid'];

									//build array to remove the menu
										$array[$this->table][$x][$this->name.'_uuid'] = $record['uuid'];

									//increment
										$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//grant temporary permissions
									$p = permissions::new();
									$p->add('menu_item_delete', 'temp');
									$p->add('menu_item_group_delete', 'temp');
									$p->add('menu_language_delete', 'temp');

								//execute delete
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('menu_item_delete', 'temp');
									$p->delete('menu_item_group_delete', 'temp');
									$p->delete('menu_language_delete', 'temp');

								//set message
									message::add($this->text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_items($records) {
			//assign the variables
				$this->name = 'menu_item';
				$this->table = 'menu_items';

			if (permission_exists($this->name.'_delete')) {

				//validate the token
					$token = new token;
					if (!$token->validate('/core/menu/menu_item_list.php')) {
						message::add($this->text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//build array
										$uuids[] = "'".$record['uuid']."'";
									//remove menu languages
										$array['menu_languages'][$x][$this->name.'_uuid'] = $record['uuid'];
									//remove menu item groups
										$array['menu_item_groups'][$x][$this->name.'_uuid'] = $record['uuid'];
									//remove menu items
										$array[$this->table][$x][$this->name.'_uuid'] = $record['uuid'];
									//increment
										$x++;
								}
							}

						//include child menu items
							if (!empty($uuids) && @sizeof($uuids) != 0) {
								$sql = "select menu_item_uuid as uuid from v_".$this->table." ";
								$sql .= "where menu_item_parent_uuid in (".implode(', ', $uuids).") ";
								$rows = $this->database->select($sql, null, 'all');
								if (!empty($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										//remove menu languages
											$array['menu_languages'][$x][$this->name.'_uuid'] = $row['uuid'];
										//remove menu item groups
											$array['menu_item_groups'][$x][$this->name.'_uuid'] = $row['uuid'];
										//remove menu items
											$array[$this->table][$x][$this->name.'_uuid'] = $row['uuid'];
										//increment
											$x++;
									}
								}
							}

						//delete the checked rows
							if (!empty($array) && is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = permissions::new();
									$p->add('menu_language_delete', 'temp');
									$p->add('menu_item_group_delete', 'temp');

								//execute delete
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('menu_language_delete', 'temp');
									$p->delete('menu_item_group_delete', 'temp');

								//set message
									message::add($this->text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * toggle a field between two values
		 */
		public function toggle_items($records) {
			//assign the variables
				$this->name = 'menu_item';
				$this->table = 'menu_items';
				$this->toggle_field = 'menu_item_protected';
				$this->toggle_values = ['true','false'];

			if (permission_exists($this->name.'_edit')) {

				//validate the token
					$token = new token;
					if (!$token->validate('/core/menu/menu_item_list.php')) {
						message::add($this->text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (!empty($uuids) && is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$parameters = null;
								$rows = $this->database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'] == '' ? $this->toggle_values[1] : $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							if (!empty($states) && is_array($states) && @sizeof($states) != 0) {
								foreach ($states as $uuid => $state) {
									//create the array
										$array[$this->table][$x][$this->name.'_uuid'] = $uuid;
										$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

									//increment
										$x++;
								}
							}

						//save the changes
							if (!empty($array) && is_array($array) && @sizeof($array) != 0) {
								//save the array
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->save($array);
									unset($array);

								//set message
									message::add($this->text['message-toggle']);
							}
							unset($records, $states);
					}
			}
		}

		/**
		 * delete items in the menu used by restore default
		 */
		public function restore_delete() {
			//remove existing menu languages
				$sql  = "delete from v_menu_languages ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$sql .= "and menu_item_uuid in ( ";
				$sql .= "	select menu_item_uuid ";
				$sql .= "	from v_menu_items ";
				$sql .= "	where menu_uuid = :menu_uuid ";
				//$sql .= "	and ( ";
				//$sql .= " 		menu_item_protected <> 'true' ";
				//$sql .= "		or menu_item_protected is null ";
				//$sql .= "	) ";
				$sql .= ") ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$this->database->execute($sql, $parameters);
				unset($sql, $parameters);

			//remove existing menu item groups
				$sql = "delete from v_menu_item_groups ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$sql .= "and menu_item_uuid in ( ";
				$sql .= "	select menu_item_uuid ";
				$sql .= "	from v_menu_items ";
				$sql .= "	where menu_uuid = :menu_uuid ";
				//$sql .= "	and ( ";
				//$sql .= " 		menu_item_protected <> 'true' ";
				//$sql .= "		or menu_item_protected is null ";
				//$sql .= "	) ";
				$sql .= ") ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$this->database->execute($sql, $parameters);
				unset($sql, $parameters);

			//remove existing menu items
				$sql  = "delete from v_menu_items ";
				$sql .= "where menu_uuid = :menu_uuid ";
				//$sql .= "and ( ";
				//$sql .= "	menu_item_protected <> 'true' ";
				//$sql .= "	or menu_item_protected is null ";
				//$sql .= ") ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$this->database->execute($sql, $parameters);
				unset($sql, $parameters);
		}

		public function assign_items($records, $menu_uuid, $group_uuid) {
			//assign the variables
				$this->name = 'menu_item';
				$this->table = 'menu_items';

			if (permission_exists($this->name.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/menu/menu_item_list.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

					//assign multiple records
					if (is_array($records) && @sizeof($records) != 0 && !empty($group_uuid)) {

						//define the group_name, group_uuid, menu_uuid
							if (!empty($records) && @sizeof($records) != 0) {
								$sql = "select group_name, group_uuid from v_groups	";
								$sql .= "where group_uuid = :group_uuid	";
								$parameters['group_uuid'] = $group_uuid;
								$database = new database;
								$group = $database->select($sql, $parameters, 'row');
							}

						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//build array
										$uuids[] = "'".$record['uuid']."'";
									//assign menu item groups
											$array['menu_item_groups'][$x]['menu_item_group_uuid'] = uuid();
											$array['menu_item_groups'][$x]['menu_uuid'] = $menu_uuid;
											$array['menu_item_groups'][$x][$this->name.'_uuid'] = $record['uuid'];
											$array['menu_item_groups'][$x]['group_name'] = $group['group_name'];
											$array['menu_item_groups'][$x]['group_uuid'] = $group['group_uuid'];
									//increment
											$x++;
								}
							}

							unset($records);

						//exlude exist rows
						if (!empty($array) && @sizeof($array) != 0) {
							$sql = "select menu_uuid, menu_item_uuid, ";
							$sql .= "group_uuid from v_menu_item_groups ";
							$database = new database;
							$menu_item_groups = $database->select($sql, null, 'all');
							$array['menu_item_groups'] = array_filter($array['menu_item_groups'], function($ar) use ($menu_item_groups) {
								foreach ($menu_item_groups as $existingArrayItem) {
									if ($ar['menu_uuid'] == $existingArrayItem['menu_uuid'] && $ar['menu_item_uuid'] == $existingArrayItem['menu_item_uuid'] && $ar['group_uuid'] == $existingArrayItem['group_uuid']) {
										return false;
									}
								}
								return true;
							});
							unset($menu_item_groups);
						}

						//add the checked rows fro group
							if (!empty($array) && is_array($array) && @sizeof($array) != 0) {
								//execute save
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);
								//set message
									message::add($text['message-add']);
							}
					}
			}
		}

		public function unassign_items($records, $menu_uuid, $group_uuid) {
			//assign the variables
				$this->name = 'menu_item';
				$this->table = 'menu_items';

			if (permission_exists($this->name.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/menu/menu_item_list.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

					//assign multiple records
					if (is_array($records) && @sizeof($records) != 0 && !empty($group_uuid)) {

						//define the group_name, group_uuid, menu_uuid
							if (!empty($records) && @sizeof($records) != 0) {
								$sql = "select group_name, group_uuid from v_groups	";
								$sql .= "where group_uuid = :group_uuid	";
								$parameters['group_uuid'] = $group_uuid;
								$database = new database;
								$group = $database->select($sql, $parameters, 'row');
							}

						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//build array
										$uuids[] = "'".$record['uuid']."'";
									//assign menu item groups
											$array['menu_item_groups'][$x]['menu_uuid'] = $menu_uuid;
											$array['menu_item_groups'][$x][$this->name.'_uuid'] = $record['uuid'];
											$array['menu_item_groups'][$x]['group_name'] = $group['group_name'];
											$array['menu_item_groups'][$x]['group_uuid'] = $group['group_uuid'];
									//increment
											$x++;
								}
							}

							unset($records);

						//include child menu items and their main_uuid too
							if (!empty($uuids) && @sizeof($uuids) != 0) {
								$sql = "select menu_uuid, menu_item_uuid as uuid from v_".$this->table." ";
								$sql .= "where menu_item_parent_uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, null, 'all');
								if (!empty($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										//assign menu item groups
											$array['menu_item_groups'][$x]['menu_uuid'] = $row['menu_uuid'];
											$array['menu_item_groups'][$x][$this->name.'_uuid'] = $row['uuid'];
											$array['menu_item_groups'][$x]['group_name'] = $group['group_name'];
											$array['menu_item_groups'][$x]['group_uuid'] = $group['group_uuid'];
										//increment
											$x++;
									}
								}
							}

							unset($uuids);

						//add the checked rows fro group
							if (!empty($array) && is_array($array) && @sizeof($array) != 0) {
							//grant temporary permissions
								$p = new permissions;
								$p->add('menu_language_delete', 'temp');
								$p->add('menu_item_group_delete', 'temp');

							//execute delete
								$database = new database;
								$database->app_name = $this->app_name;
								$database->app_uuid = $this->app_uuid;
								$database->delete($array);
								unset($array);

							//revoke temporary permissions
								$p->delete('menu_language_delete', 'temp');
								$p->delete('menu_item_group_delete', 'temp');

							//set message
								message::add($text['message-delete']);
							}
					}
			}
		}

		/**
		 * restore the default menu
		 */
		public function restore_default() {

			//get the $apps array from the installed apps from the core and mod directories
				$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_menu.php");
				$x = 0;
				if (is_array($config_list)) {
					foreach ($config_list as $config_path) {
						$app_path = dirname($config_path);
						$app_path = preg_replace('/\A.*(\/.*\/.*)\z/', '$1', $app_path);
						$y = 0;
						try {
							//echo "[".$x ."] ".$config_path."\n";
							include($config_path);
							$x++;
						}
						catch (Exception $e) {
							echo 'exception caught: ' . $e->getMessage() . "\n";
							exit;
						}
					}
				}

			//get the list of languages
				$language = new text;

			//create a uuid array of the original uuid used as the key and new uuid as the value
				if (is_array($apps)) {
					$x = 0;
					foreach ($apps as $row) {
						if (is_array($row['menu'])) {
							foreach ($row['menu'] as $menu) {
								$uuid_array[$menu['uuid']] = uuid();
							}
						}
					}
				}

			//if the item uuid is not currently in the db then add it
				$sql = "select * from v_menu_items ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$menu_items = $this->database->select($sql, $parameters, 'all');

			//use the app array to restore the default menu
				if (is_array($apps)) {
					$x = 0;
					foreach ($apps as $row) {
						if (is_array($row['menu'])) {
							foreach ($row['menu'] as $menu) {
								//set the variables
									if (!empty($menu['title'][$this->menu_language])) {
										$menu_item_title = $menu['title'][$this->menu_language];
									}
									else {
										$menu_item_title = $menu['title']['en-us'];
									}
									$uuid = $menu['uuid'];
									$menu_item_uuid = $uuid_array[$menu['uuid']];
									$menu_item_parent_uuid = $uuid_array[$menu['parent_uuid']] ?? null;
									$menu_item_category = $menu['category'];
									$menu_item_icon = $menu['icon'] ?? null;
									$menu_item_icon_color = $menu['icon_color'] ?? null;
									$menu_item_path = $menu['path'];
									$menu_item_order = $menu['order'] ?? null;
									$menu_item_description = $menu['desc'] ?? null;

								//sanitize the menu link
									$menu_item_path = preg_replace('#[^a-zA-Z0-9_:\-\.\&\=\?\/]#', '', $menu_item_path);

								//check if the menu item exists and if it does set the row array
									$menu_item_exists = false;
									foreach ($menu_items as $item) {
										if ($item['uuid'] == $menu['uuid']) {
											$menu_item_exists = true;
											$row = $item;
										}
									}

								//item exists in the database
									if ($menu_item_exists) {
										$parent_menu_item_protected = 'false';
										//get parent_menu_item_protected
										foreach ($menu_items as $item) {
											if ($item['uuid'] == $menu['parent_uuid']) {
												$parent_menu_item_protected = $item['menu_item_protected'];
											}
										}

										//parent is not protected so the parent uuid needs to be updated
										if (is_uuid($menu_item_parent_uuid) && $menu_item_parent_uuid != $row['menu_item_parent_uuid'] && $parent_menu_item_protected != 'true') {
											$array['menu_items'][$x]['menu_item_uuid'] = $row['menu_item_uuid'];
											$array['menu_items'][$x]['menu_item_parent_uuid'] = $menu_item_parent_uuid;
											$x++;
										}
									}

								//item does not exist in the database
									if (!$menu_item_exists) {
										if ($menu_item_uuid != $menu_item_parent_uuid) {
												$array['menu_items'][$x]['menu_item_uuid'] = $menu_item_uuid;
												$array['menu_items'][$x]['menu_uuid'] = $this->menu_uuid;
												$array['menu_items'][$x]['uuid'] = $uuid;
												$array['menu_items'][$x]['menu_item_title'] = $menu_item_title;
												$array['menu_items'][$x]['menu_item_link'] = $menu_item_path;
												$array['menu_items'][$x]['menu_item_category'] = $menu_item_category;
												$array['menu_items'][$x]['menu_item_icon'] = $menu_item_icon;
												$array['menu_items'][$x]['menu_item_icon_color'] = $menu_item_icon_color;
												if (!empty($menu_item_order)) {
													$array['menu_items'][$x]['menu_item_order'] = $menu_item_order;
												}
												if (is_uuid($menu_item_parent_uuid)) {
													$array['menu_items'][$x]['menu_item_parent_uuid'] = $menu_item_parent_uuid;
												}
												$array['menu_items'][$x]['menu_item_description'] = $menu_item_description;
												$x++;
										}
									}
									unset($field, $parameters, $num_rows);

								//set the menu languages
									if (!$menu_item_exists && is_array($language->languages)) {
										foreach ($language->languages as $menu_language) {
											//set the menu item title
												if (!empty($menu["title"][$menu_language])) {
													$menu_item_title = $menu["title"][$menu_language];
												}
												else {
													$menu_item_title = $menu["title"]['en-us'];
												}

											//build insert array
												$array['menu_languages'][$x]['menu_language_uuid'] = uuid();
												$array['menu_languages'][$x]['menu_item_uuid'] = $menu_item_uuid;
												$array['menu_languages'][$x]['menu_uuid'] = $this->menu_uuid;
												$array['menu_languages'][$x]['menu_language'] = $menu_language;
												$array['menu_languages'][$x]['menu_item_title'] = $menu_item_title;
												$x++;
										}
									}
							}
						}
					}
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = permissions::new();
							$p->add('menu_item_add', 'temp');
							$p->add('menu_language_add', 'temp');
						//execute insert
							$this->database->app_name = 'menu';
							$this->database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
							$this->database->save($array);
							unset($array);
						//revoke temporary permissions
							$p->delete('menu_item_add', 'temp');
							$p->delete('menu_language_add', 'temp');
					}
				}

			//make sure the default user groups exist
				$group = new groups;
				$group->defaults();

			//get default global group_uuids
				$sql = "select group_uuid, group_name from v_groups ";
				$sql .= "where domain_uuid is null ";
				$result = $this->database->select($sql, null, 'all');
				if (is_array($result) && @sizeof($result) != 0) {
					foreach ($result as $row) {
						$group_uuids[$row['group_name']] = $row['group_uuid'];
					}
				}
				unset($sql, $result, $row);

			//if there are no groups listed in v_menu_item_groups under menu_item_uuid then add the default groups
				if (is_array($apps)) {
					$x = 0;
					foreach($apps as $app) {
						if (is_array($apps)) {
							foreach ($app['menu'] as $sub_row) {
								if (isset($sub_row['groups'])) {
									foreach ($sub_row['groups'] as $group) {
										$sql = "select count(*) from v_menu_item_groups ";
										$sql .= "where menu_item_uuid = :menu_item_uuid ";
										$sql .= "and menu_uuid = :menu_uuid ";
										$sql .= "and group_name = :group_name ";
										$sql .= "and group_uuid = :group_uuid ";
										$parameters['menu_item_uuid'] = $uuid_array[$sub_row['uuid']];
										$parameters['menu_uuid'] = $this->menu_uuid;
										$parameters['group_name'] = $group;
										$parameters['group_uuid'] = $group_uuids[$group] ?? null;
										$num_rows = $this->database->select($sql, $parameters, 'column');
										if ($num_rows == 0) {
											//no menu item groups found, build insert array for defaults
												$array['menu_item_groups'][$x]['menu_item_group_uuid'] = uuid();
												$array['menu_item_groups'][$x]['menu_uuid'] = $this->menu_uuid;
												$array['menu_item_groups'][$x]['menu_item_uuid'] = $uuid_array[$sub_row['uuid']];
												$array['menu_item_groups'][$x]['group_name'] = $group;
												$array['menu_item_groups'][$x]['group_uuid'] = $group_uuids[$group] ?? null;
												$x++;
										}
										unset($sql, $parameters, $num_rows);
									}
								}
							}
						}
					}

					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = permissions::new();
							$p->add('menu_item_group_add', 'temp');
						//execute insert
							$this->database->app_name = 'menu';
							$this->database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
							$this->database->save($array);
							unset($array);
						//revoke temporary permissions
							$p->delete('menu_item_group_add', 'temp');
					}
				}

		}

		/**
		 * create the menu
		 */
		public function build_html($menu_item_level = 0) {

			$menu_html_full = '';

			$menu_array = $this->menu_array();

			if (!isset($_SESSION['groups'])) {
				$_SESSION['groups'][0]['group_name'] = 'public';
			}

			if (is_array($menu_array)) {
				foreach($menu_array as $menu_field) {
					//set the variables
					$menu_item_link = $menu_field['menu_item_link'];
					$menu_item_category = $menu_field['menu_item_category'];
					$menu_items = $menu_field['menu_items'];

					//prepare the protected menus
					//$menu_item_title = ($menu_field['menu_item_protected'] == "true") ? $menu_field['menu_item_title'] : $menu_field['menu_language_title'];
					$menu_item_title = $menu_field['menu_language_title'];

					//prepare the menu_tags according to the category
					$menu_tags = '';
					switch ($menu_item_category) {
						case "internal":
							$menu_tags = "href='".PROJECT_PATH.$submenu_item_link."'";
							break;
						case "external":
							if (substr($submenu_item_link, 0,1) == "/") {
								$submenu_item_link = PROJECT_PATH.$submenu_item_link;
							}
							$menu_tags = "href='".$submenu_item_link."' target='_blank'";
							break;
						case "email":
							$menu_tags = "href='mailto:".$submenu_item_link."'";
							break;
					}

					if ($menu_item_level == 0) {
						$menu_html  = "<ul class='menu_main'>\n";
						$menu_html .= "<li>\n";
						if (!isset($_SESSION["username"])) {
							$_SESSION["username"] = '';
						}
						if (empty($_SESSION["username"])) {
							$menu_html .= "<a $menu_tags style='padding: 0px 0px; border-style: none; background: none;'><h2 align='center' style=''>".$menu_item_title."</h2></a>\n";
						}
						else {
							if ($submenu_item_link == "/login.php" || $submenu_item_link == "/users/signup.php") {
								//hide login and sign-up when the user is logged in
							}
							else {
								if (empty($submenu_item_link)) {
									$menu_html .= "<h2 align='center' style=''>".$menu_item_title."</h2>\n";
								}
								else {
									$menu_html .= "<a ".$menu_tags." style='padding: 0px 0px; border-style: none; background: none;'><h2 align='center' style=''>".$menu_item_title."</h2></a>\n";
								}
							}
						}
					}

					if (is_array($menu_field['menu_items']) && count($menu_field['menu_items']) > 0) {
						$menu_html .= $this->build_child_html($menu_item_level, $menu_field['menu_items']);
					}

					if ($menu_item_level == 0) {
						$menu_html .= "</li>\n";
						$menu_html .= "</ul>\n\n";
					}

					$menu_html_full .= $menu_html;
				} //end for each
			}

			return $menu_html_full;
		}

		/**
		 * create the sub menus
		 */
		private function build_child_html($menu_item_level, $submenu_array) {

			$menu_item_level = $menu_item_level+1;

			if (count($_SESSION['groups']) == 0) {
				$_SESSION['groups'][0]['group_name'] = 'public';
			}

			if (is_array($submenu_array)) {
				//child menu found
				$submenu_html = "<ul class='menu_sub'>\n";

				foreach($submenu_array as $submenu_field) {
					//set the variables
						$menu_item_link = $submenu_field['menu_item_link'];
						$menu_item_category = $submenu_field['menu_item_category'];
						$menu_items = $submenu_field['menu_items'];

					//prepare the protected menus
						//$menu_item_title = ($submenu_field['menu_item_protected'] == "true") ? $submenu_field['menu_item_title'] : $submenu_field['menu_language_title'];
						$menu_item_title = $submenu_field['menu_language_title'];

					//prepare the menu_tags according to the category
						switch ($menu_item_category) {
							case "internal":
								$menu_tags = "href='".PROJECT_PATH.$menu_item_link."'";
								break;
							case "external":
								if (substr($menu_item_link, 0,1) == "/") {
									$menu_item_link = PROJECT_PATH.$menu_item_link;
								}
								$menu_tags = "href='".$menu_item_link."' target='_blank'";
								break;
							case "email":
								$menu_tags = "href='mailto:".$menu_item_link."'";
								break;
						}

					$submenu_html .= "<li>";

					//get sub menu for children
						if (is_array($menu_items) && count($menu_items) > 0) {
							$str_child_menu = $this->build_child_html($menu_item_level, $menu_items);
						}

					if (strlen($str_child_menu) > 1) {
						$submenu_html .= "<a ".$menu_tags.">".$menu_item_title."</a>";
						$submenu_html .= $str_child_menu;
						unset($str_child_menu);
					}
					else {
						$submenu_html .= "<a ".$menu_tags.">".$menu_item_title."</a>";
					}
					$submenu_html .= "</li>\n";
				}
				unset($submenu_array);

				$submenu_html .="</ul>\n";

				return $submenu_html;
			}
		}

		/**
		 * create the menu array
		 */
		public function menu_array($menu_item_level = 0) {

			//if there are no groups then set the public group
				if (!isset($_SESSION['groups'][0]['group_name'])) {
					$_SESSION['groups'][0]['group_name'] = 'public';
				}

			//get the menu from the database
				$sql = "select i.menu_item_link, l.menu_item_title as menu_language_title, ";
				$sql .= "i.menu_item_title, i.menu_item_category, i.menu_item_icon, ";
				$sql .= "i.menu_item_icon_color, i.menu_item_uuid, i.menu_item_parent_uuid ";
				$sql .= "from v_menu_items as i, v_menu_languages as l ";
				$sql .= "where i.menu_item_uuid = l.menu_item_uuid ";
				$sql .= "and l.menu_language = :menu_language ";
				$sql .= "and l.menu_uuid = :menu_uuid ";
				$sql .= "and i.menu_uuid = :menu_uuid ";
				$sql .= "and i.menu_item_parent_uuid is null ";
				$sql .= "and i.menu_item_uuid in ";
				$sql .= "( ";
				$sql .= "select menu_item_uuid ";
				$sql .= "from v_menu_item_groups ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$x = 0;
				foreach($_SESSION['groups'] as $row) {
					$sql_where_or[] = "group_name = :group_name_".$x;
					$parameters['group_name_'.$x] = $row['group_name'];
					$x++;
				}
				if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
					$sql .= "and ( ";
					$sql .= implode(' or ', $sql_where_or);
					$sql .= ") ";
				}
				$sql .= "and menu_item_uuid is not null ";
				$sql .= ") ";
				$sql .= "order by i.menu_item_order asc ";
				$parameters['menu_language'] = $this->settings->get('domain', 'language', 'en-us');
				$parameters['menu_uuid'] = $this->menu_uuid;
				$result = $this->database->select($sql, $parameters, 'all');
				unset($sql, $parameters);

			//save the menu into an array
				$x = 0;
				$a = Array();
				if (is_array($result) && @sizeof($result) != 0) {
					foreach($result as $row) {
						//add the row to the array
							$a[$x] = $row;

						//add the sub menus to the array
							$menu_item_level = 0;
							if (!empty($row['menu_item_uuid'])) {
								$a[$x]['menu_items'] = $this->menu_child_array($menu_item_level, $row['menu_item_uuid']);
							}

						//increment the row number
							$x++;
					}
				}
				unset($result, $row);

			//return the array
				return $a;
		}

		/**
		 * create the sub menus
		 */
		private function menu_child_array($menu_item_level, $menu_item_uuid) {

			//set the level
				$menu_item_level++;

			//if there are no groups then set the public group
				if (!isset($_SESSION['groups'][0]['group_name'])) {
					$_SESSION['groups'][0]['group_name'] = 'public';
				}

			//get the child menu from the database
				$sql = "select i.menu_item_link, l.menu_item_title as menu_language_title, ";
				$sql .= "i.menu_item_title, i.menu_item_category, i.menu_item_icon, ";
				$sql .= "i.menu_item_icon_color, i.menu_item_uuid, i.menu_item_parent_uuid ";
				$sql .= "from v_menu_items as i, v_menu_languages as l ";
				$sql .= "where i.menu_item_uuid = l.menu_item_uuid ";
				$sql .= "and l.menu_language = :menu_language ";
				$sql .= "and l.menu_uuid = :menu_uuid ";
				$sql .= "and i.menu_uuid = :menu_uuid ";
				$sql .= "and i.menu_item_parent_uuid = :menu_item_parent_uuid ";
				$sql .= "and i.menu_item_uuid in ";
				$sql .= "( ";
				$sql .= "select menu_item_uuid ";
				$sql .= "from v_menu_item_groups ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$x = 0;
				foreach($_SESSION['groups'] as $row) {
					$sql_where_or[] = "group_name = :group_name_".$x;
					$parameters['group_name_'.$x] = $row['group_name'];
					$x++;
				}
				if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
					$sql .= "and ( ";
					$sql .= implode(' or ', $sql_where_or);
					$sql .= ") ";
				}
				$sql .= ") ";
				$sql .= "order by l.menu_item_title, i.menu_item_order asc ";
				$parameters['menu_language'] = $this->settings->get('domain', 'language', 'en-us');
				$parameters['menu_uuid'] = $this->menu_uuid;
				$parameters['menu_item_parent_uuid'] = $menu_item_uuid;
				$sub_result = $this->database->select($sql, $parameters, 'all');
				unset($sql, $parameters);

			//save the child menu into an array
				$x = 0;
				$a = Array();
				if (is_array($sub_result) && @sizeof($sub_result) != 0) {
					foreach($sub_result as $row) {
						//set the variables
							$menu_item_link = $row['menu_item_link'];
							$menu_item_category = $row['menu_item_category'];
							$menu_item_icon = $row['menu_item_icon'];
							$menu_item_icon_color = $row['menu_item_icon_color'];
							$menu_item_uuid = $row['menu_item_uuid'];
							$menu_item_parent_uuid = $row['menu_item_parent_uuid'];

						//add the row to the array
							$a[$x] = $row;

						//prepare the menus
							//if ($row['menu_item_protected'] == "true") {
							//	$a[$x]['menu_item_title'] = $row['menu_item_title'];
							//}
							//else {
							$a[$x]['menu_item_title'] = $row['menu_language_title'];
							//}

						//get sub menu for children
							if (!empty($menu_item_uuid)) {
								$a[$x]['menu_items'] = $this->menu_child_array($menu_item_level, $menu_item_uuid);
							}

						//increment the row
							$x++;
					}
				}
				unset($sub_result, $row);

			//return the array
				return $a;
		}

		/**
		 * add the default menu when no menu exists
		 */
		public function menu_default() {
			//set the default menu_uuid
				$this->menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
			//check to see if any menu exists
				$sql = "select count(*) as count from v_menus ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$num_rows = $this->database->select($sql, $parameters, 'column');
				if ($num_rows == 0) {
					//built insert array
						$array['menus'][0]['menu_uuid'] = $this->menu_uuid;
						$array['menus'][0]['menu_name'] = 'default';
						$array['menus'][0]['menu_language'] = 'en-us';
						$array['menus'][0]['menu_description'] = 'Default Menu';

					//grant temporary permissions
						$p = permissions::new();
						$p->add('menu_add', 'temp');

					//execute insert
						$this->database->app_name = 'menu';
						$this->database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
						$this->database->save($array);
						unset($array);

					//revoke temporary permissions
						$p->delete('menu_add', 'temp');

					//add the menu items
						$this->restore_default();
				}
		}

		/**
		 * build the fixed, static or inline horizontal menu html
		 * @param array $menu_array Associative array of menu items
		 */
		public function menu_horizontal($menu_array) {

			//determine menu behavior
				$menu_style = $this->settings->get('theme', 'menu_style', 'fixed');
				switch ($menu_style) {
					case 'inline':
						$menu_type = 'default';
						$menu_width = 'calc(100% - 20px)';
						$menu_brand = false;
						$menu_corners = null;
						break;
					case 'static':
						$menu_type = 'static-top';
						$menu_width = 'calc(100% - 40px)';
						$menu_brand = true;
						$menu_corners = "style='-webkit-border-radius: 0 0 4px 4px; -moz-border-radius: 0 0 4px 4px; border-radius: 0 0 4px 4px;'";
						break;
					case 'fixed':
					default:
						$menu_type = 'fixed-'.$this->settings->get('theme', 'menu_position', 'top');
						if (!http_user_agent('mobile')) {
							$menu_width = $this->settings->get('theme', 'menu_width_fixed', 'calc(90% - 20px)');
						}
						$menu_brand = true;
						$menu_corners = null;
				}

			//begin navbar code
				$html = "<nav class='navbar navbar-expand-sm ".$menu_type."' ".$menu_corners.">\n";
				$html .= "	<div class='container-fluid' style='width: ".($menu_width ?? '100%')."; padding: 0;'>\n";
				$html .= "		<div class='navbar-brand'>\n";

				if ($menu_brand) {
					//define menu brand mark
						$menu_brand_text = escape($this->settings->get('theme', 'menu_brand_text', 'FusionPBX'));
						switch ($this->settings->get('theme', 'menu_brand_type', '')) {
							case 'text':
								$html .= "			<a class='navbar-brand-text' href='".PROJECT_PATH."/'>".$menu_brand_text."</a>\n";
								break;
							case 'image_text':
								$menu_brand_image = escape($this->settings->get('theme', 'menu_brand_image', PROJECT_PATH.'/themes/default/images/logo.png'));
								$html .= "			<a href='".PROJECT_PATH."/'>";
								$html .= "				<img id='menu_brand_image' class='navbar-logo' src='".$menu_brand_image."' title=\"".escape($menu_brand_text)."\">";
								if (!empty($this->settings->get('theme', 'menu_brand_image_hover'))) {
									$html .= 			"<img id='menu_brand_image_hover' class='navbar-logo' style='display: none;' src='".$this->settings->get('theme', 'menu_brand_image_hover')."' title=\"".escape($menu_brand_text)."\">";
								}
								$html .= 			"</a>\n";
								$html .= "			<a class='navbar-brand-text' href='".PROJECT_PATH."/'>".$menu_brand_text."</a>\n";
								break;
							case 'none':
								break;
							case 'image':
							default:
								$menu_brand_image = escape($this->settings->get('theme', 'menu_brand_image', PROJECT_PATH.'/themes/default/images/logo.png'));
								$html .= "			<a href='".PROJECT_PATH."/'>";
								$html .= "				<img id='menu_brand_image' class='navbar-logo' src='".$menu_brand_image."' title=\"".escape($menu_brand_text)."\">";
								if (!empty($this->settings->get('theme', 'menu_brand_image_hover', ''))) {
									$html .= 			"<img id='menu_brand_image_hover' class='navbar-logo' style='display: none;' src='".$this->settings->get('theme', 'menu_brand_image_hover')."' title=\"".escape($menu_brand_text)."\">";
								}
								$html .= 			"</a>\n";
								$html .= "			<a style='margin: 0;'></a>\n";
						}
				}

				$html .= "		</div>\n";

				$html .= "		<button type='button' class='navbar-toggler' data-toggle='collapse' data-target='#main_navbar' aria-expanded='false' aria-controls='main_navbar' aria-label='Toggle Menu' onclick=\"$('#body_header_user_menu').fadeOut(200);\">\n";
				$html .= "			<span class='fa-solid fa-bars'></span>\n";
				$html .= "		</button>\n";

				$html .= "		<div class='collapse navbar-collapse' id='main_navbar'>\n";
				$html .= "			<ul class='navbar-nav'>\n";

				if (!empty($menu_array) && sizeof($menu_array) != 0) {
					foreach ($menu_array as $index_main => $menu_parent) {
						$mod_li = "nav-item";
						$mod_a_1 = "";
						$submenu = false;
						if (!empty($menu_parent['menu_items']) && sizeof($menu_parent['menu_items']) > 0) {
							$mod_li = "nav-item dropdown ";
							$mod_a_1 = "data-toggle='dropdown' ";
							$submenu = true;
						}
						$mod_a_2 = (!empty($menu_parent['menu_item_link']) && !$submenu) ? $menu_parent['menu_item_link'] : '#';
						$mod_a_3 = ($menu_parent['menu_item_category'] == 'external') ? "target='_blank' " : null;
						if ($this->settings->get('theme', 'menu_main_icons', true) === true) {
							if (!empty($menu_parent['menu_item_icon']) && substr($menu_parent['menu_item_icon'], 0, 3) == 'fa-') { // font awesome icon
								$menu_main_icon = "<span class='".escape($menu_parent['menu_item_icon'])."' ".(!empty($menu_parent['menu_item_icon_color']) ? "style='color: ".$menu_parent['menu_item_icon_color']." !important;'" : null)." title=\"".escape($menu_parent['menu_language_title'])."\"></span>";
							}
							else {
								$menu_main_icon = null;
							}
							$menu_main_item = "<span class='d-sm-none d-md-none d-lg-inline' style='margin-left: 5px;'>".$menu_parent['menu_language_title']."</span>\n";
						}
						else {
							$menu_main_item = $menu_parent['menu_language_title'];
						}
						$html .= "				<li class='".$mod_li."'>\n";
						$html .= "					<a class='nav-link' ".$mod_a_1." href='".$mod_a_2."' ".$mod_a_3.">\n";
						$html .= "						".$menu_main_icon.$menu_main_item;
						$html .= "					</a>\n";
						if ($submenu) {
							$columns = @sizeof($menu_parent['menu_items']) > 20 ? 2 : 1;
							$column_current = 1;
							$mod_ul = $columns > 1 ? 'multi-column' : null;
							$html .= "					<ul class='dropdown-menu ".$mod_ul."'>\n";
							if ($columns > 1) {
								$html .= "						<div class='row'>\n";
								$html .= "							<div class='col-12 col-sm-6 pr-sm-0'>\n";
								$html .= "								<ul class='multi-column-dropdown'>\n";
							}
							foreach ($menu_parent['menu_items'] as $index_sub => $menu_sub) {
								$mod_a_2 = $menu_sub['menu_item_link'];
								if ($mod_a_2 == '') {
									$mod_a_2 = '#';
								}
								$mod_a_3 = ($menu_sub['menu_item_category'] == 'external') ? "target='_blank' " : null;
								$menu_sub_icon = null;
								if ($this->settings->get('theme', 'menu_sub_icons', true) !== false) {
									if (!empty($menu_sub['menu_item_icon']) && substr($menu_sub['menu_item_icon'], 0, 3) == 'fa-') { // font awesome icon
										$menu_sub_icon = "<span class='".escape($menu_sub['menu_item_icon'])."' style='".(!empty($menu_sub['menu_item_icon_color']) ? "color: ".$menu_sub['menu_item_icon_color']." !important;" : "opacity: 0.3;")."'></span>";
									}
									else {
										$menu_sub_icon = null;
									}
								}
								$html .= "						<li class='nav-item'><a class='nav-link' href='".$mod_a_2."' ".$mod_a_3." onclick='event.stopPropagation();'>".($this->settings->get('theme', 'menu_sub_icons', true) != false ? "<span class='fa-solid fa-minus d-inline-block d-sm-none float-left' style='margin: 4px 10px 0 25px;'></span>" : '').escape($menu_sub['menu_language_title']).$menu_sub_icon."</a></li>\n";
								if ($columns > 1 && $column_current == 1 && ($index_sub+1) > (ceil(@sizeof($menu_parent['menu_items'])/2)-1)) {
									$html .= "								</ul>\n";
									$html .= "							</div>\n";
									$html .= "							<div class='col-12 col-sm-6 pl-sm-0'>\n";
									$html .= "								<ul class='multi-column-dropdown'>\n";
									$column_current = 2;
								}

							}
							if ($columns > 1) {
								$html .= "								</ul>\n";
								$html .= "							</div>\n";
								$html .= "						</div>\n";
							}
							$html .= "					</ul>\n";
						}
						$html .= "				</li>\n";
					}
				}
				$html .= "			</ul>\n";

				$html .= "			<ul class='navbar-nav ml-auto'>\n";
				//current user (latter condition for backward compatibility)
					if (
						!empty($_SESSION['username']) &&
							$this->settings->get('theme', 'header_user_visible', 'true') == 'true' &&	//app_defaults schema data type is 'text' but should be boolean here
							$this->settings->get('theme', 'user_visible', 'true') == 'true'				//app_defaults schema data type is 'text' but should be boolean here
					) {
						//set (default) user graphic size and icon
						$user_graphic = "<i class='".$this->settings->get('theme', 'body_header_icon_user', 'fa-solid fa-user-circle')."'></i>";

						//overwrite user graphic with image from session, if exists
						if ($this->settings->get('theme', 'body_header_user_image', true) == true && !empty($_SESSION['user']['contact_image']) && is_uuid($_SESSION['user']['contact_image'])) {
							$user_graphic = "<span style=\"display: inline-block; vertical-align: middle; width: 15px; height: 15px; border-radius: 50%; margin-top: -2px; background-image: url('".PROJECT_PATH."/core/contacts/contact_attachment.php?id=".$_SESSION['user']['contact_image']."&action=download&sid=".session_id()."'); background-repeat: no-repeat; background-size: cover; background-position: center;\"></span>";
						}
						$html .= "		<li class='nav-item'>\n";
						$html .= "			<a class='nav-link header_user d-block d-sm-none' href='show:usermenu' title=\"".$_SESSION['username']."\" style='border-top: 1px solid ".($this->settings->get('theme', 'menu_sub_background_color') ?? 'rgba(0,0,0,0.90)')."' data-toggle='collapse' data-target='#main_navbar' onclick=\"event.preventDefault(); $('#body_header_user_menu').toggleFadeSlide();\">".($user_graphic ?? null)."<span style='margin-left: 7px;'>".escape($_SESSION['username'])."</span></a>";
						$html .= "			<a class='nav-link header_user d-none d-sm-block' href='show:usermenu' title=\"".$_SESSION['username']."\" onclick=\"event.preventDefault(); $('#body_header_user_menu').toggleFadeSlide();\">".($user_graphic ?? null)."<span class='d-none d-md-inline' style='margin-left: 7px;'>".escape($_SESSION['username'])."</span></a>";
						$html .= "		</li>\n";
					}

				//domain name/selector
					if (permission_exists('domain_select') && $this->settings->get('theme', 'domain_visible', 'true') == 'true' && !empty($_SESSION['username']) && !empty($_SESSION['domains']) && count($_SESSION['domains']) > 1) {
						$html .= "		<li class='nav-item'>\n";
						$html .= "			<a class='nav-link header_domain header_domain_selector_domain d-block d-sm-none' href='select:domain' onclick='event.preventDefault();' data-toggle='collapse' data-target='#main_navbar' title='".$this->text['theme-label-open_selector']."'><span class='".$this->settings->get('theme', 'body_header_icon_domain', 'fa-solid fa-earth-americas')."'></span><span style='margin-left: 7px;'>".escape($_SESSION['domain_name'])."</span></a>";
						$html .= "			<a class='nav-link header_domain header_domain_selector_domain d-none d-sm-block' href='select:domain' onclick='event.preventDefault();' title='".$this->text['theme-label-open_selector']."'><span class='".$this->settings->get('theme', 'body_header_icon_domain', 'fa-solid fa-earth-americas')."'></span><span class='d-none d-md-inline' style='margin-left: 7px;'>".escape($_SESSION['domain_name'])."</span></a>";
						$html .= "		</li>\n";
					}

				//logout icon
					if (!empty($_SESSION['username']) && isset($_SESSION['theme']['logout_icon_visible']) && $this->settings->get('theme', 'logout_icon_visible', 'false') == "true") {
						$username_full = $_SESSION['username'].((count($_SESSION['domains']) > 1) ? "@".$_SESSION["user_context"] : null);
						$html .= "		<li class='nav-item'>\n";
						$html .= "			<a class='logout_icon' href='#' title=\"".$this->text['theme-label-logout']."\" onclick=\"modal_open('modal-logout','btn_logout');\"><span class='fa-solid fa-right-from-bracket'></span></a>";
						$html .= "		</li>\n";
						unset($username_full);
					}
				$html .= "			</ul>\n";

				$html .= "		</div>\n";
				$html .= "	</div>\n";
				$html .= "</nav>\n";

				//user menu on menu bar
					//styles below are defined here to prevent caching (following a permission change, etc)
					$html .= "<style>\n";
					$html .= "div#body_header_user_menu {\n";
					$html .= "	right: ".(permission_exists('domain_select') ? '170px' : '30px')." !important;\n";
					$html .= "	}\n";
					$html .= "@media (max-width: 575.98px) {\n";
					$html .= "	div#body_header_user_menu {\n";
					$html .= "		right: 10px !important;;\n";
					$html .= "		}\n";
					$html .= "	}\n";
					$html .= "</style>\n";

					$html .= "<div id='body_header_user_menu'>\n";
					$html .= "	<div class='row m-0'>\n";
					if (!empty($_SESSION['user']['contact_image']) && is_uuid($_SESSION['user']['contact_image'])) {
						$html .= "	<div class='col-5 col-sm-6 p-0' style=\"min-width: 130px; background-image: url('".PROJECT_PATH."/core/contacts/contact_attachment.php?id=".$_SESSION['user']['contact_image']."&action=download&sid=".session_id()."'); background-repeat: no-repeat; background-size: cover; background-position: center;\"></div>\n";
					}
					else {
						$html .= "	<div class='col-5 col-sm-6 p-0 pt-1' style=\"min-width: 130px; cursor: help;\" title=\"".$this->text['label-primary-contact-attachment-image']."\"><i class='fa-solid fa-user-circle fa-8x' style='opacity: 0.1;'></i></div>\n";
					}
					// $html .= "	<div class='".(!empty($_SESSION['user']['contact_image']) && is_uuid($_SESSION['user']['contact_image']) ? 'col-7 col-sm-6 pr-0' : 'col-12 p-0')." ' style='min-width: 130px; text-align: left;'>\n";
					$html .= "		<div class='col-7 col-sm-6 pr-0' style='min-width: 130px; text-align: left;'>\n";
					if (!empty($_SESSION['user']['contact_name'])) {
						$html .= "		<div style='line-height: 95%;'><strong>".$_SESSION['user']['contact_name']."</strong></div>\n";
					}
					if (!empty($_SESSION['user']['contact_organization'])) {
						$html .= "		<div class='mt-2' style='font-size: 85%; line-height: 95%;'>".$_SESSION['user']['contact_organization']."</div>\n";
					}
					if (!empty($_SESSION['user']['extension'][0]['destination'])) {
						$html .= "		<div class='mt-2' style='font-size: 90%;'><i class='fa-solid fa-phone' style='margin-right: 5px; color: #00b043;'></i><strong>".$_SESSION['user']['extension'][0]['destination']."</strong></div>\n";
					}
					$html .= "			<div class='pt-2 mt-3' style='border-top: 1px solid ".color_adjust($this->settings->get('theme', 'body_header_shadow_color'), 0.05).";'>\n";
					$html .= "				<a href='".PROJECT_PATH."/core/users/user_edit.php?id=user'>".$this->text['title-account_settings']."</a><br>\n";
					$html .= "				<a href='".PROJECT_PATH."/logout.php'>".$this->text['title-logout']."</a>\n";
					$html .= "			</div>";
					$html .= "		</div>";
					$html .= "	</div>";
					$html .= "</div>";

				//modal for logout icon (above)
					if (!empty($_SESSION['username']) && isset($_SESSION['theme']['logout_icon_visible']) && $this->settings->get('theme', 'logout_icon_visible', 'false') == "true") {
						$html .= modal::create(['id'=>'modal-logout','type'=>'general','message'=>$this->text['theme-confirm-logout'],'actions'=>button::create(['type'=>'button','label'=>$this->text['theme-label-logout'],'icon'=>'fa-solid fa-right-from-bracket','id'=>'btn_logout','style'=>'float: right; margin-left: 15px;','collapse'=>'never','link'=>PROJECT_PATH.'/logout.php','onclick'=>"modal_close();"])]);
					}

				return $html;
		}

		/**
		 * build the vertical side menu html
		 * @param array $menu_array Associative array of menu items
		 */
		public function menu_vertical($menu_array) {
			//set defaults
				$menu_side_state = $this->settings->get('theme', 'menu_side_state', 'contracted');
				$menu_side_state_class = $menu_side_state !== 'hidden' ? 'hide-sm-up ' : '';
			//menu brand image and/or text
				$html = "	<div id='menu_side_control_container'>\n";
				$html .= "		<div class='menu_side_control_state' style='float: right; ".($menu_side_state != 'expanded' ? "display: none;" : null)."'>\n";
				if ($this->settings->get('theme', 'menu_brand_type') != 'none') {
					$html .= "		<a class='menu_side_item_main menu_side_contract' onclick='menu_side_contract();' style='height: 60px; padding: 19px 16px 8px 16px !important; ".($menu_side_state != 'expanded' ? "display: none;" : null)."'><i class='fa-solid fa-bars fa-fw'></i></a>";
				}
				$html .= "		</div>\n";
				$menu_brand_text = escape($this->settings->get('theme', 'menu_brand_text', 'FusionPBX'));
				switch ($this->settings->get('theme', 'menu_brand_type', '')) {
					case 'none':
						$html .= "<a class='menu_side_item_main menu_side_contract' onclick='menu_side_contract();' style='".($menu_side_state != 'expanded' ? "display: none;" : null)." height: 60px; min-width: ".intval($this->settings->get('theme', 'menu_side_width_contracted', 60))."px;' title=\"".$this->text['theme-label-contract_menu']."\"><i class='fa-solid fa-bars fa-fw' style='z-index: 99800; padding-left: 1px; padding-top: 11px;'></i></a>";
						$html .= "<a class='menu_side_item_main menu_side_expand' onclick='menu_side_expand();' style='".($menu_side_state == 'expanded' ? "display: none;" : null)." height: 60px;' title=\"".$this->text['theme-label-expand_menu']."\"><i class='fa-solid fa-bars fa-fw' style='z-index: 99800; padding-left: 1px; padding-top: 11px;'></i></a>";
						break;
					case 'text':
						$html .= "<a class='menu_brand_text' style='".($menu_side_state != 'expanded' ? "display: none;" : null)."' href='".PROJECT_PATH."/'>".escape($menu_brand_text)."</a>\n";
						$html .= "<a class='menu_side_item_main menu_side_expand' style='height: 60px; padding-top: 19px; ".($menu_side_state == 'expanded' ? "display: none;" : null)."' onclick='menu_side_expand();' title=\"".$this->text['theme-label-expand_menu']."\"><i class='fa-solid fa-bars fa-fw' style='z-index: 99800; padding-left: 1px;'></i></a>";
						break;
					case 'image_text':
						$menu_brand_image_contracted = $this->settings->get('theme', 'menu_side_brand_image_contracted', PROJECT_PATH.'/themes/default/images/logo_side_contracted.png');
						$html .= "<a class='menu_brand_image' href='".PROJECT_PATH."/'>";
						$html .= 	"<img id='menu_brand_image_contracted' style='".($menu_side_state == 'expanded' ? "display: none;" : null)."' src='".escape($menu_brand_image_contracted)."' title=\"".escape($menu_brand_text)."\">";
						$html .= 	"<span id='menu_brand_image_expanded' class='menu_brand_text' style='".($menu_side_state != 'expanded' ? "display: none;" : null)."'>".escape($menu_brand_text)."</span>";
						$html .= "</a>\n";
						break;
					case 'image':
					default:
						$menu_brand_image_contracted = $this->settings->get('theme', 'menu_side_brand_image_contracted', PROJECT_PATH.'/themes/default/images/logo_side_contracted.png');
						$menu_brand_image_expanded = $this->settings->get('theme', 'menu_side_brand_image_expanded', PROJECT_PATH.'/themes/default/images/logo_side_expanded.png');
						$html .= "<a class='menu_brand_image' href='".PROJECT_PATH."/'>";
						$html .= 	"<img id='menu_brand_image_contracted' style='".($menu_side_state == 'expanded' ? "display: none;" : null)."' src='".escape($menu_brand_image_contracted)."' title=\"".escape($menu_brand_text)."\">";
						$html .= 	"<img id='menu_brand_image_expanded' style='".($menu_side_state != 'expanded' ? "display: none;" : null)."' src='".escape($menu_brand_image_expanded)."' title=\"".escape($menu_brand_text)."\">";
						$html .= "</a>\n";
						break;
				}
				$html .= "	</div>\n";
			//main menu items
				if (!empty($menu_array)) {
					foreach ($menu_array as $menu_item_main) {
						$menu_target = ($menu_item_main['menu_item_category'] == 'external') ? '_blank' : '';
						$html .= "	<a class='menu_side_item_main' ".(!empty($menu_item_main['menu_item_link']) ? "href='".$menu_item_main['menu_item_link']."' target='".$menu_target."'" : "onclick=\"menu_side_expand(); menu_side_item_toggle('".$menu_item_main['menu_item_uuid']."');\"")." title=\"".$menu_item_main['menu_language_title']."\">";
						if (is_array($menu_item_main['menu_items']) && sizeof($menu_item_main['menu_items']) != 0 && $this->settings->get('theme', 'menu_side_item_main_sub_icons', true) === true) {
							$html .= "	<div class='menu_side_item_main_sub_icons' style='float: right; margin-right: -1px; ".($menu_side_state != 'expanded' ? "display: none;" : null)."'><i id='sub_arrow_".$menu_item_main['menu_item_uuid']."' class='sub_arrows ".$this->settings->get('theme', 'menu_side_item_main_sub_icon_expand', 'fa-solid fa-chevron-down')." fa-xs'></i></div>\n";
						}
						if (!empty($menu_item_main['menu_item_icon']) && substr($menu_item_main['menu_item_icon'], 0, 3) == 'fa-') { // font awesome icon
							$html .= "<i class='menu_side_item_icon ".$menu_item_main['menu_item_icon']." fa-fw' style='z-index: 99800; margin-right: 8px; ".(!empty($menu_item_main['menu_item_icon_color']) ? "color: ".$menu_item_main['menu_item_icon_color']." !important;" : null)."'></i>";
						}
						$html .= "<span class='menu_side_item_title' style='".($menu_side_state != 'expanded' ? "display: none;" : null)."'>".$menu_item_main['menu_language_title']."</span>";
						$html .= "</a>\n";
						//sub menu items
							if (is_array($menu_item_main['menu_items']) && sizeof($menu_item_main['menu_items']) != 0) {
								$html .= "	<div id='sub_".$menu_item_main['menu_item_uuid']."' class='menu_side_sub' style='display: none;'>\n";
								foreach ($menu_item_main['menu_items'] as $menu_item_sub) {
									$menu_sub_icon = null;
									if ($this->settings->get('theme', 'menu_sub_icons', true) !== false) {
										if (!empty($menu_item_sub['menu_item_icon']) && substr($menu_item_sub['menu_item_icon'], 0, 3) == 'fa-') { // font awesome icon
											$menu_sub_icon = "<span class='".escape($menu_item_sub['menu_item_icon']).(substr($menu_item_sub['menu_item_icon'], 0, 3) == 'fa-' ? ' fa-fw' : null)."' style='".(!empty($menu_item_sub['menu_item_icon_color']) ? "color: ".$menu_item_sub['menu_item_icon_color']." !important;" : "opacity: 0.3;")."'></span>";
										}
										else {
											$menu_sub_icon = null;
										}
									}
									$html .= "		<a class='menu_side_item_sub' ".($menu_item_sub['menu_item_category'] == 'external' ? "target='_blank'" : null)." href='".$menu_item_sub['menu_item_link']."'>";
									$html .= 			"<span class='menu_side_item_title' style='".($menu_side_state != 'expanded' ? "display: none;" : null)."'>".$menu_item_sub['menu_language_title']."</span>";
									$html .= 		$menu_sub_icon."</a>\n";
								}
								$html .= "	</div>\n";
							}
					}
					$html .= "	<div style='height: 100px;'></div>\n";
				}
			$html .= "</div>\n";
			$content_container_onclick = "";
			if ($menu_side_state != 'expanded') {
				$content_container_onclick = "onclick=\"clearTimeout(menu_side_contract_timer); if ($(window).width() >= 576) { menu_side_contract(); }\"";
			}
			$html .= "<div id='content_container' ".$content_container_onclick.">\n";

			//user menu on body header when side menu
				//styles below are defined here to prevent caching (following a permission change, etc)
				$html .= "<style>\n";
				$html .= "div#body_header_user_menu {\n";
				$html .= "	right: ".(permission_exists('domain_select') ? '170px' : '30px')." !important;\n";
				$html .= "	}\n";
				$html .= "@media (max-width: 575.98px) {\n";
				$html .= "	div#body_header_user_menu {\n";
				$html .= "		right: 10px !important;;\n";
				$html .= "		}\n";
				$html .= "	}\n";
				$html .= "</style>\n";

				$html .= "<div id='body_header_user_menu'>\n";
				$html .= "	<div class='row m-0'>\n";
				if (!empty($_SESSION['user']['contact_image']) && is_uuid($_SESSION['user']['contact_image'])) {
					$html .= "	<div class='col-5 col-sm-6 p-0' style=\"min-width: 130px; background-image: url('".PROJECT_PATH."/core/contacts/contact_attachment.php?id=".$_SESSION['user']['contact_image']."&action=download&sid=".session_id()."'); background-repeat: no-repeat; background-size: cover; background-position: center;\"></div>\n";
				}
				else {
					$html .= "	<div class='col-5 col-sm-6 p-0 pt-1' style=\"min-width: 130px; cursor: help;\" title=\"".$this->text['label-primary-contact-attachment-image']."\"><i class='fa-solid fa-user-circle fa-8x' style='opacity: 0.1;'></i></div>\n";
				}
				// $html .= "	<div class='".(!empty($_SESSION['user']['contact_image']) && is_uuid($_SESSION['user']['contact_image']) ? 'col-7 col-sm-6 pr-0' : 'col-12 p-0')." ' style='min-width: 130px; text-align: left;'>\n";
				$html .= "		<div class='col-7 col-sm-6 pr-0' style='min-width: 130px; text-align: left;'>\n";
				if (!empty($_SESSION['user']['contact_name'])) {
					$html .= "		<div style='line-height: 95%;'><strong>".$_SESSION['user']['contact_name']."</strong></div>\n";
				}
				if (!empty($_SESSION['user']['contact_organization'])) {
					$html .= "		<div class='mt-2' style='font-size: 85%; line-height: 95%;'>".$_SESSION['user']['contact_organization']."</div>\n";
				}
				if (!empty($_SESSION['user']['extension'][0]['destination'])) {
					$html .= "		<div class='mt-2' style='font-size: 90%;'><i class='fa-solid fa-phone' style='margin-right: 5px; color: #00b043;'></i><strong>".$_SESSION['user']['extension'][0]['destination']."</strong></div>\n";
				}
				$html .= "			<div class='pt-2 mt-3' style='border-top: 1px solid ".color_adjust($this->settings->get('theme', 'body_header_shadow_color'), 0.05).";'>\n";
				$html .= "				<a href='".PROJECT_PATH."/core/users/user_edit.php?id=user'>".$this->text['title-account_settings']."</a><br>\n";
				$html .= "				<a href='".PROJECT_PATH."/logout.php'>".$this->text['title-logout']."</a>\n";
				$html .= "			</div>";
				$html .= "		</div>";
				$html .= "	</div>";
				$html .= "</div>";

			$html .= "	<div id='body_header'>\n";
			//header: left
				$html .= "<div class='float-left'>\n";
				// $html .= button::create(['type'=>'button','id'=>'menu_side_state_hidden_button','title'=>$this->text['theme-label-expand_menu'],'icon'=>'bars','class'=>'default '.($this->settings->get('theme', 'menu_side_state') != 'hidden' ? 'hide-sm-up ' : null).'float-left','onclick'=>'menu_side_expand();']);
				$html .= "<a id='menu_side_state_hidden_button' class='$menu_side_state_class' href='show:menu' onclick=\"event.preventDefault(); menu_side_expand(); event.stopPropagation();\" title=\"".$this->text['theme-label-expand_menu']."\"><i class='fa-solid fa-bars fa-fw' style='margin: 7px 10px 5px 10px;'></i></a>";
				$body_header_brand_text = escape($this->settings->get('theme', 'body_header_brand_text', 'FusionPBX'));
				if ($this->settings->get('theme', 'body_header_brand_type') == 'image' || $this->settings->get('theme', 'body_header_brand_type') == 'image_text') {
					$body_header_brand_image = $this->settings->get('theme', 'body_header_brand_image', PROJECT_PATH.'/themes/default/images/logo_side_expanded.png');
					$html .= 	"<div id='body_header_brand_image'>";
					$html .= 		"<a href='".PROJECT_PATH."/'><img id='body_header_brand_image' src='".escape($body_header_brand_image)."' title=\"".escape($body_header_brand_text)."\"></a>";
					$html .= 	"</div>";
				}
				if ($this->settings->get('theme', 'body_header_brand_type') == 'text' || $this->settings->get('theme', 'body_header_brand_type') == 'image_text') {
					$html .= 	"<div id='body_header_brand_text'><a href='".PROJECT_PATH."/'>".$body_header_brand_text."</a></div>";
				}
				$html .= "</div>\n";
			//header: right
				$html .= "<div class='float-right' style='white-space: nowrap;'>";
				//current user
					//set (default) user graphic size and icon
					$user_graphic_size = 18;
					$user_graphic = "<i class='".$this->settings->get('theme', 'body_header_icon_user', 'fa-solid fa-user-circle')." fa-lg fa-fw' style='margin-right: 5px;'></i>";
					//overwrite user graphic with image from session, if exists
					if ($this->settings->get('theme', 'body_header_user_image', true) === true && !empty($_SESSION['user']['contact_image']) && is_uuid($_SESSION['user']['contact_image'])) {
						$user_graphic_size = str_replace(['px','%'], '', intval($this->settings->get('theme', 'body_header_user_image_size', 18)));
						$user_graphic = "<span style=\"display: inline-block; vertical-align: middle; width: ".$user_graphic_size."px; height: ".$user_graphic_size."px; border-radius: 50%; margin-right: 7px; margin-top: ".($user_graphic_size > 18 ? '-'.(ceil(($user_graphic_size - 18) / 2) - 4) : '-4')."px; background-image: url('".PROJECT_PATH."/core/contacts/contact_attachment.php?id=".$_SESSION['user']['contact_image']."&action=download&sid=".session_id()."'); background-repeat: no-repeat; background-size: cover; background-position: center;\"></span>";
					}
					$html .= "<span style='display: inline-block; padding-right: 20px; font-size: 90%;'>\n";
					$html .= "	<a href='show:usermenu' title=\"".$_SESSION['username']."\" onclick=\"event.preventDefault(); $('#body_header_user_menu').toggleFadeSlide();\">".($user_graphic ?? null)."<span class='d-none d-sm-inline'>".escape($_SESSION['username'])."</span></a>";
					$html .= "</span>\n";
				//domain name/selector (sm+)
					if (!empty($_SESSION['username']) && permission_exists('domain_select') && count($_SESSION['domains']) > 1 && $this->settings->get('theme', 'domain_visible') == 'true') {
						$html .= "<span style='display: inline-block; padding-right: 10px; font-size: 90%;'>\n";
						$html .= "	<a href='select:domain' onclick='event.preventDefault();' title='".$this->text['theme-label-open_selector']."' class='header_domain_selector_domain'><i class='".$this->settings->get('theme', 'body_header_icon_domain', 'fa-solid fa-earth-americas')." fa-fw' style='vertical-align: middle; font-size: ".($user_graphic_size - 1)."px; margin-top: ".($user_graphic_size > 18 ? '-'.(ceil(($user_graphic_size - 18) / 2) - 4) : '-3')."px; margin-right: 3px; line-height: 40%;'></i><span class='d-none d-sm-inline'>".escape($_SESSION['domain_name'])."</span></a>";
						$html .= "</span>\n";
					}
				//logout icon
					if (!empty($_SESSION['username']) && $this->settings->get('theme', 'logout_icon_visible') == "true") {
						$html .= "<a id='header_logout_icon' href='#' title=\"".$this->text['theme-label-logout']."\" onclick=\"modal_open('modal-logout','btn_logout');\"><span class='fa-solid fa-right-from-bracket'></span></a>";
					}
				$html .= "</div>";
			$html .= "	</div>\n";

			//modal for logout icon (above)
				if (!empty($_SESSION['username']) && $this->settings->get('theme', 'logout_icon_visible') == "true") {
					$html .= modal::create(['id'=>'modal-logout','type'=>'general','message'=>$this->text['theme-confirm-logout'],'actions'=>button::create(['type'=>'button','label'=>$this->text['theme-label-logout'],'icon'=>'fa-solid fa-right-from-bracket','id'=>'btn_logout','style'=>'float: right; margin-left: 15px;','collapse'=>'never','link'=>PROJECT_PATH.'/logout.php','onclick'=>"modal_close();"])]);
				}

			return $html;
		}

	}
