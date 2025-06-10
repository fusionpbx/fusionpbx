<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DeviceVendorFunctionGroup extends Pivot
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_device_vendor_function_groups';
	protected $primaryKey = 'device_vendor_function_group_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
        'device_vendor_function_uuid',
        'device_vendor_uuid',
        'group_name',
        'group_uuid',       // FIXME: Fusion does not use the uuid, instead it uses the name
    ];

    public function devicevendorfunction(): BelongsToMany 
    {
        return $this->BelongsToMany (DeviceVendorFunction::class, 'device_vendor_function_uuid', 'device_vendor_function_uuid');
    }
}
