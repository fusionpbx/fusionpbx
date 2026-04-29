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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('service_manager_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//handle scan request
	if (!empty($_POST['scan'])) {
		$object = new token;
		if (!$object->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'] ?? 'Invalid token.', 'negative');
			header('Location: service_manager.php');
			exit;
		}

		$app_dir = dirname(__DIR__);
		$found   = [];
		try {
			$rii = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($app_dir, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ($rii as $file) {
				if ($file->isFile() && $file->getExtension() === 'service') {
					$unit_name = $file->getFilename();
					if (!isset($found[$unit_name])) {
						$found[$unit_name] = $file->getPathname();
					}
				}
			}
		} catch (Exception $e) {
			message::add('Scan error: ' . $e->getMessage(), 'negative');
			header('Location: service_manager.php');
			exit;
		}

		$registered = $database->select("select service_manager_service_uuid, systemd_service from v_service_manager_services", null, 'all') ?: [];
		$registered_by_unit = [];
		foreach ($registered as $r) {
			$registered_by_unit[$r['systemd_service']] = $r['service_manager_service_uuid'];
		}

		$added = 0;
		$removed = 0;

		foreach ($found as $unit_name => $file_path) {
			$service_name = preg_replace('/\.service$/', '', $unit_name);
			$display_name = null;
			$content = @file_get_contents($file_path);
			if ($content && preg_match('/^\s*Description\s*=\s*(.+)$/m', $content, $m)) {
				$display_name = trim($m[1]);
			}
			$display_name = $display_name ?? $service_name;

			if (!isset($registered_by_unit[$unit_name])) {
				$database->execute(
					"insert into v_service_manager_services
						(service_manager_service_uuid, service_name, display_name, systemd_service,
						 service_file_path, app_path, description, last_status,
						 systemd_installed, systemd_enabled, insert_date)
					values (:uuid, :service_name, :display_name, :unit_name,
					        :file_path, :app_path, :description, 'unknown',
					        'unknown', 'unknown', NOW())",
					[
						'uuid'         => uuid(),
						'service_name' => $service_name,
						'display_name' => $display_name,
						'unit_name'    => $unit_name,
						'file_path'    => $file_path,
						'app_path'     => dirname($file_path),
						'description'  => $display_name,
					]
				);
				$added++;
			} else {
				$database->execute(
					"update v_service_manager_services
					set display_name = :display_name, service_file_path = :file_path,
					    app_path = :app_path, description = :description
					where systemd_service = :unit_name",
					[
						'display_name' => $display_name,
						'file_path'    => $file_path,
						'app_path'     => dirname($file_path),
						'description'  => $display_name,
						'unit_name'    => $unit_name,
					]
				);
			}
		}

		foreach ($registered_by_unit as $unit_name => $uuid) {
			if (!isset($found[$unit_name])) {
				$database->execute(
					"delete from v_service_manager_services where service_manager_service_uuid = :uuid",
					['uuid' => $uuid]
				);
				$removed++;
			}
		}

		$msg = "Scan complete.";
		if ($added)   { $msg .= " Added: $added."; }
		if ($removed) { $msg .= " Removed: $removed."; }
		message::add($msg, 'positive');
		header('Location: service_manager.php');
		exit;
	}

//get registered services from the database
	$sql = "
		select
			service_manager_service_uuid,
			display_name,
			systemd_service,
			service_file_path,
			description
		from v_service_manager_services
		order by display_name asc
	";
	$services = $database->select($sql, null, 'all') ?: [];

//check live systemd status for each service
	function systemd_status($unit) {
		$safe = escapeshellarg($unit);

		//installed: unit file exists in a standard systemd path
		$installed = false;
		foreach (['/etc/systemd/system/', '/lib/systemd/system/', '/usr/lib/systemd/system/', '/run/systemd/system/'] as $dir) {
			if (file_exists($dir . $unit)) { $installed = true; break; }
		}

		//enabled
		$enabled_out = [];
		exec("systemctl is-enabled $safe 2>/dev/null", $enabled_out);
		$enabled = trim($enabled_out[0] ?? 'unknown');

		//active
		$active_out = [];
		exec("systemctl is-active $safe 2>/dev/null", $active_out);
		$active = trim($active_out[0] ?? 'unknown');

		return ['installed' => $installed, 'enabled' => $enabled, 'active' => $active];
	}

	function status_badge($color, $label) {
		return "<span style='display:inline-block;width:10px;height:10px;border-radius:50%;background:$color;margin-right:5px;vertical-align:middle;'></span>"
			."<span style='color:$color;font-weight:bold;vertical-align:middle;'>".escape($label)."</span>";
	}

//create CSRF token
	$object = new token;
	$token  = $object->create($_SERVER['PHP_SELF']);

//set page title
	$document['title'] = $text['title-service_manager'];
	require_once "resources/header.php";

//action bar
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-service_manager']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<form method='post' style='display:inline;'>\n";
	echo "		<input type='hidden' name='scan' value='1'>\n";
	echo "		<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo button::create(['type'=>'submit','label'=>'Scan for Services','icon'=>'search']);
	echo "		</form>\n";
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>$settings->get('theme', 'button_icon_reload', 'sync'),'onclick'=>'location.reload();','style'=>'margin-left:6px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<p>".$text['title_description-service_manager']."</p>\n";

//services table
	echo "<style>#tbl_service_manager td { vertical-align: middle; }</style>\n";
	echo "<div class='card'>\n";
	echo "<table id='tbl_service_manager' class='list' style='table-layout:auto;'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>".$text['label-display_name']."</th>\n";
	echo "	<th class='center'>Installed</th>\n";
	echo "	<th class='center'>Enabled</th>\n";
	echo "	<th class='center'>".$text['label-status']."</th>\n";
	echo "</tr>\n";

	if (!empty($services)) {
		foreach ($services as $row) {
			$file_path = $row['service_file_path'] ?? '';
			$unit_name = $row['systemd_service'] ?? '';
			$status    = systemd_status($unit_name);

			//installed
			$installed_badge = $status['installed']
				? status_badge('#28a745', 'Yes')
				: status_badge('#dc3545', 'No');

			//enabled
			$enabled = $status['enabled'];
			if (!$status['installed']) {
				$enabled_badge = "<span style='color:#aaa;'>—</span>";
			} elseif ($enabled === 'enabled' || $enabled === 'static') {
				$enabled_badge = status_badge('#28a745', ucfirst($enabled));
			} elseif ($enabled === 'disabled') {
				$enabled_badge = status_badge('#ffc107', 'Disabled');
			} elseif ($enabled === 'masked') {
				$enabled_badge = status_badge('#dc3545', 'Masked');
			} else {
				$enabled_badge = "<span style='color:#888;'>".escape($enabled)."</span>";
			}

			//active status
			if (!$status['installed']) {
				$status_badge = "<span style='color:#aaa;'>—</span>";
			} else {
				switch ($status['active']) {
					case 'active':       $status_badge = status_badge('#28a745', 'Running');  break;
					case 'inactive':     $status_badge = status_badge('#dc3545', 'Stopped');  break;
					case 'failed':       $status_badge = status_badge('#dc3545', 'Failed');   break;
					case 'activating':   $status_badge = status_badge('#007bff', 'Starting'); break;
					case 'deactivating': $status_badge = status_badge('#6c757d', 'Stopping'); break;
					default:             $status_badge = status_badge('#6c757d', 'Unknown');
				}
			}

			echo "<tr class='list-row'>\n";
			echo "	<td>\n";
			echo "		<b>".escape($row['display_name'])."</b>\n";
			if (!empty($row['description']) && $row['description'] !== $row['display_name']) {
				echo "		<div style='font-size:0.82em;color:#666;'>".escape($row['description'])."</div>\n";
			}
			if (!empty($file_path)) {
				echo "		<div style='font-size:0.78em;color:#999;font-family:monospace;margin-top:2px;'>".htmlspecialchars($file_path, ENT_QUOTES, 'UTF-8')."</div>\n";
			}
			echo "	</td>\n";
			echo "	<td class='center'>".$installed_badge."</td>\n";
			echo "	<td class='center'>".$enabled_badge."</td>\n";
			echo "	<td class='center'>".$status_badge."</td>\n";
			echo "</tr>\n";
		}
	} else {
		echo "<tr><td colspan='4' class='center' style='padding:30px;color:#888;'>".$text['message-no_services']."</td></tr>\n";
	}

	echo "</table>\n";
	echo "</div>\n";

	require_once "resources/footer.php";
?>
