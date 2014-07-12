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
?>
<link rel="icon" href="<!--{project_path}-->/themes/enhanced/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type='text/css'>

html, body {
	margin: 0;
	padding: 0;
	margin-top: 0;
	margin-bottom: 0;
	margin-right: 0;
	margin-left: 0;
	overflow: hidden;
}

DIV#page {
	z-index: 1;
	position: absolute;
	top: 0px;
	left: 0px;
	right: 0px;
	bottom: 0px;
	padding: 10px;
	overflow: auto;
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
	font-size: 13px;
	font-family: arial;
	padding-top: 4px;
	padding-bottom: 4px;
	padding-right: 7px;
	padding-left: 0px;
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
	padding-left: 3px;
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

form {
	margin: 0px;
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
	opacity: 0.9;
	-moz-opacity: 0.9;
	}

input.btn:hover, input.button:hover, img.list_control_icon:hover {
	box-shadow: 0px 0px 5px #cddaf0;
	-webkit-box-shadow: 0px 0px 5px #cddaf0;
	-moz-box-shadow: 0px 0px 5px #cddaf0;
	opacity: 1.0;
	-moz-opacity: 1.0;
	}

input.txt, textarea.txt, select.txt, .formfld {
	font-family: arial;
	font-size: 12px;
	color: #000;
	text-align: left;
	padding: 5px;
	border: 1px solid #c0c0c0;
	background-color: #fff;
	box-shadow: 0px 0px 3px #cddaf0 inset;
	-moz-box-shadow: 0px 0px 3px #cddaf0 inset;
	-webkit-box-shadow: 0px 0px 3px #cddaf0 inset;
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
	-webkit-box-shadow: 0px 0px 5px #cddaf0;
	-moz-box-shadow: 0px 0px 5px #cddaf0;
	box-shadow: 0px 0px 5px #cddaf0;
	}

select.formfld {
	height: 27px;
	padding: 4px;
	}

.formfld_highlight_bad {
	border-color: #aa2525;
	-webkit-box-shadow: 0px 0px 3px #aa2525 inset;
	-moz-box-shadow: 0px 0px 3px #aa2525 inset;
	box-shadow: 0px 0px 3px #aa2525 inset;
	}

.formfld_highlight_good {
	border-color: #2fb22f;
	-webkit-box-shadow: 0px 0px 3px #2fb22f inset;
	-moz-box-shadow: 0px 0px 3px #2fb22f inset;
	box-shadow: 0px 0px 3px #2fb22f inset;
	}

input.txt {
	width: 98.75%;
	}

<!-- disables text input clear 'x' in IE 10+, slows down autosizeInput jquery script -->
input[type=text]::-ms-clear {
    display: none;
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

.row_stylebg {
	border-bottom: 1px solid #b9c5d8;
	background-color: #f0f2f6;
	color: #000;
	text-align: left;
	padding: 5px 7px;
}

.border {
	border: solid 1px #a4aebf;
	/*background-color: #FFFFFF;*/
}

.headermain {
	/*background-color: #7FAEDE;*/
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
	/*background:#ccc;*/
	/*margin:20px;*/
	/*border:#ccc 1px solid;*/
}

table th {
	padding:4px 7px
}

table td {
	/*background:#fff;*/
	/*padding:2px 10px 4px 10px*/
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
	margin: 0px;
}

legend {
	font-size: 13px;
	font-family: arial;
	font-weight: bold;
	color: #3164ad;
	padding-bottom: 8px;
	padding-right: 2px;
}

/* begin the menu css*/

	.menu_bar {
		background-image: url('<!--{project_path}-->/themes/enhanced/images/background_black.png');
		-webkit-border-radius: 4px 4px 4px 4px;
		-moz-border-radius: 4px 4px 4px 4px;
		border-radius: 4px 4px 4px 4px;
		padding: 4px;
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
		/*background-color: #FFFFFF;*/

		opacity: 0.9;
		filter:alpha(opacity=90);
		-moz-opacity:0.9;
		-khtml-opacity: 0.9;
		opacity: 0.9;

		-webkit-border-radius: 3px 3px 3px 3px;
		-moz-border-radius: 3px 3px 3px 3px;
		border-radius: 3px 3px 3px 3px;
		text-align: left;
		padding-top: 15px;
		padding-bottom: 25px;
		padding-left: 5px;
		padding-right:20px;
	}

	.main_content {
		<?php
			if ($browser_name == "Internet Explorer" && $browser_version_array[0] < '10' ) {
				echo "background-color: #FFFFFF;";
			}
			else {
				if (strlen($_SESSION["username"]) > 0) {
					echo "background-image: url('<!--{project_path}-->/themes/enhanced/images/content_background.png');";
				}
			}
		?>
		background-repeat: repeat-x;
		background-attachment: fixed;
		padding: 20px;
		opacity: 0.9;
		filter:alpha(opacity=90);
		-moz-opacity:0.9;
		-khtml-opacity: 0.9;
		opacity: 0.9;
		-webkit-border-radius: 3px 3px 3px 3px;
		-moz-border-radius: 3px 3px 3px 3px;
		border-radius: 3px 3px 3px 3px;
		text-align: left;
	}

	#menu{
		width:100%;
		float:left;
	}

	#menu a, #menu h2{
		font:bold 11px/16px arial,helvetica,sans-serif;
		display:block;
		/*border-color:#ccc #888 #555 #bbb;*/
		white-space:nowrap;
		margin:0;
		padding:3px 3px 3px 3px;
	}

	#menu h2{
		/*background:#222222 url(<!--{project_path}-->/css/images/expand3.gif) no-repeat 100% 100%;*/
		/*text-transform:uppercase*/
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
		/*background:#4e4b56 url(<!--{project_path}-->/css/images/expand3.gif) no-repeat 100% 100%;*/
		/*text-transform:uppercase*/
		padding:3px 3px 3px 3px;
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
		padding-top:10px;
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
		-webkit-border-radius: 0px 0px 3px 3px;
		-moz-border-radius: 0px 0px 3px 3px;
		border-radius: 0px 0px 3px 3px;
	}

	#menu a:hover{
		width:114px;
		color:#fd9c03;
		background:#1F1F1F;
		-webkit-border-radius: 3px 3px 3px 3px;
		-moz-border-radius: 3px 3px 3px 3px;
		border-radius: 3px 3px 3px 3px;
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
		-webkit-border-radius: 3px 3px 3px 3px;
		-moz-border-radius: 3px 3px 3px 3px;
		border-radius: 3px 3px 3px 3px;
		/*background:#1F1F1F url(<!--{project_path}-->/css/images/expand3.gif) no-repeat -999px -9999px;*/
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
		/*background:#a4aebf url(<!--{project_path}-->/css/images/expand3.gif) no-repeat 100% 100%;*/
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
		z-index: 99999;
		position: absolute;
		left: 0px;
		top: 0px;
		right: 0px;
		filter: alpha(opacity=0);
		opacity: 0;
		-moz-opacity:0;
		-khtml-opacity: 0;
	}

	#message_block {
		margin: 0px auto;
		width: 300px;
		height: auto;
		background-color: #000;
		background-repeat: repeat-x;
		background-image: url('<?php echo PROJECT_PATH; ?>/themes/enhanced/images/background_black.png');
		background-position: top center;
		padding: 6px 0px 8px 0px;
		-webkit-border-radius: 0px 0px 3px 3px;
		-moz-border-radius: 0px 0px 3px 3px;
		border-radius: 0px 0px 3px 3px;
		text-align: center;
	}

	#message_block .text {
		font-family: arial, san-serif;
		font-size: 10pt;
		font-weight: bold;
		color: #fff;
	}


	#domains_show_icon {
		filter: alpha(opacity=50);
		opacity: 0.5;
		-moz-opacity: 0.5;
		-khtml-opacity: 0.5;
		margin-left: 20px;
	}

	#domains_show_icon:hover {
		filter: alpha(opacity=100);
		opacity: 1;
		-moz-opacity: 1;
		-khtml-opacity: 1;
		cursor: pointer;
	}

	#domains_container {
		z-index: 99998;
		position: absolute;
		right: 0px;
		top: 0px;
		bottom: 0px;
		width: 400px;
		overflow: hidden;
		display: none;
	}

	#domains_block {
		position: absolute;
		right: -300px;
		top: 0px;
		bottom: 0px;
		width: 300px;
		padding: 20px 20px 100px 20px;
		font-family: arial, san-serif;
		font-size: 10pt;
		overflow: hidden;
		background-color: #fff;
		-webkit-box-shadow: 0px 0px 5px #888;
		-moz-box-shadow: 0px 0px 5px #888;
		box-shadow: 0px 0px 5px #888;
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
		-webkit-border-radius: 3px 3px 3px 3px;
		-moz-border-radius: 3px 3px 3px 3px;
		border-radius: 3px 3px 3px 3px;
		padding: 20px;
		}

</style>

<script type="text/javascript">
	<!--
		function jsconfirm(title,msg,url) {
			if (confirm(msg)){
				window.location = url;
			}
			else{
			}
		}
	//-->
</script>

<SCRIPT language="JavaScript">
	<!--
		function confirmdelete(url) {
			var confirmed = confirm("Are you sure want to delete this.");
			if (confirmed == true) {
				window.location=url;
			}
		}
	//-->
</SCRIPT>

<script language="javascript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-1.8.3.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery.autosize.input.js"></script>

<script language="JavaScript" type="text/javascript">
	function display_message() {
		$(document).ready(function() {
			$("#message_container").animate({ opacity: 0.9 }, "fast").delay(1750).animate({marginTop: '-=200'}, 1000);
		});
	}
</script>

<script language="JavaScript" type="text/javascript">
	$(document).ready(function() {

		$("#domains_show_icon, #domains_show_text").click(function() {
			$("#domains_container").show();
			$("#domains_block").animate({marginRight: '+=300'}, 400);
			$("#domain_filter").focus();
		});

		$("#domains_hide").click(function() {
			$("#domains_block").animate({marginRight: '-=300'}, 400, function() {
				$("#domain_filter").val('');
				domain_search($("#domain_filter").val());
				$("#domains_container").hide();
			});

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

	function hide_domains() {
		$(document).ready(function() {
			$("#domains_block").animate({marginRight: '-=300'}, 400, function() {
				$("#domains_container").hide();
			});
		});
	}

</script>

<script language="JavaScript" type="text/javascript">
	// applies the auto-size jquery script to all text inputs
	$(document).ready(function() {
		$("input.txt, textarea.txt, .formfld").autosizeInput();
	});
</script>

</head>
<body onload="display_message();">

	<?php
	// message block
	if (strlen($_SESSION['message']) > 0) {
		echo "<div id='message_container'>";
		echo "	<div id='message_block'>";
		echo "		<span class='text'>".$_SESSION['message']."</span>";
		echo "	</div>";
		echo "</div>";
		unset($_SESSION['message']);
	}

	//logged in show the domains block
	if (strlen($_SESSION["username"]) > 0 && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {

		//add multi-lingual support
		require_once "themes/enhanced/app_languages.php";
		foreach($text as $key => $value) {
			$text[$key] = $value[$_SESSION['domain']['language']['code']];
		}

		?>
		<div id="domains_container">
			<div id="domains_block">
				<div id="domains_header">
					<input id="domains_hide" type="button" class="btn" style="float: right" value="<?php echo $text['theme-button-close']; ?>">
					<b style="color: #000;"><?php echo $text['theme-title-domains']; ?></b>
					<br><br>
					<input type="text" id="domain_filter" class="formfld" style="min-width: 100%; width: 100%;" placeholder="<?php echo $text['theme-label-search']; ?>" onkeyup="domain_search(this.value);">
				</div>
				<div id="domains_list">
					<?php
					$bgcolor1 = "#eaedf2";
					$bgcolor2 = "#fff";
					foreach($_SESSION['domains'] as $domain) {
						if ($domain['domain_uuid'] != $_SESSION['domain_uuid']) {
							$bgcolor = ($bgcolor == $bgcolor1) ? $bgcolor2 : $bgcolor1;
							echo "<div id=\"".$domain['domain_name']."\" class=\"domains_list_item\" style=\"background-color: ".$bgcolor."\" onclick=\"document.location.href='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".$domain['domain_uuid']."&domain_change=true';\">";
							echo "<a href=\"".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid=".$domain['domain_uuid']."&domain_change=true\">".$domain['domain_name']."</a>\n";
							if ($domain['domain_description'] != '') {
								echo "<span class=\"domain_list_item_description\"> - ".$domain['domain_description']."</span>\n";
							}
							echo "</div>\n";
							$ary_domain_names[] = $domain['domain_name'];
							$ary_domain_descs[] = str_replace('"','\"',$domain['domain_description']);
						}
					}
					?>
				</div>

				<script>
					var domain_names = new Array("<?=implode('","', $ary_domain_names)?>");
					var domain_descs = new Array("<?=implode('","', $ary_domain_descs)?>");

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

	<div id="page" align='center'>
	<table width='90%' class='border.disabled' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td align='left' valign='top' class='headermain' colspan='2' width='100%' height='70px;'>
				<table border='0' cellpadding='0' cellspacing='0' width='100%'>
					<tr>
						<td>
							<?php
							if ($_SERVER['PHP_SELF'] != PROJECT_PATH."/resources/install.php") {
								if (strlen(PROJECT_PATH) > 0) {
									echo "<a href='".PROJECT_PATH."'><img src='".PROJECT_PATH."/themes/enhanced/images/logo.png' /></a>";
								}
								else {
									echo "<a href='/'><img src='/themes/enhanced/images/logo.png' /></a>";
								}
							}
							?>
						</td>
						<td width='100%' style='padding-right: 15px;' align='right' valign='middle'>
							<?php
							if ($_SESSION['username'] != '') {
								echo "<span style='white-space: nowrap;'>";
								echo "	<span style='color: black; font-size: 10px; font-weight: bold;'>".$text['theme-label-user']."</span>&nbsp;";
								echo "	<a href='/core/user_settings/user_dashboard.php'>";
								echo $_SESSION['username'];
								if (count($_SESSION['domains']) > 1) {
									echo "@".$_SESSION["user_context"];
								}
								echo 	"</a>";
								echo "</span>\n";
							}

							//logged in show the domains block
							if (strlen($_SESSION["username"]) > 0 && permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
								echo "<span style='white-space: nowrap; line-height: 45px;'>";
								echo "	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='color: black; font-size: 10px; font-weight: bold;'>".$text['theme-label-domain']."</span>&nbsp;";
								echo "	<a href='javascript:void(0);' id='domains_show_text'>".$_SESSION['domain_name']."</a>";
								echo "	<img id='domains_show_icon' src='".PROJECT_PATH."/themes/enhanced/images/icon_domains_show.png' style='width: 23px; height: 16px; border: none;' title='".$text['theme-label-open_selector']."' align='absmiddle'>";
								echo "</span>";
							}

							//logged out show the login
								if ($_SERVER['PHP_SELF'] != PROJECT_PATH."/resources/install.php") {
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
											echo "		  			<input type='text' class='formfld' style='min-width: 105px; width: 105px; text-align: center;' name='username' placeholder=\"".$text['label-username']."\">\n";
											echo "				</td>\n";
											echo "				<td align='left'>\n";
											echo "					<input type='password' class='formfld' style='min-width: 105px; width: 105px; text-align: center;' name='password' placeholder=\"".$text['label-password']."\">\n";
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
													echo "  		<input type='text' style='width: 150px;' class='formfld' name='domain_name'>\n";
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
				<td class='' colspan='2' width='100%' height='7px'><img src='<!--{project_path}-->/themes/enhanced/images/blank.gif'></td>
			</tr>
			<tr>
				<td class='menu_bar' colspan='2' width='100%' height='30px'>
					<!--{menu}-->
				</td>
			</tr>
			<tr>
				<td class='' colspan='2' width='100%' height='7px'><img src='<!--{project_path}-->/themes/enhanced/images/blank.gif'></td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td valign='top' align='center' width='100%'>
				<table width='100%' cellpadding='0' cellspacing='0' border='0'>
					<tr>
						<td class='main_content' align='left' valign='top' width='85%'>
							<!--{body}-->

							<br /><br />
							<br /><br />
							<br /><br />
							<br /><br />
							<br /><br />
							<br /><br />
							<br /><br />
							<br /><br />

						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<?php
	if (substr($_SERVER['PHP_SELF'], -9) != "login.php") {
		echo "<span class='smalltext'>\n";
		echo "	<a class='smalltext' target='_blank' href='http://www.fusionpbx.com'>fusionpbx.com</a>. Copyright 2008 - ".date("Y").". All rights reserved.\n";
		echo "</span><br><br>\n";
	}
	else {
		echo "<!--\n";
		echo "	http://www.fusionpbx.com \n";
		echo "	Copyright 2008 - ".date("Y")." \n";
		echo "	All rights reserved.\n";
		echo "-->\n";
	}
	?>
	</div>

<br />
</body>
</html>
