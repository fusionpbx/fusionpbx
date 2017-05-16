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
	public $languages;
	public $legacy_map = array (
		'he' => 'he-il',
		'pl' => 'pl-pl',
		'uk' => 'uk-ua',
		'he-il' => 'he',
		'pl-pl' => 'pl',
		'uk-ua' => 'uk',
	);
	
	public function __construct() {
		//define the text array
			$text = array();

		//get the global app_languages.php so we can get the list of languages
			include $_SERVER["PROJECT_ROOT"]."/resources/app_languages.php";
		
		//get the list of languages, remove en-us, sort it then put en-us in front
			unset($text['language-name']['en-us']);
			$languages = array_keys($text['language-name']);
			asort($languages);
			array_unshift($languages, 'en-us');
		
		//support legacy variable
			$_SESSION['app']['languages'] = $languages;
			$this->languages = $languages;
			
	}

	/**
	 * Called when there are no references to a particular object
	 * unset the variables used in the class
	 */
	public function __destruct() {
		if (is_array($this)) foreach ($this as $key => $value) {
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
			if (!$exclude_global && file_exists($_SERVER["PROJECT_ROOT"]."/resources/app_languages.php")) {
				include $_SERVER["PROJECT_ROOT"]."/resources/app_languages.php";
			}

		//get the app_languages.php
			if ($app_path != null) {
				$lang_path = $_SERVER["PROJECT_ROOT"]."/".$app_path."/app_languages.php";
			}
			else {
				$lang_path = getcwd().'/app_languages.php';
			}
			if(file_exists($lang_path)) {
				require $lang_path;
			}

		//check the session language
			if (isset($_SESSION['domain']) and $language_code == null) {
				$language_code = $_SESSION['domain']['language']['code'];
			} elseif ($language_code == null){
				$language_code = 'en-us';
			}

		//check the language code
			if(strlen($language_code) == 2) {
				if(array_key_exists($language_code, $this->legacy_map)) {
					$language_code = $this->legacy_map[$language_code];
				}
			}

		//reduce to specific language
			if ($language_code != 'all') {
				if (is_array($text)) foreach ($text as $key => $value) {
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
	
	/**
	 * reorganize an app_languages.php into a consistent format
	 * @var string $app_path		examples: app/exec or core/domains
	 * @var string $no_sort			don't sort the text label order
	 */
	public function organize_language($app_path = null, $no_sort = false) {
	
		//clear $text ready for the import
			$text = array();

		//get the app_languages.php
			if ($app_path == null) {
				throw new Exception("\$app_path must be specified");
			}
			$lang_path = $_SERVER["PROJECT_ROOT"]."/".$app_path."/app_languages.php";
			if(!file_exists($lang_path)){
				throw new Exception("could not find app_languages for '$app_path'");
			}
			require $lang_path;

			if (!is_array($text)) {
				throw new Exception("failed to import text data from '$app_path'");
			}

		//open the language file for writing
			$lang_file = fopen($lang_path, 'w');
			date_default_timezone_set('UTC');
			fwrite($lang_file, "<?php\n#This file was last reorganized on " . date("jS \of F Y h:i:s A e") . "\n");
			if(!$no_sort)
				ksort($text);
			$last_lang_label = "";
			foreach ($text as $lang_label => $lang_codes) {
				
				//behave differently if we are one of the special language-* tags
					if(preg_match('/\Alanguage-(\w{2}|\w{2}-\w{2})\z/', $lang_label, $lang_code)) {
						if($lang_label == 'language-en-us')
							fwrite($lang_file, "\n");
						$target_lang = $lang_code[1];
						if(strlen($target_lang) == 2) {
							if(array_key_exists($target_lang, $this->legacy_map)) {
								$target_lang = $this->legacy_map[$target_lang];
							}
						}
						$spacer = "";
						if(strlen($target_lang) == 11)
							$spacer = "   ";
						fwrite($lang_file, "\$text['language-$target_lang'$spacer]['en-us'] = \"".$this->escape_str(array_shift($text[$lang_label]))."\";\n");
					}else{
					
						//put a line break in between the last tag if it has changed
							if($last_lang_label != $lang_label)
								fwrite($lang_file, "\n");
							foreach ($this->languages as $lang_code) {
								$value = "";
								$append = "";
								$spacer = "";
								$target_lang = $lang_code;
								if(strlen($lang_code) == 2) {
									if(array_key_exists($lang_code, $this->legacy_map)) {
										$target_lang = $this->legacy_map[$lang_code];
									}
								}
								if(strlen($target_lang) == 2)
									$spacer = "   ";
								if(array_key_exists($lang_code, $text[$lang_label]))
									$value = $text[$lang_label][$lang_code];
								if(strlen($value) == 0 and array_key_exists($target_lang, $this->legacy_map)) {
									$value = $text[$lang_label][$this->legacy_map[$target_lang]];
								}
								fwrite($lang_file, "\$text['$lang_label']['$target_lang'$spacer] = \"".$this->escape_str($value)."\";$append\n");
							}
					}
					$last_lang_label = $lang_label;
			}

		//close the language file
			fwrite($lang_file, "\n?>\n");
			fclose($lang_file);
	}

	private function escape_str($string = '') {
		//remove \' otherwise we end up with a double escape
			return preg_replace("/\\\'/", "'", $string);
		//perform initial escape
			$string = addslashes($string);
		//swap \' back otherwise we end up with a double escape
			return preg_replace("/\\\'/", "'", $string);
	}
}

?>
