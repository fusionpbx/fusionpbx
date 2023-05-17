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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set a timeout
	set_time_limit(15*60); //15 minutes

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check the permission
	if (
		!permission_exists('upgrade_source') &&
		!permission_exists('upgrade_schema') &&
		!permission_exists('upgrade_apps') &&
		!permission_exists('menu_restore') &&
		!permission_exists('group_edit')
		) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set a default message_timeout
	$message_timeout = 4*1000;

//process the http post
	if (sizeof($_POST) > 0) {

		//get the action options: source, schema, app_defaults, menu_defaults, permisisons
		$action = $_POST['action'];

		//run source update
		if ($action["upgrade_source"] && permission_exists("upgrade_source") && !is_dir("/usr/share/examples/fusionpbx")) {
			$cwd = getcwd();
			chdir($_SERVER["PROJECT_ROOT"]);
			exec("git pull 2>&1", $response_source_update);
			$update_failed = true;
			if (sizeof($response_source_update) > 0) {
				$_SESSION["response"]["upgrade_source"] = $response_source_update;
				foreach ($response_source_update as $response_line) {
					if (substr_count($response_line, "Updating ") > 0 || substr_count($response_line, "Already up-to-date.") > 0 || substr_count($response_line, "Already up to date.") > 0) {
						$update_failed = false;
					}
					
					if (substr_count($response_line, "error") > 0) {
						$update_failed = true;
						break;
					}
				}
			}
			chdir($cwd);
			if ($update_failed) {
				message::add($text['message-upgrade_source_failed'], 'negative', $message_timeout);
			}
			else {
				message::add($text['message-upgrade_source'], null, $message_timeout);
			}
		}

		//load an array of the database schema and compare it with the active database
		if ($action["upgrade_schema"] && permission_exists("upgrade_schema")) {
			require_once "resources/classes/schema.php";
			$obj = new schema();
			if (isset($action["data_types"]) && $action["data_types"] == 'true') {
				$obj->data_types = true;
			}
			$_SESSION["response"]["schema"] = $obj->schema("html");
			message::add($text['message-upgrade_schema'], null, $message_timeout);
		}

		//process the apps defaults
		if ($action["app_defaults"] && permission_exists("upgrade_apps")) {
			require_once "resources/classes/domains.php";
			$domain = new domains;
			$domain->upgrade();
			message::add($text['message-upgrade_apps'], null, $message_timeout);
		}

		//restore defaults of the selected menu
		if ($action["menu_defaults"] && permission_exists("menu_restore")) {
			$sel_menu = explode('|', check_str($_POST["sel_menu"]));
			$menu_uuid = $sel_menu[0];
			$menu_language = $sel_menu[1];
			$included = true;
			require_once("core/menu/menu_restore_default.php");
			unset($sel_menu);
			message::add($text['message-upgrade_menu'], null, $message_timeout);
		}

		//restore default permissions
		if ($action["permission_defaults"] && permission_exists("group_edit")) {
			$included = true;
			require_once("core/groups/permissions_default.php");
			message::add($text['message-upgrade_permissions'], null, $message_timeout);
		}
		
		//redirect the browser
		header("Location: ".PROJECT_PATH."/core/upgrade/index.php");
		exit;

	}

//adjust color and initialize step counter
	$step = 1;
	$step_color = $_SESSION['theme']['upgrade_step_color']['text'] ? $_SESSION['theme']['upgrade_step_color']['text'] : color_adjust(($_SESSION['theme']['form_table_label_background_color']['text'] != '' ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'), -0.1);
	$step_container_style = "width: 30px; height: 30px; border: 2px solid ".$step_color."; border-radius: 50%; float: left; text-align: center; vertical-align: middle;";
	$step_number_style = "font-size: 150%; font-weight: 600; color: ".$step_color.";";

//include the header and set the title
	$document['title'] = $text['title-upgrade'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-upgrade']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'submit','label'=>$text['button-upgrade_execute'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'never']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-upgrade'];
	echo "<br /><br />";

	if (permission_exists("upgrade_source") && !is_dir("/usr/share/examples/fusionpbx") && is_writeable($_SERVER["PROJECT_ROOT"]."/.git")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_source').checked = !document.getElementById('do_source').checked;\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align:middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step++."</span></div>";
		echo "		".$text['label-upgrade_source'];
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[upgrade_source]' id='do_source' value='1' onclick=\"event.stopPropagation();\"> &nbsp;".$text['description-upgrade_source']."<br />\n";

		// show current git version info
		chdir($_SERVER["PROJECT_ROOT"]);
		exec("git rev-parse --abbrev-ref HEAD 2>&1", $git_current_branch, $branch_return_value);
		$git_current_branch = $git_current_branch[0];
		exec("git log --pretty=format:'%H' -n 1 2>&1", $git_current_commit, $commit_return_value);
		$git_current_commit = $git_current_commit[0];
		if (($branch_return_value == 0) && ($commit_return_value == 0)) {
			echo $text['label-git_branch'].' '.$git_current_branch." \n";
			//echo $text['label-git_commit'].' '." ";
			echo "<a href='https://github.com/fusionpbx/fusionpbx/compare/";
			echo $git_current_commit . "..." . "$git_current_branch' target='_blank' onclick=\"event.stopPropagation();\"> \n";
			echo $git_current_commit . "</a><br />\n";
			echo "</a>";
		}

		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	if (permission_exists("upgrade_schema")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_schema').checked = !document.getElementById('do_schema').checked; (!document.getElementById('do_schema').checked ? $('#do_data_types').prop('checked', false) : null); $('#tr_data_types').slideToggle('fast');\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align:middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step."</span></div>";
		echo "		".$text['label-upgrade_schema'];
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[upgrade_schema]' id='do_schema' value='1' onclick=\"event.stopPropagation(); $('#tr_data_types').slideToggle('fast'); (!document.getElementById('do_schema').checked ? $('#do_data_types').prop('checked', false) : null);\"> &nbsp;".$text['description-upgrade_schema']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<div id='tr_data_types' style='display: none;'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_data_types').checked = !document.getElementById('do_data_types').checked;\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align:middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style." letter-spacing: -0.06em;'>".$step++."B</span></div>";
		echo "		".$text['label-upgrade_data_types'];
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[data_types]' id='do_data_types' value='true' onclick=\"event.stopPropagation();\"> &nbsp;".$text['description-upgrade_data_types']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}

	if (permission_exists("upgrade_apps")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_apps').checked = !document.getElementById('do_apps').checked;\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align:middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step++."</span></div>";
		echo "		".$text['label-upgrade_apps'];
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[app_defaults]' id='do_apps' value='1' onclick=\"event.stopPropagation();\"> &nbsp;".$text['description-upgrade_apps']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	if (permission_exists("menu_restore")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_menu').checked = !document.getElementById('do_menu').checked; $('#sel_menu').fadeToggle('fast');\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align:middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step++."</span></div>";
		echo "		".$text['label-upgrade_menu'];
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo 		"<input type='checkbox' name='action[menu_defaults]' id='do_menu' value='1' onclick=\"event.stopPropagation(); $('#sel_menu').fadeToggle('fast');\">";
		echo 		"<select name='sel_menu' id='sel_menu' class='formfld' style='display: none; vertical-align: middle; margin-left: 5px;' onclick=\"event.stopPropagation();\">";
		$sql = "select * from v_menus order by menu_name asc;";
		$database = new database;
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			foreach ($result as &$row) {
				if ($row["menu_name"] == 'default') {
					echo "<option selected value='".$row["menu_uuid"]."|".$row["menu_language"]."'>".$row["menu_name"]."</option>";
				}
				else {
					echo "<option value='".$row["menu_uuid"]."|".$row["menu_language"]."'>".$row["menu_name"]."</option>";
				}
			}
		}
		unset ($sql, $result);
		echo 		"</select>";
		echo 		" &nbsp;".$text['description-upgrade_menu'];
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	if (permission_exists("group_edit")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_permissions').checked = !document.getElementById('do_permissions').checked;\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align:middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step++."</span></div>";
		echo "		".$text['label-upgrade_permissions'];
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[permission_defaults]' id='do_permissions' value='1' onclick=\"event.stopPropagation();\"> &nbsp;".$text['description-upgrade_permissions']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	echo "</form>\n";

	echo "<br /><br />";
	if (!empty($_SESSION["response"]) && is_array($_SESSION["response"])) {
		foreach($_SESSION["response"] as $part => $response){
			echo "<b>". $text["label-results"]." - ".$text["label-${part}"]."</b>";
			echo "<br /><br />";
			if (is_array($response)) {
				echo "<pre>";
				echo implode("\n", $response);
				echo "</pre>";
			}
			else {
				echo $response;
			}
			echo "<br /><br />";
		}
		unset($_SESSION["response"]);
	}

//include the footer
	require_once "resources/footer.php";

?>
