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

    require_once "resources/templates/engine/smarty/Smarty.class.php";
    
    /**
     * Description of bridge_template
     *
     * @author tim
     */
    class bridge_template extends Smarty {

        /**
         * constructor
         */
        function __construct() {
            parent::__construct();
            $this->template_dir = $_SERVER['DOCUMENT_ROOT'] . "/app/bridges/resources/templates/";
//        $this->config_dir = "/conf/";
            $this->compile_dir = $_SESSION['cache']['location']['text'] != "" ? $_SESSION['cache']['location']['text'] : sys_get_temp_dir();
            $this->caching = 0;
        }

    }
    