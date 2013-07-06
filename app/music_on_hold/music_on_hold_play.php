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
if (permission_exists('music_on_hold_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

$file_name = base64_decode($_GET['file_name']);
$type = $_GET['type']; //moh //rec
$category_dir = $_GET['category'];
$sampling_rate_dir = $_GET['sampling_rate'];


?>
<html>
<head>
</head>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td align='center'>
			<b>file: <?php echo $file_name ?></b>
		</td>
	</tr>
	<tr>
		<td align='center'>
		<?php
		$file_ext = substr($file_name, -3);
		if ($file_ext == "wav") {
			//HTML5 method
			echo "<audio src=\"http://localhost:8000/mod/music_on_hold/music_on_hold.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=".$type."&file_name=".base64_encode($file_name)."\" autoplay=\"autoplay\">";
			echo "</audio>";

			echo "<embed src=\"music_on_hold.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=".$type."&file_name=".base64_encode($file_name)."\" autostart=\"true\" width=\"200\" height=\"40\" name=\"sound_".$file_name."\" enablejavascript=\"true\">\n";
		}
		if ($file_ext == "mp3") {
			echo "<object type=\"application/x-shockwave-flash\" width=\"400\" height=\"17\" data=\"slim.swf?autoplay=true&song_title=".urlencode($file_name)."&song_url=music_on_hold.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=".$type."&file_name=".base64_encode($file_name)."\">\n";
			echo "<param name=\"movie\" value=\"slim.swf?autoplay=true&song_url=music_on_hold.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=".$type."&file_name=".base64_encode($file_name)."\" />\n";
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
