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
	  Portions created by the Initial Developer are Copyright (C) 2008-2018
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  Tim Fry <tim.fry@hotmail.com>
	 */

	/**
	 * Event in FreeSWITCH
	 *
	 * @author tim
	 */
	final class event {

		private $uuid;
		private $header;
		private $headers;
		private $body;
		private $name;
		private $subclass;
		private $command_arg;

		public function __construct(string $uuid, string $header, string $body) {
			$this->uuid = $uuid;
			//$this->header = $header;
			$this->body = $body;
			$this->name = null;
			$this->subclass = null;
			$this->command_arg = null;
			$this->headers = [];
			if (!empty($header)) {
				$this->header($header);
			}
		}

		public function header(?string $header = null) {
			if (func_num_args() > 0) {
				$this->header = $header;
				$this->parse_header();
				$this->set_name();
				$this->set_subclass();
				$this->set_command_arg();
				return $this;
			}
			return $this->header;
		}

		public function body(?string $body = null) {
			if (func_num_args() > 0) {
				$this->body = $body;
				return $this;
			}
			return $this->body;
		}

		public function name(?string $name = null) {
			if (func_num_args() > 0) {
				$this->name = $name;
				return $this;
			}
			return $this->name;
		}

		public function subclass(?string $subclass = null) {
			if (func_num_args() > 0) {
				$this->subclass = $subclass;
				return $this;
			}
			return $this->subclass;
		}

		public function api_command(?string $api_command = null) {
			if (func_num_args() > 0) {
				$this->api_command = $api_command;
				return $this;
			}
			return $this->api_command;
		}

		public function api_command_arg(?string $api_command_arg = null) {
			if (func_num_args() > 0) {
				$this->api_command_arg = $api_command_arg;
				return $this;
			}
			return $this->api_command_arg;
		}

		private function add_header($key, $value) {
			if (!empty($key)) {
				$this->headers[$key] = $value;
			}
		}

		private function parse_header() {
			$lines = explode("\n", $this->header);
			foreach ($lines as $line) {
				$kvp = array_map('trim', explode(":", $line, 2));
				$this->add_header($kvp[0] ?? '', $kvp[1] ?? '');
			}
		}

		private function set_name() {
			if (!empty($this->headers['Event-Name'])) {
				$this->name = $this->headers['Event-Name'];
			}
			//if name is API then override with the command name
			if (!empty($this->headers['API-Command'])) {
				$this->name = $this->headers['API-Command'];
			}
		}

		private function set_subclass() {
			if (!empty($this->headers['Event-Subclass'])) {
				$this->subclass = $this->headers['Event-Subclass'];
			}
		}

		private function set_command_arg() {
			if (!empty($this->headers['API-Command-Argument'])) {
				$this->command_arg = $this->headers['API-Command-Argument'];
			}
		}
	}
