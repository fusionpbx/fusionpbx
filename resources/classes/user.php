<?php

/*
* user class - use to store user groups, permissions and other values
*/
class user {

	private $database;
	public  $domain_uuid;
	public  $domain_name;
	private $user_uuid;
	private $permissions;
	private $groups;
	public  $username;
	public  $user_email;
	public  $contact_uuid;

	public function __construct(database $database, $domain_uuid = null, $user_uuid = null) {

		//set the database variable
		$this->database = $database;

		//set the domain_uuid
		if (isset($domain_uuid) && is_uuid($domain_uuid)) {
			$this->domain_uuid = $domain_uuid;
		}

		//set the user_uuid
		if (isset($user_uuid) && is_uuid($user_uuid)) {
			$this->user_uuid = $user_uuid;
		}

		//set the user groups, permission and details
		if (isset($domain_uuid) && is_uuid($domain_uuid) && isset($user_uuid) && is_uuid($user_uuid)) {
			$this->set_groups();
			$this->set_permissions();
			$this->set_details();
		}
	}

	/*
	* set_details method sets the user assigned details
	*/
	public function set_details() {
		$sql = "select d.domain_name, u.username, u.user_email, u.contact_uuid ";
		$sql .= "from v_users as u, v_domains as d ";
		$sql .= "where u.domain_uuid = :domain_uuid ";
		$sql .= "and u.user_uuid = :user_uuid ";
		$sql .= "and u.domain_uuid = d.domain_uuid ";
		$sql .= "and u.user_setting_enabled = 'true' ";
		$parameters['domain_uuid'] = $this->domain_uuid;
		$parameters['user_uuid'] = $this->user_uuid;
		$row = $this->database->select($sql, $parameters, 'row');
		if (is_array($row)) {
			$this->domain_name = $row['domain_name'];
			$this->username = $row['username'];
			$this->user_email = $row['user_email'];
			$this->contact_uuid = $row['contact_uuid'];
		}
	}

	/*
	* get_user_uuid method gets the user_uuid
	*/
	public function get_user_uuid() {
		return $this->user_uuid;
	}

	/*
	* set_permissions method sets the user assigned permissions
	*/
	public function set_permissions() {
		$this->permissions = new permissions($this->database, $this->domain_uuid, $this->user_uuid);
	}

	/*
	* get_permissions method gets the user assigned permissions
	*/
	public function get_permissions() {
		return $this->permissions->get_permissions();
	}

	/*
	* set_groups method sets the user assigned groups
	*/
	public function set_groups() {
		$this->groups = new groups($this->database, $this->domain_uuid, $this->user_uuid);
	}

	/*
	* get_groups method gets the user assigned groups
	*/
	public function get_groups() {
		return $this->groups->get_groups();
	}

}

?>
