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

//includes files
    require_once __DIR__ . "/require.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get(null, 'resources');

//for compatibility require this library if less than version 5.5
	if (version_compare(phpversion(), '5.5', '<')) {
		require_once "resources/functions/password.php";
	}

//start the session
	if (function_exists('session_start')) { 
		if (!isset($_SESSION)) {
			session_start();
		}
	}

//regenerate sessions to avoid session id attacks such as session fixation
	if (array_key_exists('security',$_SESSION) && $_SESSION['security']['session_rotate']['boolean'] == "true") {
		$_SESSION['session']['last_activity'] = time();
		if (!isset($_SESSION['session']['created'])) {
			$_SESSION['session']['created'] = time();
		} else if (time() - $_SESSION['session']['created'] > 28800) {
			// session started more than 8 hours ago
			session_regenerate_id(true);    // rotate the session id
			$_SESSION['session']['created'] = time();  // update creation time
		}
	}

//set the domains session
	if (!isset($_SESSION['domains'])) {
		$domain = new domains();
		$domain->session();
		$domain->set();
	}

//set the domain_uuid variable from the session
	if (!empty($_SESSION["domain_uuid"])) {
		$domain_uuid = $_SESSION["domain_uuid"];
	}

//define variables
	if (!isset($_SESSION['template_content'])) { $_SESSION["template_content"] = null; }

//if the session is not authorized then verify the identity
	if (!isset($_SESSION['authorized']) || (isset($_SESSION['authorized']) && !$_SESSION['authorized'])) {

		//clear the menu
			unset($_SESSION["menu"]);

		//clear the template only if the template has not been assigned by the superadmin
			if (empty($_SESSION['domain']['template']['name'])) {
				$_SESSION["template_content"] = '';
			}

		//validate the username and password
			$auth = new authentication;
			$result = $auth->validate();

		//if not authorized
			if (empty($_SESSION['authorized']) || !$_SESSION['authorized']) {

				//log the failed auth attempt to the system to the syslog server
					openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
					syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] authentication failed for ".$result["username"]);
					closelog();

				//redirect the user to the login page
					$target_path = !empty($_REQUEST["path"]) ? $_REQUEST["path"] : $_SERVER["PHP_SELF"];
					message::add($text['message-authentication_failed'], 'negative');
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
				elseif (isset($_SESSION['login']['destination']['text'])) {
					header("Location: ".$_SESSION['login']['destination']['text']);
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
