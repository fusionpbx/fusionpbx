<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessControlController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() : mixed
    {
        return view('pages.accessControl.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        return view('pages.accessControl.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AccessControl $accessControl)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($uuid) : View
    {
        $accessControl = AccessControl::with('accesscontrolnodes')->where('access_control_uuid', $uuid)->firstOrFail();
        $accessControlUuid = $accessControl->access_control_uuid;

        return view('pages.accessControl.form', compact('accessControl', 'accessControlUuid'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccessControl $accessControl)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid) : RedirectResponse
    {


        $accessControl = AccessControl::where('access_control_uuid', $uuid)->firstOrFail();
        try {
            DB::beginTransaction();

            AccessControlNode::whereIn('access_control_uuid', $accessControl)->delete();

            AccessControl::whereIn('access_control_uuid', $accessControl)->delete();

            DB::commit();

            return redirect()->route('accesscontrol.index');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function copy($uuid) : RedirectResponse
    {
        $originalAccessControl = AccessControl::where('access_control_uuid', $uuid)->firstOrFail();
        
        try {
            DB::beginTransaction();

            $newAccessControl = $originalAccessControl->replicate();
            $newAccessControl->access_control_uuid = Str::uuid();
            $newAccessControl->access_control_description = $newAccessControl->access_control_description . ' (Copy)';
            $newAccessControl->save();

            foreach ($originalAccessControl->accesscontrolnodes as $node) {
                $newNode = $node->replicate();
                $newNode->access_control_node_uuid = Str::uuid();
                $newNode->access_control_uuid = $newAccessControl->access_control_uuid;
                $newNode->save();
            }

            DB::commit();
            return redirect()->route('accesscontrol.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
