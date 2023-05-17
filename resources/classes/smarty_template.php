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
	 * Description of smarty
	 *
	 * @author Tim Fry <tim@voipstratus.com>
	 */
	class smarty_template implements template_engine {

		/**
		 * Deprecated
		 * @deprecated since version 5.0.1
		 * @var string $engine
		 */
		public $engine;
		public $template_dir;
		public $cache_dir;
		private $smarty;

		public function __construct(string $template_dir = "", string $cache_dir = "") {
			require_once "resources/templates/engine/smarty/Smarty.class.php";
			$this->smarty = new \Smarty();

			if(!empty($template_dir))
				$this->template_dir($template_dir);

			if(!empty($cache_dir)) {
				$this->cache_dir($cache_dir);
			}
		}

		public function assign($key, $value) {
			$this->smarty->assign($key, $value);
		}

		public function render($name): string {
			return $this->smarty->fetch($name);
		}

		public function cache_dir(?string $cache_dir = null) {

			if($cache_dir === null)
				return $this->cache_dir;

			if(file_exists($cache_dir)) {
				$this->cache_dir = $cache_dir;
				$this->smarty->setCompileDir($this->cache_dir);
				$this->smarty->setCacheDir($this->cache_dir);
			} else {
				throw new Exception('cache directory does not exist');
			}
		}

		public function template_dir(?string $template_dir = null) {

			if($template_dir === null)
				return $this->smarty->template_dir;

			if(file_exists($template_dir)) {
				$this->template_dir = $template_dir;
				$this->smarty->setTemplateDir($this->template_dir);
			} else {
				throw new Exception ('template directory does not exist');
			}
		}

	}
