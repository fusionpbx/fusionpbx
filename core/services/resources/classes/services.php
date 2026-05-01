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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * services class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
class services {

	/**
	 * declare constant variables
	 */
	const app_name = 'services';
	const app_uuid = '540c3ec2-4f0c-467f-a09d-d644439c96f2';

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

	/**
	 * declare private variables
	 */
	private $name;
	private $table;
	private $toggle_field;
	private $toggle_values;
	private $description_field;
	private $location;

	/**
	 * Constructor for the class.
	 *
	 * This method initializes the object with setting_array and session data.
	 *
	 * @param array $setting_array An optional array of settings to override default values. Defaults to [].
	 */
	public function __construct(array $setting_array = []) {
		// Set objects
		$this->database = $setting_array['database'] ?? database::new();

		// Assign the variables
		$this->app_name = 'services';
		$this->app_uuid = '540c3ec2-4f0c-467f-a09d-d644439c96f2';
		$this->name = 'service';
		$this->table = 'services';
		$this->toggle_field = 'service_enabled';
		$this->toggle_values = ['true','false'];
		$this->description_field = 'service_description';
		$this->location = 'services.php';
	}

	/**
	 * called when there are no references to a particular object
	 * unset the variables used in the class
	 */
	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}

	/**
	 * Deletes one or multiple records.
	 *
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function delete($records) {
		// Permission not found
		if (!permission_exists($this->name.'_delete')) {
			return;
		}

		// Add multi-lingual support
		$language = new text;
		$text = $language->get();

		// Validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: '.$this->location);
			exit;
		}

		// Delete multiple records
		if (is_array($records) && @sizeof($records) != 0) {
			// Build the delete array
			$x = 0;
			foreach ($records as $record) {
				// Add to the array
				if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
					$array[$this->table][$x][$this->name.'_uuid'] = $record['uuid'];
				}

				// Increment the id
				$x++;
			}

			// Delete the checked rows
			if (is_array($array) && @sizeof($array) != 0) {
				//execute delete
				$this->database->delete($array);
				unset($array);

				// Set the message
				message::add($text['message-delete'], 'alert');
			}
			unset($records);
		}
	}

	/**
	 * Toggles the state of one or more records.
	 *
	 * @param array $records  An array of record IDs to delete, where each ID is an associative array
	 *                        containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                        whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function toggle($records) {
		// Permission not found
		if (!permission_exists($this->name.'_edit')) {
			return;
		}

		// Add multi-lingual support
		$language = new text;
		$text = $language->get();

		// Validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: '.$this->location);
			exit;
		}

		// Toggle the checked records
		if (is_array($records) && @sizeof($records) != 0) {
			// Get current toggle state
			foreach($records as $record) {
				if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
					$uuids[] = "'".$record['uuid']."'";
				}
			}
			if (is_array($uuids) && @sizeof($uuids) != 0) {
				$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
				$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
				$rows = $this->database->select($sql, $parameters, 'all');
				if (is_array($rows) && @sizeof($rows) != 0) {
					foreach ($rows as $row) {
						$states[$row['uuid']] = $row['toggle'];
					}
				}
				unset($sql, $parameters, $rows, $row);
			}

			// Build the update array
			$x = 0;
			foreach($states as $uuid => $state) {
				// Create the array
				$array[$this->table][$x][$this->name.'_uuid'] = $uuid;
				$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

				// Increment the id
				$x++;
			}

			// Save the changes
			if (is_array($array) && @sizeof($array) != 0) {
				// Save the array
				$this->database->save($array);
				unset($array);

				// Set the message
				message::add($text['message-toggle']);
			}
			unset($records, $states);
		}
	}

	/**
	 * Reload the state of a service without restarting it
	 *
	 * @param array $records  An array of services to reload, where each ID is an associative array
	 *                        containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                        whether the corresponding checkbox was checked to reload the service.
	 *
	 * @return void No return value;
	 */
	public function reload($records) {
		// Permission not found
		if (!permission_exists($this->name.'_edit')) {
			return;
		}

		// Add multi-lingual support
		$language = new text;
		$text = $language->get();

		// Validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: '.$this->location);
			exit;
		}

		// reloaad the checked services
		if (is_array($records) && @sizeof($records) != 0) {
			$services = '';
			// Get current reload state
			foreach($records as $record) {
				if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
					$uuids[] = "'".$record['uuid']."'";
				}
			}
			// Reload the selected services
			if (is_array($uuids) && @sizeof($uuids) != 0) {
				$sql = "select service_name as name from v_".$this->table." ";
				$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
				$rows = $this->database->select($sql, $parameters, 'all');
				if (is_array($rows) && @sizeof($rows) != 0) {
					foreach ($rows as $row) {
						$service_name = $row['name'];
						$service_class_name = $row['name'].'_service';
						if (class_exists($service_class_name)) {
							if (method_exists($service_class_name, 'send_reload')) {
								// Reload the service
								$service_class_name::send_reload();

								// Add to the list of services that were reloaded
								$services .= "<li>".$service_name."</li>\n";
							}
						}
					}
				}

				// Set the message
				$msg = "<strong>".$text['message-services_reloaded'].":</strong><br />\n";
				$msg .= "<div style='display: flex; justify-content: center;'>\n";
				$msg .= "	<ul style='text-align: left; margin: 0;'>\n";
				$msg .= $services;
				$msg .= "	</ul>\n";
				$msg .= "</div>\n";
				message::add($msg);
			}
		}
	}

	/**
	 * Get the list of services
	 *
	 * This function iterates through all service files found in the application's
	 * core and app directories, and builds an array of the services
	 *
	 * @return array Return a list of services with name
	 */
	public function get_services($details = false, $source = 'database') {

		if ($source == 'files') {
			// Get the list of services
			$core_files = glob(dirname(__DIR__, 4) . "/core/*/resources/service/*.service");
			$app_files = glob(dirname(__DIR__, 4) . "/app/*/resources/service/*.service");
			$service_files = array_merge($core_files, $app_files);

			// Build the services array
			$services = [];
			if (stristr(PHP_OS, 'Linux')) {
				$i = 0;
				foreach($service_files as $file) {
					// Get the service name
					$service_name = $this->find_service_name($file);

					// Get the service status
					if ($details) {
						$service_status = $this->is_running($service_name);
					}

					// Build the services array
					$services[$i]['name'] = $service_name;
					$services[$i]['file'] = $file;

					// Add the service status to the array
					if ($details) {
						$services[$i]['pid'] = $service_status['pid'];
						$services[$i]['status'] = $service_status['status'];
						$services[$i]['etime'] = $service_status['etime'];
					}

					// Increment
					$i++;
				}
			}
		}

		if ($source == 'database') {
			// Get the list
			$sql = "select ";
			$sql .= " service_uuid, ";
			$sql .= " service_name, ";
			$sql .= " service_category, ";
			$sql .= " service_file, ";
			$sql .= " cast(service_enabled as text), ";
			$sql .= " service_description ";
			$sql .= "from v_services ";
			$sql .= "order by service_name asc";
			$database_services = $this->database->select($sql, $parameters ?? null, 'all');
			unset($sql, $parameters);

			$services = [];
			if (stristr(PHP_OS, 'Linux')) {
				$i = 0;
				foreach($database_services as $row) {
					// Get the service status
					if ($details) {
						$service_status = $this->is_running($row['service_name']);
					}

					// Build the services array
					$services[$i]['name'] = $row['service_name'];
					$services[$i]['file'] = $row['service_file'];
					$services[$i]['enabled'] = $row['service_enabled'];
					//$services[$i]['file'] = $file;

					// Add the service status to the array
					if ($details) {
						$services[$i]['pid'] = $service_status['pid'];
						$services[$i]['status'] = $service_status['status'];
						$services[$i]['etime'] = $service_status['etime'];
					}

					// Increment
					$i++;
				}
			}
		}

		// Return the service array
		return $services;
	}

	/**
	 * Add missing services into the database
	 *
	 * This function adds missing services
	 * into the services table in the database
	 *
	 * @return void No return value; this method modifies the database.
	 */
	public function add_missing() {
		// Add multi-lingual support
		$language = new text;
		$text = $language->get();

		// Get the list of services
		$service_array = $this->get_services(false, 'files');

		// Service mapped to the category
		$service_map['api'] = 'system';
		$service_map['call_center_callbacks'] = 'switch';
		$service_map['campaign_logs'] = 'switch';
		$service_map['campaign_queue'] = 'switch';
		$service_map['websockets'] = 'websockets';
		$service_map['active_calls'] = 'websockets';
		$service_map['active_conferences'] = 'websockets';
		$service_map['maintenance_service'] = 'system';
		$service_map['operator_panel'] = 'websockets';
		$service_map['system_status'] = 'websockets';
		$service_map['message_events'] = 'system';
		$service_map['message_queue'] = 'system';
		$service_map['transcribe_queue'] = 'queue';

		// Get the list
		$sql = "select ";
		$sql .= " service_uuid, ";
		$sql .= " service_name, ";
		$sql .= " service_category, ";
		$sql .= " cast(service_enabled as text), ";
		$sql .= " service_description ";
		$sql .= "from v_services ";
		$sql .= "order by service_name asc";
		$database_services = $this->database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);

		// Create an array to store service names from the database
		$service_names = array_column($database_services, 'service_name');

		// Add services that are not in the database
		$service_found = false;
		$services_new  = '';
		$i = 0;
		$array = [];
		foreach ($service_array as $service) {
			// Sanitize the service name
			$service_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $service['name']);

			// Built the array to save to the database
			if (!is_array($service_array) || !in_array($service_name, $service_names)) {
				// Get the category
				$service_category = '';
				if (class_exists($service_name)) {
					if (method_exists($service_name, 'get_category')) {
						$service_category = $service_name::get_category();
					}
				}

				// Alternate method to get the category
				if (empty($service_category)) {
					$service_category = $service_map[$service_name];
				}

				// Set service found to true
				$service_found = true;

				// Append the service label
				$services_new .= "<li>".$service_name."</li>\n";

				// Prepare the array
				$array['services'][$i]['service_uuid'] = uuid();
				$array['services'][$i]['service_name'] = $service_name;
				$array['services'][$i]['service_category'] = $service_category;
				$array['services'][$i]['service_file'] = $service['file'];
				$array['services'][$i]['service_enabled'] = 'true';
				$i++;
			}
		}

		// Add missing services into the database
		if (!empty($array)) {
			// Set temporary permissions
			$p = permissions::new();
			$p->add('service_add', 'temp');

			// Save to the database
			$this->database->save($array);
			unset($array);

			// Remove temporary permissions
			$p->delete('service_add', 'temp');
		}
		if ($service_found) {
			$msg = "<strong>".$text['message-added_new_services'].":</strong><br />\n";
			$msg .= "<div style='display: flex; justify-content: center;'>\n";
			$msg .= "	<ul style='text-align: left; margin: 0;'>\n";
			$msg .= $services_new;
			$msg .= "	</ul>\n";
			$msg .= "</div>\n";
			message::add($msg);
		}
	}

	/**
	 * Upgrade services by copying and enabling them.
	 *
	 * This function iterates through all service files found in the application's
	 * core and app directories, copies each one to /etc/systemd/system, reloads
	 * the daemon, and enables the service.
	 *
	 * @return void No return value;
	 */
	public function upgrade($name = 'all') {
		// Get the list of services
		$services = $this->get_services();

		// Update the services
		foreach($services as $service) {
			// Skip upgrade if not enabled
			if ($service['enabled'] != 'true') {
				continue;
			}

			// Skip if specific service requested and not this one
			if ($name !== 'all' && $service['name'] !== $name) {
				continue;
			}

			// Validate the service is in the $services array
			if ($name !== 'all') {
				$service_found = false;
				foreach ($services as $service) {
					if ($service['name'] === $name) {
						$service_found = true;
						break;
					}
				}
				if (!$service_found) {
					return;
				}
			}

			// Sanitize the service name
			$service_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $service['name']);

			// Output to the console
			if (PHP_SAPI === 'cli') {
				echo "	".$service_name."\n";
			}

			// Upgrade the service
			if (stristr(PHP_OS, 'Linux')) {
				system("cp " . escapeshellarg($service['file']) . " /etc/systemd/system/" . escapeshellarg($service['name']) . ".service");
				system("systemctl daemon-reload");
				system("systemctl enable " . escapeshellarg($service['name']));
				system("systemctl start " . escapeshellarg($service['name']));
			}
			if (stristr(PHP_OS, 'BSD')) {
				if ($service['enabled'] == 'true') {
					system("service ".escapeshellarg($service['name']). "start");
				}
			}
		}

	}

	/**
	 * Starts running services by name.
	 *
	 * This function iterates over all service files, extracts the service names,
	 * and starts each service.
	 *
	 * @param string $name Service name to start, or 'all' to start all services
	 * @return void
	 */
	public function start($name = 'all') {
		// Get the list of services
		$services = $this->get_services();

		// Validate the service is in the services array
		if ($name !== 'all') {
			$service_found = false;
			foreach ($services as $service) {
				if ($service['name'] === $name) {
					$service_found = true;
					break;
				}
			}
			if (!$service_found) {
				return;
			}
		}

		// Stop all services if equal to all stop one service if the name is not equal to all
		foreach($services as $service) {
			// Skip start if not enabled
			if ($service['enabled'] !== 'true') {
				continue;
			}

			// Skip if specific service requested and not this one
			if ($name !== 'all' && $service['name'] !== $name) {
				continue;
			}

			// Sanitize the service name
			$service_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $service['name']);

			// Output to the console
			if (PHP_SAPI === 'cli') {
				echo "	".$service_name."\n";
			}

			// Run the start command
			if (stristr(PHP_OS, 'Linux')) {
				system("systemctl start ".escapeshellarg($service_name));
			}
			if (stristr(PHP_OS, 'BSD')) {
				system("service ".escapeshellarg($service_name). "start");
			}
		}
	}

	/**
	 * Restarts all services
	 *
	 * This function restarts all core and app services.
	 *
	 * @return void No return value;
	 */
	public function restart($name = 'all') {
		// Get the list of services
		$services = $this->get_services();

		// Validate the service is in the services array
		if ($name !== 'all') {
			$service_found = false;
			foreach ($services as $service) {
				if ($service['name'] === $name) {
					$service_found = true;
					break;
				}
			}
			if (!$service_found) {
				return;
			}
		}

		// Restart all services
		foreach($services as $service) {
			// Skip restart if not enabled
			if ($service['enabled'] !== 'true') {
				continue;
			}

			// Skip if specific service requested and not this one
			if ($name !== 'all' && $service['name'] !== $name) {
				continue;
			}

			// Sanitize the service name
			$service_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $service['name']);

			// Output to the console
			if (PHP_SAPI === 'cli') {
				echo "	".$service_name."\n";
			}

			// Run the restart command
			if (stristr(PHP_OS, 'Linux')) {
				system("systemctl restart ".escapeshellarg($service_name));
			}
			if (stristr(PHP_OS, 'BSD')) {
				system("service ".escapeshellarg(service_name). "restart");
			}
		}
	}

	/**
	 * Stops running services by name.
	 *
	 * This function iterates over all service files, extracts the service names,
	 * and stops each service.
	 *
	 * @return void No return value;
	 */
	public function stop($name = 'all') {
		// Get the list of services
		$services = $this->get_services();

		// Validate the service is in the $services array
		if ($name !== 'all') {
			$service_found = false;
			foreach ($services as $service) {
				if ($service['name'] === $name) {
					$service_found = true;
					break;
				}
			}
			if (!$service_found) {
				return;
			}
		}

		// Stop all services
		foreach($services as $service) {
			// Skip if specific service requested and not this one
			if ($name !== 'all' && $service['name'] !== $name) {
				continue;
			}

			// Sanitize the service name
			$service_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $service['name']);

			// Output to the console
			if (PHP_SAPI === 'cli') {
				echo "	".$service_name."\n";
			}

			// Run the stop command
			if (stristr(PHP_OS, 'Linux')) {
				system("systemctl stop ".escapeshellarg($service['name']));
			}
			if (stristr(PHP_OS, 'BSD')) {
				system("service ".escapeshellarg($service['name']). "stop");
			}
		}
	}

	/**
	 * Finds the service name in an INI file from a given file.
	 *
	 * @param string $file The fully qualified path and file containing the ExecStart command.
	 *
	 * @return string|null The service name if found, otherwise an empty string.
	 */
	public function find_service_name(string $file) {
		$parsed = parse_ini_file($file);
		$exec_cmd = $parsed['ExecStart'];
		$parts = explode(' ', $exec_cmd);
		$php_file = $parts[1] ?? '';
		if (!empty($php_file)) {
			$path_info = pathinfo($php_file);
			return $path_info['filename'];
		}
		return '';
	}

	/**
	 * Checks whether the current user is the root user or not.
	 *
	 * @return bool True if the current user has root privileges, false otherwise.
	 */
	public function is_root(): bool {
		return posix_getuid() === 0;
	}

	/**
	 * Retrieves the name of a PHP class from an ExecStart directive in a service file.
	 *
	 * @param string $file Path to the service file.
	 *
	 * @return string The name of the PHP class, or empty string if not found.
	 */
	public function get_class_name(string $file) {
		if (!file_exists($file)) {
			return '';
		}
		$parsed = parse_ini_file($file);
		$exec_cmd = $parsed['ExecStart'] ?? '';
		$parts = explode(' ', $exec_cmd ?? '');
		$php_file = $parts[1] ?? '';
		if (!empty($php_file)) {
			return $php_file;
		}
		return '';
	}

	/**
	 * Checks if a process with the given name is currently running.
	 *
	 * @param string $name The name of the process to check for.
	 *
	 * @return array An array containing information about the process's status,
	 *               including whether it's status, its PID, and how long it's been running.
	 */
	public function is_running(string $name) {
		$name = escapeshellarg($name);
		$command = "ps -aux | grep $name | grep -v grep | awk '{print \$2}' | head -n 1";
		$pid = trim(shell_exec($command ?? ''));
		if ($pid && is_numeric($pid)) {
			$command = "ps -p $pid -o etime= | tr -d '\n'";
			$etime = trim(shell_exec($command) ?? '');
			return ['status' => true, 'pid' => $pid, 'etime' => $etime];
		}
		return ['status' => false, 'pid' => $pid, 'etime' => $etime];
	}

	/**
	 * Formats a time duration string into a human-readable format.
	 *
	 * The input string can be in one of the following formats:
	 * - dd-hh:mm:ss
	 * - hh:mm:ss
	 * - mm:ss
	 * - seconds (no units)
	 *
	 * If the input string is empty or invalid, an empty string will be returned.
	 *
	 * @param string $etime Time duration string to format.
	 *
	 * @return string Formatted time duration string in human-readable format.
	 */
	public function format_etime($etime) {
		// Format: [[dd-]hh:]mm:ss
		if (empty($etime)) return '-';

		$days = 0; $hours = 0; $minutes = 0; $seconds = 0;

		// Handle dd-hh:mm:ss
		if (preg_match('/^(\d+)-(\d+):(\d+):(\d+)$/', $etime, $m)) {
			[$_, $days, $hours, $minutes, $seconds] = $m;
		}
		// Handle hh:mm:ss
		elseif (preg_match('/^(\d+):(\d+):(\d+)$/', $etime, $m)) {
			[$_, $hours, $minutes, $seconds] = $m;
		}
		// Handle mm:ss
		elseif (preg_match('/^(\d+):(\d+)$/', $etime, $m)) {
			[$_, $minutes, $seconds] = $m;
		}

		$out = [];
		if ($days)		$out[] = $days . 'd';
		if ($hours)	 $out[] = $hours . 'h';
		if ($minutes) $out[] = $minutes . 'm';
		if ($seconds || empty($out)) $out[] = $seconds . 's';

		return implode(' ', $out);
	}

}
