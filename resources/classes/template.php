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
	  Portions created by the Initial Developer are Copyright (C) 2018 - 2019
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  Tim Fry <tim@voipstratus.com>
	 */

	/**
	 * Allows the use of templates without hard-coding which template can be used with full backward compatibility
	 *
	 * @author Mark Crane <markjcrane@fusionpbx.com>
	 * @author Tim Fry <tim@voipstratus.com>
	 */
	class template {

		/** @var string path and filename of template for engine to use */
		public $template_dir;

		/** @var string path and filename of the caching location engine will use */
		public $cache_dir;

		/** @var string $engine */
		public $engine;

		/** @var template_engine $object */
		private $object;

		/**
		 * Optional constructor params. When all params are set the init() method will be called.
		 * @see template::init()
		 * @param string $template_dir full path to the templates folder for the engine to use
		 * @param string $cache_dir default value of session variable or sys_get_temp_dir if not available
		 * @param string $engine default value of smarty
		 */
		public function __construct(string $template_dir = "", string $cache_dir = "", string $engine = 'smarty') {
			//set defaults for cache location from default settings or use the system temp dir
			$this->cache_dir = $_SESSION['server']['temp']['dir'] ?? sys_get_temp_dir();
			$this->template_dir = null;

			//override defaults
			if(!empty($engine)) 
				$this->engine = $engine;
			if(!empty($cache_dir)) 
				$this->cache_dir = $cache_dir;			
			if(!empty($template_dir))
				$this->template_dir = realpath($template_dir);

			//call init if all variables are supplied in the constructor
			if(!empty($this->engine) && !empty($this->cache_dir) && !empty($this->template_dir)) {
				$this->init();
			}
		}

		/**
		 * Initialize the template object using the property 'engine'.
		 */
		public function init() {
			require_once 'template_engine.php';
			if (!empty($this->engine)) {
				//set the class interface to use the _template suffix
				$classname = $this->engine . '_template';
				//load the class
				require_once $classname . '.php';
				//create the object
				$this->object = new $classname($this->template_dir, $this->cache_dir);
			}
		}

		/**
		 * Assign a key and value to the template.
		 * @param type $key
		 * @param type $value
		 */
		public function assign($key, $value) {
			$this->object->assign($key, $value);
		}

		/**
		 * Render a template file
		 * @param string $name Filename of the template to render
		 * @return string
		 */
		public function render($name): string {
			return $this->object->render($name);
		}

	}
