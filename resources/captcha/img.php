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
require_once "config.php";
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ); //hide notices and warnings
session_start();


// Captcha verification image -----------------------
// Description this page is used to verify the captcha

$_SESSION["captcha"] = substr(md5(date('r')), 0, 6);
$text = $_SESSION["captcha"];
//echo $text;
exit;


function isfile($filename) {
    if (@filesize($filename) > 0) { return true; } else { return false; }
}

function dircontents($dir) {
  clearstatcache();
  $htmldirlist = '';
  $htmlfilelist = '';
  $dirlist = opendir($dir);
  while ($file = readdir ($dirlist)) {
      if ($file != '.' && $file != '..') {
          $newpath = $dir.'/'.$file;
           $level = explode('/',$newpath);

           if (is_dir($newpath)) {
                //do nothing
           }
           else {
                $mod_array[] = end($level);
           }
       }
   }

   closedir($dirlist);
   return $mod_array;
}

$fontarray = dircontents($pathtofonts);
//print_r($fontarray);

function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}
srand(make_seed());
$random = (rand()%count($fontarray));
$font = $pathtofonts.$fontarray[$random];
//echo $font;

//echo phpinfo();
//exit;

$fontsize = 16;
if(@$_GET['fontsize']) {
	$fontsize = $_GET['fontsize'];
}

//picked up from a note at http://www.php.net/imagettfbbox
function imagettfbbox_custom($size, $angle, $font, $text) {
  $dummy = imagecreate(1, 1);
  $black = imagecolorallocate($dummy, 0, 0, 0);
  $bbox = imagettftext($dummy, $size, $angle, 0, 0, $black, $font, $text);
  imagedestroy($dummy);
  return $bbox;
}

// Create the image
$size = imagettfbbox_custom($fontsize, 0, $font, $text);
$width = $size[2] + $size[0] + 8;
$height = abs($size[1]) + abs($size[7]);
//$width = 200;
//$height =  200;

$im = imagecreate($width, $height);

$colourBlack = imagecolorallocate($im, 255, 255, 255);
imagecolortransparent($im, $colourBlack);

// Create some colors
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);

// Add the text
imagefttext($im, $fontsize, 0, 0, abs($size[5]), $black, $font, $text);

// Set the content-type
header("Content-type: image/png");
// Using imagepng() results in clearer text compared with
imagepng($im);
imagedestroy($im);
?>