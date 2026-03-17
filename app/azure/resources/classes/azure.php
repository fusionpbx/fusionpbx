<?php

class azure {

	/**
	 * Array of available speech formats.
	 *
	 * This array contains a list of voice names and their corresponding languages
	 * and genders. The keys are the voice names, and the values are arrays containing
	 * the language code and gender.
	 *
	 * @var array
	 */
	public static $formats = [
		'English-Zira' =>
			[
				'lang' => 'en-US',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (en-US, ZiraRUS)',
			],
		'English-Jessa' =>
			[
				'lang' => 'en-US',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (en-US, JessaRUS)',
			],
		'English-Benjamin' =>
			[
				'lang' => 'en-US',
				'gender' => 'Male',
				'name' => 'Microsoft Server Speech Text to Speech Voice (en-US, BenjaminRUS)',
			],
		'British-Susan' =>
			[
				'lang' => 'en-GB',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (en-GB, Susan, Apollo)',
			],
		'British-Hazel' =>
			[
				'lang' => 'en-GB',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (en-GB, HazelRUS)',
			],
		'British-George' =>
			[
				'lang' => 'en-GB',
				'gender' => 'Male',
				'name' => 'Microsoft Server Speech Text to Speech Voice (en-GB, George, Apollo)',
			],
		'Australian-Catherine' =>
			[
				'lang' => 'en-AU',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (en-AU, Catherine)',
			],
		'Spanish-Helena' =>
			[
				'lang' => 'es-ES',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (es-ES, HelenaRUS)',
			],
		'Spanish-Laura' =>
			[
				'lang' => 'es-ES',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (es-ES, Laura, Apollo)',
			],
		'Spanish-Pablo' =>
			[
				'lang' => 'es-ES',
				'gender' => 'Male',
				'name' => 'Microsoft Server Speech Text to Speech Voice (es-ES, Pablo, Apollo)',
			],
		'French-Julie' =>
			[
				'lang' => 'fr-FR',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (fr-FR, Julie, Apollo)',
			],
		'French-Hortense' =>
			[
				'lang' => 'fr-FR',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (fr-FR, HortenseRUS)',
			],
		'French-Paul' =>
			[
				'lang' => 'fr-FR',
				'gender' => 'Male',
				'name' => 'Microsoft Server Speech Text to Speech Voice (fr-FR, Paul, Apollo)',
			],
		'German-Hedda' =>
			[
				'lang' => 'de-DE',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (de-DE, Hedda)',
			],
		'Russian-Irina' =>
			[
				'lang' => 'ru-RU',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (ru-RU, Irina, Apollo)',
			],
		'Russian-Pavel' =>
			[
				'lang' => 'ru-RU',
				'gender' => 'Male',
				'name' => 'Microsoft Server Speech Text to Speech Voice (ru-RU, Pavel, Apollo)',
			],
		'Chinese-Huihui' =>
			[
				'lang' => 'zh-CN',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (zh-CN, HuihuiRUS)',
			],
		'Chinese-Yaoyao' =>
			[
				'lang' => 'zh-CN',
				'gender' => 'Female',
				'name' => 'Microsoft Server Speech Text to Speech Voice (zh-CN, Yaoyao, Apollo)',
			],
		'Chinese-Kangkang' =>
			[
				'lang' => 'zh-CN',
				'gender' => 'Male',
				'name' => 'Microsoft Server Speech Text to Speech Voice (zh-CN, Kangkang, Apollo)',
			],
	];

	/**
	 * Initializes the object with provided setting array.
	 *
	 * @param array $setting_array An optional array of setting values. Defaults to an empty array.
	 */
	public function __construct(array $setting_array = []) {
		//set domain and user UUIDs
		$domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$user_uuid   = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

		//set objects
		$config         = $setting_array['config'] ?? config::load();
		$this->database = $setting_array['database'] ?? database::new(['config' => $config]);
		$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
	}

	/**
	 * Returns the URL for obtaining a token from Microsoft Cognitive Services.
	 *
	 * @return string The URL for issuing a token
	 */
	private static function getTokenUrl() {
		return "https://api.cognitive.microsoft.com/sts/v1.0/issueToken";
	}

	/**
	 * Returns the URL for interacting with Microsoft Bing Speech API.
	 *
	 * @return string The URL for making requests to the Bing Speech API
	 */
	private static function getApiUrl() {
		return "https://speech.platform.bing.com/synthesize";
	}

	/**
	 * Returns the subscription key from Azure settings.
	 *
	 * @param settings $settings The settings object containing Azure configuration
	 *
	 * @return string The subscription key for Azure services
	 */
	private static function getSubscriptionKey(settings $settings) {
		return $settings->get('azure', 'key');
	}

	/**
	 * Obtains a token from Microsoft Cognitive Services.
	 *
	 * @param settings $settings Settings object containing subscription key and other parameters.
	 *
	 * @return string The response from the server, which is expected to be an XML token.
	 */
	private static function _getToken(settings $settings) {
		$url             = self::getTokenUrl();
		$subscriptionKey = self::getSubscriptionKey($settings);

		$headers   = [];
		$headers[] = 'Ocp-Apim-Subscription-Key: ' . $subscriptionKey;
		$headers[] = 'Content-Length: 0';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_SSLVERSION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);

		$response = curl_exec($ch);

		curl_close($ch);
		return $response;
	}

	/**
	 * Synthesizes the given data into a WAV audio file using Microsoft Cognitive Services.
	 *
	 * @param object $settings   Settings for the API call
	 * @param string $data       The text to be synthesized
	 * @param string $format_key The key of the format in self::$formats
	 *
	 * @return string The path to the generated WAV file
	 */
	public static function synthesize(settings $settings, $data, $format_key) {

		$lang   = self::$formats[$format_key]['lang'];
		$gender = self::$formats[$format_key]['gender'];
		$name   = self::$formats[$format_key]['name'];
		$token  = self::_getToken($settings);

		$url = self::getApiUrl();

		$headers   = [];
		$headers[] = 'Authorization: Bearer ' . $token;
		$headers[] = 'Content-Type: application/ssml+xml';
		$headers[] = 'X-Microsoft-OutputFormat: riff-16khz-16bit-mono-pcm';
		$headers[] = 'User-Agent: TTS';

		$xml_post_string = "<speak version='1.0' xml:lang='" . $lang . "'>
        <voice xml:lang='" . $lang . "' xml:gender='" . $gender . "' name='" . $name . "'>";
		$xml_post_string .= $data;
		$xml_post_string .= "</voice>
        </speak>";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_SSLVERSION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);

		$response = curl_exec($ch);
		$filename = "tts_" . time() . ".wav";
		file_put_contents("/var/www/html/fusionpbx/app/voiplyrecording/tts_record/" . $filename, $response);

		curl_close($ch);
		return $filename;
	}
}
