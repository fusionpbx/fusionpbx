<?php

//define the template class
if (!interface_exists('ai_speech')) {
	interface ai_speech {
		public function set_path(string $audio_path);
		public function set_filename(string $audio_filename);
		public function set_format(string $audio_format);
		public function set_voice(string $audio_voice);
		public function set_message(string $audio_message);
		public function get_voices() : array;
		public function is_language_enabled() : bool;
		public function set_language(string $audio_language);
		public function get_languages() : array;
		public function speech() : bool;
	}
}

?>