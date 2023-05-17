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
	 * Description of template
	 *
	 * @author Tim Fry <tim@voipstratus.com>
	 */
	class template {

		public $template_dir;
		public $cache_dir;
		public $engine;
		/** @var $object template_engine */
		private $object;

		public function __construct() {
			//set the preferred engine
			$this->engine = 'smarty';
			//set the cache location from default settings or use the system temp dir
			$this->cache_dir = $_SESSION['server']['temp']['dir'] ?? sys_get_temp_dir();
			//template directory can not be reliably determined
			$this->template_dir = null;
		}

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

		public function assign($key, $value) {
			$this->object->assign($key, $value);
		}

		public function render($name): string {
			return $this->object->render($name);
		}

	}
