<?php
include "root.php";
require_once "resources/require.php";

header("Content-type: text/css; charset: UTF-8");

$default_login = ($_REQUEST['login'] == 'default') ? true : false;

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
	if ($default_login) {
		//try using login background images/colors
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
	else {
		//use standard background images/colors
		if (isset($_SESSION['theme']) && isset($_SESSION['theme']['background_image_enabled']) && $_SESSION['theme']['background_image_enabled']['boolean'] == 'true' && is_array($_SESSION['theme']['background_image'])) {
			$background_images_enabled = true;
			$background_images = $_SESSION['theme']['background_image'];
		}
		else {
			$background_colors[0] = $_SESSION['theme']['background_color'][0];
			$background_colors[1] = $_SESSION['theme']['background_color'][1];
		}
	}

//check for background image
	if ($background_images_enabled) {
		// background image is enabled
		$image_extensions = array('jpg','jpeg','png','gif');

		if (count($background_images) > 0) {

			if ((!isset($_SESSION['background_image'])) or strlen($_SESSION['background_image']) == 0) {
				$_SESSION['background_image'] = $background_images[array_rand($background_images)];
				$background_image = $_SESSION['background_image'];
			}

			// background image(s) specified, check if source is file or folder
			if (in_array(strtolower(pathinfo($background_image, PATHINFO_EXTENSION)), $image_extensions)) {
				$image_source = 'file';
			}
			else {
				$image_source = 'folder';
			}

			// is source (file/folder) local or remote
			if (substr($background_image, 0, 4) == 'http') {
				$source_path = $background_image;
			}
			else if (substr($background_image, 0, 1) == '/') { //
				// use project path as root
				$source_path = PROJECT_PATH.$background_image;
			}
			else {
				// use theme images/backgrounds folder as root
				$source_path = PROJECT_PATH.'/themes/default/images/backgrounds/'.$background_image;
			}

		}
		else {
			// not set, so use default backgrounds folder and images
			$image_source = 'folder';
			$source_path = PROJECT_PATH.'/themes/default/images/backgrounds';
		}

		if ($image_source == 'folder') {
			if (file_exists($_SERVER["DOCUMENT_ROOT"].$source_path)) {
				// retrieve a random background image
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

// check for background color
	else if (
		$background_colors[0] != '' ||
		$background_colors[1] != ''
		) { // background color 1 or 2 is enabled

		if ($background_colors[0] != '' && $background_colors[1] == '') { // use color 1
			$background_color = "background: ".$background_colors[0].";";
		}
		else if ($background_colors[0] == '' && $background_colors[1] != '') { // use color 2
			$background_color = "background: ".$background_colors[1].";";
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
	else { // default: white
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

	/* main menu container */
	.navbar {
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
		}

	/* main menu logo */
	.navbar-logo {
		border: none;
		height: 27px;
		width: auto;
		margin: 11px 0 0 7px;
		padding-right: 13px;
		cursor: pointer;
		float: left;
		display: inline;
		}

	/* menu brand text */
	.navbar-header > div > a.navbar-brand {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color']['text'] : 'rgba(255,255,255,0.80)'; ?>;
		white-space: nowrap;
		}

	.navbar-header > div > a.navbar-brand:hover {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		}

	/* main menu item */
	.navbar .navbar-nav > li > a,
	.navbar .navbar-nav > li.current-menu-item > a {
		font-family: <?php echo ($_SESSION['theme']['menu_main_text_font']['text'] != '') ? $_SESSION['theme']['menu_main_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_main_text_size']['text'] != '') ? $_SESSION['theme']['menu_main_text_size']['text'] : '10.25pt'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_main_text_color']['text'] != '') ? $_SESSION['theme']['menu_main_text_color']['text'] : '#fff'; ?>;
		padding-right: 10px;
		padding-left: 10px;
		}

	.navbar .navbar-nav > li:hover > a,
	.navbar .navbar-nav > li:focus > a,
	.navbar .navbar-nav > li:active > a {
		color: <?php echo ($_SESSION['theme']['menu_main_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_main_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_background_color_hover']['text'] : 'rgba(0,0,0,1.0)'; ?>
		}

	.navbar .navbar-nav > li > a > span.glyphicon {
		margin: 1px 2px 0 0;
		}

	@media(min-width: 768px) {
		.dropdown:hover .dropdown-menu {
			display: block;
			}
		}

	/* sub menu container */
	.navbar-nav > li > .dropdown-menu {
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
	.dropdown-menu > li > a {
		font-family: <?php echo ($_SESSION['theme']['menu_sub_text_font']['text'] != '') ? $_SESSION['theme']['menu_sub_text_font']['text'] : 'arial'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color']['text'] : '#fff'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_sub_text_size']['text'] != '') ? $_SESSION['theme']['menu_sub_text_size']['text'] : '10pt'; ?>;
		margin: 0;
		padding: 3px 15px;
		}

	.dropdown-menu > li > a:hover,
	.dropdown-menu > li > a:focus,
	.dropdown-menu > li > a:active {
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color_hover']['text'] : '#141414'; ?>;
		outline: none;
		}

	.dropdown-menu > li > a > span.glyphicon {
		display: inline-block;
		font-size: 8pt;
		margin: 0 0 8px 8px;
		opacity: 0.30;
		text-align: top;
		}

	/* domain name/selector */
	a.domain_selector_domain {
		display: inline-block;
		white-space: nowrap;
		font-size: 9.5pt;
		color: <?php echo ($_SESSION['theme']['domain_color']['text'] != '') ? $_SESSION['theme']['domain_color']['text'] : 'rgba(255,255,255,0.8)'; ?>;
		padding: 16px 0 14px 0;
		}

	a.domain_selector_domain:hover,
	a.domain_selector_domain:focus,
	a.domain_selector_domain:active {
		color: <?php echo ($_SESSION['theme']['domain_color_hover']['text'] != '') ? $_SESSION['theme']['domain_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		text-decoration: none;
		}

	a#header_domain_selector_domain:hover,
	a#header_domain_selector_domain:focus,
	a#header_domain_selector_domain:active {
		text-decoration: none;
		cursor: pointer;
		}

	/* logout icon */
	a.logout_icon {
		display: inline-block;
		color: <?php echo ($_SESSION['theme']['logout_icon_color']['text'] != '') ? $_SESSION['theme']['logout_icon_color']['text'] : 'rgba(255,255,255,0.8)'; ?>;
		font-size: 11pt;
		padding: 16px 10px 13px 10px;
		margin-left: 10px;
		}

	a#header_logout_icon {
		display: inline-block;
		font-size: 11pt;
		padding-left: 5px;
		padding-right: 5px;
		margin-left: 5px;
		margin-right: 5px;
		}

	a.logout_icon:hover,
	a.logout_icon:focus,
	a.logout_icon:active {
		color: <?php echo ($_SESSION['theme']['logout_icon_color_hover']['text'] != '') ? $_SESSION['theme']['logout_icon_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		text-decoration: none;
		}

	/* xs menu toggle button */
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

	.navbar-inverse .navbar-toggle .icon-bar {
		background: <?php echo ($_SESSION['theme']['menu_main_toggle_color']['text'] != '') ? $_SESSION['theme']['menu_main_toggle_color']['text'] : 'rgba(255,255,255,0.8)'; ?>;
		}

	.navbar-inverse .navbar-toggle:hover > .icon-bar {
		background: <?php echo ($_SESSION['theme']['menu_main_toggle_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_toggle_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		}

/* SIDE MENU: Begin ***********************************************************/

	/* side menu container */
	div#menu_side_container {
		z-index: 99900;
		position: fixed;
		top: 0;
		left: 0;
		width: <?php echo is_numeric($_SESSION['theme']['menu_side_width_contracted']['text']) ? $_SESSION['theme']['menu_side_width_contracted']['text'] : '55'; ?>px;
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

	/* menu side brand container */
	div#menu_side_brand_container {
		position: -webkit-sticky;
		position: sticky;
		z-index: 99901;
		top: 0;
		padding: 20px;
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

	/* menu side logo */
	img#menu_brand_image_contracted {
		border: none;
		width: auto;
		max-height: 30px;
		max-width: 30px;
		margin-right: 10px;
		}

	img#menu_brand_image_expanded {
		border: none;
		height: auto;
		max-width: 145px;
		max-height: 35px;
		margin-top: -3px;
		margin-left: -6px;
		}

	/* menu brand text */
	.menu_brand_text {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color']['text'] : 'rgba(255,255,255,0.80)'; ?>;
		font-weight: 600;
		white-space: nowrap;
		}

	.menu_brand_text:hover {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		text-decoration: none;
		}

	a.menu_side_item_main {
		display: block;
		width: 100%;
		padding: 10px 20px;
		text-align: left;
		font-family: <?php echo ($_SESSION['theme']['menu_main_text_font']['text'] != '') ? $_SESSION['theme']['menu_main_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_main_text_size']['text'] != '') ? $_SESSION['theme']['menu_main_text_size']['text'] : '10.25pt'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_main_text_color']['text'] != '') ? $_SESSION['theme']['menu_main_text_color']['text'] : '#fff'; ?>;
		cursor: pointer;
		}

	a.menu_side_item_main:hover,
	a.menu_side_item_main:focus,
	a.menu_side_item_main:active {
		color: <?php echo ($_SESSION['theme']['menu_main_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_main_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_background_color_hover']['text'] : 'rgba(0,0,0,1.0)'; ?>;
		text-decoration: none;
		}

	a.menu_side_item_sub {
		display: block;
		width: 100%;
		padding: 5px 20px 5px 42px;
		text-align: left;
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		font-family: <?php echo ($_SESSION['theme']['menu_sub_text_font']['text'] != '') ? $_SESSION['theme']['menu_sub_text_font']['text'] : 'arial'; ?>;
		font-size: <?php echo ($_SESSION['theme']['menu_sub_text_size']['text'] != '') ? $_SESSION['theme']['menu_sub_text_size']['text'] : '10pt'; ?>;
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color']['text'] : '#fff'; ?>;
		cursor: pointer;
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

/* BUTTONS ********************************************************************/

	/* buttons */
	input.btn,
	input.button {
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
		}

	input.btn:hover,
	input.btn:active,
	input.btn:focus,
	input.button:hover,
	input.button:active,
	input.button:focus {
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

	/* default bootstrap buttons - not currently used */
	button.btn-default {
		font-family: Candara, Calibri, Segoe, "Segoe UI", Optima, Arial, sans-serif;
		padding: 4px 8px;
		color: #fff;
		font-weight: bold;
		font-size: 8pt;
		border: 1px solid #26242a;
		background: #3e3e3e;
		background-image: -moz-linear-gradient(top, #000 0%, #3e3e3e 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.0, #000), color-stop(1, #3e3e3e));
		-moz-border-radius: 3px;
		-webkit-border-radius: 3px;
		-khtml-border-radius: 3px;
		border-radius: 3px;
		text-align: center;
		text-transform: uppercase;
		text-shadow: 0px 0px 1px rgba(0,0,0,0.9);
		opacity: 0.9;
		-moz-opacity: 0.9;
		}

	button.btn-default:hover,
	button.btn-default:active,
	button.btn-default:focus {
		cursor: pointer;
		color: #ffffff;
		border: 1px solid #26242a;
		box-shadow: 0 0 5px #cddaf0;
		-webkit-box-shadow: 0 0 5px #cddaf0;
		-moz-box-shadow: 0 0 5px #cddaf0;
		opacity: 1.0;
		-moz-opacity: 1.0;
		}

	/* control icons (must be defined after the default bootstrap buttons) */
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

/* ICONS *********************************************************************/

	span.icon_glyphicon_body {
		width: 16px;
		height: 16px;
		color: <?php echo ($_SESSION['theme']['body_icon_color']['text'] != '') ? $_SESSION['theme']['body_icon_color']['text'] : 'rgba(0,0,0,0.25)'; ?>;
		border: 0;
		}

	span.icon_glyphicon_body:hover {
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
		background-color: #fff;
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

	#domains_list {
		position: relative;
		overflow: auto;
		width: 300px;
		height: 100%;
		padding: 1px;
		background-color: #fff;
		border: 1px solid #a4aebf;
		}

	div.domains_list_item, div.domains_list_item_active {
		text-align: left;
		border-bottom: 1px solid #c5d1e5;
		padding: 5px 8px 8px 8px;
		overflow: hidden;
		white-space: nowrap;
		cursor: pointer;
		}

	div.domains_list_item span.domain_list_item_description, div.domains_list_item_active span.domain_list_item_description {
		color: #999;
		font-size: 11px;
		}

	div.domains_list_item:hover a,
	div.domains_list_item:hover span {
		color: #5082ca;
		}
	
	div.domains_list_item_active:hover a,
	div.domains_list_item_active:hover span {
		color: <?php echo ($_SESSION['theme']['domain_active_text_color_hover']['text']); ?>;
	}

/* DOMAIN SELECTOR: END ********************************************************/

	#default_login {
		position: fixed;
		top: 50%;
		left: 50%;
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
		color: <?php echo ($_SESSION['theme']['login_link_text_color']['text'] != '') ? $_SESSION['theme']['login_link_text_color']['text'] : '#004083'; ?>;
		font-size: <?php echo ($_SESSION['theme']['login_link_text_size']['text'] != '') ? $_SESSION['theme']['login_link_text_size']['text'] : '11px'; ?>;
		font-family: <?php echo ($_SESSION['theme']['login_link_text_font']['text'] != '') ? $_SESSION['theme']['login_link_text_font']['text'] : 'Arial'; ?>;
		text-decoration: none;
		}

	a.login_link:hover {
		color: <?php echo ($_SESSION['theme']['login_link_text_color_hover']['text'] != '') ? $_SESSION['theme']['login_link_text_color_hover']['text'] : '#5082ca'; ?>;
		cursor: pointer;
		text-decoration: underline;
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
		if (
			(strlen($_SESSION["username"]) > 0 || !$default_login)
			&&
			(isset($background_images) || $background_colors[0] != '' || $background_colors[1] != '')
			) { ?>
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
		width: 90%;
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

	div#content_header {
		padding: 10px;
		margin-top: 5px;
		height: 40px;
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

	a {
		color: <?php echo ($_SESSION['theme']['text_link_color']['text'] != '') ? $_SESSION['theme']['text_link_color']['text'] : '#004083'; ?>;
		text-decoration: none;
		}

	a:hover {
		color: <?php echo ($_SESSION['theme']['text_link_color_hover']['text'] != '') ? $_SESSION['theme']['text_link_color_hover']['text'] : '#5082ca'; ?>;
		text-decoration: underline;
		}

	form {
		margin: 0;
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

	input.fileinput {
		padding: 1px;
		display: inline;
		}

	textarea {
		min-height: 75px;
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

	td.playback_progress_bar_background {
		padding: 0;
		border-bottom: none;
		background-image: -ms-linear-gradient(top, rgba(0,0,0,0.15) 0%, transparent 100%);
		background-image: -moz-linear-gradient(top, rgba(0,0,0,0.15) 0%, transparent 100%);
		background-image: -o-linear-gradient(top, rgba(0,0,0,0.15) 0%, transparent 100%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, rgba(0,0,0,0.15)), color-stop(1, transparent));
		background-image: -webkit-linear-gradient(top, rgba(0,0,0,0.15) 0%, transparent 100%);
		background-image: linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, transparent 100%);
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
		text-decoration: underline;
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
		padding: 10px;
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

	table.op_ext {
		width: 100%;
		height: 60px;
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
