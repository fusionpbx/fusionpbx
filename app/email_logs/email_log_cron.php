<?php 

//restrict to command line only
	if (defined('STDIN')) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
		require_once "resources/require.php";
		require_once "resources/classes/text.php";
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		$format = 'text'; //html, text
	
		//add multi-lingual support
		$language = new text;
		$text = $language->get();
	}
	else {
		die('access denied');
	}

//get the failed emails
	$sql = "select email_log_uuid, email from v_email_logs limit 100";
	$database = new database;
	$emails = $database->select($sql, null, 'all');

//process the emails
	if (is_array($emails) && @sizeof($emails) != 0) {
		foreach($emails as $x => $row) {
			$email_log_uuid = $row['email_log_uuid'];
			$msg = $row['email'];

			require_once "secure/v_mailto.php";
			if ($mailer_error == '') {
				//set the message
					message::add($text['message-message_resent']);

				//build delete array
					$array['email_logs'][$x]['email_log_uuid'] = $email_log_uuid;
			}
			unset($mailer_error);
		}
		if (is_array($array) && @sizeof($array) != 0) {
			//grant temporary permissions
				$p = new permissions;
				$p->add('email_log_delete', 'temp');
			//execute delete
				$database = new database;
				$database->app_name = 'email_logs';
				$database->app_uuid = 'bd64f590-9a24-468d-951f-6639ac728694';
				$database->delete($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('email_log_delete', 'temp');
		}
	}
	unset($sql, $emails, $x, $row);

?>
