<?php
//get the browser version
	$user_agent = http_user_agent();
	$browser_version =  $user_agent['version'];
	$browser_name =  $user_agent['name'];
	$browser_version_array = explode('.', $browser_version);

//set the doctype
	echo ($browser_name != "Internet Explorer") ? "<!DOCTYPE html>\n" : "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<!--{project_path}-->/resources/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<!--{project_path}-->/resources/bootstrap/css/bootstrap-datetimepicker.min.css" />
<link rel="stylesheet" href="<!--{project_path}-->/resources/bootstrap/css/bootstrap-colorpicker.min.css">
<title><!--{title}--></title>

<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/jquery/jquery-1.11.1.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/jquery/jquery.autosize.input.js"></script>

<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/momentjs/moment.js"></script>

<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/bootstrap/js/bootstrap.min.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/bootstrap/js/bootstrap-datetimepicker.min.js"></script>
<script language="JavaScript" type="text/javascript" src="<!--{project_path}-->/resources/bootstrap/js/bootstrap-colorpicker.js"></script>

<?php

//get the php self path and set a variable with only the directory path
	$php_self_array = explode ("/", $_SERVER['PHP_SELF']);
	$php_self_dir = '';
	foreach ($php_self_array as &$value) {
		if (substr($value, -4) != ".php") {
			$php_self_dir .= $value."/";
		}
	}
	unset($php_self_array);
	if (strlen(PROJECT_PATH) > 0) {
		$php_self_dir = substr($php_self_dir, strlen(PROJECT_PATH), strlen($php_self_dir));
	}


//set fav icon
	if (isset($_SESSION['theme']['favicon']['text'])){
		$favicon = $_SESSION['theme']['favicon']['text'];
	}
	else {
		$favicon = '<!--{project_path}-->/themes/default/favicon.ico';
	}
	echo "<link rel='icon' href='".$favicon."'>\n";


//check for background image
	if ($_SESSION['theme']['background_image_enabled']['boolean'] == 'true' && isset($_SESSION['theme']['background_image'])) {
		// background image is enabled
		$image_extensions = array('jpg','jpeg','png','gif');

		if (count($_SESSION['theme']['background_image']) > 0) {

			if (strlen($_SESSION['background_image']) == 0) {
				$_SESSION['background_image'] = $_SESSION['theme']['background_image'][array_rand($_SESSION['theme']['background_image'])];
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
		$_SESSION['theme']['background_color'][0] != '' ||
		$_SESSION['theme']['background_color'][1] != ''
		) { // background color 1 or 2 is enabled

		if ($_SESSION['theme']['background_color'][0] != '' && $_SESSION['theme']['background_color'][1] == '') { // use color 1
			$background_color = "background: ".$_SESSION['theme']['background_color'][0].";";
		}
		else if ($_SESSION['theme']['background_color'][0] == '' && $_SESSION['theme']['background_color'][1] != '') { // use color 2
			$background_color = "background: ".$_SESSION['theme']['background_color'][1].";";
		}
		else if ($_SESSION['theme']['background_color'][0] != '' && $_SESSION['theme']['background_color'][1] != '') { // vertical gradient
			$background_color = "background: ".$_SESSION['theme']['background_color'][0].";\n";
			$background_color .= "background: -ms-linear-gradient(top, ".$_SESSION['theme']['background_color'][0]." 0%, ".$_SESSION['theme']['background_color'][1]." 100%);\n";
			$background_color .= "background: -moz-linear-gradient(top, ".$_SESSION['theme']['background_color'][0]." 0%, ".$_SESSION['theme']['background_color'][1]." 100%);\n";
			$background_color .= "background: -o-linear-gradient(top, ".$_SESSION['theme']['background_color'][0]." 0%, ".$_SESSION['theme']['background_color'][1]." 100%);\n";
			$background_color .= "background: -webkit-gradient(linear, left top, left bottom, color-stop(0, ".$_SESSION['theme']['background_color'][0]."), color-stop(1, ".$_SESSION['theme']['background_color'][1]."));\n";
			$background_color .= "background: -webkit-linear-gradient(top, ".$_SESSION['theme']['background_color'][0]." 0%, ".$_SESSION['theme']['background_color'][1]." 100%);\n";
			$background_color .= "background: linear-gradient(to bottom, ".$_SESSION['theme']['background_color'][0]." 0%, ".$_SESSION['theme']['background_color'][1]." 100%);\n";
		}
	}
	else { // default: white
		$background_color = "background: #fff;\n";
	}
?>

<style type='text/css'>

	body {
		z-index: 1;
		position: absolute;
		margin: 0;
		padding: 0;
		overflow: auto;
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
	}

	div#footer {
		background: <?php echo $_SESSION['theme']['footer_background_color']['text']; ?>;
		text-align: center;
		vertical-align: middle;
		padding: 8px;
		-webkit-border-radius: 0 0 4px 4px;
		-moz-border-radius: 0 0 4px 4px;
		border-radius: 0 0 4px 4px;
		}

	.footer {
		font-size: 11px;
		font-family: arial;
		line-height: 14px;
		color: <?php echo $_SESSION['theme']['footer_color']['text']; ?>;
		white-space: nowrap;
		}


/* BOOTSTRAP MENU: BEGIN ******************************************************************/

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
		border: none;
		}

	/* main menu logo */
	.navbar-logo {
		border: none;
		height: 27px;
		width: auto;
		margin: 11px 13px 0 7px;
		cursor: pointer;
		float: left;
		display: inline;
		}

	/* menu brand text */
	.navbar-header > div > a.navbar-brand {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color']['text'] : 'rgba(255,255,255,0.80)'; ?>;
		}

	.navbar-header > div > a.navbar-brand:hover {
		color: <?php echo ($_SESSION['theme']['menu_brand_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_brand_text_color_hover']['text'] : 'rgba(255,255,255,1.0)'; ?>;
		}

	/* main menu item */
	.navbar-nav > li > a.dropdown-toggle, .navbar-nav > li > a.dropdown-toggle, .navbar-nav > li > a.dropdown-toggle {
		font-size: 10.25pt;
		color: <?php echo ($_SESSION['theme']['menu_main_text_color']['text'] != '') ? $_SESSION['theme']['menu_main_text_color']['text'] : '#fff'; ?>;
		padding-right: 9px;
		}

	.navbar-nav > li > a.dropdown-toggle:hover, .navbar-nav > li > a.dropdown-toggle:focus, .navbar-nav > li > a.dropdown-toggle:active {
		color: <?php echo ($_SESSION['theme']['menu_main_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_text_color_hover']['text'] : '#fd9c03'; ?>;
		}

	.navbar-nav > li > a > span.glyphicon {
		margin: 1px 7px 0 0;
		}

	@media(min-width: 768px) {
		.dropdown:hover .dropdown-menu {
			display: block;
			}
	}

	/* xs menu toggle button */
	.navbar-inverse .navbar-toggle {
		border: none;
		}

	.navbar-inverse .navbar-toggle:hover, .navbar-inverse .navbar-toggle:focus, .navbar-inverse .navbar-toggle:active {
		background: <?php echo ($_SESSION['theme']['menu_main_background_color']['text'] != '') ? $_SESSION['theme']['menu_main_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		}

	.navbar-inverse .navbar-toggle .icon-bar {
		background: <?php echo ($_SESSION['theme']['menu_main_text_color']['text'] != '') ? $_SESSION['theme']['menu_main_text_color']['text'] : '#fff'; ?>;
		}

	.navbar-inverse .navbar-toggle:hover > .icon-bar {
		background: <?php echo ($_SESSION['theme']['menu_main_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_main_text_color_hover']['text'] : '#fd9c03'; ?>;
		}

	/* sub menu container */
	.navbar-nav > li > .dropdown-menu {
		margin-top: 0;
		padding-bottom: 10px;
		border: none;
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color']['text'] : 'rgba(0,0,0,0.90)'; ?>;
		-webkit-box-shadow: <?php echo ($_SESSION['theme']['menu_sub_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_sub_shadow_color']['text'] : 'none';?>;
		-moz-box-shadow: <?php echo ($_SESSION['theme']['menu_sub_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_sub_shadow_color']['text'] : 'none';?>;
		box-shadow: <?php echo ($_SESSION['theme']['menu_sub_shadow_color']['text'] != '') ? '0 0 5px '.$_SESSION['theme']['menu_sub_shadow_color']['text'] : 'none';?>;
		}

	/* sub menu item */
	.dropdown-menu > li > a {
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color']['text'] : '#fff'; ?>;
		font-size: 10pt;
		margin: 0;
		padding: 3px 15px;
		}

	.dropdown-menu > li > a:hover, .dropdown-menu > li > a:focus, .dropdown-menu > li > a:active {
		color: <?php echo ($_SESSION['theme']['menu_sub_text_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_text_color_hover']['text'] : '#fd9c03'; ?>;
		background: <?php echo ($_SESSION['theme']['menu_sub_background_color_hover']['text'] != '') ? $_SESSION['theme']['menu_sub_background_color_hover']['text'] : '#141414'; ?>;
		}

	.dropdown-menu > li > a > span.glyphicon {
		display: inline-block;
		font-size: 8pt;
		margin: 0px 0 8px 8px;
		opacity: 0.30;
		text-align: top;
		}

	/* menu toggle button */
	.navbar-header > button.navbar-toggle {
		margin-left: 20px;
		}

	#logout_icon {
		color: <?php echo ($_SESSION['theme']['domain_color']['text'] != '') ? $_SESSION['theme']['domain_color']['text'] : '#fff'; ?>;
		font-size: 11pt;
		margin: 16px 19px 0 5px;
		filter: alpha(opacity=80);
		opacity: 0.80;
		-moz-opacity: 0.80;
		-khtml-opacity: 0.80;
		}

	#logout_icon:hover {
		filter: alpha(opacity=100);
		opacity: 1;
		-moz-opacity: 1;
		-khtml-opacity: 1;
		cursor: pointer;
		}

	.domain_selector_domain {
		<?php if ($_SESSION['theme']['domain_visible']['text'] != 'true') { ?>display: none;<?php } ?>
		white-space: nowrap;
		opacity: 0.8;
		-moz-opacity: 0.8;
		-khtml-opacity: 0.8;
		font-size: 9.5pt;
		color: <?php echo ($_SESSION['theme']['domain_color']['text'] != '') ? $_SESSION['theme']['domain_color']['text'] : '#fff'; ?>;
		}

	.domain_selector_domain:hover {
		filter: alpha(opacity=100);
		opacity: 1;
		-moz-opacity: 1;
		-khtml-opacity: 1;
		cursor: pointer;
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

	div.domains_list_item {
		text-align: left;
		border-bottom: 1px solid #c5d1e5;
		padding: 5px 8px 8px 8px;
		overflow: hidden;
		white-space: nowrap;
		cursor: pointer;
		}

	div.domains_list_item span.domain_list_item_description {
		color: #999;
		font-size: 11px;
		}

	div.domains_list_item:hover a,
	div.domains_list_item:hover span {
		color: #5082ca;
		}

/* DOMAIN SELECTOR: END ********************************************************/

	#default_login {
		display: inline-block;
		padding: 30px;
		margin-bottom: 28px;
		<?php
		if (
			isset($_SESSION['theme']['background_image']) ||
			$_SESSION['theme']['background_color'][0] != '' ||
			$_SESSION['theme']['background_color'][1] != ''
			) { ?>
			background: <?php echo ($_SESSION['theme']['login_background_color']['text'] != '') ? $_SESSION['theme']['login_background_color']['text'] : "rgba(255,255,255,0.35)"; ?>;
			-webkit-border-radius: 4px;
			-moz-border-radius: 4px;
			border-radius: 4px;
			-webkit-box-shadow: <?php echo ($_SESSION['theme']['login_shadow_color']['text'] != '') ? '0 1px 20px '.$_SESSION['theme']['login_shadow_color']['text'] : 'none';?>;
			-moz-box-shadow: <?php echo ($_SESSION['theme']['login_shadow_color']['text'] != '') ? '0 1px 20px '.$_SESSION['theme']['login_shadow_color']['text'] : 'none';?>;
			box-shadow: <?php echo ($_SESSION['theme']['login_shadow_color']['text'] != '') ? '0 1px 20px '.$_SESSION['theme']['login_shadow_color']['text'] : 'none';?>;
			<?php
		}
		?>
		}

	a.login_box_link {
		font-size: 11px;
		text-shadow: 0 0 2px <?php echo ($_SESSION['theme']['login_background_color']['text'] != '') ? $_SESSION['theme']['login_background_color']['text'] : "#ffffff"; ?>;
		cursor: pointer;
		text-decoration: underline;
		}

	#main_content {
		<?php
		if (
			strlen($_SESSION["username"]) > 0 &&
			(
				isset($_SESSION['theme']['background_image']) ||
				$_SESSION['theme']['background_color'][0] != '' ||
				$_SESSION['theme']['background_color'][1] != ''
			)) { ?>
			background: <?php echo ($_SESSION['theme']['body_color']['text'] != '') ? $_SESSION['theme']['body_color']['text'] : "#ffffff"; ?>;
			background-attachment: fixed;
			-webkit-border-radius: 4px;
			-moz-border-radius: 4px;
			border-radius: 4px;
			-webkit-box-shadow: <?php echo ($_SESSION['theme']['body_shadow_color']['text'] != '') ? '0 1px 4px '.$_SESSION['theme']['body_shadow_color']['text'] : 'none';?>;
			-moz-box-shadow: <?php echo ($_SESSION['theme']['body_shadow_color']['text'] != '') ? '0 1px 4px '.$_SESSION['theme']['body_shadow_color']['text'] : 'none';?>;
			box-shadow: <?php echo ($_SESSION['theme']['body_shadow_color']['text'] != '') ? '0 1px 4px '.$_SESSION['theme']['body_shadow_color']['text'] : 'none';?>;
			padding: 15px 20px 20px 20px;
		<?php } else { ?>
			padding: 5px 10px 10px 10px;
		<?php } ?>
		text-align: left;
		}

/* GENERAL ELEMENTS *****************************************************************/

	img {
		border: none;
		}

	a {
		color: #004083;
		width: 100%;
		}

	a:hover {
		color: #5082ca;
		}

	.title {
		color: #952424;
		font-size: 15px;
		font-family: arial;
		font-weight: bold
		}

	b {
		color: #952424;
		font-size: 15px;
		font-family: arial;
		}

	th {
		border-bottom: 1px solid #a4aebf;
		text-align: left;
		color: #3164AD;
		font-size: 12px;
		font-family: arial;
		padding-top: 4px;
		padding-bottom: 4px;
		padding-right: 7px;
		padding-left: 0;
		}

	th a:link{ color:#3164AD; text-decoration: none; }
	th a:visited{ color:#3164AD; }
	th a:hover{ color:#5082ca; text-decoration: underline; }
	th a:active{ color:#3164AD; }

	td {
		color: #5f5f5f;
		font-size: 12px;
		font-family: arial;
		}

	td.list_control_icons {
		/* multiple icons exist (horizontally) */
		padding: none;
		padding-left: 2px;
		width: 50px;
		text-align: right;
		vertical-align: top;
		white-space: nowrap;
		}

	td.list_control_icon {
		/* a single icon exists */
		padding: none;
		padding-left: 3px;
		width: 25px;
		text-align: right;
		vertical-align: top;
		white-space: nowrap;
		}

	img.list_control_icon {
		margin: 2px;
		width: 21px;
		height: 21px;
		border: none;
		opacity: 0.4;
		-moz-opacity: 0.4;
		}

	img.list_control_icon_disabled {
		margin: 2px;
		width: 21px;
		height: 21px;
		border: none;
		opacity: 0.4;
		-moz-opacity: 0.4;
		}

	form {
		margin: 0;
		}

	input.btn, input.button {
		font-family: Candara, Calibri, Segoe, "Segoe UI", Optima, Arial, sans-serif;
		padding: 3px 8px 4px 8px;
		margin-top: -1px;
		color: #fff;
		font-weight: bold;
		cursor: pointer;
		font-size: 11px;
		-moz-border-radius: 3px;
		-webkit-border-radius: 3px;
		-khtml-border-radius: 3px;
		border-radius: 3px;
		background-image: -moz-linear-gradient(top, #524f59 25%, #000 64%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #524f59), color-stop(0.64, #000));
		border: 1px solid #26242a;
		background-color: #000;
		text-align: center;
		text-transform: uppercase;
		text-shadow: 0px 0px 1px rgba(0, 0, 0, 0.85);
		opacity: 0.9;
		-moz-opacity: 0.9;
		}

	input.btn:hover, input.button:hover, img.list_control_icon:hover,
	input.btn:active, input.button:active, img.list_control_icon:active,
	input.btn:focus, input.button:focus, img.list_control_icon:focus {
		color: #fff;
		box-shadow: 0 0 5px #cddaf0;
		-webkit-box-shadow: 0 0 5px #cddaf0;
		-moz-box-shadow: 0 0 5px #cddaf0;
		opacity: 1.0;
		-moz-opacity: 1.0;
		cursor: pointer;
		}

	input.txt, textarea.txt, select.txt, .formfld {
		font-family: arial;
		font-size: 12px;
		color: #000;
		text-align: left;
		padding: 4px 6px;
		margin: 0 1px 1px 0;
		border: 1px solid #c0c0c0;
		background-color: #fff;
		box-shadow: 0 0 3px #cddaf0 inset;
		-moz-box-shadow: 0 0 3px #cddaf0 inset;
		-webkit-box-shadow: 0 0 3px #cddaf0 inset;
		border-radius: 3px;
		-moz-border-radius: 3px;
		-webkit-border-radius: 3px;
		}

	input.txt, .formfld {
		transition: width 0.25s;
		-moz-transition: width 0.25s;
		-webkit-transition: width 0.25s;
		max-width: 500px;
		}

	input.txt:focus, .formfld:focus {
		-webkit-box-shadow: 0 0 5px #cddaf0;
		-moz-box-shadow: 0 0 5px #cddaf0;
		box-shadow: 0 0 5px #cddaf0;
		}

	select.formfld {
		height: 27px;
		padding: 4px;
		}

	.formfld_highlight_bad {
		border-color: #aa2525;
		-webkit-box-shadow: 0 0 3px #aa2525 inset;
		-moz-box-shadow: 0 0 3px #aa2525 inset;
		box-shadow: 0 0 3px #aa2525 inset;
		}

	.formfld_highlight_good {
		border-color: #2fb22f;
		-webkit-box-shadow: 0 0 3px #2fb22f inset;
		-moz-box-shadow: 0 0 3px #2fb22f inset;
		box-shadow: 0 0 3px #2fb22f inset;
		}

	input.txt {
		width: 98.75%;
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
		}

/* TABLES *****************************************************************/

	.vncell {
		border-bottom: 1px solid #fff;
		background-color: #e5e9f0;
		padding: 8px;
		text-align: right;
		color: #000;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		border-right: 3px solid #e5e9f0;
		}

	.vncellreq {
		border-bottom: 1px solid #fff;
		background-color: #e5e9f0;
		padding: 8px;
		text-align: right;
		font-weight: bold;
		color: #000;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		border-right: 3px solid #cbcfd5;
		}

	.vncellcol {
		background-color: #e5e9f0;
		padding: 8px;
		padding-bottom: 6px;
		text-align: left;
		color: #000;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		border-bottom: 3px solid #e5e9f0;
		}

	.vncellcolreq {
		background-color: #e5e9f0;
		padding: 8px;
		padding-bottom: 6px;
		text-align: left;
		font-weight: bold;
		color: #000;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		border-bottom: 3px solid #cbcfd5;
		}

	.vtable {
		border-bottom: 1px solid #e5e9f0;
		color: #666;
		font-size: 8pt;
		text-align: left;
		padding: 7px;
		background-color: #fff;
		vertical-align: middle;
		}

	.vtablerow {
		color: #666;
		text-align: left;
		background-color: #fff;
		vertical-align: middle;
		height: 33px;
		}

	.listbg {
		border-bottom: 1px solid #a4aebf;
		font-size: 11px;
		background-color: #990000;
		color: #000;
		padding: 4px 16px 4px 6px;
		}

	table.tr_hover tr {
		background-color: transparent;
		cursor: default;
		}

	table.tr_hover tr:hover td,
	table.tr_hover tr:hover td a {
		color: #5082ca;
		}

	.row_style0 {
		border-bottom: 1px solid #c5d1e5;
		background-color: #e5e9f0;
		color: #000;
		text-align: left;
		padding: 4px 7px;
		}

	.row_style1 {
		border-bottom: 1px solid #c5d1e5;
		background-color: #fff;
		color: #000;
		text-align: left;
		padding: 4px 7px;
		}

	.row_style2 {
		border-bottom: 1px solid #c5d1e5;
		background-color: #fff;
		color: #000;
		text-align: center;
		padding: 0 1px 0 1px;
		width: 51px;
		white-space: nowrap;
		}

	.row_style_hor_mir_grad {
		background: -moz-linear-gradient(left, #e5e9f0 0%, #fff 25%, #fff 75%, #e5e9f0 100%);
		background: -webkit-gradient(linear, left top, right top, color-stop(0%,#e5e9f0), color-stop(25%,#fff), color-stop(75%,#fff), color-stop(100%,#e5e9f0));
		background: -webkit-linear-gradient(left, #e5e9f0 0%,#fff 25%,#fff 75%,#e5e9f0 100%);
		background: -o-linear-gradient(left, #e5e9f0 0%,#fff 25%,#fff 75%,#e5e9f0 100%);
		background: -ms-linear-gradient(left, #e5e9f0 0%,#fff 25%,#fff 75%,#e5e9f0 100%);
		background: linear-gradient(to right, #e5e9f0 0%,#fff 25%,#fff 75%,#e5e9f0 100%);
		filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#e5e9f0', endColorstr='#e5e9f0',GradientType=1 );
		}

	.row_stylebg {
		border-bottom: 1px solid #b9c5d8;
		background-color: #f0f2f6;
		color: #000;
		text-align: left;
		padding: 4px 7px;
		}

	table th {
		padding:4px 7px
		}

	table tr.even td {
		background:#eee;
		background-image: url('<!--{project_path}-->/themes/default/images/background_cell.gif');
		border-bottom: 1px solid #a4aebf;
		color: #333333;
		}

	table tr.odd td {
		border-bottom: 1px solid #a4aebf;
		color: #000000;
		}

	table tr:first-child th:first-child {
		-moz-border-radius-topleft:7px;
		-webkit-border-top-left-radius:7px;
		border-top-left-radius:7px;
		}

	table tr:first-child th:last-of-type {
		-moz-border-radius-topright:7px;
		-webkit-border-top-right-radius:7px;
		border-top-right-radius:7px;
		}

	table tr:nth-last-child(-5) td:first-of-type {
		-moz-border-radius-bottomleft:7px;
		-webkit-border-bottom-left-radius:7px;
		border-bottom-left-radius:7px;
		}

	table tr:nth-last-child(-5) td:first-of-type {
		-moz-border-radius-topleft:7px;
		-webkit-border-top-left-radius:7px;
		border-bottom-top-radius:7px;
		}

	.border {
		border: solid 1px #a4aebf;
		}

	.frm {
		border: solid 1px #ccc;
		color: #666;
		background-color: #EFEFEF;
		}

	.smalltext {
		color: #bbb;
		font-size: 11px;
		font-family: arial;
		}

	fieldset {
		padding: 8px;
		text-align: left;
		border: 1px solid #aeb7c6;
		border-radius: 3px;
		-moz-border-radius: 3px;
		-webkit-border-radius: 3px;
		margin: 0;
		}

	legend {
		font-size: 13px;
		font-family: arial;
		font-weight: bold;
		color: #3164ad;
		padding-bottom: 8px;
		padding-right: 2px;
		}

/* RESPONSE MESSAGES *******************************************************/

	#message_container {
		z-index: 99998;
		position: absolute;
		top: -80px;
		left: 0;
		right: 0;
		filter: alpha(opacity=0);
		opacity: 0;
		-moz-opacity:0;
		-khtml-opacity: 0;
		padding: 15px 0;
	}

	#message_text {
		z-index: 99999;
		position: absolute;
		top: -80px;
		left: 0;
		right: 0;
		filter: alpha(opacity=0);
		opacity: 0;
		-moz-opacity:0;
		-khtml-opacity: 0;
		margin: 0 auto;
		vertical-align: middle;
		padding: 15px 0;
		text-align: center;
		font-family: arial, san-serif;
		font-size: 10pt;
	}

	.message_container_mood_default {
		background: <?php echo $_SESSION['theme']['message_default_background_color']['text']; ?>;
		}

	.message_container_mood_negative {
		background: <?php echo $_SESSION['theme']['message_negative_background_color']['text']; ?>;
		}

	.message_container_mood_alert {
		background: <?php echo $_SESSION['theme']['message_alert_background_color']['text']; ?>;
		}

	.message_text_mood_default {
		color: <?php echo $_SESSION['theme']['message_default_color']['text']; ?>;
		}

	.message_text_mood_negative {
		color: <?php echo $_SESSION['theme']['message_negative_color']['text']; ?>;
		}

	.message_text_mood_alert {
		color: <?php echo $_SESSION['theme']['message_alert_color']['text']; ?>;
		}

/* OPERATOR PANEL: BEGIN ********************************************************/

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
		margin-top: 7px;
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
		background-image: -moz-linear-gradient(top, #8ec989 25%, #2d9c38 64%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #8ec989), color-stop(0.64, #2d9c38));
		background-color: #2d9c38;
		border: 1px solid #006200;
		}

	#op_btn_status_available_on_demand {
		background-image: -moz-linear-gradient(top, #abd0aa 25%, #629d62 64%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #abd0aa), color-stop(0.64, #629d62));
		background-color: #629d62;
		border: 1px solid #619c61;
		}

	#op_btn_status_on_break {
		background-image: -moz-linear-gradient(top, #ddc38b 25%, #be8e2c 64%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #ddc38b), color-stop(0.64, #be8e2c));
		background-color: #be8e2c;
		border: 1px solid #7d1b00;
		}

	#op_btn_status_do_not_disturb {
		background-image: -moz-linear-gradient(top, #cc8984 25%, #960d10 64%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #cc8984), color-stop(0.64, #960d10));
		background-color: #960d10;
		border: 1px solid #5b0000;
		}

	#op_btn_status_logged_out {
		background-image: -moz-linear-gradient(top, #cacac9 25%, #8d8d8b 64%);
		background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #cacac9), color-stop(0.64, #8d8d8b));
		background-color: #8d8d8b;
		border: 1px solid #5d5f5a;
		}

/* OPERATOR PANEL: END *******************************************************/

	span.playback_progress_bar {
		background-color: #c43e42;
		height: 1px;
		display: inline-block;
		}

/* USER DASHBOARD: BEGIN *****************************************************/

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
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		border-radius: 4px;
		text-align: center;
		background-color: #f5f7fa;
		background-image: -ms-linear-gradient(left, #edf1f7 0%, #f9fbfe 30%, #f9fbfe 70%, #edf1f7 100%);
		background-image: -moz-linear-gradient(left, #edf1f7 0%, #f9fbfe 30%, #f9fbfe 70%, #edf1f7 100%);
		background-image: -o-linear-gradient(left, #edf1f7 0%, #f9fbfe 30%, #f9fbfe 70%, #edf1f7 100%);
		background-image: -webkit-gradient(linear, left, right, color-stop(0, #edf1f7), color-stop(0.30, #f9fbfe), color-stop(0.70, #f9fbfe), color-stop(1, #edf1f7));
		background-image: -webkit-linear-gradient(left, #edf1f7 0%, #f9fbfe 30%, #f9fbfe 70%, #edf1f7 100%);
		background-image: linear-gradient(to right, #edf1f7 0%, #f9fbfe 30%, #f9fbfe 70%, #edf1f7 100%);
		}

	span.hud_title {
		display: block;
		font-family: Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif;
		text-shadow: 0px 1px 2px #000;
		letter-spacing: -0.02em;
		font-size: 12pt;
		color: #fff;
		width: 100%;
		height: 40px;
		text-align: center;
		line-height: 40px;
		background-color: #8e96a5;
		-webkit-border-radius: 4px 4px 0 0;
		-moz-border-radius: 4px 4px 0 0;
		border-radius: 4px 4px 0 0;
		border-bottom: 1px solid #737983;
		}

	span.hud_title:hover {
		opacity: 0.9;
		cursor: pointer;
		}

	span.hud_stat {
		display: block;
		clear: both;
		cursor: pointer;
		text-align: center;
		text-shadow: 0px 2px 2px #737983;
		width: 100%;
		height: 100px;
		color: #fff;
		font-size: 60pt;
		line-height: 77pt;
		font-weight: normal;
		background-color: #a4aebf;
		border-top: 1px solid #c5d1e5;
		}

	span.hud_stat:hover {
		opacity: 0.9;
		cursor: pointer;
		}

	span.hud_stat_title {
		display: block;
		clear: both;
		width: 100%;
		height: 30px;
		cursor: default;
		text-align: center;
		text-shadow: 0px 1px 1px #737983;
		color: #fff;
		font-size: 14px;
		padding-top: 4px;
		font-weight: normal;
		font-family: Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif;
		background-color: #a4aebf;
		border-bottom: 1px solid #909aa8;
		margin: 0;
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
		font-size: 11px;
		font-weight: bold;
		color: #000;
		text-align: left;
		}

	td.hud_text {
		font-size: 11px;
		color: #000;
		text-align: left;
		vertical-align: middle;
		}

	span.hud_expander {
		display: block;
		clear: both;
		background: #e5e9f0;
		padding: 4px 0;
		text-align: center;
		width: 100%;
		height: 25px;
		font-size: 13px;
		line-height: 5px;
		color: #a4aebf;
		-webkit-border-radius: 0 0 4px 4px;
		-moz-border-radius: 0 0 4px 4px;
		border-radius: 0 0 4px 4px;
		cursor: pointer;
		border-top: 1px solid #fff;
		text-shadow: 0px 1px 1px #fff;
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

</style>

<script language="JavaScript" type="text/javascript">

	$(document).ready(function() {

		//display message bar via js
			function display_message(msg, mood, delay) {
				var mood = (typeof mood !== 'undefined') ? mood : 'default';
				var delay = (typeof delay !== 'undefined') ? delay : <?php echo (1000 * (float) $_SESSION['theme']['message_delay']['text']); ?>;
				if (msg != '') {
					var inner_width = $(window).width();
					// add class by mood
					$("#message_container").addClass('message_container_mood_'+mood);
					$("#message_text").addClass('message_text_mood_'+mood);
					// output message
					$("#message_text").html(msg);
					$("#message_container").css({height: $("#message_text").css("height")});
					$("#message_container").css({width: inner_width});
					$("#message_text").show().animate({top: '+=80'}, 500).animate({opacity: 1}, 'fast').delay(delay).animate({top: '-=80'}, 1000).animate({opacity: 0});
					$("#message_container").show().animate({top: '+=80'}, 500).animate({opacity: <?php echo $_SESSION['theme']['message_opacity']['text']; ?>}, "fast").delay(delay).animate({top: '-=80'}, 1000).animate({opacity: 0}, function() {
						$("#message_container").removeClass('message_container_mood_'+mood);
					});
				}
			}


		//set response message, if any
			<?php
			if (strlen($_SESSION['message']) > 0) {
				$message_text = addslashes($_SESSION['message']);
				$message_mood = $_SESSION['message_mood'];
				$message_delay = $_SESSION['message_delay'];

				echo "display_message('".$message_text."'";
				echo ($message_mood != '') ? ", '".$message_mood."'" : ", 'default'";
				if ($message_delay != '') {
					echo ", '".$message_delay."'";
				}
				echo "); ";
				unset($_SESSION['message'], $_SESSION['message_mood'], $_SESSION['message_delay']);
			}
			?>


		//hide message bar on hover
			$("#message_text").mouseover(function() { $(this).hide(); $("#message_container").hide(); });


		//domain selector controls
			$(".domain_selector_domain").click(function() { show_domains(); });
			$("#domains_hide").click(function() { hide_domains(); });

			function show_domains() {
				var scrollbar_width = (window.innerWidth - $(window).width()); //gold: only solution that worked with body { overflow:auto }, even when scrollbar not visible
				if (scrollbar_width > 0) {
					$("body").css({'margin-right':scrollbar_width, 'overflow':'hidden'}); //disable body scroll bars
					$(".navbar").css('margin-right',scrollbar_width); //adjust navbar margin to compensate
					$("#domains_container").css('right',-scrollbar_width); //domain container right position to compensate
				}
				$("#domains_container").show();
				$("#domains_block").animate({marginRight: '+=300'}, 400);
				$("#domain_filter").focus();
				document.getElementById('domains_visible').value = 1;
			}

			function hide_domains() {
				$(document).ready(function() {
					$("#domains_block").animate({marginRight: '-=300'}, 400, function() {
						$("#domain_filter").val('');
						domain_search($("#domain_filter").val());
						$(".navbar").css('margin-right','0'); //restore navbar margin
						$("#domains_container").css('right','0'); //domain container right position
						$("#domains_container").hide();
						$("body").css({'margin-right':'0','overflow':'auto'}); //enable body scroll bars
					});
				});
				document.getElementById('domains_visible').value = 0;
			}

			$(document).keyup(function(e) { //escape toggles visibility
				if (e.keyCode == 27 && document.getElementById('domains_visible').value == 0) {
					show_domains();
				}
				else if (e.keyCode == 27 && document.getElementById('domains_visible').value == 1) {
					hide_domains();
				}
			});


		//link table rows (except the last - the list_control_icons cell) on a table with a class of 'tr_hover', according to the href attribute of the <tr> tag
			$('.tr_hover tr').each(function(i,e) {
			  $(e).children('td:not(.list_control_icon,.list_control_icons,.tr_link_void)').click(function() {
				 var href = $(this).closest("tr").attr("href");
				 if (href) { window.location = href; }
			  });
			});


		//apply the auto-size jquery script to all text inputs
			$("input.txt, textarea.txt, .formfld").autosizeInput();


		//audio playback functions
			img_play = new Image();	img_play.src = "<?php echo PROJECT_PATH; ?>/themes/default/images/icon_play.png";
			img_pause = new Image(); img_pause.src = "<?php echo PROJECT_PATH; ?>/themes/default/images/icon_pause.png";

			var recording_audio;

			function recording_play(recording_id) {
				if (document.getElementById('recording_progress_bar_'+recording_id)) {
					document.getElementById('recording_progress_bar_'+recording_id).style.display='';
				}
				recording_audio = document.getElementById('recording_audio_'+recording_id)

				if (recording_audio.paused) {
					recording_audio.volume = 1;
					recording_audio.play();
					document.getElementById('recording_button_'+recording_id).innerHTML = "<?php echo str_replace("class='list_control_icon'", "class='list_control_icon' style='opacity: 1;'", $v_link_label_pause); ?>";
				}
				else {
					recording_audio.pause();
					document.getElementById('recording_button_'+recording_id).innerHTML = "<?php echo $v_link_label_play; ?>";
				}
			}

			function recording_reset(recording_id) {
				if (document.getElementById('recording_progress_bar_'+recording_id)) {
					document.getElementById('recording_progress_bar_'+recording_id).style.display='none';
				}
				document.getElementById('recording_button_'+recording_id).innerHTML = "<?php echo $v_link_label_play; ?>";
			}

			function update_progress(recording_id) {
				recording_audio = document.getElementById('recording_audio_'+recording_id);
				var recording_progress = document.getElementById('recording_progress_'+recording_id);
				var value = 0;
				if (recording_audio.currentTime > 0) {
					value = (100 / recording_audio.duration) * recording_audio.currentTime;
				}
				recording_progress.style.width = value + "%";
			}


		//apply bootstrap-datetime plugin
			$(function() {
				$('.datetimepicker').datetimepicker({
					format: 'YYYY-MM-DD HH:mm'
				});
			});

		//apply bootstrap-colorpicker plugin
			$(function(){
				$('.colorpicker').colorpicker({
					align: 'left',
					customClass: 'colorpicker-2x',
					sliders: {
						saturation: {
							maxLeft: 200,
							maxTop: 200
						},
						hue: {
							maxTop: 200
						},
						alpha: {
							maxTop: 200
						}
					}
				});
			});

	});

</script>

<!--{head}-->

</head>

<?php
//add multi-lingual support
	$language = new text;
	$text = $language->get(null,'themes/default');
?>

<body onload="<?php echo $onload;?>">

	<div id='message_container' class='message_container_mood_default'></div>
	<div id='message_text' class='message_container_text_default'></div>

	<?php
	//logged in show the domains block
	if (strlen($_SESSION["username"]) > 0 && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
		?>
		<div id="domains_container">
			<input type="hidden" id="domains_visible" value="0">
			<div id="domains_block">
				<div id="domains_header">
					<input id="domains_hide" type="button" class="btn" style="float: right" value="<?php echo $text['theme-button-close']; ?>">
					<?php
					if (file_exists($_SERVER["DOCUMENT_ROOT"]."/app/domains/domains.php")) {
						$href = '/app/domains/domains.php';
					}
					else {
						$href = '/core/domain_settings/domains.php';
					}
					echo "<a href=\"".$href."\"><b style=\"color: #000;\">".$text['theme-title-domains']."</b></a> (".sizeof($_SESSION['domains']).")";
					?>
					<br><br>
					<input type="text" id="domain_filter" class="formfld" style="min-width: 100%; width: 100%;" placeholder="<?php echo $text['theme-label-search']; ?>" onkeyup="domain_search(this.value);">
				</div>
				<div id="domains_list">
					<?php
					$bgcolor1 = "#eaedf2";
					$bgcolor2 = "#fff";
					foreach($_SESSION['domains'] as $domain) {
						$bgcolor = ($bgcolor == $bgcolor1) ? $bgcolor2 : $bgcolor1;
						$bgcolor = ($domain['domain_uuid'] == $_SESSION['domain_uuid']) ? "#eeffee" : $bgcolor;
						echo "<div id=\"".$domain['domain_name']."\" class='domains_list_item' style='background-color: ".$bgcolor."' onclick=\"document.location.href='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".$domain['domain_uuid']."&domain_change=true';\">";
						echo "<a href='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".$domain['domain_uuid']."&domain_change=true' ".(($domain['domain_uuid'] == $_SESSION['domain_uuid']) ? "style='font-weight: bold;'" : null).">".$domain['domain_name']."</a>\n";
						if ($domain['domain_description'] != '') {
							echo "<span class=\"domain_list_item_description\"> - ".$domain['domain_description']."</span>\n";
						}
						echo "</div>\n";
						$ary_domain_names[] = $domain['domain_name'];
						$ary_domain_descs[] = str_replace('"','\"',$domain['domain_description']);
					}
					?>
				</div>

				<script>
					var domain_names = new Array("<?php echo implode('","', $ary_domain_names)?>");
					var domain_descs = new Array("<?php echo implode('","', $ary_domain_descs)?>");

					function domain_search(criteria) {
						for (var x = 0; x < domain_names.length; x++) {
							if (domain_names[x].toLowerCase().match(criteria.toLowerCase()) || domain_descs[x].toLowerCase().match(criteria.toLowerCase())) {
								document.getElementById(domain_names[x]).style.display = '';
							}
							else {
								document.getElementById(domain_names[x]).style.display = 'none';
							}
						}
					}
				</script>

			</div>
		</div>
		<?php
	}
	?>

	<?php
	// qr code container for contacts
	echo "<div id='qr_code_container' style='display: none;' onclick='$(this).fadeOut(400);'>";
	echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'><tr><td align='center' valign='middle'>";
	echo "		<span id='qr_code' onclick=\"$('#qr_code_container').fadeOut(400);\"></span>";
	echo "	</td></tr></table>";
	echo "</div>";
	?>

	<?php

	if (!$default_login) {

		//*************** BOOTSTRAP MENU ********************************
		function show_menu($menu_array, $menu_style, $menu_position) {

			//determine menu behavior
				switch ($menu_style) {
					case 'inline':
						$menu_type = 'default';
						$menu_width = 'calc(100% - 40px)';
						$menu_brand = false;
						break;
					case 'static':
						$menu_type = 'static-top';
						$menu_width = 'calc(100% - 40px)';
						$menu_brand = true;
						$menu_corners = "style='-webkit-border-radius: 0 0 4px 4px; -moz-border-radius: 0 0 4px 4px; border-radius: 0 0 4px 4px;'";
						break;
					case 'fixed':
					default:
						$menu_position = ($menu_position != '') ? $menu_position : 'top';
						$menu_type = 'fixed-'.$menu_position;
						$menu_width = 'calc(90% - 40px)';
						$menu_brand = true;
				}
			?>

			<nav class="navbar navbar-inverse navbar-<?php echo $menu_type; ?>" <?php echo $menu_corners; ?>>
				<div class="container-fluid" style='width: <?php echo $menu_width; ?>; padding: 0;'>
					<div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main_navbar" aria-expanded="false" aria-controls="navbar">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar" style='margin-top: 1px;'></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<?php
						if ($menu_brand) {
							//define menu brand link
								if (strlen(PROJECT_PATH) > 0) {
									$menu_brand_link = PROJECT_PATH;
								}
								else if (!$default_login) {
									$menu_brand_link = '/';
								}
							//define menu brand mark
								$menu_brand_text = ($_SESSION['theme']['menu_brand_text']['text'] != '') ? $_SESSION['theme']['menu_brand_text']['text'] : "FusionPBX";
								if ($_SESSION['theme']['menu_brand_type']['text'] == 'image' || $_SESSION['theme']['menu_brand_type']['text'] == '') {
									$menu_brand_image = (isset($_SESSION['theme']['menu_brand_image']['text'])) ? $_SESSION['theme']['menu_brand_image']['text'] : PROJECT_PATH."/themes/default/images/logo_header.png";
									echo "<img class='pull-left hidden-xs navbar-logo' src='".$menu_brand_image."' title=\"".$menu_brand_text."\" onclick=\"document.location.href='".$menu_brand_link."';\">";
									echo "<img class='pull-left visible-xs navbar-logo' src='".$menu_brand_image."' title=\"".$menu_brand_text."\" onclick=\"document.location.href='".$menu_brand_link."';\" style='margin-left: 21px;'>";
								}
								else if ($_SESSION['theme']['menu_brand_type']['text'] == 'text') {
									echo "<div class='pull-left'><a class='navbar-brand' href=\"".$menu_brand_link."\">".$menu_brand_text."</a></div>\n";
								}
						}
						//domain name/selector
							if ($_SESSION["username"] != '' && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
								echo "<ul class='nav navbar-nav pull-right visible-xs'>\n";
								echo "<li><a href='#' style='padding: 8px 4px 6px 0;' class='domain_selector_domain' title='".$text['theme-label-open_selector']."'>".$_SESSION['domain_name']."</a></li>\n";
								echo "</ul>\n";
							}
						?>
					</div>
					<div class="collapse navbar-collapse" id="main_navbar">
						<ul class="nav navbar-nav">
							<?php
							foreach ($menu_array as $index_main => $menu_parent) {
								$submenu = false;
								if (is_array($menu_parent['menu_items']) && sizeof($menu_parent['menu_items']) > 0) {
									$mod_li = "class='dropdown' ";
									$mod_a_1 = "class='dropdown-toggle text-left' data-toggle='dropdown' ";
									$submenu = true;
								}
								$mod_a_2 = ($menu_parent['menu_item_link'] != '') ? $menu_parent['menu_item_link'] : '#';
								$mod_a_3 = ($menu_parent['menu_item_category'] == 'external') ? "target='_blank' " : null;
								switch ($menu_parent['menu_item_title']) {
									case "Home": $glyph = 'home'; break;
									case "Accounts": $glyph = 'user'; break;
									case "Dialplan": $glyph = 'transfer'; break;
									case "Apps": $glyph = 'send'; break;
									case "Status": $glyph = 'equalizer'; break;
									case "Advanced": $glyph = 'cog'; break;
								}
								echo "<li ".$mod_li.">\n";
								echo "<a ".$mod_a_1." href='".$mod_a_2."' ".$mod_a_3."><span class='glyphicon glyphicon-".$glyph."' title=\"".$menu_parent['menu_language_title']."\"></span><span class='hidden-sm'>".$menu_parent['menu_language_title'].$mod_title."</span></a>\n";
								if ($submenu) {
									echo "<ul class='dropdown-menu'>\n";
									foreach ($menu_parent['menu_items'] as $index_sub => $menu_sub) {
										$mod_a_2 = ($menu_sub['menu_item_link'] != '') ? $menu_sub['menu_item_link'] : '#';
										$mod_a_3 = ($menu_sub['menu_item_category'] == 'external') ? "target='_blank' " : null;
										if ($_SESSION['theme']['menu_sub_icons']['boolean'] == 'true') {
											$mod_nw = ($menu_sub['menu_item_category'] == 'external') ? "<span class='glyphicon glyphicon-new-window'></span>" : null;
											switch ($menu_sub['menu_item_title']) {
												case 'Logout': $mod_icon = "<span class='glyphicon glyphicon-log-out'></span>"; break;
												default: $mod_icon = null;
											}
										}
										echo "<li><a href='".$mod_a_2."' ".$mod_a_3.">".(($_SESSION['theme']['menu_sub_icons']) ? "<span class='glyphicon glyphicon-minus visible-xs pull-left' style='margin: 4px 10px 0 25px;'></span>" : null).$menu_sub['menu_language_title'].$mod_icon.$mod_nw."</a></li>\n";
									}
									echo "</ul>\n";
								}
								echo "</li>\n";
							}
							?>
						</ul>
						<ul class="nav navbar-nav navbar-right">
							<?php
							//domain name/selector
								if ($_SESSION["username"] != '' && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
									echo "<li class='hidden-xs'><a href='#' class='domain_selector_domain' title='".$text['theme-label-open_selector']."'>".$_SESSION['domain_name']."</a></li>";
								}
							//logout icon
								if ($_SESSION['username'] != '' && $_SESSION['theme']['logout_icon_visible']['text'] == "true") {
									$username_full = $_SESSION['username'].((count($_SESSION['domains']) > 1) ? "@".$_SESSION["user_context"] : null);
									echo "<li class='hidden-xs'><a href='".PROJECT_PATH."/logout.php' onclick=\"return confirm('".$text['theme-confirm-logout']."')\"><span class='glyphicon glyphicon-log-out'></span></a></li>";
									unset($username_full);
								}
							?>
						</ul>
					</div>
				</div>
			</nav>

			<?php
		}


		//determine menu configuration
			$menu = new menu;
			$menu->db = $db;
			$menu->menu_uuid = $_SESSION['domain']['menu']['uuid'];
			$menu_array = $menu->menu_array();
			unset($menu);

			$menu_style = ($_SESSION['theme']['menu_style']['text'] != '') ? $_SESSION['theme']['menu_style']['text'] : 'fixed';
			$menu_position = ($_SESSION['theme']['menu_position']['text']) ? $_SESSION['theme']['menu_position']['text'] : 'top';
			$open_container = "<div class='container-fluid' style='width: 90%; padding: 0;' align='center'>";

			switch ($menu_style) {
				case 'inline':
					$logo_align = ($_SESSION['theme']['logo_align']['text'] != '') ? $_SESSION['theme']['logo_align']['text'] : 'left';
					echo str_replace("center", $logo_align, $open_container);
					if ($_SERVER['PHP_SELF'] != PROJECT_PATH."/resources/install.php") {
						$logo = ($_SESSION['theme']['logo']['text'] != '') ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH."/themes/default/images/logo.png";
						echo "<a href='".((PROJECT_PATH != '') ? PROJECT_PATH : '/')."'><img src='".$logo."' style='padding: 15px 20px;'></a>";
					}

					show_menu($menu_array, $menu_style, $menu_position);
					$body_top_style = "style='padding-top: 0px; margin-top: -8px;'";
					break;
				case 'static':
					echo $open_container;
					show_menu($menu_array, $menu_style, $menu_position);
					$body_top_style = "style='padding: 0; margin-top: -5px;'";
					break;
				case 'fixed':
					show_menu($menu_array, $menu_style, $menu_position);
					echo $open_container;
					switch ($menu_position) {
						case 'bottom': $body_top_style = "style='margin-top: 30px;'"; break;
						case 'top': $body_top_style = "style='margin-top: 65px;'"; break;
					}
			}
			?>

			<table width='100%' border='0' cellpadding='0' cellspacing='0' <?php echo $body_top_style; ?>>
				<tr>
					<td align='left' valign='top'>
						<table border='0' cellpadding='0' cellspacing='0' width='100%'>
							<tr>
								<td width='100%' style='padding-right: 15px;' align='right' valign='middle'>
									<?php
								// login form
									if ($_SERVER['PHP_SELF'] != PROJECT_PATH."/core/install/install.php" && !$default_login) {
										if (strlen($_SESSION["username"]) == 0) {
										//add multi-lingual support
											require_once "core/user_settings/app_languages.php";
											foreach($text as $key => $value) {
												$text[$key] = $value[$_SESSION['domain']['language']['code']];
											}
										//set a default login destination
											if (strlen($_SESSION['login']['destination']['url']) == 0) {
												$_SESSION['login']['destination']['url'] = PROJECT_PATH."/core/user_settings/user_dashboard.php";
											}
										//login form
											echo "<div align='right'>\n";
											echo "	<form name='login' METHOD=\"POST\" action=\"".$_SESSION['login']['destination']['url']."\">\n";
											echo "		<input type='hidden' name='path' value='".$_GET['path']."'>\n";
											echo "		<table width='200' border='0'>\n";
											echo "			<tr>\n";
											echo "				<td>\n";
											echo "		  			<input type='text' class='formfld' style='min-width: 150px; width: 105px; text-align: center;' name='username' placeholder=\"".$text['label-username']."\">\n";
											echo "				</td>\n";
											echo "				<td align='left'>\n";
											echo "					<input type='password' class='formfld' style='min-width: 150px; width: 105px; text-align: center;' name='password' placeholder=\"".$text['label-password']."\">\n";
											echo "				</td>\n";

											if ($_SESSION['login']['domain_name.visible']['boolean'] == "true") {
												echo "			<td align='left'>\n";
												echo "				<strong>".$text['label-domain'].":</strong>\n";
												echo "			</td>\n";
												echo "			<td>\n";
												if (count($_SESSION['login']['domain_name']) > 0) {
													echo "    		<select style='width: 150px;' class='formfld' name='domain_name'>\n";
													echo "    			<option value=''></option>\n";
													foreach ($_SESSION['login']['domain_name'] as &$row) {
														echo "    		<option value='$row'>$row</option>\n";
													}
													echo "    		</select>\n";
												}
												else {
													echo "  		<input type='text' style='min-width: 150px; width: 150px;' class='formfld' name='domain_name'>\n";
												}
												echo "			</td>\n";
											}

											echo "				<td align='right'>\n";
											echo "  				<input type='submit' class='btn' style='margin-left: 5px;' value=\"".$text['button-login']."\">\n";
											echo "				</td>\n";
											echo "			</tr>\n";
											echo "		</table>\n";
											echo "	</form>";
											echo "</div>";
										}
									}
									?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign='top' align='center'>
						<table cellpadding='0' cellspacing='0' border='0' align='center' width='100%'>
							<tr>
								<td id='main_content' valign='top' align='center'>
									<!--{body}-->
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<div id='footer' style='width: 100%; margin-bottom: 60px;'><span class='footer'>&copy; Copyright 2008 - <?php echo date("Y"); ?> <a href='http://www.fusionpbx.com' class='footer' target='_blank'>fusionpbx.com</a>. All rights reserved.</span></div>
		</div>

		<?php
	}

	// default login being used
	else {
		$logo = (isset($_SESSION['theme']['logo']['text'])) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH."/themes/default/images/logo.png";
		?>
		<div id="main_content" style='position: absolute; top: 0; left: 0; right: 0; bottom: 0; padding: 0;'>
			<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>
				<tr>
					<td align='center' valign='middle'>
						<span id='default_login'>
							<a href='<?php echo PROJECT_PATH; ?>/'><img src='<?php echo $logo; ?>' width='250'></a><br />
							<!--{body}-->
						</span>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
	?>

</body>
</html>
