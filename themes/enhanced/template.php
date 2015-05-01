<?php
//get the browser version
	$user_agent = http_user_agent();
	$browser_version =  $user_agent['version'];
	$browser_name =  $user_agent['name'];
	$browser_version_array = explode('.', $browser_version);

//set the doctype
	if ($browser_name == "Internet Explorer") {
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	}
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title><!--{title}--></title>
<!--{head}-->
<?php
//get the browser version
	$user_agent = http_user_agent();
	$browser_version =  $user_agent['version'];
	$browser_name =  $user_agent['name'];
	$browser_version_array = explode('.', $browser_version);

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
	if (isset($_SESSION['theme']['favicon']['text'])){
		$favicon = $_SESSION['theme']['favicon']['text'];
	}
	else {
		$favicon = '<!--{project_path}-->/themes/enhanced/favicon.ico';
        }
?>
<link rel="icon" href="<?php echo $favicon; ?>">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type='text/css'>

html, body {
	margin: 0;
	padding: 0;
	overflow: hidden;
}

DIV#page {
	z-index: 1;
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 0;
	overflow: auto;
}

<?php
if (
	isset($_SESSION['theme']['background_image']) ||
	$_SESSION['theme']['background_color'][0] != '' ||
	$_SESSION['theme']['background_color'][1] != ''
	) { ?>
	/* Set the position and dimensions of the background image. */
	DIV#page-background {
		z-index: 0;
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
	}

	<?php
	if (
		$_SESSION['theme']['background_color'][0] != '' &&
		$_SESSION['theme']['background_color'][1] != ''
		) {
		?>
		.page-background-gradient {
			background-color: <?php echo $_SESSION['theme']['background_color'][0]?>;
			background-image: -ms-linear-gradient(top, <?php echo $_SESSION['theme']['background_color'][0]?> 0%, <?php echo $_SESSION['theme']['background_color'][1]?> 100%);
			background-image: -moz-linear-gradient(top , <?php echo $_SESSION['theme']['background_color'][0]?> 0%, <?php echo $_SESSION['theme']['background_color'][1]?> 100%);
			background-image: -o-linear-gradient(top , <?php echo $_SESSION['theme']['background_color'][0]?> 0%, <?php echo $_SESSION['theme']['background_color'][1]?> 100%);
			background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, <?php echo $_SESSION['theme']['background_color'][0]?>), color-stop(1, <?php echo $_SESSION['theme']['background_color'][1]?>));
			background-image: -webkit-linear-gradient(top , <?php echo $_SESSION['theme']['background_color'][0]?> 0%, <?php echo $_SESSION['theme']['background_color'][1]?> 100%);
			background-image: linear-gradient(to bottom, <?php echo $_SESSION['theme']['background_color'][0]?> 0%, <?php echo $_SESSION['theme']['background_color'][1]?> 100%);
		}
		<?php
	}
	?>
<?php } ?>

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
		background-color: <?php echo ($_SESSION['theme']['login_background_color']['text'] != '') ? $_SESSION['theme']['login_background_color']['text'] : "#fff"; ?>;
		background: rgba(<?php echo ($_SESSION['theme']['login_background_color']['text'] != '') ? hex2rgb($_SESSION['theme']['login_background_color']['text'], ',') : "255, 255, 255"; ?>, <?php echo ($_SESSION['theme']['login_opacity']['text'] != '') ? $_SESSION['theme']['login_opacity']['text'] : "1"; ?>);
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		border-radius: 4px;
		<?php
		if ($_SESSION['theme']['login_shadow_color']['text'] != '') {
			?>
			-webkit-box-shadow: 0 1px 20px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			-moz-box-shadow: 0 1px 20px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			box-shadow: 0 1px 20px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			<?php
		}
	}
	?>
}

A.login_box_link {
	font-size: 11px;
	text-shadow: 0 0 2px <?php echo ($_SESSION['theme']['login_background_color']['text'] != '') ? $_SESSION['theme']['login_background_color']['text'] : "#fff"; ?>;
	cursor: pointer;
	text-decoration: underline;
}

DIV#footer {
	background-color: <?php echo $_SESSION['theme']['footer_background_color']['text']; ?>;
	bottom: 0;
	left: 0;
	right: 0;
	height: 20px;
	<?php
	if ($_SESSION['theme']['footer_opacity']['text'] != '') {
		?>
		-khtml-opacity: <?php echo $_SESSION['theme']['footer_opacity']['text']; ?>;
		-moz-opacity: <?php echo $_SESSION['theme']['footer_opacity']['text']; ?>;
		filter: alpha(opacity=<?php echo (100 * (float) $_SESSION['theme']['footer_opacity']['text']); ?>);
		filter: progid:DXImageTransform.Microsoft.Alpha(opacity=<?php echo $_SESSION['theme']['footer_opacity']['text']; ?>);
		opacity: <?php echo $_SESSION['theme']['footer_opacity']['text']; ?>;
		<?php
	}
	?>
	text-align: center;
	vertical-align: middle;
	padding-bottom: 0;
	padding-top: 8px;
}

.footer {
	font-size: 11px;
	font-family: arial;
	color: <?php echo $_SESSION['theme']['footer_color']['text']; ?>;
}

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
	padding: 2px 6px 3px 6px;
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

input.btn:hover, input.button:hover, img.list_control_icon:hover {
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
	padding: 5px;
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

/* removes spinners (increment/decrement controls) inside input fields */
input[type=number] { -moz-appearance: textfield; }
::-webkit-inner-spin-button { -webkit-appearance: none; }
::-webkit-outer-spin-button { -webkit-appearance: none; }

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

<!-- disables text input clear 'x' in IE 10+, slows down autosizeInput jquery script -->
input[type=text]::-ms-clear {
    display: none;
}

input.fileinput {
	padding: 1px;
	}

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
	padding: 5px 7px;
}

.row_style1 {
	border-bottom: 1px solid #c5d1e5;
	background-color: #fff;
	color: #000;
	text-align: left;
	padding: 5px 7px;
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
	padding: 5px 7px;
}

.border {
	border: solid 1px #a4aebf;
}


.frm {
	border: solid 1px #CCCCCC;
	color: #666666;
	background-color: #EFEFEF;

}

.smalltext {
	color: #BBBBBB;
	font-size: 11px;
	font-family: arial;
}

table {

}

table th {
	padding:4px 7px
}

table td {

}

table tr.even td {
	background:#eee;
	background-image: url('<!--{project_path}-->/themes/enhanced/images/background_cell.gif');
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

#main_content {
	<?php
	if (
		strlen($_SESSION["username"]) > 0 &&
		(
			isset($_SESSION['theme']['background_image']) ||
			$_SESSION['theme']['background_color'][0] != '' ||
			$_SESSION['theme']['background_color'][1] != ''
		)) { ?>
		background-color: #FFFFFF;
		background-attachment: fixed;
		<?php
		if ($_SESSION['theme']['body_opacity']['text'] != '') {
			?>
			opacity: <?php echo $_SESSION['theme']['body_opacity']['text']?>;
			filter:alpha(opacity=<?php echo (100 * (float) $_SESSION['theme']['body_opacity']['text'])?>);
			-moz-opacity: <?php echo $_SESSION['theme']['body_opacity']['text']?>;
			-khtml-opacity: <?php echo $_SESSION['theme']['body_opacity']['text']?>;
			<?php
		}
		?>
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		border-radius: 4px;
		<?php
		if ($_SESSION['theme']['login_shadow_color']['text'] != '') {
			?>
			-webkit-box-shadow: 0 1px 4px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			-moz-box-shadow: 0 1px 4px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			box-shadow: 0 1px 4px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			<?php
		}
		?>
		padding: 20px;
	<?php } else { ?>
		padding: 10px;
	<?php } ?>
	text-align: left;
}

/* begin the menu css*/

	.menu_bar {
		background-image: url('<!--{project_path}-->/themes/enhanced/images/background_black.png');
		background-position: 0px -1px;
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		border-radius: 4px;
		padding: 4px;
		<?php
		if ($_SESSION['theme']['login_shadow_color']['text'] != '') {
			?>
			-webkit-box-shadow: 0 1px 4px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			-moz-box-shadow: 0 1px 4px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			box-shadow: 0 1px 4px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			<?php
		}
		?>
	}

	.menu_bg {
		<?php
			if ($browser_name == "Internet Explorer" && $browser_version_array[0] < '10' ) {
				echo "background-color: #FFFFFF;";
			}
			else {
				if (substr($_SERVER['PHP_SELF'], -9) != "login.php") {
					echo "background-image: url('<!--{project_path}-->/themes/enhanced/images/menu_background.png');";
				}
				else {
					echo "background-image: url('<!--{project_path}-->/themes/enhanced/images/login_background.png');";
				}
			}
		?>
		background-repeat: repeat-x;
		background-attachment: fixed;
		opacity: 0.9;
		filter:alpha(opacity=90);
		-moz-opacity:0.9;
		-khtml-opacity: 0.9;
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
		text-align: left;
		padding-top: 15px;
		padding-bottom: 25px;
		padding-left: 5px;
		padding-right:20px;
	}

	#menu{
		width:100%;
		float:left;
	}

	#menu a, #menu h2{
		font:bold 11px/16px arial,helvetica,sans-serif;
		display:block;
		white-space:nowrap;
		margin:0;
		padding: 3px;
	}

	#menu h2{
		color:#FFFFFF;
		<?php
		if ($_SESSION['domain']['language']['code'] == "en-us") {
			echo "width:125px;\n";
		}
		if ($_SESSION['domain']['language']['code'] == "es-cl") {
			echo "width:175px;\n";
		}
		if ($_SESSION['domain']['language']['code'] == "fr-fr") {
			echo "width:140px;\n";
		}
		if ($_SESSION['domain']['language']['code'] == "pt-pt") {
			echo "width:175px;\n";
		}
		?>
	}

	#menu h2 h2{
		padding: 3px;
	}

	#menu a{
		<?php
		if ($browser_name == "Internet Explorer" && $browser_version_array[0] < '10' ) {
			echo "background:#333333;";
		}
		?>
		text-decoration:none;
		padding-left:7px;
		width:114px;
	}

	#menu a, #menu a:visited{
		color:#fff;
	}

	#menu .menu_sub {
		display:none;
		padding: 5px 0px 8px 0px;
		<?php
		if ($_SESSION['domain']['language']['code'] == "en-us") {
			echo "width:125px;\n";
		}
		if ($_SESSION['domain']['language']['code'] == "es-cl") {
			echo "width:175px;\n";
		}
		if ($_SESSION['domain']['language']['code'] == "fr-fr") {
			echo "width:140px;\n";
		}
		if ($_SESSION['domain']['language']['code'] == "pt-pt") {
			echo "width:175px;\n";
		}
		?>
		background:#333333;
		background-color: rgba(20, 20, 20, 0.9);
		-webkit-border-radius: 0 0 3px 3px;
		-moz-border-radius: 0 0 3px 3px;
		border-radius: 0 0 3px 3px;
		<?php
		if ($_SESSION['theme']['login_shadow_color']['text'] != '') {
			?>
			-webkit-box-shadow: 0 2px 3px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			-moz-box-shadow: 0 2px 3px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			box-shadow: 0 2px 3px <?php echo $_SESSION['theme']['login_shadow_color']['text']?>;
			<?php
		}
		?>
	}

	#menu a:hover{
		width:114px;
		color:#fd9c03;
		background:#1F1F1F;
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
	}

	#menu a:active{
		color:#fd9c03;
	}

	#menu ul{
		list-style:none;
		margin:0;
		padding:0;
		float:left;
		width:9em;
	}

	#menu li{
		position:relative;
	}

	#menu ul ul{
		position:absolute;
		z-index:500;
		top:auto;
		display:none;
	}

	#menu ul ul ul{
		top:0;
		left:100%;
	}

	/* Enter the more specific element (div) selector
	on non-anchor hovers for IE5.x to comply with the
	older version of csshover.htc - V1.21.041022. It
	improves IE's performance speed to use the older
	file and this method */

	div#menu h2:hover{
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
	}

	div#menu li:hover{
		cursor:pointer;
		z-index:100;
	}

	div#menu li:hover ul ul,
	div#menu li li:hover ul ul,
	div#menu li li li:hover ul ul,
	div#menu li li li li:hover ul ul
	{display:none;}

	div#menu li:hover ul,
	div#menu li li:hover ul,
	div#menu li li li:hover ul,
	div#menu li li li li:hover ul
	{display:block;}

	#menu a.x, #menu a.x:visited{
		font-weight:bold;
		color:#000;
	}

	#menu a.x:hover{
		color:#fff;
		background:#000;
	}

	#menu a.x:active{
		color:#060;
		background:#ccc;
	}

/* end the menu css*/

	#message_container {
		z-index: 99998;
		position: absolute;
		top: -200px;
		left: 0;
		right: 0;
		height: 30px;
		filter: alpha(opacity=0);
		opacity: 0;
		-moz-opacity:0;
		-khtml-opacity: 0;
		padding: 8px 0;
	}

	#message_text {
		z-index: 99999;
		position: absolute;
		top: -200px;
		left: 0;
		right: 0;
		filter: alpha(opacity=0);
		opacity: 0;
		-moz-opacity:0;
		-khtml-opacity: 0;
		margin: 0 auto;
		vertical-align: middle;
		padding: 8px 0;
		text-align: center;
		font-family: arial, san-serif;
		font-size: 10pt;
	}

	.message_container_mood_default {
		background-color: <?php echo $_SESSION['theme']['message_default_background_color']['text']; ?>;
	}

	.message_container_mood_negative {
		background-color: <?php echo $_SESSION['theme']['message_negative_background_color']['text']; ?>;
	}

	.message_container_mood_alert {
		background-color: <?php echo $_SESSION['theme']['message_alert_background_color']['text']; ?>;
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

	#header_icons {
		display: inline-block;
		margin-top: 10px;
		}

	#logout_icon {
		filter: alpha(opacity=80);
		opacity: 0.8;
		-moz-opacity: 0.8;
		-khtml-opacity: 0.8;
		margin-left: 6px;
	}

	#logout_icon:hover {
		filter: alpha(opacity=100);
		opacity: 1;
		-moz-opacity: 1;
		-khtml-opacity: 1;
		cursor: pointer;
	}

	#domain_selector_icon {
		filter: alpha(opacity=80);
		opacity: 0.8;
		-moz-opacity: 0.8;
		-khtml-opacity: 0.8;
		padding-left: 10px;
	}

	#domain_selector_icon:hover {
		filter: alpha(opacity=100);
		opacity: 1;
		-moz-opacity: 1;
		-khtml-opacity: 1;
		cursor: pointer;
	}

	#domain_selector_domain {
		display: <?php echo ($_SESSION['theme']['domain_visible']['text'] != 'true') ? 'none' : 'inline-block'; ?>;
		white-space: nowrap;
		padding: 2px <?php echo ($_SESSION['theme']['domain_background_opacity']['text'] != '' && $_SESSION['theme']['domain_background_opacity']['text'] != 0) ? 7 : 0; ?>px 1px 7px;
		margin-top: -1px;
		background-color: rgba(<?php echo hex2rgb($_SESSION['theme']['domain_background_color']['text'],','); ?>, <?php echo ($_SESSION['theme']['domain_background_opacity']['text'] != '') ? $_SESSION['theme']['domain_background_opacity']['text'] : 0; ?>);
		-webkit-border-radius: 1px;
		-moz-border-radius: 1px;
		border-radius: 1px;
		font-size: 12px;
		color: <?php echo ($_SESSION['theme']['domain_color']['text'] != '') ? $_SESSION['theme']['domain_color']['text'] : '#000'; ?>;
		<?php echo ($_SESSION['theme']['domain_shadow_color']['text'] != '') ? 'text-shadow: 0 0 2px '.$_SESSION['theme']['domain_shadow_color']['text'].';' : null; ?>
	}

	#domain_selector_domain:hover {
		cursor: pointer;
	}

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
		width: 300px;
		padding: 20px 20px 100px 20px;
		font-family: arial, san-serif;
		font-size: 10pt;
		overflow: hidden;
		background-color: #fff;
		-webkit-box-shadow: 0 0 10px <?php echo ($_SESSION['theme']['login_shadow_color']['text'] != '') ? $_SESSION['theme']['login_shadow_color']['text'] : "#888"; ?>;
		-moz-box-shadow: 0 0 10px <?php echo ($_SESSION['theme']['login_shadow_color']['text'] != '') ? $_SESSION['theme']['login_shadow_color']['text'] : "#888"; ?>;
		box-shadow: 0 0 10px <?php echo ($_SESSION['theme']['login_shadow_color']['text'] != '') ? $_SESSION['theme']['login_shadow_color']['text'] : "#888"; ?>;
	}

	#domains_header {
		position: relative;
		width: 300px;
		height: 55px;
		margin-bottom: 20px;
	}

	#domains_list {
		position: relative;
		overflow: auto;
		width: 296px;
		height: 100%;
		padding: 1px;
		background-color: #fff;
		border: 1px solid #a4aebf;
	}

	DIV.domains_list_item {
		border-bottom: 1px solid #c5d1e5;
		padding: 5px 8px 8px 8px;
		overflow: hidden;
		white-space: nowrap;
		cursor: pointer;
		}

	DIV.domains_list_item SPAN.domain_list_item_description {
		color: #999;
		font-size: 11px;
		}

	DIV.domains_list_item:hover A,
	DIV.domains_list_item:hover SPAN {
		color: #5082ca;
		}

	DIV.login_message {
		border: 1px solid #bae0ba;
		background-color: #eeffee;
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
		padding: 20px;
		}

/* operator panel styles begin */

	DIV.op_ext {
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

	DIV.op_state_active {
		background-color: #baf4bb;
		border-width: 1px 3px;
		border-color: #77d779;
		}

	DIV.op_state_ringing {
		background-color: #a8dbf0;
		border-width: 1px 3px;
		border-color: #41b9eb;
		}

	TABLE.op_ext {
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

	TD.op_ext_icon {
		vertical-align: middle;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		}

	IMG.op_ext_icon {
		cursor: move;
		width: 39px;
		height: 42px;
		border: none;
		}

	TD.op_ext_info {
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

	TD.op_state_ringing {
		background-color: #d1f1ff;
		}

	TD.op_state_active {
		background-color: #e1ffe2;
		}

	TABLE.op_state_ringing {
		background-color: #a8dbf0;
		}

	TABLE.op_state_active {
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

/* operator panel styles end */

SPAN.playback_progress_bar {
	background-color: #c43e42;
	height: 1px;
	display: inline-block;
	}

</style>

<?php if (substr_count($_SERVER["PHP_SELF"], "xml_cdr_statistics.php") == 0) { ?>
	<!-- // javascript calendar and color picker (source: http://rightjs.org) -->
	<script language="JavaScript" type='text/javascript' src='<?php echo PROJECT_PATH; ?>/resources/rightjs/right.js'></script>
	<script language="JavaScript" type='text/javascript' src='<?php echo PROJECT_PATH; ?>/resources/rightjs/right-calendar-src.js'></script>
	<script language="JavaScript" type='text/javascript' src='<?php echo PROJECT_PATH; ?>/resources/rightjs/right-colorpicker-src.js'></script>
<?php } ?>

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-1.11.1.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery.autosize.input.js"></script>

<script language="JavaScript" type="text/javascript">
	$(document).ready(function() {

		$("#domain_selector_domain").click(function() { show_domains(); });
		$("#domain_selector_icon").click(function() { show_domains(); });
		$("#domains_hide").click(function() { hide_domains(); });

		function show_domains() {
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
					$("#domains_container").hide();
				});
			});
			document.getElementById('domains_visible').value = 0;
		}

		// hit escape to toggle visibility of domain selector
		$(document).keyup(function(e) {
			if (e.keyCode == 27 && document.getElementById('domains_visible').value == 0) {
				show_domains();
			}
			else if (e.keyCode == 27 && document.getElementById('domains_visible').value == 1) {
				hide_domains();
			}
		});

		// linkify rows (except the last - the list_control_icons cell)
		// on a table with a class of 'tr_hover', according to the href
		// attribute of the <tr> tag
		$('.tr_hover tr').each(function(i,e) {
		  $(e).children('td:not(.list_control_icon,.list_control_icons,.tr_link_void)').click(function() {
			 var href = $(this).closest("tr").attr("href");
			 if (href) { window.location = href; }
		  });
		});

	});

</script>

<script language="JavaScript" type="text/javascript">
	// applies the auto-size jquery script to all text inputs
	$(document).ready(function() {
		$("input.txt, textarea.txt, .formfld").autosizeInput();
	});
</script>

<script language="JavaScript" type="text/javascript">
	function display_message(msg, mood, delay) {
		mood = typeof mood !== 'undefined' ? mood : 'default';
		delay = typeof delay !== 'undefined' ? delay : <?php echo (1000 * (float) $_SESSION['theme']['message_delay']['text']); ?>;
		if (msg != '') {
			// insert temp div to get width w/o scroll bar
			var helper_div = $('<div />');
			$('#page').append(helper_div);
			inner_width = helper_div.width();
			helper_div.remove();
			// add class by mood
			$("#message_container").addClass('message_container_mood_'+mood);
			$("#message_text").addClass('message_text_mood_'+mood);
			// output message
			$("#message_text").html(msg);
			$("#message_container").css({height: $("#message_text").css("height")});
			$("#message_container").css({width: inner_width});
			$("#message_text").animate({top: '+=200'}, 0).animate({opacity: 1}, "fast").delay(delay).animate({top: '-=200'}, 1000).animate({opacity: 0});
			$("#message_container").animate({top: '+=200'}, 0).animate({opacity: <?php echo $_SESSION['theme']['message_opacity']['text']; ?>}, "fast").delay(delay).animate({top: '-=200'}, 1000).animate({opacity: 0}, function() {
				$("#message_container").removeClass('message_container_mood_'+mood);
			});
		}
	}
</script>

<script type='text/javascript'>
	// preload images
	img_play = new Image();	img_play.src = "<?php echo PROJECT_PATH; ?>/themes/enhanced/images/icon_play.png";
	img_pause = new Image(); img_pause.src = "<?php echo PROJECT_PATH; ?>/themes/enhanced/images/icon_pause.png";

	var recording_audio;

	function recording_play(recording_id) {
		if (document.getElementById('recording_progress_bar_'+recording_id)) {
			document.getElementById('recording_progress_bar_'+recording_id).style.display='';
		}
		recording_audio = document.getElementById('recording_audio_'+recording_id)

		if (recording_audio.paused) {
			recording_audio.play();
			recording_audio.volume = 1;
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
</script>

</head>

<?php
//add multi-lingual support
	$language = new text;
	$text = $language->get(null,'themes/enhanced');

// set message_onload
if (strlen($_SESSION['message']) > 0) {
	$message_text = addslashes($_SESSION['message']);
	$message_mood = $_SESSION['message_mood'];
	$message_delay = $_SESSION['message_delay'];

	$onload .= "display_message('".$message_text."'";
	$onload .= ($message_mood != '') ? ", '".$message_mood."'" : ", 'default'";
	if ($message_delay != '') {
		$onload .= ", '".$message_delay."'";
	}
	$onload .= "); ";
	unset($_SESSION['message'], $_SESSION['message_mood'], $_SESSION['message_delay']);
}
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
					<b style="color: #000;"><?php echo $text['theme-title-domains']; ?></b> (<?php echo sizeof($_SESSION['domains']); ?>)
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
	// check for background image
	if (isset($_SESSION['theme']['background_image'])) {
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
				$source_path = PROJECT_PATH.'/themes/enhanced/images/backgrounds/'.$background_image;
			}

		}
		else {
			// not set, so use default backgrounds folder and images
			$image_source = 'folder';
			$source_path = PROJECT_PATH.'/themes/enhanced/images/backgrounds';
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

		//show the background
		if ($_SESSION['background_image'] != '') {
			echo "<div id='page-background'><img src=\"".$_SESSION['background_image']."\" width='100%' height='100%'></div>\n";
		}

	}

	// check for background color
	else if (
		$_SESSION['theme']['background_color'][0] != '' ||
		$_SESSION['theme']['background_color'][1] != ''
		) { // background color 1 or 2 is enabled

		echo "bg1 = ".$_SESSION['theme']['background_color'][0]."<br><br>";
		echo "bg2 = ".$_SESSION['theme']['background_color'][1]."<br><br>";

		if ($_SESSION['theme']['background_color'][0] != '' && $_SESSION['theme']['background_color'][1] == '') { // use color 1
			echo "<div id='page-background' style='background-color: ".$_SESSION['theme']['background_color'][0].";'>&nbsp;</div>\n";
		}
		else if ($_SESSION['theme']['background_color'][0] == '' && $_SESSION['theme']['background_color'][1] != '') { // use color 2
			echo "<div id='page-background' style='background-color: ".$_SESSION['theme']['background_color'][1].";'>&nbsp;</div>\n";
		}
		else if ($_SESSION['theme']['background_color'][0] != '' && $_SESSION['theme']['background_color'][1] != '') { // vertical gradient
			echo "<div id='page-background' class='page-background-gradient'>&nbsp;</div>\n";
		}
		else { // default: white
			echo "<div id='page-background' style='background-color: #fff;'>&nbsp;</div>\n";
		}
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

	<div id="page" align='center'>
		<?php if (!$default_login) { ?>
			<table width='90%' border='0' cellpadding='0' cellspacing='0'>
				<tr>
					<td align='left' valign='top'>
						<table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-top: 10px;'>
							<tr>
								<td>
									<?php
									if ($_SERVER['PHP_SELF'] != PROJECT_PATH."/resources/install.php") {
										if (isset($_SESSION['theme']['logo']['text'])){
											$logo = $_SESSION['theme']['logo']['text'];
										}
										else {
											$logo = PROJECT_PATH."/themes/enhanced/images/logo.png";
										}
										if (strlen(PROJECT_PATH) > 0) {
											echo "<a href='".PROJECT_PATH."'><img src='$logo' /></a>";
										}
										else {
											if (!$default_login) {
												echo "<a href='/'><img src='$logo' /></a>";
											}
										}
									}
									?>
								</td>
								<td width='100%' style='padding-right: 15px;' align='right' valign='middle'>
									<?php
									echo "<span id='header_icons'>";

								//domain selector icon
									if ($_SESSION["username"] != '' && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
										echo "<span id='domain_selector_domain'>".$_SESSION['domain_name']."</span><img id='domain_selector_icon' src='".PROJECT_PATH."/themes/enhanced/images/icon_domain_selector.png' style='width: 28px; height: 23px; border: none;' title='".$_SESSION['domain_name']." &#10;".$text['theme-label-open_selector']."' align='absmiddle'>";
									}

								//logout icon
									if ($_SESSION['username'] != '') {
										$username_full = $_SESSION['username'].((count($_SESSION['domains']) > 1) ? "@".$_SESSION["user_context"] : null);
										echo "<a href='".PROJECT_PATH."/logout.php' onclick=\"return confirm('".$text['theme-confirm-logout']."');\"><img id='logout_icon' src='".PROJECT_PATH."/themes/enhanced/images/icon_logout.png' style='width: 28px; height: 23px; border: none;' title='".$text['theme-label-logout']." ".$username_full."' align='absmiddle'></a>";
										unset($username_full);
									}

									echo "</span>\n";

								// login form
									if ($_SERVER['PHP_SELF'] != PROJECT_PATH."/resources/install.php" && !$default_login) {
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
				<?php
				if (strlen($_SESSION["username"]) > 0) {
					?>
					<tr>
						<td height='9px'><img src='<!--{project_path}-->/themes/enhanced/images/blank.gif'></td>
					</tr>
					<tr>
						<td class='menu_bar' height='30px'>
							<!--{menu}-->
						</td>
					</tr>
					<tr>
						<td height='9px'><img src='<!--{project_path}-->/themes/enhanced/images/blank.gif'></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td valign='top' align='center' width='100%'>
						<table cellpadding='0' cellspacing='1' border='0' width='100%' style='margin-bottom: 60px;'>

							<tr>
								<td id='main_content' valign='top' align='center'>
									<!--{body}-->
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<?php
		}

		// default login being used
		else {
			$logo = (isset($_SESSION['theme']['logo']['text'])) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH."/themes/enhanced/images/logo.png";
			?>
			<div id="main_content" class='main_content' style='position: absolute; top: 0; left: 0; right: 0; bottom: 0; padding: 0;'>
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
	</div>

	<?php
	$footer .= "&copy; Copyright 2008 - ".date("Y")." <a href='http://www.fusionpbx.com' class='footer' target='_blank'>fusionpbx.com</a>. All rights reserved.\n";
	echo "<div id='footer' style='position: absolute; z-index; 10000;'><span class='footer'>".$footer."</span></div>\n";
	if (isset($_SESSION['theme']['bottom_html']['text'])){
		echo $_SESSION['theme']['bottom_html']['text'];
	}
	?>

</body>
</html>
