<?php

namespace App\Models;

use App\Traits\GetTableName;
use App\Traits\HandlesStringBooleans;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailQueue extends Model
{
    use HasFactory, HasUniqueIdentifier, GetTableName, HandlesStringBooleans;

    protected $table = "v_email_queue";
    protected $primaryKey = "email_queue_uuid";
    public $incrementing = false;
    protected $keyType = "string";

    public $timestamps = false;

    protected $fillable = [
        'email_queue_uuid',
        'domain_uuid',
        'hostname',
        'email_to',
        'email_from',
        'email_date',
        'email_subject',
        'email_body',
        'email_status',
        'email_retry_count',
        'email_action_before',
        'email_action_after',
        'email_uuid',
        'email_trnsaction',
        'email_response',
    ];

    protected $casts = [
        'email_date' => 'datetime',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    public function attachments()
    {
        return $this->hasMany(EmailQueueAttachment::class, 'email_queue_uuid', 'email_queue_uuid');
    }
}
