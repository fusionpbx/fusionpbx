<?php

class remember_me {

	/**
	 * Declare Private variables
	 *
	 * @var mixed
	 */
	private $database;
	private $settings;
	private static $cookie_name = 'remember';
	private static $expiry_days = 7;

	/**
	 * Called when the object is created
	 */
	public function __construct($database, $settings) {
		$this->database = $database;
		$this->settings = $settings;
	}

	/**
	 * Main entry point: Checks if a valid remember me cookie exists and returns user data.
	 *
	 * @return array|null
	 */
	public function authenticate($contacts_exists = false): array|null {
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
			$this->clear_cookie();
			user_logs::add(['authorized' => false], "Invalid remember me token");
			return null;
		}

		// Rotate Token
		$this->rotate_token($selector);

		// Fetch user details
		return $this->get_user_details($user_log['user_uuid'], $contacts_exists);
	}

	/**
	 * Creates a new token, saves it to DB, and sets the browser cookie.
	 *
	 * @return array
	 */
	public function issue_token(): array {
		$selector = uuid();
		$validator = generate_password(32);
		$hashed_validator = password_hash($validator, PASSWORD_DEFAULT);

		// Set Cookie
		setcookie(self::$cookie_name, $selector . ':' . $validator, [
			'expires' => strtotime("+".self::$expiry_days." days"),
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
		setcookie(self::$cookie_name, '', time() - 3600, '/');
	}

	private function parse_cookie(string $cookie_value): array|null {
		$parts = explode(':', $cookie_value, 2);
		if (count($parts) !== 2 || !is_uuid($parts[0])) {
			return null;
		}
		return $parts;
	}

	private function find_user_token(string $remember_selector): array|null {
		// Set variables
		$remote_address = $_SERVER['REMOTE_ADDR'] ?? '';
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		// Get the user log
		$sql = "select user_uuid, remember_validator \n";
		$sql .= "from v_user_logs \n";
		$sql .= "where remember_selector = :remember_selector \n";
		$sql .= " and remote_address = :remote_address \n";
		$sql .= " and user_agent = :user_agent \n";
		$sql .= " and timestamp > now() - interval '".self::$expiry_days." days' \n";
		$parameters['remember_selector'] = $remember_selector;
		$parameters['remote_address'] = $remote_address;
		$parameters['user_agent'] = $user_agent;
		$user_log = $this->database->select($sql, $parameters, 'row');
		unset($sql, $parameters);

		if (is_array($user_log)) {
			return $user_log;
		}
		return null;
	}

	private function rotate_token(string $old_selector): void {
		$selector = uuid();
		$validator = generate_password(32);
		$hashed_validator = password_hash($validator, PASSWORD_DEFAULT);

		// Update Cookie
		setcookie(self::$cookie_name, $selector . ':' . $validator, [
			'expires' => strtotime("+".self::$expiry_days." days"),
			'path' => '/',
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax'
		]);

		// Update the tokens in the database
		$sql = "update v_user_logs \n";
		$sql .= " set remember_selector = :remember_selector, \n";
		$sql .= " remember_validator = :remember_validator \n";
		$sql .= "where remember_selector = :old_selector \n";
		$parameters['remember_selector'] = $selector;
		$parameters['remember_validator'] = $hashed_validator;
		$parameters['old_selector'] = $old_selector;
		$this->database->execute($sql, $parameters);
		unset($sql, $parameters);
	}

	private function get_user_details(string $user_uuid, bool $contacts_exists): array|null {
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

		if ($_COOKIE[self::$cookie_name]) {
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
	 */
	public static function delete_user_tokens(mixed $user) {

		// Use the database global
		global $database;

		$sql = "update v_user_logs ";
		$sql .= "set remember_selector = null, ";
		$sql .= "remember_validator = null ";
		if (is_uuid($user)) {
			$sql .= "where user_uuid = :user_uuid ";
			$parameters['user_uuid'] = $user;
		} else if ($_COOKIE[self::$cookie_name]) {
			$sql .= "where username = :username ";
			$parameters['username'] = $user;
		}
		$database->execute($sql, $parameters ?? null);
		unset($sql, $parameters);
	}
}
