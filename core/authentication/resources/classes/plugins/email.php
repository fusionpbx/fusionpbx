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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * plugin_email
 */
class plugin_email {

	/**
	 * Declare public variables
	 */
	public $domain_name;
	public $domain_uuid;
	public $username;
	public $password;
	public $user_uuid;
	public $user_email;
	public $contact_uuid;
	public $debug;

	/**
	 * Declare Private variables
	 *
	 * @var mixed
	 */
	private $database;

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
	 * time based one time password with email
	 * @return array [authorized] => true or false
	 */
	function email(authentication $auth, settings $settings) {

		//pre-process some settings
			$theme_favicon = $settings->get('theme', 'favicon', PROJECT_PATH.'/themes/default/favicon.ico');
			$theme_logo = $settings->get('theme', 'logo', PROJECT_PATH.'/themes/default/images/logo_login.png');
			$theme_login_type = $settings->get('theme', 'login_brand_type', '');
			$theme_login_image = $settings->get('theme', 'login_brand_image', '');
			$theme_login_text = $settings->get('theme', 'login_brand_text', '');
			$theme_login_logo_width = $settings->get('theme', 'login_logo_width', 'auto; max-width: 300px');
			$theme_login_logo_height = $settings->get('theme', 'login_logo_height', 'auto; max-height: 300px');
			$theme_message_delay = 1000 * (float)$settings->get('theme', 'message_delay', 3000);
			$background_videos = $settings->get('theme', 'background_video', null);
			$theme_background_video = (isset($background_videos) && is_array($background_videos)) ? $background_videos[0] : null;
			//$login_domain_name_visible = $settings->get('login', 'domain_name_visible');
			//$login_domain_name = $settings->get('login', 'domain_name');
			$login_destination = $settings->get('login', 'destination');
			$users_unique = $settings->get('users', 'unique', '');

		//get the domain
			$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
			$domain_name = $domain_array[0];

		//use the session username
			if (isset($_SESSION['username'])) {
				$_POST['username'] = $_SESSION['username'];
				$_REQUEST['username'] = $_SESSION['username'];
			}

		//request the username
			if (!isset($_POST['username']) && !isset($_POST['authentication_code'])) {

				//add multi-lingual support
				$language = new text;
				$text = $language->get(null, '/core/authentication');

				//initialize a template object
				$view = new template();
				$view->engine = 'smarty';
				$view->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/core/authentication/resources/views/';
				$view->cache_dir = sys_get_temp_dir();
				$view->init();

				//assign default values to the template
				$view->assign("project_path", PROJECT_PATH);
				$view->assign("login_destination_url", $login_destination);
				$view->assign("favicon", $theme_favicon);
				$view->assign("login_title", $text['label-username']);
				$view->assign("login_username", $text['label-username']);
				$view->assign("login_logo_width", $theme_login_logo_width);
				$view->assign("login_logo_height", $theme_login_logo_height);
				$view->assign("login_logo_source", $theme_logo);
				$view->assign("button_login", $text['button-login']);
				$view->assign("message_delay", $theme_message_delay);
				$view->assign("background_video", $theme_background_video);

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
				//if (!isset($this->username) && isset($_REQUEST['username'])) {
				//	$this->username = $_REQUEST['username'];
				//}

				//get the user details
				$sql = "select user_uuid, username, user_email, contact_uuid \n";
				$sql .= "from v_users\n";
				$sql .= "where (\n";
				$sql .= "	username = :username\n";
				$sql .= "	or user_email = :username\n";
				$sql .= ")\n";
				if ($users_unique != "global") {
					//unique username per domain (not globally unique across system - example: email address)
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				}
				$sql .= "and (user_type = 'default' or user_type is null) ";
				$parameters['username'] = $_REQUEST['username'];
				$row = $this->database->select($sql, $parameters, 'row');
				unset($parameters);

				//set class variables
				//if (!empty($row["user_email"])) {
				//	$this->user_uuid = $row['user_uuid'];
				//	$this->user_email = $row['user_email'];
				//	$this->contact_uuid = $row['contact_uuid'];
				//}

				//set a few session variables
				$_SESSION["user_uuid"] = $row['user_uuid'];
				$_SESSION["username"] = $row['username'];
				$_SESSION["user_email"] = $row['user_email'];
				$_SESSION["contact_uuid"] = $row["contact_uuid"];

				//user not found
				if (empty($row) || !is_array($row) || @sizeof($row) == 0) {
					//clear submitted usernames
					unset($this->username, $_SESSION['username'], $_REQUEST['username'], $_POST['username']);

					//clear authentication session
					unset($_SESSION['authentication']);

					//build the result array
					$result["plugin"] = "email";
					$result["domain_uuid"] = $_SESSION["domain_uuid"];
					$result["domain_name"] = $_SESSION["domain_name"];
					$result["authorized"] = false;

					//retun the array
					return $result;
				}

				//user email not found
				else if (empty($row["user_email"])) {
					//clear submitted usernames
					unset($this->username, $_SESSION['username'], $_REQUEST['username'], $_POST['username']);

					//clear authentication session
					unset($_SESSION['authentication']);

					//build the result array
					$result["plugin"] = "email";
					$result["domain_name"] = $_SESSION["domain_name"];
					$result["username"] = $_REQUEST['username'];
					$result["user_uuid"] = $_SESSION["user_uuid"];
					$result["domain_uuid"] = $_SESSION["domain_uuid"];
					$result["contact_uuid"] = $_SESSION["contact_uuid"];
					$result["authorized"] = false;

					//add the failed login to user logs
					user_logs::add($result);

					//return the array
					return $result;
				}

				//authentication code
				$_SESSION["user"]["authentication"]["email"]["code"] = generate_password(6, 1);
				$_SESSION["user"]["authentication"]["email"]["epoch"] = time();

				//$_SESSION["authentication_address"] = $_SERVER['REMOTE_ADDR'];
				//$_SESSION["authentication_date"] = 'now()';

				//set the authentication code
				//$sql = "update v_users \n";
				//$sql .= "set auth_code = :auth_code \n";
				//$sql .= "where user_uuid = :user_uuid;";
				//$parameters['auth_code'] = $auth_code_hash;
				//$parameters['user_uuid'] = $this->user_uuid;
				//$this->database->execute($sql, $parameters);
				//unset($sql);

				//email settings
				//$email_address = $this->user_email;
				//$email_subject = 'Validation Code';
				//$email_body = 'Validation Code: '.$authentication_code;

				//send email with the authentication_code
				//ob_start();
				//$sent = !send_email($email_address, $email_subject, $email_body, $email_error, null, null, 3, 3) ? false : true;
				//$response = ob_get_clean();

				//get the language code
				$language_code = $settings->get('domain', 'language', 'en-us');

				//get the email template from the database
				$sql = "select template_subject, template_body ";
				$sql .= "from v_email_templates ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$sql .= "and template_language = :template_language ";
				$sql .= "and template_category = :template_category ";
				$sql .= "and template_subcategory = :template_subcategory ";
				$sql .= "and template_type = :template_type ";
				$sql .= "and template_enabled = true ";
				$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				$parameters['template_language'] = $language_code;
				$parameters['template_category'] = 'authentication';
				$parameters['template_subcategory'] = 'email';
				$parameters['template_type'] = 'html';
				$row = $this->database->select($sql, $parameters, 'row');
				$email_subject = $row['template_subject'];
				$email_body = $row['template_body'];
				unset($sql, $parameters, $row);

				//replace variables in email subject
				$email_subject = str_replace('${domain_name}', $_SESSION["domain_name"], $email_subject);

				//replace variables in email body
				$email_body = str_replace('${domain_name}', $_SESSION["domain_name"], $email_body);
				$email_body = str_replace('${auth_code}', $_SESSION["user"]["authentication"]["email"]["code"], $email_body);

				//get the email from name and address
				$email_from_address = $_SESSION['email']['smtp_from']['text'];
				$email_from_name = $_SESSION['email']['smtp_from_name']['text'];

				//get the email send mode options: direct or email_queue
				$email_send_mode = $_SESSION['authentication']['email_send_mode']['text'] ?? 'email_queue';

				//send the email
				if ($email_send_mode == 'email_queue') {
					//set the variables
					$email_queue_uuid = uuid();
					$email_uuid = uuid();
					$hostname = gethostname();

					//add the temporary permissions
					$p = permissions::new();
					$p->add("email_queue_add", 'temp');
					$p->add("email_queue_edit", 'temp');

					$array['email_queue'][0]["email_queue_uuid"] = $email_queue_uuid;
					$array['email_queue'][0]["domain_uuid"] = $_SESSION["domain_uuid"];
					$array['email_queue'][0]["hostname"] = $hostname;
					$array['email_queue'][0]["email_date"] = 'now()';
					$array['email_queue'][0]["email_from"] = $email_from_address;
					$array['email_queue'][0]["email_to"] = $_SESSION["user_email"];
					$array['email_queue'][0]["email_subject"] = $email_subject;
					$array['email_queue'][0]["email_body"] = $email_body;
					$array['email_queue'][0]["email_status"] = 'waiting';
					$array['email_queue'][0]["email_retry_count"] = 3;
					$array['email_queue'][0]["email_uuid"] = $email_uuid;
					$array['email_queue'][0]["email_action_before"] = null;
					$array['email_queue'][0]["email_action_after"] = null;
					$this->database->save($array);
					$err = $this->database->message;
					unset($array);

					//remove the temporary permission
					$p->delete("email_queue_add", 'temp');
					$p->delete("email_queue_edit", 'temp');
				}
				else {
					//send email - direct
					$email = new email;
					$email->recipients = $_SESSION["user_email"];
					$email->subject = $email_subject;
					$email->body = $email_body;
					$email->from_address = $email_from_address;
					$email->from_name = $email_from_name;
					//$email->attachments = $email_attachments;
					$email->debug_level = 0;
					$email->method = 'direct';
					$sent = $email->send();
				}

				//debug informations
				//$email_response = $email->response;
				//$email_error = $email->email_error;
				//echo $email_response."<br />\n";
				//echo $email_error."<br />\n";

				//get the domain
				$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
				$domain_name = $domain_array[0];

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
				$view->cache_dir = sys_get_temp_dir();
				$view->init();

				//assign default values to the template
				$view->assign("project_path", PROJECT_PATH);
				$view->assign("login_destination_url", $login_destination);
				$view->assign("favicon", $theme_favicon);
				$view->assign("login_title", $text['label-verify']);
				$view->assign("login_email_description", $text['label-email_description']);
				$view->assign("login_authentication_code", $text['label-authentication_code']);
				$view->assign("login_logo_width", $theme_login_logo_width);
				$view->assign("login_logo_height", $theme_login_logo_height);
				$view->assign("login_logo_source", $theme_logo);
				$view->assign("button_verify", $text['label-verify']);
				$view->assign("message_delay", $theme_message_delay);
				if (!empty($_SESSION['username'])) {
					$view->assign("username", $_SESSION['username']);
					$view->assign("button_cancel", $text['button-cancel']);
				}

				//messages
				$view->assign('messages', message::html(true, '		'));

				//show the views
				$content = $view->render('email.htm');
				echo $content;
				exit;
			}

		//if authorized then verify
			if (isset($_POST['authentication_code'])) {

				//check if the authentication code has expired. if expired return false
				if (!empty($_SESSION["user"]) && $_SESSION["user"]["authentication"]["email"]["epoch"] + 3 > time()) {
					//authentication code expired
					$result["plugin"] = "email";
					$result["domain_name"] = $_SESSION["domain_name"];
					$result["username"] = $_SESSION["username"];
					$result["error_message"] = 'code expired';
					$result["authorized"] = false;
					print_r($result);
					return $result;
					exit;
				}

				//get the user details
				$sql = "select user_uuid, user_email, contact_uuid\n";
				$sql .= "from v_users\n";
				$sql .= "where (\n";
				$sql .= "	username = :username\n";
				$sql .= "	or user_email = :username\n";
				$sql .= ")\n";
				if ($users_unique != "global") {
					//unique username per domain (not globally unique across system - example: email address)
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				}
				$parameters['username'] = $_SESSION["username"];
				$row = $this->database->select($sql, $parameters, 'row');
				$this->user_uuid = $row['user_uuid'];
				$this->user_email = $row['user_email'];
				$this->contact_uuid = $row['contact_uuid'];
				unset($parameters);
				/*
				echo 'session code = '.$_SESSION["user"]["authentication"]["email"]["code"].'<br>';
				echo 'post code = '.$_POST['authentication_code'].'<br>';
				exit;
				*/

				//validate the code
				if (!empty($_SESSION["user"]) && $_SESSION["user"]["authentication"]["email"]["code"] === $_POST['authentication_code']) {
					$auth_valid = true;
				}
				else {
					$auth_valid = false;
				}

				//clear posted authentication code
				unset($_POST['authentication_code']);

				//check if contacts app exists
				$contacts_exists = file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/core/contacts/') ? true : false;

				//get the user details
				if ($auth_valid) {
					//get user data from the database
					$sql = "select ";
					$sql .= "	u.user_uuid, ";
					$sql .= "	u.username, ";
					$sql .= "	u.user_email, ";
					$sql .= "	u.contact_uuid ";
					if ($contacts_exists) {
						$sql .= ",";
						$sql .= "c.contact_organization, ";
						$sql .= "c.contact_name_given, ";
						$sql .= "c.contact_name_family, ";
						$sql .= "a.contact_attachment_uuid ";
					}
					$sql .= "from ";
					$sql .= "	v_users as u ";
					if ($contacts_exists) {
						$sql .= "left join v_contacts as c on u.contact_uuid = c.contact_uuid and u.contact_uuid is not null ";
						$sql .= "left join v_contact_attachments as a on u.contact_uuid = a.contact_uuid and u.contact_uuid is not null and a.attachment_primary = true and a.attachment_filename is not null and a.attachment_content is not null ";
					}
					$sql .= "where ";
					$sql .= "	u.user_uuid = :user_uuid ";
					if ($users_unique != "global") {
						//unique username per domain (not globally unique across system - example: email address)
						$sql .= "and u.domain_uuid = :domain_uuid ";
						$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
					}
					$parameters['user_uuid'] = $_SESSION["user_uuid"];
					$row = $this->database->select($sql, $parameters, 'row');
					unset($parameters);

					//set a few session variables
					//$_SESSION["username"] = $row['username']; //setting the username makes it skip the rest of the authentication
					//$_SESSION["user_email"] = $row['user_email'];
					//$_SESSION["contact_uuid"] = $row["contact_uuid"];
				}
				else {
// 					//destroy session
// 					session_unset();
// 					session_destroy();
// 					//$_SESSION['authentication']['plugin']
// 					//send http 403
// 					header('HTTP/1.0 403 Forbidden', true, 403);
//
// 					//redirect to the root of the website
// 					header("Location: ".PROJECT_PATH."/");
//
// 					//exit the code
// 					exit();

					//clear submitted usernames
					unset($this->username, $_SESSION['username'], $_REQUEST['username'], $_POST['username']);

					//clear authentication session
					unset($_SESSION['authentication']);

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
					$user_log_count = $this->database->select($sql, $parameters, 'all');
					//view_array($user_log_count);
					unset($sql, $parameters);
				*/

				//result array
				$result["plugin"] = "email";
				$result["domain_name"] = $_SESSION["domain_name"];
				$result["username"] = $_SESSION["username"];
				$result["user_uuid"] = $_SESSION["user_uuid"];
				$result["domain_uuid"] = $_SESSION["domain_uuid"];
				if ($contacts_exists) {
					$result["contact_uuid"] = $_SESSION["contact_uuid"];
					$result["contact_organization"] = $row["contact_organization"];
					$result["contact_name_given"] = $row["contact_name_given"];
					$result["contact_name_family"] = $row["contact_name_family"];
					$result["contact_image"] = $row["contact_attachment_uuid"];
				}
				$result["authorized"] = $auth_valid ? true : false;

				//add the failed login to user logs
				if (!$auth_valid) {
					user_logs::add($result);
				}

				//retun the array
				return $result;

				//$_SESSION['authentication']['plugin']['email']['plugin'] = "email";
				//$_SESSION['authentication']['plugin']['email']['domain_name'] = $_SESSION["domain_name"];
				//$_SESSION['authentication']['plugin']['email']['username'] = $row['username'];
				//$_SESSION['authentication']['plugin']['email']['user_uuid'] = $_SESSION["user_uuid"];
				//$_SESSION['authentication']['plugin']['email']['contact_uuid'] = $_SESSION["contact_uuid"];
				//$_SESSION['authentication']['plugin']['email']['domain_uuid'] =  $_SESSION["domain_uuid"];
				//$_SESSION['authentication']['plugin']['email']['authorized'] = $auth_valid ? true : false;
			}

	}
}
