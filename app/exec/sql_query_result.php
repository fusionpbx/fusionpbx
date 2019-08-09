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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('exec_sql')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//pdo database connection
	if (strlen($_REQUEST['id']) > 0) {
		require_once "sql_query_pdo.php";
	}

//check the captcha
	$code = trim($_REQUEST["code"]);
	$command_authorized = false;
	if (strtolower($_SESSION['captcha']) == strtolower($code)) {
		$command_authorized = true;
	}
	if (!$command_authorized) {
		//catpcha invalid
		exit;
	}

//show the content
	if (is_array($_POST)) {
		$sql_type = trim($_POST["sql_type"]);
		$sql_cmd = trim($_POST["command"]);
		$table_name = trim($_POST["table_name"]);
	
		$header = "<html>\n";
		$header .= "<head>\n";
		$header .= "<style type='text/css'>\n";
		$header .= "\n";
		$header .= "body {\n";
		$header .= "	font-family: arial;\n";
		$header .= "	font-size: 12px;\n";
		$header .= "	color: #444;\n";
		$header .= "}\n";
		$header .= "\n";
		$header .= "th {\n";
		$header .= "	border-top: 1px solid #444;\n";
		$header .= "	border-bottom: 1px solid #444;\n";
		$header .= "	color: #fff;\n";
		$header .= "	font-size: 12px;\n";
		$header .= "	font-family: arial;\n";
		$header .= "	font-weight: bold;\n";
		$header .= "	background-color: #777;\n";
		$header .= "	padding: 4px 7px;\n";
		$header .= "	text-align: left;\n";
		$header .= "}\n";
		$header .= "\n";
		$header .= ".row_style0 {\n";
		$header .= "	background-color: #eee;\n";
		$header .= "	border-bottom: 1px solid #999;\n";
		$header .= "	border-left: 1px solid #fff;\n";
		$header .= "	font-size: 12px;\n";
		$header .= "	color: #444;\n";
		$header .= "	text-align: left;\n";
		$header .= "	padding: 4px 7px;\n";
		$header .= "	text-align: left;\n";
		$header .= "	vertical-align: top;\n";
		$header .= "}\n";
		$header .= "\n";
		$header .= ".row_style0 a:link{ color:#444; }\n";
		$header .= ".row_style0 a:visited{ color:#444; }\n";
		$header .= ".row_style0 a:hover{ color:#444; }\n";
		$header .= ".row_style0 a:active{ color:#444; }\n";
		$header .= "\n";
		$header .= ".row_style1 {\n";
		$header .= "	border-bottom: 1px solid #999;\n";
		$header .= "	border-left: 1px solid #eee;\n";
		$header .= "	background-color: #fff;\n";
		$header .= "	font-size: 12px;\n";
		$header .= "	color: #444;\n";
		$header .= "	text-align: left;\n";
		$header .= "	padding: 4px 7px;\n";
		$header .= "	text-align: left;\n";
		$header .= "	vertical-align: top;\n";
		$header .= "}\n";
		$header .= "</style>";
		$header .= "</head>\n";
		$header .= "<body style='margin: 0; padding: 8;'>\n";

		$footer = "<body>\n";
		$footer .= "<html>\n";

		if ($sql_type == '') {

			echo $header;

			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

			//determine queries to run and show
			if ($sql_cmd != '') { $sql_array = array_filter(explode(";", $sql_cmd)); }
			if ($table_name != '') { $sql_array[] = "select * from ".$table_name; }
			$show_query = (sizeof($sql_array) > 1) ? true : false;

			if (is_array($sql_array)) foreach($sql_array as $sql_index => $sql) {
				$sql = trim($sql);

				if (sizeof($sql_array) > 1 || $show_query) {
					if ($sql_index > 0) { echo "<br /><br /><br />"; }
					echo "<span style='display: block; font-family: monospace; padding: 8px; color: green; background-color: #eefff0;'>".escape($sql).";</span><br />";
				}

				$database = new database;
				$result = $database->execute($sql, null, 'all');
				$message = $database->message;

				if ($message['message'] == 'OK' && $message['code'] == 200) {
					echo "<b>".$text['label-records'].": ".count($result)."</b>";
					echo "<br /><br />\n";
				}
				else {
					echo "<b>".$text['label-error']."</b>";
					echo "<br /><br />\n";
					echo $message['message'].' ['.$message['code']."]<br />\n";
					if (is_array($message['error']) && @sizeof($message['error']) != 0) {
						foreach ($message['error'] as $error) {
							echo "<pre>".$error."</pre><br /><br />\n";
						}
					}
				}

				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				$x = 0;
				if (is_array($result[0])) {
					echo "<thead>\n";
					echo "	<tr>\n";
					foreach ($result[0] as $key => $value) {
						echo "<th>".escape($key)."</th>\n";
						$column_array[$x++] = $key;
					}
					echo "	</tr>\n";
					echo "</thead>\n";
				}
				$x = 1;
				if (is_array($result)) {
					echo "<tbody>\n";
					foreach ($result as &$row) {
						if ($x++ > 1000) { break; }
						echo "<tr>\n";
						if (is_array($column_array)) {
							foreach ($column_array as $column_index => $column) {
								echo "<td class='".$row_style[$c]."' ".(($column_index == 0) ? "style='border-left: none;'" : null).">".escape($row[$column])."&nbsp;</td>\n";
							}
						}
						echo "</tr>\n";
						$c = ($c == 0) ? 1 : 0;
					}
					echo "</tbody>\n";
				}
				echo "</table>\n";
				echo "<br>\n";

				unset($result, $column_array);
			}
			echo $footer;
		}

		if ($sql_type == "inserts") {
			echo $header;

			$sql = trim($sql);

			//get the table data
				$sql = (strlen($sql_cmd) == 0) ? "select * from ".$table_name : $sql_cmd;

				if (strlen($sql) > 0) {
					$database = new database;
					$result = $database->execute($sql);
					$message = $database->message;

					if ($message['message'] != 'OK' || $message['code'] != 200) {
						echo "<b>".$text['label-error']."</b>";
						echo "<br /><br />\n";
						echo $message['message'].' ['.$message['code']."]<br />\n";
						if (is_array($message['error']) && @sizeof($message['error']) != 0) {
							foreach ($message['error'] as $error) {
								echo "<pre>".$error."</pre><br /><br />\n";
							}
						}
						exit;
					}

					$x = 0;
					if (is_array($result[0])) {
						foreach ($result[0] as $key => $value) {
							$column_array[$x++] = $key;
						}
					}

					$column_array_count = count($column_array);
					if (is_array($result)) foreach ($result as $index => &$row) {

						echo "<div style='font-family: monospace; border-bottom: 1px solid #ccc; padding-bottom: 8px; ".($index != 0 ? 'padding-top: 8px;' : null)."'>\n";
						echo "insert into ".$table_name." (";
						if (is_array($column_array)) {
							foreach ($column_array as $column) {
								if ($column != "menuid" && $column != "menuparentid") {
									$columns[] = $column;
								}
							}
						}
						if (is_array($columns) && sizeof($columns) > 0) {
							echo implode(', ', $columns);
						}
						echo ") values (";
						if (is_array($column_array)) {
							foreach ($column_array as $column) {
								if ($column != "menuid" && $column != "menuparentid") {
									$values[] = $row[$column] != '' ? "'".escape(check_str($row[$column]))."'" : 'null';
								}
							}
						}
						if (is_array($values) && sizeof($values) > 0) {
							echo implode(', ', $values);
						}
						echo ");\n";
						echo "</div>\n";
						unset($columns, $values);
					}

				}
			echo $footer;
		}

		if ($sql_type == "csv") {

			//set the headers
				header('Content-type: application/octet-binary');
				if (strlen($sql_cmd) > 0) {
					header('Content-Disposition: attachment; filename=data.csv');
				}
				else {
					header('Content-Disposition: attachment; filename='.escape($table_name).'.csv');
				}

			//get the table data
				if (strlen($sql_cmd) > 0) {
					$sql = $sql_cmd;
				}
				else {
					$sql = "select * from ".$table_name;
				}
				if (strlen($sql) > 0) {
					$database = new database;
					$result = $database->execute($sql);
					$message = $database->message;

					if ($message['message'] != 'OK' || $message['code'] != 200) {
						echo "<b>".$text['label-error']."</b>";
						echo "<br /><br />\n";
						echo $message['message'].' ['.$message['code']."]<br />\n";
						if (is_array($message['error']) && @sizeof($message['error']) != 0) {
							foreach ($message['error'] as $error) {
								echo "<pre>".$error."</pre><br /><br />\n";
							}
						}
						exit;
					}

					//build the column array
					$x = 0;
					if (is_array($result[0])) {
						foreach ($result[0] as $key => $value) {
							$column_array[$x] = $key;
							$x++;
						}
					}

					//column names
					echo '"'.implode('","', $column_array).'"'."\r\n";

					//column values
					if (is_array($result)) {
						foreach ($result as &$row) {
							$x = 1;
							foreach ($column_array as $column) {
								echo '"'.escape($row[$column]).'"'.(($x++ < count($column_array)) ? ',' : null);
							}
							echo "\n";
						}
					}
				}
		}
	}

?>
