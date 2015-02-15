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
if (permission_exists('schema_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (count($_POST)>0) {
	$schema_uuid = trim($_REQUEST["id"]);
	$data = trim($_POST["data"]);
	$data_delimiter = trim($_POST["data_delimiter"]);
	$data_enclosure = trim($_POST["data_enclosure"]);
}

//define the php class
	class v_schema_fields {
		var $domain_uuid;
		var $schema_uuid;
		var $field_label;
		var $field_name;
		var $field_type;
		var $field_value;
		var $field_list_hidden;
		var $field_column;
		var $field_required;
		var $field_order;
		var $field_order_tab;
		var $field_description;

		function db_field_exists() {
			global $db;
			$sql = "select count(*) as num_rows from v_schema_fields ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and schema_uuid ='$this->schema_uuid' ";
			$sql .= "and field_name = '$this->field_name' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] > 0) {
					return true;
				}
				else {
					return false;
				}
			}
		}

		function db_insert() {
			global $db;
			$sql = "insert into v_schema_fields ";
			$sql .= "(";
			$sql .= "schema_field_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "schema_uuid, ";
			$sql .= "field_label, ";
			$sql .= "field_name, ";
			$sql .= "field_type, ";
			$sql .= "field_value, ";
			$sql .= "field_list_hidden, ";
			$sql .= "field_search_by, ";
			$sql .= "field_column, ";
			$sql .= "field_required, ";
			$sql .= "field_order, ";
			$sql .= "field_order_tab, ";
			$sql .= "field_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".uuid()."', ";
			$sql .= "'$this->domain_uuid', ";
			$sql .= "'$this->schema_uuid', ";
			$sql .= "'$this->field_label', ";
			$sql .= "'$this->field_name', ";
			$sql .= "'$this->field_type', ";
			$sql .= "'$this->field_value', ";
			$sql .= "'$this->field_list_hidden', ";
			$sql .= "'no', ";
			$sql .= "'$this->field_column', ";
			$sql .= "'$this->field_required', ";
			$sql .= "'$this->field_order', ";
			$sql .= "'$this->field_order_tab', ";
			$sql .= "'$this->field_description' ";
			$sql .= ")";
			if (!$this->db_field_exists()) {
				$db->exec(check_sql($sql));
			}
			unset($sql);
		}
	}

	class v_schema_data {
		var $domain_uuid;
		var $schema_uuid;
		var $data_row_uuid;
		var $field_name;
		var $data_field_value;
		var $last_insert_id;
		var $schema_data_uuid;

		function db_unique_id() {
			global $db;
			$sql = "insert into v_schema_data_row_id ";
			$sql .= "(";
			$sql .= "domain_uuid ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$this->domain_uuid' ";
			$sql .= ")";
			$db->exec($sql);
			unset($sql);
			return $db->lastInsertId($id);
		}

		function db_insert() {
			global $db;
			$sql = "insert into v_schema_data ";
			$sql .= "(";
			$sql .= "schema_data_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "data_row_uuid, ";
			$sql .= "schema_uuid, ";
			$sql .= "field_name, ";
			$sql .= "data_field_value, ";
			$sql .= "data_add_user, ";
			$sql .= "data_add_date ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".uuid()."', ";
			$sql .= "'$this->domain_uuid', ";
			$sql .= "'$this->data_row_uuid', ";
			$sql .= "'$this->schema_uuid', ";
			$sql .= "'$this->field_name', ";
			$sql .= "'$this->data_field_value', ";
			$sql .= "'".$_SESSION["username"]."', ";
			$sql .= "now() ";
			$sql .= ")";
			$db->exec($sql);
			$this->last_insert_id = $db->lastInsertId($id);
			unset($sql);
		}

		function db_update() {
			global $db;
			$sql  = "update v_schema_data set ";
			$sql .= "data_row_uuid = '$this->data_row_uuid', ";
			$sql .= "field_name = '$this->field_name', ";
			$sql .= "data_field_value = '$this->data_field_value', ";
			$sql .= "data_add_user = '".$_SESSION["username"]."', ";
			$sql .= "data_add_date = now() ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and schema_data_uuid = '$this->schema_data_uuid' ";
			$db->exec($sql);
			unset($sql);
		}
	}

//built in str_getcsv requires PHP 5.3 or higher, this function can be used to reproduct the functionality but requirs PHP 5.1.0 or higher
	if(!function_exists('str_getcsv')) {
		function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
			$fp = fopen("php://memory", 'r+');
			fputs($fp, $input);
			rewind($fp);
			$data = fgetcsv($fp, null, $delimiter, $enclosure); // $escape only got added in 5.3.0
			fclose($fp);
			return $data;
		}
	}

	//POST to PHP variables
		if (count($_POST)>0) {

			//show the header
				require_once "resources/header.php";
				$document['title'] = $text['title-import_results'];

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-import_results']."</b></td>\n";
			echo "<td width='70%' align='right' valign='top'>\n";
			echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='schema_import.php?id=$schema_uuid'\" value='".$text['button-back']."'>\n";
			echo "	<br /><br />\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "	<tr>\n";
			echo "		<td colspan='2' align='left'>\n";

			//import data
				if (strlen($data) > 0) {
					$line_array = explode("\n",$data);
					$name_array = explode(",",$line_array[0]);
					$x = 0;
					$db->beginTransaction();
					foreach($name_array as $key => $val) {
						$field_label = trim($val);
						$field_name = trim($val);
						$field_name = str_replace(" ", "_", $field_name);
						$field_name = str_replace("-", "_", $field_name);
						$field_name = strtolower($field_name);

						$fields = new v_schema_fields;
						$fields->domain_uuid = $domain_uuid;
						$fields->schema_uuid = $schema_uuid;
						$fields->field_label = $field_label;
						$fields->field_name = $field_name;
						$fields->field_type = 'text';
						$fields->field_value = '';
						$fields->field_list_hidden = 'show';
						$fields->field_column = '1';
						$fields->field_required = 'yes';
						$fields->field_order = $x;
						$fields->field_order_tab = $x;
						$fields->field_description = $field_label;
						$fields->db_insert();
						unset($fields);
						$x++;
					}

					foreach($line_array as $key => $line) {
						if ($key > 0) {
							$value_array = str_getcsv($line, $data_delimiter, $data_enclosure);
							$x=0;
							foreach($value_array as $key => $val) {

								$field_label = trim($name_array[$x]);
								$field_name = trim($name_array[$x]);
								$field_name = str_replace(" ", "_", $field_name);
								$field_name = str_replace("-", "_", $field_name);
								$field_name = strtolower($field_name);

								$field_value = trim($val);

								$data = new v_schema_data;
								$data->domain_uuid = $domain_uuid;
								$data->schema_uuid = $schema_uuid;
								if ($x == 0) {
									$data_row_uuid = uuid();
									//echo "id: ".$data_row_uuid."<br />\n";
								}
								$data->data_row_uuid = $data_row_uuid;
								$data->field_name = $field_name;
								$data->data_field_value = $field_value;
								$data->db_insert();
								unset($data);

								echo "<strong>$field_name:</strong> $field_value<br/>\n";
								$x++;
							}
							echo "<hr size='1' />\n";
						}
					}
					$db->commit();
				} //if (strlen($data) > 0)

			echo "		</td>\n";
			echo "	</tr>";
			echo "	</table>";
			echo "<br><br>";

			//show the footer
				require_once "resources/footer.php";
				exit;
		}


//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-import'];

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-import']."</b></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_data']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea name='data' id='data' rows='7' class='formfld' style='width: 100%;' wrap='off'>$data</textarea>\n";
	echo "<br />\n";
	echo $text['description-import_data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_delimiter']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' style='width: 100px;' name='data_delimiter'>\n";
	echo "    <option value=','>, (Comma)</option>\n";
	echo "    <option value='|'>| (Pipe)</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-import_delimiter']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-import_enclosure']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' style='width: 150px;' name='data_enclosure'>\n";
	echo "    <option value='\"'>\" (Double-Quote)</option>\n";
	echo "    <option value=''>(Nothing)</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-import_enclosure']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='import' class='btn' value='".$text['button-import']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

require_once "resources/footer.php";
?>