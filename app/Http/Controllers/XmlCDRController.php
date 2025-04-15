<?php

namespace App\Http\Controllers;

use App\Models\XmlCDR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class XmlCDRController extends Controller
{
    public function index()
    {
        return view('pages.xmlcdr.index');
    }
}
