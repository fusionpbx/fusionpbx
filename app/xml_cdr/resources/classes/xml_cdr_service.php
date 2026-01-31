<?php

/**
 * Description goes here for xml_cdr service
 */
class xml_cdr_service extends service {

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
	 * limit variable
	 * @var string
	 */
	private $xml_cdr_dir;

	/**
	 * Reloads settings from database, config file and websocket server.
	 *
	 * @return void
	 */
	protected function reload_settings(): void {
		// re-read the config file to get any possible changes
		parent::$config->read();

		// Connect to the database
		$this->database = new database(['config' => parent::$config]);

		// get the settings using global defaults
		$this->settings = new settings(['database' => $this->database]);

		// get the hostname
		$this->hostname = gethostname();

		//get the xml_cdr directory
		$this->xml_cdr_dir = $this->settings->get('switch', 'log', '/var/log/freeswitch').'/xml_cdr';
	}

	public function run(): int {

		// Reload the settings
		$this->reload_settings();

		//rename the directory
		if (file_exists($this->xml_cdr_dir.'/failed/invalid_xml')) {
			rename($this->xml_cdr_dir.'/failed/invalid_xml', $this->xml_cdr_dir.'/failed/xml');
		}

		//create the invalid xml directory
		if (!file_exists($this->xml_cdr_dir.'/failed/xml')) {
			mkdir($this->xml_cdr_dir.'/failed/xml', 0770, true);
		}

		//create the invalid size directory
		if (!file_exists($this->xml_cdr_dir.'/failed/size')) {
			mkdir($this->xml_cdr_dir.'/failed/size', 0770, true);
		}

		//create the invalid sql directory
		if (!file_exists($this->xml_cdr_dir.'/failed/sql')) {
			mkdir($this->xml_cdr_dir.'/failed/sql', 0770, true);
		}

		//update permissions to correct systems with the wrong permissions
		if (file_exists($this->xml_cdr_dir.'/failed')) {
			exec('chmod 770 -R '.$this->xml_cdr_dir.'/failed');
		}

		//import the call detail records from HTTP POST or file system
		$cdr = new xml_cdr;

		// Service work is handled here
		while ($this->running) {

			//get the list of call detail records, and limit the number of records
			$xml_cdr_array = array_slice(glob($this->xml_cdr_dir . '/*.cdr.xml'), 0, 100);

			//process the call detail records
			if (!empty($xml_cdr_array)) {
				//make sure the database connection is available
				while (!$this->database->is_connected()) {
					//connect to the database
					$this->database->connect();

					//reload settings after connection to the database
					$this->settings = new settings(['database' => $this->database]);

					//sleep for a moment
					sleep(3);
				}

				foreach ($xml_cdr_array as $xml_cdr_file) {
					//move the files that are too large or zero file size to the failed size directory
					if (filesize($xml_cdr_file) >= (3 * 1024 * 1024) || filesize($xml_cdr_file) == 0) {
						//echo "WARNING: File too large or zero file size. Moving $file to failed\n";
						if (!empty($this->xml_cdr_dir)) {
							if (parent::$log_level == 7) {
								echo "Move the file ".$xml_cdr_file." to ".$this->xml_cdr_dir."/failed/size\n";
							}
							rename($xml_cdr_file, $this->xml_cdr_dir.'/failed/size/'.basename($xml_cdr_file));
						}
						continue;
					}

					//add debug information
					if (parent::$log_level == 7) {
						echo $xml_cdr_file."\n";
					}

					//get the content from the file
					$call_details = file_get_contents($xml_cdr_file);

					//process the call detail record
					if (isset($xml_cdr_file) && isset($call_details)) {
						//set the file
						$cdr->file = basename($xml_cdr_file);

						//get the leg of the call and the file prefix
						if (substr(basename($xml_cdr_file), 0, 2) == "a_") {
							$leg = "a";
						}
						else {
							$leg = "b";
						}

						//decode the xml string
						if (substr($call_details, 0, 1) == '%') {
							$call_details = urldecode($call_details);
						}

						//parse the xml and insert the data into the database
						$cdr->xml_array($i, $leg, $call_details);
					}
				}
			}

			//sleep for 100 ms
			usleep(100000);
		}
		return 0;
	}

	protected static function display_version(): void {
		echo "1.1\n";
	}

	protected static function set_command_options() {

	}

}
