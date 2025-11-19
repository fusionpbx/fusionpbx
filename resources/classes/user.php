<?php

/*
* user class - used to store user groups, permissions, and other values
*/

class user {

	public $domain_uuid;
	public $domain_name;
	public $username;
	public $user_email;
	public $contact_uuid;
	private $database;
	private $user_uuid;
	private $permissions;
	private $groups;

	/**
	 * Constructor for the class.
	 *
	 * This method initializes the object with setting_array and session data.
	 *
	 * @param array $setting_array An optional array of settings to override default values. Defaults to [].
	 */
	public function __construct(database $database, $domain_uuid, $user_uuid) {

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

		//set the user groups, permission, and details
		if (isset($domain_uuid) && is_uuid($domain_uuid) && isset($user_uuid) && is_uuid($user_uuid)) {
			$this->set_groups();
			$this->set_permissions();
			$this->set_details();
		}
	}

	/*
	* set_details method sets the user assigned details
	*/
	/**
	 * Sets the user details based on the domain UUID and user UUID.
	 *
	 * This method queries the database to retrieve the user's details,
	 * including their domain name, username, email address, and contact UUID.
	 *
	 * @access public
	 *
	 * @return bool True if the query is successful, false otherwise.
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
	/**
	 * Retrieves the user's UUID.
	 *
	 * @return string The user's unique identifier in UUID format.
	 */
	public function get_user_uuid() {
		return $this->user_uuid;
	}

	/*
	* set_permissions method sets the user assigned permissions
	*/

	/**
	 * Retrieves the permissions associated with this entity.
	 *
	 * @return array An array of permission objects or identifiers.
	 * @access public
	 */
	public function get_permissions() {
		return $this->permissions->get_permissions();
	}

	/*
	* get_permissions method gets the user assigned permissions
	*/

	/**
	 * Sets the user's permissions.
	 *
	 * @access public
	 * @return void
	 */
	public function set_permissions() {
		$this->permissions = new permissions($this->database, $this->domain_uuid, $this->user_uuid);
	}

	/*
	* set_groups method sets the user assigned groups
	*/

	/**
	 * Retrieves the user's groups.
	 *
	 * @return array An array of group objects that the user belongs to.
	 */
	public function get_groups() {
		return $this->groups->get_groups();
	}

	/*
	* get_groups method gets the user assigned groups
	*/

	/**
	 * Sets the user's group assignments.
	 *
	 * @return void
	 */
	public function set_groups() {
		$this->groups = new groups($this->database, $this->domain_uuid, $this->user_uuid);
	}

}
