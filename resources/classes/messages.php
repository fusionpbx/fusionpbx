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

if (!class_exists('messages')) {
	class messages {

		static function add($message, $mood = NULL, $delay = NULL) {
			$_SESSION["messages"][] = array(message => $message, mood => $mood, delay => $delay);
		}
		
		static function html($clear_messages = true) {
			$html = "";
			if (strlen($_SESSION['message']) > 0) {
				$message_text = addslashes($_SESSION['message']);
				$message_mood = $_SESSION['message_mood'] ?: 'default';
				$message_delay = $_SESSION['message_delay'];

				$html .= "display_message('".$message_text."', '".$message_mood."'";
				if ($message_delay != '') {
					$html .= ", '".$message_delay."'";
				}
				$html .= ");\n";
			}
			if(count($_SESSION['messages']) > 0 ){
				foreach ($_SESSION['messages'] as $message) {
					$message_text = addslashes($message['message']);
					$message_mood = $message['mood'] ?: 'default';
					$message_delay = $message['delay'];

					$html .= "display_message('".$message_text."', '".$message_mood."'";
					if ($message_delay != '') {
						$html .= ", '".$message_delay."'";
					}
					$html .= ");\n";
				}
			}
			if($clear_messages) {
				unset($_SESSION['message'], $_SESSION['message_mood'], $_SESSION['message_delay']);
				unset($_SESSION['messages']);
			}
			return $html;
		}
	}
}

?>
