<?php

class remember_me {

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Internal array structure that is populated from the database
	 *
	 * @var settings A settings object loaded from Default Settings
	 */
	private $settings;

	/**
	 * Additional private variables
	 */
	private static $cookie_name = 'remember_me';
	private $expiry_days;
	private $ip_check;

	/**
	 * Initializes the object with the setting array.
	 *
	 * @param array $setting_array An array containing settings for domain, user, and database connections. Defaults to
	 *                             an empty array.
	 *
	 * @return void
	 */
	public function __construct(array $setting_array = []) {
		// Set objects
		$this->database = $setting_array['database'] ?? database::new();
		$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database]);

		// Assign private variables
		$expiry_days = (int) $this->settings->get('login', 'remember_me_days', 7);
		$this->expiry_days = ($expiry_days >= 1 && $expiry_days <= 365) ? $expiry_days : 7;
		$this->ip_check = $this->settings->get('login', 'remember_me_ip_check', false);
	}

	/**
	 * Checks if a valid remember me cookie exists and returns user data.
	 *
	 * The token is not rotated here. Call rotate_token() with the returned 'remember_old_selector' once the user has
	 * passed all remaining checks (e.g. CIDR) so a rejected login does not consume the token.
	 *
	 * @param bool $contacts_exists Whether the contacts app exists
	 *
	 * @return array|null
	 */
	public function authenticate($contacts_exists = false) {
		if (!$this->settings->get('login', 'remember_me', false) || !isset($_COOKIE[self::$cookie_name])) {
			return null;
		}

		$cookie_data = $this->parse_cookie($_COOKIE[self::$cookie_name]);
		if (!$cookie_data) {
			return null;
		}

		[$selector, $validator] = $cookie_data;

		// Lookup token in database
		$user_log = $this->find_user_token($selector);
		if (!$user_log) {
			return null;
		}

		// Verify validator hash
		if (!password_verify($validator, $user_log['remember_validator'])) {
			// The selector matched but the validator did not, the token may have been stolen so revoke it
			$this->revoke_selector($selector);
			self::clear_cookie();
			user_logs::add(['authorized' => false], "Invalid remember me token");
			return null;
		}

		// Fetch user details, the user must still exist and be enabled
		$result = $this->get_user_details($user_log['user_uuid'], $contacts_exists);
		if ($result === null) {
			$this->revoke_selector($selector);
			self::clear_cookie();
			return null;
		}

		// Pass the selector back so the caller can rotate the token once the login is fully authorized
		$result['remember_old_selector'] = $selector;
		return $result;
	}

	/**
	 * Creates a new token, saves it to DB, and sets the browser cookie.
	 *
	 * @return array
	 */
	public function issue_token() {
		$selector = uuid();
		$validator = generate_password(32);
		$hashed_validator = password_hash($validator, PASSWORD_DEFAULT);

		// Set Cookie
		setcookie(self::$cookie_name, $selector . ':' . $validator, [
			'expires' => strtotime("+".$this->expiry_days." days"),
			'path' => '/',
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax'
		]);

		return ['selector' => $selector, 'hashed_validator' => $hashed_validator];
	}

	/**
	 * Deletes the cookie from the browser.
	 */
	public static function clear_cookie(): void {
		unset($_COOKIE[self::$cookie_name]);
		setcookie(self::$cookie_name, '', [
			'expires' => time() - 3600,
			'path' => '/',
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax'
		]);
	}

	private function parse_cookie(string $cookie_value) {
		$parts = explode(':', $cookie_value, 2);
		if (count($parts) !== 2 || !is_uuid($parts[0])) {
			return null;
		}
		return $parts;
	}

	private function find_user_token(string $remember_selector) {
		// Set variables
		$remote_address = $_SERVER['REMOTE_ADDR'] ?? '';
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		// Get the user log
		$sql = "select user_uuid, remember_validator \n";
		$sql .= "from v_user_logs \n";
		$sql .= "where remember_selector = :remember_selector \n";
		$sql .= " and user_agent = :user_agent \n";
		if ($this->ip_check) {
			$sql .= " and remote_address = :remote_address \n";
			$parameters['remote_address'] = $remote_address;
		}
		$sql .= " and timestamp > now() - interval '".(int) $this->expiry_days." days' \n";
		$parameters['remember_selector'] = $remember_selector;
		$parameters['user_agent'] = $user_agent;
		$user_log = $this->database->select($sql, $parameters, 'row');
		unset($sql, $parameters);

		if (is_array($user_log)) {
			return $user_log;
		}
		return null;
	}

	/**
	 * Replaces a used token with a new one.
	 *
	 * The old token is revoked and a new cookie is sent. The new selector and hashed validator are returned so the
	 * caller can store them on a new user log entry. Because the token lifetime is measured from the timestamp of the
	 * user log entry that holds it, this gives the token a sliding expiry: it stays valid as long as the user returns
	 * at least once every remember_me_days days.
	 *
	 * @param string $old_selector The selector of the token that was used to log in
	 *
	 * @return array ['selector' => string, 'hashed_validator' => string]
	 */
	public function rotate_token(string $old_selector): array {
		$this->revoke_selector($old_selector);
		return $this->issue_token();
	}

	/**
	 * Removes a single token from the user logs so it can no longer be used.
	 *
	 * @param string $selector The token selector
	 */
	private function revoke_selector(string $selector): void {
		$sql = "update v_user_logs \n";
		$sql .= " set remember_selector = null, \n";
		$sql .= " remember_validator = null \n";
		$sql .= "where remember_selector = :remember_selector \n";
		$parameters['remember_selector'] = $selector;
		$this->database->execute($sql, $parameters);
		unset($sql, $parameters);
	}

	private function get_user_details(string $user_uuid, bool $contacts_exists): ?array {
		// Get the user details
		$sql = "select \n";
		$sql .= " u.domain_uuid, \n";
		$sql .= " d.domain_name, \n";
		$sql .= " u.user_uuid, \n";
		$sql .= " u.username, \n";
		$sql .= " u.contact_uuid \n";
		$sql .= "from v_users as u \n";
		$sql .= "inner join v_domains as d on u.domain_uuid = d.domain_uuid \n";
		$sql .= "where u.user_uuid = :user_uuid \n";
		$sql .= "and u.user_enabled = 'true' \n";
		$parameters['user_uuid'] = $user_uuid;
		$row = $this->database->select($sql, $parameters, 'row');
		unset($sql, $parameters);

		// The user no longer exists or has been disabled
		if (!is_array($row) || empty($row['user_uuid'])) {
			return null;
		}

		// Get the contact details
		if ($contacts_exists && !empty($row["contact_uuid"])) {
			$sql = "select ";
			$sql .= " c.contact_organization, ";
			$sql .= " c.contact_name_given, ";
			$sql .= " c.contact_name_family, ";
			$sql .= " a.contact_attachment_uuid ";
			$sql .= "from v_contacts as c ";
			$sql .= "left join v_contact_attachments as a on ( \n";
			$sql .= "	c.contact_uuid = a.contact_uuid  \n";
			$sql .= "	and a.attachment_primary = true  \n";
			$sql .= "	and a.attachment_filename is not null  \n";
			$sql .= "	and a.attachment_content is not null \n";
			$sql .= ") \n";
			$sql .= "where c.contact_uuid = :contact_uuid ";
			$sql .= "and c.domain_uuid = :domain_uuid ";
			$parameters['contact_uuid'] = $row["contact_uuid"];
			$parameters['domain_uuid'] = $row["domain_uuid"];
			$contact = $this->database->select($sql, $parameters, 'row');
			unset($sql, $parameters);
		}

		// Build the result array
		$result['plugin'] = 'remember_me';
		$result['domain_name'] = $row["domain_name"];
		$result['username'] = $row['username'];
		$result['user_uuid'] = $row['user_uuid'];
		$result['contact_uuid'] = $row["contact_uuid"];
		if ($contacts_exists) {
			$result["contact_organization"] = $contact["contact_organization"] ?? '';
			$result["contact_name_given"] = $contact["contact_name_given"] ?? '';
			$result["contact_name_family"] = $contact["contact_name_family"] ?? '';
			$result["contact_image"] = $contact["contact_attachment_uuid"] ?? '';
		}
		$result['domain_uuid'] = $row['domain_uuid'];
		$result['authorized'] = true;

		return $result;
	}

	/**
	 * Deletes the current token in use
	 */
	public static function logout_action() {

		// Use the database global
		global $database;

		if (!empty($_COOKIE[self::$cookie_name])) {
			$cookie_selector = explode(":", $_COOKIE[self::$cookie_name])[0];

			$sql = "update v_user_logs ";
			$sql .= "set remember_selector = null, ";
			$sql .= "remember_validator = null ";
			$sql .= "where remember_selector = :remember_selector ";
			$parameters['remember_selector'] = $cookie_selector;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);

			//clear cookie
			self::clear_cookie();
		}
	}

	/**
	 * Deletes all tokens associated with a user.
	 *
	 * @param string $user User UUID or username
	 *
	 */
	public static function delete_user_tokens(string $user) {

		// Use the database global
		global $database;

		$sql = "update v_user_logs ";
		$sql .= "set remember_selector = null, ";
		$sql .= "remember_validator = null ";
		if (is_uuid($user)) {
			$sql .= "where user_uuid = :user_uuid ";
			$parameters['user_uuid'] = $user;
		} else {
			$sql .= "where username = :username ";
			$parameters['username'] = $user;
		}
		$database->execute($sql, $parameters ?? null);
		unset($sql, $parameters);
	}
}
