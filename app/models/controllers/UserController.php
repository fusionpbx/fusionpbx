<?php
/**
 * FusionPBX - User API Controller
 * 
 * RESTful API controller for user management with Eloquent.
 * Handles CRUD operations with permission checks and multi-tenant isolation.
 * 
 * @package    FusionPBX
 * @subpackage Controllers
 */

namespace FusionPBX\Controllers;

use FusionPBX\Models\User;
use FusionPBX\Models\Group;
use FusionPBX\Models\UserSetting;

class UserController extends BaseController
{
    /**
     * Handle incoming requests
     */
    public function handle()
    {
        $this->requireAuth();
        
        // Get user ID from URL if provided
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
     * List users
     * GET /api/users
     */
    protected function index()
    {
        $this->requirePermission('user_view');
        
        try {
            $query = User::forDomain($this->domain_uuid);
            
            // Filter by status
            if ($this->param('enabled') === 'true') {
                $query->enabled();
            }
            
            // Search
            if ($search = $this->param('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('user_status', 'like', "%{$search}%");
                });
            }
            
            // Pagination
            $perPage = min($this->param('per_page', 30), 100);
            $page = $this->param('page', 1);
            
            // Get users with relationships
            $users = $query->with(['groups', 'domain'])
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
            
            $total = User::forDomain($this->domain_uuid)->count();
            
            $this->success([
                'users' => $users,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get single user
     * GET /api/users/{id}
     */
    protected function show($id)
    {
        $this->requirePermission('user_view');
        
        try {
            $user = User::with(['groups', 'domain', 'settings'])
                ->find($id);
            
            if (!$user) {
                $this->notFound('User not found');
            }
            
            // Validate domain ownership
            $this->validateDomainOwnership($user);
            
            // Get user permissions
            $permissions = $user->getPermissionNames();
            
            $this->success([
                'user' => $user,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Create new user
     * POST /api/users
     */
    protected function store()
    {
        $this->requirePermission('user_add');
        
        // Validate required fields
        $errors = $this->validateRequired([
            'username',
            'password'
        ]);
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            // Create user
            $user = User::create([
                'user_uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'domain_uuid' => $this->domain_uuid,
                'username' => $this->param('username'),
                'password' => password_hash($this->param('password'), PASSWORD_DEFAULT),
                'user_enabled' => $this->param('user_enabled', 'true'),
                'user_status' => $this->param('user_status', 'Available'),
                'user_language' => $this->param('user_language'),
                'user_time_zone' => $this->param('user_time_zone'),
                'contact_uuid' => $this->param('contact_uuid'),
            ]);
            
            // Assign groups if provided
            if ($groupIds = $this->param('groups')) {
                if (is_array($groupIds)) {
                    $user->groups()->attach($groupIds);
                }
            }
            
            $this->created($user, 'User created successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Update user
     * PUT /api/users/{id}
     */
    protected function update($id)
    {
        $this->requirePermission('user_edit');
        
        if (!$id) {
            $this->badRequest('User ID required');
        }
        
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->notFound('User not found');
            }
            
            // Validate domain ownership
            $this->validateDomainOwnership($user);
            
            // Update fields
            $fillable = ['username', 'user_enabled', 'user_status', 
                        'user_language', 'user_time_zone', 'contact_uuid'];
            
            foreach ($fillable as $field) {
                if ($this->param($field) !== null) {
                    $user->$field = $this->param($field);
                }
            }
            
            // Update password if provided
            if ($password = $this->param('password')) {
                $user->password = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $user->save();
            
            // Update groups if provided
            if ($groupIds = $this->param('groups')) {
                if (is_array($groupIds)) {
                    $user->groups()->sync($groupIds);
                }
            }
            
            $this->success($user, 'User updated successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Delete user
     * DELETE /api/users/{id}
     */
    protected function destroy($id)
    {
        $this->requirePermission('user_delete');
        
        if (!$id) {
            $this->badRequest('User ID required');
        }
        
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->notFound('User not found');
            }
            
            // Validate domain ownership
            $this->validateDomainOwnership($user);
            
            // Detach groups
            $user->groups()->detach();
            
            // Delete user
            $user->delete();
            
            $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get user permissions
     * GET /api/users/{id}/permissions
     */
    public function permissions($id)
    {
        $this->requireAuth();
        $this->requirePermission('user_view');
        
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->notFound('User not found');
            }
            
            $this->validateDomainOwnership($user);
            
            $permissions = $user->getAllPermissions();
            $permissionNames = $user->getPermissionNames();
            
            $this->success([
                'permissions' => $permissions,
                'permission_names' => $permissionNames,
                'is_admin' => $user->isAdmin(),
                'is_superadmin' => $user->isSuperAdmin()
            ]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Get user settings
     * GET /api/users/{id}/settings
     */
    public function settings($id)
    {
        $this->requireAuth();
        $this->requirePermission('user_view');
        
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->notFound('User not found');
            }
            
            $this->validateDomainOwnership($user);
            
            $settings = $user->enabledSettings;
            
            $this->success(['settings' => $settings]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Update user setting
     * PUT /api/users/{id}/settings
     */
    public function updateSetting($id)
    {
        $this->requireAuth();
        $this->requirePermission('user_edit');
        
        $errors = $this->validateRequired([
            'category',
            'subcategory',
            'name',
            'value'
        ]);
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->notFound('User not found');
            }
            
            $this->validateDomainOwnership($user);
            
            $setting = $user->setSetting(
                $this->param('category'),
                $this->param('subcategory'),
                $this->param('name'),
                $this->param('value')
            );
            
            $this->success($setting, 'Setting updated successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
}
