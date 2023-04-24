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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('recording_play')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the variables
	$filename = $_GET['filename'];
	$type = $_GET['type']; //moh //rec

//show the content
?>
<html>
<head>
</head>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td align='center'>
			<b><?php echo escape($filename) ?></b>
		</td>
	</tr>
	<tr>
		<td align='center'>
		<?php
		// detect browser
		$user_agent = http_user_agent();
		$browser_name = $user_agent['name'];

		$file_ext = substr($filename, -3);
		if ($file_ext == "wav") {
			//HTML5 method
			if ($browser_name == "Google Chrome" || $browser_name == "Mozilla Firefox") {
				echo "<audio src=\"recordings.php?a=download&type=".urlencode($type)."&filename=".urlencode($filename)."\" autoplay=\"true\" ></audio>";
			}
			else {
				echo "<audio src=\"http://localhost:8000/mod/recordings/recordings.php?a=download&type=".urlencode($type)."&filename=".urlencode($filename)."\" autoplay=\"autoplay\"></audio>";
				echo "<embed src=\"recordings.php?a=download&type=".urlencode($type)."&filename=".urlencode($filename)."\" autostart=\"true\" width=\"300\" height=\"90\" name=\"sound_".escape($filename)."\" enablejavascript=\"true\">\n";
			}
		}
		if ($file_ext == "mp3") {
			echo "<object type=\"application/x-shockwave-flash\" width=\"400\" height=\"17\" data=\"slim.swf?autoplay=true&song_title=".urlencode($filename)."&song_url=recordings.php?a=download&type=".urlencode($type)."&filename=".urlencode($filename)."\">\n";
			echo "<param name=\"movie\" value=\"slim.swf?autoplay=true&song_url=recordings.php?a=download&type=".urlencode($type)."&filename=".urlencode($filename)."\" />\n";
			echo "<param name=\"quality\" value=\"high\"/>\n";
			echo "<param name=\"bgcolor\" value=\"#E6E6E6\"/>\n";
			echo "</object>\n";
		}
		?>
		</td>
	</tr>
</table>

</body>
</html>
