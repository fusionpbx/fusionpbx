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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once __DIR__ . "/require.php";

//database and settings
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
	$user_uuid = $_SESSION['user_uuid'] ?? '';
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);

//set variables if not set
	if (!isset($_SESSION["menu"])) { $_SESSION["menu"] = null; }
	if (!isset($_SESSION["username"])) { $_SESSION["username"] = null; }

//save the session domains array to a variable of type array
	$domains = $_SESSION['domains'] ?? [];

//count the number of domains
	$domain_count = count($domains);

//get the output from the buffer
	$body = ob_get_contents();
	ob_end_clean(); //clean the buffer

//set a default template
	if (empty($_SESSION["template_full_path"])) { //build template if session template has no length
		//set the template base path
		$template_base_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';

		//get the contents of the template and save it to the template variable
		$template_full_path = $template_base_path.'/'.$settings->get('domain', 'template', 'default').'/template.php';
		if (!file_exists($template_full_path)) {
			$template_full_path = $template_base_path.'/default/template.php';
		}
		$_SESSION["template_full_path"] = $template_full_path;
	}

//get the template_name and template_dir
	$theme_dir = dirname(__DIR__, 1).'/themes';
	$template_name = $settings->get('domain', 'template', 'default');
	if (file_exists($theme_dir.'/'.$template_name.'/template.php')) {
		$template_dir = $theme_dir.'/'.$template_name;
	}
	else {
		$template_name = 'default';
		$template_dir = $theme_dir.'/'.$template_name;
	}

//initialize a template object
	$view = new template();
	$view->engine = 'smarty';
	$view->template_dir = $template_dir;
	$view->cache_dir = sys_get_temp_dir();
	$view->init();

//add multi-lingual support
	$language = new text;
	$text_default = $language->get();
	$text_application = $language->get(null, 'themes/'.$settings->get('domain', 'template', 'default'));
	$text = array_merge($text_default, $text_application);

//create token
	$object = new token;
	$domain_json_token = $object->create('/core/domains/domain_json.php');

//set the variable
	$logo = '';

//set template variables

	//add self
		$view->assign('php_self', basename($_SERVER['PHP_SELF']));
	//add translations
		foreach($text as $key => $value) {
			$array[str_replace('-', '_', $key)] = $value;
		}
		$view->assign('text', $array);
	//project path
		$view->assign('project_path', PROJECT_PATH);
	//domain menu
		$view->assign('domain_menu', escape($settings->get('domain', 'menu')));
	//domain json token
		$view->assign('domain_json_token_name', $domain_json_token['name']);
		$view->assign('domain_json_token_hash', $domain_json_token['hash']);
	//theme settings
		if (is_array($_SESSION['theme']) && @sizeof($_SESSION['theme']) != 0) {
			//load into array
				foreach ($_SESSION['theme'] as $subcategory => $setting) {
					switch($subcategory) {
						//exceptions
							case 'favicon':
							case 'custom_css':
								if ($setting['text'] != '') {
									$tmp_url = parse_url($setting['text']);
									$tmp_path = pathinfo($setting['text']);
									if (
										is_array($tmp_url) && @sizeof($tmp_url) != 0 &&
										is_array($tmp_path) && @sizeof($tmp_path) != 0 &&
										(
											(!empty($tmp_url['scheme']) && $tmp_url['scheme'].'://'.$tmp_url['host'].$tmp_url['path'] == $tmp_path['dirname'].'/'.$tmp_path['filename'].'.'.$tmp_path['extension']) //is url
											|| $tmp_url['path'] == $tmp_path['dirname'].'/'.$tmp_path['filename'].'.'.$tmp_path['extension'] //is path
										)) {
										$settings_array['theme'][$subcategory] = $setting['text'];
									}
									unset($tmp_url, $tmp_path);
								}
								break;
						//otherwise
							default:
								if (isset($setting['text']) && $setting['text'] != '') {
									$settings_array['theme'][$subcategory] = str_replace('&lowbar;','_',escape($setting['text']));
								}
								else if (isset($setting['numeric']) && is_numeric($setting['numeric'])) {
									$settings_array['theme'][$subcategory] = $setting['numeric'];
								}
								else if (isset($setting['boolean'])) {
									$settings_array['theme'][$subcategory] = $setting['boolean'] == 'true' ? true : false;
								}
								else {
									$settings_array['theme'][$subcategory] = escape($setting);
								}
					}
				}

			//pre-process some settings
				$settings_array['theme']['favicon'] = !empty($settings_array['theme']['favicon']) ? $settings_array['theme']['favicon'] : PROJECT_PATH.'/themes/default/favicon.ico';
				$settings_array['theme']['font_loader_version'] = !empty($settings_array['theme']['font_loader_version']) ? urlencode($settings_array['theme']['font_loader_version']) : '1';
				$settings_array['theme']['message_delay'] = isset($settings_array['theme']['message_delay']) ? 1000 * (float) $settings_array['theme']['message_delay'] : 3000;
				$settings_array['theme']['menu_side_width_contracted'] = isset($settings_array['theme']['menu_side_width_contracted']) ? $settings_array['theme']['menu_side_width_contracted'] : '60';
				$settings_array['theme']['menu_side_width_expanded'] = isset($settings_array['theme']['menu_side_width_expanded']) ? $settings_array['theme']['menu_side_width_expanded'] : '225';
				$settings_array['theme']['menu_side_toggle_hover_delay_expand'] = isset($settings_array['theme']['menu_side_toggle_hover_delay_expand']) ? $settings_array['theme']['menu_side_toggle_hover_delay_expand'] : '300';
				$settings_array['theme']['menu_side_toggle_hover_delay_contract'] = isset($settings_array['theme']['menu_side_toggle_hover_delay_contract']) ? $settings_array['theme']['menu_side_toggle_hover_delay_contract'] : '1000';
				$settings_array['theme']['menu_style'] = !empty($settings_array['theme']['menu_style']) ? $settings_array['theme']['menu_style'] : 'fixed';
				$settings_array['theme']['menu_position'] = isset($settings_array['theme']['menu_position']) ? $settings_array['theme']['menu_position'] : 'top';
				$settings_array['theme']['footer'] = isset($settings_array['theme']['footer']) ? $settings_array['theme']['footer'] : '&copy; '.$text['theme-label-copyright'].' 2008 - '.date('Y')." <a href='http://www.fusionpbx.com' class='footer' target='_blank'>fusionpbx.com</a> ".$text['theme-label-all_rights_reserved'];
				$settings_array['theme']['menu_side_item_main_sub_icon_contract'] = !empty($settings_array['theme']['menu_side_item_main_sub_icon_contract']) ? explode(' ', $settings_array['theme']['menu_side_item_main_sub_icon_contract'])[1] : null;
				$settings_array['theme']['menu_side_item_main_sub_icon_expand'] = !empty($settings_array['theme']['menu_side_item_main_sub_icon_expand']) ? explode(' ', $settings_array['theme']['menu_side_item_main_sub_icon_expand'])[1] : null;
				$settings_array['theme']['menu_brand_type'] = $settings->get('theme', 'menu_brand_type', 'image');
			//assign the settings
				$view->assign('settings', $settings_array);
		}
	//background video
		if (!empty($_SESSION['theme']['background_video']) && is_array($_SESSION['theme']['background_video'])) {
			$view->assign('background_video', $_SESSION['theme']['background_video'][0]);
		}
	//document title
		if (!empty($settings->get('theme', 'title')) && $settings->get('theme', 'title') != '') {
			$document_title = $settings->get('theme', 'title');
		}
		$document_title = (!empty($document['title']) ? $document['title'].' - ' : null).($document_title ?? '');
		$view->assign('document_title', $document_title);
	//domain selector control
		$domain_selector_enabled = permission_exists('domain_select') && $domain_count > 1 ? true : false;
		$view->assign('domain_selector_enabled', $domain_selector_enabled);
	//browser name
		$user_agent = http_user_agent();
		$browser_version = $user_agent['version'];
		$view->assign('browser_name', $user_agent['name']);
		$view->assign('browser_name_short', $user_agent['name_short']);
	//login state
		$authenticated = isset($_SESSION['username']) && !empty($_SESSION['username']) ? true : false;
		$view->assign('authenticated', $authenticated);
	//domains application path
		$view->assign('domains_app_path', PROJECT_PATH.(file_exists($_SERVER['DOCUMENT_ROOT'].'/app/domains/domains.php') ? '/app/domains/domains.php' : '/core/domains/domains.php'));
	//domain count
		$view->assign('domain_count', $domain_count);
	//domain selector row background colors
		$view->assign('domain_selector_background_color_1', !empty($_SESSION['theme']['domain_inactive_background_color'][0]) != '' ? $_SESSION['theme']['domain_inactive_background_color'][0] : '#eaedf2');
		$view->assign('domain_selector_background_color_2', !empty($_SESSION['theme']['domain_inactive_background_color'][1]) != '' ? $_SESSION['theme']['domain_inactive_background_color'][1] : '#ffffff');
		$view->assign('domain_active_background_color', !empty($settings->get('theme', 'domain_active_background_color')) ? $settings->get('theme', 'domain_active_background_color') : '#eeffee');
	//domain list
		$view->assign('domains', $domains);
	//domain uuid
		$view->assign('domain_uuid', $domain_uuid);
	//menu container
		//load menu array into the session
			if (!isset($_SESSION['menu']['array'])) {
				$menu = new menu;
				$menu->menu_uuid = $settings->get('domain', 'menu');
				$_SESSION['menu']['array'] = $menu->menu_array();
				unset($menu);
			}
		//build menu by style
			switch ($settings->get('theme', 'menu_style')) {
				case 'side':
					$view->assign('menu_side_state', (!empty($settings->get('theme', 'menu_side_state')) && $settings->get('theme', 'menu_side_state') != '' ? $settings->get('theme', 'menu_side_state') : 'expanded'));
					if ($settings->get('theme', 'menu_side_state') != 'hidden') {
						$menu_side_toggle = $settings->get('theme', 'menu_side_toggle') == 'hover' ? " onmouseenter=\"clearTimeout(menu_side_contract_timer); if ($('#menu_side_container').width() < 100) { menu_side_expand_start(); }\" onmouseleave=\"clearTimeout(menu_side_expand_timer); if ($('#menu_side_container').width() > 100 && $('#menu_side_state_current').val() != 'expanded') { menu_side_contract_start(); }\"" : null;
					}
					$container_open = "<div id='menu_side_container' style='width: ".(in_array($settings->get('theme', 'menu_side_state'), ['expanded','hidden']) ? ($settings->get('theme', 'menu_side_width_expanded') ?? 225) : ($settings->get('theme', 'menu_side_width_contracted') ?? 60))."px; ".($settings->get('theme', 'menu_side_state') == 'hidden' ? "display: none;'" : "' class='hide-xs'").$menu_side_toggle." >\n";
					$menu = new menu;
					$menu->text = $text;
					$menu_html = $menu->menu_vertical($_SESSION['menu']['array']);
					unset($menu);
					break;
				case 'inline':
					$container_open = "<div class='container-fluid' style='padding: 0;' align='".($settings->get('theme', 'logo_align') != '' ? $settings->get('theme', 'logo_align') : 'left')."'>\n";
					if ($_SERVER['PHP_SELF'] != PROJECT_PATH.'/core/install/install.php') {
						$logo = "<a href='".PROJECT_PATH."/'><img src='".($settings->get('theme', 'logo') ?: PROJECT_PATH.'/themes/default/images/logo.png')."' style='padding: 15px 20px; ".($settings->get('theme', 'logo_style') ?: null)."'></a>";
					}
					$menu = new menu;
					$menu->text = $text;
					$menu_html = $menu->menu_horizontal($_SESSION['menu']['array']);
					unset($menu);
					break;
				case 'static':
					$container_open = "<div class='container-fluid' style='padding: 0;' align='center'>\n";
					$menu = new menu;
					$menu->text = $text;
					$menu_html = $menu->menu_horizontal($_SESSION['menu']['array']);
					unset($menu);
					break;
				case 'fixed':
				default:
					$menu = new menu;
					$menu->text = $text;
					$menu_html = $menu->menu_horizontal($_SESSION['menu']['array']);
					unset($menu);
					$container_open = "<div class='container-fluid' style='padding: 0;' align='center'>\n";
					break;
			}
		$view->assign('logo', $logo);
		$view->assign('menu', $menu_html);
		$view->assign('container_open', $container_open);
		$view->assign('container_close', '</div>');
		$view->assign('document_body', $body);
		$view->assign('current_year', date('Y'));

	//login logo
		//determine logo source
			if (!empty($settings->get('theme', 'logo_login')) && $settings->get('theme', 'logo_login') != '') {
				$login_logo_source = $settings->get('theme', 'logo_login');
			}
			else if (!empty($settings->get('theme', 'logo')) && $settings->get('theme', 'logo') != '') {
				$login_logo_source = $settings->get('theme', 'logo');
			}
			else {
				$login_logo_source = PROJECT_PATH.'/themes/default/images/logo_login.png';
			}
		//determine logo dimensions
			if (!empty($settings->get('theme', 'login_logo_width')) && $settings->get('theme', 'login_logo_width') != '') {
				$login_logo_width = $settings->get('theme', 'login_logo_width');
			}
			else {
				$login_logo_width = 'auto; max-width: 300px';
			}
			if (!empty($settings->get('theme', 'login_logo_height')) && $settings->get('theme', 'login_logo_height') != '') {
				$login_logo_height = $settings->get('theme', 'login_logo_height');
			}
			else {
				$login_logo_height = 'auto; max-height: 300px';
			}
		$view->assign('login_logo_source', $login_logo_source);
		$view->assign('login_logo_width', $login_logo_width);
		$view->assign('login_logo_height', $login_logo_height);

	//login page
		//$view->assign('login_page', $login_page);

	//messages
		$view->assign('messages', message::html(true, '		'));

	//set the input toggle style options: select, switch_round, switch_square
		$view->assign('input_toggle_style_switch', $input_toggle_style_switch);

	//session timer
		if ($authenticated &&
			file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH.'/app/session_timer/session_timer.php') &&
			$settings->get('security', 'session_timer_enabled', false)
			) {
			include_once PROJECT_PATH.'app/session_timer/session_timer.php';
			$view->assign('session_timer', $session_timer);
		}

	//render the view
		$output = $view->render('template.php');

	//unset background image
		unset($_SESSION['background_image']);

//send the output to the browser
	echo $output;
	unset($output);

//$statsauth = "a3az349x2bf3fdfa8dbt7x34fas5X";
//require_once "stats/stat_sadd.php";

?>
