<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeviceProfileController extends Controller
{
    public function index()
    {
        return view('device-profile.index');
    }
}
