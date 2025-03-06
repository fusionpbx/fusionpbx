<?php
namespace App\Traits;


trait GetTableName{

    public static function getTableName()
    {
        return with(new static)->getTable();
    }
}
