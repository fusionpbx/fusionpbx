<?php

namespace App\Repositories;

use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupRepository
{
    protected $model;
    

    public function __construct(Group $group)
    {
        $this->model = $group;
    }
    
    public function getAll()
    {
        return $this->model->all();
    }
    

    public function create(array $data)
    {
        return $this->model->create($data);
    }
    

    public function update(Group $group, array $data)
    {
        return $group->update($data);
    }
    

    public function delete(Group $group)
    {
        if ($group->group_protected) {
            throw new \Exception('You cannot delete a protected group');
        }
        
        return $group->delete();
    }
    
    public function copy(Group $group)
    {
        try {
            DB::beginTransaction();
            
            // Replicar el grupo base
            $newGroup = $group->replicate();
            $newGroup->group_uuid = Str::uuid();
            $newGroup->group_name = $group->group_name;
            $newGroup->group_description = $group->group_description . ' (Copy)';
            $newGroup->save();
            
            // Copiar los permisos asociados
            $permissions = $group->permissions()->get();
            $permissionsToSync = [];
            
            foreach ($permissions as $permission) {
                $permissionsToSync[$permission->permission_name] = [
                    'group_permission_uuid' => Str::uuid(),
                    'permission_assigned' => $permission->pivot->permission_assigned,
                    'permission_protected' => $permission->pivot->permission_protected
                ];
            }
            
            $newGroup->permissions()->sync($permissionsToSync);
            
            DB::commit();
            return $newGroup;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function userHasPermission($permissionName)
    {
        return auth()->user()->hasPermission($permissionName);
    }
}