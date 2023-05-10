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
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "resources/require.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get(null, 'resources');

//for compatibility require this library if less than version 5.5
	if (version_compare(phpversion(), '5.5', '<')) {
		require_once "resources/functions/password.php";
	}

//start the session
	if (!isset($_SESSION)) { session_start(); }

//define variables
	if (!isset($_SESSION['template_content'])) { $_SESSION["template_content"] = null; }

//if the session is not authorized then verify the identity
	if (!isset($_SESSION['authorized']) && !$_SESSION['authorized']) {

		//clear the menu
			unset($_SESSION["menu"]);

		//clear the template only if the template has not been assigned by the superadmin
			if (empty($_SESSION['domain']['template']['name'])) {
				$_SESSION["template_content"] = '';
			}

		//validate the username and password
			$auth = new authentication;
			$auth->debug = true;
			$result = $auth->validate();

		//if not authorized
			if (!$_SESSION['authorized']) {

				//log the failed auth attempt to the system to the syslog server
					openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
					syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] authentication failed for ".$result["username"]);
					closelog();

				//redirect the user to the login page
					$target_path = ($_REQUEST["path"] != '') ? $_REQUEST["path"] : $_SERVER["PHP_SELF"];
					message::add($text['message-invalid_credentials'], 'negative');
					header("Location: ".PROJECT_PATH."/?path=".urlencode($target_path));
					exit;
			}

		//if logged in, redirect to login destination
			if (!isset($_REQUEST["key"])) {
				if (isset($_SESSION['redirect_path'])) {
					$redirect_path = $_SESSION['redirect_path'];
					unset($_SESSION['redirect_path']);
					// prevent open redirect attacks. redirect url shouldn't contain a hostname
					$parsed_url = parse_url($redirect_path);
					if ($parsed_url['host']) {
						die("Was someone trying to hack you?");
					}
					header("Location: ".$redirect_path);
				}
				elseif (isset($_SESSION['login']['destination']['url'])) {
					header("Location: ".$_SESSION['login']['destination']['url']);
				}
				elseif (file_exists($_SERVER["PROJECT_ROOT"]."/core/dashboard/app_config.php")) {
					header("Location: ".PROJECT_PATH."/core/dashboard/");
				}
				else {
					require_once "resources/header.php";
					require_once "resources/footer.php";
				}
			}

	}

?>
