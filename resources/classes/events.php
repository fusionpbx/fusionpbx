<?php
/**
 * Events class:
 * - manages events in the FusionPBX
 * use:
 * $e = new Events;
 * $e->add_event_function('myfunction')  it could be a static method as well
 * $e->execute_event(ADD, $params)	event type, params is an associative array
 */

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";


define ("MODULE_LOAD", 1);	// when loading a FS module with FS
define ("MODULE_UNLOAD", 2);
define ("RELOADXML", 3);	// when reloading xml
define ("ADD", 4);		// when adding something
define ("EDIT", 5);		// when editing something
define ("DEL", 6);		// when deleting something
define ("LOGIN", 7);		// when login
define ("LOGOUT", 8);		// when logout

if (!class_exists('database')) {
	class Events{
		private $handler = array();
		private $event = array();

		public function __construct(){
		}
		// declare log file and file pointer as private properties

		public function add_event_function($event_type, $event_function){
			$event[$event_type][] = $event_function;
		}

		public function execute_event($event_type, $params=null){
			foreach ($this->event[$event_type] as $event_function){
				try{
					call_user_func($event_function, $params);
				}
				catch (Exception $e) {
					echo 'Exception: ',  $e->getMessage(), "\n";
				}
				// Lets log
				foreach ($this->handler as $handler){
					$handler->log_event($event_type, $params);
				}
			}
		}
	}
}

?>