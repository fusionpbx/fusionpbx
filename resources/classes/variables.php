<?php

require_once("database.php");

/**
 * Variables class provides access methods for FreeSWITCH variables from the db
 *
 * @method get returns a customisable array of db table variables
 * @method get_single_uuid returns a single db table variable by uuid
 * @method get_single_name returns a single db table variable by name
 * @method get_variable returns the expanded FreeSWITCH variable value
 * @method expand returns a switch variable with ${}'s expanded if possible
 */
if (!class_exists('variables')) {
	class variables {

		/**
		 * Get an expanded FreeSWITCH variable value
		 * @var string  $name variable name
		 * @var boolean $expand (optional) switch off expansion
		 */
		public function get_variable($name, $expand = TRUE) {
			return $this->get_single_name($name, $expand)['var_value'];
		}

		/**
		 * Get the FreeSWITCH variable table row from its uuid (taken from var_edit.php)
		 * @var string  $var_uuid UUID identifier
		 * @var boolean $expand (optional) expand the ${} brackets
		 */
		public function get_single_uuid($var_uuid, $expand = FALSE) {
			$params["var_uuid"] = $var_uuid;
			$arr = $this->get("where var_uuid = :var_uuid ", $params, $expand);
			return $arr[0];
		}

		/**
		 * Get the FreeSWITCH variable from its name
		 * @var string  $var_name Name identifier
		 * @var boolean $expand (optional) expand the ${} brackets
		 */
		public function get_single_name($var_name, $expand = FALSE) {
			$params["var_name"] = $var_name;
			$arr = $this->get("where var_name = :var_name ", $params, $expand);
			return $arr[0];
		}

		/**
		 * Get FreeSWITCH variables with optional query modifications
		 * @var string $append    (optional) modify the sql query string
		 * @var array $parameters (optional) sql query parameters
		 * @var boolean $expand (optional) expand the ${} brackets
		 */
		public function get($append = "", $parameters = null, $expand = FALSE) {
			$sql = "select * from v_vars ";
			$sql .= $append;
			$database = new database;
			$vars = $database->select($sql, $parameters, 'all');
			foreach ($vars as $key => $v) {
				$vars[$key]["var_description"] = base64_decode($v["var_description"]);
				if ($expand) {
					$vars[$key]["var_value"] = $this->expand($v["var_value"], $vars);
				}
			}
			return $vars;
		}

		private $expand_pattern = '/\${1,2}\{(.+?)\}/';
		private function value_vars($value) {
			preg_match_all($this->expand_pattern, $value, $matches);
			$kvps = array();
			for ($i=0; $i<count($matches[1]); $i++) {
				$kvps[ $matches[1][$i] ] = $matches[0][$i];
			}
			return $kvps;
		}
		private function replace_vars($value, $kvps) {
			$format = preg_replace($this->expand_pattern, "%s", $value);
			return vsprintf($format, array_values($kvps));
		}

		/**
		 * Utility function for variable expansions
		 * Looks in variables itself and the session switch settings values
		 * Looks one at a time, could probably be more efficient but probably won't get used much
		 * @var string $value variable value to look up
		 * @var array  $arr (optional) array of v_vars to check first
		 */
		public function expand($value, $arr = array()) {
			$kvps = $this->value_vars($value);
			if (@sizeof($kvps) != 0) {
				foreach ($kvps as $key => $val) {
					$found = FALSE;

					// Try the passed list (if any)
					foreach ($arr as $v) {
						if ($v["var_name"] == $key) {
							$found = TRUE;
							$kvps[$key] = $v["var_value"];
							break;
						}
					}
					if ($found) continue;

					// Try the full db list
					$result = $this->get_single_name($key);
					if (@sizeof($result) != 0) {
						$kvps[$key] = $result["var_value"];
						continue;
					}

					// Try the session switch variables
					$prefix = substr($key, 0, -4);
					if (substr($key, -4) == "_dir" && array_key_exists($prefix, $_SESSION['switch'])) {
						$kvps[$key] = $_SESSION['switch'][$prefix]['dir'];
					}
				}
				// Replace kvps
				return $this->replace_vars($value, $kvps);
			}
			return $value;
		}
	}
}


// Loops through each element. If element again is array, function is re-called. If not, result is echoed.
function traverseArray($array) {
	$row = "";
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			echo "<strong>Parent: " . $key . "</strong><br />\n";
			traverseArray($value);
		} else {
			$row .= $key . "=" . $value . ", ";
		}
	}
	echo $row . "<br />\n";
}


// Run tests
function test() {
	$vars = new variables;
	global $_SESSION;
	$_SESSION['switch']['sounds']['dir'] = '/usr/share/freeswitch/sounds';
	$_SESSION["domain_uuid"] = null;

	echo "\n*** empty:\n";
	$empty = $vars->get_single_name("wibble");
	traverseArray($empty);

	echo "\n*** get_single_name: sound_prefix:\n";
	$spbn = $vars->get_single_name("sound_prefix", TRUE);
	traverseArray($spbn);

	echo "\n*** get_single_uuid: sound_prefix:\n";
	$uuid = $spbn["var_uuid"];
	$spbu = $vars->get_single_uuid($uuid, TRUE);
	traverseArray($spbu);

	echo "\n*** get: Defaults:\n";
	$sql_mod =  "where var_category = :var_category ";
	$params["var_category"] = "Defaults";
	$arr = $vars->get($sql_mod, $params, TRUE);
	traverseArray($arr);
}

if (php_sapi_name() == 'cli') {
	test();
}

?>
