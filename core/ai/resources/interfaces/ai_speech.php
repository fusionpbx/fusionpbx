<?php

//define the template class
if (!interface_exists('ai_speech')) {
	interface ai_speech {
		public function set_path(string $audio_path);
		public function set_filename(string $audio_filename);
		public function set_format(string $audio_format);
		public function set_voice(string $audio_voice);
		public function set_message(string $audio_message);
		public function speech() : bool;
	}
}

?>