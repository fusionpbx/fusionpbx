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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
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

//connect to the database
	$database = database::new();

//set a default message_timeout
	$message_timeout = 4*1000;

//find optional apps with repos
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

//count upgradeable repos including + main repo
	$repos_count = 0;
	if (is_array($updateable_repos)) { $repos_count = @sizeof($updateable_repos); }
	$repos_count++;

//process the http post
	if (!empty($_POST) && @sizeof($_POST) > 0) {

		//get the action options: source, schema, app_defaults, menu_defaults, permissions
		$action = $_POST['action'] ?? null;

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

			global $autoload;
			if ($autoload !== null && $autoload instanceof auto_loader) {
				$autoload->reload_classes();
				$autoload->update_cache();
			}
		}

		//run optional app source updates
		if (!empty($action["optional_apps"]) && permission_exists("upgrade_source")) {

			$apps_updated = $apps_failed = 0;
			if (is_array($action["optional_apps"])) {
				foreach ($updateable_repos as $app_path => $app_details) {
					if (array_search(basename($app_path), $action["optional_apps"]) !== false) {
						$git_result = git_pull($app_path);
						if (!empty($git_result['result'])) {
							$apps_updated++;
						}
						else {
							$apps_failed++;
						}
						$_SESSION["response"]["optional_apps"][$app_details['name']] = $git_result['message'];
					}
				}
			}

			if ($apps_updated != 0) { message::add($text['message-optional_apps_upgrade_source'], null, $message_timeout); }
			if ($apps_failed != 0) { message::add($text['message-optional_apps_upgrade_source_failed'], 'negative', $message_timeout); }

			//update the auto_loader cache just-in-case the source files have updated
			global $autoload;
			if ($autoload !== null && $autoload instanceof auto_loader) {
				$autoload->reload_classes();
				$autoload->update_cache();
			}

		}

		//load an array of the database schema and compare it with the active database
		if (!empty($action["upgrade_schema"]) && permission_exists("upgrade_schema")) {
			$obj = new schema();
			if (isset($action["data_types"]) && $action["data_types"] == 'true') {
				$obj->data_types = true;
			}
			$_SESSION["response"]["schema"] = $obj->schema("html");
			message::add($text['message-upgrade_schema'], null, $message_timeout);
		}

		//process the apps defaults
		if (!empty($action["app_defaults"]) && permission_exists("upgrade_apps")) {
			//update the auto_loader cache just-in-case the source files have updated
			global $autoload;
			if ($autoload !== null && $autoload instanceof auto_loader) {
				$autoload->reload_classes();
				$autoload->update_cache();
			}

			$domain = new domains;
			$domain->upgrade();
			message::add($text['message-upgrade_apps'], null, $message_timeout);
		}

		//restore defaults of the selected menu
		if (!empty($action["menu_defaults"]) && permission_exists("menu_restore")) {
			global $autoload;
			if ($autoload !== null && $autoload instanceof auto_loader) {
				$autoload->reload_classes();
				$autoload->update_cache();
			}
			$sel_menu = explode('|', check_str($_POST["sel_menu"]));
			$menu_uuid = $sel_menu[0];
			$menu_language = $sel_menu[1];
			$included = true;
			require_once("core/menu/menu_restore_default.php");
			unset($sel_menu);
			$text = $language->get(null, '/core/upgrade');
			message::add($text['message-upgrade_menu'], null, $message_timeout);
		}

		//restore default permissions
		if (!empty($action["permission_defaults"]) && permission_exists("group_edit")) {
			global $autoload;
			if ($autoload !== null && $autoload instanceof auto_loader) {
				$autoload->reload_classes();
				$autoload->update_cache();
			}
			$included = true;
			require_once("core/groups/permissions_default.php");
			$text = $language->get(null, '/core/upgrade');
			message::add($text['message-upgrade_permissions'], null, $message_timeout);
		}

		//redirect the browser
		header("Location: ".PROJECT_PATH."/core/upgrade/index.php");
		exit;

	}

//process the http get (source preview)
	if (!empty($_GET['preview'])) {
		if (!empty($updateable_repos)) {
			foreach ($updateable_repos as $app_path => $app) {
				$repo_info = git_repo_info($app_path);
				if (empty($repo_info)) { continue; }
				$source_code[$app['app']] = $app_path;
			}
		}
		if ($_GET['preview'] == 'core') {
			$command = 'cd '.$_SERVER['PROJECT_ROOT'].' && git fetch && git diff --name-only @ @{u}';
		}
		else if (array_key_exists($_GET['preview'], $source_code)) {
			$command = 'cd '.$source_code[$_GET['preview']].' && git fetch && git diff --name-only @ @{u}';
		}
		if (!empty($command)) {
			$response = explode(PHP_EOL, shell_exec($command));
			// simplify response
			if (!empty($response)) {
				foreach ($response as $l => $line) {
					if (empty($line)) { unset($response[$l]); }
					if (substr($line, 0, 8) == 'remote: ') { unset($response[$l]); }
					if (substr($line, 0, 8) == 'remote: ') { unset($response[$l]); }
					if (substr($line, 0, 19) == 'Unpacking objects: ') { unset($response[$l]); }
					if (substr($line, 0, 5) == 'From ') { unset($response[$l]); }
					if (substr($line, 0, 3) == '   ') { unset($response[$l]); }
				}
			}
			echo "<button type='button' class='btn btn-default' style='float: right;' onclick=\"$('#source_preview_layer').fadeOut(200);\">".$text['button-close']."</button>\n";
			echo "<div class='title'>".$text['header-source_code_upgrade_preview']."</div>\n";
			echo "<br><br>\n";
			if (!empty($response) && is_array($response)) {
				echo str_replace('APP_NAME', (!empty($_GET['title']) ? "<strong>".$_GET['title']."</strong>" : null), $text['description-source_code_changes_found']);
				echo "<br><br><br>\n";
				echo "<div class='file_paths'>\n";
				if (!empty($response) && is_array($response)) {
					echo implode("<br>\n<hr style='margin: 3px 0;'>\n", $response);
				}
				echo "</div>\n";
			}
			else {
				echo str_replace('APP_NAME', (!empty($_GET['title']) ? "<strong>".$_GET['title']."</strong>" : null), $text['description-source_code_no_changes_found']);
			}
			echo "<br><br>\n";
			echo "<center>\n";
			echo "	<button type='button' class='btn btn-default' style='margin-top: 15px;' onclick=\"$('#source_preview_layer').fadeOut(200);\">".$text['button-close']."</button>\n";
			echo "</center>\n";
			exit;
		}
	}

//adjust color and initialize step counter
	$step = 1;
	$step_color = isset($_SESSION['theme']['upgrade_step_color']['text']) ? $_SESSION['theme']['upgrade_step_color']['text'] : color_adjust((!empty($_SESSION['theme']['form_table_label_background_color']['text']) ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'), -0.1);
	$step_container_style = "width: 30px; height: 30px; border: 2px solid ".$step_color."; border-radius: 50%; float: left; text-align: center; vertical-align: middle;";
	$step_number_style = "font-size: 150%; font-weight: 600; color: ".$step_color.";";

//include the header and set the title
	$document['title'] = $text['title-upgrade'];
	require_once "resources/header.php";

//source preview layer
	echo "<div id='source_preview_layer' style='display: none;'>\n";
	echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>\n";
	echo "		<tr>\n";
	echo "			<td align='center' valign='middle'>\n";
	echo "				<span id='source_preview_container'></span>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</div>\n";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-upgrade']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'submit','label'=>$text['button-upgrade_execute'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','collapse'=>'never']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";
	echo $text['description-upgrade'];
	echo "<br /><br />";

	echo "<div class='card'>\n";
	if (permission_exists("upgrade_source") && !is_dir("/usr/share/examples/fusionpbx") && is_writeable($_SERVER["PROJECT_ROOT"]."/.git")) {
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"$('#tr_applications').slideToggle('fast');\">\n";
		echo "	<td width='30%' class='vncellreq' style='vertical-align: middle;'>\n";
		echo "		<div style='".$step_container_style."'><span style='".$step_number_style."'>".$step."</span></div>";
		echo "		<div class='mt-1'>".$text['label-upgrade_source']."</div>\n";
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<div style='float: left; clear: both;'>\n";
		echo "			<input type='checkbox' id='view_source_code_options' onclick=\"event.stopPropagation(); if (!$(this).prop('checked')) { $('#do_source').prop('checked', false); $('.do_optional_app').prop('checked', false); } else { $('#tr_applications').slideDown('fast'); $('#do_source').prop('checked', true); $('.do_optional_app').prop('checked', true); }\">\n";
		echo "		</div>\n";
		echo "		<div style='overflow: hidden;'>\n";
		echo "			<span onclick=\"event.stopPropagation(); $('#tr_applications').slideToggle('fast');\">&nbsp;&nbsp;".$text['description-update_all_source_files']." (".$repos_count.")</span>\n";
		echo "		</div>\n";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<div id='tr_applications' style='display: none;'>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr onclick=\"if (document.getElementById('do_source')) { document.getElementById('do_source').checked = !document.getElementById('do_source').checked; if (document.getElementById('do_source').checked == false) { document.getElementById('view_source_code_options').checked = false; } }\">\n";
		echo "	<td width='30%' class='vncell' style='vertical-align: middle;'>\n";
		echo "		".(isset($_SESSION['theme']['title']['text']) ? $_SESSION['theme']['title']['text'] : 'FusionPBX')."\n";
		echo "	</td>\n";
		echo "	<td width='70%' class='vtable' style='height: 50px; cursor: pointer;'>\n";
		echo "		<input type='checkbox' name='action[upgrade_source]' id='do_source' value='1' onclick=\"event.stopPropagation(); if (this.checked == false) { document.getElementById('view_source_code_options').checked = false; }\">\n";
		echo "		&nbsp;".$text['description-upgrade_source']."<br />\n";
		//show current git version info
		chdir($_SERVER["PROJECT_ROOT"]);
		exec("git rev-parse --abbrev-ref HEAD 2>&1", $git_current_branch, $branch_return_value);
		$git_current_branch = $git_current_branch[0];
		exec("git log --pretty=format:'%H' -n 1 2>&1", $git_current_commit, $commit_return_value);
		$git_current_commit = $git_current_commit[0];

		if (!is_numeric($git_current_branch)) {
			echo "	<span style='font-weight: 600;'>".software::version()."</span>\n";
		}
		if ($branch_return_value == 0 && $commit_return_value == 0) {
			echo "	<a href='https://github.com/fusionpbx/fusionpbx/compare/".$git_current_commit."...".$git_current_branch."' target='_blank' title='".$git_current_commit."' onclick=\"event.stopPropagation();\"><i>".$git_current_branch."</i></a>";
			echo "&nbsp;&nbsp;<button type='button' class='btn btn-link btn-xs' onclick=\"event.stopPropagation(); source_preview('core','".(isset($_SESSION['theme']['title']['text']) ? $_SESSION['theme']['title']['text'] : 'FusionPBX')."');\">".$text['button-preview']."</button>\n";
		}
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		if (!empty($updateable_repos) && is_array($updateable_repos)) {
			foreach ($updateable_repos as $app_path => $app) {
				$repo_info = git_repo_info($app_path);
				$pull_method = substr($repo_info['url'], 0, 4) == 'http' ? 'http' : 'ssh';
				if (empty($repo_info)) { continue; }
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "<tr onclick=\"if (document.getElementById('do_".$app['app']."')) { document.getElementById('do_".$app['app']."').checked = !document.getElementById('do_".$app['app']."').checked; if (document.getElementById('do_".$app['app']."').checked == false) { document.getElementById('view_source_code_options').checked = false; } }\">\n";
				echo "	<td width='30%' class='vncell' style='vertical-align: middle;'>\n";
				echo "		".$app['name']."\n";
				echo "	</td>\n";
				echo "	<td width='70%' class='vtable' style='height: 50px; cursor: ".($pull_method == 'http' ? "pointer;'" : "help;' title=\"".$text['message-upgrade_manually'].": ".$repo_info['url']."\"").">\n";
				if ($pull_method == 'http') {
					echo "	<input type='checkbox' name='action[optional_apps][]' class='do_optional_app' id='do_".$app['app']."' value='".$app['app']."' onclick=\"event.stopPropagation(); if (this.checked == false) { document.getElementById('view_source_code_options').checked = false; }\"> &nbsp;".$app['description']."<br />\n";
				}
				else {
					echo "	<i class='fas fa-ban mr-3' style='opacity: 0.3; margin: 0 1px;'></i> ".$app['description']."<br>\n";
				}
				echo "		<span style='font-weight: 600;'>".$app['version']."</span>&nbsp;&nbsp;<i><a href='".str_replace(['git@','.com:'],['https://','.com/'], $repo_info['url'])."/compare/".$repo_info['commit']."...".$repo_info['branch']." 'target='_blank' title='".$repo_info['commit']."'>".$repo_info['branch']."</i></a>\n";
				echo "		&nbsp;&nbsp;<button type='button' class='btn btn-link btn-xs' onclick=\"event.stopPropagation(); source_preview('".$app['app']."','".$app['name']."');\">".$text['button-preview']."</button>\n";
				echo "	</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
			}
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
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && sizeof($result) != 0) {
			foreach ($result as $row) {
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
	echo "</div>\n";

	echo "</form>\n";

	echo "<br />";
	if (!empty($_SESSION["response"]) && is_array($_SESSION["response"])) {
		foreach($_SESSION["response"] as $part => $response){
			echo "<div class='card'>\n";
			echo "<b>".$text["label-results"]." - ".$text["label-{$part}"];
			echo "</b><br /><br />";
			$error_found = false;
			if ($part == "optional_apps") {
				foreach ($response as $app_name => $app_response) {
					echo "<strong>".$app_name."</strong><br>\n";
					$error_found = false;
					foreach ($app_response as $l => $response_line) {
						if (substr_count($response_line, 'error: ') != 0) {
							$error_found = true;
							$app_response[$l] = str_replace('error:', 'Error:', $response_line);
						}
					}
					if ($error_found) { $error_style = 'color: red;'; }
					echo "<pre".(!empty($error_style) ? " style='".$error_style."'" : null).">\n";
					foreach ($app_response as $response_line) {
						echo htmlspecialchars($response_line) . "\n";
					}
					echo "</pre>\n";
					unset($error_found, $error_style);
				}
			}
			else if (is_array($response)) {
				foreach ($response as $l => $response_line) {
					if (substr_count($response_line, 'error: ') != 0) {
						$error_found = true;
						$response[$l] = str_replace('error:', 'Error:', $response_line);
					}
				}
				if ($error_found) { $error_style = 'color: red;'; }
				echo "<pre".(!empty($error_style) ? " style='".$error_style."'" : null).">\n";
				echo implode("\n", $response);
				echo "</pre>";
				unset($error_found, $error_style);
			}
			else {
				echo $response;
			}
			echo "</div>\n";
		}
		unset($_SESSION["response"]);
	}

//source preview script
	echo "<script>\n";
	echo "function source_preview(source, title) {\n";
	echo "	$.ajax({\n";
	echo "		url: '".$_SERVER['PHP_SELF']."?preview=' + source + '&title=' + title,\n";
	echo "		type: 'get',\n";
	echo "		processData: false,\n";
	echo "		contentType: false,\n";
	echo "		cache: false,\n";
	echo "		success: function(response){\n";
	echo "			$('#source_preview_container').html(response);\n";
	echo "			$('#source_preview_layer').fadeIn(400);\n";
	echo "		}\n";
	echo "	});\n";
	echo "}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>