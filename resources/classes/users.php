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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/


/**
 * users class provides methods for adding and removing users
 *
 * @method string add
 * @method boolean delete
 */
if (!class_exists('users')) {
	class users {

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//place holder
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * add a user
		 */
		public function add($username, $password) {
			$id = uuid();
			//return $id;
			return false;
		}

		/**
		 * delete a user
		 */
		public function delete($id) {
			return false;
		}

	} //end scripts class
}
/*
//example use
	$user = new users;
	$user->add($username, $password);
*/
?>