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

		//save the database connection to a local variable
			include "root.php";
			require_once "resources/classes/database.php";
			$database = new database;
			$database->connect();
			$db = $database->db;

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
			$connect = ldap_connect($host,$port)
				or die("Could not connect to the LDAP server.");
			//ldap_set_option($connect, LDAP_OPT_NETWORK_TIMEOUT, 10);
			ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
			//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);

		//set the default for $user_authorized to false
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
					else {
						//connection failed
						$user_authorized = false;
					}
				}
				else {
					//password not provided
					$user_authorized = false;
				}
			}

		//check to see if the user exists
			 if ($user_authorized) {
				$sql = "select * from v_users ";
				$sql .= "where username=:username ";
				if ($_SESSION["users"]["unique"]["text"] == "global") {
					//unique username - global (example: email address)
				}
				else {
					//unique username - per domain
					$sql .= "and domain_uuid=:domain_uuid ";
				}
				$prep_statement = $db->prepare(check_sql($sql));
				if ($_SESSION["users"]["unique"]["text"] != "global") {
					$prep_statement->bindParam(':domain_uuid', $this->domain_uuid);
				}
				$prep_statement->bindParam(':username', $this->username);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
							if ($_SESSION["users"]["unique"]["text"] == "global" && $row["domain_uuid"] != $this->domain_uuid) {
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
						$sql = "insert into v_user_groups ";
						$sql .= "(";
						$sql .= "user_group_uuid, ";
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
