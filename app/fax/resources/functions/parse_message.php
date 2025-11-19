<?php

/**
 * Parse a message from the email connection.
 *
 * @param resource    $connection     IMAP connection to the mailbox
 * @param int         $message_number The message number of the message to parse
 * @param string|null $option         Optional argument for imap_fetchstructure()
 * @param string      $to_charset     Charset to decode messages into, default is 'UTF-8'
 *
 * @return array An array containing two keys: 'messages' and 'attachments'. Each key contains an array of parsed messages or attachments.
 */
function parse_message($connection, $message_number, $option = null, $to_charset = 'UTF-8') {
	$result = Array('messages'=>Array(),'attachments'=>Array());
	$structure = imap_fetchstructure($connection, $message_number, $option);

	if (isset($structure->parts)) {
		$flatten = parse_message_flatten($structure->parts);
	}
	else {
		$flatten = Array(1 => $structure);
	}

	foreach ($flatten as $id => $part){
		switch($part->type) {
		case TYPETEXT:
			$message = parse_message_decode_text($connection, $part, $message_number, $id, $option, $to_charset);
			$result['messages'][] = $message;
			break;

		case TYPEAPPLICATION: case TYPEAUDIO: case TYPEIMAGE: case TYPEVIDEO: case TYPEOTHER:
			$attachment = parse_message_decode_attach($connection, $part, $message_number, $id, $option);
			if($attachment){
				$result['attachments'][] = $attachment;
			}
			break;

		case TYPEMULTIPART: case TYPEMESSAGE:
			break;
		}
	}

	return $result;
}

/**
 * Decode the text part of a message from the email connection.
 *
 * @param resource     $connection     IMAP connection to the mailbox
 * @param array       &$part           The text part of the message, retrieved using imap_fetchstructure()
 * @param int          $message_number The message number of the message to parse
 * @param int          $id             Unique identifier for this part of the message
 * @param string|null  $option         Optional argument for imap_fetchbody()
 * @param string       $to_charset     Charset to decode messages into, default is 'UTF-8'
 *
 * @return array An array containing three keys: 'data', 'type', and 'size'. The 'data' key contains the decoded message text.
 */
function parse_message_decode_text($connection, &$part, $message_number, $id, $option, $to_charset){
	$msg = parse_message_fetch_body($connection, $part, $message_number, $id, $option);

	if($msg && $to_charset){
		$charset = '';
		if(isset($part->parameters) && count($part->parameters)) {
			foreach ($part->parameters as $parameter){
				if($parameter->attribute == 'CHARSET') {
					$charset = $parameter->value;
					break;
				}
			}
		}
		if($charset){
			if ($charset === 'windows-1256') {
				$msg = iconv('windows-1256', 'utf-8', $msg);
			} else {
				$msg = mb_convert_encoding($msg, $to_charset, $charset);
			}
		}
		$msg = trim($msg);
	}

	return Array(
		'data' => $msg,
		'type' => parse_message_get_type($part),
		'size' => strlen($msg),
	);
}

/**
 * Parse an attachment from the email connection.
 *
 * @param resource     $connection     IMAP connection to the mailbox
 * @param object      &$part           The email part to parse
 * @param int          $message_number The message number of the message containing the attachment
 * @param string       $id             The internal ID of the attachment in the message
 * @param string|null  $option         Optional argument for imap_fetchbody()
 *
 * @return array|false An array containing information about the parsed attachment, or false if no valid filename is found.
 */
function parse_message_decode_attach($connection, &$part, $message_number, $id, $option){
	$filename = false;

	if($part->ifdparameters) {
		foreach($part->dparameters as $object) {
			if(strtolower($object->attribute) == 'filename') {
				$filename = $object->value;
				break;
			}
		}
	}

	if($part->ifparameters) {
		foreach($part->parameters as $object) {
			if(strtolower($object->attribute) == 'name') {
				$filename = $object->value;
				break;
			}
		}
	}

	if(!$filename) {
		return false;
	}

	$body = parse_message_fetch_body($connection, $part, $message_number, $id, $option);

	return Array(
		'data' => $body,
		'type' => parse_message_get_type($part),
		'name' => $filename,
		'size' => strlen($body),
		'disposition' => $part->disposition,
	);
}

/**
 * Retrieves and decodes the body of a message from an email server.
 *
 * @param resource $connection     IMAP connection to the email server
 * @param object & $part           Part of the email being processed
 * @param int      $message_number The number of the message to retrieve
 * @param string   $id             Unique identifier for the part
 * @param int      $option         Option flag (default value is not documented)
 *
 * @return string The decoded body of the message
 */
function parse_message_fetch_body($connection, &$part, $message_number, $id, $option){
	$body = imap_fetchbody($connection, $message_number, $id, $option);
	if($part->encoding == ENCBASE64){
		$body = base64_decode($body);
	}
	else if($part->encoding == ENCQUOTEDPRINTABLE){
		$body = quoted_printable_decode($body);
	}
	return $body;
}

/**
 * Returns the type and subtype of a message part.
 *
 * @param object $part Message part object containing type and subtype information.
 *
 * @return string Type and subtype of the message part, separated by a slash. (e.g., "message/plain")
 */
function parse_message_get_type(&$part){
	$types = Array(
		TYPEMESSAGE     => 'message',
		TYPEMULTIPART   => 'multipart',
		TYPEAPPLICATION => 'application',
		TYPEAUDIO       => 'audio',
		TYPEIMAGE       => 'image',
		TYPETEXT        => 'text',
		TYPEVIDEO       => 'video',
		TYPEMODEL       => 'model',
		TYPEOTHER       => 'other',
	);

	return $types[$part->type] . '/' . strtolower($part->subtype);
}

/**
 * Recursively flattens a hierarchical message structure into a single-level array.
 *
 * @param object $structure  Message structure containing nested parts and subparts.
 * @param array &$result     Resulting flattened array of message parts.
 * @param string $prefix     Prefix for each part in the result array (optional).
 * @param int    $index      Index of the current part (used for generating prefixes, optional).
 * @param bool   $fullPrefix Whether to include the index in the prefix or not (optional).
 *
 * @return array Flattened message structure.
 */
function parse_message_flatten(&$structure, &$result = array(), $prefix = '', $index = 1, $fullPrefix = true) {
	foreach ($structure as $part) {
		if(isset($part->parts)) {
			if($part->type == TYPEMESSAGE) {
				parse_message_flatten($part->parts, $result, $prefix.$index.'.', 0, false);
			}
			elseif($fullPrefix) {
				parse_message_flatten($part->parts, $result, $prefix.$index.'.');
			}
			else {
				parse_message_flatten($part->parts, $result, $prefix);
			}
		}
		else {
			$result[$prefix.$index] = $part;
		}
		$index++;
	}
	return $result;
}

