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
	  Portions created by the Initial Developer are Copyright (C) 2008-2018
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  Tim Fry <tim.fry@hotmail.com>
	 */

/*
Event examples:

Event-Name: API
Core-UUID: e0943a8d-9d09-4446-bce8-da000a403b47
FreeSWITCH-Hostname: fs
FreeSWITCH-Switchname: fs
FreeSWITCH-IPv4: 172.20.0.5
FreeSWITCH-IPv6: ::1
Event-Date-Local: 2023-11-02 12:39:56
Event-Date-GMT: Thu, 02 Nov 2023 12:39:56 GMT
Event-Date-Timestamp: 1698928796902089
Event-Calling-File: switch_loadable_module.c
Event-Calling-Function: switch_api_execute
Event-Calling-Line-Number: 2954
Event-Sequence: 21603
API-Command: reloadacl

Event-Name: API
Core-UUID: e0943a8d-9d09-4446-bce8-da000a403b47
FreeSWITCH-Hostname: fs
FreeSWITCH-Switchname: fs
FreeSWITCH-IPv4: 172.20.0.5
FreeSWITCH-IPv6: ::1
Event-Date-Local: 2023-11-02 12:39:57
Event-Date-GMT: Thu, 02 Nov 2023 12:39:57 GMT
Event-Date-Timestamp: 1698928797082087
Event-Calling-File: switch_loadable_module.c
Event-Calling-Function: switch_api_execute
Event-Calling-Line-Number: 2954
Event-Sequence: 21608
API-Command: sofia
API-Command-Argument: xmlstatus

Event-Name: RELOADXML
Core-UUID: e0943a8d-9d09-4446-bce8-da000a403b47
FreeSWITCH-Hostname: fs
FreeSWITCH-Switchname: fs
FreeSWITCH-IPv4: 172.20.0.5
FreeSWITCH-IPv6: ::1
Event-Date-Local: 2023-11-02 12:39:56
Event-Date-GMT: Thu, 02 Nov 2023 12:39:56 GMT
Event-Date-Timestamp: 1698928796942090
Event-Calling-File: switch_xml.c
Event-Calling-Function: switch_xml_open_root
Event-Calling-Line-Number: 2388
Event-Sequence: 21604

Event-Subclass: fusion::file
Command: sendevent CUSTOM
API-Command: cache
API-Command-Argument: flush
Event-UUID: a09af655-8451-4291-9a24-a1057a89a522
Event-Name: CUSTOM
Core-UUID: e0943a8d-9d09-4446-bce8-da000a403b47
FreeSWITCH-Hostname: fs
FreeSWITCH-Switchname: fs
FreeSWITCH-IPv4: 172.20.0.5
FreeSWITCH-IPv6: ::1
Event-Date-Local: 2023-11-02 12:40:00
Event-Date-GMT: Thu, 02 Nov 2023 12:40:00 GMT
Event-Date-Timestamp: 1698928800162089
Event-Calling-File: mod_event_socket.c
Event-Calling-Function: parse_command
Event-Calling-Line-Number: 2275
Event-Sequence: 21615

In the examples above, The name of the event is set in the following order:
	- Event-Name is replaced with API-Command
*/

	/**
	 * Implement the event_listener to allow the exec function to be called
	 * @author tim
	 */
	interface event_listener {
		public function event_name(): string;
		public function exec(event $e): void;
	}
