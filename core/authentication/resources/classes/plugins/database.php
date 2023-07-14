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
 * plugin_database
 *
 * @method plugin_database validates the authentication using information from the database
 */
class plugin_database {

	/**
	 * Define variables and their scope
	 */
	public $domain_name;
	public $domain_uuid;
	public $user_uuid;
	public $contact_uuid;
	public $username;
	public $password;
	public $key;
	public $debug;

	/**
	 * database checks the local database to authenticate the user or key
	 * @return array [authorized] => true or false
	 */
	function database() {

		//pre-process some settings
			$settings['theme']['favicon'] = !empty($_SESSION['theme']['favicon']['text']) ? $_SESSION['theme']['favicon']['text'] : PROJECT_PATH.'/themes/default/favicon.ico';
			$settings['login']['destination'] = !empty($_SESSION['login']['destination']['text']) ? $_SESSION['login']['destination']['text'] : '';
			$settings['users']['unique'] = !empty($_SESSION['users']['unique']['text']) ? $_SESSION['users']['unique']['text'] : '';
			$settings['theme']['logo'] = !empty($_SESSION['theme']['logo']['text']) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH.'/themes/default/images/logo_login.png';
			$settings['theme']['login_logo_width'] = !empty($_SESSION['theme']['login_logo_width']['text']) ? $_SESSION['theme']['login_logo_width']['text'] : 'auto; max-width: 300px';
			$settings['theme']['login_logo_height'] = !empty($_SESSION['theme']['login_logo_height']['text']) ? $_SESSION['theme']['login_logo_height']['text'] : 'auto; max-height: 300px';
			$settings['theme']['message_delay'] = isset($_SESSION['theme']['message_delay']) ? 1000 * (float) $_SESSION['theme']['message_delay'] : 3000;

		//already authorized
			if (isset($_SESSION['authentication']['plugin']['database']) && $_SESSION['authentication']['plugin']['database']["authorized"]) {
				return;
			}
			else {
				if (isset($_SESSION['authentication']['plugin']['database']) && !$_SESSION['authentication']['plugin']['database']["authorized"]) {
					//authorized false
				}
			}

		//show the authentication code view
			if (empty($_REQUEST["username"]) && empty($_REQUEST["key"])) {

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

				//add translations
					$view->assign("login_title", $text['button-login']);
					$view->assign("label_username", $text['label-username']);
					$view->assign("label_password", $text['label-password']);
					$view->assign("button_login", $text['button-login']);

				//assign default values to the template
					$view->assign("project_path", PROJECT_PATH);
					$view->assign("login_destination_url", $settings['login']['destination']);
					$view->assign("favicon", $settings['theme']['favicon']);
					$view->assign("login_logo_width", $settings['theme']['login_logo_width']);
					$view->assign("login_logo_height", $settings['theme']['login_logo_height']);
					$view->assign("login_logo_source", $settings['theme']['logo']);
					$view->assign("message_delay", $settings['theme']['message_delay']);
// 					if (!empty($_SESSION['authentication']['plugin']['database']['authorized']) && $_SESSION['authentication']['plugin']['database']['authorized'] == 1 && !empty($_SESSION['username'])) {
					if (!empty($_SESSION['username'])) {
						$view->assign("login_password_description", $text['label-password_description']);
						$view->assign("username", $_SESSION['username']);
						$view->assign("button_cancel", $text['button-cancel']);
					}

				//messages
					$view->assign('messages', message::html(true, '		'));

				//add the token name and hash to the view
					//$view->assign("token_name", $token['name']);
					//$view->assign("token_hash", $token['hash']);

				//show the views
					$content = $view->render('login.htm');
					echo $content;
					exit;
			}

		//validate the token
			//$token = new token;
			//if (!$token->validate($_SERVER['PHP_SELF'])) {
			//	message::add($text['message-invalid_token'],'negative');
			//	header('Location: domains.php');
			//	exit;
			//}

		//add the authentication details
			if (isset($_REQUEST["username"])) {
				$this->username = $_REQUEST["username"];
				$_SESSION['username'] = $this->username;
			}
			if (isset($_REQUEST["password"])) {
				$this->password = $_REQUEST["password"];
			}
			if (isset($_SESSION['username'])) {
				$this->username = $_SESSION['username'];
			}
			if (isset($_REQUEST["key"])) {
				$this->key = $_REQUEST["key"];
			}

		//get the domain name
			$auth = new authentication;
			$auth->get_domain();
			$this->domain_uuid = $_SESSION['domain_uuid'];
			$this->domain_name = $_SESSION['domain_name'];
			$this->username = $_SESSION['username'] ?? null;

		//debug information
			//echo "domain_uuid: ".$this->domain_uuid."<br />\n";
			//echo "domain_name: ".$this->domain_name."<br />\n";
			//echo "username: ".$this->username."<br />\n";

		//set the default status
			$user_authorized = false;

		//check the username and password if they don't match then redirect to the login
			$sql = "select u.user_uuid, u.contact_uuid, u.username, u.password, ";
			$sql .= "u.user_email, u.salt, u.api_key, u.domain_uuid, d.domain_name ";
			$sql .= "from v_users as u, v_domains as d ";
			$sql .= "where u.domain_uuid = d.domain_uuid ";
			$sql .= "and (user_type = 'default' or user_type is null) ";
			if (isset($this->key) && strlen($this->key) > 30) {
				$sql .= "and u.api_key = :api_key ";
				$parameters['api_key'] = $this->key;
			}
			else {
				$sql .= "and (\n";
				$sql .= "	lower(u.username) = lower(:username)\n";
				$sql .= "	or lower(u.user_email) = lower(:username)\n";
				$sql .= ")\n";
				$parameters['username'] = $this->username;
			}
			if ($settings['users']['unique'] === "global") {
				//unique username - global (example: email address)
			}
			else {
				//unique username - per domain
				$sql .= "and u.domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
			}
			$sql .= "and (user_enabled = 'true' or user_enabled is null) ";
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (!empty($row) && is_array($row) && @sizeof($row) != 0) {

				//set the domain details
					$this->domain_uuid = $_SESSION['domain_uuid'];
					$this->domain_name = $_SESSION['domain_name'];

				//get the domain uuid when users are unique globally
					if ($settings['users']['unique'] === "global" && $row["domain_uuid"] !== $this->domain_uuid) {
						//set the domain_uuid
							$this->domain_uuid = $row["domain_uuid"];
							$this->domain_name = $row["domain_name"];

						//set the domain session variables
							$_SESSION["domain_uuid"] = $this->domain_uuid;
							$_SESSION["domain_name"] = $this->domain_name;

						//set the setting arrays
							$domain = new domains();
							$domain->set();
					}

				//set the variables
					$this->user_uuid = $row['user_uuid'];
					$this->username = $row['username'];
					$this->user_email = $row['user_email'];
					$this->contact_uuid = $row['contact_uuid'];

				//debug info
					//echo "user_uuid ".$this->user_uuid."<br />\n";
					//echo "username ".$this->username."<br />\n";
					//echo "contact_uuid ".$this->contact_uuid."<br />\n";

				//set a few session variables
					$_SESSION["user_uuid"] = $row['user_uuid'];
					$_SESSION["username"] = $row['username'];
					$_SESSION["user_email"] = $row['user_email'];
					$_SESSION["contact_uuid"] = $row["contact_uuid"];

				//validate the password
					$valid_password = false;
					if (isset($this->key) && strlen($this->key) > 30 && $this->key === $row["api_key"]) {
						$valid_password = true;
					}
					else if (substr($row["password"], 0, 1) === '$') {
						if (isset($this->password) && !empty($this->password)) {
							if (password_verify($this->password, $row["password"])) {
								$valid_password = true;
							}
						}
					}
					else {
						//deprecated - compare the password provided by the user with the one in the database
						if (md5($row["salt"].$this->password) === $row["password"]) {
							$row["password"] = crypt($this->password, '$1$'.$password_salt.'$');
							$valid_password = true;
						}
					}

				//check to to see if the the password hash needs to be updated
					if ($valid_password) {
						//set the password hash cost
						$options = array('cost' => 10);

						//check if a newer hashing algorithm is available or the cost has changed
						if (password_needs_rehash($row["password"], PASSWORD_DEFAULT, $options)) {

							//build user insert array
								$array['users'][0]['user_uuid'] = $this->user_uuid;
								$array['users'][0]['domain_uuid'] = $this->domain_uuid;
								$array['users'][0]['user_email'] = $this->user_email;
								$array['users'][0]['password'] = password_hash($this->password, PASSWORD_DEFAULT, $options);
								$array['users'][0]['salt'] = null;

							//build user group insert array
								$array['user_groups'][0]['user_group_uuid'] = uuid();
								$array['user_groups'][0]['domain_uuid'] = $this->domain_uuid;
								$array['user_groups'][0]['group_name'] = 'user';
								$array['user_groups'][0]['user_uuid'] = $this->user_uuid;

							//grant temporary permissions
								$p = new permissions;
								$p->add('user_edit', 'temp');

							//execute insert
								$database = new database;
								$database->app_name = 'authentication';
								$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932be0e41f1';
								$database->save($array);
								unset($array);

							//revoke temporary permissions
								$p->delete('user_edit', 'temp');

						}

					}
					else {
						//clear authentication session
						if (empty($_SESSION['authentication']['methods']) || !is_array($_SESSION['authentication']['methods']) || sizeof($_SESSION['authentication']['methods']) == 0) {
							unset($_SESSION['authentication']);
						}

						// clear username
						if (!empty($_REQUEST["password"])) {
							unset($_SESSION['username'], $_REQUEST['username'], $_POST['username']);
							unset($_SESSION['authentication']);
						}
					}

					//result array
					if ($valid_password) {
						$result["plugin"] = "database";
						$result["domain_name"] = $this->domain_name;
						$result["username"] = $this->username;
						$result["user_uuid"] = $this->user_uuid;
						$result["domain_uuid"] = $_SESSION['domain_uuid'];
						$result["contact_uuid"] = $this->contact_uuid;
						$result["user_email"] = $this->user_email;
						$result["sql"] = $sql;
						$result["authorized"] = $valid_password;
					}

					//return the results
					return $result ?? false;

			}
			else {

				unset($_SESSION['username'], $_REQUEST['username'], $_POST['username']);
				unset($_SESSION['authentication']);

			}

		return;

	}
}

?>