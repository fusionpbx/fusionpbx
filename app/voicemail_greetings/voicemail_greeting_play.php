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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_greeting_play')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

$filename = base64_decode($_GET['filename']);
$type = $_GET['type']; //moh //rec

?>
<html>
<head>
</head>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td align='center'>
			<b>file: <?php echo $filename ?></b>
		</td>
	</tr>
	<tr>
		<td align='center'>
		<?php
		$file_ext = substr($filename, -3);
		if ($file_ext == "wav") {
			//HTML5 method
			echo "<audio src=\"".PROJECT_PATH."/app/recordings/recordings.php?a=download&type=".$type."&filename=".base64_encode($filename)."\" autoplay=\"autoplay\">"; 
			echo "</audio>";
			echo "<embed src=\"".PROJECT_PATH."/app/recordings/recordings.php?a=download&type=".$type."&filename=".base64_encode($filename)."\" autostart=\"true\" width=\"300\" height=\"90\" name=\"sound_".$filename."\" enablejavascript=\"true\">\n";
		}
		if ($file_ext == "mp3") {
			echo "<object type=\"application/x-shockwave-flash\" width=\"400\" height=\"17\" data=\"".PROJECT_PATH."/app/recordings/slim.swf?autoplay=true&song_title=".urlencode($filename)."&song_url=".PROJECT_PATH."/app/recordings/recordings.php?a=download&type=".$type."&filename=".base64_encode($filename)."\">\n";
			echo "<param name=\"movie\" value=\"".PROJECT_PATH."/app/recordings/slim.swf?autoplay=true&song_url=recordings.php?a=download&type=".$type."&filename=".base64_encode($filename)."\" />\n";
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
