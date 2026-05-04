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
	Portions created by the Initial Developer are Copyright (C) 2018-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('ring_group_import')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post values
	$action = $_POST["action"] ?? null;
	$from_row = $_POST["from_row"] ?? '2';
	$delimiter = $_POST["data_delimiter"] ?? null;
	$enclosure = $_POST["data_enclosure"] ?? null;
	$ring_group_enabled = $_POST["ring_group_enabled"] ?? 'true';
	$ring_group_context = $_POST["ring_group_context"] ?? $_SESSION['domain_name'];
	$destination_separator = $_POST["destination_separator"] ?? '|';
	$destination_delay = $_POST["destination_delay"] ?? '0';
	$destination_timeout = $_POST["destination_timeout"] ?? '30';

//save pasted data to a csv file
	if (isset($_POST['data']) && !empty($_POST['data'])) {
		$file = $settings->get('server', 'temp')."/ring_groups-".$_SESSION['domain_name'].".csv";
		file_put_contents($file, $_POST['data']);
		$_SESSION['file'] = $file;
		$_SESSION['file_name'] = !empty($_FILES['ulfile']['name']) ? $_FILES['ulfile']['name'] : 'pasted-data.csv';
	}

//copy the uploaded csv file
	if (!empty($_FILES['ulfile']['tmp_name']) && is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('ring_group_upload')) {
		if (($_POST['type'] ?? '') == 'csv') {
			move_uploaded_file($_FILES['ulfile']['tmp_name'], $settings->get('server', 'temp').'/'.$_FILES['ulfile']['name']);
			$file = $settings->get('server', 'temp').'/'.$_FILES['ulfile']['name'];
			$_SESSION['file'] = $file;
			$_SESSION['file_name'] = $_FILES['ulfile']['name'];
		}
	}

//build the schema array (limited to ring_groups and ring_group_destinations)
	$line_fields = [];
	$schema = [];
	if (!empty($delimiter) && !empty($_SESSION['file']) && file_exists($_SESSION['file'])) {
		$line = fgets(fopen($_SESSION['file'], 'r'));
		$line_fields = explode($delimiter, $line);

		$x = 0;
		$apps = [];
		include "app/ring_groups/app_config.php";
		$i = 0;
		foreach ($apps[0]['db'] as $table) {
			$table_name = $table['table']['name'];
			$parent_name = $table['table']['parent'];
			if (substr($table_name, 0, 2) == 'v_') { $table_name = substr($table_name, 2); }
			if (substr($parent_name, 0, 2) == 'v_') { $parent_name = substr($parent_name, 2); }
			if ($table_name == 'ring_groups' || $table_name == 'ring_group_destinations') {
				$schema[$i]['table'] = $table_name;
				$schema[$i]['parent'] = $parent_name;
				foreach ($table['fields'] as $row) {
					if (!empty($row['deprecated']) && $row['deprecated'] === 'true') { continue; }
					$field_name = is_array($row['name']) ? $row['name']['text'] : $row['name'];
					//skip system/internal fields
					if (in_array($field_name, ['domain_uuid', 'ring_group_uuid', 'ring_group_destination_uuid', 'dialplan_uuid', 'insert_date', 'insert_user', 'update_date', 'update_user'])) {
						continue;
					}
					$schema[$i]['fields'][] = $field_name;
				}
				$i++;
			}
		}
	}

//get the parent table name for a given table
	function get_parent($schema, $table_name) {
		foreach ($schema as $row) {
			if ($row['table'] == $table_name) {
				return $row['parent'];
			}
		}
		return null;
	}

//process the import
	if (!empty($_SESSION['file']) && file_exists($_SESSION['file']) && $action == 'add') {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ring_group_imports.php');
				exit;
			}

		//user selected fields
			$fields = $_POST['fields'] ?? [];
			$domain_uuid = $_SESSION['domain_uuid'];

		//grant temporary dialplan permissions
			$p = permissions::new();
			$p->add('dialplan_add', 'temp');
			$p->add('dialplan_edit', 'temp');

		//process the csv file
			$handle = @fopen($_SESSION['file'], "r");
			if ($handle) {
				$row_id = 0;
				$row_number = 1;
				$array = [];

				while (($line = fgets($handle, 4096)) !== false) {
					$line = mb_convert_encoding($line, 'UTF-8');

					if ($from_row <= $row_number) {

						$result = str_getcsv($line, $delimiter, $enclosure);
						$ring_group_uuid = uuid();
						$dialplan_uuid = uuid();
						$row_destinations_raw = '';
						$row_data = [];

						//map columns to fields
						foreach ($fields as $key => $value) {
							if (empty($value)) { continue; }

							$field_array = explode('.', $value);
							$table_name = $field_array[0] ?? null;
							$field_name = $field_array[1] ?? null;
							$cell = $result[$key] ?? '';

							if ($table_name === 'ring_group_destinations' && $field_name === 'destination_number') {
								$row_destinations_raw = $cell;
								continue;
							}

							if ($table_name === 'ring_groups' && !empty($field_name)) {
								$row_data[$field_name] = $cell;
							}
						}

						//required: extension and at least one destination
						if (empty($row_data['ring_group_extension']) || $row_destinations_raw === '') {
							$row_number++;
							continue;
						}

						//apply defaults
						$row_data['ring_group_uuid'] = $ring_group_uuid;
						$row_data['domain_uuid'] = $domain_uuid;
						$row_data['dialplan_uuid'] = $dialplan_uuid;
						if (empty($row_data['ring_group_name'])) {
							$row_data['ring_group_name'] = $row_data['ring_group_extension'];
						}
						if (empty($row_data['ring_group_strategy'])) {
							$row_data['ring_group_strategy'] = 'simultaneous';
						}
						if (!isset($row_data['ring_group_call_timeout']) || $row_data['ring_group_call_timeout'] === '') {
							$row_data['ring_group_call_timeout'] = '30';
						}
						if (!isset($row_data['ring_group_enabled']) || $row_data['ring_group_enabled'] === '') {
							$row_data['ring_group_enabled'] = $ring_group_enabled;
						}
						if (!isset($row_data['ring_group_context']) || $row_data['ring_group_context'] === '') {
							$row_data['ring_group_context'] = $ring_group_context;
						}

						$array['ring_groups'][$row_id] = $row_data;

						//build destinations
						$destination_numbers = array_filter(array_map('trim', explode($destination_separator, $row_destinations_raw)));
						$y = 0;
						foreach ($destination_numbers as $destination_number) {
							$array['ring_groups'][$row_id]['ring_group_destinations'][$y]['ring_group_destination_uuid'] = uuid();
							$array['ring_groups'][$row_id]['ring_group_destinations'][$y]['domain_uuid'] = $domain_uuid;
							$array['ring_groups'][$row_id]['ring_group_destinations'][$y]['ring_group_uuid'] = $ring_group_uuid;
							$array['ring_groups'][$row_id]['ring_group_destinations'][$y]['destination_number'] = $destination_number;
							$array['ring_groups'][$row_id]['ring_group_destinations'][$y]['destination_delay'] = $destination_delay;
							$array['ring_groups'][$row_id]['ring_group_destinations'][$y]['destination_timeout'] = $destination_timeout;
							$array['ring_groups'][$row_id]['ring_group_destinations'][$y]['destination_enabled'] = 'true';
							$y++;
						}

						//build the dialplan xml
						$dialplan_xml = "<extension name=\"".xml::sanitize($row_data['ring_group_name'])."\" continue=\"\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
						$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($row_data['ring_group_extension'])."$\">\n";
						if ($settings->get('ring_group', 'ring_ready', true)) {
							$dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";
						}
						$dialplan_xml .= "		<action application=\"set\" data=\"ring_group_uuid=".xml::sanitize($ring_group_uuid)."\"/>\n";
						$dialplan_xml .= "		<action application=\"set\" data=\"record_stereo=true\"/>\n";
						$dialplan_xml .= "		<action application=\"lua\" data=\"app.lua ring_groups\"/>\n";
						$dialplan_xml .= "	</condition>\n";
						$dialplan_xml .= "</extension>\n";

						$array['dialplans'][$row_id]['domain_uuid'] = $domain_uuid;
						$array['dialplans'][$row_id]['dialplan_uuid'] = $dialplan_uuid;
						$array['dialplans'][$row_id]['app_uuid'] = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
						$array['dialplans'][$row_id]['dialplan_name'] = $row_data['ring_group_name'];
						$array['dialplans'][$row_id]['dialplan_number'] = $row_data['ring_group_extension'];
						$array['dialplans'][$row_id]['dialplan_context'] = $row_data['ring_group_context'];
						$array['dialplans'][$row_id]['dialplan_continue'] = 'false';
						$array['dialplans'][$row_id]['dialplan_xml'] = $dialplan_xml;
						$array['dialplans'][$row_id]['dialplan_order'] = '101';
						$array['dialplans'][$row_id]['dialplan_enabled'] = $row_data['ring_group_enabled'];
						$array['dialplans'][$row_id]['dialplan_description'] = $row_data['ring_group_description'] ?? '';

						$row_id++;

						//flush every 500 rows
						if ($row_id >= 500) {
							$database->save($array);
							unset($array);
							$row_id = 0;
						}
					}
					$row_number++;
				}
				fclose($handle);

				if (!empty($array) && is_array($array)) {
					$database->save($array);
				}
			}

		//remove the temporary permissions
			$p->delete('dialplan_add', 'temp');
			$p->delete('dialplan_edit', 'temp');

		//apply settings reminder
			$_SESSION['reload_xml'] = true;

		//clear cache
			$cache = new cache;
			$cache->delete('dialplan:'.$ring_group_context);

		//notify and redirect
			message::add($text['message-add']);
			header("Location: ring_groups.php");
			exit;
	}

//delete ring groups via csv
	if (!empty($_SESSION['file']) && file_exists($_SESSION['file']) && $action == 'delete') {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ring_group_imports.php');
				exit;
			}

		//user selected fields
			$fields = $_POST['fields'] ?? [];
			$domain_uuid = $_SESSION['domain_uuid'];

		//grant temporary permissions
			$p = permissions::new();
			$p->add('dialplan_delete', 'temp');
			$p->add('ring_group_delete', 'temp');

		//process the csv file
			$deleted_count = 0;
			$handle = @fopen($_SESSION['file'], "r");
			if ($handle) {
				$row_number = 1;

				while (($line = fgets($handle, 4096)) !== false) {
					$line = mb_convert_encoding($line, 'UTF-8');

					if ($from_row <= $row_number) {
						$result = str_getcsv($line, $delimiter, $enclosure);

						$row_extension = '';
						$row_uuid = '';

						foreach ($fields as $key => $value) {
							if (empty($value)) { continue; }
							$field_array = explode('.', $value);
							$table_name = $field_array[0] ?? null;
							$field_name = $field_array[1] ?? null;
							$cell = $result[$key] ?? '';

							if ($table_name === 'ring_groups' && $field_name === 'ring_group_extension') {
								$row_extension = $cell;
							}
							if ($table_name === 'ring_groups' && $field_name === 'ring_group_uuid') {
								$row_uuid = $cell;
							}
						}

						//resolve the ring_group_uuid + dialplan_uuid
						$lookup_uuid = '';
						$lookup_dialplan_uuid = '';
						if (is_uuid($row_uuid)) {
							$sql = "select ring_group_uuid, dialplan_uuid from v_ring_groups ";
							$sql .= "where domain_uuid = :domain_uuid and ring_group_uuid = :ring_group_uuid ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['ring_group_uuid'] = $row_uuid;
							$row = $database->select($sql, $parameters, 'row');
							unset($sql, $parameters);
							if (!empty($row)) {
								$lookup_uuid = $row['ring_group_uuid'];
								$lookup_dialplan_uuid = $row['dialplan_uuid'];
							}
						}
						else if (!empty($row_extension)) {
							$sql = "select ring_group_uuid, dialplan_uuid from v_ring_groups ";
							$sql .= "where domain_uuid = :domain_uuid and ring_group_extension = :ring_group_extension ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['ring_group_extension'] = $row_extension;
							$row = $database->select($sql, $parameters, 'row');
							unset($sql, $parameters);
							if (!empty($row)) {
								$lookup_uuid = $row['ring_group_uuid'];
								$lookup_dialplan_uuid = $row['dialplan_uuid'];
							}
						}

						if (is_uuid($lookup_uuid)) {
							//delete destinations
							$sql = "delete from v_ring_group_destinations where ring_group_uuid = :ring_group_uuid ";
							$parameters['ring_group_uuid'] = $lookup_uuid;
							$database->execute($sql, $parameters);
							unset($sql, $parameters);

							//delete ring group users
							$sql = "delete from v_ring_group_users where ring_group_uuid = :ring_group_uuid ";
							$parameters['ring_group_uuid'] = $lookup_uuid;
							$database->execute($sql, $parameters);
							unset($sql, $parameters);

							//delete the ring group
							$sql = "delete from v_ring_groups where ring_group_uuid = :ring_group_uuid ";
							$parameters['ring_group_uuid'] = $lookup_uuid;
							$database->execute($sql, $parameters);
							unset($sql, $parameters);

							//delete the dialplan
							if (is_uuid($lookup_dialplan_uuid)) {
								$sql = "delete from v_dialplan_details where dialplan_uuid = :dialplan_uuid ";
								$parameters['dialplan_uuid'] = $lookup_dialplan_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);

								$sql = "delete from v_dialplans where dialplan_uuid = :dialplan_uuid ";
								$parameters['dialplan_uuid'] = $lookup_dialplan_uuid;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							}

							$deleted_count++;
						}
					}
					$row_number++;
				}
				fclose($handle);
			}

		//remove the temporary permissions
			$p->delete('dialplan_delete', 'temp');
			$p->delete('ring_group_delete', 'temp');

		//apply settings reminder
			$_SESSION['reload_xml'] = true;

		//clear cache
			$cache = new cache;
			$cache->delete('dialplan:'.$ring_group_context);

		//notify and redirect
			message::add($text['message-delete'].': '.$deleted_count, 'positive');
			header("Location: ring_groups.php");
			exit;
	}

//field mapping form (after delimiter has been chosen)
	if (!empty($delimiter) && !empty($_SESSION['file']) && file_exists($_SESSION['file']) && $action !== 'add') {

		//create token
			$object = new token;
			$token = $object->create($_SERVER['PHP_SELF']);

		//include the header
			$document['title'] = $text['title-ring_group_import'];
			require_once "resources/header.php";

			echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

			echo "<div class='action_bar' id='action_bar'>\n";
			echo "	<div class='heading'><b>".$text['header-ring_group_import']."</b></div>\n";
			echo "	<div class='actions'>\n";
			echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'ring_group_imports.php']);
			echo button::create(['type'=>'submit','label'=>$text['button-import'],'icon'=>$settings->get('theme', 'button_icon_import'),'id'=>'btn_save']);
			echo "	</div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo $text['description-ring_group_import']."\n";
			echo "<br /><br />\n";

			echo "<div class='card'>\n";
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

			if (!empty($_SESSION['file_name'])) {
				echo "<tr>\n";
				echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-file_name']."</td>\n";
				echo "	<td class='vtable' align='left'><b>".escape($_SESSION['file_name'])."</b></td>\n";
				echo "</tr>\n";
			}

			//map each csv column to a field
			$x = 0;
			foreach ($line_fields as $line_field) {
				$line_field = preg_replace('#[^a-zA-Z0-9_]#', '', $line_field);
				echo "<tr>\n";
				echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".escape($line_field)."</td>\n";
				echo "	<td class='vtable' align='left'>\n";
				echo "		<select class='formfld' name='fields[$x]'>\n";
				echo "			<option value=''></option>\n";
				foreach ($schema as $group) {
					echo "			<optgroup label='".escape($group['table'])."'>\n";
					if (!empty($group['fields'])) {
						foreach ($group['fields'] as $field) {
							$selected = ($field == $line_field) ? "selected='selected'" : null;
							echo "				<option value='".escape($group['table']).".".escape($field)."' $selected>".escape($field)."</option>\n";
						}
					}
					echo "			</optgroup>\n";
				}
				echo "		</select>\n";
				echo "	</td>\n";
				echo "</tr>\n";
				$x++;
			}

			//destination separator
			echo "<tr>\n";
			echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-destination_separator']."</td>\n";
			echo "	<td class='vtable' align='left'>\n";
			echo "		<input class='formfld' type='text' name='destination_separator' maxlength='1' style='width: 60px;' value=\"".escape($destination_separator)."\">\n";
			echo "		<br />".$text['description-destination_separator']."\n";
			echo "	</td>\n";
			echo "</tr>\n";

			//destination delay
			echo "<tr>\n";
			echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-destination_delay']."</td>\n";
			echo "	<td class='vtable' align='left'>\n";
			echo "		<input class='formfld' type='number' name='destination_delay' min='0' value=\"".escape($destination_delay)."\">\n";
			echo "		<br />".$text['description-destination_delay']."\n";
			echo "	</td>\n";
			echo "</tr>\n";

			//destination timeout
			echo "<tr>\n";
			echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-destination_timeout']."</td>\n";
			echo "	<td class='vtable' align='left'>\n";
			echo "		<input class='formfld' type='number' name='destination_timeout' min='1' value=\"".escape($destination_timeout)."\">\n";
			echo "		<br />".$text['description-destination_timeout']."\n";
			echo "	</td>\n";
			echo "</tr>\n";

			//ring group context
			echo "<tr>\n";
			echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-context']."</td>\n";
			echo "	<td class='vtable' align='left'>\n";
			echo "		<input class='formfld' type='text' name='ring_group_context' maxlength='128' value=\"".escape($ring_group_context)."\">\n";
			echo "	</td>\n";
			echo "</tr>\n";

			//enabled
			echo "<tr>\n";
			echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-enabled']."</td>\n";
			echo "	<td class='vtable' align='left'>\n";
			echo "		<select class='formfld' name='ring_group_enabled'>\n";
			echo "			<option value='true' ".($ring_group_enabled === 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
			echo "			<option value='false' ".($ring_group_enabled === 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
			echo "		</select>\n";
			echo "	</td>\n";
			echo "</tr>\n";

			//action: add or delete
			echo "<tr>\n";
			echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-actions']."</td>\n";
			echo "	<td class='vtable' align='left'>\n";
			echo "		<select class='formfld' name='action'>\n";
			echo "			<option value='add' selected='selected'>".$text['label-add']."</option>\n";
			echo "			<option value='delete'>".$text['label-delete']."</option>\n";
			echo "		</select>\n";
			echo "	</td>\n";
			echo "</tr>\n";

			echo "</table>\n";
			echo "</div>\n";
			echo "<br /><br />\n";

			echo "<input type='hidden' name='from_row' value='".escape($from_row)."'>\n";
			echo "<input type='hidden' name='data_delimiter' value=\"".escape($delimiter)."\">\n";
			echo "<input type='hidden' name='data_enclosure' value=\"".escape($enclosure)."\">\n";
			echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

			echo "</form>\n";

			require_once "resources/footer.php";
			exit;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-ring_group_import'];
	require_once "resources/header.php";

//upload form (initial step)
	echo "<form name='frmUpload' method='post' enctype='multipart/form-data'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-ring_group_import']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'ring_groups.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>$settings->get('theme', 'button_icon_upload'),'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-ring_group_import']."\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";

	echo "<tr>\n";
	echo "	<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-import_data']."</td>\n";
	echo "	<td width='70%' class='vtable' align='left'>\n";
	echo "		<textarea name='data' id='data' class='formfld' style='width: 100%; min-height: 150px;' wrap='off'></textarea>\n";
	echo "		<br />".$text['description-import_data']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-from_row']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' name='from_row'>\n";
	for ($i = 2; $i <= 99; $i++) {
		$selected = ($i == $from_row) ? "selected" : null;
		echo "			<option value='$i' ".$selected.">$i</option>\n";
	}
	echo "		</select>\n";
	echo "		<br />".$text['description-from_row']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-import_delimiter']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' style='width: 60px;' name='data_delimiter'>\n";
	echo "			<option value=','>,</option>\n";
	echo "			<option value=';'>;</option>\n";
	echo "			<option value='\t'>TAB</option>\n";
	echo "		</select>\n";
	echo "		<br />".$text['description-import_delimiter']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-import_enclosure']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' style='width: 60px;' name='data_enclosure'>\n";
	echo "			<option value='\"'>\"</option>\n";
	echo "			<option value=''></option>\n";
	echo "		</select>\n";
	echo "		<br />".$text['description-import_enclosure']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	if (permission_exists('ring_group_upload')) {
		echo "<tr>\n";
		echo "	<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-import_file_upload']."</td>\n";
		echo "	<td class='vtable' align='left'>\n";
		echo "		<input name='ulfile' type='file' class='formfld fileinput' id='ulfile'>\n";
		echo "	</td>\n";
		echo "</tr>\n";
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br><br>";

	echo "<input name='type' type='hidden' value='csv'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
