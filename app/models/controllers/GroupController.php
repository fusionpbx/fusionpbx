<?php
/**
 * FusionPBX - Group API Controller
 * 
 * RESTful API controller for group management with permissions.
 * 
 * @package    FusionPBX
 * @subpackage Controllers
 */

namespace FusionPBX\Controllers;

use FusionPBX\Models\Group;
use FusionPBX\Models\Permission;
use FusionPBX\Models\GroupPermission;

class GroupController extends BaseController
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
     * List groups
     * GET /api/groups
     */
    protected function index()
    {
        $this->requirePermission('group_view');
        
        try {
            $groups = Group::forDomain($this->domain_uuid)
                ->with(['users', 'permissionsList'])
                ->get();
            
            $this->success(['groups' => $groups]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get single group
     * GET /api/groups/{id}
     */
    protected function show($id)
    {
        $this->requirePermission('group_view');
        
        try {
            $group = Group::with(['users', 'permissionsList', 'domain'])
                ->find($id);
            
            if (!$group) {
                $this->notFound('Group not found');
            }
            
            $this->validateDomainOwnership($group);
            
            $this->success(['group' => $group]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Create new group
     * POST /api/groups
     */
    protected function store()
    {
        $this->requirePermission('group_add');
        
        $errors = $this->validateRequired(['group_name']);
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $group = Group::create([
                'group_uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'domain_uuid' => $this->domain_uuid,
                'group_name' => $this->param('group_name'),
                'group_level' => $this->param('group_level', 50),
                'group_description' => $this->param('group_description'),
                'group_protected' => $this->param('group_protected', 'false'),
            ]);
            
            // Assign permissions if provided
            if ($permissions = $this->param('permissions')) {
                if (is_array($permissions)) {
                    $group->syncPermissions($permissions, $this->domain_uuid);
                }
            }
            
            $this->created($group, 'Group created successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Update group
     * PUT /api/groups/{id}
     */
    protected function update($id)
    {
        $this->requirePermission('group_edit');
        
        if (!$id) {
            $this->badRequest('Group ID required');
        }
        
        try {
            $group = Group::find($id);
            
            if (!$group) {
                $this->notFound('Group not found');
            }
            
            $this->validateDomainOwnership($group);
            
            $fillable = ['group_name', 'group_level', 'group_description', 'group_protected'];
            
            foreach ($fillable as $field) {
                if ($this->param($field) !== null) {
                    $group->$field = $this->param($field);
                }
            }
            
            $group->save();
            
            // Update permissions if provided
            if ($permissions = $this->param('permissions')) {
                if (is_array($permissions)) {
                    $group->syncPermissions($permissions, $this->domain_uuid);
                }
            }
            
            $this->success($group, 'Group updated successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Delete group
     * DELETE /api/groups/{id}
     */
    protected function destroy($id)
    {
        $this->requirePermission('group_delete');
        
        if (!$id) {
            $this->badRequest('Group ID required');
        }
        
        try {
            $group = Group::find($id);
            
            if (!$group) {
                $this->notFound('Group not found');
            }
            
            $this->validateDomainOwnership($group);
            
            // Check if protected
            if ($group->group_protected === 'true' || $group->group_protected === true) {
                $this->forbidden('Cannot delete protected group');
            }
            
            $group->delete();
            
            $this->success(null, 'Group deleted successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get group permissions
     * GET /api/groups/{id}/permissions
     */
    public function permissions($id)
    {
        $this->requireAuth();
        $this->requirePermission('group_view');
        
        try {
            $group = Group::find($id);
            
            if (!$group) {
                $this->notFound('Group not found');
            }
            
            $this->validateDomainOwnership($group);
            
            $permissions = $group->permissionsList;
            $permissionNames = $group->getPermissionNames();
            
            $this->success([
                'permissions' => $permissions,
                'permission_names' => $permissionNames
            ]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Grant permission to group
     * POST /api/groups/{id}/permissions
     */
    public function grantPermission($id)
    {
        $this->requireAuth();
        $this->requirePermission('group_edit');
        
        $errors = $this->validateRequired(['permission_uuid']);
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $group = Group::find($id);
            
            if (!$group) {
                $this->notFound('Group not found');
            }
            
            $this->validateDomainOwnership($group);
            
            $group->grantPermission(
                $this->param('permission_uuid'),
                $this->domain_uuid
            );
            
            $this->success(null, 'Permission granted successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Revoke permission from group
     * DELETE /api/groups/{id}/permissions/{permission_id}
     */
    public function revokePermission($id, $permissionId)
    {
        $this->requireAuth();
        $this->requirePermission('group_edit');
        
        try {
            $group = Group::find($id);
            
            if (!$group) {
                $this->notFound('Group not found');
            }
            
            $this->validateDomainOwnership($group);
            
            $group->revokePermission($permissionId, $this->domain_uuid);
            
            $this->success(null, 'Permission revoked successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
}
