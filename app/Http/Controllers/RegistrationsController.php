<?php

namespace App\Http\Controllers;

use App\Facades\FreeSwitch;
use App\Models\SipProfile;
use App\Services\FreeSwitch\FreeSwitchRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RegistrationsController extends Controller
{
    public function index()
    {
        return view('pages.registrations.index');
    }

}