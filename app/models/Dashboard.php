<?php
/**
 * FusionPBX - Dashboard Model
 * 
 * Eloquent model for v_dashboards table.
 * Represents a dashboard configuration in the FusionPBX system.
 * Supports multi-tenant domain-based dashboards.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Dashboard extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_dashboards';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'dashboard_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dashboard_uuid',
        'domain_uuid',
        'dashboard_name',
        'dashboard_enabled',
        'dashboard_description',
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
        'dashboard_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the dashboard belongs to.
     * Essential for multi-tenant support.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the widgets for this dashboard.
     */
    public function widgets()
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_uuid', 'dashboard_uuid');
    }

    /**
     * Get only enabled widgets for this dashboard.
     */
    public function enabledWidgets()
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_uuid', 'dashboard_uuid')
            ->where('widget_enabled', 'true')
            ->orderBy('widget_order');
    }

    /**
     * Scope a query to only include enabled dashboards.
     * Note: FusionPBX stores boolean values as strings 'true'/'false'
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('dashboard_enabled', 'true');
    }

    /**
     * Get dashboards accessible by a specific user.
     * Filters by user's domain for multi-tenant isolation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $userUuid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userUuid)
    {
        return $query->whereHas('domain.users', function($q) use ($userUuid) {
            $q->where('user_uuid', $userUuid);
        });
    }
}
