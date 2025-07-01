<?php

if (!class_exists('RingotelRepository')) {
	class RingotelRepository
	{

		private $db;
		function __construct()
		{
			$this->db = new database;
			unset($sql, $parameters);
		}

		function __destruct()
		{
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

        function getRingotelToken()
        {
        	$parameters = null;
        	$sql  = "   select default_setting_value from v_default_settings    ";
        	$sql .= "   where default_setting_category = 'ringotel'             ";
        	$sql .= "   and default_setting_subcategory = 'ringotel_token'      ";
        	//return info with destinations 
        	$res = $this->db->select($sql, $parameters, 'column');
        	unset($sql, $db, $parameters);
        	return $res;
        }

        function getRingotelApiUrl()
        {
        	$parameters = null;
        	$sql  = "   select default_setting_value from v_default_settings    ";
        	$sql .= "   where default_setting_category = 'ringotel'             ";
        	$sql .= "   and default_setting_subcategory = 'ringotel_api'      ";
        	//return info with destinations 
        	$res = $this->db->select($sql, $parameters, 'column');
        	unset($sql, $db, $parameters);
        	return $res;
        }
    }
}