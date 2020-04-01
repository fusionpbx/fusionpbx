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
	Portions created by the Initial Developer are Copyright (C) 2019-2020
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

		//clear previously validated tokens
			$this->clear_validated();

		//allow only specific characters
			$key = preg_replace('[^a-zA-Z0-9\-_@.\/]', '', $key);

		//create a token for the key submitted
			$token = [
				'name'=>hash_hmac('sha256', $key, bin2hex(random_bytes(32))),
				'hash'=>hash_hmac('sha256', $key, bin2hex(random_bytes(32))),
				'validated'=>false
				];

		//save in the token session array
			$_SESSION['tokens'][$key][] = $token;

		//send the hash
			return $token;

	}

	/**
	 * validate the token
	 * @var string $key
	 * @var string $value
	 */
	public function validate($key, $value = null) {

		//allow only specific characters
			$key = preg_replace('[^a-zA-Z0-9]', '', $key);

		//get the token name
			if (is_array($_SESSION['tokens'][$key]) && @sizeof($_SESSION['tokens'][$key]) != 0) {
				foreach ($_SESSION['tokens'][$key] as $t => $token) {
					$token_name = $token['name'];
					if (isset($_REQUEST[$token_name])) {
						$value = $_REQUEST[$token_name];
					}
				}
			}

		//limit the value to specific characters
			$value = preg_replace('[^a-zA-Z0-9]', '', $value);

		//compare the hashed tokens
			if (is_array($_SESSION['tokens'][$key]) && @sizeof($_SESSION['tokens'][$key]) != 0) {
				foreach ($_SESSION['tokens'][$key] as $t => $token) {
					if (hash_equals($token['hash'], $value)) {
						$_SESSION['tokens'][$key][$t]['validated'] = true;
						return true;
					}
				}
			}
			return false;

	}

	/**
	 * clear previously validated tokens
	 */
	private function clear_validated() {
		if (is_array($_SESSION['tokens']) && @sizeof($_SESSION['tokens']) != 0) {
			foreach ($_SESSION['tokens'] as $key => $tokens) {
				if (is_array($tokens) && @sizeof($tokens) != 0) {
					foreach ($tokens as $t => $token) {
						if ($token['validated']) {
							unset($_SESSION['tokens'][$key][$t]);
						}
					}
				}
			}
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

//note: can use $_SERVER['PHP_SELF'] instead of actual file path

*/

?>