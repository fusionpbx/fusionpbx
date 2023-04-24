<?php

if (!function_exists('transcribe')) {
	function transcribe ($file_path, $file_name, $file_extension) {

		//transcription variables
			$transcribe_provider = $_SESSION['voicemail']['transcribe_provider']['text'];
			$transcribe_language = $_SESSION['voicemail']['transcribe_language']['text'];

		//transcribe - watson
			if ($transcribe_provider == 'watson') {
				$api_key = $_SESSION['voicemail']['watson_key']['text'];
				$api_url = $_SESSION['voicemail']['watson_url']['text'];

				if ($file_extension == "mp3") {
					$content_type = 'audio/mp3';
				}
				if ($file_extension == "wav") {
					$content_type = 'audio/wav';
				}

				if (isset($api_key) && $api_key != '') {

					//check if the file exists
					if (!file_exists($file_path.'/'.$file_name)) {
						echo "file not found ".$file_path.'/'.$file_name;
						exit;
					}

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
				$api_key = $_SESSION['voicemail']['google_key']['text'];
				$api_url = $_SESSION['voicemail']['google_url']['text'];
				$transcribe_language = $_SESSION['voicemail']['transcribe_language']['text'];
				$transcribe_alternate_language = $_SESSION['voicemail']['transcribe_alternate_language']['text'];

				if (!isset($transcribe_language) && strlen($transcribe_language) == 0) {
					$transcribe_language = 'en-Us';
				}
				if (!isset($transcribe_alternate_language) && strlen($transcribe_alternate_language) == 0) {
					$transcribe_alternate_language = 'es-Us';
				}
				if ($file_extension == "mp3") {
					$content_type = 'audio/mp3';
				}
				if ($file_extension == "wav") {
					$content_type = 'audio/wav';
				}

				if (isset($api_key) && $api_key != '') {
					//$command = "curl -X POST -silent -u \"apikey:".$api_key."\" --header \"Content-type: ".$content_type."\" --data-binary @".$file_path."/".$file_name." \"".$api_url."\"";
					//echo "command: ".$command."\n";

					$command = "sox ".$file_path."/".$file_name." ".$file_path."/".$file_name.".flac trim 0 00:59 ";
					$command .= "&& echo \"{ 'config': { 'languageCode': '".$transcribe_language."', 'enableWordTimeOffsets': false , 'enableAutomaticPunctuation': true , 'alternativeLanguageCodes': '".$transcribe_alternate_language."' }, 'audio': { 'content': '`base64 -w 0 ".$file_path."/".$file_name.".flac`' } }\" ";
					$command .= "| curl -X POST -H \"Content-Type: application/json\" -d @- ".$api_url.":recognize?key=".$api_key." ";
					$command .= "&& rm -f ".$file_path."/".$file_name.".flac";
					echo $command."\n";

					//ob_start();
					//$result = passthru($command);
					//$json_result = ob_get_contents();
					//ob_end_clean();

					//run the command
					$http_response = shell_exec($command);

					//validate the json
					$ob = json_decode($http_response);
					if($ob === null) {
						echo "invalid json\n";
						return false;
					}

					$message = '';
					$json = json_decode($http_response, true);
					//echo "json; ".$json."\n";
					foreach($json['results'] as $row) {
						$message .= $row['alternatives'][0]['transcript'];
					}

					//build the response
					$array['provider'] = $transcribe_provider;
					$array['language'] = $transcribe_language;
					$array['command'] = $command;
					$array['message'] = $message;
					//print_r($array);

					return $array;
				}
			}

		//transcribe - azure
			if ($transcribe_provider == 'azure') {
				$api_key = $_SESSION['voicemail']['azure_key']['text'];
				$api_url = $_SESSION['voicemail']['azure_server_region']['text'];

				if (strlen($transcribe_language) == 0) {
					$transcribe_language = 'en-US';
				}

				if ($file_extension == "mp3") {
					$content_type = 'audio/mp3';
				}
				if ($file_extension == "wav") {
					$content_type = 'audio/wav';
				}

				if (isset($api_key) && $api_key != '') {
					$command = "curl -X POST \"https://".$api_url.".api.cognitive.microsoft.com/sts/v1.0/issueToken\" -H \"Content-type: application/x-www-form-urlencoded\" -H \"Content-Length: 0\" -H \"Ocp-Apim-Subscription-Key: ".$api_key."\"";
					$access_token_result = shell_exec($command);
					if (strlen($access_token_result) == 0) {
						return false;
					}
					else {
						$file_path = $file_path.'/'.$file_name;
						$command = "curl -X POST \"https://".$api_url.".stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1?language=".$transcribe_language."&format=detailed\" -H 'Authorization: Bearer ".$access_token_result."' -H 'Content-type: audio/wav; codec=\"audio/pcm\"; samplerate=8000; trustsourcerate=false' --data-binary @".$file_path;
						echo $command."\n";
						$http_response = shell_exec($command);
						$array = json_decode($http_response, true);
						if ($array === null) {
							return false;
						}
						else {
							$message = $array['NBest'][0]['Display'];
						}
					}
					$array['provider'] = $transcribe_provider;
					$array['language'] = $transcribe_language;
					$array['api_key'] = $api_key;
					$array['command'] = $command;
					$array['message'] = $message;
					return $array;
				}

			}

			// transcribe - custom
			// Works with self-hostable transcription service at https://github.com/AccelerateNetworks/an-transcriptions
			if ($transcribe_provider == 'custom') {
				$api_key = $_SESSION['voicemail']['api_key']['text'];
				$api_url = $_SESSION['voicemail']['transcription_server']['text'];

				if (strlen($transcribe_language) == 0) {
					$transcribe_language = 'en-US';
				}

				if ($file_extension == "mp3") {
					$content_type = 'audio/mp3';
				}
				if ($file_extension == "wav") {
					$content_type = 'audio/wav';
				}

				$message = null;
				for($retries = 5; $retries > 0; $retries--) {
					echo "sending voicemail recording to ".$api_url." for transcription";

					// submit the file for transcribing
					$file_path = $file_path.'/'.$file_name;
					$command = "curl -sX POST ".$api_url."/enqueue -H 'Authorization: Bearer ".$api_key."' -F file=@".$file_path;
					$stdout = shell_exec($command);
					$resp = json_decode($stdout, true);
					if ($resp === null) {
						echo "unexpected error: ".$stdout;
						// json not parsable, try again
						continue;
					}

					$transcription_id = $resp['id'];

					// wait for transcription to complete
					sleep(1);

					while(true) {
						echo "checking ".$api_url." for completion of job ".$transcription_id;
						$command = "curl -s ".$api_url."/j/".$transcription_id." -H 'Authorization: Bearer ".$api_key."'";
						$resp = json_decode(shell_exec($command), true);
						if ($resp === null) {
							// json not parsable, try again
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

						// transcription is queued or in progress, check again in 1 second
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
				$array['api_key'] = $api_key;
				// $array['command'] = $command
				$array['message'] = $message;
				return $array;
			}

	}
}

?>
