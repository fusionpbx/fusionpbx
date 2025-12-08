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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J. Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once __DIR__ . "/resources/require.php";

//if logged in, redirect to login destination
	if (isset($_SESSION["username"])) {
		if (!empty($settings->get('login', 'destination'))) {
			header("Location: ".$settings->get('login', 'destination'));
		}
		elseif (file_exists(__DIR__."/core/dashboard/app_config.php")) {
			header("Location: ".PROJECT_PATH."/core/dashboard/");
		}
		else {
			require_once "resources/header.php";
			require_once "resources/footer.php";
		}
	}
	else {
		//use custom index, if present, otherwise use custom login, if present, otherwise use default login
		if (file_exists(__DIR__."/themes/".($settings->get('domain', 'template') ?? '')."/index.php")) {
			require_once "themes/".$settings->get('domain', 'template', 'default')."/index.php";
		}
		else {
			//login prompt
			header("Location: ".PROJECT_PATH."/login.php");
		}
	}

?>