<?php

require_once "root.php";
require_once "resources/require.php";

ob_start('ob_gzhandler');
header('Content-type: text/css; charset: UTF-8');
header('Cache-Control: must-revalidate');
header('Expires: '.gmdate('D, d M Y H:i:s',time()+3600).' GMT');

//parse fonts (add surrounding single quotes to each font name)
	if (is_array($_SESSION['theme']) && sizeof($_SESSION['theme']) > 0) {
		foreach ($_SESSION['theme'] as $subcategory => $type) {
			if (substr_count($subcategory, '_font') > 0) {
				$font_string = $type['text'];
				if ($font_string != '') {
					if (substr_count($font_string, ',') > 0) {
						$tmp_array = explode(',', $font_string);
					}
					else {
						$tmp_array[] = $font_string;
					}
					foreach ($tmp_array as $font_name) {
						$font_name = trim($font_name, "'");
						$font_name = trim($font_name, '"');
						$font_name = trim($font_name);
						$fonts[] = $font_name;
					}
					if (sizeof($fonts) == 1 && strtolower($fonts[0]) != 'arial') { $fonts[] = 'Arial'; } //fall back font
					$_SESSION['theme'][$subcategory]['text'] = "'".implode("','", $fonts)."'";
				}
			}
			unset($fonts, $tmp_array);
		}
	}

//determine which background image/color settings to use (login or standard)
	$background_images_enabled = false;
	if (isset($_SESSION['username']) && $_SESSION['username'] != '') {
		//logged in - use standard background images/colors
		if (isset($_SESSION['theme']) && isset($_SESSION['theme']['background_image_enabled']) && $_SESSION['theme']['background_image_enabled']['boolean'] == 'true' && is_array($_SESSION['theme']['background_image'])) {
			$background_images_enabled = true;
			$background_images = $_SESSION['theme']['background_image'];
		}
		else {
			$background_colors[0] = $_SESSION['theme']['background_color'][0];
			$background_colors[1] = $_SESSION['theme']['background_color'][1];
		}
	}
	else {
		//not logged in - try using login background images/colors
		if (isset($_SESSION['theme']) && $_SESSION['theme']['login_background_image_enabled']['boolean'] == 'true' && is_array($_SESSION['theme']['login_background_image'])) {
			$background_images_enabled = true;
			$background_images = $_SESSION['theme']['login_background_image'];
		}
		else if ($_SESSION['theme']['login_background_color'][0] != '' || $_SESSION['theme']['login_background_color'][1] != '') {
			$background_colors[0] = $_SESSION['theme']['login_background_color'][0];
			$background_colors[1] = $_SESSION['theme']['login_background_color'][1];
		}
		else {
			//otherwise, use standard background images/colors
			if ($_SESSION['theme']['background_image_enabled']['boolean'] == 'true' && is_array($_SESSION['theme']['background_image'])) {
				$background_images_enabled = true;
				$background_images = $_SESSION['theme']['background_image'];
			}
			else {
				$background_colors[0] = $_SESSION['theme']['background_color'][0];
				$background_colors[1] = $_SESSION['theme']['background_color'][1];
			}
		}
	}

//check for background image
	if ($background_images_enabled) {
		//background image is enabled
		$image_extensions = array('jpg','jpeg','png','gif');

		if (count($background_images) > 0) {

			if ((!isset($_SESSION['background_image'])) or strlen($_SESSION['background_image']) == 0) {
				$_SESSION['background_image'] = $background_images[array_rand($background_images)];
				$background_image = $_SESSION['background_image'];
			}

			//background image(s) specified, check if source is file or folder
			if (in_array(strtolower(pathinfo($background_image, PATHINFO_EXTENSION)), $image_extensions)) {
				$image_source = 'file';
			}
			else {
				$image_source = 'folder';
			}

			//is source (file/folder) local or remote
			if (substr($background_image, 0, 4) == 'http') {
				$source_path = $background_image;
			}
			else if (substr($background_image, 0, 1) == '/') { //
				//use project path as root
				$source_path = PROJECT_PATH.$background_image;
			}
			else {
				//use theme images/backgrounds folder as root
				$source_path = PROJECT_PATH.'/themes/default/images/backgrounds/'.$background_image;
			}

		}
		else {
			//not set, so use default backgrounds folder and images
			$image_source = 'folder';
			$source_path = PROJECT_PATH.'/themes/default/images/backgrounds';
		}

		if ($image_source == 'folder') {
			if (file_exists($_SERVER["DOCUMENT_ROOT"].$source_path)) {
				//retrieve a random background image
				$dir_list = opendir($_SERVER["DOCUMENT_ROOT"].$source_path);
				$v_background_array = array();
				$x = 0;
				while (false !== ($file = readdir($dir_list))) {
					if ($file != "." AND $file != ".."){
						$new_path = $dir.'/'.$file;
						$level = explode('/',$new_path);
						if (in_array(strtolower(pathinfo($new_path, PATHINFO_EXTENSION)), $image_extensions)) {
							$v_background_array[] = $new_path;
						}
						if ($x > 100) { break; };
						$x++;
					}
				}
				if ($_SESSION['background_image'] == '' && sizeof($v_background_array) > 0) {
					$_SESSION['background_image'] = PROJECT_PATH.$source_path.$v_background_array[array_rand($v_background_array, 1)];
				}
			}
			else {
				$_SESSION['background_image'] = '';
			}

		}
		else if ($image_source == 'file') {
			$_SESSION['background_image'] = $source_path;
		}
	}

//check for background color
	else if (
		$background_colors[0] != '' ||
		$background_colors[1] != ''
		) { //background color 1 or 2 is enabled

		if ($background_colors[0] != '' && $background_colors[1] == '') { // use color 1
			$background_color = "background: ".$background_colors[0].";";
		}
		else if ($background_colors[0] == '' && $background_colors[1] != '') { // use color 2
			$background_color = "background: ".$background_colors[1].";";
		}
		else if ($background_colors[0] != '' && $background_colors[1] != '' && isset($_SESSION['theme']['background_radial_gradient']['text'])) { // radial gradient
			$background_color = "background: ".$background_colors[0].";\n";
			$background_color .= "background: -ms-radial-gradient(center, circle, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
			$background_color .= "background: radial-gradient(circle at center, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
		}
		else if ($background_colors[0] != '' && $background_colors[1] != '') { // vertical gradient
			$background_color = "background: ".$background_colors[0].";\n";
			$background_color .= "background: -ms-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
			$background_color .= "background: -moz-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
			$background_color .= "background: -o-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
			$background_color .= "background: -webkit-gradient(linear, left top, left bottom, color-stop(0, ".$background_colors[0]."), color-stop(1, ".$background_colors[1]."));\n";
			$background_color .= "background: -webkit-linear-gradient(top, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
			$background_color .= "background: linear-gradient(to bottom, ".$background_colors[0]." 0%, ".$background_colors[1]." 100%);\n";
		}
	}
	else { //default: white
		$background_color = "background: #ffffff;\n";
	}
?>

	html {
		height: 100%;
		width: 100%;
		}

	body {
		z-index: 1;
		position: absolute;
		margin: 0;
		padding: 0;
		overflow: auto;
		-ms-overflow-style: scrollbar; /* stops ie10+ from displaying auto-hiding scroll bar on top of the body content (the domain selector, specifically) */
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		text-align: center;
		<?php
		if ($_SESSION['background_image'] != '') {
			echo "background-image: url('".$_SESSION['background_image']."');\n";
			echo "background-size: 100% 100%;\n";
			echo "background-position: top;\n";
		}
		else {
			echo $background_color;
		}
		?>
		background-repeat: no-repeat;
		background-attachment: fixed;
		webkit-background-size:cover;
		-moz-background-size:cover;
		-o-background-size:cover;
		background-size:cover;
		}

	pre {
		white-space: pre-wrap;
		}

	div#footer {
		display: inline-block;
		width: 100%;
		background: <?php echo ($_SESSION['theme']['footer_background_color']['text'] != '') ? $_SESSION['theme']['footer_background_color']['text'] : 'rgba(0,0,0,0.2)'; ?>;
		text-align: center;
		vertical-align: middle;
		margin-bottom: 60px;
		padding: 8px;
		<?php $br = format_border_radius($_SESSION['theme']['footer_border_radius']['text'], '0 0 4px 4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	div#footer_login {
		position: absolute;
		left: 0;
		right: 0;
		bottom: 0;
		width: 100%;
		background: <?php echo ($_SESSION['theme']['footer_background_color']['text'] != '') ? $_SESSION['theme']['footer_background_color']['text'] : 'rgba(0,0,0,0.2)'; ?>;
		text-align: center;
		vertical-align: middle;
		padding: 8px;
		}

	.footer {
		font-size: 11px;
		font-family: arial;
		line-height: 14px;
		color: <?php echo ($_SESSION['theme']['footer_color']['text'] != '') ? $_SESSION['theme']['footer_color']['text'] : 'rgba(255,255,255,0.3)'; ?>;
		white-space: nowrap;
		}

	.footer > a:hover {
		color: <?php echo ($_SESSION['theme']['footer_color']['text'] != '') ? $_SESSION['theme']['footer_color']['text'] : 'rgba(255,255,255,0.3)'; ?>;
		}

/* MENU: BEGIN ******************************************************************/

	/* help bootstrap v4 menu be scrollable on mobile */
	@media screen and (max-width: 575px) {
		.navbar-collapse {
			max-height: calc(100vh - 60px);
			overflow-y: auto;
			}
	}

	/* main menu container */
	nav.navbar {
		<?php if ($_SESSION['theme']['menu_main_background_image']['text'] != '') { ?>
			background-image: url("<?php echo $_SESSION['theme']['menu_main_background_image']['text']; ?>");
			background-position: 0px 0px;
			background-repeat: repeat-x;
		<?php } else {?>
			background: <?php echo ($_SESSION['theme']['menu_main_background_color']['text'] != '') ? $_SESSION['theme']['menu_main_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		<?php } ?>
		-webkit-box-shadow: <?php echo ($_SESSION['theme']['menu_main_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_main_shadow_color']['text'] : 'none';?>;
		-moz-box-shadow: <?php echo ($_SESSION['theme']['menu_main_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_main_shadow_color']['text'] : 'none';?>;
		box-shadow: <?php echo ($_SESSION['theme']['menu_main_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_main_shadow_color']['text'] : 'none';?>;
		<?php
		echo ($_SESSION['theme']['menu_main_border_color']['text'] == '' && $_SESSION['theme']['menu_main_border_size']['text'] == '') ? "border: 0;\n" : null;
		echo ($_SESSION['theme']['menu_main_border_color']['text'] != '') ? 'border-color: '.$_SESSION['theme']['menu_main_border_color']['text'].";\n" : null;
		echo ($_SESSION['theme']['menu_main_border_size']['text'] != '') ? 'border-width: '.$_SESSION['theme']['menu_main_border_size']['text'].";\n" : null;
		switch ($_SESSION['theme']['menu_style']['text']) {
			case 'inline': $default_radius = '4px'; break;
			case 'static': $default_radius = '0 0 4px 4px'; break;
			default: $default_radius = '0';
		}
		?>
		-moz-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : $default_radius; ?>;
		-webkit-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : $default_radius; ?>;
		-khtml-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : $default_radius; ?>;
		border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : $default_radius; ?>;
		padding: 0;
		}

	/* main menu logo */
	img.navbar-logo {
		border: none;
		height: 27px;
		width: auto;
		padding: 0 10px 0 7px;
		margin-top: -2px;
		cursor: pointer;
		}

	/* menu brand text */
	div.navbar-brand > a.navbar-brand-text {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color']['text'] : 'rgba(255,255,255,0.80)'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_brand_text_size']['text'] != '') ? $_SESSION['theme']['menu_brand_text_size']['text'] : '13pt'; ?>;
		white-space: nowrap;
		}

	/* menu brand text hover */
	div.navbar-brand > a.navbar-brand-text:hover {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		text-decoration: none;
		}

	/* main menu item */
	ul.navbar-nav > li.nav-item > a.nav-link {
		font-family: <?php echo ($_SESSION['theme']['menu_main_text_font']['text'] != '') ? $_SESSION['theme']['menu_main_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_main_text_size']['text'] != '') ? $_SESSION['theme']['menu_main_text_size']['text'] : '10.25pt'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_main_text_color']['text'] != '') ? $_SESSION['theme']['menu_main_text_color']['text'] : '#fff'; ?>;
		padding: 15px 10px 14px 10px; !important;
		}

	ul.navbar-nav > li.nav-item:hover > a.nav-link,
	ul.navbar-nav > li.nav-item:focus > a.nav-link,
	ul.navbar-nav > li.nav-item:active > a.nav-link {
		color: <?php echo ($_SESSION['theme']['menu_main_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_main_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_background_color_hover']['text'] : 'rgba(0,0,0,1.0)'; ?>
		}

	.navbar .navbar-nav > li > a > span.fas {
		margin: 1px 2px 0 0;
		}

	@media(min-width: 768px) {
		.dropdown:hover .dropdown-menu {
			display: block;
			}
		}

	/* sub menu container */
	ul.navbar-nav > li.nav-item > ul.dropdown-menu {
		margin-top: 0;
		padding-top: 0;
		padding-bottom: 10px;
		<?php
		echo ($_SESSION['theme']['menu_sub_border_color']['text'] == '' && $_SESSION['theme']['menu_sub_border_size']['text'] == '') ? "border: 0;\n" : null;
		echo ($_SESSION['theme']['menu_sub_border_color']['text'] != '') ? 'border-color: '.$_SESSION['theme']['menu_sub_border_color']['text'].";\n" : null;
		echo ($_SESSION['theme']['menu_sub_border_size']['text'] != '') ? 'border-width: '.$_SESSION['theme']['menu_sub_border_size']['text'].";\n" : null;
		?>
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		-webkit-box-shadow: <?php echo ($_SESSION['theme']['menu_sub_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_sub_shadow_color']['text'] : 'none';?>;
		-moz-box-shadow: <?php echo ($_SESSION['theme']['menu_sub_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_sub_shadow_color']['text'] : 'none';?>;
		box-shadow: <?php echo ($_SESSION['theme']['menu_sub_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_sub_shadow_color']['text'] : 'none';?>;
		<?php $br = format_border_radius($_SESSION['theme']['menu_sub_border_radius']['text'], '0 0 4px 4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	/* sub menu item */
	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link {
		font-family: <?php echo ($_SESSION['theme']['menu_sub_text_font']['text'] != '') ? $_SESSION['theme']['menu_sub_text_font']['text'] : 'arial'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color']['text'] : '#fff'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_sub_text_size']['text'] != '') ? $_SESSION['theme']['menu_sub_text_size']['text'] : '10pt'; ?>;
		margin: 0;
		padding: 3px 14px !important;
		}

	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link:hover,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link:focus,
	ul.navbar-nav > li.nav-item > ul.dropdown-menu > li.nav-item > a.nav-link:active {
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color_hover']['text'] : '#141414'; ?>;
		outline: none;
		}

	a.nav-link {
		text-align: left !important;
		}

	/* sub menu item icon */
	ul.dropdown-menu > li.nav-item > a.nav-link > span.fas {
		display: inline-block;
		font-size: 8pt;
		margin: 0 0 0 8px;
		opacity: 0.30;
		}

	/* domain name/selector */
	a.domain_selector_domain {
		color: <?php echo ($_SESSION['theme']['domain_color']['text'] != '') ? $_SESSION['theme']['domain_color']['text'] : 'rgba(255,255,255,0.8)'; ?>;
		}

	a.domain_selector_domain:hover,
	a.domain_selector_domain:focus,
	a.domain_selector_domain:active {
		color: <?php echo ($_SESSION['theme']['domain_color_hover']['text'] != '') ? $_SESSION['theme']['domain_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		}

	/* logout icon */
	a.logout_icon {
		color: <?php echo ($_SESSION['theme']['logout_icon_color']['text'] != '') ? $_SESSION['theme']['logout_icon_color']['text'] : 'rgba(255,255,255,0.8)'; ?>;
		}

	a.logout_icon:hover,
	a.logout_icon:focus,
	a.logout_icon:active {
		color: <?php echo ($_SESSION['theme']['logout_icon_color_hover']['text'] != '') ? $_SESSION['theme']['logout_icon_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		}

	a#header_logout_icon {
		display: inline-block;
		font-size: 11pt;
		padding-left: 5px;
		padding-right: 5px;
		margin-left: 5px;
		margin-right: 5px;
		}

	/* xs menu toggle button */
/*
	.navbar-inverse .navbar-toggle {
		background: transparent;
		border: none;
		padding: 16px 7px 17px 20px;
		margin: 0 8px;
		}

	.navbar-inverse .navbar-toggle:hover,
	.navbar-inverse .navbar-toggle:focus,
	.navbar-inverse .navbar-toggle:active {
		background: transparent;
		}
*/

	button.navbar-toggler {
		min-height: 50px;
		}

	button.navbar-toggler > span.fas.fa-bars {
		color: <?php echo ($_SESSION['theme']['menu_main_toggle_color']['text'] != '') ? $_SESSION['theme']['menu_main_toggle_color']['text'] : 'rgba(255,255,255,0.8)'; ?>;
		}

	button.navbar-toggler > span.fas.fa-bars:hover {
		color: <?php echo ($_SESSION['theme']['menu_main_toggle_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_toggle_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		}

/* SIDE MENU: Begin ***********************************************************/

	/* side menu container */
	div#menu_side_container {
		z-index: 99900;
		position: fixed;
		top: 0;
		left: 0;
		<?php
		if ($_SESSION['theme']['menu_side_state']['text'] == 'expanded' || $_SESSION['theme']['menu_side_state']['text'] == 'hidden') {
			echo "width: ".(is_numeric($_SESSION['theme']['menu_side_width_expanded']['text']) ? $_SESSION['theme']['menu_side_width_expanded']['text'] : '225')."px;\n";
		}
		else {
			echo "width: ".(is_numeric($_SESSION['theme']['menu_side_width_contracted']['text']) ? $_SESSION['theme']['menu_side_width_contracted']['text'] : '60')."px;\n";
		}
		?>
		height: 100%;
		overflow: auto;
		<?php if ($_SESSION['theme']['menu_main_background_image']['text'] != '') { ?>
			background-image: url("<?php echo $_SESSION['theme']['menu_main_background_image']['text']; ?>");
			background-position: 0px 0px;
			background-repeat: repeat-y;
		<?php } else {?>
			background: <?php echo ($_SESSION['theme']['menu_main_background_color']['text'] != '') ? $_SESSION['theme']['menu_main_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		<?php } ?>
		-webkit-box-shadow: <?php echo ($_SESSION['theme']['menu_main_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_main_shadow_color']['text'] : 'none';?>;
		-moz-box-shadow: <?php echo ($_SESSION['theme']['menu_main_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_main_shadow_color']['text'] : 'none';?>;
		box-shadow: <?php echo ($_SESSION['theme']['menu_main_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_main_shadow_color']['text'] : 'none';?>;
		<?php
		echo ($_SESSION['theme']['menu_main_border_color']['text'] == '' && $_SESSION['theme']['menu_main_border_size']['text'] == '') ? "border: 0;\n" : null;
		echo ($_SESSION['theme']['menu_main_border_color']['text'] != '') ? 'border-color: '.$_SESSION['theme']['menu_main_border_color']['text'].";\n" : null;
		echo ($_SESSION['theme']['menu_main_border_size']['text'] != '') ? 'border-width: '.$_SESSION['theme']['menu_main_border_size']['text'].";\n" : null;
		?>
		-moz-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		-webkit-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		-khtml-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		}

	/* menu side logo */
	a.menu_brand_image {
		display: inline-block;
		text-align: center;
		padding: 15px 20px;
		}

	a.menu_brand_image:hover {
		text-decoration: none;
		}

	img#menu_brand_image_contracted {
		border: none;
		width: auto;
		max-height: 20px;
		max-width: 20px;
		margin-left: -1px;
		}

	img#menu_brand_image_expanded {
		border: none;
		height: auto;
		max-width: 145px;
		max-height: 35px;
		margin-left: -7px;
		}

	/* menu brand text */
	a.menu_brand_text {
		display: inline-block;
		padding: 10px 20px;
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color']['text'] : 'rgba(255,255,255,0.90)'; ?>;
		font-weight: 600;
		white-space: nowrap;
		}

	a.menu_brand_text:hover {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		text-decoration: none;
		}

	/* menu side control container */
	div#menu_side_control_container {
		position: -webkit-sticky;
		position: sticky;
		z-index: 99901;
		top: 0;
		padding: 0;
		min-height: 75px;
		text-align: left;
		<?php if ($_SESSION['theme']['menu_main_background_image']['text'] != '') { ?>
			background-image: url("<?php echo $_SESSION['theme']['menu_main_background_image']['text']; ?>");
			background-position: 0px 0px;
			background-repeat: repeat-y;
		<?php } else {?>
			background: <?php echo ($_SESSION['theme']['menu_main_background_color']['text'] != '') ? $_SESSION['theme']['menu_main_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		<?php } ?>
		<?php
		echo ($_SESSION['theme']['menu_main_border_color']['text'] == '' && $_SESSION['theme']['menu_main_border_size']['text'] == '') ? "border: 0;\n" : null;
		echo ($_SESSION['theme']['menu_main_border_color']['text'] != '') ? 'border-color: '.$_SESSION['theme']['menu_main_border_color']['text'].";\n" : null;
		echo ($_SESSION['theme']['menu_main_border_size']['text'] != '') ? 'border-width: '.$_SESSION['theme']['menu_main_border_size']['text'].";\n" : null;
		?>
		-moz-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		-webkit-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		-khtml-border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		border-radius: <?php echo ($_SESSION['theme']['menu_main_border_radius']['text'] != '') ? $_SESSION['theme']['menu_main_border_radius']['text'] : '0'; ?>;
		}

	div#menu_side_container > a.menu_side_item_main,
	div#menu_side_container > div > a.menu_side_item_main,
	div#menu_side_container > div#menu_side_control_container a.menu_side_item_main {
		display: block;
		width: 100%;
		padding: 10px 20px;
		text-align: left;
		font-family: <?php echo ($_SESSION['theme']['menu_main_text_font']['text'] != '') ? $_SESSION['theme']['menu_main_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_main_text_size']['text'] != '') ? $_SESSION['theme']['menu_main_text_size']['text'] : '10.25pt'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_main_text_color']['text'] != '') ? $_SESSION['theme']['menu_main_text_color']['text'] : '#fff'; ?>;
		cursor: pointer;
		}

	div#menu_side_container > a.menu_side_item_main:hover,
	div#menu_side_container > a.menu_side_item_main:focus,
	div#menu_side_container > a.menu_side_item_main:active,
	div#menu_side_container > div > a.menu_side_item_main:hover,
	div#menu_side_container > div > a.menu_side_item_main:focus,
	div#menu_side_container > div > a.menu_side_item_main:active,
	div#menu_side_container > div#menu_side_control_container > div a.menu_side_item_main:hover,
	div#menu_side_container > div#menu_side_control_container > div a.menu_side_item_main:focus,
	div#menu_side_container > div#menu_side_control_container > div a.menu_side_item_main:active {
		color: <?php echo ($_SESSION['theme']['menu_main_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_main_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_background_color_hover']['text'] : 'rgba(0,0,0,1.0)'; ?>;
		text-decoration: none;
		}

	div#menu_side_container > a.menu_side_item_main > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main > i.menu_side_item_icon {
		color: <?php echo ($_SESSION['theme']['menu_main_icon_color']['text'] != '') ? $_SESSION['theme']['menu_main_icon_color']['text'] : '#fd9c03'; ?>;
	}

	div#menu_side_container > a.menu_side_item_main:hover > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main:focus > i.menu_side_item_icon,
	div#menu_side_container > a.menu_side_item_main:active > i.menu_side_item_icon {
		color: <?php echo ($_SESSION['theme']['menu_main_icon_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_icon_color_hover']['text'] : '#fd9c03'; ?>;
	}

	a.menu_side_item_sub {
		display: block;
		width: 100%;
		padding: 5px 20px 5px 45px;
		text-align: left;
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		font-family: <?php echo ($_SESSION['theme']['menu_sub_text_font']['text'] != '') ? $_SESSION['theme']['menu_sub_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_sub_text_size']['text'] != '') ? $_SESSION['theme']['menu_sub_text_size']['text'] : '10pt'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color']['text'] : '#fff'; ?>;
		cursor: pointer;
		}

	@media (max-width: 575.98px) {
		a.menu_side_item_sub {
			padding: 8px 20px 8px 45px;
			}
	}

	a.menu_side_item_sub:hover,
	a.menu_side_item_sub:focus,
	a.menu_side_item_sub:active {
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color_hover']['text'] : 'rgba(0,0,0,1.0)'; ?>;
		text-decoration: none;
		}

	a.menu_side_toggle {
		padding: 10px;
		cursor: pointer;
		}

	div#content_container {
		padding: 0;
		padding-top: 0px;
		text-align: center;
		}

	@media (max-width: 575.98px) {
		div#content_container {
			width: 100%;
			}
	}
	@media (min-width: 576px) {
		div#content_container {
			<?php
			if ($_SESSION['theme']['menu_side_state']['text'] == 'expanded') {
				$content_container_width = is_numeric($_SESSION['theme']['menu_side_width_expanded']['text']) ? $_SESSION['theme']['menu_side_width_expanded']['text'] : '225';
			}
			else if ($_SESSION['theme']['menu_side_state']['text'] == 'hidden') {
				$content_container_width = 0;
			}
			else {
				$content_container_width = is_numeric($_SESSION['theme']['menu_side_width_contracted']['text']) ? $_SESSION['theme']['menu_side_width_contracted']['text'] : '60';
			}
			?>
			width: calc(100% - <?php echo $content_container_width; ?>px);
			float: right;
			}
	}

/* BODY/HEADER BAR *****************************************************************/

	<?php if ($_SESSION['theme']['menu_style']['text'] == 'side') { ?>
		div#body_header {
			padding: 10px 10px 15px 10px;
			height: 50px;
			<?php echo $_SESSION['theme']['body_header_background_color']['text'] != '' ? 'background-color: '.$_SESSION['theme']['body_header_background_color']['text'].';' : null; ?>
			}
	<?php } else { ?>
		div#body_header {
			padding: 10px;
			margin-top: 5px;
			height: 40px;
			}
	<?php } ?>

	div#body_header_brand_image {
		display: inline-block;
		margin-left: 10px;
		}

	div#body_header_brand_image > a:hover {
		text-decoration: none;
		}

	img#body_header_brand_image {
		border: none;
		margin-top: -4px;
		height: auto;
		max-width: 145px;
		max-height: 35px;
		}

	div#body_header_brand_text {
		display: inline-block;
		margin: 3px 0 0 10px;
		}

	div#body_header_brand_text > a {
		color: <?php echo ($_SESSION['theme']['body_header_brand_text_color']['text'] != '') ? $_SESSION['theme']['body_header_brand_text_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		font-size: <?php echo ($_SESSION['theme']['body_header_brand_text_size']['text'] != '') ? $_SESSION['theme']['body_header_brand_text_size']['text'] : '16px'; ?>;
		font-weight: 600;
		text-decoration: none;
		}

	div#body_header_brand_text > a:hover {
		color: <?php echo ($_SESSION['theme']['body_header_brand_text_color_hover']['text'] != '') ? $_SESSION['theme']['body_header_brand_text_color_hover']['text'] : 'rgba(0,0,0,1.0)'; ?>;
		text-decoration: none;
		}

/* BUTTONS ********************************************************************/

	/* buttons */
	input.btn,
	input.button,
	button.btn-default {
		height: <?php echo ($_SESSION['theme']['button_height']['text'] != '') ? $_SESSION['theme']['button_height']['text'] : '28px'; ?>;
		padding: <?php echo ($_SESSION['theme']['button_padding']['text'] != '') ? $_SESSION['theme']['button_padding']['text'] : '5px 8px'; ?>;
		border: <?php echo ($_SESSION['theme']['button_border_size']['text'] != '') ? $_SESSION['theme']['button_border_size']['text'] : '1px'; ?> solid <?php echo ($_SESSION['theme']['button_border_color']['text'] != '') ? $_SESSION['theme']['button_border_color']['text'] : '#242424'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['button_border_radius']['text'], '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		<?php
		$color_1 = ($_SESSION['theme']['button_background_color']['text'] != '') ? $_SESSION['theme']['button_background_color']['text'] : '#4f4f4f';
		$color_2 = ($_SESSION['theme']['button_background_color_bottom']['text'] != '') ? $_SESSION['theme']['button_background_color_bottom']['text'] : '#000000';
		?>
		background: <?php echo $color_1; ?>;
		background-image: -ms-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -moz-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -o-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?php echo $color_1; ?>), color-stop(1, <?php echo $color_2; ?>));
		background-image: -webkit-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: linear-gradient(to bottom, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		<?php unset($color_1, $color_2); ?>
		font-family: <?php echo ($_SESSION['theme']['button_text_font']['text'] != '') ? $_SESSION['theme']['button_text_font']['text'] : 'Candara, Calibri, Segoe, "Segoe UI", Optima, Arial, sans-serif'; ?>;
		text-align: center;
		text-transform: uppercase;
		color: <?php echo ($_SESSION['theme']['button_text_color']['text'] != '') ? $_SESSION['theme']['button_text_color']['text'] : '#ffffff'; ?>;
		font-weight: <?php echo ($_SESSION['theme']['button_text_weight']['text'] != '') ? $_SESSION['theme']['button_text_weight']['text'] : 'bold'; ?>;
		font-size: <?php echo ($_SESSION['theme']['button_text_size']['text'] != '') ? $_SESSION['theme']['button_text_size']['text'] : '11px'; ?>;
		vertical-align: middle;
		white-space: nowrap;
		}

	input.btn:hover,
	input.btn:active,
	input.btn:focus,
	input.button:hover,
	input.button:active,
	input.button:focus,
	button.btn-default:hover,
	button.btn-default:active,
	button.btn-default:focus {
		cursor: pointer;
		border-color: <?php echo ($_SESSION['theme']['button_border_color_hover']['text'] != '') ? $_SESSION['theme']['button_border_color_hover']['text'] : '#000000'; ?>;
		<?php
		$color_1 = ($_SESSION['theme']['button_background_color_hover']['text'] != '') ? $_SESSION['theme']['button_background_color_hover']['text'] : '#000000';
		$color_2 = ($_SESSION['theme']['button_background_color_bottom_hover']['text'] != '') ? $_SESSION['theme']['button_background_color_bottom_hover']['text'] : '#000000';
		?>
		background: <?php echo $color_1; ?>;
		background-image: -ms-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -moz-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -o-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?php echo $color_1; ?>), color-stop(1, <?php echo $color_2; ?>));
		background-image: -webkit-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: linear-gradient(to bottom, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		<?php unset($color_1, $color_2); ?>
		color: <?php echo ($_SESSION['theme']['button_text_color_hover']['text'] != '') ? $_SESSION['theme']['button_text_color_hover']['text'] : '#ffffff'; ?>;
		}

	/* remove (along with icons in theme/default/config.php) after transition to button class */
	button.btn-icon {
		margin: 0 2px;
		white-space: nowrap;
		}

	/* control icons (define after the default bootstrap btn-default class) */
	button.list_control_icon,
	button.list_control_icon_disabled {
		width: 24px;
		height: 24px;
		padding: 2px;
		margin: 1px;
		border: <?php echo ($_SESSION['theme']['button_border_size']['text'] != '') ? $_SESSION['theme']['button_border_size']['text'] : '1px'; ?> solid <?php echo ($_SESSION['theme']['button_border_color']['text'] != '') ? $_SESSION['theme']['button_border_color']['text'] : '#242424'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['button_border_radius']['text'], '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		<?php
		$color_1 = ($_SESSION['theme']['button_background_color']['text'] != '') ? $_SESSION['theme']['button_background_color']['text'] : '#4f4f4f';
		$color_2 = ($_SESSION['theme']['button_background_color_bottom']['text'] != '') ? $_SESSION['theme']['button_background_color_bottom']['text'] : '#000000';
		?>
		background: <?php echo $color_1; ?>;
		background-image: -ms-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -moz-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -o-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?php echo $color_1; ?>), color-stop(1, <?php echo $color_2; ?>));
		background-image: -webkit-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: linear-gradient(to bottom, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		<?php unset($color_1, $color_2); ?>
		color: <?php echo ($_SESSION['theme']['button_text_color']['text'] != '') ? $_SESSION['theme']['button_text_color']['text'] : '#ffffff'; ?>;
		font-size: 10.5pt;
		text-align: center;
		-moz-opacity: 0.3;
		opacity: 0.3;
		}

	button.list_control_icon:hover,
	button.list_control_icon:active,
	button.list_control_icon:focus {
		cursor: pointer;
		border-color: <?php echo ($_SESSION['theme']['button_border_color_hover']['text'] != '') ? $_SESSION['theme']['button_border_color_hover']['text'] : '#000000'; ?>;
		<?php
		$color_1 = ($_SESSION['theme']['button_background_color_hover']['text'] != '') ? $_SESSION['theme']['button_background_color_hover']['text'] : '#000000';
		$color_2 = ($_SESSION['theme']['button_background_color_bottom_hover']['text'] != '') ? $_SESSION['theme']['button_background_color_bottom_hover']['text'] : '#000000';
		?>
		background: <?php echo $color_1; ?>;
		background-image: -ms-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -moz-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -o-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?php echo $color_1; ?>), color-stop(1, <?php echo $color_2; ?>));
		background-image: -webkit-linear-gradient(top, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		background-image: linear-gradient(to bottom, <?php echo $color_1; ?> 0%, <?php echo $color_2; ?> 100%);
		<?php unset($color_1, $color_2); ?>
		color: <?php echo ($_SESSION['theme']['button_text_color_hover']['text'] != '') ? $_SESSION['theme']['button_text_color_hover']['text'] : '#ffffff'; ?>;
		-moz-opacity: 1.0;
		opacity: 1.0;
		}

	<?php if ($_SESSION['theme']['button_icons']['text'] == 'always' || $_SESSION['theme']['button_icons']['text'] == 'auto' || !$_SESSION['theme']['button_icons']['text']) { ?>
		button:not(.btn-link) > span.button-label.pad {
			margin-left: 6px;
			}
	<?php } ?>

	a.disabled,
	button.btn.disabled {
		outline: none; /* hides the dotted outline of the anchor tag on focus/active */
		cursor: default;
		}

/* DISPLAY BREAKPOINTS ****************************************************************/

	/* screen = extra small */
	@media (max-width: 575.98px) {
		.hide-xs,
		.hide-sm-dn,
		.hide-md-dn,
		.hide-lg-dn {
			display: none;
			}

		.show-xs,
		.show-xs-inline,
		.show-sm-dn,
		.show-sm-dn-inline,
		.show-md-dn,
		.show-md-dn-inline,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-xs-block,
		.show-sm-dn-block,
		.show-md-dn-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-xs-inline-block,
		.show-sm-dn-inline-block,
		.show-md-dn-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-xs-table-cell,
		.show-sm-dn-table-cell,
		.show-md-dn-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen = small */
	@media (min-width: 576px) and (max-width: 767.98px) {
		.hide-sm,
		.hide-sm-dn,
		.hide-md-dn,
		.hide-lg-dn,
		.hide-sm-up {
			display: none;
			}

		.show-sm,
		.show-sm-dn,
		.show-sm-dn-inline,
		.show-md-dn,
		.show-md-dn-inline,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-sm-block,
		.show-sm-dn-block,
		.show-md-dn-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-sm-inline-block,
		.show-sm-dn-inline-block,
		.show-md-dn-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-sm-table-cell,
		.show-sm-dn-table-cell,
		.show-md-dn-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen = medium */
	@media (min-width: 768px) and (max-width: 991.98px) {
		.hide-md,
		.hide-md-dn,
		.hide-lg-dn,
		.hide-md-up,
		.hide-sm-up {
			display: none;
			}

		.show-md,
		.show-md-dn,
		.show-md-dn-inline,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-md-block,
		.show-md-dn-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-md-inline-block,
		.show-md-dn-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-md-table-cell,
		.show-md-dn-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen = large */
	@media (min-width: 992px) and (max-width: 1199.98px) {
		.hide-lg,
		.hide-lg-dn,
		.hide-lg-up,
		.hide-md-up,
		.hide-sm-up {
			display: none;
			}

		.show-lg,
		.show-lg-dn,
		.show-lg-dn-inline {
			display: inline;
			}

		.show-lg-block,
		.show-lg-dn-block {
			display: block;
			}

		.show-lg-inline-block,
		.show-lg-dn-inline-block {
			display: inline-block;
			}

		.show-lg-table-cell,
		.show-lg-dn-table-cell {
			display: table-cell;
			}
	}

	/* screen >= extra large */
	@media (min-width: 1200px) {
		.hide-xl,
		.hide-lg-up,
		.hide-md-up,
		.hide-sm-up {
			display: none;
			}

		.show-xl,
		.show-xl-inline {
			display: inline;
			}

		.show-xl-block {
			display: block;
			}

		.show-xl-inline-block {
			display: inline-block;
			}

		.show-xl-table-cell {
			display: table-cell;
			}
	}

	/* hide button labels on medium and smaller screens (only if icons present) */
	@media (max-width: 991.98px) {
		button:not(.btn-link) > span.button-label.hide-md-dn {
			display: none;
			}
	}

/* ICONS *********************************************************************/

	span.icon_body {
		width: 16px;
		height: 16px;
		color: <?php echo ($_SESSION['theme']['body_icon_color']['text'] != '') ? $_SESSION['theme']['body_icon_color']['text'] : 'rgba(0,0,0,0.25)'; ?>;
		border: 0;
		}

	span.icon_body:hover {
		color: <?php echo ($_SESSION['theme']['body_icon_color_hover']['text'] != '') ? $_SESSION['theme']['body_icon_color_hover']['text'] : 'rgba(0,0,0,0.5)'; ?>;
		}

/* DOMAIN SELECTOR ***********************************************************/

	#domains_container {
		z-index: 99990;
		position: absolute;
		right: 0;
		top: 0;
		bottom: 0;
		width: 360px;
		overflow: hidden;
		display: none;
		}

	#domains_block {
		position: absolute;
		right: -300px;
		top: 0;
		bottom: 0;
		width: 340px;
		padding: 20px 20px 100px 20px;
		font-family: arial, san-serif;
		font-size: 10pt;
		overflow: hidden;
		background: <?php echo ($_SESSION['theme']['domain_selector_background_color']['text'] != '') ? $_SESSION['theme']['domain_selector_background_color']['text'] : '#fff'; ?>;
		-webkit-box-shadow: <?php echo ($_SESSION['theme']['domain_selector_shadow_color']['text'] != '') ? '0 0 10px '.$_SESSION['theme']['domain_selector_shadow_color']['text'] : 'none'; ?>;
		-moz-box-shadow: <?php echo ($_SESSION['theme']['domain_selector_shadow_color']['text'] != '') ? '0 0 10px '.$_SESSION['theme']['domain_selector_shadow_color']['text'] : 'none'; ?>;
		box-shadow: <?php echo ($_SESSION['theme']['domain_selector_shadow_color']['text'] != '') ? '0 0 10px '.$_SESSION['theme']['domain_selector_shadow_color']['text'] : 'none'; ?>;
		}

	#domains_header {
		position: relative;
		width: 300px;
		height: 55px;
		margin-bottom: 20px;
		text-align: left;
		}

	#domains_header > a#domains_title {
		font-weight: 600;
		font-size: <?php echo ($_SESSION['theme']['heading_text_size']['text'] != '') ? $_SESSION['theme']['heading_text_size']['text'] : '15px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['heading_text_font']['text'] != '') ? $_SESSION['theme']['heading_text_font']['text'] : 'arial'; ?>;
		color: <?php echo ($_SESSION['theme']['domain_selector_title_color']['text'] != '') ? $_SESSION['theme']['domain_selector_title_color']['text'] : '#000'; ?>;
		}

	#domains_header > a#domains_title:hover {
		text-decoration: none;
		color: <?php echo ($_SESSION['theme']['domain_selector_title_color_hover']['text'] != '') ? $_SESSION['theme']['domain_selector_title_color_hover']['text'] : '#5082ca'; ?>;
		}

	#domains_list {
		position: relative;
		overflow: auto;
		width: 300px;
		height: 100%;
		padding: 1px;
		background: <?php echo ($_SESSION['theme']['domain_selector_list_background_color']['text'] != '') ? $_SESSION['theme']['domain_selector_list_background_color']['text'] : '#fff'; ?>;
		border: 1px solid <?php echo ($_SESSION['theme']['domain_selector_list_border_color']['text'] != '') ? $_SESSION['theme']['domain_selector_list_border_color']['text'] : '#a4aebf'; ?>;
		}

	div.domains_list_item, div.domains_list_item_active, div.domains_list_item_inactive {
		text-align: left;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['domain_selector_list_divider_color']['text'] != '') ? $_SESSION['theme']['domain_selector_list_divider_color']['text'] : '#c5d1e5'; ?>;
		padding: 5px 8px 8px 8px;
		overflow: hidden;
		white-space: nowrap;
		cursor: pointer;
		}

	div.domains_list_item span.domain_list_item_description,
	div.domains_list_item_active span.domain_list_item_description,
	div.domains_list_item_inactive span.domain_list_item_description,

	div.domains_list_item_active span.domain_active_list_item_description,
	div.domains_list_item_inactive span.domain_inactive_list_item_description {
		font-size: 11px;
		}

	div.domains_list_item span.domain_list_item_description,
	div.domains_list_item_active span.domain_list_item_description,
	div.domains_list_item_inactive span.domain_list_item_description {
		color: #999;
		}

	div.domains_list_item_active a {
		color: <?php echo ($_SESSION['theme']['domain_active_text_color']['text'] != '') ? $_SESSION['theme']['domain_active_text_color']['text'] : '#004083'; ?>;
	}
	div.domains_list_item_inactive a {
		color: <?php echo ($_SESSION['theme']['domain_inactive_text_color']['text'] != '') ? $_SESSION['theme']['domain_inactive_text_color']['text'] : '#004083'; ?>;
	}

	div.domains_list_item_active span.domain_active_list_item_description {
		color: <?php echo ($_SESSION['theme']['domain_active_desc_text_color']['text'] != '') ? $_SESSION['theme']['domain_active_desc_text_color']['text'] : '#999'; ?>;
		}

	div.domains_list_item_inactive span.domain_inactive_list_item_description {
		color: <?php echo ($_SESSION['theme']['domain_inactive_desc_text_color']['text'] != '') ? $_SESSION['theme']['domain_inactive_desc_text_color']['text'] : '#999'; ?>;
		}

	div.domains_list_item:hover a,
	div.domains_list_item:hover span {
		color: #5082ca;
		}

	div.domains_list_item_active:hover a,
	div.domains_list_item_active:hover span {
		color: <?php echo ($_SESSION['theme']['domain_active_text_color_hover']['text']); ?>;
	}

	div.domains_list_item_inactive:hover a,
	div.domains_list_item_inactive:hover span {
		color: <?php echo ($_SESSION['theme']['domain_inactive_text_color_hover']['text']); ?>;
	}

/* DOMAIN SELECTOR: END ********************************************************/

	#default_login {
		position: fixed;
		top: <?php echo ($_SESSION['theme']['login_body_top']['text'] != '') ? $_SESSION['theme']['login_body_top']['text'] : '50%'; ?>;
		left: <?php echo ($_SESSION['theme']['login_body_left']['text'] != '') ? $_SESSION['theme']['login_body_left']['text'] : '50%'; ?>;
		-moz-transform: translate(-50%, -50%);
		-webkit-transform: translate(-50%, -50%);
		-khtml-transform: translate(-50%, -50%);
		transform: translate(-50%, -50%);
		padding: <?php echo ($_SESSION['theme']['login_body_padding']['text'] != '') ? $_SESSION['theme']['login_body_padding']['text'] : '30px'; ?>;
		<?php echo ($_SESSION['theme']['login_body_width']['text'] != '') ? 'width: '.$_SESSION['theme']['login_body_width']['text'].";\n" : null; ?>
		background: <?php echo ($_SESSION['theme']['login_body_background_color']['text'] != '') ? $_SESSION['theme']['login_body_background_color']['text'] : "rgba(255,255,255,0.35)"; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['login_body_border_radius']['text'], '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		<?php if ($_SESSION['theme']['login_body_border_size']['text'] != '' || $_SESSION['theme']['login_body_border_color']['text'] != '') { echo "border-style: solid;\n"; } ?>
		<?php echo ($_SESSION['theme']['login_body_border_size']['text'] != '') ? 'border-width: '.$_SESSION['theme']['login_body_border_size']['text'].";\n" : null; ?>
		<?php echo ($_SESSION['theme']['login_body_border_color']['text'] != '') ? 'border-color: '.$_SESSION['theme']['login_body_border_color']['text'].";\n" : null; ?>
		-webkit-box-shadow: <?php echo ($_SESSION['theme']['login_body_shadow_color']['text'] != '') ? '0 1px 20px '.$_SESSION['theme']['login_body_shadow_color']['text'] : 'none'; ?>;
		-moz-box-shadow: <?php echo ($_SESSION['theme']['login_body_shadow_color']['text'] != '') ? '0 1px 20px '.$_SESSION['theme']['login_body_shadow_color']['text'] : 'none'; ?>;
		box-shadow: <?php echo ($_SESSION['theme']['login_body_shadow_color']['text'] != '') ? '0 1px 20px '.$_SESSION['theme']['login_body_shadow_color']['text'] : 'none'; ?>;
		}

	#login_logo {
		text-decoration: none;
		}

	a.login_link {
		color: <?php echo ($_SESSION['theme']['login_link_text_color']['text'] != '') ? $_SESSION['theme']['login_link_text_color']['text'] : '#004083'; ?> !important;
		font-size: <?php echo ($_SESSION['theme']['login_link_text_size']['text'] != '') ? $_SESSION['theme']['login_link_text_size']['text'] : '11px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['login_link_text_font']['text'] != '') ? $_SESSION['theme']['login_link_text_font']['text'] : 'Arial'; ?>;
		text-decoration: none;
		}

	a.login_link:hover {
		color: <?php echo ($_SESSION['theme']['login_link_text_color_hover']['text'] != '') ? $_SESSION['theme']['login_link_text_color_hover']['text'] : '#5082ca'; ?> !important;
		cursor: pointer;
		text-decoration: none;
		}

	<?php
	//determine body padding & margins (overides on main_content style below) based on menu selection
		$menu_style = ($_SESSION['theme']['menu_style']['text'] != '') ? $_SESSION['theme']['menu_style']['text'] : 'fixed';
		$menu_position = ($_SESSION['theme']['menu_position']['text']) ? $_SESSION['theme']['menu_position']['text'] : 'top';
		switch ($menu_style) {
			case 'inline': $body_top_style = "margin-top: -8px;"; break;
			case 'static': $body_top_style = "margin-top: -5px;"; break;
			case 'fixed':
				switch ($menu_position) {
					case 'bottom': $body_top_style = "margin-top: 30px;"; break;
					case 'top':
					default: $body_top_style = "margin-top: 65px;"; break;
				}
		}
	?>

	#main_content {
		display: inline-block;
		width: 100%;
		<?php
		if (isset($background_images) || $background_colors[0] != '' || $background_colors[1] != '') {
			?>
			background: <?php echo ($_SESSION['theme']['body_color']['text'] != '') ? $_SESSION['theme']['body_color']['text'] : "#ffffff"; ?>;
			background-attachment: fixed;
			<?php $br = format_border_radius($_SESSION['theme']['body_border_radius']['text'], '4px'); ?>
			-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
			<?php unset($br); ?>
			-webkit-box-shadow: <?php echo ($_SESSION['theme']['body_shadow_color']['text'] != '') ? '0 1px 4px '.$_SESSION['theme']['body_shadow_color']['text'] : 'none';?>;
			-moz-box-shadow: <?php echo ($_SESSION['theme']['body_shadow_color']['text'] != '') ? '0 1px 4px '.$_SESSION['theme']['body_shadow_color']['text'] : 'none';?>;
			box-shadow: <?php echo ($_SESSION['theme']['body_shadow_color']['text'] != '') ? '0 1px 4px '.$_SESSION['theme']['body_shadow_color']['text'] : 'none';?>;
			padding: 20px;
			<?php
		}
		else {
			?>padding: 5px 10px 10px 10px;<?php
		}
		echo $body_top_style;
		?>
		text-align: left;
		color: <?php echo ($_SESSION['theme']['body_text_color']['text'] != '') ? $_SESSION['theme']['body_text_color']['text'] : '#5f5f5f'; ?>;
		font-size: <?php echo ($_SESSION['theme']['body_text_size']['text'] != '') ? $_SESSION['theme']['body_text_size']['text'] : '12px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['body_text_font']['text'] != '') ? $_SESSION['theme']['body_text_font']['text'] : 'arial'; ?>;
		}

	/* default body padding */
	.container-fluid {
		width: <?php echo ($_SESSION['theme']['body_width']['text'] != '') ? $_SESSION['theme']['body_width']['text'] : '90%'; ?>;
		}

	/* maximize viewport usage on xs displays */
	@media(min-width: 0px) and (max-width: 767px) {
		.container-fluid {
			width: 100%;
			}

		#main_content {
			padding: 8px;
			}
		}

/* GENERAL ELEMENTS *****************************************************************/

	img {
		border: none;
		}

	.title, b {
		color: <?php echo ($_SESSION['theme']['heading_text_color']['text'] != '') ? $_SESSION['theme']['heading_text_color']['text'] : '#952424'; ?>;
		font-size: <?php echo ($_SESSION['theme']['heading_text_size']['text'] != '') ? $_SESSION['theme']['heading_text_size']['text'] : '15px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['heading_text_font']['text'] != '') ? $_SESSION['theme']['heading_text_font']['text'] : 'arial'; ?>;
		font-weight: bold
		}

	a,
	button.btn.btn-link {
		color: <?php echo ($_SESSION['theme']['text_link_color']['text'] != '') ? $_SESSION['theme']['text_link_color']['text'] : '#004083'; ?>;
		text-decoration: none;
		}

	a:hover,
	button.btn.btn-link:hover {
		color: <?php echo ($_SESSION['theme']['text_link_color_hover']['text'] != '') ? $_SESSION['theme']['text_link_color_hover']['text'] : '#5082ca'; ?>;
		text-decoration: none;
		}

	button.btn {
		margin-left: 2px;
		margin-right: 2px;
		}

	button.btn.btn-link {
		margin: 0;
		margin-top: -2px;
		padding: 0;
		border: none;
		font-size: inherit;
		font-family: inherit;
		}

	button.btn > span.fas.fa-spin {
		display: inline-block;
		}

	form {
		margin: 0;
		}

	form.inline {
		display: inline-block;
		}

	/* style placeholder text (for browsers that support the attribute) - note: can't stack, each must be seperate */
	<?php $placeholder_color = ($_SESSION['theme']['input_text_placeholder_color']['text'] != '') ? $_SESSION['theme']['input_text_placeholder_color']['text'].';' : '#999999; opacity: 1.0;'; ?>
	::-webkit-input-placeholder { color: <?php echo $placeholder_color; ?> } /* chrome/opera/safari */
	::-moz-placeholder { color: <?php echo $placeholder_color; ?> } /* ff 19+ */
	:-moz-placeholder { color: <?php echo $placeholder_color; ?> } /* ff 18- */
	:-ms-input-placeholder { color: <?php echo $placeholder_color; ?> } /* ie 10+ */
	::placeholder { color: <?php echo $placeholder_color; ?> } /* official standard */

	select.txt,
	textarea.txt,
	input[type=text].txt,
	input[type=number].txt,
	input[type=password].txt,
	label.txt,
	select.formfld,
	textarea.formfld,
	input[type=text].formfld,
	input[type=number].formfld,
	input[type=url].formfld,
	input[type=password].formfld,
	label.formfld {
		font-family: <?php echo ($_SESSION['theme']['input_text_font']['text'] != '') ? $_SESSION['theme']['input_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['input_text_size']['text'] != '') ? $_SESSION['theme']['input_text_size']['text'] : '12px'; ?>;
		color: <?php echo ($_SESSION['theme']['input_text_color']['text'] != '') ? $_SESSION['theme']['input_text_color']['text'] : '#000000'; ?>;
		text-align: left;
		height: 28px;
		padding: 4px 6px;
		margin: 1px;
		border-width: <?php echo ($_SESSION['theme']['input_border_size']['text'] != '') ? $_SESSION['theme']['input_border_size']['text'] : '1px'; ?>;
		border-style: solid;
		border-color: <?php echo ($_SESSION['theme']['input_border_color']['text'] != '') ? $_SESSION['theme']['input_border_color']['text'] : '#c0c0c0'; ?>;
		background: <?php echo ($_SESSION['theme']['input_background_color']['text'] != '') ? $_SESSION['theme']['input_background_color']['text'] : '#ffffff'; ?>;
		<?php
		if ($_SESSION['theme']['input_shadow_inner_color']['text'] != '') {
			$inner_color = $_SESSION['theme']['input_shadow_inner_color']['text'];
			$shadows[] = "0 0 3px ".$inner_color." inset";
		}
		if ($_SESSION['theme']['input_shadow_outer_color']['text'] != '') {
			$outer_color = $_SESSION['theme']['input_shadow_outer_color']['text'];
			$shadows[] = "0 0 5px ".$outer_color;
		}
		if (is_array($shadows) && sizeof($shadows) > 0) {
			echo '-webkit-box-shadow: '.implode(', ', $shadows).";\n";
			echo '-moz-box-shadow: '.implode(', ', $shadows).";\n";
			echo 'box-shadow: '.implode(', ', $shadows).";\n";
		}
		unset($shadows);
		?>
		<?php $br = format_border_radius($_SESSION['theme']['input_border_radius']['text'], '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		vertical-align: middle;
		}

	textarea.txt,
	input[type=text].txt,
	input[type=number].txt,
	input[type=password].txt,
	textarea.formfld,
	input[type=text].formfld,
	input[type=number].formfld,
	input[type=url].formfld,
	input[type=password].formfld {
		transition: width 0.25s;
		-moz-transition: width 0.25s;
		-webkit-transition: width 0.25s;
		max-width: 500px;
		}

	select.txt,
	select.formfld {
		padding: 4px 2px;
		}

	textarea.txt:hover,
	input[type=text].txt:hover,
	input[type=number].txt:hover,
	input[type=password].txt:hover,
	label.txt:hover,
	textarea.formfld:hover,
	input[type=text].formfld:hover,
	input[type=number].formfld:hover,
	input[type=url].formfld:hover,
	input[type=password].formfld:hover,
	label.formfld:hover {
		border-color: <?php echo ($_SESSION['theme']['input_border_color_hover']['text'] != '') ? $_SESSION['theme']['input_border_color_hover']['text'] : '#c0c0c0'; ?>;
		}

	textarea.txt:focus,
	input[type=text].txt:focus,
	input[type=number].txt:focus,
	input[type=password].txt:focus,
	label.txt:focus,
	textarea.formfld:focus,
	input[type=text].formfld:focus,
	input[type=number].formfld:focus,
	input[type=url].formfld:focus,
	input[type=password].formfld:focus,
	label.formfld:focus {
		border-color: <?php echo ($_SESSION['theme']['input_border_color_focus']['text'] != '') ? $_SESSION['theme']['input_border_color_focus']['text'] : '#c0c0c0'; ?>;
		/* first clear */
		-webkit-box-shadow: none;
		-moz-box-shadow: none;
		box-shadow: none;
		<?php
		/* then set */
		$shadow_inset = $shadow_outset = '';
		if ($_SESSION['theme']['input_shadow_inner_color_focus']['text'] != '') {
			$inner_color = $_SESSION['theme']['input_shadow_inner_color_focus']['text'];
			$shadow_inset = "0 0 3px ".$inner_color." inset";
		}
		if ($_SESSION['theme']['input_shadow_outer_color_focus']['text'] != '') {
			$outer_color = $_SESSION['theme']['input_shadow_outer_color_focus']['text'];
			$shadow_outset = "0 0 5px ".$outer_color;
		}
		?>
		<?php if ($shadow_inset != '' || $shadow_outset != '') { ?>
			-webkit-box-shadow: <?php echo $shadow_inset.(($shadow_inset != '') ? ', ' : null).$shadow_outset; ?>;
			-moz-box-shadow: <?php echo $shadow_inset.(($shadow_inset != '') ? ', ' : null).$shadow_outset; ?>;
			box-shadow: <?php echo $shadow_inset.(($shadow_inset != '') ? ', ' : null).$shadow_outset; ?>;
		<?php } ?>
		}

	textarea.txt,
	textarea.formfld {
		resize: both;
		}

	input.login {
		font-family: <?php echo ($_SESSION['theme']['login_input_text_font']['text'] != '') ? $_SESSION['theme']['login_input_text_font']['text'] : (($_SESSION['theme']['input_text_font']['text'] != '') ? $_SESSION['theme']['input_text_font']['text'] : 'Arial'); ?>;
		font-size: <?php echo ($_SESSION['theme']['login_input_text_size']['text'] != '') ? $_SESSION['theme']['login_input_text_size']['text'] : (($_SESSION['theme']['input_text_size']['text'] != '') ? $_SESSION['theme']['input_text_size']['text'] : '12px'); ?>;
		color: <?php echo ($_SESSION['theme']['login_input_text_color']['text'] != '') ? $_SESSION['theme']['login_input_text_color']['text'] : (($_SESSION['theme']['input_text_color']['text'] != '') ? $_SESSION['theme']['input_text_color']['text'] : '#000000'); ?>;
		border-width: <?php echo ($_SESSION['theme']['login_input_border_size']['text'] != '') ? $_SESSION['theme']['login_input_border_size']['text'] : (($_SESSION['theme']['input_border_size']['text'] != '') ? $_SESSION['theme']['input_border_size']['text'] : '1px'); ?>;
		border-color: <?php echo ($_SESSION['theme']['login_input_border_color']['text'] != '') ? $_SESSION['theme']['login_input_border_color']['text'] : (($_SESSION['theme']['input_border_color']['text'] != '') ? $_SESSION['theme']['input_border_color']['text'] : '#c0c0c0'); ?>;
		background: <?php echo ($_SESSION['theme']['login_input_background_color']['text'] != '') ? $_SESSION['theme']['login_input_background_color']['text'] : (($_SESSION['theme']['input_background_color']['text'] != '') ? $_SESSION['theme']['input_background_color']['text'] : '#ffffff'); ?>;
		/* first clear */
		-webkit-box-shadow: none;
		-moz-box-shadow: none;
		box-shadow: none;
		<?php
		/* then set */
		if ($_SESSION['theme']['login_input_shadow_inner_color']['text'] != '') {
			$inner_color = $_SESSION['theme']['login_input_shadow_inner_color']['text'];
			$shadows[] = "0 0 3px ".$inner_color." inset";
		}
		else if ($_SESSION['theme']['input_shadow_inner_color']['text'] != '') {
			$inner_color = $_SESSION['theme']['input_shadow_inner_color']['text'];
			$shadows[] = "0 0 3px ".$inner_color." inset";
		}
		if ($_SESSION['theme']['login_input_shadow_outer_color']['text'] != '') {
			$outer_color = $_SESSION['theme']['login_input_shadow_outer_color']['text'];
			$shadows[] = "0 0 5px ".$outer_color;
		}
		else if ($_SESSION['theme']['input_shadow_outer_color']['text'] != '') {
			$outer_color = $_SESSION['theme']['input_shadow_outer_color']['text'];
			$shadows[] = "0 0 5px ".$outer_color;
		}
		if (is_array($shadows) && sizeof($shadows) > 0) {
			echo '-webkit-box-shadow: '.implode(', ', $shadows).";\n";
			echo '-moz-box-shadow: '.implode(', ', $shadows).";\n";
			echo 'box-shadow: '.implode(', ', $shadows).";\n";
		}
		unset($shadows);
		?>
		<?php
		$br = ($_SESSION['theme']['login_input_border_radius']['text'] != '') ? $_SESSION['theme']['login_input_border_radius']['text'] : $_SESSION['theme']['input_border_radius']['text'];
		$br = format_border_radius($br, '3px');
		?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	input.login:hover {
		border-color: <?php echo ($_SESSION['theme']['login_input_border_color_hover']['text'] != '') ? $_SESSION['theme']['login_input_border_color_hover']['text'] : (($_SESSION['theme']['input_border_color_hover']['text'] != '') ? $_SESSION['theme']['input_border_color_hover']['text'] : '#c0c0c0'); ?>;
		}

	input.login:focus {
		border-color: <?php echo ($_SESSION['theme']['login_input_border_color_focus']['text'] != '') ? $_SESSION['theme']['login_input_border_color_focus']['text'] : (($_SESSION['theme']['input_border_color_focus']['text'] != '') ? $_SESSION['theme']['input_border_color_focus']['text'] : '#c0c0c0'); ?>;
		/* first clear */
		-webkit-box-shadow: none;
		-moz-box-shadow: none;
		box-shadow: none;
		<?php
		/* then set */
		$shadow_inset = $shadow_outset = '';
		if ($_SESSION['theme']['login_input_shadow_inner_color_focus']['text'] != '') {
			$inner_color = $_SESSION['theme']['login_input_shadow_inner_color_focus']['text'];
			$shadow_inset = "0 0 3px ".$inner_color." inset";
		}
		else if ($_SESSION['theme']['input_shadow_inner_color_focus']['text'] != '') {
			$inner_color = $_SESSION['theme']['input_shadow_inner_color_focus']['text'];
			$shadow_inset = "0 0 3px ".$inner_color." inset";
		}
		if ($_SESSION['theme']['login_input_shadow_outer_color_focus']['text'] != '') {
			$outer_color = $_SESSION['theme']['login_input_shadow_outer_color_focus']['text'];
			$shadow_outset = "0 0 5px ".$outer_color;
		}
		else if ($_SESSION['theme']['input_shadow_outer_color_focus']['text'] != '') {
			$outer_color = $_SESSION['theme']['input_shadow_outer_color_focus']['text'];
			$shadow_outset = "0 0 5px ".$outer_color;
		}
		?>
		<?php if ($shadow_inset != '' || $shadow_outset != '') { ?>
			-webkit-box-shadow: <?php echo $shadow_inset.(($shadow_inset != '') ? ', ' : null).$shadow_outset; ?>;
			-moz-box-shadow: <?php echo $shadow_inset.(($shadow_inset != '') ? ', ' : null).$shadow_outset; ?>;
			box-shadow: <?php echo $shadow_inset.(($shadow_inset != '') ? ', ' : null).$shadow_outset; ?>;
		<?php } ?>
		}

	/* style placeholder text (for browsers that support the attribute) - note: can't stack, each must be seperate */
	<?php $placeholder_color = ($_SESSION['theme']['login_input_text_placeholder_color']['text'] != '') ? $_SESSION['theme']['login_input_text_placeholder_color']['text'].';' : '#999999; opacity: 1.0;'; ?>
	input.login::-webkit-input-placeholder { color: <?php echo $placeholder_color; ?> } /* chrome/opera/safari */
	input.login::-moz-placeholder { color: <?php echo $placeholder_color; ?> } /* ff 19+ */
	input.login:-moz-placeholder { color: <?php echo $placeholder_color; ?> } /* ff 18- */
	input.login:-ms-input-placeholder { color: <?php echo $placeholder_color; ?> } /* ie 10+ */
	input.login::placeholder { color: <?php echo $placeholder_color; ?> } /* official standard */

	input[type=password].formfld_highlight_bad,
	input[type=password].formfld_highlight_bad:hover,
	input[type=password].formfld_highlight_bad:active,
	input[type=password].formfld_highlight_bad:focus {
		border-color: #aa2525;
		-webkit-box-shadow: 0 0 3px #aa2525 inset;
		-moz-box-shadow: 0 0 3px #aa2525 inset;
		box-shadow: 0 0 3px #aa2525 inset;
		}

	input[type=password].formfld_highlight_good,
	input[type=password].formfld_highlight_good:hover,
	input[type=password].formfld_highlight_good:active,
	input[type=password].formfld_highlight_good:focus {
		border-color: #2fb22f;
		-webkit-box-shadow: 0 0 3px #2fb22f inset;
		-moz-box-shadow: 0 0 3px #2fb22f inset;
		box-shadow: 0 0 3px #2fb22f inset;
		}

	/* removes spinners (increment/decrement controls) inside input fields */
	input[type=number] { -moz-appearance: textfield; }
	::-webkit-inner-spin-button { -webkit-appearance: none; }
	::-webkit-outer-spin-button { -webkit-appearance: none; }

	/* disables text input clear 'x' in IE 10+, slows down autosizeInput jquery script */
	input[type=text]::-ms-clear {
		display: none;
	}

	/* expand list search input on focus */
	input[type=text].list-search {
		width: 70px;
		min-width: 70px;
		margin-left: 15px;
		-webkit-transition: all .5s ease;
		-moz-transition: all .5s ease;
		transition: all .5s ease;
		}

	input[type=text].list-search:focus {
		width: 150px;
		}

	input.fileinput {
		padding: 1px;
		display: inline;
		}

	input[type=checkbox] {
		margin-top: 2px;
		}

	label {
		font-weight: normal;
		vertical-align: middle;
		}

	label input[type=checkbox],
	label input[type=radio] {
		vertical-align: -2px;
		margin: 0;
		padding: 0;
		}

	span.playback_progress_bar {
		background-color: #b90004;
		width: 17px;
		height: 4px;
		margin-bottom: 3px;
		display: block;
		-moz-border-radius: 0 0 6px 6px;
		-webkit-border-radius: 0 0 6px 6px;
		-khtml-border-radius: 0 0 6px 6px;
		border-radius: 0 0 6px 6px;
		-webkit-box-shadow: 0 0 3px 0px rgba(255,0,0,0.9);
		-moz-box-shadow: 0 0 3px 0px rgba(255,0,0,0.9);
		box-shadow: 0 0 3px 0px rgba(255,0,0,0.9);
		}

	table.list tr.list-row td.playback_progress_bar_background {
		padding: 0;
		border-bottom: none;
		background-image: -ms-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: -moz-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: -o-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, rgba(0,0,0,0.10)), color-stop(1, transparent));
		background-image: -webkit-linear-gradient(top, rgba(0,0,0,0.10) 0%, transparent 100%);
		background-image: linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, transparent 100%);
		overflow: hidden;
		}

	div.pwstrength_progress {
		display: none;
		}

	div.pwstrength_progress > div.progress {
		max-width: 200px;
		height: 6px;
		margin: 1px 0 0 1px;
		background: <?php echo ($_SESSION['theme']['input_background_color']['text'] != '') ? $_SESSION['theme']['input_background_color']['text'] : 'rgb(245, 245, 245)'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['input_border_radius']['text'], '3px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		}

	div.pwstrength_progress_password_reset > div.progress {
		margin: 0 auto 4px auto;
		width: 200px;
		max-width: 200px;
		background: <?php echo ($_SESSION['theme']['login_input_background_color']['text'] != '') ? $_SESSION['theme']['login_input_background_color']['text'] : (($_SESSION['theme']['input_background_color']['text'] != '') ? $_SESSION['theme']['input_background_color']['text'] : '#ffffff'); ?>;
		border-width: <?php echo ($_SESSION['theme']['login_input_border_size']['text'] != '') ? $_SESSION['theme']['login_input_border_size']['text'] : (($_SESSION['theme']['input_border_size']['text'] != '') ? $_SESSION['theme']['input_border_size']['text'] : '1px'); ?>;
		border-color: <?php echo ($_SESSION['theme']['login_input_border_color']['text'] != '') ? $_SESSION['theme']['login_input_border_color']['text'] : (($_SESSION['theme']['input_border_color']['text'] != '') ? $_SESSION['theme']['input_border_color']['text'] : '#c0c0c0'); ?>;
		}

/* TABLES *****************************************************************/

	table {
		border-collapse: separate;
		border-spacing: 0;
		}

	th {
		padding: 4px 7px 4px 0;
		padding: 4px 7px;
		text-align: left;
		color: <?php echo ($_SESSION['theme']['table_heading_text_color']['text'] != '') ? $_SESSION['theme']['table_heading_text_color']['text'] : '#3164ad'; ?>;
		font-size: <?php echo ($_SESSION['theme']['table_heading_text_size']['text'] != '') ? $_SESSION['theme']['table_heading_text_size']['text'] : '12px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['table_heading_text_font']['text'] != '') ? $_SESSION['theme']['table_heading_text_font']['text'] : 'arial'; ?>;
		background: <?php echo ($_SESSION['theme']['table_heading_background_color']['text'] != '') ? $_SESSION['theme']['table_heading_background_color']['text'] : 'none'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['table_heading_border_color']['text'] != '') ? $_SESSION['theme']['table_heading_border_color']['text'] : '#a4aebf'; ?>;
		}

	th a, th a:visited, th a:active {
		color: <?php echo ($_SESSION['theme']['table_heading_text_color']['text'] != '') ? $_SESSION['theme']['table_heading_text_color']['text'] : '#3164ad'; ?>;
		text-decoration: none;
		}

	th a:hover {
		color: <?php echo ($_SESSION['theme']['table_heading_text_color']['text'] != '') ? $_SESSION['theme']['table_heading_text_color']['text'] : '#3164ad'; ?>;
		text-decoration: none;
		}

	td {
		color: <?php echo ($_SESSION['theme']['body_text_color']['text'] != '') ? $_SESSION['theme']['body_text_color']['text'] : '#5f5f5f'; ?>;
		font-size: <?php echo ($_SESSION['theme']['body_text_size']['text'] != '') ? $_SESSION['theme']['body_text_size']['text'] : '12px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['body_text_font']['text'] != '') ? $_SESSION['theme']['body_text_font']['text'] : 'arial'; ?>;
		}

	table.tr_hover tr {
		cursor: default;
		}

	table.tr_hover tr:hover td,
	table.tr_hover tr:hover td a {
		color: <?php echo ($_SESSION['theme']['text_link_color_hover']['text'] != '') ? $_SESSION['theme']['text_link_color_hover']['text'] : '#5082ca'; ?>;
		cursor: pointer;
		}

	table.tr_hover tr.tr_link_void:hover td {
		color: <?php echo ($_SESSION['theme']['table_row_text_color']['text'] != '') ? $_SESSION['theme']['table_row_text_color']['text'] : '#000'; ?>;
		cursor: default;
		}

	table.tr_hover tr td.tr_link_void {
		cursor: default;
		}

	td.list_control_icons {
		width: 52px;
		padding: none;
		padding-left: 2px;
		text-align: right;
		vertical-align: top;
		white-space: nowrap;
		}

	td.list_control_icon {
		width: 26px;
		padding: none;
		padding-left: 2px;
		text-align: right;
		vertical-align: top;
		white-space: nowrap;
		}

	/* form: label/field format */
	.vncell { /* form_label */
		background: <?php echo ($_SESSION['theme']['form_table_label_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_label_border_radius']['text'], '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?php echo ($_SESSION['theme']['form_table_label_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['form_table_label_border_color']['text'] != '') ? $_SESSION['theme']['form_table_label_border_color']['text'] : '#ffffff'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_label_padding']['text'] != '') ? $_SESSION['theme']['form_table_label_padding']['text'] : '7px 8px'; ?>;
		text-align: right;
		color: <?php echo ($_SESSION['theme']['form_table_label_text_color']['text'] != '') ? $_SESSION['theme']['form_table_label_text_color']['text'] : '#000000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_label_text_font']['text'] != '') ? $_SESSION['theme']['form_table_label_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_label_text_size']['text'] != '') ? $_SESSION['theme']['form_table_label_text_size']['text'] : '9pt'; ?>;
		vertical-align: top;
		}

	.vncellreq { /* form_label_required */
		background: <?php echo ($_SESSION['theme']['form_table_label_required_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_background_color']['text'] : '#e5e9f0'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_label_border_radius']['text'], '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?php echo ($_SESSION['theme']['form_table_label_required_border_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_border_color']['text'] : '#cbcfd5'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['form_table_label_border_color']['text'] != '') ? $_SESSION['theme']['form_table_label_border_color']['text'] : '#ffffff'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_label_padding']['text'] != '') ? $_SESSION['theme']['form_table_label_padding']['text'] : '7px 8px'; ?>;
		text-align: right;
		color: <?php echo ($_SESSION['theme']['form_table_label_required_text_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_text_color']['text'] : '#000000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_label_text_font']['text'] != '') ? $_SESSION['theme']['form_table_label_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_label_text_size']['text'] != '') ? $_SESSION['theme']['form_table_label_text_size']['text'] : '9pt'; ?>;
		font-weight: <?php echo ($_SESSION['theme']['form_table_label_required_text_weight']['text'] != '') ? $_SESSION['theme']['form_table_label_required_text_weight']['text'] : 'bold'; ?>;
		vertical-align: top;
		}

	.vtable { /* form_field */
		background: <?php echo ($_SESSION['theme']['form_table_field_background_color']['text'] != '') ? $_SESSION['theme']['form_table_field_background_color']['text'] : '#ffffff'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_field_border_radius']['text'], '0'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['form_table_field_border_color']['text'] != '') ? $_SESSION['theme']['form_table_field_border_color']['text'] : '#e5e9f0'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_field_padding']['text'] != '') ? $_SESSION['theme']['form_table_field_padding']['text'] : '6px'; ?>;
		text-align: left;
		vertical-align: middle;
		color: <?php echo ($_SESSION['theme']['form_table_field_text_color']['text'] != '') ? $_SESSION['theme']['form_table_field_text_color']['text'] : '#666666'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_field_text_font']['text'] != '') ? $_SESSION['theme']['form_table_field_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_field_text_size']['text'] != '') ? $_SESSION['theme']['form_table_field_text_size']['text'] : '8pt'; ?>;
		}

	/* form: heading/row format */
	.vncellcol { /* form_heading */
		background: <?php echo ($_SESSION['theme']['form_table_label_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_label_border_radius']['text'], '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 3px solid <?php echo ($_SESSION['theme']['form_table_label_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_heading_padding']['text'] != '') ? $_SESSION['theme']['form_table_heading_padding']['text'] : '8px 8px 4px 8px'; ?>;
		text-align: left;
		color: <?php echo ($_SESSION['theme']['form_table_label_text_color']['text'] != '') ? $_SESSION['theme']['form_table_label_text_color']['text'] : '#000000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_label_text_font']['text'] != '') ? $_SESSION['theme']['form_table_label_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_label_text_size']['text'] != '') ? $_SESSION['theme']['form_table_label_text_size']['text'] : '9pt'; ?>;
		}

	.vncellcolreq { /* form_heading_required */
		background: <?php echo ($_SESSION['theme']['form_table_label_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_label_border_radius']['text'], '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 3px solid <?php echo ($_SESSION['theme']['form_table_label_required_border_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_border_color']['text'] : '#cbcfd5'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_heading_padding']['text'] != '') ? $_SESSION['theme']['form_table_heading_padding']['text'] : '8px 8px 4px 8px'; ?>;
		text-align: left;
		color: <?php echo ($_SESSION['theme']['form_table_label_required_text_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_text_color']['text'] : '#000000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_label_text_font']['text'] != '') ? $_SESSION['theme']['form_table_label_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_label_text_size']['text'] != '') ? $_SESSION['theme']['form_table_label_text_size']['text'] : '9pt'; ?>;
		font-weight: <?php echo ($_SESSION['theme']['form_table_label_required_text_weight']['text'] != '') ? $_SESSION['theme']['form_table_label_required_text_weight']['text'] : 'bold'; ?>;
		}

	.vtablerow { /* form_row */
		<?php
		// determine cell height by padding
		$total_vertical_padding = 6; //default px
		if ($_SESSION['theme']['form_table_row_padding']['text'] != '') {
			$form_table_row_padding = $_SESSION['theme']['form_table_row_padding']['text'];
			$form_table_row_padding = str_replace('px', '', $form_table_row_padding);
			$form_table_row_paddings = explode(' ', $form_table_row_padding);
			switch (sizeof($form_table_row_paddings)) {
				case 4: $total_vertical_padding = ($form_table_row_paddings[0] + $form_table_row_paddings[2]); break;
				default: $total_vertical_padding = ($form_table_row_paddings[0] * 2);
			}
		}
		?>
		height: <?php echo (30 + $total_vertical_padding); ?>px;
		background: <?php echo ($_SESSION['theme']['form_table_field_background_color']['text'] != '') ? $_SESSION['theme']['form_table_field_background_color']['text'] : '#ffffff'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_field_border_radius']['text'], '0'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['form_table_field_border_color']['text'] != '') ? $_SESSION['theme']['form_table_field_border_color']['text'] : '#e5e9f0'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_row_padding']['text'] != '') ? $_SESSION['theme']['form_table_row_padding']['text'] : ($total_vertical_padding/2).'px 0'; ?>;
		text-align: left;
		vertical-align: middle;
		color: <?php echo ($_SESSION['theme']['form_table_field_text_color']['text'] != '') ? $_SESSION['theme']['form_table_field_text_color']['text'] : '#666666'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_field_text_font']['text'] != '') ? $_SESSION['theme']['form_table_field_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_row_text_size']['text'] != '') ? $_SESSION['theme']['form_table_row_text_size']['text'] : '9pt'; ?>;
		}

	.vtablerow > label {
		margin-left: 0.6em;
		margin-right: 0.6em;
		margin-bottom: 2px;
		}

	.row_style0 {
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['table_row_border_color']['text'] != '') ? $_SESSION['theme']['table_row_border_color']['text'] : '#c5d1e5'; ?>;
		background: <?php echo ($_SESSION['theme']['table_row_background_color_dark']['text'] != '') ? $_SESSION['theme']['table_row_background_color_dark']['text'] : '#e5e9f0'; ?>;
		color: <?php echo ($_SESSION['theme']['table_row_text_color']['text'] != '') ? $_SESSION['theme']['table_row_text_color']['text'] : '#000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['table_row_text_font']['text'] != '') ? $_SESSION['theme']['table_row_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['table_row_text_size']['text'] != '') ? $_SESSION['theme']['table_row_text_size']['text'] : '12px'; ?>;
		text-align: left;
		padding: 4px 7px;
		}

	.row_style1 {
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['table_row_border_color']['text'] != '') ? $_SESSION['theme']['table_row_border_color']['text'] : '#c5d1e5'; ?>;
		background: <?php echo ($_SESSION['theme']['table_row_background_color_light']['text'] != '') ? $_SESSION['theme']['table_row_background_color_light']['text'] : '#fff'; ?>;
		color: <?php echo ($_SESSION['theme']['table_row_text_color']['text'] != '') ? $_SESSION['theme']['table_row_text_color']['text'] : '#000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['table_row_text_font']['text'] != '') ? $_SESSION['theme']['table_row_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['table_row_text_size']['text'] != '') ? $_SESSION['theme']['table_row_text_size']['text'] : '12px'; ?>;
		text-align: left;
		padding: 4px 7px;
		}

	.row_style_slim {
		padding-top: 0;
		padding-bottom: 0;
		white-space: nowrap;
		}

	.row_stylebg {
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['table_row_border_color']['text'] != '') ? $_SESSION['theme']['table_row_border_color']['text'] : '#c5d1e5'; ?>;
		background: <?php echo ($_SESSION['theme']['table_row_background_color_medium']['text'] != '') ? $_SESSION['theme']['table_row_background_color_medium']['text'] : '#f0f2f6'; ?>;
		color: <?php echo ($_SESSION['theme']['table_row_text_color']['text'] != '') ? $_SESSION['theme']['table_row_text_color']['text'] : '#000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['table_row_text_font']['text'] != '') ? $_SESSION['theme']['table_row_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['table_row_text_size']['text'] != '') ? $_SESSION['theme']['table_row_text_size']['text'] : '12px'; ?>;
		text-align: left;
		padding: 4px 7px;
		}

/* RESPONSE MESSAGE STACK *******************************************************/

	#message_container {
		z-index: 99998;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		padding: 0;
		}

	.message_text {
		z-index: 99999;
		margin: 0 auto;
		padding: 15px;
		text-align: center;
		font-family: arial, san-serif;
		font-size: 10pt;
		display: block;
		color: <?php echo $_SESSION['theme']['message_default_color']['text']; ?>;
		background: <?php echo $_SESSION['theme']['message_default_background_color']['text']; ?>;
		box-shadow: inset 0px 7px 8px -10px <?php echo $_SESSION['theme']['message_default_color']['text']; ?>;
		border-bottom: solid 1px <?php echo $_SESSION['theme']['message_default_color']['text']; ?>;
		opacity: 0;
		}

	.message_mood_positive {
		color: <?php echo $_SESSION['theme']['message_positive_color']['text']; ?>;
		background: <?php echo $_SESSION['theme']['message_positive_background_color']['text']; ?>;
		box-shadow: inset 0px 7px 8px -10px <?php echo $_SESSION['theme']['message_positive_color']['text']; ?>;
		border-bottom: solid 1px <?php echo $_SESSION['theme']['message_positive_color']['text']; ?>;
		}

	.message_mood_negative {
		color: <?php echo $_SESSION['theme']['message_negative_color']['text']; ?>;
		background: <?php echo $_SESSION['theme']['message_negative_background_color']['text']; ?>;
		box-shadow: inset 0px 7px 8px -10px <?php echo $_SESSION['theme']['message_negative_color']['text']; ?>;
		border-bottom: solid 1px <?php echo $_SESSION['theme']['message_negative_color']['text']; ?>;
		}

	.message_mood_alert {
		color: <?php echo $_SESSION['theme']['message_alert_color']['text']; ?>;
		background: <?php echo $_SESSION['theme']['message_alert_background_color']['text']; ?>;
		box-shadow: inset 0px 7px 8px -10px <?php echo $_SESSION['theme']['message_alert_color']['text']; ?>;
		border-bottom: solid 1px <?php echo $_SESSION['theme']['message_alert_color']['text']; ?>;
		}

/* OPERATOR PANEL ****************************************************************/

	div.op_ext {
		float: left;
		width: 235px;
		margin: 0px 8px 8px 0px;
		padding: 0px;
		border-style: solid;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: 0 0 3px #e5e9f0;
		-moz-box-shadow: 0 0 3px #e5e9f0;
		box-shadow: 0 0 3px #e5e9f0;
		border-width: 1px 3px;
		border-color: #b9c5d8 #c5d1e5;
		background-color: #e5eaf5;
		cursor: default;
		}

	div.off_ext {
		position: relative;
		float: left;
		width: 235px;
		margin: 0px 8px 8px 0px;
		padding: 0px;
		border-style: solid;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: 0 0 3px #e5e9f0;
		-moz-box-shadow: 0 0 3px #e5e9f0;
		box-shadow: 0 0 3px #e5e9f0;
		border-width: 1px 3px;
		border-color: #b9c5d8 #c5d1e5;
		background-color: #e5eaf5;
		cursor: not-allowed;
		}
		
		div.off_ext:after {
			position: absolute;
			content: "";
			z-index: 10;
			-moz-border-radius: 5px;
			-webkit-border-radius: 5px;
			border-radius: 5px;
			display: block;
			height: 100%;
			top: 0;
			left: 0;
			right: 0;
			background: rgba(255, 255, 255, 0.5);
		}

	div.op_state_active {
		background-color: #baf4bb;
		border-width: 1px 3px;
		border-color: #77d779;
		}

	div.op_state_ringing {
		background-color: #a8dbf0;
		border-width: 1px 3px;
		border-color: #41b9eb;
		}

	table.op_ext, table.off_ext {
		width: 100%;
		height: 70px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: #e5eaf5;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		}

	td.op_ext_icon {
		vertical-align: middle;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		}

	img.op_ext_icon {
		cursor: move;
		width: 39px;
		height: 42px;
		border: none;
		}

	td.op_ext_info {
		text-align: left;
		vertical-align: top;
		font-family: arial;
		font-size: 10px;
		overflow: auto;
		width: 100%;
		padding: 3px 5px 3px 7px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: #f0f2f6;
		}

	td.op_state_ringing {
		background-color: #d1f1ff;
		}

	td.op_state_active {
		background-color: #e1ffe2;
		}

	table.op_state_ringing {
		background-color: #a8dbf0;
		}

	table.op_state_active {
		background-color: #baf4bb;
		}

	.op_user_info {
		font-family: arial;
		font-size: 10px;
		display: inline-block;
		}

	.op_user_info strong {
		color: #3164AD;
		}

	.op_caller_info {
		display: block;
		margin-top: 4px;
		font-family: arial;
		font-size: 10px;
		}

	.op_call_info {
		display: inline-block;
		padding: 0px;
		font-family: arial;
		font-size: 10px;
		}

	#op_btn_status_available {
		background-image: -moz-linear-gradient(top, #8ec989 0%, #2d9c38 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #8ec989), color-stop(1, #2d9c38));
		background-color: #2d9c38;
		border: 1px solid #006200;
		}

	#op_btn_status_available_on_demand {
		background-image: -moz-linear-gradient(top, #abd0aa 0%, #629d62 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #abd0aa), color-stop(1, #629d62));
		background-color: #629d62;
		border: 1px solid #619c61;
		}

	#op_btn_status_on_break {
		background-image: -moz-linear-gradient(top, #ddc38b 0%, #be8e2c 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #ddc38b), color-stop(1, #be8e2c));
		background-color: #be8e2c;
		border: 1px solid #7d1b00;
		}

	#op_btn_status_do_not_disturb {
		background-image: -moz-linear-gradient(top, #cc8984 0%, #960d10 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #cc8984), color-stop(1, #960d10));
		background-color: #960d10;
		border: 1px solid #5b0000;
		}

	#op_btn_status_logged_out {
		background-image: -moz-linear-gradient(top, #cacac9 0%, #8d8d8b 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #cacac9), color-stop(1, #8d8d8b));
		background-color: #8d8d8b;
		border: 1px solid #5d5f5a;
		}

/* DASHBOARD **********************************************************************/

	/* login message */
	div.login_message {
		border: 1px solid #bae0ba;
		background-color: #eeffee;
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
		padding: 20px;
		margin-bottom: 15px;
		}

	/* hud boxes */
	div.hud_box {
		height: auto;
		vertical-align: top;
		text-align: center;
		<?php
		$color_edge = ($_SESSION['theme']['dashboard_detail_background_color_edge']['text'] != '') ? $_SESSION['theme']['dashboard_detail_background_color_edge']['text'] : '#edf1f7';
		$color_center = ($_SESSION['theme']['dashboard_detail_background_color_center']['text'] != '') ? $_SESSION['theme']['dashboard_detail_background_color_center']['text'] : '#f9fbfe';
		?>
		background: <?php echo $color_center; ?>;
		background-image: -ms-linear-gradient(left, <?php echo $color_edge; ?> 0%, <?php echo $color_center; ?> 30%, <?php echo $color_center; ?> 70%, <?php echo $color_edge; ?> 100%);
		background-image: -moz-linear-gradient(left, <?php echo $color_edge; ?> 0%, <?php echo $color_center; ?> 30%, <?php echo $color_center; ?> 70%, <?php echo $color_edge; ?> 100%);
		background-image: -o-linear-gradient(left, <?php echo $color_edge; ?> 0%, <?php echo $color_center; ?> 30%, <?php echo $color_center; ?> 70%, <?php echo $color_edge; ?> 100%);
		background-image: -webkit-gradient(linear, left, right, color-stop(0, <?php echo $color_edge; ?>), color-stop(0.30, <?php echo $color_center; ?>), color-stop(0.70, <?php echo $color_center; ?>), color-stop(1, <?php echo $color_edge; ?>));
		background-image: -webkit-linear-gradient(left, <?php echo $color_edge; ?> 0%, <?php echo $color_center; ?> 30%, <?php echo $color_center; ?> 70%, <?php echo $color_edge; ?> 100%);
		background-image: linear-gradient(to right, <?php echo $color_edge; ?> 0%, <?php echo $color_center; ?> 30%, <?php echo $color_center; ?> 70%, <?php echo $color_edge; ?> 100%);
		<?php unset($color_edge, $color_center); ?>
		<?php $br = format_border_radius($_SESSION['theme']['dashboard_border_radius']['text'], '5px'); ?>
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border: 1px solid <?php echo ($_SESSION['theme']['dashboard_border_color']['text'] != '') ? $_SESSION['theme']['dashboard_border_color']['text'] : '#dbe0ea'; ?>;
		overflow: hidden;
		margin: -1px;
		}

	div.hud_box:hover {
		border: 1px solid <?php echo ($_SESSION['theme']['dashboard_border_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_border_color_hover']['text'] : '#cbd3e1'; ?>;
		}

	span.hud_title {
		display: block;
		width: 100%;
		font-family: <?php echo ($_SESSION['theme']['dashboard_heading_text_font']['text'] != '') ? $_SESSION['theme']['dashboard_heading_text_font']['text'] : 'Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif'; ?>;
		text-shadow: 0px 1px 2px <?php echo ($_SESSION['theme']['dashboard_heading_text_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_heading_text_shadow_color']['text'] : '#000'; ?>;
		letter-spacing: -0.02em;
		color: <?php echo ($_SESSION['theme']['dashboard_heading_text_color']['text'] != '') ? $_SESSION['theme']['dashboard_heading_text_color']['text'] : '#fff'; ?>;
		font-size: <?php echo ($_SESSION['theme']['dashboard_heading_text_size']['text'] != '') ? $_SESSION['theme']['dashboard_heading_text_size']['text'] : '12pt'; ?>;
		<?php
		//calculate line height based on font size
		if ($_SESSION['theme']['dashboard_heading_text_size']['text'] != '') {
			$font_size = strtolower($_SESSION['theme']['dashboard_heading_text_size']['text']);
			$tmp = str_replace(' ', '', $font_size);
			$tmp = str_replace('pt', '', $tmp);
			$tmp = str_replace('px', '', $tmp);
			$tmp = str_replace('em', '', $tmp);
			$tmp = str_replace('%', '', $tmp);
			$font_size_number = $tmp;
			$line_height_number = (int) floor($font_size_number * 2.5);
		}
		?>
		line-height: <?php echo ($line_height_number > 0) ? str_replace($font_size_number, $line_height_number, $font_size) : '26.25pt'; ?>;
		text-align: center;
		background: <?php echo ($_SESSION['theme']['dashboard_heading_background_color']['text'] != '') ? $_SESSION['theme']['dashboard_heading_background_color']['text'] : '#8e96a5'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['dashboard_heading_background_color']['text'] != '') ? color_adjust($_SESSION['theme']['dashboard_heading_background_color']['text'], 0.2) : '#737983'; ?>;
		overflow: hidden;
		}

	span.hud_title:hover {
		color: <?php echo ($_SESSION['theme']['dashboard_heading_text_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_heading_text_color_hover']['text'] : '#fff'; ?>;
		text-shadow: 0px 1px 2px <?php echo ($_SESSION['theme']['dashboard_heading_text_shadow_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_heading_text_shadow_color_hover']['text'] : '#000'; ?>;
		background: <?php echo ($_SESSION['theme']['dashboard_heading_background_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_heading_background_color_hover']['text'] : (($_SESSION['theme']['dashboard_heading_background_color']['text'] != '') ? color_adjust($_SESSION['theme']['dashboard_heading_background_color']['text'], 0.03) : '#969dab'); ?>;
		cursor: pointer;
		}

	span.hud_stat {
		display: block;
		clear: both;
		text-align: center;
		text-shadow: 0px 2px 2px <?php echo ($_SESSION['theme']['dashboard_number_text_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_shadow_color']['text'] : '#737983'; ?>;
		width: 100%;
		color: <?php echo ($_SESSION['theme']['dashboard_number_text_color']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_color']['text'] : '#fff'; ?>;
		font-family: <?php echo ($_SESSION['theme']['dashboard_number_text_font']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_font']['text'] : 'Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif'; ?>;
		font-size: <?php echo ($_SESSION['theme']['dashboard_number_text_size']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_size']['text'] : '60pt'; ?>;
		<?php
		//calculate line height based on font size
		if ($_SESSION['theme']['dashboard_number_text_size']['text'] != '') {
			$font_size = strtolower($_SESSION['theme']['dashboard_number_text_size']['text']);
			$tmp = str_replace(' ', '', $font_size);
			$tmp = str_replace('pt', '', $tmp);
			$tmp = str_replace('px', '', $tmp);
			$tmp = str_replace('em', '', $tmp);
			$tmp = str_replace('%', '', $tmp);
			$font_size_number = $tmp;
			$line_height_number = (int) floor($font_size_number * 1.28);
		}
		?>
		line-height: <?php echo ($line_height_number > 0) ? str_replace($font_size_number, $line_height_number, $font_size) : '77pt'; ?>;
		font-weight: normal;
		background: <?php echo ($_SESSION['theme']['dashboard_number_background_color']['text'] != '') ? $_SESSION['theme']['dashboard_number_background_color']['text'] : '#a4aebf'; ?>;
		border-top: 1px solid <?php echo ($_SESSION['theme']['dashboard_number_background_color']['text'] != '') ? color_adjust($_SESSION['theme']['dashboard_number_background_color']['text'], 0.2) : '#c5d1e5'; ?>;
		overflow: hidden;
		<?php
		//calculate font padding
		if ($_SESSION['theme']['dashboard_heading_text_size']['text'] != '') {
			$font_size = strtolower($_SESSION['theme']['dashboard_heading_text_size']['text']);
			$tmp = str_replace(' ', '', $font_size);
			$tmp = str_replace('pt', '', $tmp);
			$tmp = str_replace('px', '', $tmp);
			$tmp = str_replace('em', '', $tmp);
			$tmp = str_replace('%', '', $tmp);
			$font_size_number = $tmp;
			$padding_top_bottom = (int) floor((100-$tmp) * 0.25);
		}
		?>
		padding-top: <?php echo $padding_top_bottom.'px' ?>;
		padding-bottom: <?php echo $padding_top_bottom.'px' ?>;
		}

	span.hud_stat:hover {
		color: <?php echo ($_SESSION['theme']['dashboard_number_text_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_color_hover']['text'] : '#fff'; ?>;
		text-shadow: 0px 2px 2px <?php echo ($_SESSION['theme']['dashboard_number_text_shadow_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_shadow_color_hover']['text'] : '#737983'; ?>;
		background: <?php echo ($_SESSION['theme']['dashboard_number_background_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_number_background_color_hover']['text'] : (($_SESSION['theme']['dashboard_number_background_color']['text'] != '') ? color_adjust($_SESSION['theme']['dashboard_number_background_color']['text'], 0.03) : '#aeb7c5'); ?>;
		cursor: pointer;
		}

	span.hud_stat_title {
		display: block;
		clear: both;
		width: 100%;
		height: 30px;
		cursor: default;
		text-align: center;
		text-shadow: 0px 1px 1px <?php echo ($_SESSION['theme']['dashboard_number_title_text_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_number_title_text_shadow_color']['text'] : '#737983'; ?>;
		color: <?php echo ($_SESSION['theme']['dashboard_number_title_text_color']['text'] != '') ? $_SESSION['theme']['dashboard_number_title_text_color']['text'] : '#fff'; ?>;
		font-size: <?php echo ($_SESSION['theme']['dashboard_number_title_text_size']['text'] != '') ? $_SESSION['theme']['dashboard_number_title_text_size']['text'] : '14px'; ?>;
		padding-top: 4px;
		white-space: nowrap;
		letter-spacing: -0.02em;
		font-weight: normal;
		font-family: <?php echo ($_SESSION['theme']['dashboard_number_title_text_font']['text'] != '') ? $_SESSION['theme']['dashboard_number_title_text_font']['text'] : 'Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif'; ?>;
		background: <?php echo ($_SESSION['theme']['dashboard_number_background_color']['text'] != '') ? $_SESSION['theme']['dashboard_number_background_color']['text'] : '#a4aebf'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['dashboard_number_background_color']['text'] != '') ? color_adjust($_SESSION['theme']['dashboard_number_background_color']['text'], -0.2) : '#909aa8'; ?>;
		margin: 0;
		overflow: hidden;
		}

	span.hud_stat:hover + span.hud_stat_title {
		color: <?php echo ($_SESSION['theme']['dashboard_number_text_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_color_hover']['text'] : '#fff'; ?>;
		text-shadow: 0px 1px 1px <?php echo ($_SESSION['theme']['dashboard_number_text_shadow_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_number_text_shadow_color_hover']['text'] : '#737983'; ?>;
		background: <?php echo ($_SESSION['theme']['dashboard_number_background_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_number_background_color_hover']['text'] : color_adjust(($_SESSION['theme']['dashboard_number_background_color']['text'] != '') ? $_SESSION['theme']['dashboard_number_background_color']['text'] : '#a4aebf', 0.03); ?>;
		}

	div.hud_details {
		-moz-box-shadow: inset 0 7px 7px -7px <?php echo ($_SESSION['theme']['dashboard_detail_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_detail_shadow_color']['text'] : '#737983'; ?>, inset 0 -8px 12px -10px <?php echo ($_SESSION['theme']['dashboard_detail_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_detail_shadow_color']['text'] : '#737983'; ?>;
		-webkit-box-shadow: inset 0 7px 7px -7px <?php echo ($_SESSION['theme']['dashboard_detail_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_detail_shadow_color']['text'] : '#737983'; ?>, inset 0 -8px 12px -10px <?php echo ($_SESSION['theme']['dashboard_detail_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_detail_shadow_color']['text'] : '#737983'; ?>;
		box-shadow: inset 0 7px 7px -7px <?php echo ($_SESSION['theme']['dashboard_detail_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_detail_shadow_color']['text'] : '#737983'; ?>, inset 0 -8px 12px -10px <?php echo ($_SESSION['theme']['dashboard_detail_shadow_color']['text'] != '') ? $_SESSION['theme']['dashboard_detail_shadow_color']['text'] : '#737983'; ?>;
		padding-top: 3px;
		padding-bottom: 15px;
		}

	@media(min-width: 0px) and (max-width: 1199px) {
		div.hud_details {
			display: none;
			height: auto;
			}
		}

	@media(min-width: 1200px) {
		div.hud_details {
			height: 350px;
			display: block;
			}
		}

	th.hud_heading {
		text-align: left;
		font-size: <?php echo ($_SESSION['theme']['dashboard_detail_heading_text_size']['text'] != '') ? $_SESSION['theme']['dashboard_detail_heading_text_size']['text'] : '11px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['table_heading_text_font']['text'] != '') ? $_SESSION['theme']['table_heading_text_font']['text'] : 'arial'; ?>
		color: <?php echo ($_SESSION['theme']['table_heading_text_color']['text'] != '') ? $_SESSION['theme']['table_heading_text_color']['text'] : '#3164ad'; ?>;
		}

	td.hud_text {
		font-size: <?php echo ($_SESSION['theme']['dashboard_detail_row_text_size']['text'] != '') ? $_SESSION['theme']['dashboard_detail_row_text_size']['text'] : '11px'; ?>;
		color: <?php echo ($_SESSION['theme']['table_row_text_color']['text'] != '') ? $_SESSION['theme']['table_row_text_color']['text'] : '#000'; ?>;
		text-align: left;
		vertical-align: middle;
		}

	span.hud_expander {
		display: block;
		clear: both;
		background: <?php echo ($_SESSION['theme']['dashboard_footer_background_color']['text'] != '') ? $_SESSION['theme']['dashboard_footer_background_color']['text'] : '#e5e9f0'; ?>;
		padding: 4px 0;
		text-align: center;
		width: 100%;
		height: 25px;
		font-size: 13px;
		line-height: 5px;
		color: <?php echo ($_SESSION['theme']['dashboard_footer_dots_color']['text'] != '') ? $_SESSION['theme']['dashboard_footer_dots_color']['text'] : '#a4aebf'; ?>;
		border-top: 1px solid <?php echo ($_SESSION['theme']['dashboard_footer_background_color']['text'] != '') ? color_adjust($_SESSION['theme']['dashboard_footer_background_color']['text'], 0.2) : '#fff'; ?>;
		}

	span.hud_expander:hover {
		color: <?php echo ($_SESSION['theme']['dashboard_footer_dots_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_footer_dots_color_hover']['text'] : (($_SESSION['theme']['dashboard_footer_dots_color']['text'] != '') ? $_SESSION['theme']['dashboard_footer_dots_color']['text'] : '#a4aebf'); ?>;
		background: <?php echo ($_SESSION['theme']['dashboard_footer_background_color_hover']['text'] != '') ? $_SESSION['theme']['dashboard_footer_background_color_hover']['text'] : (($_SESSION['theme']['dashboard_footer_background_color']['text'] != '') ? color_adjust($_SESSION['theme']['dashboard_footer_background_color']['text'], 0.02) : '#ebeef3'); ?>;
		cursor: pointer;
		}

/* PLUGINS ********************************************************************/

	/* bootstrap colorpicker  */
	.colorpicker-2x .colorpicker-saturation {
		width: 200px;
		height: 200px;
		}

	.colorpicker-2x .colorpicker-hue,
	.colorpicker-2x .colorpicker-alpha {
		width: 30px;
		height: 200px;
		}

	.colorpicker-2x .colorpicker-color,
	.colorpicker-2x .colorpicker-color div{
		height: 30px;
		}

	/* jquery ui autocomplete styles */
	.ui-widget {
		margin: 0px;
		padding: 0px;
		}

	.ui-autocomplete {
		cursor: default;
		position: absolute;
		max-height: 200px;
		overflow-y: auto;
		overflow-x: hidden;
		white-space: nowrap;
		width: auto;
		border: 1px solid #c0c0c0;
		}

	.ui-menu, .ui-menu .ui-menu-item {
		width: 350px;
		}

	.ui-menu .ui-menu-item a {
		text-decoration: none;
		cursor: pointer;
		border-color: #fff;
		background-image: none;
		background-color: #fff;
		white-space: nowrap;
		font-family: arial;
		font-size: 12px;
		color: #444;
		}

	.ui-menu .ui-menu-item a:hover {
		color: #5082ca;
		border: 1px solid white;
		background-image: none;
		background-color: #fff;
		}

/* CSS GRID ********************************************************************/

	div.grid {
		width: 100%;
		display: grid;
		grid-gap: 0;
		}

	div.grid > div.box.contact-details {
		padding: 15px;
		border: 1px solid <?php echo ($_SESSION['theme']['table_row_border_color']['text'] != '') ? $_SESSION['theme']['table_row_border_color']['text'] : '#c5d1e5'; ?>;
		border-radius: 5px;
		background: <?php echo ($_SESSION['theme']['table_row_background_color_dark']['text'] != '') ? $_SESSION['theme']['table_row_background_color_dark']['text'] : '#e5e9f0'; ?>;
		}

	div.grid.contact-details {
		grid-template-columns: 50px auto;
		}

	div.grid > div.box {
		padding: 0;
		padding-bottom: 5px;
		}

	div.grid > div.box.contact-details-label {
		font-size: 87%;
		letter-spacing: -0.03em;
		vertical-align: middle;
		white-space: nowrap;
		}

	div.form_grid {
		width: 100%;
		display: grid;
		grid-gap: 0;
		grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
		}

	div.form_set {
		width: 100%;
		display: grid;
		grid_gap: 0;
		grid-template-columns: 150px minmax(200px, 1fr);
		}

	div.form_set > .label {
		background: <?php echo ($_SESSION['theme']['form_table_label_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_label_border_radius']['text'], '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?php echo ($_SESSION['theme']['form_table_label_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_background_color']['text'] : '#e5e9f0'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['form_table_label_border_color']['text'] != '') ? $_SESSION['theme']['form_table_label_border_color']['text'] : '#ffffff'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_label_padding']['text'] != '') ? $_SESSION['theme']['form_table_label_padding']['text'] : '7px 8px'; ?>;
		text-align: right;
		color: <?php echo ($_SESSION['theme']['form_table_label_text_color']['text'] != '') ? $_SESSION['theme']['form_table_label_text_color']['text'] : '#000000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_label_text_font']['text'] != '') ? $_SESSION['theme']['form_table_label_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_label_text_size']['text'] != '') ? $_SESSION['theme']['form_table_label_text_size']['text'] : '9pt'; ?>;
		white-space: nowrap;
		vertical-align: top;
		}

	div.form_set > .label.required {
		background: <?php echo ($_SESSION['theme']['form_table_label_required_background_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_background_color']['text'] : '#e5e9f0'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_label_border_radius']['text'], '4px'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-right: 3px solid <?php echo ($_SESSION['theme']['form_table_label_required_border_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_border_color']['text'] : '#cbcfd5'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['form_table_label_border_color']['text'] != '') ? $_SESSION['theme']['form_table_label_border_color']['text'] : '#ffffff'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_label_padding']['text'] != '') ? $_SESSION['theme']['form_table_label_padding']['text'] : '7px 8px'; ?>;
		text-align: right;
		color: <?php echo ($_SESSION['theme']['form_table_label_required_text_color']['text'] != '') ? $_SESSION['theme']['form_table_label_required_text_color']['text'] : '#000000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_label_text_font']['text'] != '') ? $_SESSION['theme']['form_table_label_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_label_text_size']['text'] != '') ? $_SESSION['theme']['form_table_label_text_size']['text'] : '9pt'; ?>;
		font-weight: <?php echo ($_SESSION['theme']['form_table_label_required_text_weight']['text'] != '') ? $_SESSION['theme']['form_table_label_required_text_weight']['text'] : 'bold'; ?>;
		white-space: nowrap;
		vertical-align: top;
		}

	div.form_set > .field {
		background: <?php echo ($_SESSION['theme']['form_table_field_background_color']['text'] != '') ? $_SESSION['theme']['form_table_field_background_color']['text'] : '#ffffff'; ?>;
		<?php $br = format_border_radius($_SESSION['theme']['form_table_field_border_radius']['text'], '0'); ?>
		-moz-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-webkit-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		-khtml-border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		border-radius: <?php echo $br['tl']['n'].$br['tl']['u']; ?> <?php echo $br['tr']['n'].$br['tr']['u']; ?> <?php echo $br['br']['n'].$br['br']['u']; ?> <?php echo $br['bl']['n'].$br['bl']['u']; ?>;
		<?php unset($br); ?>
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['form_table_field_border_color']['text'] != '') ? $_SESSION['theme']['form_table_field_border_color']['text'] : '#e5e9f0'; ?>;
		padding: <?php echo ($_SESSION['theme']['form_table_field_padding']['text'] != '') ? $_SESSION['theme']['form_table_field_padding']['text'] : '6px'; ?>;
		text-align: left;
		vertical-align: middle;
		color: <?php echo ($_SESSION['theme']['form_table_field_text_color']['text'] != '') ? $_SESSION['theme']['form_table_field_text_color']['text'] : '#666666'; ?>;
		font-family: <?php echo ($_SESSION['theme']['form_table_field_text_font']['text'] != '') ? $_SESSION['theme']['form_table_field_text_font']['text'] : 'Arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['form_table_field_text_size']['text'] != '') ? $_SESSION['theme']['form_table_field_text_size']['text'] : '8pt'; ?>;
		position: relative;
		}

	div.form_set > .field.no-wrap {
		white-space: nowrap;
		}

/* LIST ACTION BAR *************************************************************/

	div.action_bar {
		position: -webkit-sticky;
		position: sticky;
		z-index: 5;
		<?php
		switch ($_SESSION['theme']['menu_style']['text']) {
			case 'side':
				$action_bar_top = '0';
				break;
			case 'inline':
			case 'static':
				$action_bar_top = '-1px';
				break;
			case 'fixed':
			default:
				$action_bar_top = '49px';
		}
		?>
		top: <?php echo $action_bar_top; ?>;
		text-align: right;
		border-top: <?php echo ($_SESSION['theme']['action_bar_border_top']['text'] != '') ? $_SESSION['theme']['action_bar_border_top']['text'] : '0'; ?>;
		border-right: <?php echo ($_SESSION['theme']['action_bar_border_right']['text'] != '') ? $_SESSION['theme']['action_bar_border_right']['text'] : '0'; ?>;
		border-bottom: <?php echo ($_SESSION['theme']['action_bar_border_bottom']['text'] != '') ? $_SESSION['theme']['action_bar_border_bottom']['text'] : '0'; ?>;
		border-left: <?php echo ($_SESSION['theme']['action_bar_border_left']['text'] != '') ? $_SESSION['theme']['action_bar_border_left']['text'] : '0'; ?>;
		border-radius: <?php echo ($_SESSION['theme']['action_bar_border_radius']['text'] != '') ? $_SESSION['theme']['action_bar_border_radius']['text'] : '0'; ?>;
		background: <?php echo ($_SESSION['theme']['action_bar_background']['text'] != '') ? $_SESSION['theme']['action_bar_background']['text'] : 'none'; ?>;
		box-shadow: <?php echo ($_SESSION['theme']['action_bar_shadow']['text'] != '') ? $_SESSION['theme']['action_bar_shadow']['text'] : 'none'; ?>;
		padding: 10px;
		margin: -10px -10px 10px -10px;
		-webkit-transition: all .2s ease;
		-moz-transition: all .2s ease;
		transition: all .2s ease;
		}

	div.action_bar.scroll {
		border-top: <?php echo ($_SESSION['theme']['action_bar_border_top_scroll']['text'] != '') ? $_SESSION['theme']['action_bar_border_top_scroll']['text'] : 'initial'; ?>;
		border-right: <?php echo ($_SESSION['theme']['action_bar_border_right_scroll']['text'] != '') ? $_SESSION['theme']['action_bar_border_right_scroll']['text'] : 'initial'; ?>;
		border-bottom: <?php echo ($_SESSION['theme']['action_bar_border_bottom_scroll']['text'] != '') ? $_SESSION['theme']['action_bar_border_bottom_scroll']['text'] : 'initial'; ?>;
		border-left: <?php echo ($_SESSION['theme']['action_bar_border_left_scroll']['text'] != '') ? $_SESSION['theme']['action_bar_border_left_scroll']['text'] : 'initial'; ?>;
		border-radius: <?php echo ($_SESSION['theme']['action_bar_border_radius_scroll']['text'] != '') ? $_SESSION['theme']['action_bar_border_radius_scroll']['text'] : 'initial'; ?>;
		background: <?php echo ($_SESSION['theme']['action_bar_background_scroll']['text'] != '') ? $_SESSION['theme']['action_bar_background_scroll']['text'] : 'rgba(255,255,255,0.9)'; ?>;
		box-shadow: <?php echo ($_SESSION['theme']['action_bar_shadow_scroll']['text'] != '') ? $_SESSION['theme']['action_bar_shadow_scroll']['text'] : '0 3px 3px 0 rgba(0,0,0,0.2)'; ?>;
		}

	div.action_bar.sub {
		position: static;
		}

	div.action_bar > div.heading {
		float: left;
		}

	div.action_bar > div.actions {
		float: right;
		white-space: nowrap;
		}

	div.action_bar > div.actions > div.unsaved {
		display: inline-block;
		margin-right: 30px;
		color: #b00;
		}

	/* used primarily in contacts */
	div.action_bar.shrink {
		margin-bottom: 0;
		padding-bottom: 0;
		}

	div.action_bar.shrink > div.heading > b {
		font-size: 100%;
		}

/* LIST ************************************************************************/

	.list {
		width: 100%;
		empty-cells: show;
		}

	.list tr {
		cursor: default;
		}

	.list tr:hover td:not(.no-link),
	.list tr:hover td:not(.no-link) a {
		color: <?php echo ($_SESSION['theme']['text_link_color_hover']['text'] != '') ? $_SESSION['theme']['text_link_color_hover']['text'] : '#5082ca'; ?>;
		cursor: pointer;
		}

	.list-header > th {
		padding: <?php echo ($_SESSION['theme']['table_heading_padding']['text'] != '') ? $_SESSION['theme']['table_heading_padding']['text'] : '4px 7px'; ?>;
		text-align: left;
		color: <?php echo ($_SESSION['theme']['table_heading_text_color']['text'] != '') ? $_SESSION['theme']['table_heading_text_color']['text'] : '#3164ad'; ?>;
		font-size: <?php echo ($_SESSION['theme']['table_heading_text_size']['text'] != '') ? $_SESSION['theme']['table_heading_text_size']['text'] : '12px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['table_heading_text_font']['text'] != '') ? $_SESSION['theme']['table_heading_text_font']['text'] : 'arial'; ?>;
		background: <?php echo ($_SESSION['theme']['table_heading_background_color']['text'] != '') ? $_SESSION['theme']['table_heading_background_color']['text'] : 'none'; ?>;
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['table_heading_border_color']['text'] != '') ? $_SESSION['theme']['table_heading_border_color']['text'] : '#a4aebf'; ?>;
		}

	.list-header > th.shrink {
		width: 1%;
		}

	.list-header > th > a.default-color {
		color: <?php echo ($_SESSION['theme']['text_link_color']['text'] != '') ? $_SESSION['theme']['text_link_color']['text'] : '#004083'; ?>;
		}

	.list-header > th > a.default-color:hover {
		color: <?php echo ($_SESSION['theme']['text_link_color_hover']['text'] != '') ? $_SESSION['theme']['text_link_color_hover']['text'] : '#5082ca'; ?>;
		}

	.list-row:nth-child(odd) > :not(.action-button) {
		background: <?php echo ($_SESSION['theme']['table_row_background_color_light']['text'] != '') ? $_SESSION['theme']['table_row_background_color_light']['text'] : '#ffffff'; ?>;
		}

	.list-row:nth-child(even) > :not(.action-button) {
		background: <?php echo ($_SESSION['theme']['table_row_background_color_dark']['text'] != '') ? $_SESSION['theme']['table_row_background_color_dark']['text'] : '#e5e9f0'; ?>;
		}

	.list-row > td:not(.action-button) {
		border-bottom: 1px solid <?php echo ($_SESSION['theme']['table_row_border_color']['text'] != '') ? $_SESSION['theme']['table_row_border_color']['text'] : '#c5d1e5'; ?>;
		color: <?php echo ($_SESSION['theme']['table_row_text_color']['text'] != '') ? $_SESSION['theme']['table_row_text_color']['text'] : '#000'; ?>;
		font-family: <?php echo ($_SESSION['theme']['table_row_text_font']['text'] != '') ? $_SESSION['theme']['table_row_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['table_row_text_size']['text'] != '') ? $_SESSION['theme']['table_row_text_size']['text'] : '12px'; ?>;
		text-align: left;
		vertical-align: middle;
		}

	.list-row > :not(.checkbox) {
		padding: <?php echo ($_SESSION['theme']['table_row_padding']['text'] != '') ? $_SESSION['theme']['table_row_padding']['text'] : '4px 7px'; ?>;
		}

	.list-row > td.description {
		background: <?php echo ($_SESSION['theme']['table_row_background_color_medium']['text'] != '') ? $_SESSION['theme']['table_row_background_color_medium']['text'] : '#f0f2f6'; ?> !important;
		}

	.list-header > .checkbox,
	.list-row > .checkbox {
		width: 1%;
		text-align: center !important;
		cursor: default !important;
		}

	.list-row > .checkbox {
		padding: 3px 7px 1px 7px;
		}

	.list-row > .button {
		margin: 0;
		padding-top: 1px;
		padding-bottom: 1px;
		white-space: nowrap;
		}

	.list-row > .input {
		margin: 0;
		padding-top: 0;
		padding-bottom: 0;
		white-space: nowrap;
		}

	.list-row > .overflow {
		max-width: 50px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		}

	.list-header > .action-button,
	.list-row > .action-button {
		width: 1px;
		white-space: nowrap;
		background: none;
		padding: 0;
		}

	.list-header > .center,
	.list-row > .center {
		text-align: center !important;
		}

	.list-header > .right,
	.list-row > .right {
		text-align: right !important;
		}

	.list-header > .middle,
	.list-row > .middle {
		vertical-align: middle !important;
		}

	.list-header > .no-wrap,
	.list-row > .no-wrap {
		white-space: nowrap;
		}

/* EDIT ********************************************************************************/

	td.edit_delete_checkbox_all {
		text-align: center;
		width: 50px;
		}

	td.edit_delete_checkbox_all input[type=checkbox] {
		vertical-align: middle;
		margin-top: -2px;
		}

	td.edit_delete_checkbox_all > span:nth-child(2) {
		display: none;
		}

/* CURSORS ***********************************************************************/

	.cursor-default { cursor: default; }
	.cursor-help { cursor: help; }
	.cursor-pointer { cursor: pointer; }
	.cursor-denied { cursor: not-allowed; }

/* WIDTH HELPERS **********************************************************************/

	.pct-5 { width: 5%; }
	.pct-10 { width: 10%; }
	.pct-15 { width: 15%; }
	.pct-20 { width: 20%; }
	.pct-25 { width: 25%; }
	.pct-30 { width: 30%; }
	.pct-35 { width: 35%; }
	.pct-40 { width: 40%; }
	.pct-45 { width: 45%; }
	.pct-50 { width: 50%; }
	.pct-55 { width: 55%; }
	.pct-60 { width: 60%; }
	.pct-65 { width: 65%; }
	.pct-70 { width: 70%; }
	.pct-75 { width: 75%; }
	.pct-80 { width: 80%; }
	.pct-85 { width: 85%; }
	.pct-90 { width: 90%; }
	.pct-95 { width: 95%; }
	.pct-100 { width: 100%; }

/* SIDE PADDING & MARGIN HELPERS **********************************************************************/

	.pl-1 { padding-left: 1px !important; }		.pr-1 { padding-right: 1px !important; }
	.pl-2 { padding-left: 2px !important; }		.pr-2 { padding-right: 2px !important; }
	.pl-3 { padding-left: 3px !important; }		.pr-3 { padding-right: 3px !important; }
	.pl-4 { padding-left: 4px !important; }		.pr-4 { padding-right: 4px !important; }
	.pl-5 { padding-left: 5px !important; }		.pr-5 { padding-right: 5px !important; }
	.pl-6 { padding-left: 6px !important; }		.pr-6 { padding-right: 6px !important; }
	.pl-7 { padding-left: 7px !important; }		.pr-7 { padding-right: 7px !important; }
	.pl-8 { padding-left: 8px !important; }		.pr-8 { padding-right: 8px !important; }
	.pl-9 { padding-left: 9px !important; }		.pr-9 { padding-right: 9px !important; }
	.pl-10 { padding-left: 10px !important; }	.pr-10 { padding-right: 10px !important; }
	.pl-11 { padding-left: 11px !important; }	.pr-11 { padding-right: 11px !important; }
	.pl-12 { padding-left: 12px !important; }	.pr-12 { padding-right: 12px !important; }
	.pl-13 { padding-left: 13px !important; }	.pr-13 { padding-right: 13px !important; }
	.pl-14 { padding-left: 14px !important; }	.pr-14 { padding-right: 14px !important; }
	.pl-15 { padding-left: 15px !important; }	.pr-15 { padding-right: 15px !important; }
	.pl-20 { padding-left: 20px !important; }	.pr-20 { padding-right: 20px !important; }
	.pl-25 { padding-left: 25px !important; }	.pr-25 { padding-right: 25px !important; }
	.pl-30 { padding-left: 30px !important; }	.pr-30 { padding-right: 30px !important; }
	.pl-35 { padding-left: 35px !important; }	.pr-35 { padding-right: 35px !important; }
	.pl-40 { padding-left: 40px !important; }	.pr-40 { padding-right: 40px !important; }
	.pl-45 { padding-left: 45px !important; }	.pr-45 { padding-right: 45px !important; }
	.pl-50 { padding-left: 50px !important; }	.pr-50 { padding-right: 50px !important; }

	.ml-1 { margin-left: 1px !important; }		.mr-1 { margin-right: 1px !important; }
	.ml-2 { margin-left: 2px !important; }		.mr-2 { margin-right: 2px !important; }
	.ml-3 { margin-left: 3px !important; }		.mr-3 { margin-right: 3px !important; }
	.ml-4 { margin-left: 4px !important; }		.mr-4 { margin-right: 4px !important; }
	.ml-5 { margin-left: 5px !important; }		.mr-5 { margin-right: 5px !important; }
	.ml-6 { margin-left: 6px !important; }		.mr-6 { margin-right: 6px !important; }
	.ml-7 { margin-left: 7px !important; }		.mr-7 { margin-right: 7px !important; }
	.ml-8 { margin-left: 8px !important; }		.mr-8 { margin-right: 8px !important; }
	.ml-9 { margin-left: 9px !important; }		.mr-9 { margin-right: 9px !important; }
	.ml-10 { margin-left: 10px !important; }	.mr-10 { margin-right: 10px !important; }
	.ml-11 { margin-left: 11px !important; }	.mr-11 { margin-right: 11px !important; }
	.ml-12 { margin-left: 12px !important; }	.mr-12 { margin-right: 12px !important; }
	.ml-13 { margin-left: 13px !important; }	.mr-13 { margin-right: 13px !important; }
	.ml-14 { margin-left: 14px !important; }	.mr-14 { margin-right: 14px !important; }
	.ml-15 { margin-left: 15px !important; }	.mr-15 { margin-right: 15px !important; }
	.ml-20 { margin-left: 20px !important; }	.mr-20 { margin-right: 20px !important; }
	.ml-25 { margin-left: 25px !important; }	.mr-25 { margin-right: 25px !important; }
	.ml-30 { margin-left: 30px !important; }	.mr-30 { margin-right: 30px !important; }
	.ml-35 { margin-left: 35px !important; }	.mr-35 { margin-right: 35px !important; }
	.ml-40 { margin-left: 40px !important; }	.mr-40 { margin-right: 40px !important; }
	.ml-45 { margin-left: 45px !important; }	.mr-45 { margin-right: 45px !important; }
	.ml-50 { margin-left: 50px !important; }	.mr-50 { margin-right: 50px !important; }

/* MODAL ************************************************************************/

	.modal-window {
		z-index: 999999;
		position: fixed;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		opacity: 0;
		pointer-events: none;
		-webkit-transition: all <?php echo $_SESSION['theme']['modal_transition_seconds']['text'] != '' ? $_SESSION['theme']['modal_transition_seconds']['text'] : '0.3'; ?>s;
		-moz-transition: all <?php echo $_SESSION['theme']['modal_transition_seconds']['text'] != '' ? $_SESSION['theme']['modal_transition_seconds']['text'] : '0.3'; ?>s;
		transition: all <?php echo $_SESSION['theme']['modal_transition_seconds']['text'] != '' ? $_SESSION['theme']['modal_transition_seconds']['text'] : '0.3'; ?>s;
		background: <?php echo $_SESSION['theme']['modal_shade_color']['text'] != '' ? $_SESSION['theme']['modal_shade_color']['text'] : 'rgba(0, 0, 0, 0.3)'; ?>;
		}

	.modal-window > div {
		position: relative;
		padding: <?php echo $_SESSION['theme']['modal_padding']['text'] != '' ? $_SESSION['theme']['modal_padding']['text'] : '15px 20px 20px 20px'; ?>;
		background: <?php echo $_SESSION['theme']['modal_background_color']['text'] != '' ? $_SESSION['theme']['modal_background_color']['text'] : '#fff'; ?>;
		overflow: auto;
		}

	@media(min-width: 0px) and (max-width: 699px) {
		.modal-window > div {
			width: 100%;
			min-width: 200px;
			margin: 50px auto;
			border-radius: 0;
			}
		}

	@media(min-width: 700px) {
		.modal-window > div {
			width: <?php echo $_SESSION['theme']['modal_width']['text'] != '' ? $_SESSION['theme']['modal_width']['text'] : '500px'; ?>;
			margin: 10% auto;
			border-radius: <?php echo $_SESSION['theme']['modal_corner_radius']['text'] != '' ? $_SESSION['theme']['modal_corner_radius']['text'] : '5px'; ?>;
			box-shadow: <?php echo $_SESSION['theme']['modal_shadow']['text'] != '' ? $_SESSION['theme']['modal_shadow']['text'] : '0 0 40px rgba(0,0,0,0.25)'; ?>;
			}
		}

	.modal-window .modal-title {
		display: block;
		font-weight: bold;
		font-size: 120%;
		font-family: <?php echo $_SESSION['theme']['modal_title_font']['text'] != '' ? $_SESSION['theme']['modal_title_font']['text'] : ($_SESSION['theme']['heading_text_font']['text'] != '' ? $_SESSION['theme']['heading_text_font']['text'] : 'arial'); ?>;
		color: <?php echo $_SESSION['theme']['modal_title_color']['text'] != '' ? $_SESSION['theme']['modal_title_color']['text'] : ($_SESSION['theme']['heading_text_color']['text'] != '' ? $_SESSION['theme']['heading_text_color']['text'] : '#952424'); ?>;
		text-align: <?php echo $_SESSION['theme']['modal_title_alignment']['text'] != '' ? $_SESSION['theme']['modal_title_alignment']['text'] : 'left'; ?>;
		margin: <?php echo $_SESSION['theme']['modal_title_margin']['text'] != '' ? $_SESSION['theme']['modal_title_margin']['text'] : '0 0 15px 0'; ?>;
		}

	.modal-close {
		color: <?php echo $_SESSION['theme']['modal_close_color']['text'] != '' ? $_SESSION['theme']['modal_close_color']['text'] : '#aaa'; ?>;
		line-height: 50px;
		font-size: 150%;
		position: absolute;
		top: 0;
		right: 0;
		width: 50px;
		text-align: center;
		text-decoration: none !important;
		cursor: pointer;
		border-radius: <?php echo $_SESSION['theme']['modal_close_corner_radius']['text'] != '' ? $_SESSION['theme']['modal_close_corner_radius']['text'] : '0 0 0 5px'; ?>;
		background: <?php echo $_SESSION['theme']['modal_close_background_color']['text'] != '' ? $_SESSION['theme']['modal_close_background_color']['text'] : '#fff'; ?>;
		}

	.modal-close:hover {
		color: <?php echo $_SESSION['theme']['modal_close_color_hover']['text'] != '' ? $_SESSION['theme']['modal_close_color_hover']['text'] : '#000'; ?>;
		background: <?php echo $_SESSION['theme']['modal_close_background_color_hover']['text'] != '' ? $_SESSION['theme']['modal_close_background_color_hover']['text'] : '#fff'; ?>;
		}

	.modal-window .modal-message {
		display: block;
		color: <?php echo $_SESSION['theme']['modal_message_color']['text'] != '' ? $_SESSION['theme']['modal_message_color']['text'] : '#444'; ?>;
		text-align: <?php echo $_SESSION['theme']['modal_message_alignment']['text'] != '' ? $_SESSION['theme']['modal_message_alignment']['text'] : 'left'; ?>;
		margin: <?php echo $_SESSION['theme']['modal_message_margin']['text'] != '' ? $_SESSION['theme']['modal_message_margin']['text'] : '0 0 20px 0'; ?>;
		}

	.modal-actions {
		display: block;
		text-align: left;
		}

<?php

//output custom css
	if ($_SESSION['theme']['custom_css_code']['text'] != '') {
		echo $_SESSION['theme']['custom_css_code']['text'];
	}

?>
