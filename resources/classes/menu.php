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
	Copyright (C) 2010 - 2014
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the menu class
if (!class_exists('menu')) {
	class menu {
		//define the variables
			public $menu_uuid;
			public $menu_language;

		//delete items in the menu that are not protected
			public function delete() {
				//set the variable
					$db = $this->db;
				//remove existing menu languages
					$sql  = "delete from v_menu_languages ";
					$sql .= "where menu_uuid = '".$this->menu_uuid."' ";
					$db->exec(check_sql($sql));
				//remove existing unprotected menu item groups
					$sql = "delete from v_menu_item_groups ";
					$sql .= "where menu_uuid = '".$this->menu_uuid."' ";
					$sql .= "and menu_item_uuid in ( ";
					$sql .= "	select menu_item_uuid ";
					$sql .= "	from v_menu_items ";
					$sql .= "	where menu_uuid = '".$this->menu_uuid."' ";
					$sql .= "	and ( ";
					$sql .= " 		menu_item_protected <> 'true' ";
					$sql .= "		or menu_item_protected is null ";
					$sql .= "	) ";
					$sql .= ") ";
					$db->exec(check_sql($sql));
				//remove existing unprotected menu items
					$sql  = "delete from v_menu_items ";
					$sql .= "where menu_uuid = '".$this->menu_uuid."' ";
					$sql .= "and (menu_item_protected <> 'true' ";
					$sql .= "or menu_item_protected is null); ";
					$db->exec(check_sql($sql));
			}

		//restore the menu
			public function restore() {
				//set the variables
					$db = $this->db;

				//get the $apps array from the installed apps from the core and mod directories
					$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_menu.php");
					$x = 0;
					foreach ($config_list as &$config_path) {
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

				//begin the transaction
					if ($db_type == "sqlite") {
						$db->beginTransaction();
					}

				//use the app array to restore the default menu
					foreach ($apps as $row) {
						foreach ($row['menu'] as $menu) {
							//set the variables
								if (strlen($menu['title'][$this->menu_language]) > 0) {
									$menu_item_title = $menu['title'][$this->menu_language];
								}
								else {
									$menu_item_title = $menu['title']['en-us'];
								}
								$menu_item_uuid = $menu['uuid'];
								$menu_item_parent_uuid = $menu['parent_uuid'];
								$menu_item_category = $menu['category'];
								$menu_item_icon = $menu['icon'];
								$menu_item_path = $menu['path'];
								$menu_item_order = $menu['order'];
								$menu_item_description = $menu['desc'];

							//if the item uuid is not currently in the db then add it
								$sql = "select * from v_menu_items ";
								$sql .= "where menu_uuid = '".$this->menu_uuid."' ";
								$sql .= "and menu_item_uuid = '".$menu_item_uuid."' ";
								$prep_statement = $db->prepare(check_sql($sql));
								if ($prep_statement) {
									$prep_statement->execute();
									$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
									if (count($result) == 0) {
										//insert the default menu into the database
											$sql = "insert into v_menu_items ";
											$sql .= "(";
											$sql .= "menu_item_uuid, ";
											$sql .= "menu_uuid, ";
											$sql .= "menu_item_title, ";
											$sql .= "menu_item_link, ";
											$sql .= "menu_item_category, ";
											$sql .= "menu_item_icon, ";
											if (strlen($menu_item_order) > 0) {
												$sql .= "menu_item_order, ";
											}
											if (strlen($menu_item_parent_uuid) > 0) {
												$sql .= "menu_item_parent_uuid, ";
											}
											$sql .= "menu_item_description ";
											$sql .= ") ";
											$sql .= "values ";
											$sql .= "(";
											$sql .= "'".$menu_item_uuid."', ";
											$sql .= "'".$this->menu_uuid."', ";
											$sql .= "'".check_str($menu_item_title)."', ";
											$sql .= "'$menu_item_path', ";
											$sql .= "'$menu_item_category', ";
											$sql .= "'$menu_item_icon', ";
											if (strlen($menu_item_order) > 0) {
												$sql .= "'$menu_item_order', ";
											}
											if (strlen($menu_item_parent_uuid) > 0) {
												$sql .= "'$menu_item_parent_uuid', ";
											}
											$sql .= "'$menu_item_description' ";
											$sql .= ")";
											if ($menu_item_uuid == $menu_item_parent_uuid) {
												//echo $sql."<br />\n";
											}
											else {
												$db->exec(check_sql($sql));
											}
											unset($sql);

										//set the menu languages
											foreach ($menu["title"] as $menu_language => $menu_item_title) {
												$menu_language_uuid = uuid();
												$sql = "insert into v_menu_languages ";
												$sql .= "(";
												$sql .= "menu_language_uuid, ";
												$sql .= "menu_item_uuid, ";
												$sql .= "menu_uuid, ";
												$sql .= "menu_language, ";
												$sql .= "menu_item_title ";
												$sql .= ") ";
												$sql .= "values ";
												$sql .= "(";
												$sql .= "'".$menu_language_uuid."', ";
												$sql .= "'".$menu_item_uuid."', ";
												$sql .= "'".$this->menu_uuid."', ";
												$sql .= "'".$menu_language."', ";
												$sql .= "'".check_str($menu_item_title)."' ";
												$sql .= ")";
												$db->exec(check_sql($sql));
												unset($sql);
											}
									}
								}
						}
					}

				//make sure the default user groups exist
					$group = new groups;
					$group->defaults();

				//get default global group_uuids
					$sql = "select group_uuid, group_name from v_groups ";
					$sql .= "where domain_uuid is null ";
					$sql .= "and ( ";
					$sql .= "	group_name = 'public' ";
					$sql .= "	or group_name = 'user' ";
					$sql .= "	or group_name = 'admin' ";
					$sql .= "	or group_name = 'superadmin' ";
					$sql .= "	or group_name = 'agent' ";
					$sql .= ") ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					foreach ($result as $row) {
						$group_uuids[$row['group_name']] = $row['group_uuid'];
					}
					unset($sql, $prep_statement, $result);

				//if there are no groups listed in v_menu_item_groups under menu_item_uuid then add the default groups
					foreach($apps as $app) {
						foreach ($app['menu'] as $sub_row) {
							if (isset($sub_row['groups'])) foreach ($sub_row['groups'] as $group) {
								$sql = "select count(*) as count from v_menu_item_groups ";
								$sql .= "where menu_item_uuid = '".$sub_row['uuid']."' ";
								$sql .= "and menu_uuid = '".$this->menu_uuid."' ";
								$sql .= "and group_name = '".$group."' ";
								$sql .= "and group_uuid = '".$group_uuids[$group]."' ";
								//echo $sql."<br>";
								$prep_statement = $db->prepare($sql);
								$prep_statement->execute();
								$sub_result = $prep_statement->fetch(PDO::FETCH_ASSOC);
								unset ($prep_statement);
								if ($sub_result['count'] == 0) {
									//no menu item groups found add the defaults
									$sql = "insert into v_menu_item_groups ";
									$sql .= "( ";
									$sql .= "menu_item_group_uuid, ";
									$sql .= "menu_uuid, ";
									$sql .= "menu_item_uuid, ";
									$sql .= "group_name, ";
									$sql .= "group_uuid ";
									$sql .= ") ";
									$sql .= "values ";
									$sql .= "( ";
									$sql .= "'".uuid()."', ";
									$sql .= "'".$this->menu_uuid."', ";
									$sql .= "'".$sub_row['uuid']."', ";
									$sql .= "'".$group."', ";
									$sql .= "'".$group_uuids[$group]."' ";
									$sql .= ") ";
									//echo $sql."<br>";
									$db->exec(check_sql($sql));
									unset($sql);
								}
							}
						}
					}

				//commit the transaction
					if ($db_type == "sqlite") {
						$db->commit();
					}
			} //end function


		//create the menu
			public function build_html($menu_item_level = 0) {

				$db = $this->db;
				$menu_html_full = '';

				$menu_array = $this->menu_array();

				if (!isset($_SESSION['groups'])) {
					$_SESSION['groups'][0]['group_name'] = 'public';
				}

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

				return $menu_html_full;
			} //end function

		//create the sub menus
			private function build_child_html($menu_item_level, $submenu_array) {

				$db = $this->db;
				$menu_item_level = $menu_item_level+1;

				if (count($_SESSION['groups']) == 0) {
					$_SESSION['groups'][0]['group_name'] = 'public';
				}

				if (count($submenu_array) > 0) {
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
			} //end function

		//create the menu array
			public function menu_array($sql = '', $menu_item_level = 0) {

				//get the database connnection
					$db = $this->db;

				//database ojbect does not exist return immediately
					if (!$db) { return Array(); }

				//if there are no groups then set the public group
					if (!isset($_SESSION['groups'])) {
						$_SESSION['groups'][0]['group_name'] = 'public';
					}

				//get the menu from the database
					if (strlen($sql) == 0) { //default sql for base of the menu
						$sql = "select i.menu_item_link, l.menu_item_title as menu_language_title, i.menu_item_title, i.menu_item_protected, i.menu_item_category, i.menu_item_icon, i.menu_item_uuid, i.menu_item_parent_uuid ";
						$sql .= "from v_menu_items as i, v_menu_languages as l ";
						$sql .= "where i.menu_item_uuid = l.menu_item_uuid ";
						$sql .= "and l.menu_language = '".$_SESSION['domain']['language']['code']."' ";
						$sql .= "and l.menu_uuid = '".$this->menu_uuid."' ";
						$sql .= "and i.menu_uuid = '".$this->menu_uuid."' ";
						$sql .= "and i.menu_item_parent_uuid is null ";
						$sql .= "and i.menu_item_uuid in ";
						$sql .= "(select menu_item_uuid from v_menu_item_groups where menu_uuid = '".$this->menu_uuid."' ";
						$sql .= "and ( ";
						if (!isset($_SESSION['groups'])) {
							$sql .= "group_name = 'public' ";
						}
						else {
							$x = 0;
							foreach($_SESSION['groups'] as $row) {
								if ($x == 0) {
									$sql .= "group_name = '".$row['group_name']."' ";
								}
								else {
									$sql .= "or group_name = '".$row['group_name']."' ";
								}
								$x++;
							}
						}
						$sql .= ") ";
						$sql .= "and menu_item_uuid is not null ";
						$sql .= ") ";
						$sql .= "order by i.menu_item_order asc ";
					}
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

				//save the menu into an array
					$x = 0;
					$a = Array();
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
					} //end for each

				//unset the variables
					unset($prep_statement, $sql, $result);

				//return the array
					return $a;
			} //end function

		//create the sub menus
			private function menu_child_array($menu_item_level, $menu_item_uuid) {

				//get the database connnection
					$db = $this->db;

				//database ojbect does not exist return immediately
					if (!$db) { return; }

				//set the level
					$menu_item_level = $menu_item_level+1;

				//get the child menu from the database
					$sql = "select i.menu_item_link, l.menu_item_title as menu_language_title, i.menu_item_title, i.menu_item_protected, i.menu_item_category, i.menu_item_icon, i.menu_item_uuid, i.menu_item_parent_uuid ";
					$sql .= "from v_menu_items as i, v_menu_languages as l ";
					$sql .= "where i.menu_item_uuid = l.menu_item_uuid ";
					$sql .= "and l.menu_language = '".$_SESSION['domain']['language']['code']."' ";
					$sql .= "and l.menu_uuid = '".$this->menu_uuid."' ";
					$sql .= "and i.menu_uuid = '".$this->menu_uuid."' ";
					$sql .= "and i.menu_item_parent_uuid = '$menu_item_uuid' ";
					$sql .= "and i.menu_item_uuid in ";
					$sql .= "(select menu_item_uuid from v_menu_item_groups where menu_uuid = '".$this->menu_uuid."' ";
					$sql .= "and ( ";
					if (count($_SESSION['groups']) == 0) {
						$sql .= "group_name = 'public' ";
					}
					else {
						$x = 0;
						foreach($_SESSION['groups'] as $row) {
							if ($x == 0) {
								$sql .= "group_name = '".$row['group_name']."' ";
							}
							else {
								$sql .= "or group_name = '".$row['group_name']."' ";
							}
							$x++;
						}
					}
					$sql .= ") ";
					$sql .= ") ";
					$sql .= "order by l.menu_item_title, i.menu_item_order asc ";
					$sub_prep_statement = $db->prepare($sql);
					$sub_prep_statement->execute();
					$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);

				//save the child menu into an array
					if (count($sub_result) > 0) {
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
									//$str_child_menu =
								}

							//increment the row
								$x++;
						}
						unset($sql, $sub_result);
						return $a;
					}
					unset($sub_prep_statement, $sql);
			} //end function

		//add the default menu when no menu exists
			public function menu_default() {
				//set the default menu_uuid
					$this->menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
				//check to see if any menu exists
					$sql = "select count(*) as count from v_menus ";
					$sql .= "where menu_uuid = '".$this->menu_uuid."' ";
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetch(PDO::FETCH_NAMED);
					unset($sql, $prep_statement);
					if ($result['count'] == 0) {
						//set the menu variables
							$menu_name = 'default';
							$menu_language = 'en-us';
							$menu_description = 'Default Menu';

						//add the menu
							$sql = "insert into v_menus ";
							$sql .= "(";
							$sql .= "menu_uuid, ";
							$sql .= "menu_name, ";
							$sql .= "menu_language, ";
							$sql .= "menu_description ";
							$sql .= ") ";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".$this->menu_uuid."', ";
							$sql .= "'$menu_name', ";
							$sql .= "'$menu_language', ";
							$sql .= "'$menu_description' ";
							$sql .= ");";
							$this->db->exec($sql);

						//add the menu items
							$this->restore();
					}
			} //end function
	}
}

?>