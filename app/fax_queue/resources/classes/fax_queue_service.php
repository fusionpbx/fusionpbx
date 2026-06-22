<?php

/**
 * Description goes here for fax_queue service
 */
class fax_queue_service extends service {

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
	 * fax_queue_interval
	 * @var int
	 */
	private $fax_queue_interval;

	/**
	 * interval
	 * @var int
	 */
	private $fax_queue_limit;

	/**
	 * interval
	 * @var int
	 */
	private $fax_retry_interval;

	/**
	 * Reloads settings from database, config file, and fax_queue.
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

		// Get the debug
		$this->debug = $this->settings->get('fax_queue', 'debug', false);

		// Get the fax queue interval
		$this->fax_queue_interval = (int)$this->settings->get('fax_queue', 'interval', 30);

		// Set the fax queue limit
		$this->fax_queue_limit = (int)$this->settings->get('fax_queue', 'limit', 30);

		// Set the fax queue retry interval
		$this->fax_retry_interval = (int)$this->settings->get('fax_queue', 'retry_interval', 180);

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

			// Get the FAX messages that are waiting to send
			$sql = "select * from v_fax_queue ";
			$sql .= "where hostname = :hostname ";
			$sql .= "and ( ";
			$sql .= "	( ";
			$sql .= "		(fax_status = 'waiting' or fax_status = 'trying' or fax_status = 'busy') ";
			$sql .= "		and (fax_retry_date is null or floor(extract(epoch from now()) - extract(epoch from fax_retry_date)) > :retry_interval) ";
			$sql .= "	)  ";
			$sql .= "	or ( ";
			$sql .= "		fax_status = 'sent' ";
			$sql .= "		and fax_email_address is not null ";
			$sql .= "		and fax_notify_date is null ";
			$sql .= "	) ";
			$sql .= ") ";
			$sql .= "order by domain_uuid asc ";
			$sql .= "limit :limit ";
			$parameters = array();
			$parameters['hostname'] = $this->hostname;
			$parameters['limit'] = $this->fax_queue_limit;
			$parameters['retry_interval'] = $this->fax_retry_interval;
			$fax_queue = $this->database->select($sql, $parameters, 'all');

			// Show results from the database
			$this->debug($sql." ".print_r($parameters, true));

			// Process the results from the fax_queue
			if (is_array($fax_queue) && @sizeof($fax_queue) != 0) {
				foreach($fax_queue as $row) {
					$command = PHP_BINARY." ".dirname(__DIR__, 4)."/app/fax_queue/resources/job/fax_send.php ";
					$command .= "'action=send&fax_queue_uuid=".$row["fax_queue_uuid"]."&hostname=".$this->hostname."'";
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
			sleep($this->fax_queue_interval);
		}
		return 0;
	}

	protected static function display_version(): void {
		echo "1.1\n";
	}

	protected static function set_command_options() {

	}

}
