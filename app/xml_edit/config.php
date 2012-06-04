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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

include "root.php";

error_reporting (E_ALL ^ E_NOTICE);

$applicationname = 'Edit';
$bodyoptions = "";

$dbfilename = "clip.db";
$db_file_path = PROJECT_PATH."/xml_edit/";

//$temp = $_ENV["TEMP"]."\\";
if (is_writable($db_file_path.$dbfilename)) { //is writable
	//use database in current location
	echo "yes";
}
else { //not writable
    /*
    //running from a non writable location so copy to temp directory
    if (file_exists($temp.$dbfilename)) {
       $db_file_path = $temp; //file already exists use existing file
    }
    else { //file doese not exist
        //copy the file to the temp dir
        if (copy($db_file_path.$dbfilename, $temp.$dbfilename)) {
           //echo "copy succeeded.\n";
           $db_file_path = $temp;
        }
        else {
            echo "Copy Failed ";
            exit;
        }
    }
    */
}

function get_string_between($string, $start, $end){
	$string = " ".$string;
	$ini = strpos($string,$start);
	if ($ini == 0) return "";
	$ini += strlen($start);
	$len = strpos($string,$end,$ini) - $ini;
	return substr($string,$ini,$len);
}

//$fullstring = "this is my [tag]dog[/tag]";
//$parsed = get_string_between($fullstring, "[tag]", "[/tag]");

//database connection
try {
    //$db = new PDO('sqlite2:example.db'); //sqlite 2
    //$db = new PDO('sqlite::memory:'); //sqlite 3
    if (!function_exists('phpmd5')) {
      function phpmd5($string) {
          return md5($string);
      }
    }
    if (!function_exists('phpmd5')) {
      function phpunix_timestamp($string) {
          return strtotime($string);
      }
    }
    if (!function_exists('phpnow')) {
      function phpnow() {
          return date('r');
      }
    }

    if (!function_exists('phpleft')) {
      function phpleft($string, $num) {
          return substr($string, 0, $num);
      }
    }

    if (!function_exists('phpright')) {
      function phpright($string, $num) {
          return substr($string, (strlen($string)-$num), strlen($string));
      }
    }

    if (!function_exists('phpsqlitedatatype')) {
      function phpsqlitedatatype($string, $field) {

          //--- Begin: Get String Between start and end characters -----
          $start = '(';
          $end = ')';
          $ini = stripos($string,$start);
          if ($ini == 0) return "";
          $ini += strlen($start);
          $len = stripos($string,$end,$ini) - $ini;
          $string = substr($string,$ini,$len);
          //--- End: Get String Between start and end characters -----

          $strdatatype = '';
          $stringarray = split (',', $string);
          foreach($stringarray as $lnvalue) {

              //$strdatatype .= "-- ".$lnvalue ." ".strlen($lnvalue)." delim ".strrchr($lnvalue, " ")."---<br>";
              //$delimpos = stripos($lnvalue, " ");
              //$strdatatype .= substr($value,$delimpos,strlen($value))." --<br>";

              $fieldlistarray = split (" ", $value);
              //$strdatatype .= $value ."<br>";
              //$strdatatype .= $fieldlistarray[0] ."<br>";
              //echo $fieldarray[0]."<br>\n";
              if ($fieldarray[0] == $field) {
                  //$strdatatype = $fieldarray[1]." ".$fieldarray[2]." ".$fieldarray[3]." ".$fieldarray[4]; //strdatatype
              }
              unset($fieldarray, $string, $field);
          }

          //$strdatatype = $string;
          return $strdatatype;
      }
    } //end function

/*
    $db = new PDO('sqlite:'.$db_file_path.$dbfilename); //sqlite 3
    //bool PDO::sqliteCreateFunction ( string function_name, callback callback [, int num_args] )
    $db->sqliteCreateFunction('md5', 'phpmd5', 1);
    //$db->sqliteCreateFunction('unix_timestamp', 'phpunix_timestamp', 1);
    $db->sqliteCreateFunction('now', 'phpnow', 0);
    $db->sqliteCreateFunction('sqlitedatatype', 'phpsqlitedatatype', 2);
    $db->sqliteCreateFunction('strleft', 'phpleft', 2);
    $db->sqliteCreateFunction('strright', 'phpright', 2);
*/
}
catch (PDOException $error) {
   print "error: " . $error->getMessage() . "<br/>";
   die();
}

if(!function_exists('escapejs')){
  function escapejs($strtemp) {
      $strtemp = str_replace ("\"", "\\\"", $strtemp); //escape the single quote
      //$strtemp = str_replace ("'", "''", $strtemp); //escape the single quote
  	return $strtemp;
  }
}

if(!function_exists('check_str')){
  function check_str($strtemp) {
      //$strtemp = str_replace ("\$", "\\\$", $strtemp); //escape the single quote
      //$strtemp = str_replace ("\'", "''", $strtemp); //escape the single quote
      $strtemp = str_replace ("'", "''", $strtemp); //escape the single quote
      //echo "strtemp $strtemp";
  	return $strtemp;
  }
}

?>
