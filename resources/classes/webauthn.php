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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * webauthn
 *
 * Server side WebAuthn (FIDO2) support without external libraries.
 * Provides challenge generation, registration / assertion option building,
 * attestation verification (none, packed, fido-u2f and apple formats),
 * assertion verification with COSE public keys (EC2 P-256, EC2 P-384, RSA,
 * Ed25519) and a minimal deterministic CBOR decoder used to parse the
 * attestation object and authenticator data.
 *
 */
class webauthn {

	/**
	 * Authenticator data flag bits
	 */
	const flag_user_present = 0x01;
	const flag_user_verified = 0x04;
	const flag_backup_eligible = 0x08;
	const flag_backup_bound = 0x10;
	const flag_attested_data = 0x40;

	/**
	 * Generate a cryptographically secure random challenge.
	 *
	 * @param int $length Number of bytes (default 32).
	 *
	 * @return string Raw random bytes.
	 */
	public function random_challenge(int $length = 32): string {
		return random_bytes($length);
	}

	/**
	 * Build the public key credential creation options for registration.
	 *
	 * @param string $rp_id   Relying party id (usually the domain name).
	 * @param string $rp_name Human readable relying party name.
	 * @param array  $user    Array with user_uuid, username.
	 * @param array  $exclude_credentials List of base64url credential ids to exclude.
	 *
	 * @return array Options suitable for JSON encoding and navigator.credentials.create().
	 */
	public function generate_registration_options(string $rp_id, string $rp_name, array $user, array $exclude_credentials = [], string $challenge = ''): array {
		$exclude = [];
		foreach ($exclude_credentials as $credential_id) {
			$exclude[] = [
				'type' => 'public-key',
				'id' => $credential_id,
			];
		}

		$options = [
			'rp' => [
				'name' => $rp_name,
				'id' => $rp_id !== '' ? $rp_id : null,
			],
			'user' => [
				'id' => self::base64url_encode($user['user_uuid']),
				'name' => $user['username'],
				'displayName' => $user['username'],
			],
			'challenge' => self::base64url_encode($challenge !== '' ? $challenge : $this->random_challenge(32)),
			'pubKeyCredParams' => [
				['type' => 'public-key', 'alg' => -7],  // ES256 (P-256)
				['type' => 'public-key', 'alg' => -25], // RS256
				['type' => 'public-key', 'alg' => -8],  // EdDSA (Ed25519)
			],
			'timeout' => 60000,
			'attestation' => 'none',
			'authenticatorSelection' => [
				'requireResidentKey' => false,
				'residentKey' => 'preferred',
				'userVerification' => 'preferred',
			],
		];

		if (!empty($exclude)) {
			$options['excludeCredentials'] = $exclude;
		}

		return $options;
	}

	/**
	 * Build the public key credential request options for assertion.
	 *
	 * @param string $rp_id            Relying party id.
	 * @param array  $allow_credentials List of base64url credential ids.
	 *
	 * @return array Options suitable for JSON encoding and navigator.credentials.get().
	 */
	public function generate_assertion_options(string $rp_id, array $allow_credentials = [], string $challenge = ''): array {
		$allow = [];
		foreach ($allow_credentials as $credential_id) {
			$allow[] = [
				'type' => 'public-key',
				'id' => $credential_id,
			];
		}

		$options = [
			'challenge' => self::base64url_encode($challenge !== '' ? $challenge : $this->random_challenge(32)),
			'timeout' => 60000,
			'rpId' => $rp_id !== '' ? $rp_id : null,
			'userVerification' => 'preferred',
		];

		if (!empty($allow)) {
			$options['allowCredentials'] = $allow;
		}

		return $options;
	}

	/**
	 * Verify a WebAuthn registration (attestation) response.
	 *
	 * Supported attestation formats: none, packed (self attestation and x5c
	 * certificate, as used by YubiKey FIDO2), fido-u2f and apple (certn),
	 * which covers iPhone / iPad passkeys and Android passkeys.
	 *
	 * @param array  $credential Public key credential array (as posted by the browser).
	 * @param string $challenge  Raw challenge bytes used to build the options.
	 * @param string $rp_id      Relying party id used to build the options.
	 *
	 * @return array credential_id, credential_id_raw, public_key (raw CBOR COSE
	 *                     key), aaguid, sign_count.
	 *
	 * @throws InvalidArgumentException When the response is malformed or invalid.
	 */
	public function verify_attestation(array $credential, string $challenge, string $rp_id = '') {

		//validate the required parts of the credential
		if (empty($credential['response']['clientDataJSON']) || empty($credential['response']['attestationObject'])) {
			throw new InvalidArgumentException('Missing clientDataJSON or attestationObject.');
		}

		//decode the client data json (keep the raw bytes - they are part of the attestation signature message)
		$client_data_json_raw = self::base64url_decode($credential['response']['clientDataJSON']);
		$client_data = json_decode($client_data_json_raw, true);
		if (!is_array($client_data)) {
			throw new InvalidArgumentException('Invalid clientDataJSON.');
		}

		//validate the client data
		if (($client_data['type'] ?? '') !== 'webauthn.create') {
			throw new InvalidArgumentException('clientDataJSON type is not webauthn.create.');
		}
		if (!self::challenge_matches($client_data, $challenge)) {
			throw new InvalidArgumentException('clientDataJSON challenge mismatch.');
		}
		$this->verify_origin($client_data);

		//decode the attestation object (cbor map)
		$attestation_object = self::decode_cbor(self::base64url_decode($credential['response']['attestationObject']));
		if (!is_array($attestation_object) || !isset($attestation_object['fmt'], $attestation_object['attStmt'], $attestation_object['authData'])) {
			throw new InvalidArgumentException('Invalid attestation object.');
		}

		$fmt = $attestation_object['fmt'];
		$att_stmt = $attestation_object['attStmt'];
		$att_stmt = is_array($att_stmt) ? $att_stmt : [];
		$auth_data = $attestation_object['authData'];
		if (!is_string($auth_data) || strlen($auth_data) < 37) {
			throw new InvalidArgumentException('Invalid authenticator data.');
		}

		//parse the authenticator data
		$auth = $this->parse_authenticator_data($auth_data, true);

		//validate the rp id hash
		if ($rp_id !== '' && hash('sha256', $rp_id, true) !== $auth['rp_id_hash']) {
			throw new InvalidArgumentException('rpIdHash mismatch.');
		}

		//the user present flag must be set
		if (!($auth['flags'] & self::flag_user_present)) {
			throw new InvalidArgumentException('User Present flag is not set.');
		}

		//the attested credential data flag must be set and the data must exist
		if (!($auth['flags'] & self::flag_attested_data) || empty($auth['credential_data'])) {
			throw new InvalidArgumentException('Attested credential data is missing.');
		}

		$credential_id_raw = $auth['credential_data']['credential_id'];
		$public_key_raw = $auth['credential_data']['public_key'];

		//attestation message: authenticator data, hash of the client data json
		$attestation_message = $auth_data . hash('sha256', $client_data_json_raw, true);

		//verify the attestation depending on the format
		switch ($fmt) {
			case 'none':
				//self attestation with no signature is valid
				break;

			case 'packed':
				$this->verify_packed_attestation($att_stmt, $attestation_message, $public_key_raw);
				break;

			case 'fido-u2f':
				//fido u2f: sign 0x00 | rpIdHash | challenge | credentialId | userHandle
				$user_handle = !empty($auth['credential_data']['user_handle']) ? $auth['credential_data']['user_handle'] : hash('sha256', $auth['rp_id_hash'], true);
				$fido_message = "\x00" . $auth['rp_id_hash'] . $challenge . $credential_id_raw . $user_handle;
				if (empty($att_stmt['sig'])) {
					throw new InvalidArgumentException('FIDO U2F attestation signature is missing.');
				}
				//the fido-u2f attestation key is a raw spki / der public key (not a cose key),
				//so verify it directly with openssl instead of the cose based verify_signature
				if (!empty($att_stmt['ecdsaKey'])) {
					$this->verify_spki_signature($fido_message, $att_stmt['sig'], $att_stmt['ecdsaKey']);
				}
				else {
					//no attestation key, fall back to the credential public key (self attestation)
					$this->verify_signature($fido_message, $att_stmt['sig'], $public_key_raw);
				}
				break;

			case 'apple':
				//apple passkeys: sign authData | sha256(challenge) | sha256(credId) | sha256(certn)
				$certn_index = isset($att_stmt['certn']) ? (int)$att_stmt['certn'] : 0;
				if (empty($att_stmt['x5c']) || !is_array($att_stmt['x5c']) || !isset($att_stmt['x5c'][$certn_index])) {
					throw new InvalidArgumentException('Apple attestation certificate is missing.');
				}
				$cert = $att_stmt['x5c'][$certn_index];
				$apple_message = $attestation_message . hash('sha256', $cert, true);
				if (empty($att_stmt['sig'])) {
					throw new InvalidArgumentException('Apple attestation signature is missing.');
				}
				$this->verify_certificate_signature($apple_message, $att_stmt['sig'], $cert);
				break;

			default:
				throw new InvalidArgumentException('Unsupported attestation format: ' . $fmt);
		}

		//build the result array
		return [
			'credential_id' => self::base64url_encode($credential_id_raw),
			'credential_id_raw' => $credential_id_raw,
			'public_key' => $public_key_raw,
			'aaguid' => $auth['credential_data']['aaguid'],
			'sign_count' => $auth['sign_count'],
		];
	}

	/**
	 * Verify a WebAuthn assertion response.
	 *
	 * @param array  $credential_row Row from the credentials table (credential_id,
	 *                               public_key, sign_count, user_handle).
	 * @param array  $response       Response array (clientDataJSON, authenticatorData,
	 *                               signature, userHandle) as posted by the browser.
	 * @param string $challenge      Raw challenge bytes used to build the options.
	 * @param string $rp_id          Relying party id used to build the options.
	 *
	 * @return array sign_count of the authenticator data.
	 *
	 * @throws InvalidArgumentException When verification fails.
	 */
	public function verify_assertion(array $credential_row, array $response, string $challenge, string $rp_id = '') {

		//validate the required parts of the response
		if (empty($response['clientDataJSON']) || empty($response['authenticatorData']) || empty($response['signature'])) {
			throw new InvalidArgumentException('Missing clientDataJSON, authenticatorData or signature.');
		}

		//decode the client data json (keep the raw bytes - they are part of the assertion signature message)
		$client_data_json_raw = self::base64url_decode($response['clientDataJSON']);
		$client_data = json_decode($client_data_json_raw, true);
		if (!is_array($client_data)) {
			throw new InvalidArgumentException('Invalid clientDataJSON.');
		}

		//validate the client data
		if (($client_data['type'] ?? '') !== 'webauthn.get') {
			throw new InvalidArgumentException('clientDataJSON type is not webauthn.get.');
		}
		if (!self::challenge_matches($client_data, $challenge)) {
			throw new InvalidArgumentException('clientDataJSON challenge mismatch.');
		}
		$this->verify_origin($client_data);

		//parse the authenticator data (no attested credential data expected)
		$auth_data = self::base64url_decode($response['authenticatorData']);
		$auth = $this->parse_authenticator_data($auth_data, false);

		//validate the rp id hash
		if ($rp_id !== '' && hash('sha256', $rp_id, true) !== $auth['rp_id_hash']) {
			throw new InvalidArgumentException('rpIdHash mismatch.');
		}

		//the user present flag must be set
		if (!($auth['flags'] & self::flag_user_present)) {
			throw new InvalidArgumentException('User Present flag is not set.');
		}

		//validate the user handle when provided by the authenticator
		$user_handle = '';
		if (!empty($response['userHandle'])) {
			$user_handle = self::base64url_decode($response['userHandle']);
			$stored_user_handle = !empty($credential_row['user_handle']) ? self::base64url_decode($credential_row['user_handle']) : '';
			if ($stored_user_handle !== '' && $user_handle !== $stored_user_handle) {
				throw new InvalidArgumentException('User handle mismatch.');
			}
		}

		//verify the signature with the stored public key
		$signature = self::base64url_decode($response['signature']);
		$public_key_raw = self::base64url_decode($credential_row['public_key']);

		//build the candidate signature messages. per the webauthn spec the message is
		//authenticatorData || sha256(clientDataJSON) || sha256(userHandle), but some
		//authenticators (e.g. the macOS / iOS secure enclave and other platform
		//authenticators) sign without the user handle even when they return one, so
		//both variants are accepted for interoperability
		$assertion_base = $auth_data . hash('sha256', $client_data_json_raw, true);
		$candidate_messages = [
			$assertion_base . hash('sha256', $user_handle, true),
			$assertion_base,
		];

		$signature_valid = false;
		foreach ($candidate_messages as $candidate_message) {
			if ($this->signature_verifies($candidate_message, $signature, $public_key_raw)) {
				$signature_valid = true;
				break;
			}
		}
		if (!$signature_valid) {
			throw new InvalidArgumentException('Signature verification failed.');
		}

		//sign count validation, detect cloned credentials
		$stored_sign_count = (int)($credential_row['sign_count'] ?? 0);
		$current_sign_count = $auth['sign_count'];
		if ($stored_sign_count > 0 && $current_sign_count > 0 && $current_sign_count < $stored_sign_count) {
			//a real (cloned) credential rolls back by a small amount, while a genuine
			//increment that overflowed the 32 bit counter shows up as a large gap,
			//so only reject when the decrease is small (a legitimate wrap is allowed)
			$sign_count_delta = $stored_sign_count - $current_sign_count;
			if ($sign_count_delta < (0xFFFFFFFF >> 1)) {
				throw new InvalidArgumentException('Credential may be cloned (sign count rollback).');
			}
		}

		return ['sign_count' => $current_sign_count];
	}

	/**
	 * Map a known AAGUID to a friendly name.
	 *
	 * @param string $aaguid Raw 16 byte AAGUID.
	 *
	 * @return string Human readable authenticator name.
	 */
	public function aaguid_to_name(string $aaguid): string {
		$aaguid_hex = strtolower(bin2hex($aaguid));

		$aaguid_map = [
			//apple passkeys (iphone, ipad, mac)
			'00000000000000000000000000000001' => 'Apple (iPhone / iPad / Mac)',
			//yubico yubikey fido2
			'f8425763672e1944bc52462a006c8e3f' => 'YubiKey',
		];

		if (isset($aaguid_map[$aaguid_hex])) {
			return $aaguid_map[$aaguid_hex];
		}

		return 'Security key';
	}

	/**
	 * Parse the authenticator data structure.
	 *
	 * @param string $auth_data Raw authenticator data bytes.
	 * @param bool   $expect_attested_data Whether attested credential data is expected.
	 *
	 * @return array rp_id_hash, flags, sign_count, credential_data.
	 *
	 * @throws InvalidArgumentException When the data is malformed.
	 */
	protected function parse_authenticator_data(string $auth_data, bool $expect_attested_data): array {
		if (strlen($auth_data) < 37) {
			throw new InvalidArgumentException('Authenticator data is too short.');
		}

		$result = [
			'rp_id_hash' => substr($auth_data, 0, 32),
			'flags' => ord($auth_data[32]),
			'sign_count' => unpack('N', substr($auth_data, 33, 4))[1],
			'credential_data' => null,
		];

		//parse the attested credential data when the flag is set
		if ($result['flags'] & self::flag_attested_data) {
			$offset = 37;

			//standard attested credential data:
			//aaguid(16) + credIdLen(2) + credId(credIdLen) + attestationCredentialPublicKey(COSE)
			if (strlen($auth_data) < $offset + 16 + 2) {
				throw new InvalidArgumentException('Attested credential data is truncated.');
			}

			//aaguid (16 bytes)
			$aaguid = substr($auth_data, $offset, 16);
			$offset += 16;

			//credential id length (2 bytes)
			$credential_id_length = unpack('n', substr($auth_data, $offset, 2))[1];
			$offset += 2;

			if (strlen($auth_data) < $offset + $credential_id_length + 1) {
				throw new InvalidArgumentException('Attested credential data is truncated.');
			}

			//credential id
			$credential_id = substr($auth_data, $offset, $credential_id_length);
			$offset += $credential_id_length;

			//public key (cbor encoded cose key)
			//the key is the first cbor value; some authenticators (e.g. chrome)
			//append extension output (e.g. credprotect) after it, so we keep only
			//the key itself and drop any trailing bytes from the stored credential.
			$public_key = substr($auth_data, $offset);
			if (strlen($public_key) === 0) {
				throw new InvalidArgumentException('Public key is missing.');
			}
			$cose_len = 0;
			self::decode_cbor_value($public_key, $cose_len);
			if ($cose_len < strlen($public_key)) {
				$public_key = substr($public_key, 0, $cose_len);
			}

			$result['credential_data'] = [
				'aaguid' => $aaguid,
				'credential_id' => $credential_id,
				'public_key' => $public_key,
				'user_handle' => null,
			];
		} elseif ($expect_attested_data) {
			throw new InvalidArgumentException('Attested credential data flag is not set.');
		}

		return $result;
	}



	/**
	 * Verify a packed format attestation signature.
	 *
	 * @param array  $att_stmt            Attestation statement array.
	 * @param string $attestation_message Message to verify.
	 * @param string $public_key_raw      Raw COSE public key bytes (self attestation fallback).
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When verification fails.
	 */
	protected function verify_packed_attestation(array $att_stmt, string $attestation_message, string $public_key_raw): void {
		//x5c certificate attestation (yubikey, android, most authenticators)
		if (!empty($att_stmt['x5c']) && is_array($att_stmt['x5c'])) {
			if (empty($att_stmt['sig'])) {
				throw new InvalidArgumentException('Packed attestation signature is missing.');
			}
			$this->verify_certificate_signature($attestation_message, $att_stmt['sig'], $att_stmt['x5c'][0]);
			return;
		}

		//self attestation using the credential public key
		if (empty($att_stmt['sig'])) {
			throw new InvalidArgumentException('Packed attestation signature is missing.');
		}
		$this->verify_signature($attestation_message, $att_stmt['sig'], $public_key_raw);
	}

	/**
	 * Verify a signature with a certificate (der) public key.
	 *
	 * @param string $message     Message that was signed.
	 * @param string $signature   Signature bytes.
	 * @param string $certificate Der encoded x509 certificate.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When verification fails.
	 */
	protected function verify_certificate_signature(string $message, string $signature, string $certificate): void {
		$certificate_resource = @openssl_x509_read($certificate);

		//some php / openssl builds cannot read der certificates directly, try the pem wrapper
		if (!$certificate_resource) {
			$pem = "-----BEGIN CERTIFICATE-----\n"
				. chunk_split(base64_encode($certificate), 64, "\n")
				. "-----END CERTIFICATE-----\n";
			$certificate_resource = openssl_x509_read($pem);
		}

		if (!$certificate_resource) {
			throw new InvalidArgumentException('Invalid attestation certificate.');
		}
		$public_key = openssl_pkey_get_public($certificate_resource);
		if (!$public_key) {
			throw new InvalidArgumentException('Unable to read the certificate public key.');
		}
		$result = openssl_verify($message, $signature, $public_key, OPENSSL_ALGO_SHA256);
		if ($result !== 1) {
			throw new InvalidArgumentException('Attestation signature verification failed.');
		}
	}


	/**
	 * Verify an assertion / attestation signature with a COSE public key.
	 *
	 * @param string $message        Message that was signed.
	 * @param string $signature      Signature bytes.
	 * @param string $public_key_raw Raw CBOR encoded COSE public key.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the key is invalid or verification fails.
	 */
	protected function verify_signature(string $message, string $signature, string $public_key_raw): void {

		//decode the cose key (cbor map)
		//only the first cbor value is the key; some authenticators (e.g. chrome)
		//append extension output (e.g. credprotect) after it in the authenticator
		//data, so any trailing bytes after the key are intentionally ignored here.
		$cose_offset = 0;
		$cose_key = self::decode_cbor_value($public_key_raw, $cose_offset);
		if (!is_array($cose_key) || !isset($cose_key[1])) {
			throw new InvalidArgumentException('Invalid COSE public key.');
		}

		$key_type = $cose_key[1];

		switch ($key_type) {
			case 2: //EC2
				$crv = $cose_key[-1] ?? 1;
				if (empty($cose_key[-2]) || empty($cose_key[-3])) {
					throw new InvalidArgumentException('Incomplete EC2 public key.');
				}
				$x = $cose_key[-2];
				$y = $cose_key[-3];

				//build the uncompressed ec point: 0x04 | x | y
				$point = "\x04" . $x . $y;

				//build the subject public key info structure
				if ($crv === 1) {
					//p-256
					$prefix = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";
				} elseif ($crv === 2) {
					//p-384
					$prefix = "\x30\x62\x30\x16\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x05\x2b\x81\x04\x00\x22\x03\x67\x00";
				} else {
					throw new InvalidArgumentException('Unsupported EC2 curve.');
				}

				$der = $prefix . $point;
				$pem = "-----BEGIN PUBLIC KEY-----\n"
					. chunk_split(base64_encode($der), 64, "\n")
					. "-----END PUBLIC KEY-----\n";

				$result = openssl_verify($message, $signature, $pem, OPENSSL_ALGO_SHA256);
				if ($result !== 1) {
					throw new InvalidArgumentException('Signature verification failed.');
				}
				break;

			case 3: //RSA
				//cose rsa key: -1 = e (exponent), -2 = n (modulus)
				if (empty($cose_key[-1]) || empty($cose_key[-2])) {
					throw new InvalidArgumentException('Incomplete RSA public key.');
				}
				$modulus = $cose_key[-2];
				$exponent = $cose_key[-1];

				//build the rsa public key der structure (subject public key info)
				$modulus_der = $this->der_integer($modulus);
				$exponent_der = $this->der_integer($exponent);
				$rsa_public_key = "\x30" . $this->der_length(strlen($modulus_der) + strlen($exponent_der)) . $modulus_der . $exponent_der;
				//algorithm identifier: rsaEncryption (1.2.840.113549.1.1.1) + null
				$algorithm = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
				$bit_string = "\x03" . $this->der_length(strlen($rsa_public_key) + 1) . "\x00" . $rsa_public_key;
				$sequence_body = $algorithm . $bit_string;
				$der = "\x30" . $this->der_length(strlen($sequence_body)) . $sequence_body;

				$pem = "-----BEGIN PUBLIC KEY-----\n"
					. chunk_split(base64_encode($der), 64, "\n")
					. "-----END PUBLIC KEY-----\n";

				$result = openssl_verify($message, $signature, $pem, OPENSSL_ALGO_SHA256);
				if ($result !== 1) {
					throw new InvalidArgumentException('Signature verification failed.');
				}
				break;

			case 1: //OKP (ed25519)
				if (!function_exists('sodium_crypto_sign_verify_detached')) {
					throw new InvalidArgumentException('The sodium extension is required to verify Ed25519 signatures.');
				}
				if (empty($cose_key[-2]) || strlen($cose_key[-2]) !== 32) {
					throw new InvalidArgumentException('Invalid Ed25519 public key.');
				}
				$public_key = $cose_key[-2];
				if (!sodium_crypto_sign_verify_detached($signature, $message, $public_key)) {
					throw new InvalidArgumentException('Signature verification failed.');
				}
				break;

			default:
				throw new InvalidArgumentException('Unsupported COSE key type.');
		}
	}

	/**
	 * Verify a signature, returning a boolean instead of throwing.
	 *
	 * This is used to test multiple candidate assertion messages (see
	 * verify_assertion) where only one of them is expected to match.
	 *
	 * @param string $message         Data that was signed.
	 * @param string $signature       Raw signature bytes.
	 * @param string $public_key_raw  Raw COSE public key bytes.
	 *
	 * @return bool True when the signature verifies.
	 */
	protected function signature_verifies(string $message, string $signature, string $public_key_raw): bool {
		try {
			$this->verify_signature($message, $signature, $public_key_raw);
			return true;
		}
		catch (InvalidArgumentException $error) {
			return false;
		}
	}

	/**
	 * Verify a signature with a raw SPKI / DER (subject public key info) public key.
	 *
	 * Used by the fido-u2f attestation format whose ecdsaKey is an SPKI EC public key
	 * rather than a COSE key. The key is wrapped in a PEM and verified with openssl.
	 *
	 * @param string $message    Data that was signed.
	 * @param string $signature  Raw signature bytes.
	 * @param string $spki_key   Raw SPKI / DER public key bytes.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the key is invalid or verification fails.
	 */
	protected function verify_spki_signature(string $message, string $signature, string $spki_key): void {
		$pem = "-----BEGIN PUBLIC KEY-----\n"
			. chunk_split(base64_encode($spki_key), 64, "\n")
			. "-----END PUBLIC KEY-----\n";

		$result = openssl_verify($message, $signature, $pem, OPENSSL_ALGO_SHA256);
		if ($result !== 1) {
			throw new InvalidArgumentException('Signature verification failed.');
		}
	}

	/**
	 * Minimal deterministic CBOR decoder.
	 *
	 * Supports unsigned / negative integers, byte strings, text strings,
	 * arrays, maps, tags and the simple values used by WebAuthn.
	 *
	 * @param string $data Raw CBOR bytes.
	 *
	 * @return mixed Decoded value.
	 *
	 * @throws InvalidArgumentException When the data is malformed.
	 */
	public static function decode_cbor(string $data) {
		$offset = 0;
		$result = self::decode_cbor_value($data, $offset);
		if ($offset !== strlen($data)) {
			throw new InvalidArgumentException('Trailing bytes after the CBOR value.');
		}
		return $result;
	}

	/**
	 * Decode a single CBOR value starting at the offset (cursor advanced).
	 *
	 * @param string $data   Raw CBOR bytes.
	 * @param int    $offset Offset to start reading from (by reference).
	 *
	 * @return mixed Decoded value.
	 *
	 * @throws InvalidArgumentException When the data is malformed.
	 */
	protected static function decode_cbor_value(string $data, int &$offset) {
		if ($offset >= strlen($data)) {
			throw new InvalidArgumentException('Unexpected end of CBOR data.');
		}

		$initial = ord($data[$offset]);
		$offset++;

		$major_type = ($initial >> 5) & 0x07;
		$info = $initial & 0x1f;

		switch ($info) {
			case 24:
				if ($offset >= strlen($data)) { throw new InvalidArgumentException('Truncated CBOR length.'); }
				$value = ord($data[$offset]);
				$offset++;
				break;
			case 25:
				if ($offset + 2 > strlen($data)) { throw new InvalidArgumentException('Truncated CBOR length.'); }
				$value = unpack('n', substr($data, $offset, 2))[1];
				$offset += 2;
				break;
			case 26:
				if ($offset + 4 > strlen($data)) { throw new InvalidArgumentException('Truncated CBOR length.'); }
				$value = unpack('N', substr($data, $offset, 4))[1];
				$offset += 4;
				break;
			case 27:
				if ($offset + 8 > strlen($data)) { throw new InvalidArgumentException('Truncated CBOR length.'); }
				$value = unpack('J', substr($data, $offset, 8))[1];
				$offset += 8;
				break;
			default:
				$value = $info;
				break;
		}

		switch ($major_type) {
			case 0: //unsigned integer
				return $value;

			case 1: //negative integer
				return -1 - $value;

			case 2: //byte string
				if ($offset + $value > strlen($data)) { throw new InvalidArgumentException('Truncated CBOR byte string.'); }
				$result = substr($data, $offset, $value);
				$offset += $value;
				return $result;

			case 3: //text string
				if ($offset + $value > strlen($data)) { throw new InvalidArgumentException('Truncated CBOR text string.'); }
				$result = substr($data, $offset, $value);
				$offset += $value;
				return $result;

			case 4: //array
				$result = [];
				for ($i = 0; $i < $value; $i++) {
					$result[] = self::decode_cbor_value($data, $offset);
				}
				return $result;

			case 5: //map
				$result = [];
				for ($i = 0; $i < $value; $i++) {
					$key = self::decode_cbor_value($data, $offset);
					$result[$key] = self::decode_cbor_value($data, $offset);
				}
				return $result;

			case 6: //tag, return the tagged value
				return self::decode_cbor_value($data, $offset);

			case 7: //simple values
				if ($value === 20) { return false; }
				if ($value === 21) { return true; }
				if ($value === 22) { return null; }
				throw new InvalidArgumentException('Unsupported CBOR simple value.');

			default:
				throw new InvalidArgumentException('Unsupported CBOR major type.');
		}
	}


	/**
	 * Encode an integer as a DER length field.
	 *
	 * @param int $length Length to encode.
	 *
	 * @return string DER encoded length.
	 */
	protected function der_length(int $length): string {
		if ($length < 0x80) {
			return chr($length);
		}
		$bytes = '';
		while ($length > 0) {
			$bytes = chr($length & 0xff) . $bytes;
			$length >>= 8;
		}
		return chr(0x80 | strlen($bytes)) . $bytes;
	}

	/**
	 * Encode a big endian integer as a DER integer.
	 *
	 * @param string $value Big endian integer bytes.
	 *
	 * @return string DER encoded integer.
	 */
	protected function der_integer(string $value): string {
		$value = ltrim($value, "\x00");
		if ($value === '') {
			$value = "\x00";
		}
		if ((ord($value[0]) & 0x80) !== 0) {
			$value = "\x00" . $value;
		}
		return "\x02" . $this->der_length(strlen($value)) . $value;
	}

	/**
	 * Validate the client data challenge against the stored challenge.
	 *
	 * @param array  $client_data Decoded client data json array.
	 * @param string $challenge   Raw challenge bytes.
	 *
	 * @return bool True when the challenge matches.
	 */
	protected static function challenge_matches(array $client_data, string $challenge): bool {
		if (empty($client_data['challenge'])) {
			return false;
		}
		return hash_equals(self::base64url_encode($challenge), (string)$client_data['challenge']);
	}

	/**
	 * Validate the client data origin against the current request origin.
	 *
	 * @param array $client_data Decoded client data json array.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the origin does not match.
	 */
	protected function verify_origin(array $client_data): void {
		//the cross origin flag must be false
		if (isset($client_data['crossOrigin']) && $client_data['crossOrigin'] !== false) {
			throw new InvalidArgumentException('Cross origin request is not allowed.');
		}

		if (empty($client_data['origin']) || !is_string($client_data['origin'])) {
			throw new InvalidArgumentException('clientDataJSON origin is missing.');
		}

		//build the expected origin from the current request
		$protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
		$port = '';
		if (isset($_SERVER['SERVER_PORT'])) {
			if ($protocol === 'https' && (int)$_SERVER['SERVER_PORT'] !== 443) {
				$port = ':' . $_SERVER['SERVER_PORT'];
			}
			if ($protocol === 'http' && (int)$_SERVER['SERVER_PORT'] !== 80) {
				$port = ':' . $_SERVER['SERVER_PORT'];
			}
		}
		$expected_origin = $protocol . '://' . $host . $port;

		if (!hash_equals($expected_origin, $client_data['origin'])) {
			throw new InvalidArgumentException('clientDataJSON origin mismatch.');
		}
	}

	/**
	 * Encode a string using base64url (no padding).
	 *
	 * @param string $data Data to encode.
	 *
	 * @return string Base64url encoded string.
	 */
	public static function base64url_encode(string $data): string {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * Decode a base64url string (with or without padding).
	 *
	 * @param string $data Base64url encoded data.
	 *
	 * @return string Raw decoded bytes.
	 *
	 * @throws InvalidArgumentException When the data is invalid.
	 */
	public static function base64url_decode(string $data): string {
		$remainder = strlen($data) % 4;
		if ($remainder !== 0) {
			$data .= str_repeat('=', 4 - $remainder);
		}
		$decoded = base64_decode(strtr($data, '-_', '+/'), true);
		if ($decoded === false) {
			throw new InvalidArgumentException('Invalid base64url data.');
		}
		return $decoded;
	}
}


