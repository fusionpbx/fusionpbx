<?php

function parse_message($connection, $message_number, $option = null, $to_charset = 'UTF-8') {
	$structure = imap_fetchstructure($connection, $message_number, $option);
	if(isset($structure->parts)) {
		return parse_message_parts($connection, $structure, false, $message_number, $option, $to_charset);
	}
	return parse_message_part($connection, $structure, '1', $message_number, $option, $to_charset);
}

function parse_message_parts($connection, $structure, $level, $message_number, $option, $to_charset) {
	if(isset($structure->parts)) {
		for($i = 0; $i < count($structure->parts); $i++) {
			$part = $structure->parts[$i];
			if($part->type != TYPEMULTIPART){
				$id = $i + 1;
				if($level) $id = $level . '.' . $id;
			}
			else{
				$id = $level;
			}

			$msg = parse_message_part($connection, $part, $id, $message_number, $option, $to_charset);
			if($msg){
				return $msg;
			}
		}
	}
}

function parse_message_part($connection, $part, $id, $message_number, $option, $to_charset){
	$msg = false;

	if($part->type == TYPETEXT){
		$msg = imap_fetchbody($connection, $message_number, $id, $option);
		if($part->encoding == ENCBASE64){
			$msg = base64_decode($msg);
		}
		else if($part->encoding == ENCQUOTEDPRINTABLE){
			$msg = quoted_printable_decode($msg);
		}
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
	}

	if(($part->type == TYPEMULTIPART) || ($part->type == TYPEMESSAGE)){
		$msg = parse_message_parts($connection, $part, $id, $message_number, $option, $to_charset);
	}

	return $msg;
}
