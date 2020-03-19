<?php

/**
 * Get the text for the correct translation
 *
 * @method array get
 */
class text {
	public $languages;
	public $legacy_map = array(
		'he' => 'he-il',
		'pl' => 'pl-pl',
		'uk' => 'uk-ua',
		'ro' => 'ro-ro',
		'he-il' => 'he',
		'pl-pl' => 'pl',
		'uk-ua' => 'uk',
		'ro-ro' => 'ro',
		//we use the following to indicate which is the preferred
		'de' => 'de-de',
		'es' => 'es-cl',
		'fr' => 'fr-fr',
		'pt' => 'pt-pt',
	);

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//define the text array
			$text = array();

		//get the global app_languages.php so we can get the list of languages
			if (file_exists($_SERVER["PROJECT_ROOT"]."/resources/app_languages.php")) {
				include $_SERVER["PROJECT_ROOT"]."/resources/app_languages.php";
			}

		//get the list of languages, remove en-us, sort it then put en-us in front
			unset($text['language-name']['en-us']);
			if (is_array($text['language-name'])) {
				$languages = array_keys($text['language-name']);
				asort($languages);
				array_unshift($languages, 'en-us');
			}

		//support legacy variable
			if (is_array($languages)) {
				$_SESSION['app']['languages'] = $languages;
				$this->languages = $languages;
			}
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
				require $_SERVER["PROJECT_ROOT"]."/resources/app_languages.php";
			}

		//get the app_languages.php
			if ($app_path != null) {
				$lang_path = $_SERVER["PROJECT_ROOT"]."/".$app_path;
			}
			else {
				$lang_path = getcwd();
			}
			if (file_exists("${lang_path}/app_languages.php")) {
				if ($lang_path != 'resources' or $exclude_global) {
					include "${lang_path}/app_languages.php";
				}
			}
			//else {
			//	throw new Exception("could not find app_languages for '$app_path'");
			//}

		//check the session language
			if (isset($_SESSION['domain']) and $language_code == null) {
				$language_code = $_SESSION['domain']['language']['code'];
			}
			elseif ($language_code == null) {
				$language_code = 'en-us';
			}

		//check the language code
			if (strlen($language_code) == 2) {
				if (array_key_exists($language_code, $this->legacy_map)) {
					$language_code = $this->legacy_map[$language_code];
				}
			}

		//reduce to specific language
			if ($language_code != 'all') {
				if (is_array($text)) {
					foreach ($text as $key => $value) {
						if (isset($value[$language_code]) && strlen($value[$language_code]) > 0) {
							$text[$key] = $value[$language_code];
						}
						else {
							//fallback to en-us
							$text[$key] = $value['en-us'];
						}
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
			$lang_path = $_SERVER["PROJECT_ROOT"]."/$app_path/app_languages.php";
			if (!file_exists($lang_path)) {
				throw new Exception("could not find app_languages for '$app_path'");
			}
			require $lang_path;

			if (!is_array($text)) {
				throw new Exception("failed to import text data from '$app_path'");
			}

		//collect existing comments
			$comment = array();
			$file_handle = fopen($lang_path, "r");
			while (!feof($file_handle)) {
				if(preg_match('/\$text\[[\'"](.+)[\'"]\]\[[\'"](.+)[\'"]]\s+=\s+[\'"].*[\'"];\s+\/\/(.+)/', fgets($file_handle), $matches)){
					$comment[$matches[0]][$matches[1]] = $matches[2];
				}
			}
			fclose($file_handle);

		//open the language file for writing
			$lang_file = fopen($lang_path, 'w');
			date_default_timezone_set('UTC');
			fwrite($lang_file, "<?php\n#This file was last reorganized on " . date("jS \of F Y h:i:s A e") . "\n");
			if (!$no_sort) {
				if ($app_path == 'resources') {
					$temp_A['language-name'] = $text['language-name'];
					unset($text['language-name']);
					foreach($this->languages as $language) {
						$temp_B["language-$language"] = $text["language-$language"];
						unset($text["language-$language"]);
					}
					$temp_C["language-en-us"] = $temp_B["language-en-us"];
					unset($temp_B["language-en-us"]);
					ksort($temp_B);
					$temp_B = array_merge($temp_C, $temp_B);
					ksort($text);
					$text = array_merge($temp_A, $temp_B, $text);
					unset($temp_A, $temp_B, $temp_C);
				}
				else {
					ksort($text);
				}
			}
			else {
				if ($app_path == 'resources') {
					foreach($this->languages as $language) {
						$label = array_shift($text["language-$language"]);
						if (strlen($label) == 0)
							$label = $language;
						$text["language-$language"]['en-us'] = $label;
					}
				}
			}
			$last_lang_label = "";
			foreach ($text as $lang_label => $lang_codes) {

				//behave differently if we are one of the special language-* tags
					if (preg_match('/\Alanguage-(\w{2}|\w{2}-\w{2})\z/', $lang_label, $lang_code)) {
						if ($lang_label == 'language-en-us')
							fwrite($lang_file, "\n");
						$target_lang = $lang_code[1];
						if (strlen($target_lang) == 2) {
							if (array_key_exists($target_lang, $this->legacy_map)) {
								$target_lang = $this->legacy_map[$target_lang];
							}
						}
						$spacer = "";
						if (strlen($target_lang) == 11)
							$spacer = "   ";
						$language_name = $this->escape_str(array_shift($text[$lang_label]));
						if (strlen($language_name) == 0)
							$language_name = $this->escape_str($target_lang);
						fwrite($lang_file, "\$text['language-$target_lang'$spacer]['en-us'] = \"$language_name\";\n");
					}
					else {

						//put a line break in between the last tag if it has changed
							if ($last_lang_label != $lang_label)
								fwrite($lang_file, "\n");
								foreach ($this->languages as $lang_code) {
									$value = "";
									$append = "";
									$spacer = "";
									$target_lang = $lang_code;
									if (strlen($lang_code) == 2) {
										if (array_key_exists($lang_code, $this->legacy_map)) {
											$target_lang = $this->legacy_map[$lang_code];
										}
									}
									if (strlen($target_lang) == 2)
										$spacer = "   ";
									if (array_key_exists($lang_code, $text[$lang_label]))
										$value = $text[$lang_label][$lang_code];
									if (strlen($value) == 0 and array_key_exists($target_lang, $this->legacy_map)) {
										$value = $text[$lang_label][$this->legacy_map[$target_lang]];
									}
									$base_code = substr($target_lang, 0, 2);
									if (strlen($value) > 0
										and array_key_exists($base_code, $this->legacy_map )
										and $this->legacy_map[$base_code] != $target_lang
										and $value == $text[$lang_label][$this->legacy_map[$base_code]]
									) {
										$append = " //copied from ".$this->legacy_map[$base_code];
									}
									if (strlen($value) == 0) {
										foreach($this->languages as $lang_code) {
											if (substr($lang_code, 0, 2) == $base_code and strlen($text[$lang_label][$lang_code]) > 0) {
												$value = $text[$lang_label][$lang_code];
												$append = " //copied from $lang_code";
												continue;
											}
										}
									}
									if(strlen($append) == 0 && array_key_exists($comment, $lang_label) && array_key_exists($comment[$lang_label], $lang_code)) {
										$append = " //$comment[$lang_label][$lang_code]";
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

	public function detect_all_languages($no_sort = false) {

		//clear $text ready for the import
			$text = array();
			$languages = array();

		//retrieve all the languages
			$files = glob($_SERVER["PROJECT_ROOT"] . "/*/*/app_languages.php");
			foreach($files as $file) {
				include $file;
			}
			include $_SERVER["PROJECT_ROOT"] . "/resources/app_languages.php";

		//check every tag
			foreach($text as $lang_codes) {
				foreach($lang_codes as $language_code => $value) {
					if (strlen($language_code) == 2) {
						if (array_key_exists($language_code, $this->legacy_map)) {
							$language_code = $this->legacy_map[$language_code];
						}
					}
					$languages[$language_code] = 1;
				}
			}

		//set $this->languages up according to what we found
			unset($languages['en-us']);
			$languages = array_keys($languages);
			asort($languages);
			array_unshift($languages, 'en-us');

		//support legacy variable
			$_SESSION['app']['languages'] = $languages;
			$this->languages = $languages;

		//rewrite resources/app_languges
			$this->organize_language('resources', $no_sort);
	}

	public function language_totals() {

		//setup variables
			$language_totals = array();
			$language_totals['languages']['total'] = 0;
			$language_totals['menu_items']['total'] = 0;
			$language_totals['app_descriptions']['total'] = 0;
			foreach ($this->languages as $language_code) {
				$language_totals[$language_code] = 0;
			}

		//retrieve all the languages
			$text = array();
			$files = glob($_SERVER["PROJECT_ROOT"] . "/*/*/app_languages.php");
			foreach($files as $file) {
				include $file;
			}
			include $_SERVER["PROJECT_ROOT"] . "/resources/app_languages.php";

		//check every tag
			foreach($text as $label_name => $values) {
				$language_totals['languages']['total']++;
				foreach ($this->languages as $language_code) {
					if (strlen($values[$language_code]) > 0)
						$language_totals['languages'][$language_code]++;
				}
			}
			unset($text);

		//retrieve all the menus
			$x = 0;
			$files = glob($_SERVER["PROJECT_ROOT"] . "/*/*");
			foreach($files as $file) {
				if (file_exists($file . "/app_menu.php"))
					include $file . "/app_menu.php";
				if (file_exists($file . "/app_config.php"))
					include $file . "/app_config.php";
				$x++;
			}
		
		//check every tag
			foreach($apps as $app) {
				$language_totals['app_descriptions']['total']++;
				foreach($app['menu'] as $menu_item) {
					$language_totals['menu_items']['total']++;
					foreach ($this->languages as $language_code) {
						if (strlen($menu_item['title'][$language_code]) > 0)
							$language_totals['menu_items'][$language_code]++;
					}
				}
				foreach ($this->languages as $language_code) {
					if (strlen($app['description'][$language_code]) > 0) {
						$language_totals['app_descriptions'][$language_code]++;
					}
				}
			}
			
			return $language_totals;
	}

	private function escape_str($string = '') {
		//perform initial escape
			$string = addslashes(stripslashes($string));
		//swap \' as we don't need to escape those
			return preg_replace("/\\\'/", "'", $string);
		//escape " as we write our strings double quoted
			return preg_replace("/\"/", '\"', $string);
	}
}

?>
