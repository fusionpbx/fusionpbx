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
if (permission_exists('sql_query_execute')) {
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

//pdo database connection
	if (strlen($_REQUEST['id']) > 0) {
		require_once "sql_query_pdo.php";
	}

if (count($_POST)>0) {
	$sql_type = trim($_POST["sql_type"]);
	$sql_cmd = trim($_POST["sql_cmd"]);
	$table_name = trim($_POST["table_name"]);
	if (strlen($sql_cmd) == 0) { $sql_cmd = "select * from ".$table_name; }
}

if (count($_POST)>0) {
	$tmp_header = "<html>\n";
	$tmp_header .= "<head>\n";
	$tmp_header .= "<style type='text/css'>\n";
	$tmp_header .= "\n";
	$tmp_header .= "body {\n";
	$tmp_header .= "	font-family: arial;\n";
	$tmp_header .= "	font-size: 12px;\n";
	$tmp_header .= "	color: #444444;\n";
	$tmp_header .= "}\n";
	$tmp_header .= "\n";
	$tmp_header .= "th {\n";
	$tmp_header .= "	border-top: 1px solid #444444;\n";
	$tmp_header .= "	border-bottom: 1px solid #444444;\n";
	$tmp_header .= "	color: #FFFFFF;\n";
	$tmp_header .= "	font-size: 12px;\n";
	$tmp_header .= "	font-family: arial;\n";
	$tmp_header .= "	font-weight: bold;\n";
	$tmp_header .= "	background-color: #777777;\n";
	$tmp_header .= "	background-image: url(".PROJECT_PATH."'/themes/horizontal/background_th.png');\n";
	$tmp_header .= "	padding-top: 4px;\n";
	$tmp_header .= "	padding-bottom: 4px;\n";
	$tmp_header .= "	padding-right: 7px;\n";
	$tmp_header .= "	padding-left: 7px;\n";
	$tmp_header .= "}\n";
	$tmp_header .= "\n";
	$tmp_header .= ".row_style0 {\n";
	$tmp_header .= "	background-color: #EEEEEE;\n";
	$tmp_header .= "	background-image: url(".PROJECT_PATH."'/themes/horizontal/background_cell.gif');\n";
	$tmp_header .= "	border-bottom: 1px solid #999999;\n";
	$tmp_header .= "	font-size: 12px;\n";
	$tmp_header .= "	color: #444444;\n";
	$tmp_header .= "	text-align: left;\n";
	$tmp_header .= "	padding-top: 4px;\n";
	$tmp_header .= "	padding-bottom: 4px;\n";
	$tmp_header .= "	padding-right: 7px;\n";
	$tmp_header .= "	padding-left: 7px;\n";
	$tmp_header .= "}\n";
	$tmp_header .= "\n";
	$tmp_header .= ".row_style0 a:link{ color:#444444; }\n";
	$tmp_header .= ".row_style0 a:visited{ color:#444444; }\n";
	$tmp_header .= ".row_style0 a:hover{ color:#444444; }\n";
	$tmp_header .= ".row_style0 a:active{ color:#444444; }\n";
	$tmp_header .= "\n";
	$tmp_header .= ".row_style1 {\n";
	$tmp_header .= "	border-bottom: 1px solid #999999;\n";
	$tmp_header .= "	background-color: #FFFFFF;\n";
	$tmp_header .= "	font-size: 12px;\n";
	$tmp_header .= "	color: #444444;\n";
	$tmp_header .= "	text-align: left;\n";
	$tmp_header .= "	padding-top: 4px;\n";
	$tmp_header .= "	padding-bottom: 4px;\n";
	$tmp_header .= "	padding-right: 7px;\n";
	$tmp_header .= "	padding-left: 7px;\n";
	$tmp_header .= "}\n";
	$tmp_header .= "\n";
	$tmp_header .= "</style>";
	$tmp_header .= "</head>\n";
	$tmp_header .= "<body>\n";

	$tmp_footer = "<body>\n";
	$tmp_footer .= "<html>\n";

	if ($sql_type == "default") {

		echo $tmp_header;

		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		$sql_array = explode(";", $sql_cmd);
		reset($sql_array);
		foreach($sql_array as $sql) {
			$sql = trim($sql);
			echo "<b>".$text['label-sql_query'].":</b><br>\n";
			echo "".$sql."<br /><br />";

			if (strlen($sql) > 0) {
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				try {
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					echo "<b>".$text['label-results'].": ".count($result)."</b><br />";
				}
				catch(PDOException $e) {
					echo "<b>".$text['label-error'].":</b><br />\n";
					echo "<table>\n";
					echo "<tr>\n";
					echo "<td>\n";
					echo $e->getMessage();
					echo "</td>\n";
					echo "</tr>\n";
					echo "</table>\n";
				}

				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				$x = 0;
				foreach ($result[0] as $key => $value) {
					echo "<th>".$key."</th>";
					$column_array[$x] = $key;
					$x++;
				}

				$x = 1;
				foreach ($result as &$row) {
					if ($x > 1000) { break; }
					echo "<tr>\n";
					foreach ($column_array as $column) {
						echo "<td class='".$row_style[$c]."'>&nbsp;".$row[$column]."&nbsp;</td>";
					}
					echo "</tr>\n";
					if ($c==0) { $c=1; } else { $c=0; }
					$x++;
				}
				echo "</table>\n";
				echo "<br>\n";
			}
		} //foreach($sql_array as $sql)
		echo $tmp_footer;
	}

	if ($sql_type == "sql insert into") {
		echo $tmp_header;

		$sql = trim($sql);
		echo "<b>".$text['label-sql_query'].":</b><br>\n";
		echo "".$sql."<br /><br />";

		//get the table data
			if (strlen($sql_cmd) == 0) {
				$sql = "select * from $table_name";
			}
			else {
				$sql = $sql_cmd;
			}
			if (strlen($sql) > 0) {
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				}
				else {
					echo "<b>".$text['label-error'].":</b>\n";
					echo "<pre>\n";
					print_r($db->errorInfo());
					echo "</pre>\n";
				}

				$x = 0;
				foreach ($result[0] as $key => $value) {
					$column_array[$x] = $key;
					$x++;
				}

				$column_array_count = count($column_array);

				foreach ($result as &$row) {
					echo "INSERT INTO $table_name (";
					$x = 1;
					foreach ($column_array as $column) {
						if ($x < $column_array_count) {
							if ($column != "menuid" && $column != "menuparentid") {
								echo "".$column.",";
							}
						}
						else {
							if ($column != "menuid" && $column != "menuparentid") {
								echo "".$column."";
							}
						}
						$x++;
					}
					echo ") ";

					echo "VALUES ( ";
					$x = 1;
					foreach ($column_array as $column) {
						if ($x < $column_array_count) {
							if ($column != "menuid" && $column != "menuparentid") {
								if (is_null($row[$column])) {
									echo "null,";
								}
								else {
									echo "'".check_str($row[$column])."',";
								}
							}
						}
						else {
							if ($column != "menuid" && $column != "menuparentid") {
								if (is_null($row[$column])) {
									echo "null";
								}
								else {
									echo "'".check_str($row[$column])."'";
								}
							}
						}
						$x++;
					}
					echo ");<br />\n";
				}
			}
		echo $tmp_footer;
	}

	if ($sql_type == "csv") {
		//echo $tmp_header;

		//set the headers
			header('Content-type: application/octet-binary');
			header('Content-Disposition: attachment; filename='.$table_name.'.csv');

		//get the table data
			$sql = trim($sql);
			$sql = "select * from $table_name";
			if (strlen($sql) > 0) {
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				}
				else {
					echo "<b>".$text['label-error'].":</b>\n";
					echo "<pre>\n";
					print_r($db->errorInfo());
					echo "</pre>\n";
				}

				$x = 0;
				foreach ($result[0] as $key => $value) {
					$column_array[$x] = $key;
					$x++;
				}

				$column_array_count = count($column_array);

				$x = 1;
				foreach ($column_array as $column) {
					if ($x < $column_array_count) {
						echo "\"".$column."\",";
					}
					else {
						echo "\"".$column."\"";
					}
					$x++;
				}
				echo "\r\n";

				foreach ($result as &$row) {
					$x = 1;
					foreach ($column_array as $column) {
						if ($x < $column_array_count) {
							echo "\"".check_str($row[$column])."\",";
						}
						else {
							echo "\"".check_str($row[$column])."\"";
						}
						$x++;
					}
					echo "\n";
				}
			}
	}
}

?>
