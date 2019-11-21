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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>

	Original version of Call Block was written by Gerrit Visser <gerrit308@gmail.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('call_block_edit') && !permission_exists('call_block_add')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$call_block_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$extension_uuid = $_POST["extension_uuid"];
		$call_block_name = $_POST["call_block_name"];
		$call_block_number = $_POST["call_block_number"];
		$call_block_enabled = $_POST["call_block_enabled"];
		$call_block_description = $_POST["call_block_description"];
		
		$action_array = explode(':', $_POST["call_block_action"]);
		$call_block_app = $action_array[0];
		$call_block_data = $action_array[1];
	}

//handle the http post
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//delete the call block
			if (permission_exists('call_block_delete')) {
				if ($_POST['action'] == 'delete' && is_uuid($call_block_uuid)) {
					//prepare
						$array[0]['checked'] = 'true';
						$array[0]['uuid'] = $call_block_uuid;
					//delete
						$obj = new call_block;
						$obj->delete($array);
					//redirect
						header('Location: call_block.php');
						exit;
				}
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: call_block.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (strlen($call_block_name) == 0) { $msg .= $text['label-provide-name']."<br>\n"; }
			//if (strlen($call_block_number) == 0) { $msg .= $text['label-provide-number']."<br>\n"; }
			if (strlen($call_block_enabled) == 0) { $msg .= $text['label-provide-enabled']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add or update the database
			if (is_array($_POST) && sizeof($_POST) != 0 && $_POST["persistformvar"] != "true") {

				//ensure call block is enabled in the dialplan
					if ($action == "add" || $action == "update") {
						$sql = "select dialplan_uuid from v_dialplans where true ";
						$sql .= "and domain_uuid = :domain_uuid ";
						$sql .= "and app_uuid = 'b1b31930-d0ee-4395-a891-04df94599f1f' ";
						$sql .= "and dialplan_enabled <> 'true' ";
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$database = new database;
						$rows = $database->select($sql, $parameters);

						if (is_array($rows) && sizeof($rows) != 0) {
							foreach ($rows as $index => $row) {
								$array['dialplans'][$index]['dialplan_uuid'] = $row['dialplan_uuid'];
								$array['dialplans'][$index]['dialplan_enabled'] = 'true';
							}

							$p = new permissions;
							$p->add('dialplan_edit', 'temp');

							$database = new database;
							$database->save($array);
							unset($array);

							$p->delete('dialplan_edit', 'temp');
						}
					}

				if ($action == "add") {
					$array['call_block'][0]['call_block_uuid'] = uuid();
					$array['call_block'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					if (is_uuid($extension_uuid)) {
						$array['call_block'][0]['extension_uuid'] = $extension_uuid;
					}
					$array['call_block'][0]['call_block_name'] = $call_block_name;
					$array['call_block'][0]['call_block_number'] = $call_block_number;
					$array['call_block'][0]['call_block_count'] = 0;
					$array['call_block'][0]['call_block_app'] = $call_block_app;
					$array['call_block'][0]['call_block_data'] = $call_block_data;
					$array['call_block'][0]['call_block_enabled'] = $call_block_enabled;
					$array['call_block'][0]['date_added'] = time();
					$array['call_block'][0]['call_block_description'] = $call_block_description;

					$database = new database;
					$database->app_name = 'call_block';
					$database->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
					$database->save($array);
					$response = $database->message;
					unset($array);

					message::add($text['label-add-complete']);
					header("Location: call_block.php");
					return;
				}

				if ($action == "update") {
					$sql = "select c.call_block_number, d.domain_name ";
					$sql .= "from v_call_block as c ";
					$sql .= "join v_domains as d on c.domain_uuid = d.domain_uuid ";
					$sql .= "where c.domain_uuid = :domain_uuid ";
					$sql .= "and c.call_block_uuid = :call_block_uuid ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$parameters['call_block_uuid'] = $call_block_uuid;
					$database = new database;
					$result = $database->select($sql, $parameters);
					if (is_array($result) && sizeof($result) != 0) {
						//set the domain_name
						$domain_name = $result[0]["domain_name"];

						//clear the cache
						$cache = new cache;
						$cache->delete("app:call_block:".$domain_name.":".$call_block_number);
					}
					unset($sql, $parameters);

					$array['call_block'][0]['call_block_uuid'] = $call_block_uuid;
					$array['call_block'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					if (is_uuid($extension_uuid)) {
						$array['call_block'][0]['extension_uuid'] = $extension_uuid;
					}
					$array['call_block'][0]['call_block_name'] = $call_block_name;
					$array['call_block'][0]['call_block_number'] = $call_block_number;
					$array['call_block'][0]['call_block_app'] = $call_block_app;
					$array['call_block'][0]['call_block_data'] = $call_block_data;
					$array['call_block'][0]['call_block_enabled'] = $call_block_enabled;
					$array['call_block'][0]['date_added'] = time();
					$array['call_block'][0]['call_block_description'] = $call_block_description;

					$database = new database;
					$database->app_name = 'call_block';
					$database->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
					$database->save($array);
					$response = $database->message;
					unset($array);

					message::add($text['label-update-complete']);
					header("Location: call_block.php");
					return;
				}
			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$call_block_uuid = $_GET["id"];
		$sql = "select * from v_call_block ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_block_uuid = :call_block_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['call_block_uuid'] = $call_block_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$extension_uuid = $row["extension_uuid"];
			$call_block_name = $row["call_block_name"];
			$call_block_number = $row["call_block_number"];
			$call_block_app = $row["call_block_app"];
			$call_block_data = $row["call_block_data"];
			$call_block_enabled = $row["call_block_enabled"];
			$call_block_description = $row["call_block_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the extensions
	$sql = "select extension_uuid, extension, number_alias, user_context, description from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true' ";
	$sql .= "order by extension asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$extensions = $database->select($sql, $parameters);

//get the extensions
	$sql = "select voicemail_uuid, voicemail_id, voicemail_description ";
	$sql .= "from v_voicemails ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and voicemail_enabled = 'true' ";
	$sql .= "order by voicemail_id asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$voicemails = $database->select($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['label-edit-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['label-edit-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' style='margin-right: 15px;' name='' alt='".$text['button-back']."' onclick=\"window.location='call_block.php'\" value='".$text['button-back']."'>";
	if ($action == 'update' && permission_exists('call_block_delete')) {
		echo button::create(['type'=>'submit','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'action','value'=>'delete','onclick'=>"if (confirm('".$text['confirm-delete']."')) { document.getElementById('frm').submit(); } else { this.blur(); return false; }",'style'=>'margin-right: 15px;']);
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "add") {
		echo $text['label-add-note']."<br /><br />\n";
	}
	if ($action == "update") {
		echo $text['label-edit-note']."<br /><br />\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('call_block_all')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-extension']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='extension_uuid'>\n";
		echo "	<option value=''>".$text['label-all']."</option>\n";
		if (is_array($extensions) && sizeof($extensions) != 0) {
			foreach ($extensions as $row) {
				$selected = $extension_uuid == $row['extension_uuid'] ? "selected='selected'" : null;
				echo "	<option value='".urlencode($row["extension_uuid"])."' ".$selected.">".escape($row['extension'])." ".escape($row['description'])."</option>\n";
			}
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-extension']."\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_block_name' maxlength='255' value=\"".escape($call_block_name)."\">\n";
	echo "<br />\n";
	echo $text['description-call_block_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_block_number' maxlength='255' value=\"".escape($call_block_number)."\">\n";
	echo "<br />\n";
	echo $text['description-call_block_number']."\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_block_action'>\n";
	if ($call_block_app == "reject") {
		echo "	<option value='reject' selected='selected'>".$text['label-reject']."</option>\n";
	}
	else {
		echo "   <option value='reject' >".$text['label-reject']."</option>\n";
	}
	if ($call_block_app == "busy") {
		echo "	<option value='busy' selected='selected'>".$text['label-busy']."</option>\n";
	}
	else {
		echo "	<option value='busy'>".$text['label-busy']."</option>\n";
	}
	if ($call_block_app == "hold") {
		echo "	<option value='hold' selected='selected'>".$text['label-hold']."</option>\n";
	}
	else {
		echo "	<option value='hold'>".$text['label-hold']."</option>\n";
	}
	if (permission_exists('call_block_extension')) {
		if (is_array($extensions) && sizeof($extensions) != 0) {
			echo "	<optgroup label='".$text['label-extension']."'>\n";
			foreach ($extensions as &$row) {
				$selected = ($call_block_app == 'extension' && $call_block_data == $row['extension']) ? "selected='selected'" : null;
				echo "		<option value='extension:".urlencode($row["extension"])."' ".$selected.">".escape($row['extension'])." ".escape($row['description'])."</option>\n";
			}
			echo "	</optgroup>\n";
		}
	}
	if (permission_exists('call_block_voicemail')) {
		if (is_array($voicemails) && sizeof($voicemails) != 0) {
			echo "	<optgroup label='".$text['label-voicemail']."'>\n";
			foreach ($voicemails as &$row) {
				$selected = ($call_block_app == 'voicemail' && $call_block_data == $row['voicemail_id']) ? "selected='selected'" : null;
				echo "		<option value='voicemail:".urlencode($row["voicemail_id"])."' ".$selected.">".escape($row['voicemail_id'])." ".escape($row['voicemail_description'])."</option>\n";
			}
			echo "	</optgroup>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-action']."\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='call_block_enabled'>\n";
	echo "		<option value='true' ".(($call_block_enabled == "true") ? "selected" : null).">".$text['label-true']."</option>\n";
	echo "		<option value='false' ".(($call_block_enabled == "false") ? "selected" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enable']."\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_block_description' maxlength='255' value=\"".escape($call_block_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='call_block_uuid' value='".escape($call_block_uuid)."'>\n";
	}
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//get recent calls from the db (if not editing an existing call block record)
	if (!is_uuid($_REQUEST["id"])) {

		if (permission_exists('call_block_all')) {
			$sql = "select caller_id_number, caller_id_name, caller_id_number, start_epoch, direction, hangup_cause, duration, billsec, xml_cdr_uuid ";
			$sql .= "from v_xml_cdr where true ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$sql .= "and direction != 'outbound' ";
			$sql .= "order by start_stamp desc ";
			$sql .= limit_offset($_SESSION['call_block']['recent_call_limit']['text']);
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$result = $database->select($sql, $parameters);
			unset($sql, $parameters);
		}

		if (!permission_exists('call_block_all') && is_array($_SESSION['user']['extension'])) {
			foreach ($_SESSION['user']['extension'] as $assigned_extension) {
				$assigned_extensions[$assigned_extension['extension_uuid']] = $assigned_extension['user'];
			}

			$sql = "select caller_id_number, caller_id_name, caller_id_number, start_epoch, direction, hangup_cause, duration, billsec, xml_cdr_uuid ";
			$sql .= "from v_xml_cdr ";
			$sql .= "where domain_uuid = :domain_uuid ";
				if (is_array($assigned_extensions) && sizeof($assigned_extensions) != 0) {
					$x = 0;
					foreach ($assigned_extensions as $assigned_extension_uuid => $assigned_extension) {
						$sql_where_array[] = "extension_uuid = :extension_uuid_".$x;
						//$sql_where_array[] = "caller_id_number = :caller_id_number_".$x;
						//$sql_where_array[] = "destination_number = :destination_number_1_".$x;
						//$sql_where_array[] = "destination_number = :destination_number_2_".$x;
						$parameters['extension_uuid_'.$x] = $assigned_extension_uuid;
						//$parameters['caller_id_number_'.$x] = $assigned_extension;
						//$parameters['destination_number_1_'.$x] = $assigned_extension;
						//$parameters['destination_number_2_'.$x] = '*99'.$assigned_extension;
						$x++;
					}
					if (is_array($sql_where_array) && sizeof($sql_where_array) != 0) {
						$sql .= "and (".implode(' or ', $sql_where_array).") ";
					}
					unset($sql_where_array);
				}
			$sql .= "order by start_stamp desc";
			$sql .= limit_offset($_SESSION['call_block']['recent_call_limit']['text']);
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');
		}

		echo "<b>".$text['label-edit-add-recent']."</b>";
		echo "<br><br>";
		echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<th style='width: 25px;'>&nbsp;</th>\n";
		echo th_order_by('caller_id_name', $text['label-name'], $order_by, $order);
		echo th_order_by('caller_id_number', $text['label-number'], $order_by, $order);
		echo th_order_by('start_stamp', $text['label-called-on'], $order_by, $order);
		echo th_order_by('duration', $text['label-duration'], $order_by, $order);
		//echo "<td>&nbsp;</td>\n";
		echo "</tr>";
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		if (is_array($result) && sizeof($result) != 0) {
			foreach($result as $row) {
				$extension_uuids = '';
				if (!permission_exists('call_block_all') && is_array($_SESSION['user']['extension'])) {
					foreach ($_SESSION['user']['extension'] as $field) {
						if (is_uuid($field['extension_uuid'])) {
							$extension_uuids .= "&extension_uuid=".$field['extension_uuid'];
						}
					}
				}
				$tr_href = " href='call_block_cdr_add.php?id=".urlencode($row['xml_cdr_uuid'])."&name=".urlencode($row['caller_id_name']).$extension_uuids."' ";

				if (strlen($row['caller_id_number']) >= 7) {
					if (defined('TIME_24HR') && TIME_24HR == 1) {
						$tmp_start_epoch = date("j M Y H:i:s", $row['start_epoch']);
					} else {
						$tmp_start_epoch = date("j M Y h:i:sa", $row['start_epoch']);
					}
					echo "<tr ".$tr_href.">\n";
					if (
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_missed.png") &&
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_connected.png") &&
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_failed.png") &&
						file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_connected.png")
						) {
						echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>";
						switch ($row['direction']) {
							case "inbound" :
								if ($row['billsec'] == 0)
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_missed.png' style='border: none;' alt='".$text['label-inbound']." ".$text['label-missed']."'>\n";
								else
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_connected.png' style='border: none;' alt='".$text['label-inbound']."'>\n";
								break;
							case "local" :
								if ($row['billsec'] == 0)
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_failed.png' style='border: none;' alt='".$text['label-local']." ".$text['label-failed']."'>\n";
								else
									echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_connected.png' style='border: none;' alt='".$text['label-local']."'>\n";
								break;
						}
						echo "	</td>\n";
					}
					else {
						echo "	<td class='".$row_style[$c]."'>&nbsp;</td>";
					}
					echo "	<td valign='top' class='".$row_style[$c]."'>";
					echo 	$row['caller_id_name'].' ';
					echo "	</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>";
					if (is_numeric($row['caller_id_number'])) {
						echo format_phone($row['caller_id_number']).' ';
					}
					else {
						echo $row['caller_id_number'].' ';
					}
					echo "	</td>\n";
					echo "	<td valign='top' class='".$row_style[$c]."'>".$tmp_start_epoch."</td>\n";
					$seconds = ($row['hangup_cause']=="ORIGINATOR_CANCEL") ? $row['duration'] : $row['billsec'];  //If they cancelled, show the ring time, not the bill time.
					echo "	<td valign='top' class='".$row_style[$c]."'>".gmdate("G:i:s", $seconds)."</td>\n";
					//echo "	<td class='list_control_icons' ".((!(if_group("superadmin"))) ? "style='width: 25px;'" : null).">";
					//if (if_group("superadmin")) {
					//	echo "	<a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr_details.php?id=".urlencode($row['xml_cdr_uuid'])."' alt='".$text['button-view']."'>".$v_link_label_view."</a>";
					//}
					//echo "	<a href='call_block_cdr_add.php?id=".urlencode($row['xml_cdr_uuid'])."&name=".urlencode($row['caller_id_name'])."' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
					//echo "	</td>";
					echo "</tr>\n";
					$c = $c == 1 ? 0 : 1;
				}
			}
			unset($result);

		}

		echo "</table>";
		echo "<br>";

	}

//include the footer
	require_once "resources/footer.php";

?>
