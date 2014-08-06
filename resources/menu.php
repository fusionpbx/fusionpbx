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
require_once "root.php";
require_once "resources/require.php";

//define the variable
	$v_menu = '';

//force the menu to generate on every page load
	//$_SESSION["menu"] = '';

//build the menu if the session menu has no length
	if (!isset($_SESSION["menu"])) {
		$_SESSION["menu"] = '';
	}
	if (strlen($_SESSION["menu"]) == 0) {
		$menuwidth = '110';
		//echo "    <!-- http://www.seoconsultants.com/css/menus/horizontal/ -->\n";
		//echo "    <!-- http://www.tanfa.co.uk/css/examples/css-dropdown-menus.asp -->";

		$v_menu = "";
		$v_menu .= "    <!--[if IE]>\n";
		$v_menu .= "    <style type=\"text/css\" media=\"screen\">\n";
		$v_menu .= "    #menu{float:none;} /* This is required for IE to avoid positioning bug when placing content first in source. */\n";
		$v_menu .= "    /* IE Menu CSS */\n";
		$v_menu .= "    /* csshover.htc file version: V1.21.041022 - Available for download from: http://www.xs4all.nl/~peterned/csshover.html */\n";
		$v_menu .= "    body{behavior:url(/resources/csshover.htc);\n";
		$v_menu .= "    font-size:100%; /* to enable text resizing in IE */\n";
		$v_menu .= "    }\n";
		$v_menu .= "    #menu ul li{float:left;width:100%;}\n";
		$v_menu .= "    #menu h2, #menu a{height:1%;font:bold arial,helvetica,sans-serif;}\n";
		$v_menu .= "    </style>\n";
		$v_menu .= "    <![endif]-->\n";
		//$v_menu .= "    <style type=\"text/css\">@import url(\"/resources/menuh.css\");</style>\n";
		$v_menu .= "\n";

		$v_menu .= "<!-- Begin CSS Horizontal Popout Menu -->\n";
		$v_menu .= "<div id=\"menu\" style=\"position: relative; z-index:199; width:100%;\" align='left'>\n";
		$v_menu .= "\n";

		require_once "resources/classes/menu.php";
		$menu = new menu;
		$menu->db = $db;
		$menu->menu_uuid = $_SESSION['domain']['menu']['uuid'];
		$v_menu .= $menu->build_html("", "main");
		unset($menu);

		if ($_SESSION['theme']['gtranslate']['var'] == 'true'){

			$v_menu .= "<!-- GTranslate: http://gtranslate.net/ -->

<style type='text/css'>
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

<br /><select onchange='doGTranslate(this);'><option value=''>Select Language</option><option value='en|af'>Afrikaans</option><option value='en|sq'>Albanian</option><option value='en|ar'>Arabic</option><option value='en|hy'>Armenian</option><option value='en|az'>Azerbaijani</option><option value='en|eu'>Basque</option><option value='en|be'>Belarusian</option><option value='en|bg'>Bulgarian</option><option value='en|ca'>Catalan</option><option value='en|zh-CN'>Chinese (Simplified)</option><option value='en|zh-TW'>Chinese (Traditional)</option><option value='en|hr'>Croatian</option><option value='en|cs'>Czech</option><option value='en|da'>Danish</option><option value='en|nl'>Dutch</option><option value='en|en'>English</option><option value='en|et'>Estonian</option><option value='en|tl'>Filipino</option><option value='en|fi'>Finnish</option><option value='en|fr'>French</option><option value='en|gl'>Galician</option><option value='en|ka'>Georgian</option><option value='en|de'>German</option><option value='en|el'>Greek</option><option value='en|ht'>Haitian Creole</option><option value='en|iw'>Hebrew</option><option value='en|hi'>Hindi</option><option value='en|hu'>Hungarian</option><option value='en|is'>Icelandic</option><option value='en|id'>Indonesian</option><option value='en|ga'>Irish</option><option value='en|it'>Italian</option><option value='en|ja'>Japanese</option><option value='en|ko'>Korean</option><option value='en|lv'>Latvian</option><option value='en|lt'>Lithuanian</option><option value='en|mk'>Macedonian</option><option value='en|ms'>Malay</option><option value='en|mt'>Maltese</option><option value='en|no'>Norwegian</option><option value='en|fa'>Persian</option><option value='en|pl'>Polish</option><option value='en|pt'>Portuguese</option><option value='en|ro'>Romanian</option><option value='en|ru'>Russian</option><option value='en|sr'>Serbian</option><option value='en|sk'>Slovak</option><option value='en|sl'>Slovenian</option><option value='en|es'>Spanish</option><option value='en|sw'>Swahili</option><option value='en|sv'>Swedish</option><option value='en|th'>Thai</option><option value='en|tr'>Turkish</option><option value='en|uk'>Ukrainian</option><option value='en|ur'>Urdu</option><option value='en|vi'>Vietnamese</option><option value='en|cy'>Welsh</option><option value='en|yi'>Yiddish</option></select><div id='google_translate_element2'></div>
<script type='text/javascript'>
function googleTranslateElementInit2() {new google.translate.TranslateElement({pageLanguage: 'en',autoDisplay: false}, 'google_translate_element2');}
</script><script type='text/javascript' src='http://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit2'></script>


<script type='text/javascript'>
/* <![CDATA[ */
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('6 7(a,b){n{4(2.9){3 c=2.9('o');c.p(b,f,f);a.q(c)}g{3 c=2.r();a.s(\'t\'+b,c)}}u(e){}}6 h(a){4(a.8)a=a.8;4(a==\'\')v;3 b=a.w(\'|\')[1];3 c;3 d=2.x(\'y\');z(3 i=0;i<d.5;i++)4(d[i].A==\'B-C-D\')c=d[i];4(2.j(\'k\')==E||2.j(\'k\').l.5==0||c.5==0||c.l.5==0){F(6(){h(a)},G)}g{c.8=b;7(c,\'m\');7(c,\'m\')}}',43,43,'||document|var|if|length|function|GTranslateFireEvent|value|createEvent||||||true|else|doGTranslate||getElementById|google_translate_element2|innerHTML|change|try|HTMLEvents|initEvent|dispatchEvent|createEventObject|fireEvent|on|catch|return|split|getElementsByTagName|select|for|className|goog|te|combo|null|setTimeout|500'.split('|'),0,{}))
/* ]]> */
</script>
<script type='text/javascript' src='http://joomla-gtranslate.googlecode.com/svn/trunk/gt_update_notes0.js'></script>

";
		}
		$v_menu .= "</div>\n";
		$_SESSION["menu"] = $v_menu;
	}
	else {
		//echo "from session";
	}

//testing
	//echo $_SESSION["menu"];
?>
