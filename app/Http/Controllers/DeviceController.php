<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        return view('pages.devices.index');
    }

    public function create()
    {
        return view('pages.devices.form');
    }

    public function edit()
    {
        return view('pages.devices.form');
    }

    
}
