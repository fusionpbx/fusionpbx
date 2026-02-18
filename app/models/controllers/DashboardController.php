<?php
/**
 * FusionPBX - Dashboard API Controller
 * 
 * RESTful API controller for dashboard management with Eloquent.
 * 
 * @package    FusionPBX
 * @subpackage Controllers
 */

namespace FusionPBX\Controllers;

use FusionPBX\Models\Dashboard;
use FusionPBX\Models\DashboardWidget;

class DashboardController extends BaseController
{
    /**
     * Handle incoming requests
     */
    public function handle()
    {
        $this->requireAuth();
        
        $id = $this->param('id');
        
        switch ($this->method) {
            case 'GET':
                return $id ? $this->show($id) : $this->index();
            case 'POST':
                return $this->store();
            case 'PUT':
            case 'PATCH':
                return $this->update($id);
            case 'DELETE':
                return $this->destroy($id);
            default:
                $this->error('Method not allowed', 405);
        }
    }
    
    /**
     * List dashboards
     * GET /api/dashboards
     */
    protected function index()
    {
        $this->requirePermission('dashboard_view');
        
        try {
            $query = Dashboard::forDomain($this->domain_uuid);
            
            if ($this->param('enabled') === 'true') {
                $query->enabled();
            }
            
            $dashboards = $query->with(['widgets', 'domain'])
                ->get();
            
            $this->success(['dashboards' => $dashboards]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get single dashboard
     * GET /api/dashboards/{id}
     */
    protected function show($id)
    {
        $this->requirePermission('dashboard_view');
        
        try {
            $dashboard = Dashboard::with(['widgets', 'domain'])
                ->find($id);
            
            if (!$dashboard) {
                $this->notFound('Dashboard not found');
            }
            
            $this->validateDomainOwnership($dashboard);
            
            $this->success(['dashboard' => $dashboard]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Create new dashboard
     * POST /api/dashboards
     */
    protected function store()
    {
        $this->requirePermission('dashboard_add');
        
        $errors = $this->validateRequired(['dashboard_name']);
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $dashboard = Dashboard::create([
                'dashboard_uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'domain_uuid' => $this->domain_uuid,
                'dashboard_name' => $this->param('dashboard_name'),
                'dashboard_enabled' => $this->param('dashboard_enabled', 'true'),
                'dashboard_description' => $this->param('dashboard_description'),
            ]);
            
            $this->created($dashboard, 'Dashboard created successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Update dashboard
     * PUT /api/dashboards/{id}
     */
    protected function update($id)
    {
        $this->requirePermission('dashboard_edit');
        
        if (!$id) {
            $this->badRequest('Dashboard ID required');
        }
        
        try {
            $dashboard = Dashboard::find($id);
            
            if (!$dashboard) {
                $this->notFound('Dashboard not found');
            }
            
            $this->validateDomainOwnership($dashboard);
            
            $fillable = ['dashboard_name', 'dashboard_enabled', 'dashboard_description'];
            
            foreach ($fillable as $field) {
                if ($this->param($field) !== null) {
                    $dashboard->$field = $this->param($field);
                }
            }
            
            $dashboard->save();
            
            $this->success($dashboard, 'Dashboard updated successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Delete dashboard
     * DELETE /api/dashboards/{id}
     */
    protected function destroy($id)
    {
        $this->requirePermission('dashboard_delete');
        
        if (!$id) {
            $this->badRequest('Dashboard ID required');
        }
        
        try {
            $dashboard = Dashboard::find($id);
            
            if (!$dashboard) {
                $this->notFound('Dashboard not found');
            }
            
            $this->validateDomainOwnership($dashboard);
            
            $dashboard->delete();
            
            $this->success(null, 'Dashboard deleted successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
}
