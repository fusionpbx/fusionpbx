<?php

namespace App\Models;

use App\Traits\GetTableName;
use App\Traits\HandlesStringBooleans;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailQueueAttachment extends Model
{
    use HasFactory, HasUniqueIdentifier, GetTableName, HandlesStringBooleans;

    protected $table = "v_email_queue_attachments";
    protected $primaryKey = "email_queue_attachment_uuid";
    public $incrementing = false;
    protected $keyType = "string";
    const CREATED_AT = "created_at";

    protected $fillable = [
        'email_queue_attachment_uuid',
        'domain_uuid',
        'email_queue_uuid',
        'email_attachment_mime_type',
        'email_attachment_type',
        'email_attachment_path',
        'email_attachment_name',
        'email_attachment_base64',
        'email_attachment_cid',
    ];

    public function emailQueue()
    {
        return $this->belongsTo(EmailQueue::class, 'email_queue_uuid', 'email_queue_uuid');
    }
}
