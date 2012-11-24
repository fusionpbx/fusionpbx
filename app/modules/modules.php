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
if (permission_exists('modules_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

$order_by = $_GET["order_by"];
$order = $_GET["order"];

$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (strlen($_GET["a"]) > 0) {
	if ($_GET["a"] == "stop") {
		$module_name = $_GET["m"];
		if ($fp) {
			$cmd = "api unload $module_name";
			$response = trim(event_socket_request($fp, $cmd));
			$msg = '<strong>Unload Module:</strong><pre>'.$response.'</pre>';
		}
	}
	if ($_GET["a"] == "start") {
		$module_name = $_GET["m"];
		if ($fp) {
			$cmd = "api load $module_name";
			$response = trim(event_socket_request($fp, $cmd));
			$msg = '<strong>Load Module:</strong><pre>'.$response.'</pre>';
		}
	}
}

//use the module class to get the list of modules from the db and add any missing modules
	require_once "includes/classes/switch_modules.php";
	$mod = new switch_modules;
	$mod->db = $db;
	$mod->dir = $_SESSION['switch']['mod']['dir'];
	$mod->get_modules();
	$result = $mod->modules;
	$module_count = count($result);
	$mod->synch();
	$msg = $mod->msg;

//show the msg
	if ($msg) {
		save_module_xml();
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>Message</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'>$msg</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "      <br>";

	echo "<table width='100%' border='0'><tr>\n";
	echo "<td align='left' width='50%' nowrap><b>Module List</b></td>\n";
	echo "<td align='left' width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left'>\n";
	echo "Modules extend the features of the system. Use this page to enable or disable modules. ";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	$tmp_module_header = "\n";
	$tmp_module_header .= "<tr>\n";
	//$tmp_module_header .= "<th>Module Category</th>\n";
	$tmp_module_header .= "<th>Label</th>\n";
	//$tmp_module_header .= "<th>Module Name</th>\n";
	$tmp_module_header .= "<th>Description</th>\n";
	$tmp_module_header .= "<th>Status</th>\n";
	$tmp_module_header .= "<th>Action</th>\n";
	$tmp_module_header .= "<th>Enabled</th>\n";
	//$tmp_module_header .= "<th>Default Enabled</th>\n";
	$tmp_module_header .= "<td align='right' width='42'>\n";
	$tmp_module_header .= "	<a href='modules_edit.php' alt='add'>$v_link_label_add</a>\n";
	$tmp_module_header .= "</td>\n";
	$tmp_module_header .= "<tr>\n";

	if ($module_count > 0) {
		$prev_module_category = '';
		foreach($result as $row) {
			if ($prev_module_category != $row["module_category"]) {
				$c=0;
				if (strlen($prev_module_category) > 0) {
					echo "<tr>\n";
					echo "<td colspan='6'>\n";
					echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
					echo "	<tr>\n";
					echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
					echo "		<td width='33.3%' align='center' nowrap>&nbsp;</td>\n";
					echo "		<td width='33.3%' align='right'>\n";
					if (permission_exists('modules_add')) {
						echo "			<a href='modules_edit.php' alt='add'>$v_link_label_add</a>\n";
					}
					echo "		</td>\n";
					echo "	</tr>\n";
					echo "	</table>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
				echo "<tr><td colspan='4' align='left'>\n";
				echo "	<br />\n";
				echo "	<br />\n";
				echo "	<b>".$row["module_category"]."</b>&nbsp;</td></tr>\n";
				echo $tmp_module_header;
			}

			echo "<tr >\n";
			//echo "   <td valign='top' class='".$row_style[$c]."'>".$row["module_category"]."</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>".$row["module_label"]."</td>\n";
			//echo "   <td valign='top' class='".$row_style[$c]."'>".$row["module_name"]."</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>".$row["module_description"]."&nbsp;</td>\n";
			if ($mod->active($row["module_name"])) {
				echo "   <td valign='top' class='".$row_style[$c]."'>Running</td>\n";
				echo "   <td valign='top' class='".$row_style[$c]."'><a href='modules.php?a=stop&m=".$row["module_name"]."' alt='stop'>Stop</a></td>\n";
			}
			else {
				if ($row['module_enabled']=="true") {
					echo "   <td valign='top' class='".$row_style[$c]."'><b>Stopped</b></td>\n";
				}
				else {
					echo "   <td valign='top' class='".$row_style[$c]."'>Stopped $notice</td>\n";
				}
				echo "   <td valign='top' class='".$row_style[$c]."'><a href='modules.php?a=start&m=".$row["module_name"]."' alt='start'>Start</a></td>\n";
			}
			echo "   <td valign='top' class='".$row_style[$c]."'>".$row["module_enabled"]."</td>\n";
			//echo "   <td valign='top' class='".$row_style[$c]."'>".$row["module_default_enabled"]."</td>\n";
			echo "   <td valign='top' align='right'>\n";
			if (permission_exists('modules_edit')) {
				echo "		<a href='modules_edit.php?id=".$row["module_uuid"]."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('modules_delete')) {
				echo "		<a href='modules_delete.php?id=".$row["module_uuid"]."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			echo "   </td>\n";
			echo "</tr>\n";

			$prev_module_category = $row["module_category"];
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $modules, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='6'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('modules_add')) {
		echo "			<a href='modules_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";

//show the footer
	require_once "includes/footer.php";
?>