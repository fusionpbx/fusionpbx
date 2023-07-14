<?php

/**
 * plugin_ldap 
 *
 * @method ldap checks a local or remote ldap database to authenticate the user
 */
class plugin_ldap {

	/**
	 * Define variables and their scope
	 */
	public $debug;
	public $domain_name;
	public $username;
	public $password;
	public $user_uuid;
	public $contact_uuid;

	/**
	 * ldap checks a local or remote ldap database to authenticate the user
	 * @return array [authorized] => true or false
	 */
	function ldap() {

		//show the authentication code view
			if ($_REQUEST["username"]) {

				//pre-process some settings
					$settings['theme']['favicon'] = !empty($_SESSION['theme']['favicon']['text']) ? $_SESSION['theme']['favicon']['text'] : PROJECT_PATH.'/themes/default/favicon.ico';
					$settings['login']['destination'] = !empty($_SESSION['login']['destination']['text']) ? $_SESSION['login']['destination']['text'] : '';
					$settings['users']['unique'] = !empty($_SESSION['users']['unique']['text']) ? $_SESSION['users']['unique']['text'] : '';
					$settings['theme']['logo'] = !empty($_SESSION['theme']['logo']['text']) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH.'/themes/default/images/logo_login.png';
					$settings['theme']['login_logo_width'] = !empty($_SESSION['theme']['login_logo_width']['text']) ? $_SESSION['theme']['login_logo_width']['text'] : 'auto; max-width: 300px';
					$settings['theme']['login_logo_height'] = !empty($_SESSION['theme']['login_logo_height']['text']) ? $_SESSION['theme']['login_logo_height']['text'] : 'auto; max-height: 300px';

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

				//add the token name and hash to the view
					//$view->assign("token_name", $token['name']);
					//$view->assign("token_hash", $token['hash']);

				//show the views
					$content = $view->render('login.htm');
					echo $content;
					exit;
			}

		//use ldap to validate the user credentials
			if (isset($_SESSION["ldap"]["certpath"])) {
				$s = "LDAPTLS_CERT=" . $_SESSION["ldap"]["certpath"]["text"];
				putenv($s);
			}
			if (isset($_SESSION["ldap"]["certkey"])) {
				$s = "LDAPTLS_KEY=" . $_SESSION["ldap"]["certkey"]["text"];
				 putenv($s);
			}
			$host = $_SESSION["ldap"]["server_host"]["text"];
			$port = $_SESSION["ldap"]["server_port"]["numeric"];
			$connect = ldap_connect($host, $port)
				or die("Could not connect to the LDAP server.");
			//ldap_set_option($connect, LDAP_OPT_NETWORK_TIMEOUT, 10);
			ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
			//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);

		//set the default status
			$user_authorized = false;

		//provide backwards compatability
			if (!empty($_SESSION["ldap"]["user_dn"]["text"])) {
				$_SESSION["ldap"]["user_dn"][] = $_SESSION["ldap"]["user_dn"]["text"];
			}

		//check all user_dn in the array
			foreach ($_SESSION["ldap"]["user_dn"] as $user_dn) {
				$bind_dn = $_SESSION["ldap"]["user_attribute"]["text"]."=".$this->username.",".$user_dn;
				$bind_pw = $this->password;
				//Note: As of 4/16, the call below will fail randomly. PHP debug reports ldap_bind
				//called below with all arguments '*uninitialized*'. However, the debugger
				//single-stepping just before the failing call correctly displays all the values.
				if (!empty($bind_pw)) {
					$bind = ldap_bind($connect, $bind_dn, $bind_pw);
					if ($bind) {
						//connected and authorized
						$user_authorized = true;
						break;
					}
				}
			}

		//check to see if the user exists
			 if ($user_authorized) {
				$sql = "select * from v_users ";
				$sql .= "where username = :username ";
				if ($settings['users']['unique'] != "global") {
					//unique username per domain (not globally unique across system - example: email address)
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $this->domain_uuid;
				}
				$sql .= "and (user_type = 'default' or user_type is null) ";
				$parameters['username'] = $this->username;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					if ($settings['users']['unique'] == "global" && $row["domain_uuid"] != $this->domain_uuid) {
						//get the domain uuid
							$this->domain_uuid = $row["domain_uuid"];
							$this->domain_name = $_SESSION['domains'][$this->domain_uuid]['domain_name'];

						//set the domain session variables
							$_SESSION["domain_uuid"] = $this->domain_uuid;
							$_SESSION["domain_name"] = $this->domain_name;

						//set the setting arrays
							$domain = new domains();
							$domain->set();
					}
					$this->user_uuid = $row["user_uuid"];
					$this->contact_uuid = $row["contact_uuid"];
				}
				else {
					//salt used with the password to create a one way hash
						$salt = generate_password('32', '4');
						$password = generate_password('32', '4');

					//prepare the uuids
						$this->user_uuid = uuid();
						$this->contact_uuid = uuid();

					//build user insert array
						$array['users'][0]['user_uuid'] = $this->user_uuid;
						$array['users'][0]['domain_uuid'] = $this->domain_uuid;
						$array['users'][0]['contact_uuid'] = $this->contact_uuid;
						$array['users'][0]['username'] = strtolower($this->username);
						$array['users'][0]['password'] = md5($salt.$password);
						$array['users'][0]['salt'] = $salt;
						$array['users'][0]['add_date'] = now();
						$array['users'][0]['add_user'] = strtolower($this->username);
						$array['users'][0]['user_enabled'] = 'true';

					//build user group insert array
						$array['user_groups'][0]['user_group_uuid'] = uuid();
						$array['user_groups'][0]['domain_uuid'] = $this->domain_uuid;
						$array['user_groups'][0]['group_name'] = 'user';
						$array['user_groups'][0]['user_uuid'] = $this->user_uuid;

					//grant temporary permissions
						$p = new permissions;
						$p->add('user_add', 'temp');
						$p->add('user_group_add', 'temp');

					//execute insert
						$database = new database;
						$database->app_name = 'authentication';
						$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932be0e41f1';
						$database->save($array);
						unset($array);

					//revoke temporary permissions
						$p->delete('user_add', 'temp');
						$p->delete('user_group_add', 'temp');
				}
				unset($sql, $parameters, $row);
			}

		//result array
			$result["ldap"]["plugin"] = "ldap";
			$result["ldap"]["domain_name"] = $this->domain_name;
			$result["ldap"]["username"] = $this->username;
			if ($this->debug) {
				$result["ldap"]["password"] = $this->password;
			}
			$result["ldap"]["user_uuid"] = $this->user_uuid;
			$result["ldap"]["domain_uuid"] = $this->domain_uuid;
			$result["ldap"]["authorized"] = $user_authorized ? true : false;
			return $result;
	}
}

?>
