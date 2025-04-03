<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use App\Models\Group;
use App\Models\GroupPermission;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function index(PermissionRequest $request, $groupUuid = null)
    {
        $search = $request->input('search', '');
        $groupUuid = $groupUuid ?? $request->input('group_uuid');
        $filter = $request->input('filter', 'all');
    
        $group = $groupUuid ? Group::findOrFail($groupUuid) : null;
    
        $query = Permission::query();
    
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
    
        $permissions = $query
            ->with(['groupPermissionByGroup' => function ($q) use ($groupUuid) {
                $q->where('group_uuid', $groupUuid);
            }])
            ->orderBy('application_name')
            ->orderBy('permission_name')
            ->get();
    
        $permissionsByApp = $permissions->pluck('application_name')->unique()->toArray();
    
        return view('pages.permission.index', compact(
            'search',
            'permissionsByApp',
            'groupUuid',
            'group',
            'filter',
            'permissions'
        ));
    }

    public function update(Request $request, $groupUuid)
    {
        $group = Group::findOrFail($groupUuid);
        $permissions = $request->input('permissions', []);
        $protectedPermissions = $request->input('permissions_protected', []);
        
        DB::beginTransaction();
        
        try {
            GroupPermission::where('group_uuid', $groupUuid)
                ->whereNotIn('permission_name', $permissions)
                ->delete();
            
            foreach ($permissions as $permissionName) {
                $isProtected = in_array($permissionName, $protectedPermissions) ? 'true' : 'false';

                
                GroupPermission::updateOrCreate(
                    [
                        'group_uuid' => $groupUuid,
                        'permission_name' => $permissionName
                    ],
                    [
                        'permission_assigned' => 'true',
                        'permission_protected' => $isProtected,
                        'domain_uuid' => $group->domain_uuid,
                        'group_name' => $group->group_name
                    ]
                );
            }
                
            DB::commit();
            return redirect()->route('groups.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
