<?php

namespace App\Http\Controllers;

use App\Models\Dialplan;
use App\Models\Domain;
use App\Http\Requests\DialplanRequest;
use App\Repositories\DialplanRepository;
use Illuminate\Support\Facades\Session;

class DialplanController extends Controller
{
	protected $dialplanRepository;

	public function __construct(DialplanRepository $dialplanRepository)
    {
        $this->dialplanRepository = $dialplanRepository;
    }
	public function index()
	{
		return view("pages.dialplans.index");
	}

	public function create()
	{
        $domains = $this->dialplanRepository->getAllDomains();
        $types = $this->dialplanRepository->getTypesList();
        $dialplan_default_context = $this->dialplanRepository->getDefaultContext(
            request()->input('app_id'),
            Session::get('domain_name')
        );

		return view("pages.dialplans.form", compact("domains", "types", "dialplan_default_context"));
	}

	public function store(DialplanRequest $request)
	{

	}

	public function show(Dialplan $dialplan)
	{
		//
	}

	public function edit(Dialplan $dialplan)
	{
		$domains = Domain::all();

		$dialplan->load("dialplanDetails");

		$types = $this->dialplanRepository->getTypesList();

		$dialplan_default_context = (request()->input('app_id') == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') ? 'public' : Session::get('domain_name');

		return view("pages.dialplans.form", compact("dialplan", "domains", "types", "dialplan_default_context"));
	}

	public function update(DialplanRequest $request, Dialplan $dialplan)
	{

	}

	public function destroy(Dialplan $dialplan)
	{
		$dialplan->delete();

		return redirect()->route('dialplans.index');
	}
}
