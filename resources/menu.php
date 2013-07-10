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

		$v_menu .= "</div>\n";
		$_SESSION["menu"] = $v_menu;
	}
	else {
		//echo "from session";
	}

//testing
	//echo $_SESSION["menu"];
?>
