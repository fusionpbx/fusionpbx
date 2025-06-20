<?php

namespace App\Http\Controllers;

use App\Repositories\DeviceProfileRepository;
use Illuminate\Http\Request;

class DeviceProfileController extends Controller
{
    protected $deviceProfileRepository;

    public function __construct(DeviceProfileRepository $deviceProfileRepository)
    {
        $this->deviceProfileRepository = $deviceProfileRepository;
    }
    public function index()
    {
        return view('pages.devicesprofiles.index');
    }

    public function create()
    {
        return view('pages.devicesprofiles.form');
    }

    public function edit(string $uuid)
    {
        $deviceProfile = $this->deviceProfileRepository->findByUuid($uuid);
        $deviceProfileUuid = $deviceProfile->device_profile_uuid;
        return view('pages.devicesprofiles.form', compact('deviceProfileUuid'));
    }
}
