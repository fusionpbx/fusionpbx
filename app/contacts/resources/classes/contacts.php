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
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the contacts class
if (!class_exists('contacts')) {
	class contacts {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $tables;
		private $uuid_prefix;

		/**
		 * declare public variables
		 */
		public $contact_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'contacts';
				$this->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
				$this->permission_prefix = 'contact_';
				$this->list_page = 'contacts.php';
				$this->tables[] = 'contact_addresses';
				$this->tables[] = 'contact_attachments';
				$this->tables[] = 'contact_emails';
				$this->tables[] = 'contact_groups';
				$this->tables[] = 'contact_notes';
				$this->tables[] = 'contact_phones';
				$this->tables[] = 'contact_relations';
				$this->tables[] = 'contact_settings';
				$this->tables[] = 'contact_times';
				$this->tables[] = 'contact_urls';
				$this->tables[] = 'contact_users';
				$this->tables[] = 'contacts';
				$this->uuid_prefix = 'contact_';

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									if (is_array($this->tables) && @sizeof($this->tables) != 0) {
										foreach ($this->tables as $table) {
											$array[$table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
											$array[$table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										}
									}
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temp permissions
									$p = new permissions;
									$database = new database;
									foreach ($this->tables as $table) {
										$p->add($database->singular($table).'_delete', 'temp');
									}

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temp permissions
									$database = new database;
									foreach ($this->tables as $table) {
										$p->delete($database->singular($table).'_delete', 'temp');
									}

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_properties($records) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//validate the token
				$token = new token;
				if (!$token->validate($_SERVER['PHP_SELF'])) {
					message::add($text['message-invalid_token'],'negative');
					header('Location: '.$this->list_page);
					exit;
				}

			//delete multiple records
				if (is_array($records) && @sizeof($records) != 0) {

					//check permissions and build the delete array
						$x = 0;
						foreach ($records as $property_name => $properties) {
							$database = new database;
							if (permission_exists($database->singular($property_name).'_delete')) {
								if (is_array($properties) && @sizeof($properties) != 0) {
									foreach ($properties as $property) {
										if ($property['checked'] == 'true' && is_uuid($property['uuid'])) {
											$array[$property_name][$x][$database->singular($property_name).'_uuid'] = $property['uuid'];
											$array[$property_name][$x]['contact_uuid'] = $this->contact_uuid;
											$array[$property_name][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
											$x++;
										}
									}
								}
							}
						}

					//delete the checked rows
						if (is_array($array) && @sizeof($array) != 0) {
							//execute delete
								$database = new database;
								$database->app_name = $this->app_name;
								$database->app_uuid = $this->app_uuid;
								$database->delete($array);
								unset($array);
						}
						unset($records);
				}
		}

		public function delete_users($records) {
			//assign private variables
				$this->permission_prefix = 'contact_user_';
				$this->table = 'contact_users';
				$this->uuid_prefix = 'contact_user_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked ivr menu options, build delete array
							$x = 0;
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['contact_uuid'] = $this->contact_uuid;
									$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);
							}
							unset($records);
					}
			}
		}

		public function delete_groups($records) {
			//assign private variables
				$this->permission_prefix = 'contact_group_';
				$this->table = 'contact_groups';
				$this->uuid_prefix = 'contact_group_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked ivr menu options, build delete array
							$x = 0;
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['contact_uuid'] = $this->contact_uuid;
									$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);
							}
							unset($records);
					}
			}
		} //method

	} //class
}

?>