<?php

/**
 * TOTP (Time-based One-Time Password) authenticator.
 *
 * Implements RFC 6238 (TOTP) and RFC 4226 (HOTP) using HMAC-SHA1.
 * Provides secure secret generation, code verification, and otpauth URL building.
 *
 */
class authenticator {

	/**
	 * Number of digits in each one-time password code.
	 *
	 * @var int
	 */
	const code_length = 6;

	/**
	 * Time step in seconds for TOTP window.
	 *
	 * @var int
	 */
	const time_step = 30;

	/**
	 * Number of allowed time windows to check (current ± 1).
	 *
	 * @var int
	 */
	const time_window = 1;

	/**
	 * Base32 alphabet per RFC 4648 (uppercase, no padding).
	 *
	 * @var string
	 */
	const base32_alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * Generates a cryptographically secure Base32-encoded secret.
	 *
	 * Uses random_bytes() which draws from the operating system's
	 * CSPRNG, ensuring the secret is unpredictable.
	 *
	 * @param int $bytes Number of random bytes to encode (default 20).
	 *
	 * @return string Base32-encoded secret string.
	 */
	public function generate_secret(int $bytes = 20): string {
		return self::base32_encode(random_bytes($bytes));
	}

	/**
	 * Generates a TOTP code for the given secret and optional timestamp.
	 *
	 * Implements the HOTP algorithm from RFC 4226:
	 *   1. Decode Base32 secret to raw bytes.
	 *   2. Pack the 32-bit time counter as an 8-byte big-endian value.
	 *   3. Compute HMAC-SHA1 over the counter using the secret as key.
	 *   4. Dynamic truncation to extract a 31-bit integer.
	 *   5. Modulo 10^CODE_LENGTH to get the numeric code.
	 *
	 * @param string $secret Base32-encoded secret.
	 * @param int|null $timestamp Unix timestamp to use (defaults to now).
	 *
	 * @return string Zero-padded numeric code string.
	 *
	 * @throws InvalidArgumentException If the secret is empty or invalid.
	 */
	public function get_code(string $secret, ?int $timestamp = null): string {
		if ($secret === '') {
			throw new InvalidArgumentException('TOTP secret must not be empty.');
		}

		$raw_secret = self::base32_decode($secret);
		if ($raw_secret === null || $raw_secret === '') {
			throw new InvalidArgumentException('Invalid Base32 secret provided.');
		}

		if ($timestamp === null) {
			$timestamp = time();
		}

		$counter = intdiv($timestamp, self::time_step);
		$hash = $this->hotp($raw_secret, $counter);

		return str_pad($hash % (10 ** self::code_length), self::code_length, '0', STR_PAD_LEFT);
	}

	/**
	 * Validates a user-supplied TOTP code against the stored secret.
	 *
	 * Checks the current time window plus one step in each direction
	 * to tolerate minor clock skew. Uses hash_equals() for constant-time
	 * comparison to prevent timing attacks.
	 *
	 * @param string $secret Base32-encoded secret.
	 * @param string $code   The numeric code to verify.
	 *
	 * @return bool True if the code is valid, false otherwise.
	 */
	public function check_code(string $secret, $code): bool {
		// Enforce strict format: exactly CODE_LENGTH digits, no whitespace or other characters
		if (!is_string($code) || strlen($code) !== self::code_length || !ctype_digit($code)) {
			return false;
		}

		if ($secret === '') {
			return false;
		}

		$raw_secret = self::base32_decode($secret);
		if ($raw_secret === null || $raw_secret === '') {
			return false;
		}

		$current_counter = intdiv(time(), self::time_step);

		for ($offset = -self::time_window; $offset <= self::time_window; $offset++) {
			$counter = $current_counter + $offset;
			if ($counter < 0) {
				continue;
			}

			$expected = $this->hotp($raw_secret, $counter);
			$expected_str = str_pad($expected % (10 ** self::code_length), self::code_length, '0', STR_PAD_LEFT);

			if (hash_equals($expected_str, $code)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Builds an otpauth:// URI for TOTP provisioning (QR code content).
	 *
	 * Per the otpauth URI format specification:
	 *   otpauth://totp/issuer:account?secret=...&issuer=...&algorithm=SHA1&digits=6&period=30
	 *
	 * @param string $account  The account name (e.g., username or email).
	 * @param string $issuer   The issuer name (e.g., domain or application name).
	 * @param string $secret   The Base32-encoded TOTP secret.
	 *
	 * @return string The complete otpauth:// URI.
	 */
	public function get_otp_auth_url(string $account, string $issuer, string $secret): string {
		$label = rawurlencode($issuer . ':' . $account);
		$params = http_build_query([
			'secret'    => $secret,
			'issuer'    => $issuer,
			'algorithm' => 'SHA1',
			'digits'    => self::code_length,
			'period'    => self::time_step,
		]);

		return "otpauth://totp/{$label}?{$params}";
	}

	/**
	 * Computes the HOTP value using dynamic truncation (RFC 4226 §5.3).
	 *
	 * @param string $rawSecret Raw binary key.
	 * @param int    $counter   32-bit counter (time step).
	 *
	 * @return int The truncated 31-bit integer result.
	 */
	private function hotp(string $raw_secret, int $counter): int {
		// Pack counter as 8-byte big-endian (RFC 4226 requires 64-bit counter)
		$bin_counter = self::int_to_bytes($counter, 8);

		$mac = hash_hmac('sha1', $bin_counter, $raw_secret, true);

		// Dynamic truncation: last nibble of the HMAC gives the offset
		$offset = ord($mac[19]) & 0x0f;

		// Extract 4 bytes from the offset position, clear the sign bit
		$value = 0;
		for ($i = 0; $i < 4; $i++) {
			$value = ($value << 8) | ord($mac[$offset + $i]);
		}
		$value &= 0x7fffffff;

		return $value;
	}

	/**
	 * Encodes a binary string to Base32 (RFC 4648, uppercase, no padding).
	 *
	 * @param string $data Raw binary data.
	 *
	 * @return string Base32-encoded string.
	 */
	private static function base32_encode(string $data): string {
		$alphabet = self::base32_alphabet;
		$result = '';
		$buffer = 0;
		$bits_remaining = 0;

		for ($i = 0, $len = strlen($data); $i < $len; $i++) {
			$buffer = ($buffer << 8) | ord($data[$i]);
			$bits_remaining += 8;

			while ($bits_remaining >= 5) {
				$bits_remaining -= 5;
				$result .= $alphabet[($buffer >> $bits_remaining) & 0x1f];
				$buffer &= (1 << $bits_remaining) - 1;
			}
		}

		if ($bits_remaining > 0) {
			$result .= $alphabet[($buffer << (5 - $bits_remaining)) & 0x1f];
		}

		return $result;
	}

	/**
	 * Decodes a Base32 string to raw binary (RFC 4648, case-insensitive).
	 *
	 * Accepts both uppercase and lowercase input. Ignores padding '=' characters.
	 *
	 * @param string $encoded Base32-encoded string.
	 *
	 * @return string|null Raw binary data, or null if decoding fails.
	 */
	private static function base32_decode(string $encoded): ?string {
		$alphabet = self::base32_alphabet;
		$lookup = array_flip(str_split($alphabet));
		$result = '';
		$buffer = 0;
		$bits_remaining = 0;

		for ($i = 0, $len = strlen($encoded); $i < $len; $i++) {
			$char = strtoupper($encoded[$i]);

			// Skip padding
			if ($char === '=') {
				break;
			}

			if (!isset($lookup[$char])) {
				return null;
			}

			$buffer = ($buffer << 5) | $lookup[$char];
			$bits_remaining += 5;

			if ($bits_remaining >= 8) {
				$bits_remaining -= 8;
				$result .= chr(($buffer >> $bits_remaining) & 0xff);
				$buffer &= (1 << $bits_remaining) - 1;
			}
		}

		return $result;
	}

	/**
	 * Converts an integer to a fixed-length big-endian byte string.
	 *
	 * @param int  $value  The integer value.
	 * @param int  $length Number of bytes in the output.
	 *
	 * @return string Binary representation.
	 */
	private static function int_to_bytes(int $value, int $length): string {
		$bytes = '';
		for ($i = $length - 1; $i >= 0; $i--) {
			$bytes .= chr(($value >> ($i * 8)) & 0xff);
		}
		return $bytes;
	}
}
