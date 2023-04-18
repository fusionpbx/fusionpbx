<?php

/**
 * plugin_database
 *
 * @method validate uses authentication plugins to check if a user is authorized to login
 * @method get_domain used to get the domain name from the URL or username and then sets both domain_name and domain_uuid
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

	/**
	 * database checks the local database to authenticate the user or key
	 * @return array [authorized] => true or false
	 */
	function database() {

		//already authorized
			if (isset($_SESSION['authentication']['plugin']['database']) && $_SESSION['authentication']['plugin']['database']["authorized"]) {
				//echo __line__;
				return;
			}
			else {
				if (isset($_SESSION['authentication']['plugin']['database']) && !$_SESSION['authentication']['plugin']['database']["authorized"]) {
					//authorized false
					session_unset();
					session_destroy();
				}
			}

		//show the authentication code view
			if ($_REQUEST["username"] == '' && $_REQUEST["key"] == '') {

				//login logo source
					if (isset($_SESSION['theme']['logo_login']['text']) && $_SESSION['theme']['logo_login']['text'] != '') {
						$login_logo_source = $_SESSION['theme']['logo_login']['text'];
					}
					else if (isset($_SESSION['theme']['logo']['text']) && $_SESSION['theme']['logo']['text'] != '') {
						$login_logo_source = $_SESSION['theme']['logo']['text'];
					}
					else {
						$login_logo_source = PROJECT_PATH.'/themes/default/images/logo_login.png';
					}

				//login logo dimensions
					if (isset($_SESSION['theme']['login_logo_width']['text']) && $_SESSION['theme']['login_logo_width']['text'] != '') {
						$login_logo_width = $_SESSION['theme']['login_logo_width']['text'];
					}
					else {
						$login_logo_width = 'auto; max-width: 300px';
					}
					if (isset($_SESSION['theme']['login_logo_height']['text']) && $_SESSION['theme']['login_logo_height']['text'] != '') {
						$login_logo_height = $_SESSION['theme']['login_logo_height']['text'];
					}
					else {
						$login_logo_height = 'auto; max-height: 300px';
					}

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
					$view->assign("login_logo_width", $login_logo_width);
					$view->assign("login_logo_height", $login_logo_height);
					$view->assign("login_logo_source", $login_logo_source);

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

		//set the default status
			$user_authorized = false;

		//check the username and password if they don't match then redirect to the login
			$sql = "select u.user_uuid, u.contact_uuid, u.username, u.password, ";
			$sql .= "u.user_email, u.salt, u.api_key, u.domain_uuid, d.domain_name ";
			$sql .= "from v_users as u, v_domains as d ";
			$sql .= "where u.domain_uuid = d.domain_uuid ";
			if (strlen($this->key) > 30) {
				$sql .= "and u.api_key = :api_key ";
				$parameters['api_key'] = $this->key;
			}
			else {
				$sql .= "and lower(u.username) = lower(:username) ";
				$parameters['username'] = $this->username;
			}
			if ($_SESSION["users"]["unique"]["text"] === "global") {
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
			if (is_array($row)) {

				//set the domain details
					$this->domain_uuid = $_SESSION['domain_uuid'];
					$this->domain_name = $_SESSION['domain_name'];

				//get the domain uuid when users are unique globally
					if ($_SESSION["users"]["unique"]["text"] === "global" && $row["domain_uuid"] !== $this->domain_uuid) {
						//set the domain_uuid
							$this->domain_uuid = $row["domain_uuid"];
							$this->domain_name = $row["domain_name"];

						//set the domain session variables
							$_SESSION["domain_uuid"] = $this->domain_uuid;
							$_SESSION["domain_name"] = $this->domain_name;

						//set the setting arrays
							$domain = new domains();
							$domain->db = $db;
							$domain->set();
					}

				//set the variables
					$this->user_uuid = $row['user_uuid'];
					$this->username = $row['username'];
					$this->contact_uuid = $row['contact_uuid'];

				//debug info
					//echo "user_uuid ".$this->user_uuid."<br />\n";
					//echo "username ".$this->username."<br />\n";
					//echo "contact_uuid ".$this->contact_uuid."<br />\n";

				//set a few session variables
					$_SESSION["user_uuid"] = $row['user_uuid'];
					$_SESSION["contact_uuid"] = $row["contact_uuid"];
					$_SESSION["username"] = $row['username'];
					$_SESSION["user_email"] = $row['user_email'];

				//validate the password
					$valid_password = false;
					if (isset($this->key) && strlen($this->key) > 30 && $this->key === $row["api_key"]) {
						$valid_password = true;
					}
					else if (substr($row["password"], 0, 1) === '$') {
						if (isset($this->password) && strlen($this->password) > 0) {
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

			}

		//result array
			$result["plugin"] = "database";
			$result["domain_name"] = $this->domain_name;
			$result["username"] = $this->username;
			$result["user_uuid"] = $this->user_uuid;
			$result["domain_uuid"] = $_SESSION['domain_uuid'];
			$result["contact_uuid"] = $this->contact_uuid;
			$result["sql"] = $sql;
			$result["authorized"] = $valid_password;

		//return the results
			return $result;

	}
}

?>
