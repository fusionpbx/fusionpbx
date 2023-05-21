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
	  Portions created by the Initial Developer are Copyright (C) 2018 - 2019
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  Tim Fry <tim@voipstratus.com>
	 */

	/**
	 * UUID Class
	 * <p>The uuid class provides an already validated uuid object. This can greatly
	 * reduce the requirement for checking a uuid each time as a valid object is
	 * guaranteed to have a valid UUID.</p>
	 *
	 * <h1>Example 1 - standard constructor:</h1>
	 * <p><i>$ex1_uuid = new uuid();</i></p>
	 * 
	 * <h1>Example 2 - static method call:</h1>
	 * <p><i>$ex2_uuid = uuid::new();</i></p>
	 *
	 * <h1>Example 3 - validate a proposed uuid:</h1>
	 * <p><i>$ex3_uuid = new uuid('a24d4d25-4f3a-3c10-1333-12a560bef91a');</i></p>
	 * <h1>Example 4 - building on previous examples we can echo the uuid contained in the object</h1>
	 * <p>
	 *    <i>echo "Current valid uuid: $ex1_uuid\n";</i>
	 *    <i>echo "Current valid uuid: $ex2_uuid\n";</i>
	 *    <i>echo "Current valid uuid: $ex3_uuid\n";</i>
	 * </p>
	 * <p><b>NOTE:</b><br>
	 * When combined with typed function parameters it makes a fail-safe way to handle UUIDs.</p>
	 * @author Tim Fry <tim@voipstratus.com>
	 */
	final class uuid implements \Stringable {

		/**
		 * @var const UUID regular expression
		 */
		const UUID_REGEX = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';

		/**
		 *
		 * @var string internal tracking of the uuid
		 */
		private $uuid;

		/**
		 * Creates a new uuid using installed operating system packages
		 * @param string $uuid
		 * @throws InvalidArgumentException Thrown when the string provided to the constructor is not a valid UUID.
		 * @throws \Throwable
		 */
		public function __construct(string $uuid = null) {
			//if a uuid is passed then immediately check if it is valid
			if($uuid !== null) {
				if(!self::is_valid($uuid)) {
					throw new InvalidArgumentException('The uuid string must be a valid UUID type');
				}
			} else {
				//otherwise create a new one
				try {
					$uuid = self::generate();
				} catch (\Throwable $ex) {
					//catch any exception the uuid creation methods throw
					//and then rethrow them so the caller can decide what to do
					throw $ex;
				}
			}
			//all sanity checks have passed so it must be valid so store it
			$this->uuid = $uuid;
		}


		/**
		 * String representation of <i>$this</i> object.
		 * @return string valid UUID string
		 */
		public function __toString(): string {
			return $this->uuid;
		}

		/**
		 * Returns a UUID in a string form.
		 * <p>The generate method will return a valid UUID represented as a string
		 * using the current operating system helper functions. The operating system
		 * is required to have specific packages installed:<br>
		 *     <b>FreeBSD:</b>
		 *       - Requires package <i>ossp-uuid</i>.<br>
		 *     <b>Linux:</b>
		 *       - Requires package <i>uuidgen</i>.<br>
		 *     <b>Windows:</b>
		 *       - Requires the function <i>com_create_guid</i> to be available.<br></p>
		 * <p>The operating system is detected using the PHP constand <i>PHP_OS</i>. The first
		 * Three letters are then extracted and converted to lower case to test for known
		 * operating systems. If the operating system cannot be determined from the lowercase
		 * first three letters an Exception is thrown.</p>
		 *
		 * @return string valid UUID
		 * @throws Exception An exception is thrown when the operating system can not be detected.
		 */
		public static function generate(): string {
			$uuid = "";
			$os = strtolower(substr(PHP_OS, 0, 3));
			switch($os) {
				case 'fre':
				case 'lin':
				case 'win':
					$uuid = self::$os();
					break;
				default:
					throw new Exception('Unable to detect operating system');
			}
			return $uuid;
		}

		/**
		 * Returns a newly constructed uuid object containing a valid uuid string.
		 * @return uuid
		 * @see uuid::__construct()
		 */
		public static function new(): uuid {
			return new uuid();
		}

		/**
		 * Checks if a uuid is valid using a regular expression
		 * @param string $uuid
		 * @return bool true if it conforms to an uuid standard
		 * @see self::UUID_REGEX
		 */
		public static function is_valid(string $uuid): bool {
			return preg_match(self::UUID_REGEX, $uuid);
		}

		/**
		 * Executes a shell function to create a UUID.
		 * @return string
		 * @throws Exception
		 */
		private static function fre(): string {
			$uuid = trim(shell_exec("uuid -v 4"));
			if (!self::is_valid($uuid)) {
				throw new Exception("Please install ossp-uuid package.\n");
			}
			return $uuid;
		}
		
		/**
		 * Executes a shell function to create a UUID.
		 * @return string new UUID
		 * @throws Exception
		 */
		private static function lin(): string {
			$uuid = trim(file_get_contents('/proc/sys/kernel/random/uuid'));
			if (!self::is_valid($uuid)) {
				$uuid = trim(shell_exec("uuidgen"));
				if (!self::is_valid($uuid)) {
					throw new Exception("Please install the uuidgen.\n");
				}
			}
			return $uuid;
		}

		/**
		 * Executes a function call to create a UUID.
		 * @return string new UUID
		 * @throws Exception
		 */
		private static function win(): string {
			$uuid = "";
			if(function_exists('com_create_guid')) {
				$uuid = trim(com_create_guid(), '{}');
				if (!self::is_valid($uuid)) {
					throw new Exception("The com_create_guid() function failed to create a uuid.\n");
				}
			}
			return $uuid;
		}

	}
