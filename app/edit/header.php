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
	James Rose <james.o.rose@gmail.com>
*/

//add multi-lingual support
	$language = new text;
	$text = $language->get();

echo "<html>";
echo "<head>";
echo "<title>".$title."</title>";
//echo "<link href='style.css' rel='stylesheet' type='text/css'>";
echo "<style type='text/css'>";
echo "<!--\n";

echo "th {\n";
echo "	color: #5f5f5f;\n";
echo "	font-size: 12px;\n";
echo "	font-family: arial;\n";
echo "	font-weight: bold;\n";
echo "	background-color: #EFEFEF;\n";
echo "}\n";

echo "BODY {\n";
echo "	font-size: 11px;\n";
echo "	font-family: arial;\n";
echo "}\n";

echo "TD {\n";
echo "	font-size: 11px;\n";
echo "	font-family: arial;\n";
echo "}\n";

echo "INPUT, SELECT, TEXTAREA {\n";
echo "	font-size: 11px;\n";
echo "    }\n";

echo ".btn {\n";
echo "    width: 100%;\n";
echo "}\n";

echo ".txt {\n";
echo "    width: 100%;\n";
echo "}\n";

echo "//-->\n";
echo "</style>";


echo "<SCRIPT language=\"JavaScript\">\n";
echo "<!--\n";
echo "function confirmdelete(url)\n";
echo "{\n";
echo " var confirmed = confirm('".$text['button-delete-this']."');\n";
echo " if (confirmed == true) {\n";
echo "      window.location=url;\n";
echo " }\n";
echo "}\n";
echo "//-->\n";
echo "</SCRIPT>";
echo "</head>";
echo "<body style='margin: 0; padding: 5px;'>";
echo "<div align='center'>";

?>