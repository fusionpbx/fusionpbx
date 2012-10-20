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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('music_on_hold_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

$filename = base64_decode($_GET['filename']);
$type = $_GET['type']; //moh //rec
$category_folder = $_GET['category'];
$samplingrate_folder = $_GET['samplingrate'];


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
			echo "<audio src=\"http://localhost:8000/mod/music_on_hold/v_music_on_hold.php?a=download&category=".$category_folder."&samplingrate=".$samplingrate_folder."&type=".$type."&filename=".base64_encode($filename)."\" autoplay=\"autoplay\">";
			echo "</audio>";

			echo "<embed src=\"v_music_on_hold.php?a=download&category=".$category_folder."&samplingrate=".$samplingrate_folder."&type=".$type."&filename=".base64_encode($filename)."\" autostart=\"true\" width=\"200\" height=\"40\" name=\"sound_".$filename."\" enablejavascript=\"true\">\n";

		}
		if ($file_ext == "mp3") {
			echo "<object type=\"application/x-shockwave-flash\" width=\"400\" height=\"17\" data=\"slim.swf?autoplay=true&song_title=".urlencode($filename)."&song_url=v_music_on_hold.php?a=download&category=".$category_folder."&samplingrate=".$samplingrate_folder."&type=".$type."&filename=".base64_encode($filename)."\">\n";
			echo "<param name=\"movie\" value=\"slim.swf?autoplay=true&song_url=v_music_on_hold.php?a=download&category=".$category_folder."&samplingrate=".$samplingrate_folder."&type=".$type."&filename=".base64_encode($filename)."\" />\n";
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
