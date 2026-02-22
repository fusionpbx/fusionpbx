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
	public $contact_organization;
	public $contact_name_given;
	public $contact_name_family;
	public $contact_image;
	public $username;
	public $password;
	public $key;
	public $debug;
	public $user_email;

	/**
	 * database checks the local database to authenticate the user or key
	 *
	 * @return array [authorized] => true or false
	 */
	function database(authentication $auth, settings $settings) {

		//pre-process some settings
		$theme_favicon             = $settings->get('theme', 'favicon', PROJECT_PATH . '/themes/default/favicon.ico');
		$theme_logo                = $settings->get('theme', 'logo', PROJECT_PATH . '/themes/default/images/logo_login.png');
		$theme_login_type          = $settings->get('theme', 'login_brand_type', '');
		$theme_login_image         = $settings->get('theme', 'login_brand_image', '');
		$theme_login_text          = $settings->get('theme', 'login_brand_text', '');
		$theme_login_logo_width    = $settings->get('theme', 'login_logo_width', 'auto; max-width: 300px');
		$theme_login_logo_height   = $settings->get('theme', 'login_logo_height', 'auto; max-height: 300px');
		$theme_message_delay       = 1000 * (float)$settings->get('theme', 'message_delay', 3000);
		$background_videos         = $settings->get('theme', 'background_video', []);
		$theme_background_video    = (isset($background_videos[0])) ? $background_videos[0] : '';
		$login_domain_name_visible = $settings->get('login', 'domain_name_visible', false);
		$login_domain_name         = $settings->get('login', 'domain_name');
		$login_remember_me         = $settings->get('login', 'remember_me');
		$login_destination         = $settings->get('login', 'destination');
		$users_unique              = $settings->get('users', 'unique', '');

		//set the default login type and image
		if (empty($theme_login_type)) {
			$theme_login_type  = 'image';
			$theme_login_image = $theme_logo;
		}

		//determine whether to show the forgot password for resetting the password
		$login_password_reset_enabled = false;
		if (!empty($settings->get('login', 'password_reset_key'))) {
			$login_password_reset_enabled = true;
		}

		//check if already authorized
		if (isset($_SESSION['authentication']['plugin']['database']) && $_SESSION['authentication']['plugin']['database']["authorized"]) {
			return;
		}

		//show the authentication code view
		if (empty($_REQUEST["username"]) && empty($_REQUEST["key"])) {

			//get the domain
			$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
			$domain_name  = $domain_array[0];

			//create token
			//$object = new token;
			//$token = $object->create('login');

			//add multi-lingual support
			$language = new text;
			$text     = $language->get(null, '/core/authentication');

			//initialize a template object
			$view               = new template();
			$view->engine       = 'smarty';
			$view->template_dir = dirname(__DIR__, 5) . '/core/authentication/resources/views/';
			$view->cache_dir    = sys_get_temp_dir();
			$view->init();

			//add translations
			$view->assign("login_title", $text['button-login']);
			$view->assign("label_username", $text['label-username']);
			$view->assign("label_password", $text['label-password']);
			$view->assign("label_domain", $text['label-domain']);
			$view->assign("label_remember_me", $text['label-remember_me']);
			$view->assign("button_login", $text['button-login']);

			//assign default values to the template
			$view->assign("project_path", PROJECT_PATH);
			$view->assign("login_destination_url", $login_destination);
			$view->assign("login_domain_name_visible", $login_domain_name_visible);
			$view->assign("login_domain_names", $login_domain_name);
			$view->assign("login_remember_me", $login_remember_me);
			$view->assign("login_password_reset_enabled", $login_password_reset_enabled);
			$view->assign("favicon", $theme_favicon);
			$view->assign("login_logo_width", $theme_login_logo_width);
			$view->assign("login_logo_height", $theme_login_logo_height);
			$view->assign("login_logo_source", $theme_logo);
			$view->assign("message_delay", $theme_message_delay);
			$view->assign("background_video", $theme_background_video);
			$view->assign("login_password_description", $text['label-password_description']);
			$view->assign("button_cancel", $text['button-cancel']);
			$view->assign("button_forgot_password", $text['button-forgot_password']);

			//assign openid values to the template
			if ($settings->get('open_id', 'enabled', false)) {
				$classes = $settings->get('open_id', 'methods', []);
				$banners = [];
				foreach ($classes as $open_id_class) {
					if (class_exists($open_id_class)) {
						$banners[] = [
							'name' => $open_id_class,
							'image' => $open_id_class::get_banner_image($settings),
							'class' => $open_id_class::get_banner_css_class($settings),
							'url' => '/app/open_id/open_id.php?action=' . $open_id_class,
						];
					}
				}
				if (count($banners) > 0) {
					$view->assign('banners', $banners);
				}
			}

			//assign user to the template
			if (!empty($_SESSION['username'])) {
				$view->assign("username", $_SESSION['username']);
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
			$this->username       = $_REQUEST["username"];
			$_SESSION['username'] = $this->username;
		}
		if (isset($_REQUEST["password"])) {
			$this->password = $_REQUEST["password"];
		}
		if (isset($_POST["remember"])) {
			$_SESSION['remember'] = $_POST["remember"];
		}
		if (isset($_REQUEST["key"])) {
			$this->key = $_REQUEST["key"];
		}
		if (isset($_REQUEST["domain_name"])) {
			$domain_name       = $_REQUEST["domain_name"];
			$this->domain_name = $_REQUEST["domain_name"];
		}

		//get the domain name
		$auth->get_domain();
		$this->username = $_SESSION['username'] ?? null;
		//$this->domain_uuid = $_SESSION['domain_uuid'] ?? null;
		//$this->domain_name = $_SESSION['domain_name'] ?? null;

		//debug information
		//echo "domain_uuid: ".$this->domain_uuid."<br />\n";
		//view_array($this->domain_uuid, false);
		//echo "domain_name: ".$this->domain_name."<br />\n";
		//echo "username: ".$this->username."<br />\n";

		//set the default status
		$user_authorized = false;

		//check if contacts app exists
		$contacts_exists = file_exists(dirname(__DIR__, 5) . '/core/contacts/') ? true : false;

		//check the username and password if they don't match then redirect to the login
		$sql = "select ";
		$sql .= "	d.domain_name, ";
		$sql .= "	u.user_uuid, ";
		$sql .= "	u.contact_uuid, ";
		$sql .= "	u.username, ";
		$sql .= "	u.password, ";
		$sql .= "	u.user_email, ";
		$sql .= "	u.salt, ";
		$sql .= "	u.api_key, ";
		$sql .= "	u.domain_uuid ";
		$sql .= "from ";
		$sql .= "	v_domains as d, ";
		$sql .= "	v_users as u ";
		$sql .= "where ";
		$sql .= "	u.domain_uuid = d.domain_uuid ";
		$sql .= "	and (";
		$sql .= "		user_type = 'default' ";
		$sql .= "		or user_type is null";
		$sql .= "	) ";
		if (isset($this->key) && strlen($this->key) > 30) {
			$sql                   .= "and u.api_key = :api_key ";
			$parameters['api_key'] = $this->key;
		} else {
			$sql                    .= "and (\n";
			$sql                    .= "	lower(u.username) = lower(:username)\n";
			$sql                    .= "	or lower(u.user_email) = lower(:username)\n";
			$sql                    .= ")\n";
			$parameters['username'] = $this->username;
		}
		if ($users_unique === "global") {
			//unique username - global (example: email address)
		} else {
			//unique username - per domain
			$sql                       .= "and u.domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		$sql .= "and (user_enabled = true or user_enabled is null) ";
		$row = $settings->database()->select($sql, $parameters, 'row');
		if (!empty($row) && is_array($row) && @sizeof($row) != 0) {

			//validate the password
			$valid_password = false;
			if (isset($this->key) && strlen($this->key) > 30 && $this->key === $row["api_key"]) {
				$valid_password = true;
			} elseif (substr($row["password"], 0, 1) === '$') {
				if (isset($this->password) && !empty($this->password)) {
					if (password_verify($this->password, $row["password"])) {
						$valid_password = true;
					}
				}
			} else {
				//deprecated - compare the password provided by the user with the one in the database
				if (md5($row["salt"] . $this->password) === $row["password"]) {
					$row["password"] = crypt($this->password, '$1$' . $row['salt'] . '$');
					$valid_password  = true;
				}
			}

			//set the domain and user settings
			if ($valid_password) {
				//set the domain_uuid
				$this->domain_uuid = $row["domain_uuid"];
				$this->domain_name = $row["domain_name"];

				//set the domain session variables
				$_SESSION["domain_uuid"] = $this->domain_uuid;
				$_SESSION["domain_name"] = $this->domain_name;

				//set the domain setting
				if ($users_unique === "global" && $row["domain_uuid"] !== $this->domain_uuid) {
					$domain = new domains();
					$domain->set();
				}

				//set the variables
				$this->user_uuid    = $row['user_uuid'];
				$this->username     = $row['username'];
				$this->user_email   = $row['user_email'];
				$this->contact_uuid = $row['contact_uuid'];

				//get the user contact details
				if ($contacts_exists) {
					unset($parameters);
					$sql                        = "select ";
					$sql                        .= " c.contact_organization, ";
					$sql                        .= " c.contact_name_given, ";
					$sql                        .= " c.contact_name_family, ";
					$sql                        .= " a.contact_attachment_uuid ";
					$sql                        .= "from v_contacts as c ";
					$sql                        .= "left join v_contact_attachments as a on c.contact_uuid = a.contact_uuid ";
					$sql                        .= "where c.contact_uuid = :contact_uuid ";
					$sql                        .= "and c.domain_uuid = :domain_uuid ";
					$sql                        .= "and a.attachment_primary = true ";
					$sql                        .= "and a.attachment_filename is not null ";
					$sql                        .= "and a.attachment_content is not null ";
					$parameters['domain_uuid']  = $this->domain_uuid;
					$parameters['contact_uuid'] = $this->contact_uuid;
					$contact                    = $settings->database()->select($sql, $parameters, 'row');
					$this->contact_organization = $contact['contact_organization'] ?? '';
					$this->contact_name_given   = $contact['contact_name_given'] ?? '';
					$this->contact_name_family  = $contact['contact_name_family'] ?? '';
					$this->contact_image        = $contact['contact_attachment_uuid'] ?? '';
				}

				//debug info
				//echo "user_uuid ".$this->user_uuid."<br />\n";
				//echo "username ".$this->username."<br />\n";
				//echo "contact_uuid ".$this->contact_uuid."<br />\n";

				//set a few session variables
				$_SESSION["user_uuid"]    = $row['user_uuid'];
				$_SESSION["username"]     = $row['username'];
				$_SESSION["user_email"]   = $row['user_email'];
				$_SESSION["contact_uuid"] = $row["contact_uuid"];
			}

			//check to to see if the the password hash needs to be updated
			if ($valid_password) {
				//set the password hash cost
				$options = ['cost' => 10];

				//check if a newer hashing algorithm is available or the cost has changed
				if (password_needs_rehash($row["password"], PASSWORD_DEFAULT, $options)) {

					//build user insert array
					$array                            = [];
					$array['users'][0]['user_uuid']   = $this->user_uuid;
					$array['users'][0]['domain_uuid'] = $this->domain_uuid;
					$array['users'][0]['user_email']  = $this->user_email;
					$array['users'][0]['password']    = password_hash($this->password, PASSWORD_DEFAULT, $options);
					$array['users'][0]['salt']        = null;

					//build user group insert array
					$array['user_groups'][0]['user_group_uuid'] = uuid();
					$array['user_groups'][0]['domain_uuid']     = $this->domain_uuid;
					$array['user_groups'][0]['group_name']      = 'user';
					$array['user_groups'][0]['user_uuid']       = $this->user_uuid;

					//grant temporary permissions
					$p = permissions::new();
					$p->add('user_edit', 'temp');

					//execute insert
					$settings->database()->app_name = 'authentication';
					$settings->database()->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932be0e41f1';
					$settings->database()->save($array);
					unset($array);

					//revoke temporary permissions
					$p->delete('user_edit', 'temp');

				}

			}

			//result array
			if ($valid_password) {
				$result["plugin"]       = "database";
				$result["domain_name"]  = $this->domain_name;
				$result["username"]     = $this->username;
				$result["user_uuid"]    = $this->user_uuid;
				$result["domain_uuid"]  = $_SESSION['domain_uuid'];
				$result["contact_uuid"] = $this->contact_uuid;
				if ($contacts_exists) {
					$result["contact_organization"] = $this->contact_organization;
					$result["contact_name_given"]   = $this->contact_name_given;
					$result["contact_name_family"]  = $this->contact_name_family;
					$result["contact_image"]        = $this->contact_image;
				}
				$result["user_email"] = $this->user_email;
				$result["sql"]        = $sql;
				$result["authorized"] = $valid_password;
			}

			//return the results
			return $result ?? false;

		}

		return;

	}
}

?>
