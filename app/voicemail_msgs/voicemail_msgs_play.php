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
if (permission_exists('voicemail_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the http get values
	$uuid = $_GET['uuid'];
	$file_ext = $_GET['ext'];
	$type = $_GET['type']; //vm
	$desc = $_GET['desc'];
	$id = $_GET['id'];

//get the domain from the domains array
	$domain_name = $_SESSION['domains'][$domain_uuid]['domain_name'];
	
//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		$msg = "<div align='center'>".$text['confirm-socket']."<br /></div>";
	}
	
//show the error message or show the content
	if (strlen($msg) > 0) {
		require_once "includes/header.php";
		echo "<div align='center'>\n";
		echo "	<table width='40%'>\n";
		echo "		<tr>\n";
		echo "			<th align='left'>".$text['label-message']."</th>\n";
		echo "		</tr>\n";
		echo "		<tr>\n";
		echo "			<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "		</tr>\n";
		echo "	</table>\n";
		echo "</div>\n";
		require_once "includes/footer.php";
		return;
	}

?>
<html>
<head>
</head>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td align='center'>
			<b>voicemail: <?php echo$desc?></b>
		</td>
	</tr>
	<tr>
	<td align='center'>
	<?php
	//mark voicemail as read
		$cmd = "api vm_read " .$id."@".$domain_name." read ".$uuid;
		$response = trim(event_socket_request($fp, $cmd));
		if (strcmp($response,"+OK")==0) {
			$msg = "Complete";
		}
		else {
			$msg = "Failed";
		}
	//embed html tag to play the wav file
		if ($file_ext == "wav") {
			echo "<embed src=\"voicemail_msgs.php?a=download&type=".$type."&uuid=".$uuid."\" autostart=true width=200 height=40 name=\"sound".$uuid."\" enablejavascript=\"true\">\n";
		}
	//object html tag to add flash player that can play the mp3 file
		if ($file_ext == "mp3") {
			echo "<object type=\"application/x-shockwave-flash\" width=\"400\" height=\"17\" data=\"slim.swf?autoplay=true&song_title=".urlencode($uuid)."&song_url=".urlencode(PROJECT_PATH."/voicemail_msgs.php?a=download&type=".$type."&uuid=".$uuid)."\">\n";
			echo "<param name=\"movie\" value=\"slim.swf?autoplay=true&song_url=".urlencode(PROJECT_PATH."/voicemail_msgs.php?a=download&type=".$type."&uuid=".$uuid)."\" />\n";
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