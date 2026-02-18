<?php
/**
 * FusionPBX - Contact Model
 * 
 * Eloquent model for v_contacts table.
 * Represents a contact in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Contact extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_contacts';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'contact_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_uuid',
        'domain_uuid',
        'contact_type',
        'contact_organization',
        'contact_name_given',
        'contact_name_family',
        'contact_nickname',
        'contact_title',
        'contact_category',
        'contact_role',
        'contact_email',
        'contact_url',
        'contact_time_zone',
        'contact_note',
        'contact_scope',
        'insert_date',
        'insert_user',
        'update_date',
        'update_user'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the contact belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the contact phones.
     */
    public function phones()
    {
        return $this->hasMany(ContactPhone::class, 'contact_uuid', 'contact_uuid');
    }

    /**
     * Get the contact addresses.
     */
    public function addresses()
    {
        return $this->hasMany(ContactAddress::class, 'contact_uuid', 'contact_uuid');
    }

    /**
     * Get the contact emails.
     */
    public function emails()
    {
        return $this->hasMany(ContactEmail::class, 'contact_uuid', 'contact_uuid');
    }

    /**
     * Get the contact notes.
     */
    public function notes()
    {
        return $this->hasMany(ContactNote::class, 'contact_uuid', 'contact_uuid');
    }

    /**
     * Get the contact attachments.
     */
    public function attachments()
    {
        return $this->hasMany(ContactAttachment::class, 'contact_uuid', 'contact_uuid');
    }

    /**
     * Get the users associated with the contact.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'v_contact_users',
            'contact_uuid',
            'user_uuid'
        );
    }
}
