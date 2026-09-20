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

/**
 * plugin_passkey
 *
 * @method passkey webauthn / passkey authenticator, second factor after the primary authentication
 */
class plugin_passkey {

	/**
	 * Declare Public variables
	 *
	 * @var mixed
	 */
	public $debug;
	public $domain_name;
	public $domain_uuid;
	public $username;
	public $password;
	public $user_uuid;
	public $user_email;
	public $contact_uuid;

	/**
	 * Declare Private variables
	 *
	 * @var mixed
	 */
	private $database;
	private $webauthn;

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//connect to the database
		if (empty($this->database)) {
			$this->database = database::new();
		}
	}

	/**
	 * webauthn passkey authentication
	 *
	 * @return array [authorized] => true or false
	 */
	function passkey(authentication $auth, settings $settings) {

		//add multi-lingual support
		$language = new text;
		$text = $language->get(null, '/core/authentication');

		//validate the token
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$token = new token;
			if (!$token->validate('login')) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: login.php');
				exit;
			}
		}

		//pre-process some settings
		$theme_favicon = $settings->get('theme', 'favicon', PROJECT_PATH . '/themes/default/favicon.ico');
		$theme_logo = $settings->get('theme', 'logo', PROJECT_PATH . '/themes/default/images/logo_login.png');
		$theme_login_type = $settings->get('theme', 'login_brand_type', '');
		$theme_login_image = $settings->get('theme', 'login_brand_image', '');
		$theme_login_text = $settings->get('theme', 'login_brand_text', '');
		$theme_login_logo_width = $settings->get('theme', 'login_logo_width', 'auto; max-width: 300px');
		$theme_login_logo_height = $settings->get('theme', 'login_logo_height', 'auto; max-height: 300px');
		$theme_message_delay = 1000 * (float)$settings->get('theme', 'message_delay', 3000);
		$background_videos = $settings->get('theme', 'background_video', null);
		$theme_background_video = (isset($background_videos) && is_array($background_videos)) ? $background_videos[0] : null;
		$login_remember_me = $settings->get('login', 'remember_me');
		$login_destination = $settings->get('login', 'destination');
		$users_unique = $settings->get('users', 'unique', '');

		//in the "Login with Passkey" flow only a pre-registered passkey may be used to sign in
		$passkey_only = !empty($_SESSION['authentication']['passkey_only']);

		//set the default login type and image
		if (empty($theme_login_type)) {
			$theme_login_type = 'image';
			$theme_login_image = $theme_logo;
		}
		//get the username
		if (isset($_SESSION["username"])) {
			$this->username = $_SESSION["username"];
		}
		if (isset($_POST['username'])) {
			$this->username = $_POST['username'];
			$_SESSION["username"] = $this->username;
		}
		if (isset($_POST["remember_me"])) {
			$_SESSION['remember_me'] = $_POST["remember_me"];
		}

		//the username is required, the primary authentication must have been processed first
		if (empty($this->username)) {
			//show the username form (passkey is the primary authentication method)
			$login_username = $text['label-username'] ?? 'Username';
			$button_login = $text['button-login'] ?? 'Login';
			$login_remember_me = $settings->get('login', 'remember_me');
			$label_remember_me = $text['label-remember_me'] ?? 'Remember Me';

			//create token
			$object = new token;
			$token = $object->create('login');

			//initialize a template object
			$view = new template();
			$view->engine = 'smarty';
			$view->template_dir = dirname(__DIR__, 5) . '/core/authentication/resources/views/';
			$view->cache_dir = sys_get_temp_dir();
			$view->init();

			//assign values to the template
			$view->assign("project_path", PROJECT_PATH);
			$view->assign("login_title", $text['title-passkey_sign_in'] ?? 'Passkey Sign In');
			$view->assign("login_username", $login_username);
			$view->assign("button_login", $button_login);
			$view->assign("favicon", $theme_favicon);
			$view->assign("login_logo_width", $theme_login_logo_width);
			$view->assign("login_logo_height", $theme_login_logo_height);
			$view->assign("login_logo_source", $theme_login_image);
			$view->assign("message_delay", $theme_message_delay);
			$view->assign("background_video", $theme_background_video);
			$view->assign("login_remember_me", $login_remember_me);
			$view->assign("label_remember_me", $label_remember_me);

			//add the token name and hash to the view
			$view->assign("token_name", $token['name']);
			$view->assign("token_hash", $token['hash']);

			//messages
			$view->assign('messages', message::html(true, '\t\t'));

			//show the view
			$content = $view->render('username.htm');
			echo $content;
			exit;
		}

		//get the user details
		$sql = "select user_uuid, username, user_email, contact_uuid\n";
		$sql .= "from v_users\n";
		$sql .= "where (\n";
		$sql .= " username = :username\n";
		$sql .= " or user_email = :username\n";
		$sql .= ")\n";
		if (empty($users_unique) || $users_unique != "global") {
			//unique username per domain (not globally unique across system - example: email address)
			$sql .= "and domain_uuid = :domain_uuid \n";
			$parameters['domain_uuid'] = $_SESSION["domain_uuid"] ?? $this->domain_uuid;
		}
		$sql .= "and (user_type = 'default' or user_type is null) \n";
		$parameters['username'] = $this->username;
		$row = $this->database->select($sql, $parameters, 'row');
		unset($sql, $parameters);
		if (empty($row) || !is_array($row) || @sizeof($row) == 0) {
			//build the result array
			$result["plugin"] = "passkey";
			$result["domain_uuid"] = $_SESSION["domain_uuid"] ?? $this->domain_uuid;
			$result["domain_name"] = $_SESSION["domain_name"] ?? $this->domain_name;
			$result["username"] = $this->username;
			$result["authorized"] = false;

			//return the array
			return $result;
		}

		//set class variables
		$this->user_uuid = $row['user_uuid'];
		$this->username = $row['username'];
		$this->user_email = $row['user_email'];
		$this->contact_uuid = $row['contact_uuid'];

		//set a few session variables
		$_SESSION["user_uuid"] = $this->user_uuid;
		$_SESSION["username"] = $this->username;
		$_SESSION["user_email"] = $this->user_email;
		$_SESSION["contact_uuid"] = $this->contact_uuid;

		//initialize the webauthn object
		$this->webauthn = new webauthn();

		//set the relying party id (domain name without the port) and name
		$domain_array = explode(":", $_SERVER["HTTP_HOST"] ?? 'localhost');
		$rp_id = $domain_array[0];
		$rp_name = !empty($_SESSION['domain_name']) ? $_SESSION['domain_name'] : $rp_id;

		//get the user passkey credentials
		$sql = "select user_passkey_uuid, rp_id, credential_id, public_key, user_handle, aaguid, display_name, sign_count \n";
		$sql .= "from v_user_passkeys \n";
		$sql .= "where user_uuid = :user_uuid \n";
		$sql .= "and passkey_enabled = true \n";
		$sql .= "order by insert_date \n";
		$parameters['user_uuid'] = $this->user_uuid;
		$credentials = $this->database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		//process the passkey assertion (sign in with an existing passkey)
		if (isset($_POST['passkey_assertion'])) {
			$auth_valid = false;
			$verify = null;
			$credential_row = null;

			//get the challenge used to build the assertion options
			$challenge = $_SESSION['passkey_assertion_challenge'] ?? '';
			$challenge_time = $_SESSION['passkey_challenge_time'] ?? 0;
			unset($_SESSION['passkey_assertion_challenge'], $_SESSION['passkey_challenge_time']);

			//the challenge must exist and be recent
			if (!empty($challenge) && (time() - $challenge_time) < 300) {
				$response = json_decode($_POST['passkey_assertion'], true);
				if (is_array($response) && !empty($response['response']) && !empty($response['id'])) {
					//find the credential that the user selected
					foreach ($credentials as $credential) {
						if ($credential['credential_id'] === $response['id']) {
							$credential_row = $credential;
							break;
						}
					}
					if (!empty($credential_row)) {
						try {
							$verify = $this->webauthn->verify_assertion($credential_row, $response['response'], $challenge, $rp_id);
							$auth_valid = true;
						}
						catch (Exception $error) {
							$auth_valid = false;
						}
					}
				}
			}

			//update the sign count when the assertion is valid
			if ($auth_valid) {
				$array['user_passkeys'][0]['user_passkey_uuid'] = $credential_row['user_passkey_uuid'];
				$array['user_passkeys'][0]['domain_uuid'] = $_SESSION["domain_uuid"] ?? $this->domain_uuid;
				$array['user_passkeys'][0]['user_uuid'] = $this->user_uuid;
				$array['user_passkeys'][0]['sign_count'] = $verify['sign_count'];

				//add the user_passkey_edit permission
				$p = permissions::new();
				$p->add("user_passkey_edit", "temp");

				//save the data
				$this->database->save($array);

				//remove the temporary permission
				$p->delete("user_passkey_edit", "temp");
				unset($array);
			}

			//add a message when the assertion fails
			if (!$auth_valid) {
				message::add($text['message-passkey_assertion_failed'], 'negative');
			}

			//build the result array
			$result["plugin"] = "passkey";
			$result["domain_name"] = $_SESSION["domain_name"] ?? $this->domain_name;
			$result["username"] = $this->username;
			$result["user_uuid"] = $this->user_uuid;
			$result["domain_uuid"] = $_SESSION["domain_uuid"] ?? $this->domain_uuid;
			$result["contact_uuid"] = $this->contact_uuid;
			$result["user_email"] = $this->user_email;
			$result["authorized"] = $auth_valid ? true : false;

			//add the failed login to user logs
			if (!$auth_valid) {
				user_logs::add($result);
			}

			//return the array
			return $result;
		}



		//process the passkey registration (register a new passkey during sign in)
		if (isset($_POST['passkey_registration'])) {

			//the "Login with Passkey" flow only allows pre-registered passkeys - refuse first time registrations
			if ($passkey_only) {
				message::add($text['message-passkey_not_registered'] ?? 'No passkey is registered for this account. Please log in with your username and password.', 'negative');
				unset($_SESSION['authentication']['passkey_only_until']);
				$_SESSION['authentication']['passkey_only'] = false;
				header('Location: ' . PROJECT_PATH . '/login.php');
				exit;
			}

			$auth_valid = false;
			$verified = null;

			//get the challenge used to build the registration options
			$challenge = $_SESSION['passkey_registration_challenge'] ?? '';
			$challenge_time = $_SESSION['passkey_challenge_time'] ?? 0;
			unset($_SESSION['passkey_registration_challenge'], $_SESSION['passkey_challenge_time']);

			//the challenge must exist and be recent
			if (!empty($challenge) && (time() - $challenge_time) < 300) {
				$credential = json_decode($_POST['passkey_registration'], true);
				if (is_array($credential) && !empty($credential['response'])) {
					try {
						$verified = $this->webauthn->verify_attestation($credential, $challenge, $rp_id);
						$auth_valid = true;
					}
					catch (Exception $error) {
						$auth_valid = false;
					}
				}
			}

			//save the new credential when the attestation is valid
			if ($auth_valid) {
				$x = 0;
				$array['user_passkeys'][$x]['domain_uuid'] = $_SESSION["domain_uuid"] ?? $this->domain_uuid;
				$array['user_passkeys'][$x]['user_uuid'] = $this->user_uuid;
				$array['user_passkeys'][$x]['rp_id'] = $rp_id;
				$array['user_passkeys'][$x]['credential_id'] = $verified['credential_id'];
				$array['user_passkeys'][$x]['public_key'] = webauthn::base64url_encode($verified['public_key']);
				$array['user_passkeys'][$x]['user_handle'] = webauthn::base64url_encode($this->user_uuid);
				$array['user_passkeys'][$x]['aaguid'] = bin2hex($verified['aaguid']);
				$array['user_passkeys'][$x]['display_name'] = $this->webauthn->aaguid_to_name($verified['aaguid']);
				$array['user_passkeys'][$x]['sign_count'] = $verified['sign_count'];
				$array['user_passkeys'][$x]['passkey_enabled'] = 'true';

				//add the user_passkey_add permission
				$p = permissions::new();
				$p->add("user_passkey_add", "temp");

				//save the data
				$this->database->save($array);

				//remove the temporary permission
				$p->delete("user_passkey_add", "temp");
				unset($array);
			}

			//add a message when the registration fails
			if (!$auth_valid) {
				message::add($text['message-passkey_registration_failed'], 'negative');
			}

			//build the result array
			$result["plugin"] = "passkey";
			$result["domain_name"] = $_SESSION["domain_name"] ?? $this->domain_name;
			$result["username"] = $this->username;
			$result["user_uuid"] = $this->user_uuid;
			$result["domain_uuid"] = $_SESSION["domain_uuid"] ?? $this->domain_uuid;
			$result["contact_uuid"] = $this->contact_uuid;
			$result["user_email"] = $this->user_email;
			$result["authorized"] = $auth_valid ? true : false;

			//add the failed login to user logs
			if (!$auth_valid) {
				user_logs::add($result);
			}

			//return the array
			return $result;
		}


		//show the passkey view (sign in or register)
		//create token
		$object = new token;
		$token = $object->create('login');

		//initialize a template object
		$view = new template();
		$view->engine = 'smarty';
		$view->template_dir = dirname(__DIR__, 5) . '/core/authentication/resources/views/';
		$view->cache_dir = sys_get_temp_dir();
		$view->init();

		//assign common values to the template
		$view->assign("project_path", PROJECT_PATH);
		$view->assign("login_destination_url", $login_destination);
		$view->assign("login_logo_width", $theme_login_logo_width);
		$view->assign("login_logo_height", $theme_login_logo_height);
		$view->assign("login_logo_source", $theme_login_image);
		$view->assign("favicon", $theme_favicon);
		$view->assign("background_video", $theme_background_video);
		$view->assign("login_remember_me", $login_remember_me);
		$view->assign("label_remember_me", $text['label-remember_me']);
		$view->assign("text_passkey_unsupported", $text['message-passkey_unsupported']);
		$view->assign("message_delay", $theme_message_delay);

		//add the token name and hash to the view
		$view->assign("token_name", $token['name']);
		$view->assign("token_hash", $token['hash']);

		//messages
		$view->assign('messages', message::html(true, '\t\t'));

		//show the views
		if (!empty($_SESSION['username'])) {
			$view->assign("username", escape($_SESSION['username']));
			$view->assign("button_cancel", $text['button-cancel']);
		}

		//build the options depending on whether the user has registered passkeys
		if (!empty($credentials) && is_array($credentials)) {
			//sign in with an existing passkey
			$view->assign("login_title", $text['title-passkey_sign_in']);
			$view->assign("passkey_description", $text['description-passkey_sign_in']);
			$view->assign("button_passkey", $text['button-passkey_sign_in']);
			$view->assign("passkey_mode", "assertion");

			//set the challenge in the session
			$challenge = random_bytes(32);
			$_SESSION['passkey_assertion_challenge'] = $challenge;
			$_SESSION['passkey_challenge_time'] = time();

			//build the assertion options
			$allow_credentials = [];
			foreach ($credentials as $credential) {
				$allow_credentials[] = $credential['credential_id'];
			}
			$options = $this->webauthn->generate_assertion_options($rp_id, $allow_credentials, $challenge);
			$view->assign("passkey_options", json_encode($options));

			//render the template
			$content = $view->render('passkey.htm');
		}
		elseif ($passkey_only) {
			//the "Login with Passkey" flow requires a passkey to already be registered - first time registrations are not allowed
			message::add($text['message-passkey_not_registered'] ?? 'No passkey is registered for this account. Please log in with your username and password.', 'negative');
			unset($_SESSION['authentication']['passkey_only_until']);
			$_SESSION['authentication']['passkey_only'] = false;
			header('Location: ' . PROJECT_PATH . '/login.php');
			exit;
		}
		else {
			//register a new passkey (only in the multi-factor flow, not the "Login with Passkey" flow)
			$view->assign("login_title", $text['title-passkey_register']);
			$view->assign("passkey_description", $text['description-passkey_register']);
			$view->assign("button_passkey", $text['button-passkey_register']);
			$view->assign("passkey_mode", "registration");

			//set the challenge in the session
			$challenge = random_bytes(32);
			$_SESSION['passkey_registration_challenge'] = $challenge;
			$_SESSION['passkey_challenge_time'] = time();

			//build the registration options
			$exclude_credentials = [];
			foreach ($credentials as $credential) {
				$exclude_credentials[] = $credential['credential_id'];
			}
			$user = [
				'user_uuid' => $this->user_uuid,
				'username' => $this->username,
			];
			$options = $this->webauthn->generate_registration_options($rp_id, $rp_name, $user, $exclude_credentials, $challenge);
			$view->assign("passkey_options", json_encode($options));

			//render the template
			$content = $view->render('passkey.htm');
		}
		echo $content;
		exit;
	}
}
