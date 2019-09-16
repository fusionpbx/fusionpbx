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
	Portions created by the Initial Developer are Copyright (C) 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * captcha class
 *
 * @method string get
 */
class token {

	/**
	* Called when the object is created
	*/
	//public $code;

	/**
	* Class constructor
	*/
	public function __construct() {

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
	 * Create the token
	 * @var string $key
	 */
	public function create($key) {

		//create a token and save in the token session array
		$_SESSION['tokens'][$key]['name'] = hash_hmac('sha256', $key, bin2hex(random_bytes(32)));
		$_SESSION['tokens'][$key]['hash'] = hash_hmac('sha256', $key, bin2hex(random_bytes(32)));

		//send the hash
		return $_SESSION['tokens'][$key]['hash'];
	}

	/**
	 * validate the token
	 * @var string $key
	 */
	public function validate($key, $value) {

		//compare the hashed tokens
		if (hash_equals($_SESSION['tokens'][$key]['hash'], $value)) {
			return true;
		}
		else {
			return false;
		}

	}

}

/*

//create token
$token = new token;
$token_hash = $token->create('/app/users/user_edit.php');

echo "<input type='hidden' name='token' value='".$token_hash."'>";

//------------------------

//validate the token
$token = new token;
$token_valid = $token->validate('/app/users/user_edit.php', $_POST['token']);
if (!$token_valid) {
	echo "access denied";
	exit;
}

*/

?>
