<?php

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
    * get only one row
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  string $condition - condition field
    * @param  string $value - value of condition field
    * @return array table row
    */
    public static function get_row($db,$table,$condition,$value)
    {
        $db->prepare("SELECT * FROM $table WHERE $condition=?");
        $db->execute($value);
        $db->setFetchMode(PDO::FETCH_OBJ);
        $data =  $db->fetch();
        return $data;
    }

    /**
    * get only single field value
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  string $field - field name to be returned
    * @param  string $condition - condition field
    * @param  string $value - value of condition field
    * @return mixed data in field
    */
    public static function get_value($db,$table,$column,$condition_field,$condition_value)
    {
        $statment = $db->prepare("SELECT $column FROM $table WHERE $condition_field=?");
        $statment->bindParam(1, $condition_value);
        $statment->execute();
        $data = $statment->fetchColumn(0);
        return $data;
    }

    /**
    * get count of rows
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  string $field - field name to be returned
    * @param  string $condition - condition field
    * @param  string $value - value of condition field
    * @return mixed data in field
    */
    public static function get_count($db,$table,$condition_field,$condition_operator,$condition_value)
    {
        $statment = $db->prepare("SELECT COUNT(0) FROM $table WHERE $condition_field $condition_operator ?");
        $statment->bindParam(1, $condition_value);
        $statment->execute();
        $data = $statment->fetchColumn(0);
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
        $statment = $db->prepare("SELECT ".implode(',', $columns)." FROM $table WHERE $condition_field $condition_operator ?");
        $statment->execute();
        $statment->setFetchMode(PDO::FETCH_OBJ);
        $data =  $db->fetch();
        return  $data;
    }

    /**
    * get custom sql statment
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  array $fields - specific columns to return
    * @return array selected tables and rows
    */
    public static function get_sql($db,$sql)
    {
        if($sql === null) return null;
        $statment = $db->prepare($sql);
        $statment->execute();
        $statment->setFetchMode(PDO::FETCH_OBJ);
        $data =  $db->fetch();
        return  $data;
    }
    
    /**
    * get number of rows
    * @param  pdo $db - database object as pdo type
    * @param  string $table - table name
    * @param  array $data - associative array 'col'=>'val'
    * @param  string $condition_field - primary key column name or any other columns name
    * @param  string $condition_value - value to match the condition field to 
    * @param  int $val   key value
    */
    public function set_row($db,$table,$data,$condition_field,$condition_value) {
        
        if($data !== null)
            exit;
        elseif ($condition_field !== null&&$condition_value !== null) 
        {
            $data = array_values($data);
            array_push($data,$value);
            //grab keys
            $cols=array();
                foreach (array_keys($data) as $col) {
                $cols[]=$col."=?";
            }
            $ins=$db->prepare("UPDATE $table SET ".implode(', ', $cols)." where $condition_field=$condition_value");
            $ins->execute($data);
        }
        else 
        {
            $data = array_values($data);
            //grab keys
            $cols=implode(', ', array_keys($data));
            //grab values and change it value
            $vals=array();
            foreach ($data as $key) {
                $keys='?';
                $vals[]=$keys;
            }
            $ins=$db->prepare("INSERT INTO $table ($col) values (".implode(',', $vals).")");
            $ins->execute($data);
        }
    }

    /**
    * delete record
    * @param  string $table table name
    * @param  string $where column name for condition (commonly primay key column name)
    * @param   int $id   key value
    */
    public function delete($db,$table,$condition_field,$condition_value) {
        $statment=$db->prepare("DELETE FROM $table WHERE $condition_field=?");
        $statment->execute($condition_value);
    }
}
?>