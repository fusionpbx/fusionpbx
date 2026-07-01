<?php

if (!function_exists('transcribe')) {
	function transcribe ($file_path, $file_name, $file_extension) {
		//check if the file exists
			if (!file_exists($file_path.'/'.$file_name)) {
				echo "file not found ".$file_path.'/'.$file_name;
				exit;
			}

		//get the email queue settings
			$settings = new settings(['category' => 'voicemail']);

		//transcription variables
			$transcribe_provider = $settings->get('voicemail', 'transcribe_provider');
			$transcribe_language = $settings->get('voicemail', 'transcribe_language');

		//transcribe - watson
			if ($transcribe_provider == 'watson') {
				$api_key = $settings->get('voicemail', 'watson_key');
				$api_url = $settings->get('voicemail', 'watson_url');

				if ($file_extension == "mp3") {
					$content_type = 'audio/mp3';
				}
				if ($file_extension == "wav") {
					$content_type = 'audio/wav';
				}

				if (isset($api_key) && $api_key != '') {
					//start output buffer
					ob_start();
					$out = fopen('php://output', 'w');

					//create the curl resource
					$ch = curl_init();

					//set the curl options
					curl_setopt($ch, CURLOPT_URL, $api_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_USERPWD, 'apikey' . ':' . $api_key);
					curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); //set the authentication type
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: '.$content_type]);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER,TRUE);
					curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_path.'/'.$file_name));
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);	//The number of seconds to wait while trying to connect.
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);	//To follow any "Location: " header that the server sends as part of the HTTP header.
					curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);	//To automatically set the Referer: field in requests where it follows a Location: redirect.
					curl_setopt($ch, CURLOPT_TIMEOUT, 300);	//The maximum number of seconds to allow cURL functions to execute.
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);	//To stop cURL from verifying the peer's certificate.
					curl_setopt($ch, CURLOPT_HEADER, 0); //hide the headers when set to 0

					//add verbose for debugging
					curl_setopt($ch, CURLOPT_VERBOSE, true);
					curl_setopt($ch, CURLOPT_STDERR, $out);

					//execute the curl with the options
					$http_content = curl_exec($ch);

					//return the error
					if (curl_errno($ch)) {
						echo 'Error:' . curl_error($ch);
					}

					//close the curl resource
					curl_close($ch);

					//show the debug information
					fclose($out);
					$debug = ob_get_clean();
					echo $debug;

					//$command = "curl -X POST -silent -u \"apikey:".$api_key."\" --header \"Content-type: ".$content_type."\" --data-binary @".$file_path."/".$file_name." \"".$api_url."\"";
					//echo "command: ".$command."\n";

					//ob_start();
					//$result = passthru($command);
					//$json_result = ob_get_contents();
					//ob_end_clean();

					//run the command
					//$http_response = shell_exec($command);
					//echo "http_response:\n".$http_response."\n";

					//remove headers and return the http content
					//$http_response = trim(str_ireplace("HTTP/1.1 100 Continue", "", $http_response));

					//$temp_array = explode("HTTP/1.1 200 OK", $http_response);
					//$http_array = explode("\r\n\r\n", $temp_array[1]);
					//$http_content = trim($http_array[1]);
					echo "http_content:\n".$http_content."\n";

					//validate the json
					$ob = json_decode($http_content);
					if($ob === null) {
						echo "invalid json\n";
						return false;
					}

					$message = '';
					$json = json_decode($http_content, true);
					//echo "json; ".$json."\n";
					foreach($json['results'] as $row) {
						$message .= $row['alternatives'][0]['transcript'];
					}

					$message = str_replace("%HESITATION", " ", trim($message));
					$message = ucfirst($message);
					$array['provider'] = $transcribe_provider;
					$array['language'] = $transcribe_language;
					//$array['command'] = $command;
					$array['message'] = $message;

					return $array;
				}
			}

		//transcribe - google
			if ($transcribe_provider == 'google') {
				$api_key = $settings->get('voicemail', 'google_key');
				$api_url = $settings->get('voicemail', 'google_url');
				$application_credentials = $settings->get('voicemail', 'google_application_credentials');
				$transcribe_language =  $settings->get('voicemail', 'transcribe_language');
				$transcribe_alternate_language = $settings->get('voicemail', 'transcribe_alternate_language');

				if (!isset($transcribe_language) && empty($transcribe_language)) {
					$transcribe_language = 'en-US';
				}
				if (!isset($transcribe_alternate_language) && empty($transcribe_alternate_language)) {
					$transcribe_alternate_language = 'es-US';
				}

				$full_file_path = $file_path.'/'.$file_name;

				//version 1
				if (substr($api_url, 0, 32) == 'https://speech.googleapis.com/v1') {
					if (isset($api_key) && $api_key != '') {
						$flac_path = $file_path.'/'.$file_name.'.flac';

						$sox_cmd = escapeshellarg('sox').' '.escapeshellarg($full_file_path).' '.escapeshellarg($flac_path).' trim 0 00:59';
						exec($sox_cmd, $sox_output, $sox_return);
						if ($sox_return !== 0) {
							echo "sox conversion failed\n";
							return false;
						}

						$audio_content = file_get_contents($flac_path);
						if ($audio_content === false) {
							echo "failed to read flac file\n";
							return false;
						}
						$audio_base64 = base64_encode($audio_content);
						unlink($flac_path);

						$json_request = json_encode([
							'config' => [
								'languageCode' => $transcribe_language,
								'enableWordTimeOffsets' => false,
								'enableAutomaticPunctuation' => true,
								'alternativeLanguageCodes' => $transcribe_alternate_language
							],
							'audio' => [
								'content' => $audio_base64
							]
						]);

						$ch = curl_init();
						curl_setopt_array($ch, [
							CURLOPT_URL => $api_url.':recognize?key='.$api_key,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_POST => true,
							CURLOPT_POSTFIELDS => $json_request,
							CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
							CURLOPT_CONNECTTIMEOUT => 20,
							CURLOPT_TIMEOUT => 300,
							CURLOPT_SSL_VERIFYPEER => true
						]);
						$http_response = curl_exec($ch);
						if (curl_errno($ch)) {
							echo 'Error:' . curl_error($ch);
						}
						curl_close($ch);
					}
				}
				//version 2
				elseif (substr($api_url, 0, 32) == 'https://speech.googleapis.com/v2') {
					$audio_content = file_get_contents($full_file_path);
					if ($audio_content === false) {
						echo "failed to read audio file\n";
						return false;
					}
					$audio_base64 = base64_encode($audio_content);

					$json_request = json_encode([
						'config' => [
							'auto_decoding_config' => new stdClass(),
							'language_codes' => [$transcribe_language],
							'model' => 'long'
						],
						'content' => $audio_base64
					]);

					$ch = curl_init();
					$headers = ['Content-Type: application/json'];

					if (!empty($application_credentials)) {
						putenv('GOOGLE_APPLICATION_CREDENTIALS='.$application_credentials);
						$gcloud_cmd = escapeshellarg('gcloud').' auth application-default print-access-token';
						$access_token = trim(shell_exec($gcloud_cmd));
						if (!empty($access_token)) {
							$headers[] = 'Authorization: Bearer '.$access_token;
						}
					}

					curl_setopt_array($ch, [
						CURLOPT_URL => $api_url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => $json_request,
						CURLOPT_HTTPHEADER => $headers,
						CURLOPT_CONNECTTIMEOUT => 20,
						CURLOPT_TIMEOUT => 300,
						CURLOPT_SSL_VERIFYPEER => true
					]);
					$http_response = curl_exec($ch);
					if (curl_errno($ch)) {
						echo 'Error:' . curl_error($ch);
					}
					curl_close($ch);
				}

				//validate the json
				if (!empty($http_response)) {
					$ob = json_decode($http_response);
					if($ob === null) {
						echo "invalid json\n";
						return false;
					}

					$json = json_decode($http_response, true);
					$message = '';
					foreach($json['results'] as $row) {
						$message .= $row['alternatives'][0]['transcript'];
					}
				}

				//build the response
				$array['provider'] = $transcribe_provider;
				$array['language'] = $transcribe_language;
				$array['message'] = $message ?? '';

				return $array;
			}

		//transcribe - azure
			if ($transcribe_provider == 'azure') {
				$api_key = $settings->get('voicemail', 'azure_key');
				$api_url = $settings->get('voicemail', 'azure_server_region');

				if (empty($transcribe_language)) {
					$transcribe_language = 'en-US';
				}

				if (isset($api_key) && $api_key != '') {
					$full_file_path = $file_path.'/'.$file_name;

					$ch = curl_init();
					curl_setopt_array($ch, [
						CURLOPT_URL => 'https://'.$api_url.'.api.cognitive.microsoft.com/sts/v1.0/issueToken',
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => '',
						CURLOPT_HTTPHEADER => [
							'Content-type: application/x-www-form-urlencoded',
							'Content-Length: 0',
							'Ocp-Apim-Subscription-Key: '.$api_key
						],
						CURLOPT_CONNECTTIMEOUT => 20,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_SSL_VERIFYPEER => true
					]);
					$access_token_result = curl_exec($ch);
					$token_errno = curl_errno($ch);
					curl_close($ch);

					if ($token_errno || empty($access_token_result)) {
						return false;
					}

					$audio_data = file_get_contents($full_file_path);
					if ($audio_data === false) {
						return false;
					}

					$ch = curl_init();
					curl_setopt_array($ch, [
						CURLOPT_URL => 'https://'.$api_url.'.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1?language='.urlencode($transcribe_language).'&format=detailed',
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => $audio_data,
						CURLOPT_HTTPHEADER => [
							'Authorization: Bearer '.$access_token_result,
							'Content-type: audio/wav; codec="audio/pcm"; samplerate=8000'
						],
						CURLOPT_CONNECTTIMEOUT => 20,
						CURLOPT_TIMEOUT => 300,
						CURLOPT_SSL_VERIFYPEER => true
					]);
					$http_response = curl_exec($ch);
					if (curl_errno($ch)) {
						echo 'Error:' . curl_error($ch);
					}
					curl_close($ch);

					$array = json_decode($http_response, true);
					if ($array === null) {
						return false;
					}
					else {
						$message = $array['NBest'][0]['Display'];
					}

					$array['provider'] = $transcribe_provider;
					$array['language'] = $transcribe_language;
					$array['message'] = $message;
					return $array;
				}

			}

			// transcribe - custom
			// Works with self-hostable transcription service at https://github.com/AccelerateNetworks/an-transcriptions
			if ($transcribe_provider == 'custom') {
				$api_key = $settings->get('voicemail', 'api_key');
				$api_url = $settings->get('voicemail', 'transcription_server');

				if (empty($transcribe_language)) {
					$transcribe_language = 'en-US';
				}

				$full_file_path = $file_path.'/'.$file_name;

				$message = null;
				for($retries = 5; $retries > 0; $retries--) {
					echo "sending voicemail recording to ".$api_url." for transcription";

					// submit the file for transcribing
					$ch = curl_init();
					curl_setopt_array($ch, [
						CURLOPT_URL => $api_url.'/enqueue',
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => ['file' => curl_file_create($full_file_path)],
						CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$api_key],
						CURLOPT_CONNECTTIMEOUT => 20,
						CURLOPT_TIMEOUT => 60,
						CURLOPT_SSL_VERIFYPEER => true
					]);
					$stdout = curl_exec($ch);
					if (curl_errno($ch)) {
						echo 'Error:' . curl_error($ch);
					}
					curl_close($ch);

					$resp = json_decode($stdout, true);
					if ($resp === null) {
						echo "unexpected error: ".$stdout;
						continue;
					}

					$transcription_id = $resp['id'];

					// wait for transcription to complete
					sleep(1);

					while(true) {
						echo "checking ".$api_url." for completion of job ".$transcription_id;
						$ch = curl_init();
						curl_setopt_array($ch, [
							CURLOPT_URL => $api_url.'/j/'.$transcription_id,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$api_key],
							CURLOPT_CONNECTTIMEOUT => 10,
							CURLOPT_TIMEOUT => 30,
							CURLOPT_SSL_VERIFYPEER => true
						]);
						$stdout = curl_exec($ch);
						if (curl_errno($ch)) {
							echo 'Error:' . curl_error($ch);
						}
						curl_close($ch);

						$resp = json_decode($stdout, true);
						if ($resp === null) {
							continue;
						}

						if($resp['status'] == "failed") {
							echo "transcription failed, retrying";
							break;
						}

						if($resp['status'] == "finished") {
							echo "transcription succeeded";
							$message = $resp['result'];
							break;
						}

						sleep(1);
					}

					if($message !== null) {
						break;
					}
				}

				if($message == null) {
					return false;
				}

				$array['provider'] = $transcribe_provider;
				$array['language'] = $transcribe_language;
				$array['message'] = $message;
				return $array;
			}

		//transcribe - openai
		// settings:
		//		openai_key (required)
		//		openai_url
		//		openai_model
			if ($transcribe_provider == 'openai') {
				$api_key = $settings->get('voicemail', 'openai_key');
				$api_url = $settings->get('voicemail', 'openai_url');
				$api_voice_model = $settings->get('voicemail', 'openai_model');

				if (empty($api_url)) {
					$api_url = "https://api.openai.com/v1/audio/transcriptions";
				}

				if (empty($api_voice_model)) {
					$api_voice_model = "whisper-1";
				}

				if (isset($api_key) && $api_key != '') {

					$full_file_name = $file_path.'/'.$file_name ;

					//start output buffer
					ob_start();
					$out = fopen('php://output', 'w');

					//create the curl resource
					$ch = curl_init();

					$post_data = array(
						'model'=>$api_voice_model,
						'file'=>curl_file_create($full_file_name)
					);

					//set the curl options
					curl_setopt_array($ch, array(
						CURLOPT_URL =>$api_url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_SSL_VERIFYPEER => TRUE,
						CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$api_key),
						CURLOPT_POSTFIELDS => $post_data,

					));

					// //add verbose for debugging
					// curl_setopt($ch, CURLOPT_VERBOSE, true);
					curl_setopt($ch, CURLOPT_STDERR, $out);

					//execute the curl with the options
					$http_content = curl_exec($ch);

					//return the error
					if (curl_errno($ch)) {
						echo 'Error:' . curl_error($ch);
					}

					//close the curl resource
					curl_close($ch);

					//show the debug information
					fclose($out);
					$debug = ob_get_clean();
					echo $debug;


					$ob = json_decode($http_content, true);

					$message = $ob['text'];
					return array(
						'provider' => $transcribe_provider,
						'message' => $message
					);
				}

			}
		// todo: add error checking
		//		return array('message' => "Missing valid transcribe_provider";

	}
}

?>
