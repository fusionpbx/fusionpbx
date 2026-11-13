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
	 * Maximum number of unvalidated tokens kept per key. This bounds session growth
	 * caused by tokens that are created but never validated (for example, a page that
	 * is refreshed repeatedly without using its action links).
	 */
	const MAX_TOKENS_PER_KEY = 20;

	/**
	 * Class constructor
	 */
	public function __construct() {

	}

	/**
	 * Create the token
	 *
	 * @var string $key
	 */
	public function create($key) {

		//clear previously validated tokens
		$this->clear_validated();

		//allow only specific characters
		$key = preg_replace('/[^a-zA-Z0-9\-_@.\/]/', '', $key);

		//create a token for the key submitted
		$token = [
			'name' => hash_hmac('sha256', $key, bin2hex(random_bytes(32))),
			'hash' => hash_hmac('sha256', $key, bin2hex(random_bytes(32))),
			'validated' => false,
		];

		//save in the token session array
		$_SESSION['tokens'][$key][] = $token;

		//bound the number of unvalidated tokens stored for this key
		if (is_array($_SESSION['tokens'][$key]) && sizeof($_SESSION['tokens'][$key]) > self::MAX_TOKENS_PER_KEY) {
			$_SESSION['tokens'][$key] = array_slice($_SESSION['tokens'][$key], -self::MAX_TOKENS_PER_KEY, null, true);
		}

		//send the hash
		return $token;

	}

	/**
	 * clear previously validated tokens
	 */
	private function clear_validated() {
		if (!empty($_SESSION['tokens']) && is_array($_SESSION['tokens']) && @sizeof($_SESSION['tokens']) != 0) {
			foreach ($_SESSION['tokens'] as $key => $tokens) {
				if (is_array($tokens) && @sizeof($tokens) != 0) {
					foreach ($tokens as $t => $token) {
						if (!empty($token['validated'])) {
							unset($_SESSION['tokens'][$key][$t]);
						}
					}
				}
			}
		}
	}

	/**
	 * Checks that the request originated from this site.
	 *
	 * A token proves the user was shown a form by this application; it does not prove
	 * which site the browser was on when the request was sent. Checking Origin (and,
	 * for older browsers, Referer) closes that gap and stops a cross-site page from
	 * replaying a token it could not have read, or triggering a state changing GET by
	 * top level navigation.
	 *
	 * Requests with no Origin and no Referer header are allowed through because many
	 * privacy configurations strip both headers; the token check still applies. This
	 * method only rejects requests that positively claim to be from another origin.
	 *
	 * @return bool True when the request is same origin or cannot be identified.
	 */
	public static function same_origin(): bool {
		//get the expected host, falling back to the server name
		$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
		if ($host === '') {
			$host = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
		}
		if ($host === '') {
			//nothing to compare against, do not block the request
			return true;
		}

		//the host without its port, used for the host comparison
		$host_name = preg_replace('/:\d+$/', '', $host);

		//the server port is only compared when both sides declare one. A reverse proxy
		//commonly makes these disagree, so an omitted port is treated as unspecified
		//rather than as a mismatch.
		$server_port = (string)($_SERVER['SERVER_PORT'] ?? '');
		if ($server_port === '80' || $server_port === '443') {
			$server_port = '';
		}

		//compare one origin style URL against the expected host, returns null when the
		//value cannot be parsed so the caller can fall through to the next header
		$matches_origin = function (string $url) use ($host_name, $server_port): ?bool {
			if (!preg_match('/^[a-z][a-z0-9+.\-]*:\/\/([^\/:?#]+)(?::([0-9]+))?(?:[\/?#]|$)/', $url, $parts)) {
				return null;
			}
			if (strtolower($parts[1]) !== $host_name) {
				return false;
			}
			$origin_port = $parts[2] ?? '';
			if ($origin_port !== '' && $server_port !== '' && $origin_port !== $server_port) {
				return false;
			}
			return true;
		};

		//check the Origin header first, it is sent by all modern browsers on state
		//changing requests and cannot be forged by script
		$origin = strtolower(trim((string)($_SERVER['HTTP_ORIGIN'] ?? '')));
		if ($origin !== '' && $origin !== 'null') {
			$result = $matches_origin($origin);
			//an origin that cannot be parsed is treated as foreign
			return $result === null ? false : $result;
		}

		//fall back to the Referer header for requests that do not send Origin
		$referer = strtolower(trim((string)($_SERVER['HTTP_REFERER'] ?? '')));
		if ($referer !== '') {
			$result = $matches_origin($referer);
			if ($result !== null) {
				return $result;
			}
		}

		//no usable Origin or Referer header was provided
		return true;
	}

	/**
	 * Validate a token for a state changing endpoint.
	 *
	 * Preferred name to use on new endpoints. It is equivalent to validate(), which
	 * already performs the same origin check, and exists so call sites read clearly.
	 *
	 * @var string $key   The key used when the token was created.
	 * @var string $value An optional explicit token value.
	 *
	 * @return bool True when the request is same origin and the token is valid.
	 */
	public function validate_request($key, $value = '') {
		return $this->validate($key, $value);
	}

	/**
	 * validate the token
	 *
	 * @var string $key
	 * @var string $value
	 */
	public function validate($key, $value = '') {

		//reject requests that were initiated from another site (defense in depth against CSRF)
		if (!self::same_origin()) {
			return false;
		}

		//allow only specific characters
		$key = preg_replace('/[^a-zA-Z0-9\-\_@.\/]/', '', $key);

		//get the token name
		if (!empty($_SESSION['tokens']) && is_array($_SESSION['tokens'][$key]) && @sizeof($_SESSION['tokens'][$key]) != 0) {
			foreach ($_SESSION['tokens'][$key] as $t => $token) {
				$token_name = $token['name'];
				if (isset($_REQUEST[$token_name])) {
					$value = $_REQUEST[$token_name];
					break;
				}
			}
		}

		//limit the value to specific characters
		$value = preg_replace('/[^a-zA-Z0-9]/', '', (string)$value);

		//compare the hashed tokens
		if (!empty($_SESSION['tokens']) && is_array($_SESSION['tokens'][$key]) && @sizeof($_SESSION['tokens'][$key]) != 0) {
			foreach ($_SESSION['tokens'][$key] as $t => $token) {
				if (hash_equals($token['hash'], $value)) {
					$_SESSION['tokens'][$key][$t]['validated'] = true;
					return true;
				}
			}
		}
		return false;

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

//------------------------

//validate the token and require that the request came from this site
	$token = new token;
	if (!$token->validate_request('/app/bridges/bridge_edit.php')) {
		$_SESSION["message"] = $text['message-invalid_token'];
		header('Location: bridges.php');
		exit;
	}

*/
