<?php
/*
 FusionPBX
 Version: MPL 1.1

 The contents of this file are subject to the Mozilla Public License Version
 1.1 (the "License"); you may not use this file except in compliance with
 the License. You may obtain a copy of the License at
 http://www.mozilla.org/MPL/

 Software distributed under the License is distributed on an "AS IS" basis,
 WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 for the specific language governing rights and limitations under the
 License.

 The Original Code is FusionPBX

 The Initial Developer of the Original Code is
 Mark J Crane <markjcrane@fusionpbx.com>
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the email logs class
if (!class_exists('email_logs')) {
	class email_logs {

		/**
		 * declare private parameters
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private parameters
				$this->app_name = 'email_logs';
				$this->app_uuid = 'bd64f590-9a24-468d-951f-6639ac728694';
				$this->permission_prefix = 'email_log_';
				$this->list_page = 'email_logs.php';
				$this->table = 'email_logs';
				$this->uuid_prefix = 'email_log_';

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/app/email_logs/email_logs.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * resend records
		 */
		public function resend($records) {
			if (permission_exists($this->permission_prefix.'resend')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/app/email_logs/email_logs.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//resend multiple records (eventually)
					if (is_array($records) && @sizeof($records) != 0) {

						//retrieve checked records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = $record['uuid'];
								}
							}

						//resend emails
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$x = 0;
								foreach ($uuids as $x => $uuid) {

									//get email message
										$sql = "select email from v_email_logs ";
										$sql .= "where email_log_uuid = :email_log_uuid ";
										$parameters['email_log_uuid'] = $uuid;
										$database = new database;
										$email = $database->select($sql, $parameters, 'column');
										$found = $email != '' ? true : false;
										unset($sql, $parameters, $row);

									//resend email
										if ($found) {
											$msg = $email;
											$resend = true;
											require "secure/v_mailto.php";
											if ($mailer_error == '') {

												//build the delete array
													$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;

												//grant temporary permissions
													$p = new permissions;
													$p->add('email_log_delete', 'temp');

												//delete the email log
													$database = new database;
													$database->app_name = $this->app_name;
													$database->app_uuid = $this->app_uuid;
													$database->delete($array);
													unset($array);

												//revoke temporary permissions
													$p->delete('email_log_delete', 'temp');

												//set message
													message::add($text['message-message_resent']);

											}
											else {

												//set message
													message::add($text['message-resend_failed'].": ".$mailer_error, 'negative', 4000);

											}
										}

									//increment counter
										$x++;

								}
							}

					}

			}
		}

		/**
		 * download records
		 */
		public function download($records) {
			if (permission_exists($this->permission_prefix.'download')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/app/email_logs/email_logs.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//download multiple records (eventually zip individual emails together)
					if (is_array($records) && @sizeof($records) != 0) {

						//retrieve checked records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = $record['uuid'];
								}
							}

						//download emails
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								foreach ($uuids as $x => $uuid) {

									//get email details
										$sql = "select call_uuid, sent_date, type, email from v_email_logs ";
										$sql .= "where email_log_uuid = :email_log_uuid ";
										$parameters['email_log_uuid'] = $uuid;
										$database = new database;
										$row = $database->select($sql, $parameters, 'row');
										if (is_array($row) && @sizeof($row) != 0 && is_uuid($row['call_uuid'])) {

											//santize filename components
												$sent_date = str_replace('-','', $row['sent_date']);
												$sent_date = str_replace(':','', $sent_date);
												$sent_date = str_replace(' ','_', $sent_date);
												$type = strtolower($row['type']);
												$email_filename = $sent_date.'_'.$type.'_'.$row['call_uuid'].'.eml';

											//single email
												if (@sizeof($uuids) == 1) {

													//set headers
														header("Content-Type: message/rfc822");
														header('Content-Disposition: attachment; filename="'.$email_filename.'"');
														header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
														header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
														header("Content-Length: ".strlen($row['email']));

													//output content
														echo $row['email'];
														exit;
												}

											//multiple emails
												else {
													if (is_dir($_SESSION['server']['temp']['dir'])) {

														if (file_put_contents($_SESSION['server']['temp']['dir'].'/'.$email_filename, $row['email'])) {
															$email_files[] = $_SESSION['server']['temp']['dir'].'/'.$email_filename;
														}
													}
												}

										}
										unset($sql, $parameters, $row);
								}

								//download compressed file
								if (@sizeof($email_files) != 0) {

									//define compressed file name
										$compressed_filename = 'emails_'.date('Ymd_His').'.zip';

									//compress email files
										$command = 'zip -mj '.$_SESSION['server']['temp']['dir'].'/'.$compressed_filename.' '.implode(' ', $email_files).' 2>&1';
										exec($command, $response, $restore_errlevel);
										unset($command);

									//push download
										if (file_exists($_SESSION['server']['temp']['dir'].'/'.$compressed_filename)) {

											//open file
												session_cache_limiter('public');
												$fd = fopen($_SESSION['server']['temp']['dir'].'/'.$compressed_filename, 'rb');

											//set headers
												header("Content-Type: application/zip");
												header('Content-Disposition: attachment; filename="'.$compressed_filename.'"');
												header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
												header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
												header("Content-Length: ".filesize($_SESSION['server']['temp']['dir'].'/'.$compressed_filename));

											//output file content
												ob_clean();
												fpassthru($fd);
												fclose($fd);

											//remove compressed file
												@unlink($_SESSION['server']['temp']['dir'].'/'.$compressed_filename);
												exit;

										}

								}

							}

					}

			}

		} //method

	} //class
}

?>