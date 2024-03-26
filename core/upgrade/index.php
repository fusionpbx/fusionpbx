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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set a timeout
	set_time_limit(15*60); //15 minutes

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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
	if (!empty($_POST) && @sizeof($_POST) > 0) {

		//get the action options: source, schema, app_defaults, menu_defaults, permisisons
		$action = $_POST['action'];

		//run source update
		if (!empty($action["upgrade_source"]) && permission_exists("upgrade_source") && !is_dir("/usr/share/examples/fusionpbx")) {

			$project_update_status = git_pull($_SERVER["PROJECT_ROOT"]);

			$_SESSION["response"]["upgrade_source"] = $project_update_status['message'];

			if (!empty($project_update_status['result'])) {
				message::add($text['message-upgrade_source'], null, $message_timeout);
			}
			else {
				message::add($text['message-upgrade_source_failed'], 'negative', $message_timeout);
			}
		}

		//run optional app source updates
		if (!empty($action["optional_apps"]) && permission_exists("upgrade_source")) {

			$updateable_repos = git_find_repos($_SERVER["PROJECT_ROOT"]."/app");

			$apps_updated = $apps_failed = 0;
			if (is_array($action["optional_apps"])) {
				foreach ($updateable_repos as $repo => $apps) {
					if (array_search(basename($repo), $action["optional_apps"]) !== false) {
						$git_result = git_pull($repo);
						if ($git_result['result']) {
							$_SESSION["response"]["optional_apps"][basename($repo)] = $git_result['message'];
							$apps_updated++;
						}
						else {
							$apps_failed++;
						}
					}
				}
			}

			if ($apps_updated != 0) { message::add($text['message-optional_apps_upgrade_source'], null, $message_timeout); }
			if ($apps_failed != 0) { message::add($text['message-optional_apps_upgrade_source_failed'], 'negative', $message_timeout); }

		}

		//load an array of the database schema and compare it with the active database
		if (!empty($action["upgrade_schema"]) && permission_exists("upgrade_schema")) {
			require_once "resources/classes/schema.php";
			$obj = new schema();
			if (isset($action["data_types"]) && $action["data_types"] == 'true') {
				$obj->data_types = true;
			}
			$_SESSION["response"]["schema"] = $obj->schema("html");
			message::add($text['message-upgrade_schema'], null, $message_timeout);
		}

		//process the apps defaults
		if (!empty($action["app_defaults"]) && permission_exists("upgrade_apps")) {
			require_once "resources/classes/domains.php";
			$domain = new domains;
			$domain->upgrade();
			message::add($text['message-upgrade_apps'], null, $message_timeout);
		}

		//restore defaults of the selected menu
		if (!empty($action["menu_defaults"]) && permission_exists("menu_restore")) {
			$sel_menu = explode('|', check_str($_POST["sel_menu"]));
			$menu_uuid = $sel_menu[0];
			$menu_language = $sel_menu[1];
			$included = true;
			require_once("core/menu/menu_restore_default.php");
			unset($sel_menu);
			message::add($text['message-upgrade_menu'], null, $message_timeout);
		}

		//restore default permissions
		if (!empty($action["permission_defaults"]) && permission_exists("group_edit")) {
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
	$step_color = isset($_SESSION['theme']['upgrade_step_color']['text']) ? $_SESSION['theme']['upgrade_step_color']['text'] : color_adjust((!empty($_SESSION['theme']['form_table_label_background_color']['text']) ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'), -0.1);
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
		echo "<tr onclick=\"document.getElementById('do_source').checked = !document.getElementById('do_source').checked; (!document.getElementById('do_source').checked ? $('.do_optional_app').prop('checked', false) : null); $('#tr_optional_apps').slideToggle('fast');\">\n";
		echo "	<td width='30%' class='vncellreq' style='vertical-align: middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step."</span></div>";
		echo "		<div class='mt-1'>".$text['label-upgrade_source']."</div>\n";
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[upgrade_source]' id='do_source' value='1' onclick=\"event.stopPropagation(); $('#tr_optional_apps').slideToggle('fast'); (!document.getElementById('do_source').checked ? $('.do_optional_app').prop('checked', false) : null);\"> &nbsp;".$text['description-upgrade_source']."<br />\n";
		//show current git version info
		chdir($_SERVER["PROJECT_ROOT"]);
		exec("git rev-parse --abbrev-ref HEAD 2>&1", $git_current_branch, $branch_return_value);
		$git_current_branch = $git_current_branch[0];
		exec("git log --pretty=format:'%H' -n 1 2>&1", $git_current_commit, $commit_return_value);
		$git_current_commit = $git_current_commit[0];
		if (!is_numeric($git_current_branch)) {
			echo "	<span style='font-weight: 600;'>".software::version()."</span>&nbsp;\n";
		}
		if ($branch_return_value == 0 && $commit_return_value == 0) {
			echo "	<a href='https://github.com/fusionpbx/fusionpbx/compare/".$git_current_commit."...".$git_current_branch."' target='_blank' title='".$git_current_commit."' onclick=\"event.stopPropagation();\"><i>".$git_current_branch."</i></a>";
		}
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		//find and show optional apps with repos
		$updateable_repos = git_find_repos($_SERVER["PROJECT_ROOT"]."/app");
		if (!empty($updateable_repos) && is_array($updateable_repos) && @sizeof($updateable_repos) != 0) {
			foreach ($updateable_repos as $app_path => $repo) {
				$x = 0;
				include $app_path.'/app_config.php';
				$updateable_repos[$app_path]['app'] = $repo[0];
				$updateable_repos[$app_path]['name'] = $apps[$x]['name'];
				$updateable_repos[$app_path]['uuid'] = $apps[$x]['uuid'];
				$updateable_repos[$app_path]['version'] = $apps[$x]['version'];
				$updateable_repos[$app_path]['description'] = $apps[$x]['description'][$_SESSION['domain']['language']['code']];
				unset($apps, $updateable_repos[$app_path][0]);
			}
		}
		echo "<div id='tr_optional_apps' style='display: none;'>\n";
		foreach ($updateable_repos as $repo => $app) {
			$repo_info = git_repo_info($repo);
			$pull_method = substr($repo_info['url'], 0, 4) == 'http' ? 'http' : 'ssh';
			if (!$repo_info) { continue; }
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr onclick=\"if (document.getElementById('do_".$app['app']."')) { document.getElementById('do_".$app['app']."').checked = !document.getElementById('do_".$app['app']."').checked; }\">\n";
			echo "	<td width='30%' class='vncell' style='vertical-align: middle;'>\n";
			echo "		".$app['name']."\n";
			echo "	</td>\n";
			echo "	<td width='70%' class='vtable' style='height: 50px; cursor: ".($pull_method == 'http' ? "pointer;'" : "help;' title=\"".$text['message-upgrade_manually'].": ".$repo_info['url']."\"").">\n";
			if ($pull_method == 'http') {
				echo "	<input type='checkbox' name='action[optional_apps][]' class='do_optional_app' id='do_".$app['app']."' value='".$app['app']."' onclick=\"event.stopPropagation();\"> &nbsp;".$app['description']."<br />\n";
			}
			else {
				echo "	<i class='fas fa-ban mr-3' style='opacity: 0.4;'></i> &nbsp;".$app['description']."<br>\n";
			}
			echo "		<span style='font-weight: 600;'>".$app['version']."</span>&nbsp;&nbsp;<i><a href='".str_replace(['git@','.com:'],['https://','.com/'], $repo_info['url'])."/compare/".$repo_info['commit']."...".$repo_info['branch']." 'target='_blank' title='".$repo_info['commit']."'>".$repo_info['branch']."</i></a>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
		}
		echo "</div>\n";
		$step++;
	}

	if (permission_exists("upgrade_schema")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_schema').checked = !document.getElementById('do_schema').checked; (!document.getElementById('do_schema').checked ? $('#do_data_types').prop('checked', false) : null); $('#tr_data_types').slideToggle('fast');\">\n";
		echo "	<td width='30%' class='vncellreq' style='vertical-align: middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step."</span></div>";
		echo "		<div class='mt-1'>".$text['label-upgrade_schema']."</div>\n";
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[upgrade_schema]' id='do_schema' value='1' onclick=\"event.stopPropagation(); $('#tr_data_types').slideToggle('fast'); (!document.getElementById('do_schema').checked ? $('#do_data_types').prop('checked', false) : null);\"> &nbsp;".$text['description-upgrade_schema']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<div id='tr_data_types' style='display: none;'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_data_types').checked = !document.getElementById('do_data_types').checked;\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align: middle;'>\n";
		echo "		".$text['label-upgrade_data_types'];
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[data_types]' id='do_data_types' value='true' onclick=\"event.stopPropagation();\"> &nbsp;".$text['description-upgrade_data_types']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
		$step++;
	}

	if (permission_exists("upgrade_apps")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_apps').checked = !document.getElementById('do_apps').checked;\">\n";
		echo "	<td width='30%' class='vncellreq' style='vertical-align: middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step."</span></div>";
		echo "		<div class='mt-1'>".$text['label-upgrade_apps']."</div>\n";
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[app_defaults]' id='do_apps' value='1' onclick=\"event.stopPropagation();\"> &nbsp;".$text['description-upgrade_apps']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		$step++;
	}

	if (permission_exists("menu_restore")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_menu').checked = !document.getElementById('do_menu').checked; $('#sel_menu').fadeToggle('fast');\">\n";
		echo "	<td width='30%' class='vncellreq' style='vertical-align: middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step."</span></div>";
		echo "		<div class='mt-1'>".$text['label-upgrade_menu']."</div>\n";
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
		$step++;
	}

	if (permission_exists("group_edit")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"document.getElementById('do_permissions').checked = !document.getElementById('do_permissions').checked;\">\n";
		echo "	<td width='30%' class='vncellreq' style='vertical-align: middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step."</span></div>";
		echo "		<div class='mt-1'>".$text['label-upgrade_permissions']."</div>\n";
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[permission_defaults]' id='do_permissions' value='1' onclick=\"event.stopPropagation();\"> &nbsp;".$text['description-upgrade_permissions']."\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		$step++;
	}

	echo "</form>\n";

	echo "<br /><br />";
	if (!empty($_SESSION["response"]) && is_array($_SESSION["response"])) {
		foreach($_SESSION["response"] as $part => $response){
			echo "<b>".$text["label-results"]." - ".$text["label-${part}"];
			echo "</b><br /><br />";
			if ($part == "optional_apps") {
				foreach ($response as $app_name => $app_response) {
					echo "<strong>".$app_name."</strong><br>\n";
					echo "<pre>\n";
					foreach ($app_response as $response_line) {
						echo htmlspecialchars($response_line) . "\n";
					}
					echo "</pre>\n";
				}
			}
			elseif (is_array($response)) {
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