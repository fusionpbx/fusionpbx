<?php
/*-
 * Copyright (c) 2022 - 2023 Mark J Crane <markjcrane@fusionpbx.com>
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

/**
 * email class
 *
 * @method boolean send
 */
if (!class_exists('email')) {
	class email {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;

		public $domain_uuid;
		public $method;
		public $recipients;
		public $subject;
		public $body;
		public $from_address;
		public $from_name; 
		public $priority;
		public $debug_level; 
		public $attachments; 
		public $read_confirmation;
		public $error;
		public $response;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
			$this->app_name = 'email';
			$this->name = 'email';
			$this->app_uuid = '7a4fef67-5bf8-436a-ae25-7e3c03afcf96';
			$this->priority = 0;
			$this->debug_level = 3;
			$this->read_confirmation = false;
		}

		/**
		 * parse raw emails
		 */
		public function parse($message) {
			//includes
			require_once('resources/pop3/mime_parser.php');
			require_once('resources/pop3/rfc822_addresses.php');
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/emails/email_transcription.php")) {
				require_once($_SERVER["PROJECT_ROOT"]."/app/emails/email_transcription.php");
			}

			//parse the email message
			$mime = new mime_parser_class;
			$mime->decode_bodies = 1;
			$parameters = array(
				//'File'=>$message_file,

				// Read a message from a string instead of a file
				'Data' => $message,

				// Save the message body parts to a directory
				// 'SaveBody' => '/tmp',

				// Do not retrieve or save message body parts
				//   'SkipBody' => 1,
			);
			$success = $mime->Decode($parameters, $decoded);
			unset($parameters);

			if (!$success) {
				echo "MIME message decoding error: ".HtmlSpecialChars($mime->error)."\n";
			}
			else {

				//get the headers
				$this->headers = json_decode($decoded[0]["Headers"]["x-headers:"], true);
				$this->subject = $decoded[0]["Headers"]["subject:"];
				$this->from_name = $decoded[0]["ExtractedAddresses"]["from:"][0]["name"];
				$this->from_address = $decoded[0]["ExtractedAddresses"]["from:"][0]["address"];
				$this->reply_to = $decoded[0]["Headers"]["reply-to:"];
				$this->recipients = $decoded[0]["ExtractedAddresses"]["to:"];
				$this->date = $decoded[0]["Headers"]["date:"];

				//debug information
				//view_array($decoded[0]);
				//view_array($this);
				//view_array($this->recipients);

				//get the body
				$this->body = ''; //$parts_array["Parts"][0]["Headers"]["content-type:"];

				//get the body
				$this->body = '';
				$this->content_type = $decoded[0]['Headers']['content-type:'];
				if (substr($this->content_type, 0, 15) == "multipart/mixed" || substr($this->content_type, 0, 21) == "multipart/alternative") {
					foreach ($decoded[0]["Parts"] as $row) {
						$body_content_type = $row["Headers"]["content-type:"];
						if (substr($body_content_type, 0, 9) == "text/html") {
							$this->body = $row["Body"];
						}
						if (substr($body_content_type, 0, 10) == "text/plain") { 
							$body_plain = $row["Body"];
							$this->body = $body_plain;
						}
					}
				}
				else {
					$content_type_array = explode(";", $content_type);
					$this->body = $decoded[0]["Body"];
					//if ($content_type_array[0] == "text/html" || $content_type_array[0] == "text/plain") {
					//	$body = $row["Body"];
					//}
				}

				//get the attachments and add to the email
				$x = 0;
				foreach ($decoded[0]["Parts"] as &$parts_array) {
					//image/tiff;name="testfax.tif"
					//text/plain; charset=ISO-8859-1; format=flowed
					$content_type = $parts_array["Parts"][0]["Headers"]["content-type:"];

					//base64, 7bit
					$content_transfer_encoding = $parts_array["Parts"][0]["Headers"]["content-transfer-encoding:"];

					//inline;filename="testfax.tif"
					$content_disposition = $parts_array["Parts"][0]["Headers"]["content-disposition"];

					//testfax.tif
					$file = $parts_array["FileName"];

					//inline	
					$filedisposition = $parts_array["FileDisposition"];

					$body_part = $parts_array["BodyPart"];
					$body_length = $parts_array["BodyLength"];

					if (!empty($file)) {
						//get the file information
							$file_ext = pathinfo($file, PATHINFO_EXTENSION);
							$file_name = substr($file, 0, (strlen($file) - strlen($file_ext))-1 );
							$encoding = "base64"; //base64_decode

							switch ($file_ext){
								case "wav":
									$mime_type = "audio/x-wav";
									break;
								case "mp3":
									$mime_type = "audio/x-mp3";
									break;
								case "pdf":
									$mime_type = "application/pdf";
									break;
								case "tif":
									$mime_type = "image/tiff";
									break;
								case "tiff":
									$mime_type = "image/tiff";
									break;
								default:
									$mime_type = "binary/octet-stream";
									break;
							}

						//add attachment(s)
							$this->attachments[$x]['type'] = 'string';
							$this->attachments[$x]['name'] = $file;
							$this->attachments[$x]['value'] = $parts_array["Body"];
						
						//increment the id
							$x++;
					}
				}

			}
		}

		/**
		 * send emails
		 */
		public function send() {

			//set the domain_uuid if not set
			if (!isset($this->domain_uuid) && isset($_SESSION)) {
				$this->domain_uuid = $_SESSION['domain_uuid'];
			}

			//get the email queue settings
			$setting = new settings(["domain_uuid" => $this->domain_uuid]);

			//set the send_method if not already set
			if (!isset($this->method)) {
				if ($setting->get('email_queue','enabled') == 'true') {
					$this->method = 'queue';
				}
				else {
					$this->method = 'direct';
				}
			}

			//add the email to the queue
			if ($this->method == 'queue') {

				//add the email_queue_uuid
				$email_queue_uuid = uuid();

				//prepare the array
				$array['email_queue'][0]['email_queue_uuid'] = $email_queue_uuid;
				$array['email_queue'][0]['domain_uuid'] = $this->domain_uuid;
				$array['email_queue'][0]['hostname'] = gethostname();
				$array['email_queue'][0]['email_date'] = 'now()';
				$array['email_queue'][0]['email_from'] = $this->from_address;
				$array['email_queue'][0]['email_to'] = $this->recipients;
				$array['email_queue'][0]['email_subject'] = $this->subject;
				$array['email_queue'][0]['email_body'] = $this->body;
				$array['email_queue'][0]['email_status'] = 'waiting';
				$array['email_queue'][0]['email_retry_count'] = null;
				//$array['email_queue'][0]['email_action_before'] = $email_action_before;
				//$array['email_queue'][0]['email_action_after'] = $email_action_after;

				//add email attachments
				if (is_array($this->attachments) && sizeof($this->attachments) > 0) {
					$y = 0;
					foreach ($this->attachments as $attachment) {
						//set the name of the file, determine extension
						if ($attachment['path'] && $attachment['name']) {
							if (file_exists($attachment['path'] && $attachment['name'])) {
								$attachment['type'] = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
							}
						}
						else if ($attachment['value']) {
							//old method
							if (strlen($attachment['value']) < 255 && file_exists($attachment['value'])) {
								$attachment['name'] = $attachment['name'] != '' ? $attachment['name'] : basename($attachment['value']);
								$attachment['path'] = pathinfo($attachment['value'], PATHINFO_DIRNAME);
								$attachment['type'] = strtolower(pathinfo($attachment['value'], PATHINFO_EXTENSION));
							}
						}

						//set the mime type
						switch ($attachment['type']) {
							case "jpg":
							case "jpeg":
								$attachment['mime_type'] = 'image/jpeg';
								break;
							case "gif":
								$attachment['mime_type'] = 'image/gif';
								break;
							case "png":
								$attachment['mime_type'] = 'image/png';
								break;
							case "pdf":
								$attachment['mime_type'] = 'application/pdf';
								break;
							case "tif":
							case "tiff":
								$attachment['mime_type'] = 'image/tiff';
								break;
							case "mp3":
								$attachment['mime_type'] = 'audio/mpeg';
								break;
							case "wav":
								$attachment['mime_type'] = 'audio/x-wav';
								break;
							case "opus":
								$attachment['mime_type'] = 'audio/opus';
								break;
							case "ogg":
								$attachment['mime_type'] = 'audio/ogg';
								break;
							default:
								$attachment['mime_type'] = 'binary/octet-stream';
						}

						//add the attachments to the array
						$array['email_queue_attachments'][$y]['email_queue_attachment_uuid'] = uuid();
						$array['email_queue_attachments'][$y]['email_queue_uuid'] = $email_queue_uuid;
						$array['email_queue_attachments'][$y]['domain_uuid'] = $this->domain_uuid;
						$array['email_queue_attachments'][$y]['email_attachment_mime_type'] = $attachment['mime_type'];
						$array['email_queue_attachments'][$y]['email_attachment_type'] = $attachment['type'];
						$array['email_queue_attachments'][$y]['email_attachment_name'] = $attachment['name'];
						$array['email_queue_attachments'][$y]['email_attachment_path'] = $attachment['path'];
						$array['email_queue_attachments'][$y]['email_attachment_base64'] = $attachment['base64'];
						$y++;
					}
				}

				//add temporary permissions
				$p = new permissions;
				$p->add("email_queue_add", 'temp');
				$p->add("email_queue_attachment_add", 'temp');

				//save the dialplan
				$database = new database;
				$database->app_name = 'email';
				$database->app_uuid = 'e24b5dab-3bcc-42e8-99c1-19b0c558c2d7';
				$database->save($array);
				//$dialplan_response = $database->message;
				unset($array);

				//remove temporary permissions
				$p->delete("dialplan_add", 'temp');
				$p->delete("dialplan_detail_add", 'temp');

			}

			//send the email directly
			if ($this->method == 'direct') {
				/*
				RECIPIENTS NOTE:

					Pass in a single email address...

						user@domain.com

					Pass in a comma or semi-colon delimited string of e-mail addresses...

						user@domain.com,user2@domain2.com,user3@domain3.com
						user@domain.com;user2@domain2.com;user3@domain3.com

					Pass in a simple array of email addresses...

						Array (
							[0] => user@domain.com
							[1] => user2@domain2.com
							[2] => user3@domain3.com
						)

					Pass in a multi-dimentional array of addresses (delivery, address, name)...

						Array (
							[0] => Array (
								[delivery] => to
								[address] => user@domain.com
								[name] => user 1
								)
							[1] => Array (
								[delivery] => cc
								[address] => user2@domain2.com
								[name] => user 2
								)
							[2] => Array (
								[delivery] => bcc
								[address] => user3@domain3.com
								[name] => user 3
								)
						)

				ATTACHMENTS NOTE:

					Pass in as many files as necessary in an array in the following format...

						Array (
							[0] => Array (
								[mime_type] => image/jpeg (will be determined by file extension, if empty)
								[name] => filename.ext
								[path] => /source/folder/ (not used if base64 content)
								[base64] => file content as base64 (not used if name and path set)
								[cid] => content id of file attachment (only used if referencing attached files in body content)
								)
							[1] => Array (
								...
								)
						)

				ERROR RESPONSE:

					Error messages are stored in the variable passed into $this->error BY REFERENCE
				*/

				try {
					//include the phpmailer classes
					include_once("resources/phpmailer/class.phpmailer.php");
					include_once("resources/phpmailer/class.smtp.php");

					//use the email default settings
					if (!empty($setting->get('email','smtp_hostname'))) {
						$smtp['hostname'] = $setting->get('email','smtp_hostname');
					}
					$smtp['host'] 		= (!empty($setting->get('email','smtp_host')) ? $setting->get('email','smtp_host'): '127.0.0.1');
					if (!empty($setting->get('email','smtp_port'))) {
						$smtp['port'] = (int)$setting->get('email','smtp_port');
					}
					else {
						$smtp['port'] = 0;
					}
					$smtp['secure'] 	= $setting->get('email','smtp_secure');
					$smtp['auth'] 		= $setting->get('email','smtp_auth');
					$smtp['username'] 	= $setting->get('email','smtp_username');
					$smtp['password'] 	= $setting->get('email','smtp_password');
					$smtp['from'] 		= $setting->get('email','smtp_from');
					$smtp['from_name'] 	= $setting->get('email','smtp_from_name');
					$smtp['validate_certificate'] = $setting->get('email','smtp_validate_certificate');
					$smtp['crypto_method'] = $setting->get('email','smtp_crypto_method') ?? null;

					if (!empty($setting->get('voicemail','smtp_from')) && !empty($setting->get('voicemail','smtp_from'))) {
						$smtp['from'] = $setting->get('voicemail','smtp_from');
					}
					if (!empty($setting->get('voicemail','smtp_from_name')) && !empty($setting->get('voicemail','smtp_from_name'))) {
						$smtp['from_name'] = $setting->get('voicemail','smtp_from_name');
					}

					//override the domain-specific smtp server settings, if any
					$sql = "select domain_setting_subcategory, domain_setting_value ";
					$sql .= "from v_domain_settings ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and (domain_setting_category = 'email' or domain_setting_category = 'voicemail') ";
					$sql .= "and domain_setting_enabled = 'true' ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$database = new database;
					$result = $database->select($sql, $parameters, 'all');
					if (is_array($result) && @sizeof($result) != 0) {
						foreach ($result as $row) {
							if ($row['domain_setting_value'] != '') {
								$smtp[str_replace('smtp_','',$row["domain_setting_subcategory"])] = $row['domain_setting_value'];
							}
						}
					}
					unset($sql, $parameters, $result, $row);

					//value adjustments
					$smtp['auth']		= ($smtp['auth'] == "true") ? true : false;
					$smtp['password']	= ($smtp['password'] != '') ? $smtp['password'] : null;
					$smtp['secure']		= ($smtp['secure'] != "none") ? $smtp['secure'] : null;
					$smtp['username']	= ($smtp['username'] != '') ? $smtp['username'] : null;

					//create the email object and set general settings
					$mail = new PHPMailer();
					$mail->IsSMTP();
					if (!empty($smtp['hostname'])) {
						$mail->Hostname = $smtp['hostname'];
					}
					$mail->Host = $smtp['host'];
					if (is_numeric($smtp['port'])) {
						$mail->Port = $smtp['port'];
					}

					if ($smtp['auth'] == "true") {
						$mail->SMTPAuth = true;
						$mail->Username = $smtp['username'];
						$mail->Password = $smtp['password'];
					}
					else {
						$mail->SMTPAuth = false;
					}

					$smtp_secure = true;
					if ($smtp['secure'] == "") {
						$mail->SMTPSecure = 'none';
						$mail->SMTPAutoTLS = false;
						$smtp_secure = false;
					}
					elseif ($smtp['secure']  == "none") {
						$mail->SMTPSecure = 'none';
						$mail->SMTPAutoTLS = false;
						$smtp_secure = false;
					}
					else {
						$mail->SMTPSecure = $smtp['secure'];
					}

					if ($smtp_secure && isset($smtp['validate_certificate']) && $smtp['validate_certificate'] == "false") {
						//bypass certificate check e.g. for self-signed certificates
						$smtp_options['ssl']['verify_peer'] = false;
						$smtp_options['ssl']['verify_peer_name'] = false;
						$smtp_options['ssl']['allow_self_signed'] = true;
					}

					//used to set the SSL version
					if ($smtp_secure && isset($smtp['crypto_method'])) {
						$smtp_options['ssl']['crypto_method'] = $smtp['crypto_method'];
					}

					//add SMTP Options if the array exists
					if (is_array($smtp_options)) {
						$mail->SMTPOptions = $smtp_options;
					}

					$this->from_address = ($this->from_address != '') ? $this->from_address : $smtp['from'];
					$this->from_name = ($this->from_name != '') ? $this->from_name : $smtp['from_name'];
					$mail->SetFrom($this->from_address, $this->from_name);
					$mail->AddReplyTo($this->from_address, $this->from_name);
					$mail->Subject = $this->subject;
					$mail->MsgHTML($this->body);
					$mail->Priority = $this->priority;
					if ($this->read_confirmation) {
						$mail->AddCustomHeader('X-Confirm-Reading-To: '.$this->from_address);
						$mail->AddCustomHeader('Return-Receipt-To: '.$this->from_address);
						$mail->AddCustomHeader('Disposition-Notification-To: '.$this->from_address);
					}
					if (is_numeric($this->debug_level) && $this->debug_level > 0) {
						$mail->SMTPDebug = $this->debug_level;
					}
					$mail->Timeout       =   20; //set the timeout (seconds)
    					$mail->SMTPKeepAlive = true; //don't close the connection between messages

					//add the email recipients
					$address_found = false;
					if (!is_array($this->recipients)) { // must be a single or delimited recipient address(s)
						$this->recipients = str_replace(' ', '', $this->recipients);
						$this->recipients = str_replace(',', ';', $this->recipients);
						$this->recipients = explode(';', $this->recipients); // convert to array of addresses
					}

					foreach ($this->recipients as $this->recipient) {
						if (is_array($this->recipient)) { // check if each recipient has multiple fields
							if ($this->recipient["address"] != '' && valid_email($this->recipient["address"])) { // check if valid address
								switch ($this->recipient["delivery"]) {
									case "cc" :		$mail->AddCC($this->recipient["address"], ($this->recipient["name"]) ? $this->recipient["name"] : $this->recipient["address"]);			break;
									case "bcc" :	$mail->AddBCC($this->recipient["address"], ($this->recipient["name"]) ? $this->recipient["name"] : $this->recipient["address"]);			break;
									default :		$mail->AddAddress($this->recipient["address"], ($this->recipient["name"]) ? $this->recipient["name"] : $this->recipient["address"]);
								}
								$address_found = true;
							}
						}
						else if ($this->recipient != '' && valid_email($this->recipient)) { // check if recipient value is simply (only) an address
							$mail->AddAddress($this->recipient);
							$address_found = true;
						}
					}

					if (!$address_found) {
						$this->error = "No valid e-mail address provided.";
						return false;
					}

					//add email attachments
					if (is_array($this->attachments) && sizeof($this->attachments) > 0) {
						foreach ($this->attachments as $attachment) {

							//add the attachments
							if (file_exists($attachment['path'].'/'.$attachment['name'])) {
								$mail->AddAttachment($attachment['path'].'/'.$attachment['name'], $attachment['name'], 'base64', $attachment['mime_type']);
							}
							else {
								if ($attachment['base64']) {
									if ($attachment['cid']) {
										$mail->addStringEmbeddedImage(base64_decode($attachment['base64']), $attachment['cid'], $attachment['name'], 'base64', $attachment['mime_type']);
									}
									else {
										$mail->AddStringAttachment(base64_decode($attachment['base64']), $attachment['name'], 'base64', $attachment['mime_type']);
									}
								}
							}
						}
					}

					//save output to a buffer
					ob_start();

					//send the email
					$mail_status = $mail->Send();

					//get the output buffer
					$this->response = ob_get_clean();

					//send the email
					if (!$mail_status) {
						if (isset($mail->ErrorInfo) && !empty($mail->ErrorInfo)) {
							$this->error = $mail->ErrorInfo;
						}
						return false;
					}

					//cleanup the mail object
					$mail->ClearAddresses();
					$mail->SmtpClose();
					unset($mail);
					return true;

				}
				catch (Exception $e) {
					$this->error = $mail->ErrorInfo;
					return false;
				}

			}
		}

	}
}

/*
$email = new email;
$email->recipients = $recipients;
$email->subject = $email_subject;
$email->body = $email_body;
$email->from_address = $email_from_address;
$email->from_name = $email_from_name;
$email->attachments = $email_attachments;
$response = $mail->error;
$sent = $email->send();
*/

?>
