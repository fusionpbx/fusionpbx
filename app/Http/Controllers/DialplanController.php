<?php

namespace App\Http\Controllers;

use App\Models\Dialplan;
use App\Models\Domain;
use App\Http\Requests\DialplanRequest;
use App\Models\Destination;
use App\Repositories\DialplanDetailRepository;
use App\Repositories\DialplanRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DialplanController extends Controller
{
	protected $dialplanRepository;
	protected $dialplanDetailRepository;

	public function __construct(DialplanRepository $dialplanRepository, DialplanDetailRepository $dialplanDetailRepository)
    {
        $this->dialplanRepository = $dialplanRepository;
        $this->dialplanDetailRepository = $dialplanDetailRepository;
    }

	public function index(Request $request)
	{
		$app_uuid = $request->query("app_uuid");

		return view("pages.dialplans.index", compact("app_uuid"));
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

	public function createInbound(Request $request)
	{
		$app_uuid = $request->query("app_uuid");

		$destinations = Destination::where("domain_uuid", Session::get("domain_uuid"))->get();

		return view("pages.dialplans.inbound.form", compact("app_uuid", "destinations"));
	}

	// public function storeInbound(MenuItemRequest $request)
	// {
    //     $menuItem = $this->dialplanDetailRepository->createInbound($request->validated());

    //     return redirect()->route("menus.edit", [$menuItem->menu_uuid]);
	// }
}
