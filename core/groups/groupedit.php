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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_edit')) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//process update
	if (count($_POST) > 0) {
		//set the variables
			$group_uuid = $_POST['group_uuid'];
			$group_name = $_POST['group_name'];
			$group_name_previous = $_POST['group_name_previous'];
			$domain_uuid = $_POST["domain_uuid"];
			$domain_uuid_previous = $_POST["domain_uuid_previous"];
			$group_level = $_POST["group_level"];
			$group_description = $_POST["group_description"];

		//check for global/domain duplicates
			$sql = "select count(*) from v_groups ";
			$sql .= "where group_name = :group_name ";
			$sql .= "and group_uuid <> :group_uuid ";
			if (is_uuid($domain_uuid)) {
				$sql .= "and domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
			}
			else {
				$sql .= "and domain_uuid is null ";
			}
			$parameters['group_name'] = $group_name;
			$parameters['group_uuid'] = $group_uuid;
			$database = new database;
			$num_rows = $database->select($sql, $parameters, 'column');
			$group_exists = ($num_rows > 0) ? true : false;
			unset($sql, $parameters, $num_rows);

		//update group
			if (!$group_exists) {
				$array['groups'][0]['group_uuid'] = $group_uuid;
				$array['groups'][0]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
				$array['groups'][0]['group_name'] = $group_name;
				$array['groups'][0]['group_level'] = $group_level;
				$array['groups'][0]['group_description'] = $group_description;
				$database = new database;
				$database->app_name = 'groups';
				$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
				$database->save($array);
				unset($array);

				//group changed from global to domain-specific
				if (!is_uuid($domain_uuid_previous) && is_uuid($domain_uuid)) {
					//remove any users assigned to the group from the old domain
						$sql = "delete from v_user_groups where group_uuid = :group_uuid and domain_uuid <> :domain_uuid ";
						$parameters['group_uuid'] = $group_uuid;
						$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$database->app_name = 'groups';
						$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
						$database->execute($sql, $parameters);
						unset($sql, $parameters);

					//update permissions to use new domain uuid
						$sql = "update v_group_permissions set domain_uuid = :domain_uuid where group_name = :group_name and domain_uuid is null ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['group_name'] = $group_name_previous;
						$database = new database;
						$database->app_name = 'groups';
						$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
						$database->execute($sql, $parameters);
						unset($sql, $parameters);

					//change group name
						if ($group_name != $group_name_previous && $group_name != '') {
							//change group name in group users
								$sql = "update v_user_groups set group_name = :group_name_new where group_uuid = :group_uuid and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['group_uuid'] = $group_uuid;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

							//change group name in permissions
								$sql = "update v_group_permissions set group_name = :group_name_new where domain_uuid = :domain_uuid and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
						}
				}
				//group changed from one domain to another
				else if (is_uuid($domain_uuid_previous) && is_uuid($domain_uuid) && $domain_uuid_previous != $domain_uuid) {
					//remove any users assigned to the group from the old domain
						$array['user_groups'][0]['group_uuid'] = $group_uuid;
						$array['user_groups'][0]['domain_uuid'] = $domain_uuid_previous;

						$p = new permissions;
						$p->add('user_group_delete', 'temp');

						$database = new database;
						$database->app_name = 'groups';
						$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
						$database->delete($array);
						unset($array);

						$p->delete('user_group_delete', 'temp');
					//update permissions to use new domain uuid
						$sql = "update v_group_permissions set domain_uuid = :domain_uuid_new where group_name = :group_name and domain_uuid = :domain_uuid_old ";
						$parameters['domain_uuid_new'] = $domain_uuid;
						$parameters['group_name'] = $group_name_previous;
						$parameters['domain_uuid_old'] = $domain_uuid_previous;
						$database = new database;
						$database->app_name = 'groups';
						$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					//change group name
						if ($group_name != $group_name_previous && $group_name != '') {
							//change group name in group users
								$sql = "update v_user_groups set group_name = :group_name_new where group_uuid = :group_uuid and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['group_uuid'] = $group_uuid;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							//change group name in permissions
								$sql = "update v_group_permissions set group_name = :group_name_new where domain_uuid = :domain_uuid and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
						}
				}

				//group changed from domain-specific to global
				else if (is_uuid($domain_uuid_previous) && !is_uuid($domain_uuid)) {
					//change group name
						if ($group_name != $group_name_previous && $group_name != '') {
							//change group name in group users
								$sql = "update v_user_groups set group_name = :group_name_new where group_uuid = :group_uuid and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['group_uuid'] = $group_uuid;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							//change group name in permissions
								$sql = "update v_group_permissions set group_name = :group_name_new where domain_uuid = :domain_uuid and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['domain_uuid'] = $domain_uuid_previous;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
						}
					//update permissions to not use a domain uuid
						$sql = "update v_group_permissions set domain_uuid = null where group_name = :group_name and domain_uuid = :domain_uuid ";
						$parameters['group_name'] = $group_name;
						$parameters['domain_uuid'] = $domain_uuid_previous;
						$database = new database;
						$database->app_name = 'groups';
						$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
				}

				//domain didn't change, but name may still
				else {
					//change group name
						if ($group_name != $group_name_previous && $group_name != '') {
							//change group name in group users
								$sql = "update v_user_groups set group_name = :group_name_new where group_uuid = :group_uuid and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['group_uuid'] = $group_uuid;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							//change group name in permissions
								$sql = "update v_group_permissions set group_name = :group_name_new ";
								if (is_uuid($domain_uuid)) {
									$sql .= "where domain_uuid = :domain_uuid ";
									$parameters['domain_uuid'] = $domain_uuid;
								}
								else {
									$sql .= "where domain_uuid is null ";
								}
								$sql .= "and group_name = :group_name_old ";
								$parameters['group_name_new'] = $group_name;
								$parameters['group_name_old'] = $group_name_previous;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
						}
				}

				message::add($text['message-update']);
				header("Location: groups.php");
			}
			else {
				message::add($text['message-group_exists'], 'negative');
				header("Location: groupedit.php?id=".$group_uuid);
			}

		//redirect the user
			return;
	}

//pre-populate the form
	$group_uuid = $_REQUEST['id'];
	if (is_uuid($group_uuid)) {
		$sql = "select * from v_groups where ";
		$sql .= "group_uuid = :group_uuid ";
		$parameters['group_uuid'] = $group_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$group_name = $row['group_name'];
			$domain_uuid = $row['domain_uuid'];
			$group_level = $row['group_level'];
			$group_description = $row['group_description'];
		}
		unset($sql, $parameters, $row);
	}

//include the header
	include "resources/header.php";
	$document['title'] = $text['title-group_edit'];

//copy group javascript
	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	function copy_group() {\n";
	echo "		var new_group_name;\n";
	echo "		var new_group_desc;\n";
	echo "		new_group_name = prompt('".$text['message-new_group_name']."');\n";
	echo "		if (new_group_name != null) {\n";
	echo "			new_group_desc = prompt('".$text['message-new_group_description']."');\n";
	echo "			if (new_group_desc != null) {\n";
	echo "				window.location = 'permissions_copy.php?id=".escape($group_uuid)."&new_group_name=' + new_group_name + '&new_group_desc=' + new_group_desc;\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<form name='login' method='post' action=''>\n";
	echo "<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";

	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top'>\n";
	echo "			<b>".$text['header-group_edit']."</b>\n";
	echo "			<br><br>\n";
	echo "			".$text['description-group_edit']."\n";
	echo "		</td>\n";
	echo "		<td align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'> ";
	echo "			<input type='button' class='btn' alt='".$text['button-copy']."' onclick='copy_group();' value='".$text['button-copy']."'>";
	echo "  		<input type='submit' class='btn' value=\"".$text['button-save']."\">\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";

	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top'>\n";
	echo 	$text['label-group_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' align='left' class='vtable'>\n";
	echo "	<input type='hidden' name='group_name_previous' value=\"".escape($group_name)."\">\n";
	echo "  <input type='text' class='formfld' name='group_name' value=\"".escape($group_name)."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('group_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input type='hidden' name='domain_uuid_previous' value='".escape($domain_uuid)."'>\n";
		echo "	<select class='formfld' name='domain_uuid'>\n";
		echo "	<option value='' ".((strlen($domain_uuid) == 0) ? "selected='selected'" : null).">".$text['option-global']."</option>\n";
		foreach ($_SESSION['domains'] as $row) {
			echo "<option value='".escape($row['domain_uuid'])."' ".(($row['domain_uuid'] == $domain_uuid) ? "selected='selected'" : null).">".escape($row['domain_name'])."</option>\n";
		}
		echo "	</select>\n";
		echo "	<br />\n";
		echo 	$text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else {
		echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top'>\n";
	echo "		".$text['label-level']."\n";
	echo "</td>\n";
	echo "<td align='left' class='vtable' valign='top'>\n";
	echo "		<select name='group_level' class='formfld'>\n";
	$i = 10;
	while ($i <= 90) {
		$selected = ($i == $group_level) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "			<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "			<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "			<option value='$i' ".$selected.">$i</option>\n";
		}
		$i = $i + 10;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top'>\n";
	echo 	$text['label-group_description']."\n";
	echo "</td>\n";
	echo "<td align='left' class='vtable' valign='top'>\n";
	echo "	<textarea name='group_description' class='formfld' style='width: 250px; height: 50px;'>".escape($group_description)."</textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2' align='right'>\n";
	echo "	<br />";
	echo "	<input type='submit' class='btn' value=\"".$text['button-save']."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>";
	echo "</form>";

//include the footer
	include "resources/footer.php";

?>
