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
	Copyright (C) 2010 - 2022
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * menu class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('menu')) {
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
		private $description_field;
		private $location;
		public $menu_uuid;
		public $menu_language;
		public $text;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'menus';
				$this->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
				$this->location = 'menus.php';
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
		 * delete rows from the database
		 */
		public function delete($records) {
			//assign the variables
				$this->name = 'menu';
				$this->table = 'menus';

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
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
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
									$p = new permissions;
									$p->add('menu_item_delete', 'temp');
									$p->add('menu_item_group_delete', 'temp');
									$p->add('menu_language_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('menu_item_delete', 'temp');
									$p->delete('menu_item_group_delete', 'temp');
									$p->delete('menu_language_delete', 'temp');

								//set message
									message::add($text['message-delete']);
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

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
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
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select menu_item_uuid as uuid from v_".$this->table." ";
								$sql .= "where menu_item_parent_uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
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
							if (is_array($array) && @sizeof($array) != 0) {

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

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'] == '' ? $this->toggle_values[1] : $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach ($states as $uuid => $state) {
								//create the array
									$array[$this->table][$x][$this->name.'_uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

								//increment
									$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}
			}
		}

		/**
		 * delete items in the menu that are not protected
		 */
		public function delete_unprotected() {
			//remove existing menu languages
				$sql  = "delete from v_menu_languages ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$sql .= "and menu_item_uuid in ( ";
				$sql .= "	select menu_item_uuid ";
				$sql .= "	from v_menu_items ";
				$sql .= "	where menu_uuid = :menu_uuid ";
				$sql .= "	and ( ";
				$sql .= " 		menu_item_protected <> 'true' ";
				$sql .= "		or menu_item_protected is null ";
				$sql .= "	) ";
				$sql .= ") ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$database = new database;
				$database->execute($sql, $parameters);
				unset($sql, $parameters);

			//remove existing unprotected menu item groups
				$sql = "delete from v_menu_item_groups ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$sql .= "and menu_item_uuid in ( ";
				$sql .= "	select menu_item_uuid ";
				$sql .= "	from v_menu_items ";
				$sql .= "	where menu_uuid = :menu_uuid ";
				$sql .= "	and ( ";
				$sql .= " 		menu_item_protected <> 'true' ";
				$sql .= "		or menu_item_protected is null ";
				$sql .= "	) ";
				$sql .= ") ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$database = new database;
				$database->execute($sql, $parameters);
				unset($sql, $parameters);

			//remove existing unprotected menu items
				$sql  = "delete from v_menu_items ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$sql .= "and ( ";
				$sql .= "	menu_item_protected <> 'true' ";
				$sql .= "	or menu_item_protected is null ";
				$sql .= ") ";
				$parameters['menu_uuid'] = $this->menu_uuid;
				$database = new database;
				$database->execute($sql, $parameters);
				unset($sql, $parameters);
		}

		/**
		 * restore the menu
		 */
		public function restore() {

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
				$database = new database;
				$menu_items = $database->select($sql, $parameters, 'all');

			//use the app array to restore the default menu
				if (is_array($apps)) {
					$x = 0;
					foreach ($apps as $row) {
						if (is_array($row['menu'])) {
							foreach ($row['menu'] as $menu) {
								//set the variables
									if (strlen($menu['title'][$this->menu_language]) > 0) {
										$menu_item_title = $menu['title'][$this->menu_language];
									}
									else {
										$menu_item_title = $menu['title']['en-us'];
									}
									$uuid = $menu['uuid'];
									$menu_item_uuid = $uuid_array[$menu['uuid']];
									$menu_item_parent_uuid = $uuid_array[$menu['parent_uuid']];
									$menu_item_category = $menu['category'];
									$menu_item_icon = $menu['icon'];
									$menu_item_path = $menu['path'];
									$menu_item_order = $menu['order'];
									$menu_item_description = $menu['desc'];

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
												if (strlen($menu_item_order) > 0) {
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
												$menu_item_title = $menu["title"][$menu_language];
												if (strlen($menu_item_title) == 0) {
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
							$p = new permissions;
							$p->add('menu_item_add', 'temp');
							$p->add('menu_language_add', 'temp');
						//execute insert
							$database = new database;
							$database->app_name = 'menu';
							$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
							$database->save($array);
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
				$database = new database;
				$result = $database->select($sql, null, 'all');
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
										$parameters['group_uuid'] = $group_uuids[$group];
										$database = new database;
										$num_rows = $database->select($sql, $parameters, 'column');
										if ($num_rows == 0) {
											//no menu item groups found, build insert array for defaults
												$array['menu_item_groups'][$x]['menu_item_group_uuid'] = uuid();
												$array['menu_item_groups'][$x]['menu_uuid'] = $this->menu_uuid;
												$array['menu_item_groups'][$x]['menu_item_uuid'] = $uuid_array[$sub_row['uuid']];
												$array['menu_item_groups'][$x]['group_name'] = $group;
												$array['menu_item_groups'][$x]['group_uuid'] = $group_uuids[$group];
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
							$p = new permissions;
							$p->add('menu_item_group_add', 'temp');
						//execute insert
							$database = new database;
							$database->app_name = 'menu';
							$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
							$database->save($array);
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
					$menu_item_title = ($menu_field['menu_item_protected'] == "true") ? $menu_field['menu_item_title'] : $menu_field['menu_language_title'];

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
						if (strlen($_SESSION["username"]) == 0) {
							$menu_html .= "<a $menu_tags style='padding: 0px 0px; border-style: none; background: none;'><h2 align='center' style=''>".$menu_item_title."</h2></a>\n";
						}
						else {
							if ($submenu_item_link == "/login.php" || $submenu_item_link == "/users/signup.php") {
								//hide login and sign-up when the user is logged in
							}
							else {
								if (strlen($submenu_item_link) == 0) {
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
						$menu_item_title = ($submenu_field['menu_item_protected'] == "true") ? $submenu_field['menu_item_title'] : $submenu_field['menu_language_title'];

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
				$sql .= "i.menu_item_title, i.menu_item_protected, i.menu_item_category, ";
				$sql .= "i.menu_item_icon, i.menu_item_uuid, i.menu_item_parent_uuid ";
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
				$parameters['menu_language'] = $_SESSION['domain']['language']['code'];
				$parameters['menu_uuid'] = $this->menu_uuid;
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');
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
							if (strlen($row['menu_item_uuid']) > 0) {
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
				$menu_item_level = $menu_item_level + 1;

			//if there are no groups then set the public group
				if (!isset($_SESSION['groups'][0]['group_name'])) {
					$_SESSION['groups'][0]['group_name'] = 'public';
				}

			//get the child menu from the database
				$sql = "select i.menu_item_link, l.menu_item_title as menu_language_title, i.menu_item_title, i.menu_item_protected, i.menu_item_category, i.menu_item_icon, i.menu_item_uuid, i.menu_item_parent_uuid ";
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
				$parameters['menu_language'] = $_SESSION['domain']['language']['code'];
				$parameters['menu_uuid'] = $this->menu_uuid;
				$parameters['menu_item_parent_uuid'] = $menu_item_uuid;
				$database = new database;
				$sub_result = $database->select($sql, $parameters, 'all');
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
							$menu_item_uuid = $row['menu_item_uuid'];
							$menu_item_parent_uuid = $row['menu_item_parent_uuid'];

						//add the row to the array
							$a[$x] = $row;

						//prepare the protected menus
							if ($row['menu_item_protected'] == "true") {
								$a[$x]['menu_item_title'] = $row['menu_item_title'];
							}
							else {
								$a[$x]['menu_item_title'] = $row['menu_language_title'];
							}

						//get sub menu for children
							if (strlen($menu_item_uuid) > 0) {
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
				$database = new database;
				$num_rows = $database->select($sql, $parameters, 'column');
				if ($num_rows == 0) {
					//built insert array
						$array['menus'][0]['menu_uuid'] = $this->menu_uuid;
						$array['menus'][0]['menu_name'] = 'default';
						$array['menus'][0]['menu_language'] = 'en-us';
						$array['menus'][0]['menu_description'] = 'Default Menu';

					//grant temporary permissions
						$p = new permissions;
						$p->add('menu_add', 'temp');

					//execute insert
						$database = new database;
						$database->app_name = 'menu';
						$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
						$database->save($array);
						unset($array);

					//revoke temporary permissions
						$p->delete('menu_add', 'temp');

					//add the menu items
						$this->restore();
				}
				unset($sql, $parameters, $result, $row);
		}

		/**
		 * build the fixed, static or inline horizontal menu html
		 */
		public function menu_horizontal($menu_array) {

			//determine menu behavior
				$menu_style = $_SESSION['theme']['menu_style']['text'] != '' ? $_SESSION['theme']['menu_style']['text'] : 'fixed';
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
						$menu_type = 'fixed-'.($_SESSION['theme']['menu_position']['text'] != '' ? $_SESSION['theme']['menu_position']['text'] : 'top');
						if (!http_user_agent('mobile')) {
							$menu_width = $_SESSION['theme']['menu_width_fixed']['text'] != '' ? $_SESSION['theme']['menu_width_fixed']['text'] : 'calc(90% - 20px)';
						}
						$menu_brand = true;
						$menu_corners = null;
				}

			//begin navbar code
				$html = "<nav class='navbar navbar-expand-sm ".$menu_type."' ".$menu_corners.">\n";
				$html .= "	<div class='container-fluid' style='width: ".$menu_width."; padding: 0;'>\n";
				$html .= "		<div class='navbar-brand'>\n";

				if ($menu_brand) {
					//define menu brand mark
						$menu_brand_text = ($_SESSION['theme']['menu_brand_text']['text'] != '') ? escape($_SESSION['theme']['menu_brand_text']['text']) : "FusionPBX";
						switch ($_SESSION['theme']['menu_brand_type']['text']) {
							case 'text':
								$html .= "			<a class='navbar-brand-text' href='".PROJECT_PATH."/'>".$menu_brand_text."</a>\n";
								break;
							case 'image_text':
								$menu_brand_image = ($_SESSION['theme']['menu_brand_image']['text'] != '') ? escape($_SESSION['theme']['menu_brand_image']['text']) : PROJECT_PATH."/themes/default/images/logo.png";
								$html .= "			<a href='".PROJECT_PATH."/'>";
								$html .= "				<img id='menu_brand_image' class='navbar-logo' src='".$menu_brand_image."' title=\"".escape($menu_brand_text)."\">";
								if ($_SESSION['theme']['menu_brand_image_hover']['text'] != '') {
									$html .= 			"<img id='menu_brand_image_hover' class='navbar-logo' style='display: none;' src='".$_SESSION['theme']['menu_brand_image_hover']['text']."' title=\"".escape($menu_brand_text)."\">";
								}
								$html .= 			"</a>\n";
								$html .= "			<a class='navbar-brand-text' href='".PROJECT_PATH."/'>".$menu_brand_text."</a>\n";
								break;
							case 'none':
								break;
							case 'image':
							default:
								$menu_brand_image = ($_SESSION['theme']['menu_brand_image']['text'] != '') ? escape($_SESSION['theme']['menu_brand_image']['text']) : PROJECT_PATH."/themes/default/images/logo.png";
								$html .= "			<a href='".PROJECT_PATH."/'>";
								$html .= "				<img id='menu_brand_image' class='navbar-logo' src='".$menu_brand_image."' title=\"".escape($menu_brand_text)."\">";
								if (isset($_SESSION['theme']['menu_brand_image_hover']['text']) && $_SESSION['theme']['menu_brand_image_hover']['text'] != '') {
									$html .= 			"<img id='menu_brand_image_hover' class='navbar-logo' style='display: none;' src='".$_SESSION['theme']['menu_brand_image_hover']['text']."' title=\"".escape($menu_brand_text)."\">";
								}
								$html .= 			"</a>\n";
								$html .= "			<a style='margin: 0;'></a>\n";
						}
				}

				$html .= "		</div>\n";

				$html .= "		<button type='button' class='navbar-toggler' data-toggle='collapse' data-target='#main_navbar' aria-expanded='false' aria-controls='main_navbar' aria-label='Toggle Menu'>\n";
				$html .= "			<span class='fas fa-bars'></span>\n";
				$html .= "		</button>\n";

				$html .= "		<div class='collapse navbar-collapse' id='main_navbar'>\n";
				$html .= "			<ul class='navbar-nav'>\n";

				if (is_array($menu_array) && sizeof($menu_array) != 0) {
					foreach ($menu_array as $index_main => $menu_parent) {
						$mod_li = "nav-item";
						$mod_a_1 = "";
						$submenu = false;
						if (is_array($menu_parent['menu_items']) && sizeof($menu_parent['menu_items']) > 0) {
							$mod_li = "nav-item dropdown ";
							$mod_a_1 = "data-toggle='dropdown' ";
							$submenu = true;
						}
						$mod_a_2 = ($menu_parent['menu_item_link'] != '' && !$submenu) ? $menu_parent['menu_item_link'] : '#';
						$mod_a_3 = ($menu_parent['menu_item_category'] == 'external') ? "target='_blank' " : null;
						if (isset($_SESSION['theme']['menu_main_icons']['boolean']) && $_SESSION['theme']['menu_main_icons']['boolean'] == 'true') {
							if ($menu_parent['menu_item_icon'] != '' && substr_count($menu_parent['menu_item_icon'], 'fa-') > 0) {
								$menu_main_icon = "<span class='fas ".$menu_parent['menu_item_icon']."' title=\"".escape($menu_parent['menu_language_title'])."\"></span>\n";
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
							$html .= "					<ul class='dropdown-menu'>\n";
							foreach ($menu_parent['menu_items'] as $index_sub => $menu_sub) {
								$mod_a_2 = $menu_sub['menu_item_link'];
								if ($mod_a_2 == '') {
									$mod_a_2 = '#';
								}
								$mod_a_3 = ($menu_sub['menu_item_category'] == 'external') ? "target='_blank' " : null;
								$menu_sub_icon = null;
								if ($_SESSION['theme']['menu_sub_icons']['boolean'] != 'false') {
									if ($menu_sub['menu_item_icon'] != '' && substr_count($menu_sub['menu_item_icon'], 'fa-') > 0) {
										$menu_sub_icon = "<span class='fas ".escape($menu_sub['menu_item_icon'])."'></span>";
									}
									else {
										$menu_sub_icon = null;
									}
								}
								$html .= "						<li class='nav-item'><a class='nav-link' href='".$mod_a_2."' ".$mod_a_3.">".($_SESSION['theme']['menu_sub_icons'] ? "<span class='fas fa-bar d-inline-block d-sm-none float-left' style='margin: 4px 10px 0 25px;'></span>" : null).escape($menu_sub['menu_language_title']).$menu_sub_icon."</a></li>\n";
							}
							$html .= "					</ul>\n";
						}
						$html .= "				</li>\n";
					}
				}
				$html .= "			</ul>\n";

				$html .= "			<ul class='navbar-nav ml-auto'>\n";
				//current user
					if ($_SESSION['theme']['user_visible']['text'] == 'true') {
						$html .= "		<li class='nav-item'>\n";
						$html .= "			<a class='header_user' href='".PROJECT_PATH."/core/users/user_edit.php?id=user' title=\"".$this->text['theme-label-user']."\"><i class='fas fa-".($_SESSION['theme']['body_header_icon_user']['text'] != '' ? $_SESSION['theme']['body_header_icon_user']['text'] : 'user-circle')." fa-lg fa-fw' style='margin-top: 6px; margin-right: 5px;'></i>".$_SESSION['username']."</a>";
						$html .= "		</li>\n";
					}
				//domain name/selector
					if (isset($_SESSION['username']) && $_SESSION['username'] != '' && permission_exists('domain_select') && count($_SESSION['domains']) > 1 && $_SESSION['theme']['domain_visible']['text'] == 'true') {
						$html .= "		<li class='nav-item'>\n";
						$html .= "			<a class='header_domain' href='#' id='header_domain_selector_domain' title='".$this->text['theme-label-open_selector']."'><i class='fas fa-".($_SESSION['theme']['body_header_icon_domain']['text'] != '' ? $_SESSION['theme']['body_header_icon_domain']['text'] : 'globe-americas')." fa-lg fa-fw' style='margin-top: 6px; margin-right: 5px;'></i>".escape($_SESSION['domain_name'])."</a>";
						$html .= "		</li>\n";
					}
				//logout icon
					if (isset($_SESSION['username']) && $_SESSION['username'] != '' && $_SESSION['theme']['logout_icon_visible']['text'] == "true") {
						$username_full = $_SESSION['username'].((count($_SESSION['domains']) > 1) ? "@".$_SESSION["user_context"] : null);
						$html .= "		<li class='nav-item'>\n";
						$html .= "			<a class='logout_icon' href='#' title=\"".$this->text['theme-label-logout']."\" onclick=\"modal_open('modal-logout','btn_logout');\"><span class='fas fa-sign-out-alt'></span></a>";
						$html .= "		</li>\n";
						unset($username_full);
					}
				$html .= "			</ul>\n";

				$html .= "		</div>\n";
				$html .= "	</div>\n";
				$html .= "</nav>\n";

				//modal for logout icon (above)
					if (isset($_SESSION['username']) && $_SESSION['username'] != '' && $_SESSION['theme']['logout_icon_visible']['text'] == "true") {
						$html .= modal::create(['id'=>'modal-logout','type'=>'general','message'=>$this->text['theme-confirm-logout'],'actions'=>button::create(['type'=>'button','label'=>$this->text['theme-label-logout'],'icon'=>'sign-out-alt','id'=>'btn_logout','style'=>'float: right; margin-left: 15px;','collapse'=>'never','link'=>PROJECT_PATH.'/logout.php','onclick'=>"modal_close();"])]);
					}

				return $html;
				unset($html);

		}

		/**
		 * build the vertical side menu html
		 */
		public function menu_vertical($menu_array) {

			//menu brand image and/or text
				$html .= "	<div id='menu_side_control_container'>\n";
				$html .= "		<div class='menu_side_control_state' style='float: right; ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? 'display: none' : null)."'>\n";
				if ($_SESSION['theme']['menu_brand_type']['text'] != 'none') {
					$html .= "		<a class='menu_side_item_main menu_side_contract' onclick='menu_side_contract();' style='padding: 8px 15px !important; ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? "display: none;" : null)."'><i class='fas fa-bars fa-fw'></i></a>\n";
				}
				if ($_SESSION['theme']['menu_side_pin']['boolean'] == 'true') {
					$html .= "		<a class='menu_side_item_main' id='menu_side_state_set_expanded' onclick=\"menu_side_state_set('expanded');\" oncontextmenu=\"menu_side_state_set('delete'); return false;\" style='padding: 8px 14px 8px 16px !important; ".($_SESSION['theme']['menu_side_state']['text'] == 'expanded' ? 'display: none' : null)."' title=\"".$this->text['theme-label-pin_menu']."\"><i class='fas fa-toggle-off fa-sm fa-fw'></i></a>\n";
					$html .= "		<a class='menu_side_item_main' id='menu_side_state_set_contracted' onclick=\"menu_side_state_set('contracted');\" oncontextmenu=\"menu_side_state_set('delete'); return false;\" style='padding: 8px 14px 8px 16px !important; ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? 'display: none' : null)."' title=\"".$this->text['theme-label-unpin_menu']."\"><i class='fas fa-toggle-on fa-sm fa-fw'></i></a>\n";
				}
				$html .= "		</div>\n";
				if ($_SESSION['theme']['menu_brand_type']['text'] == 'none') {
					$html .= "		<a class='menu_side_item_main menu_side_contract' onclick='menu_side_contract();' style='".($_SESSION['theme']['menu_side_pin']['boolean'] == 'true' ? "max-width: calc(100% - 50px);" : null)." ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? "display: none;" : null)."' title=\"".$this->text['theme-label-contract_menu']."\"><i class='fas fa-bars fa-fw' style='z-index: 99800; padding-left: 1px;'></i></a>";
				}
				$menu_brand_text = $_SESSION['theme']['menu_brand_text']['text'] != '' ? escape($_SESSION['theme']['menu_brand_text']['text']) : "FusionPBX";
				if ($_SESSION['theme']['menu_brand_type']['text'] == 'text') {
					$html .= "		<a class='menu_brand_text' ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? "style='display: none;'" : null)." href='".PROJECT_PATH."/'>".$menu_brand_text."</a>\n";
				}
				if ($_SESSION['theme']['menu_brand_type']['text'] == 'image' || $_SESSION['theme']['menu_brand_type']['text'] == 'image_text' || $_SESSION['theme']['menu_brand_type']['text'] == '') {
					$menu_brand_image_contracted = $_SESSION['theme']['menu_side_brand_image_contracted']['text'] != '' ? $_SESSION['theme']['menu_side_brand_image_contracted']['text'] : PROJECT_PATH."/themes/default/images/logo_side_contracted.png";
					$menu_brand_image_expanded = $_SESSION['theme']['menu_side_brand_image_expanded']['text'] != '' ? $_SESSION['theme']['menu_side_brand_image_expanded']['text'] : PROJECT_PATH."/themes/default/images/logo_side_expanded.png";
					$html .= "		<a class='menu_brand_image' href='".PROJECT_PATH."/'>";
					$html .= 			"<img id='menu_brand_image_contracted' style='".($_SESSION['theme']['menu_side_state']['text'] == 'expanded' ? "display: none;" : null)."' src='".escape($menu_brand_image_contracted)."' title=\"".escape($menu_brand_text)."\">";
					$html .= 			"<img id='menu_brand_image_expanded' ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? "style='display: none;'" : null)." src='".escape($menu_brand_image_expanded)."' title=\"".escape($menu_brand_text)."\">";
					$html .= 		"</a>\n";
				}
// 				else {
// 					$html .= "		<a class='menu_side_item_main menu_side_expand' ".($_SESSION['theme']['menu_side_state']['text'] == 'expanded' ? "style='display: none';" : null)." onclick='menu_side_expand();' title=\"".$this->text['theme-label-expand_menu']."\"><i class='fas fa-bars fa-fw' style='z-index: 99800; padding-left: 1px;'></i></a>";
// 				}
				$html .= "	</div>\n";
			//main menu items
				if (is_array($menu_array) && sizeof($menu_array) != 0) {
					foreach ($menu_array as $menu_index_main => $menu_item_main) {
						$menu_target = ($menu_item_main['menu_item_category'] == 'external') ? '_blank' : '';
						$html .= "	<a class='menu_side_item_main' ".($menu_item_main['menu_item_link'] != '' ? "href='".$menu_item_main['menu_item_link']."' target='".$menu_target."'" : "onclick=\"menu_side_expand(); menu_side_item_toggle('".$menu_item_main['menu_item_uuid']."');\"")." title=\"".$menu_item_main['menu_language_title']."\">";
						if (is_array($menu_item_main['menu_items']) && sizeof($menu_item_main['menu_items']) != 0 && $_SESSION['theme']['menu_side_item_main_sub_icons']['boolean'] == 'true') {
							$html .= "	<div class='menu_side_item_main_sub_icons' style='float: right; margin-right: -1px; ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? "display: none;" : null)."'><i id='sub_arrow_".$menu_item_main['menu_item_uuid']."' class='sub_arrows fas fa-".($_SESSION['theme']['menu_side_item_main_sub_icon_expand']['text'] != '' ? $_SESSION['theme']['menu_side_item_main_sub_icon_expand']['text'] : 'chevron-down')." fa-xs'></i></div>\n";
						}
						if ($menu_item_main['menu_item_icon'] != '') {
							$html .= "<i class='menu_side_item_icon fas ".$menu_item_main['menu_item_icon']." fa-fw' style='z-index: 99800; margin-right: 8px;'></i>";
						}
						$html .= "<span class='menu_side_item_title' ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? "style='display: none;'" : null).">".$menu_item_main['menu_language_title']."</span>";
						$html .= "</a>\n";
						//sub menu items
							if (is_array($menu_item_main['menu_items']) && sizeof($menu_item_main['menu_items']) != 0) {
								$html .= "	<div id='sub_".$menu_item_main['menu_item_uuid']."' class='menu_side_sub' style='display: none;'>\n";
								foreach ($menu_item_main['menu_items'] as $menu_index_sub => $menu_item_sub) {
									$html .= "		<a class='menu_side_item_sub' ".($menu_item_sub['menu_item_category'] == 'external' ? "target='_blank'" : null)." href='".$menu_item_sub['menu_item_link']."'>";
									$html .= 			"<span class='menu_side_item_title' ".($_SESSION['theme']['menu_side_state']['text'] != 'expanded' ? "style='display: none;'" : null).">".$menu_item_sub['menu_language_title']."</span>";
									$html .= 		"</a>\n";
								}
								$html .= "	</div>\n";
							}
					}
					$html .= "	<div style='height: 100px;'></div>\n";
				}
			$html .= "</div>\n";
			if ($_SESSION['theme']['menu_side_state']['text'] != 'expanded' && $_SESSION['theme']['menu_side_state']['text'] != 'hidden') {
				$content_container_onclick = "onclick=\"clearTimeout(menu_side_contract_timer); if ($(window).width() >= 576) { menu_side_contract(); }\"";
			}
			$html .= "<div id='content_container' ".$content_container_onclick.">\n";
			$html .= "	<div id='body_header'>\n";
			//header: left
				$html .= "<div class='float-left'>\n";
				$html .= button::create(['type'=>'button','id'=>'menu_side_state_hidden_button','title'=>$this->text['theme-label-expand_menu'],'icon'=>'bars','class'=>'default '.($_SESSION['theme']['menu_side_state']['text'] != 'hidden' ? 'hide-sm-up ' : null).'float-left','onclick'=>'menu_side_expand();']);
				$body_header_brand_text = $_SESSION['theme']['body_header_brand_text']['text'] != '' ? escape($_SESSION['theme']['body_header_brand_text']['text']) : "FusionPBX";
				if ($_SESSION['theme']['body_header_brand_type']['text'] == 'image' || $_SESSION['theme']['body_header_brand_type']['text'] == 'image_text') {
					$body_header_brand_image = $_SESSION['theme']['body_header_brand_image']['text'] != '' ? $_SESSION['theme']['body_header_brand_image']['text'] : PROJECT_PATH."/themes/default/images/logo_side_expanded.png";
					$html .= 	"<div id='body_header_brand_image'>";
					$html .= 		"<a href='".PROJECT_PATH."/'><img id='body_header_brand_image' src='".escape($body_header_brand_image)."' title=\"".escape($body_header_brand_text)."\"></a>";
					$html .= 	"</div>";
				}
				if ($_SESSION['theme']['body_header_brand_type']['text'] == 'text' || $_SESSION['theme']['body_header_brand_type']['text'] == 'image_text') {
					$html .= 	"<div id='body_header_brand_text'><a href='".PROJECT_PATH."/'>".$body_header_brand_text."</a></div>";
				}
				$html .= "</div>\n";
			//header: right
				$html .= "<div class='float-right' style='white-space: nowrap;'>";
				//current user
					$html .= "<span style='display: inline-block; padding-right: 20px; font-size: 90%;'>\n";
					$html .= "	<a href='".PROJECT_PATH."/core/users/user_edit.php?id=user' title=\"".$this->text['theme-label-user']."\"><i class='fas fa-".($_SESSION['theme']['body_header_icon_user']['text'] != '' ? $_SESSION['theme']['body_header_icon_user']['text'] : 'user-circle')." fa-lg fa-fw' style='margin-top: 6px; margin-right: 5px;'></i>".$_SESSION['username']."</a>";
					$html .= "</span>\n";
				//domain name/selector (sm+)
					if (isset($_SESSION['username']) && $_SESSION['username'] != '' && permission_exists('domain_select') && count($_SESSION['domains']) > 1 && $_SESSION['theme']['domain_visible']['text'] == 'true') {
						$html .= "<span style='display: inline-block; padding-right: 10px; font-size: 90%;'>\n";
						$html .= "	<a href='#' id='header_domain_selector_domain' title='".$this->text['theme-label-open_selector']."'><i class='fas fa-".($_SESSION['theme']['body_header_icon_domain']['text'] != '' ? $_SESSION['theme']['body_header_icon_domain']['text'] : 'globe-americas')." fa-lg fa-fw' style='margin-top: 6px; margin-right: 5px;'></i>".escape($_SESSION['domain_name'])."</a>";
						$html .= "</span>\n";
					}
				//logout icon
					if (isset($_SESSION['username']) && $_SESSION['username'] != '' && $_SESSION['theme']['logout_icon_visible']['text'] == "true") {
						$html .= "<a id='header_logout_icon' href='#' title=\"".$this->text['theme-label-logout']."\" onclick=\"modal_open('modal-logout','btn_logout');\"><span class='fas fa-sign-out-alt'></span></a>";
					}
				$html .= "</div>";
			$html .= "	</div>\n";

			//modal for logout icon (above)
				if (isset($_SESSION['username']) && $_SESSION['username'] != '' && $_SESSION['theme']['logout_icon_visible']['text'] == "true") {
					$html .= modal::create(['id'=>'modal-logout','type'=>'general','message'=>$this->text['theme-confirm-logout'],'actions'=>button::create(['type'=>'button','label'=>$this->text['theme-label-logout'],'icon'=>'sign-out-alt','id'=>'btn_logout','style'=>'float: right; margin-left: 15px;','collapse'=>'never','link'=>PROJECT_PATH.'/logout.php','onclick'=>"modal_close();"])]);
				}

			return $html;
			unset($html);

		}

	}
}

?>
