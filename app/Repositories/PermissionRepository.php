<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository
{

    protected $model;

    public function __construct(Permission $permission)
    {
        $this->model = $permission;
    }
    
    public function getFilteredPermissions(?string $search, ?string $groupUuid, string $filter = 'all'): Collection
    {
        $query = $this->model->query();

        if ($groupUuid) {
            $query->when($filter === 'assigned', function ($q) use ($groupUuid) {
                $q->whereHas('groupPermissions', function ($subQ) use ($groupUuid) {
                    $subQ->where('group_uuid', $groupUuid);
                });
            })
            ->when($filter === 'not_assigned', function ($q) use ($groupUuid) {
                $q->whereDoesntHave('groupPermissions', function ($subQ) use ($groupUuid) {
                    $subQ->where('group_uuid', $groupUuid);
                });
            })
            ->when($filter === 'protected', function ($q) use ($groupUuid) {
                $q->whereHas('groupPermissions', function ($subQ) use ($groupUuid) {
                    $subQ->where('group_uuid', $groupUuid)
                        ->where('permission_protected', true);
                });
            });
        }

        if (!empty($search)) {
            $query->where('permission_name', 'like', '%' . $search . '%');
        }

        return $query
            ->with(['groupPermissionByGroup' => function ($q) use ($groupUuid) {
                $q->where('group_uuid', $groupUuid);
            }])
            ->orderBy('application_name')
            ->orderBy('permission_name')
            ->get();
    }


    public function getUniqueApplicationNames(Collection $permissions): array
    {
        return $permissions->pluck('application_name')->unique()->toArray();
    }


    public function isPermissionAllowed(string $permissionName, ?string $userUuid): bool
    {
        if (!$userUuid) {
            return false;
        }

        return $this->model
            ->whereHas('groups.users', function ($query) use ($userUuid, $permissionName) {
                $query->where('user_uuid', $userUuid)
                    ->where('permission_name', $permissionName);
            })
            ->exists();
    }
}