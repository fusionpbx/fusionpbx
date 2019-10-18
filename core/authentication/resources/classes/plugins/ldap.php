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
			if (strlen($_SESSION["ldap"]["user_dn"]["text"]) > 0) {
				$_SESSION["ldap"]["user_dn"][] = $_SESSION["ldap"]["user_dn"]["text"];
			}

		//check all user_dn in the array
			foreach ($_SESSION["ldap"]["user_dn"] as $user_dn) {
				$bind_dn = $_SESSION["ldap"]["user_attribute"]["text"]."=".$this->username.",".$user_dn;
				$bind_pw = $this->password;
				//Note: As of 4/16, the call below will fail randomly. PHP debug reports ldap_bind
				//called below with all arguments '*uninitialized*'. However, the debugger
				//single-stepping just before the failing call correctly displays all the values.
				if (strlen($bind_pw) > 0) {
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
				if ($_SESSION["users"]["unique"]["text"] != "global") {
					//unique username per domain (not globally unique across system - example: email address)
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $this->domain_uuid;
				}
				$parameters['username'] = $this->username;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					if ($_SESSION["users"]["unique"]["text"] == "global" && $row["domain_uuid"] != $this->domain_uuid) {
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
			$result["plugin"] = "ldap";
			$result["domain_name"] = $this->domain_name;
			$result["username"] = $this->username;
			if ($this->debug) {
				$result["password"] = $this->password;
			}
			$result["user_uuid"] = $this->user_uuid;
			$result["domain_uuid"] = $this->domain_uuid;
			$result["authorized"] = $user_authorized ? 'true' : 'false';
			return $result;
	}
}

?>