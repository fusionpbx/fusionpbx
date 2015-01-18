<?php

/**
 * Get the text for the correct translation
 * 
 * @method array get
 */
class text {

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//get the list of languages that are available
		if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/translate")) {
			include("app/translate/translate_include.php");
		}
	}

	/**
	 * Called when there are no references to a particular object
	 * unset the variables used in the class
	 */
	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}

	/**
	 * Get a specific item from the cache
	 * @var string $path		examples: app/exec or core/domains
	 */
	public function get($path) {
		//get the app_languages.php
			if (strlen($path) > 0) {
				require_once glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/".$path."/app_{languages}.php",GLOB_BRACE);
			}
			else {
				require_once getcwd().'/app_languages.php';
			}

		//add multi-lingual support
			foreach($text as $key => $value) {
				$text[$key] = $value[$_SESSION['domain']['language']['code']];
			}

		//return the array of translations
			return $text;

	}

}

?>