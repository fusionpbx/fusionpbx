<?php
/*
	Copyright (c) 2023 Mark J Crane <markjcrane@fusionpbx.com>

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions
	are met:

		1. Redistributions of source code must retain the above copyright
		notice, this list of conditions and the following disclaimer.

		2. Redistributions in binary form must reproduce the above copyright
		notice, this list of conditions and the following disclaimer in the
		documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
	ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
	FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
	DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
	OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
	HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
	LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
	OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
	SUCH DAMAGE.
*/

//check the permission
	if (defined('STDIN')) {
		//includes files
		require_once dirname(__DIR__, 4) . "/resources/require.php";
	}
	else {
		exit;
	}

//increase limits
	set_time_limit(0);
	//ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '512M');

//save the arguments to variables
	$script_name = $argv[0];
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}

//define the short options
	// : required, :: optional, no colon no value
	//$short_options  = "a"; // email address
	//$short_options .= "l:"; // template language
	//$short_options .= "c:"; // template_category
	//$short_options .= "s:"; // template_subcategory
	//$short_options .= "t::"; // template_type
	//$short_options .= "D::"; // domain

//define the long options
	$long_options = array(
		"email_address:",
		"email_attachment::",
		"template_language:",
		"template_category:",
		"template_subcategory:",
		"template_type:",
		"domain_name:",
		"debug:"
	);

//get the email attachment allowed paths
	if (isset($conf['email.attachments.0.path'])) {
		$i = 0;
		while(true) {
			if (isset($conf['email.attachments.'.$i.'.path'])) {
				$attachment_allowed_paths[] = $conf['email.attachments.'.$i.'.path'];
			}
			else {
				break;
			}
			$i++;
		}
	}

//get the command line parameters
	$options = getopt(null, $long_options);

//set the values from the command line parameters
	foreach($options as $option_key => $option_value) {
		switch ($option_key) {
			case 'email_address':
				if (is_array($option_value)) {
					$email_array = $option_value;
				}
				else {
					$email_array[] = $option_value;
				}
				break;
			case 'email_attachment':
				if (is_array($option_value)) {
					$email_attachment_array = $option_value;
				}
				else {
					$email_attachment_array[] = $option_value;
				}
				break;
			case 'template_language':
				$template_language = $option_value;
				break;
			case 'template_category':
				$template_category = $option_value;
				break;
			case 'template_subcategory':
				$template_subcategory = $option_value;
				break;
			case 'template_type':
				$template_type = $option_value;
				break;
			case 'domain_name':
				$domain_name = $option_value;
				break;
			case 'debug':
				$debug = $option_value;
				break;
		}
	}

//set default values
	if (empty($template_language)) {
		$template_language = 'en-us';
	}
	if (empty($template_type)) {
		$template_type = 'html';
	}

//prepare the email attachment
	$i = 0;
	foreach($email_attachment_array as $email_attachment) {
		foreach($attachment_allowed_paths as $allowed_path) {
			if ($allowed_path == dirname($email_attachment) && file_exists($email_attachment)) {
				$email_attachments[$i]['path'] = dirname($email_attachment);
				$email_attachments[$i]['name'] = basename($email_attachment);
			}
			$i++;
		}
	}

//get the domain_uuid
	$sql = "select domain_uuid from v_domains ";
	$sql .= "where domain_name = :domain_name ";
	$parameters['domain_name'] = $domain_name;
	$database = new database;
	$domain_uuid = $database->select($sql, $parameters, 'column');
	unset($parameters);

//get the email queue settings
	$smtp_from = $settings->get('email', 'smtp_from');
	$smtp_from_name = $settings->get('email', 'smtp_from_name', $smtp_from);
	$save_response = $settings->get('email_queue', 'save_response');

//debug information
	if (!empty($debug) && $debug == 'true') {
		echo "email_address: "; print_r($email_array);
		echo "template_language: ".$template_language."\n";
		echo "template_category: ".$template_category."\n";
		echo "template_subcategory: ".$template_subcategory."\n";
		echo "template_type: ".$template_type."\n";
		echo "domain_name: ".$domain_name."\n";
		echo "debug: ".$debug."\n";
		echo "smtp_from: $smtp_from\n";
		echo "smtp_from_name: $smtp_from_name\n";
	}

//define the message variable
	$message = '';

//show required details
	if (empty($smtp_from)) {
		$message .= "smtp_from needs to be set in Default Settings\n";
	}
	if (!is_array($email_array)) {
		$message .= "email_address\n";
	}
	if (empty($smtp_from)) {
		$message .= "template_category\n";
	}
	if (empty($template_subcategory)) {
		$message .= "template_subcategory\n";
	}
	if (empty($template_subcategory)) {
		$message .= "template_subcategory\n";
	}
	if (empty($domain_name)) {
		$message .= "domain_name\n";
	}
	if (!empty($message)) {
		echo "Following parameters are required\n";
		echo $message;
		exit;
	}

//get the email template from the database
	$sql = "select template_subject, template_body from v_email_templates ";
	$sql .= "where template_enabled = 'true' ";
	$sql .= "and template_language = :template_language ";
	$sql .= "and template_category = :template_category ";
	$sql .= "and template_subcategory = :template_subcategory ";
	$sql .= "and template_type = :template_type ";
	$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['template_language'] = $template_language;
	$parameters['template_category'] = $template_category;
	$parameters['template_subcategory'] = $template_subcategory;
	$parameters['template_type'] = $template_type;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row)) {
		$email_subject = $row['template_subject'];
		$email_body = $row['template_body'];
	}
	unset($sql, $parameters);

//replace variables in email subject
	if (!empty($email_subject)) {
		$email_subject = str_replace('${domain_name}', $domain_name, $email_subject);
	}

//replace variables in email body
	if (!empty($email_body)) {
		$email_body = str_replace('${domain_name}', $domain_name, $email_body);
	}

//more debug information
	if (!empty($debug) && $debug == 'true') {
		echo "email_subject: $email_subject\n";
		echo "email_body: ";
		echo $email_body."\n";
	}

//create the email object
	$email = new email;

//send email
	foreach ($email_array as $email_address) {
		$email->recipients = $email_address;
		$email->subject = $email_subject;
		$email->body = $email_body;
		$email->from_address = $smtp_from;
		$email->from_name = $smtp_from_name;
		if (isset($email_attachments)) {
			$email->attachments = $email_attachments;
		}
		$email->debug_level = 3;
		$email_response = $email->send();
		if (!empty($debug) && $debug == 'true') {
			echo $email_response;
		}
	}

?>
