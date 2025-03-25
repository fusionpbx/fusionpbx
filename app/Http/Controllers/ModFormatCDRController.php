<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\DomainSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;



class ModFormatCDRController extends Controller
{
    public function store(Request $request){
        if(App::hasDebugModeEnabled()){
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] input: '.print_r($request->toArray(), true));
        }

        // TODO: Check for Authentication

        // Detect Format
        $default_settings = new DefaultSettingController;
        $format = $default_settings->get('config', 'format_cdr.format', 'text') ?? 'xml';
    }
}
