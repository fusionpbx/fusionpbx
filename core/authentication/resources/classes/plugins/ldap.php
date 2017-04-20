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
	public $connection;

    /**
     * Authenticate a username and password with a specific ldap resource
     * @param $connection
     * @param $short_domain
     * @param $bind_user
     * @param $bind_password
     * @return bool
     */
    private function authenticate($connection, $short_domain, $bind_user, $bind_password) {
        // Try to bind with the connection and credentials
        $bind = @ldap_bind($connection, $short_domain . '\\' . $bind_user, $bind_password);
        // Return a boolean based on the successes of the bind
        return ($bind) ? true : false;
    }

    /**
     * Connects to ldap server. Supports multiple LDAP servers, uses the first one that binds correctly and sets the class $connection variable.
     * @param $prefix
     * @param $hosts
     * @param $port
     * @param $short_domain
     * @param $bind_user
     * @param $bind_password
     * @return resource
     */
	private function connect($prefix, $hosts, $port, $short_domain, $bind_user, $bind_password) {

	    // Note: this could potentially be improved by taking an associative array with hosts as the key and the port as the value.
        // As it is right now, only one port is applied to all hosts.

        // Set the certpath if it is there
    	if (isset($_SESSION["ldap"]["certpath"])) {
			$s = "LDAPTLS_CERT=" . $_SESSION["ldap"]["certpath"]["text"];
			putenv($s);
		}
		// Set the certkey if it is there
		if (isset($_SESSION["ldap"]["certkey"])) {
			$s = "LDAPTLS_KEY=" . $_SESSION["ldap"]["certkey"]["text"];
			putenv($s);
		}

        // Loop over each host, return the first to connect
        foreach ($hosts as $host) {
        	// Connect to the current host using the scheme and port specified
            $this->connection = ldap_connect($prefix . $host, $port);
            // Set ldap options
           	//ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, 10);
			//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
            ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

            // If the authenticate function returns true, break out of the foreach hosts loop and return the connection resource.
            // Otherwise move onto the next host.
            if ($this->authenticate($this->connection, $short_domain, $bind_user, $bind_password)) {
                return $this->connection;
            }
        }
        // Die if we make it here, none of the servers provided worked
        die('Could not connect to any LDAP servers. [' . implode(', ',$hosts) . ']:' . $port);
    }


	/**
	 * ldap checks a local or remote ldap database to authenticate the user
	 * @return array [authorized] => true or false
	 */
	function ldap() {

		//save the database connection to a local variable
			include "root.php";
			require_once "resources/classes/database.php";
			$database = new database;
			$database->connect();
			$db = $database->db;

        // gather variables that pertain to ldap
            $ldap_prefix = ($_SESSION["ldap"]["secure"]["boolean"]) ? 'ldaps://' : 'ldap://';
            $ldap_port = $_SESSION["ldap"]["server_port"]["numeric"];
            $ldap_bind_user = $_SESSION["ldap"]["bind_username"]["text"];
            $lda_bind_password = $_SESSION["ldap"]["bind_password"]["text"];
            $ldap_short_domain = $_SESSION["ldap"]["short_domain"]["text"];
        //provide backwards compatibility for hosts
            if (strlen($_SESSION["ldap"]["server_host"]["text"]) > 0) {
            	$_SESSION["ldap"]["server_host"][] = $_SESSION["ldap"]["server_host"]["text"];
            }
			$ldap_hosts = $_SESSION["ldap"]["server_host"];

        // connect to ldap
			$connect = $this->connect($ldap_prefix, $ldap_hosts, $ldap_port, $ldap_short_domain, $ldap_bind_user, $lda_bind_password);

		// determine if the user logging in is authorized
			$user_authorized = $this->authenticate($connect, $ldap_short_domain, $this->username, $this->password);


		//check to see if the user exists
			 if ($user_authorized) {
				$sql = "select * from v_users ";
				$sql .= "where username=:username ";
				if ($_SESSION["user"]["unique"]["text"] == "global") {
					//unique username - global (example: email address)
				}
				else {
					//unique username - per domain
					$sql .= "and domain_uuid=:domain_uuid ";
				}
				$prep_statement = $db->prepare(check_sql($sql));
				if ($_SESSION["user"]["unique"]["text"] != "global") {
					$prep_statement->bindParam(':domain_uuid', $this->domain_uuid);
				}
				$prep_statement->bindParam(':username', $this->username);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
							if ($_SESSION["user"]["unique"]["text"] == "global" && $row["domain_uuid"] != $this->domain_uuid) {
								//get the domain uuid
									$this->domain_uuid = $row["domain_uuid"];
									$this->domain_name = $_SESSION['domains'][$this->domain_uuid]['domain_name'];

								//set the domain session variables
									$_SESSION["domain_uuid"] = $this->domain_uuid;
									$_SESSION["domain_name"] = $this->domain_name;

								//set the setting arrays
									$domain = new domains();
									$domain->db = $db;
									$domain->set();
							}
							$this->user_uuid = $row["user_uuid"];
							$this->contact_uuid = $row["contact_uuid"];
					}
				}
				else {
					//salt used with the password to create a one way hash
						$salt = generate_password('32', '4');
						$password = generate_password('32', '4');

					//prepare the uuids
						$this->user_uuid = uuid();
						$this->contact_uuid = uuid();

					//add the user
						$sql = "insert into v_users ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "user_uuid, ";
						$sql .= "contact_uuid, ";
						$sql .= "username, ";
						$sql .= "password, ";
						$sql .= "salt, ";
						$sql .= "add_date, ";
						$sql .= "add_user, ";
						$sql .= "user_enabled ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$this->domain_uuid."', ";
						$sql .= "'".$this->user_uuid."', ";
						$sql .= "'".$this->contact_uuid."', ";
						$sql .= "'".strtolower($this->username)."', ";
						$sql .= "'".md5($salt.$password)."', ";
						$sql .= "'".$salt."', ";
						$sql .= "now(), ";
						$sql .= "'".strtolower($this->username)."', ";
						$sql .= "'true' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);

					//add the user to group user
						$group_name = 'user';
						$sql = "insert into v_group_users ";
						$sql .= "(";
						$sql .= "group_user_uuid, ";
						$sql .= "domain_uuid, ";
						$sql .= "group_name, ";
						$sql .= "user_uuid ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".uuid()."', ";
						$sql .= "'".$this->domain_uuid."', ";
						$sql .= "'".$group_name."', ";
						$sql .= "'".$this->user_uuid."' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
				}
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
			if ($user_authorized) {
				$result["authorized"] = "true";
			}
			else {
				$result["authorized"] = "false";
			}
			return $result;
	}
}

?>