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
	Sebastian Krupinski <sebastian@ksacorp.com>
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Sebastian Krupinski <sebastian@ksacorp.com>
*/

class database {
    
    /**
    * generate dsn statement
    * @param  string $type - sqlite, mysql, pgsql, etc
    * @param  string $host - fqdn or ip address
    * @param  string $port - port number
    * @param  string $name - name of database or filename when using sqlite
    * @return object pdo
    */
    private static function _dsn($type,$host=null,$port=null,$name)
    {
        switch ($type) {
        case 'sqlite': 
            return "$type:$name;";
            break;
        default:
            return "$type:host=$host;port=$port;";
            break;
        }
    }

    /**
    * connect to database
    * @param  string $type - sqlite, mysql, pgsql, etc
    * @param  string $host - fqdn or ip address
    * @param  string $port - port number
    * @param  string $name - name of database or filename when using sqlite
    * @param  string $username - authentication username
    * @param  string $password - authentication password
    * @param  array  $options - array of database options
    * @return object pdo
    */
    public static function connect($type,$host=null,$port=null,$name,$username=null,$password=null,$options)
    {
        try
        {
            $db = new PDO(self::_dsn($type,$host,$port,$name), $username, $password, $options);
        } 
        catch (PDOException $exception)
        {
            echo "Exception: ".$exception->getMessage();
        }
        if ($db!==null) return $db;
        else return false;
    }

    /**
    * disconnect from database
    * @param pdo $db - database object as pdo type
    */
    public static function disconnect($db)
    {
        try
        {
            $db=null;
            return true;
        } 
        catch (Exception $exception)
        {
            echo "Exception: ".$exception->getMessage();
            return false;
        }
    }

    /**
    * begin a transaction.
    * @param pdo $db - database object as pdo type
    */
    public static function begin_transaction($db)
    {
        $db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        $db->beginTransaction();
    }
    
    /**
    * end the transaction.
    * @param pdo $db - database object as pdo type
    */
    public static function end_transaction($db)
    {
        $db->commit();
        $db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }
    
    /**
    * revert the transaction.
    * @param pdo $db - database object as pdo type
    */
    public static function revert_transactions($db)
    {
        $db->rollBack();
        $db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }

    /**
    * check if there is exist data
    * @param  string $table table name
    * @param  array $dat array list of data to find
    * @return true or false
    */
    public static function check_exist($table,$dat) {
        $data = array_values( $dat );
       //grab keys
        $cols=array_keys($dat);
        $col=implode(', ', $cols);
        foreach ($cols as $key) {
          $keys=$key."=?";
          $mark[]=$keys;
        }
        $jum=count($dat);
        if ($jum>1) {
            $im=implode(' and  ', $mark);
                $db->prepare("SELECT $col from $table WHERE $im");
        } else {
          $im=implode('', $mark);
                $db->prepare("SELECT $col from $table WHERE $im");
        }
        $db->execute( $data );
        $db->setFetchMode( PDO::FETCH_OBJ );
        $jum = $db->rowCount();
        if ($jum>0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * get last insert id
    * @return int last insert id
    */
    public static function get_lastid()
    {
        return $db->lastInsertId();
    }

    /**
    * get only single row
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  string $filterc - filter column
    * @param  string $filterv - filter value
    * @return array table row
    */
    public static function get_row($db,$table,$filterc,$filterv)
    {
        $db->prepare("SELECT * FROM $table WHERE $filterc=?");
        $db->execute($filterv);
        $db->setFetchMode(PDO::FETCH_OBJ);
        $data =  $db->fetch();
        return $data;
    }

    /**
    * get only single column
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  string $column - column to return value from
    * @param  string $filterc - filter column
    * @param  string $filterv - filter value
    * @return array table row
    */
    public static function get_col($db,$table,$column,$filterc,$filterv)
    {
        $db->prepare("SELECT $column FROM $table WHERE $filterc=?");
        $db->execute($filterv);
        $db->setFetchMode(PDO::FETCH_OBJ);
        $data =  $db->fetch();
        return $data;
    }

    /**
    * get only single value
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  string $column - column to return value from
    * @param  string $filterc - filter column
    * @param  string $filterv - filter value
    * @return mixed data in field
    */
    public static function get_value($db,$table,$column,$filterc,$filterv)
    {
        $cmd = $db->prepare("SELECT $column FROM $table WHERE $filterc=?");
        $cmd->bindParam(1, $filterv);
        $cmd->execute();
        $data = $cmd->fetchColumn(0);
        return $data;
    }

    /**
    * get count of rows
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  array $filter - multidimensional array
    * @return mixed data in field
    */
    public static function get_count($db,$table,$filter)
    {

        $cmd = $db->prepare("SELECT COUNT(0) FROM $table WHERE $filterc $condition_operator ?");
        $cmd->bindParam(1, $filterv);
        $cmd->execute();
        $data = $cmd->fetchColumn(0);
    }

    /**
    * get specific columns and rows
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  array $fields - specific columns to return
    * @param  array $conditions - specific rows to select
    * @return array selected tables and rows
    */
    public static function get_table($db,$table,$columns,$conditions)
    {
        if($fields === null) $fields=array("*");
        $cmd = $db->prepare("SELECT ".implode(',', $columns)." FROM $table WHERE $filterc $condition_operator ?");
        $cmd->execute();
        $cmd->setFetchMode(PDO::FETCH_OBJ);
        $data =  $cmd->fetch();
        return  $data;
    }

    /**
    * get data with custom sql statment
    * @param  pdo $db - database object as pdo type
    * @param  string $sql - custom sql statment
    * @return mixed - any returned data
    */
    public static function execute($db,$sql)
    {
        if($sql === null) return null;
        $cmd = $db->prepare($sql);
        $cmd->execute();
        $cmd->setFetchMode(PDO::FETCH_OBJ);
        $data =  $cmd->fetch();
        return  $data;
    }
    
    /**
    * set single row
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  array $data - associative array 'col'=>'val'
    * @param  string $filterc - primary key column name or any other columns name
    * @param  string $filterv - value to match the condition field to 
    * @param  int $val   key value
    */
    public static function set_row($db,$table,$data,$filterc,$filterv) {
        
        if($data === null)
            exit;
        elseif ($filterc !== null&&$filterv !== null) 
        {
            // get values
            $v = array_values($data);
            // add condition value
            array_push($v,$filterv);
            // get keys
            $c=array();
            foreach (array_keys($data) as $k) {
                $c[]=$k."=?";
            }
            // phrase command
            $cmd=$db->prepare("UPDATE $table SET ".implode(', ', $c)." WHERE $filterc=?;");
            $cmd->execute($v);
        }
        else 
        {
            // get values
            $v = array_values($data);
            // get keys
            $c=implode(', ', array_keys($data));
            // phrase command
            $cmd=$db->prepare("INSERT INTO $table ($c) values (".str_repeat("?,",count($c)-1)."?)");
            $cmd->execute($v);
        }
    }

    /**
    * delete row
    * @param  string $table table name
    * @param  string $where column name for condition (commonly primay key column name)
    * @param   int $id   key value
    */
    public static function delete_row($db,$table,$filterc,$filterv) {
        $cmd=$db->prepare("DELETE FROM $table WHERE $filterc=?");
        $cmd->execute($filterv);
    }

    /**
    * delete rows
    * @param  string $table table name
    * @param  string $where column name for condition (commonly primay key column name)
    * @param   int $id   key value
    */
    public static function delete_rows($db,$table,$filterc,$filterv) {
        $cmd=$db->prepare("DELETE FROM $table WHERE $filterc=?");
        $cmd->execute($filterv);
    }
}
?>