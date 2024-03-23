<?php

 /**
 * ai class
 *
 * @method null download
 */
if (!class_exists('ai_openai')) {
	class ai_openai implements ai_speech, ai_transcribe {

		/**
		 * declare private variables
		 */
		private $transcribe_key;
		private $speech_key;
		private $path;
		private $filename;
		private $format;
		private $voice;
		private $message;

		/**
		 * called when the object is created
		 */
		public function __construct($setting) {
			//make the setting object
			if (!$setting) {
				$setting = new settings();
			}

			//build the setting object and get the recording path
			$this->transcribe_key = $setting->get('audio', 'transcribe_key');
			$this->speech_key = $setting->get('audio', 'speech_key');

		}

		public function set_path(string $audio_path) {
			$this->path = $audio_path;
		}

		public function set_filename(string $audio_filename) {
			$this->filename = $audio_filename;
		}

		public function set_format(string $audio_format) {
			$this->format = $audio_format;
		}

		public function set_voice(string $audio_voice) {
			$this->voice = $audio_voice;
		}

		public function set_message(string $audio_message) {
			$this->message = $audio_message;
		}

		/**
		 * speech - text to speech
		 */
		public function speech() : bool {

			// set the request URL
			$url = 'https://api.openai.com/v1/audio/speech';

			// set the request headers
			$headers = [
				'Authorization: Bearer ' . $this->speech_key,
				'Content-Type: application/json'
			];

			// Set the request data format, wav, mp3, opus
			$data = [
				'model' => 'tts-1-hd',
				'input' => $this->message,
				'voice' => $this->voice,
				'response_format' => 'wav'
			];

			// initialize curl handle
			$ch = curl_init($url);

			// set the curl options
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			// run the curl request and get the response
			$response = curl_exec($ch);

			// close the handle
			curl_close($ch);

			// check for errors
			if ($response === false) {
				return false;
			}
			else {
				// save the audio file
				file_put_contents($this->path.'/'.$this->filename, $response);
				return true;
			}

		}

		/**
		 * transcribe - speech to text
		 */
		public function transcribe() : string {
			// initialize a curl handle
			$ch = curl_init();

			// set the URL for the request
			curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');

			// set the request method to POST
			curl_setopt($ch, CURLOPT_POST, true);

			// set the request headers
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer '.$this->transcribe_key,
				'Content-Type: multipart/form-data'
			));

			// set the POST data
			$post_data = array(
				'file' => new CURLFile($this->path.'/'.$this->filename),
				'model' => 'whisper-1',
				'response_format' => 'text'
			);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

			// return the response as a string instead of outputting it directly
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// run the curl request and transcription message
			$this->message = curl_exec($ch);

			// check for errors
			if (curl_errno($ch)) {
				echo 'Error: ' . curl_error($ch);
				exit;
			}

			// close the handle
			curl_close($ch);

			// return the transcription
			if (empty($this->message)) {
				return '';
			}
			else {
				return trim($this->message);
			}
		}

	}
}

?>