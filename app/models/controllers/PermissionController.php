<?php
/**
 * FusionPBX - Permission API Controller
 * 
 * RESTful API controller for system permissions.
 * 
 * @package    FusionPBX
 * @subpackage Controllers
 */

namespace FusionPBX\Controllers;

use FusionPBX\Models\Permission;

class PermissionController extends BaseController
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
            default:
                $this->error('Method not allowed', 405);
        }
    }
    
    /**
     * List permissions
     * GET /api/permissions
     */
    protected function index()
    {
        $this->requirePermission('permission_view');
        
        try {
            $query = Permission::query();
            
            // Filter by application
            if ($application = $this->param('application')) {
                $query->byApplication($application);
            }
            
            // Search
            if ($search = $this->param('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('permission_name', 'like', "%{$search}%")
                      ->orWhere('permission_description', 'like', "%{$search}%")
                      ->orWhere('application_name', 'like', "%{$search}%");
                });
            }
            
            $permissions = $query->get();
            
            // Group by application if requested
            if ($this->param('group_by') === 'application') {
                $grouped = $permissions->groupBy('application_name');
                $this->success(['permissions' => $grouped]);
            } else {
                $this->success(['permissions' => $permissions]);
            }
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get single permission
     * GET /api/permissions/{id}
     */
    protected function show($id)
    {
        $this->requirePermission('permission_view');
        
        try {
            $permission = Permission::with(['groups'])
                ->find($id);
            
            if (!$permission) {
                $this->notFound('Permission not found');
            }
            
            $this->success(['permission' => $permission]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get permissions by application
     * GET /api/permissions/by-application/{application}
     */
    public function byApplication($application)
    {
        $this->requireAuth();
        $this->requirePermission('permission_view');
        
        try {
            $permissions = Permission::byApplication($application)->get();
            
            $this->success([
                'application' => $application,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
}
