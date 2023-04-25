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
 Portions created by the Initial Developer are Copyright (C) 2020
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the databases class
if (!class_exists('databases')) {
	class databases {

		const NAME = 'databases';
		const UUID = '8d229b6d-1383-fcec-74c6-4ce1682479e2';
		const PERMISSION_PREFIX = 'database_';
		const LIST_PAGE = 'databases.php';
		const TABLE = 'databases';
		const UUID_PREFIX = 'database_';
		
		public function __get($name) {
			switch($name) {
				case 'app_name':
					return self::NAME;
				case 'app_uuid':
					return self::UUID;
				case 'permission_prefix':
					return self::PERMISSION_PREFIX;
				case 'list_page':
					return self::LIST_PAGE;
				case 'table':
					return self::TABLE;
				case 'uuid_prefix':
					return self::UUID_PREFIX;
			}
		}
		
		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists(self::PERMISSION_PREFIX.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.self::LIST_PAGE);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[self::TABLE][$x][self::UUID_PREFIX.'uuid'] = $record['uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = self::NAME;
									$database->app_uuid = self::UUID;
									$database->delete($array);
									unset($array);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * copy records
		 */
		public function copy($records) {
			if (permission_exists(self::PERMISSION_PREFIX.'add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.self::LIST_PAGE);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select * from v_".self::TABLE." ";
								$sql .= "where ".self::UUID_PREFIX."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, '', 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $x => $row) {

										//copy data
											$array[self::TABLE][$x] = $row;

										//overwrite
											$array[self::TABLE][$x][self::UUID_PREFIX.'uuid'] = uuid();
											$array[self::TABLE][$x]['database_description'] = trim($row['database_description'].' ('.$text['label-copy'].')');

									}
								}
								unset($sql, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = self::NAME;
									$database->app_uuid = self::UUID;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		}

	}
}
