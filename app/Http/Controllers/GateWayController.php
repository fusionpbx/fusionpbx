<?php

namespace App\Http\Controllers;

use App\Http\Requests\GatewayRequest;
use App\Models\Domain;
use App\Models\Gateway;
use App\Models\SipProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GateWayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): mixed
    {
        return view('pages.gateway.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() : View
    {
        $domains = Domain::all();
        $profiles = SipProfile::all();

        return view('pages.gateway.form', compact('domains', 'profiles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GatewayRequest $request) : RedirectResponse
    {
        Gateway::create($request->validated());

        return redirect()->route('gateways.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Gateway $gateway)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gateway $gateway) : View
    {
        $domains = Domain::all();
        $profiles = SipProfile::all();

        return view('pages.gateway.form', compact('gateway', 'domains', 'profiles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GatewayRequest $request, Gateway $gateway) : RedirectResponse
    {
        $gateway->update($request->all());

        return redirect()->route('gateways.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gateway $gateway) : RedirectResponse
    {
        $gateway->delete();

        return redirect()->route('gateways.index');
    }

    public function copy(Gateway $gateway) : RedirectResponse
    {

        try {
            DB::beginTransaction();
            if (auth()->user()->hasPermission('gateway_add')) {
                $newGateway = $gateway->replicate();
                $newGateway->gateway_uuid = Str::uuid();
                $newGateway->description = $newGateway->description . ' (Copy)';
                $newGateway->save();

                DB::commit();

            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return redirect()->route('gateways.index')->with('success', 'Gateway copied successfully!');
    }
}
