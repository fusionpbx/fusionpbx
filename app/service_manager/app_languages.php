<?php

	//assign the variables
		$language['title-service_manager'] = "Service Manager";
		$language['title_description-service_manager'] = "Monitor and control system services detected from FusionPBX apps.";

	//labels
		$language['label-service_name'] = "Service";
		$language['label-display_name'] = "Name";
		$language['label-systemd_service'] = "Unit";
		$language['label-status'] = "Status";
		$language['label-last_checked'] = "Last Checked";
		$language['label-action'] = "Pending Action";
		$language['label-actions'] = "Actions";
		$language['label-description'] = "Description";
		$language['label-app_path'] = "App Path";

	//status labels
		$language['label-running'] = "Running";
		$language['label-stopped'] = "Stopped";
		$language['label-unknown'] = "Unknown";
		$language['label-active'] = "Active";
		$language['label-inactive'] = "Inactive";
		$language['label-failed'] = "Failed";

	//action labels
		$language['label-pending'] = "Requested";
		$language['label-processing'] = "Processing";
		$language['label-completed'] = "Completed";
		$language['label-action_failed'] = "Action Failed";

	//buttons
		$language['button-start'] = "Start";
		$language['button-stop'] = "Stop";
		$language['button-restart'] = "Restart";
		$language['button-refresh'] = "Refresh";

	//messages
		$language['message-action_queued'] = "Action queued. The background worker will process it shortly.";
		$language['message-worker_offline'] = "Warning: Service Manager worker may be offline. Actions may not be processed.";
		$language['message-no_services'] = "No services discovered yet. Ensure the service-manager.service is running.";
		$language['message-confirm_stop'] = "Are you sure you want to stop this service?";
		$language['message-confirm_restart'] = "Are you sure you want to restart this service?";
		$language['message-success'] = "Action successfully queued.";
		$language['message-invalid_action'] = "Invalid action requested.";
		$language['message-service_not_found'] = "Service not found.";
		$language['message-permission_denied'] = "Permission denied.";

	//install notice
		$language['notice-install_worker'] = "To install the background worker, run as root:";

?>
