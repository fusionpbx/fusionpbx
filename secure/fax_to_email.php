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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//file or console
	$output_type = "file";

//only allow command line
	if (!defined('STDIN')) {
		exit;
	}

//determine if windows is true or false
	$IS_WINDOWS = stristr(PHP_OS, 'WIN') ? true : false;

if (!function_exists('exec_in_dir')) {
	function exec_in_dir($dir, $cmd, &$ok) {
		$args = func_get_args();
		$cwd = getcwd();
		chdir($dir);
		$output = array();
		$ret = 0;
		$result = exec($cmd, $output, $ret);
		if ($cwd)
			chdir($cwd);
		$ok = ($ret == 0);
		return implode("\n", $output);
	}
}

if (!function_exists('correct_path')) {
	function correct_path($p) {
		global $IS_WINDOWS;
		if ($IS_WINDOWS) {
			return str_replace('/', '\\', $p);
		}
		return $p;
	}
}

if (!function_exists('path_join')) {
	function path_join() {
		$args = func_get_args();
		$paths = array();
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}

		$prefix = null;
		foreach ($paths as $path) {
			if ($prefix === null && !empty($path)) {
				if (substr($path, 0, 1) == '/') $prefix = '/';
				else $prefix = '';
			}
			$path = trim( $path, '/' );
		}

		if ($prefix === null) {
			return '';
		}

		$paths = array_filter($paths);
		return $prefix . implode('/', $paths);
	}
}

if (!function_exists('tiff2pdf')) {
	function tiff2pdf($tiff_file_name) {
		//convert the tif to a pdf
		//Ubuntu: apt-get install libtiff-tools

		global $settings, $IS_WINDOWS;

		if (!file_exists($tiff_file_name) || !is_file($tiff_file_name)) {
			echo "tiff file does not exist\n";
			return false; // "tiff file does not exist";
		}

		$GS = $IS_WINDOWS ? 'gswin32c' : 'gs';
		$tiff_file = pathinfo($tiff_file_name);
		$dir_fax = $tiff_file['dirname'];
		$fax_file_name = $tiff_file['filename'];
		$pdf_file_name = path_join($dir_fax, $fax_file_name . '.pdf');

		if (file_exists($pdf_file_name)) {
			return $pdf_file_name;
		}

		$dir_fax_temp = $settings->get('server', 'temp', sys_get_temp_dir());
		if (!$dir_fax_temp) {
			$dir_fax_temp = path_join(dirname($dir_fax), 'temp');
		}

		if (!file_exists($dir_fax_temp)) {
			echo "cannot create temporary directory\n";
			return false; //cannot create temporary directory
		}

		$cmd  = "tiffinfo " . correct_path($tiff_file_name) . ' | grep "Resolution:"';
		$ok   = false;
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if (!$ok) {
			echo "cannot find fax resoulution\n";
			return false; // "cannot find fax resoulution"
		}

		$ppi_w = 0;
		$ppi_h = 0;
		$tmp = array();
		if (preg_match('/Resolution.*?(\d+).*?(\d+)/', $resp, $tmp)) {
			$ppi_w = $tmp[1];
			$ppi_h = $tmp[2];
		}

		$cmd = "tiffinfo " . $tiff_file_name . ' | grep "Image Width:"';
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if (!$ok) {
			echo "can not find fax size";
			return false; // "can not find fax size"
		}

		$pix_w = 0;
		$pix_h = 0;
		$tmp = array();
		if (preg_match('/Width.*?(\d+).*?Length.*?(\d+)/', $resp, $tmp)) {
			$pix_w = $tmp[1];
			$pix_h = $tmp[2];
		}

		$page_width  = $pix_w / $ppi_w;
		$page_height = $pix_h / $ppi_h;
		$page_size   = 'a4';

		if (($page_width > 8.4) && ($page_height > 13)) {
			$page_width  = 8.5;
			$page_height = 14;
			$page_size   = 'legal';
		}
		elseif (($page_width > 8.4) && ($page_height < 12)) {
			$page_width  = 8.5;
			$page_height = 11;
			$page_size   = 'letter';
		}
		elseif (($page_width < 8.4) && ($page_height > 11)) {
			$page_width  = 8.3;
			$page_height = 11.7;
			$page_size   = 'a4';
		}
		$page_width  = sprintf('%.4f', $page_width);
		$page_height = sprintf('%.4f', $page_height);

		$cmd = implode(' ', array('tiff2pdf',
			'-o', correct_path($pdf_file_name),
			correct_path($tiff_file_name),
		));

		$resp = exec_in_dir($dir_fax, $cmd, $ok);

		if (!file_exists($pdf_file_name)) {
			echo "can not create pdf: $resp";
			return false;
		}

		return $pdf_file_name;
	}
}

if (!function_exists('fax_split_dtmf')) {
	function fax_split_dtmf(&$fax_number, &$fax_dtmf) {
		$tmp = array();
		$fax_dtmf = '';
		if (preg_match('/^\s*(.*?)\s*\((.*)\)\s*$/', $fax_number, $tmp)) {
			$fax_number = $tmp[1];
			$fax_dtmf = $tmp[2];
		}
	}
}

//includes files
	require_once dirname(__DIR__) . "/resources/require.php";
	include_once "resources/phpmailer/class.phpmailer.php";
	include_once "resources/phpmailer/class.smtp.php"; // optional, gets called from within class.phpmailer.php if not already loaded

//set php ini values
	ini_set('max_execution_time', 900); //15 minutes
	ini_set('memory_limit', '96M');

//add a delimeter to the log
	echo "\n---------------------------------\n";

//get the parameters and save them as variables in the request superglobal
	if (empty($_REQUEST)) {
		foreach ($_SERVER['argv'] as $arg) {
			if (strpos($arg, '=') !== false) {
				[$key, $value] = explode('=', $arg, 2);
				$_REQUEST[$key] = $value;
			}
		}
	}

	//use a foreach loop so order doesn't matter
	foreach ($_REQUEST as $key => $value) {
		if (strpos($value, '=') !== false) {
			$arg = explode('=', $value);
			if (count($arg) > 1) {
				$key = $arg[0];
				$value = $arg[1];
			}
		}
		switch($key) {
			case 'email':
				$fax_email = $value;
				break;
			case 'extension':
				$fax_extension = $value;
				break;
			case 'name':
				$fax_file = $value;
				break;
			case 'messages':
				$fax_messages = $value;
				break;
			case 'domain':
				//domain_name is global for all functions
				$domain_name = $value;
				break;
			case 'retry':
				$fax_relay = $value;
				break;
			case 'mailfrom_address':
				$mail_from_address = $value;
				break;
			case 'debug':
				$output_type = 'console';
				break;
			case 'config':
				$config_file = $value;
				break;
			case 'caller_id_name':
			case 'caller_id_number':
			case 'fax_relay':
			case 'fax_prefix':
			case 'domain_name':
			case 'mail_from_address':
			case 'output_type':
			case 'fax_extension':
			case 'debug_level':
			case 'config_file':
			case 'fax_file_uuid':
				//tansform the name of the key into the name of the variable using $$
				$$key = $value;
				break;
		}
	}

//mark global variables for all functions
	global $output_type, $config, $database, $domain_name, $domains, $settings, $domain_uuid, $debug_level;

//run shutdown function on exit
	register_shutdown_function('shutdown');

//start to cache the output
	if ($output_type == "file") {
		ob_end_clean();
		ob_start();
	}

//find and load the config file
	$config = config::load($config_file ?? '');		//use the config_file option from command line if available

//stop if no config
	if ($config->is_empty()) {
		exit("Unable to find config file\n");
	}

//create a single database object
	$database = database::new(['config' => $config]);

//get the fax file name (only) if a full path
	$fax_path = pathinfo($fax_file);
	$fax_file_only = $fax_path['basename'];
	$fax_file_name = $fax_path['filename'];
	$dir_fax = $fax_path['dirname'];

//get the enabled domains from the database
	//$domains = domains::fetch($database);
	$domains = [];
	$result = $database->select("select domain_uuid, domain_name from v_domains where domain_enabled = 'true'");
	if (is_array($result) && @sizeof($result) != 0) {
		foreach ($result as $row) {
			//set the domain variables
			$domains[$row["domain_uuid"]] = $row["domain_name"];
		}
	}
	unset($result, $row);

//set the global variable domain_uuid using domain_name passed on command line
	$domain_uuid = array_search($domain_name, $domains);

//ensure we have a domain_uuid
	if (empty($domain_uuid)) {
		exit("Domain '$domain_name' not found\n");
	}

//ensure we have a fax extension
	if (empty($fax_extension)) {
		exit("No fax extension set\n");
	}

//now that we have the domain_name and uuid, create a settings object for the domain
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

//get the language code
	$language_code = $settings->get('domain', 'language', 'en-us');

//add multi-lingual support
	$language = new text;
	$text = $language->get($language_code, 'app/fax');

//prepare smtp server settings
	$email_from_address = $settings->get('fax','smtp_from', $settings->get('email', 'smtp_from', ''));
	$email_from_name = $settings->get('fax', 'smtp_from_name', $settings->get('email', 'smtp_from_name', $email_from_address));

//get the fax settings from the database
	$sql = "select * from v_fax ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and fax_extension = :fax_extension ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['fax_extension'] = $fax_extension;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		$fax_email = $row["fax_email"];
		$fax_email_file = $row["fax_file"];
		$fax_uuid = $row["fax_uuid"];
		$fax_accountcode = $row["fax_accountcode"];
		$fax_prefix = $row["fax_prefix"];
		$fax_pin_number = $row["fax_pin_number"];
		$fax_caller_id_name = $row["fax_caller_id_name"];
		$fax_caller_id_number = $row["fax_caller_id_number"];
		$fax_forward_number = $row["fax_forward_number"];
		$fax_description = $row["fax_description"];
		$fax_email_inbound_subject_tag = $row['fax_email_inbound_subject_tag'];
		$mail_to_address = $fax_email;
	}
	unset($sql, $parameters, $row);

//set the fax directory
	if (!file_exists($dir_fax) || !file_exists(path_join($dir_fax, $fax_file_only))) {
		$switch_dir = $settings->get('switch', 'storage', '/var/lib/freeswitch/storage');
		$dir_fax = "$switch_dir/fax/$domain_name/$fax_extension/inbox";
		if (!file_exists($dir_fax) || !file_exists(path_join($dir_fax, $fax_file_only))) {
			$dir_fax = "$switch_dir/fax/$fax_extension/inbox";
		}
	}
	$fax_file = path_join($dir_fax, $fax_file_only);

//used for debug
	echo "fax_prefix: $fax_prefix\n";
	echo "mail_to_adress: $mail_to_address\n";
	echo "fax_email: $fax_email\n";
	echo "fax_extension: $fax_extension\n";
	echo "fax_name: $fax_file_only\n";
	echo "dir_fax: $dir_fax\n";
	echo "full_path: $fax_file\n";

	$pdf_file = tiff2pdf($fax_file);
	echo "file: $pdf_file \n";
	if (!$pdf_file) {
		$fax_file_warning = "warning: Fax image not available on server.";
	}
	else{
		$fax_file_warning = '';
	}

	echo "fax file warning(s): $fax_file_warning\n";
	echo "pdf file: $pdf_file\n";

//forward the fax
	if (file_exists($fax_file) && is_file($fax_file)) {
		if (strpos($fax_file_name,'#') !== false) {
			$tmp = explode("#",$fax_file_name);
			$fax_forward_number = $fax_prefix.$tmp[0];
		}

		if (isset($fax_forward_number) && !empty($fax_forward_number)) {
			//show info
			echo "fax_forward_number: $fax_forward_number\n";

			//add fax to the fax queue or send it directly
			//build an array to add the fax to the queue
			$array['fax_queue'][0]['fax_queue_uuid'] = uuid();
			$array['fax_queue'][0]['domain_uuid'] = $domain_uuid;
			$array['fax_queue'][0]['fax_uuid'] = $fax_uuid;
			$array['fax_queue'][0]['fax_date'] = 'now()';
			$array['fax_queue'][0]['hostname'] = gethostname();
			$array['fax_queue'][0]['fax_caller_id_name'] = $fax_caller_id_name;
			$array['fax_queue'][0]['fax_caller_id_number'] = $fax_caller_id_number;
			$array['fax_queue'][0]['fax_number'] = $fax_forward_number;
			$array['fax_queue'][0]['fax_prefix'] = $fax_prefix;
			$array['fax_queue'][0]['fax_email_address'] = $mail_to_address;
			$array['fax_queue'][0]['fax_file'] = $fax_file;
			$array['fax_queue'][0]['fax_status'] = 'waiting';
			$array['fax_queue'][0]['fax_retry_count'] = 0;
			$array['fax_queue'][0]['fax_accountcode'] = $fax_accountcode;

			//add temporary permisison
			$p = permissions::new();
			$p->add('fax_queue_add', 'temp');

			//save the data
			$database->app_name = 'fax_queue';
			$database->app_uuid = '3656287f-4b22-4cf1-91f6-00386bf488f4';
			$database->save($array);

			//remove temporary permisison
			$p->delete('fax_queue_add', 'temp');

			//add message to show in the browser
			message::add($text['confirm-queued']);

		}
	}

//send the email
	if (!empty($fax_email) && file_exists($fax_file)) {

		//get the template subcategory
		if (!empty($fax_relay) && $fax_relay == 'true') {
			$template_subcategory = 'relay';
		}
		else {
			$template_subcategory = 'inbound';
		}

		//get the email template from the database
		if (!empty($domain_uuid)) {
			$sql = "select template_subject, template_body from v_email_templates ";
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$sql .= "and template_language = :template_language ";
			$sql .= "and template_category = :template_category ";
			$sql .= "and template_subcategory = :template_subcategory ";
			$sql .= "and template_type = :template_type ";
			$sql .= "and template_enabled = 'true' ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['template_language'] = $language_code;
			$parameters['template_category'] = 'fax';
			$parameters['template_subcategory'] = $template_subcategory;
			$parameters['template_type'] = 'html';
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row)) {
				$email_subject = $row['template_subject'];
				$email_body = $row['template_body'];
			}
			unset($sql, $parameters);

			//replace variables in email subject
			$email_subject = str_replace('${domain_name}', $domain_name, $email_subject);
			$email_subject = str_replace('${fax_file_name}', $fax_file_name, $email_subject);
			$email_subject = str_replace('${fax_extension}', $fax_extension, $email_subject);
			$email_subject = str_replace('${fax_messages}', $fax_messages, $email_subject);
			$email_subject = str_replace('${fax_file_warning}', $fax_file_warning, $email_subject);
			$email_subject = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_subject);

			//replace variables in email body
			$email_body = str_replace('${domain_name}', $domain_name, $email_body);
			$email_body = str_replace('${fax_file_name}', $fax_file_name, $email_body);
			$email_body = str_replace('${fax_extension}', $fax_extension, $email_body);
			$email_body = str_replace('${fax_messages}', $fax_messages, $email_body);
			$email_body = str_replace('${fax_file_warning}', $fax_file_warning, $email_body);
			$email_body = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_body);
			if ($fax_email_file == 'link') {
				$email_body = str_replace('${fax_file_message}', "<a href='https://".$domain_name.PROJECT_PATH.'/app/fax/fax_files.php?id='.$fax_uuid.'&fax_file_uuid='.$fax_file_uuid.'&a=download_link&type=fax_inbox&box=inbox&ext='.$fax_extension.'&filename='.urlencode($fax_file_name).(!empty($pdf_file) && file_exists($pdf_file) ? '.pdf' : '.tif')."'>".$text['label-fax_download']."</a>", $email_body);
			}
			else {
				$email_body = str_replace('${fax_file_message}', $text['label-fax_attached'], $email_body);
			}

			//debug info
			//echo "<hr />\n";
			//echo "email_address ".$fax_email."<br />\n";
			//echo "email_subject ".$email_subject."<br />\n";
			//echo "email_body ".$email_body."<br />\n";
			//echo "<hr />\n";

			//add the attachment
			if (!empty($fax_file_name) && $fax_email_file != 'link') {
				$email_attachments[0]['type'] = 'file';
				if (!empty($pdf_file) && file_exists($pdf_file)) {
					$email_attachments[0]['name'] = $fax_file_name.'.pdf';
					$email_attachments[0]['value'] = $pdf_file;
				}
				else {
					$email_attachments[0]['name'] = $fax_file_name.'.tif';
					$email_attachments[0]['value'] = $fax_file;
				}
			}

			//send the email
			$email = new email(["domain_uuid" => $domain_uuid]);
			$email->recipients = $fax_email;
			$email->subject = $email_subject;
			$email->body = $email_body;
			$email->from_address = $email_from_address;
			$email->from_name = $email_from_name;
			if ($fax_email_file != 'link') {
				$email->attachments = $email_attachments;
			}
			//$email->debug_level = 3;
			$response = $mail->error;
			$sent = $email->send();
		}

		//output to the log
		echo "email_from_address: ".$email_from_address."\n";
		echo "email_from_name: ".$email_from_address."\n";
		echo "email_subject: $email_subject\n";

		//send the email
		if ($sent) {
			echo "Mailer Error";
			$email_status='failed';
		}
		else {
			echo "Message sent!";
			$email_status='ok';
		}
	}

//when the exit is called, capture the statements
function shutdown() {
	//bring variables in to function scope
	global $output_type, $settings, $debug_level;

	//open the file for writing
	if ($output_type == "file") {
		//ensure we can access the settings object
		if (class_exists('settings') && $settings instanceof settings) {
			//temporary directory in default settings or use the system temp directory
			$temp_dir = $settings->get('server', 'temp', sys_get_temp_dir());
		}
		else {
			//settings is not available
			$temp_dir = sys_get_temp_dir();
		}

		//open the file
		$fp = fopen("$temp_dir/fax_to_email.log", "w");

		//get the output from the buffer
		$content = ob_get_contents();

		//clean the buffer
		ob_end_clean();

		//write the contents of the buffer
		fwrite($fp, $content);

		//close the file pointer
		fclose($fp);
	}
	else {
		if ($debug_level > 2) {
			var_dump($_REQUEST);
		}
	}
}

?>
