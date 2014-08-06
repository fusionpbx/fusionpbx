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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//add multi-lingual support
	require_once "core/user_settings/app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the http values and set as variables
	$path = check_str($_GET["path"]);
	$msg = check_str($_GET["msg"]);

//set a default login destination
	if (strlen($_SESSION['login']['destination']['url']) == 0) {
		$_SESSION['login']['destination']['url'] = PROJECT_PATH."/core/user_settings/user_dashboard.php";
	}

//add the header
	include "resources/header.php";

//show the message
	if (strlen($msg) > 0) {
		echo "<br><br>";
		echo "<div align='center'>\n";
		echo "<table width='50%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>Message</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'>\n";
		switch ($msg) {
			case "username required":
				echo "<strong>Please provide a username.</strong>";
				break;
			case "incorrect account information":
			   echo "<strong>The username or password was incorrect. Please try again.</strong>";
				break;
			case "install complete":
				echo "<br />\n";
				echo "Installation is complete. <br />";
				echo "<br /> ";
				echo  "<strong>Getting Started:</strong><br /> ";
				echo "<ul><li>There are two levels of admins 1. superadmin 2. admin.<br />";
				echo "<br />\n";
				echo "username: <strong>superadmin</strong> <br />password: <strong>fusionpbx</strong> <br />\n";
				echo "<br />\n";
				echo "username: <strong>admin</strong> <br />password: <strong>fusionpbx</strong> <br/><br/>\n";
				echo "</li>\n";
				echo "<li>\n";
				echo "The database connection settings have been saved to ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/config.php.<br />\n";
				echo "</li>\n";
				echo "</ul>\n";
				echo "<strong>\n";
				break;
		}
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
		echo "<br /><br />\n\n";
	}

//show the content
	echo "<br />\n";
	echo "<form name='login' method='post' action='".$_SESSION['login']['destination']['url']."'>\n";
	echo "<input type='hidden' name='path' value='".$path."'>\n";
	echo "<input type='text' class='formfld' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='username' id='username' placeholder=\"".$text['label-username']."\"><br />\n";
	echo "<input type='password' class='formfld' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='password' placeholder=\"".$text['label-password']."\"><br />\n";
	if ($_SESSION['login']['domain_name.visible']['boolean'] == "true") {
		if (count($_SESSION['login']['domain_name']) > 0) {
			echo "<select style='width: 200px; margin-bottom: 8px;' class='formfld' name='domain_name'>\n";
			echo "	<option value=''></option>\n";
			foreach ($_SESSION['login']['domain_name'] as &$row) {
				echo "	<option value='$row'>$row</option>\n";
			}
			echo "</select>\n";
			echo "<br />";
		}
		else {
			echo "<input type='text' class='formfld' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='domain_name' placeholder=\"".$text['label-domain']."\"><br />\n";
		}
	}
if ($_SESSION['theme']['gtranslate']['var'] == 'true'){  ?>
<!-- GTranslate: http://gtranslate.net/ -->
<br />
<style type="text/css">
<!--
a.gflag {vertical-align:middle;font-size:16px;padding:1px 0;background-repeat:no-repeat;background-image:url('http://joomla-gtranslate.googlecode.com/svn/trunk/mod_gtranslate/tmpl/lang/16.png');}
a.gflag img {border:0;}
a.gflag:hover {background-image:url('http://joomla-gtranslate.googlecode.com/svn/trunk/mod_gtranslate/tmpl/lang/16a.png');}
#goog-gt-tt {display:none !important;}
.goog-te-banner-frame {display:none !important;}
.goog-te-menu-value:hover {text-decoration:none !important;}
body {top:0 !important;}
#google_translate_element2 {display:none!important;}
-->
</style>

 <select onchange="doGTranslate(this);"><option value="">Select Language</option><option value="en|af">Afrikaans</option><option value="en|sq">Albanian</option><option value="en|ar">Arabic</option><option value="en|hy">Armenian</option><option value="en|az">Azerbaijani</option><option value="en|eu">Basque</option><option value="en|be">Belarusian</option><option value="en|bg">Bulgarian</option><option value="en|ca">Catalan</option><option value="en|zh-CN">Chinese (Simplified)</option><option value="en|zh-TW">Chinese (Traditional)</option><option value="en|hr">Croatian</option><option value="en|cs">Czech</option><option value="en|da">Danish</option><option value="en|nl">Dutch</option><option value="en|en">English</option><option value="en|et">Estonian</option><option value="en|tl">Filipino</option><option value="en|fi">Finnish</option><option value="en|fr">French</option><option value="en|gl">Galician</option><option value="en|ka">Georgian</option><option value="en|de">German</option><option value="en|el">Greek</option><option value="en|ht">Haitian Creole</option><option value="en|iw">Hebrew</option><option value="en|hi">Hindi</option><option value="en|hu">Hungarian</option><option value="en|is">Icelandic</option><option value="en|id">Indonesian</option><option value="en|ga">Irish</option><option value="en|it">Italian</option><option value="en|ja">Japanese</option><option value="en|ko">Korean</option><option value="en|lv">Latvian</option><option value="en|lt">Lithuanian</option><option value="en|mk">Macedonian</option><option value="en|ms">Malay</option><option value="en|mt">Maltese</option><option value="en|no">Norwegian</option><option value="en|fa">Persian</option><option value="en|pl">Polish</option><option value="en|pt">Portuguese</option><option value="en|ro">Romanian</option><option value="en|ru">Russian</option><option value="en|sr">Serbian</option><option value="en|sk">Slovak</option><option value="en|sl">Slovenian</option><option value="en|es">Spanish</option><option value="en|sw">Swahili</option><option value="en|sv">Swedish</option><option value="en|th">Thai</option><option value="en|tr">Turkish</option><option value="en|uk">Ukrainian</option><option value="en|ur">Urdu</option><option value="en|vi">Vietnamese</option><option value="en|cy">Welsh</option><option value="en|yi">Yiddish</option></select><div id="google_translate_element2"></div>
<script type="text/javascript">
function googleTranslateElementInit2() {new google.translate.TranslateElement({pageLanguage: 'en',autoDisplay: false}, 'google_translate_element2');}
</script><script type="text/javascript" src="http://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit2"></script>


<script type="text/javascript">
/* <![CDATA[ */
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('6 7(a,b){n{4(2.9){3 c=2.9("o");c.p(b,f,f);a.q(c)}g{3 c=2.r();a.s(\'t\'+b,c)}}u(e){}}6 h(a){4(a.8)a=a.8;4(a==\'\')v;3 b=a.w(\'|\')[1];3 c;3 d=2.x(\'y\');z(3 i=0;i<d.5;i++)4(d[i].A==\'B-C-D\')c=d[i];4(2.j(\'k\')==E||2.j(\'k\').l.5==0||c.5==0||c.l.5==0){F(6(){h(a)},G)}g{c.8=b;7(c,\'m\');7(c,\'m\')}}',43,43,'||document|var|if|length|function|GTranslateFireEvent|value|createEvent||||||true|else|doGTranslate||getElementById|google_translate_element2|innerHTML|change|try|HTMLEvents|initEvent|dispatchEvent|createEventObject|fireEvent|on|catch|return|split|getElementsByTagName|select|for|className|goog|te|combo|null|setTimeout|500'.split('|'),0,{}))
/* ]]> */
</script>
<script type="text/javascript" src="http://joomla-gtranslate.googlecode.com/svn/trunk/gt_update_notes0.js"></script>
<?php
                                }
	echo "<br />";
	echo "<input type='submit' class='btn' style='width: 100px; margin-top: 15px;' value='".$text['button-login']."'>\n";
	echo "</form>";
	echo "<script>document.getElementById('username').focus();</script>";

//add the footer
	$default_login = true;
	include "resources/footer.php";

?>
