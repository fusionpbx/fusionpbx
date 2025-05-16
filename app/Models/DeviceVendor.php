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

class DeviceVendor extends Model
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_device_vendors';
	protected $primaryKey = 'device_vendor_uuid';
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
        'name',
        'enabled',
        'description',
    ];

	 public function functions(): HasMany {
        return $this->HasMany(DeviceVendorFunction::class, 'device_vendor_uuid', 'device_vendor_uuid');
    }
}
