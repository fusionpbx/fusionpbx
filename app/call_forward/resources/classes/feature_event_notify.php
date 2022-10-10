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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	KonradSC <konrd@yahoo.com>
*/

//define the feature_event_notify class
	class feature_event_notify {

		public $debug;
		public $domain_name;
		public $extension;
		public $forward_all_destination;
		public $forward_all_enabled;
		public $forward_busy_destination;
		public $forward_busy_enabled;
		public $forward_no_answer_destination;
		public $forward_no_answer_enabled;
		public $do_not_disturb;
		public $ring_count;

	//feature_event method		
		public function send_notify() {
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				// Get the SIP profiles for the extension
				$command = "sofia_contact */{$this->extension}@{$this->domain_name}";
				$contact_string = event_socket_request($fp, "api ".$command);
				// The first value in the array will be full matching text, the second one will be the array of profile matches
				preg_match_all('/sofia\/([^,]+)\/(?:[^,]+)/', $contact_string, $matches);
				if (sizeof($matches) != 2 || sizeof($matches[1]) < 1) {
					$profiles = array("internal");
				} else {
					// We have at least one profile, get all of the unique profiles
					$profiles = array_unique($matches[1]);
				}

				foreach ($profiles as $profile) {
					//send the event
					$event = "sendevent SWITCH_EVENT_PHONE_FEATURE\n";
					$event .= "profile: " . $profile . "\n";
					$event .= "user: " . $this->extension . "\n";
					$event .= "host: " . $this->domain_name . "\n";
					$event .= "device: \n";
					$event .= "Feature-Event: init\n";
					$event .= "forward_immediate_enabled: " . $this->forward_all_enabled . "\n";
					$event .= "forward_immediate: " . $this->forward_all_destination . "\n";
					$event .= "forward_busy_enabled: " . $this->forward_busy_enabled . "\n";
					$event .= "forward_busy: " . $this->forward_busy_destination . "\n";
					$event .= "forward_no_answer_enabled: " . $this->forward_no_answer_enabled . "\n";
					$event .= "forward_no_answer: " . $this->forward_no_answer_destination . "\n";
					$event .= "doNotDisturbOn: " . $this->do_not_disturb . "\n";
					$event .= "ringCount: " . $this->ring_count . "\n";
					event_socket_request($fp, $event);
				}
				fclose($fp);
			}
		} //function
	
	} //class

?>
