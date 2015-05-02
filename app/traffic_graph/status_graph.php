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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('traffic_graph_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

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

require_once "resources/header.php";
$document['title'] = $text['title-traffic_graph'];

?>
<table cellpadding='0' cellspacing='0' border='0' align='right'>
	<tr>
		<td>
			<?php
			// run netstat to determine interface info
				exec("netstat -i", $result_array);
			//parse the data into a named array
				$x = 0;
				foreach ($result_array as $key => $value) {
					if ($value != "Kernel Interface table") {
						if ($x != 0) {
							//get the values
								$interface_info = preg_split("/\s+/", $result_array[$key]);
							//list all the interfaces
								$options[] = "<option value='".$interface_info[0]."' ".(($interface == $interface_info[0]) ? "selected='selected'" : null).">".htmlspecialchars($interface_info[0])."</option>";
							//auto-select first interface
								if ($interface == '') { $interface = $interface_info[0]; }
						}
						$x++;
					}
				}
			//output form, if interfaces exist'
				if (sizeof($options)) {
					?>
					<form name="form1" action="status_graph.php" method="get" style="">
					<strong><?php echo $text['label-interface']?></strong>&nbsp;&nbsp;
					<select name="interface" class="formfld" style="width: 100px; z-index: -10;" onchange="document.form1.submit()">
						<?php echo implode("\n", $options); ?>
					</select>
					<input type='hidden' name='width' value='<?php echo $width; ?>'>
					<input type='hidden' name='height' value='<?php echo $height; ?>'>
					</form>
					<?php
				}
			?>
		</td>
	</tr>
</table>
<b><?php echo $text['header-traffic_graph']?></b>
<br><br>
<?php echo $text['description-traffic_graph']?>
<br><br>

<div align="center">
	<br><br>
	<?php
	if (sizeof($options) > 0) {
		?>
		<object data="svg_graph.php?interface=<?php echo $interface; ?>" type="image/svg+xml" width="<?php echo $width; ?>" height="<?php echo $height; ?>">
			<param name="src" value="svg_graph.php?interface=<?php echo $interface; ?>" />
			<?php echo $text['description-no_svg']?>
		</object>
		<?php
	}
	else {
		echo "<br><br><br><br><br>";
		echo $text['message-no_interfaces_found'];
		echo "<br><br><br><br><br>";
	}
	?>
</div>
<br><br><br>

<?php
require_once "resources/footer.php";
?>
