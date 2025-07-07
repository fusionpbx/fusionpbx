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

/**
 * Description of extension_filter
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class extension_filter {

	private $extensions;

	public function __construct(array $extensions = []) {
		//organize the extensions in a way we can use isset for fast lookup
		foreach ($extensions as $extension) {
			$presence_id = $extension['user'] . '@' . $extension['user_context'];
			$this->extensions[$presence_id] = true;
		}
	}

	public function __invoke(string $key, $value): ?bool {
		//only match on channel_presence_id key
		if ($key === 'channel_presence_id' && !isset($this->extensions[$value])) {
			// Drop the message
			return null;
		}
		//no key to match on
		return true;
	}
}
