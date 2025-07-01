<?php 

require_once "resources/check_auth.php"; 

class loadConfig {
    /**
     * $param [string]- array with names default settings: category, subcategory
     * $returnName [string]- here you need set name for return parameter
     */
    public function returnConfig($param) {
        if ($_SESSION[$param['category']][$param['subcategory']]['text']) {
            return $_SESSION[$param['category']][$param['subcategory']]['text'];
        }
    }
}

?>