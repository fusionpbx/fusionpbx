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
<link rel="icon" href="<!--{project_path}-->/themes/classic/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type='text/css'>

img {
	border: none;
}

A {
	color: #004083;
	width: 100%;
}

body {
	margin-top: 0px;
	margin-bottom: 0px;
	margin-right: 0px;
	margin-left: 0px;
	background-image: url('<!--{project_path}-->/themes/classic/images/background.jpg');
	background-repeat: repeat-x;
	background-attachment: fixed;
	background-color: #FFFFFF;
}

th {
	border-top: 1px solid #444444;
	border-bottom: 1px solid #444444;
	text-align: left;
	color: #FFFFFF;
	font-size: 12px;
	font-family: arial;
	font-weight: bold;
	/*background-color: #506eab;*/
	background-image: url('<!--{project_path}-->/themes/classic/images/background_th.png');
	padding-top: 4px;
	padding-bottom: 4px;
	padding-right: 7px;
	padding-left: 7px;
}

th a:link{ color:#FFFFFF; }
th a:visited{ color:#FFFFFF; }
th a:hover{ color:#FFFFFF; }
th a:active{ color:#FFFFFF; }

td {
	color: #5f5f5f;
	font-size: 12px;
	font-family: arial;
}

form {
	margin: 0px;
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

INPUT.btn {
	font-family: verdana;
	font-size: 11px;
}

INPUT.button {
	font-family: verdana;
	font-size: 11px;
}

SELECT.txt {
	font-family: arial;
	font-size: 12px;
	width: 98.75%;
	border: solid 1px #CCCCCC;
	color: #666666;
	background-color: #EFEFEF;
	background-repeat:repeat-x;
	height: 19px;
}

TEXTAREA.txt {
	font-family: arial;
	font-size: 12px;
	width: 98.75%;
	border: solid 1px #CCCCCC;
	color: #666666;
	background-color: #EFEFEF;
	background-repeat:repeat-x;
	overflow: auto;
	padding: 4px;

	-moz-border-radius-topleft:5px;
	-webkit-border-top-left-radius:5px;
	border-top-left-radius:5px;

	-moz-border-radius-topright:5px;
	-webkit-border-top-right-radius:5px;
	border-top-right-radius:5px;

	-moz-border-radius-bottomleft:5px;
	-webkit-border-bottom-left-radius:5px;
	border-bottom-left-radius:5px;

	-moz-border-radius-bottomright:5px;
	-webkit-border-bottom-right-radius:5px;
	border-bottom-right-radius:5px;
}

INPUT.txt {
	font-family: arial;
	font-size: 12px;
	width: 98.75%;
	border: solid 1px #CCCCCC;
	color: #666666;
	background-color: #EFEFEF;
	background-repeat:repeat-x;
}

.formfld {
	border: solid 1px #CCCCCC;
	color: #666666;
	background-color: #F7F7F7;
	width: 50%;
	text-align: left;
	/*width: 300px;*/
	padding-left: 4px;

	-moz-border-radius-topleft:5px;
	-webkit-border-top-left-radius:5px;
	border-top-left-radius:5px;

	-moz-border-radius-topright:5px;
	-webkit-border-top-right-radius:5px;
	border-top-right-radius:5px;

	-moz-border-radius-bottomleft:5px;
	-webkit-border-bottom-left-radius:5px;
	border-bottom-left-radius:5px;

	-moz-border-radius-bottomright:5px;
	-webkit-border-bottom-right-radius:5px;
	border-bottom-right-radius:5px;
}

/*
table{
	-webkit-border-radius: 18px 18px 0px 0px;
	-moz-border-radius: 18px 18px 0px 0px;
	border-radius: 18px 18px 0px 0px;
}

.th:first-child .th:first-child {
	-webkit-border-radius: 8px 8px 0px 0px;
	-moz-border-radius: 8px 8px 0px 0px;
	border-radius: 8px 8px 0px 0px;
}

th:last-child th:first-child {
	-webkit-border-radius: 8px 8px 0px 0px;
	-moz-border-radius: 8px 8px 0px 0px;
	border-radius: 8px 8px 0px 0px;
}
*/

.vncell {
	border-bottom: 1px solid #999999;
	/*background-color: #639BC1;*/
	background-image: url('<!--{project_path}-->/themes/classic/images/background_cell.gif');
	padding-right: 20px;
	padding-left: 8px;
	text-align: left;
	color: #444444;
}

/*
.vncell a:link{ color:#444444; }
.vncell a:visited{ color:#444444; }
.vncell style0 a:hover{ color:#444444; }
.vncell a:active{ color:#444444; }
*/

.vncellreq {
	background-image: url('<!--{project_path}-->/themes/classic/images/background_cell.gif');
	border-bottom: 1px solid #999999;
	background-color: #639BC1;
	padding-right: 20px;
	padding-left: 8px;
	text-align: left;
	font-weight: bold;
	color: #444444;
}

.vtable {
	text-align: left;
	border-bottom: 1px solid #DFDFDF;
}

.listbg {
	border-bottom: 1px solid #999999;
	font-size: 11px;
	background-color: #990000;
	color: #444444;
	padding-right: 16px;
	padding-left: 6px;
	padding-top: 4px;
	padding-bottom: 4px;
}

.row_style0 {
	background-image: url('<!--{project_path}-->/themes/classic/images/background_cell.gif');
	border-bottom: 1px solid #999999;
	color: #444444;
	text-align: left;
	padding-top: 4px;
	padding-bottom: 4px;
	padding-right: 7px;
	padding-left: 7px;
}

.row_style0 a:link{ color:#444444; }
.row_style0 a:visited{ color:#444444; }
.row_style0 a:hover{ color:#444444; }
.row_style0 a:active{ color:#444444; }

.row_style1 {
	border-bottom: 1px solid #999999;
	background-color: #FFFFFF;
	text-align: left;
	padding-top: 4px;
	padding-bottom: 4px;
	padding-right: 7px;
	padding-left: 7px;
}

.row_stylebg {
	border-bottom: 1px solid #888888;
	background-color: #5F5F5F;
	color: #FFFFFF;
	text-align: left;
	padding-top: 5px;
	padding-bottom: 5px;
	padding-right: 10px;
	padding-left: 10px;
}

.border {
	border: solid 1px #999999;
	background-color: #FFFFFF;
}

.headermain {
	background-color: #7FAEDE;
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
	padding:4px 10px
}

table td {
	/*background:#fff;*/
	/*padding:2px 10px 4px 10px*/
}

table tr.even td {
	background:#eee;
	background-image: url('<!--{project_path}-->/themes/classic/images/background_cell.gif');
	border-bottom: 1px solid #999999;
	color: #444444;
}

table tr.odd td {
	border-bottom: 1px solid #999999;
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


/* begin the menu css*/

	/* CSS Menus - classic CSS Menu with Dropdown and Popout Menus - 20050131 */

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
		color:#FFFFFF;
		/*background:#222222 url(<!--{project_path}-->/css/images/expand3.gif) no-repeat 100% 100%;*/
		/*text-transform:uppercase*/
		width:118px;
	}

	#menu h2 h2{
		background:#4e4b56 url(<!--{project_path}-->/css/images/expand3.gif) no-repeat 100% 100%;
		/*text-transform:uppercase*/
		padding:3px 3px 3px 3px;
	}

	#menu a{
		<?php
		$user_agent = http_user_agent();
		$browser_version =  $user_agent['version'];
		$browser_name =  $user_agent['name'];
		$browser_version_array = explode('.', $browser_version);
		if ($browser_name == "Internet Explorer" && $browser_version_array[0] < '9' ) {
			echo "background:#333333;";
		}
		?>
		text-decoration:none;
		padding-left:7px;
		width:114px;
	}

	#menu a, #menu a:visited{
		color:#cccccc;
	}

	#menu .menu_sub {
		width:124px;
		background:#333333;
		background-color: rgba(20, 20, 20, 0.9);
		-moz-border-radius-bottomleft:7px;
		-webkit-border-bottom-left-radius:7px;
		border-bottom-left-radius:7px;
		-moz-border-radius-bottomright:7px;
		-webkit-border-bottom-right-radius:7px;
		border-bottom-right-radius:7px;
	}

	#menu a:hover{
		width:114px;
		color:#fd9c03;
		background:#1F1F1F;
		-moz-border-radius-bottomleft:7px;
		-webkit-border-bottom-left-radius:7px;
		border-bottom-left-radius:7px;
		-moz-border-radius-bottomright:7px;
		-webkit-border-bottom-right-radius:7px;
		border-bottom-right-radius:7px;
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
		-webkit-border-radius: 12px 12px 0px 0px;
		-moz-border-radius: 12px 12px 0px 0px;
		border-radius: 12px 12px 0px 0px;
		/*background:#1F1F1F url(<!--{project_path}-->/css/images/expand3.gif) no-repeat -999px -9999px;*/
		/*background:#1F1F1F url(<!--{project_path}-->/themes/classic/images/background_cell.gif) no-repeat -999px -9999px;*/
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
		background:#999999 url(<!--{project_path}-->/css/images/expand3.gif) no-repeat 100% 100%;
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
		top: -200px;
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
		background-image: url('<?php echo PROJECT_PATH?>/themes/classic/images/background_black.png');
		background-position: top center;
		padding: 10px;
		-webkit-border-radius: 0px 0px 7px 7px;
		-moz-border-radius: 0px 0px 7px 7px;
		border-radius: 0px 0px 7px 7px;
		text-align: center;
	}

	#message_block .text {
		font-family: arial, san-serif;
		font-size: 10pt;
		font-weight: bold;
		color: #fff;
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

<script language="javascript" type="text/javascript" src="<?php echo PROJECT_PATH?>/resources/jquery/jquery-1.8.3.js"></script>

<script language="JavaScript" type="text/javascript">
	function display_message(msg) {
		$("#message_text").html(msg);
		$("#message_container").animate({top: '+=200'}, 0).animate({ opacity: 0.9 }, "fast").delay(1750).animate({top: '-=200'}, 1000).animate({opacity: 0});
	}
</script>

</head>

<?php
// set message_onload
if (strlen($_SESSION['message']) > 0) {
	$message_text = addslashes($_SESSION['message']);
	$onload .= "display_message('".$message_text."');";
	unset($_SESSION['message']);
}
?>

<body onload="<?php echo $onload;?>">

	<div id='message_container'>
		<div id='message_block'>
			<span id='message_text' class='text'></span>
		</div>
	</div>

	<?php
		// qr code container for contacts
		echo "<div id='qr_code_container' style='display: none;' onclick='$(this).fadeOut(400);'>";
		echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'><tr><td align='center' valign='middle'>";
		echo "		<span id='qr_code' onclick=\"$('#qr_code_container').fadeOut(400);\"></span>";
		echo "	</td></tr></table>";
		echo "</div>";
	?>

<div align='center'>
<table width='90%' class='border.disabled' style='background-color:#FFFFFF;' border='0' cellpadding='0' cellspacing='0'>
<tr>
<td class='headermain' style='background-color:#FFFFFF;' width='100%'>
	<table cellpadding='0' cellspacing='0' border='0' style="background-image: url('<!--{project_path}-->/themes/classic/images/background_head.png'); color: #FFFFFF; font-size: 20px;" width='100%'>
	<tr>
	<td align='center' colspan='2' style='' width='100%' height='4'>
	</td>
	</tr>
	<tr>
	<td></td>
	<td align='left' valign='middle' nowrap>
		<table border='0' cellpadding='0' cellspacing='0' width='100%'>
			<tr>
				<td>
					<a href='/<!--{project_path}-->'><img src='<!--{project_path}-->/themes/classic/images/logo.png' /></a>
				</td>
				<td width='50%' class='' align='right' valign='middle'>
					<?php
					if (permission_exists("domain_select") && count($_SESSION['domains']) > 1) {
						//$tmp_style = "style=\"opacity:0.7;filter:alpha(opacity=70)\" ";
						//$tmp_style .= "onmouseover=\"this.style.opacity=1;this.filters.alpha.opacity=90\" ";
						//$tmp_style .= "onmouseout=\"this.style.opacity=0.7;this.filters.alpha.opacity=70\" ";
						$tmp_style = "style=\"opacity:0.7;\" ";
						$tmp_style .= "onmouseover=\"this.style.opacity=1;\" ";
						$tmp_style .= "onmouseout=\"this.style.opacity=0.7;\" ";
						echo "		<select id='domain_uuid' name='domain_uuid' class='formfld' onchange=\"window.location='".PROJECT_PATH."/core/domain_settings/domains.php?domain_uuid='+this.value+'&domain_change=true';\" $tmp_style>\n";
						foreach($_SESSION['domains'] as $row) {
							if ($row['domain_uuid'] == $_SESSION['domain_uuid']) {
								echo "	<option value='".$row['domain_uuid']."' selected='selected'>".$row['domain_name']."</option>\n";
							}
							else {
								echo "	<option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
							}
						}
						echo "	</select>\n";
						unset($tmp_style);
					}
					?>
					&nbsp;
				</td>
			</tr>
		</table>
	</td>
	</tr>
	<tr>
	<td align='center' colspan='2' style="background-image: url('<!--{project_path}-->/themes/classic/images/background_black.png');" width='100%' height='22'>
	<!--{menu}-->
	</td>
	</tr>
	</table>

</td>
</tr>
<!--
<tr><td colspan='100%'><img src='<!--{project_path}-->/images/spacer.gif' width='100%' height='1' style='background-color: #BBBBBB;'></td></tr>
-->
<tr>
<td valign='top' align='center' width='100%'>

<table width='100%' cellpadding='25' cellspacing='0' border='0'>
<td width='100%' align='left' valign='top'>
<!--{body}-->
<br />
<br />
</td>
</tr>
</table>

</td>
</tr>
</table>

<span class='smalltext'>
<a class='smalltext' target='_blank' href='http://www.fusionpbx.com'>fusionpbx.com</a>. Copyright 2008 - 2012. All Rights Reserved
</span>

</td>
</tr>
</table>
</div>

</td>
</tr>
</table>

<br>
</body>
</html>
