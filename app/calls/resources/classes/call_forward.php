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
	Copyright (C) 2010 - 2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Errol Samuels <voiptology@gmail.com>
	Andrew Querol <andrew@querol.me>
*/
include "root.php";

//define the call_forward class
	class call_forward extends feature_base {
		public function disable(array $uuids) {
			if (!permission_exists('call_forward')) {
				return;
			}
			$this->set($uuids, false);
		}

		public function enable(array $uuids) {
			if (!permission_exists('call_forward')) {
				return;
			}
			$this->set($uuids, true);
		}

		/**
		 * toggle records
		 * @param array $uuids The uuids to toggle
		 */
		public function toggle(array $uuids) {
			if (!permission_exists('call_forward')) {
				return;
			}

			$this->set($uuids, null);
		} //function

		protected function update(array $extension) : array {
			$extension = parent::update($extension);
			//disable other features
			if ($extension['forward_all_enabled'] == feature_base::enabled) {
				$extension['do_not_disturb'] = feature_base::disabled; //false
				$extension['follow_me_enabled'] = feature_base::disabled; //false
			}
			return $extension;
		}

		/**
		 * @param array $uuids The extension UUIDs to perform this operation on
		 * @param ?bool $new_state The new state or null to toggle
		 */
		private function set(array $uuids, ?bool $new_state) {
			$extensions = $this->getExistingState($uuids);

			// Set the DND state
			$updates = array();
			foreach ($extensions as $uuid => $extension) {
				// Create a copy of $new_state since we do not want to clobber it when toggling.
				$updated_state = $new_state;
				if (is_null($new_state)) {
					$updated_state = $extension['forward_all_enabled'] != feature_base::enabled;
				}
				$destination_exists = $extension['forward_all_destination'] != '';

				// Update the extension array with the new DND state
				$extension['forward_all_enabled'] = $updated_state && $destination_exists ? feature_base::enabled : feature_base::disabled;

				// Build the update array and perform any per-extension updates
				$updates['extensions'][] = $this->update($extension);
			}
			$this->save($updates);

			unset($records, $extensions, $extension, $updates);
		}
	}// class

?>