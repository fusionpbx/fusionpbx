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
 * plugin_email
 *
 * @method email time based one time password authenticate the user
 */
class plugin_email {

	/**
	 * Define variables and their scope
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
	 * time based one time password with email
	 * @return array [authorized] => true or false
	 */
	function email() {

			//pre-process some settings
			$settings['theme']['favicon'] = !empty($_SESSION['theme']['favicon']['text']) ? $_SESSION['theme']['favicon']['text'] : PROJECT_PATH.'/themes/default/favicon.ico';
			$settings['login']['destination'] = !empty($_SESSION['login']['destination']['text']) ? $_SESSION['login']['destination']['text'] : '';
			$settings['users']['unique'] = !empty($_SESSION['users']['unique']['text']) ? $_SESSION['users']['unique']['text'] : '';
			$settings['theme']['logo'] = !empty($_SESSION['theme']['logo']['text']) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH.'/themes/default/images/logo_login.png';
			$settings['theme']['login_logo_width'] = !empty($_SESSION['theme']['login_logo_width']['text']) ? $_SESSION['theme']['login_logo_width']['text'] : 'auto; max-width: 300px';
			$settings['theme']['login_logo_height'] = !empty($_SESSION['theme']['login_logo_height']['text']) ? $_SESSION['theme']['login_logo_height']['text'] : 'auto; max-height: 300px';
			$settings['theme']['message_delay'] = isset($_SESSION['theme']['message_delay']) ? 1000 * (float) $_SESSION['theme']['message_delay'] : 3000;

			//set a default template
			$_SESSION['domain']['template']['name'] = 'default';
			$_SESSION['theme']['menu_brand_image']['text'] = PROJECT_PATH.'/themes/default/images/logo.png';
			$_SESSION['theme']['menu_brand_type']['text'] = 'image';

			//get the domain
			$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
			$domain_name = $domain_array[0];

			//temp directory
			$_SESSION['server']['temp']['dir'] = '/tmp';

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
				if ($settings['users']['unique'] != "global") {
					//unique username per domain (not globally unique across system - example: email address)
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				}
				$sql .= "and (user_type = 'default' or user_type is null) ";
				$parameters['username'] = $_REQUEST['username'];
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
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
				//$database->execute($sql, $parameters);
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
				$language_code = $_SESSION['domain']['language']['code'];

				//get the email template from the database
				$sql = "select template_subject, template_body ";
				$sql .= "from v_email_templates ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$sql .= "and template_language = :template_language ";
				$sql .= "and template_category = :template_category ";
				$sql .= "and template_subcategory = :template_subcategory ";
				$sql .= "and template_type = :template_type ";
				$sql .= "and template_enabled = 'true' ";
				$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
				$parameters['template_language'] = $language_code;
				$parameters['template_category'] = 'authentication';
				$parameters['template_subcategory'] = 'email';
				$parameters['template_type'] = 'html';
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
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
					$p = new permissions;
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
					$database = new database;
					$database->app_name = 'email queue';
					$database->app_uuid = '5befdf60-a242-445f-91b3-2e9ee3e0ddf7';
					$database->save($array);
					$err = $database->message;
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
				$view->assign("login_title", $text['label-verify']);
				$view->assign("login_email_description", $text['label-email_description']);
				$view->assign("login_authentication_code", $text['label-authentication_code']);
				$view->assign("login_logo_width", $settings['theme']['login_logo_width']);
				$view->assign("login_logo_height", $settings['theme']['login_logo_height']);
				$view->assign("login_logo_source", $settings['theme']['logo']);
				$view->assign("button_verify", $text['label-verify']);
				$view->assign("message_delay", $settings['theme']['message_delay']);
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

				//get the user details
				if ($auth_valid) {
					//get user data from the database
					$sql = "select user_uuid, username, user_email, contact_uuid from v_users ";
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
					$database = new database;
					$user_log_count = $database->select($sql, $parameters, 'all');
					//view_array($user_log_count);
					unset($sql, $parameters);
				*/

				//result array
				$result["plugin"] = "email";
				$result["domain_name"] = $_SESSION["domain_name"];
				$result["username"] = $_SESSION["username"];
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

?>
