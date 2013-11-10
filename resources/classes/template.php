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
	Copyright (C) 2013
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the template class
	if (!class_exists('template')) {
		class template {

			public $engine;
			public $name;
			public $template_dir;
			public $cache_dir;
			private $object;
			private $x = 0;

			public function __construct() {
				if ($this->engine === 'smarty') {
					include "resources/templates/engine/smarty/Smarty.class.php";
					$this->object = new Smarty();
					$this->object->setTemplateDir($template_dir);
					$this->object->setCompileDir($compile_dir);
					$this->object->setCacheDir($cache_dir);
				}
				if ($this->engine === 'raintpl') {
					include "resources/templates/engine/raintpl/rain.tpl.class.php";
					$this->object = new RainTPL();
					raintpl::configure( 'tpl_dir', $this->template_dir);
					raintpl::configure( 'cache_dir', $this->cache_dir);
				}
				if ($this->engine === 'twig') {
					require_once "resources/templates/engine/twig/Autoloader.php";
					Twig_Autoloader::register();
					$loader = new Twig_Loader_Filesystem($template_dir);
					$this->object = new Twig_Environment($loader);
				}
			}

			public function __destruct() {
				foreach ($this as $key => $value) {
					unset($this->$key);
				}
			}

			public function assign($key, $value) {
				if ($this->engine === 'smarty') {
					$this->object->assign($key, $value);
				}
				if ($this->engine === 'raintpl') {
					$this->object->assign($key, $value);
				}
				if ($this->engine === 'twig') {
					$this->var_array[$this->x][$key] = $value; 
					$this->x++;
				}
			}

			public function render() {
				if ($this->engine === 'smarty') {
					return $this->object->fetch($this->name);
				}
				if ($this->engine === 'raintpl') {
					return $this->object-> draw($this->name, 'return_string=true');
				}
				if ($this->engine === 'twig') {
					return $twig->render($this->name,$this->var_array);
				}
			}
		}
	}

?>
