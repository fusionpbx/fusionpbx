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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Tim Fry <tim@fusionpbx.com>
*/

//application details
	$apps[$x]['name'] = "Operator Panel";
	$apps[$x]['uuid'] = "8493ad5d-9240-426c-b330-774ed0bd7ede";
	$apps[$x]['category'] = "Switch";
	$apps[$x]['subcategory'] = "";
	$apps[$x]['version'] = "1.0";
	$apps[$x]['license'] = "Mozilla Public License 1.1";
	$apps[$x]['url'] = "http://www.fusionpbx.com";
	$apps[$x]['description']['en-us'] = "A real-time operator panel using WebSockets for live call, conference, and agent management.";
	$apps[$x]['description']['en-gb'] = "A real-time operator panel using WebSockets for live call, conference, and agent management.";
	$apps[$x]['description']['ar-eg'] = "";
	$apps[$x]['description']['de-at'] = "";
	$apps[$x]['description']['de-ch'] = "";
	$apps[$x]['description']['de-de'] = "";
	$apps[$x]['description']['es-cl'] = "";
	$apps[$x]['description']['es-mx'] = "";
	$apps[$x]['description']['fr-ca'] = "";
	$apps[$x]['description']['fr-fr'] = "";
	$apps[$x]['description']['he-il'] = "";
	$apps[$x]['description']['it-it'] = "";
	$apps[$x]['description']['ka-ge'] = "";
	$apps[$x]['description']['nl-nl'] = "";
	$apps[$x]['description']['pl-pl'] = "";
	$apps[$x]['description']['pt-br'] = "";
	$apps[$x]['description']['pt-pt'] = "";
	$apps[$x]['description']['ro-ro'] = "";
	$apps[$x]['description']['ru-ru'] = "";
	$apps[$x]['description']['sv-se'] = "";
	$apps[$x]['description']['uk-ua'] = "";

// Permissions are inherited from the basic_operator_panel app (uuid: dd3d173a-5d51-4231-ab22-b18c5b712bb2).
// The following permissions are reused:
//   operator_panel_view       - view the panel
//   operator_panel_manage     - supervisor: transfer, agent management
//   operator_panel_originate  - originate calls via drag-and-drop
//   operator_panel_eavesdrop  - listen to calls
//   operator_panel_hangup     - disconnect calls
//   operator_panel_record     - record calls
//   operator_panel_call_details - view caller/callee details
//   operator_panel_on_demand  - on-demand availability status

// Tab visibility permissions
	$y = 0;
	$apps[$x]['permissions'][$y]['name'] = "operator_panel_extensions";
	$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
	$apps[$x]['permissions'][$y]['groups'][] = "admin";
	$y++;
	$apps[$x]['permissions'][$y]['name'] = "operator_panel_calls";
	$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
	$apps[$x]['permissions'][$y]['groups'][] = "admin";
	$y++;
	$apps[$x]['permissions'][$y]['name'] = "operator_panel_conferences";
	$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
	$apps[$x]['permissions'][$y]['groups'][] = "admin";
	$y++;
	$apps[$x]['permissions'][$y]['name'] = "operator_panel_agents";

//default settings
	$y = 0;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "e150a7ae-e06c-4e48-98a2-fec35b469895";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "reconnect_delay";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "500";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Base delay in milliseconds before attempting to reconnect after disconnect.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "0dfc9e80-7451-46c3-b89d-0c14679d655e";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "ping_interval";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "5000";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Interval in milliseconds between keepalive ping requests.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "eeca82cf-3743-4728-9a4d-207a647788e0";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "auth_timeout";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "5000";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Timeout in milliseconds waiting for WebSocket authentication before redirecting to login.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "50b08093-19f6-450f-b534-b4aff88fdd38";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "pong_timeout";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "1500";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Timeout in milliseconds waiting for pong response before counting a failure.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "cb357440-9d16-40b3-ba99-ea7973a21610";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "pong_timeout_max_retries";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "2";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Number of pong timeouts allowed before reloading the page. During retries, status indicator shows warning color.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "10057611-f272-45a4-a23f-144633277596";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "max_reconnect_delay";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "5000";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Maximum delay in milliseconds between reconnection attempts (exponential backoff cap).";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "0cfc12b2-c9fd-4a45-99ef-fc7ed3616954";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "refresh_interval";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "0";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Optional interval in milliseconds to periodically refresh data. Set to 0 to disable (rely on WebSocket events only).";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "b4d6fbcb-0a6e-4e92-bbe6-d8c68776a4b5";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "agent_stats_interval";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "10";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Interval in seconds between agent stats broadcast to all subscribers.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "a2482295-3a47-4188-a4b8-6c303f2e62e8";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "card_label_position";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "left";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Position of extension group card labels. Valid values: top, left, right, bottom, hidden.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "c4a7db2a-ec69-4aef-a95b-1a8f2d7d2de1";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "registrations_reconcile_enabled";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "false";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Enable periodic registration-state reconciliation polling via action request. Disable to rely only on registration_change events.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "f3be5aa5-2b7e-4e8c-a00a-ae9e001ec646";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "operator_panel";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "debug_show_permissions_mode";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "off";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Debug permissions output mode: 'off', 'bytes', or 'full'.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "ef6bb923-21cd-4c0d-acdd-60646bfac3ab";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_connected";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "#28a745";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Color of the status indicator when connected and receiving pong responses.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "2749a174-80ec-475f-a3a3-40be46ce524f";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_warning";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "#ffc107";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Color of the status indicator when ping sent but pong not yet received (warning state).";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "dad10272-120f-42c3-8da6-34f17315dc6a";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_disconnected";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "#dc3545";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Color of the status indicator when disconnected or not authenticated.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "01be0a26-8ad4-4558-93e5-41979a2e84d7";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_connecting";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "#6c757d";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Color of the status indicator when connecting or authenticating.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "b6a02563-e1d8-4f7e-9732-68e2f1266d6c";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_icon_connected";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fa-solid fa-plug-circle-check";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for connected status.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "d06a9ef7-d589-4267-920f-351b9255b6d0";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_icon_warning";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fa-solid fa-plug-circle-exclamation";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for warning status (ping sent, awaiting pong).";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "7f747305-b93d-467f-9d27-44ab76c77c16";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_icon_disconnected";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fa-solid fa-plug-circle-xmark";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for disconnected status.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "f01ebd98-461b-4644-9946-38f3b8fd7fca";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_icon_connecting";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fa-solid fa-plug fa-fade";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for connecting status.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "ec8f0281-5d2b-4a3b-9481-f2c9880db101";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_mute";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-microphone";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference mute action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "3d4dbe79-f1af-4bea-804e-27ee0ac31302";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_unmute";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-microphone-slash";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference unmute action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "b15d1ae4-6087-48cf-a46f-148fb5236103";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_deaf";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-headphones";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference deaf action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "2b560cff-58ef-42c2-b4e5-08d77c339a04";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_undeaf";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-deaf";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference undeaf action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "21f8af30-d08f-4342-bddb-bb68804e1005";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_energy_up";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-plus";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference energy up action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "4fdc30b2-7db8-4c9f-9b7e-4c78fa757706";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_energy_down";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-minus";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference energy down action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "2b706528-8a6b-409c-84bb-95f905344707";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_volume_down";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-volume-down";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference volume down action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "9220ec76-6760-4b75-a29e-503b94ca7508";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_volume_up";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-volume-up";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference volume up action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "866dc078-68cb-43aa-b70b-b73d213dbd09";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_gain_down";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-sort-amount-down";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference gain down action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "d4bde1f1-b0d8-4836-a78b-f8c4bb510a10";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_gain_up";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-sort-amount-up";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference gain up action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "f55d2796-46b5-4ca2-998f-256184489b11";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_conference_icon_kick";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "fas fa-ban";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Font Awesome icon class for the conference kick action.";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "b367ce0d-beb0-424a-9fc9-0c83690001ee";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "theme";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "operator_panel_status_show_icon";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "boolean";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Show the Font Awesome icon next to the status text in the connection status indicator. Set to false to show text only.";
