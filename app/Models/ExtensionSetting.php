<?php

namespace App\Models;

use App\Models\Extension;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class ExtensionSetting extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_extension_settings';
	protected $primaryKey = 'extension_setting_uuid';
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
        'extension_uuid',
        'extension_setting_type',
        'extension_setting_name',
        'extension_setting_value',
        'extension_setting_enabled',
        'extension_setting_description',
	];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
	protected $hidden = [
	];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
	protected $casts = [
	];

	public function extension(): BelongsTo {
		return $this->belongsTo(Extension::class, 'extension_suuid', 'extension_uuid');
	}

}
