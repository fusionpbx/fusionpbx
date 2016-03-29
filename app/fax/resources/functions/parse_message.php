<?php

function parse_message($connection, $message_number, $option = '', $to_charset = 'UTF-8') {
	$structure = imap_fetchstructure($connection, $message_number, $option);
	if(isset($structure->parts) && count($structure->parts)) {
		for($i = 0; $i < count($structure->parts); $i++) {
			$msg = '';
			$part = $structure->parts[$i];
			if($part->type == TYPETEXT){
				$msg = imap_fetchbody($connection, $message_number, $i+1, $option);
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
				}

				if($msg){
					return $msg;
				}
			}
		}
	}
}
