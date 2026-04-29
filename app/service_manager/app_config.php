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

	//application details
		$apps[$x]['name'] = "Service Manager";
		$apps[$x]['uuid'] = "f7a3c291-5b8d-4e2a-9601-3d8e7c9b0a1f";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Monitors and controls system services for FusionPBX applications.";
		$apps[$x]['description']['en-gb'] = "Monitors and controls system services for FusionPBX applications.";
		$apps[$x]['description']['de-de'] = "Überwacht und steuert Systemdienste für FusionPBX-Anwendungen.";
		$apps[$x]['description']['es-mx'] = "Supervisa y controla los servicios del sistema para aplicaciones FusionPBX.";
		$apps[$x]['description']['fr-fr'] = "Surveille et contrôle les services système pour les applications FusionPBX.";
		$apps[$x]['description']['pt-br'] = "Monitora e controla os serviços do sistema para aplicações FusionPBX.";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "service_manager_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "a6b5c4d3-2e1f-4a8b-9c7d-0e5f6a7b8c9d";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "service_manager_start";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "service_manager_stop";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "service_manager_restart";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;

	//database schema - v_service_manager_services
		$y=0;
		$apps[$x]['db'][$y]['table']['name'] = "v_service_manager_services";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "service_manager_service_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "service_name";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(255)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "display_name";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(255)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "systemd_service";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(255)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "app_path";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "text";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "description";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "text";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "service_file_path";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "text";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "systemd_installed";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(10)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "systemd_enabled";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(50)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "last_status";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(50)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "last_status_check";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "timestamp without time zone";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "datetime";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "insert_date";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "timestamp without time zone";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "datetime";
		$y++;

	//database schema - v_service_manager_actions
		$apps[$x]['db'][$y]['table']['name'] = "v_service_manager_actions";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "service_manager_action_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "service_manager_service_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "action";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(50)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "requested_by";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(255)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "requested_at";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "timestamp without time zone";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "datetime";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "status";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "varchar(50)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "completed_at";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "timestamp without time zone";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "datetime";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "output";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "text";

?>
