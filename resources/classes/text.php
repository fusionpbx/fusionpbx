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
	 * @var string $language_code	examples: en-us, es-cl, fr-fr, pt-pt
	 * @var string $app_path		examples: app/exec or core/domains
	 */
	public function get($language_code = null, $app_path = null) {
		//get the app_languages.php
			if (isset($app_path)) {
				require_once glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/".$app_path."/app_{languages}.php",GLOB_BRACE);
			}
			else {
				require_once getcwd().'/app_languages.php';
			}

		//add multi-lingual support
			if ($language_code != 'all') {
				foreach($text as $key => $value) {
					if ($language_code == null) {
						$text[$key] = $value[$_SESSION['domain']['language']['code']];
					}
					else {
						$text[$key] = $value[$language_code];
					}
				}
			}

		//return the array of translations
			return $text;
	}
}

?>