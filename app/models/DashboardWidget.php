<?php
/**
 * FusionPBX - DashboardWidget Model
 * 
 * Eloquent model for v_dashboard_widgets table.
 * Represents widgets that can be displayed on dashboards.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class DashboardWidget extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_dashboard_widgets';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'dashboard_widget_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dashboard_widget_uuid',
        'dashboard_uuid',
        'dashboard_widget_parent_uuid',
        'widget_name',
        'widget_path',
        'widget_order',
        'widget_enabled',
        'widget_description',
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
        'widget_order' => 'integer',
        'widget_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the dashboard that this widget belongs to.
     */
    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class, 'dashboard_uuid', 'dashboard_uuid');
    }

    /**
     * Get the parent widget (for nested widgets).
     */
    public function parent()
    {
        return $this->belongsTo(DashboardWidget::class, 'dashboard_widget_parent_uuid', 'dashboard_widget_uuid');
    }

    /**
     * Get child widgets (for nested widgets).
     */
    public function children()
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_widget_parent_uuid', 'dashboard_widget_uuid');
    }

    /**
     * Get the widget groups for this widget.
     */
    public function widgetGroups()
    {
        return $this->hasMany(DashboardWidgetGroup::class, 'dashboard_widget_uuid', 'dashboard_widget_uuid');
    }

    /**
     * Scope a query to only include enabled widgets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('widget_enabled', 'true');
    }

    /**
     * Scope a query to order by widget order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('widget_order');
    }

    /**
     * Scope a query to only include top-level widgets (no parent).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('dashboard_widget_parent_uuid');
    }
}
