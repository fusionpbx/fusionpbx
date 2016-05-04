include_once('virtual.php');

class Syslog extends Event_Handler{

	protected $ident;
	protected $option;
	protected $facility;
	protected $priority;
	function __construct($ident='fusionpbx', $option=(LOG_PID | LOG_PERROR), $facility=LOG_LOCAL0, $priority=LOG_INFO){
		$this->ident = $ident;
		$this->option = $option;
		$this->facility = $facility;
		$this->priority = $priority;

		if ($_SESSION['event']['syslog']['enable'] <> 0){
			openlog($ident, $option, $facility);
		}
	}

	function __destruct(){
		if ($_SESSION['event']['syslog']['enable'] <> 0){
			closelog();
		}
	}

	public function log_event($event_type, $params){
		if ($_SESSION['event']['syslog']['enable'] <> 0){
			$log = '' ;
			foreach ($params as $k => $v) {
				$log .= "[$k]=[$v] ";
			}

			syslog($priority, $log);
		}
	}
}
