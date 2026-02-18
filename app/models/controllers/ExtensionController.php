<?php
/**
 * FusionPBX - Extension API Controller
 * 
 * RESTful API controller for extension management with Eloquent.
 * 
 * @package    FusionPBX
 * @subpackage Controllers
 */

namespace FusionPBX\Controllers;

use FusionPBX\Models\Extension;
use FusionPBX\Models\Voicemail;

class ExtensionController extends BaseController
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
     * List extensions
     * GET /api/extensions
     */
    protected function index()
    {
        $this->requirePermission('extension_view');
        
        try {
            $query = Extension::forDomain($this->domain_uuid);
            
            // Filter by enabled
            if ($this->param('enabled') === 'true') {
                $query->enabled();
            }
            
            // Search
            if ($search = $this->param('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('extension', 'like', "%{$search}%")
                      ->orWhere('effective_caller_id_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Pagination
            $perPage = min($this->param('per_page', 30), 100);
            $page = $this->param('page', 1);
            
            $extensions = $query->with(['voicemail', 'domain', 'users'])
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->orderBy('extension')
                ->get();
            
            $total = Extension::forDomain($this->domain_uuid)->count();
            
            $this->success([
                'extensions' => $extensions,
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
     * Get single extension
     * GET /api/extensions/{id}
     */
    protected function show($id)
    {
        $this->requirePermission('extension_view');
        
        try {
            $extension = Extension::with(['voicemail', 'domain', 'users', 'settings'])
                ->find($id);
            
            if (!$extension) {
                $this->notFound('Extension not found');
            }
            
            $this->validateDomainOwnership($extension);
            
            $this->success(['extension' => $extension]);
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Create new extension
     * POST /api/extensions
     */
    protected function store()
    {
        $this->requirePermission('extension_add');
        
        $errors = $this->validateRequired(['extension']);
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $extension = Extension::create([
                'extension_uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'domain_uuid' => $this->domain_uuid,
                'extension' => $this->param('extension'),
                'number_alias' => $this->param('number_alias'),
                'password' => $this->param('password'),
                'accountcode' => $this->param('accountcode'),
                'effective_caller_id_name' => $this->param('effective_caller_id_name'),
                'effective_caller_id_number' => $this->param('effective_caller_id_number'),
                'outbound_caller_id_name' => $this->param('outbound_caller_id_name'),
                'outbound_caller_id_number' => $this->param('outbound_caller_id_number'),
                'directory_visible' => $this->param('directory_visible', 'true'),
                'directory_exten_visible' => $this->param('directory_exten_visible', 'true'),
                'enabled' => $this->param('enabled', 'true'),
                'description' => $this->param('description'),
            ]);
            
            $this->created($extension, 'Extension created successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Update extension
     * PUT /api/extensions/{id}
     */
    protected function update($id)
    {
        $this->requirePermission('extension_edit');
        
        if (!$id) {
            $this->badRequest('Extension ID required');
        }
        
        try {
            $extension = Extension::find($id);
            
            if (!$extension) {
                $this->notFound('Extension not found');
            }
            
            $this->validateDomainOwnership($extension);
            
            // Update fields
            $fillable = ['extension', 'number_alias', 'password', 'accountcode',
                        'effective_caller_id_name', 'effective_caller_id_number',
                        'outbound_caller_id_name', 'outbound_caller_id_number',
                        'directory_visible', 'directory_exten_visible', 'enabled', 
                        'description'];
            
            foreach ($fillable as $field) {
                if ($this->param($field) !== null) {
                    $extension->$field = $this->param($field);
                }
            }
            
            $extension->save();
            
            $this->success($extension, 'Extension updated successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
    
    /**
     * Delete extension
     * DELETE /api/extensions/{id}
     */
    protected function destroy($id)
    {
        $this->requirePermission('extension_delete');
        
        if (!$id) {
            $this->badRequest('Extension ID required');
        }
        
        try {
            $extension = Extension::find($id);
            
            if (!$extension) {
                $this->notFound('Extension not found');
            }
            
            $this->validateDomainOwnership($extension);
            
            $extension->delete();
            
            $this->success(null, 'Extension deleted successfully');
        } catch (\Exception $e) {
            $this->serverError($e->getMessage());
        }
    }
}
