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
	Portions created by the Initial Developer are Copyright (C) 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * captcha class
 *
 * @method string get
 */
class captcha {

	/**
	* Called when the object is created
	*/
	public $code;

	/**
	* Class constructor
	*/
	public function __construct() {

	}

	/**
	 * Called when there are no references to a particular object
	 * unset the variables used in the class
	 */
	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}

	/**
	 * Create the captcha image
	 * @var string $code
	 */
	public function image_captcha() {

		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
		require_once "resources/functions.php";
		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ); //hide notices and warnings

		//start the session
		ini_set("session.cookie_httponly", True);
		if (!isset($_SESSION)) { session_start(); }

		//$_SESSION["captcha"] = substr(md5(uuid()), 0, 6);
		//$text = $_SESSION["captcha"];
		$text = $this->code;

		// Set the font path
		$font_path = $_SERVER["DOCUMENT_ROOT"]."/resources/captcha/fonts";

		// Array of fonts
		//$fonts[] = 'ROUGD.TTF';
		//$fonts[] = 'Zebra.ttf';
		//$fonts[] = 'hanshand.ttf';
		$fonts = glob($font_path.'/*.[tT][tT][fF]');
		//print_r($fonts);
		//exit;

		// Randomize the fonts
		srand(uuid());
		$random = (rand()%count($fonts));
		//$font = $font_path.'/'.$fonts[$random];
		$font = $fonts[$random];

		// Set the font size
		$font_size = 16;
		if(@$_GET['fontsize']) {
			$font_size = $_GET['fontsize'];
		}

		// Create the image
		$size = $this->image_size($font_size, 0, $font, $text);
		$width = $size[2] + $size[0] + 8;
		$height = abs($size[1]) + abs($size[7]);
		//$width = 100;
		//$height =  40;

		// Set the image size
		$image = imagecreate($width, $height);

		// Create some colors
		$white = imagecolorallocate($image, 255, 255, 255);
		$black = imagecolorallocate($image, 0, 0, 0);

		// Set the transparent color
		imagecolortransparent($image, $white);

		// Add the text
		imagefttext($image, $font_size, 0, 0, abs($size[5]), $black, $font, $text);

		// Set the content-type
		//header("Content-type: image/png");
		//imagepng($image));

		ob_start();
		imagepng($image);
		$image_buffer = ob_get_clean();
		//echo "<img src=\"data:image/png;base64, ".base64_encode($image_buffer)."\" />\n";
		imagedestroy($image);
		return $image_buffer;
	}

	/**
	 * return the image in base64
	 */
	public function image_base64() {
		return base64_encode($this->image_captcha());
	}

	/**
	 * Get the image size
	 * @var string $value	string image size
	 */
	private function image_size($size, $angle, $font, $text) {
		$dummy = imagecreate(1, 1);
		$black = imagecolorallocate($dummy, 0, 0, 0);
		$bbox = imagettftext($dummy, $size, $angle, 0, 0, $black, $font, $text);
		imagedestroy($dummy);
		return $bbox;
	}

}

/*
$captcha = new captcha;
$captcha->code = 'abcdefg';
$image_base64 = $captcha->base64();
echo "<img src=\"data:image/png;base64, ".$image_base64."\" />\n";
*/

?>
