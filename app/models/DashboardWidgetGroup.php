<?php
/**
 * FusionPBX - DashboardWidgetGroup Model
 * 
 * Eloquent model for v_dashboard_widget_groups table.
 * Links widgets to groups for permission-based widget visibility.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class DashboardWidgetGroup extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_dashboard_widget_groups';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'dashboard_widget_group_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dashboard_widget_group_uuid',
        'dashboard_widget_uuid',
        'group_uuid',
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
     * Get the dashboard widget that this group assignment belongs to.
     */
    public function dashboardWidget()
    {
        return $this->belongsTo(DashboardWidget::class, 'dashboard_widget_uuid', 'dashboard_widget_uuid');
    }

    /**
     * Get the group associated with this widget.
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_uuid', 'group_uuid');
    }
}
