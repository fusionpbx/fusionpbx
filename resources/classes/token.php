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

		//allow only specific characters
		$key = preg_replace('[^a-zA-Z0-9\-_@.\/]', '', $key);

		//create a token and save in the token session array
		$_SESSION['tokens'][$key]['name'] = hash_hmac('sha256', $key, bin2hex(random_bytes(32)));
		$_SESSION['tokens'][$key]['hash'] = hash_hmac('sha256', $key, bin2hex(random_bytes(32)));

		//send the hash
		return $_SESSION['tokens'][$key];

	}

	/**
	 * validate the token
	 * @var string $key
	 */
	public function validate($key, $value = null) {

		//allow only specific characters
		$key = preg_replace('[^a-zA-Z0-9]', '', $key);

		//get the token name
		$token_name = $_SESSION['tokens'][$key]['name'];
		if (isset($_REQUEST[$token_name])) {
			$value = $_REQUEST[$token_name];
		}
		else {
			$value;
		}

		//limit the value to specific characters
		$value = preg_replace('[^a-zA-Z0-9]', '', $value);

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
	$object = new token;
	$token = $object->create('/app/bridges/bridge_edit.php');

echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

//------------------------

//validate the token
	$token = new token;
	if (!$token->validate('/app/bridges/bridge_edit.php')) {
		$_SESSION["message"] = $text['message-invalid_token'];
		header('Location: bridges.php');
		exit;
	}

*/

?>
