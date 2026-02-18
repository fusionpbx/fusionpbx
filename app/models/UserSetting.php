<?php
/**
 * FusionPBX - UserSetting Model
 * 
 * Eloquent model for v_user_settings table.
 * Represents user-specific settings and preferences.
 * Supports multi-tenant domain-based user settings.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class UserSetting extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_user_settings';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_setting_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_setting_uuid',
        'user_uuid',
        'domain_uuid',
        'user_setting_category',
        'user_setting_subcategory',
        'user_setting_name',
        'user_setting_value',
        'user_setting_enabled',
        'user_setting_description',
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
        'user_setting_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the user that this setting belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'user_uuid');
    }

    /**
     * Get the domain that this setting belongs to.
     * Essential for multi-tenant support.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Scope a query to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('user_setting_category', $category);
    }

    /**
     * Scope a query to filter by category and subcategory.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @param  string  $subcategory
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategorySubcategory($query, $category, $subcategory)
    {
        return $query->where('user_setting_category', $category)
            ->where('user_setting_subcategory', $subcategory);
    }

    /**
     * Scope a query to filter by setting name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeName($query, $name)
    {
        return $query->where('user_setting_name', $name);
    }

    /**
     * Scope a query to only include enabled settings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('user_setting_enabled', 'true');
    }

    /**
     * Get a specific setting value for a user.
     *
     * @param  string  $userUuid
     * @param  string  $category
     * @param  string  $subcategory
     * @param  string  $name
     * @param  mixed  $default
     * @return mixed
     */
    public static function getValue($userUuid, $category, $subcategory, $name, $default = null)
    {
        $setting = static::where('user_uuid', $userUuid)
            ->where('user_setting_category', $category)
            ->where('user_setting_subcategory', $subcategory)
            ->where('user_setting_name', $name)
            ->where('user_setting_enabled', 'true')
            ->first();

        return $setting ? $setting->user_setting_value : $default;
    }

    /**
     * Set a specific setting value for a user.
     *
     * @param  string  $userUuid
     * @param  string  $domainUuid
     * @param  string  $category
     * @param  string  $subcategory
     * @param  string  $name
     * @param  mixed  $value
     * @return UserSetting
     */
    public static function setValue($userUuid, $domainUuid, $category, $subcategory, $name, $value)
    {
        return static::updateOrCreate(
            [
                'user_uuid' => $userUuid,
                'user_setting_category' => $category,
                'user_setting_subcategory' => $subcategory,
                'user_setting_name' => $name,
            ],
            [
                'domain_uuid' => $domainUuid,
                'user_setting_value' => $value,
                'user_setting_enabled' => 'true',
            ]
        );
    }
}
