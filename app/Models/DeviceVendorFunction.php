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

class DeviceVendorFunction extends Model
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_device_vendor_functions';
	protected $primaryKey = 'device_vendor_function_uuid';
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
        'device_vendor_uuid',
        'type',
        'subtype',
        'value',
        'enabled',
        'description',
    ];

    public function groups(): BelongsToMany {
        return $this->belongsToMany(DeviceVendorFunctionGroup::class, 'v_device_vendor_function_groups', 'device_vendor_function_uuid', 'group_uuid');
    }

	 public function devicevendor(): BelongsTo {
        return $this->belongsTo(DeviceVendor::class, 'device_vendor_uuid', 'device_vendor_uuid');
    }
}
