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

/**
 * plugin_totp
 *
 * @method totp time based one time password authenticate the user
 */
class plugin_totp {

	/**
	 * Define variables and their scope
	 */
	public $debug;
	public $domain_name;
	public $username;
	public $password;
	public $user_uuid;
	public $user_email;
	public $contact_uuid;
	private $user_totp_secret;

	/**
	 * time based one time password aka totp
	 * @return array [authorized] => true or false
	 */
	function totp() {

		//pre-process some settings
			$settings['theme']['favicon'] = !empty($_SESSION['theme']['favicon']['text']) ? $_SESSION['theme']['favicon']['text'] : PROJECT_PATH.'/themes/default/favicon.ico';
			$settings['login']['destination'] = !empty($_SESSION['login']['destination']['text']) ? $_SESSION['login']['destination']['text'] : '';
			$settings['users']['unique'] = !empty($_SESSION['users']['unique']['text']) ? $_SESSION['users']['unique']['text'] : '';
			$settings['theme']['logo'] = !empty($_SESSION['theme']['logo']['text']) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH.'/themes/default/images/logo_login.png';
			$settings['theme']['login_logo_width'] = !empty($_SESSION['theme']['login_logo_width']['text']) ? $_SESSION['theme']['login_logo_width']['text'] : 'auto; max-width: 300px';
			$settings['theme']['login_logo_height'] = !empty($_SESSION['theme']['login_logo_height']['text']) ? $_SESSION['theme']['login_logo_height']['text'] : 'auto; max-height: 300px';
			$settings['theme']['message_delay'] = isset($_SESSION['theme']['message_delay']) ? 1000 * (float) $_SESSION['theme']['message_delay'] : 3000;

		//get the username
			if (isset($_SESSION["username"])) {
				$this->username = $_SESSION["username"];
			}
			if (isset($_POST['username'])) {
				$this->username = $_POST['username'];
				$_SESSION["username"] = $this->username;
			}

		//request the username
			if (!$this->username && !isset($_POST['authentication_code'])) {

				//set a default template
				$_SESSION['domain']['template']['name'] = 'default';
				$_SESSION['theme']['menu_brand_image']['text'] = PROJECT_PATH.'/themes/default/images/logo.png';
				$_SESSION['theme']['menu_brand_type']['text'] = 'image';

				//get the domain
				$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
				$domain_name = $domain_array[0];

				//temp directory
				$_SESSION['server']['temp']['dir'] = '/tmp';

				//create token
				//$object = new token;
				//$token = $object->create('login');

				//add multi-lingual support
				$language = new text;
				$text = $language->get(null, '/core/authentication');

				//initialize a template object
				$view = new template();
				$view->engine = 'smarty';
				$view->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/core/authentication/resources/views/';
				$view->cache_dir = $_SESSION['server']['temp']['dir'];
				$view->init();

				//assign default values to the template
				$view->assign("project_path", PROJECT_PATH);
				$view->assign("login_destination_url", $settings['login']['destination']);
				$view->assign("favicon", $settings['theme']['favicon']);
				$view->assign("login_title", $text['label-username']);
				$view->assign("login_username", $text['label-username']);
				$view->assign("login_logo_width", $settings['theme']['login_logo_width']);
				$view->assign("login_logo_height", $settings['theme']['login_logo_height']);
				$view->assign("login_logo_source", $settings['theme']['logo']);
				$view->assign("button_login", $text['button-login']);
				$view->assign("favicon", $settings['theme']['favicon']);
				$view->assign("message_delay", $settings['theme']['message_delay']);

				//messages
				$view->assign('messages', message::html(true, '		'));

				//show the views
				$content = $view->render('username.htm');
				echo $content;
				exit;
			}

		//show the authentication code view
			if (!isset($_POST['authentication_code'])) {

				//get the username
				if (!isset($this->username) && isset($_REQUEST['username'])) {
					$this->username = $_REQUEST['username'];
					$_SESSION['username'] = $this->username;
				}

				//get the domain name
				if (!empty($_SESSION['username'])) {
					$auth = new authentication;
					$auth->get_domain();
					$this->domain_uuid = $_SESSION['domain_uuid'];
					$this->domain_name = $_SESSION['domain_name'];
					$this->username = $_SESSION['username'];
				}

				//get the user details
				$sql = "select user_uuid, username, user_email, contact_uuid, user_totp_secret\n";
				$sql .= "from v_users\n";
				$sql .= "where (\n";
				$sql .= "	username = :username\n";
				$sql .= "	or user_email = :username\n";
				$sql .= ")\n";
				if (empty($_SESSION["users"]["unique"]["text"]) || $_SESSION["users"]["unique"]["text"] != "global") {
					//unique username per domain (not globally unique across system - example: email address)
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $this->domain_uuid;
				}
				$sql .= "and (user_type = 'default' or user_type is null) ";
				$parameters['username'] = $this->username;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (empty($row) || !is_array($row) || @sizeof($row) == 0) {
					//clear submitted usernames
					unset($this->username, $_SESSION['username'], $_REQUEST['username'], $_POST['username']);

					//build the result array
					$result["plugin"] = "totp";
					$result["domain_uuid"] = $_SESSION["domain_uuid"];
					$result["domain_name"] = $_SESSION["domain_name"];
					$result["authorized"] = false;

					//retun the array
					return $result;
				}
				unset($parameters);

				//set class variables
				$this->user_uuid = $row['user_uuid'];
				$this->user_email = $row['user_email'];
				$this->contact_uuid = $row['contact_uuid'];
				$this->user_totp_secret = $row['user_totp_secret'];

				//set a few session variables
				$_SESSION["user_uuid"] = $row['user_uuid'];
				$_SESSION["username"] = $row['username'];
				$_SESSION["user_email"] = $row['user_email'];
				$_SESSION["contact_uuid"] = $row["contact_uuid"];

				//set a default template
				$_SESSION['domain']['template']['name'] = 'default';
				$_SESSION['theme']['menu_brand_image']['text'] = PROJECT_PATH.'/themes/default/images/logo.png';
				$_SESSION['theme']['menu_brand_type']['text'] = 'image';

				//get the domain
				$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
				$domain_name = $domain_array[0];

				//temp directory
				$_SESSION['server']['temp']['dir'] = '/tmp';

				//create token
				//$object = new token;
				//$token = $object->create('login');

				//add multi-lingual support
				$language = new text;
				$text = $language->get(null, '/core/authentication');

				//initialize a template object
				$view = new template();
				$view->engine = 'smarty';
				$view->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/core/authentication/resources/views/';
				$view->cache_dir = $_SESSION['server']['temp']['dir'];
				$view->init();

				//assign values to the template
				$view->assign("project_path", PROJECT_PATH);
				$view->assign("login_destination_url", $settings['login']['destination']);
				$view->assign("favicon", $settings['theme']['favicon']);
				$view->assign("login_title", $text['label-verify']);
				$view->assign("login_totp_description", $text['label-totp_description']);
				$view->assign("login_authentication_code", $text['label-authentication_code']);
				$view->assign("login_logo_width", $settings['theme']['login_logo_width']);
				$view->assign("login_logo_height", $settings['theme']['login_logo_height']);
				$view->assign("login_logo_source", $settings['theme']['logo']);
				$view->assign("favicon", $settings['theme']['favicon']);
				if (!empty($_SESSION['username'])) {
					$view->assign("username", $_SESSION['username']);
					$view->assign("button_cancel", $text['button-cancel']);
				}

				//show the views
				if (!empty($_SESSION['authentication']['plugin']['database']['authorized']) && empty($this->user_totp_secret)) {

					//create the totp secret
					$base32 = new base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', FALSE, TRUE, TRUE);
					$user_totp_secret = $base32->encode(generate_password(20,3));
					$this->user_totp_secret = $user_totp_secret;

					//add user setting to array for update
					$x = 0;
					$array['users'][$x]['user_uuid'] = $this->user_uuid;
					$array['users'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['users'][$x]['user_totp_secret'] = $this->user_totp_secret;

					//add the user_edit permission
					$p = new permissions;
					$p->add("user_edit", "temp");

					//save the data
					$database = new database;
					$database->app_name = 'users';
					$database->app_uuid = '112124b3-95c2-5352-7e9d-d14c0b88f207';
					$database->save($array);

					//remove the temporary permission
					$p->delete("user_edit", "temp");

					//qr code includes
					require_once 'resources/qr_code/QRErrorCorrectLevel.php';
					require_once 'resources/qr_code/QRCode.php';
					require_once 'resources/qr_code/QRCodeImage.php';

					//build the otp authentication url
					$otpauth = "otpauth://totp/".$this->username;
					$otpauth .= "?secret=".$this->user_totp_secret;
					$otpauth .= "&issuer=".$_SESSION['domain_name'];

					//build the qr code image
					try {
						$code = new QRCode (- 1, QRErrorCorrectLevel::H);
						$code->addData($otpauth);
						$code->make();
						$img = new QRCodeImage ($code, $width=210, $height=210, $quality=50);
						$img->draw();
						$image = $img->getImage();
						$img->finish();
					}
					catch (Exception $error) {
						echo $error;
					}

					//assign values to the template
					$view->assign("totp_secret", $this->user_totp_secret);
					$view->assign("totp_image", base64_encode($image));
					$view->assign("totp_description", $text['description-totp']);
					$view->assign("button_next", $text['button-next']);
					$view->assign("favicon", $settings['theme']['favicon']);
					$view->assign("message_delay", $settings['theme']['message_delay']);

					//messages
					$view->assign('messages', message::html(true, '		'));

					//render the template
					$content = $view->render('totp_secret.htm');
				}
				else {
					//assign values to the template
					$view->assign("button_verify", $text['label-verify']);
					$view->assign("message_delay", $settings['theme']['message_delay']);

					//messages
					$view->assign('messages', message::html(true, '		'));

					//render the template
					$content = $view->render('totp.htm');
				}
				echo $content;
				exit;
			}

		//if authorized then verify
			if (isset($_POST['authentication_code'])) {

				//get the user details
				$sql = "select user_uuid, user_email, contact_uuid, user_totp_secret\n";
				$sql .= "from v_users\n";
				$sql .= "where (\n";
				$sql .= "	username = :username\n";
				$sql .= "	or user_email = :username\n";
				$sql .= ")\n";
				if ($settings['users']['unique'] != "global") {
					//unique username per domain (not globally unique across system - example: email address)
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				}
				$parameters['username'] = $_SESSION["username"];
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				$this->user_uuid = $row['user_uuid'];
				$this->user_email = $row['user_email'];
				$this->contact_uuid = $row['contact_uuid'];
				$this->user_totp_secret = $row['user_totp_secret'];
				unset($parameters);

				//create the authenticator object
				$totp = new google_authenticator;

				//validate the code
				if ($totp->checkCode($this->user_totp_secret, $_POST['authentication_code'])) {
					$auth_valid = true;
				}
				else {
					$auth_valid = false;
				}

				//clear posted authentication code
				unset($_POST['authentication_code']);

				//get the user details
				if ($auth_valid) {
					//get user data from the database
					$sql = "select user_uuid, username, user_email, contact_uuid ";
					$sql .= "from v_users ";
					$sql .= "where user_uuid = :user_uuid ";
					if ($settings['users']['unique'] != "global") {
						//unique username per domain (not globally unique across system - example: email address)
						$sql .= "and domain_uuid = :domain_uuid ";
						$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
					}
					$parameters['user_uuid'] = $_SESSION["user_uuid"];
					$database = new database;
					$row = $database->select($sql, $parameters, 'row');
					unset($parameters);
				}
				else {
// 					//destroy session
// 					session_unset();
// 					session_destroy();
//
// 					//send http 403
// 					header('HTTP/1.0 403 Forbidden', true, 403);
//
// 					//redirect to the root of the website
// 					header("Location: ".PROJECT_PATH."/");
//
// 					//exit the code
// 					exit();

					//clear authentication session
					unset($_SESSION['authentication']);

					// clear username
					unset($_SESSION["username"]);
				}

				/*
				//check if user successfully logged in during the interval
					//$sql = "select user_log_uuid, timestamp, user_name, user_agent, remote_address ";
					$sql = "select count(*) as count ";
					$sql .= "from v_user_logs ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and user_uuid = :user_uuid ";
					$sql .= "and user_agent = :user_agent ";
					$sql .= "and type = 'login' ";
					$sql .= "and result = 'success' ";
					$sql .= "and floor(extract(epoch from now()) - extract(epoch from timestamp)) > 3 ";
					$sql .= "and floor(extract(epoch from now()) - extract(epoch from timestamp)) < 300 ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$parameters['user_uuid'] = $this->user_uuid;
					$parameters['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
					$database = new database;
					$user_log_count = $database->select($sql, $parameters, 'all');
					//view_array($user_log_count);
					unset($sql, $parameters);
				*/

				//build the result array
				$result["plugin"] = "totp";
				$result["domain_name"] = $_SESSION["domain_name"];
				$result["username"] = $_SESSION["username"] ?? null;
				$result["user_uuid"] = $_SESSION["user_uuid"];
				$result["domain_uuid"] = $_SESSION["domain_uuid"];
				$result["contact_uuid"] = $_SESSION["contact_uuid"];
				$result["authorized"] = $auth_valid ? true : false;

				//add the failed login to user logs
				if (!$auth_valid) {
					user_logs::add($result);
				}

				//retun the array
				return $result;

				//$_SESSION['authentication']['plugin']['totp']['plugin'] = "totp";
				//$_SESSION['authentication']['plugin']['totp']['domain_name'] = $_SESSION["domain_name"];
				//$_SESSION['authentication']['plugin']['totp']['username'] = $row['username'];
				//$_SESSION['authentication']['plugin']['totp']['user_uuid'] = $_SESSION["user_uuid"];
				//$_SESSION['authentication']['plugin']['totp']['contact_uuid'] = $_SESSION["contact_uuid"];
				//$_SESSION['authentication']['plugin']['totp']['domain_uuid'] =  $_SESSION["domain_uuid"];
				//$_SESSION['authentication']['plugin']['totp']['authorized'] = $auth_valid ? true : false;
			}

	}
}

?>