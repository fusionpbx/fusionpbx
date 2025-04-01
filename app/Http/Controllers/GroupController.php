<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Domain;
use App\Http\Requests\GroupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $domains = Domain::all();

        return view('pages.groups.form', compact('domains'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GroupRequest $request)
    {
        Group::create($request->validated());

        return redirect()->route('groups.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Group $group)
    {
        $domains = Domain::all();

        return view('pages.groups.form', compact('group', 'domains'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GroupRequest $request, Group $group)
    {
        $group->update($request->all());

        return redirect()->route('groups.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group)
    {
        if($group->group_protected){
            return redirect()->route('groups.index')->with('error', 'You cannot delete a protected group');
        }
 
        $group->delete();
        return redirect()->route('groups.index');
    }

    public function copy(Group $group)
    {
        try {
            DB::beginTransaction();
            if(auth()->user()->hasPermission('group_add')){
                $newGroup = $group->replicate();
                $newGroup->group_uuid = Str::uuid();
                $newGroup->group_name = $group->group_name;
                $newGroup->group_description = $group->group_description . ' (Copy)';
                $newGroup->save();
        
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
                return redirect()->route('groups.index')->with('success', 'Group copied successfully!');
                
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            return redirect()->route('groups.index')->with('error', 'Failed to copy group: ' . $e->getMessage());
        }


    }
}
