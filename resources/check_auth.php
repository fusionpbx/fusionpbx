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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
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
	if (isset($_SESSION['authorized']) && $_SESSION['authorized']) {
		//set the last activity time
		$_SESSION['session']['last_activity'] = time();

		//if session created is not set then set the time
		if (!isset($_SESSION['session']['created'])) {
			$_SESSION['session']['created'] = time();
		}

		//check the elapsed time if exceeds limit then rotate the session
		if (time() - $_SESSION['session']['created'] > 900) {

			//build the user log array
			$log_array['domain_uuid'] = $_SESSION['user']['domain_uuid'];
			$log_array['domain_name'] = $_SESSION['user']['domain_name'];
			$log_array['username'] = $_SESSION['user']['username'];
			$log_array['user_uuid'] = $_SESSION['user']['user_uuid'];
			$log_array['authorized'] = true;

			//session started more than 15 minutes
			session_regenerate_id(true);

			// update creation time
			$_SESSION['session']['created'] = time();

			//add the result to the user logs
			user_logs::add($log_array);
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

//if session authorized is not set then set the default value to false
	if (!isset($_SESSION['authorized'])) {
		$_SESSION['authorized'] = false;
	}

//session validate: use HTTP_USER_AGENT as a default value
	if (!isset($conf['session.validate'])) {
		$conf['session.validate'][] = 'HTTP_USER_AGENT';
	}

//session validate: prepare the server array
	foreach($conf['session.validate'] as $name) {
		$server_array[$name] = $_SERVER[$name];
	}
	unset($name);

//session validate: check to see if the session is valid
	if ($_SESSION['authorized'] && $_SESSION["user_hash"] !== hash('sha256', implode($server_array))) {
		session_destroy();
		header("Location: ".PROJECT_PATH."/logout.php");
	}

//if the session is not authorized then verify the identity
	if (!$_SESSION['authorized']) {

		//clear the template only if the template has not been assigned by the superadmin
			if (empty($settings->get('domain', 'template'))) {
				$_SESSION["template_content"] = '';
			}

		//validate the username and password
			$auth = new authentication(['settings' => $settings]);
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

		//clear the menu
			unset($_SESSION["menu"]);

		//get settings based on the user
			$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid'], 'user_uuid' => $_SESSION['user_uuid']]);
			settings::clear_cache();

		//if logged in, redirect to login destination
			if (!isset($_REQUEST["key"]) && !isset($_COOKIE['remember'])) {

				//connect to the settings object
				$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);

				//redirect the user
				if (isset($_SESSION['redirect_path'])) {
					$redirect_path = $_SESSION['redirect_path'];
					unset($_SESSION['redirect_path']);

					// prevent open redirect attacks. redirect url shouldn't contain a hostname
					$parsed_url = parse_url($redirect_path);
					if ($parsed_url['host']) {
						die("Was someone trying to hack you?");
					}
					header("Location: ".$redirect_path);
					exit;
				}
				elseif (!empty($settings->get('login', 'destination', ''))) {
					header("Location: ".$settings->get('login', 'destination', ''));
					exit;
				}
				elseif (file_exists(dirname(__DIR__, 1)."/core/dashboard/app_config.php")) {
					header("Location: ".PROJECT_PATH."/core/dashboard/");
					exit;
				}
				else {
					require_once "resources/header.php";
					require_once "resources/footer.php";
				}
			}

	}

?>
