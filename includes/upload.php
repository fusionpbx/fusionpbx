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
include "root.php";
require_once "includes/require.php";require_once "includes/checkauth.php";

require_once($virtualroot."includes/header.php");

//The hidden MAX_FILE_SIZE field contains the maximum file size accepted, in bytes.
//This cannot be larger than upload_max_filesize in php.ini (default 2MB).

echo "<table width='600' border='0' cellpadding='0' >";

echo "<tr><td colspan='2' align='center'>\n";
echo "<br>";
echo "<form enctype=\"multipart/form-data\" action=\"upload2.php\" method=\"post\">\n";
echo "    <input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"1000000\" />\n";
echo "    Upload File: <input name=\"userfile\" type=\"file\" />\n";
echo "    <input type=\"submit\" value=\"Upload File\" />\n";
echo "</form>\n";

echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";


require_once($virtualroot."includes/footer.php");


?>
