<?php
/*-
 * Copyright (c) 2008-2025 Mark J Crane <markjcrane@fusionpbx.com>
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/functions/object_to_array.php";
	require_once "resources/functions/parse_message.php";

//check permissions
	if (!permission_exists('fax_extension_advanced')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get submitted id
	$fax_uuid = !empty($_GET['id']) && is_uuid($_GET['id']) ? $_GET['id'] : null;

//get advanced fax settings
	if (permission_exists('fax_extension_view')) {
		//retrieve any fax ext
		$sql = "select ";
		$sql .= "f.fax_name, f.fax_extension, f.fax_email_connection_type, f.fax_email_connection_host, f.fax_email_connection_port, f.fax_email_connection_security, ";
		$sql .= "f.fax_email_connection_validate, f.fax_email_connection_username, f.fax_email_connection_password, f.fax_email_connection_mailbox, f.fax_email_inbound_subject_tag ";
		$sql .= "from v_fax as f ";
		$sql .= "where f.domain_uuid = :domain_uuid ";
		$sql .= "and f.fax_uuid = :fax_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fax_uuid'] = $fax_uuid;
	}
	else {
		//retrieve only fax ext assigned to user
		$sql = "select ";
		$sql .= "f.fax_name, f.fax_extension, f.fax_email_connection_type, f.fax_email_connection_host, f.fax_email_connection_port, f.fax_email_connection_security, ";
		$sql .= "f.fax_email_connection_validate, f.fax_email_connection_username, f.fax_email_connection_password, f.fax_email_connection_mailbox, f.fax_email_inbound_subject_tag ";
		$sql .= "from v_fax as f, v_fax_users as u ";
		$sql .= "where f.fax_uuid = u.fax_uuid ";
		$sql .= "and f.domain_uuid = :domain_uuid ";
		$sql .= "and f.fax_uuid = :fax_uuid ";
		$sql .= "and u.user_uuid = :user_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fax_uuid'] = $fax_uuid;
		$parameters['user_uuid'] = $_SESSION['user_uuid'];
	}
	$fax = $database->select($sql, $parameters, 'row');
	unset($sql, $parameters);

//attempt connection
	if (!empty($fax) && is_array($fax)) {
		$fax_email_connection = "{".$fax["fax_email_connection_host"].":".$fax["fax_email_connection_port"]."/".$fax["fax_email_connection_type"];
		$fax_email_connection .= !empty($fax["fax_email_connection_security"]) ? "/".$fax["fax_email_connection_security"] : "/notls";
		$fax_email_connection .= "/".($fax["fax_email_connection_validate"] == false ? "no" : null)."validate-cert";
		$fax_email_connection .= "}".$fax["fax_email_connection_mailbox"];
		if (!$connection = @imap_open($fax_email_connection, $fax["fax_email_connection_username"], $fax["fax_email_connection_password"])) {
			$connected = false;
			$response = imap_errors();
		}
		else {
			$connected = true;
			//get message count
			$message_count = imap_num_msg($connection);
			$response = imap_errors();
			imap_close($connection);
		}
	}
	else {
		$connected = false;
		$response = $text['label-advanced_fax_settings_not_found'];
	}

//show the content
	echo "<input type='button' class='btn' style='float: right;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
	echo "<b>".$text['header-advanced_fax_settings_test']."</b>\n";
	echo "<br><br>\n";

	echo str_replace(['[NAME]','[EXT]'], ['<strong>'.$fax['fax_name'].'</strong>','<strong>'.$fax['fax_extension'].'</strong>'], $text['description-advanced_fax_settings_test'])."\n";
	echo "<br><br><br>\n";

	if (!empty($fax) && is_array($fax)) {
		echo "<b>".$text['header-settings']."</b>\n";
		echo "<br><br>\n";

		echo "<table>\n";
		foreach ($fax as $field => $value) {
			if ($field == 'fax_email_connection_username' || $field == 'fax_email_connection_password') { continue; }
			echo "<tr>\n";
				echo "<td style='padding-right: 30px;'>".ucwords(str_replace(['_','fax email '],[' ',''], $field))."</td>\n";
				echo "<td style='padding-right: 30px;'>";
				if (is_bool($value)) {
					echo !empty($value) ? 'True' : 'False';
				}
				else {
					echo in_array($field, ['fax_name','fax_extension']) ? '<strong>'.($value ?? null).'</strong>' : ($value ?? null);
				}
				echo "</td>\n";
			echo "<tr>\n";
		}
		echo "<tr>\n";
			echo "<td style='padding-right: 30px;'>".$text['label-connection_string']."</td>\n";
			echo "<td style='padding-right: 30px;'>".escape($fax_email_connection)."</td>\n";
		echo "<tr>\n";
		echo "</table>\n";
		echo "<br><br>\n";
	}

	echo "<b>".$text['header-result']."</b>\n";
	echo "<br><br>\n";

	echo "<div style='width: 100%; max-height: 250px; overflow: auto; border: 1px solid ".($settings->get('theme', 'table_row_border_color') ?? '#c5d1e5')."; padding: 12px 15px; background-color: ".($settings->get('theme', 'table_row_background_color_light') ?? '#fff')."; font-family: monospace; font-size: 85%;'>\n";
		echo ($connected ? $text['label-connection_success'] : $text['label-connection_failed']);
		echo "<br><br>\n";
		if (is_array($response)) {
			foreach ($response as $message) {
				echo $message."<br>\n";
			}
		}
		else {
			echo $response;
		}
		if (isset($message_count) && is_numeric($message_count)) {
			echo $message_count.' Message'.($message_count != 1).' Found';
		}
	echo "</div>\n";
	echo "<br><br>\n";


	echo "<center>\n";
	echo "	<input type='button' class='btn' style='margin-top: 15px;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
	echo "</center>\n";