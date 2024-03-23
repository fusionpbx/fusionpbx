<?php

//define the template class
if (!interface_exists('ai_transcribe')) {
	interface ai_transcribe {
		public function set_path(string $audio_path);
		public function set_filename(string $audio_filename);
		public function transcribe() : string;
	}
}

?>