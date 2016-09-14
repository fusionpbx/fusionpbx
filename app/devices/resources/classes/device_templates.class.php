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

class device_templates
{
    /**
    * enumerate templates from database
    * @param  pdo $db - database object as pdo type
    * @param  string $domain_uuid - the uuid of the domain or null for global
    * @return object - template data as array ofclass object
    */
    public static function enumerate($db, $domain_uuid = null)
    {
        // prepare sql
        $sql = "SELECT * FROM v_device_templates ";
        $data = [];
        
        if ($domain_uuid && $domain_uuid!="Global") {
            $sql .= " WHERE domain_uuid IS NULL";
        }
        elseif($domain_uuid)  {
            $sql .= " WHERE domain_uuid = ?";
            $data[] = $domain_uuid;
        }

        // execute
        //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $cmd = $db->prepare($sql);
        $cmd->execute($data);
        $data = $cmd->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_UNIQUE, __CLASS__);
        // return data
        return  $data;
    }


    /**
    * enumerate spacific templates from database
    * @param  pdo $db - database object as pdo type
    * @param  array $filter - list of conditions
    * @param  array $columns - list of fields
    * @return object - template data as array of class objects
    */
    public static function find($db, $filter = null, $columns = null, $sort = null, $options = null)
    {
        // prepare sql
        $data = [];
        $sql = "SELECT ".(($columns === null)?"*":implode(',', $columns))." FROM v_device_templates ";

        //filter
        if (isset($filter)&&!empty($filter)) {
            $sql .= "WHERE ";
            if (is_array($filter[0])) {
                foreach ($filter as $k => $v) {
                    if (count($v)==4) {
                        $sql .= "$v[0] $v[1] ? ".$v[3]." "; 
                        $data[] = $v[2];
                    }
                    elseif (count($v)==3) {
                        $sql .= "$v[0] $v[1] ? "; 
                        $data[] = $v[2];
                    }
                    elseif (count($v)==1) {
                        $sql .= $v[0]." ";
                    }
                }
            }
            elseif (is_array($filter)) {
                $sql .= "$filter[0] $filter[1] ? ".$filter[3]." "; 
                $data[] = $filter[2];
            }
            elseif (is_string($filter)) {
                $sql .= $filter." ";
            }
        }

        //sort
        if (isset($sort)&&!empty($sort)) {
            $sql .= "ORDER BY ";
            if (is_array($sort[0])) {
                foreach ($sort as $k => $v) {
                    $sql .= "$v[0] $v[1],";
                }
                $sql = rtrim($sql,',');
            }
            elseif (is_array($sort)) {
                $sql .= "$sort[0] $sort[1]";
            }
            elseif (is_string($sort)){
                $sql .= $sort;
            }
        }
        
        // execute
        //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $cmd = $db->prepare($sql);
        $cmd->execute($data);
        if ($options['numbered']) {
            $data = $cmd->fetchAll(PDO::FETCH_ASSOC);
        }
        elseif ($options['named']) {
            $data = $cmd->fetchAll(PDO::FETCH_NAMED|PDO::FETCH_UNIQUE);
        }
        elseif ($options['numberedvalue']) {
            $data = $cmd->fetchAll(PDO::FETCH_COLUMN);
        }
        elseif ($options['namedvalue']) {
            $data = $cmd->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        else {
            $data = $cmd->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_UNIQUE, __CLASS__);
        }
        // return data
        return  $data;
    }

    /**
    * count spacific templates from database
    * @param  pdo $db - database object as pdo type
    * @param  array $filter - list of conditions
    * @return mixed - value of the count
    */
    public static function count($db, $filter = null)
    {
        // prepare sql
        $data = self::find($db, $filter, ['COUNT(0)'], null, [numbered=>true]);
        // return data
        return  $data[0];
    }

    /**
    * copy template in database
    * @param  pdo $db - database object as pdo type
    * @param  string $original - template uuid in database
    * @param  string $clone - template uuid for clone
    * @return object - template data as class object
    */
    public static function copy($db, $original, $override=null)
    {
        $data = (array) self::get($db, $original);
        $data['uuid'] = null;
        $data['name'] .= " - copy ".date("Y-m-d H:i:s");
        $data['protected'] = "false";
        if (is_array($override)) {
            $data = array_merge((array) $data, (array) $override);
        }
        return  self::put($db, null, (array) $data);
    }

    /**
    * get template from database
    * @param  pdo $db - database object as pdo type
    * @param  string $uuid - template uuid
    * @return object - template data as class object
    */
    public static function get($db, $uuid, $columns = null)
    {
        // prepare sql
        $sql = "SELECT ".(($columns === null)?"*":implode(',', $columns))." FROM v_device_templates ";
        
        if(is_array($uuid)){
            $sql .= "WHERE uuid IN (".str_repeat("?,",count($uuid)-1)."?)";
        }
        else{
            $sql .= "WHERE uuid = ?";
            $uuid = [$uuid];
        }

        // execute
        //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $cmd = $db->prepare($sql);
        $cmd->execute($uuid);
        //$data = $cmd->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_UNIQUE, "device_template");
        $data = $cmd->fetchObject(__CLASS__);
        return  $data;
    }

    /**
    * put template data to database
    * @param  pdo $db - database object as pdo type
    * @param  string $uuid - template uuid
    * @param  array $data - associative array of data to put in the database
    * @return nothing
    */
    public static function put($db, $uuid, $data = array())
    {
        if($data === null)
            exit;
        elseif ($uuid) 
        {
            // remove uuid if present
            if (isset($data['uuid'])) unset($data['uuid']);
            // get values
            $v = array_values($data);
            // add condition value
            $v[] = $uuid;
            // get keys
            $c = [];
            foreach (array_keys($data) as $k) {
                $c[]=$k."=?";
            }
            // phrase command
            //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->prepare("UPDATE v_device_templates SET ".implode(', ', $c)." WHERE uuid=?;")->execute($v);
            return $data['uuid'];
        }
        else 
        {
            // generate and add uuid if not present
            $data['uuid'] = is_uuid($data['uuid']) ? $data['uuid'] : uuid();
            // get values
            $v = array_values($data);
            // get keys
            $c = implode(", ", array_keys($data));
            // phrase command
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->prepare("INSERT INTO v_device_templates ($c) values (".str_repeat("?,",count($v)-1)."?)")->execute($v);
            return $data['uuid'];
        }
    }

    /**
    * drop template(s) from database
    * @param  pdo $db - database object as pdo type
    * @param  mixed $uuid - array to single value of template uuid
    * @return nothing
    */
    public static function drop($db, $uuid)
    {
        // prepare sql
        if($columns === null) $columns[] = "*";
        $sql = "DELETE FROM v_device_templates WHERE ";
        
        if(is_array($uuid)){
            $sql .= "uuid IN (".str_repeat("?,",count($uuid)-1)."?)";
        }
        else{
            $sql .= "uuid = ?";
            $uuid = [$uuid];
        }

        // execute
        //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->prepare($sql)->execute($uuid);
    }

    /**
    * compiles all master and slave templates in to one string
    * @param  pdo $db - database object as pdo type
    * @param  string $uuid - template uuid
    * @return string - template data 
    */
    public static function compile($db, $uuid)
    {
        $t = self::get($db,$uuid,['data','include']);
        
        // includes - load them and add them
        if (isset($t->include)) {
            // get the includes
            foreach (explode(",", $t->include) as $k => $v) {
                $tl[] = self::get($db,$v,['data'])->data;
            }
            // find magic tokens and replace them
            if (preg_match("/\<\[include\:\d{1,2}\]\>/", $t->data)) {
                $d = $t->data;
                foreach($tl as $k => $v){
                    $d = str_replace('<[include:'.($k+1).']>',$v, $d);
                }
            }
            // no magic tokens so just concact
            else {
                $d = $t->data."\n".implode("\n",$tl);
            }
        }
        // no includes - just send data 
        else {
            $d = $t->data."\n";
        }

        return  $d;
    }
}
?>