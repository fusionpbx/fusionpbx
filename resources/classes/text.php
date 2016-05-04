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
		//place holder
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
	public function get($language_code = null, $app_path = null, $exclude_global = false) {

		//define the text array
			$text = array();

		//get the global app_languages.php
			if (!$exclude_global){
				include $_SERVER["PROJECT_ROOT"]."/resources/app_languages.php";
			}

		//get the app_languages.php
			if ($app_path != null) {
				$lang_path = $_SERVER["PROJECT_ROOT"]."/".$app_path."/app_languages.php";
			}
			else {
				$lang_path = getcwd().'/app_languages.php';
			}
			if(file_exists($lang_path)){
				require $lang_path;
			}

		//get the available languages
			krsort($text);
			foreach ($text as $lang_label => $lang_codes) {
				foreach ($lang_codes as $lang_code => $lang_text) {
					if ($lang_text != '') {
						$app_languages[] = $lang_code;
					}
				}
			}
			$_SESSION['app']['languages'] = array_unique($app_languages);

		//check the session language
			if (isset($_SESSION['domain']) and $language_code == null){
				$language_code = $_SESSION['domain']['language']['code'];
			} elseif ($language_code == null){
				$language_code = 'en-us';
			}

		//reduce to specific language
			if ($language_code != 'all') {
				foreach ($text as $key => $value) {
					if (strlen($value[$language_code]) > 0) {
						$text[$key] = $value[$language_code];
					} else {
						//fallback to en-us
						$text[$key] = $value['en-us'];
					}
				}
			}

		//return the array of translations
			return $text;
	}
}

?>