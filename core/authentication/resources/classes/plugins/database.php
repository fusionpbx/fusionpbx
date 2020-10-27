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
	public $debug;
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

		//set the default status
			$user_authorized = false;

		//check the username and password if they don't match then redirect to the login
			$sql = "select u.user_uuid, u.contact_uuid, u.username, u.password, u.salt, u.api_key, u.domain_uuid, d.domain_name ";
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
			if (is_array($row) && @sizeof($row) !== 0) {

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

				//set the user_uuid
					$this->user_uuid = $row['user_uuid'];
					$this->contact_uuid = $row['contact_uuid'];

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
			if ($this->debug) {
				$result["password"] = $this->password;
			}
			$result["user_uuid"] = $this->user_uuid;
			$result["domain_uuid"] = $this->domain_uuid;
			$result["contact_uuid"] = $this->contact_uuid;
			$result["sql"] = $sql;
			if ($valid_password) {
				$result["authorized"] = "true";
			}
			else {
				$result["authorized"] = "false";
			}
			return $result;
	}
}

?>
