<?php

namespace App\Http\Controllers;

use App\Repositories\DeviceVendorRepository;
use Illuminate\Http\Request;

class DeviceVendorController extends Controller
{
    public $deviceVendorRepository;

    public function __construct(DeviceVendorRepository $deviceVendorRepository)
    {
        $this->deviceVendorRepository = $deviceVendorRepository;
    }
    public function index()
    {
        return view('pages.devicevendors.index');
    }

    public function create()
    {
        return view('pages.devicevendors.form');
    }

    public function edit($uuid)
    {
        $deviceVendor = $this->deviceVendorRepository->findVendorByUuid($uuid);
        $vendorUuid = $deviceVendor->device_vendor_uuid;
        return view('pages.devicevendors.form', compact('vendorUuid'));
    }
}
