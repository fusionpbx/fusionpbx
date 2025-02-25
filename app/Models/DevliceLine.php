<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

class DeviceLine extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_device_lines';
	protected $primaryKey = 'device_line_uuid';
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
        'domain_uuid',
        'device_uuid',
        'line_number',
        'server_address',
        'server_address_primary',
        'server_address_secondary',
        'outbound_proxy_primary',
        'outbound_proxy_secondary',
        'label',
        'display_name',
        'user_id',
        'auth_id',
        'password',
        'sip_port',
        'sip_transport',
        'register_expires',
        'shared_line',
        'enabled',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function device(): BelongsTo {
		return $this->belongsTo(Device::class, 'device_uuid', 'device_uuid');
	}
}
