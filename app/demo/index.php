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
	Matthew Vale <github@mafoo.org>
*/
require_once "root.php";
require_once "resources/require.php";

//check permissions
	$permissions = new permissions;
	require_once "resources/check_auth.php";
	$permissions->require_any(array("demo_a", "demo_b"));
	$permissions->require_all(array("demo_c", "demo_d"));

	echo "<h1>This page is to demonstrate functionality of a back end system and serves no purpose</h1>\n";
	echo "<span>If you can see this page you have the permissions demo_c and demo_d and have one of either demo_a or demo_b</span>\n";

//include the footer
	require_once "resources/footer.php";

?>