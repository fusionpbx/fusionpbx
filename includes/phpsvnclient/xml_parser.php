<?php
/*
 * xml_parser.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/xmlparser/xml_parser.php,v 1.19 2006/11/22 01:25:05 mlemos Exp $
 *
 */

/*
 * Parser error numbers:
 *
 * 1 - Could not create the XML parser
 * 2 - Could not parse data
 * 3 - Could not read from input stream
 *
 */

$xml_parser_handlers=array();

Function xml_parser_start_element_handler($parser,$name,$attrs)
{
  global $xml_parser_handlers;

	if(!strcmp($xml_parser_handlers[$parser]->error,""))
		$xml_parser_handlers[$parser]->StartElement($xml_parser_handlers[$parser],$name,$attrs);
}

Function xml_parser_end_element_handler($parser,$name)
{
  global $xml_parser_handlers;

	if(!strcmp($xml_parser_handlers[$parser]->error,""))
		$xml_parser_handlers[$parser]->EndElement($xml_parser_handlers[$parser],$name);
}

Function xml_parser_character_data_handler($parser,$data)
{
  global $xml_parser_handlers;

	if(!strcmp($xml_parser_handlers[$parser]->error,""))
		$xml_parser_handlers[$parser]->CharacterData($xml_parser_handlers[$parser],$data);
}

class xml_parser_handler_class
{
	var $xml_parser;
	var $error_number=0;
	var $error="";
	var $error_code=0;
	var $error_line,$error_column,$error_byte_index;
	var $structure=array();
	var $positions=array();
	var $path="";
	var $store_positions=0;
	var $simplified_xml=0;
	var $fail_on_non_simplified_xml=0;

	Function SetError(&$object,$error_number,$error)
	{
		$object->error_number=$error_number;
		$object->error=$error;
		$object->error_line=xml_get_current_line_number($object->xml_parser);
		$object->error_column=xml_get_current_column_number($object->xml_parser);
		$object->error_byte_index=xml_get_current_byte_index($object->xml_parser);
	}

	Function SetElementData(&$object,$path,&$data)
	{
		$object->structure[$path]=$data;
		if($object->store_positions)
		{
			$object->positions[$path]=array(
				"Line"=>xml_get_current_line_number($object->xml_parser),
				"Column"=>xml_get_current_column_number($object->xml_parser),
				"Byte"=>xml_get_current_byte_index($object->xml_parser)
			);
		}
	}

	Function StartElement(&$object,$name,&$attrs)
	{
		if(strcmp($this->path,""))
		{
			$element=$object->structure[$this->path]["Elements"];
			$object->structure[$this->path]["Elements"]++;
			$this->path.=",$element";
		}
		else
		{
			$element=0;
			$this->path="0";
		}
		$data=array(
			"Tag"=>$name,
			"Elements"=>0
		);
		if($object->simplified_xml)
		{
			if($object->fail_on_non_simplified_xml
			&& count($attrs)>0)
			{
				$this->SetError($object,2,"Simplified XML can not have attributes in tags");
				return;
			}
		}
		else
			$data["Attributes"]=$attrs;
		$this->SetElementData($object,$this->path,$data);
	}

	Function EndElement(&$object,$name)
	{
		$this->path=(($position=strrpos($this->path,",")) ? substr($this->path,0,$position) : "");
	}

	Function CharacterData(&$object,$data)
	{
		$element=$object->structure[$this->path]["Elements"];
		$previous=$this->path.",".strval($element-1);
		if($element>0
		&& GetType($object->structure[$previous])=="string")
			$object->structure[$previous].=$data;
		else
		{
			$this->SetElementData($object,$this->path.",$element",$data);
			$object->structure[$this->path]["Elements"]++;
		}
	}
};

class xml_parser_class
{
	var $xml_parser=0;
	var $parser_handler;
	var $error="";
	var $error_number=0;
	var $error_line=0;
	var $error_column=0;
	var $error_byte_index=0;
	var $error_code=0;
	var $stream_buffer_size=4096;
	var $structure=array();
	var $positions=array();
	var $store_positions=0;
	var $case_folding=0;
	var $target_encoding="ISO-8859-1";
	var $simplified_xml=0;
	var $fail_on_non_simplified_xml=0;

	Function xml_parser_start_element_handler($parser,$name,$attrs)
	{
		if(!strcmp($this->error,""))
			$this->parser_handler->StartElement($this,$name,$attrs);
	}

	Function xml_parser_end_element_handler($parser,$name)
	{
		if(!strcmp($this->error,""))
			$this->parser_handler->EndElement($this,$name);
	}

	Function xml_parser_character_data_handler($parser,$data)
	{
		if(!strcmp($this->error,""))
			$this->parser_handler->CharacterData($this,$data);
	}

	Function SetErrorPosition($error_number,$error,$line,$column,$byte_index)
	{
		$this->error_number=$error_number;
		$this->error=$error;
		$this->error_line=$line;
		$this->error_column=$column;
		$this->error_byte_index=$byte_index;
	}

	Function SetError($error_number,$error)
	{
		$this->error_number=$error_number;
		$this->error=$error;
		if($this->xml_parser)
		{
			$line=xml_get_current_line_number($this->xml_parser);
			$column=xml_get_current_column_number($this->xml_parser);
			$byte_index=xml_get_current_byte_index($this->xml_parser);
		}
		else
		{
			$line=$column=1;
			$byte_index=0;
		}
		$this->SetErrorPosition($error_number,$error,$line,$column,$byte_index);
	}

	Function Parse($data,$end_of_data)
	{
		global $xml_parser_handlers;

		if(strcmp($this->error,""))
			return($this->error);
		if(!$this->xml_parser)
		{
			if(!function_exists("xml_parser_create"))
			{
				$this->SetError(1,"XML support is not available in this PHP configuration");
				return($this->error);
			}
			if(!($this->xml_parser=xml_parser_create()))
			{
				$this->SetError(1,"Could not create the XML parser");
				return($this->error);
			}
			xml_parser_set_option($this->xml_parser,XML_OPTION_CASE_FOLDING,$this->case_folding);
			xml_parser_set_option($this->xml_parser,XML_OPTION_TARGET_ENCODING,$this->target_encoding);
			if(function_exists("xml_set_object"))
			{
				xml_set_object($this->xml_parser,$this);
				$this->parser_handler=new xml_parser_handler_class;
				$this->structure=array();
				$this->positions=array();
			}
			else
			{
				$xml_parser_handlers[$this->xml_parser]=new xml_parser_handler_class;
				$xml_parser_handlers[$this->xml_parser]->xml_parser=$this->xml_parser;
				$xml_parser_handlers[$this->xml_parser]->store_positions=$this->store_positions;
				$xml_parser_handlers[$this->xml_parser]->simplified_xml=$this->simplified_xml;
				$xml_parser_handlers[$this->xml_parser]->fail_on_non_simplified_xml=$this->fail_on_non_simplified_xml;
			}
			xml_set_element_handler($this->xml_parser,"xml_parser_start_element_handler","xml_parser_end_element_handler");
			xml_set_character_data_handler($this->xml_parser,"xml_parser_character_data_handler");
		}
		$parser_ok=xml_parse($this->xml_parser,$data,$end_of_data);
		if(!function_exists("xml_set_object"))
			$this->error=$xml_parser_handlers[$this->xml_parser]->error;
		if(!strcmp($this->error,""))
		{
			if($parser_ok)
			{
				if($end_of_data)
				{
					if(function_exists("xml_set_object"))
						Unset($this->parser_handler);
					else
					{
						$this->structure=$xml_parser_handlers[$this->xml_parser]->structure;
						$this->positions=$xml_parser_handlers[$this->xml_parser]->positions;
						Unset($xml_parser_handlers[$this->xml_parser]);
					}
					xml_parser_free($this->xml_parser);
					$this->xml_parser=0;
				}
			}
			else
				$this->SetError(2,"Could not parse data: ".xml_error_string($this->error_code=xml_get_error_code($this->xml_parser)));
		}
		else
		{
			if(!function_exists("xml_set_object"))
			{
				$this->error_number=$xml_parser_handlers[$this->xml_parser]->error_number;
				$this->error_code=$xml_parser_handlers[$this->xml_parser]->error_code;
				$this->error_line=$xml_parser_handlers[$this->xml_parser]->error_line;
				$this->error_column=$xml_parser_handlers[$this->xml_parser]->error_column;
				$this->error_byte_index=$xml_parser_handlers[$this->xml_parser]->error_byte_index;
			}			
		}
		return($this->error);
	}

	Function VerifyWhiteSpace($path)
	{
		if($this->store_positions)
		{
			$line=$parser->positions[$path]["Line"];
			$column=$parser->positions[$path]["Column"];
			$byte_index=$parser->positions[$path]["Byte"];
		}
		else
		{
			$line=$column=1;
			$byte_index=0;
		}
		if(!IsSet($this->structure[$path]))
		{
			$this->SetErrorPosition(2,"element path does not exist",$line,$column,$byte_index);
			return($this->error);
		}
		if(GetType($this->structure[$path])!="string")
		{
			$this->SetErrorPosition(2,"element is not data",$line,$column,$byte_index);
			return($this->error);
		}
		$data=$this->structure[$path];
		for($previous_return=0,$position=0;$position<strlen($data);$position++)
		{
			switch($data[$position])
			{
				case " ":
				case "\t":
					$column++;
					$byte_index++;
					$previous_return=0;
					break;
				case "\n":
					if(!$previous_return)
						$line++;
					$column=1;
					$byte_index++;
					$previous_return=0;
					break;
				case "\r":
					$line++;
					$column=1;
					$byte_index++;
					$previous_return=1;
					break;
				default:
					$this->SetErrorPosition(2,"data is not white space",$line,$column,$byte_index);
					return($this->error);
			}
		}
		return("");
	}

	Function ParseStream($stream)
	{
		if(strcmp($this->error,""))
			return($this->error);
		do
		{
			if(!($data=@fread($stream,$this->stream_buffer_size)))
			{
				if(!feof($stream))
				{
					$this->SetError(3,"Could not read from input stream".(IsSet($php_errormsg) ? ': '.$php_errormsg : ''));
					break;
				}
			}
			if(strcmp($error=$this->Parse($data,feof($stream)),""))
				break;
		}
		while(!feof($stream));
		return($this->error);
	}

	Function ParseFile($file)
	{
		if(!file_exists($file))
			return("the XML file to parse ($file) does not exist");
		if(!($definition=@fopen($file,"r")))
			return("could not open the XML file ($file)".(IsSet($php_errormsg) ? ': '.$php_errormsg : ''));
		$error=$this->ParseStream($definition);
		fclose($definition);
		return($error);
	}
};

Function XMLParseFile(&$parser,$file,$store_positions,$cache="",$case_folding=0,$target_encoding="ISO-8859-1",$simplified_xml=0,$fail_on_non_simplified_xml=0)
{
	if(!file_exists($file))
		return("the XML file to parse ($file) does not exist");
	if(strcmp($cache,""))
	{
		if(file_exists($cache)
		&& filemtime($file)<=filemtime($cache))
		{
			if(($cache_file=@fopen($cache,"r")))
			{
				if(function_exists("set_file_buffer"))
					set_file_buffer($cache_file,0);
				if(!($cache_contents=@fread($cache_file,filesize($cache))))
					$error="could not read from the XML cache file $cache".(IsSet($php_errormsg) ? ': '.$php_errormsg : '');
				else
					$error="";
				fclose($cache_file);
				if(!strcmp($error,""))
				{
					if(GetType($parser=unserialize($cache_contents))=="object"
					&& IsSet($parser->structure))
					{
						if(!IsSet($parser->simplified_xml))
							$parser->simplified_xml=0;
						if(($simplified_xml
						|| !$parser->simplified_xml)
						&& (!$store_positions
						|| $parser->store_positions))
						{
							return("");
						}
					}
					else
						$error="it was not specified a valid cache object in XML file ($cache)";
				}
			}
			else
				$error="could not open cache XML file ($cache)".(IsSet($php_errormsg) ? ': '.$php_errormsg : '');
			if(strcmp($error,""))
				return($error);
		}
	}
	$parser=new xml_parser_class;
	$parser->store_positions=$store_positions;
	$parser->case_folding=$case_folding;
	$parser->target_encoding=$target_encoding;
	$parser->simplified_xml=$simplified_xml;
	$parser->fail_on_non_simplified_xml=$fail_on_non_simplified_xml;
	if(!strcmp($error=$parser->ParseFile($file),"")
	&& strcmp($cache,""))
	{
		if(($cache_file=@fopen($cache,"w")))
		{
			if(function_exists("set_file_buffer"))
				set_file_buffer($cache_file,0);
			if(!@fwrite($cache_file,serialize($parser))
			|| !@fclose($cache_file))
				$error="could to write to the XML cache file ($cache)".(IsSet($php_errormsg) ? ': '.$php_errormsg : '');
			if(strcmp($error,""))
				unlink($cache);
		}
		else
			$error="could not open for writing to the cache file ($cache)".(IsSet($php_errormsg) ? ': '.$php_errormsg : '');
	}
	return($error);
}

?>