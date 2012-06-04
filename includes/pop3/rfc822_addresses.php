<?php
/*
 * rfc822_addresses.php
 *
 * @(#) $Id: rfc822_addresses.php,v 1.13 2010/04/08 20:09:23 mlemos Exp $
 *
 */

/*
{metadocument}<?xml version="1.0" encoding="ISO-8859-1" ?>
<class>

	<package>net.manuellemos.mimeparser</package>

	<version>@(#) $Id: rfc822_addresses.php,v 1.13 2010/04/08 20:09:23 mlemos Exp $</version>
	<copyright>Copyright © (C) Manuel Lemos 2006 - 2008</copyright>
	<title>RFC 822 e-mail addresses parser</title>
	<author>Manuel Lemos</author>
	<authoraddress>mlemos-at-acm.org</authoraddress>

	<documentation>
		<idiom>en</idiom>
		<purpose>Parse e-mail addresses from headers of <link>
				<url>http://www.ietf.org/rfc/rfc822.txt</url>
				<data>RFC 822</data>
			</link> compliant e-mail messages.</purpose>
		<usage>Use the function <functionlink>ParseAddressList</functionlink>
			function to retrieve the list of e-mail addresses contained in
			e-mail message headers like <tt>From</tt>, <tt>To</tt>, <tt>Cc</tt>
			or <tt>Bcc</tt>.</usage>
	</documentation>

{/metadocument}
*/

class rfc822_addresses_class
{
	/* Private variables */

	var $v = '';

	/* Public variables */

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
	var $error = '';

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
		<name>ignore_syntax_errors</name>
		<type>BOOLEAN</type>
		<value>1</value>
		<documentation>
			<purpose>Specify whether the class should ignore syntax errors in
				malformed addresses.</purpose>
			<usage>Set this variable to <booleanvalue>0</booleanvalue> if it is
				necessary to verify whether message data may be corrupted due to
				to eventual bugs in the program that generated the
				message.<paragraphbreak />
				Currently the class only ignores some types of syntax errors.
				Other syntax errors may still cause the
				<functionlink>ParseAddressList</functionlink> to fail.</usage>
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

	/* Private functions */

	Function SetError($error)
	{
		$this->error = $error;
		return(0);
	}

	Function SetPositionedError($error, $position)
	{
		$this->error_position = $position;
		return($this->SetError($error));
	}

	Function SetWarning($warning, $position)
	{
		$this->warnings[$position]=$warning;
		return(1);
	}

	Function SetPositionedWarning($error, $position)
	{
		if(!$this->ignore_syntax_errors)
			return($this->SetPositionedError($error, $position));
		return($this->SetWarning($error, $position));
	}

	Function QDecode($p, &$value, &$encoding)
	{
		$encoding = $charset = null;
		$s = 0;
		$decoded = '';
		$l = strlen($value);
		while($s < $l)
		{
			if(GetType($q = strpos($value, '=?', $s)) != 'integer')
			{
				if($s == 0)
					return(1);
				if($s < $l)
					$decoded .= substr($value, $s);
				break;
			}
			if($s < $q)
				$decoded .= substr($value, $s, $q - $s);
			$q += 2;
			if(GetType($c = strpos($value, '?', $q)) != 'integer'
			|| $q == $c)
				return($this->SetPositionedWarning('invalid Q-encoding character set', $p + $q));
			if(IsSet($charset))
			{
				$another_charset = strtolower(substr($value, $q, $c - $q));
				if(strcmp($charset, $another_charset)
				&& strcmp($another_charset, 'ascii'))
					return($this->SetWarning('it is not possible to decode an encoded value using mixed character sets into a single value', $p + $q));
			}
			else
			{
				$charset = strtolower(substr($value, $q, $c - $q));
				if(!strcmp($charset, 'ascii'))
					$charset = null;
			}
			++$c;
			if(GetType($t = strpos($value, '?', $c)) != 'integer'
			|| $c==$t)
				return($this->SetPositionedWarning('invalid Q-encoding type', $p + $c));
			$type = strtolower(substr($value, $c, $t - $c));
			++$t;
			if(GetType($e = strpos($value, '?=', $t)) != 'integer')
				return($this->SetPositionedWarning('invalid Q-encoding encoded data', $p + $e));
			switch($type)
			{
				case 'q':
					for($s = $t; $s<$e;)
					{
						switch($b = $value[$s])
						{
							case '=':
								$h = HexDec($hex = strtolower(substr($value, $s + 1, 2)));
								if($s + 3 > $e
								|| strcmp(sprintf('%02x', $h), $hex))
									return($this->SetPositionedWarning('invalid Q-encoding q encoded data', $p + $s));
								$decoded .= chr($h);
								$s += 3;
								break;

							case '_':
								$decoded .= ' ';
								++$s;
								break;

							default:
								$decoded .= $b;
								++$s;
						}
					}
					break;

				case 'b':
					if($e <= $t
					|| strlen($binary = base64_decode($data = substr($value, $t, $e - $t))) == 0
					|| GetType($binary) != 'string')
						return($this->SetPositionedWarning('invalid Q-encoding b encoded data', $p + $t));
					$decoded .= $binary;
					$s = $e;
					break;

				default:
					return($this->SetPositionedWarning('Q-encoding '.$type.' is not yet supported', $p + $c));
			}
			$s += 2;
		}
		$value = $decoded;
		$encoding = $charset;
		return(1);
	}

	Function ParseCText(&$p, &$c_text)
	{
		$c_text = null;
		$v = $this->v;
		if($p<strlen($v)
		&& GetType(strchr("\t\r\n ()\\\0", $c = $v[$p])) != 'string'
		&& Ord($c)<128)
		{
			$c_text = $c;
			++$p;
		}
		return(1);
	}

	Function ParseQText(&$p, &$q_text)
	{
		$q_text = null;
		$v = $this->v;
		if($p>strlen($v)
		|| GetType(strchr("\t\r\n \"\\\0", $c = $v[$p])) == 'string')
			return(1);
		if(Ord($c) >= 128)
		{
			if(!$this->ignore_syntax_errors)
				return(1);
			$this->SetPositionedWarning('it was used an unencoded 8 bit character', $p);
		}
		$q_text = $c;
		++$p;
		return(1);
	}

	Function ParseQuotedPair(&$p, &$quoted_pair)
	{
		$quoted_pair = null;
		$v = $this->v;
		$l = strlen($v);
		if($p+1 < $l
		&& !strcmp($v[$p], '\\')
		&& GetType(strchr("\r\n\0", $c = $v[$p + 1])) != 'string'
		&& Ord($c)<128)
		{
			$quoted_pair = $c;
			$p += 2;
		}
		return(1);
	}

	Function ParseCContent(&$p, &$c_content)
	{
		$c_content = null;
		$c = $p;
		if(!$this->ParseQuotedPair($c, $content))
			return(0);
		if(!IsSet($content))
		{
			if(!$this->ParseCText($c, $content))
				return(0);
			if(!IsSet($content))
			{
				if(!$this->ParseComment($c, $content))
					return(0);
				if(!IsSet($content))
					return(1);
			}
		}
		$c_content = $content;
		$p = $c;
		return(1);
	}

	Function SkipWhiteSpace(&$p)
	{
		$v = $this->v;
		$l = strlen($v);
		for(;$p<$l; ++$p)
		{
			switch($v[$p])
			{
				case ' ':
				case "\n":
				case "\r":
				case "\t":
					break;
				default:
					return(1);
			}
		}
		return(1);
	}

	Function ParseComment(&$p, &$comment)
	{
		$comment = null;
		$v = $this->v;
		$l = strlen($v);
		$c = $p;
		if($c >= $l
		|| strcmp($v[$c], '('))
			return(1);
		++$c;
		for(; $c < $l;)
		{
			if(!$this->SkipWhiteSpace($c))
				return(0);
			if(!$this->ParseCContent($c, $c_content))
				return(0);
			if(!IsSet($c_content))
				break;
		}
		if(!$this->SkipWhiteSpace($c))
			return(0);
		if($c >= $l
		|| strcmp($v[$c], ')'))
			return(1);
		++$c;
		$comment = substr($v, $p, $c - $p);
		$p = $c;
		return(1);
	}

	Function SkipCommentGetWhiteSpace(&$p, &$space)
	{
		$v = $this->v;
		$l = strlen($v);
		for($space = '';$p<$l;)
		{
			switch($w = $v[$p])
			{
				case ' ':
				case "\n":
				case "\r":
				case "\t":
					++$p;
					$space .= $w;
					break;
				case '(':
					if(!$this->ParseComment($p, $comment))
						return(0);
				default:
					return(1);
			}
		}
		return(1);
	}

	Function SkipCommentWhiteSpace(&$p)
	{
		$v = $this->v;
		$l = strlen($v);
		for(;$p<$l;)
		{
			switch($w = $v[$p])
			{
				case ' ':
				case "\n":
				case "\r":
				case "\t":
					++$p;
					break;
				case '(':
					if(!$this->ParseComment($p, $comment))
						return(0);
				default:
					return(1);
			}
		}
		return(1);
	}

	Function ParseQContent(&$p, &$q_content)
	{
		$q_content = null;
		$q = $p;
		if(!$this->ParseQuotedPair($q, $content))
			return(0);
		if(!IsSet($content))
		{
			if(!$this->ParseQText($q, $content))
				return(0);
			if(!IsSet($content))
				return(1);
		}
		$q_content = $content;
		$p = $q;
		return(1);
	}

	Function ParseAtom(&$p, &$atom, $dot)
	{
		$atom = null;
		$v = $this->v;
		$l = strlen($v);
		$a = $p;
		if(!$this->SkipCommentGetWhiteSpace($a, $space))
			return(0);
		$match = '/^([-'.($dot ? '.' : '').'A-Za-z0-9!#$&\'*+\\/=?^_{|}~]+)/';
		for($s = $a;$a < $l;)
		{
			if(preg_match($match, substr($this->v, $a), $m))
				$a += strlen($m[1]);
			elseif(Ord($v[$a]) < 128)
				break;
			elseif(!$this->SetPositionedWarning('it was used an unencoded 8 bit character', $a))
				return(0);
			else
				++$a;
		}
		if($s == $a)
			return(1);
		$atom = $space.substr($this->v, $s, $a - $s);
		if(!$this->SkipCommentGetWhiteSpace($a, $space))
			return(0);
		$atom .= $space;
		$p = $a;
		return(1);
	}

	Function ParseQuotedString(&$p, &$quoted_string)
	{
		$quoted_string = null;
		$v = $this->v;
		$l = strlen($v);
		$s = $p;
		if(!$this->SkipCommentWhiteSpace($s))
			return(0);
		if($s >= $l
		|| strcmp($v[$s], '"'))
			return(1);
		++$s;
		for($string = '';$s < $l;)
		{
			$w = $s;
			if(!$this->SkipWhiteSpace($s))
				return(0);
			if($w != $s)
				$string .= substr($v, $w, $s - $w);
			if(!$this->ParseQContent($s, $q_content))
				return(0);
			if(!IsSet($q_content))
				break;
			$string .= $q_content;
		}
			$w = $s;
		if(!$this->SkipWhiteSpace($s))
			return(0);
		if($w != $s)
			$string .= substr($v, $w, $s - $w);
		if($s >= $l
		|| strcmp($v[$s], '"'))
			return(1);
		++$s;
		if(!$this->SkipCommentWhiteSpace($s))
			return(0);
		$quoted_string = $string;
		$p = $s;
		return(1);
	}

	Function ParseWord(&$p, &$word)
	{
		$word = null;
		if(!$this->ParseQuotedString($p, $word))
			return(0);
		if(IsSet($word))
			return(1);
		if(!$this->ParseAtom($p, $word, 0))
			return(0);
		return(1);
	}

	Function ParseObsPhrase(&$p, &$obs_phrase)
	{
		$obs_phrase = null;
		$v = $this->v;
		$l = strlen($v);
		$ph = $p;
		if(!$this->ParseWord($ph, $word))
			return(0);
		$string = $word;
		for(;;)
		{
			if(!$this->ParseWord($ph, $word))
				return(0);
			if(IsSet($word))
			{
				$string .= $word;
				continue;
			}
			$w = $ph;
			if(!$this->SkipCommentGetWhiteSpace($ph, $space))
				return(0);
			if($w != $ph)
			{
				$string .= $space;
				continue;
			}
			if($ph >= $l
			|| strcmp($v[$ph], '.'))
				break;
			$string .= '.';
			++$ph;
		}
		$obs_phrase = $string;
		$p = $ph;
		return(1);
	}

	Function ParsePhrase(&$p, &$phrase)
	{
		$phrase = null;
		if(!$this->ParseObsPhrase($p, $phrase))
			return(0);
		if(IsSet($phrase))
			return(1);
		$ph = $p;
		if(!$this->ParseWord($ph, $word))
			return(0);
		$string = $word;
		for(;;)
		{
			if(!$this->ParseWord($ph, $word))
				return(0);
			if(!IsSet($word))
				break;
			$string .= $word;
		}
		$phrase = $string;
		$p = $ph;
		return(1);
	}

	Function ParseAddrSpec(&$p, &$addr_spec)
	{
		$addr_spec = null;
		$v = $this->v;
		$l = strlen($v);
		$a = $p;
		if(!$this->ParseQuotedString($a, $local_part))
			return(0);
		if(!IsSet($local_part))
		{
			if(!$this->ParseAtom($a, $local_part, 1))
				return(0);
			$local_part = trim($local_part);
		}
		if($a >= $l
		|| strcmp($v[$a], '@'))
			return(1);
		++$a;
		if(!$this->ParseAtom($a, $domain, 1))
			return(0);
		if(!IsSet($domain))
			return(1);
		$addr_spec = $local_part.'@'.trim($domain);
		$p = $a;
		return(1);
	}

	Function ParseAngleAddr(&$p, &$addr)
	{
		$addr = null;
		$v = $this->v;
		$l = strlen($v);
		$a = $p;
		if(!$this->SkipCommentWhiteSpace($a))
			return(0);
		if($a >= $l
		|| strcmp($v[$a], '<'))
			return(1);
		++$a;
		if(!$this->ParseAddrSpec($a, $addr_spec))
			return(0);
		if($a >= $l
		|| strcmp($v[$a], '>'))
			return(1);
		++$a;
		if(!$this->SkipCommentWhiteSpace($a))
			return(0);
		$addr = $addr_spec;
		$p = $a;
		return(1);
	}

	Function ParseName(&$p, &$address)
	{
		$address = null;
		$a = $p;
		if(!$this->ParsePhrase($a, $display_name))
			return(0);
		if(IsSet($display_name))
		{
			if(!$this->QDecode($p, $display_name, $encoding))
				return(0);
			$address['name'] = trim($display_name);
			if(IsSet($encoding))
				$address['encoding'] = $encoding;
		}
		$p = $a;
		return(1);
	}

	Function ParseNameAddr(&$p, &$address)
	{
		$address = null;
		$a = $p;
		if(!$this->ParsePhrase($a, $display_name))
			return(0);
		if(!$this->ParseAngleAddr($a, $addr))
			return(0);
		if(!IsSet($addr))
			return(1);
		$address = array('address'=>$addr);
		if(IsSet($display_name))
		{
			if(!$this->QDecode($p, $display_name, $encoding))
				return(0);
			$address['name'] = trim($display_name);
			if(IsSet($encoding))
				$address['encoding'] = $encoding;
		}
		$p = $a;
		return(1);
	}

	Function ParseAddrNameAddr(&$p, &$address)
	{
		$address = null;
		$a = $p;
		if(!$this->ParseAddrSpec($a, $display_name))
			return(0);
		if(!IsSet($display_name))
			return(1);
		if(!$this->ParseAngleAddr($a, $addr))
			return(0);
		if(!IsSet($addr))
			return(1);
		if(!$this->QDecode($p, $display_name, $encoding))
			return(0);
		$address = array(
			'address'=>$addr,
			'name' => trim($display_name)
		);
		if(IsSet($encoding))
			$address['encoding'] = $encoding;
		$p = $a;
		return(1);
	}

	Function ParseMailbox(&$p, &$address)
	{
		$address = null;
		if($this->ignore_syntax_errors)
		{
			$a = $p;
			if(!$this->ParseAddrNameAddr($p, $address))
				return(0);
			if(IsSet($address))
				return($this->SetPositionedWarning('it was specified an unquoted address as name', $a));
		}
		if(!$this->ParseNameAddr($p, $address))
			return(0);
		if(IsSet($address))
			return(1);
		if(!$this->ParseAddrSpec($p, $addr_spec))
			return(0);
		if(IsSet($addr_spec))
		{
			$address = array('address'=>$addr_spec);
			return(1);
		}
		$a = $p;
		if($this->ignore_syntax_errors
		&& $this->ParseName($p, $address)
		&& IsSet($address))
			return($this->SetPositionedWarning('it was specified a name without an address', $a));
		return(1);
	}

	Function ParseMailboxGroup(&$p, &$mailbox_group)
	{
		$v = $this->v;
		$l = strlen($v);
		$g = $p;
		if(!$this->ParseMailbox($g, $address))
			return(0);
		if(!IsSet($address))
			return(1);
		$addresses = array($address);
		for(;$g < $l;)
		{
			if(strcmp($v[$g], ','))
				break;
			++$g;
			if(!$this->ParseMailbox($g, $address))
				return(0);
			if(!IsSet($address))
				return(1);
			$addresses[] = $address;
		}
		$mailbox_group = $addresses;
		$p = $g;
		return(1);
	}

	Function ParseGroup(&$p, &$address)
	{
		$address = null;
		$v = $this->v;
		$l = strlen($v);
		$g = $p;
		if(!$this->ParsePhrase($g, $display_name))
			return(0);
		if(!IsSet($display_name)
		|| $g >= $l
		|| strcmp($v[$g], ':'))
			return(1);
		++$g;
		if(!$this->ParseMailboxGroup($g, $mailbox_group))
			return(0);
		if(!IsSet($mailbox_group))
		{
			if(!$this->SkipCommentWhiteSpace($g))
				return(0);
			$mailbox_group = array();
		}
		if($g >= $l
		|| strcmp($v[$g], ';'))
			return(1);
		$c = ++$g;
		if($this->SkipCommentWhiteSpace($g)
		&& $g > $c
		&& !$this->SetPositionedWarning('it were used invalid comments after a group of addresses', $c))
			return(0);
		if(!$this->QDecode($p, $display_name, $encoding))
			return(0);
		$address = array(
			'name'=>$display_name,
			'group'=>$mailbox_group
		);
		if(IsSet($encoding))
			$address['encoding'] = $encoding;
		$p = $g;
		return(1);
	}

	Function ParseAddress(&$p, &$address)
	{
		$address = null;
		if(!$this->ParseGroup($p, $address))
			return(0);
		if(!IsSet($address))
		{
			if(!$this->ParseMailbox($p, $address))
				return(0);
		}
		return(1);
	}

	/* Public functions */

/*
{metadocument}
	<function>
		<name>ParseAddressList</name>
		<type>BOOLEAN</type>
		<documentation>
			<purpose>Parse and extract e-mail addresses eventually from headers
				of an e-mail message.</purpose>
			<usage>Pass a string value with a list of e-mail addresses to the
				<argumentlink>
					<function>ParseAddressList</function>
					<argument>value</argument>
				</argumentlink>. The <argumentlink>
					<function>ParseAddressList</function>
					<argument>addresses</argument>
				</argumentlink> returns the list of e-mail addresses found.</usage>
			<returnvalue>This function returns <booleanvalue>1</booleanvalue> if
				the specified value is parsed successfully. Otherwise,
				check the variables <variablelink>error</variablelink> and
				<variablelink>error_position</variablelink> to determine what
				error occurred and the relevant value position.</returnvalue>
		</documentation>
		<argument>
			<name>value</name>
			<type>STRING</type>
			<documentation>
				<purpose>String with a list of e-mail addresses to parse.</purpose>
			</documentation>
		</argument>
		<argument>
			<name>addresses</name>
			<type>ARRAY</type>
			<out />
			<documentation>
				<purpose>Return the list of parsed e-mail addresses.
					Each entry in the list is an associative array.<paragraphbreak />
					For normal addresses, this associative array has the entry
					<stringvalue>address</stringvalue> set to the e-mail address.
					If the address has an associated name, it is stored in the
					entry <stringvalue>name</stringvalue>.<paragraphbreak />
					For address groups, there is the entry
					<stringvalue>name</stringvalue>.
					The group addresses list are stored in the entry
					<stringvalue>group</stringvalue> as an array. The structure of
					the group addresses list array is the same as this addresses
					list array argument.</purpose>
			</documentation>
		</argument>
		<do>
{/metadocument}
*/
	Function ParseAddressList($value, &$addresses)
	{
		$this->warnings = array();
		$addresses = array();
		$this->v = $v = $value;
		$l = strlen($v);
		$p = 0;
		if(!$this->ParseAddress($p, $address))
			return(0);
		if(!IsSet($address))
			return($this->SetPositionedError('it was not specified a valid address', $p));
		$addresses[] = $address;
		while($p < $l)
		{
			if(strcmp($v[$p], ',')
			&& !$this->SetPositionedWarning('multiple addresses must be separated by commas: ', $p))
				return(0);
			++$p;
			if(!$this->ParseAddress($p, $address))
				return(0);
			if(!IsSet($address))
				return($this->SetPositionedError('it was not specified a valid address after comma', $p));
			$addresses[] = $address;
		}
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