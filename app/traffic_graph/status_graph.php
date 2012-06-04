<?php
/* $Id$ */
/*
	status_graph.php
	Part of pfSense
	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	Originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('traffic_graph_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if ($_REQUEST['interface']) {
	$interface = $_REQUEST['interface'];
}
else {
	$interface = '';
}
if ($_REQUEST['width']) {
	$width = $_REQUEST['width'];
}
else {
	$width = "660"; //550 //660 //792
}

if ($_REQUEST['height']) {
	$height = $_REQUEST['height'];
}
else {
	$height = "330"; //275 //330 //396
}

$pg_title = "<b>Traffic Graph</b>\n";

require_once "includes/header.php";
?>
<table width='100%'>
<tr>
<td align='left'>
	<p class="pgtitle"><?php echo $pg_title; ?></p>
</td>
<td align='right'>
	<form name="form1" action="status_graph.php" method="get" style="">
	Interface:
	<select name="interface" class="formfld" style="width:100px; z-index: -10;" onchange="document.form1.submit()">
	<option value=''></option>
	<?php
// run netstat to determine interface info
	exec("netstat -i", $result_array);
	//exec("netstat -i -nWb -f link", $result_array);

//show the result array
	//echo "<pre>\n";
	//print_r($result_array);
	//echo "</pre>\n";

//parse the data into a named array
	$x = 0;
	foreach ($result_array as $key => $value) {
		if ($value != "Kernel Interface table") {
			if ($x == 0) {
				//get the names of the values
					$interface_name_info = preg_split("/\s+/", $result_array[1]);
			}
			else {
				//get the values
					$interface_value_info = preg_split("/\s+/", $result_array[$key]);
				//list all the interfaces
					if ($interface == $interface_value_info[0]) {
						echo "<option value='".$interface_value_info[0]."' selected='selected'>".$interface_value_info[0]."</option>";
					}
					else {
						echo "<option value='".$interface_value_info[0]."'>".htmlspecialchars($interface_value_info[0])."</option>";
					}
			}
			$x++;
		}
	}
	?>
	</select>
	<input type='hidden' name='width' value='<?php echo $width; ?>'>
	<input type='hidden' name='height' value='<?php echo $height; ?>'>
	</form>
</td>
</tr>
</table>

<strong>Note:</strong> the <a href="http://www.adobe.com/svg/viewer/install/" target="_blank">Adobe SVG Viewer</a>, Firefox 1.5 or later or other browser supporting SVG is required to view the graph.

<br />
<br />
<br />
<br />

<div align="center">
	<object data="svg_graph.php?interface=<?php echo $interface; ?>" type="image/svg+xml" width="<?php echo $width; ?>" height="<?php echo $height; ?>">
		<param name="src" value="svg_graph.php?interface=<?php echo $interface; ?>" />
		Your browser does not support the type SVG! You need to either use Firefox or download the Adobe SVG plugin.
	</object>
</div>

<?php
require_once "includes/footer.php";
?>