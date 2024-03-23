<?php

/**
 * audio class
 *
 * @method null download
 */
if (!class_exists('ai')) {
	class ai {

		/**
		 * declare private variables
		 */
		private $transcribe_key;
		private $speech_key;

		/** @var string $engine */
		private $transcribe_engine;
		private $speech_engine;

		/** @var template_engine $object */
		private $transcribe_object;
		private $speech_object;

		private $setting;

		public $audio_path;
		public $audio_filename;
		public $audio_format;
		public $audio_voice;
		public $audio_message;

		/**
		 * called when the object is created
		 */
		public function __construct(settings $setting = null) {
			//make the setting object
			if ($setting === null) {
				$setting = new settings();
			}

			$this->setting = $setting;

			//build the setting object and get the recording path
			$this->transcribe_key = $setting->get('audio', 'transcribe_key');
			$this->transcribe_engine = $setting->get('audio', 'transcribe_engine');
			$this->speech_key = $setting->get('audio', 'speech_key');
			$this->speech_engine = $setting->get('audio', 'speech_engine');
		}

		/**
		 * speech - text to speech
		 */
		public function speech() {
			if (!empty($this->speech_engine)) {
				//set the class interface to use the _template suffix
				$classname = 'audio_'.$this->speech_engine;

				//load the class
				//require_once $classname . '.php';

				//create the object
				$object = new $classname($this->setting);

				//ensure the class has implemented the audio_interface interface
				if ($object instanceof audio_interface) {
					$object->set_path($this->audio_path);
					$object->set_filename($this->audio_filename);
					$object->set_format($this->audio_format);
					$object->set_voice($this->audio_voice);
					$object->set_message($this->audio_message);
					$object->speech();
				}
				else {
					return false;
				}
			}
		}

		/**
		 * transcribe - speech to text
		 */
		public function transcribe() : string {

			if (!empty($this->transcribe_engine)) {
				//set the class interface to use the _template suffix
				$classname = 'audio_'.$this->transcribe_engine;

				//load the class
				//require_once $classname . '.php';

				//create the object
				$object = new $classname($this->setting);
				//ensure the class has implemented the audio_interface interface
				if ($object instanceof audio_interface) {
					$object->set_path($this->audio_path);
					$object->set_filename($this->audio_filename);
					return $object->transcribe();
				}
				else {
					return '';
				}
			}

		}

	}
}

?>