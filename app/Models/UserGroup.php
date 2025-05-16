<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserGroup extends Pivot
{
    use HasFactory, HasUniqueIdentifier, GetTableName;
    protected $table = 'v_user_groups';
    protected $primaryKey = 'user_group_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    // Map Laravel's timestamps to your custom timestamp columns
    const CREATED_AT = 'insert_date';
    const UPDATED_AT = 'update_date';

    protected $fillable = [
        'domain_uuid',
        'user_uuid',
        'group_uuid',
        'group_name'  // This appears in your table but might be redundant
    ];

    public function domain(): BelongsTo {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_uuid', 'user_uuid');
    }

    public function group(): BelongsTo {
        return $this->belongsTo(Group::class, 'group_uuid', 'group_uuid');
    }
}
