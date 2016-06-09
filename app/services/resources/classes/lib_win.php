<?php

if(!defined('WIN32_ERROR_ACCESS_DENIED')) define('WIN32_ERROR_ACCESS_DENIED',0x00000005);
if(!defined('WIN32_ERROR_CIRCULAR_DEPENDENCY')) define('WIN32_ERROR_CIRCULAR_DEPENDENCY',0x00000423);
if(!defined('WIN32_ERROR_DATABASE_DOES_NOT_EXIST')) define('WIN32_ERROR_DATABASE_DOES_NOT_EXIST',0x00000429);
if(!defined('WIN32_ERROR_DEPENDENT_SERVICES_RUNNING')) define('WIN32_ERROR_DEPENDENT_SERVICES_RUNNING',0x0000041B);
if(!defined('WIN32_ERROR_DUPLICATE_SERVICE_NAME')) define('WIN32_ERROR_DUPLICATE_SERVICE_NAME',0x00000436);
if(!defined('WIN32_ERROR_FAILED_SERVICE_CONTROLLER_CONNECT')) define('WIN32_ERROR_FAILED_SERVICE_CONTROLLER_CONNECT',0x00000427);
if(!defined('WIN32_ERROR_INSUFFICIENT_BUFFER')) define('WIN32_ERROR_INSUFFICIENT_BUFFER',0x0000007A);
if(!defined('WIN32_ERROR_INVALID_DATA')) define('WIN32_ERROR_INVALID_DATA',0x0000000D);
if(!defined('WIN32_ERROR_INVALID_HANDLE')) define('WIN32_ERROR_INVALID_HANDLE',0x00000006);
if(!defined('WIN32_ERROR_INVALID_LEVEL')) define('WIN32_ERROR_INVALID_LEVEL',0x0000007C);
if(!defined('WIN32_ERROR_INVALID_NAME')) define('WIN32_ERROR_INVALID_NAME',0x0000007B);
if(!defined('WIN32_ERROR_INVALID_PARAMETER')) define('WIN32_ERROR_INVALID_PARAMETER',0x00000057);
if(!defined('WIN32_ERROR_INVALID_SERVICE_ACCOUNT')) define('WIN32_ERROR_INVALID_SERVICE_ACCOUNT',0x00000421);
if(!defined('WIN32_ERROR_INVALID_SERVICE_CONTROL')) define('WIN32_ERROR_INVALID_SERVICE_CONTROL',0x0000041C);
if(!defined('WIN32_ERROR_PATH_NOT_FOUND')) define('WIN32_ERROR_PATH_NOT_FOUND',0x00000003);
if(!defined('WIN32_ERROR_SERVICE_ALREADY_RUNNING')) define('WIN32_ERROR_SERVICE_ALREADY_RUNNING',0x00000420);
if(!defined('WIN32_ERROR_SERVICE_CANNOT_ACCEPT_CTRL')) define('WIN32_ERROR_SERVICE_CANNOT_ACCEPT_CTRL',0x00000425);
if(!defined('WIN32_ERROR_SERVICE_DATABASE_LOCKED')) define('WIN32_ERROR_SERVICE_DATABASE_LOCKED',0x0000041F);
if(!defined('WIN32_ERROR_SERVICE_DEPENDENCY_DELETED')) define('WIN32_ERROR_SERVICE_DEPENDENCY_DELETED',0x00000433);
if(!defined('WIN32_ERROR_SERVICE_DEPENDENCY_FAIL')) define('WIN32_ERROR_SERVICE_DEPENDENCY_FAIL',0x0000042C);
if(!defined('WIN32_ERROR_SERVICE_DISABLED')) define('WIN32_ERROR_SERVICE_DISABLED',0x00000422);
if(!defined('WIN32_ERROR_SERVICE_DOES_NOT_EXIST')) define('WIN32_ERROR_SERVICE_DOES_NOT_EXIST',0x00000424);
if(!defined('WIN32_ERROR_SERVICE_EXISTS')) define('WIN32_ERROR_SERVICE_EXISTS',0x00000431);
if(!defined('WIN32_ERROR_SERVICE_LOGON_FAILED')) define('WIN32_ERROR_SERVICE_LOGON_FAILED',0x0000042D);
if(!defined('WIN32_ERROR_SERVICE_MARKED_FOR_DELETE')) define('WIN32_ERROR_SERVICE_MARKED_FOR_DELETE',0x00000430);
if(!defined('WIN32_ERROR_SERVICE_NO_THREAD')) define('WIN32_ERROR_SERVICE_NO_THREAD',0x0000041E);
if(!defined('WIN32_ERROR_SERVICE_NOT_ACTIVE')) define('WIN32_ERROR_SERVICE_NOT_ACTIVE',0x00000426);
if(!defined('WIN32_ERROR_SERVICE_REQUEST_TIMEOUT')) define('WIN32_ERROR_SERVICE_REQUEST_TIMEOUT',0x0000041D);
if(!defined('WIN32_ERROR_SHUTDOWN_IN_PROGRESS')) define('WIN32_ERROR_SHUTDOWN_IN_PROGRESS',0x0000045B);
if(!defined('WIN32_NO_ERROR')) define('WIN32_NO_ERROR',0x00000000);

if(function_exists('win32_query_service_status')){

	class win_service{
		private static $service_state = array(
			//Service Status Constants
			WIN32_SERVICE_CONTINUE_PENDING =>'CONTINUE_PENDING',
			WIN32_SERVICE_PAUSE_PENDING    =>'PAUSE_PENDING',
			WIN32_SERVICE_PAUSED           =>'PAUSED',
			WIN32_SERVICE_RUNNING          =>'RUNNING',
			WIN32_SERVICE_START_PENDING    =>'START_PENDING',
			WIN32_SERVICE_STOP_PENDING     =>'STOP_PENDING',
			WIN32_SERVICE_STOPPED          =>'STOPPED',
		);

		private static $win_error = array(
			WIN32_NO_ERROR                                    => 'NO_ERROR',
			WIN32_ERROR_ACCESS_DENIED                         => 'ACCESS_DENIED',
			WIN32_ERROR_CIRCULAR_DEPENDENCY                   => 'CIRCULAR_DEPENDENCY',
			WIN32_ERROR_DATABASE_DOES_NOT_EXIST               => 'DATABASE_DOES_NOT_EXIST',
			WIN32_ERROR_DEPENDENT_SERVICES_RUNNING            => 'DEPENDENT_SERVICES_RUNNING',
			WIN32_ERROR_DUPLICATE_SERVICE_NAME                => 'DUPLICATE_SERVICE_NAME',
			WIN32_ERROR_FAILED_SERVICE_CONTROLLER_CONNECT     => 'FAILED_SERVICE_CONTROLLER_CONNECT',
			WIN32_ERROR_INSUFFICIENT_BUFFER                   => 'INSUFFICIENT_BUFFER',
			WIN32_ERROR_INVALID_DATA                          => 'INVALID_DATA',
			WIN32_ERROR_INVALID_HANDLE                        => 'INVALID_HANDLE',
			WIN32_ERROR_INVALID_LEVEL                         => 'INVALID_LEVEL',
			WIN32_ERROR_INVALID_NAME                          => 'INVALID_NAME',
			WIN32_ERROR_INVALID_PARAMETER                     => 'INVALID_PARAMETER',
			WIN32_ERROR_INVALID_SERVICE_ACCOUNT               => 'INVALID_SERVICE_ACCOUNT',
			WIN32_ERROR_INVALID_SERVICE_CONTROL               => 'INVALID_SERVICE_CONTROL',
			WIN32_ERROR_PATH_NOT_FOUND                        => 'PATH_NOT_FOUND',
			WIN32_ERROR_SERVICE_ALREADY_RUNNING               => 'SERVICE_ALREADY_RUNNING',
			WIN32_ERROR_SERVICE_CANNOT_ACCEPT_CTRL            => 'SERVICE_CANNOT_ACCEPT_CTRL',
			WIN32_ERROR_SERVICE_DATABASE_LOCKED               => 'SERVICE_DATABASE_LOCKED',
			WIN32_ERROR_SERVICE_DEPENDENCY_DELETED            => 'SERVICE_DEPENDENCY_DELETED',
			WIN32_ERROR_SERVICE_DEPENDENCY_FAIL               => 'SERVICE_DEPENDENCY_FAIL',
			WIN32_ERROR_SERVICE_DISABLED                      => 'SERVICE_DISABLED',
			WIN32_ERROR_SERVICE_DOES_NOT_EXIST                => 'SERVICE_DOES_NOT_EXIST',
			WIN32_ERROR_SERVICE_EXISTS                        => 'SERVICE_EXISTS',
			WIN32_ERROR_SERVICE_LOGON_FAILED                  => 'SERVICE_LOGON_FAILED',
			WIN32_ERROR_SERVICE_MARKED_FOR_DELETE             => 'SERVICE_MARKED_FOR_DELETE',
			WIN32_ERROR_SERVICE_NO_THREAD                     => 'SERVICE_NO_THREAD',
			WIN32_ERROR_SERVICE_NOT_ACTIVE                    => 'SERVICE_NOT_ACTIVE',
			WIN32_ERROR_SERVICE_REQUEST_TIMEOUT               => 'SERVICE_REQUEST_TIMEOUT',
			WIN32_ERROR_SHUTDOWN_IN_PROGRESS                  => 'SHUTDOWN_IN_PROGRESS'
		);

		private static function val2val($val,$map,$default){
			if(isset($map[$val])) return $map[$val];
			return $default;
		}

		var $status;
		var $last_error;
		var $name;
		var $description;
		var $machine;

		function win_service($srvname, $machine=null){
			$this->name = $srvname;
			$this->machine = $machine;
			$this->status = null;
			$this->last_error = WIN32_NO_ERROR;
		}

		function refresh_status(){
			$status = win32_query_service_status($this->name,$this->machine);
			if(is_array($status)){
				$this->status = (object)$status;
				$this->last_error = WIN32_NO_ERROR;
				return true;
			}
			$this->status = null;
			$last_error = $status;
			return false;
		}

		function start(){
			$this->last_error = win32_start_service($this->name, $this->machine);
			return ($this->last_error === WIN32_NO_ERROR) or ($this->last_error === WIN32_ERROR_SERVICE_ALREADY_RUNNING);
		}

		function stop(){
			$this->last_error = win32_stop_service($this->name, $this->machine);
			return $this->last_error === WIN32_NO_ERROR;
		}

		function last_error($as_string = true){
			if($as_string){
				return self::val2val(
					$this->last_error, self::$win_error, $this->last_error
				);
			}
			return $this->last_error;
		}

		function state($as_string = true){
			if((!$this->status)and(!$this->refresh_status())) return false;
			if($as_string){
				return self::val2val(
					$this->status->CurrentState, self::$service_state, 'UNKNOWN'
				);
			}
			return $this->status->CurrentState;
		}

		function pid(){
			if((!$this->status)and(!$this->refresh_status())) return false;
			return $this->status->ProcessId;
		}

	}

}

if(function_exists('reg_open_key')){

	class win_reg_key{

		private static $HK = array(
			HKEY_CLASSES_ROOT   => "HKCR",
			HKEY_CURRENT_USER   => "HKCU",
			HKEY_LOCAL_MACHINE  => "HKLM",
			HKEY_USERS          => "HKU",
			HKEY_CURRENT_CONFIG => "HKCC",
		);

		function __construct($haiv, $key){
			$this->h = $haiv;
			$this->k = $key;
			$this->r = reg_open_key($this->h, $this->k);
			$this->shell = new COM('WScript.Shell');
			if(!$this->shell){
				throw new Exception("Cannot create shell object.");
			}
			if(!$this->r){
				throw new Exception("Cannot access registry.");
			}
			$this->path = self::$HK[$this->h] . '\\' . $this->k;
		}

		function __destruct(){
			if($this->r){
				reg_close_key($this->r);
				$this->r = false;
			}
		}

		function keys(){
			return reg_enum_key($this->r);
		}

		function values($as_hash = false){
			$values = reg_enum_value($this->r);
			if(!$as_hash) return $values;
			$result = Array();
			foreach($values as $key){
				$result[$key] = reg_get_value($this->r, $key);
			}
			return $result;
		}

		function value($key){
			return reg_get_value($this->r, $key);
		}

		function exists($key){
			$v = $this->value($key);
			if($v === NULL)  return false;
			if($v === false) return false;
			return true;
		}

		private function write_raw($key, $type, $value){
			return reg_set_value($this->r, $key, $type, $value);
		}

		function write_dword($key, $value){
			return $this->write_raw($key, REG_DWORD, $value);
		}

		function write_string($key, $value){
			return $this->write_raw($key, REG_SZ, $value);
		}

		function remove_value($key){
			if(!$this->exists($key)) return;
			$key = $this->path . '\\' . $key;
			$this->shell->RegDelete($key);
		}

	}

}
