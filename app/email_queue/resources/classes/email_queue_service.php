<?php

/**
 * Description goes here for email_queue service
 */
class email_queue_service extends service {

	/**
	 * database object
	 * @var database
	 */
	private $database;

	/**
	 * settings object
	 * @var settings
	 */
	private $settings;

	/**
	 * hostname variable
	 * @var string
	 */
	private $hostname;

	/**
	 * debug
	 * @var bool
	 */
	private $debug;

	/**
	 * interval
	 * @var int
	 */
	private $interval;

	/**
	 * interval
	 * @var int
	 */
	private $email_queue_limit;

	/**
	 * Reloads settings from database, config file, and email_queue.
	 *
	 * @return void
	 */
	public function reload_settings(): void {
		// Re-read the config file to get any possible changes
		parent::$config->read();

		// Connect to the database
		$this->database = new database(['config' => parent::$config]);

		// Get the settings using global defaults
		$this->settings = new settings(['database' => $this->database]);

		// Get the email queue interval
		$this->interval = (int)$this->settings->get('email_queue', 'interval', 30);

		// Set the email queue limit
		$this->email_queue_limit = (int)$this->settings->get('email_queue', 'limit', 30);

		// Get the hostname
		$this->hostname = gethostname();
	}

	public function run(): int {
		// Reload the settings
		$this->reload_settings();

		// Service work is handled here
		while ($this->running) {
			// Initialize the array for switch events
			$json_array = [];

			// Make sure the database connection is available
			while (!$this->database->is_connected()) {
				// Connect to the database
				$this->database->connect();

				// Reload settings after connection to the database
				$this->settings = new settings(['database' => $this->database]);

				// Sleep for a moment
				sleep(1);
			}

			// Get the messages that are waiting to send
			$sql = "select * from v_email_queue ";
			$sql .= "where (email_status = 'waiting' or email_status = 'trying') ";
			$sql .= "and hostname = :hostname ";
			$sql .= "order by domain_uuid, email_date desc ";
			$sql .= "limit :limit ";
			$parameters = array();
			$parameters['hostname'] = $this->hostname;
			$parameters['limit'] = $this->email_queue_limit;
			$email_queue = $this->database->select($sql, $parameters, 'all');

			// Show results from the database
			$this->debug($sql." ".print_r($parameters, true));

			// Process the results from the email_queue
			if (is_array($email_queue) && @sizeof($email_queue) != 0) {
				foreach($email_queue as $row) {
					$command = PHP_BINARY." ".dirname(__DIR__, 4)."/app/email_queue/resources/jobs/email_send.php ";
					$command .= "'action=send&email_queue_uuid=".$row["email_queue_uuid"]."&hostname=".$this->hostname."'";
					$this->debug($command);
					if (parent::is_debug_mode()) {
						// Run process inline to see debug info
						$result = system($command);
					}
					else {
						// Starts process rapidly doesn't wait for previous process to finish (used for production)
						$handle = popen($command." > /dev/null &", 'r');
						$this->debug("$handle " . gettype($handle));
						$result = fread($handle, 2096);
						pclose($handle);
					}
					$this->debug($result);
				}
			}

			// Pause to prevent excessive database queries
			sleep($this->interval);
		}
		return 0;
	}

	protected static function display_version(): void {
		echo "1.1\n";
	}

	protected static function set_command_options() {

	}

}
