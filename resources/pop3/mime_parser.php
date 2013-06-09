<?php
/*
 * mime_parser.php
 *
 * @(#) $Id: mime_parser.php,v 1.68 2010/04/08 22:58:13 mlemos Exp $
 *
 */

define('MIME_PARSER_START',        1);
define('MIME_PARSER_HEADER',       2);
define('MIME_PARSER_HEADER_VALUE', 3);
define('MIME_PARSER_BODY',         4);
define('MIME_PARSER_BODY_START',   5);
define('MIME_PARSER_BODY_DATA',    6);
define('MIME_PARSER_BODY_DONE',    7);
define('MIME_PARSER_END',          8);

define('MIME_MESSAGE_START',            1);
define('MIME_MESSAGE_GET_HEADER_NAME',  2);
define('MIME_MESSAGE_GET_HEADER_VALUE', 3);
define('MIME_MESSAGE_GET_BODY',         4);
define('MIME_MESSAGE_GET_BODY_PART',    5);

define('MIME_ADDRESS_START',            1);
define('MIME_ADDRESS_FIRST',            2);

/*
{metadocument}<?xml version="1.0" encoding="ISO-8859-1" ?>
<class>

	<package>net.manuellemos.mimeparser</package>

	<version>@(#) $Id: mime_parser.php,v 1.68 2010/04/08 22:58:13 mlemos Exp $</version>
	<copyright>Copyright © (C) Manuel Lemos 2006 - 2008</copyright>
	<title>MIME parser</title>
	<author>Manuel Lemos</author>
	<authoraddress>mlemos-at-acm.org</authoraddress>

	<documentation>
		<idiom>en</idiom>
		<purpose>Parse MIME encapsulated e-mail message data compliant with
			the RFC 2822 or aggregated in mbox format.</purpose>
		<usage>Use the function <functionlink>Decode</functionlink> function
			to retrieve the structure of the messages to be parsed. Adjust its
			parameters to tell how to return the decoded body data.
			Use the <tt>SaveBody</tt> parameter to make the body parts be saved
			to files when the message is larger than the available memory. Use
			the <tt>SkipBody</tt> parameter to just retrieve the message
			structure without returning the body data.<paragraphbreak />
			If the message data is an archive that may contain multiple messages
			aggregated in the mbox format, set the variable
			<variablelink>mbox</variablelink> to <booleanvalue>1</booleanvalue>.</usage>
	</documentation>

{/metadocument}
*/

class mime_parser_class
{
/*
{metadocument}
	<variable>
		<name>error</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Store the message that is returned when an error
				occurs.</purpose>
			<usage>Check this variable to understand what happened when a call to
				any of the class functions has failed.<paragraphbreak />
				This class uses cumulative error handling. This means that if one
				class functions that may fail is called and this variable was
				already set to an error message due to a failure in a previous call
				to the same or other function, the function will also fail and does
				not do anything.<paragraphbreak />
				This allows programs using this class to safely call several
				functions that may fail and only check the failure condition after
				the last function call.<paragraphbreak />
				Just set this variable to an empty string to clear the error
				condition.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $error='';

/*
{metadocument}
	<variable>
		<name>error_position</name>
		<type>INTEGER</type>
		<value>-1</value>
		<documentation>
			<purpose>Point to the position of the message data or file that
				refers to the last error that occurred.</purpose>
			<usage>Check this variable to determine the relevant position of the
				message when a parsing error occurs.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $error_position = -1;

/*
{metadocument}
	<variable>
		<name>mbox</name>
		<type>BOOLEAN</type>
		<value>0</value>
		<documentation>
			<purpose>Specify whether the message data to parse is a single RFC
				2822 message or it is an archive that contain multiple messages in
				the mbox format.</purpose>
			<usage>Set this variable to <booleanvalue>1</booleanvalue> if it is
				it is intended to parse an mbox message archive.<br />
				mbox archives may contain multiple messages. Each message starts
				with the header <tt>From</tt>. Since all valid RFC 2822 headers
				must with a colon, the class will fail to parse a mbox archive if
				this variable is set to <booleanvalue>0</booleanvalue>.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $mbox = 0;

/*
{metadocument}
	<variable>
		<name>decode_headers</name>
		<type>BOOLEAN</type>
		<value>1</value>
		<documentation>
			<purpose>Specify whether the message headers should be decoded.</purpose>
			<usage>Set this variable to <booleanvalue>1</booleanvalue> if it is
				necessary to decode message headers that may have non-ASCII
				characters and use other character set encodings.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $decode_headers = 1;

/*
{metadocument}
	<variable>
		<name>decode_bodies</name>
		<type>BOOLEAN</type>
		<value>1</value>
		<documentation>
			<purpose>Specify whether the message body parts should be decoded.</purpose>
			<usage>Set this variable to <booleanvalue>1</booleanvalue> if it is
				necessary to parse the message bodies and extract its part
				structure.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $decode_bodies = 1;

/*
{metadocument}
	<variable>
		<name>extract_addresses</name>
		<type>BOOLEAN</type>
		<value>1</value>
		<documentation>
			<purpose>Specify whether the message headers that usually contain
				e-mail addresses should be parsed and the addresses should be
				extracted by the <functionlink>Decode</functionlink> function.</purpose>
			<usage>Set this variable to <booleanvalue>1</booleanvalue> if it is
				necessary to extract the e-mail addresses contained in certain
				message headers.<paragraphbreak />
				The headers to be parsed are defined by the
				<variablelink>address_headers</variablelink> variable.<paragraphbreak />
				The parsed addresses are returned by the
				<tt>ExtractedAddresses</tt> entry of the <argumentlink>
					<function>Decode</function>
					<argument>decoded</argument>
				</argumentlink> argument of the
				<functionlink>Decode</functionlink> function.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $extract_addresses = 1;

/*
{metadocument}
	<variable>
		<name>address_headers</name>
		<type>HASH</type>
		<value></value>
		<documentation>
			<purpose>Specify which headers contain addresses that should be
				parsed and extracted.</purpose>
			<usage>Change this variable if you need to extract e-mail addresses
				from a different list of message headers.<paragraphbreak />
				It must be set to an associative array with keys set to the names
				of the headers to be parsed including the colon. The array values
				must be set to a boolean flag to tell whether the headers with the
				respective name should be parsed. The header names must be in lower
				case.<paragraphbreak />
				By default the class addresses from the headers:
				<stringvalue>from:</stringvalue>, <stringvalue>to:</stringvalue>,
				<stringvalue>cc:</stringvalue>, <stringvalue>bcc:</stringvalue>,
				<stringvalue>return-path:</stringvalue>,
				<stringvalue>reply-to:</stringvalue> and
				<stringvalue>disposition-notification-to:</stringvalue>.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $address_headers = array(
		'from:' => 1,
		'to:' => 1,
		'cc:' => 1,
		'bcc:' => 1,
		'return-path:'=>1,
		'reply-to:'=>1,
		'disposition-notification-to:'=>1
	);

/*
{metadocument}
	<variable>
		<name>message_buffer_length</name>
		<type>INTEGER</type>
		<value>8000</value>
		<documentation>
			<purpose>Maximum length of the chunks of message data that the class
				parse at one time.</purpose>
			<usage>Adjust this value according to the available memory.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $message_buffer_length = 8000;

/*
{metadocument}
	<variable>
		<name>ignore_syntax_errors</name>
		<type>BOOLEAN</type>
		<value>1</value>
		<documentation>
			<purpose>Specify whether the class should ignore syntax errors in
				malformed messages.</purpose>
			<usage>Set this variable to <booleanvalue>0</booleanvalue> if it is
				necessary to verify whether message data may be corrupted due to
				to eventual bugs in the program that generated the
				message.<paragraphbreak />
				Currently the class only ignores some types of syntax errors.
				Other syntax errors may still cause the
				<functionlink>Decode</functionlink> to fail.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $ignore_syntax_errors=1;

/*
{metadocument}
	<variable>
		<name>warnings</name>
		<type>HASH</type>
		<value></value>
		<documentation>
			<purpose>Return a list of positions of the original message that
				contain syntax errors.</purpose>
			<usage>Check this variable to retrieve eventual message syntax
				errors that were ignored when the
				<variablelink>ignore_syntax_errors</variablelink> is set to
				<booleanvalue>1</booleanvalue>.<paragraphbreak />
				The indexes of this array are the positions of the errors. The
				array values are the corresponding syntax error messages.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $warnings=array();

/*
{metadocument}
	<variable>
		<name>track_lines</name>
		<type>BOOLEAN</type>
		<value>0</value>
		<documentation>
			<purpose>Tell the class to keep track the position of each message
				line.</purpose>
			<usage>Set this variable to <integervalue>1</integervalue> if you
				need to determine the line and column number associated to a given
				position of the parsed message.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $track_lines = 0;

	/* Private variables */
	var $state = MIME_PARSER_START;
	var $buffer = '';
	var $buffer_position = 0;
	var $offset = 0;
	var $parts = array();
	var $part_position = 0;
	var $headers = array();
	var $body_parser;
	var $body_parser_state = MIME_PARSER_BODY_DONE;
	var $body_buffer = '';
	var $body_buffer_position = 0;
	var $body_offset = 0;
	var $current_header = '';
	var $file;
	var $body_file;
	var $position = 0;
	var $body_part_number = 1;
	var $next_token = '';
	var $lines = array();
	var $line_offset = 0;
	var $last_line = 1;
	var $last_carriage_return = 0;

	/* Private functions */

	Function SetError($error)
	{
		$this->error = $error;
		return(0);
	}

	Function SetErrorWithContact($error)
	{
		return($this->SetError($error.'. Please contact the author Manuel Lemos <mlemos@acm.org> and send a copy of this message to let him add support for this kind of messages'));
	}

	Function SetPositionedError($error, $position)
	{
		$this->error_position = $position;
		return($this->SetError($error));
	}

	Function SetPositionedWarning($error, $position)
	{
		if(!$this->ignore_syntax_errors)
			return($this->SetPositionedError($error, $position));
		$this->warnings[$position]=$error;
		return(1);
	}

	Function SetPHPError($error, &$php_error_message)
	{
		if(IsSet($php_error_message)
		&& strlen($php_error_message))
			$error .= ': '.$php_error_message;
		return($this->SetError($error));
	}

	Function ResetParserState()
	{
		$this->error='';
		$this->error_position = -1;
		$this->state = MIME_PARSER_START;
		$this->buffer = '';
		$this->buffer_position = 0;
		$this->offset = 0;
		$this->parts = array();
		$this->part_position = 0;
		$this->headers = array();
		$this->body_parser_state = MIME_PARSER_BODY_DONE;
		$this->body_buffer = '';
		$this->body_buffer_position = 0;
		$this->body_offset = 0;
		$this->current_header = '';
		$this->position = 0;
		$this->body_part_number = 1;
		$this->next_token = '';
		$this->lines = ($this->track_lines ? array(0 => 0) : array());
		$this->line_offset = 0;
		$this->last_line = 0;
		$this->last_carriage_return = 0;
	}

	Function Tokenize($string,$separator="")
	{
		if(!strcmp($separator,""))
		{
			$separator=$string;
			$string=$this->next_token;
		}
		for($character=0;$character<strlen($separator);$character++)
		{
			if(GetType($position=strpos($string,$separator[$character]))=='integer')
				$found=(IsSet($found) ? min($found,$position) : $position);
		}
		if(IsSet($found))
		{
			$this->next_token=substr($string,$found+1);
			return(substr($string,0,$found));
		}
		else
		{
			$this->next_token='';
			return($string);
		}
	}

	Function ParseStructuredHeader($value, &$type, &$parameters, &$character_sets, &$languages)
	{
		$type = strtolower(trim($this->Tokenize($value, ';')));
		$p = trim($this->Tokenize(''));
		$parameters = $character_sets = $languages = array();
		while(strlen($p))
		{
			$parameter = trim(strtolower($this->Tokenize($p, '=')));
			$remaining = trim($this->Tokenize(''));
			if(strlen($remaining)
			&& !strcmp($remaining[0], '"')
			&& (GetType($quote = strpos($remaining, '"', 1)) == 'integer'))
			{
				$value = substr($remaining, 1, $quote - 1);
				$p = trim(substr($remaining, $quote + 1));
				if(strlen($p) > 0
				&& !strcmp($p[0], ';'))
					$p = substr($p, 1);
			}
			else
			{
				$value = trim($this->Tokenize($remaining, ';'));
				$p = trim($this->Tokenize(''));
			}
			if(($l=strlen($parameter))
			&& !strcmp($parameter[$l - 1],'*'))
			{
				$parameter=$this->Tokenize($parameter, '*');
				if(IsSet($parameters[$parameter])
				&& IsSet($character_sets[$parameter]))
					$value = $parameters[$parameter] . UrlDecode($value);
				else
				{
					$character_sets[$parameter] = strtolower($this->Tokenize($value, '\''));
					$languages[$parameter] = $this->Tokenize('\'');
					$value = UrlDecode($this->Tokenize(''));
				}
			}
			$parameters[$parameter] = $value;
		}
	}

	Function FindStringLineBreak($string, $position, &$break, &$line_break)
	{
		if(GetType($line_break=strpos($string, $break="\n", $position))=='integer')
		{
			if(GetType($new_line_break=strpos($string, "\n", $position))=='integer')
			{
				if($new_line_break < $line_break)
				{
					$break = "\n";
					$line_break = $new_line_break;
					return(1);
				}
			}
			if($line_break>$position
			&& $string[$line_break-1]=="\r")
			{
				$line_break--;
				$break="\r\n";
			}
			return(1);
		}
		return(GetType($line_break=strpos($string, $break="\r", $position))=='integer');
	}

	Function FindLineBreak($position, &$break, &$line_break)
	{
		if(GetType($line_break=strpos($this->buffer, $break="\r", $position))=='integer')
		{
			if(GetType($new_line_break=strpos($this->buffer, "\n", $position))=='integer')
			{
				if($new_line_break < $line_break)
				{
					$break = "\n";
					$line_break = $new_line_break;
					return(1);
				}
			}
			if(($n = $line_break + 1) < strlen($this->buffer)
			&& $this->buffer[$n]=="\n")
				$break="\r\n";
			return(1);
		}
		return(GetType($line_break=strpos($this->buffer, $break="\n", $position))=='integer');
	}

	Function FindBodyLineBreak($position, &$break, &$line_break)
	{
		if(GetType($line_break=strpos($this->body_buffer, $break="\r", $position))=='integer')
		{
			if(GetType($new_line_break=strpos($this->body_buffer, "\n", $position))=='integer')
			{
				if($new_line_break < $line_break)
				{
					$break = "\n";
					$line_break = $new_line_break;
					return(1);
				}
			}
			if(($n = $line_break + 1) < strlen($this->body_buffer)
			&& $this->body_buffer[$n]=="\n")
				$break="\r\n";
			return(1);
		}
		return(GetType($line_break=strpos($this->body_buffer, $break="\n", $position))=='integer');
	}

	Function ParseHeaderString($body, &$position, &$headers)
	{
		$l = strlen($body);
		$headers = array();
		for(;$position < $l;)
		{
			if($this->FindStringLineBreak($body, $position, $break, $line_break))
			{
				$line = substr($body, $position, $line_break - $position);
				$position = $line_break + strlen($break);
			}
			else
			{
				$line = substr($body, $position);
				$position = $l;
			}
			if(strlen($line)==0)
				break;
			$h = strtolower(strtok($line,':'));
			$headers[$h] = trim(strtok(''));
		}
	}

	Function ParsePart($end, &$part, &$need_more_data)
	{
		$need_more_data = 0;
		switch($this->state)
		{
			case MIME_PARSER_START:
				$part=array(
					'Type'=>'MessageStart',
					'Position'=>$this->offset + $this->buffer_position
				);
				$this->state = MIME_PARSER_HEADER;
				break;
			case MIME_PARSER_HEADER:
				if($this->FindLineBreak($this->buffer_position, $break, $line_break))
				{
					$next = $line_break + strlen($break);
					if(!strcmp($break,"\r")
					&& strlen($this->buffer) == $next
					&& !$end)
					{
						$need_more_data = 1;
						break;
					}
					if($line_break==$this->buffer_position)
					{
						$part=array(
							'Type'=>'BodyStart',
							'Position'=>$this->offset + $this->buffer_position
						);
						$this->buffer_position = $next;
						$this->state = MIME_PARSER_BODY;
						break;
					}
				}
				if(GetType($colon=strpos($this->buffer, ':', $this->buffer_position))=='integer')
				{
					if(GetType($space=strpos(substr($this->buffer, $this->buffer_position, $colon - $this->buffer_position), ' '))=='integer')
					{
						if((!$this->mbox
						|| strcmp(substr($this->buffer, $this->buffer_position, $space), 'From'))
						&& !$this->SetPositionedWarning('invalid header name line', $this->buffer_position))
							return(0);
						$next = $this->buffer_position + $space + 1;
					}
					else
						$next = $colon+1;
				}
				else
				{
					$need_more_data = 1;
					break;
				}
				$part=array(
					'Type'=>'HeaderName',
					'Name'=>substr($this->buffer, $this->buffer_position, $next - $this->buffer_position),
					'Position'=>$this->offset + $this->buffer_position
				);
				$this->buffer_position = $next;
				$this->state = MIME_PARSER_HEADER_VALUE;
				break;
			case MIME_PARSER_HEADER_VALUE:
				$position = $this->buffer_position;
				$value = '';
				for(;;)
				{
					if($this->FindLineBreak($position, $break, $line_break))
					{
						$next = $line_break + strlen($break);
						$line = substr($this->buffer, $position, $line_break - $position);
						if(strlen($this->buffer) == $next)
						{
							if(!$end)
							{
								$need_more_data = 1;
								break 2;
							}
							$value .= $line;
							$part=array(
								'Type'=>'HeaderValue',
								'Value'=>$value,
								'Position'=>$this->offset + $this->buffer_position
							);
							$this->buffer_position = $next;
							$this->state = MIME_PARSER_END;
							break ;
						}
						else
						{
							$character = $this->buffer[$next];
							if(!strcmp($character, ' ')
							|| !strcmp($character, "\t"))
							{
								$value .= $line;
								$position = $next + 1;
							}
							else
							{
								$value .= $line;
								$part=array(
									'Type'=>'HeaderValue',
									'Value'=>$value,
									'Position'=>$this->offset + $this->buffer_position
								);
								$this->buffer_position = $next;
								$this->state = MIME_PARSER_HEADER;
								break 2;
							}
						}
					}
					else
					{
						if(!$end)
						{
							$need_more_data = 1;
							break;
						}
						else
						{
							$value .= substr($this->buffer, $position);
							$part=array(
								'Type'=>'HeaderValue',
								'Value'=>$value,
								'Position'=>$this->offset + $this->buffer_position
							);
							$this->buffer_position = strlen($this->buffer);
							$this->state = MIME_PARSER_END;
							break;
						}
					}
				}
				break;
			case MIME_PARSER_BODY:
				if($this->mbox)
				{
					$add = 0;
					$append='';
					if($this->FindLineBreak($this->buffer_position, $break, $line_break))
					{
						$next = $line_break + strlen($break);
						$following = $next + strlen($break);
						if($following >= strlen($this->buffer)
						|| GetType($line=strpos($this->buffer, $break, $following))!='integer')
						{
							if(!$end)
							{
								$need_more_data = 1;
								break;
							}
						}
						$start = substr($this->buffer, $next, strlen($break.'From '));
						if(!strcmp($break.'From ', $start))
						{
							if($line_break == $this->buffer_position)
							{
								$part=array(
									'Type'=>'MessageEnd',
									'Position'=>$this->offset + $this->buffer_position
								);
								$this->buffer_position = $following;
								$this->state = MIME_PARSER_START;
								break;
							}
							else
								$add = strlen($break);
							$next = $line_break;
						}
						elseif(($indent = strspn($this->buffer, '>', $next)) > 0)
						{
							$start = substr($this->buffer, $next + $indent, strlen('From '));
							if(!strcmp('From ', $start))
							{
								$part=array(
									'Type'=>'BodyData',
									'Data'=>substr($this->buffer, $this->buffer_position, $next - $this->buffer_position),
									'Position'=>$this->offset + $this->buffer_position
								);
								$this->buffer_position = $next + 1;
								break;
							}
						}
					}
					else
					{
						if(!$end)
						{
							$need_more_data = 1;
							break;
						}
						$next = strlen($this->buffer);
						$append="\r\n";
					}
					if($next > $this->buffer_position)
					{
						$part=array(
							'Type'=>'BodyData',
							'Data'=>substr($this->buffer, $this->buffer_position, $next + $add - $this->buffer_position).$append,
							'Position'=>$this->offset + $this->buffer_position
						);
					}
					elseif($end)
					{
						$part=array(
							'Type'=>'MessageEnd',
							'Position'=>$this->offset + $this->buffer_position
						);
						$this->state = MIME_PARSER_END;
					}
					$this->buffer_position = $next;
				}
				else
				{
					if(strlen($this->buffer)-$this->buffer_position)
					{
						$data=substr($this->buffer, $this->buffer_position, strlen($this->buffer) - $this->buffer_position);
						$end_line = (!strcmp(substr($data,-1),"\n") || !strcmp(substr($data,-1),"\r"));
						if($end
						&& !$end_line)
						{
							$data.="\n";
							$end_line = 1;
						}
						$offset = $this->offset + $this->buffer_position;
						$this->buffer_position = strlen($this->buffer);
						$need_more_data = !$end;
						if(!$end_line)
						{
							if(GetType($line_break=strrpos($data, "\n"))=='integer'
							|| GetType($line_break=strrpos($data, "\r"))=='integer')
							{
								$line_break++;
								$this->buffer_position -= strlen($data) - $line_break;
								$data = substr($data, 0, $line_break);
							}
						}
						$part=array(
							'Type'=>'BodyData',
							'Data'=>$data,
							'Position'=>$offset
						);
					}
					else
					{
						if($end)
						{
							$part=array(
								'Type'=>'MessageEnd',
								'Position'=>$this->offset + $this->buffer_position
							);
							$this->state = MIME_PARSER_END;
						}
						else
							$need_more_data = 1;
					}
				}
				break;
			default:
				return($this->SetPositionedError($this->state.' is not a valid parser state', $this->buffer_position));
		}
		return(1);
	}

	Function QueueBodyParts()
	{
		for(;;)
		{
			if(!$this->body_parser->GetPart($part,$end))
				return($this->SetError($this->body_parser->error));
			if($end)
				return(1);
			if(!IsSet($part['Part']))
				$part['Part']=$this->headers['Boundary'];
			$this->parts[]=$part;
		}
	}

	Function ParseParameters($value, &$first, &$parameters, $return)
	{
		$first = strtolower(trim(strtok($value, ';')));
		$values = trim(strtok(''));
		$parameters = array();
		$return_value = '';
		while(strlen($values))
		{
			$parameter = trim(strtolower(strtok($values, '=')));
			$value = trim(strtok(';'));
			$l = strlen($value);
			if($l > 1
			&& !strcmp($value[0], '"')
			&& !strcmp($value[$l - 1], '"'))
				$value = substr($value, 1, $l - 2);
			$parameters[$parameter] = $value;
			if(!strcmp($parameter, $return))
				$return_value = $value;
			$values = trim(strtok(''));
		}
		return($return_value);
	}

	Function DecodePart($part)
	{
		switch($part['Type'])
		{
			case 'MessageStart':
				$this->headers=array();
				break;
			case 'HeaderName':
				if($this->decode_bodies)
					$this->current_header = strtolower($part['Name']);
				break;
			case 'HeaderValue':
				if($this->decode_headers)
				{
					$value = $part['Value'];
					$error = '';
					for($decoded_header = array(), $position = 0; $position<strlen($value); )
					{
						if(GetType($encoded=strpos($value,'=?', $position))!='integer')
						{
							if($position<strlen($value))
							{
								if(count($decoded_header))
									$decoded_header[count($decoded_header)-1]['Value'].=substr($value, $position);
								else
								{
									$decoded_header[]=array(
										'Value'=>substr($value, $position),
										'Encoding'=>'ASCII'
									);
								}
							}
							break;
						}
						$set = $encoded + 2;
						if(GetType($method=strpos($value,'?', $set))!='integer')
						{
							$error = 'invalid header encoding syntax '.$part['Value'];
							$error_position = $part['Position'] + $set;
							break;
						}
						$encoding=strtoupper(substr($value, $set, $method - $set));
						$method += 1;
						if(GetType($data=strpos($value,'?', $method))!='integer')
						{
							$error = 'invalid header encoding syntax '.$part['Value'];
							$error_position = $part['Position'] + $set;
							break;
						}
						$start = $data + 1;
						if(GetType($end=strpos($value,'?=', $start))!='integer')
						{
							$error = 'invalid header encoding syntax '.$part['Value'];
							$error_position = $part['Position'] + $start;
							break;
						}
						if($encoded > $position)
						{
							if(count($decoded_header))
								$decoded_header[count($decoded_header)-1]['Value'].=substr($value, $position, $encoded - $position);
							else
							{
								$decoded_header[]=array(
									'Value'=>substr($value, $position, $encoded - $position),
									'Encoding'=>'ASCII'
								);
							}
						}
						switch(strtolower(substr($value, $method, $data - $method)))
						{
							case 'q':
								if($end>$start)
								{
									for($decoded = '', $position = $start; $position < $end ; )
									{
										switch($value[$position])
										{
											case '=':
												$h = HexDec($hex = strtolower(substr($value, $position+1, 2)));
												if($end - $position < 3
												|| strcmp(sprintf('%02x', $h), $hex))
												{
													$warning = 'the header specified an invalid encoded character';
													$warning_position = $part['Position'] + $position + 1;
													if($this->ignore_syntax_errors)
													{
														$this->SetPositionedWarning($warning, $warning_position);
														$decoded .= '=';
														$position ++;
													}
													else
													{
														$error = $warning;
														$error_position = $warning_position;
														break 4;
													}
												}
												else
												{
													$decoded .= Chr($h);
													$position += 3;
												}
												break;
											case '_':
												$decoded .= ' ';
												$position++;
												break;
											default:
												$decoded .= $value[$position];
												$position++;
												break;
										}
									}
									if(count($decoded_header)
									&& (!strcmp($decoded_header[$last = count($decoded_header)-1]['Encoding'], 'ASCII'))
									|| !strcmp($decoded_header[$last]['Encoding'], $encoding))
									{
										$decoded_header[$last]['Value'].= $decoded;
										$decoded_header[$last]['Encoding']= $encoding;
									}
									else
									{
										$decoded_header[]=array(
											'Value'=>$decoded,
											'Encoding'=>$encoding
										);
									}
								}
								break;
							case 'b':
								$decoded=base64_decode(substr($value, $start, $end - $start));
								if($end <= $start
								|| GetType($decoded) != 'string'
								|| strlen($decoded) == 0)
								{
									$warning = 'the header specified an invalid base64 encoded text';
									$warning_position = $part['Position'] + $start;
									if($this->ignore_syntax_errors)
										$this->SetPositionedWarning($warning, $warning_position);
									else
									{
										$error = $warning;
										$error_position = $warning_position;
										break 2;
									}
								}
								if(count($decoded_header)
								&& (!strcmp($decoded_header[$last = count($decoded_header)-1]['Encoding'], 'ASCII'))
								|| !strcmp($decoded_header[$last]['Encoding'], $encoding))
								{
									$decoded_header[$last]['Value'].= $decoded;
									$decoded_header[$last]['Encoding']= $encoding;
								}
								else
								{
									$decoded_header[]=array(
										'Value'=>$decoded,
										'Encoding'=>$encoding
									);
								}
								break;
							default:
								$error = 'the header specified an unsupported encoding method';
								$error_position = $part['Position'] + $method;
								break 2;
						}
						$position = $end + 2;
					}
					if(strlen($error)==0
					&& count($decoded_header))
						$part['Decoded']=$decoded_header;
				}
				if($this->decode_bodies
				|| $this->decode_headers)
				{
					switch($this->current_header)
					{
						case 'content-type:':
							$boundary = $this->ParseParameters($part['Value'], $type, $parameters, 'boundary');
							$this->headers['Type'] = $type;
							if($this->decode_headers)
							{
								$part['MainValue'] = $type;
								$part['Parameters'] = $parameters;
							}
							if(!strcmp(strtok($type, '/'), 'multipart'))
							{
								$this->headers['Multipart'] = 1;
								if(strlen($boundary))
									$this->headers['Boundary'] = $boundary;
								else
									return($this->SetPositionedError('multipart content-type header does not specify the boundary parameter', $part['Position']));
							}
							break;
						case 'content-transfer-encoding:':
							switch($this->headers['Encoding']=strtolower(trim($part['Value'])))
							{
								case 'quoted-printable':
									$this->headers['QuotedPrintable'] = 1;
									break;
								case '7 bit':
								case '8 bit':
									if(!$this->SetPositionedWarning('"'.$this->headers['Encoding'].'" is an incorrect content transfer encoding type', $part['Position']))
										return(0);
								case '7bit':
								case '8bit':
								case 'binary':
									break;
								case 'base64':
									$this->headers['Base64']=1;
									break;
								default:
									if(!$this->SetPositionedWarning('decoding '.$this->headers['Encoding'].' encoded bodies is not yet supported', $part['Position']))
										return(0);
							}
							break;
					}
				}
				break;
			case 'BodyStart':
				if($this->decode_bodies
				&& IsSet($this->headers['Multipart']))
				{
					$this->body_parser_state = MIME_PARSER_BODY_START;
					$this->body_buffer = '';
					$this->body_buffer_position = 0;
				}
				break;
			case 'MessageEnd':
				if($this->decode_bodies
				&& IsSet($this->headers['Multipart'])
				&& $this->body_parser_state != MIME_PARSER_BODY_DONE)
				{
					if($this->body_parser_state != MIME_PARSER_BODY_DATA)
						return($this->SetPositionedError('incomplete message body part', $part['Position']));
					if(!$this->SetPositionedWarning('truncated message body part', $part['Position']))
						return(0);
				}
				break;
			case 'BodyData':
				if($this->decode_bodies)
				{
					if(strlen($this->body_buffer)==0)
					{
						$this->body_buffer = $part['Data'];
						$this->body_offset = $part['Position'];
					}
					else
						$this->body_buffer .= $part['Data'];
					if(IsSet($this->headers['Multipart']))
					{
						$boundary = '--'.$this->headers['Boundary'];
						switch($this->body_parser_state)
						{
							case MIME_PARSER_BODY_START:
								for($position = $this->body_buffer_position; ;)
								{
									if(!$this->FindBodyLineBreak($position, $break, $line_break))
										return(1);
									$next = $line_break + strlen($break);
									if(!strcmp(rtrim(substr($this->body_buffer, $position, $line_break - $position)), $boundary))
									{
										$part=array(
											'Type'=>'StartPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $next
										);
										$this->parts[]=$part;
										UnSet($this->body_parser);
										$this->body_parser = new mime_parser_class;
										$this->body_parser->decode_bodies = 1;
										$this->body_parser->decode_headers = $this->decode_headers;
										$this->body_parser->mbox = 0;
										$this->body_parser_state = MIME_PARSER_BODY_DATA;
										$this->body_buffer = substr($this->body_buffer, $next);
										$this->body_offset += $next;
										$this->body_buffer_position = 0;
										break;
									}
									else
										$position = $next;
								}
							case MIME_PARSER_BODY_DATA:
								for($position = $this->body_buffer_position; ;)
								{
									if(!$this->FindBodyLineBreak($position, $break, $line_break))
									{
										if($position > 0)
										{
											if(!$this->body_parser->Parse(substr($this->body_buffer, 0, $position), 0))
												return($this->SetError($this->body_parser->error));
											if(!$this->QueueBodyParts())
												return(0);
										}
										$this->body_buffer = substr($this->body_buffer, $position);
										$this->body_buffer_position = 0;
										$this->body_offset += $position;
										return(1);
									}
									$next = $line_break + strlen($break);
									$line = rtrim(substr($this->body_buffer, $position, $line_break - $position));
									if(!strcmp($line, $boundary.'--'))
									{
										if(!$this->body_parser->Parse(substr($this->body_buffer, 0, $position), 1))
											return($this->SetError($this->body_parser->error));
										if(!$this->QueueBodyParts())
											return(0);
										$part=array(
											'Type'=>'EndPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $position
										);
										$this->body_buffer = substr($this->body_buffer, $next);
										$this->body_buffer_position = 0;
										$this->body_offset += $next;
										$this->body_parser_state = MIME_PARSER_BODY_DONE;
										break 2;
									}
									elseif(!strcmp($line, $boundary))
									{
										if(!$this->body_parser->Parse(substr($this->body_buffer, 0, $position), 1))
											return($this->SetError($this->body_parser->error));
										if(!$this->QueueBodyParts())
											return(0);
										$part=array(
											'Type'=>'EndPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $position
										);
										$this->parts[] = $part;
										$part=array(
											'Type'=>'StartPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $next
										);
										$this->parts[] = $part;
										UnSet($this->body_parser);
										$this->body_parser = new mime_parser_class;
										$this->body_parser->decode_bodies = 1;
										$this->body_parser->decode_headers = $this->decode_headers;
										$this->body_parser->mbox = 0;
										$this->body_buffer = substr($this->body_buffer, $next);
										$this->body_buffer_position = 0;
										$this->body_offset += $next;
										$position=0;
										continue;
									}
									$position = $next;
								}
								break;
							case MIME_PARSER_BODY_DONE:
								return(1);
							default:
								return($this->SetPositionedError($this->state.' is not a valid body parser state', $this->body_buffer_position));
						}
					}
					elseif(IsSet($this->headers['QuotedPrintable']))
					{
						for($end = strlen($this->body_buffer), $decoded = '', $position = $this->body_buffer_position; $position < $end; )
						{
							if(GetType($equal = strpos($this->body_buffer, '=', $position))!='integer')
							{
								$decoded .= substr($this->body_buffer, $position);
								$position = $end;
								break;
							}
							$next = $equal + 1;
							switch($end - $equal)
							{
								case 1:
									$decoded .= substr($this->body_buffer, $position, $equal - $position);
									$position = $equal;
									break 2;
								case 2:
									$decoded .= substr($this->body_buffer, $position, $equal - $position);
									if(!strcmp($this->body_buffer[$next],"\n"))
										$position = $end;
									else
										$position = $equal;
									break 2;
							}
							if(!strcmp(substr($this->body_buffer, $next, 2), $break="\r\n")
							|| !strcmp($this->body_buffer[$next], $break="\n")
							|| !strcmp($this->body_buffer[$next], $break="\r"))
							{
								$decoded .= substr($this->body_buffer, $position, $equal - $position);
								$position = $next + strlen($break);
								continue;
							}
							$decoded .= substr($this->body_buffer, $position, $equal - $position);
							$h = HexDec($hex=strtolower(substr($this->body_buffer, $next, 2)));
							if(strcmp(sprintf('%02x', $h), $hex))
							{
								if(!$this->SetPositionedWarning('the body specified an invalid quoted-printable encoded character', $this->body_offset + $next))
									return(0);
								$decoded.='=';
								$position=$next;
							}
							else
							{
								$decoded .= Chr($h);
								$position = $equal + 3;
							}
						}
						if(strlen($decoded)==0)
						{
							$this->body_buffer_position = $position;
							return(1);
						}
						$part['Data'] = $decoded;
						$this->body_buffer = substr($this->body_buffer, $position);
						$this->body_buffer_position = 0;
						$this->body_offset += $position;
					}
					elseif(IsSet($this->headers['Base64']))
					{
						$part['Data'] = base64_decode($this->body_buffer_position ? substr($this->body_buffer,$this->body_buffer_position) : $this->body_buffer);
						$this->body_offset += strlen($this->body_buffer) - $this->body_buffer_position;
						$this->body_buffer_position = 0;
						$this->body_buffer = '';
					}
					else
					{
						$part['Data'] = substr($this->body_buffer, $this->body_buffer_position);
						$this->body_buffer_position = 0;
						$this->body_buffer = '';
					}
				}
				break;
		}
		$this->parts[]=$part;
		return(1);
	}

	Function DecodeStream($parameters, &$end_of_message, &$decoded)
	{
		$end_of_message = 1;
		$state = MIME_MESSAGE_START;
		for(;;)
		{
			if(!$this->GetPart($part, $end))
				return(0);
			if($end)
			{
				if(IsSet($parameters['File']))
				{
					$end_of_data = feof($this->file);
					if($end_of_data)
						break;
					$data = @fread($this->file, $this->message_buffer_length);
					if(GetType($data)!='string')
						return($this->SetPHPError('could not read the message file', $php_errormsg));
					$end_of_data = feof($this->file);
				}
				else
				{
					$end_of_data=($this->position>=strlen($parameters['Data']));
					if($end_of_data)
						break;
					$data = substr($parameters['Data'], $this->position, $this->message_buffer_length);
					$this->position += strlen($data);
					$end_of_data = ($this->position >= strlen($parameters['Data']));
				}
				if(!$this->Parse($data, $end_of_data))
					return(0);
				continue;
			}
			$type = $part['Type'];
			switch($state)
			{
				case MIME_MESSAGE_START:
					switch($type)
					{
						case 'MessageStart':
							$decoded=array(
								'Headers'=>array(),
								'Parts'=>array()
							);
							$end_of_message = 0;
							$state = MIME_MESSAGE_GET_HEADER_NAME;
							continue 3;
						case 'MessageEnd':
							return($this->SetPositionedWarning('incorrectly ended body part', $part['Position']));
					}
					break;

				case MIME_MESSAGE_GET_HEADER_NAME:
					switch($type)
					{
						case 'HeaderName':
							$header = strtolower($part['Name']);
							$state = MIME_MESSAGE_GET_HEADER_VALUE;
							continue 3;
						case 'BodyStart':
							$state = MIME_MESSAGE_GET_BODY;
							$part_number = 0;
							continue 3;
					}
					break;

				case MIME_MESSAGE_GET_HEADER_VALUE:
					switch($type)
					{
						case 'HeaderValue':
							$value = trim($part['Value']);
							if(!IsSet($decoded['Headers'][$header]))
							{
								$h = 0;
								$decoded['Headers'][$header]=$value;
								if($this->extract_addresses
								&& IsSet($this->address_headers[$header]))
									$decoded['HeaderPositions'][$header] = $part['Position'];
							}
							elseif(GetType($decoded['Headers'][$header])=='string')
							{
								$h = 1;
								$decoded['Headers'][$header]=array($decoded['Headers'][$header], $value);
							}
							else
							{
								$h = count($decoded['Headers'][$header]);
								$decoded['Headers'][$header][]=$value;
							}
							if(IsSet($part['Decoded'])
							&& (count($part['Decoded'])>1
							|| strcmp($part['Decoded'][0]['Encoding'],'ASCII')
							|| strcmp($value, trim($part['Decoded'][0]['Value']))))
							{
								$p=$part['Decoded'];
								$p[0]['Value']=ltrim($p[0]['Value']);
								$last=count($p)-1;
								$p[$last]['Value']=rtrim($p[$last]['Value']);
								$decoded['DecodedHeaders'][$header][$h]=$p;
							}
							switch($header)
							{
								case 'content-disposition:':
									$filename='filename';
									break;
								case 'content-type:':
									if(!IsSet($decoded['FileName']))
									{
										$filename='name';
										break;
									}
								default:
									$filename='';
									break;
							}
							if(strlen($filename))
							{
								if(IsSet($decoded['DecodedHeaders'][$header][$h])
								&& count($decoded['DecodedHeaders'][$header][$h]) == 1)
								{
									$value = $decoded['DecodedHeaders'][$header][$h][0]['Value'];
									$encoding = $decoded['DecodedHeaders'][$header][$h][0]['Encoding'];
								}
								else
									$encoding = '';
								$this->ParseStructuredHeader($value, $type, $header_parameters, $character_sets, $languages);
								if(IsSet($header_parameters[$filename]))
								{
									$decoded['FileName']=$header_parameters[$filename];
									if(IsSet($character_sets[$filename])
									&& strlen($character_sets[$filename]))
										$decoded['FileNameCharacterSet']=$character_sets[$filename];
									if(IsSet($character_sets['language'])
									&& strlen($character_sets['language']))
										$decoded['FileNameCharacterSet']=$character_sets[$filename];
									if(!IsSet($decoded['FileNameCharacterSet'])
									&& strlen($encoding))
										$decoded['FileNameCharacterSet'] = $encoding;
									if(!strcmp($header, 'content-disposition:'))
										$decoded['FileDisposition']=$type;
								}
							}
							$state = MIME_MESSAGE_GET_HEADER_NAME;
							continue 3;
					}
					break;

				case MIME_MESSAGE_GET_BODY:
					switch($type)
					{
						case 'BodyData':
							if(IsSet($parameters['SaveBody']))
							{
								if(!IsSet($decoded['BodyFile']))
								{
									$directory_separator=(defined('DIRECTORY_SEPARATOR') ? DIRECTORY_SEPARATOR : '/');
									$path = (strlen($parameters['SaveBody']) ? ($parameters['SaveBody'].(strcmp($parameters['SaveBody'][strlen($parameters['SaveBody'])-1], $directory_separator) ? $directory_separator : '')) : '').strval($this->body_part_number);
									if(!($this->body_file = fopen($path, 'wb')))
										return($this->SetPHPError('could not create file '.$path.' to save the message body part', $php_errormsg));
									$decoded['BodyFile'] = $path;
									$decoded['BodyPart'] = $this->body_part_number;
									$decoded['BodyLength'] = 0;
									$this->body_part_number++;
								}
								if(strlen($part['Data'])
								&& !fwrite($this->body_file, $part['Data']))
								{
									$this->SetPHPError('could not save the message body part to file '.$decoded['BodyFile'], $php_errormsg);
									fclose($this->body_file);
									@unlink($decoded['BodyFile']);
									return(0);
								}
							}
							elseif(IsSet($parameters['SkipBody'])
							&& $parameters['SkipBody'])
							{
								if(!IsSet($decoded['BodyPart']))
								{
									$decoded['BodyPart'] = $this->body_part_number;
									$decoded['BodyLength'] = 0;
									$this->body_part_number++;
								}
							}
							else
							{
								if(IsSet($decoded['Body']))
									$decoded['Body'].=$part['Data'];
								else
								{
									$decoded['Body']=$part['Data'];
									$decoded['BodyPart'] = $this->body_part_number;
									$decoded['BodyLength'] = 0;
									$this->body_part_number++;
								}
							}
							$decoded['BodyLength'] += strlen($part['Data']);
							continue 3;
						case 'StartPart':
							if(!$this->DecodeStream($parameters, $end_of_part, $decoded_part))
								return(0);
							$decoded['Parts'][$part_number]=$decoded_part;
							$part_number++;
							$state = MIME_MESSAGE_GET_BODY_PART;
							continue 3;
						case 'MessageEnd':
							if(IsSet($decoded['BodyFile']))
								fclose($this->body_file);
							return(1);
					}
					break;

				case MIME_MESSAGE_GET_BODY_PART:
					switch($type)
					{
						case 'EndPart':
							$state = MIME_MESSAGE_GET_BODY;
							continue 3;
					}
					break;
			}
			return($this->SetError('unexpected decoded message part type '.$type.' in state '.$state));
		}
		return(1);
	}

	/* Public functions */

	Function Parse($data, $end)
	{
		if(strlen($this->error))
			return(0);
		if($this->state==MIME_PARSER_END)
			return($this->SetError('the parser already reached the end'));
		$length = strlen($data);
		if($this->track_lines
		&& $length)
		{
			$line = $this->last_line;
			$position = 0;
			if($this->last_carriage_return)
			{
				if($data[0] == "\n")
					++$position;
				$this->lines[++$line] = $this->line_offset + $position;
				$this->last_carriage_return = 0;
			}
			while($position < $length)
			{
				$position += strcspn($data, "\r\n", $position) ;
				if($position >= $length)
					break;
				if($data[$position] == "\r")
				{
					++$position;
					if($position >= $length)
					{
						$this->last_carriage_return = 1;
						break;
					}
					if($data[$position] == "\n")
						++$position;
					$this->lines[++$line] = $this->line_offset + $position;
				}
				else
				{
					++$position;
					$this->lines[++$line] = $this->line_offset + $position;
				}
			}
			$this->last_line = $line;
			$this->line_offset += $length;
		}
		$this->buffer .= $data;
		do
		{
			Unset($part);
			if(!$this->ParsePart($end, $part, $need_more_data))
				return(0);
			if(IsSet($part)
			&& !$this->DecodePart($part))
				return(0);
		}
		while(!$need_more_data
		&& $this->state!=MIME_PARSER_END);
		if($end
		&& $this->state!=MIME_PARSER_END)
			return($this->SetError('reached a premature end of data'));
		if($this->buffer_position>0)
		{
			$this->offset += $this->buffer_position;
			$this->buffer = substr($this->buffer, $this->buffer_position);
			$this->buffer_position = 0;
		}
		return(1);
	}

	Function ParseFile($file)
	{
		if(strlen($this->error))
			return(0);
		if(!($stream = @fopen($file, 'r')))
			return($this->SetPHPError('Could not open the file '.$file, $php_errormsg));
		for($end = 0;!$end;)
		{
			if(!($data = @fread($stream, $this->message_buffer_length)))
			{
				$this->SetPHPError('Could not read the file '.$file, $php_errormsg);
				fclose($stream);
				return(0);
			}
			$end=feof($stream);
			if(!$this->Parse($data, $end))
			{
				fclose($stream);
				return(0);
			}
		}
		fclose($stream);
		return(1);
	}

	Function GetPart(&$part, &$end)
	{
		$end = ($this->part_position >= count($this->parts));
		if($end)
		{
			if($this->part_position)
			{
				$this->part_position = 0;
				$this->parts = array();
			}
		}
		else
		{
			$part = $this->parts[$this->part_position];
			$this->part_position ++;
		}
		return(1);
	}

/*
{metadocument}
	<function>
		<name>Decode</name>
		<type>BOOLEAN</type>
		<documentation>
			<purpose>Parse and decode message data and retrieve its structure.</purpose>
			<usage>Pass an array to the <argumentlink>
					<function>Decode</function>
					<argument>parameters</argument>
				</argumentlink>
				parameter to define whether the message data should be read and
				parsed from a file or a data string, as well additional parsing
				options. The <argumentlink>
					<function>Decode</function>
					<argument>decoded</argument>
				</argumentlink> returns the
				data structure of the parsed messages.</usage>
			<returnvalue>This function returns <booleanvalue>1</booleanvalue> if
				the specified message data is parsed successfully. Otherwise,
				check the variables <variablelink>error</variablelink> and
				<variablelink>error_position</variablelink> to determine what
				error occurred and the relevant message position.</returnvalue>
		</documentation>
		<argument>
			<name>parameters</name>
			<type>HASH</type>
			<documentation>
				<purpose>Associative array to specify parameters for the message
					data parsing and decoding operation. Here follows the list of
					supported parameters that should be used as indexes of the
					array:<paragraphbreak />
					<tt>File</tt><paragraphbreak />
					Name of the file from which the message data will be read. It
					may be the name of a file stream or a remote URL, as long as
					your PHP installation is configured to allow accessing remote
					files with the <tt>fopen()</tt> function.<paragraphbreak />
					<tt>Data</tt><paragraphbreak />
					String that specifies the message data. This should be used
					as alternative data source for passing data available in memory,
					like for instance messages stored in a database that was queried
					dynamically and the message data was fetched into a string
					variable.<paragraphbreak />
					<tt>SaveBody</tt><paragraphbreak />
					If this parameter is specified, the message body parts are saved
					to files. The path of the directory where the files are saved is
					defined by this parameter value. The information about the
					message body part structure is returned by the <argumentlink>
						<function>Decode</function>
						<argument>decoded</argument>
					</argumentlink> argument, but it just returns the body data part
					file name instead of the actual body data. It is recommended for
					retrieving messages larger than the available memory. The names
					of the body part files are numbers starting from
					<stringvalue>1</stringvalue>.<paragraphbreak />
					<tt>SkipBody</tt><paragraphbreak />
					If this parameter is set to <booleanvalue>1</booleanvalue>, the
					message body parts are skipped. This means the information about
					the message body part structure is returned by the <argumentlink>
						<function>Decode</function>
						<argument>decoded</argument>
					</argumentlink> but it does not return any body data. It is
					recommended just for parsing messages without the need to
					retrieve the message body part data.</purpose>
			</documentation>
		</argument>
		<argument>
			<name>decoded</name>
			<type>ARRAY</type>
			<out />
			<documentation>
				<purpose>Retrieve the structure of the parsed message headers and
					body data.<paragraphbreak />
					The argument is used to return by reference an array of message
					structure definitions. Each array entry refers to the structure
					of each message that is found and parsed successfully.<paragraphbreak />
					Each message entry consists of an associative array with several
					entries that describe the message structure. Here follows the
					list of message structure entries names and the meaning of the
					respective values:<paragraphbreak />
					<tt>Headers</tt><paragraphbreak />
					Associative array that returns the list of all the message
					headers. The array entries are the header names mapped to
					lower case, including the end colon. The array values are the
					respective header raw values without any start or trailing white
					spaces. Long header values split between multiple message lines
					are gathered in single string without line breaks. If an header
					with the same name appears more than once in the message, the
					respective value is an array with the values of all of the
					header occurrences.<paragraphbreak />
					<tt>DecodedHeaders</tt><paragraphbreak />
					Associative array that returns the list of all the encoded
					message headers when the
					<variablelink>decode_headers</variablelink> variable is set. The
					array entries are the header names mapped to lower case,
					including the end colon. The array values are also arrays that
					list only the occurrences of the header that originally were
					encoded. Each entry of the decoded header array contains more
					associative arrays that describe each part of the decoded
					header. Each of those associative arrays have an entry named
					<tt>Value</tt> that contains the decoded header part value, and
					another entry named <tt>Encoding</tt> that specifies the
					character set encoding of the value in upper case.<paragraphbreak />
					<tt>ExtractedAddresses</tt><paragraphbreak />
					If the <variablelink>extract_addresses</variablelink> variable
					is set to <booleanvalue>1</booleanvalue>, this entry is set to an
					associative array with the addresses found in the headers
					specified by the <variablelink>address_headers</variablelink>
					variable.<paragraphbreak />
					The parsed addresses found on each header are returned as an
					array with the format of the <link>
						<data>addresses</data>
						<url>rfc822_addresses_class.html#argument_ParseAddressList_addresses</url>
					</link> argument of the <link>
						<data>ParseAddressList</data>
						<url>rfc822_addresses_class.html#function_ParseAddressList</url>
					</link> function of the <link>
						<data>RFC 822 addresses</data>
						<url>rfc822_addresses_class.html</url>
					</link> class.<paragraphbreak />
					<tt>Parts</tt><paragraphbreak />
					If this message content type is multipart, this entry is an
					array that describes each of the parts contained in the message
					body. Each message part is described by an associative array
					with the same structure of a complete message
					definition.<paragraphbreak />
					<tt>Body</tt><paragraphbreak />
					String with the decoded data contained in the message body. If
					the <tt>SaveBody</tt> or <tt>SkipBody</tt> parameters are
					defined, the <tt>Body</tt> entry is not set.<paragraphbreak />
					<tt>BodyFile</tt><paragraphbreak />
					Name of the file to which the message body data was saved when
					the <tt>SaveBody</tt> parameter is defined.<paragraphbreak />
					<tt>BodyLength</tt><paragraphbreak />
					Length of the current decoded body part.<paragraphbreak />
					<tt>BodyPart</tt><paragraphbreak />
					Number of the current message body part.<paragraphbreak />
					<tt>FileName</tt><paragraphbreak />
					Name of the file for body parts composed from
					files.<paragraphbreak />
					<tt>FileNameCharacterSet</tt><paragraphbreak />
					Character set encoding for file parts with names that may
					include non-ASCII characters.<paragraphbreak />
					<tt>FileNameLanguage</tt><paragraphbreak />
					Language of file parts with names that may include non-ASCII
					characters.<paragraphbreak />
					<tt>FileDisposition</tt><paragraphbreak />
					Disposition of parts that files. It may be either
					<tt><stringvalue>inline</stringvalue></tt> for file parts to be
					displayed with the message, or
					<tt><stringvalue>attachment</stringvalue></tt> otherwise.</purpose>
			</documentation>
		</argument>
		<do>
{/metadocument}
*/
	Function Decode($parameters, &$decoded)
	{
		if(IsSet($parameters['File']))
		{
			if(!($this->file = @fopen($parameters['File'], 'r')))
				return($this->SetPHPError('could not open the message file to decode '.$parameters['File'], $php_errormsg));
		}
		elseif(IsSet($parameters['Data']))
			$this->position = 0;
		else
			return($this->SetError('it was not specified a valid message to decode'));
		$this->warnings = $decoded = array();
		$this->ResetParserState();
		$addresses = new rfc822_addresses_class;
		$addresses->ignore_syntax_errors = $this->ignore_syntax_errors;
		for($message = 0; ($success = $this->DecodeStream($parameters, $end_of_message, $decoded_message)) && !$end_of_message; $message++)
		{
			if($this->extract_addresses)
			{
				$headers = $decoded_message['Headers'];
				$positions = (IsSet($decoded_message['HeaderPositions']) ? $decoded_message['HeaderPositions'] : array());
				$th = count($headers);
				for(Reset($headers), $h = 0; $h<$th; Next($headers), ++$h)
				{
					$header = Key($headers);
					if(IsSet($this->address_headers[$header])
					&& $this->address_headers[$header])
					{
						$values = (GetType($headers[$header]) == 'array' ? $headers[$header] : array($headers[$header]));
						$p = (GetType($positions[$header]) == 'array' ? $positions[$header] : array($positions[$header]));
						$tv = count($values);
						for($v = 0; $v<$tv; ++$v)
						{
							if($addresses->ParseAddressList($values[$v], $a))
							{
								if($v==0)
									$decoded_message['ExtractedAddresses'][$header] = $a;
								else
								{
									$tl = count($a);
									for($l = 0; $l<$tl; ++$l)
										$decoded_message['ExtractedAddresses'][$header][] = $a[$l];
								}
								$tw = count($addresses->warnings);
								for($w = 0, Reset($addresses->warnings); $w < $tw; Next($addresses->warnings), $w++)
								{
									$warning = Key($addresses->warnings);
									if(!$this->SetPositionedWarning('Address extraction warning from header '.$header.' '.$addresses->warnings[$warning], $warning + $p[$v]))
										return(0);
								}
							}
							elseif(!$this->SetPositionedWarning('Address extraction error from header '.$header.' '.$addresses->error, $addresses->error_position + $p[$v]))
								return(0);
						}
					}
				}
				UnSet($decoded_message['HeaderPositions']);
			}
			$decoded[$message]=$decoded_message;
		}
		if(IsSet($parameters['File']))
			fclose($this->file);
		return($success);
	}
/*
{metadocument}
		</do>
	</function>
{/metadocument}
*/

	Function CopyAddresses($message, &$results, $header)
	{
		if(!IsSet($message['Headers'][$header]))
			return;
		if(!IsSet($message['ExtractedAddresses'][$header]))
		{
			$parser = new rfc822_addresses_class;
			$parser->ignore_syntax_errors = $this->ignore_syntax_errors;
			$values = (GetType($message['Headers'][$header]) == 'array' ? $message['Headers'][$header] : array($message['Headers'][$header]));
			$tv = count($values);
			$addresses = array();
			for($v = 0; $v<$tv; ++$v)
			{
				if($parser->ParseAddressList($values[$v], $a))
				{
					if($v==0)
						$addresses = $a;
					else
					{
						$tl = count($a);
						for($l = 0; $l<$tl; ++$l)
							$addresses[] = $a[$l];
					}
				}
			}
		}
		else
			$addresses = $message['ExtractedAddresses'][$header];
		if(count($addresses))
			$results[ucfirst(substr($header, 0, strlen($header) -1))] = $addresses;
	}

	Function ReadMessageBody($message, &$body, $prefix)
	{
		if(IsSet($message[$prefix]))
			$body = $message[$prefix];
		elseif(IsSet($message[$prefix.'File']))
		{
			$path = $message[$prefix.'File'];
			if(!($file = @fopen($path, 'rb')))
				return($this->SetPHPError('could not open the message body file '.$path, $php_errormsg));
			for($body = '', $end = 0;!$end;)
			{
				if(!($data = @fread($file, $this->message_buffer_length)))
				{
					$this->SetPHPError('Could not open the message body file '.$path, $php_errormsg);
					fclose($stream);
					return(0);
				}
				$end=feof($file);
				$body.=$data;
			}
			fclose($file);
		}
		else
			$body = '';
		return(1);
	}
/*
{metadocument}
	<function>
		<name>Analyze</name>
		<type>BOOLEAN</type>
		<documentation>
			<purpose>Analyze a parsed message to describe its contents.</purpose>
			<usage>Pass an array to the <argumentlink>
					<function>Analyze</function>
					<argument>message</argument>
				</argumentlink>
				parameter with the decoded message array structure returned by the
				<functionlink>Decode</functionlink> function. The <argumentlink>
					<function>Analyze</function>
					<argument>results</argument>
				</argumentlink> returns details about the type of message that was
				analyzed and its contents.</usage>
			<returnvalue>This function returns <booleanvalue>1</booleanvalue> if
				the specified message is analyzed successfully. Otherwise,
				check the variables <variablelink>error</variablelink> and
				<variablelink>error_position</variablelink> to determine what
				error occurred.</returnvalue>
		</documentation>
		<argument>
			<name>message</name>
			<type>HASH</type>
			<documentation>
				<purpose>Pass an associative array with the definition of an
					individual message returned by the <argumentlink>
					<function>Decode</function>
					<argument>decoded</argument>
				</argumentlink> argument of the
				<functionlink>Decode</functionlink> function..</purpose>
			</documentation>
		</argument>
		<argument>
			<name>results</name>
			<type>HASH</type>
			<out />
			<documentation>
				<purpose>Returns an associative array with the results of the
					analysis. Some types of entries are returned for all types of
					analyzed messages. Other entries are specific to each type of
					message.<paragraphbreak />
					<tt>Type</tt><paragraphbreak />
					Type of message that was analyzed. Currently it supports the
					types: <tt>binary</tt>, <tt>text</tt>, <tt>html</tt>,
					<tt>video</tt>, <tt>image</tt>, <tt>audio</tt>, <tt>zip</tt>,
					<tt>pdf</tt>, <tt>postscript</tt>, <tt>ms-word</tt>,
					<tt>ms-excel</tt>, <tt>ms-powerpoint</tt>, <tt>ms-tnef</tt>,
					<tt>odf-writer</tt>, <tt>signature</tt>, <tt>report-type</tt>,
					<tt>delivery-status</tt> and <tt>message</tt>.<paragraphbreak />
					<tt>SubType</tt><paragraphbreak />
					Name of the variant of the message type format.<paragraphbreak />
					<tt>Description</tt><paragraphbreak />
					Human readable description in English of the message type.<paragraphbreak />
					<paragraphbreak />
					<paragraphbreak />
					<paragraphbreak />
					<b>From message headers:</b><paragraphbreak />
					<tt>Encoding</tt><paragraphbreak />
					Character set encoding of the message part.<paragraphbreak />
					<tt>Subject</tt><paragraphbreak />
					The message subject.<paragraphbreak />
					<tt>SubjectEncoding</tt><paragraphbreak />
					Character set encoding of the message subject.<paragraphbreak />
					<tt>Date</tt><paragraphbreak />
					The message date.<paragraphbreak />
					<tt>From</tt><paragraphbreak />
					<tt>To</tt><paragraphbreak />
					<tt>Cc</tt><paragraphbreak />
					<tt>Bcc</tt><paragraphbreak />
					Array of e-mail addresses found in the <tt>From</tt>,
					<tt>To</tt>, <tt>Cc</tt>, <tt>Bcc</tt>.<paragraphbreak />
					Each of the entries consists of an associative array with an
					entry named <tt>address</tt> with the e-mail address and
					optionally another named <tt>name</tt> with the associated
					name.<paragraphbreak />
					<paragraphbreak />
					<paragraphbreak />
					<b>For content message parts:</b><paragraphbreak />
					<paragraphbreak />
					<tt>Data</tt><paragraphbreak />
					String of data of the message part.<paragraphbreak />
					<tt>DataFile</tt><paragraphbreak />
					File with data of the message part.<paragraphbreak />
					<tt>DataLength</tt><paragraphbreak />
					Length of the data of the message part.<paragraphbreak />
					<paragraphbreak />
					<paragraphbreak />
					<paragraphbreak />
					<b>For message with embedded files:</b><paragraphbreak />
					<paragraphbreak />
					<tt>FileName</tt><paragraphbreak />
					Original name of the file.<paragraphbreak />
					<tt>ContentID</tt><paragraphbreak />
					Content identifier of the file to be used in references from
					other message parts.<paragraphbreak />
					For instance, an HTML message may reference images embedded in
					the message using URLs that start with the
					<stringvalue>cid:</stringvalue> followed by the content
					identifier of the embedded image file part.<paragraphbreak />
					<tt>Disposition</tt><paragraphbreak />
					Information of whether the embedded file should be displayed
					inline when the message is presented, or it is an attachment
					file.<paragraphbreak />
					<paragraphbreak />
					<paragraphbreak />
					<b>For composite message:</b><paragraphbreak />
					<paragraphbreak />
					<tt>Attachments</tt><paragraphbreak />
					List of files attached to the message.<paragraphbreak />
					<tt>Alternative</tt><paragraphbreak />
					List of alternative message parts that can be displayed if the
					main message type is not supported by the program displaying
					the message.<paragraphbreak />
					<tt>Related</tt><paragraphbreak />
					List of message parts related with the main message type.<paragraphbreak />
					It may list for instance embedded images or CSS files related
					with an HTML message type.<paragraphbreak />
					<paragraphbreak />
					<paragraphbreak />
					<b>For bounced messages or other types of delivery status report
					messages:</b><paragraphbreak />
					<paragraphbreak />
					<tt>Recipients</tt><paragraphbreak />
					List of recipients of the original message.<paragraphbreak />
					Each entry contains an associative array that may have the
					entries: <tt>Recipient</tt> with the original recipient address,
					<tt>Action</tt> with the name action that triggered the delivery
					status report, <tt>Status</tt> with the code of the status of
					the message delivery.<paragraphbreak />
					<tt>Response</tt><paragraphbreak />
					Human readable response sent by the server the originated the
					report.<paragraphbreak />
					</purpose>
			</documentation>
		</argument>
		<do>
{/metadocument}
*/
	Function Analyze($message, &$results)
	{
		$results = array();
		if(!IsSet($message['Headers']['content-type:']))
			$content_type = 'text/plain';
		elseif(count($message['Headers']['content-type:']) == 1)
			$content_type = $message['Headers']['content-type:'];
		else
		{
			if(!$this->SetPositionedWarning('message contains multiple content-type headers', 0))
				return(0);
			$content_type = $message['Headers']['content-type:'][0];
		}
		$disposition = $this->ParseParameters($content_type, $content_type, $parameters, 'disposition');
		$type = $this->Tokenize($content_type, '/');
		$sub_type = $this->Tokenize(';');
		$copy_body = 1;
		$tolerate_unrecognized = 1;
		switch($type)
		{
			case 'multipart':
				$tolerate_unrecognized = 0;
				$copy_body = 0;
				$lp = count($message['Parts']);
				if($lp == 0)
					return($this->SetError($this->decode_bodies ? 'No parts were found in the '.$content_type.' part message' : 'It is not possible to analyze multipart messages without parsing the contained message parts. Please set the decode_bodies variable to 1 before parsing the message'));
				$parts = array();
				for($p = 0; $p < $lp; ++$p)
				{
					if(!$this->Analyze($message['Parts'][$p], $parts[$p]))
						return(0);
				}
				switch($sub_type)
				{
					case 'alternative':
						$p = $lp;
						$results = $parts[--$p];
						for(--$p ; $p >=0 ; --$p)
							$results['Alternative'][] = $parts[$p];
						break;

					case 'related':
						$results = $parts[0];
						for($p = 1; $p < $lp; ++$p)
							$results['Related'][] = $parts[$p];
						break;

					case 'mixed':
						$results = $parts[0];
						for($p = 1; $p < $lp; ++$p)
							$results['Attachments'][] = $parts[$p];
						break;

					case 'report':
						if(IsSet($parameters['report-type']))
						{
							switch($parameters['report-type'])
							{
								case 'delivery-status':
									for($p = 1; $p < $lp; ++$p)
									{
										if(!strcmp($parts[$p]['Type'], $parameters['report-type']))
										{
											$results = $parts[$p];
											break;
										}
									}
									if(!$this->ReadMessageBody($parts[0], $body, 'Data'))
										return(0);
									if(strlen($body))
										$results['Response'] = $body;
									break;
							}
						}
						$results['Type'] = $parameters['report-type'];
						break;

					case 'signed':
						if($lp != 2)
							return($this->SetError('this '.$content_type.' message does not have just 2 parts'));
						if(strcmp($parts[1]['Type'], 'signature'))
						{
							$this->SetErrorWithContact('this '.$content_type.' message does not contain a signature');
							$this->error = '';
						}
						$results = $parts[0];
						$results['Signature'] = $parts[1];
						break;

					case 'appledouble':
						if($lp != 2)
							return($this->SetError('this '.$content_type.' message does not have just 2 parts'));
						if(strcmp($parts[0]['Type'], 'applefile'))
						{
							$this->SetErrorWithContact('this '.$content_type.' message does not contain an Apple file header');
							$this->error = '';
						}
						$results = $parts[1];
						$results['AppleFileHeader'] = $parts[0];
						break;

				}
				break;
			case 'text':
				switch($sub_type)
				{
					case 'plain':
						$results['Type'] = 'text';
						$results['Description'] = 'Text message';
						break;
					case 'html':
						$results['Type'] = 'html';
						$results['Description'] = 'HTML message';
						break;
					default:
						$results['Type'] = $type;
						$results['SubType'] = $sub_type;
						$results['Description'] = 'Text file in the '.strtoupper($sub_type).' format';
						break;
				}
				break;
			case 'video':
				$results['Type'] = $type;
				$results['SubType'] = $sub_type;
				$results['Description'] = 'Video file in the '.strtoupper($sub_type).' format';
				break;
			case 'image':
				$results['Type'] = $type;
				$results['SubType'] = $sub_type;
				$results['Description'] = 'Image file in the '.strtoupper($sub_type).' format';
				break;
			case 'audio':
				$results['Type'] = $type;
				$results['SubType'] = $sub_type;
				$results['Description'] = 'Audio file in the '.strtoupper($sub_type).' format';
				break;
			case 'application':
				switch($sub_type)
				{
					case 'octet-stream':
					case 'x-msdownload':
						$results['Type'] = 'binary';
						$results['Description'] = 'Binary file';
						break;
					case 'pdf':
						$results['Type'] = $sub_type;
						$results['Description'] = 'Document in PDF format';
						break;
					case 'postscript':
						$results['Type'] = $sub_type;
						$results['Description'] = 'Document in Postscript format';
						break;
					case 'msword':
						$results['Type'] = 'ms-word';
						$results['Description'] = 'Word processing document in Microsoft Word format';
						break;
					case 'vnd.ms-powerpoint':
						$results['Type'] = 'ms-powerpoint';
						$results['Description'] = 'Presentation in Microsoft PowerPoint format';
						break;
					case 'vnd.ms-excel':
						$results['Type'] = 'ms-excel';
						$results['Description'] = 'Spreadsheet in Microsoft Excel format';
						break;
					case 'x-compressed':
						if(!IsSet($parameters['name'])
						|| GetType($dot = strpos($parameters['name'], '.'))!='integer'
						|| strcmp($extension = strtolower(substr($parameters['name'], $dot + 1)), 'zip'))
							break;
					case 'zip':
					case 'x-zip':
					case 'x-zip-compressed':
						$results['Type'] = 'zip';
						$results['Description'] = 'ZIP archive with compressed files';
						break;
					case 'ms-tnef':
						$results['Type'] = $sub_type;
						$results['Description'] = 'Microsoft Exchange data usually sent by Microsoft Outlook';
						break;
					case 'pgp-signature':
						$results['Type'] = 'signature';
						$results['SubType'] = $sub_type;
						$results['Description'] = 'Message signature for PGP';
						break;
					case 'x-pkcs7-signature':
					case 'pkcs7-signature':
						$results['Type'] = 'signature';
						$results['SubType'] = $sub_type;
						$results['Description'] = 'PKCS message signature';
						break;
					case 'vnd.oasis.opendocument.text':
						$results['Type'] = 'odf-writer';
						$results['Description'] = 'Word processing document in ODF text format used by OpenOffice Writer';
						break;
					case 'applefile':
						$results['Type'] = 'applefile';
						$results['Description'] = 'Apple file resource header';
						break;
				}
				break;
			case 'message':
				$tolerate_unrecognized = 0;
				switch($sub_type)
				{
					case 'delivery-status':
						$results['Type'] = $sub_type;
						$results['Description'] = 'Notification of the status of delivery of a message';
						if(!$this->ReadMessageBody($message, $body, 'Body'))
							return(0);
						if(($l = strlen($body)))
						{
							$position = 0;
							$this->ParseHeaderString($body, $position, $headers);
							$recipients = array();
							for(;$position<$l;)
							{
								$this->ParseHeaderString($body, $position, $headers);
								if(count($headers))
								{
									$r = count($recipients);
									if(IsSet($headers['action']))
										$recipients[$r]['Action'] = $headers['action'];
									if(IsSet($headers['status']))
										$recipients[$r]['Status'] = $headers['status'];
									if(IsSet($headers['original-recipient']))
									{
										strtok($headers['original-recipient'], ';');
										$recipients[$r]['Address'] = trim(strtok(''));
									}
									elseif(IsSet($headers['final-recipient']))
									{
										strtok($headers['final-recipient'], ';');
										$recipients[$r]['Address'] = trim(strtok(''));
									}
								}
							}
							$results['Recipients'] = $recipients;
						}
						$copy_body = 0;
						break;
					case 'rfc822':
						$results['Type'] = 'message';
						$results['Description'] = 'E-mail message';
						break;
				}
				break;
			default:
				$tolerate_unrecognized = 0;
				break;
		}
		if(!IsSet($results['Type']))
		{
			$this->SetErrorWithContact($content_type.' message parts are not yet recognized');
			$results['Type'] = $this->error;
			$this->error = '';
		}
		if(IsSet($parameters['charset']))
			$results['Encoding'] = strtolower($parameters['charset']);
		if(IsSet($message['Headers']['subject:']))
		{
			if(IsSet($message['DecodedHeaders']['subject:'])
			&& count($message['DecodedHeaders']['subject:']) == 1
			&& count($message['DecodedHeaders']['subject:'][0]) == 1)
			{
				$results['Subject'] = $message['DecodedHeaders']['subject:'][0][0]['Value'];
				$results['SubjectEncoding'] = strtolower($message['DecodedHeaders']['subject:'][0][0]['Encoding']);
			}
			else
				$results['Subject'] = $message['Headers']['subject:'];
		}
		if(IsSet($message['Headers']['date:']))
		{
			if(IsSet($message['DecodedHeaders']['date:'])
			&& count($message['DecodedHeaders']['date:']) == 1
			&& count($message['DecodedHeaders']['date:'][0]) == 1)
				$results['Date'] = $message['DecodedHeaders']['date:'][0][0]['Value'];
			else
				$results['Date'] = $message['Headers']['date:'];
		}
		$l = count($this->address_headers);
		for(Reset($this->address_headers), $h = 0; $h<$l; Next($this->address_headers), ++$h)
			$this->CopyAddresses($message, $results, Key($this->address_headers));
		if($copy_body)
		{
			if(IsSet($message['Body']))
				$results['Data'] = $message['Body'];
			elseif(IsSet($message['BodyFile']))
				$results['DataFile'] = $message['BodyFile'];
			elseif(IsSet($message['BodyLength']))
				$results['DataLength'] = $message['BodyLength'];
			if(IsSet($message['FileName']))
				$results['FileName'] = $message['FileName'];
			if(IsSet($message['FileDisposition']))
				$results['FileDisposition'] = $message['FileDisposition'];
			if(IsSet($message['Headers']['content-id:']))
			{
				$content_id = trim($message['Headers']['content-id:']);
				$l = strlen($content_id);
				if(!strcmp($content_id[0], '<')
				&& !strcmp($content_id[$l - 1], '>'))
					$results['ContentID'] = substr($content_id, 1, $l - 2);
			}
		}
		return(1);
	}
/*
{metadocument}
		</do>
	</function>
{/metadocument}
*/

/*
{metadocument}
	<function>
		<name>GetPositionLine</name>
		<type>BOOLEAN</type>
		<documentation>
			<purpose>Get the line number of the document that corresponds to a
				given position.</purpose>
			<usage>Pass the document offset number as the position to be
				located. Make sure the <variablelink>track_lines</variablelink>
				variable is set to <booleanvalue>1</booleanvalue> before parsing
				the document.</usage>
			<returnvalue>This function returns <booleanvalue>1</booleanvalue> if
				 the <variablelink>track_lines</variablelink> variable is set to
				<booleanvalue>1</booleanvalue> and it was given a valid positive
				position number that does not exceed the position of the last
				parsed document line.</returnvalue>
		</documentation>
		<argument>
			<name>position</name>
			<type>INTEGER</type>
			<documentation>
				<purpose>Position of the line to be located.</purpose>
			</documentation>
		</argument>
		<argument>
			<name>line</name>
			<type>INTEGER</type>
			<out />
			<documentation>
				<purpose>Returns the number of the line that corresponds to the
					given document position.</purpose>
			</documentation>
		</argument>
		<argument>
			<name>column</name>
			<type>INTEGER</type>
			<out />
			<documentation>
				<purpose>Returns the number of the column of the line that
					corresponds to the given document position.</purpose>
			</documentation>
		</argument>
		<do>
{/metadocument}
*/
	Function GetPositionLine($position, &$line, &$column)
	{
		if(!$this->track_lines)
			return($this->SetPositionedError('line positions are not being tracked', $position));
		$bottom = 0;
		$top = count($this->lines) - 1;
		if($position < 0)
			return($this->SetPositionedError('it was not specified a valid position', $position));
		for(;;)
		{
			$line = intval(($bottom + $top) / 2);
			$current = $this->lines[$line];
			if($current < $position)
				$bottom = $line + 1;
			elseif($current > $position)
				$top = $line - 1;
			else
				break;
			if($top < $bottom)
			{
				$line = $top;
				break;
			}
		}
		$column = $position - $this->lines[$line] + 1;
		++$line;
		return(1);
	}
/*
{metadocument}
		</do>
	</function>
{/metadocument}
*/
};

/*

{metadocument}
</class>
{/metadocument}

*/

?>