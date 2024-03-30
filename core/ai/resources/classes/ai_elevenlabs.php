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
 * ai_elevenlabs class
 *
 */
class ai_elevenlabs implements ai_speech {

	private $voice;
	private $path;
	private $message;
	private $format;
	private $filename;
	private $languages;
	private $transcribe_key;
	private $speech_key;
	private $model;

	public function __construct($settings) {
		$this->voice = "";
		$this->path = "";
		$this->message = "";
		$this->format = "";
		$this->filename = "";
		//build the setting object and get the recording path
		$this->transcribe_key = $settings->get('ai', 'transcribe_key');
		$this->speech_key = $settings->get('ai', 'speech_key');
	}

	public function set_filename(string $audio_filename) {
		$this->filename = $audio_filename;
	}

	public function set_format(string $audio_format) {
		$this->format = $audio_format;
	}

	public function set_message(string $audio_message) {
		$this->message = $audio_message;
	}

	public function set_path(string $audio_path) {
		$this->path = $audio_path;
	}

	public function set_voice(string $audio_voice) {
		$this->voice = $audio_voice;
	}

	public function speech(): bool {
		$model_id = $this->model;
		$ch = curl_init('https://api.elevenlabs.io/v1/text-to-speech/' . $this->voice);
		$headers = [
			'Content-Type: application/json',
			"xi-api-key: $this->speech_key",
		];
		$encoded_message = json_encode([
			'model_id' => $model_id,
			'text' => $this->message,
			'voice_settings' => [
				'similarity_boost' => 1,
				'stability' => 1,
			],
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_message);
		$response = curl_exec($ch);
		$error = curl_error($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
//		$curl = new curl('https://api.elevenlabs.io/v1/text-to-speech/' . $this->voice);
//		$response = $curl->set_headers($headers)->post($encoded_message);
//		$error = $curl->get_error();
//		$http_code = $curl->get_http_code();
//		if ($curl->get_http_code() == 200) {
		if ($http_code == 200) {
			file_put_contents($this->path . '/' . $this->filename, $response);
			return true;
		}
		return false;
	}

	public function is_language_enabled(): bool {
		return false;
	}

	public function get_languages(): array {
		return ['english' => 'English'];
	}

	public function get_voices(): array {
		$return_value = [];
		$url = 'https://api.elevenlabs.io/v1/voices';
		$headers = [
			'Content-Type: application/json',
			"xi-api-key: $this->speech_key",
		];
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
		]);

		$response = curl_exec($curl);
		$error = curl_error($curl);

		curl_close($curl);
		if (!empty($response)) {
			$json_array = json_decode($response, true);
			foreach($json_array['voices'] as $row) {
				$voice_id = $row['voice_id'];
				$name = $row['name'];
				$gender = $row['labels']['gender'] ?? '';
				$accent = $row['labels']['accent'] ?? '';
				$use_case = $row['labels']['use case'] ?? '';
				$recommended_model = $row['high_quality_base_model_ids'][0] ?? '';
				$return_value[$voice_id] = "$name ($gender, $accent";
				if (!empty($use_case)) {
					$return_value[$voice_id] .= ", " . $use_case;
				}
				$return_value[$voice_id] .= ")";
				if (!empty($recommended_model)) {
					$return_value[$voice_id] .= " - $recommended_model";
				}
			}
		}
		return $return_value;
	}

	public function set_language(string $audio_language) {
		$this->languages = $audio_language;
	}

	public function set_model(string $model): void {
		if (array_key_exists($model, $this->get_models())) {
			$this->model = $model;
		} else {
			throw new \Exception('Model does not exist');
		}
	}

	public function get_models(): array {
		return [
			'eleven_turbo_v1' => 'Eleven Turbo v1',
			'eleven_turbo_v2' => 'Eleven Turbo v2',
			'eleven_multilingual_v1' => 'Eleven Multilingual v1',
			'eleven_multilingual_v2' => 'Eleven Multilingual v2',
		];
	}
}
