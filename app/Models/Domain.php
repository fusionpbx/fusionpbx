<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'v_domains';
    protected $primaryKey = 'domain_uuid';
    public $incrementing = false;
    protected $keyType='string';	// TODO, check if UUID is valid
    const CREATED_AT = 'insert_date';
    const UPDATED_AT = 'update_date';
}
