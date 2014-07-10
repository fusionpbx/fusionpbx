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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('group_permissions') || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

require_once "resources/header.php";
$document['title'] = $text['title-group_permissions'];

require_once "resources/paging.php";

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
	$x=0;
	foreach ($config_list as &$config_path) {
		include($config_path);
		$x++;
	}

//if there are no permissions listed in v_group_permissions then set the default permissions
	$sql = "select count(*) as count from v_group_permissions ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$group_permission_count = $row["count"];
		break; //limit to 1 row
	}
	unset ($prep_statement);
	if ($group_permission_count == 0) {
		//no permissions found add the defaults
		foreach($apps as $app) {
			foreach ($app['permissions'] as $row) {
				foreach ($row['groups'] as $group) {
					//add the record
					$sql = "insert into v_group_permissions ";
					$sql .= "(";
					$sql .= "group_permission_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "permission_name, ";
					$sql .= "group_name ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'$domain_uuid', ";
					$sql .= "'".$row['name']."', ";
					$sql .= "'".$group."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}
			}
		}
	}

//get the http values and set them as php variables
	$group_name = $_REQUEST['group_name'];

//get the permissions assigned to this group
	$sql = " select * from v_group_permissions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and group_name = '$group_name' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$permission_name = $row["permission_name"];
		$permissions_db[$permission_name] = "true";
	}

//show the db checklist
	//echo "<pre>";
	//print_r($permissions_db);
	//echo "</pre>";

//list all the permissions in the database
	foreach($apps as $app) {
		foreach ($app['permissions'] as $row) {
			if ($permissions_db[$row['name']] == "true") {
				$permissions_db_checklist[$row['name']] = "true";
			}
			else {
				$permissions_db_checklist[$row['name']] = "false";
			}
		}
	}

//show the db checklist
	//echo "<pre>";
	//print_r($permissions_db_checklist);
	//echo "</pre>";

//process the http post
	if (count($_POST)>0) {
		foreach($_POST['permissions_form'] as $permission) {
			$permissions_form[$permission] = "true";
		}

		//list all the permissions
			foreach($apps as $app) {
				foreach ($app['permissions'] as $row) {
					if ($permissions_form[$row['name']] == "true") {
						$permissions_form_checklist[$row['name']] = "true";
					}
					else {
						$permissions_form_checklist[$row['name']] = "false";
					}
				}
			}
		//show the form db checklist
			//echo "<pre>";
			//print_r($permissions_form_checklist);
			//echo "</pre>";

		//list all the permissions
			foreach($apps as $app) {
				foreach ($app['permissions'] as $row) {
					$permission = $row['name'];
					if ($permissions_db_checklist[$permission] == "true" && $permissions_form_checklist[$permission] == "true") {
						//matched do nothing
					}
					if ($permissions_db_checklist[$permission] == "false" && $permissions_form_checklist[$permission] == "false") {
						//matched do nothing
					}
					if ($permissions_db_checklist[$permission] == "true" && $permissions_form_checklist[$permission] == "false") {
						//delete the record
							$sql = "delete from v_group_permissions ";
							$sql .= "where domain_uuid = '$domain_uuid' ";
							$sql .= "and group_name = '$group_name' ";
							$sql .= "and permission_name = '$permission' ";
							$db->exec(check_sql($sql));
							unset($sql);

						foreach($apps as $app) {
							foreach ($app['permissions'] as $row) {
								if ($row['name'] == $permission) {

									$sql = "delete from v_menu_item_groups ";
									$sql .= "where menu_item_uuid = '".$row['menu']['uuid']."' ";
									$sql .= "and group_name = '$group_name' ";
									$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$db->exec(check_sql($sql));
									unset($sql);

									$sql = "";
									$sql .= " select menu_item_parent_uuid from v_menu_items ";
									$sql .= "where menu_item_uuid = '".$row['menu']['uuid']."' ";
									$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$prep_statement = $db->prepare(check_sql($sql));
									$prep_statement->execute();
									$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
									foreach ($result as &$row) {
										$menu_item_parent_uuid = $row["menu_item_parent_uuid"];
									}
									unset ($prep_statement);

									$sql = "";
									$sql .= " select * from v_menu_items as i, v_menu_item_groups as g  ";
									$sql .= "where i.menu_item_uuid = g.menu_item_uuid ";
									$sql .= "and i.menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$sql .= "and i.menu_item_parent_uuid = '$menu_item_parent_uuid' ";
									$sql .= "and g.group_name = '$group_name' ";
									$prep_statement = $db->prepare(check_sql($sql));
									$prep_statement->execute();
									$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
									$result_count = count($result);
									if ($result_count == 0) {
										$sql = "delete from v_menu_item_groups ";
										$sql .= "where menu_item_uuid = '$menu_item_parent_uuid' ";
										$sql .= "and group_name = '$group_name' ";
										$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
										$db->exec(check_sql($sql));
										unset($sql);
									}
									unset ($prep_statement);



								}
							}
						}
						//set the permission to false in the permissions_db_checklist
							$permissions_db_checklist[$permission] = "false";
					}
					if ($permissions_db_checklist[$permission] == "false" && $permissions_form_checklist[$permission] == "true") {
						//add the record
							$sql = "insert into v_group_permissions ";
							$sql .= "(";
							$sql .= "group_permission_uuid, ";
							$sql .= "domain_uuid, ";
							$sql .= "permission_name, ";
							$sql .= "group_name ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							$sql .= "'$domain_uuid', ";
							$sql .= "'$permission', ";
							$sql .= "'$group_name' ";
							$sql .= ")";
							$db->exec(check_sql($sql));
							unset($sql);

						foreach($apps as $app) {
							foreach ($app['permissions'] as $row) {
								if ($row['name'] == $permission) {

									$sql = "insert into v_menu_item_groups ";
									$sql .= "(";
									$sql .= "menu_uuid, ";
									$sql .= "menu_item_uuid, ";
									$sql .= "group_name ";
									$sql .= ")";
									$sql .= "values ";
									$sql .= "(";
									$sql .= "'b4750c3f-2a86-b00d-b7d0-345c14eca286', ";
									$sql .= "'".$row['menu']['uuid']."', ";
									$sql .= "'$group_name' ";
									$sql .= ")";
									$db->exec(check_sql($sql));
									unset($sql);

									$sql = "";
									$sql .= " select menu_item_parent_uuid from v_menu_items ";
									$sql .= "where menu_item_uuid = '".$row['menu']['uuid']."' ";
									$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$prep_statement = $db->prepare(check_sql($sql));
									$prep_statement->execute();
									$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
									foreach ($result as &$row) {
										$menu_item_parent_uuid = $row["menu_item_parent_uuid"];
									}
									unset ($prep_statement);

									$sql = "";
									$sql .= " select * from v_menu_item_groups ";
									$sql .= "where menu_item_uuid = '$menu_item_parent_uuid' ";
									$sql .= "and group_name = '$group_name' ";
									$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
									$prep_statement = $db->prepare(check_sql($sql));
									$prep_statement->execute();
									$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
									$result_count = count($result);
									if ($result_count == 0) {
										$sql = "insert into v_menu_item_groups ";
										$sql .= "(";
										$sql .= "menu_uuid, ";
										$sql .= "menu_item_uuid, ";
										$sql .= "group_name ";
										$sql .= ")";
										$sql .= "values ";
										$sql .= "(";
										$sql .= "'b4750c3f-2a86-b00d-b7d0-345c14eca286', ";
										$sql .= "'$menu_item_parent_uuid', ";
										$sql .= "'$group_name' ";
										$sql .= ")";
										$db->exec(check_sql($sql));
										unset($sql);
									}
									unset ($prep_statement);
								}
							}
						}
						//set the permission to true in the permissions_db_checklist
							$permissions_db_checklist[$permission] = "true";
					}
				}
			}

		$_SESSION["message"] = $text['message-update'];
		header("Location: groups.php");
		return;
	}

// copy group javascript

	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	function copy_group() {\n";
	echo "		var new_group_name;\n";
	echo "		var new_group_desc;\n";
	echo "		new_group_name = prompt('".$text['message-new_group_name']."');\n";
	echo "		if (new_group_name != null) {\n";
	echo "			new_group_desc = prompt('".$text['message-new_group_description']."');\n";
	echo "			if (new_group_desc != null) {\n";
	echo "				window.location = 'permissions_copy.php?group_name=".$group_name."&new_group_name=' + new_group_name + '&new_group_desc=' + new_group_desc;\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align=\"left\" nowrap=\"nowrap\"><b>".$text['header-group_permissions'].$group_name."</b></td>\n";
	echo "<td width='50%' align=\"right\">\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'> ";
	echo "	<input type='button' class='btn' alt='".$text['button-copy']."' onclick='copy_group();' value='".$text['button-copy']."'>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo "	".$text['description-group_permissions']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	echo "<br />\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='left'>\n";

	//list all the permissions
		foreach($apps as $app) {
			$app_name = $app['name'];
			$description = $app['description']['en-us'];

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "	<td valign='top' style='width:80%' nowrap='nowrap'>\n";
			echo "<strong>".$app_name."</strong><br />\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "	<td valign='top'>\n";
			echo "".$description."<br /><br />";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>";

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<th>".$text['label-permission_permissions']."</th>\n";
			echo "<th>".$text['label-permission_description']."</th>\n";
			echo "<tr>\n";

			foreach ($app['permissions'] as $row) {
				echo "<tr >\n";
				echo "	<td valign='top' style='width:250px' nowrap='nowrap' class='".$row_style[$c]."'>\n";
				if ($permissions_db_checklist[$row['name']] == "true") {
					echo "		<input type='checkbox' name='permissions_form[]' checked='checked' value='".$row['name']."'>\n";
				}
				else {
					echo "		<input type='checkbox' name='permissions_form[]' value='".$row['name']."'>\n";
				}
				echo "		&nbsp; ".$row['name']."\n";
				echo "	</td>\n";
				echo "	<td valign='top' class='row_stylebg'>\n";
				echo "		&nbsp; ".$row['description']."\n";
				echo "	</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			}

			echo "<tr>\n";
			echo "	<td colspan='3' align='right' style='padding-top: 5px;'>\n";
			echo "		<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>";
			echo "<br />\n";
		} //end foreach
		unset($sql, $result, $row_count);

	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<form>\n";

//show the footer
	require_once "resources/footer.php";

?>
