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

class devices
{
    /**
    * enumerate spacific devices
    * @param  pdo $db - database object as pdo type
    * @param  array $filter - list of conditions
    * @param  array $columns - list of fields
    * @return object - vendor data as array of class objects
    */
    public static function find($db, $filter = null, $columns = null, $sort = null, $options = null)
    {
        // prepare sql
        $data = [];
        $sql = "SELECT ".(($columns === null)?"*":implode(',', $columns))." FROM v_devices ";

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
    * enumerate unique domains
    * @param  pdo $db - database object as pdo type
    * @return object - data as key value pair array
    */
    public static function list_linked_domains($db)
    {
        // prepare sql
        $data = [];
        $sql = "SELECT DISTINCT v_devices.domain_uuid AS domain_uuid, v_domains.domain_name AS domain_name FROM v_devices ";
        $sql .= "LEFT JOIN v_domains ON v_domains.domain_uuid = v_devices.domain_uuid ";
        
        // execute
        //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $cmd = $db->prepare($sql);
        $cmd->execute($data);
        $data = $cmd->fetchAll(PDO::FETCH_KEY_PAIR);
        // return data
        return  $data;
    }

    /**
    * enumerate unique templates
    * @param  pdo $db - database object as pdo type
    * @return object - data as key value pair array
    */
    public static function list_linked_templates($db)
    {
        // prepare sql
        $data = [];
        $sql = "SELECT DISTINCT v_devices.device_template AS template_uuid, v_device_templates.name AS template_name FROM v_devices ";
        $sql .= "LEFT JOIN v_device_templates ON CAST(v_device_templates.uuid AS TEXT) = v_devices.device_template ";
        
        // execute
        //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $cmd = $db->prepare($sql);
        $cmd->execute($data);
        $data = $cmd->fetchAll(PDO::FETCH_KEY_PAIR);
        // return data
        return  $data;
    }

    /**
    * enumerate unique vendors
    * @param  pdo $db - database object as pdo type
    * @return object - data as key value pair array
    */
    public static function list_linked_vendors($db)
    {
        // prepare sql
        $data = [];
        $sql = "SELECT DISTINCT v_devices.device_vendor AS vendor_uuid, v_device_vendors.name AS vendor_name FROM v_devices ";
        $sql .= "LEFT JOIN v_device_vendors ON CAST(v_device_vendors.device_vendor_uuid AS TEXT) = v_devices.device_vendor ";
        
        // execute
        //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $cmd = $db->prepare($sql);
        $cmd->execute($data);
        $data = $cmd->fetchAll(PDO::FETCH_KEY_PAIR);
        // return data
        return  $data;
    }

    /**
    * put device data to database
    * @param  pdo $db - database object as pdo type
    * @param  string $uuid - device uuid
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
            if (isset($data['device_uuid'])) unset($data['device_uuid']);
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
            $db->prepare("UPDATE v_devices SET ".implode(', ', $c)." WHERE device_uuid=?;")->execute($v);
            return $data['device_uuid'];
        }
        else 
        {
            // generate and add uuid if not present
            $data['device_uuid'] = is_uuid($data['device_uuid']) ? $data['device_uuid'] : uuid();
            // get values
            $v = array_values($data);
            // get keys
            $c = implode(", ", array_keys($data));
            // phrase command
            //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->prepare("INSERT INTO v_devices ($c) values (".str_repeat("?,",count($v)-1)."?)")->execute($v);
            return $data['device_uuid'];
        }
    }
}

?>