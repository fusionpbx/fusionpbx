<?php
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
// @package     GoogleAuthenticator
// @link        https://github.com/chregu/GoogleAuthenticator.php
// @author      Christian Stocker
// @license     http://www.apache.org/licenses/LICENSE-2.0  Apache 2.0

class google_authenticator {
	static $PASS_CODE_LENGTH = 6;
	static $PIN_MODULO;
	static $SECRET_LENGTH = 10;

	public function __construct() {
		self::$PIN_MODULO = pow(10, self::$PASS_CODE_LENGTH);
	}

	/**
	 * Checks if the provided code is valid based on the secret and time.
	 *
	 * @param string $secret The secret to verify against.
	 * @param string $code   The code to check for validity.
	 *
	 * @return bool True if the code is valid, false otherwise.
	 */
	public function checkCode($secret, $code) {
		$time = floor(time() / 30);
		for ($i = -1; $i <= 1; $i++) {
			if ($this->getCode($secret, $time + $i) == $code) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generates a PIN code based on the provided secret and time.
	 *
	 * @param string   $secret The secret to use for generating the code.
	 * @param int|null $time   The current time in seconds since the Unix epoch. Defaults to the current time divided
	 *                         by 30.
	 *
	 * @return string A six-digit PIN code.
	 */
	public function getCode($secret, $time = null) {

		if (!$time) {
			$time = floor(time() / 30);
		}

		$base32 = new base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true);
		$secret = $base32->decode($secret);

		$time = pack("N", $time);
		$time = str_pad($time, 8, chr(0), STR_PAD_LEFT);

		$hash = hash_hmac('sha1', $time, $secret, true);
		$offset = ord(substr($hash, -1));
		$offset = $offset & 0xF;

		$truncatedHash = self::hashToInt($hash, $offset) & 0x7FFFFFFF;
		$pinValue = str_pad($truncatedHash % self::$PIN_MODULO, 6, "0", STR_PAD_LEFT);
		return $pinValue;
	}

	/**
	 * Converts a byte array to an integer value.
	 *
	 * @param string $bytes The byte array to convert.
	 * @param int    $start The starting position in the byte array.
	 *
	 * @return int The converted integer value.
	 */
	protected function hashToInt($bytes, $start) {
		$input = substr($bytes, $start, strlen($bytes) - $start);
		$val2 = unpack("N", substr($input, 0, 4));
		return $val2[1];
	}

	/**
	 * Generates a URL for the Google QR code.
	 *
	 * @param string $user     The user's username.
	 * @param string $hostname The hostname of the service.
	 * @param string $secret   The secret to encode in the QR code.
	 *
	 * @return string A URL that can be used to generate a QR code with the provided secret.
	 */
	public function getUrl($user, $hostname, $secret) {
		$url = sprintf("otpauth://totp/%s@%s?secret=%s", $user, $hostname, $secret);
		$encoder = "https://www.google.com/chart?chs=200x200&chld=M|0&cht=qr&chl=";
		$encoderURL = sprintf("%sotpauth://totp/%s@%s&secret=%s", $encoder, $user, $hostname, $secret);
		return $encoderURL;
	}

	/**
	 * Generates a secret based on random characters and converts it to Base32.
	 *
	 * @return string The generated secret in Base32 format.
	 */
	public function generateSecret() {
		$secret = "";
		for ($i = 1; $i <= self::$SECRET_LENGTH; $i++) {
			$c = rand(0, 255);
			$secret .= pack("c", $c);
		}

		$base32 = new base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true);
		return $base32->encode($secret);
	}

}
