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
	public function get($language_code = null, $app_path = null) {
		//get the global app_languages.php
			include $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/core/app_languages.php";
		//get the app_languages.php
			if ($app_path != null) {
				include $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/".$app_path."/app_languages.php";
			}
			else {
				include getcwd().'/app_languages.php';
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
			if(isset($_SESSION['domain'])){
				$language_code = $_SESSION['domain']['language']['code'];
			}elseif($language_code == null){
				$language_code = 'en-us';
			}
		//reduce to specific language
			if ($language_code != 'all') {
				foreach($text as $key => $value) {
					$text[$key] = $value[$language_code];
				}
			}
			if ($language_code != 'all') {
				foreach($language_name as $code => $value) {
					$text["language-$code"] = $value;
				}
			}else{
				foreach($language_name as $code => $value) {
					foreach($language_name as $c_code => $value) {
						$text["language-$code"][$c_code] = $value;
					}
				}
			}
			
		//return the array of translations
			return $text;
	}
}
?>