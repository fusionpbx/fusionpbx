<?php

function parse_message($connection, $message_number, $option = null, $to_charset = 'UTF-8') {
	$result = Array('messages'=>Array(),'attachments'=>Array());
	$structure = imap_fetchstructure($connection, $message_number, $option);

	if (isset($structure->parts)) {
		$flatten = parse_message_flatten($structure->parts);
	}
	else {
		$flatten = Array(1 => $structure);
	}

	foreach($flatten as $id => &$part){
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

function parse_message_decode_text($connection, &$part, $message_number, $id, $option, $to_charset){
	$msg = parse_message_fetch_body($connection, $part, $message_number, $id, $option);

	if($msg && $to_charset){
		$charset = '';
		if(isset($part->parameters) && count($part->parameters)) {
			foreach($part->parameters as &$parameter){
				if($parameter->attribute == 'CHARSET') {
					$charset = $parameter->value;
					break;
				}
			}
		}
		if($charset){
			$msg = mb_convert_encoding($msg, $to_charset, $charset);
		}
		$msg = trim($msg);
	}

	return Array(
		'data' => $msg,
		'type' => parse_message_get_type($part),
		'size' => strlen($msg),
	);
}

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

function parse_message_flatten(&$structure, &$result = array(), $prefix = '', $index = 1, $fullPrefix = true) {
	foreach($structure as &$part) {
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

