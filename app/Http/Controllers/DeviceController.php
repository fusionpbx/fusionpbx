<?php

namespace App\Http\Controllers;

use App\Repositories\DeviceRepository;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    protected $deviceRepository;

    public function __construct(DeviceRepository $deviceRepository)
    {
        $this->deviceRepository = $deviceRepository;
    }


    public function index()
    {
        return view('pages.devices.index');
    }

    public function create()
    {
        return view('pages.devices.form');
    }

    public function edit(string $uuid)
    {
        $device = $this->deviceRepository->findByUuid($uuid);
        $deviceUuid = $device->device_uuid;
        
        return view('pages.devices.form', compact('deviceUuid'));
    }

    public function import()
    {
        return view('pages.devices.import');
    }

    public function export()
    {
        return view('pages.devices.export');
    }

    
}
