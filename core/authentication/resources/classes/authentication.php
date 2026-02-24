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
 * authentication
 *
 */
class authentication {

	/**
	 * Declare Public variables
	 *
	 * @var mixed
	 */
	public $domain_uuid;
	public $user_uuid;
	public $domain_name;
	public $username;
	public $password;
	public $key;

	/**
	 * Declare Private variables
	 *
	 * @var mixed
	 */
	private $database;
	private $settings;

	/**
	 * Called when the object is created
	 */
	public function __construct(array $setting_array = []) {
		//set the config object
		$config = $setting_array['config'] ?? config::load();

		//set the database connection
		$this->database = $setting_array['database'] ?? database::new(['config' => $config]);

		//set the settings object
		$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database]);

		//intialize the object
		$this->user_uuid = null;
	}

	/**
	 * validate uses authentication plugins to check if a user is authorized to login
	 *
	 * @return array|false [plugin] => last plugin used to authenticate the user [authorized] => true or false
	 */
	public function validate() {

		//set default return array as null
		$result = null;

		//use a login message when a login attempt fails
		$failed_login_message = null;

		//get the domain_name and domain_uuid
		if (!isset($this->domain_name) || !isset($this->domain_uuid)) {
			$this->get_domain();
		}

		//create a settings object to pass to plugins
		$this->settings = new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid]);

		//set the default authentication method to the database
		if (empty($_SESSION['authentication']['methods']) || !is_array($_SESSION['authentication']['methods'])) {
			$_SESSION['authentication']['methods'][] = 'database';
		}

		//set the database as the default plugin
		if (!isset($_SESSION['authentication']['methods'])) {
			$_SESSION['authentication']['methods'][] = 'database';
		}

		//check if contacts app exists
		$contacts_exists = file_exists(dirname(__DIR__, 4) . '/core/contacts/');

		//check for remember me cookie
		if (isset($_COOKIE['remember'])) {
			//set variables
			$plugin_name = 'remember';
			$remote_address = $_SERVER['REMOTE_ADDR'] ?? '';
			$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
			list($cookie_selector, $cookie_validator) = explode(":", $_COOKIE['remember']);

			//get user logs
			$sql = "select user_uuid, remember_validator from v_user_logs ";
			$sql .= "where remember_selector = :remember_selector \n";
			$sql .= "and remote_address = :remote_address ";
			$sql .= "and user_agent = :user_agent ";
			$sql .= "and timestamp > NOW() - INTERVAL '7 days' ";
			$sql .= "and result = 'success' ";
			$sql .= "limit 1 ";
			$parameters['remember_selector'] = $cookie_selector;
			$parameters['remote_address'] = $remote_address;
			$parameters['user_agent'] = $user_agent;
			$user_logs = $this->database->select($sql, $parameters, 'row');
			unset($sql, $parameters);

			//validate the token
			if (!empty($user_logs) && password_verify($cookie_validator, $user_logs['remember_validator'])) {
				//get the user details
				$sql = "select \n";
				$sql .= "u.domain_uuid, \n";
				$sql .= "d.domain_name, \n";
				$sql .= "u.user_uuid, \n";
				$sql .= "u.username, \n";
				$sql .= "u.contact_uuid \n";
				$sql .= "from v_users as u, v_domains as d \n";
				$sql .= "where user_uuid = :user_uuid \n";
				$sql .= "and u.domain_uuid = d.domain_uuid \n";
				$sql .= "and u.user_enabled = 'true' \n";
				$parameters['user_uuid'] = $user_logs['user_uuid'];
				$row = $this->database->select($sql, $parameters, 'row');
				unset($sql, $parameters);

				//get the contact details
				if ($contacts_exists && !empty($row["contact_uuid"])) {
					$sql = "select * from v_contacts \n";
					$sql .= "where contact_uuid = :contact_uuid \n";
					$sql .= "and domain_uuid = :domain_uuid \n";
					$parameters['contact_uuid'] = $row["contact_uuid"];
					$parameters['domain_uuid'] = $row["domain_uuid"];
					$contact = $this->database->select($sql, $parameters, 'row');
					unset($sql, $parameters);
				}

				//build a result array
				$result['plugin']       = $plugin_name;
				$result['domain_name']  = $row["domain_name"];
				$result['username']     = $row['username'];
				$result['user_uuid']    = $row['user_uuid'];
				$result['contact_uuid'] = $row["contact_uuid"];
				if ($contacts_exists) {
					$result["contact_organization"] = $contact["contact_organization"] ?? '';
					$result["contact_name_given"]   = $contact["contact_name_given"] ?? '';
					$result["contact_name_family"]  = $contact["contact_name_family"] ?? '';
					$result["contact_image"]        = $contact["contact_image"] ?? '';
				}
				$result['domain_uuid'] = $row['domain_uuid'];
				$result['authorized']  = true;

				//set the domain_uuid
				$this->domain_uuid = $row["domain_uuid"];

				//set the user_uuid
				$this->user_uuid = $row["user_uuid"];

				//save the result to the authentication plugin
				$_SESSION['authentication']['methods'] = [];
				$_SESSION['authentication']['methods'][] = $plugin_name;
				$_SESSION['authentication']['plugin'] = [];
				$_SESSION['authentication']['plugin'][$plugin_name] = $result;

				//create the session
				self::create_user_session($result, $this->settings);

				//generate new token
				$selector = uuid();
				$validator = generate_password(32);
				$hashed_validator = password_hash($validator, PASSWORD_DEFAULT);
				$token = $selector.':'.$validator;

				//update the user logs
				$sql = "update v_user_logs ";
				$sql .= "set remember_selector = :remember_selector, ";
				$sql .= "remember_validator = :remember_validator ";
				$sql .= "where remember_selector = :cookie_selector ";
				$parameters['remember_selector'] = $selector;
				$parameters['remember_validator'] = $hashed_validator;
				$parameters['cookie_selector'] = $cookie_selector;
				$this->database->execute($sql, $parameters);
				unset($sql, $parameters);

				//set the cookie
				setcookie('remember', $token, [
					'expires' => strtotime('+7 days'),
					'path' => '/',
					'secure' => true,
					'httponly' => true,
					'samesite' => 'Strict'
				]);

			}
		}

		//use the authentication plugins
		foreach ($_SESSION['authentication']['methods'] as $name) {
			//skip the loop if already authorized
			if (isset($result['authorized']) && $result['authorized']) {
				break;
			}

			//already processed the plugin move to the next plugin
			if (!empty($_SESSION['authentication']['plugin'][$name]['authorized']) && $_SESSION['authentication']['plugin'][$name]['authorized']) {
				continue;
			}

			//prepare variables
			$class_name = "plugin_" . $name;
			$base       = __DIR__ . "/plugins";
			$plugin     = $base . "/" . $name . ".php";

			//process the plugin
			if (file_exists($plugin)) {
				//run the plugin
				$object = new $class_name();
				$object->domain_name = $this->domain_name;
				$object->domain_uuid = $this->domain_uuid;
				if ($name == 'database' && isset($this->key)) {
					$object->key = $this->key;
				}
				if ($name == 'database' && isset($this->username)) {
					$object->username = $this->username;
					$object->password = $this->password;
				}
				//initialize the plugin send the authentication object and settings
				$array = $object->$name($this, $this->settings);

				//build a result array
				if (!empty($array) && is_array($array)) {
					$result['plugin']       = $array["plugin"];
					$result['domain_name']  = $array["domain_name"];
					$result['username']     = $array["username"];
					$result['user_uuid']    = $array["user_uuid"];
					$result['contact_uuid'] = $array["contact_uuid"];
					if ($contacts_exists) {
						$result["contact_organization"] = $array["contact_organization"] ?? '';
						$result["contact_name_given"]   = $array["contact_name_given"] ?? '';
						$result["contact_name_family"]  = $array["contact_name_family"] ?? '';
						$result["contact_image"]        = $array["contact_image"] ?? '';
					}
					$result['domain_uuid'] = $array["domain_uuid"];
					$result['authorized']  = $array["authorized"];

					//set the domain_uuid
					$this->domain_uuid = $array["domain_uuid"];

					//set the user_uuid
					$this->user_uuid = $array["user_uuid"];

					//save the result to the authentication plugin
					$_SESSION['authentication']['plugin'][$name] = $result;
				}

				//plugin authorized false
				if (!$result['authorized']) {
					break;
				}
			}
		}

		//make sure all plugins are in the array
		if (!empty($_SESSION['authentication']['methods'])) {
			foreach ($_SESSION['authentication']['methods'] as $name) {
				if (!isset($_SESSION['authentication']['plugin'][$name]['authorized'])) {
					$_SESSION['authentication']['plugin'][$name]['plugin']      = $name;
					$_SESSION['authentication']['plugin'][$name]['domain_name'] = $_SESSION['domain_name'];
					$_SESSION['authentication']['plugin'][$name]['domain_uuid'] = $_SESSION['domain_uuid'];
					$_SESSION['authentication']['plugin'][$name]['username']    = $_SESSION['username'];
					$_SESSION['authentication']['plugin'][$name]['user_uuid']   = $_SESSION['user_uuid'];
					$_SESSION['authentication']['plugin'][$name]['user_email']  = $_SESSION['user_email'];
					$_SESSION['authentication']['plugin'][$name]['authorized']  = false;
				}
			}
		}

		//debug information
		// view_array($_SESSION['authentication'], false);

		//set authorized to false if any authentication method failed
		$authorized  = false;
		$plugin_name = '';
		if (is_array($_SESSION['authentication']['plugin'])) {
			foreach ($_SESSION['authentication']['plugin'] as $row) {
				$plugin_name = $row['plugin'];
				if ($row["authorized"]) {
					$authorized = true;
				} else {
					$authorized           = false;
					$failed_login_message = "Authentication plugin '$plugin_name' blocked login attempt";
					break;
				}
			}
		}

		//user is authorized - get user settings, check user cidr
		if ($authorized) {
			//get the cidr restrictions from global, domain, and user default settings
			$this->settings = new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);
			$cidr_list      = $this->settings->get('domain', 'cidr', []);
			if (check_cidr($cidr_list, $_SERVER['REMOTE_ADDR'])) {
				//user passed the cidr check
				self::create_user_session($result, $this->settings);
			} else {
				//user failed the cidr check - no longer authorized
				$authorized                                                = false;
				$failed_login_message                                      = "CIDR blocked login attempt";
				$_SESSION['authentication']['plugin'][$name]['authorized'] = false;
			}
		}

		//create remember me token
		if ($authorized && isset($_SESSION['username']) && isset($_SESSION['remember'])) {
			//set session variables
			$input_username = $_SESSION['username'];
			$remember = $_SESSION['remember'];

			//match the username
			$sql = "select user_uuid from v_users ";
			$sql .= "where username = :username";
			$parameters['username'] = $input_username;
			$user = $this->database->select($sql, $parameters, 'row');
			unset($sql, $parameters);

			if ($remember && $user) {
				//generate the token
				$selector = uuid();
				$validator = generate_password(32);
				$hashed_validator = password_hash($validator, PASSWORD_DEFAULT);
				$token = $selector.':'.$validator;
				$remote_address = $_SERVER['REMOTE_ADDR'] ?? '';
				$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

				//save token to the user logs
				$sql = "update v_user_logs ";
				$sql .= "set remember_selector = :remember_selector, ";
				$sql .= "remember_validator = :remember_validator ";
				$sql .= "where user_log_uuid = ( ";
				$sql .= "	select user_log_uuid FROM v_user_logs ";
				$sql .= "	where result = 'success' ";
				$sql .= "	and remote_address = :remote_address ";
				$sql .= "	and user_agent = :user_agent ";
				$sql .= "	and user_uuid = :user_uuid ";
				$sql .= "	and timestamp > NOW() - INTERVAL '7 days' ";
				$sql .= "	order by timestamp desc limit 1 ";
				$sql .= ") ";
				$parameters['remember_selector'] = $selector;
				$parameters['remember_validator'] = $hashed_validator;
				$parameters['remote_address'] = $remote_address;
				$parameters['user_agent'] = $user_agent;
				$parameters['user_uuid'] = $user['user_uuid'];
				$this->database->execute($sql, $parameters);
				unset($sql, $parameters);

				//set the cookie
				setcookie('remember', $token, [
					'expires' => strtotime('+7 days'),
					'path' => '/',
					'secure' => true,
					'httponly' => true,
					'samesite' => 'Strict'
				]);
			}
		}

		//set a session variable to indicate whether or not we are authorized
		$_SESSION['authorized'] = $authorized;

		//log the attempt
		user_logs::add($_SESSION['authentication']['plugin'][$name], $failed_login_message);

		//return the result
		return $result ?? false;
	}

	/**
	 * Creates a valid user session in the superglobal $_SESSION.
	 * <p>The $result must be a validated user with the appropriate variables set.<br>
	 * The associative array
	 *
	 * @param array|bool $result   Associative array containing: domain_uuid, domain_name, user_uuid, username. Contact
	 *                             keys can be empty, but should still be present. They include: contact_uuid,
	 *                             contact_name_given, contact_name_family, contact_image.
	 * @param settings   $settings From the settings object
	 *
	 * @return void
	 * @global string    $conf
	 * @global database  $database
	 */
	public static function create_user_session($result = [], $settings = null): void {

		//use the database global
		global $database;

		// validate data
		if (empty($result)) {
			return;
		}

		// Required keys
		$required_keys = [
			'domain_uuid' => true,
			'domain_name' => true,
			'user_uuid' => true,
			'username' => true,
		];

		// Any missing required_fields are left in the $diff array.
		// When all keys are present the $diff array will be empty.
		$diff = array_diff_key($required_keys, $result);

		// All required keys must be present in the $result associative array
		if (!empty($diff)) {
			return;
		}

		// Domain and User UUIDs must be valid UUIDs
		if (!is_uuid($result['domain_uuid']) || !is_uuid($result['user_uuid'])) {
			return;
		}

		// If Contact UUID has a value it must be a valid UUID
		if (!empty($result['contact_uuid']) && !is_uuid($result['contact_uuid'])) {
			return;
		}

		//
		// All data validated continue to create session
		//

		// Set project root directory
		$project_root = dirname(__DIR__, 4);

		// Set the session variables
		$_SESSION["domain_uuid"] = $result["domain_uuid"];
		$_SESSION["domain_name"] = $result["domain_name"];
		$_SESSION["user_uuid"]   = $result["user_uuid"];
		$_SESSION["context"]     = $result['domain_name'];

		// Build the session server array to validate the session
		global $conf;
		if (!isset($conf['session.validate'])) {
			$conf['session.validate'][] = 'HTTP_USER_AGENT';
		}
		foreach ($conf['session.validate'] as $name) {
			$server_array[$name] = $_SERVER[$name];
		}

		// Save the user hash to be used in check_auth
		$_SESSION["user_hash"] = hash('sha256', implode($server_array));

		// User session array
		$_SESSION["user"]["domain_uuid"]  = $result["domain_uuid"];
		$_SESSION["user"]["domain_name"]  = $result["domain_name"];
		$_SESSION["user"]["user_uuid"]    = $result["user_uuid"];
		$_SESSION["user"]["username"]     = $result["username"];
		$_SESSION["user"]["contact_uuid"] = $result["contact_uuid"] ?? null; //contact_uuid is optional

		// Check for contacts
		if (file_exists($project_root . '/core/contacts/')) {
			$_SESSION["user"]["contact_organization"] = $result["contact_organization"] ?? null;
			$_SESSION["user"]["contact_name"]         = trim(($result["contact_name_given"] ?? '') . ' ' . ($result["contact_name_family"] ?? ''));
			$_SESSION["user"]["contact_name_given"]   = $result["contact_name_given"] ?? null;
			$_SESSION["user"]["contact_name_family"]  = $result["contact_name_family"] ?? null;
			$_SESSION["user"]["contact_image"]        = !empty($result["contact_image"]) && is_uuid($result["contact_image"]) ? $result["contact_image"] : null;
		}

		//empty the permissions
		if (isset($_SESSION['permissions'])) {
			unset($_SESSION['permissions']);
		}

		//get the groups assigned to the user
		$group = new groups($database, $result["domain_uuid"], $result["user_uuid"]);
		$group->session();

		//get the permissions assigned to the user through the assigned groups
		$permission = new permissions($database, $result["domain_uuid"], $result["user_uuid"]);
		$permission->session();

		//get the domains
		if (file_exists($project_root . '/app/domains/resources/domains.php') && !is_cli()) {
			require_once $project_root . '/app/domains/resources/domains.php';
		}

		//initialize the parameters array
		$parameters = [];

		//get the user settings
		$sql = "select * from v_user_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and user_uuid = :user_uuid ";
		$sql .= "and user_setting_enabled = true ";
		$parameters['domain_uuid'] = $result["domain_uuid"];
		$parameters['user_uuid'] = $result["user_uuid"];
		$user_settings = $database->select($sql, $parameters, 'all');

		//store user settings in the session when available
		if (is_array($user_settings)) {
			foreach ($user_settings as $row) {
				$name = $row['user_setting_name'];
				$category = $row['user_setting_category'];
				$subcategory = $row['user_setting_subcategory'];
				if (isset($row['user_setting_value'])) {
					if (empty($subcategory)) {
						//$$category[$name] = $row['domain_setting_value'];
						if ($name == "array") {
							$_SESSION[$category][] = $row['user_setting_value'];
						} else {
							$_SESSION[$category][$name] = $row['user_setting_value'];
						}
					} else {
						//$$category[$subcategory][$name] = $row['domain_setting_value'];
						if ($name == "array") {
							$_SESSION[$category][$subcategory][] = $row['user_setting_value'];
						} else {
							$_SESSION[$category][$subcategory][$name] = $row['user_setting_value'];
						}
					}
				}
			}
		}

		//get the extensions that are assigned to this user
		if (file_exists($project_root . '/app/extensions/app_config.php')) {
			if (isset($_SESSION["user"]) && is_uuid($_SESSION["user_uuid"]) && is_uuid($_SESSION["domain_uuid"]) && !isset($_SESSION['user']['extension'])) {
				//define the array
				$parameters = [];

				//initialize the array
				$_SESSION['user']['extension'] = [];

				//get the user extension list
				$sql = "select ";
				$sql .= "e.extension_uuid, ";
				$sql .= "e.extension, ";
				$sql .= "e.number_alias, ";
				$sql .= "e.user_context, ";
				$sql .= "e.outbound_caller_id_name, ";
				$sql .= "e.outbound_caller_id_number, ";
				$sql .= "e.description ";
				$sql .= "from ";
				$sql .= "v_extension_users as u, ";
				$sql .= "v_extensions as e ";
				$sql .= "where ";
				$sql .= "e.domain_uuid = :domain_uuid ";
				$sql .= "and e.extension_uuid = u.extension_uuid ";
				$sql .= "and u.user_uuid = :user_uuid ";
				$sql .= "and e.enabled = 'true' ";
				$sql .= "order by ";
				$sql .= "e.extension asc ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$parameters['user_uuid']   = $_SESSION['user_uuid'];
				$extensions                = $database->select($sql, $parameters, 'all');
				if (!empty($extensions)) {
					foreach ($extensions as $x => $row) {
						//set the destination
						$destination = $row['extension'];
						if (!empty($row['number_alias'])) {
							$destination = $row['number_alias'];
						}

						//build the user array
						$_SESSION['user']['extension'][$x]['user']                      = $row['extension'];
						$_SESSION['user']['extension'][$x]['number_alias']              = $row['number_alias'];
						$_SESSION['user']['extension'][$x]['destination']               = $destination;
						$_SESSION['user']['extension'][$x]['extension_uuid']            = $row['extension_uuid'];
						$_SESSION['user']['extension'][$x]['outbound_caller_id_name']   = $row['outbound_caller_id_name'];
						$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
						$_SESSION['user']['extension'][$x]['user_context']              = $row['user_context'];
						$_SESSION['user']['extension'][$x]['description']               = $row['description'];

						//set the context
						$_SESSION['user']['user_context'] = $row["user_context"];
						$_SESSION['user_context']         = $row["user_context"];
					}
				}
			}
		}

		//set the time zone
		if (!isset($_SESSION["time_zone"]["user"])) {
			$_SESSION["time_zone"]["user"] = null;
		}
		if (strlen($_SESSION["time_zone"]["user"] ?? '') === 0) {
			//set the domain time zone as the default time zone
			date_default_timezone_set($settings->get('domain', 'time_zone', 'UTC'));
		} else {
			//set the user defined time zone
			date_default_timezone_set($_SESSION["time_zone"]["user"]);
		}

		//regenerate the session on login
		//session_regenerate_id(true);

		//add the username to the session - username session could be set so check_auth uses an authorized session variable instead
		$_SESSION['username'] = $_SESSION['user']["username"];
		return;
	}

	/**
	 *  get_domain used to get the domain name from the URL or username and then sets both domain_name and domain_uuid
	 */
	public function get_domain() {

		//get the domain from the url
		$this->domain_name = $_SERVER["HTTP_HOST"];

		//get the domain name from the http value
		if (!empty($_REQUEST["domain_name"])) {
			$this->domain_name = $_REQUEST["domain_name"];
		}

		//remote port number from the domain name
		$domain_array = explode(":", $this->domain_name);
		if (count($domain_array) > 1) {
			$this->domain_name = $domain_array[0];
		}

		//if the username
		if (!empty($_REQUEST["username"])) {
			$_SESSION['username'] = $_REQUEST["username"];
		}

		//set a default value for unqiue
		$_SESSION["users"]["unique"]["text"] = $this->settings->get('users', 'unique', '');

		//get the domain name from the username
		if (!empty($_SESSION['username']) && $this->settings->get('users', 'unique', '') != "global") {
			$username_array = explode("@", $_SESSION['username']);
			if (count($username_array) > 1) {
				//get the domain name
				$domain_name = $username_array[count($username_array) - 1];

				//check if the domain from the username exists
				$domain_exists = false;
				foreach ($_SESSION['domains'] as $row) {
					if (lower_case($row['domain_name']) == lower_case($domain_name)) {
						$this->domain_uuid = $row['domain_uuid'];
						$domain_exists     = true;
						break;
					}
				}

				//if the domain exists then set domain_name and update the username
				if ($domain_exists) {
					$this->domain_name = $domain_name;
					$this->username    = substr($_SESSION['username'], 0, -(strlen($domain_name) + 1));
					//$_SESSION['domain_name'] = $domain_name;
					$_SESSION['username']    = $this->username;
					$_SESSION['domain_uuid'] = $this->domain_uuid;
				}

				//unset the domain name variable
				unset($domain_name);
			}
		}

		//get the domain uuid and domain settings
		if (isset($this->domain_name) && !isset($this->domain_uuid)) {
			foreach ($_SESSION['domains'] as $row) {
				if (lower_case($row['domain_name']) == lower_case($this->domain_name)) {
					$this->domain_uuid       = $row['domain_uuid'];
					$_SESSION['domain_uuid'] = $row['domain_uuid'];
					break;
				}
			}
		}

		//set the setting arrays
		$obj = new domains(['database' => $this->database]);
		$obj->set();

		//set the domain settings
		if (!empty($this->domain_name) && !empty($_SESSION["domain_uuid"])) {
			$_SESSION['domain_name']        = $this->domain_name;
			$_SESSION['domain_parent_uuid'] = $_SESSION["domain_uuid"];
		}

		//set the domain name
		return $this->domain_name;
	}
}

/*
$auth = new authentication;
$auth->username = "user";
$auth->password = "password";
$auth->domain_name = "sip.fusionpbx.com";
$response = $auth->validate();
print_r($response);
*/
