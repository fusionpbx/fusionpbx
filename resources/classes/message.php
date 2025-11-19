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

class message {

	/**
	 * Returns the total number of messages in the session.
	 *
	 * @return int The number of messages, or 0 if no messages are present in the session.
	 */
	static function count() {
		return isset($_SESSION["messages"]) && is_array($_SESSION["messages"]) ? sizeof($_SESSION["messages"]) : 0;
	}

	/**
	 * Renders session messages into HTML.
	 *
	 * @param bool   $clear_messages Whether to clear the session 'messages' array after rendering. Defaults to true.
	 * @param string $spacer         The indentation spacer for the generated HTML code.
	 *
	 * @return string The rendered HTML message display code.
	 */
	static function html($clear_messages = true, $spacer = "") {
		$html = "{$spacer}//render the messages\n";
		$spacer .= "\t";
		if (isset($_SESSION['message']) || isset($_SESSION['messages'])) {
			if (!empty($_SESSION['message']) && !is_array($_SESSION['message'])) {
				self::add($_SESSION['message'], $_SESSION['message_mood'] ?? null, $_SESSION['message_delay'] ?? null);
				unset($_SESSION['message'], $_SESSION['message_mood'], $_SESSION['message_delay']);
			}
			if (!empty($_SESSION['messages']) && is_array($_SESSION['messages']) && @sizeof($_SESSION['messages']) != 0) {
				foreach ($_SESSION['messages'] as $message_mood => $message) {
					$message_text = str_replace(["\r\n", "\n", "\r"], '\\n', addslashes(join('<br/>', $message['message'])));
					$message_delay = array_sum($message['delay']) / count($message['delay']);
					$html .= "{$spacer}display_message('$message_text', '$message_mood', '$message_delay');\n";
				}
			}
		}
		if ($clear_messages) {
			unset($_SESSION['messages']);
		}
		return $html;
	}

	/**
	 * Adds a message to the session messages array.
	 *
	 * @param string      $message The message to add.
	 * @param string|null $mood    The mood of the message. Defaults to 'positive'.
	 * @param int|null    $delay   The delay before displaying the message. Defaults to the theme's default text
	 *                             message delay in milliseconds.
	 *
	 * @return void
	 */
	static function add($message, $mood = null, $delay = null) {
		//set mood and delay
		$mood = $mood ?: 'positive';
		$delay = $delay ?: (1000 * (float)$_SESSION['theme']['message_delay']['text']);
		//ignore duplicate messages
		if (isset($_SESSION["messages"]) && !empty($_SESSION["messages"][$mood]['message'])) {
			if (!in_array($message, $_SESSION["messages"][$mood]['message'])) {
				$_SESSION["messages"][$mood]['message'][] = $message;
				$_SESSION["messages"][$mood]['delay'][] = $delay;
			}
		} else {
			$_SESSION["messages"][$mood]['message'][] = $message;
			$_SESSION["messages"][$mood]['delay'][] = $delay;
		}
	}
}
