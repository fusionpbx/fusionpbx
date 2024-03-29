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
 * Description of curl
 *
 * @author tim
 */
class curl {

	private $ch;
	private $response;
	private $error;
	private $http_code;

	public function __construct($url = null, $options = []) {
		$this->ch = curl_init($url);
		curl_setopt_array($this->ch, $options);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	}

	/**
	 * Set an option for a cURL transfer
	 * @param type $option
	 * @param type $value
	 * @return $this
	 */
	public function set_option($option, $value) {
		curl_setopt($this->ch, $option, $value);
		return $this; // Allow chaining
	}

	public function set_url($url) {
		curl_setopt($this->ch, CURLOPT_URL, $url);
		return $this; // Allow chaining
	}

	public function set_headers(array $headers) {
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		return $this; // Allow chaining
	}

	public function get() {
		$this->response = curl_exec($this->ch);
		$this->error = curl_error($this->ch);
		$this->http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		return $this->response;
	}

	public function post(string $data) {
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		return $this->get(); // Reuse get() method for simplicity
	}

	public function get_response() {
		return $this->response;
	}

	public function get_error() {
		return $this->error;
	}

	public function get_http_code() {
		return $this->http_code;
	}

	public function __destruct() {
		curl_close($this->ch);
	}
}
