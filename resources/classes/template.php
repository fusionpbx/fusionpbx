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

//define the template class
class template {

	public $engine;
	public $template_dir;
	public $cache_dir;
	private $object;
	private $var_array;

	public function __construct() {
	}

	/**
	 * Initializes the template engine based on the selected engine.
	 *
	 * @access public
	 */
	public function init() {
		if ($this->engine === 'smarty') {
			require_once "resources/templates/engine/smarty/Smarty.class.php";
			$this->object = new Smarty();
			$this->object->setTemplateDir($this->template_dir);
			$this->object->setCompileDir($this->cache_dir);
			$this->object->setCacheDir($this->cache_dir);
			$this->object->registerPlugin("modifier", "in_array", "in_array");
		}
		if ($this->engine === 'raintpl') {
			require_once "resources/templates/engine/raintpl/rain.tpl.class.php";
			$this->object = new RainTPL();
			RainTPL::configure('tpl_dir', realpath($this->template_dir) . "/");
			RainTPL::configure('cache_dir', realpath($this->cache_dir) . "/");
		}
		if ($this->engine === 'twig') {
			require_once "resources/templates/engine/Twig/Autoloader.php";
			Twig_Autoloader::register();
			$loader = new Twig_Loader_Filesystem($this->template_dir);
			$this->object = new Twig_Environment($loader);
			$lexer = new Twig_Lexer($this->object, [
				'tag_comment' => ['{*', '*}'],
				'tag_block' => ['{', '}'],
				'tag_variable' => ['{$', '}'],
			]);
			$this->object->setLexer($lexer);
		}
	}

	/**
	 * Assigns a value to the template engine based on the selected engine.
	 *
	 * @param string $key   The key for the assigned value.
	 * @param mixed  $value The value to be assigned.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function assign($key, $value) {
		if ($this->engine === 'smarty') {
			$this->object->assign($key, $value);
		}
		if ($this->engine === 'raintpl') {
			$this->object->assign($key, $value);
		}
		if ($this->engine === 'twig') {
			$this->var_array[$key] = $value;
		}
	}

	/**
	 * Renders the given template using the configured engine.
	 *
	 * @param string $name Name of the template to render. Values can be 'smarty', 'raintpl' or 'twig' (case sensitive)
	 *
	 * @return mixed The rendered template output, depending on the used engine
	 *
	 * @access public
	 */
	public function render($name) {
		if ($this->engine === 'smarty') {
			return $this->object->fetch($name);
		}
		if ($this->engine === 'raintpl') {
			return $this->object->draw($name, 'return_string=true');
		}
		if ($this->engine === 'twig') {
			return $this->object->render($name, $this->var_array);
		}
	}
}
