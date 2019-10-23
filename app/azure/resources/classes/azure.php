<?php

class azure{

  public static $formats = array (
    'English-Zira' => 
      array (
        'lang' => 'en-US',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (en-US, ZiraRUS)'
      ),
    'English-Jessa' => 
      array (
        'lang' => 'en-US',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (en-US, JessaRUS)'
      ),
    'English-Benjamin' => 
      array (
        'lang' => 'en-US',
        'gender' => 'Male',
        'name' => 'Microsoft Server Speech Text to Speech Voice (en-US, BenjaminRUS)'
      ),
    'British-Susan' => 
      array (
        'lang' => 'en-GB',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (en-GB, Susan, Apollo)'
      ),
    'British-Hazel' => 
      array (
        'lang' => 'en-GB',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (en-GB, HazelRUS)'
      ),
    'British-George' => 
      array (
        'lang' => 'en-GB',
        'gender' => 'Male',
        'name' => 'Microsoft Server Speech Text to Speech Voice (en-GB, George, Apollo)'
      ),
    'Australian-Catherine' => 
      array (
        'lang' => 'en-AU',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (en-AU, Catherine)'
      ),
    'Spanish-Helena' => 
      array (
        'lang' => 'es-ES',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (es-ES, HelenaRUS)'
      ),
    'Spanish-Laura' => 
      array (
        'lang' => 'es-ES',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (es-ES, Laura, Apollo)'
      ),
    'Spanish-Pablo' => 
      array (
        'lang' => 'es-ES',
        'gender' => 'Male',
        'name' => 'Microsoft Server Speech Text to Speech Voice (es-ES, Pablo, Apollo)'
      ),
    'French-Julie' => 
      array (
        'lang' => 'fr-FR',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (fr-FR, Julie, Apollo)'
      ),
    'French-Hortense' => 
      array (
        'lang' => 'fr-FR',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (fr-FR, HortenseRUS)'
      ),
    'French-Paul' => 
      array (
        'lang' => 'fr-FR',
        'gender' => 'Male',
        'name' => 'Microsoft Server Speech Text to Speech Voice (fr-FR, Paul, Apollo)'
      ),
    'German-Hedda' => 
      array (
        'lang' => 'de-DE',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (de-DE, Hedda)'
      ),
    'Russian-Irina' => 
      array (
        'lang' => 'ru-RU',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (ru-RU, Irina, Apollo)'
      ),
    'Russian-Pavel' => 
      array (
        'lang' => 'ru-RU',
        'gender' => 'Male',
        'name' => 'Microsoft Server Speech Text to Speech Voice (ru-RU, Pavel, Apollo)'
      ),
    'Chinese-Huihui' => 
      array (
        'lang' => 'zh-CN',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (zh-CN, HuihuiRUS)'
      ),
    'Chinese-Yaoyao' => 
      array (
        'lang' => 'zh-CN',
        'gender' => 'Female',
        'name' => 'Microsoft Server Speech Text to Speech Voice (zh-CN, Yaoyao, Apollo)'
      ),
    'Chinese-Kangkang' => 
      array (
        'lang' => 'zh-CN',
        'gender' => 'Male',
        'name' => 'Microsoft Server Speech Text to Speech Voice (zh-CN, Kangkang, Apollo)'
      )
    );

    private static function getTokenUrl(){
        return "https://api.cognitive.microsoft.com/sts/v1.0/issueToken";
    }

    private static function getApiUrl(){
        return "https://speech.platform.bing.com/synthesize";
    }

    private static function getSubscriptionKey(){
        return $_SESSION['azure']['key']['text'];
    }

    private static function _getToken(){
        $url = self::getTokenUrl();
        $subscriptionKey = self::getSubscriptionKey();

        $headers = array();
        $headers[] = 'Ocp-Apim-Subscription-Key: '. $subscriptionKey;
        $headers[] = 'Content-Length: 0';

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        //curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 60 );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 300 );
        curl_setopt ( $ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
        
        $response = curl_exec($ch);

        curl_close($ch);
        return $response;
    }

    public static function synthesize($data,$formate_key){

        $lang = self::$formats[$formate_key]['lang'];
        $gender = self::$formats[$formate_key]['gender'];
        $name = self::$formats[$formate_key]['name'];
        $token = self::_getToken();

        $url = self::getApiUrl();
        
        $headers = array();
        $headers[] = 'Authorization: Bearer '. $token;
        $headers[] = 'Content-Type: application/ssml+xml';
        $headers[] = 'X-Microsoft-OutputFormat: riff-16khz-16bit-mono-pcm';
        $headers[] = 'User-Agent: TTS';

        $xml_post_string = "<speak version='1.0' xml:lang='".$lang."'>
        <voice xml:lang='".$lang."' xml:gender='".$gender."' name='".$name."'>";
        $xml_post_string .= $data;
        $xml_post_string .= "</voice>
        </speak>";

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        //curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 60 );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 300 );
        curl_setopt ( $ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
        
        $response = curl_exec($ch);
        $filename = "tts_".time().".wav";
        file_put_contents("/var/www/html/fusionpbx/app/voiplyrecording/tts_record/".$filename, $response);

        curl_close($ch);
        return $filename;  
    }
}
