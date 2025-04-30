<?php

namespace App\Http\Controllers;

use App\Http\Requests\GatewayRequest;
use App\Models\Gateway;
use App\Repositories\GatewayRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class GateWayController extends Controller
{
    protected $gatewayRepository;

    public function __construct(GatewayRepository $gatewayRepository)
    {
        $this->gatewayRepository = $gatewayRepository;
    }

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
        $domains = $this->gatewayRepository->getAllDomains();
        $profiles = $this->gatewayRepository->getAllSipProfiles();

        return view('pages.gateway.form', compact('domains', 'profiles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GatewayRequest $request) : RedirectResponse
    {
        $this->gatewayRepository->create($request->validated());
        
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
        $domains = $this->gatewayRepository->getAllDomains();
        $profiles = $this->gatewayRepository->getAllSipProfiles();

        return view('pages.gateway.form', compact('gateway', 'domains', 'profiles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GatewayRequest $request, Gateway $gateway) : RedirectResponse
    {
        $this->gatewayRepository->update($gateway, $request->all());

        return redirect()->route('gateways.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gateway $gateway) : RedirectResponse
    {
        $this->gatewayRepository->delete($gateway);

        return redirect()->route('gateways.index');
    }

    public function copy(Gateway $gateway) : RedirectResponse
    {
        try {
            DB::beginTransaction();
            if (auth()->user()->hasPermission('gateway_add')) {
                $this->gatewayRepository->copy($gateway);
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return redirect()->route('gateways.index')->with('success', 'Gateway copied successfully!');
    }
}