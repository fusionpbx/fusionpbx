<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use App\Repositories\AccessControlRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessControlController extends Controller
{
    protected $accessControlRepository;

    public function __construct(AccessControlRepository $accessControlRepository)
    {
        $this->accessControlRepository = $accessControlRepository;
    }


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
        $accessControl = $this->accessControlRepository->findByUuidWithNodes($uuid);
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
        $this->accessControlRepository->delete($uuid);
        return redirect()->route('accesscontrol.index');
    }

    public function copy($uuid) : RedirectResponse
    {
        $this->accessControlRepository->copy($uuid);
        return redirect()->route('accesscontrol.index');
    }
}
