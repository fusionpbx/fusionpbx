<?php

namespace App\Http\Controllers;

use App\Models\Dialplan;
use App\Models\Domain;
use App\Http\Requests\DialplanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DialplanController extends Controller
{
	public function index()
	{
		return view("pages.dialplans.index");
	}

	public function create()
	{
		$domains = Domain::all();

		return view("pages.dialplans.form", compact("domains"));
	}

	public function store(DialplanRequest $request)
	{
		$dialplan = Dialplan::create($request->validated());

		$this->syncDetails($request, $dialplan);

		return redirect()->route("dialplans.index");
	}

	public function show(Dialplan $dialplan)
	{
		//
	}

	public function edit(Dialplan $dialplan)
	{
		$domains = Domain::all();

		$dialplan->load("dialplandetails");

		return view("pages.dialplans.form", compact("dialplan", "domains"));
	}

	public function update(DialplanRequest $request, Dialplan $dialplan)
	{
		$dialplan->update($request->validated());

		$this->syncDetails($request, $dialplan);

		return redirect()->route("dialplans.index");
	}

	public function destroy(Dialplan $dialplan)
	{
		$dialplan->delete();

		return redirect()->route('dialplans.index');
	}

	private function syncDetails(DialplanRequest $request, Dialplan $dialplan)
	{
		$dialplan_details = array_values($request->input("dialplan_details", []));

		foreach($dialplan_details as $key => $value)
		{
			$dialplan_details[$key]["dialplan_detail_uuid"] = Str::uuid(); //NOTE: won't need in the future
			$dialplan_details[$key]["domain_uuid"] = $dialplan->domain->domain_uuid; //NOTE: won't need in the future
		}

		$dialplan->dialplandetails()->delete();
		$dialplan->dialplandetails()->upsert($dialplan_details, "dialplan_detail_uuid");
	}
}
