<?php

/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

require_once dirname(__DIR__, 2) . '/resources/require.php';

// reload_xml_cdr.php (simplified)
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
	&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

try {
	if (class_exists('xml_cdr_service')) xml_cdr_service::send_reload();
	else $result = shell_exec('/usr/bin/systemctl restart xml_cdr');

	if ($is_ajax) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['status' => 'ok', 'message' => 'XML CDR reloaded']);
		exit;
	}

	// Non-AJAX fallback: keep your current behavior (redirect or render page)
	// header('Location: /app/system/some_page.php?msg=xml_cdr_reloaded');
	exit;
} catch (Throwable $e) {
	if ($is_ajax) {
		http_response_code(500);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		exit;
	}
	throw $e;
}
