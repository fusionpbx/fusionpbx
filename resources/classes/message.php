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
	Portions created by the Initial Developer are Copyright (C) 2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/

if (!class_exists('message')) {
	class message {

		static function add($message, $mood = null, $delay = null) {
			//set mood and delay
				$mood = $mood ?: 'positive';
				$delay = $delay ?: (1000 * (float) $_SESSION['theme']['message_delay']['text']);
			//ignore duplicate messages
				if (is_array($_SESSION["messages"][$mood]['message']) && @sizeof($_SESSION["messages"][$mood]['message']) != 0) {
					if (!in_array($message, $_SESSION["messages"][$mood]['message'])) {
						$_SESSION["messages"][$mood]['message'][] = $message;
						$_SESSION["messages"][$mood]['delay'][] = $delay;
					}
				}
				else {
					$_SESSION["messages"][$mood]['message'][] = $message;
					$_SESSION["messages"][$mood]['delay'][] = $delay;
				}
		}

		static function count() {
			return is_array($_SESSION["messages"]) ? sizeof($_SESSION["messages"]) : 0;
		}

		static function html($clear_messages = true, $spacer = "") {
			$html = "${spacer}//render the messages\n";
			$spacer .="\t";
			if (is_string($_SESSION['message']) && strlen(trim($_SESSION['message'])) > 0) {
				self::add($_SESSION['message'], $_SESSION['message_mood'], $_SESSION['message_delay']);
				unset($_SESSION['message'], $_SESSION['message_mood'], $_SESSION['message_delay']);
			}
			if (is_array($_SESSION['messages']) && count($_SESSION['messages']) > 0 ) {
				foreach ($_SESSION['messages'] as $message_mood => $message) {
					$message_text = str_replace(array("\r\n", "\n", "\r"),'\\n',addslashes(join('<br/>', $message['message'])));
					$message_delay = array_sum($message['delay'])/count($message['delay']);
					$html .= "${spacer}display_message('$message_text', '$message_mood', '$message_delay');\n";
				}
			}
			if ($clear_messages) {
				unset($_SESSION['messages']);
			}
			return $html;
		}
	}
}

?>
