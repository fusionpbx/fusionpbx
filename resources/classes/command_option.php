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
 * Portions created by the Initial Developer are Copyright (C) 2008-2024
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

/**
 * Container object for creating command line options when creating a service
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class command_option {

	private $short_option;
	private $long_option;
	private $description;
	private $short_description;
	private $long_description;
	private $functions;

	/**
	 * Constructs an empty command_option
	 */
	public function __construct() {
		$this->short_option = '';
		$this->long_option = '';
		$this->description = '';
		$this->short_description = '';
		$this->long_description = '';
		$this->functions = [];
	}

	/**
	 * Creates a new instance of CommandOption with automatically assigned properties.
	 *
	 * @param array $options Key/Value pairs to assign as properties on the new instance.
	 *
	 * @return command_option Returns a populated instance of command_option.
	 */
	public static function new(...$options): command_option {
		$obj = new command_option();

		//automatically assign properties to the object that were passed in key/value pairs
		self::parse_options($obj, $options);

		//return the command_option with all properties filled in that were passed
		return $obj;
	}

	// used to parse object values when created

	/**
	 * Recursively parses the provided options array and applies its values to the given object.
	 *
	 * @param mixed $obj     The object whose properties will be updated with the parsed options
	 * @param array $options The associative array containing the options to parse and apply
	 *
	 * @return void This method does not return a value, it updates the provided object instead.
	 */
	private static function parse_options($obj, $options) {
		foreach ($options as $key => $value) {
			if (is_array($value)) {
				self::parse_options($obj, $value);
			}
			//call the method with the name of $key and pass it $value
			if (method_exists($obj, $key)) {
				$obj->{$key}($value);
			} elseif (property_exists($obj, $key)) {
				$obj->{$key} = $value;
			}
		}
	}

	/**
	 * Appends the callback function to the array of existing callback functions
	 *
	 * @param string|null $function When function param is set, the callback function will be appended to the list of
	 *                              functions. When called without a param, the array will be returned of current
	 *                              callbacks.
	 *
	 * @return $this|array Returns the array of callbacks if no parameters passed or this object when appending a
	 *                     callback
	 */
	public function callback(?string $function = null) {
		if ($function !== null) {
			$this->functions += [$function];
			return $this;
		}
		return $this->functions;
	}

	/**
	 * Appends the callback function to the array of existing callback functions
	 *
	 * @param string|null $function When function param is set, the callback function will be appended to the list of
	 *                              functions. When called without a param, the array will be returned of current
	 *                              callbacks.
	 *
	 * @return $this|array Returns the array of callbacks if no parameters passed or this object when appending a
	 *                     callback
	 */
	public function function_append(?string $function = null) {
		if ($function !== null) {
			$this->functions += [$function];
			return $this;
		}
		return $this->functions;
	}

	/**
	 * Converts the current object to an array.
	 *
	 * @return array The array representation of the current object, containing
	 *               information about options and functions.
	 */
	public function to_array(): array {
		$array['short_option'] = $this->short_option();
		$array['long_option'] = $this->long_option();
		$array['description'] = $this->description();
		$array['short_description'] = $this->short_description();
		$array['long_description'] = $this->long_description();
		$array['functions'] = $this->functions();
		return $array;
	}

	/**
	 * Sets or returns the short option value
	 *
	 * @param string|null $short_option
	 *
	 * @return $this
	 */
	public function short_option(?string $short_option = null) {
		if (!empty($short_option)) {
			$this->short_option = $short_option;
			return $this;
		}
		return $this->short_option;
	}

	/**
	 * Sets or returns the long option value
	 *
	 * @param string|null $long_option
	 *
	 * @return $this
	 */
	public function long_option(?string $long_option = null) {
		if (!empty($long_option)) {
			$this->long_option = $long_option;
			return $this;
		}
		return $this->long_option;
	}

	/**
	 * Set the general description
	 *
	 * @param string|null $description
	 *
	 * @return $this
	 */
	public function description(?string $description = null) {
		if (!empty($description)) {
			$this->description = $description;
			return $this;
		}
		return $this->description;
	}

	/**
	 * Sets or returns the short_description. If short_description is empty then the short_option is used as a default.
	 *
	 * @param string|null $short_description When parameter is null, it returns the currently set value. When not null
	 *                                       the short description is set to the passed value.
	 *
	 * @return $this
	 */
	public function short_description(?string $short_description = null) {
		if (!empty($short_description)) {
			$this->short_description = $short_description;
			return $this;
		}
		if (empty($this->short_description)) {
			if (str_ends_with($this->short_option, ':')) {
				$short = rtrim($this->short_option, ':');
				$short_description = "-$short <value>";
			} else {
				$short_description = '-' . $this->short_option;
			}
		} else {
			$short_description = $this->short_description;
		}
		return $short_description;
	}

	/**
	 * Sets or returns the long_description. If long_description is empty then the long_option is used as a default.
	 *
	 * @param string|null $long_description When parameter is null, it returns the currently set value. When not null
	 *                                      the long description is set to the passed value.
	 *
	 * @return self|string
	 */
	public function long_description(?string $long_description = null) {
		if ($long_description !== null) {
			$this->long_description = $long_description;
			return $this;
		}
		if (empty($this->long_description)) {
			if (str_ends_with($this->long_option, ':')) {
				$long = rtrim($this->long_option, ':');
				$long_description = "--$long <value>";
			} else {
				$long_description = '--' . $this->long_option;
			}
		} else {
			$long_description = $this->long_description;
		}
		return $long_description;
	}

	/**
	 * Sets or retrieves the array of callback functions
	 *
	 * @param array|null $functions When functions param is set, the array will be assigned to the list of callbacks.
	 *                              When called without a parameter, the current array of callbacks will be returned.
	 *
	 * @return $this|array Returns the array of callbacks if no parameters passed or this object when setting a new
	 *                     array
	 */
	public function functions(?array $functions = null) {
		if ($functions !== null) {
			$this->functions = $functions;
			return $this;
		}
		return $this->functions;
	}
}

/* Examples
$command_option = command_option::new([
	'short_option'=>'m',
	'long_option' =>'my-option',
	'description' =>'Create an option that uses -m or --my-option command-line parameter'
]);

$command_option = command_option::new()
	->short_option('m')
	->long_option('my-option')
	->description('Create an option that uses -m or --my-option command-line parameter');

echo $command_option->description();

$command_parsing_array = $command_option->to_array();
print_r($command_parsing_array);


 //*/
