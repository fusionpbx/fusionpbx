<?php

namespace App\Http\Controllers;

use App\Models\Dialplan;
use App\Http\Requests\DialplanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DialplanController extends Controller
{
	public function index()
	{
		return view("pages.dialplans.index");
	}

	public function create()
	{
		$dialplans = Dialplan::all();

		return view("pages.dialplans.form", compact("dialplans"));
	}

	public function store(DialplanRequest $request)
	{
		Dialplan::create($request->validated());

		return redirect()->route("dialplans.index");
	}

	public function show(Dialplan $dialplan)
	{
		//
	}

	public function edit(Dialplan $dialplan)
	{
		$dialplans = Dialplan::all();

		return view("pages.dialplans.form", compact("dialplan", "dialplans"));
	}

	public function update(DialplanRequest $request, Dialplan $dialplan)
	{
		$dialplan->update($request->validated());

		return redirect()->route("dialplans.index");
	}

	public function destroy(Dialplan $dialplan)
	{
		$dialplan->delete();

		return redirect()->route('dialplans.index');
	}
}
